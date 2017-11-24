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
 * @author Unit: MEdTech Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_AAMC_CI"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (($router) && ($router->initRoute())) {

		if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], "int"))) {
			$REPORT_ID = $tmp_input;
		}

		if ($REPORT_ID) {
			$query = "SELECT * FROM `reports_aamc_ci` WHERE `raci_id` = ".$db->qstr($REPORT_ID)." AND `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
			$REPORT = $db->GetRow($query);
			if ($REPORT) {
				$SHORT_REPORT_TITLE = date("Y", strtotime($REPORT["report_start"]))."-".date("Y", strtotime($REPORT["report_finish"])). " Curriculum";

				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/reports/aamc/manage?id=".$REPORT_ID, "title" => $SHORT_REPORT_TITLE);

				$module_file = $router->getRoute();
				if ($module_file) {
					require_once($module_file);
				}
			} else {
				add_notice("You don't appear to have access to change this organisation. If you feel you are seeing this in error, please contact your system administrator.");
				echo display_notice();
			}
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}