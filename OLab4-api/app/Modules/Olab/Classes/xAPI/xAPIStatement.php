<?php

namespace Entrada\Modules\Olab\Classes\xAPI;

use \Exception;
use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Models\Lrs;
use Entrada\Modules\Olab\Models\LrsStatement;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMaps;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapCounter;
use Entrada\Modules\Olab\Classes\xAPI\xAPIQuestion;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapNodes;
use Entrada\Modules\Olab\Classes\xAPI\xAPIUserSessions;

use \DateTime;
use \TinCan\RemoteLRS;
use \TinCan\LRSResponse;

/**
 * Class1 short summary.
 *
 * (OLab3 Model_Leap_Statement class)
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIStatement extends xAPI
{
    private $object;

    const XAPI_OLAB_PLATFORM = "OLAB4";
    const INITIATOR_DEFAULT = 1;
    const INITIATOR_H5P = 2;
    const INITIATOR_VIDEO_MASHUP = 3;

    const STATUS_NEW = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    private static $initiators = [
        self::INITIATOR_DEFAULT => 'Default',
        self::INITIATOR_H5P => 'H5P',
        self::INITIATOR_VIDEO_MASHUP => 'Video mashup',
    ];

    public $response;

    /**
     * Summary of __construct
     */
    public function __construct( Statements $statement ) {
        parent::__construct();
        $this->object = $statement;
    }

    public function Statement() {
        return $this->object;
    }

    /**
     * @return array
     */
    public static function getInitiators()
    {
        return static::$initiators;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function sanitizeString($string)
    {
        $string = (string)$string;
        $string = strip_tags($string);
        $string = str_replace(PHP_EOL, '', $string);

        return $string;
    }

    /**
     * @return string
     */
    public static function getExtensionCounterKey()
    {
        //return Model_Leap_Map_Counter::getAdminBaseUrl();
        return xAPIMapCounters::getAdminBaseUrl();
    }

    /**
     * @return string
     */
    public static function getExtensionQuestionKey()
    {
        //return Model_Leap_Map_Question::getAdminBaseUrl();
        return xAPIQuestions::getAdminBaseUrl();
    }

    /**
     * @return string
     */
    public static function getExtensionSessionKey()
    {
        //return Model_Leap_User_Session::getAdminBaseUrl();
        return xAPIUserSessions::getAdminBaseUrl();
    }

    /**
     * @return string
     */
    public static function getExtensionNodeKey()
    {
        //return Model_Leap_Map_Node::getAdminBaseUrl();
        return xAPIMapNodes::getAdminBaseUrl();
    }

    /**
     * @return string
     */
    public static function getExtensionMapKey()
    {
        //return Model_Leap_Map::getAdminBaseUrl();
        return xAPIMaps::getAdminBaseUrl();
    }

    /**
     * @param $result
     * @param $object
     * @param $verb
     * @param $context
     * @param UserSessions|
     * @param null|float $timestamp
     * @return xAPIStatement
     */
    public static function create( UserState $oState,
                                   $verb,
                                   $object,
                                   $result = null,
                                   $context = null,
                                   $timestamp = null,
                                   $initiator = null,
                                   $bind_LRS = true ) 
    {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $session_id = $oState->session_id;

        if ( $context == null ) {
          $context = [];
        }

        /** @var xAPIStatement $xapi_statement */
        $xapi_statement = new xAPIStatement( new Statements() );

        // convert sessionId to UserSession record
        if (is_numeric($session_id)) {
            /** @var UserSessions $session_id */
            $oSession = UserSessions::At($session_id);
        }
        else {
          $oSession = $session_id;
        }

        $xapi_session = new xAPIUserSessions( $oSession );

        $xapi_statement->Statement()->session_id = $oSession->id;
        $xapi_statement->Statement()->initiator = ($initiator === null 
                                  ? self::INITIATOR_DEFAULT 
                                  : $initiator);
        //timestamp
        if ($timestamp === null) {
            $timestamp = microtime(true);
        }

        $xapi_statement->Statement()->timestamp = (float)$timestamp;

        $statement_payload = array();

        $statement_payload['timestamp'] = DateTime::createFromFormat('U.u', 
                                                             number_format((float)$xapi_statement->Statement()->timestamp, 6, '.', ''))
                                    ->format('Y-m-d\TH:i:s.u') . 'Z';
        //actor     
        $user_str = HostSystemApi::getUser( $oSession->user_id );

        $statement_payload['actor'] = array( 'objectType' => 'Agent',
                                     'name' => trim($user_str),
                                     'account' => array(
                                        'homePage' => xAPI::GetUrlBase(),
                                        'name' => (string)$oSession->user_id,
                                      ),
                                    );
        //verb
        $statement_payload['verb'] = $verb;

        //object
        $statement_payload['object'] = $object;
        if (!isset($statement_payload['object']['objectType'])) {
            $statement_payload['object']['objectType'] = 'Activity';
        }

        //result
        if (!empty($result)) {
            $statement_payload['result'] = $result;
        }

        //context
        $statement_payload['context']['contextActivities']['category'][]['id'] = 
          xAPIUserSessions::getAdminBaseUrl() . $oSession->id;

        $map_url = xAPIMaps::getAdminBaseUrl() . $oSession->map_id;
        $statement_payload['context']['contextActivities']['parent'][]['id'] = $map_url;

        $webinar_id = $oSession->webinar_id;
        if (!empty($webinar_id)) {
            $webinar_url = xAPI::GetUrlBase() . 'webinarManager/edit/' . $webinar_id;
            $statement_payload['context']['contextActivities']['grouping'][]['id'] = $webinar_url;
        }

        $statement_payload['context']['extensions'][self::getExtensionSessionKey()] = 
          $xapi_session->toxAPIExtensionObject();

        if (is_array($context)) {

            foreach ($context as $key => $value) {

                if ($key !== 'contextActivities') {
                    $statement_payload['context'][$key] = 
                      array_merge($statement_payload['context'][$key], $context[$key]);
                } else {

                    foreach ($value as $contextActivitiesKey => $contextActivities) {
                        if (!is_array($contextActivities)) {
                            $contextActivities = [$contextActivities];
                        }
                        foreach ($contextActivities as $contextActivity) {
                            $statement_payload['context'][$key][$contextActivitiesKey][] = $contextActivity;
                        }
                    }
                }

            }
        }

        $statement_payload['context']['platform'] = self::XAPI_OLAB_PLATFORM;
        $statement_payload['context']['extensions']['context_id'] = $oState->GetValue("contextId");

        $xapi_statement->Statement()->statement = json_encode($statement_payload);
        $xapi_statement->Statement()->save();

        if ($bind_LRS) {
            $xapi_statement->bindLRS();
        }

        return $xapi_statement;
    }

    /**
     * @return LrsStatement
     */
    public function bindLRS()
    {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $lrs_statement = null;

        // get active LRS
        if ( !$this->HaveLrs() )
            return null;

        $lrs_statement = new LrsStatement();

        $lrs_statement->lrs_id = $this->LrsConfiguration()->id;
        $lrs_statement->statement_id = $this->object->id;
        $lrs_statement->status = self::STATUS_NEW;

        $lrs_statement->save();

        return $lrs_statement;
    }

    public function send()
    {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // get active LRS
        if ( !$this->HaveLrs() )
            return null;

        $this->connect();

        $data = json_decode($this->object->statement, true);
        $data['context']['registration'] = $this->object->UserSession()->first()->uuid;

        /** @var \TinCan\LRSResponse $response */
        $response = $this->LrsServer()->saveStatement($data);

        if ($response->success) {
            return true;
        } else {
            $this->response = $response;
            throw new Exception( $response->content );
        }
    }

    public function save()
    {
        $this->object->save();
    }

    public function insert()
    {
        $this->object->save();
    }
}