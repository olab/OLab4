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

 
class MetaDataRelation {
	
	const SEPARATOR = ":";
	public static $TYPES = array("organisation","group","role","user");
	
	private $meta_data_relation_id,
			$meta_type_id,
			$entity_type,
			$entity_value;
			
	private $organisation,
			$group,
			$role,
			$user;
			
	/**
	 * Returns the Organisation ID for this relationship
	 * @return int
	 */
	public function getOrganisationRestriction() {
		return $this->organisation;
	}
	
	/**
	 * @return string 
	 */
	public function getGroupRestriction() {
		return $this->group;
	}
	
	/**
	 * @return string
	 */
	public function getRoleRestriction() {
		return $this->role;
	}
	
	/**
	 * Returns the Proxy ID for this relationship
	 * @return int
	 */
	public function getUserRestriction() {
		return $this->user;
	}
	
	/**
	 * Returns true if this relationship is applicable to the supplied user
	 * @param User $user
	 * @return boolean
	 */
	public function isRelated(User $user) {
		if ((($this->organisation) && ($user->getOrganisationId() != $this->organisation))
		|| (($this->group) && ($user->getGroup() != $this->group))
		|| (($this->role) && ($user->getRole() != $this->role))
		|| (($this->user) && ($user->getID() != $this->user))) {
			return false;
		}
		return true;
	}
	
	/**
	 * Parses out the rtelationship restriction componenets from a string of the fromat "organistion:group:role:user". Internal use only. 
	 * @throws Exception 
	 */
	private function parseParts() {
		$type_parts = explode(self::SEPARATOR, $this->entity_type);
		$value_parts = explode(self::SEPARATOR, $this->entity_value);
		if (count($type_parts) !== count($value_parts)) {
			throw new Exception("Invalid meta data relation");
		}
		$parts = array_combine($type_parts, $value_parts);
		foreach (self::$TYPES as $part) {
			$this->{$part} = $parts[$part];
		}
	}
	
	/**
	 * Returns a MetaDataRelation object belonging to the specified id, if one exists.
	 * @param int $meta_data_relation_id
	 * @return MetaDataRelation
	 */
	public function get($meta_data_relation_id) {
		$cache = SimpleCache::getCache();
		$relation = $cache->get("MetaDataRelation",$meta_data_relation_id);
		if (!$relation) {
			global $db;
			$query = "SELECT * FROM `meta_data_relations` WHERE `meta_data_relation_id` = ?";
			$result = $db->getRow($query, array($meta_data_relation_id));
			if ($result) {
				$relation = self::fromArray($result);  			
			}		
		} 
		return $relation;
	}
	
	public static function fromArray(array $arr, self $MDR = null) {
		if (is_null($MDR)) {
			$MDR = new self();
		}
		$MDR->meta_data_relation_id = $arr['meta_data_relation_id'];
		$MDR->meta_type_id = $arr['meta_type_id'];
		$MDR->entity_type = $arr['entity_type'];
		$MDR->entity_value = $arr['entity_value'];
		$MDR->parseParts();
		return $MDR;
	}
	
	public function getMetaDataType() {
		return MetaDataType::get($this->meta_type_id);
	}
}