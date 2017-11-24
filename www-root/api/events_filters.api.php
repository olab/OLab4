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
 * Serves the HTML for the Learning Event filters.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
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

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
	$options_for = false;

	if (isset($_GET["options_for"])) {
		$options_for = clean_input($_GET["options_for"], array("trim"));
	}

	$organisation_id = $ENTRADA_USER->getActiveOrganisation();
	
	if ($options_for && $ENTRADA_USER->getActiveOrganisation()) {
		
		$organisation[$ENTRADA_USER->getActiveOrganisation()] = array("text" => fetch_organisation_title($ENTRADA_USER->getActiveOrganisation()), "value" => "organisation_" . $ENTRADA_USER->getActiveOrganisation(), "category" => true);

		switch ($options_for) {
			case "teacher" : // Teachers
				$teachers = $organisation;
				
				$query = "	SELECT a.`id` AS `proxy_id`, a.`organisation_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON b.`user_id` = a.`id`
							JOIN `event_contacts` AS c
							ON c.`proxy_id` = a.`id`
							JOIN `events` AS d
							ON d.`event_id` = c.`event_id`
							JOIN `courses` AS e
							ON e.`course_id` = d.`course_id`
							WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
							AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))
							AND a.`id` IN (SELECT `proxy_id` FROM `event_contacts`)
							AND e.`organisation_id` =  " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
							GROUP BY a.`id`
							ORDER BY `fullname` ASC";
				$teacher_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
				if ($teacher_results) {

					foreach ($teacher_results as $teacher) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["teacher"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["teacher"]) && in_array($teacher["proxy_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["teacher"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$teachers[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $teacher["fullname"], "value" => "teacher_".$teacher["proxy_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("teacher", $teachers, array("title" => "Select Teachers:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
				}
			break;
			case "student" : // Students
				$students = $organisation;

				$query = "	SELECT a.`id` AS `proxy_id`, a.`organisation_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
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
							(($ENTRADA_USER->getGroup() == "student") ? " AND a.`id` = ".$db->qstr($ENTRADA_USER->getID()) : "")."
							GROUP BY a.`id`
							ORDER BY a.`grad_year` DESC, a.`lastname` ASC, a.`firstname` ASC";
				$student_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
				if ($student_results) {
					
					foreach ($student_results as $student) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["student"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["student"]) && in_array($student["proxy_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["student"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$students[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $student["fullname"], "value" => "student_".$student["proxy_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("student", $students, array("title" => "Select Students:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
				}
			break;
			case "course" : // Courses
				
				$courses = array();
				$courses[1] = array("text" => $organisation[$ENTRADA_USER->getActiveOrganisation()]["text"] . " Active Courses", "value" => "active_courses", "category" => true);
				$courses[0] = array("text" => $organisation[$ENTRADA_USER->getActiveOrganisation()]["text"] . " Inactive Courses", "value" => "inactive_courses", "category" => true);            

				$courses_results = courses_fetch_courses(false);
				if ($courses_results) {
					foreach ($courses_results as $course) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["course"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["course"]) && in_array($course["course_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["course"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$courses[$course["course_active"]]["options"][] = array("text" => ($course["course_code"] ? $course["course_code"].": " : "").$course["course_name"], "value" => "course_" . $course["course_id"], "checked" => $checked);
					}
					
					// If there are no inactive courses, don't display the heading.
					if (!isset($courses[0]["options"]) || empty($courses[0]["options"])) {
						unset($courses[0]);
					}

					echo lp_multiple_select_popup("course", $courses, array("title" => "Select Courses:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
				}
			break;
			case "group" : // Classes & Groups
				$groups = $organisation;
				
				$groups_results = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
				if ($groups_results) {
					
					foreach ($groups_results as $group) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["group"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["group"]) && in_array($group["group_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["group"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$groups[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $group["group_name"], "value" => "group_" . $group["group_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("group", $groups, array("title" => "Select Classes or Groups:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
				}
			break;
			case "eventtype" : // Learning Event Types
				$eventtypes = $organisation;
				
				$query = "	SELECT a.`eventtype_id`, a.`eventtype_title`
							FROM `events_lu_eventtypes` AS a
							LEFT JOIN `eventtype_organisation` AS c 
							ON a.`eventtype_id` = c.`eventtype_id` 
							LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS b
							ON b.`organisation_id` = c.`organisation_id` 
							WHERE b.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
							AND a.`eventtype_active` = '1' 
							ORDER BY a.`eventtype_order` ASC";
				$eventtype_results = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
				if ($eventtype_results) {
					
					foreach ($eventtype_results as $eventtype) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["eventtype"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["eventtype"]) && in_array($eventtype["eventtype_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["eventtype"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$eventtypes[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $eventtype["eventtype_title"], "value" => "eventtype_" . $eventtype["eventtype_id"], "checked" => $checked);
					}

					echo lp_multiple_select_popup("eventtype", $eventtypes, array("title" => "Select Event Types:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
				}
			break;
			case "term" : // Terms
				$terms = $organisation;

				$query = "	SELECT a.*
							FROM `curriculum_lu_types` AS a
							JOIN `curriculum_type_organisation` AS b
							ON b.`curriculum_type_id` = a.`curriculum_type_id`
							WHERE a.`curriculum_type_active` = '1'
							AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							ORDER BY a.`curriculum_type_order` ASC";
				$curriculum_types = $db->GetAll($query);
				if ($curriculum_types) {
					foreach ($curriculum_types as $curriculum_type) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["term"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["term"]) && in_array($curriculum_type["curriculum_type_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["term"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$terms[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $curriculum_type["curriculum_type_name"], "value" => "term_" . $curriculum_type["curriculum_type_id"], "checked" => $checked);
					}
				}

				echo lp_multiple_select_popup("term", $terms, array("title" => "Select Terms:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
			break;
			case "cp" : // Clinical Presentations aka MCC Presentations or Objectives.
				$presentations = $organisation;

				$clinical_presentations = fetch_clinical_presentations();
				if ($clinical_presentations) {
					foreach ($clinical_presentations as $clinical_presentation) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["cp"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["cp"]) && in_array($clinical_presentation["objective_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["cp"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$presentations[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $clinical_presentation["objective_name"], "value" => "cp_" . $clinical_presentation["objective_id"], "checked" => $checked);
					}
				}

				echo lp_multiple_select_popup("cp", $presentations, array("title" => "Select " . $translate->_("Clinical Presentations") . ":", "submit_text" => "Apply", "cancel" => true, "submit" => true));
			break;
			case "co" : // Curriculum Objectives
				$objectives = $organisation;
				
				$children = array();
				fetch_curriculum_objectives_children(0, $children);
				
				if ($children) {
					foreach ($children as $curriculum_objective) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["co"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["co"]) && in_array($curriculum_objective["objective_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["co"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$objectives[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $curriculum_objective["objective_name"], "value" => "co_" . $curriculum_objective["objective_id"], "checked" => $checked);
					}
				}

				echo lp_multiple_select_popup("co", $objectives, array("title" => "Select " . $translate->_("Curriculum Objectives") . ":", "submit_text" => "Apply", "cancel" => true, "submit" => true));
			break;
			case "topic" : // Topics
				$topics = $organisation;
				
				$event_topics = fetch_event_topics();
				if ($event_topics) {
					
					foreach ($event_topics as $topic) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["topic"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["topic"]) && in_array($topic["topic_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["topic"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$topics[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $topic["topic_name"], "value" => "topic_" . $topic["topic_id"], "checked" => $checked);
					}
				}

				echo lp_multiple_select_popup("topic", $topics, array("title" => "Select Hot Topics:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
			break;
			case "department" : // Departments
				$department = $organisation;
				
				$query =	"SELECT `department_id`, `department_title` 
							 FROM `".AUTH_DATABASE."`.`departments` 
							 WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
							 AND `department_active` = '1'";
				$departments = $db->GetAll($query);
				if (!empty($departments)) {
					foreach ($departments as $department_details) {
						if (isset($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["department"]) && is_array($_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["department"]) && in_array($department_details["department_id"], $_SESSION[APPLICATION_IDENTIFIER]["events"]["filters"]["department"])) {
							$checked = "checked=\"checked\"";
						} else {
							$checked = "";
						}

						$department[$ENTRADA_USER->getActiveOrganisation()]["options"][] = array("text" => $department_details["department_title"], "value" => "department_" . $department_details["department_id"], "checked" => $checked);
					}
				}
				echo lp_multiple_select_popup("department", $department, array("title" => "Select Departments:", "submit_text" => "Apply", "cancel" => true, "submit" => true));
			break;
			default :
				application_log("notice", "Unknown learning event filter type [" . $options_for . "] provided to events_filters API.");
			break;
		}
	}
}
