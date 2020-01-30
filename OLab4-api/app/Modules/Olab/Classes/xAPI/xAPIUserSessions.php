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
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Models\UserSessiontraces;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Map short summary.
 *
 * ( OLab3 Model_Leap_User_Session class)
 * 
 * Map description.
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIUserSessions extends xAPI
{
    private $object;

    /**
     * Summary of __construct
     * @param Maps $map 
     */
    public function __construct( UserSessions $obj ) {

        parent::__construct();
        $this->object = $obj;    
    }

    public function UserSession() {
        return $this->object;
    }

    public static function getAdminBaseUrl()
    {
        return xAPI::GetUrlBase() . 'reportManager/showReport/';
    }

    public function toxAPIExtensionObject()
    {
        $result = $this->object->toArray();

        // convert time to ISO8601 format
        $result['start_time'] = date('c', $this->object->start_time);

        if ( $this->object->end_time != null ) {
          $result['end_time'] = date('c', $this->object->end_time);          
        }

        $result['id'] = static::getAdminBaseUrl() . $this->object->id;
        $result['internal_id'] = $this->object->id;
        unset($result['user_ip']);

        return $result;
    }

    public static function sendSessionsToLRS( $sessions )
    {
        /** @var UserSessions[] $sessions_array */
        $sessions_array = $sessions->toArray();
        foreach ($sessions_array as $session) {
            self::createSessionStatements($session);
            $session->sendXAPIStatements( $session->Statements() );
        }
    }

    /**
     * @param UserSessions $session
     */
    public static function createSessionStatements( $session )
    {
        //create responses statements
        $responses = $session->UserResponses()->get();
        foreach ($responses as $response) {
            $response->createXAPIStatement();
        }
        //end create responses statements

        /** @var UserSessionTraces[] $session_traces_array */
        $session_traces_array = $session->traces->toArray();

        if (count($session_traces_array) > 0) {

            usort($session_traces_array, function ($a, $b) {
                $al = (int)$a->id;
                $bl = (int)$b->id;
                if ($al == $bl) {
                    return 0;
                }

                return ($al > $bl) ? +1 : -1;
            });

            $session_traces_array[0]->createXAPIStatementInitialized();

            foreach ($session_traces_array as $key => $session_trace) {
                $session_trace->createXAPIStatementArrived();
                $session_trace->createXAPIStatementLaunched();
                $session_trace->createXAPIStatementSuspended();
                $session_trace->createXAPIStatementResumed();

                if (isset($session_traces_array[$key - 1])) {
                    if (!isset($session_traces_array[$key - 2])) {
                        $session_trace->createXAPIStatementUpdated($session_traces_array[$key - 1], false, true);
                    } else {
                        $session_trace->createXAPIStatementUpdated($session_traces_array[$key - 1]);
                    }
                }

                if (!isset($session_traces_array[$key + 1])) {
                    $session_trace->createXAPIStatementCompleted();
                }
            }
        }
    }

    public function sendXAPIStatements()
    {
        $this->sendInternalXAPIStatements();
        $this->sendThirdPartyXAPIStatements();
    }

    public function sendInternalXAPIStatements()
    {
        /** @var LrsStatement[] $lrs_statements */
        $lrs_statements = LrsStatement::New()->get();

        //$lrs_statements = DB_ORM::select('LRSStatement')
        //    ->join('INNER', 'statements')->on('lrs_statement.statement_id', '=', 'statements.id')
        //    ->where('statements.session_id', '=', $this->id)
        //    ->where('lrs_statement.status', '=', LrsStatement::STATUS_NEW)
        //    ->query();

        foreach ($lrs_statements as $lrs_statement) {
            $lrs_statement->sendAndSave();
        }
    }

    public function sendThirdPartyXAPIStatements()
    {
        /** @var Statements[] $statements */
        $statements = DB_ORM::select('Statement')
            ->where('statements.session_id', '=', $this->id)
            ->where('statements.initiator', '<>', Statements::INITIATOR_DEFAULT)
            ->query();

        $lrs_statements = [];
        foreach ($statements as $statement) {
            $new_lrs_statements = $statement->bindLRS();
            $lrs_statements = array_merge($lrs_statements, $new_lrs_statements);
        }

        foreach ($lrs_statements as $lrs_statement) {
            $lrs_statement->sendAndSave();
        }
    }
}