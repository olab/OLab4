<?php

class Models_Logbook_Entry {
	
	private	$lentry_id;
    private $proxy_id;
	private	$encounter_date;
    private $course_id;
    private $lsite_id;
    private $llocation_id;
    private $patient_id;
    private $agerange_id;
    private $gender;
    private $objectives;
    private $reflection;
    private $comments;
    private $updated_date;
    private $entry_active;
			
	public function __construct(	$lentry_id      = NULL,
									$proxy_id       = NULL,
									$encounter_date = NULL,
									$course_id      = NULL,
									$lsite_id       = NULL,
									$llocation_id   = NULL,
									$patient_id     = NULL,
									$agerange_id    = NULL,
									$gender         = NULL,
									$objectives     = NULL,
									$reflection     = NULL,
									$comments       = NULL,
									$updated_date   = NULL,
									$entry_active   = 1
                                ) {
		
		$this->lentry_id        = $lentry_id;
		$this->proxy_id         = $proxy_id;
		$this->encounter_date   = $encounter_date;
		$this->course_id        = $course_id;
		$this->lsite_id         = $lsite_id;
		$this->llocation_id     = $llocation_id;
		$this->patient_id       = $patient_id;
		$this->agerange_id      = $agerange_id;
		$this->gender           = $gender;
		$this->objectives       = $objectives;
		$this->reflection       = $reflection;
		$this->comments         = $comments;
		$this->updated_date     = $updated_date;
		$this->entry_active     = $entry_active;
		
	}
	
	public function getID() {
		return $this->lentry_id;
	}
    
    public function getProxyID() {
        return $this->proxy_id;
    }
    
    public function getEncounterDate() {
        return $this->encounter_date;
    }
    
    public function getCourseID() {
        return $this->course_id;
    }
    
    public function getCourse() {
        global $db;
        
        if ($this->getCourseID()) {
            $query = "SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($this->getCourseID());
            $course = $db->GetRow($query);
            return $course;
        } else {
            return false;
        }
    }
    
    public function getCourseName() {
        global $db;
        if ($this->getCourseID()) {
            $query = "SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($this->getCourseID());
            $course = $db->GetOne($query);
            return $course;
        } else {
            return false;
        }
    }
    
    public function getInstitutionID() {
        return $this->lsite_id;
    }
    
    public function getInstitution() {
        global $db;
        
        $query = "SELECT `site_name` FROM `logbook_lu_sites` WHERE `lsite_id` = ".$db->qstr($this->getInstitutionID());
        $institution = $db->GetOne($query);
        return $institution;
    }
    
    public function getLocationID() {
        return $this->llocation_id;
    }
    
    public function getLocation() {
        global $db;
        
        $query = "SELECT `location` FROM `logbook_lu_locations` WHERE `llocation_id` = ".$db->qstr($this->getLocationID());
        $location = $db->GetOne($query);
        return $location;
    }
    
    public function getPatientID() {
        return $this->patient_id;
    }
    
    public function getAgeRangeID() {
        return $this->agerange_id;
    }
    
    public function getAgeRange() {
        $query = "SELECT `agerange_name` FROM `logbook_lu_ageranges` WHERE `agerange_id` = ".$db->qstr($this->getAgeRangeID());
        $agerange = $db->GetOne($query);
        return $agerange;
    }
    
    public function getGender() {
        return $this->gender;
    }
    
    public function getObjectives() {
        return $this->objectives;
    }
    
    public function getReflection() {
        return $this->reflection;
    }
    
    public function getComments() {
        return $this->comments;
    }
    
    public function getActive() {
        return $this->entry_active;
    }
    
    public function getCourseObjectives() {
        global $db;
        
        if ($this->getCourseID()) {
            $objectives = array("required" => array(), "logged" => array(), "disabled" => array());
            $logbook = new Models_Logbook();
            $required_objectives = $logbook->getAllRequiredObjectives($this->getCourseID());
            foreach ($required_objectives as $required_objective) {
                $query = "SELECT a.`objective_id` FROM `logbook_entry_objectives` AS a
                            JOIN `logbook_entries` AS b
                            ON a.`lentry_id` = b.`lentry_id`
                            WHERE b.`entry_active` = 1
                            AND b.`course_id` = ".$db->qstr($this->getCourseID())."
                            AND a.`objective_id` = ".$db->qstr($required_objective->getID())."
                            AND a.`objective_active` = 1
                            LIMIT 0, 1";
                $objective_found = ($db->getOne($query) ? true : false);
                $objectives[($this->attachedObjectiveIsDuplicate($required_objective->getID()) ? "disabled" : ($objective_found ? "logged" : "required"))][] = $required_objective;
            }
            
            return $objectives;
        } else {
            return false;
        }
    }
    
