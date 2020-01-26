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
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMaps;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapCounter;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMapNodes;
use Entrada\Modules\Olab\Classes\xAPI\xAPIUserSessions;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Class1 short summary.
 *
 * (OLab3 Model_Leap_LRSStatement class)
 *
 * @version 1.0
 * @author wirunc
 */
class xAPILrsStatement extends xAPI
{
    private $object;

    const STATUS_NEW = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;

    public static $statuses = array(
        self::STATUS_NEW => 'New',
        self::STATUS_SUCCESS => 'Successfully sent to LRS',
        self::STATUS_FAIL => 'Failed',
    );

    /**
     * Summary of __construct
     * @param LrsStatement $obj 
     */
    public function __construct(LrsStatement $obj)
    {
        parent::__construct();
        $this->object = $obj;  
    }

    //-----------------------------------------------------
    // Additional helper methods
    //-----------------------------------------------------

    /**
     * @param xAPILrsStatement[] $xapi_lrs_statements
     */
    public static function sendStatementsToLRS($xapi_lrs_statements)
    {
        foreach ($xapi_lrs_statements as $xapi_lrs_statement) {
            $xapi_lrs_statement->sendAndSave();
        }
    }

    public function send()
    {
        $statement = $this->object->Statement();
        $lrs = $this->object->Lrs();

        return $statement->send($lrs);
    }

    public function sendAndSave()
    {
        $result = $this->send();

        $this->object->status = $result ? self::STATUS_SUCCESS : self::STATUS_FAIL;
        $this->object->save();

        return $result;
    }

    public function getStatusName()
    {
        return isset(static::$statuses[$this->object->status]) 
            ? static::$statuses[$this->object->status] 
            : 'unknown';
    }

    //public function save($reload = false)
    //{
    //    $id = $this->id;

    //    if ($id <= 0) {
    //        $this->created_at = time();
    //    }

    //    $this->updated_at = time();

    //    parent::save($reload);
    //}

    //public function insert($reload = false)
    //{
    //    $id = $this->id;
    //    if ($id <= 0) {
    //        $this->created_at = time();
    //    }

    //    $this->updated_at = time();

    //    parent::insert($reload);
    //}

}