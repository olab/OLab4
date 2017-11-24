<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
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
	$countries_id = ((isset($_GET["countries_id"])) ? clean_input($_GET["countries_id"], "int") : 0);
	$tmp_input = ((isset($_GET["prov_state"]) && $_GET["prov_state"] != "undefined") ? clean_input(rawurldecode($_GET["prov_state"]), array("notags", "trim")) : "");

	$province_id = 0;
	$province = "";


	$out = "html";

	if (isset($_POST["out"]) && ($tmp_input = clean_input($_POST["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	} elseif (isset($_GET["out"]) && ($tmp_input = clean_input($_GET["out"], array("trim", "notags")))) {
		$out = $tmp_input;
	}

	if (ctype_digit($tmp_input)) {
		$province_id = (int) $tmp_input;
	} else {
		$province = $tmp_input;
	}

	$output = "";
	if ($countries_id) {
		$results = Models_Province::fetchAllByCountryID($countries_id);
		if ($results) {
			$output .= "<select id=\"prov_state\" name=\"prov_state\" class=\"input-large\">\n";
			$output .=  "<option value=\"0\"".((!$province_id) ? " selected=\"selected\"" : "").">-- Select Province / State --</option>\n";
			foreach($results as $result_object) {
				$result = $result_object->toArray();
				$output .=  "<option value=\"".clean_input($result["province_id"], array("notags", "specialchars"))."\"".(($province_id == $result["province_id"]) ? " selected=\"selected\"" : ($province == clean_input($result["province"], array("notags", "specialchars")) ? " selected=\"selected\"" : "")).">".clean_input($result["province"], array("notags", "specialchars"))."</option>\n";
			}
			$output .=  "</select>\n";
		} else {
			$output .=  "<input type=\"text\" id=\"prov_state\" name=\"prov_state\" value=\"".clean_input($province, array("notags", "specialchars"))."\" maxlength=\"100\" />";
		}
		echo $output;
		exit;
	}

	$output .= "<input type=\"hidden\" id=\"prov_state\" name=\"prov_state\" value=\"0\" />\n";
	$output .= "<span style=\"line-height:30px;\">Please select a <strong>Country</strong> first.\n";
    echo $output;
}
?>
