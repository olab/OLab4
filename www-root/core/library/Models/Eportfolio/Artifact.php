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
 * Models_Eportfolio_Artifact
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */
class Models_Eportfolio_Artifact {

	private $artifact_id,
			$title,
			$description,
			$allow_learner_addable,
			$order,
			$active,
			$updated_date,
			$updated_by;
	
	public function __construct($arr = NULL) {
		if (is_array($arr)) {
			$this->fromArray($arr);
		}
	}
	
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				$arr[$class_var] = $this->$class_var;
			}
		}
		return $arr;
	}
	
	public function fromArray($arr) {
		foreach ($arr as $class_var_name => $value) {
			$this->$class_var_name = $value;
		}
		return $this;
	}
	
	
	public static function fetchRow($artifact_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolios_lu_artifacts` WHERE `artifact_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($artifact_id, $active));
		if ($result) {
			$folder = new self($result);
			return $folder;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolios_lu_artifacts` WHERE `active` = ?";
		$results = $db->GetAll($query, array($active));
		if ($results) {
			$portfolios = array();
			foreach ($results as $result) {
				$portfolios[] = new self($result);
			}
			return $portfolios;
		} else {
			return false;
		}
	}
	
	public function insert() {
		global $db;
		if ($db->AutoExecute("`portfolios_lu_artifacts`", $this->toArray(), "INSERT")) {
			$this->artifact_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolios_lu_artifacts`", $this->toArray(), "UPDATE", "`artifact_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolios_lu_artifacts` WHERE `artifact_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}
	
	public function getID() {
		return $this->artifact_id;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function getAllowLearnerAddable() {
		return $this->allow_learner_addable;
	}
	
	public function getOrder() {
		return $this->order;
	}
	
	public function getActive() {
		return $this->active;
	}
	
	public function getUpdateDate() {
		return $this->updated_date;
	}
	
	public function getUpdatedBy() {
		$user = User::fetchRowByID($this->updated_by);
		return $user;
	}
	
}

?>
