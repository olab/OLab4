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
 * Primary controller file for the System Reports module.
 * /admin/reports
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
} elseif (!$ENTRADA_ACL->amIAllowed("reportindex", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_REPORTS", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE, "title" => $MODULES[strtolower($MODULE)]["title"]);

	if (($router) && ($router->initRoute())) {

		$PREFERENCES = preferences_load($MODULE);
		$ORGANISATION_LIST = array();

		if ((isset($_POST["reporting_start"])) && ((int) trim($_POST["reporting_start"])) && (isset($_POST["reporting_finish"])) && ((int) trim($_POST["reporting_finish"]))) {
			$report_date = validate_calendars("reporting", true, true, true);

			if ((isset($report_date["start"])) && ((int) $report_date["start"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"] = (int) $report_date["start"];
			}

			if ((isset($report_date["finish"])) && ((int) $report_date["finish"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"] = (int) $report_date["finish"];
			}
		} else {
			if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"]	= strtotime("July 1st, ".(date("Y", time()) - ((date("m", time()) < 7) ?  1 : 2))." 0:00:00");
			}

			if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]	= strtotime("+1 year", ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"] - 1));
			}
		}

		/**
		 * Set the posted organisation.
		 */
		if ((isset($_POST["organisation_id"])) && ((int) trim($_POST["organisation_id"]))) {
			if ((int) trim($_POST["organisation_id"]) == -1) {
				$query = "SELECT `organisation_id`, `organisation_title` FROM `".AUTH_DATABASE."`.`organisations`";
				$results = $db->GetAll($query);
				if ($results) {
					$ORGANISATION_LIST = $results;
					$all = true;
					foreach ($results as $result) {
						if (!$ENTRADA_ACL->amIAllowed('resourceorganisation'.$result['organisation_id'], 'read')) {
							$all = false;
						}
					}

					if ($all) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = -1;
					}
				}
			}

			if ($ENTRADA_ACL->amIAllowed('resourceorganisation'.(int) trim($_POST["organisation_id"]), 'read')) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = (int) trim($_POST["organisation_id"]);
			}
		}

		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = $_SESSION['details']['organisation_id'];
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