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
 * Secondary controller file used by the questions module within the evaluations module.
 * /admin/evaluations/questions
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "read", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/questions", "title" => "Manage Questions");

	$QUESTION_ID = 0;
	$ALLOW_QUESTION_MODIFICATIONS = false;
	$EVALUATION_TARGETS = array();

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
		$QUESTION_ID = $tmp_input;
	} elseif (isset($_POST["id"]) && ($tmp_input = clean_input($_POST["id"], array("trim", "int")))) {
		$QUESTION_ID = $tmp_input;
	}

	if (isset($_GET["form_id"]) && ($tmp_input = clean_input($_GET["form_id"], array("trim", "int")))) {
		$FORM_ID = $tmp_input;
	} elseif (isset($_POST["form_id"]) && ($tmp_input = clean_input($_POST["form_id"], array("trim", "int")))) {
		$FORM_ID = $tmp_input;
	}

	if (($router) && ($router->initRoute())) {
		/**
		 * Fetch a list of available evaluation question types that can be used.
		 */
		$query = "SELECT * FROM `evaluations_lu_questiontypes` WHERE `questiontype_active` = 1 ORDER BY `questiontype_title` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$QUESTION_TYPES[$result["questiontype_id"]] = $result;
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