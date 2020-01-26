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
 * Primary controller file for the Quizzes module.
 * /admin/quizzes
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

/**
 * Allows you to specify which record id your particular component is
 * dealing with (i.e. http:// ... /admin/events?section=edit&id=1562).
 */
if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
	$RECORD_ID = $tmp_input;
} elseif ((isset($_POST["id"])) && ($tmp_input = clean_input($_POST["id"], array("trim", "int")))) {
	$RECORD_ID = $tmp_input;
} else {
	$RECORD_ID = 0;
}

if (isset($_REQUEST["community"]) && $_REQUEST["community"]) {
	$QUIZ_TYPE = "community_page";
} else if (isset($_GET["assessment"]) && $_GET["assessment"]) {
	$QUIZ_TYPE = "assessment";
} else {
	$QUIZ_TYPE = "event";
}

if ($QUIZ_TYPE == "community_page" && $_SESSION["details"]["group"] != "student" && $SECTION == "results") {
	$query		= "	SELECT `content_id`
					FROM `attached_quizzes` 
					WHERE `aquiz_id` = ".$db->qstr($RECORD_ID);
	$cpage_id = $db->GetOne($query);
	if ($cpage_id) {
		$community_access_query = "	SELECT * FROM `community_members` AS a
									JOIN `community_pages` AS b
									ON a.`community_id` = b.`community_id`
									WHERE b.`cpage_id` = ".$db->qstr($cpage_id)."
									AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
									AND a.`member_active` = '1'
									AND a.`member_acl` = '1'";
		$access = ($db->GetRow($community_access_query) ? true : false);
	} else {
		$access = false;
	}
} else {
	$access = false;
}

if (!$access && (!$ENTRADA_ACL->amIAllowed($MODULES["quizzes"]["resource"], $MODULES["quizzes"]["permission"], false))) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_QUIZZES", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/quizzes", "title" => $MODULES[strtolower($MODULE)]["title"]);

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		$ALLOW_QUESTION_MODIFICATIONS = false;

		/**
		 * Check to see if we can add / modify / delete questions from this quiz.
		 */
		if ((int) $RECORD_ID) {
			$query	= "SELECT COUNT(*) AS `total` FROM `quiz_progress` WHERE `quiz_id` = ".$db->qstr($RECORD_ID);
			$result = $db->GetRow($query);
			if ((!$result) || ((int) $result["total"] === 0)) {
				$ALLOW_QUESTION_MODIFICATIONS = true;
			}
		}
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