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

    public function getAudienceValue () {
        return $this->audience_value;
    }

    public function getDateRangeString() {
        global $translate;

        if ($this->getStartDate() && $this->getFinishDate()) {
            return date("F jS, Y", html_encode($this->getStartDate()))." ".$translate->_("to")." ".date("F jS, Y", html_encode($this->getFinishDate()));
        }
    }

    public static function fetchRowByID($cperiod_id) {
        $self = new self();
        return $self->fetchRow(array("cperiod_id" => $cperiod_id));
    }

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

    public static function fetchAllByCurriculumType($curriculum_type_id = 0, $active = 1) {
        $curriculum_type_id = (int) $curriculum_type_id;
        $active = (int) $active;

        $self = new self();
        return $self->fetchAll(array("curriculum_type_id" => $curriculum_type_id, "active" => $active), $default_method = "=", $default_mode = "AND", $sort_column = "cperiod_id", $sort_order = "DESC", $limit = null);
    }

    /**
     * Takes in a curriculum type id and an option search value
     * Gets all curriculum periods using the title or start and finsh date for filters
     * Returns a list of curriculum periods
     * @param $curriculum_type_id
     * @param $search_value
     * @return array
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

    public static function fetchRowByCurriculumTypeIDCourseID ($curriculum_type_id = null, $course_id = null, $active = 1) {
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

    public function fetchAllCurrent($active = 1) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "start_date", "value" => time(), "method" => "<="),
            array("key" => "finish_date", "value" => time(), "method" => ">="),
            array("key" => "active", "value" => $active, "method" => "="),
        ));
    }

}
