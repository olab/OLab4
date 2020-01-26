<?php
/**
 * Olab AccessDenied custom exception class
 */

namespace Entrada\Modules\Olab\Classes;

use \Exception;

class OlabObjectNotFoundException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($object_type, $id, Exception $previous = null) {

        //if ( gettype( $object_type )  === "string") {
        parent::__construct("$object_type not found.  Id = $id", 404, $previous);          
        //}
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": $this->message not found.  Id = $this->code\n";
    }

    public function customFunction() {
        echo "A custom function for this type of exception\n";
    }
}