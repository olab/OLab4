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

/**
 * Simple User class with basic information
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */

require_once("User.class.php");

/**
 * Minor Extension of User class specific to faculty members who have the clinical flag set to 1
 * @author Jonathan Fingland
 *
 */
class ClinicalFacultyMember extends User{
	
	/**
	 * Alias for User::fromArray()
	 * @see User::fromArray()
	 * @param array $arr
	 * @return ClinicalFacultyMember
	 */
	public static function fromArray(array $arr, User $user) {
		$user = new self(); //to ensure the object is of the correct type
		$user = parent::fromArray($arr, $user);
		return $user;
	}
		
	/**
	 * Alias for User::get()
	 * @todo Modify to use actual db query to enforce clinical flag validity
	 * 
	 * @param int $proxy_id
	 * @return User
	 */
	public static function get($proxy_id = 0, $reload_cache = false) {
		return parent::get($proxy_id, $reload_cache);
	
	}
}