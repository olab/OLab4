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
 * This file displays the list of learning events that match any requested
 * filters. Data is pulled from the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} else if (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
	$ERROR++;
	$ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Process any sorting or pagination requests.
	 */
	events_process_sorting();

	/**
	 * Process any filter requests.
	 */
	events_process_filters($ACTION, "admin");

	/**
	 * Check if preferences need to be updated.
	 */
	preferences_update($MODULE, $PREFERENCES);
	
	if ((!isset($_GET["my_export_options"])) || (!$csv_headings = json_decode($_GET["my_export_options"], true)) || !is_array($csv_headings)  || !(sizeof($csv_headings) > 0)) {
		$_SESSION["export_error"] = "No fields selected for export.";
		application_log("error", "No fields selected for Event Export.");
		header("Location: ".ENTRADA_URL."/admin/events");
	} else {
		$_SESSION["export_error"] = "";
		$_SESSION["my_export_options"] = $csv_headings;
	}
	
	$csv_delimiter = ",";
	$csv_enclosure = '"';

    $draft_id = (int) $_GET["draft_id"];

    $learning_events = draft_events_fetch_filtered_draft_events(
			$ENTRADA_USER->getActiveId(),
			$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"],
			$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"],
			$ENTRADA_USER->getActiveOrganisation(),
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["dtype"],
			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"],
			0,
            $draft_id,
			$_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"],
			false);

	if (!empty($learning_events["events"])) {
		/**
		 * Clears all open buffers so we can return a plain response for the Javascript.
		 */
		ob_clear_open_buffers();
	

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"draft-schedule-export-".date("Y-m-d").".csv\"");
		header("Content-Transfer-Encoding: binary");		
	
		$fp = fopen("php://output", "w");
	
		// Output CSV headings
		fputcsv($fp, $csv_headings, $csv_delimiter, $csv_enclosure);
		
		$objective_name = $translate->_("events_filter_controls");
		$curriculum_objectives_name = $objective_name["co"]["global_lu_objectives_name"];
		$clinical_presentations_name = $objective_name["cp"]["global_lu_objectives_name"];

		foreach ($learning_events["events"] as $event) {
			$event_type_durations = array();
			$event_types = array();
			$audience_cohorts = array();
			$audience_groups = array();
			$audience_students = array();
			$teacher_numbers = array();
			$teacher_names = array();
			$auditor_numbers = array();
			$auditor_names = array();
			$teachers_assistant_numbers = array();
			$teachers_assistant_names = array();
			$tutor_numbers = array();
			$tutor_names = array();
			$student_names = array();

			// Event Type Durations, and Event Types
			$query = "	SELECT a.`duration`, b.`eventtype_title`
						FROM `draft_eventtypes` AS a
						JOIN `events_lu_eventtypes` AS b
						ON b.`eventtype_id` = a.`eventtype_id`
						WHERE a.`event_id` = ".$db->qstr($event["event_id"]);
			if ($results = $db->GetAll($query)) {
				foreach ($results as $key => $result) {
					$event_type_durations[$key] = $result["duration"];
					$event_types[$key] = $result["eventtype_title"];
				}
			}

			// Event Audience (Cohorts, Student Numbers, Course Codes)
			$query = "	SELECT a.`audience_type`, a.`audience_value`
						FROM `draft_audience` AS a
						WHERE a.`event_id` = ".$db->qstr($event["event_id"]);
			if ($results = $db->GetAll($query)) {
				foreach ($results as $result) {
					
					switch ($result["audience_type"]) {
						case "cohort" :
							$query = "SELECT `group_name` FROM `groups` WHERE `group_id` = ".$db->qstr($result["audience_value"]);
							if ($audience = $db->GetRow($query)) {
								$audience_cohorts[] = $audience["group_name"];
							}
							$query = "	SELECT *
										FROM `group_members` g
										JOIN " . AUTH_DATABASE . ".`user_data` ud
										ON g.`proxy_id` = ud.`id`
										WHERE g.`group_id` = " . $db->qstr($result["audience_value"]);
							$s_results = $db->GetAll($query);
							if ($s_results) {
								foreach($s_results as $audience) {
									$student_names[] = $audience["firstname"] . " " . $audience["lastname"];
								}
							}
						break;
						case "proxy_id" :
							$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($result["audience_value"]);
							if ($audience = $db->GetRow($query)) {
								$audience_students[] = (int) $audience["number"];
								$student_names[] = $audience["firstname"] . " " . $audience["lastname"];
							}
						break;
						case "group_id" :
							$query = "SELECT `group_name` FROM `course_groups` WHERE `cgroup_id` = ".$db->qstr($result["audience_value"]);
							if ($audience = $db->GetRow($query)) {
								$audience_groups[] = $audience["group_name"];
							}
							$query = "	SELECT *
										FROM `course_group_audience` cga
										JOIN " . AUTH_DATABASE . ".`user_data` ud
										ON cga.`proxy_id` = ud.`id`
										WHERE cga.`cgroup_id` = " . $db->qstr($result["audience_value"]);
							$s_results = $db->GetAll($query);
							if ($s_results) {
								foreach($s_results as $audience) {
									$student_names[] = $audience["firstname"] . " " . $audience["lastname"];
								}
							}
						default :
							continue;
						break;
					}
				}
			}
			
			// Staff Numbers, and Names
			$query = "	SELECT b.`number`, a.`contact_role`, CONCAT(b.`firstname`, ' ', b.`lastname`) AS `fullname`
						FROM `draft_contacts` AS a
						JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON b.`id` = a.`proxy_id`
						WHERE a.`event_id` = ".$db->qstr($event["event_id"])."						
						ORDER BY `contact_order` ASC";
			if ($results = $db->GetAll($query)) {
				foreach ($results as $key => $result) {
					switch ($result["contact_role"]) {
						case "teacher":
							$teacher_numbers[$key] = (int) $result["number"];
							$teacher_names[$key] = $result["fullname"];
							break;
						
						case "tutor":
							$tutor_numbers[$key] = (int) $result["number"];
							$tutor_names[$key] = $result["fullname"];
							break;
						
						case "ta":
							$teachers_assistant_numbers[$key] = (int) $result["number"];
							$teachers_assistant_names[$key] = $result["fullname"];
							break;
						
						case "auditor":
							$auditor_numbers[$key] = (int) $result["number"];
							$auditor_names[$key] = $result["fullname"];
							break;
						
						default:
							break;
					}					
				}
			}
			
			$row = array();
			foreach ($csv_headings as $key => $value) {
				switch($key) {
					case "event_start_date" :
						$row[$key] = date("Y-m-d", $event["event_start"]);
						break;
					case "event_start_time":
						$row[key] = date("H:i", $event["event_start"]);
						break;
					case "event_type_durations":
						$row[$key] = implode("; ", $event_type_durations);
						break;
					case "total_duration":
						$row[$key] = (($event["event_finish"] - $event["event_start"]) / 60);
						break;
					case "release_date" :
						$row[$key] = date("Y-m-d H:i", $event["event_start"]);
						break;
					case "release_until" :
						$row[$key] = date("Y-m-d H:i", $event["event_start"]);
						break;
					case "event_types":
						$row[$key] = implode("; ", $event_types);
						break;
					case "audience_cohorts":
						$row[$key] = implode("; ", $audience_cohorts);
						break;
					case "audience_groups":
						$row[$key] = implode("; ", $audience_groups);
						break;
					case "audience_students":
						$row[$key] = implode("; ", $audience_students);
						break;
					case "teacher_numbers":
						$row[$key] = implode("; ", $teacher_numbers);
						break;
					case "teacher_names":
						$row[$key] = implode("; ", $teacher_names);
						break;
					case "teachers_assistant_numbers":
						$row[$key] = implode("; ", $teachers_assistant_numbers);
						break;
					case "teachers_assistant_names":
						$row[$key] = implode("; ", $teachers_assistant_names);
						break;
					case "tutor_numbers":
						$row[$key] = implode("; ", $tutor_numbers);
						break;
					case "tutor_names":
						$row[$key] = implode("; ", $tutor_names);
						break;
					case "auditor_numbers":
						$row[$key] = implode("; ", $auditor_numbers);
						break;
					case "auditor_names":
						$row[$key] = implode("; ", $auditor_names);
						break;
					case "student_names":
						$row[$key] = implode("; ", $student_names);
						break;
					case "attached_quizzes":
						$attached_quizzes = array();
						$query = "	SELECT *
									FROM `attached_quizzes` aq
									WHERE aq.`content_id` = " . $db->qstr($event["event_id"]) . "
									AND	aq.`content_type` = 'event'";
						$items = $db->GetAll($query);
						if ($items) {
							foreach ($items as $i) {
								$attached_quizzes[] = $i["quiz_title"];
							}
						}
						
						$row[$key] = implode("; ", $attached_quizzes);
						break;
					case "attached_links":
						$attached_links = array();
						$query = "	SELECT *
									FROM `event_links` el
									WHERE el.`event_id` = " . $db->qstr($event["event_id"]);
						$items = $db->GetAll($query);
						if ($items) {
							foreach ($items as $i) {
								$attached_links[] = $i["link"];
							}
						}
						
						$row[$key] = implode("; ", $attached_links);
						break;
					case "parent_id":
						if (is_null($event[$key]) || $event[$key] == 0) {
							$row[$key] = 1;
						} else {
							$row[$key] = 0;
						}
						break;
					case "objectives_release_date":
						if ($event["objectives_release_date"] != 0) {
							$row[$key] = date("Y-m-d H:i", $event["objectives_release_date"]);
						} else {
							$row[$key] = 0;
						}
						
						break;
					default:
						if (is_int($event[$key])) {
							$row[$key] = (int) $event[$key];
						} else {
							$row[$key] = $event[$key];
						}
						break;
				}
				
			}
			
			fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
		}
		
		fclose($fp);
		exit;
	} else {
		header("Location: ".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id);
	}
}