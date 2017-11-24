<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves as the main Entrada administrative request controller file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
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
	if (isset($_POST["countries_id"]) && ($tmp_input = clean_input($_POST["countries_id"], array("trim", "int")))) {
		$countries_id = $tmp_input;
	} elseif (isset($_GET["countries_id"]) && ($tmp_input = clean_input($_GET["countries_id"], array("trim", "int")))) {
		$countries_id = $tmp_input;
	} else {
		$countries_id = 0;
	}

	if (isset($_POST["city"]) && ($tmp_input = clean_input($_POST["city"], array("trim", "notags")))) {
		$region_name = $tmp_input;
	} elseif (isset($_GET["city"]) && ($tmp_input = clean_input($_GET["city"], array("trim", "notags")))) {
		$region_name = $tmp_input;
	} else {
		$region_name = "";
	}

	if (($countries_id) && ($region_name)) {
		echo "<ul>\n";
		$query = "	SELECT a.*, b.`province`, c.`country`
					FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
					LEFT JOIN `global_lu_provinces` AS b
					ON b.`province_id` = a.`province_id`
					LEFT JOIN `global_lu_countries` AS c
					ON c.`countries_id` = a.`countries_id`
					WHERE a.`countries_id` = ".$db->qstr($countries_id)."
					AND a.`region_name` LIKE ".$db->qstr("%".$region_name."%")."
					AND a.`region_active` = '1'
					GROUP BY a.`region_name`, a.`prov_state`
					ORDER BY c.`country`, b.`province`, a.`prov_state`, a.`region_name` ASC, a.`manage_apartments` DESC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$comment = array();
				if (trim($result["province"])) {
					$comment[] = $result["province"];
				}
				$comment[] = $result["country"];

				echo "\t<li id=\"".(int) $result["region_id"]."\">".str_ireplace($region_name, "<strong>".$region_name."</strong>", html_encode($result["region_name"]))."<div class=\"content-small informal\">".implode(", ", $comment)."</div></li>\n";
			}
		}
		echo "</ul>";
	}
} else {
	application_log("error", "City names API accessed without valid session_id.");
}
?>