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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed($MODULES[strtolower($MODULE)]["resource"], $MODULES[strtolower($MODULE)]["permission"], false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_ASSIGNMENTS", true);

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$COURSE_ID = $tmp_input;
			$query = "	SELECT * FROM `courses`
						WHERE `course_id` = ".$db->qstr($COURSE_ID)."
						AND `course_active` = '1'";
			$course_details	= $db->GetRow($query);
            $BREADCRUMB[] = array("title" => $course_details["course_code"]);

		} else {
			$COURSE_ID = 0;
		}

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook?section=view&id=".$COURSE_ID, "title" => $translate->_("Gradebook"));
		
		if ((isset($_GET["assignment_id"])) && ($tmp_input = clean_input($_GET["assignment_id"], array("nows", "int")))) {
			$ASSIGNMENT_ID = $tmp_input;
		} else {
			$ASSIGNMENT_ID = 0;
		}
		//Display this assignment if the user is a Dropbox Contact for an assignment associated with this assessment or if they are the Course Owner.
		$query =  "	SELECT a.`course_id`, a.`assignment_id`, a.`assignment_title` 
					FROM `assignments` a
					JOIN `assignment_contacts`	b
					ON a.`assignment_id` = b.`assignment_id`
					WHERE a.`assignment_id` = " . $db->qstr($ASSIGNMENT_ID) . "
					AND b.`proxy_id` = " . $db->qstr($ENTRADA_USER->getActiveId()) . "
					AND a.`assignment_active` = 1";
		$assignment_contact = $db->GetRow($query);		
		if (!$assignment_contact && !$ENTRADA_ACL->amIAllowed(new CourseContentResource($course_details["course_id"], $course_details["organisation_id"]), "update")) {
			$url = ENTRADA_URL."/admin/gradebook";
			$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
			
			add_error("You do not have the permissions required to access this assignment.<br /><br />You will now be redirected to the <strong>Gradebook index</strong> page.  This will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

			echo display_error();

			application_log("error", "User: " . $ENTRADA_USER->getActiveId() . ", does not have access to this assignment id [".$ASSIGNMENT_ID."]");
		} else {
			$module_file = $router->getRoute();
			if ($module_file) {
				require_once($module_file);
			}

			/**
			* Check if preferences need to be updated on the server at this point.
			*/
			preferences_update($MODULE, $PREFERENCES);
		}
	}
}