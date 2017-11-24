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
 * 
 * Class to model data values about users. One requirement here was that data also had to support effective dates and expiration dates 
 * @author Jonathan Fingland
 *
 */
class MetaDataValue {
	private $meta_value_id,
			$meta_type_id,
			$proxy_id,
			$data_value,
			$notes,
			$effective_date,
			$expiry_date;
	
	function __construct() {
	}
	
	/**
	 * Returns MetaDataValue constructed from elements in the supplied array. 
	 * @param array $arr
	 * @return MetaDataValue
	 */
	public static function fromArray(array $arr) {
		$cache = SimpleCache::getCache();
		$value = $cache->get("MetaValue",$arr['meta_value_id']);
		if (!$value) {
			$value=new self();
		}		
		$value->meta_value_id = $arr['meta_value_id'];
		$value->meta_type_id = $arr['meta_type_id'];
		$value->proxy_id = $arr['proxy_id'];
		$value->data_value = $arr['data_value'];
		$value->notes = $arr['value_notes'];
		$value->effective_date = $arr['effective_date'];
		$value->expiry_date = $arr['expiry_date'];
		$cache->set($value, "MetaValue", $arr['meta_value_id']);
		return $value;
	}
	
	/**
	 * Returns the associated MetaDataType 
	 * @return MetaDataType
	 */
	public function getType() {
		return MetaDataType::get($this->meta_type_id);
	}
	
	/**
	 * Returns the associated User
	 * @return User
	 */
	public function getUser() {
		return User::fetchRowByID($this->proxy_id);
	}
	
	/**
	 * Returns the value data point
	 * @return string
	 */
	public function getValue() {
		return $this->data_value;
	}
	
	/**
	 * Returns the notes associated with the value
	 * @return string
	 */
	public function getNotes() {
		return $this->notes;
	}
	
	/**
	 * Returns the timestamp from which this value becomes effective
	 * @return int
	 */
	public function getEffectiveDate() {
		return $this->effective_date;
	}
	
	/**
	 * Returns the timestamp on which this value expires
	 * @return int
	 */
	public function getExpiryDate() {
		return $this->expiry_date;
	}
	
	/**
	 * Returns the internal ID
	 * @return int
	 */
	public function getID() {
		return $this->meta_value_id;
	}
	
	/**
	 * Returns the MetaDataValue corresponding to the provided ID
	 * @param int $meta_value_id
	 * @return MetaDataValue
	 */
	public static function get($meta_value_id) {
		$cache = SimpleCache::getCache();
		$value = $cache->get("MetaValue",$meta_value_id);
		if (!$value) {
			global $db;
			$query = "SELECT * FROM `meta_values` WHERE `meta_value_id` = ?";
			$result = $db->getRow($query, array($meta_value_id));
			if ($result) {
				$value = self::fromArray($result);  			
			}		
		} 
		return $value;
	}
	
	/**
	 * Returns the new value ID if succesful in creating a new MetaDataValue of the provided type (ID) for the provided user (ID)
	 * @param int $type_id
	 * @param int $proxy_id
	 */
	public static function create($type_id, $proxy_id) {
		global $db;
		$query = "INSERT INTO `meta_values` (`meta_type_id`, `proxy_id`) value (?,?)";
		$result = $db->Execute($query, array($type_id, $proxy_id));
		if ($result !== false) {
			return $db->Insert_ID('meta_values', 'meta_value_id');
		}
	}
	
	/**
	 * Updates this entry with the data supplied in the array. Can add an error if the DB fails the update.
	 * @param array $inputs
	 */
	public function update(array $inputs) {
		extract($inputs);
		$cache = SimpleCache::getCache();
		$cache->remove("MetaValue", $this->meta_value_id);
		
		global $db;
		$query = "UPDATE `meta_values` SET `meta_type_id`=?, `data_value`=?, `value_notes`=?, `effective_date`=?, `expiry_date`=? WHERE `meta_value_id`=?";
		if(!$db->Execute($query, array($type, $value, $notes, $effective_date, $expiry_date, $this->meta_value_id))) {
			add_error("Failed to update meta data");
			application_log("error", "Unable to update a meta_values record. Database said: ".$db->ErrorMsg());
		}
	} 
	
	/**
	 * Removes this Value from the dabatase
	 */
	public function delete() {
		$cache = SimpleCache::getCache();
		$cache->remove("MetaValue", $this->meta_value_id);
		
		global $db;
		$query="DELETE FROM `meta_values` where `meta_value_id`=?";
		if(!$db->Execute($query, array($this->meta_value_id))) {
			add_error("Failed to remove meta data from database.");
			application_log("error", "Unable to delete a meta_values record. Database said: ".$db->ErrorMsg());
		} 	
	} 
}