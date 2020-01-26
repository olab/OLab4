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
 * Collection of MetaDataValue model objects 
 * @author Jonathan Fingland
 *
 */
class MetaDataValues extends Collection {

	/**
	 * Retrieves Multiple values based on provided criteria.  
	 * @param $organisation Organisation ID
	 * @param $group 
	 * @param $role
	 * @param $proxy_id User's ID 
	 * @param MetaDataType $type
	 * @param $options advanced selection criteroa
	 * @return MetaDataValues
	 */
	public static function get($organisation=null, $group=null, $role=null, $proxy_id=null, MetaDataType $type=null, $include_sub_types=true, $options=array()) {
		global $db;
		$conditions = array();
		if (array_key_exists('order by', $options)) {
			$order = array();
			if (is_array($options['order by'])) {
				foreach ($options['order by'] as $orders) {
					$order[] = "`".$orders[0]."` ". (isset($orders[1]) ? $orders[1] : "asc"); 
				}	
			}
			$order_by = " ORDER BY ".implode(",",$order);
		} else {
			$order_by = " ORDER BY lastname, firstname";
		}
		if (isset($options['limit'])) {
			$limit = $options['limit'];
		} else {
			$limit = -1;
		}
		if (isset($options['offset'])) {
			$offset = $options['offset'];
		} else {
			$offset = -1;
		}
		if (isset($options['where'])) {
			$conditions[] = $options['where'];
		}
		
		
		$query = "SELECT a.*, b.`group`, b.`role`, c.* from `".AUTH_DATABASE."`.`user_data` a 
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` b on a.`id`=b.`user_id` and b.`app_id`=?
					LEFT JOIN `meta_values` c on c.`proxy_id`=a.`id`";
		$conditions[] = generateAccessConditions($organisation, $group, $role, $proxy_id, 'b');
		
		if ($type) {
			$type_id = $type->getID();
			if ($include_sub_types) {
				$types = MetaDataTypes::get($organisation, $group, $role, $proxy_id);
				$desc_type_ids = getUniqueDescendantTypeIDs($types, $type);
				if ($desc_type_ids) {
					//includes values from the specified type as well as sub types
					$desc_type_ids[] = $type_id;
					$conditions[] = "`meta_type_id` IN (".implode(",", $desc_type_ids) .")";
				} else {
					$conditions[] = "`meta_type_id`=".$db->qstr($type_id);
				}
			} else {
				$conditions[] = "`meta_type_id`=".$db->qstr($type_id);
			}
		}
		if ($conditions) {
			$query .= " WHERE " . implode(" AND ",$conditions);
		}
		$query.= $order_by;
		$results = $db->SelectLimit($query, $limit, $offset, array(AUTH_APP_ID));
		$values = array();
		if ($results) {
			foreach ($results as $result) {
				//print_r($result);
				//user caching
				//$tmp_user = User::fromArray($result);
				//print_r($tmp_user);
				$values[] = MetaDataValue::fromArray($result);
			}
		}
		return new self($values);
	}
}