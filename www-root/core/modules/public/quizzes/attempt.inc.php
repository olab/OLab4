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
	if ($QUIZ_TYPE == "event") {
        $quiz = Models_Quiz_Attached_Event::fetchRowByID($RECORD_ID);
	} else {
		$quiz = Models_Quiz_Attached_CommunityPage::fetchRowByID($RECORD_ID);
	}
	$quiz_record	= $quiz->toArray();
    if ($quiz_record) {
		if ($QUIZ_TYPE == "event") {
			$query = "	SELECT e.*, c.`organisation_id`
						FROM `events` e
						JOIN `courses` c
						ON e.`course_id` = c.`course_id`
						WHERE e.`event_id` = " . $db->qstr($quiz_record["content_id"]);
			$result = $db->GetRow($query);
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/events?id=".$quiz_record["content_id"], "title" => limit_chars($quiz_record["content_title"], 32));
		} else {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"], "title" => limit_chars($quiz_record["content_title"], 32));
		}
		$BREADCRUMB[]	= array("url" => ENTRADA_URL."/".$MODULE."?section=attempt".(isset($QUIZ_TYPE) && $QUIZ_TYPE == "community_page" ? "&community=true" : "")."&id=".$RECORD_ID, "title" => limit_chars($quiz_record["quiz_title"], 32));
		if ($result && $ENTRADA_ACL->amIAllowed(new EventResource($quiz_record["content_id"], $result['course_id'], $result['organisation_id']), 'read')) {
			/**
			* Providing there is no release date, or the release date is in the past
			* on both the quiz and the event, allow them to continue.
			*/
			if ((((int) $quiz_record["release_date"] === 0) || ($quiz_record["release_date"] <= time())) && ($QUIZ_TYPE == "community_page" || (((int) $quiz_record["event_release_date"] === 0) || ($quiz_record["event_release_date"] <= time())))) {
				/**
				* Providing there is no expiry date, or the expiry date is in the
				* future on both the quiz and the event, allow them to continue.
				*/
				if ((((int) $quiz_record["release_until"] === 0) || ($quiz_record["release_until"] > time())) && ($QUIZ_TYPE == "community_page" || (((int) $quiz_record["event_release_until"] === 0) || ($quiz_record["event_release_until"] > time())))) {
					/**
					* Get the number of completed attempts this user has made.
					*/
					$completed_attempts = quiz_fetch_attempts($RECORD_ID);

					/**
					* Providing they can still still make attempts at this quiz, allow them to continue.
					*/
					if (((int) $quiz_record["quiz_attempts"] === 0) || ($completed_attempts < $quiz_record["quiz_attempts"])) {
						$problem_questions = array();

						echo "<h1>".html_encode($quiz_record["quiz_title"])."</h1>";

						// Error checking
						switch ($STEP) {
							case 2 :
								/**
								* Check to see if they currently have any quiz attempts underway, if they do then
								* restart their session, otherwise start them a new session.
								*/
								$progress = Models_Quiz_Progress::fetchRowByAquizIDProxyID($RECORD_ID, $ENTRADA_USER->getID(), "inprogress");
								$progress_record = $progress->toArray();
								if ($progress_record) {
									$qprogress_id		= $progress_record["qprogress_id"];
									$quiz_start_time	= $progress_record["updated_date"];
									$quiz_end_time		= (((int) $quiz_record["quiz_timeout"]) ? ($quiz_start_time + ($quiz_record["quiz_timeout"] * 60)) : 0);
									$quiz_score			= 0;
									$quiz_value			= 0;

									/**
									* Check if there is a timeout set, and if the current time is less than the timeout.
									*/
									if ((!$quiz_end_time) || (time() <= ($quiz_end_time + 30))) {
										if ((isset($_POST["responses"])) && (is_array($_POST["responses"])) && (count($_POST["responses"]) > 0)) {
											/**
											* Get a list of all of the questions in this quiz so we
											* can run through a clean set of questions.
											*/
											$questions = Models_Quiz_Question::fetchAllMCQ($progress_record["quiz_id"]);
											if ($questions) {
                                                foreach ($questions as $q) {
                                                    if (!is_array($q)) {
                                                        $temp_questions[] = $q;
                                                    } else {
                                                        foreach ($q as $grouped_question) {
                                                            $temp_questions[] = $grouped_question;
                                                        }
                                                    }
                                                }
                                                $questions = $temp_questions;
//                                                $q_count = 0;
//                                                foreach ($questions as $q) {
//                                                    if (!is_array($q)) {
//                                                        $q_count++;
//                                                    } else {
//                                                        foreach ($q as $grouped_q) {
//                                                            $q_count++;
//                                                        }
//                                                    }
//                                                }
//                                                echo "ccount: ".count($_POST["responses"]). "-".$q_count;
//												if (count($_POST["responses"]) != $q_count) {
//													add_error("In order to submit your quiz for marking, you must first answer all of the questions. The unanswered questions will be highlighted, as well as the page selector for any page with an unanswered question on it.");
//												}

												foreach ($questions as $q) {
                                                    $question = $q->toArray();
                                                    /**
                                                    * Checking to see if the qquestion_id was submitted with the
                                                    * response $_POST, and if they've actually answered the question.
                                                    */
                                                    if ((isset($_POST["responses"][$question["qquestion_id"]])) && ($qqresponse_id = clean_input($_POST["responses"][$question["qquestion_id"]], "int"))) {
                                                        if (!quiz_save_response($qprogress_id, $progress_record["aquiz_id"], $progress_record["content_id"], $progress_record["quiz_id"], $question["qquestion_id"], $qqresponse_id, $QUIZ_TYPE)) {
                                                            add_error("A problem was found storing a question response, please verify your responses and try again.");

                                                            $problem_questions[] = $question["qquestion_id"];
                                                        }
                                                    } else {
                                                        $problem_questions[] = $question["qquestion_id"];
                                                    }
												}
											} else {
												add_error("An error occurred while attempting to save your quiz responses. The system administrator has been notified of this error; please try again later.");

												application_log("error", "Unable to find any quiz questions for quiz_id [".$progress_record["quiz_id"]."]. Database said: ".$db->ErrorMsg());
											}

											/**
											* We can now safely say that all questions have valid responses
											* and that we have stored those responses quiz_progress_responses table.
											*/
											if (!$ERROR) {
                                                
												$PROCESSED = quiz_load_progress($qprogress_id);

												foreach ($questions as $question) {
                                                    
                                                    $question = $question->toArray();
                                                    
													$question_correct	= false;
													$question_points	= 0;
                                                    
													$responses = Models_Quiz_Question_Response::fetchAllRecords($question["qquestion_id"]);
                                                    
													if ($responses) {
														foreach ($responses as $r) {
                                                            $response = $r->toArray();
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

												$quiz_progress_array = array (
                                                    "qprogress_id"      => $qprogress_id,
                                                    "aquiz_id"          => $RECORD_ID,
                                                    "quiz_id"           => $quiz_record["quiz_id"],
                                                    "progress_value"    => "complete",
                                                    "content_id"        => $quiz_record["content_id"],
                                                    "content_type"      => $QUIZ_TYPE,
                                                    "quiz_score"        => $quiz_score,
                                                    "quiz_value"        => $quiz_value,
                                                    "updated_date"      => time(),
                                                    "proxy_id"          => $ENTRADA_USER->getActiveID(),
                                                    "updated_by"        => $ENTRADA_USER->getID()
                                                );
                                                
                                                $progress = new Models_Quiz_Progress($quiz_progress_array);
												if ($progress->update()) {
													/**
													* Add a completed quiz statistic.
													*/
													add_statistic("events", "quiz_complete", "aquiz_id", $RECORD_ID);

													/**
													* Increase the number of completed attempts this quiz has had.
													*/
													if (!$db->AutoExecute("attached_quizzes", array("accesses" => ($quiz_record["accesses"] + 1)), "UPDATE", "aquiz_id = ".$db->qstr($RECORD_ID))) {
														application_log("error", "Unable to increment the total number of accesses (the number of completed quizzes) of aquiz_id [".$RECORD_ID."].");
													}

													application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed aquiz_id [".$RECORD_ID."].");

													/**
													* Check if this is a formative quiz, or a summative quiz that has passed it's release date (not likely)
													* then forward the user on the quiz results section; otherwise, let them know that their quiz
													* has been accepted and return them to the event page.
													*/
													if (($quiz_record["quiztype_code"] == "immediate") || (($quiz_record["quiztype_code"] == "delayed") && (((int) $quiz_record["release_until"] === 0) || ($quiz_record["release_until"] <= time())))) {
														header("Location: ".ENTRADA_URL."/quizzes?section=results".(isset($QUIZ_TYPE) && $QUIZ_TYPE == "community_page" ? "&community=true" : "")."&id=".$progress_record["qprogress_id"]);
														exit;
													} elseif ($quiz_record["quiztype_code"] == "delayed") {
														if ($QUIZ_TYPE == "community_page") {
															$url = ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"];
															add_success("Thank-you for completing the <strong>".html_encode($quiz_record["quiz_title"])."</strong> quiz. Your responses have been successfully recorded, and your grade and any feedback will be released <strong>".date(DEFAULT_DATE_FORMAT, $quiz_record["release_until"])."</strong>.<br /><br />You will now be redirected back to the learning event; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
														} else {
															$url = ENTRADA_URL."/events?id=".$quiz_record["content_id"];
															add_success("Thank-you for completing the <strong>".html_encode($quiz_record["quiz_title"])."</strong> quiz. Your responses have been successfully recorded, and your grade and any feedback will be released <strong>".date(DEFAULT_DATE_FORMAT, $quiz_record["release_until"])."</strong>.<br /><br />You will now be redirected back to the learning event; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
														}

														$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";
													} else {
														if ($QUIZ_TYPE == "community_page") {
															$url = ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"];
															add_success("Thank-you for completing the <strong>".html_encode($quiz_record["quiz_title"])."</strong> quiz. Your responses have been successfully recorded.<br /><br />You will now be redirected back to the learning event; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
														} else {
															$url = ENTRADA_URL."/events?id=".$quiz_record["content_id"];
															add_success("Thank-you for completing the <strong>".html_encode($quiz_record["quiz_title"])."</strong> quiz. Your responses have been successfully recorded.<br /><br />You will now be redirected back to the learning event; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
														}

														$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";
                                                    }
												} else {
													application_log("error", "Unable to record the final quiz results for aquiz_id [".$RECORD_ID."] in the quiz_progress table. Database said: ".$db->ErrorMsg());

													add_error("We were unable to record the final results for this quiz at this time. Please be assured that your responses are saved, but you will need to come back to this quiz to re-submit it. This problem has been reported to a system administrator; please try again later.");

													echo display_error();
												}
											}
										} else {
											add_error("In order to submit your quiz for marking, you must first answer some of the questions.");
										}
									} else {
										$quiz_progress_array	= array (
																	"progress_value" => "expired",
																	"content_id" => $quiz_record["content_id"],
																	"content_type" => $QUIZ_TYPE,
																	"quiz_score" => "0",
																	"quiz_value" => "0",
																	"updated_date" => time(),
																	"updated_by" => $ENTRADA_USER->getID()
																);

										if (!$db->AutoExecute("quiz_progress", $quiz_progress_array, "UPDATE", "qprogress_id = ".$db->qstr($qprogress_id))) {
											application_log("error", "Unable to update the qprogress_id [".$qprogress_id."] to expired. Database said: ".$db->ErrorMsg());
										}

										$completed_attempts += 1;

										add_error("We were unable to save your previous quiz attempt because your time limit expired <strong>".date(DEFAULT_DATE_FORMAT, $quiz_end_time)."</strong>, and you submitted your quiz <strong>".date(DEFAULT_DATE_FORMAT)."</strong>.");

										application_log("notice", "Unable to save qprogress_id [".$qprogress_id."] because it expired.");
									}
								} else {
									add_error("We were unable to locate a quiz that is currently in progress.<br /><br />If you pressed your web-browsers back button, please refrain from doing this when you are posting quiz information.");

									application_log("error", "Unable to locate a quiz currently in progress when attempting to save a quiz.");
								}

								if ($ERROR) {
									$STEP = 1;
								}
							break;
							case 1 :
							default :
								continue;
							break;
						}

						if (((int) $quiz_record["quiz_attempts"] === 0) || ($completed_attempts < $quiz_record["quiz_attempts"])) {
							// Display Content
							switch ($STEP) {
								case 2 :
									if ($SUCCESS) {
										echo display_success();
									}
								break;
								case 1 :
								default :
									/**
									* Check to see if they currently have any quiz attempts underway, if they do then
									* restart their session, otherwise start them a new session.
									*/
									$query				= "	SELECT *
															FROM `quiz_progress`
															WHERE `aquiz_id` = ".$db->qstr($RECORD_ID)."
															AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
															AND `progress_value` = 'inprogress'
															ORDER BY `updated_date` ASC";
									$progress_record	= $db->GetRow($query);
									if ($progress_record) {
										$qprogress_id		= $progress_record["qprogress_id"];
										$quiz_start_time	= $progress_record["updated_date"];
									} else {
										$quiz_start_time		= time();
										$quiz_progress_array	= array (
																	"aquiz_id" => $RECORD_ID,
																	"content_id" => $quiz_record["content_id"],
																	"content_type" => $QUIZ_TYPE,
																	"quiz_id" => $quiz_record["quiz_id"],
																	"proxy_id" => $ENTRADA_USER->getID(),
																	"progress_value" => "inprogress",
																	"quiz_score" => 0,
																	"quiz_value" => 0,
																	"updated_date" => $quiz_start_time,
																	"updated_by" => $ENTRADA_USER->getID()
																);
										if ($db->AutoExecute("quiz_progress", $quiz_progress_array, "INSERT"))  {
											$qprogress_id = $db->Insert_Id();
										} else {
											add_error("Unable to create a progress entry for this quiz, it is not advisable to continue at this time. The system administrator was notified of this error; please try again later.");

											application_log("error", "Unable to create a quiz_progress entery when attempting complete a quiz. Database said: ".$db->ErrorMsg());
										}
									}

									if (isset($qprogress_id) && $qprogress_id) {
										add_statistic("events", "quiz_view", "aquiz_id", $RECORD_ID);

										$quiz_timeout			= (((int) $quiz_record["quiz_timeout"]) ? ($quiz_record["quiz_timeout"] * 60) : 0);
										$quiz_end_time			= (($quiz_timeout) ? ($quiz_start_time + $quiz_timeout) : 0);

										/**
										* Check to see if the release_until date is before the current end_time,
										* if it is, shorten the $quiz_end_time to the release_until date.
										*/
										if (($quiz_end_time) && ((int) $quiz_record["release_until"]) && ($quiz_end_time > $quiz_record["release_until"])) {
											$quiz_end_time = $quiz_record["release_until"];
										}

										$quiz_time_remaining	= ($quiz_end_time - time());
										$ajax_load_progress		= quiz_load_progress($qprogress_id);

										if ($quiz_end_time) {
											?>
											<div id="display-quiz-timeout" class="display-generic">
												You have until <strong><?php echo date(DEFAULT_DATE_FORMAT, $quiz_end_time); ?></strong> to complete this quiz.

												<div id="quiz-timer" style="margin-top: 15px; display: none"></div>
											</div>
											<script type="text/javascript">
											function quizTimeout(timeout) {
												this.timeout = timeout;

												function countdown() {
													output	= new Array();
													if (this.timeout > 0) {
														if (this.timeout <= 10) {
															if ($('display-quiz-timeout').hasClassName('display-notice')) {
																$('display-quiz-timeout').removeClassName('display-notice');
															}

															if (!$('display-quiz-timeout').hasClassName('display-error')) {
																$('display-quiz-timeout').addClassName('display-error');
															}

															Effect.Pulsate('display-quiz-timeout', { pulses: 2, duration: 1 });
														} else if ((this.timeout <= 60) && ((this.timeout % 10) == 0)) {
															Effect.Pulsate('display-quiz-timeout', { pulses: 3, duration: 1, from: 0.2 });
														} else if (this.timeout <= 120) {
															if ($('display-quiz-timeout').hasClassName('display-generic')) {
																$('display-quiz-timeout').removeClassName('display-generic');
															}

															if (!$('display-quiz-timeout').hasClassName('display-notice')) {
																$('display-quiz-timeout').addClassName('display-notice');
															}
														}

														seconds	= Math.floor(this.timeout / 1) % 60;
														minutes	= Math.floor(this.timeout / 60) % 60;
														hours	= Math.floor(this.timeout / 3600) % 24;
														days	= Math.floor(this.timeout / 86400) % 86400;

														if (days > 0) {
															output[output.length] = days + ' day' + ((days != 1) ? 's' : '');
														}
														if (hours > 0) {
															output[output.length] = hours + ' hour' + ((hours != 1) ? 's' : '');
														}
														if (minutes > 0) {
															output[output.length] = minutes + ' minute' + ((minutes != 1) ? 's' : '');
														}

														output[output.length]		= ((output.length > 0) ? ' and ' : '') + seconds + ' second' + ((seconds != 1) ? 's' : '');

														$('quiz-timer').innerHTML	= output.join(', ');

														this.timeout	= (this.timeout - 1);
														countdown.timer	= setTimeout(countdown, 1000);
													} else {
														$('quiz-timer').innerHTML = 'Unfortunately your time limit has expired. There is a 30 second grace period, please submit your quiz immediately.';
													}
												}

												$('quiz-timer').show();
												countdown();
											}

											quizTimeout('<?php echo $quiz_time_remaining; ?>');
											</script>
											<?php
										}
										?>
										<div id="display-unsaved-warning" class="display-notice" style="display: none">
											<ul>
												<li><strong>Warning Unsaved Response:</strong><br />Your response to the question indicated by a yellow background was not automatically saved.</li>
											</ul>
										</div>
										<?php
										if ($ERROR) {
											echo display_error();
										}
										if ($NOTICE) {
											echo display_notice();
										}
										if (clean_input($quiz_record["quiz_notes"], array("notags", "nows")) != "") {
											echo clean_input($quiz_record["quiz_notes"], "allowedtags");
										}
										?>
										<form class="question-responses" action="<?php echo ENTRADA_URL."/".$MODULE; ?>?section=attempt<?php echo (isset($QUIZ_TYPE) && $QUIZ_TYPE == "community_page" ? "&amp;community=true" : ""); ?>&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="2" />
										<?php
										$questions = Models_Quiz_Question::fetchAllRecords($quiz_record["quiz_id"]);
                                        if (isset($questions) && isset($quiz_record["random_order"]) && $quiz_record["random_order"] == 1) {
                                            $grouped_questions = array();
                                            $i = 0;
                                            $questions_ordered_array = array();
                                            $unordered_questions = array();
                                            
											foreach ($questions as $question_key => $question) {
												if (!is_null($question->getQquestionGroupID())) {
                                                    if (!isset($grouped_questions[$question->getQquestionGroupID()])) {
                                                        $grouped_questions[$question->getQquestionGroupID()] = array("group" => true, "questions" => array());
                                                    }
                                                    $grouped_questions[$question->getQquestionGroupID()]["questions"][] = $question;
                                                    $questions_ordered_array[] = NULL;
                                                    unset($questions[$question_key]);
												} else if ($question->getQuestiontypeID() == "1") {
                                                    $questions_ordered_array[] = NULL;
                                                    $unordered_questions[] = $question;
                                                    unset($questions[$question_key]);
                                                } else {
                                                    $questions_ordered_array[] = $question;
                                                }
                                                $i++;
											}

                                            foreach ($grouped_questions as $group_id => $q_group) {
                                                $unordered_questions[] = $q_group;
                                            }
                                            shuffle($unordered_questions);
                                            $randomized_questions = array();
                                            foreach ($unordered_questions as $question) {
                                                if (is_array($question) && $question["group"]) {
                                                    foreach ($question["questions"] as $child_question) {
                                                        $randomized_questions[] = $child_question;
                                                    }
                                                } else {
                                                    $randomized_questions[] = $question;
                                                }
                                            }
                                            $index = 0;
                                            foreach ($randomized_questions as $question) {
                                                $question_added = false;
                                                while (!$question_added) {
                                                    if (!isset($questions_ordered_array[$index]) || is_null($questions_ordered_array[$index])) {
                                                        $questions_ordered_array[$index] = $question;
                                                        $question_added = true;
                                                    }
                                                    $index++;
                                                }
                                            }
                                            $questions = array();
                                            $count = 1;
                                            foreach ($questions_ordered_array as $question) {
                                                if ($count < count($questions_ordered_array) || $question->getQuestiontypeID() != 3) {
                                                    $questions[] = $question;
                                                }
                                                $count++;
                                            }
										}
                                        
										$total_questions	= 0;
										if ($questions) {
											$total_questions = count($questions);
											?>
										<style type="text/css">
											.question-responses {
                                                position:relative;
                                            }
											.pagination {
                                                float:right;
                                            }
											.page.active {
                                                display:block;
                                            }
											.page.inactive {
                                                display:none;
                                            }
                                            ol.questions {
                                                padding-left:20px;
                                            }
										</style>
											<?php
                                                $problem_pages = array();
												$page_counter = 1;
												$counter = 1;
												$quiz_markup = "";
                                                $used_qquestion_group_ids = array();
												foreach ($questions as $question) {
                                                    if ($question->getQquestionGroupID()) {
                                                        if (!in_array($question->getQquestionGroupID(), $used_qquestion_group_ids)) {
                                                            $used_qquestion_group_ids[] = $question->getQquestionGroupID();
                                                            $grouped_qquestions = Models_Quiz_Question::fetchGroupedQuestions($question->getQuizID(), $question->getQquestionGroupID());
                                                            if ($grouped_qquestions) {
                                                                $quiz_markup .= "</ol><ol class=\"questions group\" start=\"".$counter."\">";
                                                                foreach ($grouped_qquestions as $question) {
                                                                    $quiz_markup .= "<li>".clean_input($question->getQuestionText(), "trim");
                                                                    $responses	= Models_Quiz_Question_Response::fetchAllRecords($question->getQquestionID());
                                                                    if ($responses) {
                                                                        $quiz_markup .= "<ul class=\"responses\">";
                                                                        foreach ($responses as $r) {
                                                                            $response = $r->toArray();
                                                                            $quiz_markup .= "<li class=\"row-fluid\">";
                                                                            $quiz_markup .= "	<span class=\"span1\"><input type=\"radio\" id=\"response_".$question->getQquestionID()."_".$response["qqresponse_id"]."\" name=\"responses[".$question->getQquestionID()."]\" value=\"".$response["qqresponse_id"]."\"".(($ajax_load_progress[$question->getQquestionID()] == $response["qqresponse_id"]) ? " checked=\"checked\"" : "")." onclick=\"((this.checked == true) ? storeResponse('".$question->getQquestionID()."', '".$response["qqresponse_id"]."') : false)\" /></span>";
                                                                            $quiz_markup .= "	<label class=\"span11\" for=\"response_".$question->getQquestionID()."_".$response["qqresponse_id"]."\">".clean_input($response["response_text"], (($response["response_is_html"] == 1) ? "trim" : "encode"))."</label>";
                                                                            $quiz_markup .= "</li>\n";
                                                                        }
                                                                        $quiz_markup .= "</ul>";
                                                                    }
                                                                    $quiz_markup .= "</li>";
                                                                    $counter ++;
                                                                }
                                                                $quiz_markup .= "</ol><ol class=\"questions\" start=\"".$counter."\">\n";
                                                            }
                                                            $quiz_markup .= "</li>";
                                                        }
                                                    } else if ($question->getQuestiontypeID() == 3) {
														$page_counter++;
														$break_id = $question->getQquestionID();
														$quiz_markup .= "</ol></div><div class=\"page inactive\" data-id=\"".$page_counter."\">";
														$quiz_markup .= "<h2>Quiz Page ".$page_counter."</h2>";
														$quiz_markup .= "<ol class=\"questions\" start=\"".$counter."\">";
													} else if ($question->getQuestiontypeID() == 2) {
														$quiz_markup .= "</ol>";
														$quiz_markup .= "<div class=\"display-generic\">".$question->getQuestionText()."</div>";
														$quiz_markup .= "<ol class=\"questions\" start=\"".$counter."\">";
													} else {
                                                        if (in_array($question->getQquestionID(), $problem_questions) && !array_key_exists($page_counter, $problem_pages)) {
                                                            $problem_pages[$page_counter] = true;
                                                        }
														$quiz_markup .= "<li id=\"question_".$question->getQquestionID()."\"".((in_array($question->getQquestionID(), $problem_questions)) ? " class=\"notice\"" : "")." data-page=\"".$page_counter."\">";
														$quiz_markup .= "	<div class=\"question noneditable\">\n";
														$quiz_markup .= "		<span id=\"question_text_".$question->getQquestionID()."\" class=\"question\">".clean_input($question->getQuestionText(), "trim")."</span>";
														$quiz_markup .= "	</div>\n";
														if ($question->getQuestiontypeID() == 1) {
															$quiz_markup .= "	<ul class=\"responses\">\n";
															$query		= "	SELECT a.*
																			FROM `quiz_question_responses` AS a
																			WHERE a.`qquestion_id` = ".$db->qstr($question->getQquestionID())."
																			AND a.`response_active` = '1'
																			ORDER BY ".(($question->getRandomizeResponses() == 1) ? "RAND()" : "a.`response_order` ASC");
															$responses	= $db->GetAll($query);
															if ($responses) {
																foreach ($responses as $response) {
																	$quiz_markup .= "<li class=\"row-fluid\">";
																	$quiz_markup .= "	<span class=\"span1\"><input type=\"radio\" id=\"response_".$question->getQquestionID()."_".$response["qqresponse_id"]."\" name=\"responses[".$question->getQquestionID()."]\" value=\"".$response["qqresponse_id"]."\"".(($ajax_load_progress[$question->getQquestionID()] == $response["qqresponse_id"]) ? " checked=\"checked\"" : "")." onclick=\"((this.checked == true) ? storeResponse('".$question->getQquestionID()."', '".$response["qqresponse_id"]."') : false)\" /></span>";
																	$quiz_markup .= "	<label class=\"span11\" for=\"response_".$question->getQquestionID()."_".$response["qqresponse_id"]."\">".clean_input($response["response_text"], (($response["response_is_html"] == 1) ? "trim" : "encode"))."</label>";
																	$quiz_markup .= "</li>\n";
																}
															}
															$quiz_markup .= "	</ul>\n";
														} else if ($question->getQuestiontypeID() == 2) {
															$quiz_markup .= "<textarea id=\"response_".$question->getQquestionID()."\" name=\"responses[".$question->getQquestionID()."]\" maxlength=\"".$question->getCharLimit()."\" style=\"width:95%;\"></textarea>";
														}
														$quiz_markup .= "</li>\n";
														$counter ++;
													}
												} ?>
											<div class="quiz-questions" id="quiz-content-questions-holder">
												<?php
												if ($page_counter > 1) { 
													?>
													<div class="row-fluid pagination pagination-right pagination-top">
														<ul>
															<li><a href="#" class="prev">&laquo;</a></li>
															<?php 
															for ($i = 1; $i <= $page_counter; $i++) {
																echo "<li".($i == 1 ? " class=\"active\"" : "")."><a href=\"#".$i."\" id=\"page-selector-top-".$i."\" data-id=\"".$i."\"".((array_key_exists($i, $problem_pages)) && $problem_pages[$i] ? " class=\"notice\"" : "").">".$i."</a></li>";
															} 
															?>
															<li><a href="#" class="next">&raquo;</a></li>
														</ul>
													</div>
													<?php
												}
												?>
												<div class="page active" data-id="1">
												<?php echo ($page_counter > 1 ? "<h2>Quiz Page 1</h2>" : ""); ?>
												<ol class="questions" id="quiz-questions-list">
													<?php echo $quiz_markup; ?>
												</ol>
												</div>
												<?php
												if ($page_counter > 1) { 
													?>
													<div class="row-fluid pagination pagination-right pagination-bottom">
														<ul>
															<li><a href="#" class="prev">&laquo;</a></li>
															<?php 
															for ($i = 1; $i <= $page_counter; $i++) {
																echo "<li".($i == 1 ? " class=\"active\"" : "")."><a href=\"#".$i."\" id=\"page-selector-bottom-".$i."\" data-id=\"".$i."\"".((array_key_exists($i, $problem_pages)) && $problem_pages[$i] ? " class=\"notice\"" : "").">".$i."</a></li>";
															} 
															?>
															<li><a href="#" class="next">&raquo;</a></li>
														</ul>
													</div>
													<?php
												}
												?>
											</div>
											<?php
										} else {
											add_error("There are no questions currently available for under this quiz. This problem has been reported to a system administrator; please try again later.");

											application_log("error", "Unable to locate any questions for quiz [".$quiz_record["quiz_id"]."]. Database said: ".$db->ErrorMsg());
										}
										?>
										<div class="row-fluid border-above-medium space-above pad-top">
											<input type="button" class="btn space-left" onclick="window.location = '<?php echo ENTRADA_URL; ?>/events?id=<?php echo $quiz_record["content_id"]; ?>'" value="Exit Quiz" />
											<input id="submit-button" type="submit" class="btn btn-primary pull-right"<?php echo ($page_counter > 1 ? " style=\"display: none;\"" : ""); ?> value="Submit Quiz" />
										</div>
										</form>
										<script type="text/javascript">
											var total_pages = jQuery(".pagination-top").length >= 1 ? jQuery(".pagination-top ul li").length - 2 : 1;
											var active_page = 1;
											jQuery(function(){
												jQuery(".pagination ul li a").live("click", function() {
													if (jQuery(this).hasClass("prev") || jQuery(this).hasClass("next")) {
														old_page = active_page;
														if (jQuery(this).hasClass("prev") && active_page > 1) {
															active_page--;
														} else if (jQuery(this).hasClass("next") && active_page != total_pages) {
															active_page++;
														}
														if (old_page != active_page) {
															jQuery(".pagination-top ul li").removeClass("active");
															jQuery(".pagination-top ul li").eq(active_page).addClass("active");
															jQuery(".pagination-bottom ul li").removeClass("active");
															jQuery(".pagination-bottom ul li").eq(active_page).addClass("active");
															jQuery(".page.active").fadeOut("fast", function() {
																jQuery(".page.active").removeClass("active").addClass("inactive");
																jQuery(".page[data-id="+ (active_page) + "]").fadeIn().removeClass("inactive").addClass("active");
															});
														}
													} else {
														if (active_page != jQuery(this).attr("data-id")) {
															active_page = jQuery(this).attr("data-id");
															jQuery(".pagination-top ul li").removeClass("active");
															jQuery(".pagination-top ul li").eq(active_page).addClass("active");
															jQuery(".pagination-bottom ul li").removeClass("active");
															jQuery(".pagination-bottom ul li").eq(active_page).addClass("active");
															jQuery(".page.active").fadeOut("fast", function() {
																jQuery(".page.active").removeClass("active").addClass("inactive");
																jQuery(".page.inactive[data-id="+ (active_page) + "]").fadeIn().removeClass("inactive").addClass("active");
															});
														}
													}
													if (active_page >= total_pages) {
														jQuery('#submit-button').show();
													} else {
														jQuery('#submit-button').hide();
													}
													return false;
												});
											});
										</script>
										<script type="text/javascript">
										function storeResponse(qid, rid) {
											new Ajax.Request('<?php echo ENTRADA_URL."/".$MODULE; ?>', {
												method: 'post',
												parameters: { 'section' : 'save-response', 'id' : '<?php echo $RECORD_ID; ?>', 'qid' : qid, 'rid' : rid<?php echo ($QUIZ_TYPE == "community_page" ? ", 'community' : 'true'" : ""); ?> },
												onSuccess: function(transport) {
													if (transport.responseText.match(200)) {
														$('question_' + qid).removeClassName('notice');
                                                        var page_id = jQuery('#question_' + qid).data('page');
                                                        if (jQuery('.page[data-id="'+ page_id +'"] .notice').length == 0) {
                                                            $('page-selector-top-' + page_id).removeClassName('notice');
                                                            $('page-selector-bottom-' + page_id).removeClassName('notice');
                                                        }

														if ($$('#quiz-questions-list li.notice').length <= 0) {
															$('display-unsaved-warning').fade({ duration: 0.5 });
														}
													} else {
														$('question_' + qid).addClassName('notice');

														if ($('display-unsaved-warning').style.display == 'none') {
															$('display-unsaved-warning').appear({ duration: 0.5 });
														}
													}
												},
												onError: function() {
														$('question_' + qid).addClassName('notice');

														if ($('display-unsaved-warning').style.display == 'none') {
															$('display-unsaved-warning').appear({ duration: 0.5 });
														}
												}
											});
										}
										</script>
										<?php
										$sidebar_html = quiz_generate_description($quiz_record["required"], $quiz_record["quiztype_code"], $quiz_record["quiz_timeout"], $total_questions, $quiz_record["quiz_attempts"], $quiz_record["timeframe"], $quiz_record["require_attendance"], (isset($quiz_record["course_id"]) && $quiz_record["course_id"] ? $quiz_record["course_id"] : 0));
										new_sidebar_item("Quiz Statement", $sidebar_html, "page-anchors", "open", "1.9");
									} else {
										add_error("Unable to locate your progress information for this quiz at this time. The system administrator has been notified of this error; please try again later.");

										echo display_error();

										application_log("error", "Failed to locate a qprogress_id [".$qprogress_id."] (either existing or created) when attempting to complete aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["content_id"]."]).");
									}
								break;
							}
						} else {
							add_error("You were only able to attempt this quiz a total of <strong>".(int) $quiz_record["quiz_attempts"]." time".(($quiz_record["quiz_attempts"] != 1) ? "s" : "")."</strong>, and time limit for your final attempt expired before completion.<br /><br />Please contact a teacher if you require further assistance.");

							echo display_error();

							application_log("notice", "Someone attempted to complete aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["content_id"]."]) more than the total number of possible attempts [".$quiz_record["quiz_attempts"]."] after their final attempt expired.");
						}
					} else {
						add_notice("You were only able to attempt this quiz a total of <strong>".(int) $quiz_record["quiz_attempts"]." time".(($quiz_record["quiz_attempts"] != 1) ? "s" : "")."</strong>.<br /><br />Please contact a teacher if you require further assistance.");

						echo display_notice();

						application_log("notice", "Someone attempted to complete aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["content_id"]."]) more than the total number of possible attempts [".$quiz_record["quiz_attempts"]."].");
					}
				} else {
					add_notice("You were only able to attempt this quiz until <strong>".date(DEFAULT_DATE_FORMAT, $quiz_record["release_until"])."</strong>.<br /><br />Please contact a teacher if you require further assistance.");

					echo display_notice();

					application_log("error", "Someone attempted to complete aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["content_id"]."] after the release date.");
				}
			} else {
				add_notice("You cannot attempt this quiz until <strong>".date(DEFAULT_DATE_FORMAT, $quiz_record["release_date"])."</strong>.<br /><br />Please contact a teacher if you require further assistance.");

				echo display_notice();

				application_log("error", "Someone attempted to complete aquiz_id [".$RECORD_ID."] (quiz_id [".$quiz_record["quiz_id"]."] / event_id [".$quiz_record["content_id"]."] prior to the release date.");
			}
		} else {
			add_error("This quiz is only accessible by authorized users.");

			echo display_error();

			application_log("error", "Unauthorized user attempted to access quiz [".$RECORD_ID."].");
		}
	} else {
		add_error("In order to attempt a quiz, you must provide a valid quiz identifier.");

		echo display_error();

		application_log("error", "Failed to provide a valid aquiz_id identifer [".$RECORD_ID."] when attempting to take a quiz.");
	}
} else {
	add_error("In order to attempt a quiz, you must provide a valid quiz identifier.");

	echo display_error();

	application_log("error", "Failed to provide an aquiz_id identifier when attempting to take a quiz.");
}