    public function getCourseObjectivesMobile() {
        global $db;
        
        if ($this->getCourseID()) {
            $objectives = array("required" => array(), "logged" => array(), "disabled" => array());
            $logbook = new Models_Logbook();
            $required_objectives = $logbook->getAllRequiredObjectivesMobile($this->getCourseID());
            foreach ($required_objectives as $required_objective) {
                $query = "SELECT a.`objective_id` FROM `logbook_entry_objectives` AS a
                            JOIN `logbook_entries` AS b
                            ON a.`lentry_id` = b.`lentry_id`
                            WHERE b.`entry_active` = 1
                            AND b.`course_id` = ".$db->qstr($this->getCourseID())."
                            AND a.`objective_id` = ".$db->qstr($required_objective["objective_id"])."
                            AND a.`objective_active` = 1
                            LIMIT 0, 1";
                $objective_found = ($db->getOne($query) ? true : false);
                $objectives[($this->attachedObjectiveIsDuplicate($required_objective["objective_id"]) ? "disabled" : ($objective_found ? "logged" : "required"))][] = $required_objective;
            }
            
            return $objectives;
        } else {
            return false;
        }
    }
    
    public function addObjective($objective_id, $participation_level) {
        global $ENTRADA_USER;
        
        if (!$this->attachedObjectiveIsDuplicate($objective_id)) {
            $objective = Models_Objective::fetchRow($objective_id);
            if ($objective) {
                $objective_array = array();
                $objective_array["objective"] = $objective;
                $objective_array["objective_id"] = $objective_id;
                $objective_array["lentry_id"] = $this->getID();
                $objective_array["participation_level"] = $participation_level;
                $objective_array["updated_date"] = time();
                $objective_array["updated_by"] = $ENTRADA_USER->getID();
                $objective_array["objective_active"] = 1;
                $entry_objective = new Models_Logbook_Entry_Objective();
                $this->objectives[] = $entry_objective->fromArray($objective_array);
                return $entry_objective;
            }
        }
        return false;
    }
    
    public function setCourseID($course_id) {
        global $db;
        
        $query = "SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($course_id)." AND `course_active` = 1";
        $course = $db->GetRow($query);
        if ($course) {
            $old_course_id = $this->getCourseID();
            $this->course_id = $course_id;
            if ($this->getCourseObjectives()) {
                return true;
            } else {
                $this->course_id = $old_course_id;
            }
        }
        return false;
    }
	
	/**
	 * Returns objects values in an array.
	 * @return Array
	 */
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars("Models_Logbook_Entry");
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
	
    public static function fetchAll($proxy_id, $active = 1) {
		global $db;
		
		$logbook_entries = array();
		
		$query = "SELECT * FROM `logbook_entries` WHERE `proxy_id` = ".$db->qstr($proxy_id)." AND `entry_active` = ".$db->qstr($active);
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
                $entry_objectives = Models_Logbook_Entry_Objective::fetchAll($result["lentry_id"]);
                if ($entry_objectives) {
                    foreach ($entry_objectives as $entry_objective) {
                        $result["objectives"][] = $entry_objective;
                    }
                }
                $logbook_entry = new self();
                $logbook_entries[] = $logbook_entry->fromArray($result);
			}
		}
		
        return $logbook_entries;
    }

    public static function fetchRow($lentry_id = 0, $active = 1) {
        global $db;

		$logbook_entry = false;
		
		if ($lentry_id != 0) {
			$query = "SELECT * FROM `logbook_entries` WHERE `lentry_id` = ? AND `entry_active` = ?";
			$result = $db->GetRow($query, array($lentry_id, $active));
			if ($result) {
                $entry_objectives = Models_Logbook_Entry_Objective::fetchAll($result["lentry_id"]);
                if ($entry_objectives) {
                    foreach ($entry_objectives as $entry_objective) {
                        $result["objectives"][] = $entry_objective;
                    }
                }
                $le = new self();
                $logbook_entry = $le->fromArray($result);
			}
		}
		
        return $logbook_entry;
    }

    public function insert() {
		global $db;
		
		if ($db->AutoExecute("`logbook_entries`", $this->toArray(), "INSERT")) {
			$this->lentry_id = $db->Insert_ID();
            foreach ($this->objectives as $objective) {
                if ($objective->setEntryID($this->getID())) {
                    $objective->insert();
                }
            }
			return true;
		} else {
			return false;
		}
    }

	public function update() {
		global $db;
		
		if ($db->AutoExecute("`logbook_entries`", $this->toArray(), "UPDATE", "`lentry_id` = ".$db->qstr($this->getID()))) {
            $existing_objectives = Models_Logbook_Entry_Objective::fetchAll($this->getID());
            foreach ($this->getObjectives() as $objective) {
                if (!isset($existing_objectives[$objective->getObjectiveID()])) {
                    $objective->insert();
                } elseif ($existing_objectives[$objective->getObjectiveID()]->getParticipationLevel() != $objective->getParticipationLevel()) {
                    $existing_objectives[$objective->getObjectiveID()]->setParticipationLevel($objective->getParticipationLevel());
                    $existing_objectives[$objective->getObjectiveID()]->update();
                }
            }
            foreach ($existing_objectives as $existing_objective) {
                if (!$this->attachedObjectiveIsDuplicate($existing_objective->getObjectiveID())) {
                    $existing_objective->delete();
                }
            }
			return true;
		} else {
			return false;
		}
		
    }
    
    public function attachedObjectiveIsDuplicate($objective_id) {
        if (is_array($this->getObjectives())) {
            foreach ($this->getObjectives() as $objective) {
                if ($objective->getObjectiveID() == $objective_id) {
                    return true;
                }
            }
        }
        return false;
    }

    public function delete() {
        $this->objective_active = false;
		return $this->update();
    }
}