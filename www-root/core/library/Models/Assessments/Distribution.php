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

class Models_Assessments_Distribution extends Models_Base {
    protected $adistribution_id, $one45_scenariosAttached_id, $form_id, $method, $organisation_id, $title, $description, $assessment_type, $cperiod_id, $course_id, $assessor_option, $min_submittable, $max_submittable, $repeat_targets, $submittable_by_target, $flagging_notifications, $start_date, $end_date, $release_start_date, $release_end_date, $release_date, $mandatory, $feedback_required, $distributor_timeout, $notifications, $visibility_status, $delivery_date, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_assessment_distributions";
    protected static $primary_key = "adistribution_id";
    protected static $default_sort_column = "adistribution_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adistribution_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getOne45ScenariosAttachedID() {
        return $this->one45_scenariosAttached_id;
    }

    public function getFormID() {
        return $this->form_id;
    }
    
    public function getMethod() {
        return $this->method;
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

    public function getAssessmentType() {
        return $this->assessment_type;
    }

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getAssessorOption() {
        return $this->assessor_option;
    }

    public function getMinSubmittable() {
        return $this->min_submittable;
    }

    public function getMaxSubmittable() {
        return $this->max_submittable;
    }

    public function getRepeatTargets() {
        return $this->repeat_targets;
    }

    public function getSubmittableByTarget() {
        return $this->submittable_by_target;
    }

    public function getFlaggingNotifications() {
        return $this->flagging_notifications;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function getReleaseStartDate() {
        return $this->release_start_date;
    }

    public function getReleaseEndDate() {
        return $this->release_end_date;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getMandatory() {
        return $this->mandatory;
    }

    public function getFeedbackRequired() {
        return $this->feedback_required;
    }

    public function getDistributorTimeout() {
        return $this->distributor_timeout;
    }

    public function getNotifications() {
        return $this->notifications;
    }

    public function getVisibilityStatus() {
        return $this->visibility_status;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
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

    public function setDeliveryDate($delivery_date) {
        $this->delivery_date = $delivery_date;
    }

    public static function fetchRowByID($adistribution_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDCourseIDFormID($adistribution_id, $course_id, $form_id = NULL) {
        global $db;
        $distribution = false;
        $AND_FORM_ID = "";

        if (is_array($course_id) && !empty($course_id)) {
            $imploded = implode(",", $course_id);
            $AND_COURSE_ID = "AND `course_id` IN ($imploded)";
        } else {
            $AND_COURSE_ID = "AND `course_id` = " . $db->qstr($course_id);
        }

        if (!is_null($form_id)) {
            $AND_FORM_ID = "AND `form_id` = " . $db->qstr($form_id);
        }

        $query = "  SELECT * FROM `cbl_assessment_distributions` 
                    WHERE `adistribution_id` = ? 
                    $AND_FORM_ID 
                    $AND_COURSE_ID ";

        $result = $db->GetRow($query, array($adistribution_id));

        if ($result) {
            $distribution = new self($result);
        }

        return $distribution;
    }

    public static function fetchRowByIDOrganisationID($adistribution_id, $organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByIDIgnoreDeletedDate($adistribution_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public static function fetchAllRecordsIgnoreDeletedDate() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adistribution_id", "value" => 1, "method" => ">=")));
    }

    public static function fetchAllRecordsByDate($search_date, $deleted_date = NULL) {
        global $db;
        $output = array();

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `cbl_assessment_distribution_schedule` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    WHERE (
                        (
                            b.`start_date` <= ".$db->qstr($search_date)." AND (b.`end_date` + 86400) >= ".$db->qstr($search_date)."
                        )
                    )
                    AND a.`deleted_date` IS NULL
                    UNION
                    SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `cbl_assessment_distribution_schedule` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    JOIN `cbl_schedule` AS c
                    ON b.`schedule_id` = c.`schedule_id`
                    WHERE (
                        (
                            c.`start_date` <= ".$db->qstr($search_date)." AND (c.`end_date` + 86400) >= ".$db->qstr($search_date)."
                        )
                    )
                    AND a.`deleted_date` IS NULL";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Assessments_Distribution($result);
            }
        }
        return $output;
    }

    public static function fetchAllRecordsByDateRange($start_date, $end_date, $deleted_date = NULL) {
        global $db;
        $output = array();

        $query = "SELECT * FROM `".static::$database_name."`.`".static::$table_name."`
                    WHERE (
                        (
                            (`start_date` >= ".$db->qstr($start_date)." AND `start_date` <= ".$db->qstr($end_date).")
                            OR (`end_date` >= ".$db->qstr($start_date)." AND `end_date` <= ".$db->qstr($end_date).")
                            OR (`start_date` <= ".$db->qstr($start_date)." AND `end_date` >= ".$db->qstr($end_date).")
                        )
                    )
                    AND `deleted_date` IS NULL
                    UNION (
                        SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                        JOIN `cbl_assessment_distribution_schedule` AS b
                        ON a.`adistribution_id` = b.`adistribution_id`
                        JOIN `cbl_schedule` AS c
                        ON b.`schedule_id` = c.`schedule_id`
                        WHERE (
                            (
                                (c.`start_date` >= ".$db->qstr($start_date)." AND c.`start_date` <= ".$db->qstr($end_date).")
                                OR (c.`end_date` >= ".$db->qstr($start_date)." AND c.`end_date` <= ".$db->qstr($end_date).")
                                OR (c.`start_date` <= ".$db->qstr($start_date)." AND c.`end_date` >= ".$db->qstr($end_date).")
                            )
                        )
                        AND a.`deleted_date` IS NULL
                    )
                    ORDER BY `adistribution_id` DESC";

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Assessments_Distribution($result);
            }
        }
        return $output;
    }

    public static function fetchAllByFormID($form_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "form_id", "value" => $form_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            ));
    }
    
    public function getCourseName () {
        $course = Models_Course::get($this->course_id);
        $course_name = "N/A";
        if ($course) {
            $course_name = $course->getCourseName();
        }
        return $course_name;
    }

    // TODO: Remove global $ENTRADA_USER dependency
    public static function fetchFilteredDistributions($search_value = null, $filters = array(), $offset = 0, $limit = 50) {
        global $db, $ENTRADA_USER;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "          SELECT a.`adistribution_id`, a.`title`, a.`course_id`, a.`updated_date`, a.`cperiod_id`, e.`course_name`, f.`start_date`, f.`finish_date`, f.`curriculum_period_title` FROM `cbl_assessment_distributions` AS a
                            JOIN `courses` AS e
                            ON a.`course_id` = e.`course_id`
                            JOIN `curriculum_periods` AS f
                            ON a.`cperiod_id` = f.`cperiod_id`";

        if ($filters) {
            if (array_key_exists("cperiod", $filters) && !array_key_exists("author", $filters) && !array_key_exists("course", $filters)) {
                if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                                ON a.`adistribution_id` = b.`adistribution_id`
                                AND
                                ("
                                    .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                                    .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                                    .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                                    .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                                    (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                                )";
                }

                $query .= " AND f.`cperiod_id`  IN (". implode(",", array_keys($filters["cperiod"])) .")
                            AND f.`active` = '1'";
            } else {
                if (array_key_exists("author", $filters)) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                                ON a.`adistribution_id` = b.`adistribution_id`
                                AND b.`author_type` = 'proxy_id'
                                AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
                }

                if (array_key_exists("course", $filters)) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS d
                                ON a.`adistribution_id` = d.`adistribution_id`
                                AND d.`author_type` = 'course_id'
                                AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
                }

                if (array_key_exists("cperiod", $filters)) {
                    $query .= " AND f.`cperiod_id`  IN (". implode(",", array_keys($filters["cperiod"])) .")
                                AND f.`active` = '1'";
                }
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                            ON a.`adistribution_id` = b.`adistribution_id` 
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

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());

        if($search_value != null) {
            self::removeTextBetweenDates($search_value);
            $query .= " AND
                            (
                                (
                                    a.`title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                    OR a.`description` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                    OR (
                                            f.`curriculum_period_title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                            OR CONCAT( FROM_UNIXTIME(f.`start_date`,'%Y-%m-%d'), ' ', FROM_UNIXTIME(f.`finish_date`,'%Y-%m-%d') ) LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                       )
                                    OR e.`course_name` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                )
                            )";
        }
        
        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }
            
            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }
        }
        
        $query .= " GROUP BY a.`adistribution_id`
                    ORDER BY a.`title` ASC
                    LIMIT " . (int) $offset . ", " . (int) $limit;

        return $db->GetAll($query);
    }

    // TODO: Remove global $ENTRADA_USER dependency
    public static function countAllDistributions ($search_value = null, $filters = array()) {
        global $db, $ENTRADA_USER;
        $results = false;
        $course_permissions = $ENTRADA_USER->getCoursePermissions();
        
        $query = "  SELECT COUNT(DISTINCT a.`adistribution_id`) AS `total_distributions` FROM `cbl_assessment_distributions` AS a
                    JOIN `courses` AS e
                    ON a.`course_id` = e.`course_id`
                    JOIN `curriculum_periods` AS f
                    ON a.`cperiod_id` = f.`cperiod_id`";
        
        if ($filters) {
            if (array_key_exists("cperiod", $filters) && !array_key_exists("author", $filters) && !array_key_exists("course", $filters)) {
                if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                                ON a.`adistribution_id` = b.`adistribution_id`
                                AND
                                ("
                                    .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                                    .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                                    .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                                    .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                                    (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                                )";                                
                }

                $query .= " AND f.`cperiod_id`  IN (". implode(",", array_keys($filters["cperiod"])) .")
                            AND f.`active` = '1'";
            } else {
                if (array_key_exists("author", $filters)) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                                ON a.`adistribution_id` = b.`adistribution_id`
                                AND b.`author_type` = 'proxy_id'
                                AND b.`author_id`  IN (". implode(",", array_keys($filters["author"])) .")";
                }

                if (array_key_exists("course", $filters)) {
                    $query .= " JOIN `cbl_assessment_distribution_authors` AS d
                                ON a.`adistribution_id` = d.`adistribution_id`
                                AND d.`author_type` = 'course_id'
                                AND d.`author_id`  IN (". implode(",", array_keys($filters["course"])) .")";
                }

                if (array_key_exists("cperiod", $filters)) {
                    $query .= " AND f.`cperiod_id`  IN (". implode(",", array_keys($filters["cperiod"])) .")
                                AND f.`active` = '1'";
                }
            }
        } else {
            if (!($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")) {
                $query .= " JOIN `cbl_assessment_distribution_authors` AS b
                            ON a.`adistribution_id` = b.`adistribution_id` 
                            AND 	
                            ("
                                .(isset($course_permissions["director"]) && $course_permissions["director"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["director"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoordinator"]) && $course_permissions["pcoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["pcoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["ccoordinator"]) && $course_permissions["ccoordinator"] ? "(b.`author_type` = 'course_id' AND b.`author_id` IN (".rtrim(implode(',', $course_permissions["ccoordinator"]), ',').")) OR" : "")
                                .(isset($course_permissions["pcoord_id"]) && $course_permissions["pcoord_id"] ? "(b.`author_type` = 'course_id' AND b.`author_id`IN (".rtrim(implode(',', $course_permissions["pcoord_id"]), ',').")) OR" : "")."
                                (b.`author_type` = 'proxy_id' AND b.`author_id` = " . $db->qstr($ENTRADA_USER->getActiveID()) . ")
                            )";                          
            }
        }

        $query .= " WHERE a.`deleted_date` IS NULL
                    AND a.`organisation_id` = ". $db->qstr($ENTRADA_USER->getActiveOrganisation());

        if($search_value != null) {
            self::removeTextBetweenDates($search_value);
            $query .= " AND
                            (
                                (
                                    a.`title` LIKE (" . $db->qstr("%" . $search_value . "%") . ") 
                                    OR a.`description` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                    OR (
                                            f.`curriculum_period_title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                            OR CONCAT( FROM_UNIXTIME(f.`start_date`,'%Y-%m-%d'), ' ', FROM_UNIXTIME(f.`finish_date`,'%Y-%m-%d') ) LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                       )
                                    OR e.`course_name` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                )
                            )";
        }

        if ($filters) {
            if (array_key_exists("author", $filters)) {
                $query .= " AND b.`deleted_date` IS NULL";
            }

            if (array_key_exists("course", $filters)) {
                $query .= " AND d.`deleted_date` IS NULL";
            }
        }

        $result = $db->GetRow($query);

        if ($result) {
            $results = $result["total_distributions"];
        }
        
        return $results;
    }

    /**
     * Takes in a search value and breaks it into strings in order to determine
     * if a date is within the search. Then the positions of the two spaces between the
     * dates will be used to remove anything between the dates. If only one date if passed
     * everything after the first space is removed.
     * @param $search_value
     */

    private static function removeTextBetweenDates (&$search_value) {
        $user_input = explode(" ", $search_value);
        if(!$user_input) {
            $user_input[] = $search_value;
        }

        foreach($user_input as $user_field) {
            if (DateTime::createFromFormat('Y-m-d', $user_field) !== false) {
                $first_pos = strpos($search_value, ' ');
                $second_pos = strpos($search_value, ' ', $first_pos + 1);
                if($first_pos) {
                    if (!$second_pos || $second_pos == strlen($search_value) - 1) {
                        $search_value = substr_replace($search_value, "", $first_pos, strlen($search_value) - 1);
                    } else if ($first_pos != $second_pos) {
                        $search_value = substr_replace($search_value, " ", $first_pos, $second_pos - $first_pos + 1);
                    }
                }
            }
        }
    }
    
    public static function saveFilterPreferences ($filters = array()) {
        global $db;
        
        if (!empty($filters)) {
            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";
                    $target = clean_input($target, array("int"));
                    switch ($filter_type) {
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
                        case "cperiod" :
                            $cperiod = Models_Curriculum_Period::fetchRowByID($target);
                            if ($cperiod) {
                                $target_label = ($cperiod->getCurriculumPeriodTitle() ? $cperiod->getCurriculumPeriodTitle() : date("Y-m-d", $cperiod->getStartDate()) . " to " . date("Y-m-d", $cperiod->getFinishDate()));
                            }
                        break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }
    
    public static function fetchAllCourseCoordinators ($search_value = NULL, $active = 1) {
        global $db;
        $pcoordinators = false;
        
        $query = "  SELECT a.`course_name`, a.`course_code`, b.`contact_id`, c.`id`, c.`firstname`, c.`lastname` FROM `courses` AS a
                    JOIN `course_contacts` AS b
                    ON a.`course_id` = b.`course_id`
                    JOIN `". AUTH_DATABASE ."`.`user_data` AS c
                    ON b.`proxy_id` = c.`id` 
                    WHERE a.`course_active` = ?
                    AND b.`contact_type` = 'pcoordinator'
                    AND (
                        c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .") 
                        OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    UNION
                    SELECT a.`course_name`, a.`course_code`, a.`pcoord_id` AS `contact_id`, b.`id`, b.`firstname`, b.`lastname` FROM `courses` AS a
                    JOIN `". AUTH_DATABASE ."`.`user_data` AS b
                    ON a.`pcoord_id` = b.`id`
                    WHERE a.`course_active` = ?
                    AND (
                        b.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`lastname` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )";
        
        $results = $db->GetAll($query, array($active, $active));
        if ($results) {
            $added_ids = array();
            foreach ($results as $result) {
                if (!in_array($result["id"], $added_ids)) {
                    $added_ids[] = $result["id"];
                    $pcoordinators[] = array("proxy_id" => $result["id"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
                }
            }
        }
        
        return $pcoordinators;
    }

    public static function fetchAllDelegators ($search_value = NULL, $active = 1) {
        global $db;
        $delegators = array();

        $query = "  SELECT a.`course_name`, a.`course_code`, b.`contact_id`, c.`id`, c.`firstname`, c.`lastname` FROM `". AUTH_DATABASE ."`.`user_data` AS c
                    ON b.`proxy_id` = c.`id`
                    WHERE a.`course_active` = ?
                    AND b.`contact_type` = 'pcoordinator'
                    AND (
                        c.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR c.`lastname` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    UNION
                    SELECT a.`course_name`, a.`course_code`, a.`pcoord_id` AS `contact_id`, b.`id`, b.`firstname`, b.`lastname` FROM `courses` AS a
                    JOIN `". AUTH_DATABASE ."`.`user_data` AS b
                    ON a.`pcoord_id` = b.`id`
                    WHERE a.`course_active` = ?
                    AND (
                        b.`firstname` LIKE (". $db->qstr("%". $search_value ."%") .")
                        OR b.`lastname` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )";

        $results = $db->GetAll($query, array($active, $active));
        if ($results) {
            $added_ids = array();
            foreach ($results as $result) {
                if (!in_array($result["id"], $added_ids)) {
                    $added_ids[] = $result["id"];
                    $delegators[] = array("proxy_id" => $result["id"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
                }
            }
        }

        return $delegators;
    }

    public static function fetchDistributionData($adistribution_id) {
        global $db;
        $query = "SELECT a.`adistribution_id`, a.`title`, a.`description`, a.`cperiod_id`, a.`release_date`, a.`mandatory`, a.`feedback_required`, a.`form_id`, a.`start_date`, a.`end_date`, b.`title` AS `form_title`, a.`course_id`, a.`assessor_option`, c.`schedule_type`, d.`course_name`, a.`submittable_by_target`, a.`flagging_notifications`, a.`repeat_targets`, a.`min_submittable`, a.`max_submittable`, a.`delivery_date`
                    FROM `cbl_assessment_distributions` AS a
                    JOIN `cbl_assessments_lu_forms` AS b
                    ON a.`form_id` = b.`form_id`
                    LEFT JOIN `cbl_assessment_distribution_schedule` AS c
                    ON a.`adistribution_id` = c.`adistribution_id`
                    LEFT JOIN `courses` AS d
                    ON a.`course_id` = d.`course_id`
                    WHERE a.`adistribution_id` = ?";
        return $db->GetRow($query, array($adistribution_id));
    }

    public function getTargets ($dassessment_id) {
        return Models_Assessments_Distribution_Target::getAssessmentTargets($this->getID(), $dassessment_id);
    }

    public function getAssessors ($delegator_id, $filter_start_date = false, $filter_end_date = false, $use_delegator = null) {
        return Models_Assessments_Distribution_Assessor::getAssessmentAssessors($this->adistribution_id, $delegator_id, $filter_start_date, $filter_end_date, (isset($use_delegator) && $use_delegator == true ? true : false));
    }

    public static function fetchAllByTitleCPeriodID($title, $cperiod_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "title", "method" => "=", "value" => $title),
            array("key" => "cperiod_id", "method" => "=", "value" => $cperiod_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByCourseID($course_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "course_id", "method" => "=", "value" => $course_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

}