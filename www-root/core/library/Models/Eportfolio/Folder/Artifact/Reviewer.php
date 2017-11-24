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
 * Models_Eportfolio_Folder_Artifact_Reviewer
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */
class Models_Eportfolio_Folder_Artifact_Reviewer {
	
	private $pfareviewer_id,
			$pfartifact_id,
			$proxy_id,
			$active = 1,
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
	
	public static function fetchRow($pfareviewer_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_folder_artifact_reviewers` WHERE `pfareviewer_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($pfareviewer_id, $active));
		if ($result) {
			$folder = new self($result);
			return $folder;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($pfartifact_id = NULL, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_folder_artifact_reviewers` WHERE ".(!is_null($pfartifact_id) ? " `pfartifact_id` = " . $db->qstr($pfartifact_id) . " AND " : "")." `active` = ?";
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
		if ($db->AutoExecute("`portfolio_folder_artifact_reviewers`", $this->toArray(), "INSERT")) {
			$this->pfareviewer_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolio_folder_artifact_reviewers`", $this->toArray(), "UPDATE", "`pfareviewer_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolio_folder_artifact_reviewers` WHERE `pfareviewer_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}
	
	public function getID() {
		return $this->pfareviewer_id;
	}
	
	public function getPfartifactID() {
		return $this->pfartifact_id;
	}
	
	public function getPfartifact() {
		$pfartifact = Models_Eportfolio_Folder_Artifact::fetchRow($this->pfartifact_id);
		return $pfartifact;
	}
	
	public function getProxyID() {
		return $this->proxy_id;
	}
	
	public function getReviewer() {
		$reviewer = User::fetchRowByID($this->proxy_id);
		return $reviewer;
	}
	
	public function getActive() {
		return $this->active;
	}
	
	public function getUpdatedDate() {
		return $this->updated_date;
	}
	
	public function getUpdatedBy() {
		$user = User::fetchRowByID($this->updated_by);
		return $user;
	}
	
	public function getArtifact() {
		$artifact = Models_Eportfolio_Folder_Artifact::fetchRow($this->pfartifact_id);
		return $artifact;
	}
	
}

?>
