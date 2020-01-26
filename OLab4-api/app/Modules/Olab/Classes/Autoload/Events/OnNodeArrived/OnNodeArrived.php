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
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\MapNodes;
use \Ds\Map;

/**
 *
 * @version 1.0
 * @author wirunc
 */
class OnNodeArrived implements iEventHandler
{
  public function __construct() {

    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

  }

  public function fireEvent(...$event_args )
  {
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    try 
    {    
      
      // update the server-level node counter (+1) and persist to database
      $oNodeCounter = Counters::AtByName( Counters::SYSTEM_NODE_COUNTER_NAME );
      if ( $oNodeCounter != null ) {

        if ( $oNodeCounter->value != null ) {
          $oNodeCounter->value = $oNodeCounter->value + 1;
        }
        else {
          $oNodeCounter->value = 1;
        }
        $oNodeCounter->save();
      }

    }
    catch (Exception $exception)
    {
      Log::error( __CLASS__ . "::" .  __FUNCTION__ . ": Exception " . $exception->getMessage() );
    }

  }
}