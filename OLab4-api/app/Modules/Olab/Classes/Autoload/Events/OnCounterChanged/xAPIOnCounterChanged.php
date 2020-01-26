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
use Entrada\Modules\Olab\Classes\xAPI\xAPIStatement;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapCounters;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use \Ds\Map;

/**
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIOnCounterChanged implements iEventHandler
{
    public function __construct() {

        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    }

    /**
     * Summary of fireEvent
     * @param array $event_args ([0][0][0] = state, [0][0][1] = Counter )
     */
    public function fireEvent(...$event_args )
    {
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            
            Log::debug( "Event handler for '" . __CLASS__ . "' fired." );

            /** var UserState $oState **/
            $oState = $event_args[0][0][0];

            /** var Counter $oCounter **/
            $aCounter = $event_args[0][0][1];

            $oMap = Maps::At( $oState->map_id );
            if ( $oMap == null ) {
              throw new Exception("unable to find map " . $oState->map_id );
            }
            if ( $oMap->send_xapi_statements ) {
                $this->buildOnCounterChanged( $oState, $oMap, $aCounter );
            }
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
        }
    }

    /**
     * Summary of buildOnNodeArrived
     * @param UserState $oState
     * @param Maps $oMap 
     * @param MapNodes $oNode 
     */
    private function buildOnCounterChanged( UserState $oState, Maps $oMap, $aCounter )
    {
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $timestamp = time();

        //verb
        $verb = array(
            'id' => 'http://w3id.org/xapi/medbiq/verbs/updated',
            'display' => array(
                'en-US' => 'updated'
            ),
        );

        /** var xAPIMaps $xapi **/
        $xapi_counter = new xAPIMapCounters( $aCounter );

        /** var array $xapi_object **/
        $xapi_object = $xapi_counter->toxAPIObject();

        $result = array(
            'score' => array(
                'raw' => $aCounter["value"],
            ),
        );

        //$mainCounter = DB_SQL::select('default')
        //    ->from(Model_Leap_Map_Counter::table())
        //    ->where('status', '=', '1', 'AND')
        //    ->where('map_id', '=', $this->map_id)
        //    ->limit(1)
        //    ->query();

        //if ($mainCounter->is_loaded()) {
        //    if (isset($mainCounter[0])) {
        //        $score_value = $mainCounter[0]['start_value'];
        //    }
        //}
        
        //if (!isset($score_value)) {
        //    $score_value = DB_ORM::model('Map_Counter')->getMainCounterFromSessionTrace($this->as_array());
        //    $score_value = isset($score_value['value']) ? $score_value['value'] : 0;
        //}

        //$result['score']['raw'] = $score_value;

        //$counters = $this->getCountersAsArray();
        //foreach ($counters as $counter_id => $counter_value) {
        //    $counter_base_url = URL::base(true) . 'counterManager/editCounter/';
        //    $counter_url = $counter_base_url . $this->map_id . '/' . $counter_id;
            
        //    // TODO: move initial counter values to the user_sessions table (add new column)
            
        //    /** @var Model_Leap_Map_Counter $counterObj */
        //    $counterObj = DB_ORM::model('Map_Counter', [$counter_id]);
        //    if ($counterObj->is_loaded()) {
        //        $counter_value = $counterObj->start_value;
        //    }
            
        //    $result['extensions'][$counter_base_url][] = [
        //        'id' => $counter_url,
        //        'internal_id' => (string)$counter_id,
        //        'value' => (string)$counter_value,
        //    ];
        //}

        //context
        $context = array();

        $context['extensions'][xAPIStatement::getExtensionNodeKey()] 
            = $xapi_counter->toxAPIExtensionObject();

        $xapi_statement = xAPIStatement::create($oState, $verb, $xapi_object, 
                                                $result, $context, $timestamp);
        $xapi_statement->send();
    }
}

