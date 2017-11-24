<?php

class Models_Objective {

	private	$objective_id,
			$objective_code,
			$objective_name,
			$objective_description,
            $objective_secondary_description,
			$objective_parent,
            $objective_set_id,
            $associated_objective,
			$objective_order,
			$objective_loggable,
			$objective_active,
			$updated_date,
			$updated_by;

	public function __construct(	$objective_id = NULL,
                                    $objective_code = NULL,
                                    $objective_name = NULL,
                                    $objective_description = NULL,
                                    $objective_secondary_description = NULL,
                                    $objective_parent = NULL,
                                    $objective_set_id = NULL,
                                    $associated_objective = NULL,
                                    $objective_order = NULL,
                                    $objective_loggable = NULL,
                                    $objective_active = 1,
                                    $updated_date = NULL,
                                    $updated_by = NULL) {

		$this->objective_id = $objective_id;
		$this->objective_code = $objective_code;
		$this->objective_name = $objective_name;
		$this->objective_description = $objective_description;
        $this->objective_secondary_description = $objective_secondary_description;
		$this->objective_parent = $objective_parent;
        $this->objective_set_id = $objective_set_id;
        $this->associated_objective = $associated_objective;
		$this->objective_order = $objective_order;
		$this->objective_loggable = $objective_loggable;
		$this->objective_active = $objective_active;
		$this->updated_date = $updated_date;
		$this->updated_by = $updated_by;

	}

	public function getID() {
		return $this->objective_id;
	}

	public function getCode() {
		return $this->objective_code;
	}

	public function getName() {
		return $this->objective_name;
	}

    public function getDescription() {
        return $this->objective_description;
    }

    public function getSecondaryDescription() {
        return $this->objective_secondary_description;
    }

	public function getParent() {
		return $this->objective_parent;
	}

    public function getObjectiveSetID() {
        return $this->objective_set_id;
    }

    public function getAssociatedObjective() {
        return $this->associated_objective;
    }

	public function getOrder() {
		return $this->objective_order;
	}

	public function getDateUpdated() {
		return $this->updated_date;
	}

	public function getUpdatedBy() {
		return $this->updated_by;
	}

	public function getLoggable() {
		return $this->objective_loggable;
	}

	public function getActive() {
		return $this->objective_active;
	}

	/**
     * Traverses up the objective tree to find the root objective
     *
     * @return Models_Objective
     */
	public function getRoot(){
	    $root = $this;
	    if ((int) $this->getParent() !== 0){
            $parent = Models_Objective::fetchRow((int) $this->getParent());
            if ($parent){
                return $parent->getRoot();
            }
        }

        return $root;
    }
	
	/**
	 * Returns objects values in an array.
	 * @return Array
	 */
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars("Models_Objective");
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

