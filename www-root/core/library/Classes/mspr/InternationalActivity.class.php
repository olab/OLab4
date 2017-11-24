<?php
require_once("Classes/utility/Editable.interface.php");

class InternationalActivity implements Editable {
	private $id;
	private $student_id;
	private $title;
	private $site;
	private $start;
	private $end;
	private $location;
	
	function __construct($id, $student_id, $title, $site, $location, $start, $end) {
		$this->id = $id;
		$this->student_id = $student_id;
		$this->title = $title;
		$this->site = $site;
		$this->location = $location;
		$this->start = $start;
		$this->end = $end;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function getStudentID() {
		return $this->student_id;	
	}
	
	public function getUser() {
		return User::fetchRowByID($this->student_id);
	}

	public function getSite() {
		return $this->site;
	}
	
	public function getLocation () {
		return $this->location;
	}
	
	public function getTitle(){
		return $this->title;
	}
	
	public function getDetails() {
		$elements = array();
		$elements[] = $this->title;
		$elements[] = $this->site . ", " . $this->location;
		$details = implode("\n", $elements);
		return $details;
	}
	
	public function getStart() {
		return $this->start;
	}
	
	public function getEnd() {
		return $this->end;
	}
	
	public function getStartDate() {
		return array(
			"d" => date("j", $this->start),
			"m" => date("n", $this->start),
			"y" => date("Y", $this->start)
		);
	}
	
	public function getEndDate() {
		return array(
			"d" => date("j", $this->end),
			"m" => date("n", $this->end),
			"y" => date("Y", $this->end)
		);
	}
	
	public function getPeriod() {
		return formatDateRange($this->getStartDate(), $this->getEndDate()); 
	}
	
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_international_activities` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {
			
			$obs =  new InternationalActivity($result['id'], $result['student_id'], $result['title'], $result['site'], $result['location'], $result['start'], $result['end']);
			return $obs;
		}
	} 

	public static function create(array $input_arr) {
		extract($input_arr);
		global $db;

        if (strtotime($start) < strtotime($end)) {
            $query = "insert into `student_international_activities` (`student_id`, `title`,`site`,`location`,`start`, `end`) value (?,?,?,?,?,?)";
            if (!$db->Execute($query, array($user_id, $title, $site, $location, $start, $end))) {
                add_error("Failed to create new International Activity.");
                application_log("error", "Unable to update a student_international_activity record. Database said: " . $db->ErrorMsg());
            } else {
                add_success("Successfully added new International Activity.");
                $insert_id = $db->Insert_ID();
                return self::get($insert_id);
            }
        } else {
            add_error("Failed to create new contribution. Invalid dates");
        }
	}
	
	public function delete() {
		global $db;
		$query = "DELETE FROM `student_international_activities` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove International Activity from database.");
			application_log("error", "Unable to delete a student_international_activity record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed International Activity.");
		}		
	}
	
	public function update(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "update `student_international_activities` set
				 `title`=?, `site`=?,`location`=?, `start`=?, `end`=?
				 where `id`=?";
		
		if(!$db->Execute($query, array($title, $site, $location, $start, $end, $this->id))) {
			add_error("Failed to update International Activity.");
			application_log("error", "Unable to update a student_international_activities record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated International Activity.");
		}	
	}
}