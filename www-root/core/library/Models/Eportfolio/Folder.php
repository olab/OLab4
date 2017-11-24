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
 * Models_Eportfolio_Folder
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */

class Models_Eportfolio_Folder {

	private $pfolder_id,
			$portfolio_id,
			$title,
			$description,
			$allow_learner_artifacts,
			$order,
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
	
	public static function fetchRow($pfolder_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_folders` WHERE `pfolder_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($pfolder_id, $active));
		if ($result) {
			$folder = new self($result);
			return $folder;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($portfolio_id = NULL, $flagged = false, $proxy_id = NULL, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolio_folders` WHERE ".(!is_null($portfolio_id) ? " `portfolio_id` = " .$db->qstr($portfolio_id) . " AND " : "")." `active` = ?";
		
		if ($flagged) {
			$query = "SELECT a.*, c.`proxy_id`
						FROM `portfolio_folders` AS a
						LEFT JOIN `portfolio_folder_artifacts` AS b
						ON a.`pfolder_id` = b.`pfolder_id`
						LEFT JOIN `portfolio_entries` AS c
						ON b.`pfartifact_id` = c.`pfartifact_id`
						WHERE c.`flag` = 1
						AND c.`proxy_id` = ".$db->qstr($proxy_id)."
						AND a.`active` = ?";
		}
		
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
		if ($db->AutoExecute("`portfolio_folders`", $this->toArray(), "INSERT")) {
			$this->pfolder_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolio_folders`", $this->toArray(), "UPDATE", "`pfolder_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolio_folders` WHERE `pfolder_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}	
	
	public function getID() {
		return $this->pfolder_id;
	}
	
	public function getPortfolioID() {
		return $this->portfolio_id;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function getAllowLearnerArtifacts() {
		return $this->allow_learner_artifacts;
	}
	
	public function getOrder() {
		return $this->order;
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
	
	public function getArtifacts($proxy_id = NULL) {
		$artifacts = Models_Eportfolio_Folder_Artifact::fetchAll($this->pfolder_id, $proxy_id);
		return $artifacts;
	}
	
	public function getPortfolio() {
		$portfolio = Models_Eportfolio::fetchRow($this->portfolio_id);
		return $portfolio;
	}
	
}

?>