    public static function fetchByOrganisation($organisation_id, $active = 1) {
		global $db;

		$objectives = false;

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_active` = ?";
					
		$results = $db->GetAll($query, array($organisation_id, $active));
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
			}
		}

        return $objectives;
    }
    
    public static function fetchAllByOrganisationParentID($organisation_id = null, $parent_id = 0, $active = 1) {
        global $db;

		$objectives = false;

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_parent` = ?
                    AND a.`objective_active` = ?
                    ORDER BY `objective_order` ASC";
        
        $results = $db->GetAll($query, array($organisation_id,$parent_id, $active));
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
			}
		}
        
        return $objectives;
    }

    public static function fetchByOrganisationSearchValue($organisation_id, $search_value = null, $parent_id = null, $active = 1) {
		global $db;

		$objectives = false;

        if (isset($search_value) && $tmp_input = clean_input(strtolower($search_value), array("trim", "striptags"))) {
            $PROCESSED["search_value"] = $tmp_input;
        } else {
            $PROCESSED["search_value"] = "";
        }

        $parent_id = clean_input($parent_id, array("trim", "int"));

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_parent` = ?
                    AND a.`objective_name` LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%") . "
                    AND a.`objective_active` = ?
                    ORDER BY a.`objective_order` ASC";

		$results = $db->GetAll($query, array($organisation_id, $parent_id, $active));
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
			}
		}

        return $objectives;
    }

    public static function fetchAllByParentID($organisation_id = null, $parent_id = null, $active = 1) {
		global $db;

		$objectives = false;

        $parent_id = clean_input($parent_id, array("trim", "int"));

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_parent` = ?
                    AND a.`objective_active` = ?
                    ORDER BY a.`objective_order` ASC";

		$results = $db->GetAll($query, array($organisation_id, $parent_id, $active));
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
			}
		}

        return $objectives;
    }

    public static function countObjectiveChildren ($objective_id = null, $active = 1) {
        global $db;

        $query = "  SELECT COUNT(a.`objective_id`) AS `total_children` FROM `global_lu_objectives` AS a
                    WHERE a.`objective_parent` = ?
                    AND a.`objective_active` = ?";

        $result = $db->GetRow($query, array($objective_id, $active));

        if ($result) {
            return $result["total_children"];
        } else {
            return 0;
        }

    }

    public static function fetchAll($parent_id = NULL, $active = 1) {
		global $db;

		$objectives = false;

		$query = "SELECT * FROM `global_lu_objectives` WHERE `objective_active` = ?".(isset($parent_id) && ($parent_id || $parent_id === 0) ? " AND `objective_parent` = ?" : "");
		$results = $db->GetAll($query, array($active, $parent_id));
		if ($results) {
			foreach ($results as $result) {
				$objective = new self();
				$objectives[$result["objective_id"]] = $objective->fromArray($result);
			}
		}

        return $objectives;
    }

    public static function fetchObjectiveSet($objective_id) {
        global $db;

        $parent_id = (int)$objective_id;

        if (!$parent_id) {
            return false;
        }

        $level = 0;

        do{
            $level++;
            $parent = self::fetchRow($parent_id);
            if ($parent) {
            $parent_id = (int) $parent->getParent();
            } else {
                $parent = false;
                $parent_id = 0;
            }
        } while($parent_id && $level < 10);

        if ($level == 10) {
            return false;
        }

        return $parent;
    }

    public static function fetchObjectives($parent_id = 0, &$objectives, $active_only = true) {
        global $db;

        $parent_id = (int) $parent_id;

        $active_only = (bool) $active_only;

        $query = "SELECT a.*
                    FROM `global_lu_objectives` AS a
                    WHERE a.`objective_parent` = ?
                    ".($active_only ? "AND a.`objective_active` = '1'" : "")."
                    ORDER BY a.`objective_order` ASC";
        $results = $db->GetAll($query, array($parent_id));
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = $result;

                self::fetchObjectives($result["objective_id"], $objectives, $active_only);
            }

            return true;
        }

        return false;
    }

    public static function fetchObjectivesWithRelationships (&$objectives, $objective_parent, $flat = false, $objective_parents = array(), $top_parent = NULL) {
        global $db;

        $query = "SELECT a.`objective_id`, a.`objective_name` FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_parent` = ?
                    AND a.`objective_active` = 1
                    ORDER BY a.`objective_order`";
        $objective_children = $db->GetAll($query, array($objective_parent));
        if ($objective_children) {
            if ($objective_parent) {
                $objective_parents[] = $objective_parent;
            }
            foreach ($objective_children as $objective_child) {
                if (!$top_parent) {
                    $temp_top_parent = $objective_child["objective_id"];
                } else {
                    $temp_top_parent = $top_parent;
                }
                if ($flat && $objectives) {
                    $objectives[$objective_child["objective_id"]."_ID"] = array("objective_id" => $objective_child["objective_id"], "objective_name" => $objective_child["objective_name"], "objective_parents" => $objective_parents, "top_parent" => $temp_top_parent, "children" => array());
                } else {
                    $objectives[$objective_child["objective_id"]."_ID"] = array("objective_id" => $objective_child["objective_id"], "objective_name" => $objective_child["objective_name"], "objective_parents" => $objective_parents, "top_parent" => $temp_top_parent, "children" => array());
                }
                self::fetchObjectivesWithRelationships($objectives, $objective_child["objective_id"], $flat, $objective_parents, $temp_top_parent);
                $children = array_slice($objectives, (array_search($objective_child["objective_id"]."_ID", array_keys($objectives)) + 1));
                if (is_array($children) && count($children)) {
                    foreach ($children as $objective) {
                        if ($flat) {
                            $objectives[$objective_child["objective_id"]."_ID"]["children"][] = $objective["objective_id"];
                        } else {
                            $objectives[$objective_child["objective_id"]."_ID"]["children"][] = $objective;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    public static function fetchObjectivesMappedTo($objective_id = 0) {
        global $db;

        $objective_id = (int) $objective_id;

        $output = array();

        if ($objective_id) {
            $query = "SELECT b.*
                        FROM linked_objectives AS a
                        JOIN global_lu_objectives AS b
                        ON b.objective_id = a.target_objective_id
                        WHERE a.objective_id = ?
                        ORDER BY b.objective_order ASC";
            $output = $db->GetAll($query, array($objective_id));
        }

        return $output;
    }

    public static function fetchObjectivesMappedFrom($objective_id = 0) {
        global $db;

        $objective_id = (int) $objective_id;

        $output = array();

        if ($objective_id) {
            $query = "SELECT b.*
                        FROM linked_objectives AS a
                        JOIN global_lu_objectives AS b
                        ON b.objective_id = a.objective_id
                        WHERE a.target_objective_id = ?
                        ORDER BY b.objective_order ASC";
            $output = $db->GetAll($query, array($objective_id));
        }

        return $output;
    }

    public static function descendantInArray($objective_id, $objective_ids_array, $first_level = false) {
        global $db;
        if (!$first_level && in_array($objective_id, $objective_ids_array)) {
            return true;
        }
        $found = false;
        $children = self::fetchAll($objective_id);
        if (!$children || !@count($children)) {
            return false;
        } else {
            foreach ($children as $child) {
                if (self::descendantInArray($child->getID(), $objective_ids_array)) {
                    $found = true;
                    break;
                }
            }
        }
        return $found;
    }

    /* @return bool|Models_Objective */
    public static function fetchRow($objective_id = 0, $active = 1) {
        global $db;

		$return = false;

		if ($objective_id != 0) {
			$query = "SELECT * FROM `global_lu_objectives` WHERE `objective_id` = ? AND `objective_active` = ?";
			$result = $db->GetRow($query, array($objective_id, $active));
			if ($result) {
				$objective = new self();
				$return = $objective->fromArray($result);
			}
		}

        return $return;
    }

    public function insertOrganisationId($organisation_id) {
        global $db;
        $arr = array("objective_id" => $this->objective_id, "organisation_id" => $organisation_id);
        return (bool)$db->AutoExecute("`objective_organisation`", $arr, "INSERT");
    }

    /* @return bool|Models_Objective */
    public static function fetchRowByName($objective_name, $active = 1) {
        global $db;

        $organisation_id = (int) $organisation_id;

		$return = false;

        $query = "SELECT a.*
                  FROM `global_lu_objectives` AS a
                  JOIN `objective_organisation` AS b
                  ON b.`objective_id` = a.`objective_id`
                  WHERE b.`organisation_id` = ?
                  AND a.`objective_name` = ?
                  AND a.`objective_active` = ?";
        $result = $db->GetRow($query, array($organisation_id, $objective_name, $active));
        if ($result) {
            $objective = new self();
            $return = $objective->fromArray($result);
        }

        return $return;
    }

    public function insert() {
		global $db;

		if ($db->AutoExecute("`global_lu_objectives`", $this->toArray(), "INSERT")) {
			$this->objective_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
    }

	public function update() {
		global $db;

		if ($db->AutoExecute("`global_lu_objectives`", $this->toArray(), "UPDATE", "`objective_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}

    }

    public function delete() {
        $this->objective_active = false;
		return $this->update();
    }

    public static function getChildIDs($objective_id) {
        global $db;

        $objective_ids = array();
        $query = "SELECT `objective_id` FROM `global_lu_objectives` WHERE `objective_parent` = ".$db->qstr($objective_id);
        $child_ids = $db->GetAll($query);
        if ($child_ids) {
            foreach ($child_ids as $child_id) {
                $objective_ids[] = $child_id["objective_id"];
                $grandchild_ids = Models_Objective::getChildIDs($child_id["objective_id"]);
                if ($grandchild_ids) {
                    foreach ($grandchild_ids as $grandchild_id) {
                        $objective_ids[] = $grandchild_id;
                    }
                }
            }
        }
        return $objective_ids;
    }

    public static function getObjectiveSetDepth($objective_id, $max_depth = 0) {
        $child_ids = self::getChildIDs($objective_id);

        if (is_array($child_ids)) {
            foreach ($child_ids as $value) {
                $depth = self::getObjectiveSetDepth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    public static function fetchExplicitEventObjective($objective_id, $event_id) {
        global $db;
        $query = "SELECT c.`objective_id`, e.`objective_code`, e.`objective_name`, e.`objective_description`
                    FROM `events` AS a
                    JOIN `courses` AS b
                    ON a.`course_id` = b.`course_id`
                    JOIN `event_objectives` AS c
                    ON a.`event_id` = c.`event_id`
                    LEFT JOIN `course_objectives` AS d
                    ON a.`course_id` = d.`course_id`
                    AND c.`objective_id` = d.`objective_id`
                    JOIN `global_lu_objectives` AS e
                    ON c.`objective_id` = e.`objective_id`
                    WHERE a.`event_id` = ?
                    AND c.`objective_id` = ?
                    AND d.`cobjective_id` IS NULL";
        return $db->GetRow($query, array($event_id, $objective_id));
    }
    
    public function getByIDAndOrganisation($objective_id, $organisation_id) {
        global $db;

        $query = "SELECT a.`objective_id`
							FROM `global_lu_objectives` AS a
							JOIN `objective_organisation` AS b
							ON a.`objective_id` = b.`objective_id`
							WHERE a.`objective_id` = ?
							AND a.`objective_active` = '1'
							AND b.`organisation_id` = ?
							ORDER BY a.`objective_order` ASC";

        $result = $db->GetRow($query, array($objective_id, $organisation_id));

        if($result) {
            return $result;
        }

        return false;
    }

    public function getAllByCourseAndOrganisation($course_id, $organisation_id) {
        global $db;

        $query = "	SELECT a.* FROM `global_lu_objectives` a
                                JOIN `objective_audience` b
                                ON a.`objective_id` = b.`objective_id`
                                AND b.`organisation_id` = ?
                                WHERE (
                                        (b.`audience_value` = 'all')
                                        OR
                                        (b.`audience_type` = 'course' AND b.`audience_value` = ?)
                                    )
                                AND a.`objective_parent` = '0'
                                AND a.`objective_active` = '1'";
        $objectives = $db->GetAll($query, array($organisation_id, $course_id));

        if ($objectives) {
            return $objectives;
        }

        return false;
    }

    public function getAllMappedByCourse($course_id) {
        global $db;

        $query = "	SELECT a.*,b.`objective_type`, b.`importance`
                    FROM `global_lu_objectives` a
                    JOIN `course_objectives` b
                    ON a.`objective_id` = b.`objective_id`
                    AND b.`course_id` = ?
                    WHERE a.`objective_active` = '1'
                    AND b.`active` = '1'
                    GROUP BY a.`objective_id`
                    ORDER BY b.`importance` ASC";
        $objectives = $db->GetAll($query, array($course_id));

        if ($objectives) {
            return $objectives;
        }

        return false;
    }

    public function fetchAllChildrenByObjectiveSetID ($objective_set_id = null, $active = 1) {
        global $db;
        $objectives = array();

        $query = "  SELECT * FROM `global_lu_objectives` WHERE `objective_set_id` = ? AND `objective_parent` != 0 AND `objective_active` = ?";
        $results = $db->GetAll($query, array($objective_set_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
            }
        }

        return $objectives;
    }
}
