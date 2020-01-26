<?php

class Models_Logbook {
	
	private	$course_id;
	private	$logbook_entries;
	private	$logged_objectives;
			
	public function __construct(	$course_id              = NULL,
									$logbook_entries        = NULL,
									$logged_objectives      = NULL,
									$required_objectives    = NULL
                                ) {
        global $ENTRADA_USER, $db;
		
		$this->course_id = $course_id;
        
        if ($logbook_entries) {
            $this->logbook_entries = $logbook_entries;
        } else {
            $this->logbook_entries = Models_Logbook_Entry::fetchAll($ENTRADA_USER->GetID());
        }
        
        if (!$logged_objectives) {
            $logged_objectives = array();
            foreach ($this->logbook_entries as $entry) {
                if (!array_key_exists($entry->getCourseID(), $logged_objectives)) {
                    $logged_objectives[$entry->getCourseID()] = array();
                }
                if (@count($entry->getObjectives())) {
                    foreach ($entry->getObjectives() as $objective) {
                        $logged_objectives[$entry->getCourseID()][$objective->getID()] = $objective;
                    }
                }
            }
            
        }
        $this->logged_objectives = $logged_objectives;
        
        if (!$required_objectives) {
            $required_objectives = array();
            $query = "SELECT * FROM `course_objectives`";
            $course_objectives = $db->GetAll($query);
            foreach ($course_objectives as $course_objective) {
                $objective = Models_Objective::fetchRow($course_objective["objective_id"]);
                if ($objective && $objective->getLoggable()) {
                    if (!array_key_exists($course_objective["course_id"], $required_objectives)) {
                        $required_objectives[$course_objective["course_id"]] = array();
                    }
                    if (!array_key_exists($course_objective["course_id"], $logged_objectives) || !array_key_exists($objective->getID(), $logged_objectives[$course_objective["course_id"]])) {
                        $required_objectives[$course_objective["course_id"]][$objective->getID()] = $objective;
                    }
                }
            }
        }
        $this->required_objectives = $required_objectives;
		
	}
    
    public function getCourseID() {
        return $this->course_id;
    }
    
    public function setCourseID($course_id) {
        if ((int)$course_id) {
            $this->course_id = ((int) $course_id);
            return true;
        } else {
            return false;
        }
    }
    
    public function getAllRequiredObjectives($course_id = false) {
        global $db;
        
        if (!$course_id) {
            $course_id = $this->getCourseID();
        }
        
        $objectives = array();
        
        $query = "SELECT `objective_id` FROM `course_objectives`
                    ".($course_id ? "WHERE `course_id` = ".$db->qstr($course_id) : "");
        $objective_ids = $db->GetAll($query);
        if ($objective_ids) {
            $objective_ids_array = $objective_ids;
            foreach ($objective_ids as $objective_id) {
                $objective = Models_Objective::fetchRow($objective_id["objective_id"]);
                if ($objective) {
                    $descendant_ids = Models_Objective::getChildIDs($objective_id["objective_id"]);
                    foreach ($descendant_ids as $descendant_id) {
                        $descendant = Models_Objective::fetchRow($descendant_id);
                        if ($descendant && $descendant->getLoggable() && !(Models_Objective::getChildIDs($descendant_id))) {
                            $objectives[$descendant->getID()] = $descendant;
                        }
                    }
                    if (!$descendant_ids && $objective->getLoggable()) {
                        $objectives[$objective->getID()] = $objective;
                    }
                }
            }
        }
        return $objectives;
    }
    
    public function getAllRequiredObjectivesMobile($course_id = false) {
        global $db;
        
        if (!$course_id) {
            $course_id = $this->getCourseID();
        }
        
        $objectives = array();
        
        $query = "SELECT `objective_id` FROM `course_objectives`
                    ".($course_id ? "WHERE `course_id` = ".$db->qstr($course_id) : "");
        $objective_ids = $db->GetAll($query);
        if ($objective_ids) {
            $objective_ids_array = $objective_ids;
            foreach ($objective_ids as $objective_id) {
                $objective = Models_Objective::fetchRow($objective_id["objective_id"]);
                if ($objective) {
                    $descendant_ids = Models_Objective::getChildIDs($objective_id["objective_id"]);
                    foreach ($descendant_ids as $descendant_id) {
                        $descendant = Models_Objective::fetchRow($descendant_id);
                        if ($descendant && $descendant->getLoggable() && !(Models_Objective::getChildIDs($descendant_id))) {
                            $objectives[$descendant->getID()] = $descendant->toArray();
                        }
                    }
                    if (!$descendant_ids && $objective->getLoggable()) {
                        $objectives[$objective->getID()] = $objective->toArray();
                    }
                }
            }
        }
        return $objectives;
    }
    
    public static function getLoggingCourses () {
        global $db, $ENTRADA_ACL, $ENTRADA_USER;
        
        $query = "SELECT * FROM `courses`
                    WHERE `course_id` IN (
                        SELECT DISTINCT `course_id` FROM `course_objectives` AS a
                        JOIN `global_lu_objectives` AS b
                        ON a.`objective_id` = b.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS c
                        ON b.`objective_id` = c.`objective_parent`
                        LEFT JOIN `global_lu_objectives` AS d
                        ON c.`objective_id` = d.`objective_parent`
                        LEFT JOIN `global_lu_objectives` AS e
                        ON d.`objective_id` = e.`objective_parent`
                        WHERE (b.`objective_loggable` = 1 OR c.`objective_loggable` = 1 OR d.`objective_loggable` = 1 OR e.`objective_loggable` = 1)
                    )
                    AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND `course_active` = 1";
        $courses = $db->GetAll($query);
        $available_courses = array();
        if ($courses) {
            foreach ($courses as $course) {
                if ($ENTRADA_ACL->amIAllowed(new CourseResource($course["course_id"], $course["organisation_id"]), "read")) {
                    $available_courses[] = $course;
                }
            }
        }
        if ($available_courses) {
            return $available_courses;
        } else {
            return false;
        }
    }
    
    public static function getInstitutions () {
        global $db;
        $query = "SELECT * FROM `logbook_lu_sites` WHERE `site_active` = 1";
        $institutions = $db->GetAll($query);
        if ($institutions) {
            return $institutions;
        } else {
            return false;
        }
    }
    
    public static function getLocations () {
        global $db;
        $query = "SELECT * FROM `logbook_lu_locations` WHERE `location_active` = 1";
        $locations = $db->GetAll($query);
        if ($locations) {
            return $locations;
        } else {
            return false;
        }
    }
    
    public static function getAgeRanges () {
        global $db;
        $query = "SELECT * FROM `logbook_lu_ageranges` WHERE `agerange_active` = 1";
        $ageranges = $db->GetAll($query);
        if ($ageranges) {
            return $ageranges;
        } else {
            return false;
        }
    }
}