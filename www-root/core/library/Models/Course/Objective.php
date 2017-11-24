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
 * A class to handle course objectives.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Models_Course_Objective {

    private $cobjective_id,
            $course_id,
            $objective_id,
            $importance = 1,
            $objective_type,
            $objective_details,
            $objective_start,
            $objective_finish,
            $updated_date,
            $updated_by,
            $active = 1;
    
    protected static $table_name = "course_objectives";
    protected static $default_sort_column = "cobjective_id";
    protected static $primary_key = "cobjective_id";
    
    public function __construct($arr = NULL) {
        if (is_array($arr)) {
            $this->fromArray($arr);
        }
    }

    public function getID() {
        return $this->cobjective_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getImportance() {
        return $this->importance;
    }

    public function getObjectiveType() {
        return $this->objective_type;
    }

    public function getObjectiveDetails() {
        return $this->objective_details;
    }

    public function getObjectiveStart() {
        return $this->objective_start;
    }

    public function getObjectiveFinish() {
        return $this->objective_finish;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getActive() {
        return $this->active = 1;
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
     * @return Models_Form
     */
    public function fromArray($arr) {
        $class_vars = array_keys(get_class_vars(get_called_class()));
        foreach ($arr as $class_var_name => $value) {
            if (in_array($class_var_name, $class_vars)) {
                $this->$class_var_name = $value;
            }
        }
        return $this;
    }
    
    /**
     * @param array $constraints
     * @param string $default_method
     * @param string $default_mode
     * @return bool|Models_User_Physician_Bio
     */
    private function fetchRow($constraints = array("cobjective_id" => "0"), $default_method = "=", $default_mode = "AND") {
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
                    } elseif ($constraint["value"] || $constraint["value"] === "0") {
                        $value = clean_input($constraint["value"], array("trim", "striptags"));
                    } else {
                        $value = NULL;
                    }
                } elseif (!is_array($constraint) && in_array($index, $class_vars)) {
                    $key = clean_input($index, array("trim", "striptags"));
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && isset($value) && ($value || $value === 0)) {
                    $replacements .= "\n ".(empty($where) ? "WHERE " : (isset($mode) && $mode ? $mode : $default_mode))." `".$key."` ".(isset($method) && $method ? $method : $default_method)." ?";
                    $where[] = $value;
                }
            }
            if (!empty($where)) {
                $query = "SELECT * FROM `".static::$table_name."` ".$replacements;
                $result = $db->GetRow($query, $where);
                if ($result) {
                    $self = new self();
                    $self = $self->fromArray($result);
                }
            }
        }
        return $self;
    }

    /*
     * See the fetchRow method for documentation. These two are functionally identical other than the fact
     * fetchAll returns all the results (by using $db->GetAll()) whereas fetchRow only returns the first result.
     */
    private function fetchAll($constraints = array("cobjective_id" => "0"), $default_method = "=", $default_mode = "AND") {
        global $db;
        $output = array();
        if (is_array($constraints) && !empty($constraints)) {
            $where = array();
            $replacements = "";
            $class_vars = array_keys(get_class_vars(get_called_class()));
            foreach ($constraints as $index => $constraint) {
                $key = false;
                $value = false;
                if (is_array($constraint) && (in_array($constraint["key"], $class_vars) || $constraint["key"] == "fullname")) {
                    $mode = (isset($constraint["mode"]) && in_array(strtoupper($constraint["mode"]), array("OR", "AND")) ? $constraint["mode"] : $default_mode);
                    if ($constraint["key"] == "fullname") {
                        $key = "CONCAT(`first_name`, ' ', `last_name`)";
                    } else {
                        $key = "`".clean_input($constraint["key"], array("trim", "striptags"))."`";
                    }
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
                } elseif (!is_array($constraint) && (in_array($index, $class_vars) || $index == "fullname")) {
                    if ($index == "fullname") {
                        $key = "CONCAT(`first_name`, ' ', `last_name`)";
                    } else {
                        $key = "`".clean_input($index, array("trim", "striptags"))."`";
                    }
                    $value = clean_input($constraint, array("trim", "striptags"));
                }
                if (isset($key) && $key && isset($value) && ($value || $value === 0)) {
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
                $query = "SELECT * FROM `".static::$table_name."` ".$replacements;
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
    
    public static function fetchAllByCourseID($course_id, $objective_type = NULL) {
        $self = new self();
        
        $params = array(
            "course_id" => $course_id, 
            "active" => "1"
        );
        
        if (!is_null($objective_type) && ($objective_type == "course" || $objective_type == "event")) {
            $params["objective_type"] = $objective_type; 
        }
        
        return $self->fetchAll($params);
    }
    
    public static function fetchRowByID($cobjective_id) {
        $self = new self();
        return $self->fetchRow(array("cobjective_id" => $cobjective_id));
    }
    
    public static function fetchRowByCourseIDObjectiveID($course_id, $objective_id, $active = "1") {
        $self = new self();
        return $self->fetchRow(array("course_id" => $course_id, "objective_id" => $objective_id, "active" => $active));
    }

    public static function fetchAllByOrganisationIDCourseID($organisation_id, $course_id) {
        global $db;

        $query = "	SELECT b.*
                    FROM `course_objectives` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`objective_type` = 'event'
                    AND c.`organisation_id` = ".$db->qstr($organisation_id)."
                    AND b.`objective_active` = '1'
                    AND a.`active` = '1'
                    AND a.`course_id` = ".$db->qstr($course_id)."
                    GROUP BY b.`objective_id`
                    ORDER BY b.`objective_order`";
        $results = $db->GetAll($query);

        return $results ? $results : false;
    }
    
    public function getCourseObjectiveID() {
        return $this->cobjective_id;
    }

    public function update() {
        global $db;
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`cobjective_id` = ".$db->qstr($this->cobjective_id))) {
            return $this;
        } else {
            application_log("error", "Error inserting a ".get_called_class().". DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

    public function insert() {
        global $db;
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->cobjective_id = $db->Insert_ID();
            return $this;
        } else {
            application_log("error", "Error inserting a ".get_called_class().". DB Said: " . $db->ErrorMsg());
            return false;
        }
    }

    public function getCountByCourseID($course_id) {
        global $db;

        $query = "	SELECT COUNT(*) FROM course_objectives WHERE course_id = ? ";

        $result = $db->GetOne($query, array($course_id));

        if ($result) {
            return $result;
        }
        
        return false;
    }
    
}

?>
