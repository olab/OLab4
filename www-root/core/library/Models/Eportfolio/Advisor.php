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

class Models_Eportfolio_Advisor {

	private $padvisor_id,
			$proxy_id,
			$firstname,
			$lastname,
			$related,
            $portfolio_id,
			$active = 1;
	
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
	
	public static function fetchRow($proxy_id, $organisation_id = 1) {
		global $db;
		
		$query = "SELECT a.`padvisor_id`, b.`id` AS `proxy_id`, b.`firstname`, b.`lastname`
					FROM `portfolio-advisors` AS a
					JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON a.`proxy_id` = c.`user_id`
					AND c.`organisation_id` = ".$db->qstr($organisation_id)."
					WHERE a.`proxy_id` = ".$db->qstr($proxy_id)."
					GROUP BY a.`proxy_id`";
		$result = $db->GetRow($query);
		if ($result) {
			$query = "SELECT a.* FROM `".AUTH_DATABASE."`.`user_relations` AS a
						JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON a.`to` = b.`user_id` 
						WHERE a.`from` = ".$db->qstr($proxy_id)."
						AND b.`organisation_id` = ".$db->qstr($organisation_id);
			$related = $db->GetAll($query);
			if ($related) {
				$result["related"] = $related;
			}
			$advisor = new self($result);
			return $advisor;
		} else {
			return false;
		}
	}
	
	public static function fetchAll($organisation_id = 1) {
		global $db;
		
		$query = "SELECT a.`padvisor_id`, b.`id` AS `proxy_id`, b.`firstname`, b.`lastname`
					FROM `portfolio-advisors` AS a
					JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON a.`proxy_id` = c.`user_id`
					AND c.`organisation_id` = ".$db->qstr($organisation_id)."
					GROUP BY a.`proxy_id`
                    ORDER BY b.`lastname`, b.`firstname`";
		$results = $db->GetAll($query);
		if ($results) {
			$advisors = array();
			foreach ($results as $result) {
				$query = "SELECT a.* FROM `".AUTH_DATABASE."`.`user_relations` AS a
							JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON a.`to` = b.`user_id` 
							WHERE a.`from` = ".$db->qstr($proxy_id)."
							AND b.`organisation_id` = ".$db->qstr($organisation_id);
				$related = $db->GetAll($query);
				if ($related) {
					$result["related"] = $related;
				}
				$advisors[] = new self($result);
			}
			return $advisors;
		} else {
			return false;
		}
	}
	
	public function insert() {
		global $db;
		if ($db->AutoExecute("`portfolio-advisors`", $this->toArray(), "INSERT")) {
			$this->padvisor_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
	
	public function update() {
		global $db;
		if ($db->AutoExecute("`portfolio-advisors`", $this->toArray(), "UPDATE", "`padvisor_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
	
	public function delete() {
		global $db;
		
		$query = "DELETE FROM `portfolio-advisors` WHERE `padvisor_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}
	
	public function getFirstName() {
		return $this->firstname;
	}

    /**
     * Sets the firstname.
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }
	
	public function getLastName() {
		return $this->lastname;
	}

    /**
     * Sets the lastname.
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }
	
	public function getID() {
		return $this->padvisor_id;
	}
	
	public function getProxyID() {
		return $this->proxy_id;
	}
	
	public function getRelated() {
		return $this->related;
	}

    /**
     * Sets the related array;
     *
     * @param $related Array
     */
    public function setRelated($related) {
        $this->related = $related;
    }

    /**
     * Set the portfolio_id.
     *
     * @param int $portfolio_id
     */
    public function setPortfolioID($portfolio_id)
    {
        $this->portfolio_id = $portfolio_id;
    }

    /**
     * Get the portfolio_id.
     *
     * @return int
     */
    public function getPortfolioID()
    {
        return $this->portfolio_id;
    }

    /**
     * Set the Active value.
     *
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get the Active value.
     *
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

	public static function deleteRelation($advisor_id, $student_id) {
		global $db;
		$query = "DELETE FROM `".AUTH_DATABASE."`.`user_relations` WHERE `from` = ".$db->qstr($advisor_id)." AND `to` = ".$db->qstr($student_id);
		$result = $db->Execute($query);
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function addRelation($advisor_id, $student_id) {
		global $db;
		$query = "INSERT INTO `".AUTH_DATABASE."`.`user_relations` (`from`, `to`, `type`) VALUES (".$db->qstr($advisor_id).", ".$db->qstr($student_id).", '1')";
		$result = $db->Execute($query);
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
	
}

?>
