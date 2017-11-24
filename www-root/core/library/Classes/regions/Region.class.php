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
 * Generic Region class. Uses provinces in database for datasource. Could be modified for more general case.
 * @author Jonathan Fingland
 *
 */
class Region {
	protected $name;
	protected $abbreviation;
	protected $region_id;
	protected $parent_id;
	
	/**
	 * @param $name
	 * @param $region_id
	 * @param $abbreviation
	 * @param $parent_id
	 */
	function __construct ($name, $region_id=null, $abbreviation=null, $parent_id=null) {
		$this->name = $name;
		$this->abbreviation = $abbreviation;
		$this->region_id = $region_id;
		$this->parent_id = $parent_id;
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getAbbreviation() {
		return $this->abbreviation;
	}
	
	/**
	 * @return Region
	 */
	public function getParent() {
		if ($this->parent_id) {
			//for now "parents" are always a country
			return Country::get($this->parent_id);
		}
	}
	
	/**
	 * @return int
	 */
	public function getParentID() {
		return $this->parent_id;
	}
	
	/**
	 * @return int
	 */
	public function getID() {
		 return $this->region_id;
	}
	
	/**
	 * @param $region_id
	 * @return Region
	 */
	public static function get($region_id) {
		global $db;
		$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = ?";
		$result = $db->getRow($query, array($province_id));
		if ($result) {
			return new self($result['province'], $result['province_id'], $result['abbreviation'], $result['country_id']);
		}		
	}
} 
