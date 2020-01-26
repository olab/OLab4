<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
*/

 
interface Validation {
	
	/**
	 * returns a list of required fields for Model
	 */
	public function fetchRequiredFields();


	/**
	 * returns a list of field validation rules for Model
	 */
	public function fetchFieldRules();

	/**
	 * returns a list of fields that should be included in an array version of the Model
	 */
	public function fetchArrayFields();	

}