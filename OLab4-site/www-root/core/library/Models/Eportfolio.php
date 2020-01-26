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
 * Models_Eportfolio
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 */

class Models_Eportfolio {

	private $portfolio_id,
			$group_id,
			$portfolio_name,
			$start_date,
			$finish_date,
			$active = 1,
			$updated_date,
			$updated_by,
			$organisation_id,
			$allow_student_export = 1;
	
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
				if (!is_null($this->$class_var)) {
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
	
	public static function fetchRow($portfolio_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolios` WHERE `portfolio_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($portfolio_id, $active));
		if ($result) {
			$portfolio = new self($result);
			return $portfolio;
		} else {
			return false;
		}
	}
	
	public static function fetchRowByGroupID($group_id, $active = 1) {
		global $db;
		
		$query = "SELECT * FROM `portfolios` WHERE `group_id` = ? AND `active` = ?";
		$result = $db->GetRow($query, array($group_id, $active));
		if ($result) {
			$portfolio = new self($result);
			return $portfolio;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($organisation_id = NULL, $advisor = NULL, $active = 1) {
		global $db;
		
		if (!is_null($advisor)) {
			$query = "SELECT e.*
						FROM `".AUTH_DATABASE."`.`user_relations` AS a
						JOIN `portfolio_folder_artifacts` AS b
						ON a.`to` = b.`proxy_id`
						JOIN `portfolio_entries` AS c
						ON a.`to` = c.`proxy_id`
						JOIN `portfolio_folders` AS d
						ON b.`pfolder_id` = d.`pfolder_id`
						JOIN `portfolios` AS e
						ON d.`portfolio_id` = e.`portfolio_id`
						WHERE a.`from` = ".$db->qstr($advisor)."
						GROUP BY e.`portfolio_id`
						ORDER BY e.`portfolio_name` ASC";
		} else {
			$query = "SELECT *
						FROM `portfolios`
						WHERE ".(!is_null($organisation_id) ? " `organisation_id` = ".$db->qstr($organisation_id)." AND " : "")."
						`active` = ".$db->qstr($active) ."
						ORDER BY `portfolio_name` ASC";
		}


		$results = $db->GetAll($query);
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
		if ($db->AutoExecute("`portfolios`", $this->toArray(), "INSERT")) {
			$this->portfolio_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolios`", $this->toArray(), "UPDATE", "`portfolio_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolios` WHERE `portfolio_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}

	public function getID() {
		return $this->portfolio_id;
	}
	
	public function getGroupID() {
		return $this->group_id;
	}
	
	public function getGroup($flagged = false, $proxy_id = false) {
		global $db;
		
		if ($flagged) {
			if ($proxy_id) {
				$query = "SELECT a.`proxy_id`
							FROM `group_members` AS a 
							JOIN `portfolio_entries` AS b
							ON a.`proxy_id` = b.`proxy_id`
							JOIN `entrada-gh-auth`.`user_relations` AS c
							ON a.`proxy_id` = c.`to`
							WHERE a.`group_id` = ".$db->qstr($this->group_id)." AND a.`member_active` = 1
							AND b.`flag` = 1
							AND c.`from` = ".$db->qstr($proxy_id)."
							GROUP BY a.`gmember_id`, a.`proxy_id`";
			} else {
				$query = "SELECT a.`proxy_id`
							FROM `group_members` AS a 
							JOIN `portfolio_entries` AS b
							ON a.`proxy_id` = b.`proxy_id`
							WHERE a.`group_id` = ".$db->qstr($this->group_id)." AND a.`member_active` = 1
							AND b.`flag` = 1
							GROUP BY a.`gmember_id`, a.`proxy_id`";
			}
		} else {
			if ($proxy_id) {
				$query = "SELECT a.* FROM `group_members` AS a 
							LEFT JOIN `".AUTH_DATABASE."`.`user_relations` AS b
							ON a.`proxy_id` = b.`to`
							WHERE a.`group_id` = ".$db->qstr($this->group_id)." AND a.`member_active` = 1 AND b.`from` = ".$db->qstr($proxy_id);
			} else {
				$query = "SELECT * FROM `group_members` WHERE `group_id` = ".$db->qstr($this->group_id)." AND `member_active` = 1";
			}
		}
		$results = $db->GetAll($query);
		if ($results) {
			$g_members = array();
			$i = 0;
			foreach ($results as $result) {
				$user = User::fetchRowByID($result["proxy_id"]);
				if ($user) {
					$g_members[$i]["proxy_id"] = $user->getID();
					$g_members[$i]["firstname"] = $user->getFirstname();
					$g_members[$i]["lastname"] = $user->getLastname();
					$i++;
				}
			}
			if (!empty($g_members)) {
				return $g_members;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function getPortfolioName() {
		return $this->portfolio_name;
	}
	
	public function getStartDate() {
		return $this->start_date;
	}
	
	public function getFinishDate() {
		return $this->finish_date;
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
	
	public function getOrganisationID() {
		return $this->organisation_id;
	}
	
	public function getAllowStudentExport() {
		return $this->allow_student_export;
	}
	
	public function getFolders() {
		$folders = Models_Eportfolio_Folder::fetchAll($this->portfolio_id);
		return $folders;
	}
	
	public function copy($old_portfolio_id) {
		$old_folders = Models_Eportfolio_Folder::fetchAll($old_portfolio_id);
		if ($old_folders) {
			foreach ($old_folders as $folder) {
				$old_folder_artifacts = $folder->getArtifacts();
				$folder->fromArray(array("pfolder_id" => NULL, "portfolio_id" => $this->portfolio_id));
				if ($folder->insert()) {
					if ($old_folder_artifacts) {
						foreach ($old_folder_artifacts as $artifact) {
							$artifact->fromArray(array("pfartifact_id" => NULL, "pfolder_id" => $folder->getID()));
							if (!$artifact->insert()) {
								$error;
							}
						}
					}
				}
			}
		}
		if (!$error) {
			return true;
		} else {
			return false;
		}
	}
	
}

?>
