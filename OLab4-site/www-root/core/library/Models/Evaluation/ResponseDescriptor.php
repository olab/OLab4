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
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Evaluation_ResponseDescriptor {

    private $erdescriptor_id,
        $organisation_id,
        $descriptor,
        $reportable,
        $order,
        $updated_date,
        $updated_by,
        $active;

    protected static $table_name = "evaluations_lu_response_descriptors";

    public function __construct($arr = NULL) {
        if (is_array($arr)) {
            $this->fromArray($arr);
        }
    }

    public function getID() {
        return $this->erdescriptor_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getDescriptor() {
        return $this->descriptor;
    }

    public function getReportable() {
        return $this->reportable;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getActive() {
        return $this->active;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->erdescriptor_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;
        if (isset($this->erdescriptor_id)) {
            if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`erdescriptor_id` = ".$db->qstr($this->erdescriptor_id))) {
                return $this;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function delete() {
        $this->active = 0;
        return $this->update();
    }

    /**
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return bool|Models_Evaluation_ResponseDescriptor
     */
    private function fetchRow($constraints = array("erdescriptor_id" => "0"), $default_method = "=", $default_mode = "AND", $sort_column = "date_completed", $sort_order = "ASC") {
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
                    $method = $default_method;
                    $mode = $default_mode;
                }
                if (isset($key) && $key && isset($value) && ($value || $value === 0 || $value === "0")) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode))." `".$key."` ".(isset($method) && $method ? $method : $default_method)." ?";
                    $where[] = $value;
                }
            }
            if (!empty($where)) {
                if (!in_array($sort_column, $class_vars)) {
                    $sort_column = "order";
                }
                if ($sort_order == "DESC") {
                    $sort_order = "DESC";
                } else {
                    $sort_order = "ASC";
                }
                $query = "SELECT * FROM `".static::$table_name."` ".$replacements." ORDER BY `".$sort_column."` ".$sort_order;
                $result = $db->GetRow($query, $where);
                if ($result) {
                    $self = new self();
                    $self = $self->fromArray($result);
                }
            }
        }
        return $self;
    }


    /**
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return array
     */
    private function fetchAll($constraints = array("erdescriptor_id" => "0"), $default_method = "=", $default_mode = "AND", $sort_column = "completed_date", $sort_order = "ASC") {
        global $db;
        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                if (is_array($constraint) && in_array($constraint["key"], $class_vars)) {
                    $mode = (isset($constraint["mode"]) && in_array(strtoupper($constraint["mode"]), array("OR", "AND")) ? $constraint["mode"] : $default_mode);
                    $key = "`".clean_input($constraint["key"], array("trim", "striptags"))."`";
                    $method = (isset($constraint["method"]) && in_array(strtoupper($constraint["method"]), array("=", ">", ">=", "<", "<=", "!=", "<>", "BETWEEN", "LIKE", "IS NOT", "IS")) ? $constraint["method"] : $default_method);
                    if (strtoupper($method) == "BETWEEN" && is_array($constraint["value"]) && @count($constraint["value"]) == 2) {
                        $value = array(
                            clean_input($constraint["value"][0], array("trim", "striptags")),
                            clean_input($constraint["value"][1], array("trim", "striptags"))
                        );
                    } elseif ($constraint["value"]) {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } elseif ($constraint["value"] || $constraint["value"] === "0") {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && in_array($index, $class_vars)) {
                    $key = "`".clean_input($index, array("trim", "striptags"))."`";
                    $value = clean_input($constraint, array("trim", "striptags"));
                    $method = $default_method;
                    $mode = $default_mode;
                }
                if (isset($key) && $key && isset($value) && ($value || $value === 0)) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode))." ".$key." ".(isset($method) && $method ? $method : $default_method).($method == "BETWEEN" ? " ? AND ?" : " ?");
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
                    $sort_column = "order";
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
                        $output[] = new self($result);
                    }
                }
            }
        }
        return $output;
    }

    public static function fetchByID($erdescriptor_id, $active = true) {
        $self = new self();

        return $self->fetchRow(array(array("key" => "erdescriptor_id", "value" => $erdescriptor_id)));
    }

    public static function fetchByResponseID($eqresponse_id, $active = true) {
        global $db;

        $self = new self();

        $query = "SELECT a.* FROM `".static::$table_name."` AS a
                    JOIN `evaluation_question_response_descriptors` AS b
                    ON a.`erdescriptor_id` = b.`erdescriptor_id`
                    WHERE b.`eqresponse_id` = ?
                    AND a.`active` = ?";
        $descriptor = $db->GetRow($query, array($eqresponse_id, $active));
        if ($descriptor) {
            $self = $self->fromArray($descriptor);
        }

        return $self;
    }

    public static function fetchAllByOrganisation($organisation_id) {
        $self = new self();

        return $self->fetchAll(array(array("key" => "organisation_id", "value" => $organisation_id), array("key" => "active", "value" => 1)));
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
     * @param array $arr
     * @return Models_Evaluation_ResponseDescriptor
     */
    public function fromArray(array $arr) {
        $class_vars = array_keys(get_class_vars(get_called_class()));
        foreach ($arr as $class_var_name => $value) {
            if (in_array($class_var_name, $class_vars)) {
                $this->$class_var_name = $value;
                unset($arr[$class_var_name]);
            }
        }
        return $this;
    }

}
?>