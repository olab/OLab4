<?php
/**
 * 
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Base Model class that contains code used across all models
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */
abstract class ModelBase{
	
	public function __construct(){
		$this->VALID = true;
	}

	abstract public function create();

	abstract public function update($id = false);

	abstract public function delete($id = false);

	public function mapArray(array $arr, $mode = false){
		$klass = get_called_class();
		$o = $klass::fromArray($arr,$mode);
		
		if ($o) {
			$o_arr = $o->toArray();
			foreach($o_arr as $field=>$value){
				if(isset($arr[$field])){
					$this->$field = $arr[$field];
				}
			}
			$this->VALID = $o->isValid();
		} else return false;

		return $this;
	}

	public static function fromArray(array $arr, $mode = false) {			
		$klass = get_called_class();
		$o = new $klass();		
		//if the class implements the Validation interface, fetch the required fields and field rules and enforce and required fields
		if ($mode != "fetch" && in_array('Validation',class_implements($o))) {
			$req_fields = $o->fetchRequiredFields($mode);
			$field_rules = $o->fetchFieldRules($mode);
			foreach ($req_fields as $field) {
				if (!isset($arr[$field]) || !trim($arr[$field])) {
					if (!isset($o->$field) || !trim($o->$field)){
						$field_split = explode("_",$field);
						foreach ($field_split as $key=>$field_segment) {
							$field_split[$key] = ucwords($field_segment);
						}
						$field_text = implode(" ",$field_split);
						add_error("<strong>".$field_text."</strong> is a required field. Please ensure you've provided a value.");
						$o->VALID = false;
					}
				}
			}		
		} else {
			//if Validation not implemented, no fields are considered required and basic string cleaning will be done
			$field_rules = false;
		}
		//Cleans and sets each field
		
		//foreach value in the array (likely $_POST), clean using the rules for that field, or if no rules defined do string cleaning
		foreach($arr as $field=>$value){
			if($field_rules && isset($field_rules[$field])){
			/**
			* @todo Determine the best way to clean AND validate
			* ex: if the field is int, and 'abc' it the value, have it return an error message
			* will likely need to use filter_vars in place of clean_input
			*/					
				$cleaned = clean_input($arr[$field],$field_rules[$field]);	
				$o->$field = $cleaned;
			} else {
				//if no cleaning rule specified, default to basic string cleaning
				$cleaned = clean_input($arr[$field],array("trim","notags"));	
				$o->$field = $cleaned;
			}
		}
		return $o;
	}

	public function isValid(){
		return $this->VALID;
	}

	protected function toArray($mode = false) {
		$array = array();
		//if class implements the Validation interface, fetch which fields should be present in the array
		if (in_array('Validation',class_implements($this))) {
			$fields = $this->fetchArrayFields();
			foreach($fields as $field){
				$array[$field] = $this->$field;
			}
		} else {
			//if Validation interface is not implemented, all fields are considered displayable
			foreach($this as $field=>$value){
				$array[$field] = $value;
			}			
		}
		return $array;
	}

	/**
	* can be used to statically call create($array), update($id,$array), and delete($id)
	* arrays used to populate model based on validation rules
	*/
	public static function __callStatic($name, $arr){
		$static_functions = array('create','update','delete');
		if (!in_array($name,$static_functions)) return false;
		$klass = get_called_class();
		$o = new $klass();
		switch($name){
			case 'create':
				if (!is_array($arr[0])) return false;				
				$o->mapArray($arr[0]);			
				return call_user_func(array($o,'create'));
				break;
			case 'update':
				if (!is_array($arr[1])) return false;				
				$o->mapArray($arr[1]);

				return call_user_func(array($o,'update'),$arr[0]);
				break;
			case 'delete':
			return call_user_func(array($o,'delete'),$arr[0]);
			break;
		}
	}	
}

