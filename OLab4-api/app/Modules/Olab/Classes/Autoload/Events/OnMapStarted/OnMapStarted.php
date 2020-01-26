<?php

/**
 * This is the default system handler for this event.  Do not change
 */

namespace Entrada\Modules\Olab\Classes\Autoload\Events;

use Illuminate\Support\Facades\Log;
use \Exception;
use \DirectoryIterator;
use Entrada\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\xAPI\xAPIMaps;
use Entrada\Modules\Olab\Classes\xAPI\xAPIStatement;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use \Ds\Map;

/**
 *
 * @version 1.0
 * @author wirunc
 */
class OnMapStarted implements iEventHandler
{
    public function __construct() {

        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    }

    /**
     * Summary of fireEvent
     * @param array $event_args ([0][0][0] = sessionId, [0][0][1] = MapNode)
     */
    public function fireEvent(...$event_args )
    {
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        Log::debug( "Event handler for '" . __CLASS__ . "' fired." );
    }

    private function buildOnSessionInitialized( Maps $oMap )
    {
        $xAPIMap = new xAPIStatement();

    }
}