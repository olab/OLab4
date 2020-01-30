<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Lrs;
use Entrada\Modules\Olab\Models\LrsStatement;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Classes\xAPI\xAPIStatement;

class OlabLrsController extends OlabController
{
    /**
     * Get list of maps
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoints_active() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

            $aPayload['data'] = null;

            // run common controller initialization
            $this->initialize();

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::restApiError( $exception );
        }

        return response()->json($aPayload);

    }

    /**
     * Get list of new LRS statements
     * @return \Illuminate\Http\JsonResponse
     */
    public function statements_new() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

            /** @var Lrs $lrs */          
            $lrs = Lrs::Active()->first();

            /** @var LrsStatement[] $lrs_statements */          
            $lrs_statements = $lrs->NewStatements()->with('Statement')->get();

            /** @var LrsStatement $lrs_statement */          
            foreach ($lrs_statements as $lrs_statement) {

                /** @var Statements $statement */          
                $statement = $lrs_statement->Statement()->with('UserSession')->first();      
                
                $xapi = new xAPIStatement( $statement );
                $xapi->send( $lrs, $statement );

                break;
            }
            
            //$aPayload['data'] = $active_lrs_statements;

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

        return response()->json($aPayload);

    }

    /**
     * Get list of maps
     * @return \Illuminate\Http\JsonResponse
     */
    public function statements_transmit() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

            $aPayload['data'] = null;

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::restApiError( $exception );
        }

        return response()->json($aPayload);

    }

}
