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
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Statements;
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\xAPI\xAPICustomStatement;
use \DateTime;
use \TinCan\RemoteLRS;

/**
 * Class1 short summary.
 *
 * (OLab3 Model_Leap_Map_Node class)
 *
 * @version 1.0
 * @author wirunc
 */
class xAPIMapNodes extends xAPI
{
    private $object;

    /**
     * Summary of __construct
     * @param Maps $map 
     */
    public function __construct( MapNodes $node ) {

        parent::__construct();
        $this->object = $node;    
    }

    public static function getAdminBaseUrl()
    {
        return xAPI::GetUrlBase() . '/nodeManager/editNode/';
    }

    public function toxAPIExtensionObject()
    {
        $node = $this->object;

        $result = $node->toArray();

        unset( $result['MapNodeLinks']);
        unset( $result['Notes']);
        unset( $result['annotation']);
        unset( $result['CounterActions']);
        unset( $result['Notes']);
        unset( $result['questions']);
        unset( $result['constants']);
        unset( $result['files']);
        unset( $result['scripts']);
        $result['text'] = substr( $result['text'], 0, 50 ) . "...";

        $result['id'] = static::getAdminBaseUrl() . $node->id;
        $result['internal_id'] = $node->id;

        return $result;
    }

    public function toxAPIObject()
    {
        $node = $this->object;
        $url = static::getAdminBaseUrl() . $node->id;

        $object = array(
            'id' => $url,
            'definition' => array(
                'name' => array(
                    'en-US' => 'node "' . $node->title . '" (#' . $node->id . ')'
                ),
                'description' => array(
                    'en-US' => 'Node content: ...'
                ),
                'type' => 'http://activitystrea.ms/schema/1.0/node',
                'moreInfo' => $url,
            ),
        );

        $object['definition']['extensions'][xAPIStatement::getExtensionNodeKey()] 
            = $this->toxAPIExtensionObject();

        return $object;
    }

}