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
 * Models_Eportfolio_Entry
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */
class Models_Eportfolio_Entry {

	private $pentry_id,
			$pfartifact_id,
			$proxy_id,
			$type,
			$submitted_date,
			$reviewed_date,
			$reviewed_by,
			$flag,
			$flagged_by,
			$flagged_date,
			$_edata,
			$order,
			$active = 1,
			$updated_date,
			$updated_by;
	
	public function __construct($arr = NULL) {
		if (is_array($arr)) {
			$this->fromArray($arr);
		}
	}
	
	private function _toArray() {
		$arr = array();
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				$arr[$class_var] = $this->$class_var;
			}
		}
		return $arr;
	}
	
	public function toArray() {
		$arr = false;
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				if ($class_var == "_edata") {
					$arr[$class_var] = unserialize($this->$class_var);
				} else {
					$arr[$class_var] = $this->$class_var;
				}
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
	
	public static function fetchRow($pentry_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_entries` WHERE `pentry_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($pentry_id, $active));
		
		if ($result) {
			$folder = new self($result);
			return $folder;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($pfartifact_id = NULL, $proxy_id = NULL, $active = 1) {
		global $db;
		
		$query = "	SELECT * FROM `portfolio_entries` WHERE ".
					(!is_null($pfartifact_id) ? "`pfartifact_id` = " . $db->qstr($pfartifact_id) . " AND " : ""). 
					(!is_null($proxy_id) ? "`proxy_id` = " . $db->qstr($proxy_id) . " AND " : "")."
					`active` = ?";
		
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
		
		$arr = array();
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
				$arr[$class_var] = $this->$class_var;
			}
		}
		
		if ($db->AutoExecute("`portfolio_entries`", $arr, "INSERT")) {
			$this->pentry_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolio_entries`", $this->_toArray(), "UPDATE", "`pentry_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolio_entries` WHERE `pentry_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}

	public function getID() {
		return $this->pentry_id;
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
	
	public function getType() {
		return $this->type;
	}
	
	public function getSubmittedDate() {
		return $this->submitted_date;
	}
	
	public function getReviewedDate() {
		return $this->reviewed_date;
	}
	
	public function getFlag() {
		return $this->flag;
	}
	
	public function getFlagedBy() {
		$flagged_by = User::fetchRowByID($this->flagged_by);
		return $flagged_by;
	}
	
	public function getFlaggedDate() {
		return $this->flagged_date;
	}
	
	public function getOrder() {
		return $this->order;
	}
	
	public function getEdata() {
		return $this->_edata;
	}
	
	public function getEdataDecoded() {
		return unserialize($this->_edata);
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
	
	public function getComments() {
		$comments = Models_Eportfolio_Entry_Comment::fetchAll($this->pentry_id);
		return $comments;
	}
	
	public function getPermissions() {
		$permissions = Models_Eportfolio_Entry_Permission::fetchAll($this->pentry_id);
	}
	
	public function saveFile($tmp_filename) {
		$pfartifact = $this->getPfartifact();
	
		$pfolder = $pfartifact->getFolder();

		$portfolio = $pfolder->getPortfolio();

		if (!is_dir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID())) {
			mkdir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID(), 0777);
		}

		if (!is_dir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID())) {
			mkdir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID(), 0777);
		}

		if (!is_dir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID())) {
			mkdir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID(), 0777);
		}

		if (!is_dir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$this->getProxyID())) {
			mkdir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$this->getProxyID(), 0777);
		}

		if (is_dir(EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$this->getProxyID())) {
			$file_realpath = EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$this->getProxyID()."/".$this->getID();

			if (copy($tmp_filename, $file_realpath)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
}

?>
