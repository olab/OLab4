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

/**
 * Utility Class for getting a list of Courses
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Courses extends Collection {
	
	/**
	 * Returns a Collection of Course objects
	 * TODO add criteria to selection process 
	 * @param array
	 * @return Courses
	 */
	static public function get($active_only=false) {
		global $db;
		$query = "SELECT * from `courses`";
		if ($active) {
			$query .= " where `active`=1";
		}
		
		$results = $db->getAll($query);
		$courses = array();
		if ($results) {
			foreach ($results as $result) {
				$course =  Course::fromArray($result);
				$courses[] = $course;
			}
		}
		return new self($courses);
	}
	
	/**
	 * Returns courses for which the supplied user is an owner (director or program coordinator)
	 * @param User $user
	 * @param boolean $active_only flag to determine if only active courses should be included
	 * @return Courses
	 */
	static public function getByOwner(User $user, $active_only=false ) {
		global $db;
		$user_id = $user->getID();
		
		$query = "SELECT * from `courses` where (`director_id`=".$db->qstr($user_id) ." OR `pcoord_id`=".$db->qstr($user_id).")";
		if ($active) {
			$query .= " and `active`=1";
		}
		$results = $db->getAll($query);
		$courses = array();
		if ($results) {
			foreach ($results as $result) {
				$course =  Course::fromArray($result);
				$courses[] = $course;
			}
		}
		return new self($courses);
	}

}