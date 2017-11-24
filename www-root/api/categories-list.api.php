<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a number of select boxes based on hierarchy.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {

	if ($_SESSION["isAuthorized"]) {
		if (isset($_REQUEST["id"]) && ((int)$_REQUEST["id"])) {
			$category_id = clean_input($_REQUEST["id"], array("int"));
		} else {
			$category_id = 0;
		}
		if (isset($_REQUEST["excluded"]) && (count(explode(",", $_REQUEST["excluded"])))) {
			$excluded_array = explode(",", $_REQUEST["excluded"]);
			$excluded_valid = true;
			$excluded_clean = "";
			foreach ($excluded_array as $excluded_category_id) {
				if (!(int)$excluded_category_id) {
					$excluded_valid = false;
					break;
				} else {
					$excluded_clean .= ($excluded_clean ? "," : "").$db->qstr(((int)$excluded_category_id));
				}
			}
			if ($excluded_valid && $excluded_clean) {
				$excluded = $excluded_clean;
			} else {
				$excluded = 0;
			}
		} else {
			$excluded = 0;
		}
		if (isset($_REQUEST["pid"]) && ((int)$_REQUEST["pid"])) {
			$parent_id = clean_input($_REQUEST["pid"], array("int"));
		} else {
			$parent_id = 0;
		}
		
		if (isset($_REQUEST["organisation_id"]) && ((int)$_REQUEST["organisation_id"])) {
			$organisation_id = clean_input($_REQUEST["organisation_id"], array("int"));
		} else {
			$organisation_id = $ENTRADA_USER->getActiveOrganisation();
		}
		
		if (isset($_REQUEST["type"]) && $_REQUEST["type"] == "order") {
			if ($parent_id) {
				$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
							WHERE `category_parent` = ".$db->qstr($parent_id)."
							AND `category_status` != 'trash'
							AND (`organisation_id` = ".$db->qstr($organisation_id)." OR `organisation_id` IS NULL)
							ORDER BY `category_order` ASC";
			} else {
				$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
							WHERE `category_parent` = '0' 
							AND `category_status` != 'trash' 
							AND (`organisation_id` = ".$db->qstr($organisation_id)." OR `organisation_id` IS NULL)
							ORDER BY `category_order` ASC";
			}
			$categories = $db->GetAll($query);
			if ($categories) {
				$count = 0;
				echo "<select id=\"category_order\" name=\"category_order\">\n";
				$current_selected = false;
				$selected = false;
				$count = 0;
                if (isset($category_id) && $category_id) {
                    echo "<option id=\"leave_alone_-1\" value=\"-1\" selected=\"selected\">-- Do Not Change --</option>\n";
                }
				foreach ($categories as $category) {
					if ($category["category_id"] != $category_id) {
						$count++;
						echo "<option id=\"before_obj_".$category["category_id"]."\" value=\"".$count."\">Before ".$category["category_name"]."</option>\n";
					}
				}
				echo "<option id=\"after_obj_".$category["category_id"]."\" value=\"".($count+1)."\" ".(!isset($category_id) || !$category_id ? "selected=\"selected\"" : "").">After ".$category["category_name"]."</option>\n";
				echo "</select>\n";
			} else {
				echo "<select id=\"category_order\" name=\"category_order\">\n";
				echo "<option id=\"first\" value=\"1\" >-- Only Category --</option>\n";
				echo "</select>\n";
			}
		} else {
			if ($parent_id !== 0) {
				$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
							WHERE `category_id` = ".$db->qstr($parent_id)."
							AND (`organisation_id` = ".$db->qstr($organisation_id)." OR `organisation_id` IS NULL)
							AND `category_status` != 'trash'";
				$category = $db->GetRow($query);
				if ($category) {
					if ($category["category_parent"]) {
						$last_parent_id								= $category["category_parent"];
						$category_selected_reverse[1]["id"]		= 0;
						$category_selected_reverse[1]["parent"]	= $parent_id;
						$category_selected_reverse[2]["id"]		= $parent_id;
						$category_selected_reverse[2]["parent"]	= $category["category_parent"];
						$count = 2;
						while ($last_parent_id) {
							$count++;
							$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
										WHERE `category_id` = ".$db->qstr($last_parent_id)."
										AND (`organisation_id` = ".$db->qstr($organisation_id)." OR `organisation_id` IS NULL)
										AND `category_status` != 'trash'";
							$parent_category = $db->GetRow($query);
							$category_selected_reverse[$count]["parent"]	= $parent_category["category_parent"];
							$category_selected_reverse[$count]["id"]		= $parent_category["category_id"];
							$last_parent_id									= $parent_category["category_parent"];
						}
						$index = $count;
						foreach ($category_selected_reverse as $category_item) {
							$category_selected[$index]["parent"]	= $category_item["parent"];
							$category_selected[$index]["id"]		= $category_item["id"];
							$index--;
						}
					} else {
							$category_selected[1]["id"]		= $parent_id;
							$category_selected[2]["id"]		= 0;
							$category_selected[1]["parent"]	= 0;
							$category_selected[2]["parent"]	= $parent_id;
							$count = 2;
					}
					echo "<input type=\"hidden\" name=\"category_id\" value=\"".$parent_id."\" />\n";
				}
			} else {
				$category_selected[1]["id"]		= 0;
				$category_selected[1]["parent"]	= 0;
				$count = 1;
			}
			if ($category_id) {
				echo "<input type=\"hidden\" name=\"delete[".$category_id."][category_parent]\" id=\"children_".$category_id."_move\" value=\"".$parent_id."\" />\n";
			}
			$last_title = false;
			$margin = 0;
			for ($level = 1; $level <= $count; $level++) {
				if ($category_selected[$level]["parent"] !== false) {
                    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`categories`
                                WHERE `category_parent` = ".$db->qstr($category_selected[$level]["parent"])."
                                AND (`organisation_id` = ".$db->qstr($organisation_id)." OR `organisation_id` IS NULL)
                                AND `category_status` != 'trash'".
                                ($excluded ? " AND `category_id` NOT IN (".$excluded.")" : ($category_id ? " AND `category_id` != ".$db->qstr($category_id) : ""));
					$results = $db->GetAll($query);
					if ($results) {
						echo "<div style=\"padding: 0px; margin-left: ".$margin."px;\">\n";
						echo "\t<img height=\"20\" width=\"15\" src=\"".ENTRADA_URL."/images/tree/minus".($margin ? "2" : "5").".gif\" alt=\"Level\" title=\"Level\" style=\"position: relative; top: 6px;\"/>";
						echo "\t<select id=\"category-".$category_selected[$level]["parent"]."\" name=\"category-".$category_selected[$level]["parent"]."\" onChange=\"selectCategory(this.options[this.selectedIndex].value".($category_id ? ", ".$category_id : "").($excluded ? ", ".$excluded : "")."); selectOrder(".($category_id ? $category_id.", " : "")."this.options[this.selectedIndex].value);\">\n";
							if ($last_title) {
								echo "\t\t<option value=\"".$category_selected[$level]["parent"]."\">-- Under ".clean_input($last_title, array("notags"))." --</option>\n";
							} else {
								echo "\t\t<option value=\"".($level > 1 ? $category_selected[($level-1)]["id"] : 0)."\"".($category_selected[$level]["id"] == 0 ? " selected=\"selected\"" : "").">-- No Parent --</option>\n";
							}
						foreach ($results as $result) {
							echo "\t\t<option value=\"".$result["category_id"]."\"".($category_selected[$level]["id"] == $result["category_id"] ? " selected=\"selected\"" : "").">".clean_input($result["category_name"], array("notags"))."</option>\n";
							if (($count - 1) == $level && $category_selected[$level]["id"] == $result["category_id"]) {
								$last_title = $result["category_name"];
							}
						}
						echo "\t</select>\n";
						echo "</div>\n";
					} elseif ($category_selected[$level]["parent"] === 0) {
                        echo display_notice("No other categories were found.");
                    }
					$margin += 20;
				}
			}
		}
	}
}
