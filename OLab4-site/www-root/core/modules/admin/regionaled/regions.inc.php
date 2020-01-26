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
 * Secondary controller file used by the regions module within the regionaled module.
 * /admin/regionaled/regions
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONALED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update")) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_REGIONS", true);

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled/regions", "title" => "Manage Regions");

	if (($router) && ($router->initRoute())) {
		/**
		 * Set the user selected country, or setup the default country.
		 */
		if (isset($_GET["country"]) && ($tmp_input = clean_input($_GET["country"], array("nows", "int")))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"] = $tmp_input;
		} else {
			if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"] = DEFAULT_COUNTRY_ID;
			}
		}
		/**
		 * Set the user selected country, or setup the default country.
		 */
		if (isset($_GET["province_id"]) && ($tmp_input = clean_input($_GET["province_id"], array("nows", "int")))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["province_id"] = $tmp_input;
		} else {
			if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["province_id"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["province_id"] = DEFAULT_PROVINCE_ID;
			}
		}

		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}