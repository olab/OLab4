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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_RatingScale extends Models_Base {
    protected $rating_scale_id, $organisation_id, $rating_scale_type, $rating_scale_title, $rating_scale_description, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_assessment_rating_scale";
    protected static $default_sort_column = "rating_scale_title";
    protected static $primary_key = "rating_scale_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rating_scale_id;
    }

    public function getRatingScaleID() {
        return $this->rating_scale_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getRatingScaleType() {
        return $this->rating_scale_type;
    }

    public function getRatingScaleTitle() {
        return $this->rating_scale_title;
    }

    public function getRatingScaleDescription() {
        return $this->rating_scale_description;
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

    public static function fetchRowByID($rating_scale_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByTitleTypeIDOrganisationID($title, $scale_type_id, $organisation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_title", "value" => $title, "method" => "="),
            array("key" => "rating_scale_type", "value" => $scale_type_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
        ));
    }

    public static function fetchRowByIDIncludeDeleted($rating_scale_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
        ));
    }

    public static function fetchRowByIDOrganisationID($rating_scale_id, $organisation_id, $deleted_date = null) {
        global $db;

        $deleted = (is_null($deleted_date)) ? 'IS NULL' : " = '".$deleted_date."'";

        $query = "
			SELECT
				`rs`.*
			FROM
				`cbl_assessment_rating_scale` AS `rs`
			LEFT JOIN
				`cbl_assessment_rating_scale_authors` AS `rsa`
			USING
				(`rating_scale_id`)
			WHERE
				`rs`.`rating_scale_id` = ".$rating_scale_id."
				AND `rs`.`organisation_id` = ".$organisation_id."
				AND `rs`.`deleted_date` ".$deleted."
				AND `rsa`.`deleted_date` ".$deleted."
		";

        if ($result = $db->GetAll($query)) {
            return $result[0];
        }

        return false;
    }

    /**
     * Fetch the IDs of the forms that have items that use that rating scale attached to it.
     *
     * @param $rating_scale_id
     * @return array
     */
    public static function fetchFormIDsByRatingScaleID($rating_scale_id) {
        global $db;
        $form_ids = array();
        $query = "(SELECT      DISTINCT(fe.`form_id`)
                  FROM        `cbl_assessment_form_elements` AS fe
                  LEFT JOIN   `cbl_assessments_lu_items`     AS i ON i.`item_id` = fe.`element_id`
                  LEFT JOIN   `cbl_assessments_lu_forms`     AS fr ON fr.`form_id` = fe.`form_id`
                  WHERE       fe.`element_type` = 'item'
                  AND         i.`deleted_date` IS NULL
                  AND         fe.`deleted_date` IS NULL
                  AND         fr.`deleted_date` IS NULL
                  AND         i.rating_scale_id = ?)
                  
                  UNION
                  
                  (SELECT      DISTINCT(fe.`form_id`)
                  FROM        `cbl_assessment_form_elements` AS fe
                  LEFT JOIN   `cbl_assessments_lu_rubrics`     AS i ON i.`rubric_id` = fe.`rubric_id`
                  LEFT JOIN   `cbl_assessments_lu_forms`     AS fr ON fr.`form_id` = fe.`form_id`
                  WHERE       fe.`element_type` = 'item'
                  AND         i.`deleted_date` IS NULL
                  AND         fe.`deleted_date` IS NULL
                  AND         fr.`deleted_date` IS NULL
                  AND         i.rating_scale_id = ?)";

        $forms = $db->GetAll($query, array($rating_scale_id, $rating_scale_id));
        if (is_array($forms)) {
            foreach ($forms as $form_record) {
                $form_ids[] = (int)$form_record["form_id"];
            }
        }
        return $form_ids;
    }

    public static function fetchFirstRecord($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction
        );
    }

    public static function fetchCountRatingScalesWithoutTypesInUse() {
        global $db;
        $query = "SELECT COUNT(*) as `count`
                  FROM `cbl_assessment_rating_scale` AS rs
                  WHERE rs.`rating_scale_type` IS NULL
                  OR rs.`rating_scale_type` = 0
                  AND rs.`deleted_date` IS NULL";
        $result = $db->GetAll($query);
        if (!is_array($result) || empty($result)) {
            return 0;
        }
        return $result[0]["count"];
    }

    public static function fetchAllRecords($deleted_date = NULL, $sort_column = NULL, $sort_direction = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction);
    }

    public static function fetchCountAllRecordsBySearchTerm($search_value, $organisation_id, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $rating_scale_type_id = NULL) {
        $count_array = self::fetchAllRecordsBySearchTerm($search_value, $organisation_id, $limit, $offset, $sort_direction, $sort_column, $filters, $rating_scale_type_id, true);
        if (empty($count_array)) {
            return 0;
        } else {
            return $count_array[0]["count"];
        }
    }

    public static function fetchAllRecordsBySearchTerm($search_value, $organisation_id, $limit, $offset, $sort_direction, $sort_column, $filters = array(), $rating_scale_type_id = NULL, $count_only = false) {
        global $db;
        global $ENTRADA_USER; // TODO: Remove this external dependency

        if (isset($sort_column) && $tmp_input = clean_input($sort_column, array("trim", "striptags"))) {
            $sort_column = $tmp_input;
        } else {
            $sort_column = "rating_scale_title";
        }

        if (isset($sort_direction) && $tmp_input = clean_input($sort_direction, array("trim", "alpha"))) {
            $sort_direction = $tmp_input;
        } else {
            $sort_direction = "ASC";
        }

        $course_permissions = $ENTRADA_USER->getCoursePermissions();

        if ($count_only) {
            $query = "SELECT COUNT(*) as `count` ";
        } else {
            $query = "SELECT `rs`.*, `rst`.`title` AS `rating_scale_type_name` ";
        }
        if ($ENTRADA_USER->getActiveRole() != "admin" || array_key_exists("author", $filters)) {
            $query .= ", `rsa`.`author_type`, `rsa`.`author_id` ";
        }

        $query .= "
			FROM
				`cbl_assessment_rating_scale` AS `rs`
		";

        $query .= "LEFT JOIN `cbl_assessments_lu_rating_scale_types` AS `rst` ON `rst`.`rating_scale_type_id` = `rs`.`rating_scale_type` ";

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= "
					LEFT JOIN
						`cbl_assessment_rating_scale_authors` AS `rsa`
					ON
						`rsa`.`rating_scale_id` = `rs`.`rating_scale_id`
						AND `rsa`.`author_type` = 'proxy_id'
						AND `rsa`.`author_id` IN (".implode(",", array_keys($filters["author"])).")";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= "
					LEFT JOIN
						`cbl_assessment_rating_scale_authors` AS `rsa2`
					ON
						`rsa2`.`rating_scale_id` = `rs`.`rating_scale_id`
						AND `rsa2`.`author_type` = 'organisation_id'
						AND `rsa2`.`author_id` IN (".implode(",", array_keys($filters["organisation"])).")";
            }

            if (array_key_exists("course", $filters)) {
                $query .= "
					LEFT JOIN
						`cbl_assessment_rating_scale_authors` AS `rsa3`
					ON
						`rsa3`.`rating_scale_id` = `rs`.`rating_scale_id`
						AND `rsa3`.`author_type` = 'course_id'
						AND `rsa3`.`author_id` IN (".implode(",", array_keys($filters["course"])).")";
            }
        } else {
            if ($ENTRADA_USER->getActiveRole() != "admin") {
                $query .= "
					LEFT JOIN
						`cbl_assessment_rating_scale_authors` AS `rsa`
					ON
						`rsa`.`rating_scale_id` = `rs`.`rating_scale_id`
						AND ("
                    .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(`rsa`.`author_type` = 'course_id' AND `rsa`.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                    .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(`rsa`.`author_type` = 'course_id' AND `rsa`.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                    .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(`rsa`.`author_type` = 'course_id' AND `rsa`.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                    .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(`rsa`.`author_type` = 'course_id' AND `rsa`.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
							(`rsa`.`author_type` = 'proxy_id' AND `rsa`.`author_id` = ".$db->qstr($ENTRADA_USER->getActiveID()).") OR
							(`rsa`.`author_type` = 'organisation_id' AND `rsa`.`author_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation()).")
						)
				";
            } else {
                if (!$count_only) {
                    $query .= "
                        LEFT JOIN
                            `cbl_assessment_rating_scale_authors` AS `rsa`
                        USING (`rating_scale_id`)
                    ";
                }
            }
        }

        $query .= "
			WHERE
				`rs`.`deleted_date` IS NULL
				AND `rs`.`organisation_id` = $organisation_id ";

        if ($search_value) {
            $query .= "AND `rs`.`rating_scale_title` LIKE (?)";
        }

        if ($rating_scale_type_id) {
            $query .= " AND `rst`.`rating_scale_type_id` = $rating_scale_type_id "; // filter by a particular type
        } else if ($rating_scale_type_id === 0) {
            $query .= " AND (`rst`.`rating_scale_type_id` = 0 OR `rst`.`rating_scale_type_id` IS NULL)"; // filter by "no type"
        } else if ($rating_scale_type_id === null) {
            // No filter set, return all types
        }

        if ($filters) {
            if (array_key_exists("scale_type", $filters)) {
                $query .= "
					AND `rst`.`deleted_date` IS NULL
					AND `rst`.`rating_scale_type_id` IN (".implode(",", array_keys($filters["scale_type"])).")
				";
            }

            if (array_key_exists("author", $filters)) {
                $query .= "
					AND `rsa`.`deleted_date` IS NULL
					AND `rsa`.`author_id` IN (".implode(",", array_keys($filters["author"])).")
				";
            }

            if (array_key_exists("organisation", $filters)) {
                $query .= "
					AND `rsa2`.`deleted_date` IS NULL
					AND `rsa2`.`author_id` IN (".implode(",", array_keys($filters["organisation"])).")
				";
            }

            if (array_key_exists("course", $filters)) {
                $query .= "
					AND `rsa3`.`deleted_date` IS NULL
					AND `rsa3`.`author_id` IN (".implode(",", array_keys($filters["course"])).")
				";
            }
        } else if ($ENTRADA_USER->getActiveRole() != "admin") {
            $query .= " AND `rsa`.`deleted_date` IS NULL";
        }

        if (!$count_only) {
            $query .= "
                GROUP BY
                    `rs`.`rating_scale_id`
                ORDER BY
                    `rs`.`$sort_column` $sort_direction";

            if ($limit) {
                $query .= " LIMIT $limit";
            }
            if ($offset) {
                $query .= " OFFSET $offset";
            }
        }
        $conditions = array();
        if ($search_value) {
            $conditions[] = $search_value;
        }
        $results = $db->GetAll($query, $conditions);
        return $results;
    }

    public static function saveFilterPreferences($filters = array()) {
        global $db;

        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    switch ($filter_type) {
                        case "scale_type" :
                            $scale_type = Models_Assessments_RatingScale_Type::fetchRowByID($target);
                            if ($scale_type) {
                                $target_label = $scale_type->getTitle();
                            }
                            break;
                        case "course" :
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                            break;
                        case "author" :
                            $query = "
								SELECT
									CONCAT(`firstname`, ' ', `lastname`) AS `fullname`
								FROM
									`".AUTH_DATABASE."`.`user_data`
								WHERE
									`id` = ?
							";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["fullname"];
                            }
                            break;
                        case "organisation" :
                            $query = "
								SELECT
									*
								FROM
									`".AUTH_DATABASE."`.`organisations`
								WHERE
									`organisation_id` = ?
							";
                            $results = $db->GetRow($query, array($target));
                            if ($results) {
                                $target_label = $results["organisation_title"];
                            }
                            break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }

    public static function fetchAllByScaleTypeID($scale_type_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rating_scale_type", "value" => $scale_type_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * Fetch the rating scale and the rating scale type by the rating scale id
     * @param $rating_scale_id
     * @return array
     */
    public function fetchMilestoneScaleAndTypeByID($rating_scale_id) {
        global $db;

        $query = "  SELECT * FROM `cbl_assessment_rating_scale` AS rs
                    JOIN `cbl_assessments_lu_rating_scale_types` AS rst
                    ON rs.`rating_scale_type` = rst.`rating_scale_type_id`
                    WHERE rs.`rating_scale_id` = ?
                    AND rst.`shortname` = 'milestone_ec'";

        return $db->getRow($query, array($rating_scale_id));
    }
}