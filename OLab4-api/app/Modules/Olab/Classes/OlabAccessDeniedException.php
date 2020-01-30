<?php
/**
 * Olab AccessDenied custom exception class
 */

namespace Entrada\Modules\Olab\Classes;

use \Exception;

class OlabAccessDeniedException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {

        // some code
        if ( gettype( $message ) === "string") {
          parent::__construct("Access denied: '$message' id = $code", 404, $previous);          
        }
        else {
          $type = basename(str_replace('\\', '/', get_class($message)));
          parent::__construct("Access Denied: '$type' id = $code", 404, $previous);                    
        }

    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function customFunction() {
        echo "A custom function for this type of exception\n";
    }
}