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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	$course_owner = false;
	$courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
	if ($courses) {
		foreach ($courses as $course) {
			if (CourseOwnerAssertion::_checkCourseOwner($ENTRADA_USER->getActiveID(), $course->getID())) {
				$course_owner = true;
			}
		}
	}
	if ($course_owner) {
		define("IN_ASSESSMENTS_LEARNERS", true);

		if (($router) && ($router->initRoute())) {
            $PREFERENCES = preferences_load($MODULE);
            $PROXY_ID = 0;
			if (isset($_GET["proxy_id"]) && ($tmp_input = clean_input($_GET["proxy_id"], array("trim", "int")))) {
				$PROXY_ID = $tmp_input;
			}

			/**
			 * Load the requested view section.
			 */
			$module_file = $router->getRoute();
			if ($module_file) {
				require_once($module_file);
			}
            preferences_update($MODULE, $PREFERENCES);
		} else {
			$url = ENTRADA_URL."/".$MODULE;
			application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

			header("Location: ".$url);
			exit;
		}
	} else {
		$ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";

		$ERROR++;
		$ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";

		echo display_error();

		application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
	}
}