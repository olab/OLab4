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
 * Allows students to delete an elective in the system if it has not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('logbook', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	
	ob_clear_open_buffers();
	
	if (isset($_GET["id"]) && ((int)$_GET["id"])) {
		$PROXY_ID = $_GET["id"];
		if ($ENTRADA_USER->getID() != $PROXY_ID) {
			$student = false;
		} else {
			$student = true;
		}
	} else {
		$PROXY_ID = $ENTRADA_USER->getID();
		$student = true;
	}
	/**
	 * Update requested column to sort by.
	 * Valid: date, teacher, title, phase
	 */
	if(isset($_GET["sb"])) {
		if(in_array(trim($_GET["sb"]), array("rotation" , "location", "site", "patient", "date"))) {
			if (trim($_GET["sb"]) == "rotation") {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "f.`rotation_title`";
			} elseif (trim($_GET["sb"]) == "location") {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "b.`location`";
			} elseif (trim($_GET["sb"]) == "site") {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "c.`site_name`";
			} elseif (trim($_GET["sb"]) == "patient") {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "a.`patient_info`";
			} elseif (trim($_GET["sb"]) == "date") {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "a.`encounter_date`";
			}
		}
	} else {
		if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "f.`rotation_title`";
		}
	}

	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if(isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "DESC" : "ASC");
	} else {
		if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "ASC";
		}
	}
	
	
	$query = "	SELECT `rotation_title`, `rotation_id`
				FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`";
	$rotations = $db->GetAll($query);
	$rotation_names = Array();
	foreach ($rotations as $rotation) {
		$rotation_names[$rotation["rotation_id"]] = $rotation["rotation_title"];
	}
	
	if (isset($_GET["rotation"]) && (clean_input($_GET["rotation"], "int"))) {
		$rotation_id = clean_input($_GET["rotation"], "int");
		if (!array_key_exists($rotation_id, $rotation_names) || !$rotation_names[$rotation_id]) {
			$rotation_id = 0;
		} elseif ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "f.`rotation_title`") {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "a.`encounter_date`";
		}
	}
	
	$clerk_name = $db->GetOne("	SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `fullname` 
								FROM `".AUTH_DATABASE."`.`user_data`
								WHERE `id` = ".$db->qstr($PROXY_ID));

	$query = "	SELECT a.`encounter_date`, a.`patient_info`, a.`gender`, g.`age`, a.`lentry_id`, c.`site_name`, b.`location`, f.`rotation_title`, a.`reflection`, a.`comments`, d.`rotation_id`
				FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a 
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS b
				ON a.`llocation_id` = b.`llocation_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_sites` AS c
				ON a.`lsite_id` = c.`lsite_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS d
				ON a.`rotation_id` = d.`event_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS f
				ON d.`rotation_id` = f.`rotation_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange` AS g
				ON a.`agerange_id` = g.`agerange_id`
				WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)."
				AND a.`entry_active` = '1'
				ORDER BY a.`encounter_date` ASC";
	
	$results = $db->GetAll($query);
	if ($results) {
		$rotation_ids = Array();
		foreach ($results as $result) {
			if (array_search($result["rotation_id"], $rotation_ids) === false) {
				$rotation_ids[] = $result["rotation_id"];
			}
		}
		
		if (!$student) {
			$query = "SELECT a.`course_id`, b.`organisation_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
                        JOIN `courses` AS b
                        ON a.`course_id` = b.`course_id`";
			$courses = $db->GetAll($query);
			$allow_view = false;
			foreach ($courses as $course) {
				if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), 'update')) {
					$allow_view = true;
				}
			}
		}
		
		if ($student || $allow_view) {
			header("Content-Type:  application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"".date("Y-m-d") . "-" . $clerk_name . "-patient-encounters.csv\"");
			echo "\"Encounter Date\",\"Patient ID\",\"Patient Gender\",\"Patient Age Range\",\"Logbook Entry ID\",\"Site Name\",\"Location\",\"Rotation\",\"Reflection on learning experience\",\"Additional comments\",\"Clinical Presentations Encountered\",\"Clinical Tasks Performed\"\n";
			foreach ($results as $result) {
				$query = "	SELECT b.`objective_name`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
							JOIN `global_lu_objectives` AS b
							ON a.`objective_id` = b.`objective_id`
							JOIN `objective_organisation` AS c
							ON c.`organisation_id` = b.`organisation_id`
							WHERE a.`lentry_id` = ".$db->qstr($result["lentry_id"])."
							AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							AND b.`objective_active` = '1'";
				
				$objectives = $db->GetAll($query);
				$result["objectives"] = "";
				if ($objectives) {
                    foreach ($objectives as $objective) {
                        if ($result["objectives"]) {
                            $result["objectives"] .= "; ";
                        }
                        $result["objectives"] .= $objective["objective_name"];
                    }
                }
				$query = "	SELECT b.`procedure`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
							JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS b
							ON a.`lprocedure_id` = b.`lprocedure_id`
							WHERE a.`lentry_id` = ".$db->qstr($result["lentry_id"]);
				
				$procedures = $db->GetAll($query);
				$result["procedures"] = "";
				foreach ($procedures as $procedure) {
					if ($result["procedures"]) {
						$result["procedures"] .= "; ";
					}
					$result["procedures"] .= $procedure["procedure"];
				}
				$result["encounter_date"] = date(DEFAULT_DATE_FORMAT, $result["encounter_date"]);
				$result["encounter_date"] = "\"".$result["encounter_date"]."\"";
				$result["patient_info"] = "\"".$result["patient_info"]."\"";
				$result["lentry_id"] = "\"".$result["lentry_id"]."\"";
				$result["age"] = "\"".str_replace(array("-"), array("to"), $result["age"])."\"";
				$result["gender"] = "\"".($result["gender"] == "m" ? "Male" : ($result["gender"] == "f" ? "Female" : "Undefined"))."\"";
				$result["site_name"] = "\"".$result["site_name"]."\"";
				$result["location"] = "\"".$result["location"]."\"";
				$result["rotation_title"] = "\"".$result["rotation_title"]."\"";
				$result["reflection"] = "\"".$result["reflection"]."\"";
				$result["comments"] = "\"".$result["comments"]."\"";
				$result["objectives"] = "\"".$result["objectives"]."\"";
				$result["procedures"] = "\"".$result["procedures"]."\"";
				echo implode(",", $result)."\n";
			}
		}
	}
}
exit();