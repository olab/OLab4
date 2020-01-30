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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

require_once("Classes/utility/SimpleCache.class.php");
require_once("User.class.php");
require_once("Classes/utility/Collection.class.php");

/**
 * Utility Class for getting a list of Users
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Users extends Collection {
	
	/**
	 * Returns a Collection of User objects 
	 * TODO add criteria to selection process 
	 * @param array
	 * @return Users
	 */
	static public function get($organisation=null, $group=null, $role=null, $proxy_id=null) {
		global $db;
		$query = "SELECT a.*, b.`group`, b.`role`, b.`id` AS `access_id` from `".AUTH_DATABASE."`.`user_data` a LEFT JOIN `".AUTH_DATABASE."`.`user_access` b on a.`id`=b.`user_id` and b.`app_id`=?";
		$conditions = generateAccessConditions($organisation, $group, $role, $proxy_id, 'b');
		if ($conditions) {
			$query .= " WHERE " . $conditions;
		}
		$query.=" ORDER BY lastname, firstname";
		//note to self. check use index page for user access components of display
		$results = $db->getAll($query, array(AUTH_APP_ID));
		$users = array();
		if ($results) {
			foreach ($results as $result) {
				$user = new User();
				$user =  User::fromArray($result, $user);
				$cohort = groups_get_cohort($result["id"]);
				if ($cohort) {
					$user->setCohort($cohort["group_id"]);
				}
				$users[] = $user;
			}
		}
		return new self($users);
	}

}