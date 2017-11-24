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
 * This API file returns an HTML table of the possible targets for the selected
 * evaluation form. For instance, if the selected form is a course evaluation
 * it will return HTML used by the administrator to select which course / courses
 * they wish to evaluate.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_EVENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "create", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	/**
	 * Clears all open buffers so we can return a plain response for the Javascript.
	 */
	ob_clear_open_buffers();

	$options_for = false;
	$course_id = 0;

	if (isset($_POST["options_for"]) && ($tmp_input = clean_input($_POST["options_for"], array("trim")))) {
		$options_for = $tmp_input;
	}

	if (isset($_POST["course_id"]) && ($tmp_input = clean_input($_POST["course_id"], array("int")))) {
		$course_id = $tmp_input;
        $PROCESSED["course_id"] = $course_id;

	}

	if (isset($_POST["event_id"]) && ($tmp_input = clean_input($_POST["event_id"], array("int")))) {
		$event_id = $tmp_input;
	}

	if ($options_for && $ENTRADA_USER->getActiveOrganisation()) {

		$organisation[$ENTRADA_USER->getActiveOrganisation()] = array("text" => fetch_organisation_title($ENTRADA_USER->getActiveOrganisation()), "value" => "organisation_" . $ENTRADA_USER->getActiveOrganisation(), "category" => true);

		switch ($options_for) {
			case "cohorts" : // Classes
				/**
				 * Cohorts.
				 */
				if ((isset($_POST["event_audience_cohorts"]))) {
					$associated_audience = explode(',', $_POST["event_audience_cohorts"]);
					if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
						foreach($associated_audience as $audience_id) {
							if (strpos($audience_id, "cohort") !== false) {
								if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
									$query = "	SELECT *
												FROM `groups`
												WHERE `group_id` = ".$db->qstr($group_id)."
												AND (`group_type` = 'cohort' OR `group_type` = 'course_list')
												AND `group_active` = 1";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_cohort_ids"][] = $group_id;
									}
								}
							}
						}
					}
				}

				$groups = $organisation;

				$query = "	SELECT a.*
							FROM `groups` AS a
							JOIN `group_organisations` AS b
							ON b.`group_id` = a.`group_id`
							WHERE a.`group_active` = '1'
							AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							AND (a.`group_type` = 'cohort' OR a.`group_type` = 'course_list')
							ORDER BY a.`group_name` DESC";
				$groups_results = $db->GetAll($query);
				if ($groups_results) {
					foreach ($groups_results as $group) {
						if (isset($PROCESSED["associated_cohort_ids"]) && is_array($PROCESSED["associated_cohort_ids"]) && in_array($group["group_id"], $PROCESSED["associated_cohort_ids"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$groups[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $group["group_name"], "value" => "cohort_" . $group["group_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("cohorts", $groups, array("title" => "Select Cohorts/Course Lists of Learners:", "submit_text" => "Close", "submit" => true, "type" => 'audience'));
				} else {
					echo display_notice("There are no cohorts of learners available.");
				}
			break;
			case "course_groups" :
				/**
				 * Course Groups
				 */
				if (isset($_POST["event_audience_course_groups"]) && isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
					$associated_audience = explode(',', $_POST["event_audience_course_groups"]);
					if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
						foreach($associated_audience as $audience_id) {
							if (strpos($audience_id, "cgroup") !== false) {
								if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
									$query = "	SELECT *
												FROM `course_groups`
												WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
												AND `course_id` = ".$db->qstr($PROCESSED["course_id"])."
												AND (`active` = '1')";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_cgroup_ids"][] = $cgroup_id;
									}
								}
							}
						}
					}
				}

				$groups = $organisation;

				$query = "	SELECT a.*
							FROM `course_groups` AS a
							WHERE a.`active` = '1'
							AND a.`course_id` = ".$db->qstr($course_id)."
							ORDER BY LENGTH(a.`group_name`), a.`group_name` ASC"; // The LENGTH sort is a MySQL natural sorting hack.
				$groups_results = $db->GetAll($query);
				if ($groups_results) {
					foreach ($groups_results as $group) {
						if (isset($PROCESSED["associated_cgroup_ids"]) && is_array($PROCESSED["associated_cgroup_ids"]) && in_array($group["cgroup_id"], $PROCESSED["associated_cgroup_ids"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$groups[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $group["group_name"], "value" => "cgroup_" . $group["cgroup_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("course_groups", $groups, array("title" => "Select Course Specific Small Groups:", "submit_text" => "Close", "submit" => true, "type" => 'audience'));
				} else {
					//echo display_notice("There are no small groups in the course you have selected.");
				}
			break;
			case "students" : // Students
				/**
				 * Learners
				 */
				if ((isset($_POST["event_audience_students"]))) {
					$associated_audience = explode(',', $_POST["event_audience_students"]);
					if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
						foreach($associated_audience as $audience_id) {
							if (strpos($audience_id, "student") !== false) {
								if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
									$query = "	SELECT a.*
												FROM `".AUTH_DATABASE."`.`user_data` AS a
												LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
												ON a.`id` = b.`user_id`
												WHERE a.`id` = ".$db->qstr($proxy_id)."
												AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
												AND b.`account_active` = 'true'
												AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
												AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
									$result	= $db->GetRow($query);
									if ($result) {
										$PROCESSED["associated_proxy_ids"][] = $proxy_id;
									}
								}
							}
						}
					}
				}

				$students = $organisation;

				$query = "	SELECT a.`id` AS `proxy_id`, b.`organisation_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON a.`id` = b.`user_id`
							WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
							AND b.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
							AND b.`account_active` = 'true'
							AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
							AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
							AND b.`group` = 'student'
							AND a.`grad_year` >= ".$db->qstr((fetch_first_year() - 4)).
							(($ENTRADA_USER->getActiveGroup() == "student") ? " AND a.`id` = ".$db->qstr($ENTRADA_USER->getID()) : "")."
							GROUP BY a.`id`
							ORDER BY a.`grad_year` DESC, a.`lastname` ASC, a.`firstname` ASC";
				$student_results = $db->GetAll($query);
				if ($student_results) {
					foreach ($student_results as $student) {
						if (isset($PROCESSED["associated_proxy_ids"]) && is_array($PROCESSED["associated_proxy_ids"]) && in_array($student["proxy_id"], $PROCESSED["associated_proxy_ids"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$students[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $student["fullname"], "value" => "student_".$student["proxy_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("students", $students, array("title" => "Select Individual Learners:", "submit_text" => "Close", "submit" => true, "type" => 'audience'));
				} else {
					echo display_notice("There are no students available.");
				}
			break;

			default :
				application_log("notice", "Unknown learning event filter type [" . $options_for . "] provided to events_filters API.");
			break;
		}
	}
}
exit;