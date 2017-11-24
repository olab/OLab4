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

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	$category_id = ((isset($_GET["cat_id"])) ? (int) trim($_GET["cat_id"]) : 0);

	if ($category_id) {
		$department_id	= ((isset($_GET["dept_id"])) ? (int) trim($_GET["dept_id"]) : 0);
		$disabled		= (((isset($_GET["disabled"])) && (trim($_GET["disabled"]) != "")) ? true : false);

		$query		= "	SELECT `category_type`, `category_id`, `category_name`
						FROM `".CLERKSHIP_DATABASE."`.`categories`
						WHERE `category_parent` = ".$db->qstr($category_id);
		$results	= $db->GetAll($query);
		if($results) {
			echo "<select id=\"department_id\" name=\"department_id\"".(($disabled) ? " disabled=\"disabled\"" : "")." style=\"width: 256px\">\n";
			echo "<option value=\"0\"".((!$department_id) ? " selected=\"selected\"" : "").">-- Select Department --</option>\n";
			foreach($results as $result) {
				echo "<option value=\"".(int) $result["category_id"]."\"".(($department_id == $result["category_id"]) ? " selected=\"selected\"" : "").">".html_encode($result["category_name"])."</option>\n";
			}
			echo "</select>\n";
			exit;
		}
	}

	echo "<input type=\"hidden\" id=\"department_id\" name=\"department_id\" value=\"0\" />\n";
	echo "Please select an <strong>Elective Period</strong> from above first.\n";
}
?>
