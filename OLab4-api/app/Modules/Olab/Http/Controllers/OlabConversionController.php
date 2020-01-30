<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use XMLReader;
use ZipArchive;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConverter;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Files;

class OlabConversionController extends OlabController
{

  const OLAB_STATE_COUNTER_KEY = 'cnt';
  const OLAB_STATE_NODE_KEY = 'nodeId';
  const OLAB_STATE_MAP_KEY = 'mapId';
  const OLAB_STATE_KEY = 'olabstate';

  /**
   * Get list of maps of a specific render version
   * @param {number} map renderer version to query
   * @return \Illuminate\Http\JsonResponse
   */
  public function index( $version ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $version . ")" );

    $aPayload = array();

    try {

      // run common controller initialization
      $this->initialize();

      $version_number = intval( $version );

      // error check the parameter
      if ( $version_number == 0 )
        throw new Exception( $version . " is not a valid version" );

      // get all active maps
      $records = Maps::byVersion( $version )->get(['id', 'name', 'abstract'] );
      $aPayload['data'] = $records;
    }
    catch (Exception $exception) {
      return OlabExceptionHandler::restApiError( $exception );
    }

    return response()->json($aPayload);
  }

  /**
   * Converts a map to the latest schema version
   * @param mixed $map_id 
   * @return \Illuminate\Http\JsonResponse
   */
  public function convert( $map_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $map_id . ")" );

    $aPayload = array();
    $aPayload["id"] = $map_id;

    $oConverter = new OlabConverter();

    try
    {
      // run common controller initialization
      $this->initialize();

      $version_number = intval( $map_id );

      // error check the parameter
      if ( $map_id == 0 )
        throw new Exception( $map_id . " is not a valid number" );

      $oMap = $oConverter->convert( $map_id );

      $aPayload["id"] = $oMap->id;
      $aPayload["name"] = $oMap->name;
      $aPayload["result"] = 1;

    }
    catch (Exception $exception) {
      //return OlabExceptionHandler::restApiError( $exception );
      $aPayload["result"] = 0;

      $location = basename( $exception->getFile() ) . "(" . $exception->getLine() . "): ";
      $message = $location . $exception->getMessage();
      $aPayload["message"] = $message;
      $aPayload["conversionStack"] = $oConverter->GetConversionStack();
      $aPayload["callStack"] = $exception->getTrace();

    }

    return response()->json($aPayload);
  }

  /**
   * Imports a map in MVP format to the latest schema version
   * @param mixed $import_name
   * @return \Illuminate\Http\JsonResponse
   */
  private function import( $import_directory ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $import_directory . ")" );

    $aPayload = array();
    $aPayload['callStack'] = array();

    $oConverter = new OlabConverter();

    try
    {
      $oMap = $oConverter->import( $import_directory );

      if ( $oConverter->HaveImportErrors() ) {

        $aPayload["message"] = "Error";
        $aPayload["result"] = 0;
        $aPayload["errors"] = $oConverter->GetImportErrors();

      }
      else {

        $aPayload["id"] = $oMap->id;
        $aPayload["message"] = "Success";
        $aPayload["name"] = $oMap->name;
        $aPayload["result"] = 1;

      }

    }
    catch (Exception $exception) {

      $aPayload["message"] = "Error";
      $aPayload["result"] = 0;
      $location = basename( $exception->getFile() ) . "(" . $exception->getLine() . "): ";
      $aPayload["errors"][] = $location . $exception->getMessage();
      $aPayload['callStack'] = $exception->getTrace();

    }

    return $aPayload;
  }

  private function get_max_file_size() {

    $max_upload = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
    $pos = strpos($max_upload, "M");

    if ( $pos !== false ) {
  	  $max_upload = str_replace('M', '', $max_upload);
	    $max_upload = $max_upload * 1048576;
    }

    return $max_upload;
  }

  public function upload() {
    
    $errors = [];
    $import_result = array();

    try
    {
      // run common controller initialization
      $this->initialize();

      if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_FILES['files'])) {

          $path = HostSystemApi::getImportRoot();

          $extensions = ['zip'];

          $file_name = basename( $_FILES['files']['name'] );
          $file_tmp = $_FILES['files']['tmp_name'];
          $file_type = $_FILES['files']['type'];
          $file_size = $_FILES['files']['size'];
          $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

          $path = $path . DIRECTORY_SEPARATOR . pathinfo($file_name, PATHINFO_FILENAME);
          $file = $path . DIRECTORY_SEPARATOR . $file_name;

          if (!in_array($file_ext, $extensions)) {
            $errors[] = 'Invalid import file: ' . $file_name;
          }

          $max_filesize = $this->get_max_file_size();
          if ($file_size > $max_filesize ) {
            $errors[] = 'File: ' . $file_name . ' ' . ' size exceeds limit of ' . $max_filesize . " bytes.";
          }

          if (empty($errors)) {

            // test if target directory exists, if not make it
            if (!file_exists($path)) {
              mkdir( $path, 0777, true );
            }

            // error check the path to ensure we have a valid directory
            if ( !file_exists( $path ) )
              $errors[] = $path . " is not a valid directory";

            else {

              // move the uploaded file to the target directory where it will 
              // be extracted
              move_uploaded_file($file_tmp, $file);

              $zip = new ZipArchive;
              $res = $zip->open($file);

              if ($res === TRUE) {

                $zip->extractTo($path);
                $zip->close();

                $import_result = $this->import( $path );

              } else {
                $errors[] = 'Unzip error: ' . $res;
              }

            }
          }
        }
      }        	
    }
    catch (Exception $exception) {
      $aPayload["message"] = "Error";
      $aPayload["result"] = 0;
      $location = basename( $exception->getFile() ) . "(" . $exception->getLine() . "): ";
      $aPayload["errors"][] = $location . $exception->getMessage();
      $aPayload['callStack'] = $exception->getTrace();
    }

    if (!empty($errors)) {
      $import_result["message"] = "Error";
      $import_result["result"] = 0;
      $import_result["errors"] = $errors;
      $import_result["callstack"] = array();
    }

    return response()->json( $import_result );
  }
}