<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	
	/**
	 * Clears all open buffers so we can return a simple REST response.
	 */
	ob_clear_open_buffers();
	
	if (isset($_POST["action"]) && ($tmp_input = clean_input($_POST["action"], array("striptags", "trim")))) {
		$action = $tmp_input;
	}
	
	switch ($action) {
		case "update-order" :
			if (isset($_POST["observership_id"])) {
				$PROCESSED["observership_id"] = clean_input($_POST["observership_id"], array("int"));
			}
			if (isset($_POST["order"])) {
				$PROCESSED["order"] = clean_input($_POST["order"], array("int"));
			}
			
			if (is_int($PROCESSED["observership_id"]) && is_int($PROCESSED["order"])) {
				
				$query = "UPDATE `student_observerships` SET `order` = " . $db->qstr($PROCESSED["order"]) . " WHERE `id` = " . $db->qstr($PROCESSED["observership_id"]);
				if ($db->Execute($query)) {
					echo json_encode(array("status" => "success", "data" => array("observership_id" => $PROCESSED["observership_id"], "order" => $PROCESSED["order"])));
				} else {
					echo json_encode(array("status" => "error2"));
				}
				
			} else {
				echo json_encode(array("status" => "error1"));
			}
			
		break;
		default:
		break;
	}
	
	
	exit;
	
}