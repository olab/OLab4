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
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Blueprint extends Models_Base {
    protected $form_blueprint_id, $form_type_id, $course_id, $title, $description, $include_instructions, $instructions, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $published, $active, $complete, $organisation_id;

    protected static $table_name = "cbl_assessments_lu_form_blueprints";
    protected static $primary_key = "form_blueprint_id";
    protected static $default_sort_column = "form_blueprint_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->form_blueprint_id;
    }

    public function getFormBlueprintID() {
        return $this->form_blueprint_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function setOrganisationID($org_id) {
        $this->organisation_id = $org_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getIncludeInstructions() {
        return $this->include_instructions;
    }

    public function getInstructions() {
        return $this->instructions;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedDate($time) {
        return $this->updated_date = $time;
    }

    public function setUpdatedBy($proxy_id) {
        return $this->updated_by = $proxy_id;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getPublished() {
        return $this->published;
    }

    public function getComplete() {
        return $this->complete;
    }

    public function getActive() {
        return $this->active;
    }

    public function setPublished($value) {
        $this->published = $value;
    }

    public static function fetchRowByID($form_blueprint_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_blueprint_id", "value" => $form_blueprint_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS"),
        ));
    }

    public static function fetchRowByIDIncludeDeleted($form_blueprint_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_blueprint_id", "value" => $form_blueprint_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords($include_deleted = false) {
        $self = new self();
        $constraints = array();
        $constraints[] = array("key" => "form_blueprint_id", "value" => 1, "method" => ">=");
        if (!$include_deleted) {
            $constraints[] = array("key" => "deleted_date", "value" => null, "method" => "IS");
        }
        return $self->fetchAll($constraints);
    }

    /**
     * Fetch a form blueprint by ID and organisation. We join form_blueprints->form_type->form_type_organisation in order to limit by org.
     *
     * @param $blueprint_id
     * @param $organisation_id
     * @return bool|array
     */
    public static function fetchRowByIDOrganisationID($blueprint_id, $organisation_id) {
        global $db;
        $query = "SELECT  fbp.*
                  FROM    `cbl_assessments_lu_form_blueprints` AS fbp
                  JOIN    `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = fbp.`form_type_id`
                  JOIN    `cbl_assessments_form_type_organisation` AS fto ON fto.`form_type_id` = ft.`form_type_id`
                  WHERE   fto.`organisation_id` = ?
                  AND     fbp.`form_blueprint_id` = ?";
        $results = $db->GetRow($query, array($organisation_id, $blueprint_id));
        if (empty($results)) {
            return false;
        }
        $object = new self();
        $object->fromArray($results);
        return $object;
    }

    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array()) {
        global $db;
        global $ENTRADA_USER;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "form_blueprint_id";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT a.*, h.title as form_type_title, COUNT(DISTINCT b.`afblueprint_element_id`) AS `item_count`
                    FROM `cbl_assessments_lu_form_blueprints` AS a
                    LEFT JOIN `cbl_assessments_form_blueprint_elements` AS b
                    ON a.`form_blueprint_id` = b.`form_blueprint_id`
                    AND b.`deleted_date` IS NULL
                    LEFT JOIN `cbl_assessments_lu_form_types` AS h
                    ON h.form_type_id = a.form_type_id";

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessments_form_blueprint_authors` AS c
                            ON a.`form_blueprint_id` = c.`form_blueprint_id`
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessments_form_blueprint_elements` AS f
                            ON a.`form_blueprint_id` = f.`form_blueprint_id`
                            JOIN `cbl_assessments_form_blueprint_objectives` AS g
                            ON f.`afblueprint_element_id` = g.`afblueprint_element_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
            if (array_key_exists("milestones", $filters)) {
                $query .= " JOIN `cbl_assessments_form_blueprint_elements` AS f
                            ON a.`form_blueprint_id` = f.`form_blueprint_id`
                            JOIN `cbl_assessments_form_blueprint_objectives` AS g
                            ON f.`afblueprint_element_id` = g.`afblueprint_element_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["milestones"])) .")";
            }
            if (array_key_exists("epas", $filters)) {
                $query .= " JOIN `cbl_assessments_form_blueprint_elements` AS f
                            ON a.`form_blueprint_id` = f.`form_blueprint_id`
                            JOIN `cbl_assessments_form_blueprint_objectives` AS g
                            ON f.`afblueprint_element_id` = g.`afblueprint_element_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["epas"])) .")";
            }
            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " JOIN `cbl_assessments_form_blueprint_elements` AS f
                            ON a.`form_blueprint_id` = f.`form_blueprint_id`
                            JOIN `cbl_assessments_form_blueprint_objectives` AS g
                            ON f.`afblueprint_element_id` = g.`afblueprint_element_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["contextual_variables"])) .")";
            }
            if (array_key_exists("form_types", $filters)) {
                $query .= " JOIN `cbl_assessments_form_type_organisation` AS i
                            ON h.`form_type_id` = i.`form_type_id`";
            }
        }
        if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " JOIN `cbl_assessments_form_blueprint_authors` AS c
                        ON a.`form_blueprint_id` = c.`form_blueprint_id` 
                        AND     
                        ("
                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                            (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                        )";
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )";


        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND a.`course_id` IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("epa", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("milestone", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("contextual_variables", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }

            if (array_key_exists("form_types", $filters)) {
                $query .= " AND h.`form_type_id` IN (". implode(",", array_keys($filters["form_types"])) .")
                            AND h.`deleted_date` IS NULL
                            AND i.`deleted_date` IS NULL";
            }
        } else if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND c.`deleted_date` IS NULL";
        }

        $query .= " GROUP BY a.`form_blueprint_id`
                    ORDER BY a.`" . (string) $sort_column . "` " . (string) $sort_direction;

        if ($limit) {
            $query .= " LIMIT $limit";
        }

        if ($offset) {
            $query .= " OFFSET $offset";
        }

        $results = $db->GetAll($query);
        return $results;
    }

    public static function saveFilterPreferences($filters = array()) {
        global $db;

        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    $skip = false;
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
                        case "milestones" :
                        case "epas" :
                        case "contextual_variables" :
                            $objective = Models_Objective::fetchRow($target);
                            $label = "";
                            $shortname = "";
                            if ($objective) {
                                if ($filter_type == "contextual_variables") {
                                    $target_label = strlen($objective->getName()) > 50 ? substr($objective->getName(),0,50)."..." : $objective->getName();
                                } else {
                                    $target_label = strlen($objective->getCode() . " " . $objective->getName()) > 50 ? substr($objective->getCode() . " " . $objective->getName(),0,50)."..." : $objective->getCode() . " " . $objective->getName();
                                }
                            } else {
                                $course = Models_Course::get($target);
                                if ($course) {
                                    $objective = new Models_Objective();
                                    if ($filter_type == "milestones") {
                                        $shortname = "milestone";
                                    }
                                    if ($filter_type == "epas") {
                                        $shortname = "epa";
                                    }
                                    if ($filter_type == "contextual_variables") {
                                        $shortname = "contextual_variable";
                                    }
                                    $objectives = $objective->fetchChildrenByObjectiveSetShortnameCourseID($shortname, $course->getID());
                                    foreach($objectives as $objective) {
                                        if ($filter_type == "contextual_variables") {
                                            $label = strlen($objective->getName()) > 50 ? substr($objective->getName(),0,50)."..." : $objective->getName();
                                        } else {
                                            $label = strlen($objective->getCode() . " " . $objective->getName()) > 50 ? substr($objective->getCode() . " " . $objective->getName(),0,50)."..." : $objective->getCode() . " " . $objective->getName();
                                        }
                                        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"][$filter_type][$objective->getID()] = $label;
                                    }
                                    $skip = true;
                                }
                            }
                        break;
                        case "form_types" :
                            $form_type_model = new Models_Assessments_Form_Type();
                            $form_type = $form_type_model->fetchRowByID($target);
                            if ($form_type) {
                                $target_label = $form_type->getTitle();
                            }
                        break;
                    }
                    if (!$skip) {
                        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"][$filter_type][$target] = $target_label;
                    }
                }
            }
        }
    }

    /**
     * Delete all other blueprint of a type except for the one with the ID specified.
     *
     * @param $form_type_id
     * @param $form_blueprint_id
     * @param $proxy_id
     * @return mixed
     */
    public static function deleteOtherFormTypeBlueprint($form_type_id, $form_blueprint_id, $proxy_id) {
        global $db;

        $query = "UPDATE `cbl_assessments_lu_form_blueprints` SET
                  `deleted_date` = ?,
                  `updated_by` = ?
                  WHERE `form_type_id` = ?
                  AND `form_blueprint_id` != ?";

        return $db->execute($query, array(time(), $proxy_id, $form_type_id, $form_blueprint_id));
    }

    public function fetchAllByCourseID ($course_id = 0, $active = 1) {
        $self = new self();

        return $self->fetchAll(array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "="),
        ));
    }

    public function fetchAllByCourseIDIgnoreActive ($course_id = 0) {
        $self = new self();

        return $self->fetchAll(array(
            array("key" => "course_id", "value" => $course_id, "method" => "=")
        ));
    }

    public static function fetchIncompleteList() {
        global $db;
        $query = "SELECT a.`form_blueprint_id`, a.`organisation_id`, a.`created_by` 
                  FROM `cbl_assessments_lu_form_blueprints` AS a 
                  WHERE (a.`complete` IS NULL OR a.`complete` <> 1) 
                  AND a.`published` = 1 
                  AND `deleted_date` IS NULL";

        $result = $db->GetAll($query);
        if (empty($result)) {
            return array();
        }
        return $result;
    }
}