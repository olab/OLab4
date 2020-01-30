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
 * Serves as the main Entrada "public" request controller file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

define("IN_CURRICULUM", true);

$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => "Curriculum");

if (($router) && ($router->initRoute())) {

	$PREFERENCES = preferences_load($MODULE);

	/**
	 * Determine reporting start and end date.
	 */
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
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"] = strtotime("July 1st, ".(date("Y", time()) - ((date("m", time()) < 7) ?  1 : 2))." 0:00:00");
		}

		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"] = strtotime("+1 year", ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"] - 1));
		}
	}

	/**
	 * Set the posted organisation.
	 */
	if ((isset($_POST["organisation_id"])) && ((int) trim($_POST["organisation_id"]))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = (int) trim($_POST["organisation_id"]);
	} else {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["organisation_id"] = $_SESSION["details"]["organisation_id"];
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
/* vim: set noexpandtab: */
