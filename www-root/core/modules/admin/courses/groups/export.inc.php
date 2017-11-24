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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_GROUPS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} else if (!$ENTRADA_ACL->amIAllowed("course", "update", false)) {
	$ERROR++;
	$ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$csv_delimiter = ",";
	$csv_enclosure = '"';
	
	$course_group_ids = array();
	$course_group_ids_string = "";
	if (isset($_POST["groups"]) && @count($_POST["groups"])) {
		foreach ($_POST["groups"] as $course_group_id) {
			$course_group_ids[] = clean_input($course_group_id, "int");
			$course_group_ids_string .= ($course_group_ids_string ? "," : "").$db->qstr(clean_input($course_group_id, "int"));
		}
	}

	$course_groups_object = new Models_Course_Group();
	$course_groups = $course_groups_object->getAllByMultipleGroupID($course_group_ids);
	
	if ($course_groups) {
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
		header("Content-Disposition: attachment; filename=\"".clean_input($course_groups[0]["course_code"], array("trim", "file"))."-course-group-export-".date("Y-m-d").".csv\"");
		header("Content-Transfer-Encoding: binary");		

		$fp = fopen("php://output", "w");
	
		// Output CSV headings
		fputcsv($fp, array("course_name" => "Course Name", "group_name" => "Group Name", "tutors" => "Tutors", "members" => "Group Members"), $csv_delimiter, $csv_enclosure);
		
		$row["course_name"] = $course_groups[0]["course_name"];
		fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
		
		foreach ($course_groups as $course_group) {
			$course_group_audience_object = new Models_Course_Group_Audience();
			$course_group_contacts_object = new Models_Course_Group_Contact();
			$course_group_audience = $course_group_audience_object->getExportGroupAudienceByGroupID($course_group["cgroup_id"]);
			$course_group_contacts = $course_group_contacts_object->getExportGroupContactsByGroupID($course_group["cgroup_id"]);
			$row = array();
			$row["course_name"] = "";
			$row["group_name"] = $course_group["group_name"];
			fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
			if ($course_group_contacts) {
				foreach ($course_group_contacts as $course_group_contact) {
					$row = array();
					$row["course_name"] = "";
					$row["group_name"] = "";
					$row["tutors"] = $course_group_contact["fullname"];
					fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
				}
			}
			if ($course_group_audience) {
				foreach ($course_group_audience as $course_group_member) {
					$row = array();
					$row["course_name"] = "";
					$row["group_name"] = "";
					$row["tutors"] = "";
					$row["members"] = $course_group_member["fullname"];
					fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
				}
			}
		}
		
		fclose($fp);
		exit;
	} else {
		header("Location: ".ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID);
	}
}