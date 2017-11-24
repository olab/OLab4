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
			$parent_type_id;
	
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

	function __construct() {
	}
	
	/**
	 * Returns a MetaDataType based on the array inputs. If $type is supplied, then it will modify that. Previously cached copies will also be modified if they share the same ID
	 * <code>
	 * $arr = array(
	 *              "meta_type_id"   => 1,
	 *              "label"          => "Test",
	 *              "description"    => "Testing Type",
	 *              "parent_type_id" => null
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
	
}