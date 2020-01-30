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
 * 400: Unable to save response because no aquiz_id was provided.
 * 401: Unable to save response because no valid aquiz_id was provided.
 * 402: Attempted to submit a response to a question before the quiz release period.
 * 403: Attempted to submit a response to a question after the quiz release period.
 * 404: Attempted to submit a response to a question when they have already completed the quiz the maximum number of times.
 * 405: Unable to locate a current progress record.
 * 406: Unable to update the quiz_progress.updated_date field to the current timestamp.
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


if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif (!$COMMUNITY_LOAD) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
}
if ($RECORD_ID) {
	$query			= "	SELECT a.*, b.*
						FROM `attached_quizzes` AS a
						LEFT JOIN `quizzes` AS b
						ON a.`quiz_id` = b.`quiz_id`
						WHERE a.`aquiz_id` = ".$db->qstr($RECORD_ID)."
						AND b.`quiz_active` = '1'";
	$quiz_record	= $db->GetRow($query);
	if ($quiz_record) {
		/**
		 * Providing there is no release date, or the release date is in the past
		 * on both the quiz and the event, allow them to continue.
		 */
		if ((((int) $quiz_record["release_date"] === 0) || ($quiz_record["release_date"] <= time()))) {
			/**
			 * Providing there is no expiry date, or the expiry date is in the
			 * future on both the quiz and the event, allow them to continue.
			 */
			if ((((int) $quiz_record["release_until"] === 0) || ($quiz_record["release_until"] > time()))) {
				$completed_attempts = 0;

				$query				= "	SELECT *
										FROM `quiz_progress`
										WHERE `aquiz_id` = ".$db->qstr($RECORD_ID)."
										AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
										AND `progress_value` = 'complete'
										ORDER BY `updated_date` ASC";
				$completed_record	= $db->GetAll($query);
				if ($completed_record) {
					$completed_attempts = count($completed_record);
				}

				/**
				 * Providing they can still still make attempts at this quiz, allow them to continue.
				 */
				if (((int) $quiz_record["quiz_attempts"] === 0) || ($completed_attempts < $quiz_record["quiz_attempts"])) {
					/**
					 * Check to see if they currently have a quiz in progress,
					 * if the do, then use that qprogress_id.
					 */
					$query				= "	SELECT *
											FROM `quiz_progress`
											WHERE `aquiz_id` = ".$db->qstr($RECORD_ID)."
											AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
											AND `progress_value` = 'inprogress'
											ORDER BY `updated_date` ASC";
					$progress_record	= $db->GetRow($query);
					if ($progress_record) {
						$quiz_progress_array	= array (
													"updated_date" => time(),
													"updated_by" => $ENTRADA_USER->getID()
												);

						if ($db->AutoExecute("quiz_progress", $quiz_progress_array, "UPDATE", "`qprogress_id` = ".$db->qstr($progress_record["qprogress_id"]))) {
							if ((isset($_POST["qid"])) && ($tmp_input = clean_input($_POST["qid"], "int"))) {
								$qquestion_id = $tmp_input;

								if ((isset($_POST["rid"])) && ($tmp_input = clean_input($_POST["rid"], "int"))) {
									$qqresponse_id = $tmp_input;

								if (quiz_save_response($progress_record["qprogress_id"], $progress_record["aquiz_id"], $progress_record["content_id"], $progress_record["quiz_id"], $qquestion_id, $qqresponse_id, $QUIZ_TYPE)) {
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
									application_log("error", "A rid variable was not provided when attempting to submit a response to a question.");

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
							application_log("error", "Unable to update the quiz_progress.updated_date field when attempting to submit a question response for qprogress_id [".$progress_record["qprogress_id"]."] when attempting to continue with a quiz. Database said: ".$db->ErrorMsg());

							/**
							 * @exception 406: Unable to update the quiz_progress.updated_date field to the current timestamp.
							 */
							echo 406;
							exit;
						}
					} else {
						application_log("error", "Unable to locate a current quiz_progress record when attempting to submit a question response to aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["event_id"]."]).");

						/**
						 * @exception 405: Unable to locate a current progress record.
						 */
						echo 405;
						exit;
					}
				} else {
					application_log("error", "Someone attempted to submit a question response to aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["event_id"]."]) more than the total number of possible attempts [".$quiz_record["quiz_attempts"]."].");

					/**
					 * @exception 404: Attempted to submit a response to a question when they have already completed the quiz the maximum number of times.
					 */
					echo 404;
					exit;
				}
			} else {
				application_log("error", "Attempted to submit a response to a question after the quiz release period.");

				/**
				 * @exception 403: Attempted to submit a response to a question after the quiz release period.
				 */
				echo 403;
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
		application_log("error", "Failed to provide a valid aquiz_id identifier when attempting to save a response to a quiz question.");

		/**
		 * @exception 401: Unable to save response because no valid aquiz_id was provided.
		 */
		echo 401;
		exit;
	}
} else {
	application_log("error", "Failed to provide an aquiz_id identifier when attempting to save a response to a quiz question.");

	/**
	 * @exception 400: Unable to save response because no aquiz_id was provided.
	 */
	echo 400;
	exit;
}