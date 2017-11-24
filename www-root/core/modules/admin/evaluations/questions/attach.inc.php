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
 * Please add a description here about what this file does.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed("evaluationform", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Delete Evaluation Questions");

	echo "<h1>Attach Evaluation Questions</h1>";

	$QUESTION_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if(((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) && (!isset($_GET["id"]) || !$_GET["id"])) {
				header("Location: ".ENTRADA_URL."/admin/evaluations/questions");
				exit;
			} else {
				foreach($_POST["checked"] as $question_id) {
					$question_id = (int) trim($question_id);
					if($question_id) {
						$QUESTION_IDS[] = $question_id;
					}
				}
				if (isset($_POST["form_id"]) && ($eform_id = clean_input($_POST["form_id"], "int"))) {
					$PROCESSED["eform_id"] = $eform_id;
				} elseif (isset($_GET["form_id"]) && ($eform_id = clean_input($_GET["form_id"], "int"))) {
					$PROCESSED["eform_id"] = $eform_id;
				}
				
				if (!@count($QUESTIONIDS) && isset($_GET["id"]) && ($equestion_id = clean_input($_GET["id"], "int"))) {
					$QUESTION_IDS[] = $equestion_id;
				}
				
				if (!isset($PROCESSED["eform_id"]) && $STEP == 2) {
					$ERROR++;
					$ERRORSTR[] = "No evaluation form identifier was provided to attach to. Please ensure you select an evaluation form from this page before trying again.";
				}
				
				if(!@count($QUESTION_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid evaluation question identifiers provided to attach. Please ensure that you access this section through the evaluation index.";
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$total_attached = 0;
			$query = "SELECT * FROM `evaluation_forms` WHERE `eform_id` = ".$db->qstr($PROCESSED["eform_id"]);
			$form = $db->GetRow($query);
			if ($form) {
				$new_question_ids_array = $QUESTION_IDS;
				foreach ($QUESTION_IDS as $question_id) {
					$question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($question_id);
					$query = "SELECT `erubric_id` FROM `evaluation_rubric_questions` WHERE `equestion_id` = ".$db->qstr($question_id);
					$rubric_id = $db->GetOne($query);
					if ($rubric_id) {
						$old_question_ids_array = $new_question_ids_array;
						$new_question_ids_array = array();
						foreach($old_question_ids_array as $temp_question_id) {
							$new_question_ids_array[] = $temp_question_id;
							if ($temp_question_id == $question_id) {
								$query = "SELECT `equestion_id` FROM `evaluation_rubric_questions` 
											WHERE `erubric_id` = ".$db->qstr($rubric_id)."
											AND `equestion_id` <> ".$db->qstr($question_id);
								$new_questions = $db->GetAll($query);
								if ($new_questions) {
									foreach ($new_questions as $question) {
										$new_question_ids_array[] = $question["equestion_id"];
									}
								}
							}
						}
					}
				}
				$QUESTION_IDS = $new_question_ids_array;
				foreach($QUESTION_IDS as $question_id) {
					if($question_id = (int) $question_id) {
						$PROCESSED["equestion_id"] = $question_id;
						/**
						 * Check to see if this evaluation question exists.
						 */
						$query = "SELECT * FROM `evaluations_lu_questions`
									WHERE `equestion_id` = ".$db->qstr($question_id);
						$question = $db->GetRow($query);
						if ($question) {
							/**
							 * Check to see if the evaluation question is already attached to the chosen form.
							 */
							$query = "SELECT `equestion_id` FROM `evaluation_form_questions` WHERE `equestion_id` = ".$db->qstr($question_id)." AND `eform_id` = ".$db->qstr($PROCESSED["eform_id"]);
							$question_found = $db->GetOne($query);
							if (!$question_found) {
								$query = "SELECT `question_order` FROM `evaluation_form_questions`
											WHERE `eform_id` = ".$db->qstr($PROCESSED["eform_id"])."
											ORDER BY `question_order` DESC";
								$question_order = $db->GetOne($query);
								$PROCESSED["question_order"] = ($question_order ? $question_order : 0) + 1;
								$PROCESSED["allow_comments"] = $question["allow_comments"];
								if ($db->AutoExecute("evaluation_form_questions", $PROCESSED, "INSERT")) {
									$total_attached++;
								} else {
									$ERROR++;
									$ERRORSTR[] = "Problem encountered while attempting to attach the selected questions to the evaluation form [".$form["form_title"]."].";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "Problem encountered while attempting to attach the selected questions to the evaluation form [".$form["form_title"]."]. Please ensure you only attempt to attach questions to the form which aren't already attached to it.";
							}
						}
					}
				}

				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$PROCESSED["eform_id"]."\\'', 5000)";

				if($total_attached) {
					$SUCCESS++;
					$SUCCESSSTR[$SUCCESS]  = "You have successfully attached ".$total_removed." evaluation question".(($total_attached != 1) ? "s" : "")." to the selected evaluation form [".$form["form_title"]."]:";
					$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the evaluation form edit page in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$PROCESSED["eform_id"]."\">click here</a> if you do not wish to wait.";

					echo display_success();

					application_log("success", "Successfully attached evaluation question ids: [".implode(", ", $QUESTION_IDS)."] to the form [".$PROCESSED["eform_id"]."].");
				} else {
					$ERROR++;
					$ERRORSTR[] = "Unable to attach the chosen evaluation questions to the selected evaluation form.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";

					application_log("error", "Failed to attach all evaluation questions from the attach request. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} else {
				$total_questions = count($QUESTION_IDS);
				$question_ids_string = "";
				foreach ($QUESTION_IDS as $question_id) {
					$question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($question_id);
					$query = "SELECT `erubric_id` FROM `evaluation_rubric_questions` WHERE `equestion_id` = ".$db->qstr($question_id);
					$rubric_id = $db->GetOne($query);
					if ($rubric_id) {
						$query = "SELECT `equestion_id` FROM `evaluation_rubric_questions` 
									WHERE `erubric_id` = ".$db->qstr($rubric_id)."
									AND `equestion_id` <> ".$db->qstr($question_id);
						$new_questions = $db->GetAll($query);
						if ($new_questions) {
							foreach ($new_questions as $question) {
								$question_ids_string .= ", ".$db->qstr($question["equestion_id"]);
							}
						}
					}
				}
				$query = "SELECT a.*, b.`questiontype_shortname`, b.`questiontype_title`
							FROM `evaluations_lu_questions` AS a
							JOIN `evaluations_lu_questiontypes` AS b
							ON a.`questiontype_id` = b.`questiontype_id`
							LEFT JOIN `evaluation_rubric_questions` AS c
							ON a.`equestion_id` = c.`equestion_id`
							WHERE a.`question_active` = 1
							AND a.`equestion_id` IN (".$question_ids_string.")
							GROUP BY a.`equestion_id`
							ORDER BY c.`erubric_id`, c.`question_order`, b.`questiontype_id`";
				$results	= $db->GetAll($query);
				if($results) {
					$question_controls = Classes_Evaluation::getQuestionControlsArray($results);
					$HEAD[] = "<script type=\"text/javascript\">
					var question_controls = ".json_encode($question_controls).";
					var modalDialog;
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
					});

					function openDialog (equestion_id) {
						if (equestion_id) {
							modalDialog.container.update('<div id=\"form-questions-list\">'+question_controls[equestion_id]+'</div>');
							modalDialog.open();
						} else {
							modalDialog.open();
						}
					}
					
					function loadForm (form_id) {
						$('reloadForm').action = '".ENTRADA_URL."/admin/evaluations/questions?section=attach&form_id='+form_id;
						$('reloadForm').submit();
					}
					</script>";
					echo display_notice(array("Please select a form from the dropdown below, then review the following evaluation question".(($total_questions != 1) ? "s" : "")." to ensure that you wish to attach ".(($total_questions != 1) ? "them" : "it")." to the selected form."));
					?>
					<form id="reloadForm" name="reloadForm" action="<?php echo ENTRADA_URL."/admin/evaluations/questions?section=attach".(isset($FORM_ID) && $FORM_ID ? "&form_id=".$FORM_ID : ""); ?>" method="post">
						<?php
							foreach ($results as $result) {
								echo "<input type=\"hidden\" name=\"checked[]\" value=\"".$result["equestion_id"]."\" />\n";
							}
						?>
					</form>
					<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?section=attach&amp;step=2" method="post">
					<?php
					$query = "SELECT * FROM `evaluation_forms` WHERE `form_active` = 1";
					$forms = $db->GetAll($query);
					if ($forms) {
						echo "<label for=\"form_id\">Evaluation Form: </label>";
						echo "<select name=\"form_id\" id=\"form_id\" onchange=\"loadForm(this.options[this.selectedIndex].value)\">\n";
						echo "	<option value=\"0\">--- Select a form to attach the chosen questions to ---</option>";
						foreach ($forms as $form) {
							$query = "SELECT COUNT(`eform_id`) FROM `evaluations` WHERE `eform_id` = ".$db->qstr($form["eform_id"]);
							$used = $db->GetOne($query);
							if (!$used) {
								echo "<option value=\"".$form["eform_id"]."\"".($PROCESSED["eform_id"] == $form["eform_id"] ? " selected=\"selected\"" : "").">".$form["form_title"]."</option>";
							}
						}
						echo "</select>\n";
						echo "<br /><br />\n";
					}
					?>
					<table class="tableList" cellspacing="0" summary="List of Events">
					<colgroup>
						<col class="modified" />
						<col class="title" />
						<col class="type-title" />
						<col class="actions" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="title">Question</td>
							<td class="type-title">Question Type</td>
							<td class="actions" style="font-size: 12px">&nbsp;</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input type="submit" class="btn btn-primary" value="Attach Questions" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if (isset($FORM_ID) && $FORM_ID) {
							$query = "SELECT `equestion_id` FROM `evaluation_form_questions` WHERE `eform_id` = ".$db->qstr($FORM_ID);
							$used_questions = $db->GetAll($query);
							$used_ids = array();
							foreach ($used_questions as $used_question) {
								$used_ids[] = $used_question["equestion_id"];
							}
						}
						foreach($results as $result) {
							if (isset($question_controls[$result["equestion_id"]])) {
								echo "<tr id=\"equestion-".$result["equestion_id"]."\" class=\"equestion\">\n";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["equestion_id"]."\" ".(isset($used_ids) && $used_ids && array_search($result["equestion_id"], $used_ids) !== false ? "disabled=\"disabled\"" : "checked=\"checked\"")." /></td>\n";
								echo "	<td class=\"title\"><div class=\"evaluation-questions-list\">".$question_controls[$result["equestion_id"]]."</div></td>\n";
								echo "	<td class=\"type-title\">".html_encode($result["questiontype_title"])."</td>\n";
								echo "	<td class=\"actions\"><img style=\"cursor: pointer;\" height=\"16\" width=\"16\" src=\"".ENTRADA_URL."/images/magnify.gif\" onclick=\"openDialog(".$result["equestion_id"].")\" alt=\"View Evaluation Question Full Size\" title=\"View Evaluation Question Full Size\" /> <a href=\"".ENTRADA_URL."/admin/evaluations/questions?section=edit&amp;id=".$result["equestion_id"]."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Edit Evaluation Question\" title=\"Edit Evaluation Question\" border=\"0\" /></a></td>\n";
								echo "</tr>\n";
								if (isset($used_ids) && $used_ids && array_search($result["equestion_id"], $used_ids) !== false) {
									echo "<tr>\n";
									echo "	<td>&nbsp;</td>\n";
									echo "	<td colspan=\"3\">";
									echo "		<div id=\"display-notice-box\" class=\"display-notice\" style=\"white-space: normal;\">\n";
									echo "			<ul>\n";
									echo "				<li>This question is already attached to the selected form, and will not be added now. Please select a different form if you wish to add this question, or create a new question to attach to this form.</li>\n";
									echo "			</ul>\n";
									echo "		</div>\n";
									echo "	</td>\n";
									echo "</tr>\n";
								}
							}
						}
						?>
					</tbody>
					</table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());

					header("Location: ".ENTRADA_URL."/admin/evaluations/questions");
					exit;
				}
			}
		break;
	}
}