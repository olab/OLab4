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
 * Country provides country name and abbreviation (if available). based on data from database
 * @author Jonathan Fingland
 *
 */
class Country extends Region {
    
    protected $iso2;
    protected $isonum;
    
    /**
     * @param string $name
     * @param integer $region_id
     * @param string $abbreviation
     * @param string $iso2
     * @param integer $isonum
     */
    function __construct($name, $region_id = null, $abbreviation = null, $iso2 = null, $isonum = null) {
        $this->name = $name;
        $this->abbreviation = $abbreviation;
        $this->region_id = $region_id;
        $this->iso2 = $iso2;
        $this->isonum = $isonum;
    }
    
	
	/**
	 * Returns the Country corresponding to the provided country ID
	 * @param int $country_id
	 * @return Country
	 */
	public static function get($country_id) {
		global $db;
		$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = ?";
		$result = $db->GetRow($query, array($country_id));
		if ($result) {
			return new self($result["country"], $result["countries_id"], $result["abbreviation"], $result["iso2"], $result["isonum"]);
		}		
	}
	
	/**
	 * This returns the 3 letter ISO code for the country. Functionally identical to getAbbreviation ().
	 * @return string
	 */
	public function getIso3() {
	    return $this->abbreviation;
	}
	
	/**
	 * This returns the 2 letter ISO code for the country.
	 * @return string
	 */
	public function getIso2() {
	    return $this->iso2;
	}
	
	/**
	 * This returns the ISO number for the country.
	 * @return integer
	 */
	public function getIsonum() {
	    return $this->isonum;
	}
}
 
