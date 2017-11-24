<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
*/

 
interface Sequenceable {
	
	/**
	 * Orders the implementing classes contents according to the provided sequence. 
	 * @param unknown_type $user_id
	 * @param array $ids
	 */
	public function setSequence($user_id, array $ids);
}