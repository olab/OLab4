<?php

/**
 * Special Collection of Users in which all elements are contacts for a specific event
 * @author Jonathan Fingland
 *
 */
class EventContacts extends Users {
	
	/**
	 * Returns a Collection of User objects which are specified as contacts for the provided event ID
	 * @param int $event_id
	 * @return EventContacts
	 */
	static function get($event_id) {
		global $db;
		$query = "SELECT * from `event_contacts` where `event_id`=".$db->qstr($event_id);
		
		$results = $db->getAll($query);
		$contacts = array();
		if ($results) {
			foreach ($results as $result) {
				$contact = User::fetchRowByID($result['proxy_id']);
				
				if ($contact) {
					$contacts[] = $contact;
				}
			}
		}
		return new self($contacts);
	}
}