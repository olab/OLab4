<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Base Model class that provides common methods and information to all Models.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Base {

    protected static $database_name = DATABASE_NAME;

    // Child models are required to overload these variables.
    protected static $table_name          = null;
    protected static $primary_key         = null;
    protected static $default_sort_column = null;
    protected static $default_sort_order = "ASC";

    public function __construct($arr = NULL) {
        if (is_array($arr)) {
            if (!isset(static::$primary_key) && !empty($arr)) {
                $arr_keys = array_keys($arr);
                static::$primary_key = $arr_keys[0];
            }

            if (!isset($this->default_sort_column)) {
                static::$default_sort_column = static::$primary_key;
            }

            $this->fromArray($arr);
        }
    }

    /**
     * Method returns true if the provided table exists, otherwise returns false.
     *
     * @param string $database
     * @param string $table
     * @return bool
     */
    public function tableExists($database = "", $table = "") {
        global $db;

        if ($database && $table) {
            $query = "SELECT `table_name` FROM `information_schema`.`columns` WHERE `table_schema` = ? AND `table_name` = ? LIMIT 1";

            $result = $db->GetRow($query, array($database, $table));

            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method returns true if the provided column exists in the provided table and database, otherwise returns false.
     *
     * @param string $database
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function columnExists($database = "", $table = "", $column = "") {
        global $db;

        if ($database && $table && $column) {
            $query = "SELECT `column_name` FROM `information_schema`.`columns` WHERE `table_schema` = ? AND `table_name` = ? AND `column_name` = ?";
            $result = $db->GetRow($query, array($database, $table, $column));
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method returns true if the provided index exists in the provided table and database, otherwise returns false.
     *
     * @param string $database
     * @param string $table
     * @param string $index
     * @return bool
     */
    public function indexExists($database = "", $table = "", $index = "") {
        global $db;

        if ($database && $table && $index) {
            $query = "SELECT `index_name` FROM `information_schema`.`statistics` WHERE `table_schema` = ? AND `table_name` = ? AND `index_name` = ?";
            $result = $db->GetRow($query, array($database, $table, $index));
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method returns the MySQL field meta data for the provided field.
     *
     * @param string $database
     * @param string $table
     * @param string $field
     * @return array
     */
    public function fieldMetadata($database = "", $table = "", $field = "") {
        global $db;

        $result = false;

        $database = preg_replace("/[^a-z0-9_]/i", "", $database);
        $table = preg_replace("/[^a-z0-9_]/i", "", $table);

        if ($table && $field) {
            $query = "SHOW FIELDS FROM `" . $database . "`.`" . $table . "` WHERE `Field` = ?";
            $result = $db->GetRow($query, array($field));

            return $result;
        }

        return $result;
    }

    /**
     * Method returns te MySQL Engine being used by the provided database and table.
     *
     * @param string $database
     * @param string $table
     * @return string
     */
    public function getTableEngine($database = "", $table = "") {
        global $db;

        $result = "";
        if ($database && $table) {
            $query = "SELECT `engine` FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?";
            $result = $db->GetOne($query, array($database, $table));
        }

        return $result;
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
                $static_tester = new ReflectionProperty(get_called_class(), $class_var);
                if (!$static_tester->isStatic()) {
                    $arr[$class_var] = $this->$class_var;
                }
            }
        }

        return $arr;
    }

    /**
     * Uses key-value pair to set object values
     * @return Models_Base
     */
    public function fromArray($arr) {
        foreach ($arr as $class_var_name => $value) {
            $this->$class_var_name = $value;
        }

        return $this;
    }

    /**
     * See the fetchRow method for documentation. These two are functionally identical other than the fact
     * fetchAll returns all the results (by using $db->GetAll()) whereas fetchRow only returns the first result.
     *
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return array
     */
    protected static function fetchAll($constraints, $default_method = "=", $default_mode = "AND", $sort_column = "use_default", $sort_order = "ASC", $sort_column2 = "use_default", $sort_order2 = "ASC", $limit = null) {
        global $db;

        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));

            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                $replacements_string = "";

                if (is_array($constraint)) {
                    if (in_array($constraint["key"], $class_vars)) {
                        $key = clean_input($constraint["key"], array("trim", "striptags"));
                    } else {
                        $key = $constraint["key"];
                        if (is_array($key)) {
                            if (strtoupper($key["function"]) == "CONCAT") {
                                if ($key["keys"] && is_array($key["keys"]) && (count($key["keys"]) > 1)) {
                                    $fn_key = function($keys) {
                                        $return = array();

                                        foreach($keys as $k) {
                                            if ($k != " ") {
                                                $return[] = $k;
                                            } else {
                                                $return[] = "' '";
                                            }
                                        }

                                        return $return;
                                    };

                                    $key_str = implode(",", $fn_key($key["keys"]));
                                    $key_str = "CONCAT(" . $key_str . ")";
                                }

                                $key = $key_str;
                            }

                        }
                    }

                    $mode = (isset($constraint["mode"]) && in_array(strtoupper($constraint["mode"]), array("OR", "AND")) ? $constraint["mode"] : $default_mode);
                    $method = (isset($constraint["method"]) && in_array(strtoupper($constraint["method"]), array("=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS", "IN")) ? $constraint["method"] : $default_method);

                    if (strtoupper($method) == "BETWEEN" && is_array($constraint["value"]) && @count($constraint["value"]) == 2) {
                        $value = array(
                            clean_input($constraint["value"][0], array("trim", "striptags")),
                            clean_input($constraint["value"][1], array("trim", "striptags"))
                        );
                    } elseif (strtoupper($method) == "IN" && is_array($constraint["value"]) && @count($constraint["value"]) >= 1) {
                        $value = array();
                        foreach ($constraint["value"] as $constraint_value) {
                            $value[] = clean_input($constraint_value, array("trim", "striptags"));
                            $replacements_string .= ($replacements_string ? ", " : "")."?";
                        }
                    } elseif ($constraint["value"]) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif ($constraint["value"] || $constraint["value"] === "0" || $constraint["value"] === 0) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif ($constraint["value"] === "NULL") {
                        $value = NULL;
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && (in_array($index, $class_vars))) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && (isset($value) || is_null($value)) && ($value || $value === 0 ||  $value === "0" || is_null($value))) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode)).
                        " `".$key."` ".(isset($method) && $method ? $method : $default_method).
                        (isset($method) && $method == "BETWEEN" ? " ? AND ?" : (isset($method) && $method == "IN" ? " (".$replacements_string.")" : " ?"));
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $where[] = $v;
                        }
                    } else {
                        $where[] = $value;
                    }
                }
            }

            if (!empty($where)) {
                if (!in_array($sort_column, $class_vars)) {
                    $sort_column = static::$default_sort_column;
                }

                if ($sort_order == "DESC") {
                    $sort_order = "DESC";
                } else {
                    $sort_order = "ASC";
                }
                
                if (!in_array($sort_column2, $class_vars)) {
                    $sort_column2_used = 0;
                } else {
                    $sort_column2_used = 1;
                }

                if ($sort_order2 == "DESC") {
                    $sort_order2 = "DESC";
                } else {
                    $sort_order2 = "ASC";
                }

                $query = "SELECT * FROM `".static::$database_name."`.`".static::$table_name."` ".$replacements." ORDER BY `".$sort_column."` ".$sort_order . ($sort_column2_used ? ", `" . $sort_column2 . "` " . $sort_order2 : "") . (isset($limit) && $limit > 0 ? " LIMIT " . $limit : "");
                $results = $db->GetAll($query, $where);
                if ($results) {
                    foreach ($results as $result) {
                        $class = get_called_class();

                        $output[] = new $class($result);
                    }
                }
            }
        }

        return $output;
    }

    /**
     * This constraints array can have either of two possible formats, or even a blend of the two.
     * Each element in the array should either be a key-value pair with the key of the array
     * being the field name, and the value being the value in the field, or an array holding
     * at least elements, with the "key" key, and the "value" key, and then the optional
     * inclusion of a "mode" key holding 'AND' or 'OR' which will come before the line in the where statement
     * (only if it is not the first constraint), and a "method" which determines which operator will be used
     * out of the following: "=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS".
     * For an example, here is an array, and the query which it would build:
     */

    /*
       Array:
       $constraints = array(
           array(
               "key"    => "firstname",
               "method" => "LIKE"
               "value"  => "%John%",
           ),
           array(
               "mode"   => "AND",
               "key"    => "lastname",
               "method" => "LIKE"
               "value"  => "%Mc%",
           ),
           array(
               "mode"   => "OR",
               "key"    => "id",
               "method" => "="
               "value"  => "1",
           )
       );

       Query:
            SELECT * FROM `user_data`
            WHERE `firstname` LIKE '%John%'
            AND `lastname` LIKE '%Mc%'
            OR `id` = '1'
    */

    /**
     * It is possible to use the CONCAT function in the where clause of the fetchAll by passing the key as an array in the format:
     * array("function" => "CONCAT", "keys" => array("lastname", "firstname"))
     *
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return bool|Models_Base
     */
    protected static function fetchRow($constraints, $default_method = "=", $default_mode = "AND") {
        global $db;

        $self = false;
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));

            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                $replacements_string = "";

                if (is_array($constraint) && in_array($constraint["key"], $class_vars)) {
                    $mode = (isset($constraint["mode"]) && in_array(strtoupper($constraint["mode"]), array("OR", "AND")) ? $constraint["mode"] : $default_mode);
                    $key = clean_input($constraint["key"], array("trim", "striptags"));
                    $method = (isset($constraint["method"]) && in_array(strtoupper($constraint["method"]), array("=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS", "IN")) ? $constraint["method"] : $default_method);

                    if (strtoupper($method) == "BETWEEN" && is_array($constraint["value"]) && @count($constraint["value"]) == 2) {
                        $value = clean_input($constraint["value"][0], array("trim", "striptags"))." AND ".clean_input($constraint["value"][1], array("trim", "striptags"));
                    } elseif (strtoupper($method) == "IN" && is_array($constraint["value"]) && @count($constraint["value"]) >= 1) {
                        $value = array();
                        foreach ($constraint["value"] as $constraint_value) {
                            $value[] = clean_input($constraint_value, array("trim", "striptags"));
                            $replacements_string .= ($replacements_string ? ", " : "")."?";
                        }
                    } elseif (isset($constraint["value"]) && $constraint["value"]) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif (isset($constraint["value"]) && ($constraint["value"] || $constraint["value"] === "0" || $constraint["value"] === 0)) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && in_array($index, $class_vars)) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && (isset($value) || is_null($value)) && ($value || $value === 0 ||  $value === "0" || is_null($value))) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode)).
                        " `".$key."` ".(isset($method) && $method ? $method : $default_method).
                        (isset($method) && $method == "BETWEEN" ? " ? AND ?" : (isset($method) && $method == "IN" ? " (".$replacements_string.")" : " ?"));
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $where[] = $v;
                        }
                    } else {
                        $where[] = $value;
                    }
                }
            }

            if (!empty($where)) {
                $query = "SELECT * FROM `" . static::$database_name . "`.`" . static::$table_name . "` " . $replacements;
                $result = $db->GetRow($query, $where);
                if ($result) {
                    $class = get_called_class();
                    $self = new $class($result);
                }
            }
        }

        return $self;
    }

    public function insert() {
        global $db;
        if ($db->AutoExecute("`" . static::$database_name . "`.`" . static::$table_name . "`", $this->toArray(), "INSERT")) {
            $this->{static::$primary_key} = $db->Insert_ID();

            return $this;
        } else {
            application_log("error", "Error inserting a ".get_called_class().". DB Said: " . $db->ErrorMsg());

            echo $db->ErrorMsg();

            return false;
        }
    }

    public function update () {
        global $db;

        if ($db->AutoExecute("`" . static::$database_name . "`.`" . static::$table_name . "`", $this->toArray(), "UPDATE", "`" . static::$primary_key . "` = " . $db->qstr($this->{static::$primary_key}))) {
            return $this;
        } else {
            application_log("error", "Error updating  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());

            return false;
        }
    }

    public function startTransaction() {
        global $db;

        if ($response = $db->StartTrans()) {
            return $response;
        }

        return false;
    }

    public function completeTransaction() {
        global $db;

        if ($response = $db->CompleteTrans()) {
            return $response;
        }

        return false;
    }
}
