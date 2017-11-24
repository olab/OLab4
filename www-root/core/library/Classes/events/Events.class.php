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
 * Utility Class for getting a list of Events
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Events extends Collection {
	
	/**
	 * Returns a Collection of Event objects 
	 * @param array
	 * @return Users
	 */
	static public function get() {
		$query = "SELECT * from `events`";
		
		$results = $db->getAll($query);
		$events = array();
		if ($results) {
			foreach ($results as $result) {
				$event = new Event($result['event_id'],$result['recurring_id'],$result['region_id'],$result['course_id'],$result['event_phase'],$result['event_title'],$result['event_description'],$result['event_goals'],$result['event_objectives'],$result['event_message'],$result['event_location'],$result['event_start'],$result['event_finish'],$result['event_duration'],$result['release_date'],$result['release_until'],$result['updated_date'],$result['updated_by']);
				$events[] = $event;
			}
		}
		return new self($events);
	}
	
	/**
	 * Returns all events for which the provided user is a listed contact
	 * @param User $user
	 * @return Events
	 */
	static public function getByContact(User $user) {
		global $db;
		$user_id = $user->getID();
		$query = "SELECT distinct a.* from `events` a join `event_contacts` b on a.`event_id`=b.`event_id` where `proxy_id`=?";
		$results = $db->getAll($query, array($user_id));
		$events = array();
		if ($results) {
			foreach ($results as $result) {
				$event = new Event($result['event_id'],$result['recurring_id'],$result['region_id'],$result['course_id'],$result['event_phase'],$result['event_title'],$result['event_description'],$result['event_goals'],$result['event_objectives'],$result['event_message'],$result['event_location'],$result['event_start'],$result['event_finish'],$result['event_duration'],$result['release_date'],$result['release_until'],$result['updated_date'],$result['updated_by']);
				$events[] = $event;
			}
		}
		return new self($events);
	}

}