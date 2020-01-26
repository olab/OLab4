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
			case 'update':
				$user = User::fetchRowByID($PROXY_ID);
				
				$org_id = $user->getOrganisationId();
				$group = $user->getGroup();
				$role = $user->getRole();
				$proxy_id = $user->getID();
				
				$caching = MetaDataValues::get($org_id, $group, $role,$proxy_id);
				
				//first go through the values array and verify that all of the indices are correct 
				$indices = filter_var(array_keys($_POST['value']),FILTER_VALIDATE_INT,array("flags"=>FILTER_REQUIRE_ARRAY));
				if (in_array(false, $indices, true)) {
					add_error("Invalid value id provided. Please try again.");
				}
				//then check each value array to ensure either delete=1 or the other values are valid
				if (!has_error()) {
					$updates = array();
					$deletes = array();
					foreach ($_POST['value'] as $key => $value) {
						//first ensure that the value exists, and then that it belongs to the user in question
						
						$meta_value = MetaDataValue::get($key);
						if (!$meta_value) {
							add_error("Value not found.");
							continue;
						} elseif($meta_value->getUser() != $user) {
							add_error("Specified value does not belong to the specified user.");
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
					echo editMetaDataTable_User($user);
				} else {
					//if there were any errors, return a 500 and display errors
					header("HTTP/1.0 500 Internal Error");
					echo display_status_messages(false);
				}
				
				break;
			case 'new_value':
				$cat_id = filter_input(INPUT_POST, "type", FILTER_SANITIZE_NUMBER_INT );
				$type = MetaDataType::get($cat_id);
				if ($type) {
					$user = User::fetchRowByID($PROXY_ID);
					$org_id = $user->getOrganisationId();
					$group = $user->getGroup();
					$role = $user->getRole();
					
					$types = MetaDataTypes::get($org_id, $group, $role, $PROXY_ID);
										
					$value_id = MetaDataValue::create($cat_id, $PROXY_ID);
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
