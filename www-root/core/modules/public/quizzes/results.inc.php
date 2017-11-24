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
 * This section is loaded when an individual wants to attempt a quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_QUIZZES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($RECORD_ID) {
	$query = "SELECT `content_type` FROM `quiz_progress` WHERE `qprogress_id` = ".$db->qstr($RECORD_ID);
	$QUIZ_TYPE = $db->GetOne($query);
	if ($QUIZ_TYPE == "event") {
		$query			= "	SELECT a.`quiz_score`, a.`quiz_value`, a.`proxy_id`, b.*, d.`event_id`, d.`event_title` AS `content_title`, d.`event_start`, d.`event_finish`, d.`release_date` AS `event_release_date`, d.`release_until` AS `event_release_until`, d.`course_id`, e.`organisation_id`, f.`quiztype_code`
							FROM `quiz_progress` AS a
							LEFT JOIN `attached_quizzes` AS b
							ON b.`aquiz_id` = a.`aquiz_id`
							LEFT JOIN `quizzes` AS c
							ON c.`quiz_id` = a.`quiz_id`
							LEFT JOIN `events` AS d
							ON a.`content_type` = 'event' 
							AND d.`event_id` = a.`content_id`
							LEFT JOIN `courses` AS e
							ON e.`course_id` = d.`course_id`
							LEFT JOIN `quizzes_lu_quiztypes` AS f
							ON f.`quiztype_id` = b.`quiztype_id`
							WHERE a.`qprogress_id` = ".$db->qstr($RECORD_ID)."
							AND c.`quiz_active` = '1'";
	} else {
		$query			= "	SELECT a.`quiz_score`, a.`quiz_value`, a.`proxy_id`, b.*, dp.`page_title` AS `content_title`, d.`community_id`, d.`community_url`, dp.`page_url`, f.`quiztype_code`
							FROM `quiz_progress` AS a
							LEFT JOIN `attached_quizzes` AS b
							ON b.`aquiz_id` = a.`aquiz_id`
							LEFT JOIN `quizzes` AS c
							ON c.`quiz_id` = a.`quiz_id`
							LEFT JOIN `community_pages` AS dp
							ON a.`content_type` = 'community_page' 
							AND dp.`cpage_id` = a.`content_id`
							LEFT JOIN `communities` AS d
							ON d.`community_id` = dp.`community_id`
							LEFT JOIN `quizzes_lu_quiztypes` AS f
							ON f.`quiztype_id` = b.`quiztype_id`
							WHERE a.`qprogress_id` = ".$db->qstr($RECORD_ID)."
							AND c.`quiz_active` = '1'";
	}
	$quiz_record = $db->GetRow($query);
	if ($quiz_record) {
		$is_administrator = false;
		if ($QUIZ_TYPE == "event") {
			if ($ENTRADA_ACL->amIAllowed(new EventContentResource($quiz_record["event_id"], $quiz_record["course_id"], $quiz_record["organisation_id"]), "update")) {
				$is_administrator	= true;
			}
		} else {			
			$query	= "SELECT * FROM `community_members` WHERE `community_id` = ".$db->qstr($quiz_record["community_id"])." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `member_active` = '1' AND `member_acl` = '1'";
			$result	= $db->GetRow($query);
			if ($result) {				
				$is_administrator = true;
			}
		}

		if (($is_administrator) || ($quiz_record["proxy_id"] == $ENTRADA_USER->getActiveId())) {
			$respondent_name = get_account_data("firstlast", $quiz_record["proxy_id"]);
			if ($QUIZ_TYPE == "event") {
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/events?id=".$quiz_record["event_id"], "title" => limit_chars($quiz_record["content_title"], 32));
			} else{
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"], "title" => limit_chars($quiz_record["content_title"], 32));
			}
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/".$MODULE."?section=results".($QUIZ_TYPE == "community_page" ? "&community=true" : "")."&id=".$RECORD_ID, "title" => limit_chars($quiz_record["quiz_title"], 32));

			if ($is_administrator) {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?section=results".($QUIZ_TYPE == "community_page" ? "&community=true" : "")."&id=".$quiz_record["aquiz_id"], "title" => "Quiz Results");
				$BREADCRUMB[] = array("url" => "", "title" => $respondent_name);
			}

			/**
			 * Providing there is no expiry date, or the expiry date is in the
			 * future on both the quiz and the event, allow them to continue.
			 */
			if (($is_administrator) || ($quiz_record["quiztype_code"] == "immediate") || (($quiz_record["quiztype_code"] == "delayed") && (((int) $quiz_record["release_until"] === 0) || ($quiz_record["release_until"] <= time())))) {
				$quiz_score = $quiz_record["quiz_score"];
				$quiz_value	= $quiz_record["quiz_value"];

				$query		= "	SELECT a.*
								FROM `quiz_questions` AS a
								WHERE a.`quiz_id` = ".$db->qstr($quiz_record["quiz_id"])."
								AND a.`question_active` = '1'
								ORDER BY a.`question_order` ASC";
				$questions	= $db->GetAll($query);
                $questions = Models_Quiz_Question::fetchAllRecords($quiz_record["quiz_id"]);
				if ($questions) {
					$PROCESSED = quiz_load_progress($RECORD_ID);

					/**
					 * Calculates the percentage for display purposes.
					 */
					$quiz_percentage = ((round(($quiz_score / $quiz_value), 2)) * 100);

					if ($quiz_percentage >= 70) {
						$display_class	= "success";
					} elseif (($quiz_percentage > 50) && ($quiz_percentage < 70)) {
						$display_class	= "notice";
					} else {
						$display_class	= "error";
					}

					echo "<h1>".html_encode($quiz_record["quiz_title"])."</h1>";
					?>
					<div class="display-<?php echo $display_class; ?>">
							<h3><?php echo html_encode($respondent_name); ?> Quiz Results:</h3>
						<div style="font-size: 200%; margin-bottom: 10px">
							You got <strong><?php echo $quiz_score; ?>/<?php echo $quiz_value; ?></strong> on this quiz, which is <strong><?php echo $quiz_percentage; ?>%</strong>.
						</div>
					</div>
                    <style type="text/css">
                        .group {
                            border:1px dashed #B8B8B8;
                            border-radius: 10px;
                            padding:20px;
                            background:#F8F8F8;
                        }
                    </style>
					<div class="quiz-questions" id="quiz-content-questions-holder">
						<ol class="questions" id="quiz-questions-list">
						<?php
						foreach ($questions as $q) {
                            if ($q->getQuestionTypeID() != 3) {
                            
                            $question = $q->toArray();
							$question_correct	= false;
							$question_feedback	= "";

							echo "<li id=\"question_".$question["qquestion_id"]."\" class=\"".($question["questiontype_id"] == 4 ? "group" : "")."\">";
							echo "	<div class=\"question noneditable\">\n";
							echo "		<span id=\"question_text_".$question["qquestion_id"]."\" class=\"question\">".clean_input($question["question_text"], "trim")."</span>";
							echo "	</div>\n";
                            if ($q->getQuestionTypeID() == "4") {
                                $grouped_qquestions = Models_Quiz_Question::fetchGroupedQuestions($q->getQquestionID());
                                if ($grouped_qquestions) {
                                    echo "<ul>";
                                    foreach ($grouped_qquestions as $q) {
                                        $question = $q->toArray();
                                        echo "<li id=\"question_".$question["qquestion_id"]."\">";
                                        echo "	<div class=\"question noneditable\">\n";
                                        echo "		<span id=\"question_text_".$question["qquestion_id"]."\" class=\"question\">".clean_input($question["question_text"], "trim")."</span>";
                                        echo "	</div>\n";
                                        $responses = Models_Quiz_Question_Response::fetchAllRecords($q->getQquestionID());
                                        if ($responses) {
                                            echo "	<ul class=\"responses\">\n";
                                            foreach ($responses as $r) {
                                                $response = $r->toArray();
                                                
                                                $response_selected	= false;
                                                $response_correct	= false;

                                                if ($PROCESSED[$question["qquestion_id"]] == $response["qqresponse_id"]) {
                                                    $response_selected = true;

                                                    if ($response["response_correct"] == 1) {
                                                        $response_correct	= true;
                                                        $question_correct	= true;
                                                    } else {
                                                        $response_correct	= false;
                                                    }

                                                    if ($tmp_input = clean_input($response["response_feedback"], array("notags", "trim"))) {
                                                        $question_feedback = $tmp_input;
                                                    }
                                                }
                                                
                                                echo "<li".(($response_selected) ? " class=\"selected ".(($response_correct) ? "correct" : "incorrect")."\"" : (($response["response_correct"] == 1) ? " class=\"correct\"" : "")).">";
                                                echo clean_input($response["response_text"], (($response["response_is_html"] == 1) ? "trim" : "encode"));

                                                if ($response_selected) {
                                                    if ($response["response_correct"] == 1) {
                                                        echo "<img class=\"question-response-indicator\" src=\"".ENTRADA_URL."/images/question-response-correct.gif\" alt=\"Correct\" title=\"Correct\" />";
                                                    } else {
                                                        echo "<img class=\"question-response-indicator\" src=\"".ENTRADA_URL."/images/question-response-incorrect.gif\" alt=\"Incorrect\" title=\"Incorrect\" />";
                                                    }
                                                }
                                                echo "</li>\n";
                                            }
                                            echo "  </ul>\n";
                                        }
                                        echo "</li>\n";
                                    }
                                    echo "</ul>\n";
                                }
                            } else {
                                echo "	<ul class=\"responses\">\n";
                                $query		= "	SELECT a.*
                                                FROM `quiz_question_responses` AS a
                                                WHERE a.`qquestion_id` = ".$db->qstr($question["qquestion_id"])."
                                                AND a.`response_active` = '1'
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
                                            } else {
                                                $response_correct	= false;
                                            }

                                            if ($tmp_input = clean_input($response["response_feedback"], array("notags", "trim"))) {
                                                $question_feedback = $tmp_input;
                                            }
                                        }

                                        echo "<li".(($response_selected) ? " class=\"selected ".(($response_correct) ? "correct" : "incorrect")."\"" : (($response["response_correct"] == 1) ? " class=\"correct\"" : "")).">";
                                        echo	clean_input($response["response_text"], (($response["response_is_html"] == 1) ? "trim" : "encode"));

                                        if ($response_selected) {
                                            if ($response["response_correct"] == 1) {
                                                echo "<img class=\"question-response-indicator\" src=\"".ENTRADA_URL."/images/question-response-correct.gif\" alt=\"Correct\" title=\"Correct\" />";
                                            } else {
                                                echo "<img class=\"question-response-indicator\" src=\"".ENTRADA_URL."/images/question-response-incorrect.gif\" alt=\"Incorrect\" title=\"Incorrect\" />";
                                            }
                                        }
                                        echo "</li>\n";
                                    }
                                }
                                echo "	</ul>\n";    
                            }
							if ($question_feedback != "") {
								echo "	<div class=\"display-generic\" style=\"margin-left: 65px; padding: 10px\">\n";
								echo "		<strong>Question Feedback:</strong><br />";
								echo		$question_feedback;
								echo "	</div>";
							}
							echo "</li>\n";
                            }
						}
						?>
						</ol>
					</div>
					<div style="border-top: 2px #CCCCCC solid; margin-top: 10px; padding-top: 10px">
						<span class="content-small">Reference ID: <?php echo $RECORD_ID; ?></span>
						<button class="btn" style="float: right" onclick="window.location = '<?php echo ($QUIZ_TYPE == "event" ? ENTRADA_URL."/events?id=".$quiz_record["event_id"] : ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"]); ?>'">Exit Quiz</button>
					</div>
					<div class="clear"></div>
					<?php
				} else {
					application_log("error", "Unable to locate any questions for quiz [".$quiz_record["quiz_id"]."]. Database said: ".$db->ErrorMsg());

					$ERROR++;
					$ERRORSTR[] = "There are no questions currently available for under this quiz. This problem has been reported to a system administrator; please try again later.";

					echo display_error();
				}
			} elseif (($quiz_record["quiztype_code"] == "hide")) {
				$NOTICE++;
				$NOTICESTR[] = "You will not be able to review your quiz results for this quiz, however the results should be released to you by a teacher, likely through a <a href=\"".ENTRADA_URL."/profile/gradebook\">Course Gradebook</a>.<br /><br />Please contact a teacher if you require further assistance.";

				echo display_notice();

				application_log("error", "Someone attempted to review results of qprogress_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["event_id"]."]) after the release date.");
			} else {
				$NOTICE++;
				$NOTICESTR[] = "You will not be able to review your quiz results until after <strong>".date(DEFAULT_DATE_FORMAT, $quiz_record["release_until"])."</strong>.<br /><br />Please contact a teacher if you require further assistance.";

				echo display_notice();

				application_log("error", "Someone attempted to review results of qprogress_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["event_id"]."]) after the release date.");
			}
		} else {
			application_log("error", "Someone attempted to review results of eprogress_id [".$RECORD_ID."] that they were not entitled to view.");

			header("Location: ".ENTRADA_URL."/events?id=".$quiz_record["event_id"]);
			exit;
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to review a quiz, you must provide a valid attempt identifier.";

		echo display_error();

		application_log("error", "Failed to provide a valid qprogress_id [".$RECORD_ID."] when attempting to view quiz results.");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "In order to review a quiz, you must provide a valid attempt identifier.";

	echo display_error();

	application_log("error", "Failed to provide an qprogress_id [".$RECORD_ID."] when attempting to view quiz results.");
}