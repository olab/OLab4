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

require_once("Award.class.php");
require_once("InternalAward.class.php");

/**
 * Model for retrieving and modifying awards list
 * 
 * @author jonathan fingland
 */
class InternalAwards extends Collection {
	
	/**
	 * array of Awards objects
	 * @var array
	 */
	private static $awards;
	
	/**
	 * Used for determining if results have already been retrieved
	 * @var boolean
	 */
	private static $initialized = false;
	
	/**
	 * Adds the provided Award to the collection
	 * @param Award $award
	 */
	public static function add(InternalAward $award) {
		//Doesn't mean anything to add when the collection hasn't been initialized by get()  
		if (! self::$initialized) {
			array_push(self::$awards, $award);
		}
	}
	
	/**
	 * Sorts the collection by award title 
	 * @param int $direction
	 */
	public function sort($direction = SORT_ASC, $sort_by = '') {
		static $last_direction;

		if (!is_null($direction)) {
			$last_direction = $direction;
		} elseif(!is_null($last_direction)) {
			$direction = $last_direction;
		} else {
			$direction = SORT_ASC;
			$last_direction = $direction;
		}
		
		usort($awards,array("Award","compare_awards"));
		if ( $direction == SORT_DESC) {
			array_reverse($awards);
		}
	}
	
	/**
	 * 
	 * @param bool $refresh
	 * @return array:
	 */
	static function get($refresh = false) {
		global $db;
		if (! self::$initialized || $refresh) {
			self::$awards = array();
			$query		= "SELECT *, `id` as award_id FROM `student_awards_internal_types` order by title asc";
			$results	= $db->GetAll($query);
			foreach ($results as $result) {
				array_push(self::$awards, InternalAward::fromArray($result));
			}
			self::$initialized = true;

		}
		return (self::$awards);
		//return new self(self::$awards);
	}
}