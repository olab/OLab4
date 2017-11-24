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
 * This is the primary controller file for public Quiz module in Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	define("IN_PUBLIC_EVALUATIONS", true);

	if (isset($_GET["view_type"]) && in_array($_GET["view_type"], array("all", "complete", "available", "overdue"))) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["view_type"] = $_GET["view_type"];
		if (isset($_GET["ajax"]) && $_GET["ajax"]) {
			ob_clear_open_buffers();
			echo "200";
			exit;
		}
	} elseif (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["view_type"]) || !$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["view_type"]) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["view_type"] = "all";
	}

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => $translate->_("Clerkship Evaluations"));

	if (($router) && ($router->initRoute())) {
		$PREFERENCES = preferences_load($MODULE);

		/**
		 * Add in LivePipe Controls. If we decide to use this, it should be added to index.php and admin.php instead.
		 */
		$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/livepipe/livepipe.js\" type=\"text/javascript\"></script>";
		$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/livepipe/window.js\" type=\"text/javascript\"></script>";

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
		
		if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
			$STEP = (int) trim($_GET["step"]);
		} elseif((isset($_POST["step"])) && ((int) trim($_POST["step"]))) {
			$STEP = (int) trim($_POST["step"]);
		} else {
			$STEP = 1;
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
		$url = ENTRADA_URL."/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}
}