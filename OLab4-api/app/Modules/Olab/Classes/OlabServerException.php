<?php
/**
 * Olab AccessDenied custom exception class
 */

namespace Entrada\Modules\Olab\Classes;

use \Exception;

class OlabServerException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct( $message, Exception $previous = null) {

        //if ( gettype( $object_type )  === "string") {
        parent::__construct( $message, 500, $previous);          
        //}
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": $this->message.\n";
    }

    public function customFunction() {
        echo "A custom function for this type of exception\n";
    }
}