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

if (isset($_POST["cid"]) && $_SESSION["isAuthorized"]) {
	$category_id = clean_input($_POST["cid"], array("int"));
	if ($category_id) {
		$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
					WHERE `category_id` = ".$db->qstr($category_id);
		$category = $db->GetRow($query);
		if ($category) {
			if ($category["category_parent"] != 0) {
				$parent_id 								= $category["category_parent"];
				$category_selected_reverse[1]["id"]		= 0;
				$category_selected_reverse[1]["parent"]	= $category_id;
				$category_selected_reverse[2]["id"]		= $category_id;
				$category_selected_reverse[2]["parent"]	= $category["category_parent"];
				$count = 2;
				while ($parent_id != 0) {
					$count++;
					$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
								WHERE `category_id` = ".$db->qstr($parent_id);
					$parent_category = $db->GetRow($query);
					$category_selected_reverse[$count]["parent"]	= $parent_category["category_parent"];
					$category_selected_reverse[$count]["id"]		= $parent_category["category_id"];
					$parent_id 										= $parent_category["category_parent"];
				}
				$index = $count;
				foreach ($category_selected_reverse as $category_item) {
					$category_selected[$index]["parent"]	= $category_item["parent"];
					$category_selected[$index]["id"]		= $category_item["id"];
					$index--;
				}
			} else {
					$category_selected[1]["id"]		= $category_id;
					$category_selected[2]["id"]		= 0;
					$category_selected[1]["parent"]	= 0;
					$category_selected[2]["parent"]	= $category_id;
					$count = 2;
			}
			echo "<input type=\"hidden\" name=\"category_id\" value=\"".$category_id."\" />\n";
		}
	} else {
		$category_selected[1]["id"]		= 0;
		$category_selected[1]["parent"]	= 0;
		$count = 1;
	}
	$margin = 0;
	for ($level = 1; $level <= $count; $level++) {
		if (isset($category_selected[$level]["parent"])) {
			$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
						WHERE `category_parent` = ".$db->qstr($category_selected[$level]["parent"])."
						AND `category_status` != 'trash'";
			$results = $db->GetAll($query);
			if ($results) {
				echo "<div style=\"padding: 0px; margin-left: ".$margin."px;\">\n";
				echo "\t<img height=\"20\" width=\"15\" src=\"".ENTRADA_URL."/images/tree/minus".($margin ? "2" : "5").".gif\" alt=\"Level\" title=\"Level\" style=\"position: relative; top: 6px;\"/>";
				echo "\t<select id=\"category-".$category_selected[$level]["parent"]."\" name=\"category-".$category_selected[$level]["parent"]."\" onChange=\"selectCategory(this.options[this.selectedIndex].value)\">\n";
					echo "\t\t<option value=\"0\"".($category_selected[$level]["id"] == 0 ? " selected=\"selected\"" : "").">-- Select Category --</option>\n";
				foreach ($results as $result) {
					echo "\t\t<option value=\"".$result["category_id"]."\"".($category_selected[$level]["id"] == $result["category_id"] ? " selected=\"selected\"" : "").">".clean_input($result["category_name"], array("notags"))."</option>\n";
				}
				echo "\t</select>\n";
				echo "</div>\n";
			}
			$margin += 20;
		}
	}
}