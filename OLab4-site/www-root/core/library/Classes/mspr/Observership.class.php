<?php

/**
 * 
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Simple class for data-entry of observerships. XXX Replace when policy and plan in place for observserships going forward.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

require_once 'core/library/Classes/utility/ModelBase.class.php';
class Observership extends ModelBase {
	protected $id;
	protected $student_id;
	protected $title;
	protected $activity_type;
	protected $organisation;
	protected $observership_details;
	protected $clinical_discipline;
	protected $site;
	protected $start;
	protected $end;
	protected $city;
	protected $prov;
	protected $country;
	protected $location;
	protected $preceptor_firstname;
	protected $preceptor_lastname;
	protected $preceptor_prefix;
	protected $preceptor_proxy_id;
	protected $preceptor_email;
	protected $address_l1;
	protected $address_l2;
	protected $postal_code;
	protected $phone;
	protected $fax;
	protected $status;
	protected $unique_id;
	protected $notice_sent;
	protected $order;
	protected $reflection_id;
	protected $career;
	protected $LOADED_PRECEPTOR = false;
	
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
	public static function fromArray(array $arr, $mode = "add", $proxy_id = false) {
		global $ENTRADA_USER;
		$res = parent::fromArray($arr,$mode);		
		if ($res && $mode != "fetch") {
			//due to the way generate_calendars works this is a necessary step
			
			$res->start = isset($res->observership_start_date) ? strtotime($res->observership_start_date) : (isset($res->start) ? $res->start : 0);
			$res->end = isset($res->observership_finish_date) ? strtotime($res->observership_finish_date) : (isset($res->end) ? $res->end : 0);

			if(!$res->start){
				add_error("<strong>Observership Start</strong> is a required field. Please ensure you've provided a value.");
				$res->VALID = false;				
			}
			
			if (($ENTRADA_USER->getGroup() != "staff" && $ENTRADA_USER->getGroup() != "medtech") && $res->start < strtotime(date("Y-m-d", time()))) {
				add_error("Entry of historical observerships is not available, any entered observership must start tomorrow or later.");
				$res->VALID = false;
			}

			if ($ENTRADA_USER->getGroup() == "staff" || $ENTRADA_USER->getGroup() == "medtech") {
				$res->status = "approved";
			}
			
			if(!$res->end){
				$res->end = $res->start;
			}
			
			if ($res->end < $res->start) {
				add_error("<strong>Observership Start</strong> is before <strong>Observership End</strong>.");
				$res->VALID = false;
			}
			
			//if proxy id set, map it to the field used by Model/database		
			if (isset($arr["preceptor_associated_director"]) && $arr["preceptor_associated_director"]){
				$res->preceptor_proxy_id = (int) $arr["preceptor_associated_director"];
				$res->preceptor_firstname = "";
				$res->preceptor_lastname = "";
				$res->preceptor_email = "";
			} elseif (!(isset($arr["preceptor_associated_director"]) && $arr["preceptor_associated_director"]) 
					&& !(isset($arr["preceptor_firstname"]) && $arr["preceptor_firstname"]
							&& isset($arr["preceptor_lastname"]) && $arr["preceptor_lastname"]
							 && isset($arr["preceptor_email"]) && $arr["preceptor_email"])) {
				//if no proxy id, and no manually entered data, data is invalid and error needs to display
				add_error("<strong>Preceptor</strong> is a required field. Please ensure you've provided a value.");
				$res->VALID = false;
			}else{
				$res->preceptor_proxy_id = "";
			}
			if (isset($arr["activity_type"]) && $arr["activity_type"] == "ipobservership"){
				if(!isset($arr["observership_details"]) || !$arr["observership_details"] || trim($arr["observership_details"]) == ""){
					add_error("<strong>Observership Details</strong> is a required field. Please ensure you've provided a value.");
					$res->VALID = false;					
				}
			}
            
            
            if (!isset($arr["activity_type"]) || trim($arr["activity_type"]) == "") {
                add_error("<strong>Activity Type</strong> is a required field. Please ensure you've provided a value.");
            }
            
            if (!isset($arr["clinical_discipline"]) || trim($arr["clinical_discipline"]) == "") {
                add_error("<strong>Eligible Clinical Disciplines</strong> is a required field. Please ensure you've provided a value.");
            }
            
            if (!isset($arr["organisation"]) || trim($arr["organisation"]) == "") {
                add_error("<strong>Organisation</strong> is a required field. Please ensure you've provided a value.");
            }
            
            if (!isset($arr["address_l1"]) || trim($arr["address_l1"]) == "") {
                add_error("<strong>Address Line 1</strong> is a required field. Please ensure you've provided a value.");
            }
            
            if (!isset($arr["city"]) || trim($arr["city"]) == "") {
                add_error("<strong>City</strong> is a required field. Please ensure you've provided a value.");
            }
            
            if (trim($arr["prov_state"]) == "0") {
                add_error("<strong>Province</strong> is a required field. Please ensure you've provided a value.");
            }

			if($mode == "add"){
				if(!isset($arr["read"]) || !trim($arr["read"])){
					add_error("You must agree to the procedures and regulations of the Student Observership policy.");
					$res->VALID = false;					
				}
			}
			$res->student_id = $proxy_id ? $proxy_id : $ENTRADA_USER->getActiveId();
		}
		return $res;
	}

	public function fetchRequiredFields(){
		/*
		 * Temporarily disabled.
		 */
		if(true) {
		return array(	"activity_type",	
						"clinical_discipline",	
						"organisation",												
						"city",									
						"prov",		
						"country",						
						"address_l1",
						"observership_start");	
		}
		return array();
	}

	public function fetchFieldRules(){
		return array(	"id"=>array("int"),
						"title"=>array("trim","notags"),
						"status"=>array("trim","notags"),		
						"observership_details"=>array("trim","notags"),																
						"activity_type"=>array("trim","notags"),
						"organisation"=>array("trim","notags"),
						"clinical_discipline"=>array("trim","notags"),
						"supervisor"=>array("trim","notags"),
						"supervisor_email"=>array("trim","notags"),
						"prov"=>array("trim","notags"),
						"start"=>array("int"),
						"finish"=>array("int"),
						"address_l1"=>array("trim","notags"),
						"address_l2"=>array("trim","notags"),
						"postal_code"=>array("trim","notags"),
						"phone"=>array("trim","notags"),
						"fax"=>array("trim","notags"),
                        "city" => array("trim","notags"),
						"updated_date"=>array("int"),
						"updated_by"=>array("int")
					);
	}

	public function fetchArrayFields(){
		//easier to assume you want all fields in array with the exception of those you don't want
		//gets all fields and removes LOADED_PRECEPTOR and VALID which are only used for Model logic, not data
		$fields = array_keys(get_object_vars($this));
		return array_diff($fields,array('LOADED_PRECEPTOR','VALID'));
	}	
	
	public function getID() {
		return $this->id;
	}
	
	public function getStudentID() {
		return $this->student_id;	
	}
	
	public function getUser() {
		return User::fetchRowByID($this->student_id);
	}

	public function getSite() {
		return $this->city . ", " . $this->prov . ", " . $this->country;
	}
	
	public function getLocation () {
		return $this->location;
	}
	
	public function getActivityType(){
		return $this->activity_type;
	}

	public function getOrganisation(){
		return $this->organisation;
	}

	public function getClinicalDiscipline() {
		return $this->clinical_discipline;
	}	

	public function getObservershipDetails(){
		return $this->observership_details;
	}	

	public function getTitle(){
		return ucwords($this->title);
	}

	public function getAddressLine1(){
		return $this->address_l1;
	}

	public function getAddressLine2(){
		return $this->address_l2;
	}

	public function getCity(){
		return $this->city;
	}

	public function getProv(){
		return $this->prov;
	}

	public function getCountry(){
		return $this->country;
	}
	
	public function getPostalCode(){
		return $this->postal_code;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function getFax(){
		return $this->fax;
	}
	
	public function getPreceptorFirstname() {
		if ($this->preceptor_proxy_id) {
			$preceptor = $this->LOADED_PRECEPTOR?$this->LOADED_PRECEPTOR:$this->getPreceptor();
			if ($preceptor) {
				return $preceptor->getFirstname();
			}
		} else {
			return $this->preceptor_firstname;	
		}
	}
	
	public function getPreceptorLastname() {
		if ($this->preceptor_proxy_id) {
			$preceptor = $this->LOADED_PRECEPTOR?$this->LOADED_PRECEPTOR:$this->getPreceptor();
			if ($preceptor) {
				return $preceptor->getLastname();
			}
		} else {
			return $this->preceptor_lastname;
		}
	}
	
	public function getPreceptorPrefix() {
		if ($this->preceptor_proxy_id) {
			$preceptor = $this->LOADED_PRECEPTOR?$this->LOADED_PRECEPTOR:$this->getPreceptor();
			if ($preceptor) {
				return $preceptor->getPrefix();
			}
		} else {
			return $this->preceptor_prefix;
		}
	}
	
	public function getPreceptor() {
		if ($this->preceptor_proxy_id) {
			$this->LOADED_PRECEPTOR = User::fetchRowByID($this->preceptor_proxy_id);
			return $this->LOADED_PRECEPTOR;
		}
	}
	
	public function getPreceptorEmail() {
		if ($this->preceptor_email) {
			return $this->preceptor_email;
		} else {
			$preceptor = $this->LOADED_PRECEPTOR?$this->LOADED_PRECEPTOR:$this->getPreceptor();
			if ($preceptor) {
				return $preceptor->getEmail();
			}
		}
	}

	public function preceptorAutocompleted(){
		return $this->preceptor_proxy_id?$this->preceptor_proxy_id:false;
	}
	
	public function getDetails() {
		$preceptor = trim(($this->getPreceptorPrefix() ? $this->getPreceptorPrefix() . " " : "") . $this->getPreceptorFirstname() . " " . $this->getPreceptorLastname());
		
		$elements = array();
		$elements[] = ucwords($this->title);
		$elements[] = (!empty($this->site) ? $this->site : $this->organisation) . ", " . (!empty($this->location) ? $this->location : (!empty($this->city) ? $this->city . ", " : "") . (!empty($this->prov) ? $this->prov . ", " : ""). (!empty($this->country) ? $this->country : ""));
		$elements[] = $preceptor;
		$details = implode("\n", $elements);
		return $details;
	}
	
	public function getStart() {
		return $this->start;
	}
	
	public function getEnd() {
		if ($this->end) {
			return $this->end;
		} else {
			return $this->start;
		}
	}
	
	public function getStartDate() {
		return array(
			"d" => date("j", $this->start),
			"m" => date("n", $this->start),
			"y" => date("Y", $this->start)
		);
	}
	
	public function getEndDate() {
		if (!$this->end) {
			return $this->getStartDate();
		} else {
			return array(
				"d" => date("j", $this->end),
				"m" => date("n", $this->end),
				"y" => date("Y", $this->end)
			);
		}
	}
	
	public function getPeriod() {
		return formatDateRange($this->getStartDate(), $this->getEndDate()); 
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	public function getNoticeSent() {
		return $this->notice_sent;
	}
	
	public function getUniqueID() {
		return $this->unique_id;
	}
	
	public function getOrder() {
		return $this->order;
	}
	
	public function getReflection() {
		return $this->reflection_id;
	}


	public function getCareer() {
		return $this->career;
	}
	
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_observerships` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {		
			$obs = Observership::fromArray($result,"fetch");
			return $obs;
		}
	}
	
	public static function getByUniqueID($unique_id) {
		global $db;
		$query		= "SELECT * FROM `student_observerships` WHERE `unique_id` = ".$db->qstr($unique_id);
		$result = $db->getRow($query);
		if ($result) {
			$obs = Observership::fromArray($result, "fetch");
			return $obs;
		}
	}

	public function next($id){
		global $db;
		$query = "	SELECT * FROM `student_observerships` 
					WHERE `status` = 'pending' 
					AND `id` > ".$db->qstr($id)." LIMIT 1";						
					error_log($query);
		$response = $db->GetRow($query);					
		if (!$response) return false; 
		$next = Observership::fromArray($response);					
		
		foreach($next as $field=>$value){
			$this->$field = $value;
		}

		return $this;
	}	

	public function create() {
		global $db;
		
		if (!isset($this->status)) {
			$this->status = 'pending';	
		}
		$this->unique_id = hash("sha256", uniqid("obs-", true));
		$this->notice_id = 0;
		
		if ($this->preceptor_email) {
			$query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `email` = ".$db->qstr($this->preceptor_email)." OR `email_alt` = ".$db->qstr($this->preceptor_email);
			$proxy_id = $db->GetOne($query);
			if ($proxy_id) {
				$this->preceptor_proxy_id = $proxy_id;
			}
		}
		
		$query = "SELECT COUNT(*) AS `count` FROM `student_observerships` WHERE `student_id` = ".$db->qstr($this->student_id);
		$order = $db->GetOne($query);
		if ($order) {
			$this->order = $order;
		}
		if ($this->activity_type == "ipobservership") {
			$this->title = $this->clinical_discipline . " " . $this->observership_details;
		} else {
			$this->title = $this->clinical_discipline . " " . $this->activity_type;
		}
		$data = $this->toArray();
		
		if(!$db->AutoExecute("student_observerships", $data, "INSERT")) {
			add_error("Failed to create new Observership.".$db->ErrorMsg());
			application_log("error", "Unable to update a student_observerships record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added new Observership.");
			$insert_id = $db->Insert_ID();
			return self::get($insert_id); 
		}
	}
	
	public function delete($id = false) {
		global $db;
		$id = (int) $id ? $id : $this->id;
		if ($this->status == "pending" || $this->status == "approved" || $this->status == "rejected") {
			$query = "DELETE FROM `student_observerships` where `id`=".$db->qstr($id);
			if(!$db->Execute($query)) {
				application_log("error", "Unable to delete a student_observerships record. Database said: ".$db->ErrorMsg());
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

		if (!$this->preceptor_proxy_id) {
			$query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `email` = ".$db->qstr($this->preceptor_email)." OR `email_alt` = ".$db->qstr($this->preceptor_email);
			$proxy_id = $db->GetOne($query);
			if ($proxy_id) {
				$this->preceptor_proxy_id = $proxy_id;
			}
		}
		
		if ($this->activity_type == "ipobservership") {
			$this->title = $this->clinical_discipline . " " . $this->observership_details;
		} else {
			$this->title = $this->clinical_discipline . " " . $this->activity_type;
		}
		
		$data = $this->toArray();
		unset($data["id"]);
		if(!$db->AutoExecute("student_observerships", $data, "UPDATE", "`id` = ".$db->qstr($id))) {
			add_error("Failed to update Observership.");
			application_log("error", "Unable to update a student_observerships record. Database said: ".$db->ErrorMsg());
			return false;
			error_log($db->ErrorMsg());
		} else {
			add_success("Successfully updated Observership.");
			return true; 
		}
	}
		
	public function compare($obs, $compare_by='start') {
		switch($compare_by) {
			case 'start':
			case 'end':
				return $this->$compare_by == $obs->$compare_by ? 0 : ( $this->$compare_by > $obs->$compare_by ? 1 : -1 );
				break;
			case 'title':
				return strcasecmp($this->$compare_by, $obs->$compare_by);
		}
	}
}