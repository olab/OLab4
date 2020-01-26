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
 * This file looks a bit different because it is called only by AJAX requests
 * and returns status codes based on it's ability to complete the requested
 * action. In this case, the requested action is to re-order quiz questions.
 * 
 * 0	Unable to start processing request.
 * 200	There were no errors, everything was updated successfully.
 * 400	Cannot update question order becuase no id was provided.
 * 401	Cannot update question order because quiz could not be found.
 * 402	Cannot update question order, because it's in use.
 * 403	Unable to find a valid order array.
 * 404	Order array is empty, unable to process.
 * 405	There were errors in the update SQL execution, check the error_log.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
 * @version $Id: add.inc.php 317 2009-01-19 19:26:35Z simpson $
 * 
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationformquestion", "update", false)) {

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");

	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} else {
	ob_clear_open_buffers();
	if (isset($_GET["efquestion_id"]) && ($tmp_input = clean_input($_GET["efquestion_id"], "int"))) {
		$EFQUESTION_ID = $tmp_input;
	} elseif (isset($_POST["efquestion_id"]) && ($tmp_input = clean_input($_POST["efquestion_id"], "int"))) {
		$EFQUESTION_ID = $tmp_input;
	} else {
		$EFQUESTION_ID = false;
	}
	
	if (isset($_POST["step"]) && ((int)$_POST["step"])) {
		$STEP = clean_input($_POST["step"], "int");
	}
	if ($FORM_ID && $EFQUESTION_ID) {
		switch ($STEP) {
			case 2 :
				/** 
				* Fetch the Clinical Presentation details.
				*/
				$clinical_presentations_list	= array();
				$clinical_presentations			= array();

				$results = fetch_clinical_presentations();
				if ($results) {
					foreach ($results as $result) {
						$clinical_presentations_list[$result["objective_id"]] = $result["objective_name"];
					}
				} else {
					$clinical_presentations_list = false;
				}
				if (isset($_POST["clinical_presentations_submit"]) && $_POST["clinical_presentations_submit"]) {
					if ((isset($_POST["clinical_presentations"])) && (is_array($_POST["clinical_presentations"])) && (count($_POST["clinical_presentations"]))) {
						foreach ($_POST["clinical_presentations"] as $objective_id) {
							if ($objective_id = clean_input($objective_id, array("trim", "int"))) {
									$query	= "SELECT a.`objective_id`
												FROM `global_lu_objectives` AS a
												JOIN `objective_organisation` AS b
												WHERE a.`objective_id` = ".$db->qstr($objective_id)."
												AND a.`objective_active` = '1'
												AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
												ORDER BY a.`objective_order` ASC";
								$result	= $db->GetRow($query);
								if ($result) {
									$clinical_presentations[$objective_id] = $clinical_presentations_list[$objective_id];
								}
							}
						}
					} else {
						$clinical_presentations = array();
					}
				}
				/**
				 * Insert Clinical Presentations.
				 */
				$query = "DELETE FROM `evaluation_form_question_objectives` WHERE `efquestion_id` = ".$db->qstr($EFQUESTION_ID);
				if ($db->Execute($query)) {
					if ((is_array($clinical_presentations)) && (count($clinical_presentations))) {
						foreach ($clinical_presentations as $objective_id => $presentation_name) {
							if (!$db->AutoExecute("evaluation_form_question_objectives", array("efquestion_id" => $EFQUESTION_ID, "objective_id" => $objective_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
								$ERROR++;
								application_log("error", "Unable to insert a new clinical presentation to the database when editing an evaluation form question. Database said: ".$db->ErrorMsg());
							}
						}
					}
				}
				header("Location: ".ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID."&success=".($ERROR ? "false" : "true"));
				
			break;
			case 1 :
			default :
				/**
				 * Fetch the Clinical Presentation details.
				 */
				$clinical_presentations_list = array();
				$clinical_presentations = array();


				$results = Classes_Evaluation::getClinicalPresentations();
				if ($results) {
					foreach ($results as $result) {
						$clinical_presentations_list[$result["objective_id"]] = $result["objective_name"];
					}
				}

				if (isset($_POST["clinical_presentations_submit"]) && $_POST["clinical_presentations_submit"]) {
					if (((isset($_POST["clinical_presentations"])) && (is_array($_POST["clinical_presentations"])) && (count($_POST["clinical_presentations"])))) {
						foreach ($_POST["clinical_presentations"] as $objective_id) {
							if ($objective_id = clean_input($objective_id, array("trim", "int"))) {
								$query	= "SELECT a.`objective_id`
											FROM `global_lu_objectives` AS a
											JOIN `evaluation_form_question_objectives` AS b
											ON b.`efquestion_id` = ".$db->qstr($EFQUESTION_ID)."
											AND a.`objective_id` = b.`objective_id`
											JOIN `objective_organisation` AS c
											ON a.`objective_id` = c.`objective_id`
											WHERE a.`objective_id` = ".$db->qstr($objective_id)."
											AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
											AND b.`objective_type` = 'event'
											AND a.`objective_active` = '1'";
								$result	= $db->GetRow($query);
								if ($result) {
									$clinical_presentations[$objective_id] = $clinical_presentations_list[$objective_id];
								}
							}
						}
					} else {
						$clinical_presentations = array();
					}
					if ($clinical_presentations && count($clinical_presentations)) {
						$query = "DELETE FROM `evaluation_form_question_objectives` WHERE `efquestion_id` = ".$db->qstr($EFQUESTION_ID);
						$db->Execute($query);
						foreach ($clinical_presentations as $clinical_presentation) {
							$evaluation_form_question_objective = array("efquestion_id" => $EFQUESTION_ID,
																		"objective_id" => $clinical_presentation["objective_id"],
																		"updated_date" => time(),
																		"updated_by" => $ENTRADA_USER->getID());
							if (!$db->AutoExecute("evaluation_form_question_objectives", $evaluation_form_question_objective, "INSERT")) {
								application_log("error", "Unable to insert a new evalution_form_question_objectives record while updating an evaluation form question's associated clinical presentations. Database said: ".$db->ErrorMsg());
							} else {
								history_log($EFQUESTION_ID, "Updated evaluation form question clinical presentations.");
							}
						}
					}
				} else {
					$query = "	SELECT `objective_id`
								FROM `evaluation_form_question_objectives`
								WHERE `efquestion_id` = ".$db->qstr($EFQUESTION_ID);
					$results = $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$clinical_presentations[$result["objective_id"]] = $clinical_presentations_list[$result["objective_id"]];
						}
					}
				}
				?>
				<h2>Associated MCC Presentations</h2>
				<form method="POST" action="<?php echo ENTRADA_URL."/admin/evaluations/forms/questions?section=api-objectives&id=".$FORM_ID."&efquestion_id=".$EFQUESTION_ID; ?>" onsubmit="selIt()">
					<input type="hidden" name="step" value="2" />
					<select class="multi-picklist" id="PickList" name="clinical_presentations[]" multiple="multiple" size="5" style="width: 100%; margin-bottom: 5px">
					<?php
					if ((is_array($clinical_presentations)) && (count($clinical_presentations))) {
						foreach ($clinical_presentations as $objective_id => $presentation_name) {
							echo "<option value=\"".(int) $objective_id."\">".html_encode($presentation_name)."</option>\n";
						}
					}
					?>
					</select>
					<input type="hidden" value="1" name="clinical_presentations_submit" />
					<div style="float: right; display: inline">
						<input type="button" id="clinical_presentations_list_remove_btn" class="btn btn-danger" onclick="delIt()" value="Remove" />
						<input type="button" id="clinical_presentations_list_add_btn" class="btn btn-success" onclick="addIt()" value="Add" />
					</div>
					<div id="clinical_presentations_list" style="clear: both; padding-top: 3px;">
						<h2>Clinical Presentations List</h2>
						<select class="multi-picklist" id="SelectList" name="other_event_objectives_list" multiple="multiple" size="15" style="width: 100%">
						<?php
						foreach ($clinical_presentations_list as $objective_id => $presentation_name) {
							if (!array_key_exists($objective_id, $clinical_presentations)) {
								echo "<option value=\"".(int) $objective_id."\">".html_encode($presentation_name)."</option>\n";
							}
						}
						?>
						</select>
					</div>
					<div id="scripts-on-open" style="display: none;">
					if ($('PickList')) {
						$('PickList').observe('keypress', function(event) {
							if (event.keyCode == Event.KEY_DELETE) {
								delIt();
							}
						});
					}

					if ($('SelectList')) {
						$('SelectList').observe('keypress', function(event) {
							if (event.keyCode == Event.KEY_RETURN) {
								addIt();
							}
						});
					}
					</div>
					<hr style="margin-top: 60px;" />
					<div id="footer">
						<button id="close-button" class="btn" onclick="Control.Modal.close()">Cancel</button>
						<input style="float: right;" class="btn btn-primary" type="submit" value="Save Changes" />
					</div>
				</form>
				<?php
			break;
		}
	} else {
		/**
		 * @exception 400: Cannot update question order becuase no id was provided.
		 */
		echo 400;
		exit;
	}
};
exit;