<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Displays accommodation details to the user based on a particular event_id.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}
$BREADCRUMB[]	= array("url" => "", "title" => "Clinical Presentations List");


if (!$grad_year = get_account_data("grad_year", $ENTRADA_USER->getID())) {
    if (!isset($_GET["grad_year"]) || !($grad_year = (int)$_GET["grad_year"])) {
        $grad_year = fetch_first_year() - 2;
    }
}
if ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] != "student") {
	$sidebar_html  = "<div>View required clinical presentations and tasks for:</div>\n";
	$sidebar_html  .= "<ul class=\"menu\">\n";
	if (isset($SYSTEM_GROUPS["student"]) && !empty($SYSTEM_GROUPS["student"])) {
		foreach ($SYSTEM_GROUPS["student"] as $class) {
			if ($class >= (date("Y") - 1) && $class <= (date("Y") + 2)) {
                $sidebar_html .= "	<li".($grad_year == $class ? " class=\"on\"" : "")."><a href=\"".ENTRADA_URL."/clerkship/objectives?grad_year=".$class."\"><strong>Class of ".$class."</strong></a></li>\n";
			}
		}
	}
	$sidebar_html .= "</ul>\n";
	new_sidebar_item("Logging Requirements", $sidebar_html, "page-clerkship", "open");
}
echo "<h1>Class of ".$grad_year."</h1>\n";
if (isset($_GET["rotation"]) && (clean_input($_GET["rotation"], "int"))) {
	$rotation = clean_input($_GET["rotation"], "int");
    $query = "	SELECT a.*, b.`rotation_id`
				FROM `global_lu_objectives` AS a
				JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS b
				ON a.`objective_id` = b.`objective_id`
				AND `rotation_id` = ".$db->qstr($rotation)."
				AND a.`objective_active` = '1'
				WHERE b.`grad_year_min` <= ".$db->qstr($grad_year)."
				AND (b.`grad_year_max` = 0 OR b.`grad_year_max` >= ".$db->qstr($grad_year).")";
} else {
	$rotation = false;
	$query = "	SELECT a.*, b.`rotation_id`
				FROM `global_lu_objectives` AS a
				JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS b
				ON a.`objective_id` = b.`objective_id`
				AND a.`objective_active` = '1'
				WHERE b.`grad_year_min` <= ".$db->qstr($grad_year)."
				AND (b.`grad_year_max` = 0 OR b.`grad_year_max` >= ".$db->qstr($grad_year).")
				AND b.`rotation_id` != 11
				ORDER BY b.`rotation_id` ASC, a.`objective_id` ASC";
}
$objectives = $db->GetAll($query);
if ($objectives) {
	echo "<h1>Clinical Presentations:</h1>";
	echo "<ul>\n";
		$last = 0;
	foreach ($objectives as $objective) {
		if (isset($objective["rotation_id"]) && $objective["rotation_id"] != $last) {
			$last = $objective["rotation_id"];
			echo "<h2>".$db->GetOne("SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_id` = ".$db->qstr($last))."</h2>";
		}
		$location_string = "";
		if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
			$query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
						ON a.`lmobjective_id` = b.`lmobjective_id`
						JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						ON b.`lltype_id` = c.`lltype_id`
						WHERE a.`objective_id` = ".$db->qstr($objective["objective_id"])."
						AND a.`rotation_id` = ".$db->qstr($objective["rotation_id"])." 
						AND a.`grad_year_min` <= ".$db->qstr($grad_year)."
						AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr($grad_year).")
						GROUP BY c.`lltype_id`";
			$locations = $db->GetAll($query);
			foreach ($locations as $location) {
				$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
			}
		}
		echo "<img src=\"".ENTRADA_URL."/images/checkbox-off.gif\" /> ".$objective["objective_name"].($location_string ? " (".$location_string.")" : "")."<br/>\n";
	}
	echo "</ul>\n";
	$query = "SELECT a.*, b.`rotation_id`
				FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
				JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
				ON a.`lprocedure_id` = b.`lprocedure_id`
				WHERE b.`grad_year_min` <= ".$db->qstr($grad_year)."
				".($rotation ? "AND b.`rotation_id` = ".$db->qstr($rotation) : "")."
				AND (b.`grad_year_max` = 0 OR b.`grad_year_max` >= ".$db->qstr($grad_year).")
				AND b.`rotation_id` != 11
				ORDER BY b.`rotation_id` ASC, a.`lprocedure_id` ASC";
	$procedures = $db->GetAll($query);
	if ($procedures) {
		echo "<br/><br/>\n";
		echo "<h1>Clinical Tasks:</h1>\n";
		echo "<ul>\n";
			$last = 0;
		foreach ($procedures as $procedure) {
			if (isset($procedure["rotation_id"]) && $procedure["rotation_id"] != $last) {
				$last = $procedure["rotation_id"];
				echo "<h2>".$db->GetOne("SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_id` = ".$db->qstr($last))."</h2>";
			}
			$location_string = "";
			if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
				$query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
							ON a.`lpprocedure_id` = b.`lpprocedure_id`
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
							ON b.`lltype_id` = c.`lltype_id`
							WHERE a.`lprocedure_id` = ".$db->qstr($procedure["lprocedure_id"])."
							AND a.`rotation_id` = ".$db->qstr($procedure["rotation_id"])." 
							AND a.`grad_year_min` <= ".$db->qstr($grad_year)."
							AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr($grad_year).")
							GROUP BY c.`lltype_id`";
				$locations = $db->GetAll($query);
				foreach ($locations as $location) {
					$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
				}
			}
			echo "<img src=\"".ENTRADA_URL."/images/checkbox-off.gif\" /> ".$procedure["procedure"].($location_string ? " (".$location_string.")" : "")."<br/>\n";
		}
		echo "</ul>\n";
	}
}