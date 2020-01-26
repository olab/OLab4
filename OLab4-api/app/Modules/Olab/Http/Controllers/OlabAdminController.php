<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\CollectionAccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\ObjectAccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\QuestionsAccessControl;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;

use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Files;

class OlabAdminController extends OlabController
{
    public function info() {         

      // spin up a function tracer.  Handles entry/exit/timing messages
      $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

      $aPayload = array();
      $aPayload['data'] = array();
      $aPayload['data']['version'] = OLabUtilities::get_script_version();
      $aPayload['data']['baseUrl'] = OLabUtilities::get_path_info()['apiBaseUrl'];

      $payload = OLabUtilities::make_api_return( null, $tracer, $aPayload );

      return response()->json($payload);

    }

    /**
     * Get list of users
     * @return \Illuminate\Http\JsonResponse
     */
    public function users() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

          // get all users maps
          $users = HostSystemApi::getUserList();
          $aPayload['data'] = $users;

          Log::debug("found " . sizeof( $users ) . " users" );

        }
        catch (Exception $exception) {
          OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
          $payload = OLabUtilities::make_api_return( $exception, $tracer );
        }

        return response()->json($aPayload);
    }

    /**
     * Get list of roles
     * @return \Illuminate\Http\JsonResponse
     */
    public function roles() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

          $index = 0;

          // get all users maps
          $users = HostSystemApi::getRoleList();
          foreach( $users as &$user) {
            $user->id = $index++;
          }

          $aPayload['data'] = $users;

          Log::debug("found " . sizeof( $users ) . " roles" );

        }
        catch (Exception $exception) {
          OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
          $payload = OLabUtilities::make_api_return( $exception, $tracer );
        }

        return response()->json($aPayload);
    }

    public function test() {
      
      $oObj = Questions::ById( 3431 )->first();
      $oAclObj = AccessControlBase::classFactory( $oObj );

      $oObj = Maps::ById( 5 )->first();
      $oAclObj2 = AccessControlBase::classFactory( $oObj );
    }
}
