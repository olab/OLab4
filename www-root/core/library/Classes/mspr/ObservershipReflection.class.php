<?php

/**
 * 
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Simple class for data-entry of observerships. XXX Replace when policy and plan in place for observserships going forward.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

require_once 'core/library/Classes/utility/ModelBase.class.php';
require_once 'core/library/Classes/utility/Validation.interface.php';
class ObservershipReflection extends ModelBase implements Validation {
	protected $id;
	protected $observership_id;
	protected $physicians_role;
	protected $physician_reflection;
	protected $role_practice;
	protected $observership_challenge;
	protected $discipline_reflection;
	protected $challenge_predictions;
	protected $questions;
	protected $career;
	
	function __construct($post = false, $mode="") {
		parent::__construct();
		if ($post) {
			$r = $this->mapArray($post,$mode); 
			if ($r) {
				if ($mode == "create") {
					$this->create();
				}
				return $this;
			} else {
				return false;
			}
		}
	}


	/**
	* Redo the following three functions to pull from a common ValidationFields array that contains information 
	* about if they're required, what their rules are and whether or not they should be visible in an array version of the model.
	*/
	public static function fromArray(array $arr, $mode = "add") {
		$res = parent::fromArray($arr,$mode);
		return $res;
	}

	public function fetchRequiredFields(){
		return array(	"observership_id",
						"physicians_role",	
						"physician_reflection",		
						"observership_challenge",									
						"discipline_reflection",						
						"career");		
	}

	public function fetchFieldRules(){
		return array(	"id"=>array("int"),
						"observership_id"=>array("int"),
						"physicians_role"=>array("trim", "notags"),	
						"physician_reflection"=>array("trim", "notags"),	
						"observership_challenge"=>array("trim", "notags"),	
						"discipline_reflection"=>array("trim", "notags"),	
						"career"=>array("int")
					);
	}

	public function fetchArrayFields(){
		return array(	"id",
						"observership_id",
						"physicians_role",	
						"physician_reflection",		
						"observership_challenge",									
						"discipline_reflection",						
						"career"
					);
	}	
	
	public function getObservership() {
		return Observership::get($this->observership_id);
	}
	
	public function getObservershipID() {
		return $this->observership_id;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function getPhysiciansRole() {
		return $this->physicians_role;
	}
	
	public function getPhysicianReflection() {
		return $this->physician_reflection;
	}
	
	public function getRolePractice() {
		return $this->role_practice;
	}
	
	public function getObservershipChallenge() {
		return $this->observership_challenge;
	}
	
	public function getDisciplineReflection() {
		return $this->discipline_reflection;
	}
	
	public function getChallengePredictions() {
		return $this->challenge_predictions;
	}
	
	public function getQuestions() {
		return $this->questions;
	}
	
	public function getCareer() {
		return $this->career;
	}
	
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `observership_reflections` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {			
			$obs = ObservershipReflection::fromArray($result);
			return $obs;
		}
	}
	
	public function create() {
		global $db;
		
		$data = $this->toArray();
		if(!$db->AutoExecute("observership_reflections", $data, "INSERT")) {
			add_error("Failed to create new Observership Reflection.");
			application_log("error", "Unable to update a observership_reflections record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added new Observership Reflection.");
			$insert_id = $db->Insert_ID();
			return self::get($insert_id); 
		}
	}
	
	public function delete($id = false) {		
		global $db;
		$id = (int)$id?$id:$this->id;
		if ($this->status == "pending") {
			$query = "DELETE FROM `observership_reflections` where `id`=".$db->qstr($id);
			if(!$db->Execute($query)) {
				application_log("error", "Unable to delete a observership_reflections record. Database said: ".$db->ErrorMsg());
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	public function update($id = false) {
		global $db;
		$id = (int) $id ? $id : $this->id;

		$data = $this->toArray();
		if(!$db->AutoExecute("observership_reflections", $data, "UPDATE", "`id` = ".$db->qstr($id))) {
			add_error("Failed to update Observership Reflection.");
			application_log("error", "Unable to update a observership_reflections record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Thank you, your observership reflection has been updated. You will be redirected to your observerships list in 5 seconds. Please <a href=\"".ENTRADA_URL."/profile/observerships\">click here</a> if you do not wish to wait.");
			return true; 
		}
	}
}