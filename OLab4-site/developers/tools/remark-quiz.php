#!/usr/bin/php
<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.	If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Unit: Medical Education Technology Unit
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    realpath(dirname(__FILE__) . "/includes"),
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

@ini_set("auto_detect_line_endings", 1);
@ini_set("magic_quotes_runtime", 0);
set_time_limit(0);

if((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

require_once("autoload.php");

require_once("config.inc.php");
require_once("dbconnection.inc.php");
require_once("functions.inc.php");
$ACTION			= ((isset($_SERVER["argv"][1]) && trim($_SERVER["argv"][1])) ? trim($_SERVER["argv"][1]) : false);
if (!isset($ACTION) || !$ACTION || $ACTION == "-usage") {
	echo "\nUsage: remark-quiz.php [mode] [quiz_id] [question number]";
	echo "\n   -usage             	Brings up this help screen.";
	echo "\n   -remark            	Update the quiz_progress for all instances of the quiz referred to by the quiz_id supplied.\n";
	echo "\n   -delete            	Deletes the quiz question in the quiz_id and question number supplied, then updates the quiz_progress for all instances of the quiz referred to by the quiz_id from the quiz question row.\n";
} elseif ($ACTION == "-remark") {
	$RECORD_ID			= ((isset($_SERVER["argv"][2]) && ((int) $_SERVER["argv"][2])) ? (int) $_SERVER["argv"][2] : false);
	if (isset($RECORD_ID) && $RECORD_ID) {
		$query				= "	SELECT * FROM `quiz_progress`
								WHERE `quiz_id` = ".$db->qstr($RECORD_ID)."
								ORDER BY `updated_date` ASC";
		$progress_records	= $db->GetAll($query);
		if ($progress_records) {
			foreach ($progress_records as $progress_record) {
				/**
				 * Get a list of all of the questions in this quiz so we
				 * can run through a clean set of questions.
				 */
				$query		= "	SELECT a.*
								FROM `quiz_questions` AS a
								WHERE a.`quiz_id` = ".$db->qstr($progress_record["quiz_id"])."
								ORDER BY a.`question_order` ASC";
				$questions	= $db->GetAll($query);

				$qprogress_id		= $progress_record["qprogress_id"];
				$quiz_score			= 0;
				$quiz_value			= 0;

				$PROCESSED = quiz_load_progress($qprogress_id);

				foreach ($questions as $question) {
					$question_correct	= false;
					$question_points	= 0;

					$query		= "	SELECT a.*
									FROM `quiz_question_responses` AS a
									WHERE a.`qquestion_id` = ".$db->qstr($question["qquestion_id"])."
									ORDER BY ".(($question["randomize_responses"] == 1) ? "RAND()" : "a.`response_order` ASC");
					$responses	= $db->GetAll($query);
					if ($responses) {
						foreach ($responses as $response) {
							$response_selected	= false;
							$response_correct	= false;

							if ($PROCESSED[$question["qquestion_id"]] == $response["qqresponse_id"]) {
								$response_selected = true;

								if ($response["response_correct"] == 1) {
									$response_correct	= true;
									$question_correct	= true;
									$question_points	= $question["question_points"];
								} else {
									$response_correct	= false;
								}
							}
						}
					}

					$quiz_score += $question_points;
					$quiz_value += $question["question_points"];
				}

				$quiz_progress_array	= array (
											"quiz_score" => $quiz_score,
											"quiz_value" => $quiz_value
										);

				if (!$db->AutoExecute("quiz_progress", $quiz_progress_array, "UPDATE", "qprogress_id = ".$db->qstr($qprogress_id))) {
					echo "\nThere was an issue encountered while attempting to update this quiz progress for proxy id [".$db->qstr($progress_record["proxy_id"])."].\n";
				} else {
					echo "\nQuiz progress for proxy id [".$db->qstr($progress_record["proxy_id"])."] has been updated successfully.\n";
				}
			}
		} else {
			echo "\nThere were no attached_quizzes found with that id, please ensure you have entered a valid aquiz_id when attempting to remark a quiz.\n";
		}
	} else {
		echo "\nPlease ensure you enter a valid aquiz_id when attempting to remark a quiz.\n";
	}
} elseif ($ACTION == "-delete") {
	$QUIZ_ID			= ((isset($_SERVER["argv"][2]) && ((int) $_SERVER["argv"][2])) ? (int) $_SERVER["argv"][2] : false);
	$QUESTION_ORDER			= ((isset($_SERVER["argv"][3]) && ((int) $_SERVER["argv"][3])) ? (int) $_SERVER["argv"][3] : false);
	$query = "	SELECT * FROM `quiz_questions`
				WHERE `quiz_id` = ".$db->qstr($QUIZ_ID)."
				ORDER BY `question_order` ASC
				LIMIT ".((int)($QUESTION_ORDER - 1)).", 1";
	$quiz_question = $db->GetRow($query);

	$query = "DELETE FROM `quiz_question_responses` WHERE `qquestion_id` = ".$db->qstr($quiz_question["qquestion_id"]);
	if ($db->Execute($query)) {
		$query = "DELETE FROM `quiz_questions` WHERE `qquestion_id` = ".$db->qstr($quiz_question["qquestion_id"]);
		if ($db->Execute($query)) {
			$query = "DELETE FROM `quiz_progress_responses` WHERE `qquestion_id` = ".$db->qstr($quiz_question["qquestion_id"]);
			if ($db->Execute($query)) {
				$query	= "	SELECT * FROM `quiz_progress`
							WHERE `quiz_id` = ".$db->qstr($quiz_question["quiz_id"])."
							ORDER BY `updated_date` ASC";
				$progress_records	= $db->GetAll($query);
				if ($progress_records) {
					foreach ($progress_records as $progress_record) {
						/**
						 * Get a list of all of the questions in this quiz so we
						 * can run through a clean set of questions.
						 */
						$query		= "	SELECT a.*
										FROM `quiz_questions` AS a
										WHERE a.`quiz_id` = ".$db->qstr($progress_record["quiz_id"])."
										ORDER BY a.`question_order` ASC";
						$questions	= $db->GetAll($query);

						$qprogress_id		= $progress_record["qprogress_id"];
						$quiz_score			= 0;
						$quiz_value			= 0;

						$PROCESSED = quiz_load_progress($qprogress_id);

						foreach ($questions as $question) {
							$question_correct	= false;
							$question_points	= 0;

							$query		= "	SELECT a.*
											FROM `quiz_question_responses` AS a
											WHERE a.`qquestion_id` = ".$db->qstr($question["qquestion_id"])."
											ORDER BY ".(($question["randomize_responses"] == 1) ? "RAND()" : "a.`response_order` ASC");
							$responses	= $db->GetAll($query);
							if ($responses) {
								foreach ($responses as $response) {
									$response_selected	= false;
									$response_correct	= false;

									if ($PROCESSED[$question["qquestion_id"]] == $response["qqresponse_id"]) {
										$response_selected = true;

										if ($response["response_correct"] == 1) {
											$response_correct	= true;
											$question_correct	= true;
											$question_points	= $question["question_points"];
										} else {
											$response_correct	= false;
										}
									}
								}
							}

							$quiz_score += $question_points;
							$quiz_value += $question["question_points"];
						}

						$quiz_progress_array	= array (
													"quiz_score" => $quiz_score,
													"quiz_value" => $quiz_value
												);

						if (!$db->AutoExecute("quiz_progress", $quiz_progress_array, "UPDATE", "qprogress_id = ".$db->qstr($qprogress_id))) {
							echo "\nThere was an issue encountered while attempting to update this quiz progress for proxy id [".$db->qstr($progress_record["proxy_id"])."].\n";
						} else {
							echo "\nQuiz progress for proxy id [".$db->qstr($progress_record["proxy_id"])."] has been updated successfully.\n";
						}
					}
				} else {
					echo "\nThere were no attached_quizzes found with that id, please ensure you have entered a valid aquiz_id when attempting to remark a quiz.\n";
				}
			}
		}
	}
}