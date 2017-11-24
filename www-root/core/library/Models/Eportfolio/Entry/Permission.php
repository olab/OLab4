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
 * Models_Eportfolio_Entry_Permission
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */
class Models_Eportfolio_Entry_Permission {

	private $permission_id,
			$pentry_id,
			$allow_to,
			$proxy_id,
			$view,
			$comment,
			$edit;
	
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
	
	public static function fetchRow($permission_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_artifact_permissions` WHERE `permission_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($permission_id, $active));
		if ($result) {
			$folder = new self($result);
			return $folder;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($pentry_id = NULL, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_artifact_permissions` WHERE " . (!is_null($pentry_id) ? "`pentry_id` = " . $db->qstr($pentry_id) . " AND " : "") . " `active` = ?";
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
		if ($db->AutoExecute("`portfolio_artifact_permissions`", $this->toArray(), "INSERT")) {
			$this->permission_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolio_artifact_permissions`", $this->toArray(), "UPDATE", "`permission_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolio_artifact_permissions` WHERE `permission_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}
	
	public function getID() {
		return $this->permission_id;
	}
	
	public function getPentryID() {
		return $this->pentry_id;
	}
	
	public function getPentry() {
		$pentry = Models_Eportfolio_Entry::fetchRow($this->pentry_id);
		return $pentry;
	}
	
	public function getAllowTo() {
		return $this->allow_to;
	}
	
	public function getProxyID() {
		return $this->proxy_id;
	}
	
	public function getView() {
		return $this->view;
	}
	
	public function getComment() {
		return $this->comment;
	}
	
	public function getEdit() {
		return $this->edit;
	}
	
	public function getEntry() {
		$entry = Models_Eportfolio_Entry::fetchRow($this->pentry_id);
		return $entry;
	}
	
}

?>
