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

class Models_Schedule extends Models_Base {
    protected   $schedule_id, $title, $description, $schedule_type, $schedule_parent_id, $organisation_id, $course_id, $region_id,
                $facility_id, $start_date, $end_date, $block_type_id, $schedule_order, $copied_from, $created_date, $created_by, $updated_date,
                $updated_by, $deleted_date, $cperiod_id, $draft_id, $code;

    protected $child_types = array(
        "organisation"  => "academic_year",
        "academic_year" => "stream",
        "stream"        => "block"
    );

    protected static $table_name = "cbl_schedule";
    protected static $primary_key = "schedule_id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->schedule_id;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getScheduleType() {
        return $this->schedule_type;
    }

    public function getChildScheduleType() {
        return $this->child_types[$this->schedule_type];
    }

    public function getScheduleParentID() {
        return $this->schedule_parent_id;
    }

    public function getBlockTypeID() {
        return $this->block_type_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getRegionID() {
        return $this->region_id;
    }

    public function getFacilityID() {
        return $this->facility_id;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
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

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    public function getDraftID() {
        return $this->draft_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getCode() {
        return $this->code;
    }

    public function getOrder() {
        return $this->schedule_order;
    }

    public function getCopiedFrom() {
        return $this->copied_from;
    }

    public function getChildren() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $this->organisation_id, "method" => "="),
            array("key" => "schedule_parent_id", "value" => $this->schedule_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public function getChildrenByDate($date_time) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $this->organisation_id, "method" => "="),
            array("key" => "schedule_parent_id", "value" => $this->schedule_id, "method" => "="),
            array("key" => "start_date", "value" => $date_time, "method" => "<="),
            array("key" => "end_date", "value" => $date_time, "method" => ">="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS"),
            "=", "AND", "start_date", $sort_order = "ASC"
        ));
    }

    public static function fetchAllByParentAndEndDateInRange($organisation_id, $parent_id, $start_date, $end_date) {
        global $db;

        $output = array();

        $query = "SELECT * FROM `".static::$table_name."`
                    WHERE `organisation_id` = ?
                    AND `schedule_parent_id` = ?
                    AND `end_date` >= ?
                    AND `end_date` <= ?
                    AND `deleted_date` IS NULL";
        $results = $db->GetAll($query, array($organisation_id, $parent_id, $start_date, $end_date));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

    }

    public static function fetchAllByParentAndDateRange($organisation_id, $parent_id, $start_date, $end_date, $include_deleted = false) {
        global $db;

        $output = array();

        $AND_deleted = "AND `deleted_date` IS NULL";
        if ($include_deleted) {
            $AND_deleted = "";
        }

        $query = "SELECT * FROM `".static::$table_name."`
                    WHERE `organisation_id` = ?
                    AND `schedule_parent_id` = ?
                    AND (
                            (`start_date` >= ? AND `start_date` <= ?)
                            OR (`end_date` >= ? AND `end_date` <= ?)
                            OR (`start_date` <= ? AND `end_date` >= ?)
                        )
                    $AND_deleted";
        $results = $db->GetAll($query, array($organisation_id, $parent_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        return $output;
    }

    public static function fetchAllByParentAndDateRangeGroupedByScheduleID($organisation_id, $parent_id, $ids_array, $start_date, $end_date) {
        global $db;
        $output = array();

        $AND_audience_value = "AND b.`audience_value` IS NOT NULL";
        if (!empty($ids_array)) {
            $cleaned_ids = array_map(function ($a) {
                return clean_input($a, array("striptags", "int"));
            }, $ids_array);
            $in_str = implode(",", $cleaned_ids);
            if ($in_str) {
                $AND_audience_value = "AND b.`audience_value` IN ($in_str)";
            }
        }

        $query = "SELECT DISTINCT b.*,  a.*, c.`name` AS `block_type_name`, c.`number_of_blocks`
                  FROM `cbl_schedule` AS a
                  LEFT JOIN `cbl_schedule_audience` AS b ON b.schedule_id = a.schedule_id AND b.audience_type = 'proxy_id'
                  LEFT JOIN `cbl_schedule_lu_block_types` AS c ON a.`block_type_id` = c.`block_type_id`

                  WHERE a.`organisation_id` =?
                  AND a.`schedule_parent_id` = ?
                  AND (
                        (a.`start_date` >= ? AND a.`start_date` <= ?) OR
                        (a.`end_date` >= ? AND a.`end_date` <= ?) OR
                        (a.`start_date` <= ? AND a.`end_date` >= ?)
                  )
                  AND a.`deleted_date` IS NULL
                  AND b.`deleted_date` IS NULL
                  $AND_audience_value
                  AND c.`deleted_date` IS NULL
                  GROUP BY a.`schedule_id`";

        $results = $db->GetAll($query, array($organisation_id, $parent_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        return $output;
    }

    public static function fetchRowByID($schedule_id, $search_term = null, $deleted_date = NULL) {
        global $db;
        $schedule = false;

        $query = "  SELECT a.*
                    FROM `cbl_schedule` AS a
                    LEFT JOIN `curriculum_periods` AS b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    WHERE `schedule_id` = ?
                    AND `title` LIKE ?";


        $result = $db->GetRow($query, array($schedule_id, "%". $search_term ."%"));
        if ($result) {
            $schedule = new self($result);
        }
        
        return $schedule;
    }

    public static function fetchRowByIDStatus($schedule_id, $status = "live", $group_by = false) {
        global $db;
        $schedule = false;

        $query = "  SELECT a.*
                    FROM `cbl_schedule` AS a
                    LEFT JOIN `curriculum_periods` AS b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    JOIN `cbl_schedule_drafts` AS c
                    ON a.`draft_id` = c.`cbl_schedule_draft_id`
                    WHERE a.`schedule_id` = ?
                    AND c.`status` = ?";

        if ($group_by) {
            $query .= " GROUP BY a.`draft_id`";
        }

        $result = $db->GetRow($query, array($schedule_id, $status));
        if ($result) {
            $schedule = new self($result);
        }

        return $schedule;
    }

    public static function fetchDraftIdRowByID($schedule_id, $deleted_date = NULL) {
        global $db;

        $query = "  SELECT a.*
                    FROM `cbl_schedule` AS a
                    LEFT JOIN `curriculum_periods` AS b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    WHERE `schedule_id` = ?
                    GROUP BY a.`draft_id`";
        $result = $db->GetRow($query, array($schedule_id));
        return new self($result);
    }

    public static function fetchRowByIDScheduleType($schedule_id, $schedule_type, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "="),
            array("key" => "schedule_type", "value" => $schedule_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function getRow($schedule_id = null, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByOrgID($org_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $org_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByOrgByTypeByParent($org_id, $parent_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "organisation_id", "value" => $org_id, "method" => "="),
            array("key" => "schedule_parent_id", "value" => $parent_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchClinicalSchedules($org_id, $status = "draft") {
        global $db;

        $s = false;

        $query = "SELECT *
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule_drafts` AS b ON a.`draft_id` = b.`cbl_schedule_draft_id`
                    WHERE a.`schedule_parent_id` = 0
                    AND a.`organisation_id` = ?
                    AND b.`status` = ?";
        $results = $db->GetAll($query, array($org_id, $status));
        if ($results) {
            foreach ($results as $result) {
                $s[] = new self($result);
            }
        }
        return $s;
    }

    public static function fetchAllProgramsByOrg($org_id) {
        global $db;

        $query = "SELECT IF (COUNT(a.`schedule_id`) > 1, CONCAT(b.`course_code`, ' - ', b.`course_name`), a.`title`) AS `title`, a.`schedule_type`, a.`course_id`, a.`schedule_id`
                    FROM `cbl_schedule` AS a
                    JOIN `courses` AS b
                    ON a.`course_id` = b.`course_id`
                    WHERE (a.`schedule_parent_id` = 0
                    OR a.`schedule_parent_id` IS NULL)
                    AND (b.`organisation_id` = ".$org_id.")
                    GROUP BY IF (a.`course_id` IS NOT NULL, a.`course_id`, a.`schedule_id`)";
        return $db->GetAll($query);
    }

    public static function fetchAllRecords($start_date = NULL, $end_date = NULL, $schedule_parent_id = NULL, $course_id = NULL, $type = NULL, $deleted_date = NULL) {
        global $db;

        $s = false;

        $query = "SELECT * FROM `cbl_schedule` WHERE 1 ";

        if ($start_date && $end_date) {
            $query .= "AND ((`start_date` >= {$db->qstr($start_date)} AND `start_date` <= {$db->qstr($end_date)})";
            $query .= "OR  (`end_date` >= {$db->qstr($start_date)} AND `end_date` <= {$db->qstr($end_date)}))";
        }
        $query .= (is_null($schedule_parent_id)) ?  " AND (`schedule_parent_id` IS NULL OR `schedule_parent_id` = 0)" : " AND `schedule_parent_id` = {$db->qstr($schedule_parent_id)}";
        $query .= (!is_null($course_id)) ?          " AND `course_id` = {$db->qstr($course_id)}" : "";
        $query .= (!is_null($type)) ?               " AND `schedule_type` = {$db->qstr($type)}" : "";
        $query .= (!is_null($deleted_date)) ?       " AND `deleted_date` <= {$db->qstr($deleted_date)}" : " AND `deleted_date` IS NULL";
        $query .= " ORDER BY `start_date`";

        $results = $db->GetAll($query);

        if ($results) {
            $s = array();
            foreach ($results as $result) {
                $s[] = new self($result);
            }
        }
        return $s;
    }

    public static function fetchAllByCPeriod($cperiod_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "cperiod_id", "value" => $cperiod_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllTemplatesByCPeriodID($cperiod_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "cperiod_id", "value" => $cperiod_id, "method" => "="),
            array("key" => "schedule_parent_id",    "value" => "0", "method" => "="),
            array("key" => "schedule_type",         "value" => "stream", "method" => "="),
            array("key" => "deleted_date",          "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllBlockTemplatesByCPeriodIDBlockTypeID($cperiod_id, $block_type_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "cperiod_id",            "value" => $cperiod_id, "method" => "="),
            array("key" => "schedule_type",         "value" => "block", "method" => "="),
            array("key" => "block_type_id",         "value" => $block_type_id, "method" => "="),
            array("key" => "deleted_date",          "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "schedule_id");
    }

    public static function fetchAllByDraftID($draft_id, $type = NULL, $deleted_date = NULL) {
        $self = new self();

        $constraints = array(
            array("key" => "draft_id", "value" => $draft_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if (!is_null($type)) {
            $constraints[] = array("key" => "schedule_type", "value" => $type, "method" => "=");
        }

        return $self->fetchAll($constraints);
    }

    public static function fetchAllByDraftIDScheduleTypeBlockTypeIDScheduleOrder($draft_id, $type, $block_type_id, $schedule_order, $deleted_date = NULL) {
        $self = new self();

        $constraints = array(
            array("key" => "draft_id", "value" => $draft_id, "method" => "="),
            array("key" => "schedule_type", "value" => $type, "method" => "="),
            array("key" => "block_type_id", "value" => $block_type_id, "method" => "="),
            array("key" => "schedule_order", "value" => $schedule_order, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        return $self->fetchAll($constraints, $default_method = "=", $default_mode = "AND", $sort_column = "title", $sort_order = "ASC");
    }

    public static function fetchRowByAudienceValueAudienceType($audience_value, $audience_type, $current_schedule = true, $current_date = "") {
        global $db;
        $AND_date_greater = "";
        $AND_date_less = "";
        $ORDER_BY = "";
        $data = false;

        if ($current_schedule) {
            if ($current_date != "" && $current_date != null) {
                $AND_date_greater = " AND " . $db->qstr($current_date) . " >= a.`start_date`";
                $AND_date_less = " AND " . $db->qstr($current_date) . " <= a.`end_date`";
            }
            $ORDER_BY = "ORDER BY a.`end_date` ASC";
        } else {
            if ($current_date != "" && $current_date != null) {
                $AND_date_greater = " AND a.`start_date` >= " . $db->qstr($current_date);
            }
            $ORDER_BY = "ORDER BY a.`start_date` ASC";
        }

        $query = "  SELECT a.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule_audience` AS b
                    ON a.`schedule_id` = b.`schedule_id`
                    JOIN `cbl_schedule_drafts` AS c
                    ON c.`cbl_schedule_draft_id` = a.`draft_id`
                    WHERE b.`audience_value` = ?
                    AND b.`audience_type` = ?
                    AND c.`status` = 'live'
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    
                    $AND_date_greater
                    $AND_date_less
                    $ORDER_BY
                    ";

        $result = $db->GetRow($query, array($audience_value, $audience_type));

        if ($result) {
            $data = new self($result);
        }

        return $data;
    }

    public function getBreadCrumbData() {
        global $db;
        $query = "SELECT a.`schedule_id`, a.`title`, @pv := a.`schedule_parent_id` AS `schedule_parent_id`, a.`schedule_type` from
                    (SELECT * FROM cbl_schedule ORDER BY `schedule_id` DESC) AS a
                    JOIN
                    (SELECT @pv := ".$db->qstr($this->schedule_id).") tmp
                    WHERE a.schedule_id = @pv";
        return $results = $db->GetAll($query);
    }

    /**
     *  A recursive function to set the deleted_date of the schedule and it's children.
     */
    public function delete() {
        global $db, $ENTRADA_USER;
        $query = "SELECT * FROM `cbl_schedule` WHERE `schedule_parent_id` = ".$db->qstr($this->schedule_id);
        $children = $db->GetAll($query);

        if ($children) {
            foreach ($children as $child) {
                $schedule = new self($child);
                $schedule->delete();
            }
        }

        $slots = Models_Schedule_Slot::fetchAllByScheduleID($this->schedule_id);
        if ($slots) {
            foreach ($slots as $slot) {
                $audience = Models_Schedule_Audience::fetchAllBySlotID($slot->getID());
                if ($audience) {
                    foreach ($audience as $audience_member) {
                        $audience_member->fromArray(array("deleted_date" => time()))->update();
                    }
                }
                $slot->fromArray(array("deleted_date" => time()))->update();
            }
        }

        $this->deleted_date = time();
        $this->updated_date = time();
        $this->updated_by = $ENTRADA_USER->getActiveID();

        return $this->update();
    }
    
    public static function countAllByCourseID ($course_id = null, $schedule_type = "rotation") {
        global $db;
        
        $query = "  SELECT COUNT(`schedule_id`) as `total_schedules` FROM `cbl_schedule` 
                    WHERE `course_id` = ?
                    AND `schedule_type` = ? 
                    AND `deleted_date` IS NULL";
        $results = $db->GetRow($query, array($course_id, $schedule_type));
        return (int) $results["total_schedules"];
    }
    
    public static function countScheduleChildren ($parent_id = null) {
        global $db;
        
        $query = "  SELECT COUNT(`schedule_id`) as `total_children` FROM `cbl_schedule` 
                    WHERE `schedule_parent_id` = ?
                    AND `deleted_date` IS NULL";
        $results = $db->GetRow($query, array($parent_id));
        return (int) $results["total_children"];
    }
    
    public static function fetchAllByCourseIDScheduleType($course_id = null, $search_value = "", $schedule_type = null, $deleted_date = null) {
        global $db;
        $schedules = false;
        
        $query = "  SELECT * FROM `cbl_schedule` 
                    WHERE `deleted_date` IS NULL
                    AND `schedule_type` = " . $db->qstr($schedule_type) . "
                    AND `course_id` = " . $db->qstr($course_id) . "
                    AND 
                    (
                        `title` LIKE (". $db->qstr("%". $search_value ."%") .") OR
                        `description` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    ORDER BY `title` ASC";
        
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $schedules[] = new self($result); 
            }
        }
        
        return $schedules;
    }

    public static function fetchAllByCourseIDScheduleTypeCperiod($course_id = null, $search_value = "", $schedule_type = null, $cperiod_id = null, $deleted_date = null) {
        global $db;
        $schedules = false;

        if (is_array($course_id) && !empty($course_id)) {
            $imploded = implode(",", $course_id);
            $AND_COURSE_ID = "AND `course_id` IN ($imploded)";
        } else {
            $AND_COURSE_ID = "AND `course_id` = " . $db->qstr($course_id);
        }

        $query = "  SELECT * FROM `cbl_schedule`
                    WHERE `deleted_date` IS NULL
                    AND `schedule_type` = " . $db->qstr($schedule_type) . "
                    $AND_COURSE_ID
                    AND
                    (
                        `title` LIKE (". $db->qstr("%". $search_value ."%") .") OR
                        `description` LIKE (". $db->qstr("%". $search_value ."%") .")
                    )
                    AND `cperiod_id` = ". $db->qstr($cperiod_id) ."
                    ORDER BY `title` ASC";

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $schedules[] = new self($result);
            }
        }

        return $schedules;
    }

    public static function fetchAllByCourseIDCourseCodeScheduleType($course_id, $course_code, $schedule_type) {
        global $db;
        $schedules = false;

        if (is_array($course_id) && !empty($course_id)) {
            $imploded = implode(",", $course_id);
            $AND_COURSE_ID = "AND `course_id` IN ($imploded)";
        } else {
            $AND_COURSE_ID = "AND `course_id` = " . $db->qstr($course_id);
        }

        $query = "  SELECT * FROM `cbl_schedule`
                    WHERE `code` = ?
                    $AND_COURSE_ID                  
                    AND `schedule_type` = ?";

        $results = $db->GetAll($query, array($course_code, $schedule_type));
        if ($results) {
            foreach ($results as $result) {
                $schedules[] = new self($result);
            }
        }

        return $schedules;
    }

    public static function fetchRowByDraftIDBlockNumAndCode($draft_id, $code, $block_num) {
        global $db;
        $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    WHERE a.`draft_id` = ?
                    AND a.`schedule_parent_id` = 0
                    AND a.`code` = ?
                    AND a.`deleted_date` IS NULL
                    ORDER BY b.`end_date`
                    LIMIT 1 OFFSET ?";
        $result = $db->GetRow($query, array($draft_id, $code, ($block_num - 1)));

        if ($result) {
            return new self($result);
        } else {
            return false;
        }
    }

    public static function fetchAllByParentID($parent_id = null, $deleted_date = null, $sort_by_start = false) {
        $self = new self();
        if ($sort_by_start) {
            return $self->fetchAll(array(
                    array("key" => "schedule_parent_id", "value" => $parent_id, "method" => "="),
                    array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))),
                    "=", "AND", "start_date", "ASC");
        } else {
            return $self->fetchAll(array(
                array("key" => "schedule_parent_id", "value" => $parent_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS")))
            );
        }
    }

    public static function fetchParentByID($schedule_id, $deleted_date = NULL) {
        global $db;
        $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_parent_id` = b.`schedule_id`
                    WHERE a.`schedule_id` = ?
                    AND b.`schedule_parent_id` = 0".
                    (is_null($deleted_date) ? " AND a.`deleted_date` IS NULL AND b.`deleted_date` IS NULL" : (" AND a.`deleted_date` < ".$db->qstr($deleted_date) . " AND b.`deleted_date` < ".$db->qstr($deleted_date))) ;
        $result = $db->getRow($query, array($schedule_id));
        if ($result) {
            return new self ($result);
        } else {
            return false;
        }
    }

    public static function fetchAllByDraftIDBlockNum($draft_id, $block_num) {
        global $db;
        $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    WHERE a.`draft_id` = ?
                    AND a.`schedule_parent_id` = 0
                    AND b.`schedule_order` = ?
                    ORDER BY b.`end_date`";
        $results = $db->GetAll($query, array($draft_id, $block_num));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchRowByDraftIDCode($draft_id, $code) {
        global $db;
        $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    WHERE a.`draft_id` = ?
                    AND a.`schedule_parent_id` = 0
                    AND a.`code` = ?
                    ORDER BY b.`end_date`";
        $result = $db->GetRow($query, array($draft_id, $code));
        if ($result) {
            return new self ($result);
        } else {
            return false;
        }
    }

    public static function fetchRowByCodeIndex($code, $index, $course_id = false) {
        global $db;
        if ($course_id) {
            $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    JOIN `courses` AS c
                    ON a.`course_id` = c.`course_id`
                    WHERE (
                      (a.`code` = ? AND a.`course_id` = ?)
                      OR (CONCAT_WS('-', c.`course_code`, a.`code`) = ?)
                    )
                    AND b.`schedule_order` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    ORDER BY b.`schedule_order`";
            $constraints = array($code, $course_id, $code, $index);
        } else {
            $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    WHERE a.`code` = ?
                    AND b.`schedule_order` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    ORDER BY b.`schedule_order`";
            $constraints = array($code, $index);
        }
        $result = $db->GetRow($query, $constraints);
        if ($result) {
            return new self($result);
        } else {
            return false;
        }
    }

    public static function fetchAllByCodeIndex($code, $index, $course_id = false) {
        global $db;
        if ($course_id) {
            $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    JOIN `courses` AS c
                    ON a.`course_id` = c.`course_id`
                    WHERE (
                      (a.`code` = ? AND a.`course_id` = ?)
                      OR (CONCAT_WS('-', c.`course_code`, a.`code`) = ?)
                    )
                    AND b.`schedule_order` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    ORDER BY b.`schedule_order`";
            $constraints = array($code, $course_id, $code, $index);
        } else {
            $query = "SELECT b.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    WHERE a.`code` = ?
                    AND b.`schedule_order` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    ORDER BY b.`schedule_order`";
            $constraints = array($code, $index);
        }
        $results = $db->GetAll($query, $constraints);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return new self($output);
        } else {
            return false;
        }
    }

    public static function fetchOffService($draft_id, $cperiod_id = NULL, $course_id = NULL) {
        global $db;
        $query = "SELECT a.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_parent_id`
                    JOIN `cbl_schedule_slots` AS c
                    ON b.`schedule_id` = c.`schedule_id`
                    WHERE a.`schedule_type` = 'rotation_stream'
                    AND c.`slot_type_id` = 2
                    AND a.`draft_id` != ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND (" . (!is_null($course_id) ? "c.`course_id` = ".$db->qstr($course_id)." OR " : "") . "c.`course_id` IS NULL)
                    AND c.`deleted_date` IS NULL ".
                    (!is_null($cperiod_id) ? "AND a.`cperiod_id` = " . $db->qstr($cperiod_id) : "")."
                    GROUP BY a.`schedule_id`";
        $results = $db->GetAll($query, array($draft_id));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAllOnOffByDraftIDSlotIndex($draft_id, $index, $cperiod_id = false, $course_id = NULL) {
        global $db;

        $output = false;

        $query = "SELECT a.*
                    FROM `cbl_schedule` AS a
                    JOIN `cbl_schedule` AS b
                    ON b.`schedule_parent_id` = a.`schedule_id`
                    JOIN `cbl_schedule_slots` AS c
                    ON b.`schedule_id` = c.`schedule_id`
                    WHERE a.`schedule_parent_id` = 0
                    AND a.`schedule_type` = 'rotation_stream'
                    AND ((a.`draft_id` = ? AND c.`slot_type_id` = '1') OR (a.`draft_id` != ? AND c.`slot_type_id` = '2'))
                    AND b.`schedule_order` = ?
                    AND b.`deleted_date` IS NULL ".
                    ($cperiod_id != false ? "AND a.`cperiod_id` = ".$db->qstr($cperiod_id) : "")." ".
                    (!is_null($course_id) ? "AND (c.`course_id` = " . $db->qstr($course_id) . " OR c.`course_id` IS NULL)" : "")."
                    GROUP BY a.`schedule_id`
                    ORDER BY c.`slot_type_id`, a.`title` ASC";
        $results = $db->GetAll($query, array($draft_id, $draft_id, $index));

        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self ($result);
            }
        }

        return $output;
    }

    public static function fetchRotationsByMonth($proxy_id, $draft_id, $start_date, $end_date) {
        global $db;
        $query = "SELECT c.`schedule_id`, d.`title`, c.`schedule_order` AS `order`, d.`code`, c.`start_date` AS `start`, c.`end_date` AS `end`, c.`block_type_id`, b.`slot_type_id`, 'rotation' AS `event_type`
                    FROM `cbl_schedule_audience` AS a
                    JOIN `cbl_schedule_slots` AS b
                    ON a.`schedule_slot_id` = b.`schedule_slot_id`
                    JOIN `cbl_schedule` AS c
                    ON a.`schedule_id` = c.`schedule_id`
                    JOIN `cbl_schedule` AS d
                    ON c.`schedule_parent_id` = d.`schedule_id`
                    WHERE a.`audience_value` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    AND a.`audience_type` = 'proxy_id'
                    AND (c.`draft_id` = ? OR (c.`draft_id` != ? AND b.`slot_type_id` = '2'))
                    AND ((c.`start_date` >= ? AND c.`start_date` <= ?) OR
                         (c.`end_date` >= ? AND c.`end_date` <= ?)) OR
                         (c.`start_date` <= ? AND c.`end_date` >= ?)";
        $results = $db->GetAll($query, array($proxy_id, $draft_id, $draft_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date));
        return $results;
    }

    public static function fetchDuplicateCheck($title, $code_id, $org_id, $course_id, $draft_id) {
        global $db;

        $query = "SELECT *
        FROM `cbl_schedule`
        WHERE `title` = ".$db->qstr($title)."
        AND `code` = ".$db->qstr($code_id)."
        AND `organisation_id` = ".$db->qstr($org_id)."
        AND `course_id` = ".$db->qstr($course_id)."
        AND `draft_id` = ".$db->qstr($draft_id)." ";
        $results = $db->GetAll($query);
        return $results;
    }

    public static function fetchLastID() {
        global $db;
        $results = $db->Insert_Id();
        return $results;
    }

    public static function fetchRowByParentDateRangeDeliveryPeriodAndOffset($parent_id, $start_date, $end_date, $period_offset, $delivery_period) {
        global $db;

        switch ($delivery_period) {
            default :
            case "after-start" :
                $date_query_identifier = "(`start_date` + " . $db->qstr($period_offset).")";
            break;
            case "before-middle" :
                $date_query_identifier = "((`start_date` + ((`end_date` - `start_date`) / 2)) - " . $db->qstr($period_offset).")";
            break;
            case "after-middle" :
                $date_query_identifier = "((`start_date` + ((`end_date` - `start_date`) / 2)) + " . $db->qstr($period_offset).")";
            break;
            case "before-end" :
                $date_query_identifier = "(`end_date` - " . $db->qstr($period_offset).")";
            break;
            case "after-end" :
                $date_query_identifier = "(`end_date` + " . $db->qstr($period_offset).")";
            break;
        }

        $query = "SELECT * FROM `".static::$database_name."`.`".static::$table_name."`
                    WHERE `schedule_parent_id` = ".$db->qstr($parent_id)."
                    AND ".$date_query_identifier." BETWEEN ".$db->qstr($start_date)." AND ".$db->qstr($end_date);
        $result = $db->GetRow($query);

        if ($result) {
            return new self ($result);
        } else {
            return false;
        }
    }

    public static function fetchDistinctBlockTypesByDraftID($draft_id) {
        global $db;
        $query = "SELECT DISTINCT `block_type_id` FROM `cbl_schedule`
                  WHERE `draft_id` = ?
                  AND `block_type_id` IS NOT NULL
                  ORDER BY `block_type_id` ASC";
        $results = $db->GetAll($query, array($draft_id));

        return $results;
    }

    public static function fetchRowByParentID($schedule_parent_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_parent_id", "value" => $schedule_parent_id, "method" => "=")
        ));
    }

    public static function addAllSchedules($values) {
        global $db;

        $query = "INSERT INTO `" . DATABASE_NAME . "`.`cbl_schedule` (`schedule_id`, `title`, `code`, `description`, `schedule_type`, `schedule_parent_id`, `organisation_id`, 
        `course_id`, `region_id`, `facility_id`, `cperiod_id`, `start_date`, `end_date`, `block_type_id`, `draft_id`, `schedule_order`, `copied_from`, 
        `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES ". $values;

        $db->Execute($query);
    }

    public function createValueString() {
        $values_array = array();

        $values_array[] = "null";
        $values_array[] = $this->title ? "'$this->title'" : "null";
        $values_array[] = $this->code ? "'$this->code'" : "null";
        $values_array[] = $this->description ? "'$this->description'" : "null";
        $values_array[] = $this->schedule_type ? "'$this->schedule_type'" : "null";
        $values_array[] = $this->schedule_parent_id ? $this->schedule_parent_id : "null";
        $values_array[] = $this->organisation_id ? $this->organisation_id : "null";
        $values_array[] = $this->course_id ? $this->course_id : "null";
        $values_array[] = $this->region_id ? $this->region_id : "null";
        $values_array[] = $this->facility_id ? $this->facility_id : "null";
        $values_array[] = $this->cperiod_id ? $this->cperiod_id : "null";
        $values_array[] = $this->start_date ? $this->start_date : "null";
        $values_array[] = $this->end_date ? $this->end_date : "null";
        $values_array[] = $this->block_type_id ? $this->block_type_id : "null";
        $values_array[] = $this->draft_id ? $this->draft_id : "null";
        $values_array[] = $this->schedule_order ? $this->schedule_order : "null";
        $values_array[] = $this->copied_from ? $this->copied_from : "null";
        $values_array[] = $this->created_date ? $this->created_date : "null";
        $values_array[] = $this->created_by ? $this->created_by : "null";
        $values_array[] = $this->updated_date ? $this->updated_date : "null";
        $values_array[] = $this->updated_by ? $this->updated_by : "null";
        $values_array[] = $this->deleted_date ? $this->deleted_date : "null";

        return "(" . implode($values_array, ",") . ")";
    }

    public static function blockContainsSchedule($current_audience_membership, $repeat_count, $block_order, $block_type_id, $draft_id, $schedule_id, &$codes, $unique_block_parent_ids) {
        foreach ($current_audience_membership as $audience_member) {
            for ($i = 0; $i < $repeat_count; $i++) {
                $tmp_schedule = Models_Schedule::fetchRowByID($audience_member["schedule_id"]);

                if ($tmp_schedule->getOrder() == ($block_order + $i) && $tmp_schedule->getBlockTypeID() == $block_type_id && $tmp_schedule->getDraftID() == $draft_id) {

                    $tmp_schedule_parent = Models_Schedule::fetchRowByID($tmp_schedule->getScheduleParentID());
                    if (!in_array($tmp_schedule_parent->getID(), $unique_block_parent_ids) && !in_array($tmp_schedule_parent->getCode(), $codes[$i])) {
                        $codes[$i][] = $tmp_schedule_parent->getCode();
                    }

                    if ($tmp_schedule->getID() == $schedule_id) {
                        $membership = new Models_Schedule_Audience($audience_member);
                        $membership->fromArray(array("deleted_date" => time()))->update();
                    }
                }
            }
        }
    }
    
    public static function fetchRowByParentIDBlockTypeIDScheduleOrder($schedule_parent_id, $block_type_id, $schedule_order, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_parent_id", "value" => $schedule_parent_id, "method" => "="),
            array("key" => "block_type_id", "value" => $block_type_id, "method" => "="),
            array("key" => "schedule_order", "value" => $schedule_order, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllScheduleChangesByProxyID($proxy_id) {
        $schedule_change_data = array();
        $draft_id_list = array();

        $drafts = Models_Schedule_Draft::fetchAllByProxyID($proxy_id, "live");
        if ($drafts) {
            foreach ($drafts as $draft) {
                $draft_id_list[] = $draft->getID();
            }
            $draft_id_list = implode(",", $draft_id_list);

            if ($draft_id_list && !empty($draft_id_list)) {
                $audience_members = Models_Schedule_Audience::fetchAllOnServiceByDraftID($draft_id_list);

                if ($audience_members) {
                    foreach ($audience_members as $audience_member) {
                        if (isset($audience_member["audience_value"]) && $audience_member["audience_value"]) {
                            $current_schedule = Models_Schedule::fetchRowByAudienceValueAudienceType($audience_member["audience_value"], $audience_member["audience_type"], true, strtotime("today"));
                            $next_schedule = Models_Schedule::fetchRowByAudienceValueAudienceType($audience_member["audience_value"], $audience_member["audience_type"], false, strtotime("today"));

                            if ($current_schedule && $next_schedule && $current_schedule->getScheduleParentID() != $next_schedule->getScheduleParentID()) {
                                $user = Models_User::fetchRowByID($audience_member["audience_value"]);

                                if ($user) {
                                    $current_schedule_title = $current_schedule->getTitle();
                                    $next_schedule_title = $next_schedule->getTitle();

                                    if ($current_schedule->getScheduleParentID()) {
                                        $parent = $current_schedule->fetchRowByID($current_schedule->getScheduleParentID());
                                        $current_schedule_title = $parent->getTitle();
                                    }

                                    if ($next_schedule->getScheduleParentID()) {
                                        $parent = $next_schedule->fetchRowByID($next_schedule->getScheduleParentID());
                                        $next_schedule_title = $parent->getTitle();
                                    }

                                    $user_schedule_change = array(
                                        "audience_full_name" => $user->getFullname(false),
                                        "schedule_title_before" => $current_schedule_title,
                                        "schedule_start_before" => $current_schedule->getStartDate(),
                                        "schedule_end_before" => $current_schedule->getEndDate(),
                                        "schedule_title_after" => $next_schedule_title,
                                        "schedule_start_after" => $next_schedule->getStartDate(),
                                        "schedule_end_after" => $next_schedule->getEndDate(),
                                        "schedule_old_url" => ENTRADA_URL . "/admin/rotationschedule?section=edit-draft&draft_id=" . $current_schedule->getDraftID(),
                                        "schedule_new_url" => ENTRADA_URL . "/admin/rotationschedule?section=edit-draft&draft_id=" . $next_schedule->getDraftID()
                                    );

                                    $schedule_change_data[$audience_member["audience_value"]] = $user_schedule_change;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $schedule_change_data;
    }
}