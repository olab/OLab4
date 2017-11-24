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
	if ($FORM_ID) {
		$query = "	SELECT a.*
					FROM `evaluation_forms` AS a
					WHERE a.`eform_id` = ".$db->qstr($FORM_ID)."
					AND a.`form_active` = '1'";
		$form_record = $db->GetRow($query);
		if ($form_record && $ENTRADA_ACL->amIAllowed(new EvaluationFormResource($question_record["eform_id"], $question_record["organisation_id"], true), "update")) {
			if ($ALLOW_QUESTION_MODIFICATIONS) {
				/**
				 * Clears all open buffers so we can return a simple REST response.
				 */
				ob_clear_open_buffers();

				if ((isset($_POST["result"])) && ($tmp_input = clean_input($_POST["result"], array("trim", "notags")))) {
					/**
					 * Turns a formatted string (result[]=23&result[]12&result[]=26) into an array
					 * and places it in the $result variable.
					 */
					parse_str($tmp_input, $result);
					$order = 0;
					if ((isset($result["order"])) && (is_array($result["order"]))) {
						foreach ($result["order"] as $equestion_id) {
							echo $order++;
							$query = "SELECT `erubric_id` FROM `evaluation_rubric_questions` WHERE `equestion_id` = ".$db->qstr($equestion_id);
							$rubric_id = $db->GetOne($query);
							if ($rubric_id) {
								$query = "SELECT b.*, c.*, d.`organisation_id` FROM `evaluation_rubric_questions` AS a
											JOIN `evaluations_lu_questions` AS b
											ON a.`equestion_id` = b.`equestion_id`
											JOIN `evaluation_form_questions` AS c
											ON b.`equestion_id` = c.`equestion_id`
											AND c.`eform_id` = ".$db->qstr($FORM_ID)."
											JOIN `evaluation_forms` AS d
											ON c.`eform_id` = d.`eform_id`
											WHERE a.`erubric_id` = ".$db->qstr($rubric_id);
								$questions = $db->GetAll($query);
								foreach ($questions as $question) {
									if($ENTRADA_ACL->amIAllowed(new EvaluationFormResource($question_record["eform_id"], $question_record["organisation_id"], true), "update")) {
										if(!$db->AutoExecute("evaluation_form_questions", array("question_order" => (int) $order), "UPDATE", "`equestion_id` = ".$db->qstr($question["equestion_id"])." AND `eform_id` = ".$db->qstr($FORM_ID))) {

											$ERROR++;
											application_log("error", "Unable to update eform_id [".$FORM_ID."] question [".$equestion_id."] order [".$order."] when reordering evaluation form questions. Database said: ".$db->ErrorMsg());
										}
									} else {
										$ERROR++;
										application_log("error", "Unable to update eform_id [".$FORM_ID."] question [".$equestion_id."] order [".$order."] when reordering evaluation form questions due to a lack of permissions.");
									}
									echo "---".$order++;
								}
							} else {
								if($ENTRADA_ACL->amIAllowed(new EvaluationFormResource($question_record["eform_id"], $question_record["organisation_id"], true), "update")) {
									if(!$db->AutoExecute("evaluation_form_questions", array("question_order" => (int) $order), "UPDATE", "`equestion_id` = ".$db->qstr($equestion_id)." AND `eform_id` = ".$db->qstr($FORM_ID))) {

										$ERROR++;
										application_log("error", "Unable to update eform_id [".$FORM_ID."] question [".$equestion_id."] order [".$order."] when reordering evaluation form questions. Database said: ".$db->ErrorMsg());
									}
								} else {
									$ERROR++;
									application_log("error", "Unable to update eform_id [".$FORM_ID."] question [".$equestion_id."] order [".$order."] when reordering evaluation form questions due to a lack of permissions.");
								}
							}
						}

						if ($ERROR) {
							application_log("error", "Unable to update question order in evaluation form [".$form_record["eform_id"]."]. Database said: ".$db->ErrorMsg());

							/**
							 * @exception 405: There were errors in the update SQL execution, check the error_log.
							 */
							echo 405;
						} else {
							application_log("success", "Questions for evaluation form [".$form_record["eform_id"]."] were successfully reordered.");

							/**
							 * @exception 200: There were no errors, everything was updated successfully.
							 */
							echo 200;
						}
						exit;
					} else {
						/**
						 * @exception 404: Order array is empty, unable to process.
						 */
						echo 404;
						exit;
					}
				} else {
					/**
					 * @exception 403: Unable to find a valid order array.
					 */
					echo 403;
					exit;
				}
			} else {
				/**
				 * @exception 402: Cannot update question order, because it's in use.
				 */
				echo 402;
				exit;
			}
		} else {
			/**
			 * @exception 401: Cannot update question order because quiz could not be found.
			 */
			echo 401;
			exit;
		}
	} else {
		/**
		 * @exception 400: Cannot update question order becuase no id was provided.
		 */
		echo 400;
		exit;
	}
}
echo 0;
exit;