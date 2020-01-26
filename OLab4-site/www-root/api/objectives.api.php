<?php
/**
 * Online Course Resources [Pre-Clerkship]
 * Module:	Courses
 * Area:	Admin
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @version 0.8.3
 * @copyright Copyright 2009 Queen's University, MEdTech Unit
 *
 * $Id: add.inc.php 505 2009-07-09 19:15:57Z jellis $
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
	
	if (isset($_POST["course_ids"]) && ($course_ids = (explode(",", $_POST["course_ids"])))) {
		
		//$org_id = $_POST["org_id"];
		$org_id = $ENTRADA_USER->getActiveOrganisation();
		$top_level_id = $_POST["top_level_id"];
		
		
		if (isset($_POST["hierarchy"])) {
			if (isset($_POST["event_id"]) && $_POST["event_id"]) {
				$event_id = (int)$_POST["event_id"];
			} else {
				$event_id = 0;
			}
			
			list($objectives,$top_level_id) = courses_fetch_objectives($org_id, $course_ids, -1,-0, false, false, $event_id);
			if ($event_id) {
				$temp_objectives = $objectives["objectives"];
				foreach ($temp_objectives as $objective_id => $objective) {
					if ($objective["event_objective"]) {
						if (!array_key_exists($objective_id, $objectives["used_ids"])) {
							$objectives["objectives"][$objective_id][($objectives["objectives"][$objectives["objectives"][$objective_id]["parent"]]["primary"] ? "primary" : ($objectives["objectives"][$objectives["objectives"][$objective_id]["parent"]]["secondary"] ? "secondary" : "tertiary"))] = true;
							$objectives[($objectives["objectives"][$objectives["objectives"][$objective_id]["parent"]]["primary"] ? "primary_ids" : ($objectives["objectives"][$objectives["objectives"][$objective_id]["parent"]]["secondary"] ? "secondary_ids" : "tertiary_ids"))][] = $objective_id;
							$objectives["used_ids"][] = $objective_id;
							foreach ($objective["parent_ids"] as $parent_id) {
								$objectives["objectives"][$parent_id]["objective_children"]++;
							}
						}
						$show_objectives = true;
					} elseif ($objective["primary"] || $objective["secondary"] || $objective["tertiary"]) {
						foreach ($objective["parent_ids"] as $parent_id) {
							$objectives["objectives"][$parent_id]["objective_children"]--;
						}
						unset($objectives["used_ids"][$objective_id]);
						if ($objective["primary"]) {
							unset($objectives["primary_ids"][$objective_id]);
							$objectives["objectives"][$objective_id]["primary"] = false;
						} elseif ($objective["secondary"]) {
							unset($objectives["secondary_ids"][$objective_id]);
							$objectives["objectives"][$objective_id]["secondary"] = false;
						} elseif ($objective["tertiary"]) {
							unset($objectives["tertiary_ids"][$objective_id]);
							$objectives["objectives"][$objective_id]["tertiary"] = false;
						}
					}
				}
			}
			echo course_objectives_in_list($objectives, $top_level_id,$top_level_id, false, false, 1, false, true, "primary", true);
		} else {
			if (isset($_POST["event_id"]) && $_POST["event_id"]) {
				$event_id = (int)$_POST["event_id"];
			} else {
				$event_id = 0;
			}
			if (isset($_POST["primary_ids"]) && $_POST["primary_ids"]) {
				$primary_ids = explode(",", $_POST["primary_ids"]);
				if (!is_array($primary_ids)) {
					$primary_ids = array($_POST["primary_ids"]);
				}
			} else {
				$primary_ids = array();
			}
			if (isset($_POST["secondary_ids"]) && $_POST["secondary_ids"]) {
				$secondary_ids = explode(",", $_POST["secondary_ids"]);
				if (!is_array($secondary_ids)) {
					$secondary_ids = array($_POST["secondary_ids"]);
				}
			} else {
				$secondary_ids = array();
			}
			if (isset($_POST["tertiary_ids"]) && $_POST["tertiary_ids"]) {
				$tertiary_ids = explode(",", $_POST["tertiary_ids"]);
				if (!is_array($tertiary_ids)) {
					$tertiary_ids = array($_POST["tertiary_ids"]);
				}
			} else {
				$tertiary_ids = array();
			}
			list($objectives,$top_level_id) = courses_fetch_objectives($org_id,$course_ids, -1,0,false, array("primary" => $primary_ids, "secondary" => $secondary_ids, "tertiary" => $tertiary_ids), $event_id);
			echo course_objectives_in_list($objectives, $top_level_id,$top_level_id, true, false, 1, false, true, "primary", true);
		}
	}
	exit;
}