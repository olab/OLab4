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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version 
 */

require_once("ClinicalFacultyMember.class.php");
require_once("Classes/utility/Collection.class.php");

/**
 * Utility Class for getting a list of Medical Faculty Members
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ClinicalFacultyMembers extends Collection {
	
	static public function get() {
		global $db, $ENTRADA_USER;
		$ORGANISATION_ID	= $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["organisation_id"];
		
		$curdbname = $db->databaseName;
		$db->SelectDB(AUTH_DATABASE);
		$query = "SELECT distinct `user_data`.*
				FROM user_data 
				LEFT JOIN user_access ON `user_access`.`user_id` = `user_data`.`id`
				where user_access.group='faculty' and clinical='1' and `user_data`.`organisation_id`=? group by lastname,firstname 
				order by lastname,firstname";
		$results	= $db->GetAll($query, array($ORGANISATION_ID));
		$db->SelectDB($curdbname);
		$faculty_members = array();
		if ($results) {
			foreach ($results as $result) {
			    $member = new User();
				$faculty_member = ClinicalFacultyMember::fromArray($result, $member);
				$faculty_members[] = $faculty_member;
			}
		}
		return new self($faculty_members);
	}
}			
		