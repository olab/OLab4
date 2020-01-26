<?php

/**
 * OLabUtilities short summary.
 *
 * OLabUtilities description.
 *
 * @version 1.0
 * @author wirunc
 */

namespace Entrada\Modules\Olab\Classes;

use \Exception;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Models\Options;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\Globals;
use Entrada\Modules\Olab\Models\Courses;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;

class OLabUtilities
{
  const MAX_STACK_DEPTH = 5;

  // update this for every script version
  public static $script_version = "00.34.2020.01.09";

  /**
   * Safe decode string, if base64 or not
   * @param mixed $source 
   * @return mixed
   */
  public static function safe_base64_decode( $source ) {

    if ( base64_encode(base64_decode($source, true)) === $source){
      return base64_decode( $source );
    }

    return $source;

  }

  public static function extract_int( $source, &$target, $default_value = null ) {

    if ( isset( $source )) {
      $target = (int)$source->__toString();
    }
    else {
      if ( $default_value != null ) {
        $target = $default_value;
      }
    }

    return $target;
  }

  public static function safe_rename( &$array, string $source_name, $new_name = null) {
    
    // test if source key is set
    if ( array_key_exists( $source_name, $array )) {

      if ( $new_name != null ) {

        $array[$new_name] = $array[$source_name];
        unset( $array[$source_name] );

      }
      else {
        
        unset( $array[$source_name] );

      }

    }

  }

  /**
   * Concatenates to parts of a path, cleaning up any extra '/'
   * @param string $part1 First part of path
   * @param string $part2 Second part of path
   * @return string
   */
  public static function concat_path( $part1, $part2 ) {

    $part1 = rtrim( $part1, '/' );
    $part2 = ltrim( $part2, '/' );

    return $part1 . '/' . $part2;
  }

  public static function base($full) {
    return $_SERVER['HTTP_HOST'] . '/';
  }

  /**
   * Adds a string to the <HEAD> section
   * @param mixed $html 
   */
  public static function addToHead( $html ) {
    global $HEAD;
    $HEAD[] = $html;
  }

  /**
   * Add a cache-buster parameter
   * @param mixed $file_location 
   * @return string
   */
  public static function get( $file_location = null){

    if ($file_location != null) {

      if (file_exists($file_location)) {

        $filemtime = filemtime($file_location);

      } else {
        $filemtime = time();
      }

    } else {

      $filemtime = time();

    }
    return $file_location.'?v='.$filemtime;
  }

  /**
   * Get paths information
   * @return string[]
   */
  public static function get_path_info()
  {
    return array(
        // e.g. '/var/www/vhosts/OLab4/www-root',
        'siteBaseDir' => HostSystemApi::getFileRoot(),
        // e.g. 'http://olab4.localhost/apidev'
        'siteBaseUrl' => HostSystemApi::getRootUrl(),
        // e.g. '/apidev/api/v2/olab'
        'apiRelativePath' => HostSystemApi::getRelativePath() . '/' . API_BASE_PATH . "/olab",
        // e.g. '/apidev'
        'siteRelativeUrl' => HostSystemApi::getRelativePath(),
        // e.g. 'http://olab4.localhost/apidev/api/v2/olab'
        'apiBaseUrl' => HostSystemApi::getRootUrl() . '/' . API_BASE_PATH . "/olab"
    );    
  }

  public static function make_api_return( $exception = null, $tracer = null, $payload = "", $status = 0, $error_code = 0, $message = "" ) {
    
    $data = array();

    $data['status'] = $status;
    $data['error_code'] = $error_code;
    $data['message'] = "success";      

    if ( strlen( $message ) > 0 ) {
      $data['message'] = $message;      
    }

    $diagnostics = array();

    if ( $exception != null ) {

      $data['message'] = $exception->getMessage();

      if ( $status == null ) {
        $data['status'] = 1;          
      }

      if ( $error_code == null ) {
        $data['error_code'] = $exception->getCode();          
      }       

      $exception_type = get_class( $exception );

      if (strpos( $exception_type, 'NotFoundHttpException') !== false) {
        $data['error_code'] = 404;
      }

      else if (strpos( $exception_type, 'ErrorException') !== false) {
        $data['error_code'] = 500;
      }

      else if (strpos( $exception_type, 'ModelNotFoundException') !== false) {
        $data['error_code'] = 404;
      }

      else if (strpos( $exception_type, 'OlabObjectNotFoundException') !== false) {
        $data['error_code'] = 404;
        $data['message'] = $exception->getMessage();
      }

      else if (strpos( $exception_type, 'OlabAccessDeniedException') !== false) {
        $data['error_code'] = 401;
        $data['message'] = $exception->getMessage();
      }

      if ( $tracer != null ) {
        $diagnostics['location'] = $tracer->sBlockName;      
      }

      $diagnostics['stack'] = $exception->getTrace();      

      // limit stack to only last 5 levels
      if ( sizeof( $diagnostics['stack'] ) > self::MAX_STACK_DEPTH ) {
        $diagnostics['stack'] = array_slice($diagnostics['stack'], 0, self::MAX_STACK_DEPTH);
      }

      // get rid of args from stack - too big
      foreach( $diagnostics['stack'] as &$item ) {
      
        if ( isset( $item['args'])) {
          unset( $item['args'] );
        }
      }

      $diagnostics['file'] = $exception->getFile();
      $diagnostics['line'] = $exception->getLine();
    }

    $data['diagnostics'] = $diagnostics;
    $data['data'] = array();
    if ( $payload != null ) {
      $data['data'] = $payload;      
    }

    return $data;
  }

