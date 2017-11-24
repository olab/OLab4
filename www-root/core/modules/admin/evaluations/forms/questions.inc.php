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
 * Tertiary controller file used by the questions component of the forms component, within the evaluations module.
 * /admin/evaluations/forms/questions
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationformquestion", "update", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	if ($FORM_ID) {
		$query = "	SELECT a.*
					FROM `evaluation_forms` AS a
					WHERE a.`eform_id` = ".$db->qstr($FORM_ID)."
					AND a.`form_active` = '1'";
		$FORM_RECORD = $db->GetRow($query);
		if ($FORM_RECORD && $ENTRADA_ACL->amIAllowed(new EvaluationFormResource($FORM_ID, $FORM_RECORD["organisation_id"], true), "update")) {
			define("IN_QUESTION", true);

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/forms?section=edit&amp;id=".$FORM_ID, "title" => limit_chars($FORM_RECORD["form_title"], 32));

			if (($router) && ($router->initRoute())) {
				echo "<div class=\"content-small\">".clean_input($EVALUATION_TARGETS[$FORM_RECORD["target_id"]]["target_title"], array("trim", "encode"))." Form</div>";
				echo "<h1 class=\"form-title\">".html_encode($FORM_RECORD["form_title"])."</h1>";
				echo "<div style=\"margin-bottom: 15px\">".clean_input($FORM_RECORD["form_description"], array("trim"))."</div>";

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
		} else {
			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/evaluations/forms\\'', 5000)";

			application_log("notice", "Someone attempted to manage an evaluation form that does not exist [".$FORM_ID."]. Database said: ".$db->ErrorMsg());

			$ERROR++;
			$ERRORSTR[] = "The evaluation form you are trying to manage does not exist in the system.";

			echo display_error();
		}
	} else {
		application_log("notice", "Someone attempted to manage an evaluation form question without providing a form id.");

		header("Location: ".ENTRADA_URL."/admin/evaluations/forms");
		exit;
	}
}