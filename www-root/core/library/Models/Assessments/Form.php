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

class Models_Assessments_Form extends Models_Base {
    protected $form_id, $one45_form_id, $organisation_id, $title, $description, $objective_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_forms";
    protected static $primary_key = "form_id";
    protected static $default_sort_column = "form_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->form_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getOne45FormID() {
        return $this->one45_form_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }
    
    public function getObjectiveID() {
        return $this->objective_id;
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

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function fetchFormElements() {
        global $db;
        $query = "SELECT * FROM `cbl_assessment_form_elements` WHERE `form_id` = ? AND `deleted_date` IS NULL GROUP BY IF(`rubric_id` IS NOT NULL, `rubric_id`, `afelement_id`) ORDER BY `order` ASC";

        $results = $db->GetAll($query, $this->form_id);
        if ($results) {
            return $results;
        } else {
            return false;
        }
    }

    public function fetchFormElementCount() {
        $form_elements = $this->fetchFormElements();

        if ($form_elements) {
            return count($form_elements);
        }

        return false;
    }

    /**
     * Gets a full set of data necessary to render one complete form. 
     * The purpose of this method is to re-structure the data coming from db
     * in a way that is easily consumable by Views_Gradebook_Assessments_Form items
     * @return array $results
     */
    public function getCompleteFormData($assessment_id = null, $proxy_id = null) {

        // Get list of form element IDs
        $blocks = $this->fetchFormElements();

        // Get all possible form element entries. This includes the item level comments
        $form_element_model = new Models_Assessments_Form_Element(array('form_id' => $this->form_id));
        $form_elements = $form_element_model->fetchAllFormElementsRubricsItemsItemTypes($assessment_id, $proxy_id);

        if ($form_elements) {

            $rubrics = array();
            $text_elements = array();
            $items = array();

            $results = array();
            $item_ids = array();

            // Create array of item ids for next query
            foreach($form_elements as $form_element) {
                $item_ids[] = $form_element['element_id'];
            }

            // Get item responses and descriptors. 
            $item_responses = Models_Assessments_Item_Response::fetchAllByItemIDsWithResponseDescriptors($item_ids, $assessment_id, $proxy_id);
            $item_responses_by_id = array();

            if ($item_responses) {
                foreach ($item_responses as $item_response) {
                    foreach ($form_elements as $i => $form_element) {
                        if ($form_element['element_id'] == $item_response['item_id']) {
                            $form_elements[$i]['item_responses'][] = $item_response;
                        }
                    }

                    $item_responses_by_id[$item_response['item_id']][] = $item_response;
                }
            }            

            // Populate separate arrays based on existence of rubric_id
            foreach($form_elements as $form_element) {

                if (is_numeric($form_element['rubric_id'])) {
                    if (!$rubrics[$form_element['rubric_id']]) {
                        $rubrics[$form_element['rubric_id']]['details']['title'] = $form_element['rubric_title'];
                        $rubrics[$form_element['rubric_id']]['details']['type'] = 'Rubric';
                    }
                    
                    $rubrics[$form_element['rubric_id']]['items'][] = $form_element;
                    $rubrics[$form_element['rubric_id']]['item_responses'] = $item_responses_by_id[$form_element['element_id']];
                }
                elseif ($form_element['element_type'] == 'text') {
                    $text_elements[$form_element['afelement_id']]['details']['title'] = $form_element['element_text'];
                    $text_elements[$form_element['afelement_id']]['details']['type'] = 'Text';
                    $text_elements[$form_element['afelement_id']]['item'] = $form_element;
                }
                elseif ($form_element['element_type'] == 'objective') {
                    // Do nothing with objective types, as they have a specific use case not necessary for gradebook grading. 
                    // In the future, adding objective-type objects to a form can be done here.
                }
                else {
                    $items[$form_element['element_id']]['details']['title'] = $form_element['item_text'];
                    $items[$form_element['element_id']]['details']['type'] = $form_element['classname'];
                    $items[$form_element['element_id']]['item'] = $form_element;
                }
            }

            // Populate results as one array with an element_id as key for each.
            // Also, create a simple array of IDs for the next query
            foreach($blocks as $block) {

                if ($block['rubric_id']) {
                    $results[$block['afelement_id']] = $rubrics[$block['rubric_id']];
                }
                elseif ($block['element_type'] == 'text') {
                    $results[$block['afelement_id']] = $text_elements[$block['afelement_id']];
                }
                else {
                    if ($items[$block['element_id']]) {
                        $results[$block['afelement_id']] = $items[$block['element_id']];
                    }
                }
            }

            // Returns the completed array
            return $results;
        }

        return false;    
    }
    
    public static function fetchAllRecordsBySearchTerm($search_value, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $rubric_id = null, $item_id = null) {
        global $db;
        global $ENTRADA_USER;
        
        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "form_id";
        }
        
        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }
        
        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT a.*, COUNT(DISTINCT b.`afelement_id`) AS `item_count` " . ((!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) || array_key_exists("author", $filters) ? ", c.`author_type`, c.`author_id`" : "") ."
                    FROM `cbl_assessments_lu_forms` AS a
                    LEFT JOIN `cbl_assessment_form_elements` AS b
                    ON a.`form_id` = b.`form_id`
                    AND b.`deleted_date` IS NULL";
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_form_authors` AS c
                            ON a.`form_id` = c.`form_id`
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_form_authors` AS e
                            ON a.`form_id` = e.`form_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessment_form_elements` AS f
                            ON a.`form_id` = f.`form_id`
                            AND f.`element_type` = 'item'
                            JOIN `cbl_assessment_item_objectives` AS g
                            ON f.`element_id` = g.`item_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_form_authors` AS c
                            ON a.`form_id` = c.`form_id` 
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
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());


        // Item ID query supercedes rubric id query
        if ($item_id) {
            $query .= "AND b.`element_id` = {$db->qstr($item_id)} AND b.`element_type` = 'item'";
        } else if ($rubric_id) {
            $query .= "AND b.`rubric_id` = {$db->qstr($rubric_id)}";
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND f.`deleted_date` IS NULL
                            AND g.`deleted_date` IS NULL";
            }
        } else if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND c.`deleted_date` IS NULL";
        }
        
        $query .= " GROUP BY a.`form_id`
                    ORDER BY a.`" . (string) $sort_column . "` " . (string) $sort_direction . " 
                    LIMIT " . (int) $offset . ", " . (int) $limit;

        $results = $db->GetAll($query);
        return $results;
    }
    
    public static function countAllRecordsBySearchTerm($search_value, $filters = array(), $rubric_id = null, $item_id = null) {
        global $db;
        global $ENTRADA_USER;
        
        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT COUNT(DISTINCT a.`form_id`) AS `total_forms`
                    FROM `cbl_assessments_lu_forms` AS a";
        $elements_joined = false;
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_form_authors` AS b
                            ON a.`form_id` = b.`form_id`
                            AND b.`author_type` = 'proxy_id'
                            AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_form_authors` AS d
                            ON a.`form_id` = d.`form_id`
                            AND d.`author_type` = 'course_id'
                            AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $elements_joined = true;
                $query .= " JOIN `cbl_assessment_form_elements` AS e
                            ON a.`form_id` = e.`form_id`
                            AND e.`element_type` = 'item'
                            JOIN `cbl_assessment_item_objectives` AS f
                            ON e.`element_id` = f.`item_id`
                            AND f.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_form_authors` AS b
                            ON a.`form_id` = b.`form_id` 
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

        if (!$elements_joined && ($item_id || $rubric_id)) {
            $query .= " JOIN `cbl_assessment_form_elements` AS e ON a.`form_id` = e.`form_id` ";
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
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
        } else if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND b.`deleted_date` IS NULL";
        }

        // Item ID query supercedes rubric id query
        if ($item_id) {
            $query .= " AND e.`element_id` = {$db->qstr($item_id)} AND e.`element_type` = 'item'";
        } else if ($rubric_id) {
            $query .= " AND e.`rubric_id` = {$db->qstr($rubric_id)}";
        }


        $results = $db->GetRow($query);
        if ($results) {
            return $results["total_forms"];
        }
        return 0;
    }
    
    public static function fetchRowByID($form_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIncludeDeleted($form_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
        ));
    }

    public static function fetchRowByIDSearchTerm($form_id, $search_value = NULL) {
        global $db;
        $form = false;
        $AND_SEARCH_LIKE = "";

        if (!is_null($search_value) && $search_value != "") {
            $AND_SEARCH_LIKE = "AND
            (
                (
                    `title` LIKE (". $db->qstr("%". $search_value ."%") .")
                    OR `description` LIKE (". $db->qstr("%". $search_value ."%") .")
                )
            )";
        }

        $query = "  SELECT * FROM `cbl_assessments_lu_forms`
                    WHERE `form_id` = ? 
                    AND `deleted_date` IS NULL
                    $AND_SEARCH_LIKE ";

        $result = $db->GetRow($query, array($form_id));
        if ($result) {
            $form = new self($result);
        }
        return $form;
    }

    public static function fetchRowByIDOrganisationID($form_id, $organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
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
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }
    
    public static function fetchAllByOwner($proxy_id, $organisation_id, $search_value = "", $limit = null, $offset = null) {
        global $db, $ENTRADA_USER;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        $forms = false;
        
        $query = "SELECT * FROM `cbl_assessments_lu_forms` AS a";
        
        if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " JOIN `cbl_assessment_form_authors` AS b
                        ON a.`form_id` = b.`form_id` 
                        AND     
                        ("
                            .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                            .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                            .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                            .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                            (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($proxy_id) . ")
                        )";                        
        }
        
        $query .= " WHERE a.`deleted_date` IS NULL
                    AND
                    (
                        (
                            a.`title` LIKE (". $db->qstr("%". $search_value ."%") .") 
                            OR a.`description` LIKE (". $db->qstr("%". $search_value ."%") .")
                        )
                    )
                    AND a.`organisation_id` = ". $db->qstr($organisation_id);
        
        if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND b.`deleted_date` IS NULL";
        }

        $query .= " GROUP BY a.`form_id`";
        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }
        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $form) {
                $forms[] = new self($form);
            }
        }
        
        return $forms;
    }

    public static function fetchAllByAttachedRubric ($rubric_id) {
        global $db;
        $forms = array();

        $query = "  SELECT a.*, b.* FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessment_form_elements` AS b
                    ON a.`form_id` = b.`form_id`
                    WHERE b.`rubric_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    GROUP BY a.`form_id`";

        $results = $db->GetAll($query, array($rubric_id));
        if ($results) {
            foreach ($results as $result) {
                $forms[] = new self(array("form_id" => $result["form_id"], "one45_form_id" => $result["one45_form_id"], "organisation_id" => $result["organisation_id"], "title" => $result["title"], "description" => $result["description"], "created_date" => $result["created_date"], "created_by" => $result["created_by"], "updated_date" => $result["updated_date"], "updated_by" => $result["updated_by"], "deleted_date" => $result["deleted_date"]));
            }
        }

        return $forms;
    }
}