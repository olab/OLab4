<?php

class Models_Curriculum_Period extends Models_Base {

    protected $cperiod_id,
        $curriculum_type_id,
        $start_date,
        $finish_date,
        $curriculum_period_title,
        $active;

    protected static $table_name = "curriculum_periods";
    protected static $default_sort_column = "cperiod_id";
    protected static $primary_key = "cperiod_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID () {
        return $this->cperiod_id;
    }

    public function getCperiodID()
    {
        return $this->cperiod_id;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function getCurriculumPeriodTitle()
    {
        return $this->curriculum_period_title;
    }

    public function getCurriculumTypeID()
    {
        return $this->curriculum_type_id;
    }

    public function getFinishDate()
    {
        return $this->finish_date;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    public function getPeriodText() {
        $range = date("F jS, Y", $this->getStartDate())." to ".date("F jS, Y", $this->getFinishDate());
        if ($this->getCurriculumPeriodTitle()) {
            return sprintf("%s - %s", $this->getCurriculumPeriodTitle(), $range);
        } else {
            return $range;
        }
    }

    public function getAudienceValue () {
        return $this->audience_value;
    }

    public function getDateRangeString() {
        global $translate;

        if ($this->getStartDate() && $this->getFinishDate()) {
            return date("F jS, Y", html_encode($this->getStartDate()))." ".$translate->_("to")." ".date("F jS, Y", html_encode($this->getFinishDate()));
        }
    }

    /* @return bool|Models_Curriculum_Period */
    public static function fetchRowByID($cperiod_id) {
        $self = new self();
        return $self->fetchRow(array("cperiod_id" => $cperiod_id));
    }

    /* @return bool|Models_Curriculum_Period */
    public static function fetchRowByMultipleIDAsc($cperiod_id_array = array()) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "creport_id",
                "value"     => $cperiod_id_array,
                "method"    => "IN"
            )
        );

        $results = $self->fetchAll($constraints, "=", "AND", "start_date", "start_date");

        if ($results) {
            return $results[0]->toArray();
        }

        return false;

    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public static function fetchAllByCourseID($course_id) {
        global $db;
        $query = "
        SELECT `a`.* FROM `".static::$table_name."` AS `a`
        INNER JOIN `course_audience` AS `b` ON `a`.`cperiod_id` = `b`.`cperiod_id`
        WHERE `b`.`course_id` = ?
        AND `a`.`active` = 1
        ORDER BY `a`.`finish_date` DESC";
        $results = $db->GetAll($query, array($course_id));
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        }
    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public static function fetchAllByCurriculumType($curriculum_type_id = 0, $active = 1) {
        $curriculum_type_id = (int) $curriculum_type_id;
        $active = (int) $active;

        $self = new self();
        return $self->fetchAll(array("curriculum_type_id" => $curriculum_type_id, "active" => $active), $default_method = "=", $default_mode = "AND", $sort_column = "cperiod_id", $sort_order = "DESC", $limit = null);
    }

    public static function fetchAllByCurriculumTypeID($curriculum_type_id = null, $active = 1) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "curriculum_type_id", "value" => $curriculum_type_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "="),
        ));
    }

    /**
     * Takes in a curriculum type id and an option search value
     * Gets all curriculum periods using the title or start and finsh date for filters
     * Returns a list of curriculum periods
     * @param $curriculum_type_id
     * @param $search_value
     * @return ArrayObject|Models_Curriculum_Period[]
     */
    
    public static function fetchAllByCurriculumTypeSearchTerm($curriculum_type_id, $search_value = null) {
        global $db;

        $output = array();

        $query = "      SELECT * FROM `curriculum_periods`
                        WHERE `curriculum_type_id` = ? 
                        AND `active` = 1";

        if($search_value != null) {
            $first_pos = strpos($search_value, ' ');
            $second_pos = strpos($search_value, ' ', $first_pos + 1);
            if($first_pos) {
                if (!$second_pos || $second_pos == strlen($search_value) - 1) {
                    $search_value = substr_replace($search_value, "", $first_pos, strlen($search_value) - 1);
                } else if ($first_pos != $second_pos) {
                    $search_value = substr_replace($search_value, " ", $first_pos, $second_pos - $first_pos + 1);
                }
            }

            $query .= " AND (
                                `curriculum_period_title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                                OR CONCAT( FROM_UNIXTIME(`start_date`,'%Y-%m-%d'), ' ', FROM_UNIXTIME(`finish_date`,'%Y-%m-%d') ) LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                            )";
        }

        $query .= " ORDER BY `finish_date` DESC";

        $results = $db->GetAll($query, array($curriculum_type_id));

        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public static function fetchAllByDateRangeCourseID($start_date = 0, $end_date = 0, $course_id = 0) {
        global $db;

        $start_date = (int) $start_date;
        $end_date = (int) $end_date;
        $course_id = (int) $course_id;

        $output = array();

        $query = "SELECT a.* FROM `".static::$table_name."` AS a
                  JOIN `course_audience` AS b
                  ON a.`cperiod_id` = b.`cperiod_id`
                  WHERE b.`course_id` = ?
                  AND (
                         (a.`start_date` <= ? AND a.`finish_date` >= ?)
                         OR (a.`start_date` <= ? AND a.`finish_date` >= ?)
                         OR (a.`start_date` >= ? AND a.`finish_date` <= ?)
                      )
                  AND `active` = 1";
        $results = $db->GetAll($query, array($course_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    public static function fetchAllByDateCourseID($date, $course_id) {
        global $db;
        $query = "
            SELECT `a`.* FROM `".static::$table_name."` AS `a`
            INNER JOIN `course_audience` AS `b` ON `a`.`cperiod_id` = `b`.`cperiod_id`
            WHERE `b`.`course_id` = ?
            AND `a`.`start_date` <= ?
            AND `a`.`active` = 1
            ORDER BY `a`.`finish_date` DESC";
        $results = $db->GetAll($query, array($course_id, $date));
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        }
    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public function getAllByFinishDateCurriculumType($curriculum_type_id = null, $finish_date = 0){
        global $db;

        $additional_sql = "";
        $constrains = array($curriculum_type_id);

        if ($finish_date) {
            $additional_sql .= " AND `finish_date` >= ? ";
            $constrains[]= $finish_date;
        }

        $query = "SELECT * FROM `curriculum_periods`
                  WHERE `curriculum_type_id` = ? 
                  AND `active` = 1 ".$additional_sql;

        $curriculum_periods = $db->GetAll($query, $constrains);

        if ($curriculum_periods) {
            return $curriculum_periods;
        }

        return false;
    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public static function fetchAllByCurriculumTypeIDCourseID ($curriculum_type_id = null, $course_id = null, $active = 1) {
        global $db;
        $periods = false;

        $query = "	SELECT * FROM `curriculum_periods` a
                    JOIN `course_audience` b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    WHERE a.`curriculum_type_id` = ? 
                    AND a.`active` = ?
                    AND b.`course_id` = ?
                    GROUP BY a.`cperiod_id`
                    ORDER BY a.`start_date` DESC";

        $results = $db->GetAll($query, array($curriculum_type_id, $active, $course_id));
        if ($results) {
            foreach ($results as $result) {
                $period = new self($result);
                $periods[] = $period;
            }
        }
        return $periods;
    }

    /* @return bool|Models_Curriculum_Period */
    public static function fetchLastActiveByCurriculumTypeID ($curriculum_type_id = null, $date = null, $active = 1) {
        global $db;
        $curriculum_period = false;

        $query = "  SELECT * FROM `curriculum_periods`
                    WHERE `curriculum_type_id` = ?
                    AND `finish_date` < ?
                    AND `active` = ?
                    GROUP BY `cperiod_id`
                    ORDER BY `finish_date` DESC";

        $result = $db->GetRow($query, array($curriculum_type_id, $date, $active));
        if ($result) {
            $curriculum_period  = new self($result);
        }

        return $curriculum_period;
    }

    /* @return ArrayObject|Models_Curriculum_Period[] */
    public function fetchAllCurrent($active = 1) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "start_date", "value" => time(), "method" => "<="),
            array("key" => "finish_date", "value" => time(), "method" => ">="),
            array("key" => "active", "value" => $active, "method" => "="),
        ));
    }

    public static function fetchAllCurrentIDs($active = 1, $curriculum_type_ids = null) {
        global $db;

        $query = "SELECT cperiod_id 
                  FROM `curriculum_periods`
                  WHERE `start_date` <= ?
                  AND `finish_date` >= ?
                  AND active = ?";

        if ($curriculum_type_ids) {
            $query .= " AND `curriculum_type_id` IN (".implode(", ", $curriculum_type_ids).")";
        }

        return $db->getCol($query, array(time(), time(), $active));
    }

    public function fetchAllCurrentIDsByOrganisation($organisation_id) {
        $cperiod_ids = array();
        $curriculum_type_ids = array();

        $all_ctypes = Models_Curriculum_Type::fetchAllByOrg($organisation_id);
        if ($all_ctypes) {

            foreach ($all_ctypes as $ctype) {
                $curriculum_type_ids[] = $ctype->getID();
            }
            $cperiod_ids = Models_Curriculum_Period::fetchAllCurrentIDs(1, $curriculum_type_ids);
        }

        return $cperiod_ids;
    }

    public static function fetchRowByCurriculumTypeIDCourseID($curriculum_type_id = null, $course_id = null, $active = 1) {
        global $db;
        $periods = false;

        $query = "	SELECT * FROM `curriculum_periods` a
                    JOIN `course_audience` b
                    ON a.`cperiod_id` = b.`cperiod_id`
                    WHERE a.`curriculum_type_id` = ? 
                    AND a.`active` = ?
                    AND b.`course_id` = ?
                    GROUP BY a.`cperiod_id`
                    ORDER BY a.`start_date` DESC";

        $results = $db->GetAll($query, array($curriculum_type_id, $active, $course_id));
        if ($results) {
            foreach ($results as $result) {
                $period = new self($result);
                $periods[] = $period;
            }
        }
        return $periods;
    }

    /**
     * returns a string of curriculum periods that are consumable by the Advanced Search Widget
     * @return string
     */
    public function fetchCurriculumPeriodsAdvancedSearch() {
        global $ENTRADA_USER;
        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        $data = array();
        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                $cperiods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                if ($cperiods) {
                    foreach ($cperiods as $cperiod) {
                        $data[] = array("target_id" => $cperiod->getID(), "target_label" => date("Y-m-d", $cperiod->getStartDate())." - ".date("Y-m-d", $cperiod->getFinishDate()), "level_selectable" => false, "target_children" => 1);
                    }
                }
            }
        }

        if ($data) {
            return json_encode(array("status" => "success", "data" => $data, "parent_name" => "0"));
        } else {
            return json_encode(array("status" => "error", "data" => "There were no curriculum periods found"));
        }
    }

    public static function fetchAllByCurriculumTypeIDOrganisationID($curriculum_type_id, $organisation_id) {
        global $db;

        $query = "SELECT a.* FROM `curriculum_periods` a
                  JOIN `curriculum_lu_types` b ON b.`curriculum_type_id` = a.`curriculum_type_id`
                  JOIN `curriculum_type_organisation` c ON c.`curriculum_type_id` = b.`curriculum_type_id`
                  WHERE a.`curriculum_type_id` = ?
                  AND c.`organisation_id` = ?
                  AND a.`active` = 1
                  AND b.`curriculum_type_active` = 1
                  GROUP BY a.`cperiod_id`
                  ORDER BY a.`start_date` DESC";

        $results = $db->GetAll($query, array($curriculum_type_id, $organisation_id));
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
            return $output;
        }
    }
}
