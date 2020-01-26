<?php
/**
 *
 * Entrada [ http://www.entrada-project.org ]
 *
 * Base Model class that provides common methods and information to all Views.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Views_Deprecated_Base {
    protected $database_name       = DATABASE_NAME;

    // Child models are required to overload these variables.
    protected $table_name               = null;
    protected $default_fieldset         = null;
    protected $primary_key              = null;
    protected $default_sort_column      = null;
    protected $joinable_tables          = null;

    //Built by the constructor
    protected $requested_fields         = null;
    protected $possible_constraints     = null;
    protected $group_by_fields          = null;
    protected $explicit_table_joins     = array();

    public function __construct($fields_array = NULL) {
        if (!isset($this->default_sort_column)) {
            $this->default_sort_column = $this->primary_key;
        }

        $this->requested_fields = array();

        $this->setFields(($fields_array ? $fields_array : $this->default_fieldset));
    }

    protected function addTableJoins($tables_array) {
        if (isset($tables_array) && !is_array($tables_array)) {
            $tables_array = array($tables_array);
        }
        foreach ($tables_array as $table) {
            if (array_key_exists($table, $this->joinable_tables)) {
                if (!array_key_exists($table, $this->requested_fields) && !in_array($table, $this->explicit_table_joins)) {
                    $this->explicit_table_joins[] = $table;
                }
            }
        }
        $this->buildPossibleConstraints();
    }

    protected function setFields($fields_array) {
        global $db;

        $this->requested_fields = array();

        $query = "DESCRIBE `".$this->table_name."`";
        $columns = $db->getAll($query);
        if ($columns) {
            foreach ($columns as $column) {
                if (in_array($column["Field"], $fields_array)) {
                    $this->requested_fields[$this->table_name][$column["Field"]] = $column["Field"];
                }
            }
        }
        foreach ($fields_array as $field) {
            foreach ($this->joinable_tables as $table => $table_data) {
                if (array_key_exists($field, $table_data["fields"])) {
                    if (!array_key_exists($table, $this->requested_fields)) {
                        $this->requested_fields[$table] = array();
                    }
                    $this->requested_fields[$table][$field] = $table_data["fields"][$field];
                }
            }
        }
        $this->buildPossibleConstraints();
    }

    protected function buildPossibleConstraints() {
        global $db;

        $this->possible_constraints = array();
        $query = "DESCRIBE `".$this->database_name."`.`".$this->table_name."`";
        $columns = $db->GetAll($query);
        if ($columns) {
            foreach ($columns as $column) {
                $this->possible_constraints[] = "`".$this->database_name."`."."`".$this->table_name."`."."`".$column["Field"]."`";
            }
        }
        if ($this->requested_fields) {
            foreach (array_keys($this->requested_fields) as $table) {
                $query = "DESCRIBE `" . (isset($this->joinable_tables[$table]["database"]) && $this->joinable_tables[$table]["database"] ? $this->joinable_tables[$table]["database"] : $this->database_name) . "`.`" . $table . "`";
                $columns = $db->GetAll($query);
                if ($columns) {
                    foreach ($columns as $column) {
                        $this->possible_constraints[] = "`" . (isset($this->joinable_tables[$table]["database"]) && $this->joinable_tables[$table]["database"] ? $this->joinable_tables[$table]["database"] : $this->database_name) . "`." . "`" . $table . "`." . "`" . $column["Field"] . "`";
                    }
                }
            }
        }
    }

    protected function buildJoins() {
        $table_joins = "";
        $tables_matrix = array();
        $tables_matrix[$this->table_name] = array("database" => $this->database_name, "fields" => array());
        if (array_key_exists($this->table_name, $this->requested_fields)) {
            foreach ($this->requested_fields[$this->table_name] as $field_alias => $field) {
                $tables_matrix[$this->table_name]["fields"][$field_alias] = $field;
            }
        }
        if (isset($this->explicit_table_joins) && $this->explicit_table_joins) {
            foreach ($this->explicit_table_joins as $table) {
                if (array_key_exists($table, $this->joinable_tables)) {
                    if (!array_key_exists($table, $tables_matrix)) {
                        $tables_matrix[$table] = array("database" => (isset($this->joinable_tables[$table]["database"]) && $this->joinable_tables[$table]["database"] ? $this->joinable_tables[$table]["database"] : $this->database_name), "fields" => array());
                    }
                }
            }
        }
        if (isset($this->requested_fields) && !empty($this->requested_fields)) {
            foreach ($this->requested_fields as $table => $fields) {
                if (array_key_exists($table, $this->joinable_tables)) {
                    if (!array_key_exists($table, $tables_matrix)) {
                        $tables_matrix[$table] = array("database" => (isset($this->joinable_tables[$table]["database"]) && $this->joinable_tables[$table]["database"] ? $this->joinable_tables[$table]["database"] : $this->database_name), "fields" => array());
                    }
                    foreach ($this->requested_fields[$table] as $field_alias => $field) {
                        $tables_matrix[$table]["fields"][$field_alias] = $field;
                    }
                }
            }
        }
        if (count($tables_matrix) > 1) {
            $joined_tables = array();
            foreach ($this->joinable_tables as $table => $join_details) {
                if (array_key_exists($table, $tables_matrix) && !in_array($table, $joined_tables)) {
                    if (isset($join_details["required_tables"]) && is_array($join_details["required_tables"])) {
                        foreach ($join_details["required_tables"] as $required_table) {
                            if (!in_array($required_table, $joined_tables)) {
                                $table_joins .= " " . (isset($this->joinable_tables[$required_table]["left"]) && $this->joinable_tables[$required_table]["left"] ? "LEFT " : "") . "JOIN `" . (isset($this->joinable_tables[$required_table]["database"]) && $this->joinable_tables[$required_table]["database"] ? $this->joinable_tables[$required_table]["database"] : $this->database_name) . "`.`" . $table . "`
                                    ON " . $this->joinable_tables[$required_table]["join_conditions"];
                                $joined_tables[] = $required_table;
                            }
                        }
                    }
                    $table_joins .= " " . (isset($join_details["left"]) && $join_details["left"] ? "LEFT " : "") . "JOIN `" . (isset($join_details["database"]) && $join_details["database"] ? $join_details["database"] : $this->database_name) . "`.`" . $table . "`
                                    ON " . $join_details["join_conditions"];
                    $joined_tables[] = $table;
                }
            }
        }

        return $table_joins;
    }

    protected function buildFieldset () {
        $fieldset = "";
        if ($this->requested_fields && !empty($this->requested_fields)) {
            if (array_key_exists($this->table_name, $this->requested_fields)) {
                foreach ($this->requested_fields[$this->table_name] as $field) {
                    $fieldset .= ($fieldset ? ", " : "")."`".$this->database_name."`.`".$this->table_name."`.`".$field."`";
                }
            }
            foreach ($this->requested_fields as $table => $fields) {
                if (array_key_exists($table, $this->joinable_tables)) {
                    foreach ($this->requested_fields[$table] as $field_alias => $field) {
                        $fieldset .= ($fieldset ? ", " : "")."`".(isset($this->joinable_tables[$table]["database"]) && $this->joinable_tables[$table]["database"] ? $this->joinable_tables[$table]["database"] : $this->database_name)."`.`".$table."`.`".$field."` AS `".$field_alias."`";
                    }
                }
            }
        }
        if (!$fieldset) {
            $fieldset = "`".$this->database_name."`.`".$this->table_name."`.*";
        }
        return $fieldset;
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
    protected function fetchAll($constraints, $default_method = "=", $default_mode = "AND", $sort_column = "use_default", $sort_order = "ASC", $group_by = NULL) {
        global $db;
        $table_joins = $this->buildJoins();
        $fieldset = $this->buildFieldset();
        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                $replacements_string = "";
                if (is_array($constraint)) {
                    if (in_array($constraint["key"], $this->possible_constraints)) {
                        $key = clean_input($constraint["key"], array("trim", "striptags"));
                    } else {
                        $key = $constraint["key"];
                        if (is_array($key)) {
                            if (strtoupper($key["function"]) == "CONCAT") {
                                if ($key["keys"] && is_array($key["keys"]) && count($key["keys"] > 1)) {
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
                } elseif (!is_array($constraint) && (in_array($index, $this->possible_constraints))) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && (isset($value) || is_null($value)) && ($value || $value === 0 ||  $value === "0" || is_null($value))) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode)).
                        " ".$key." ".(isset($method) && $method ? $method : $default_method).
                        ($method == "BETWEEN" ? " ? AND ?" : ($method == "IN" ? " (".$replacements_string.")" : " ?"));
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
                if (!in_array($sort_column, $this->possible_constraints)) {
                    $sort_column = $this->default_sort_column;
                }
                if ($sort_order == "DESC") {
                    $sort_order = "DESC";
                } else {
                    $sort_order = "ASC";
                }
                $query = "SELECT ".$fieldset." FROM `".$this->database_name."`.`".$this->table_name."` ".$table_joins." ".$replacements . (!is_null($group_by) ? "GROUP BY " . $group_by : "") . " ORDER BY ".$sort_column." ".$sort_order;
                $output = $db->GetAll($query, $where);
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
                "value"  => "%John%",
                "method" => "LIKE"
            ),
            array(
                "mode"   => "AND",
                "key"    => "lastname",
                "value"  => "%Mc%",
                "method" => "LIKE"
            ),
            array(
                "mode"   => "OR",
                "key"    => "id",
                "value"  => "1",
                "method" => "="
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
    protected function fetchRow($constraints, $default_method = "=", $default_mode = "AND", $sort_column = "use_default", $sort_order = "ASC", $group_by = NULL) {
        global $db;
        $table_joins = $this->buildJoins();
        $fieldset = $this->buildFieldset();
        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                $replacements_string = "";
                if (is_array($constraint)) {
                    if (in_array($constraint["key"], $this->possible_constraints)) {
                        $key = clean_input($constraint["key"], array("trim", "striptags"));
                    } else {
                        $key = $constraint["key"];
                        if (is_array($key)) {
                            if (strtoupper($key["function"]) == "CONCAT") {
                                if ($key["keys"] && is_array($key["keys"]) && count($key["keys"] > 1)) {
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
                } elseif (!is_array($constraint) && (in_array($index, $this->possible_constraints))) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && (isset($value) || is_null($value)) && ($value || $value === 0 ||  $value === "0" || is_null($value))) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode)).
                        " ".$key." ".(isset($method) && $method ? $method : $default_method).
                        ($method == "BETWEEN" ? " ? AND ?" : ($method == "IN" ? " (".$replacements_string.")" : " ?"));
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
                if (!in_array($sort_column, $this->possible_constraints)) {
                    $sort_column = $this->default_sort_column;
                }
                if ($sort_order == "DESC") {
                    $sort_order = "DESC";
                } else {
                    $sort_order = "ASC";
                }
                $query = "SELECT ".$fieldset." FROM `".$this->database_name."`.`".$this->table_name."` ".$table_joins." ".$replacements. (!is_null($group_by) ? "GROUP BY " . $group_by : "") . " ORDER BY ".$sort_column." ".$sort_order;
                $output = $db->GetRow($query, $where);
            }
        }
        return $output;
    }
}