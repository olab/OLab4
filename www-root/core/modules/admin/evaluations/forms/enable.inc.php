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
 * This file is used by administrators to enable a particular evaluation form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($FORM_ID) {
		$query = "	SELECT a.*
					FROM `evaluation_forms` AS a
					WHERE a.`eform_id` = ".$db->qstr($FORM_ID)."
					AND a.`form_active` = '0'";
		$form_record = $db->GetRow($query);
		if ($form_record && $ENTRADA_ACL->amIAllowed(new EvaluationFormResource($form_record["eform_id"], $form_record["organisation_id"], true), "update")) {
			if ($db->AutoExecute("evaluation_forms", array("form_active" => 1), "UPDATE", "`eform_id` = ".$FORM_ID)) {
				$url = ENTRADA_URL."/admin/evaluations/forms";
				$SUCCESS++;
				$SUCCESSSTR[] = "You have successfully activated this evaluation form.<br /><br />You will now be redirected back to the evaluation form index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

				$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

				echo display_success();

				application_log("success", "Successfully activated evaluation form [".$FORM_ID."].");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to activate an evaluation form you must provide a valid identifier.";

			echo display_error();

			application_log("notice", "Failed to provide a valid form identifer [".$FORM_ID."] when attempting to activate an evaluation form.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to activate an evaluation form you must provide a valid identifier.";

		echo display_error();

		application_log("notice", "Failed to provide am identifier when attempting to activate an evaluation form.");
	}
}