<?php
/**
 *
 * Entrada [ http://www.entrada-project.org ]
 *
 * Base Model class that provides common methods and information to all Models.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Base {

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
    protected function fetchAll($constraints, $default_method = "=", $default_mode = "AND", $sort_column = "use_default", $sort_order = "ASC") {
        global $db;
        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                if (is_array($constraint)) {
                    if (in_array($constraint["key"], $class_vars)) {
                        $key = "`".clean_input($constraint["key"], array("trim", "striptags"))."`";
                    } else {
                        $key = $constraint["key"];
                        if (is_array($key)) {
                            if (strtoupper($key["function"]) == "CONCAT") {
                                if ($key["keys"] && is_array($key["keys"]) && count($key["keys"] > 1)) {
                                    $fn_key = function($keys) {
                                        $return = array();
                                        foreach($keys as $k) {
                                            if ($k != " ") {
                                                $return[] = "`".$k."`";
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
                    $method = (isset($constraint["method"]) && in_array(strtoupper($constraint["method"]), array("=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS")) ? $constraint["method"] : $default_method);
                    if (strtoupper($method) == "BETWEEN" && is_array($constraint["value"]) && @count($constraint["value"]) == 2) {
                        $value = array(
                            clean_input($constraint["value"][0], array("trim", "striptags")),
                            clean_input($constraint["value"][1], array("trim", "striptags"))
                        );
                    } elseif ($constraint["value"]) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif ($constraint["value"] || $constraint["value"] === "0" || $constraint["value"] === 0) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && (in_array($index, $class_vars))) {
                    $key = "`".clean_input($index, array("trim", "striptags"))."`";
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && isset($value) && ($value || $value === 0 ||  $value === "0")) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode))." ".$key." ".(isset($method) && $method ? $method : $default_method).(isset($method) && $method == "BETWEEN" ? " ? AND ?" : " ?");
                    if (is_array($value) && @count($value) == 2) {
                        $where[] = $value[0];
                        $where[] = $value[1];
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
                $query = "SELECT * FROM `".static::$table_name."` ".$replacements." ORDER BY `".$sort_column."` ".$sort_order;
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
     *
     * Array:
        $constraints = array(
        array(
        "key"       => "firstname" ,
        "value"     => "%John%",
        "method"    => "LIKE"
        ),
        array(
        "mode"      => "AND",
        "key"       => "lastname" ,
        "value"     => "%Mc%",
        "method"    => "LIKE"
        ),
        array(
        "mode"      => "OR",
        "key"       => "id" ,
        "value"     => "1",
        "method"    => "="
        )
        );

     * Query:
            SELECT * FROM `user_data`
            WHERE `firstname` LIKE '%John%'
            AND `lastname` LIKE '%Mc%'
            OR `id` = '1'
     *
     * It is possible to use the CONCAT function in the where clause of the fetchAll by passing the key as an array in the format:
     * array("function" => "CONCAT", "keys" => array("lastname", "firstname"))
     *
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return bool|Models_Base
     */
    protected function fetchRow($constraints, $default_method = "=", $default_mode = "AND") {
        global $db;

        $self = false;
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                if (is_array($constraint) && in_array($constraint["key"], $class_vars)) {
                    $mode = (isset($constraint["mode"]) && in_array(strtoupper($constraint["mode"]), array("OR", "AND")) ? $constraint["mode"] : $default_mode);
                    $key = clean_input($constraint["key"], array("trim", "striptags"));
                    $method = (isset($constraint["method"]) && in_array(strtoupper($constraint["method"]), array("=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS")) ? $constraint["method"] : $default_method);
                    if (strtoupper($method) == "BETWEEN" && is_array($constraint["value"]) && @count($constraint["value"]) == 2) {
                        $value = clean_input($constraint["value"][0], array("trim", "striptags"))." AND ".clean_input($constraint["value"][1], array("trim", "striptags"));
                    } elseif ($constraint["value"]) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif ($constraint["value"] || $constraint["value"] === "0" || $constraint["value"] === 0) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && in_array($index, $class_vars)) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && isset($value) && ($value || $value === "0" || $value === 0)) {
                    $replacements .= "\n ".(empty($where) ?
                            "WHERE " : (isset($mode) && $mode ?
                                $mode : $default_mode))." `".$key."` ".(isset($method) && $method ?
                            $method : $default_method)." ?";
                    $where[] = $value;
                }
            }

            if (!empty($where)) {
                $query = "SELECT * FROM `".static::$table_name."` ".$replacements;
                $result = $db->GetRow($query, $where);

                if ($result) {
                    $class = get_called_class();
                    $self = new $class($result);
                }
            }
        }

        return $self;
    }
}
