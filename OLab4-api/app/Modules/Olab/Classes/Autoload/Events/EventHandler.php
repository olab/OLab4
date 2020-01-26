<?php

namespace Entrada\Modules\Olab\Classes\Autoload\Events;

use Illuminate\Support\Facades\Log;
use \Exception;
use \DirectoryIterator;
use Entrada\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use \Ds\Map;

// Declare the interface 'iTemplate'
interface iEventHandler
{
    public function fireEvent(...$event_args );
}

/**
 * Access control base class
 *
 * @version 1.0
 * @author wirunc
 */
class EventHandler extends OlabAutoloadBase
{
    const ON_MAP_STARTED = "OnMapStarted";
    const ON_NODE_ARRIVED = "OnNodeArrived";
    const ON_QUESTION_RESPONDED = "OnQuestionResponded";
    const ON_COUNTER_CHANGED = "OnCounterChanged";
    const ON_MAP_COMPLETED = "OnMapCompleted";
    const ON_MAP_RESUMED = "OnCheckpointResumed";

    // event handler map - keyed on event name which points to 
    // array of handlers for that event
    protected $mapEventHandlers;
    protected $eventHandlersDirectory;

    public function __construct() {

        $this->mapEventHandlers = new Map([]);
        $this->eventHandlersDirectory = OlabAutoloadBase::GetAutoLoadBasePath() . "/Events";

        $this->initialize();
    }

    private function initialize() {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            $event_iter = new DirectoryIterator($this->eventHandlersDirectory);

            // build list of events based on directory names
            foreach ($event_iter as $event_info) {

                if ($event_info->isDir() && !$event_info->isDot()) {

                    $event_name = $event_info->getFilename();
                    Log::debug( "Loading event handlers for '" . $event_name . "'" );

                    $this->mapEventHandlers->put( $event_name, [] );

                    $event_directory = $this->eventHandlersDirectory . "/" . $event_name;
                    // iterate through all files in the subdirectory
                    $handler_iter = new DirectoryIterator( $event_directory );

                    foreach ($handler_iter as $handler_info) {

                        // only interested in php files...
                        if (!$handler_info->isDir() && !$handler_info->isDot() && ( $handler_info->getExtension() === "php" ) ) {

                            Log::debug( "Loading handler class '" . $handler_info->getBasename() . "'" );

                            $handler_file_name = $handler_info->getFilename();
                            require_once $event_directory . "/" . $handler_file_name;

                            // the file name MUST be the name of the event handler class in the file
                            $class_name = 'Entrada\Modules\Olab\Classes\Autoload\Events\\' . 
                                $handler_info->getBasename(".php");

                            // add class to the handler map
                            if ( class_exists( $class_name )) {
                                array_push( $this->mapEventHandlers[ $event_name ], new $class_name );
                            }

                        }

                    }

                }
            }        	
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
        }        

    }

    public function FireEvent( $event_name, ...$event_args ) {
        
        // check for registered event handler
        if ( !$this->mapEventHandlers->hasKey( $event_name ) ) {
            Log::error( "Unregisted event handler '" . $event_name . "'" );
            return;
        }

        Log::info( "Firing event '" . $event_name . "' handler" );

        foreach ( $this->mapEventHandlers[ $event_name ] as $handler ) {
            $handler->fireEvent( $event_args );
        }
    }

}