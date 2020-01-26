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
 * This file displays the list of all evaluation questions available in the system.
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
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationquestion", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	?>
	<h1>Manage Evaluation Questions</h1>
    <div style="float: right">
        <a href="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?section=add"  class="btn btn-small btn-success pull-right cursor-pointer space-below"><i class="icon-plus-sign icon-white"></i> Create New Evaluation Question</a>
    </div>
	<div class="clear"></div>
	<a id="false-link" href="#placeholder"></a>
	<div id="placeholder" style="display: none"></div>
	<?php
	$results = Classes_Evaluation::getAuthorEvaluationQuestions();
	if ($results) {
		if (isset($FORM_ID) && $FORM_ID) {
			$query	= "SELECT COUNT(*) AS `total` FROM `evaluations` WHERE `eform_id` = ".$db->qstr($FORM_ID);
			$result = $db->GetRow($query);
			if ((!$result) || ((int) $result["total"] === 0)) {
				$query = "SELECT `form_title` FROM `evaluation_forms` WHERE `eform_id` = ".$db->qstr($FORM_ID);
				$form_title = $db->GetOne($query);
				$query = "SELECT `equestion_id` FROM `evaluation_form_questions` WHERE `eform_id` = ".$db->qstr($FORM_ID);
				$used_questions = $db->GetAll($query);
				$used_ids = array();
				foreach ($used_questions as $used_question) {
					$used_ids[] = $used_question["equestion_id"];
				}
				$temp_results = $results;
				$results = array();
				foreach ($temp_results as $temp_result) {
					if (array_search($temp_result["equestion_id"], $used_ids) === false) {
						$results[] = $temp_result;
					}
				}
				add_notice("To attach evaluation questions to the selected form [".$form_title."], you may either click the 'paperclip' image to add one question, or select the checkboxes on the line of each question that you wish to add, then press the 'Attach Selected' button at the bottom of the page.");
				echo display_notice();
			} else {
				$FORM_ID = false;
			}
		}
		$question_controls = Classes_Evaluation::getQuestionControlsArray($results);
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
		$HEAD[] = "<script type=\"text/javascript\">
		var question_controls = ".json_encode($question_controls).";
		var modalDialog;
		var oTable;
		jQuery(document).ready(function() {
			modalDialog = new Control.Modal($('false-link'), {
				position:		'center',
				overlayOpacity:	0.75,
				closeOnClick:	'overlay',
				className:		'default-tooltip',
				fade:			true,
				fadeDuration:	0.30,
				width: 755
			});
			oTable = jQuery('#evaluationquestions').dataTable(
				{    
					'sPaginationType': 'full_numbers',
					'bInfo': false,
                    'bAutoWidth': false
				}
			);
		});

		function openDialog (equestion_id) {
			if (equestion_id) {
				modalDialog.container.update('<div id=\"form-questions-list\">'+question_controls[equestion_id]+'</div>');
				modalDialog.open();
			} else {
				modalDialog.open();
			}
		}
		</script>";
		?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?<?php echo replace_query(array("section" => "attach")); ?>" method="post">
		<table class="tableList" id="evaluationquestions" cellspacing="0" summary="List of Evaluation Questions">
		<colgroup>
			<col class="modified" />
            <col class="actions" />
			<col class="title" />
			<col class="type-title" />
			<col class="actions" />
		</colgroup>
		<thead>
			<tr>
				<td class="modified">&nbsp;</td>
				<td class="actions">Question Code</td>
				<td class="title">Question</td>
				<td class="type-title">Question Type</td>
				<td class="actions">&nbsp;</td>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td></td>
				<td style="padding-top: 10px" colspan="2">
					<input type="submit" class="btn btn-primary" value="Attach Selected" />
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			foreach ($results as $result) {
				if (isset($question_controls[$result["equestion_id"]])) {
					echo "<tr id=\"equestion-".$result["equestion_id"]."\">\n";
					echo "	<td><input type=\"checkbox\" name=\"checked[]\" class=\"attach\" value=\"".$result["equestion_id"]."\" /></td>\n";
					echo "	<td><a href=\"".ENTRADA_URL."/admin/evaluations/questions?section=edit&amp;id=".$result["equestion_id"]."\">".html_encode($result["question_code"])."</a></td>\n";
					echo "	<td><div class=\"mini-evaluation-questions-list\">".$question_controls[$result["equestion_id"]]."</div></td>\n";
					echo "	<td><a href=\"".ENTRADA_URL."/admin/evaluations/questions?section=edit&amp;id=".$result["equestion_id"]."\">".html_encode($result["questiontype_title"])."</a></td>\n";
					echo "	<td><img style=\"cursor: pointer;\" height=\"16\" width=\"16\" src=\"".ENTRADA_URL."/images/magnify.gif\" onclick=\"openDialog(".$result["equestion_id"].")\" alt=\"View Evaluation Question Full Size\" title=\"View Evaluation Question Full Size\" /> <a href=\"".ENTRADA_URL."/admin/evaluations/questions?section=edit&amp;id=".$result["equestion_id"]."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Edit Evaluation Question\" title=\"Edit Evaluation Question\" border=\"0\" /></a> <a href=\"".ENTRADA_URL."/admin/evaluations/questions?section=attach&amp;id=".$result["equestion_id"].(isset($FORM_ID) && (int)$FORM_ID ? "&amp;form_id=".((int)$FORM_ID) : "")."\"><img src=\"".ENTRADA_URL."/images/attachment.gif\" width=\"16\" height=\"16\" alt=\"Attach Evaluation Question to Form\" title=\"Attach Evaluation Question to Form\" border=\"0\" /></a></td>\n";
					echo "</tr>\n";
				}
			}
			?>
		</tbody>
		</table>
		</form>
		<?php
	} else {
		?>
		<div class="display-generic">
			The Manage Questions tool allows you to create and manage questions that can be attached to forms to be electronically distributed to evaluators.
			<br /><br />
			Creating evaluation questions is easy; to begin simply click the <strong>Create New Evaluation Question</strong> link above and follow the on-screen instructions.
		</div>
		<?php
	}
}