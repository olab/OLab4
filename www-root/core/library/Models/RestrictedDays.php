<?php

class Models_RestrictedDays {

	private $orday_id,
			$organisation_id,
			$date_type,
			$offset,
			$day,
			$month,
			$year,
            $updated_date,
            $updated_by,
			$day_active;
	
	public function __construct(
                                    $orday_id           = NULL,
                                    $organisation_id    = NULL,
                                    $date_type          = NULL,
                                    $offset             = NULL,
                                    $day                = NULL,
                                    $month              = NULL,
                                    $year               = NULL,
                                    $updated_date       = NULL,
                                    $updated_by         = NULL,
                                    $day_active         = 1
                                ) {
		$this->orday_id             = $orday_id;
		$this->organisation_id      = $organisation_id;
		$this->date_type            = $date_type;
		$this->offset               = $offset;
		$this->day                  = $day;
		$this->month                = $month;
		$this->year                 = $year;
		$this->updated_date         = $updated_date;
		$this->updated_by           = $updated_by;
		$this->day_active           = $day_active;
	}
	
	public function getID() {
		return $this->orday_id;
	}
    
	public function getOrganisationID() {
		return $this->organisation_id;
	}
	
	public function getDateType() {
		return $this->date_type;
	}
	
	public function getOffset() {
		return $this->offset;
	}
	
	public function getDay() {
		return $this->day;
	}
	
	public function getMonth() {
		return $this->month;
	}
	
	public function getYear() {
		return $this->year;
	}
	
	public function getActive() {
		return $this->day_active;
	}
    
    public function getName() {
        $name = false;
        
        $weekdays_array = array(
                                1 => "Monday",
                                2 => "Tuesday",
                                3 => "Wednesday",
                                4 => "Thursday",
                                5 => "Friday",
                                6 => "Saturday",
                                7 => "Sunday"
                                );
        $months_array = array(
                                1 => "January",
                                2 => "February",
                                3 => "March",
                                4 => "April",
                                5 => "May",
                                6 => "June",
                                7 => "July",
                                8 => "August",
                                9 => "September",
                                10 => "October",
                                11 => "November",
                                12 => "December"
                                );
        $offset_array = array(
                                1 => "First",
                                2 => "Second",
                                3 => "Third",
                                4 => "Fourth",
                                5 => "Last"
                            );
        
        
        switch ($this->getDateType()) {
            case "specific" :
                $date = mktime(12, 0, 0, $this->getMonth(), $this->getDay(), ($this->getYear() ? $this->getYear() : date("Y")));
                $name = date(($this->getYear() ? "l, " : "")."F jS".($this->getYear() ? ", Y" : ""), $date);
            break;
            case "weekly" :
                $name = $weekdays_array[$this->getDay()];
            break;
            case "computed" :
                $name = $offset_array[$this->getOffset()]." ".$weekdays_array[$this->getDay()]." of ".$months_array[$this->getMonth()];
            break;
            case "monthly" :
                $name = $offset_array[$this->getOffset()]." ".$weekdays_array[$this->getDay()]." of every month";
            break;
        }
        
        return $name;
    }
    
    public function getCalculatedDate($year = 0, $month = 0, $checkdate = 0) {
        $date = 0;
        
        if ($this->getYear()) {
            $year = $this->getYear();
        }
        
        if (!$month || $this->getDateType() == "computed") {
            $month = $this->getMonth();
        }
        
        $week_offset = $this->getOffset();
        
        $day = $this->getDay();
        
        switch ($this->getDateType()) {
            case "specific" :
                $date = mktime(12, 0, 0, $month, $day, $year);
            break;
            case "weekly" :
                $weekday = date("N", $checkdate);
                $monthday = date("j", $checkdate);
                if ($weekday != $day) {
                    $monthday = ($monthday + ($day - $weekday));
                    if ($monthday > 0) {
                        $monthdays_total = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        if ($monthday > $monthdays_total) {
                            if ($month < 12) {
                                $month++;
                            } else {
                                $month = 1;
                                $year++;
                            }
                            $monthday -= $monthdays_total;
                        }
                    } else {
                        if ($month > 1) {
                            $month--;
                        } else {
                            $month = 12;
                            $year--;
                        }
                        $monthdays_total = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        $monthday = $monthdays_total + $monthday;
                    }
                }
                $date = mktime(12, 0, 0, $month, $monthday, $year);
            break;
            case "computed" :
            case "monthly" :
                $weekdays_array = array(
                                        1 => "Monday",
                                        2 => "Tuesday",
                                        3 => "Wednesday",
                                        4 => "Thursday",
                                        5 => "Friday",
                                        6 => "Saturday",
                                        7 => "Sunday"
                                        );
                $months_array = array(
                                        1 => "January",
                                        2 => "February",
                                        3 => "March",
                                        4 => "April",
                                        5 => "May",
                                        6 => "June",
                                        7 => "July",
                                        8 => "August",
                                        9 => "September",
                                        10 => "October",
                                        11 => "November",
                                        12 => "December"
                                        );
                $offset_array = array(
                                        1 => "first",
                                        2 => "second",
                                        3 => "third",
                                        4 => "fourth",
                                        5 => "last"
                                    );
                $date = strtotime($offset_array[$week_offset]." ".$weekdays_array[$this->getDay()]." of ".$months_array[$month]." ".$year);
            break;
        }
        
        return $date;
    }
	
	/**
	 * Returns objects values in an array.
	 * @return Array
	 */
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars("Models_RestrictedDays");
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				$arr[$class_var] = $this->$class_var;
			}
		}
		return $arr;
	}
	
	/**
	 * Uses key-value pair to set object values
	 * @return Organisation
	 */
	public function fromArray($arr) {
		foreach ($arr as $class_var_name => $value) {
			$this->$class_var_name = $value;
		}
		return $this;
	}
	
    public static function fetchAll($organisation_id = 0, $active = 1) {
		global $db;
		
		$restricted_days = false;
		
		$query = "SELECT * FROM `organisation_lu_restricted_days` WHERE `organisation_id` = ? AND `day_active` = ?";
		$results = $db->GetAll($query, array($organisation_id, $active));
		if ($results) {
			foreach ($results as $result) {
				$restricted_day = new self();
				$restricted_days[$result["orday_id"]] = $restricted_day->fromArray($result);
			}
		}
		
        return $restricted_days;
    }

    public static function fetchRow($orday_id = 0) {
        global $db;
		
		$restricted_day = false;
		
		$query = "SELECT * FROM `organisation_lu_restricted_days` WHERE `orday_id` = ?";
		$result = $db->GetRow($query, array($orday_id));
		if ($result) {
			$rd = new self();
			$restricted_day = $rd->fromArray($result);
		}
		
        return $restricted_day;
    }

    public function insert() {
		global $db;
		
		if ($db->AutoExecute("`organisation_lu_restricted_days`", $this->toArray(), "INSERT")) {
			$this->orday = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
    }

    public function update() {
		global $db;
		if ($db->AutoExecute("`organisation_lu_restricted_days`", $this->toArray(), "UPDATE", "`orday_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
    }

    public function delete() {
        $this->day_active = 0;
		return $this->update();
    }
	
}
