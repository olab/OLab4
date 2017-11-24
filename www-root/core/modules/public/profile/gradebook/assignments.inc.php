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
 * Primary controller file for the public Gradebook module.
 * /gradebook
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */
require_once("Entrada/gradebook/handlers.inc.php");

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "read", false)) { 

	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_PUBLIC_ASSIGNMENTS",	true);
	
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.modal.js\"></script>\n";
	$JQUERY[] = "<link href=\"".ENTRADA_URL."/css/jquery/flexigrid.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/flexigrid.js\"></script>\n";
	$JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.editable.js\"></script>\n";
	$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE ."-assignments.js\"></script>";
	$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE ."-file-upload.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/profile/assignments.css\" />";
	$ASSESSMENT_TYPES = array("Formative", "Summative", "Narrative");
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments", "title" => "Assignments");

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);
		if (isset($_GET["assignment_id"]) && ($tmp_input = clean_input($_GET["assignment_id"], array("nows", "int")))) {
			$RECORD_ID = $tmp_input;
		} else {
			$RECORD_ID = 0;
		}
		
		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}

		/**
		 * Check if preferences need to be updated on the server at this point.
		 */
		preferences_update($MODULE, $PREFERENCES);
	} else {
		$url = ENTRADA_URL;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}