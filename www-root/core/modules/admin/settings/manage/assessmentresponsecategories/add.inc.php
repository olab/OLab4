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
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT_RESPONSE_CATEGORIES"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= $translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?section=add&org=".$ORGANISATION_ID, "title" => $translate->_("Add Response Category"));
	
	echo "<h1>".$translate->_("Add Response Category")."</h1>";
	
	define("ADD_ASSESSMENT_RESPONSE_CATEGORY", true);
	require_once("form.inc.php");
	
}