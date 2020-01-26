<?php

/**
 * xAPIHandler short summary.
 *
 * xAPIHandler description.
 *
 * @version 1.0
 * @author wirunc
 */

namespace Entrada\Modules\Olab\Classes\xAPI;

use \Exception;
use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;

use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Maps;

use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Models\Lrs;

use \TinCan\RemoteLRS;
use \TinCan\LRSResponse;

/**
 * Class1 short summary.
 *
 * (OLab3 Model_Leap_Base class)
 *
 * @version 1.0
 * @author wirunc
 */
class xAPI
{
    private $lrs_record = null;
    private $lrs = null;
    //const URL_BASE = "http://demo.olab.ca/";

    public static function GetUrlBase() {
      return HostSystemApi::getRootUrl();
    }

    public function __construct() {
        $this->lrs_record = Lrs::Active()->first();
    }

    public function LrsConfiguration() {
        return $this->lrs_record;
    }

    public function LrsServer() {
        return $this->lrs;
    }

    public function HaveLrs() {
        return $this->lrs_record != null;
    }

    //static public function base($full) {
    //    return $_SERVER['HTTP_HOST'] . '/';
    //}

    /**
     * Create LRS connection object
     */
    protected function Connect() {
        
        $this->lrs = new RemoteLRS( $this->lrs_record->url, 
                                    $this->lrs_record->getAPIVersionName(), 
                                    $this->lrs_record->username,
                                    $this->lrs_record->password );
    }

    protected function transmitToLrs( $data ) {
        /** @var LRSResponse $response */
        $response = $this->LrsServer()->saveStatement($data);       
        return $response;
    }


}