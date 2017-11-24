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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	if ($ENTRADA_ACL->amIAllowed("metadata", "create", false)) {

		ob_clear_open_buffers();
	
		require_once("Entrada/metadata/functions.inc.php");
		
		$request = filter_input(INPUT_POST, "request", FILTER_SANITIZE_STRING );
		switch($request) {
			case 'categories':
				$opts = filter_input_array(INPUT_POST, array(
					"associated_organisation_id" => FILTER_SANITIZE_NUMBER_INT,
					"associated_group" => FILTER_SANITIZE_STRING,
					"associated_role" => FILTER_SANITIZE_STRING,
					"associated_cat_id" => FILTER_SANITIZE_NUMBER_INT
				));
				
				if (!$opts["associated_organisation_id"] ) {
					add_error("Invalid or unspecified Organisation");
				} else {
					$organisation_id = $opts["associated_organisation_id"];
				}
				if (!$opts["associated_group"]){
					add_error("Invalid or unspecified Group");
				} else {
					$group = $opts["associated_group"];
				}
				if ($opts["associated_role"] == "all") {
					$role = null;
				} else {
					$role = $opts["associated_role"];
				}
				
				if (!$opts["associated_cat_id"]) {
					$cat_id = null;
				} else {
					$cat_id = $opts["associated_cat_id"];
				}
				
				if (!has_error()) {
					echo display_category_select($organisation_id, $group, $role, null, $cat_id);
				} else {
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
				}
				break;
			case 'get_table':
				$opts = filter_input_array(INPUT_POST, array(
					"associated_organisation_id" => FILTER_SANITIZE_NUMBER_INT,
					"associated_group" => FILTER_SANITIZE_STRING,
					"associated_role" => FILTER_SANITIZE_STRING,
					"associated_cat_id" => FILTER_SANITIZE_NUMBER_INT
				));
				
				if(!$opts["associated_cat_id"]) {
					add_error("Invalid or unspecified Category.");
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
					exit;
				} else {
					$category = MetaDataType::get($opts["associated_cat_id"]);
				}
				
				if (!$opts["associated_organisation_id"] ) {
					add_error("Invalid or unspecified Organisation");
				} else {
					$organisation_id = $opts["associated_organisation_id"];
					$organisation = Organisation::get($organisation_id);
				}
				if (!$opts["associated_group"]){
					add_error("Invalid or unspecified Group");
				} else {
					$group = $opts["associated_group"];
				}
				if ($opts["associated_role"] == "all") {
					$role = null;
				} else {
					$role = $opts["associated_role"];
				}
				if (!has_error()) {
					echo "<h2>".$category->getLabel()."</h2>";
					echo "<div class=\"content-small\">for ". $organisation->getTitle(). " &gt; " . ucwords($group). " &gt; " . ucwords($opts["associated_role"]) . "</div><br />";
					echo editMetaDataTable_Category($organisation_id, $group, $role, null, $category);
				} else {
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
				}
				
				break;
			case 'update':
				$opts = filter_input_array(INPUT_POST, array(
					"associated_organisation_id" => FILTER_SANITIZE_NUMBER_INT,
					"associated_group" => FILTER_SANITIZE_STRING,
					"associated_role" => FILTER_SANITIZE_STRING,
					"associated_cat_id" => FILTER_SANITIZE_NUMBER_INT
				));
				
				if(!$opts["associated_cat_id"]) {
					add_error("Invalid or unspecified Category.");
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
					exit;
				} else {
					$category = MetaDataType::get($opts["associated_cat_id"]);
				}
				
				if (!$opts["associated_organisation_id"] ) {
					add_error("Invalid or unspecified Organisation");
				} else {
					$organisation_id = $opts["associated_organisation_id"];
					$organisation = Organisation::get($organisation_id);
				}
				if (!$opts["associated_group"]){
					add_error("Invalid or unspecified Group");
				} else {
					$group = $opts["associated_group"];
				}
				if ($opts["associated_role"] == "all") {
					$role = null;
				} else {
					$role = $opts["associated_role"];
				}
				if (!has_error()) {
					$caching = MetaDataValues::get($organisation_id, $group, $role,null, $category);
					
					//first go through the values array and verify that all of the indices are correct 
					$indices = filter_var(array_keys($_POST['value']),FILTER_VALIDATE_INT,array("flags"=>FILTER_REQUIRE_ARRAY));
					if (in_array(false, $indices, true)) {
						add_error("Invalid value id provided. Please try again.");
					}
				}
				//then check each value array to ensure either delete=1 or the other values are valid
				if(!has_error()) {
					$updates = array();
					$deletes = array();
					foreach ($_POST['value'] as $key => $value) {
						//first ensure that the value exists
						
						$meta_value = MetaDataValue::get($key);
						if (!$meta_value) {
							add_error("Value not found.");
							continue;
						}
						
						$update_value = validate_value_update($value);
						if ($update_value) {
							$updates[$key] = $update_value;
						} else {
							$delete_value = validate_value_delete($value);
							if ($delete_value) {
								$deletes[$key] = $delete_value; 
							} else {
								//if there are any problems, note the error and carry on looking.
								add_error("No action can be taken as incomplete information was provided.");
							}
						}
					}
					//$values = filter_input(INPUT_POST, 'value', FILTER_CALLBACK, array('options'=>'validate_value', 'flags'=>FILTER_REQUIRE_SCALAR) );
				}
				
				//if all is good, delete the values marked for deletion, then update the other values
				if (!has_error()) {
					foreach ($deletes as $key => $value) {
						$meta_value = MetaDataValue::get($key);
						$meta_value->delete();
					} 
					foreach ($updates as $key=>$update) {
						$update['effective_date'] = fmt_date($update['effective_date']);
						$update['expiry_date'] = fmt_date($update['expiry_date']);
						$meta_value = MetaDataValue::get($key);
						$meta_value->update($update);
					}
				}			
				
				if (!has_error()) {
					echo "<h2>".$category->getLabel()."</h2>";
					echo "<div class=\"content-small\">for ". $organisation->getTitle(). " &gt; " . ucwords($group). " &gt; " . ucwords($opts["associated_role"]) . "</div><br />";
					echo editMetaDataTable_Category($organisation_id, $group, $role, null, $category);
				} else {
					//if there were any errors, return a 500 and display errors
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
				}
				
				break;
			case 'new_value':
				$cat_id = filter_input(INPUT_POST, "type", FILTER_SANITIZE_NUMBER_INT );
				$proxy_id = filter_input(INPUT_POST, "proxy_id", FILTER_SANITIZE_NUMBER_INT );
				$type = MetaDataType::get($cat_id);
				if ($type) {
					$user = User::fetchRowByID($proxy_id);
					$org_id = $user->getOrganisationId();
					$group = $user->getGroup();
					$role = $user->getRole();
					
					$types = MetaDataTypes::get($org_id, $group, $role, $proxy_id);
										
					$value_id = MetaDataValue::create($cat_id, $proxy_id);
					$value = MetaDataValue::get($value_id);
					$descendant_type_sets = getDescendentTypesArray($types, $type);
					header("Content-Type: application/xml");
					echo editMetaDataRow($value, $type, $descendant_type_sets);
				} else {
					header("HTTP/1.0 500 Internal Error");
					echo display_error("Invalid type. Please try again.");
				}
		}
	} 
	exit;
}
