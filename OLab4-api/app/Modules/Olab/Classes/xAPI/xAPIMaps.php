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
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Map short summary.
 *
 * Map description.
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIMaps extends xAPI
{
    private $object;

    /**
     * Summary of __construct
     * @param Maps $obj 
     */
    public function __construct( Maps $obj ) {

        parent::__construct();
        $this->object = $obj;    
    }

    public static function getAdminBaseUrl()
    {
        return xAPI::GetUrlBase() . '/labyrinthManager/global/';
    }

    public function toxAPIExtensionObject()
    {
        $map = $this->object;

        $result = $map->toArray();
        $result['id'] = static::getAdminBaseUrl() . $map->id;
        $result['internal_id'] = $map->id;

        // null out stuff we don't need
        $result['abstract'] = null;

        return $result;
    }

    public function toxAPIObject()
    {
        $map = $this->object;
        $url = static::getAdminBaseUrl() . $map->id;

        $object = array(
            'id' => $url,
            'definition' => array(
                'name' => array(
                    'en-US' => 'map "' . $map->name . '" (#' . $map->id . ')'
                ),
                'description' => array(
                    'en-US' => 'Map description: ' . xAPIStatement::sanitizeString($map->abstract)
                ),
                'type' => 'http://adlnet.gov/expapi/activities/module',
                'moreInfo' => $url,
            ),
        );

        $object['definition']['extensions'][xAPIStatement::getExtensionMapKey()] 
            = $this->toxAPIExtensionObject();

        return $object;
    }
}