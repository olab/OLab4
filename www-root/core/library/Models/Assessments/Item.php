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

class Models_Assessments_Item extends Models_Base {
    protected $item_id, $one45_element_id, $organisation_id, $itemtype_id, $item_group_id, $item_code, $rating_scale_id, $mandatory = 1, $allow_default = 0, $hide_from_index = 0, $default_response, $attributes, $item_text, $item_description, $comment_type, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_items";
    protected static $primary_key = "item_id";
    protected static $default_sort_column = "item_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->item_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getOne45ElementID() {
        return $this->one45_element_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getItemtypeID() {
        return $this->itemtype_id;
    }

    public function getItemGroupID() {
        return $this->item_group_id;
    }

    public function getItemCode() {
        return $this->item_code;
    }

    public function getRatingScaleID() {
        return $this->rating_scale_id;
 	}

    public function getItemText() {
        return $this->item_text;
    }

    public function getItemDescription() {
        return $this->item_description;
    }

    public function getCommentType() {
        return $this->comment_type;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function getMandatory() {
        return $this->mandatory;
    }

    public function getHideFromIndex() {
        return $this->hide_from_index;
    }

    public function getAllowDefault() {
        return $this->allow_default;
    }

    public function getDefaultResponse() {
        return $this->default_response;
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

    public function getCommentTypeEnumValues() {
        if ($metadata = $this->fieldMetadata(DATABASE_NAME, "cbl_assessments_lu_items", "comment_type")) {
            if ($metadata["Type"]) {
                $enum_values_string = trim($metadata["Type"], "enum(");
                $enum_values_string = trim($enum_values_string, ")");
                $enum_values = explode(",", $enum_values_string);
                return array_map(function($v) { return trim($v, "'"); }, $enum_values);
            }
        }
        return false;
    }

    public static function fetchRowByID($item_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDOrganisationID($item_id, $organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIncludeDeleted($item_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public static function fetchAllRecordsBySearchTerm($search_value = null, $limit = null, $offset = null, $sort_direction = null, $sort_column = null, $rubric_width = null, $item_type = null, $existing_rubric_items = null, $rubric_descriptors = null, $exclude_item_ids = null, $form_id = null, $filters = array(), $rating_scale_id = 0) {
        global $db;
        global $ENTRADA_USER;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "created_date";
        }
        
        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "DESC";
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        if (!$existing_rubric_items) {
            $existing_rubric_items = array();
        }

        $rubric_items = array();

        if (is_array($exclude_item_ids)) {
            $rubric_items = array_merge($rubric_items, $exclude_item_ids);
        }
        if (is_array($existing_rubric_items)) {
            $rubric_items = array_merge($rubric_items, $existing_rubric_items);
        }

        $query = "  SELECT a.`item_id`, a.`itemtype_id`, a.`item_code`, a.`organisation_id`, a.`item_text`, a.`comment_type`, a.`mandatory`, a.`created_date`, b.`name`, COUNT(DISTINCT h.`iresponse_id`) AS `responses`
                    FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_itemtypes` AS b
                    ON a.`itemtype_id` = b.`itemtype_id` "
            . (isset($item_type) && $item_type ? "AND a.`itemtype_id` IN (" . implode(",", $item_type) . ")" : "")
            . (!empty($rubric_items) ? " AND a.`item_id` NOT IN (" . implode(",", $rubric_items).")" : "")
            . (isset($form_id) && $form_id ? " AND a.`itemtype_id` != 11" : "");

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_item_authors` AS c
                            ON a.`item_id` = c.`item_id`
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_item_authors` AS e
                            ON a.`item_id` = e.`item_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessment_item_objectives` AS g
                            ON a.`item_id` = g.`item_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_item_authors` AS c
                            ON a.`item_id` = c.`item_id`
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
        
        $query .= " LEFT JOIN `cbl_assessments_lu_item_responses` AS h
                    ON a.`item_id` = h.`item_id` AND h.`deleted_date` IS NULL";

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND a.`item_group_id` IS NULL
                    AND (a.`hide_from_index` = 0 OR a.`hide_from_index` IS NULL)
                    AND b.`deleted_date` IS NULL
                    AND
                    (
                        a.`item_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`name` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR a.`item_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("course", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND g.`deleted_date` IS NULL";
            }
        } else if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND c.`deleted_date` IS NULL";
        }

        if ($rating_scale_id) {
            $query .= " AND a.`rating_scale_id`=". $db->qstr($rating_scale_id);
        }

        if ($rubric_descriptors) {
            sort($rubric_descriptors);
        }

        $query .= " GROUP BY a.`item_id` " .
            (isset($rubric_width) && !$rating_scale_id ? " HAVING count(*) = ".$db->qstr($rubric_width).
                (isset($rubric_descriptors) && $rubric_descriptors && !$rating_scale_id ? " AND GROUP_CONCAT(h.`ardescriptor_id` ORDER BY h.`ardescriptor_id` ASC) = " . $db->qstr(implode(",", $rubric_descriptors)) : "") : "") . "
                    ORDER BY `" . $sort_column . "` " . $sort_direction . "
                    LIMIT " . ($offset ? (int) $offset : 0) . ", " . (int) $limit;

        $results = $db->GetAll($query);
        return $results;
    }

    public static function fetchRecordByID($item_id) {
        global $db, $ENTRADA_USER;

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT a.`item_id`, a.`itemtype_id`, a.`item_code`, a.`organisation_id`, a.`item_text`, a.`comment_type`, a.`mandatory`, a.`created_date`, b.`name`, COUNT(DISTINCT h.`iresponse_id`) AS `responses`
                    FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_itemtypes` AS b
                    ON a.`itemtype_id` = b.`itemtype_id` ";


        if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " JOIN `cbl_assessment_item_authors` AS c
                        ON a.`item_id` = c.`item_id`
                        AND c.`deleted_date` IS NULL
                        AND
                        ("
                            .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["director"], ',')).")) OR" : "")
                            .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                            .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                            .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(c.`author_type` = 'course_id' AND c.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                            (c.`author_type` = 'proxy_id' AND c.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                        )";
        }


        $query .= " LEFT JOIN `cbl_assessments_lu_item_responses` AS h
                    ON a.`item_id` = h.`item_id` AND h.`deleted_date` IS NULL
                    WHERE a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND a.`item_id` = ".$db->qstr($item_id)."
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    GROUP BY a.`item_id`";

        $result = $db->GetRow($query);
        return $result;
    }

    public static function countAllRecordsBySearchTerm($search_value, $rubric_width, $item_type = null, $rubric_items = null, $rubric_descriptors = null, $filters = array(), $rating_scale_id = null) {
        global $db;
        global $ENTRADA_USER;

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        $query = "  SELECT count(DISTINCT `item_id`) as `total_items`
                    FROM
                    (SELECT a.`item_id`, a.`itemtype_id`, a.`item_code`, a.`organisation_id`, a.`item_text`, a.`comment_type`, a.`mandatory`, a.`created_date`, b.`name`," . ((!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) ? " c.`author_type`, c.`author_id`, " : " ") ." COUNT(DISTINCT h.`iresponse_id`) AS `responses`
                    FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_itemtypes` AS b
                    ON a.`itemtype_id` = b.`itemtype_id` "
            . (isset($item_type) && $item_type ? "AND a.`itemtype_id` IN (" . implode(",", $item_type) . ")" : "")
            . (isset($rubric_items) && $rubric_items ? " AND a.`item_id` NOT IN (" . implode(",", $rubric_items).")" : "");

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " JOIN `cbl_assessment_item_authors` AS c
                            ON a.`item_id` = c.`item_id`
                            AND c.`author_type` = 'proxy_id'
                            AND c.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " JOIN `cbl_assessment_item_authors` AS e
                            ON a.`item_id` = e.`item_id`
                            AND e.`author_type` = 'course_id'
                            AND e.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " JOIN `cbl_assessment_item_objectives` AS g
                            ON a.`item_id` = g.`item_id`
                            AND g.`objective_id` IN (". implode(",", array_keys($filters["curriculum_tag"])) .")";
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_item_authors` AS c
                            ON a.`item_id` = c.`item_id`
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


        $query .= " LEFT JOIN `cbl_assessments_lu_item_responses` AS h
                    ON a.`item_id` = h.`item_id` AND h.`deleted_date` IS NULL";

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND a.`item_group_id` IS NULL
                    AND (a.`hide_from_index` = 0 OR a.`hide_from_index` IS NULL)
                    AND
                    (
                        a.`item_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`name` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR a.`item_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());

        if ($rating_scale_id) {
            $query .= " AND a.rating_scale_id = ".$db->qstr($rating_scale_id);
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND c.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND e.`deleted_date` IS NULL";
            }

            if (array_key_exists("curriculum_tag", $filters)) {
                $query .= " AND g.`deleted_date` IS NULL";
            }
        } else if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
            $query .= " AND c.`deleted_date` IS NULL";
        }

        if ($rubric_descriptors) {
            sort($rubric_descriptors);
        }

        $query .= " GROUP BY a.`item_id` " .
            (isset($rubric_width) ? " HAVING count(*) = ".$db->qstr($rubric_width).
                (isset($rubric_descriptors) && $rubric_descriptors ? " AND GROUP_CONCAT(h.`ardescriptor_id` ORDER BY h.`ardescriptor_id` ASC) = " . $db->qstr(implode(",", $rubric_descriptors)) : "") : "");

        $query .= ") x";

        $results = $db->GetRow($query);
        if ($results) {
            return $results["total_items"];
        }
        return 0;
    }

    public static function fetchAllRecordsBySearchTermItemType($search_value, $start, $limit, $sort_direction, $sort_column, $item_type = 1) {
        global $db;

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "item_id";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        if ($search_value && $tmp_input = clean_input($search_value, array("trim", "striptags"))) {
            $where = "  WHERE a.`item_text` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`name` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR a.`item_code` LIKE (". $db->qstr("%". $search_value ."%") .")
                        AND a.`itemtype_id` = " . $db->qstr($item_type);
        }

        $query = "  SELECT a.*, b.`name`, COUNT(c.`item_id`) AS `responses`
                    FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_itemtypes` AS b
                    ON a.`itemtype_id` = b.`itemtype_id`
                    JOIN `cbl_assessments_lu_item_responses` AS c
                    ON a.`item_id` = c.`item_id` AND c.`deleted_date` IS NULL
                    ". (isset($where) ? $where : "") ."
                    GROUP BY a.`item_id`
                    ORDER BY `" . $sort_column . "` " . $sort_direction . "
                    " . ((int) $start != 0 ? "LIMIT " . (int) $start . ", " . (int) $limit : "LIMIT " . (int) $start . ", " . (int) 100);

        $results = $db->GetAll($query);
        return $results;
    }

    public function getItemType() {
        return Models_Assessments_Itemtype::fetchRowByID($this->itemtype_id);
    }

    /**
     * This method returns responses for the item. If there's a rating scale associated with it,
     * it returns the responses associated to that scale, else it returns responses attached
     * directly to the item.
     *
     * @return array
     */
    public function getItemResponses() {
        if ($this->rating_scale_id) {
            return Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleID($this->rating_scale_id);
        } else {
            return Models_Assessments_Item_Response::fetchAllRecordsByItemID($this->item_id);
        }
    }

    public function getItemObjectives() {
        return Models_Assessments_Item_Objective::fetchAllRecordsByItemID($this->item_id);
    }
    
    public function getItemTags() {
        Models_Assessments_Tag::fetchAllRecordsByItemID($this->item_id);
    }
    
    public static function fetchItemsByProxyID($proxy_id) {
        global $db;
        $items = false;
        
        $query = "  SELECT * FROM `cbl_assessments_lu_items` AS a
                    LEFT JOIN `cbl_assessment_item_authors` AS b
                    ON a.`item_id` = b.`item_id`
                    WHERE b.`author_type` = 'proxy_id'
                    AND b.`author_id` = ?";
        
        $results = $db->GetAll($query, array($proxy_id));
        if ($results) {
            foreach ($results as $result) {
                $items[] = new self($result);
            }
        }
        return $items;
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
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }
    
    public static function fetchFieldNoteItem ($objective_id) {
        global $db;
        $item = false;
        
        $query = "  SELECT a.`item_id`, b.`itemtype_id`, b.`item_text` FROM `cbl_assessment_item_objectives` AS a
                    JOIN `cbl_assessments_lu_items` AS b
                    ON a.`item_id` = b.`item_id`
                    WHERE a.`objective_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`itemtype_id` = 13
                    AND b.`deleted_date` IS NULL";

        $result = $db->GetRow($query, array($objective_id));
        
        if ($result) {
            $item = new self($result);
        }
        
        return $item;
    }

    public static function fetchItemByResponseID ($iresponse_id) {
        global $db;
        $item = false;

        $query = "SELECT a.`iresponse_id`, b.* FROM `cbl_assessments_lu_item_responses` AS a
                  JOIN `cbl_assessments_lu_items` AS b
                  ON a.`item_id` = b.`item_id`
                  WHERE a.`iresponse_id` = ?";

        $result = $db->GetRow($query, array($iresponse_id));

        if ($result) {
            $item = new self($result);
        }

        return $item;
    }

    public static function fetchItemObjectives($item_id) {
        global $db;

        $query = "SELECT b.* 
                  FROM `cbl_assessment_item_objectives` a
                  LEFT JOIN `global_lu_objectives` b 
                  ON a.objective_id = b.objective_id
                  WHERE a.item_id = ?
                  AND a.deleted_date IS NULL
                  AND b.objective_active = 1";

        return $db->getAll($query, array($item_id));
    }

    public static function fetchItemIDsByScaleID($scale_id) {
        global $db;
        $item_ids = array();

        $sql = "SELECT DISTINCT `item_id`
                FROM `cbl_assessments_lu_items`
                WHERE `rating_scale_id` = ?";
        $items = $db->GetAll($sql, array($scale_id));
        if (is_array($items)) {
            foreach ($items as $item) {
                $item_ids[] = $item["item_id"];
            }
        }
        return $item_ids;
    }

    public static function fetchItemIDsHavingScaleIDByRubricID($rubric_id) {
        global $db;

        // Fetch all item IDs for items contained in a rubric
        $query = "SELECT i.`item_id`
                  FROM `cbl_assessments_lu_items` AS i
                  JOIN `cbl_assessment_rubric_items` AS ri ON i.`item_id` = ri.`item_id`
                  WHERE ri.`rubric_id` = ?
                  AND i.`rating_scale_id` >= 1
                  AND i.`rating_scale_id` IS NOT NULL
                  AND ri.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($rubric_id));
        if (empty($results)) {
            return array();
        }
        $return_array = array();
        foreach ($results as $result) {
            $return_array[$result["item_id"]] = (int)$result["item_id"];
        }
        return $return_array;
    }
}