  /**
   * Provided for OLab3 compatibility
   * @return boolean
   */
  public static function is_ssl()
  {
    if (isset($_SERVER['HTTPS'])) {
      if ('on' == strtolower($_SERVER['HTTPS'])) {
        return true;
      }
      if ('1' == $_SERVER['HTTPS']) {
        return true;
      }
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
      return true;
    }

    return false;
  }

  public static function get_option($option, $default = false, $cast = true)
  {
    $option = trim($option);

    if (empty($option)) {
      return false;
    }

    $record = Options::At( $option );

    if ( $record != null ) {
      $value = $record->value;
    } else { // option does not exist
      return $default;
    }

    if ($cast) {
      if (self::is_serialized($value)) {
        return @unserialize($value);
      } elseif (self::isJSON($value)) {
        return json_decode($value, true);
      }
    }

    return $value;
  }

  /**
   * @param string $string
   * @return bool
   */
  public static function isJSON($string)
  {
    if (!is_string($string)) {
      return false;
    }

    if ($string === '') {
      return false;
    }

    if (!in_array($string{0}, ['[', '{'])) {
      return false;
    }

    return true;
  }

  /**
   * Check value to find if it was serialized.
   *
   * If $data is not an string, then returned value will always be false.
   * Serialized data is always a string.
   *
   * @param string $data Value to check to see if was serialized.
   * @param bool $strict Optional. Whether to be strict about the end of the string. Default true.
   * @return bool False if not serialized and true if it was.
   */
  public static function is_serialized($data, $strict = true)
  {
    // if it isn't a string, it isn't serialized.
    if (!is_string($data)) {
      return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
      return true;
    }
    if (strlen($data) < 4) {
      return false;
    }
    if (':' !== $data[1]) {
      return false;
    }
    if ($strict) {
      $lastc = substr($data, -1);
      if (';' !== $lastc && '}' !== $lastc) {
        return false;
      }
    } else {
      $semicolon = strpos($data, ';');
      $brace = strpos($data, '}');
      // Either ; or } must exist.
      if (false === $semicolon && false === $brace) {
        return false;
      }
      // But neither must be in the first X characters.
      if (false !== $semicolon && $semicolon < 3) {
        return false;
      }
      if (false !== $brace && $brace < 4) {
        return false;
      }
    }
    $token = $data[0];
    switch ($token) {
      case 's' :
        if ($strict) {
          if ('"' !== substr($data, -2, 1)) {
            return false;
          }
        } elseif (false === strpos($data, '"')) {
          return false;
        }
      // or else fall through
      case 'a' :
      case 'O' :
        return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
      case 'b' :
      case 'i' :
      case 'd' :
        $end = $strict ? '$' : '';

        return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
    }

    return false;
  }

  public static function update_option($option, $value, $autoload = false)
  {
    return Model_Leap_Option::update($option, $value, $autoload);
  }

  public static function add_option($option, $value = '', $autoload = false)
  {
    return Model_Leap_Option::set($option, $value, $autoload);
  }

  public static function admin_url($path = '')
  {
    $url = self::base(true);
    if ($path && is_string($path)) {
      $url .= ltrim($path, '/');
    }

    return $url;
  }

  public static function get_script_version( ) {
    
    //$script_version = date("H.i.Y.m.d");
    return self::$script_version;

  }

  public static function get_object_type( $oObj ) {
      
      $type = gettype( $oObj );
      if ( $type != "object") {
          return $type;
      }

      $ancestry = class_parents( $oObj );
      
      $string = get_class( $oObj) . '<-' .
                implode('<-', array_reverse( $ancestry ));

      return $string;

  }

  /**
   * Tests if an object is derived from a named type
   * @param mixed $instance 
   * @param mixed $target_type 
   * @return string
   */
  public static function is_of_type( $instance, $target_type ) {
    
    if ( $instance == null ) {
      return false;
    }

    // test special test if testing for array
    if ( is_array( $instance ) ) {
      return $target_type === "array";
    }

    $string = self::get_object_type( $instance );
    return strpos( $string, $target_type ) !== false;

  }


  /**
   * Summary of get_parent_object
   * @param mixed $oObj source model object 
   * @param mixed $throw 
   * @throws Exception 
   * @return mixed
   */
   public static function get_parent_object( $oObj, $throw = true ) {
    
    $scope_level = $oObj->imageable_type;
    $parent_id = $oObj->imageable_id;

    $oObj = null;
    if ( $scope_level == Servers::IMAGEABLE_TYPE ) {
      $oObj = Servers::find( $parent_id );
    }

    else if ( $scope_level == Maps::IMAGEABLE_TYPE ) {
      $oObj = Maps::find( $parent_id );
    }

    else if ( $scope_level == MapNodes::IMAGEABLE_TYPE ) {
      $oObj = MapNodes::find( $parent_id );
    }

    else if ( $scope_level == Courses::IMAGEABLE_TYPE ) {
      $oObj = Courses::find( $parent_id );
    }

    else if ( $scope_level == Globals::IMAGEABLE_TYPE ) {
      $oObj = Globals::find( $parent_id );
    }

    if ( ( $oObj == null ) && ( $throw ) ) {
      throw new OlabObjectNotFoundException( $scope_level, $parent_id );
    }

    return $oObj;

  }
}