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
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Rubric extends Models_Base {
    protected $rubric_id, $one45_element_id, $organisation_id, $rubric_title, $rubric_description, $rubric_item_code, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_rubrics";
    protected static $default_sort_column = "rubric_title";
    protected static $primary_key = "rubric_id";
    protected static $display_columns = array(1 => "rubric_id", 2 => "rubric_title", 3 => "rubric_description");

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rubric_id;
    }

    public function getRubricID() {
        return $this->rubric_id;
    }

    public function getOne45ElementID() {
        return $this->one45_element_id;
    }

    public function getOrganisationId()
    {
        return $this->organisation_id;
    }

    public function getRubricTitle() {
        return $this->rubric_title;
    }

    public function getRubricDescription() {
        return $this->rubric_description;
    }

    public function getGroupedItemCode() {
        return $this->rubric_item_code;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function getDisplayColumns() {
        return self::$display_columns;
    }

    public static function fetchRowByID($rubric_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDOrganisationID($rubric_id, $organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIncludeDeleted($rubric_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction);
    }

    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $item_id = NULL) {
        global $db;
        global $ENTRADA_USER;
        
        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "rubric_title";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "  SELECT a.* " . ($ENTRADA_USER->getActiveRole() != "admin" || array_key_exists("author", $filters) ? ", b.`author_type`, b.`author_id`" : "") ."
                    FROM `cbl_assessments_lu_rubrics` AS a";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS b
                            ON a.`rubric_id` = b.`rubric_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }
            
            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS d
                            ON a.`rubric_id` = d.`rubric_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_items` AS e
                            ON a.`rubric_id` = e.`rubric_id`
                            JOIN `cbl_assessment_item_objectives` AS f
                            ON e.`item_id` = f.`item_id`
                            AND f.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveRole() != "admin") {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS b
                            ON a.`rubric_id` = b.`rubric_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                                (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                            )";
            }
        }

        if (isset($item_id) && $item_id) {
            $query .= " JOIN `cbl_assessment_rubric_items` AS e
                            ON a.`rubric_id` = e.`rubric_id`
                            AND e.`item_id` = ".$db->qstr($item_id);
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`rubric_title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`rubric_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                            OR a.`rubric_item_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL
                            AND f.`deleted_date` IS NULL";
            }
        } else if ($ENTRADA_USER->getActiveRole() != "admin") {
            $query .= " AND b.`deleted_date` IS NULL";
        }

        $query .= " GROUP BY a.`rubric_id`
                    ORDER BY a.`" . $sort_column . "` " . $sort_direction . "
                    LIMIT " . (int) $offset . ", " . (int) $limit;

        $results = $db->GetAll($query);
        return $results;
    }
    
    public static function countAllRecordsBySearchTerm($search_value, $filters = array(), $item_id = null) {
        global $db;
        global $ENTRADA_USER;
        
        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT COUNT(DISTINCT a.rubric_id) as total_rubrics
                    FROM `cbl_assessments_lu_rubrics` AS a";

        if (isset($item_id) && $item_id) {
            $query .= " JOIN `cbl_assessment_rubric_items` AS e
                            ON a.`rubric_id` = e.`rubric_id`
                            AND e.`item_id` = ".$db->qstr($item_id);
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS b
                            ON a.`rubric_id` = b.`rubric_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS d
                            ON a.`rubric_id` = d.`rubric_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessment_rubric_items` AS e
                            ON a.`rubric_id` = e.`rubric_id`
                            JOIN `cbl_assessment_item_objectives` AS f
                            ON e.`item_id` = f.`item_id`
                            AND f.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if ($ENTRADA_USER->getActiveRole() != "admin") {
                $query .= " JOIN `cbl_assessment_rubric_authors` AS c
                            ON a.`rubric_id` = c.`rubric_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                                (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                            )";                 
            }
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`rubric_title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`rubric_description` LIKE (". $db->qstr("%". $search_value ."%") .")
                            OR a.`rubric_item_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL
                            AND f.`deleted_date` IS NULL";
            }
        } else if ($ENTRADA_USER->getActiveRole() != "admin") {
            $query .= " AND c.`deleted_date` IS NULL";
        }

        $results = $db->GetRow($query);
        if ($results) {
            return $results["total_rubrics"];
        }
        return 0;
    }
    
    public static function saveFilterPreferences($filters = array()) {
        global $db;
        
        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    switch ($filter_type) {
                        case "curriculum_tag" :
                            $objective = Models_Objective::fetchRow($target);
                            if ($objective) {
                                $target_label = $objective->getName();
                            }
                        break;
                        case "course" :
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                        break;
                        case "author" :
                            $query = "SELECT CONCAT(`firstname`, ' ', `lastname`) AS fullname FROM `". AUTH_DATABASE ."`.`user_data` WHERE `id` = ?";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["fullname"];
                            }
                        break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }
}