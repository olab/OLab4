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
 * Secondary controller file used by the reports module within the distributions module.
 * /admin/assessments/distributions/reports
 *
 * @author Organisation: Queen's univeristy
 * @author Unit: Education Technology unit
 * @author Developer:  Devin Monroe <dm149@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
*/

if (!defined("IN_DISTRIBUTIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/distributions/reports", "title" => "Distribution Reports");

	if (($router) && ($router->initRoute())) {
		$module_file = $router->getRoute();
		if ($module_file) {
            ?>
            <style media="print">
                @-moz-document url-prefix() {
                    * {
                        overflow: hidden !important;
                    }
                }
            </style>
            <?php
			require_once($module_file);
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}