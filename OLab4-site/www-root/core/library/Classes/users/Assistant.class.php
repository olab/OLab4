<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

/**
 * Extends the User class with properties for Assistants 
 * @author Jonathan Fingland
 *
 */
class Assistant extends User {
	
	/**
	 * Internal ID for this record
	 * @var int
	 */
	private $permission_id;
	
	/**
	 * Proxy ID of the user to whcih this Assistant is assigned 
	 * @var int
	 */
	private $assigned_to;
	
	/**
	 * timestamp of the date from which this relationship becomes valid 
	 * @var int
	 */
	private $valid_from;
	
	/**
	 * timestamp of the date on which this relationship becomes invalid
	 * @var int
	 */
	private $valid_until;
	
	/**
	 * Adds the Assistant related fields to this object
	 * @param array $arr
	 */
	private function addAssistantFields(array $arr) { 
		$this->permission_id = $arr['permission_id'];
		$this->assigned_to = $arr['assigned_to'];
		$this->valid_from = $arr['valid_from'];
		$this->valid_to = $arr['valid_to'];
	}

	/**
	 * Returns an Assistant object based on the Array inputs. calls the parent (User) fromArray method to setup the User fields
	 * @param array $arr
	 * @return Assistant
	 */
	public static function fromArray(array $arr) {
		$asst = new self(); 
		$asst = parent::fromArray($arr, $asst);
		$asst->addAssistantFields($arr);
		return $asst;
	}
}
