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
 * MetaDataTypes are heirarchical structures used to define the types of meta data values. Top level types are also called categories and have no parent  
 * @author Jonathan Fingland
 *
 */ 
class MetaDataType {
	
	private	$meta_type_id,
			$label,
			$description,
			$parent_type_id,
			$restricted;
	
	/**
	 * Returns the parent type, if any. Returns null if none is found.
	 * <code>
	 * $type = $value->getType();
	 * $parent = $type->getParent();
	 * </code>
	 * @return MetaDataType
	 */
	public function getParent() {
		if ($this->parent_type_id) {
			return MetaDataType::get($this->parent_type_id);
		}
	}
	
	/**
	 * Returns the interal ID
	 * @return int
	 */
	public function getID() {
		return $this->meta_type_id;
	}
	
	/**
	 * Returns the label to be used for the type of data
	 * <code>
	 * <tr>
	 *  <th><?php echo htmlspecialchars($type->getLabel()); ?></th>
	 * </tr>
	 * </code>
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}
	
	/**
	 * Returns the description (if any) for this type
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Returns the parent ID (if any) for this type
	 * @return int
	 */
	public function getParentID() {
		return $this->parent_type_id;
	}

	/**
	 * Returns the restricted from public (if any) for this type
	 * @return int
	 */
	public function getRestricted() {
		return $this->restricted;
	}

	function __construct() {
	}
	
	/**
	 * Returns a MetaDataType based on the array inputs. If $type is supplied, then it will modify that. Previously cached copies will also be modified if they share the same ID
	 * <code>
	 * $arr = array(
	 *              "meta_type_id"   => 1,
	 *              "label"          => "Test",
	 *              "description"    => "Testing Type",
	 *              "parent_type_id" => null,
	 *              "restricted" => null
	 *              );
	 * $type = MetaDataType::fromArray($arr);
	 * </code>
	 *  
	 * @param array $arr 
	 * @param MetaDataType $type If using a pre-existing object, this parameter will be the mutated object. If null, or not supplied, a new MetaDataType will be returned
	 * @return MetaDataType
	 */
	public static function fromArray(array $arr, MetaDataType $type = null) {
		$cache = SimpleCache::getCache();
		if (is_null($type)) {
			$user = $cache->get("MetaDataType",$arr['meta_type_id']); //re-use a cached copy if we can. helps prevent inconsistent objects 
			if (!$type) {
				$type = new self();
			}
		}
		
		$type->meta_type_id = $arr['meta_type_id'];
		$type->label = $arr['label'];
		$type->description = $arr['description'];
		$type->parent_type_id = $arr['parent_type_id'];
		$type->restricted = $arr['restricted'];
		
		$cache->set($type, "MetaDataType",$arr['meta_type_id']);
		
		return $type;
	}
	
	/**
	 * Returns a MetaDataType corresponding to the provided ID
	 * <code>
	 * $type_id = 1234;
	 * $type = MetaDataType::get($type_id);
	 * </code>
	 * @param int $meta_type_id
	 * @return MetaDataType
	 */
	public static function get($meta_type_id) {
		$cache = SimpleCache::getCache();
		$type = $cache->get("MetaDataType",$meta_type_id);
		if (!$type) {
			global $db;
			$query = "SELECT * FROM `meta_types` WHERE `meta_type_id` = ?";
			$result = $db->getRow($query, array($meta_type_id));
			if ($result) {
				$type = self::fromArray($result);
			}		
		}
		return $type;
	}

	function __toString() {
		return $this->getLabel();
	}
	
	/**
	 * Insert a MetaDataType and it's MetaTypeRelations
	 * @param array of new values for meta_type_id
	 * @return boolean
	 */
	public function insert (array $arr) {
		global $db, $ORGANISATION_ID;
		if ($db->AutoExecute("meta_types", $arr, "INSERT")) {
			if ($META_ID = $db->Insert_Id()) {
				if (isset($arr["groups"]) && is_array($arr["groups"])) {
					foreach ($arr["groups"] as $group) {
						$params = array("meta_type_id" => $META_ID, "entity_type" => "organisation:group", "entity_value" => $ORGANISATION_ID . ":" . $group);

						if (!$db->AutoExecute("meta_type_relations", $params, "INSERT")) {
							application_log("error", "Error inserting  ".get_called_class()." id[$META_ID] relation. DB Said: " . $db->ErrorMsg());
							return false;
						}
					}
				}
				application_log("success", "New Meta Data Type [$META_ID] added to the system.");
				return true;
			}
		}
		application_log("error", "Error inserting  ".get_called_class()." id[$META_ID]. DB Said: " . $db->ErrorMsg());
		return false;
	}

	/**
	 * Update a MetaDataType and it's MetaTypeRelations
	 * @param array of new values for meta_type_id
	 * @return boolean
	 */
	public function update (array $arr) {
		global $db, $ORGANISATION_ID;
		if ($db->AutoExecute("meta_types", $arr, "UPDATE", "`meta_type_id` = ".$db->qstr($arr["meta_type_id"]))) {
			if(isset($arr["groups"]) && is_array($arr["groups"])) {
				$query = "	SELECT * FROM `meta_types` WHERE `parent_type_id` = " . $db->qstr($arr["meta_type_id"]);
				$children = $db->GetAll($query);
				$db->Execute("DELETE FROM `meta_type_relations` WHERE `meta_type_id` = " . $db->qstr($arr["meta_type_id"]));
				foreach ($children as $child) {
					$db->Execute("DELETE FROM `meta_type_relations` WHERE `meta_type_id` = " . $db->qstr($child["meta_type_id"]));
				}
				foreach ($arr["groups"] as $group) {

					$params = array("meta_type_id" => $arr["meta_type_id"], "entity_type" => "organisation:group", "entity_value" => $ORGANISATION_ID . ":" . $group);

					if ($db->AutoExecute("meta_type_relations", $params, "INSERT")) {
						foreach ($children as $child) {
							$params = array("meta_type_id" => $child["meta_type_id"], "entity_type" => "organisation:group", "entity_value" => $ORGANISATION_ID . ":" . $group);
							$db->AutoExecute("meta_type_relations", $params, "INSERT");
						}
					}
				}
			}
			application_log("success", "New Meta Data Type [$arr[meta_type_id]] added to the system.");

			return true;
		} else {
			application_log("error", "Error updating  ".get_called_class()." id[$arr[meta_type_id]]. DB Said: " . $db->ErrorMsg());
			return false;
		}
	}
	/**
	 * Delete a MetaDataType and it's MetaTypeRelation
	 * @param array of new values for meta_type_id
	 * @return boolean
	 */
	public function delete ($meta_type_id) {
		global $db;

		$query = "DELETE FROM `meta_types` WHERE `meta_type_id` = ".$db->qstr($meta_type_id);

		if($db->Execute($query)){
			$query = "DELETE FROM `meta_type_relations` WHERE `meta_type_id` = ".$db->qstr($meta_type_id);
			if(!$db->Execute($query)){
				application_log("error", "Error deleting relationship ".get_called_class()." id[$meta_type_id]. DB Said: " . $db->ErrorMsg());
				return false;
			}
			return true;
		}
		application_log("error", "Error deleting  ".get_called_class()." id[$meta_type_id]. DB Said: " . $db->ErrorMsg());
		return false;
	}
}