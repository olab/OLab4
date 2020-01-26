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
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Class1 short summary.
 *
 * (OLab3 Model_Leap_Map_Counter class)
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIMapCounters extends xAPI
{
    private $object;

    /**
     * Summary of __construct
     * @param Maps $obj 
     */
    public function __construct( $obj ) {

        parent::__construct();

        // convert object to array
        if ( gettype( $obj ) != "array" ) {
          $obj = $obj->toArray();
        }

        $this->object = $obj;    
    }

    public static function getAdminBaseUrl()
    {
        return xAPI::GetUrlBase() . '/counterManager/editCounter/';
    }

    public function toxAPIExtensionObject()
    {
        $result['id'] = static::getAdminBaseUrl() . '/' . $this->object["id"];
        $result['internal_id'] = $this->object["id"];

        return $result;
    }

    public function toxAPIObject()
    {
        $url = static::getAdminBaseUrl() . 'counterManager/editCounter/' . $this->object["id"];
        $object = array(
            'id' => $url,
            'definition' => array(
                'name' => array(
                    'en-US' => 'counter "' . $this->object["name"] . '" (#' . $this->object["id"] . ')'
                ),
                'description' => array(
                    'en-US' => 'Counter description: ' . xAPIStatement::sanitizeString($this->object["description"])
                ),
                //'type' => 'http://activitystrea.ms/schema/1.0/node',
                'moreInfo' => $url,
            ),
        );

        $object['definition']['extensions'][xAPIStatement::getExtensionCounterKey()] 
          = $this->toxAPIExtensionObject();

        return $object;
    }

}