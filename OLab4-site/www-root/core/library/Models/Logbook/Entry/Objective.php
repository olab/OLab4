<?php

class Models_Logbook_Entry_Objective {

	private $leobjective_id,
			$lentry_id,
			$objective_id,
			$objective,
            $participation_level,
            $updated_date,
            $updated_by,
			$objective_active;
	
	public function __construct(
                                    $leobjective_id         = NULL,
                                    $lentry_id              = NULL,
                                    $objective_id           = NULL,
                                    $objective              = NULL,
                                    $participation_level    = NULL,
                                    $updated_date           = NULL,
                                    $updated_by             = NULL,
                                    $objective_active       = 1
                                ) {
		$this->aobjective_id        = $leobjective_id;
		$this->lentry_id            = $lentry_id;
		$this->objective_id         = $objective_id;
		$this->objective            = $objective;
		$this->participation_level  = $participation_level;
		$this->updated_date         = $updated_date;
		$this->updated_by           = $updated_by;
		$this->objective_active     = $objective_active;
	}
	
	public function getID() {
		return $this->leobjective_id;
	}
	
	public function getObjectiveID() {
		return $this->objective_id;
	}
    
	public function getEntryID() {
		return $this->lentry_id;
	}
	
	public function getObjective() {
		return $this->objective;
	}
	
	public function getParticipationLevel() {
		return $this->participation_level;
	}
	
	public function getActive() {
		return $this->objective_active;
	}
    
    public function setEntryID($lentry_id) {
        if ((int)$lentry_id) {
            $this->lentry_id = ((int) $lentry_id);
            return true;
        } else {
            return false;
        }
    }
    
    public function setParticipationLevel($participation_level) {
        if ((int)$participation_level) {
            $this->participation_level = ((int) $participation_level);
            return true;
        } else {
            return false;
        }
    }
	
	/**
	 * Returns objects values in an array.
	 * @return Array
	 */
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars("Models_Logbook_Entry_Objective");
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
	
    public static function fetchAll($lentry_id = 0, $active = 1) {
		global $db;
		
		$entry_objectives = false;
		
		$query = "SELECT * FROM `logbook_entry_objectives` WHERE `lentry_id` = ? AND `objective_active` = ?";
		$results = $db->GetAll($query, array($lentry_id, $active));
		if ($results) {
			foreach ($results as $result) {
                if ($result["objective_id"]) {
                    $result["objective"] = Models_Objective::fetchRow($result["objective_id"]);
                }
				$entry_objective = new self();
				$entry_objectives[$result["objective_id"]] = $entry_objective->fromArray($result);
			}
		}
		
        return $entry_objectives;
    }

    public static function fetchRow($leobjective_id = 0) {
        global $db;
		
		$entry_objective = false;
		
		$query = "SELECT * FROM `logbook_entry_objectives` WHERE `leobjective_id` = ?";
		$result = $db->GetRow($query, array($leobjective_id));
		if ($result) {
            if ($result["objective_id"]) {
                $result["objective"] = Models_Objective::fetchRow($result["objective_id"]);
            }
			$leo = new self();
			$entry_objective = $leo->fromArray($result);
		}
		
        return $entry_objective;
    }

    public function insert() {
		global $db;
		
		if ($db->AutoExecute("`logbook_entry_objectives`", $this->toArray(), "INSERT")) {
			$this->leobjective_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
    }

    public function update() {
		global $db;
		if ($db->AutoExecute("`logbook_entry_objectives`", $this->toArray(), "UPDATE", "`leobjective_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
    }

    public function delete() {
        $this->objective_active = 0;
		return $this->update();
    }
	
	public static function isDuplicate($objective_id, $lentry_id, $active = 1) {
		global $db;
		$query = "SELECT * FROM `logbook_entry_objectives` WHERE `lentry_id` = ? AND `objective_active` = ? AND `objective_id` = ?";
		$duplicates = $db->GetAll($query, array($lentry_id, $active, $objective_id));
		if (!empty($duplicates)) {
			return true;
		} else {
			return false;
		}
	}
	
}
