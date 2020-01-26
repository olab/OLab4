<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

define("DEFAULT_ORGANIZATION_CATEGORY_ID", 49);

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (isset($_POST["cid"]) && $_SESSION["isAuthorized"]) {
	$category_id = clean_input($_POST["cid"], array("int"));
	if ($category_id) {
		$query = "SELECT `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
					WHERE `category_id` = ".$db->qstr($category_id);
		$rotation_id = $db->GetOne($query);
		if ($rotation_id) {
			if ($rotation_id == 8 && isset($_POST["event_id"]) && $_POST["event_id"]) {
				$event_id = clean_input($_POST["event_id"], array("int"));
				$query = "SELECT `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`events`
							WHERE `event_id` = ".$db->qstr($event_id);
				$event_rotation_id = $db->GetOne($query);
				if ($event_rotation_id) {
					echo $event_rotation_id;
				} else {
					echo $rotation_id;
				}
			} else {
				echo $rotation_id;
			}
		}
	}
}

?>