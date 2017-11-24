<?php
/**
 * Model to logging and statistics collection.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Models_Logger {

    private $level,
        $message,
        $action,
        $action_field,
        $action_value,
        $timestamp,
        $prune_date,
        $ip_address,
        $url,
        $file,
        $proxy_id;

    public function __construct($arr = NULL) {
        if (is_array($arr)) {
            $this->fromArray($arr);
        }
    }

    /**
     * Returns objects values in an array.
     * @return Array
     */
    public function toArray() {
        $arr = false;
        $class_vars = get_class_vars(get_called_class());
        if (isset($class_vars)) {
            foreach ($class_vars as $class_var => $value) {
                $arr[$class_var] = $this->$class_var;
            }
        }
        return $arr;
    }

    /**
     * Uses key-value pair to set object values
     * @return Models_Logger
     */
    public function fromArray($arr) {
        foreach ($arr as $class_var_name => $value) {
            $this->$class_var_name = $value;
        }
        return $this;
    }

    /**
     * Clears all class var values.
     * @return Boolean
     */
    private function clear() {
        $class_vars = get_class_vars(get_called_class());
        if (isset($class_vars)) {
            foreach ($class_vars as $class_var => $value) {
                $this->$class_var = NULL;
            }
        }
        return true;
    }

    /**
     * Logs information to DB.
     * @global type $db
     * @return Models_Logger
     */
    public function log($msg, $action, $action_field, $action_value, $level = 1, $file = NULL, $user_id = NULL) {
        global $db, $_SERVER;

        $prune_dates = array("1" => "0",
            "2" => "+7 years",
            "3" => "+3 years",
            "4" => "+1 year");

        $this->level        = $level;
        $this->message      = $msg;
        $this->action       = $action;
        $this->action_field = $action_field;
        $this->action_value = $action_value;
        $this->timestamp    = time();
        $this->prune_date   = $this->level == 1 ? "0" : strtotime(date("Y-m-d", $this->timestamp) . " " . $prune_dates[$this->level]);
        $this->proxy_id     = $user_id;
        $this->ip_address   = $_SERVER['REMOTE_ADDR'];
        $this->url          = $_SERVER['REQUEST_URI'];
        $this->file         = $file;

        if ($db->AutoExecute("statistics_logging", $this->toArray(), "INSERT")) {
            $this->clear();
            return true;
        } else {
            return false;
        }

    }

}

?>
