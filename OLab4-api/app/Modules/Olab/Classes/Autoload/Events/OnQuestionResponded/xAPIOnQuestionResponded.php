<?php

/**
 * This is the default system handler for this event.  Do not change
 */

namespace Entrada\Modules\Olab\Classes\Autoload\Events;

use Illuminate\Support\Facades\Log;
use \Exception;
use \DirectoryIterator;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\xAPI\xAPI;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMaps;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapNodes;
use Entrada\Modules\Olab\Classes\xAPI\xAPIQuestions;
use Entrada\Modules\Olab\Classes\xAPI\xAPIStatement;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Models\UserState;

use \Ds\Map;

/**
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIOnQuestionResponded implements iEventHandler
{
    public function __construct() {

        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    }

    /**
     * Summary of fireEvent
     * @param array $event_args ([0][0][0] = sessionId, [0][0][1] = MapNodes, [0][0][2] = QuestionResponses)
     */
    public function fireEvent(...$event_args )
    {
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {            
            Log::debug( "Event handler for '" . __CLASS__ . "' fired." );

            // get the current user
            $user_id = HostSystemApi::getUserId();

            $oState = $event_args[0][0][0];

            /** @var MapNodes $oMapNode */
            $oMapNode = $event_args[0][0][1];

            /** @var QuestionResponses $oResponse */
            $oResponse = $event_args[0][0][2];

            /** var Questions $oQuestion **/
            $oQuestion = $oResponse->Question()->first();

            /** @var Maps $oMap */
            $oMap = $oMapNode->Map()->first();

            if ( $oMap->send_xapi_statements ) {
                $this->buildOnQuestionResponded( $oState, $oMapNode, $oQuestion, $oResponse );
            }
            
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }
 
    }

    /**
     * Summary of buildOnNodeArrived
     * @param int $session_id 
     * @param MapNodes $oMapNode 
     * @param Questions $oQuestion 
     * @param QuestionResponses $oResponse 
     */
    private function buildOnQuestionResponded( UserState $oState, MapNodes $oMapNode, Questions $oQuestion, QuestionResponses $oResponse )
    {
        $timestamp = time();

        //verb
        $verb = array(
            'id' => 'http://adlnet.gov/expapi/verbs/responded',
            'display' => array(
                'en-US' => 'responded'
            ),
        );

        $xapi = new xAPIQuestions( $oQuestion );

        /** var array $xapi **/
        $xapi_object = $xapi->toxAPIObject();

        $result = array(
            'response' => $oResponse->response,
        );

        //context
        $context = array();
        $node_url = OLabUtilities::base(true) . 'nodeManager/editNode/' . $oMapNode->id;
        $context['contextActivities']['parent'][]['id'] = $node_url;

        $map_url = OLabUtilities::base(true) . 'labyrinthManager/global/' . $oMapNode->map_id;
        $context['contextActivities']['grouping'][]['id'] = $map_url;

        //$webinar_id = $session->webinar_id;
        //if (!empty($webinar_id)) {
        //    $webinar_url = xAPI::getAdminBaseUrl() . 'webinarManager/edit/' . $webinar_id;
        //    $context['contextActivities']['parent'][]['id'] = $webinar_url;
        //}

        $xapi_statement = xAPIStatement::create($oState, $verb, $xapi_object, $result, $context, $timestamp);
        $xapi_statement->send();


    }
}

