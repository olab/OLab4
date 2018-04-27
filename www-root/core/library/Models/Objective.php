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
 * @copyright Copyright 2016 The Entrada Project. All Rights Reserved.
 *
 */

class Models_Objective implements Models_IObjective {

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
			$updated_by,
            $objective_status_id,
            $non_examinable,
            $admin_notes,
            $objective_translation_status_id;

	public function __construct(	$objective_id = NULL,
                                    $objective_code = NULL,
                                    $objective_name = NULL,
                                    $objective_description = NULL,
                                    $objective_secondary_description = NULL,
                                    $objective_parent = NULL,
                                    $objective_set_id = NULL,
                                    $associated_objective = NULL,
                                    $objective_order = NULL,
                                    $objective_loggable = 0,
                                    $objective_active = 1,
                                    $updated_date = NULL,
                                    $updated_by = NULL,
                                    $objective_status_id = 0,
                                    $non_examinable = 0,
                                    $admin_notes = NULL,
                                    $objective_translation_status_id = 0) {

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
        $this->objective_status_id = $objective_status_id;
        $this->non_examinable = $non_examinable;
        $this->admin_notes = $admin_notes;
        $this->objective_translation_status_id = $objective_translation_status_id;
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

    public function setObjectiveSetID($objective_set_id) {
        $this->objective_set_id = $objective_set_id;
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

	public function getCompletionStatus($proxy_id, $course_id) {
	    global $db;

	    $query = "SELECT count(*)
	                FROM `cbl_learner_objectives_completion`
	                WHERE `proxy_id` = ?
	                AND `course_id` = ?
	                AND `objective_id` = ?
	                AND `deleted_date` IS NULL";

	    return $db->getOne($query, array($proxy_id, $course_id, $this->objective_id));
    }

    public function getStatus() {
        return $this->objective_status_id;
    }

    public function getNonExaminable() {
        return $this->non_examinable;
    }

    public function getAdminNotes() {
        return $this->admin_notes;
    }

    public function getTranslationStatusId() {
        return $this->objective_translation_status_id;
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

	public function setObjectiveName($objective_name) {
        $this->objective_name = $objective_name;
    }

    public function setObjectiveDescription($objective_description) {
        $this->objective_description = $objective_description;
    }

    public function setObjectiveSecondaryDescription($objective_secondary_description) {
        $this->objective_secondary_description = $objective_secondary_description;
    }

    public function setObjectiveActive($objective_active) {
        $this->objective_active = $objective_active;
    }

    public function setUpdateDate($update_date) {
        $this->updated_date = $update_date;
    }

    public function setUpdateBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getObjectiveText($always_show_code = false) {
        if ($this->objective_code) {
            return $this->objective_code . ": " . $this->objective_name;
        } else {
            $is_code = preg_match("/^[A-Z]+\-[\d\.]+$/", $this->objective_name);
            if ($this->objective_description && $is_code) {
                if ($always_show_code) {
                    return sprintf("%s: %s", $this->objective_name, $this->objective_description);
                } else {
                    return $this->objective_description;
                }
            } else {
                return $this->objective_name;
            }
        }
    }

    // Returns all the active or inactive objectives for an organisation.
    // Optional objective_id will return just that objective.
    public static function fetchByOrganisation($organisation_id, $active = 1, $objective_id = NULL) {
		global $db;

		$objectives = false;

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_active` = ?";

        if (isset($objective_id)) {
            $query .= ' AND a.`objective_id` = ?';
            $results = $db->GetAll($query, array($organisation_id, $active, $objective_id));
        } else {
           $results = $db->GetAll($query, array($organisation_id, $active));
        }
					
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self(
                    $result["objective_id"],
                    $result["objective_code"],
                    $result["objective_name"],
                    $result["objective_description"],
                    $result["objective_secondary_description"],
                    $result["objective_parent"],
                    $result["objective_set_id"],
                    $result["associated_objective"],
                    $result["objective_order"],
                    $result["objective_loggable"],
                    $result["objective_active"],
                    $result["updated_date"],
                    $result["updated_by"],
                    $result["objective_status_id"],
                    $result["non_examinable"],
                    $result["admin_notes"]
                );
			}
		}

        return $objectives;
    }
    
    public static function fetchAllByOrganisationParentID($organisation_id = null, $parent_id = 0, $active = 1, $language_id = 1) {
        global $db;
        $objectives = false;
        if ($parent_id == 0) {
            $query = "  SELECT 
                        a.*
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
                    WHERE b.`organisation_id` = ?
                        AND a.`objective_parent` = ?
                        AND a.`objective_active` = ?
                        ORDER BY `objective_order` ASC";

            $results = $db->GetAll($query, array($organisation_id, $parent_id, $active));
        } else {
            $query = "  SELECT 
                        a.objective_id, 
                        a.objective_code,
                        ot.objective_description, ot.objective_name,  
                        a.objective_secondary_description, 
                        a.objective_parent, a.objective_set_id, a.objective_order, a.objective_loggable,
                        a.objective_active, a.updated_date, a.updated_by
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
                    LEFT JOIN `objective_translation` AS ot ON a.`objective_id` = ot.`objective_id` 
                    WHERE b.`organisation_id` = ?
                        AND a.`objective_parent` = ?
                        AND a.`objective_active` = ?
                        AND ot.language_id = ?
                        ORDER BY `objective_order` ASC";

            $results = $db->GetAll($query, array($organisation_id,$parent_id, $active, $language_id));
        }
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = new self(
                        $result["objective_id"], 
                        $result["objective_code"], 
                        $result["objective_name"], 
                        $result["objective_description"], 
                        $result["objective_secondary_description"], 
                        $result["objective_parent"], 
                        $result["objective_set_id"], 
                        $result["objective_order"], 
                        $result["objective_loggable"], 
                        $result["objective_active"], 
                        $result["updated_date"], 
                        $result["updated_by"]);
            }
        }
        
        return $objectives;
    }

    public static function fetchByOrganisationSearchValue($organisation_id = 0, $search_value = "", $parent_id = 0, $active = 1) {
        global $db;

        $organisation_id = (int) $organisation_id;
        $search_value = clean_input($search_value, array("striptags", "trim"));
        $parent_id = (int) $parent_id;
        $active = (int) $active;

        $objectives = array();

        if ($search_value) {
            $PROCESSED["search_value"] = $search_value;
        } else {
            $PROCESSED["search_value"] = "";
        }

        if ($organisation_id) {
            $query = "SELECT a.*
                        FROM `global_lu_objectives` AS a
                        JOIN `objective_organisation` AS b
                        ON a.`objective_id` = b.`objective_id`
                        WHERE b.`organisation_id` = ?
                        AND a.`objective_parent` = ?
                        AND a.`objective_name` LIKE " . $db->qstr("%" . $PROCESSED["search_value"] . "%") . "
                        AND a.`objective_active` = ?
                        ORDER BY a.`objective_order` ASC";
            $results = $db->GetAll($query, array($organisation_id, $parent_id, $active));
            if ($results) {
                foreach ($results as $result) {
                    $objective = new self();
                    $objectives[] = $objective->fromArray($result);
                }
            }
        }

        return $objectives;
    }

    public static function fetchByOrganisationSearchCodeName($organisation_id = 0, $search_value = "", $parent_id = 0, $limit = 0) {
        global $db;

        $organisation_id = (int) $organisation_id;
        $search_value = clean_input($search_value, array("striptags", "trim"));
        $parent_id = (int) $parent_id;
        $limit = (int) $limit;

        $query = "SELECT a.*, (a.`objective_name` = " . $db->qstr($search_value) . " OR a.`objective_code` = " . $db->qstr($search_value) . ") AS `relevance`
            FROM `global_lu_objectives` AS a
            JOIN `objective_organisation` AS b
            ON a.`objective_id` = b.`objective_id`
            WHERE b.`organisation_id` = ?
            AND a.`objective_active` = 1";

        if ($parent_id) {
            $query .= " AND a.`objective_parent` = ?";
        }

        if ($search_value) {
            $query .= " AND (a.`objective_name` LIKE " . $db->qstr("%". $search_value ."%") .
                      " OR a.`objective_code` LIKE " . $db->qstr("%". $search_value ."%") . ")";
        }

        $query .= " ORDER BY `relevance` DESC, a.`objective_order` ASC";

        if ($limit) {
            $query .= " LIMIT " . $limit;
        }

        $results = $db->GetAll($query, array($organisation_id, $parent_id));

        if ($results) {
            $objectives = array();

            foreach ($results as $result) {
                $objectives[] = new self(
                    $result["objective_id"], $result["objective_code"], $result["objective_name"],
                    $result["objective_description"], $result["objective_secondary_description"],
                    $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"],
                    $result["objective_order"], $result["objective_loggable"], $result["objective_active"],
                    $result["updated_date"], $result["updated_by"]
                );
            }

            if (count($objectives) === 1) {
                // just return the object if only one result
                return $objectives[0];
            } else {
                return $objectives;
            }
        } else {
            // return array();
            // make it easier to do an is_array/is_object iterable test
            return null;
        }
    }
    
    public static function fetchAllChildIds($organisation_id, $parent_id, $active=1) {
        global $db;
        
        $child_ids = array();
        
        //Get all first level children
        $query = 
            "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
             JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
             WHERE b.organisation_id=? AND objective_parent = ?";
        $param_array = array($organisation_id, $parent_id);
        $results = $db->GetAll($query, $param_array);
             
        while ($results) {
            $parent_set = array();
            foreach ($results as $obj) {
               $child_ids[] = $obj["objective_id"]; 
            }
            $query = "SELECT a.`objective_id` FROM `global_lu_objectives` AS a
             JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
             WHERE b.organisation_id=? AND objective_parent IN (".$query.")";
            array_unshift($param_array,  $organisation_id);
            $results = $db->GetAll($query, $param_array);
        }
        
        return $child_ids;
    }
    
    public static function fetchFilteredObjectives($organisation_id, $search_value = null, $objective_set_id = null, $parent_id = null, $active = 1, $status_filters=null, $attribute_filters = null, $rows_per_page = 25, $current_page = 1) {
        global $db;

        $objectives = array();
        $search_query = array();
        $table_columns = array();
        if (isset($_SESSION["curriculum-tags-view-type"]) && $_SESSION["curriculum-tags-view-type"] == "list-view") {
            $table_columns = array("objective_name" => true);
        } else {
            $table_columns = (isset($_SESSION["curriculum_tag_table_columns"]) ? $_SESSION["curriculum_tag_table_columns"] : array("objective_name" => true));
        }
        $show_attributes = false;
        foreach ($table_columns as $key=>$column) {
            if (strpos($key, "attribute") == 0)
                $show_attributes = true;
        }

        if (isset($search_value) && $tmp_input = clean_input(strtolower($search_value), array("trim", "striptags"))) {
            $PROCESSED["search_value"] = $tmp_input;
            $search_query[] = "a.`objective_id` = " . $db->qstr($PROCESSED["search_value"]) . "
                             OR a.objective_code LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%") . "
                             OR a.`objective_name` LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%") . "
                             OR a.`objective_description` LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%") . "
                                              ";
        } else {
            $PROCESSED["search_value"] = "";
        }
        
        $filter_query = "";
        if (!empty($status_filters) && is_array($status_filters)) {
            foreach($status_filters as $key => $values) {
                if (!empty($values) && is_array($values)) {
                    $filter_query = $filter_query . " AND a.".$key." in (".implode(",",$values).") ";
                }
            }
        }
        
        $attribute_filter_query = "";
        if (!empty($attribute_filters) && is_array($attribute_filters)) {
            foreach($attribute_filters as $key => $values) {
                if (!empty($values) && is_array($values)) {
                    $attribute_filter_query = $attribute_filter_query .
                                                "JOIN linked_objectives $key 
                                                    ON $key.objective_id=a.objective_id 
                                                    AND $key.active=1 
                                                    AND $key.target_objective_id in (".implode(",",$values).") ";
                }
            }
        }

        $language_joins = array();
        $language_columns = array("a.objective_name","a.objective_description");
        $objective_set_id = clean_input($objective_set_id, array("trim", "int"));

        $objective_set = Models_ObjectiveSet::fetchRowByID($objective_set_id);
        if ($objective_set) {
            if ($objective_set->getLanguages() != null) {
                $languages = json_decode($objective_set->getLanguages(), true);
                if ($languages && sizeof($languages) > 1) {
                    $language_columns = array();
                    foreach ($languages as $key => $language) {
                            if ($language == "en") {
                                $language_columns[] = "
                                IF(" . $language . ".objective_name IS NULL, a.objective_name, " . $language . ".objective_name) AS " . $language . "_name, 
                                IF(" . $language . ".objective_description IS NULL, a.objective_description, " . $language . ".objective_description) AS " . $language . "_description";
                            } else {
                                $language_columns[] = $language . ".objective_name AS " . $language . "_name," . $language . ".objective_description AS " . $language . "_description";
                            }
                            $language_joins[] = "LEFT JOIN objective_translation $language 
                                                    ON a.objective_id=$language.objective_id AND $language.language_id = 
                                                    (SELECT `language_id` FROM `language` WHERE `iso_6391_code` = '$language')";
                            if ($PROCESSED["search_value"] != "") {
                                $search_query[] = "$language.`objective_name` LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%") . "
                                              OR $language.`objective_description` LIKE " . $db->qstr("%". $PROCESSED["search_value"] ."%");
                            }
                    }
                }
            }
        }

        $query = "  SELECT a.objective_id, a.objective_code, a.objective_parent, " . implode(",", $language_columns) . ",
                        os.objective_status_description, ts.objective_translation_status_description
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
                    " . implode(" ", $language_joins) . "
                    LEFT JOIN objective_status os ON a.objective_status_id=os.objective_status_id 
                    LEFT JOIN objective_translation_status ts ON a.objective_translation_status_id=ts.objective_translation_status_id ".
                    $attribute_filter_query.
                    "WHERE b.`organisation_id` = ?
                    AND a.`objective_set_id` = ? " . $filter_query . (empty($search_query) ? " AND a.`objective_parent` = $parent_id " : " AND (" . implode(" OR ", $search_query) . ")") . "
                    AND a.`objective_active` = ?
                    ORDER BY a.`objective_order`
                    LIMIT " . ($current_page == 1 ? "0 ," . $rows_per_page : (($current_page - 1) * $rows_per_page) . ", " . $rows_per_page);
        $count_query = "  SELECT COUNT(a.objective_id) AS total_rows
                    FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b ON a.`objective_id` = b.`objective_id`
                    " . implode(" ", $language_joins) . "
                    LEFT JOIN objective_status os ON a.objective_status_id=os.objective_status_id 
                    LEFT JOIN objective_translation_status ts ON a.objective_translation_status_id=ts.objective_translation_status_id " .
                    $attribute_filter_query .
                    "WHERE b.`organisation_id` = ?
                            AND a.`objective_set_id` = ? " . $filter_query . (empty($search_query) ? " AND a.`objective_parent` = $parent_id " : " AND (" . implode(" OR ", $search_query) . ")") . "
                            AND a.`objective_active` = ?
                            ORDER BY a.`objective_order`";

        $results = $db->GetAll($query, array($organisation_id, $objective_set_id, $active));

        $row_count = $db->GetRow($count_query, array($organisation_id, $objective_set_id, $active));

        if (isset($results) && is_array($results)) {
            $total_rows = 0;
            if ($row_count) {
                $total_rows = $row_count["total_rows"];
            }
            foreach($results as $result) {
                $long_method = "";
                $curriculum_tag = Models_Objective::fetchRow($result["objective_id"]);
                $breadcrumbs = $curriculum_tag->getPath($result["objective_id"]);
                $objective = array(
                    "objective_id" => $result["objective_id"],
                    "objective_code" => $result["objective_code"],
                    "objective_parent" => $result["objective_parent"],
                    "objective_status_description" => $result["objective_status_description"],
                    "objective_translation_status_description" => $result["objective_translation_status_description"],
                    "breadcrumbs" => $breadcrumbs
                );

                if ($languages && sizeof($languages) > 1) {
                    foreach ($languages as $key => $language) {
                        $objective[$language . "_name"] = $result[$language . "_name"];
                        $objective[$language . "_description"] = $result[$language . "_description"];
                    }
                }else {
                    $objective["objective_name"] = $result["objective_name"];
                    $objective["objective_description"] = $result["objective_description"];
                }

                if (isset($_SESSION["curriculum-tags-view-type"]) && $_SESSION["curriculum-tags-view-type"] == "list-view") {
                    $long_method = $curriculum_tag->getLongMethod();
                    $objective["long_method"] = $long_method;
                } else {
                    if ($show_attributes) {
                        if ($attributes = Models_Objective_TagAttribute::fetchAllByObjectiveSetID($objective_set_id)) {
                            foreach ($attributes as $attribute) {
                                $objective_set = Models_ObjectiveSet::fetchRowByID($attribute->getTargetObjectiveSetId());
                                $set_id = $objective_set->getID();
                                if (isset($table_columns["attribute_" . $set_id]) && $table_columns["attribute_" . $set_id]) {
                                    $query = "SELECT b.objective_name
                                            FROM linked_objectives a
                                            LEFT JOIN global_lu_objectives b on b.objective_id=a.target_objective_id
                                            LEFT JOIN global_lu_objective_sets c on b.objective_set_id=c.objective_set_id
                                            WHERE a.active=1 AND a.objective_id = ? AND b.objective_set_id = ?
                                            ORDER BY a.objective_id, b.objective_set_id";
                                    $results = $db->GetAll($query, array($objective["objective_id"], $set_id));
                                    if ($results) {
                                        $linked_objectives = array();
                                        foreach ($results as $result) {
                                            $linked_objectives[] = $result["objective_name"];
                                        }
                                        $objective["attribute_" . $set_id] = $linked_objectives;
                                    }
                                }

                            }
                        }
                    }
                }

                $objectives[] = $objective;
            }
            return array("data" => $objectives, "total_rows" => $total_rows);
        } else {
            return false;
        }
    }

    public static function fetchByOrganisationSearchNameDescription($organisation_id, $search_value, $parent_id, $limit) {
        global $db;
        if ($search_value) {
            $search_sql = "
                AND (a.`objective_name` LIKE " . $db->qstr("%". $search_value ."%") . " OR
                a.`objective_description` LIKE " . $db->qstr("%". $search_value ."%") . ")";
        } else {
            $search_sql = "";
        }
        $query = "
            SELECT a.*, (a.`objective_name` = ".$db->qstr($search_value)." OR a.`objective_description` = ".$db->qstr($search_value).") AS `relevance`
            FROM `global_lu_objectives` AS a
            JOIN `objective_organisation` AS b
            ON a.`objective_id` = b.`objective_id`
            WHERE b.`organisation_id` = ?
            AND a.`objective_parent` = ?
            AND a.`objective_active` = 1
            {$search_sql}
            ORDER BY `relevance` DESC, a.`objective_order` ASC
            LIMIT {$limit}";
        $results = $db->GetAll($query, array($organisation_id, $parent_id));
        return array_map(function (array $result) {
            $objective = new self();
            $objective->fromArray($result);
            return $objective;
        }, $results);
    }

    public static function fetchAllByParentID($organisation_id = null, $parent_id = null, $active = 1) {
		global $db;

		$objectives = false;

        $parent_id = clean_input($parent_id, "int");

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


    public static function fetchAllByParentIDObjectiveAudienceNotCourse($organisation_id = null) {
		global $db;

		$objectives = false;

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                      ON a.`objective_id` = b.`objective_id`
                    LEFT JOIN `objective_audience` AS c 
                      ON c.`objective_id` = a.`objective_id`
                      AND c.`organisation_id` = ?
                    WHERE 1
                      AND b.`organisation_id` = ? 
                      AND (a.`objective_parent` = 0 OR a.`objective_parent` IS NULL) 
                      AND a.`objective_active` = 1 
                      AND ((c.`audience_type` = 'COURSE' AND c.`audience_value` != 'none') 
                        OR (c.`audience_type` IS NULL))
                    ORDER BY a.`objective_order` ASC";
		$results = $db->GetAll($query, array($organisation_id, $organisation_id));
		if ($results) {
			foreach ($results as $result) {
				$objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
			}
		}

        return $objectives;
    }


    public static function fetchAllByParentIDCBMECourseObjective($parent_id, $course_id, $organisation_id, $active = 1, $order_by = "") {
		global $db;

		$objectives = false;

        $parent_id = clean_input($parent_id, array("trim", "int"));
        $order_by = clean_input($order_by, array("trim", "striptags"));

		$query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `cbme_course_objectives` AS c 
                    ON a.`objective_id` = c.`objective_id` 
                    AND c.`course_id` = ?
                    WHERE b.`organisation_id` = ?
                    AND a.`objective_parent` = ?
                    AND a.`objective_active` = ?";

        if ($order_by) {
            $query .= " ORDER BY " . $order_by;
        } else {
            $query .= " ORDER BY a.`objective_order` ASC";
        }

		$results = $db->GetAll($query, array($course_id, $organisation_id, $parent_id, $active));
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

    /*public static function fetchAll($parent_id = NULL, $active = 1) {
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
    }*/

    public static function fetchAll($parent_id = NULL, $active = 1, $language_id = 1, $organisation_id, $orderByName = false, $status=null) {
		global $db;

		$objectives = false;
		
		$status_sql = "";
		if ($status != null && !empty($status)) {
		    $status_sql = " AND glo.objective_status_id = ? ";
        } 
        
        // Do natural sorting for alphanumerics.
        // This heuristic isn't perfect but the results are pretty good.
        // SOUNDEX grabs the characters before the numeric part.
        // LPAD left pads the numeric part with zeros.
        $orderBy = $orderByName ? 'SOUNDEX(objective_code), LPAD(objective_code, 10, 0), objective_name' : 'objective_id';
        $query = "SELECT
            glo.`objective_id` AS objective_id,
            glo.`objective_code` AS objective_code,
            glo.`objective_name` AS objective_name,
            glo.`objective_description` AS objective_description,
            glo.`objective_parent` AS objective_parent,
            glo.`objective_set_id`,
            glo.`objective_parent`,
            glo.`objective_active`,
            glo.`non_examinable`,
            glo.`objective_status_id`,
            glo.`objective_translation_status_id`
            FROM `global_lu_objectives` AS glo
            LEFT JOIN `objective_translation` AS ot
            ON glo.`objective_id` = ot.`objective_id`
            INNER JOIN `objective_organisation` AS org ON glo.`objective_id` = org.`objective_id`
            WHERE glo.`objective_active` = ?".(isset($parent_id) && ($parent_id || $parent_id === 0) ? " AND glo.`objective_parent` = ?" : "")
            . "AND org.`organisation_id` = ? ".$status_sql.
            "ORDER BY " . $orderBy;

        $params = array($active, $parent_id, $organisation_id);
        if (!empty($status_sql)) {
            $params[] = $status;
        }

        $results = $db->GetAll($query, $params);
		if ($results) {
			foreach ($results as $result) {
				$objective = new self();
				$objectives[$result["objective_id"]] = $objective->fromArray($result);
			}
		}

        return $objectives;
    }

    public static function fetchAllBySetID($obj_set, $active = 1) {
        global $db;

        $query = "SELECT *
            FROM `global_lu_objectives` AS glo
            WHERE glo.`objective_set_id` = ? 
            AND glo.`objective_active` = ?
            AND glo.`objective_parent` != 0";

        $results = $db->GetAll($query, [$obj_set, $active]);
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }

        return $objectives;
    }

    public static function fetchAllBySetIDParentID($obj_set, $parent_id, $active = 1) {
        global $db;

        $query = "SELECT *
            FROM `global_lu_objectives` AS glo
            WHERE glo.`objective_parent` = ? AND glo.`objective_set_id` = ? AND glo.`objective_active` = ?";

        $results = $db->GetAll($query, [$parent_id, $obj_set, $active]);
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }

        return $objectives;
    }

    public static function fetchRowBySetIDParentID($obj_set, $parent_id, $organisation_id = null, $active = 1) {
        global $db;

        $query = "SELECT *
            FROM `global_lu_objectives` AS glo
            INNER JOIN `objective_organisation` AS org 
            ON glo.`objective_id` = org.`objective_id`
            WHERE glo.`objective_parent` = ? 
            AND glo.`objective_set_id` = ? 
            AND glo.`objective_active` = ?
            ".(!is_null($organisation_id) ? " AND org.`organisation_id` = ".$db->qstr($organisation_id) : "");

        $result = $db->GetRow($query, [$parent_id, $obj_set, $active]);
        if ($result) {
            $self = new self();
            return $self->fromArray($result);
        } else {
            return false;
        }
    }

    public static function fetchRowBySetIDCodeName($obj_set, $code, $name, $organisation_id = null, $parent_id = null, $active = 1) {
        global $db;

        $query = "SELECT *
            FROM `global_lu_objectives` AS glo
            ".(!is_null($organisation_id) ? " INNER JOIN `objective_organisation` AS org ON glo.`objective_id` = org.`objective_id`" : "") ."
            WHERE glo.`objective_set_id` = ?  
            AND glo.`objective_code` = ?
            AND glo.`objective_name` = ?
            AND glo.`objective_active` = ?
            ".(!is_null($parent_id) ? " AND glo.`objective_parent` = ".$db->qstr($parent_id) : "") .
            (!is_null($organisation_id) ? " AND org.`organisation_id` = ".$db->qstr($organisation_id) : "");

        $result = $db->GetRow($query, [$obj_set, $code, $name, $active]);
        if ($result) {
            $self = new self();
            return $self->fromArray($result);
        } else {
            return false;
        }
    }

    public static function fetchObjectiveSet($objective_id, $organisation_id) {
        $parent_id = (int) $objective_id;

        if (!$parent_id) {
            return false;
        }

        $level = 0;

        do {
            $level++;
            $parent = self::fetchRow($parent_id, 1, $organisation_id);
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

    ////////
    /// @TODO This fetchObjectivesMappedTo method requires special investigation. There were changes made to it so all
    /// usages of it need to be investigated to make sure this is functional. I have left the HEAD version here and called
    /// it fetchObjectivesMappedToHEAD(). THIS SHOULD BE REMOVED once the methods are merged correctly.
    public static function fetchObjectivesMappedToHEAD($objective_id = 0, $cmapversion_id = 0, $active = 1) {
        global $db;

        $objective_id = (int) $objective_id;
        $cmapversion_id = (int) $cmapversion_id;
        $active = (int) $active;

        $output = [];

        if ($objective_id) {
            if ($cmapversion_id) {
                $where = "AND a.`version_id` = " . $db->qstr($cmapversion_id);
            } else {
                $where = "AND a.`version_id` IS NULL";
            }

            $query = "SELECT b.*
                        FROM `linked_objectives` AS a
                        JOIN `global_lu_objectives` AS b
                        ON b.`objective_id` = a.`target_objective_id`
                        WHERE a.`objective_id` = ?
                        AND b.`objective_active` = ?
                        AND a.`active` = ?
                        " . $where . "
                        ORDER BY b.`objective_order` ASC";
            $output = $db->GetAll($query, array($objective_id, $active, $active));
        }

        return $output;
    }

    ////////
    /// @TODO This fetchObjectivesMappedTo method requires special investigation. There were changes made to it so all
    /// usages of it need to be investigated to make sure this is functional. I have left the HEAD version here and called
    /// it fetchObjectivesMappedToHEAD(). THIS SHOULD BE REMOVED once the methods are merged correctly.
    public static function fetchObjectivesMappedTo($objective_id = 0, $active = 1, $organization_id=NULL, $parent_code=NULL) {
        global $db;

        $objective_id = (int) $objective_id;
        
        $param_arr = array();
        
        $organization_sql = "";
        if ($organization_id != NULL) {
            $organization_sql = " JOIN `objective_organisation` AS oo ON b.`objective_id` = oo.`objective_id` and oo.`organisation_id` = ? ";
            $param_arr[] = $organization_id;
        }
        
        $param_arr[] = $objective_id;
        $param_arr[] = $active;
        $param_arr[] = $active;
        
        $parent_sql = "";
        if ($parent_code != NULL) {
            $parent = Models_Objective::fetchRowByCode($parent_code, $active, $organization_id);
        }
        
        if ($parent) {
            $parent_sql = " AND b.objective_parent=? ";
            $param_arr[] = $parent->getID();
        }

        $output = [];

        if ($objective_id) {
            if ($cmapversion_id) {
                $where = "AND a.`version_id` = " . $db->qstr($cmapversion_id);
            } else {
                $where = "AND a.`version_id` IS NULL";
            }

            $query = "SELECT b.*
                        FROM linked_objectives AS a
                        JOIN global_lu_objectives AS b ON b.objective_id = a.target_objective_id ".
                        $organization_sql .
                        "WHERE a.objective_id = ?
                        AND b.objective_active = ?
                        AND a.active = ? ".
                        $parent_sql .
                        "ORDER BY a.target_objective_id ASC";
            $output = $db->GetAll($query, $param_arr);
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
    public static function fetchRow($objective_id = 0, $active = 1, $organisation_id = null) {
        global $db;

		$return = false;

		if ($objective_id != 0) {
            $query = "SELECT * FROM `global_lu_objectives` AS glo
            	INNER JOIN `objective_organisation` AS org ON glo.`objective_id` = org.`objective_id`
            	WHERE glo.`objective_id` = ? AND glo.`objective_active` = ? 
            	".(!is_null($organisation_id) ? " AND org.`organisation_id` = ".$db->qstr($organisation_id) : "");

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
    public static function fetchRowByName($organisation_id, $objective_name, $active = 1) {
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
    
    public static function fetchRowByCode($objective_code, $active = 1, $organisation_id) {
        global $db;

        $query = "SELECT * FROM `global_lu_objectives` AS glo
        	INNER JOIN `objective_organisation` AS org ON glo.`objective_id` = org.`objective_id`
        	WHERE glo.`objective_code` = ? AND glo.`objective_active` = ? AND org.`organisation_id` = ?";

        $result = $db->GetRow($query, array($objective_code, $active, $organisation_id));
        if ($result) {
            $objective = new self();
            return $objective->fromArray($result);
        } else {
            return false;
        }
    }

    public static function fetchRowBySetID($objective_set, $organisation_id, $active = 1) {
        global $db;

        $query = "SELECT * FROM `global_lu_objectives` AS glo
        	INNER JOIN `objective_organisation` AS org ON glo.`objective_id` = org.`objective_id`
        	WHERE glo.`objective_set_id` = ? AND glo.`objective_active` = ? AND org.`organisation_id` = ?";

        $result = $db->GetRow($query, [$objective_set, $organisation_id, $active]);
        if ($result) {
            $objective = new self();
            $return = $objective->fromArray($result);
        }

        return $return;
    }

    public static function fetchAllOrganisationBySetID($objective_set, $active = 1) {
        global $db;
        $organisation_ids = [];

        $query = "SELECT DISTINCT org.`organisation_id` FROM `global_lu_objectives` AS glo
            INNER JOIN `objective_organisation` AS org 
            ON glo.`objective_id` = org.`objective_id`
            WHERE glo.`objective_set_id` = ? 
            AND glo.`objective_active` = ?
            ORDER BY org.`organisation_id` ASC";

        $results = $db->GetAll($query, [$objective_set, $active]);
        if ($results) {
            foreach ($results as $obj) {
                $organisation_ids[] = $obj["organisation_id"];
            }
        }
        return $organisation_ids;
    }

    public function fetchAllChildrenByObjectiveSetID ($objective_set_id = null, $organisation_id, $active = 1) {
        global $db;
        $objectives = [];

        $query = "SELECT glo.* FROM `global_lu_objectives` AS glo
                    INNER JOIN `objective_organisation` AS org 
                    ON glo.`objective_id` = org.`objective_id`
                    WHERE glo.`objective_set_id` = ?
                    AND org.`organisation_id` = ?
                    AND glo.`objective_parent` != 0
                    AND glo.`objective_active` = ?";
        $results = $db->GetAll($query, array($objective_set_id, $organisation_id, $active));

        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }
        return $objectives;
    }

    public static function fetchRowsByCode($objective_code, $active = 1) {
        global $db;

        $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_code` = ? AND `objective_active` = ?";
        $result = $db->GetAll($query, array($objective_code, $active));
        return $result;
    }

    public static function fetchRowByNameParentID($organisation_id, $objective_name, $parent_id) {
        global $db;
        $query = "SELECT a.*
                  FROM `global_lu_objectives` AS a
                  JOIN `objective_organisation` AS b
                  ON b.`objective_id` = a.`objective_id`
                  WHERE b.`organisation_id` = ?
                  AND a.`objective_name` = ?
                  AND a.`objective_parent` = ?
                  AND a.`objective_active` = 1";
        $result = $db->GetRow($query, array($organisation_id, $objective_name, $parent_id));
        if ($result === false) {
            application_log("error", "Couldn't get objective ".$objective_name." with parent ".$parent_id.", DB said: ".$db->ErrorMsg());
            throw new Exception("Couldn't get objective ".$objective_name." with parent ".$parent_id);
        } else if ($result) {
            $objective = new self();
            $objective->fromArray($result);
            return $objective;
        } else {
            return false;
        }
    }

    public function insert() {
		global $db;

		// if ($db->AutoExecute("`global_lu_objectives`", $tempArray, "INSERT")) {
        if ($db->AutoExecute("`global_lu_objectives`", $this->toArray(), "INSERT")) {
			$this->objective_id = $db->Insert_ID();
			return true;
		}
		return false;
    }

	public function update() {
		global $db;

		if ($db->AutoExecute("`global_lu_objectives`", $this->toArray(), "UPDATE", "`objective_id` = ".$db->qstr($this->getID()))) {
			return true;
		}
		return false;
    }

    public function delete($perform_ordering = true) {
        $this->objective_active = false;
        return $this->update($perform_ordering);
    }

    public function deleteChildren($parent_id) {
        global $db;
        $objectives = $db->GetAll(" SELECT a.* 
                                FROM `" . DATABASE_NAME . "`.`global_lu_objectives` AS a 
                                JOIN `" . DATABASE_NAME . "`.`objective_organisation` AS b ON a.`objective_id` = b.`objective_id` 
                                WHERE a.`objective_parent` = $parent_id;");
        if ($objectives) {
            foreach ($objectives as $objective) {
                $result = $db->Execute("UPDATE `" . DATABASE_NAME . "`.`global_lu_objectives` SET `objective_active` = 0 WHERE `objective_id` = " . $objective["objective_id"] . ";");
                if ($result) {
                    if ($linked_objectives = Models_Objective_LinkedObjective::fetchAllByObjectiveID($objective["objective_id"])) {
                        foreach ($linked_objectives as $linked_objective) {
                            $linked_objective->setActive(0);
                            $linked_objective->update();
                        }
                    }
                    if ($linked_objectives = Models_Objective_LinkedObjective::fetchAllByTargetObjectiveID($objective["objective_id"])) {
                        foreach ($linked_objectives as $linked_objective) {
                            $linked_objective->setActive(0);
                            $linked_objective->update();
                        }
                    }
                    $objective_org = new Models_Objective_Organisation();
                    $objective_org->fromArray(array("objective_id" => $objective["objective_id"], "organisation_id" => $objective["organisation_id"]))->delete();
                }

                if (Models_Objective::countObjectiveChildren($objective["objective_id"]) > 0)
                    $this->deleteChildren($objective["objective_id"]);
            }
        } else {
            return false;
        }
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

    public static function getFirstLevelChildIDs($objective_id, $active = 1) {
        global $db;

        $objective_ids = array();
        $query = "SELECT `objective_id` 
                    FROM `global_lu_objectives` 
                    WHERE `objective_parent` = ? 
                    AND `objective_active` = ?";
        $child_ids = $db->GetAll($query, [$objective_id, $active]);
        if ($child_ids) {
            foreach ($child_ids as $child_id) {
                $objective_ids[] = $child_id["objective_id"];
            }
        }
        return $objective_ids;
    }

    public static function getObjectiveChildren($objective_id = 0) {
        global $db;
        $children = array();

        $results = $db->Execute("SELECT * 
                                FROM " . DATABASE_NAME . ".`global_lu_objectives` 
                                WHERE `objective_parent` = ? 
                                AND `objective_active` = 1", $objective_id);
        if ($results) {
            foreach ($results as $result) {
                $children[] = $result;
                if (static::countObjectiveChildren($result["objective_id"]) > 0) {
                    static::getObjectiveChildren($result["objective_id"]);
                }
            }
        }

        if (!empty($children)) {
            return $children;
        } else {
            return false;
        }
    }

    public static function getObjectiveSetDepth($objective_id, $max_depth = 0) {
        $child_ids = self::getFirstLevelChildIDs($objective_id);

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

    public function fetchAllChildrenByObjectiveSetIDCourseID ($objective_set_id = null, $course_id = null, $active = 1) {
        global $db;
        $objectives = array();

        $query = "  SELECT a.* FROM `global_lu_objectives` AS a
                    JOIN `cbme_course_objectives` AS b
                    ON a.`objective_id` = b.`objective_id`
                    WHERE a.`objective_set_id` = ?
                    AND b.`course_id` = ?
                    AND a.`objective_parent` != 0
                    AND a.`objective_active` = ?
                    ORDER BY a.`objective_order` ASC";
        $results = $db->GetAll($query, array($objective_set_id, $course_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = new self($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_description"], $result["objective_secondary_description"], $result["objective_parent"], $result["objective_set_id"], $result["associated_objective"], $result["objective_order"], $result["objective_loggable"], $result["objective_active"], $result["updated_date"], $result["updated_by"]);
            }
        }

        return $objectives;
    }

    public function fetchParentForSetBySetShortnameObjectiveCode($shortname, $objective_code) {
        global $db;
        $query = "SELECT a.* 
                  FROM `global_lu_objectives` AS a 
                  JOIN `global_lu_objective_sets` AS b 
                  ON  a.`objective_set_id` = b.`objective_set_id` AND b.`shortname` = ? 
                  WHERE a.`objective_code` = ? 
                  LIMIT 1";
        $parent_fetch_result = $db->GetOne($query, array($shortname, $objective_code));
        if (!$parent_fetch_result) {
            return false;
        }
        return new self($parent_fetch_result);
    }

    public function fetchChildrenByObjectiveSetShortname ($shortname = null, $organisation_id = null, $search_term = "", $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND (b.`objective_code` LIKE CONCAT(?, '%') OR b.`objective_name` LIKE CONCAT(?, '%') OR b.`objective_description` LIKE CONCAT(?, '%') OR b.`objective_secondary_description` LIKE CONCAT(?, '%'))
                    AND a.`deleted_date` IS NULL
                    AND c.`organisation_id` = ?
                    AND b.`objective_active` = ?
                    ORDER BY b.`objective_order`";

        $results = $db->GetAll($query, array($shortname, $search_term, $search_term, $search_term, $search_term, $organisation_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }
        return $objectives;
    }

    public function fetchTemplateObjectivesByObjectiveParent ($objective_id = null, $organisation_id = null, $search_term = "", $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT c.* FROM `cbme_objective_templates` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `global_lu_objectives` AS c
                    ON a.`objective_id` = c.`objective_id`
                    WHERE a.`objective_parent` = ?
                    AND b.`organisation_id` = ?
                    AND c.`objective_active` = ?
                    AND a.`deleted_date` IS NULL";

        $results = $db->GetAll($query, array($objective_id, $organisation_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }
        return $objectives;
    }

    /**
     * Fetch course specific objectives by objective_set_shortname
     * @param string $shortname
     * @param int $course_id
     * @param string $search_term
     * @param int $active
     * @return array
     */
    public function fetchChildrenByObjectiveSetShortnameCourseID ($shortname = null, $course_id = null, $search_term = "", $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND c.`course_id` = ?
                    AND (b.`objective_code` LIKE CONCAT('%', ?, '%') OR b.`objective_name` LIKE CONCAT('%', ?, '%') OR b.`objective_description` LIKE CONCAT('%', ?, '%') OR b.`objective_secondary_description` LIKE CONCAT('%', ?, '%'))
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_order`";

        $results = $db->GetAll($query, array($shortname, $course_id, $search_term, $search_term, $search_term, $search_term, $active));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }
        return $objectives;
    }

    public function fetchChildrenByObjectiveSetShortnameObjectiveCodeCourseIDIgnoreActive ($shortname = null, $objective_code = null, $course_id = null) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` = ?
                    AND c.`course_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_code`";

        $results = $db->GetAll($query, array($shortname, $objective_code, $course_id));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }

        return $objectives;
    }

    /**
     * Fetch all course specific objectives by their objective_set_shortname and objective_code
     * @param string $shortname
     * @param int $course_id
     * @param string $stage_code
     * @param string $search_term
     * @param int $active
     * @return array
     */
    public function fetchObjectivesByShortnameCourseIDStage ($shortname = "", $course_id = null, $stage_code = null, $search_term = "", $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` LIKE CONCAT(?, '%')
                    AND c.`course_id` = ?
                    AND (b.`objective_code` LIKE CONCAT('%', ?, '%') OR b.`objective_name` LIKE CONCAT('%', ?, '%') OR b.`objective_description` LIKE CONCAT('%', ?, '%') OR b.`objective_secondary_description` LIKE CONCAT('%', ?, '%'))
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_order`";

        $results = $db->GetAll($query, array($shortname, $stage_code, $course_id, $search_term, $search_term, $search_term, $search_term, $active));
        if ($results) {
            foreach ($results as $result) {
                $self = new self($result);
                $objectives[] = $self->fromArray($result);
            }
        }
        return $objectives;
    }

    /**
     * Get course specific CBME objectives by shortname and optionally a search term
     * @param string $shortname
     * @param null $course_id
     * @param null $objective_code
     * @param int $active
     * @return array
     */
    public function fetchCbmeCourseObjectivesByCode($shortname = "", $course_id = null, $objective_code = null, $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` LIKE CONCAT(?, '%')
                    AND c.`course_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_order`";
        $results = $db->GetAll($query, array($shortname, $objective_code, $course_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $result["searched_code"] = $objective_code;
                $objectives[] = $result;
            }
        }
        return $objectives;
    }

    public function fetchRowByShortnameCode ($shortname = null, $objective_code = null, $active = 1) {
        global $db;
        $objective = false;
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    ORDER BY b.`objective_order`";

        $result = $db->GetRow($query, array($shortname, $objective_code, $active));
        if ($result) {
            $objective = $result;
        }
        return $objective;
    }

    public function fetchRowByObjectiveCodeObjectiveSetID ($objective_code = null, $objective_set_id = null, $active = 1) {
        global $db;
        $objective = false;

        $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_code` = ? AND `objective_set_id` = ? AND `objective_active` = ?";
        $result = $db->GetRow($query, array($objective_code, $objective_set_id, $active));
        if ($result) {
            $self = new self();
            $objective = $self->fromArray($result);
        }

        return $objective;
    }

    public function fetchRowByObjectiveCode ($objective_code = null, $active = 1) {
        global $db;
        $objective = false;

        $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_code` = ? AND `objective_active` = ?";
        $result = $db->GetRow($query, array($objective_code, $active));
        if ($result) {
            $self = new self();
            $objective = $self->fromArray($result);
        }

        return $objective;
    }

    public function fetchRowByObjectiveCodeObjectiveSetShortname($objective_code, $shortname, $active = 1) {
        global $db;
        $objective = false;

        $query = "
           SELECT * 
           FROM `global_lu_objectives` AS o
           LEFT JOIN `global_lu_objective_sets` AS os
             ON os.`objective_set_id` = o.`objective_set_id`
           WHERE o.`objective_code` = ? 
             AND o.`objective_active` = ?
             AND os.`shortname` = ?
        ";
        $result = $db->GetRow($query, array($objective_code, $active, $shortname));
        if ($result) {
            $self = new self();
            $objective = $self->fromArray($result);
        }
        return $objective;
    }

    /**
     * This function fetches a course specific CBME objective by shortname and the provided objective code(s).
     *
     * @param string $shortname
     * @param string $objective_code
     * @param int $course_id
     * @param int $active
     * @return array
     */

    public function fetchRowByObjectiveCodeCourseID ($shortname = "", $objective_code = "", $course_id = 0, $active = 1) {
        global $db;
        $objective = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code`  = ?
                    AND c.`course_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_order`";
        $results = $db->GetRow($query, array($shortname, $objective_code, $course_id, $active));
        if ($results) {
            $objective = $results;
        }
        return $objective;
    }

    /**
     * This function fetches all course specific CBME objectives by shortname and the provided objective code(s).
     *
     * @param string $shortname
     * @param string $objective_code
     * @param int $course_id
     * @param int $active
     * @return array
     */

    public function fetchAllByObjectiveCodeCourseID ($shortname = null, $objective_codes = null, $course_id = null, $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` IN ('".implode("','", $objective_codes)."')
                    AND c.`course_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    AND c.`deleted_date` IS NULL
                    ORDER BY b.`objective_order`";
        $results = $db->GetAll($query, array($shortname, $course_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = $result;
            }
        }
        return $objectives;
    }

    public function fetchRowByNameAndObjectiveSetID($objective_name, $objective_set_id) {
        global $db;
        $objective = false;
        $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_name` = ? AND `objective_set_id` = ? AND `objective_active` = 1";
        $result = $db->GetRow($query, array($objective_name, $objective_set_id));
        if ($result) {
            $self = new self();
            $objective = $self->fromArray($result);
        }
        return $objective;
    }

    public function fetchAllByShortnameCode ($shortname = null, $objective_code = null, $search_term = "", $active = 1) {
        global $db;
        $objectives = array();
        $query = "  SELECT b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_parent` != 0
                    AND b.`objective_code` LIKE CONCAT(?, '%')
                    AND (b.`objective_code` LIKE CONCAT('%', ?, '%') OR b.`objective_name` LIKE CONCAT('%', ?, '%') OR b.`objective_description` LIKE CONCAT('%', ?, '%') OR b.`objective_secondary_description` LIKE CONCAT('%', ?, '%'))
                    AND a.`deleted_date` IS NULL
                    AND b.`objective_active` = ?
                    ORDER BY b.`objective_order`";

        $results = $db->GetAll($query, array($shortname, $objective_code, $search_term, $search_term, $search_term, $search_term, $active));
        if ($results) {
            foreach ($results as $result) {
                $objectives[] = $result;
            }
        }
        return $objectives;
    }

    public function fetchAllByObjectiveSetID ($objective_set_id, $organisation_id = null, $active = 1) {
        global $db;
        $objectives = false;

        $JOIN_organisation = "";
        $AND_organisation = "";

        if ($organisation_id) {
            $JOIN_organisation = "  JOIN `objective_organisation` AS oo 
                                    ON glo.`objective_id` = oo.`objective_id`";
            $AND_organisation = "   AND oo.`organisation_id` = " . $db->qstr($organisation_id);
        }

        $query = "SELECT * FROM `global_lu_objectives` as glo
                  {$JOIN_organisation}
                  WHERE glo.`objective_set_id` = ? 
                  AND glo.`objective_active` = ? 
                  AND glo.`objective_code` IS NOT NULL
                  {$AND_organisation}
                  ORDER BY glo.`objective_code`";
        $results = $db->GetAll($query, array($objective_set_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }

        return $objectives;
    }

    /**
     * Get all of the objectives with a given array of objective sets
     * @param $objective_set_ids
     * @param int $active
     * @return array|bool
     */
    public function fetchAllByObjectiveSetIDs ($objective_set_ids, $active = 1) {
        global $db;
        $objectives = false;
        $sanitized_objective_ids = array();

        foreach ($objective_set_ids as $objective_id) {
            if ($tmp_input = clean_input($objective_id, array("int"))) {
                $sanitized_objective_ids[] = $tmp_input;
            }
        }

        $objective_set_ids = implode(", ", $sanitized_objective_ids);

        $query = "SELECT * FROM `global_lu_objectives` WHERE `objective_set_id` IN (".$objective_set_ids.") AND `objective_active` = ? AND `objective_code` IS NOT NULL ORDER BY `objective_code`";
        $results = $db->GetAll($query, array($active));
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }
        return $objectives;
    }


    public static function deleteAllWithAllChildren($objectives = array(), $organisation_id = 0) {
        foreach ($objectives as $objective) {
            if ($children = self::fetchAllByParentID($organisation_id ,$objective->getID()) ) {
                self::deleteAllWithAllChildren($children, $organisation_id);
            }
            $objective->delete(false);
        }
    }

    /**
     * Fetch the count of direct descendants (but NOT their descendants, only the immediate children) of an objective that are of a particular objective set.
     *
     * @param $objective_parent
     * @param $objective_set_id
     * @return bool
     */
    public function fetchChildrenCountByObjectiveSetID($objective_parent, $objective_set_id) {
        global $db;
        $sql = "SELECT COUNT(objective_id) AS children_count FROM `global_lu_objectives` WHERE objective_set_id = ? AND objective_parent = ?";
        return $db->GetOne($sql, array($objective_set_id, $objective_parent));
    }

    public function fetchObjectiveSetsByCourse($organisation_id, $course_id = null, $proxy_id = null) {
        global $translate;
        $data = array();

        // EPAs.
        if ($course_id && $proxy_id) {
            $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $proxy_id, "actor_organisation_id" => $organisation_id, "course_id" => $course_id));
            $epas = $tree_object->fetchTreeNodesAtDepth(0, "o.objective_code", false, true);
            if ($epas) {
                foreach ($epas as $objective) {
                    $objective["objective_name"] = $translate->_("EPAs");
                    $objective["node_id"] = $objective["cbme_objective_tree_id"];
                    $children = $tree_object->fetchBranch($objective["node_id"], ($objective["depth"] + 1), "o.objective_code");
                    $objective["has_children"] = $children && !empty($children) ? true : false;
                    $data[] = $objective;
                }
            }
        }

        // Organisation curriculum tag sets.
        $child_objectives = $objectives = Models_Objective::fetchAllByParentID($organisation_id, 0);
        if ($child_objectives) {
            foreach ($child_objectives as $objective) {
                if (!$objective->getObjectiveSetID()) {
                    $objective = $objective->toArray();
                    $children = Models_Objective::fetchAllByParentID($organisation_id, $objective["objective_id"]);
                    $objective["has_children"] = $children && !empty($children) ? true : false;
                    $objective["node_id"] = null;
                    $objective["depth"] = null;
                    $objective["course_id"] = null;
                    $data[] = $objective;
                }
            }
        }

        return $data;
    }

    public function fetchAllByObjectiveSetShortnameOrganisationID($objective_set_shortname, $organisation_id = null, $active = 1) {
        global $db;
        $objectives = false;

        $JOIN_organisation = "";
        $AND_organisation = "";

        if ($organisation_id) {
            $JOIN_organisation = "  JOIN `objective_organisation` AS oo 
                                ON glo.`objective_id` = oo.`objective_id`";
            $AND_organisation = "   AND oo.`organisation_id` = " . $db->qstr($organisation_id);
        }

        $query = "SELECT * FROM `global_lu_objectives` AS glo
                  JOIN `global_lu_objective_sets` glos
                  ON glo.`objective_set_id` = glos.`objective_set_id`
                  {$JOIN_organisation}
                  WHERE glos.`shortname` = ?
                  {$AND_organisation}
                  AND glo.`objective_active` = ? 
                  AND glo.`objective_code` IS NOT NULL 
                  ORDER BY glo.`objective_code`";
        $results = $db->GetAll($query, array($objective_set_shortname, $active));
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }

        return $objectives;
    }

    public function insertByTableName($table, $data) {
        global $db;

        if ($db->AutoExecute($table, $data, "INSERT")) {
            return true;
        }
        return false;
    }

    public function fetchLinkedObjectives($objective_id, $active = 1, $organisation_id) {
        global $db;
        $result = false;

        if ($objective_id != 0) {
            $query = "SELECT * FROM `linked_objectives` AS lo
            	INNER JOIN `objective_organisation` AS org ON lo.`objective_id` = org.`objective_id`
            	WHERE lo.`objective_id` = ? AND lo.`active` = ? AND org.`organisation_id` = ?";
            $result = $db->GetAll($query, array($objective_id, $active, $organisation_id));
        }
        return $result;
    }

    public static function fetchStatus() {
        global $db;

        $query = "SELECT * FROM `objective_status`";
        $result = $db->GetAll($query);
        if ($result) {
            return $result;
        }

        return false;
    }

    public static function enableLinkedObjective($objective_id, $target_objective_id, $active) {
        global $db;

        $query = "UPDATE `linked_objectives` SET `active` = " . $active . " WHERE `objective_id` = ";
        $query .= $objective_id . " AND `target_objective_id` = " . $target_objective_id;

        if ($db->Execute($query)) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchStatusById($objective_status_id) {
        global $db;

        $query = "SELECT * FROM `objective_status` WHERE `objective_status_id` = ?";
        $result = $db->GetRow($query, array($objective_status_id));
        if ($result) {
            return $result;
        }

        return false;
    }

    public function fetchTranslationStatus() {
        global $db;

        $query = "SELECT * FROM `objective_translation_status`";
        $result = $db->GetAll($query);
        if ($result) {
            return $result;
        }

        return false;
    }

    public static function fetchTranslationStatusById($id) {
        global $db;

        $query = "SELECT * FROM `objective_translation_status` WHERE `objective_translation_status_id` = ?";
        $result = $db->GetRow($query, array($id));
        if ($result) {
            return $result;
        }

        return false;
    }

    // $lang_id corresponds to the language_table
    public  function getTranslation($lang_id) {
        global $db;

        $query = "SELECT * FROM `objective_translation`
                  WHERE `objective_id` = ?
                  AND `language_id` = ?";
        
        $results = $db->GetAll($query, array($this->objective_id, $lang_id));

        // Assume one entry to return
        if (!empty($results) && !empty($results[0])) {
            return $results[0];
        }
        
        return NULL;
    }

    // Does insert or update
    // $lang_id corresponds to the language_table
    public function setTranslation($lang_id, $description, $name = '') {
        global $db;

        $translation = $this->getTranslation($lang_id);
        $translation_id = NULL;
        if (!empty($translation)) {
            $translation_id = $translation['objective_translation_id'];
        }

        $fieldValues = array(
            'objective_id' => $this->objective_id,
            'language_id' => $lang_id,
            'objective_description' => $description,
            'objective_name' => $name,
        );

        // Insert or update
        if (empty($translation_id)) {
            if ($db->AutoExecute("`objective_translation`", $fieldValues, "INSERT")) {
                return true;
            }
            return false;

        } else {
            if ($db->AutoExecute("`objective_translation`", $fieldValues, "UPDATE", "`objective_translation_id` = " . $translation_id)) {
                return true;
            }
        }
        return false;
    }

    // Get events that are mapped to this objective.
    public static function fetchEventObjectives($objective_id, $organisation_id) {
        global $db;
        $query = "SELECT * 
                    FROM `event_objectives` AS eo
                    JOIN `events` AS e ON eo.`event_id` = e.`event_id`
                    JOIN `objective_organisation` AS org ON eo.`objective_id` = org.`objective_id`
                    LEFT JOIN `courses` AS c ON e.`course_id` = c.`course_id`
                    WHERE eo.`objective_id` = ? AND org.`organisation_id` = ?
                    ORDER BY e.`event_title`";
        return $db->GetAll($query, array($objective_id, $organisation_id));
    }

    // Add a mapping between an event and an objective.
    public static function addMapEventObjective($event_id, $objective_id) {
        global $db;

        $fieldValues = array(
            'objective_id' => $objective_id,
            'event_id' => $event_id,
        );

        // Insert the mapping.
        if ($db->AutoExecute("`event_objectives`", $fieldValues, "INSERT")) {
            return true;
        }
        return false;
    }

    // Remove the mapping between an event and an objective.
    public static function deleteMapEventObjective($event_id, $objective_id) {
        global $db;

        $query = "DELETE FROM `event_objectives` WHERE `objective_id` = $objective_id AND `event_id` = $event_id";

        if ($db->Execute($query)) {
            return true;
        }
        return false;
    }

    /**
     * Traverses up the objective tree to find the root objective level
     */
    public function getLevel($objective_id, $level = 1) {
        $objective = self::fetchRow($objective_id);
        if ((int) $objective->getParent() !== 0) {
            $parent = self::fetchRow((int) $objective->getParent());
            if ($parent) {
                $level += 1;
                return self::getLevel($parent->getID(), $level);
            }
        }
        return $level;
    }

    public function getPath($objective_id, $objective_array = []) {
        $objective = self::fetchRow($objective_id);
        $objective_array[] = $objective->getShortMethod();
        if ($objective->getParent() !== 0) {
            $parent = Models_Objective::fetchRow($objective->getParent());
            if ($parent) {
                return $parent->getPath($parent->getID(), $objective_array);
            }
        }
        return array_reverse($objective_array);
    }

    public function getShortMethod() {
        $objective_set = Models_ObjectiveSet::fetchRowByID($this->getObjectiveSetID());
        $final_message = $this->getName();
        if ($objective_set) {
            $short_method = $objective_set->getShortMethod();
            if (!is_null($short_method) && !empty($short_method)) {
                $message_search = array(
                    "%c",
                    "%t",
                    "%d"
                );

                $message_replace = array(
                    $this->getCode(),
                    $this->getName(),
                    $this->getDescription()
                );

                $final_message = str_ireplace($message_search, $message_replace, $short_method);
            }
        }
        return $final_message;
    }

    public function getLongMethod() {
        $objective_set = Models_ObjectiveSet::fetchRowByID($this->getObjectiveSetID());
        $final_message = $this->getName() . " <br> " . $this->getDescription();
        if ($objective_set) {
            $long_method = $objective_set->getLongMethod();
            if (!is_null($long_method) && !empty($long_method)) {
                $message_search = array(
                    "%c",
                    "%t",
                    "%d"
                );

                $message_replace = array(
                    $this->getCode(),
                    $this->getName(),
                    $this->getDescription()
                );

                $final_message = str_ireplace($message_search, $message_replace, $long_method);
            }
        }
        return $final_message;
    }

    public function fetchAllAfterOrderByParentIdOrganisation ($order = 0, $parent_id = 0, $organisation_id = null) {
        global $db;

        $objectives = [];
        $query = "SELECT a.* FROM `global_lu_objectives` AS a
                        LEFT JOIN `objective_organisation` AS b
                        ON a.`objective_id` = b.`objective_id`
                        WHERE a.`objective_parent` = ?
                        AND a.`objective_order` >= ?
                        AND a.`objective_active` = '1'
                        AND (b.`organisation_id` = ? OR b.`organisation_id` IS NULL)
                        ORDER BY a.`objective_order` ASC";

        $results = $db->GetAll($query, [$parent_id, $order, $organisation_id]);
        if ($results) {
            foreach ($results as $result) {
                $objective = new self();
                $objectives[] = $objective->fromArray($result);
            }
        }
    return $objectives;
    }

    public static function UpdateObjectiveSetIDs($objective_set_id, $parent_id) {
        global $db;
        $query = "SELECT a.* 
                  FROM `" . DATABASE_NAME . "`.`global_lu_objectives` AS a 
                  JOIN `" . DATABASE_NAME . "`.`objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id` 
                  WHERE a.`objective_parent` = ?;";
        $objectives = $db->GetAll($query, $parent_id);
        if ($objectives) {
            foreach ($objectives as $objective) {
                $query = "  UPDATE `" . DATABASE_NAME . "`.`global_lu_objectives`
                            SET `objective_set_id` = ?
                            WHERE `objective_id` = ?
                            AND `objective_set_id` = 0;";
                $result = $db->Execute($query, [$objective_set_id, $objective["objective_id"]]);
                if ($result) {
                    if (static::countObjectiveChildren($objective["objective_id"]) > 0)
                        static::UpdateObjectiveSetIDs($objective_set_id, $objective["objective_id"]);
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }
}
