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
 * Primary controller file for the Polls module.
 * /admin/polls
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
} elseif (!$ENTRADA_ACL->amIAllowed("poll", "update")) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_POLLS", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/polls", "title" => $MODULES[strtolower($MODULE)]["title"]);

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		$POLL_TARGETS = array();
		$POLL_TARGETS["all"] = "Poll all students, faculty &amp; staff";
		$POLL_TARGETS["students"] = "Poll all students";

		$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
		if (isset($active_cohorts) && !empty($active_cohorts)) {
			foreach ($active_cohorts as $cohort) {
				$POLL_TARGETS[$cohort["group_id"]] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Poll ".html_encode($cohort["group_name"]);
			}
		}

		$POLL_TARGETS["faculty"] = "Poll all faculty";
		$POLL_TARGETS["staff"] = "Poll all staff";

		if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
			$POLL_ID = $tmp_input;
		} else {
			$POLL_ID = 0;
		}

		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}

		$query	= "	SELECT a.`poll_id`
					FROM `poll_questions` AS a
					LEFT JOIN `poll_results` AS b
					ON b.`poll_id` = a.`poll_id`
					WHERE b.`result_id` IS NOT NULL
					ORDER BY RAND() LIMIT 1";
		$result	= $db->GetRow($query);
		if ($result) {
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/poll-js.php\"></script>\n";

			new_sidebar_item("Poll Results", poll_display($result["poll_id"]), "quick-poll-results", "open");
		}

		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	}
}