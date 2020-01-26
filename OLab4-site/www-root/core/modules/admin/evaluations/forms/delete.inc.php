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
 * This file is used by administrators to disable a particular evaluation form.
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
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationform", "delete", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Delete Evaluation Forms");

	if (isset($_POST["delete"]) && is_array($_POST["delete"]) && !empty($_POST["delete"])) {
		$eform_ids = array();
		foreach ($_POST["delete"] as $eform_id) {
			$eform_id = clean_input($eform_id, "int");
			if ($eform_id) {
				$query	= "	SELECT a.*, b.`target_shortname`, b.`target_title`
							FROM `evaluation_forms` AS a
							LEFT JOIN `evaluations_lu_targets` AS b
							ON b.`target_id` = a.`target_id`
							WHERE a.`eform_id` = ".$db->qstr($eform_id)."
							AND a.`form_active` = '1'";
				$form_record = $db->GetRow($query);
				if ($form_record && $ENTRADA_ACL->amIAllowed(new EvaluationFormResource($form_record["eform_id"], $form_record["organisation_id"], true), "delete")) {
					$eform_ids[$eform_id] = $form_record;
				}
			}
		}

		$forms_selected = count($eform_ids);
		if ($forms_selected) {
			if (isset($_POST["confirmed"]) && ($_POST["confirmed"] == 1)) {
				$query = "UPDATE `evaluation_forms` SET `form_active` = '0' WHERE `eform_id` IN (".implode(", ", array_keys($eform_ids)).")";
				if ($db->Execute($query) && ($db->Affected_Rows() == $forms_selected)) {
					$url = ENTRADA_URL."/admin/evaluations/forms";
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully disabled the ".$forms_selected." selected evaluation form".(($forms_selected != 1) ? "s" : "").".<br /><br />You will now be redirected back to the evaluation form index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

					echo display_success();

					application_log("success", "Successfully disable evaluation forms [".implode(", ", array_keys($eform_ids))."].");
				}
			} else {
				?>
				<h1>Disable Evaluation Forms</h1>
				<div class="display-generic">
				If you proceed the following evaluation form<?php echo (($forms_selected != 1) ? "s" : ""); ?> will be <strong>disabled</strong> and no longer be usable in future evaluations.
				</div>

				<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/forms?section=delete" method="post">
				<input type="hidden" name="confirmed" value="1" />
				<table class="tableList" cellspacing="0" summary="List of Evaluation Forms To Disable">
				<colgroup>
					<col class="modified" />
					<col class="general" />
					<col class="title" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="general">Form Type</td>
						<td class="title">Evaluation Form Title</td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td></td>
						<td style="padding-top: 10px" colspan="2">
							<input type="submit" class="btn btn-danger" value="Confirm Disable" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
					foreach ($eform_ids as $result) {
						echo "<tr id=\"eform-".$result["eform_id"]."\">\n";
						echo "	<td><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["eform_id"]."\" checked=\"checked\" /></td>\n";
						echo "	<td>".html_encode($result["target_title"])."</td>\n";
						echo "	<td><a href=\"".ENTRADA_URL."/admin/evaluations/forms?section=edit&amp;id=".$result["eform_id"]."\">".html_encode($result["form_title"])."</a></td>\n";
						echo "</tr>\n";
					}
					?>
				</tbody>
				</table>
				</form>
				<?php
			}
		} else {
			add_error("In order to disable an evaluation form you must provide at least one valid identifier.");

			echo display_error();

			application_log("notice", "Failed to provide a valid identifier when attempting to disable an evaluation form.");
		}
	} else {
		add_error("In order to disable an evaluation form you must provide at least one a valid identifier.");

		echo display_error();

		application_log("notice", "Failed to provide an identifier when attempting to disable an evaluation form.");
	}
}