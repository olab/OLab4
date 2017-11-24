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
 * action. In this case, the requested action is to submit a response to an
 * answered quiz questions.
 *
 * 0	Unable to start processing request.
 * 200	There were no errors, the response was recorded successfully.
 * 400: Unable to save response because no aevaluation_id was provided.
 * 401: Unable to save response because no valid aevaluation_id was provided.
 * 402: Attempted to submit a response to a question before the quiz release period.
 * 404: Attempted to submit a response to a question when they have already completed the quiz the maximum number of times.
 * 405: Unable to locate a current progress record.
 * 406: Unable to update the evaluation_progress.updated_date field to the current timestamp.
 * 407: Quiz Question ID was not provided.
 * 408: Quiz Question Response ID was not provided.
 * 409: Unable to record a response to a question.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: save-response.inc.php 1170 2010-05-01 14:35:01Z simpson $
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EVALUATIONS"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
}
ob_clear_open_buffers();

if ($RECORD_ID) {
	$query			= "	SELECT *
						FROM `evaluations` AS a
						LEFT JOIN `evaluation_forms` AS b
						ON a.`eform_id` = b.`eform_id`
						LEFT JOIN `evaluation_progress` AS c
						ON a.`evaluation_id` = c.`evaluation_id`
						LEFT JOIN `evaluations_lu_targets` AS d
						ON b.`target_id` = d.`target_id`
						WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
						AND a.`evaluation_active` = '1'";
	$evaluation_record	= $db->GetRow($query);
	if ($evaluation_record) {
		/**
		 * Providing there is no release date, or the release date is in the past
		 * on both the quiz and the event, allow them to continue.
		 */
		if (((int) $evaluation_record["release_date"] === 0) || ($evaluation_record["release_date"] <= time())) {

			$query				= "	SELECT *
									FROM `evaluation_progress` AS a
									LEFT JOIN `evaluations` AS b
									ON a.`evaluation_id` = b.`evaluation_id`
									LEFT JOIN `evaluation_forms` AS c
									ON b.`eform_id` = c.`eform_id`
									WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
									AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
									AND a.`progress_value` = 'inprogress'
									ORDER BY a.`updated_date` ASC";
			$progress_record	= $db->GetRow($query);
			if ($progress_record) {
				$evaluation_progress_array	= array (
											"updated_date" => time(),
											"updated_by" => $ENTRADA_USER->getID()
										);

				if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "UPDATE", "`eprogress_id` = ".$db->qstr($progress_record["eprogress_id"]))) {
					if ((isset($_POST["qid"])) && ($tmp_input = clean_input($_POST["qid"], "int"))) {
						$qquestion_id = $tmp_input;

						if ((isset($_POST["rid"])) && ($tmp_input = clean_input($_POST["rid"], "int"))) {
							$qqresponse_id = $tmp_input;
                        } elseif ((isset($_POST["comments"])) && clean_input($_POST["comments"], array("trim", "notags"))) {
                            $qqresponse_id = 0;
                        }
                        if (isset($qqresponse_id)) {
							if ((isset($_POST["comments"])) && clean_input($_POST["comments"], array("trim", "notags"))) {
								$comments = clean_input($_POST["comments"], array("trim", "notags"));
							} else {
								$comments = NULL;
							}
							if (Classes_Evaluation::evaluation_save_response($progress_record["eprogress_id"], $progress_record["eform_id"], $qquestion_id, $qqresponse_id, $comments)) {
								echo 200;
								exit;
							} else {
								/**
								 * @exception 409: Unable to record a response to a question.
								 */
								echo 409;
								exit;
							}
						} else {
							application_log("error", "A rid variable was not provided when attempting to submit a response to a question with no comments attached.");

							/**
							 * @exception 408: Quiz Question Response ID was not provided.
							 */
							echo 408;
							exit;
						}
					} else {
						application_log("error", "A qid variable was not provided when attempting to submit a response to a question.");

						/**
						 * @exception 407: Quiz Question ID was not provided.
						 */
						echo 407;
						exit;
					}
				} else {
					application_log("error", "Unable to update the evaluation_progress.updated_date field when attempting to submit a question response for qprogress_id [".$progress_record["qprogress_id"]."] when attempting to continue with a quiz. Database said: ".$db->ErrorMsg());

					/**
					 * @exception 406: Unable to update the evaluation_progress.updated_date field to the current timestamp.
					 */
					echo 406;
					exit;
				}
			} else {
				application_log("error", "Unable to locate a current evaluation_progress record when attempting to submit a question response to aevaluation_id [".$RECORD_ID."] (evaluation_id [".$evaluation_record["evaluation_id"]."] / event_id [".$evaluation_record["event_id"]."]).");

				/**
				 * @exception 405: Unable to locate a current progress record.
				 */
				echo 405;
				exit;
			}
		} else {
			application_log("error", "Attempted to submit a response to a question before the quiz release period.");

			/**
			 * @exception 402: Attempted to submit a response to a question before the quiz release period.
			 */
			echo 402;
			exit;
		}
	} else {
		application_log("error", "Failed to provide a valid aevaluation_id identifier when attempting to save a response to a quiz question.");

		/**
		 * @exception 401: Unable to save response because no valid aevaluation_id was provided.
		 */
		echo 401;
		exit;
	}
} else {
	application_log("error", "Failed to provide an aevaluation_id identifier when attempting to save a response to a quiz question.");

	/**
	 * @exception 400: Unable to save response because no aevaluation_id was provided.
	 */
	echo 400;
	exit;
}