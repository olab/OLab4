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
 * This file is used by quiz authors to add quiz questions to a particular quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quizquestion', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $question_types = Models_Quiz_QuestionType::fetchAllRecords();
	if ($question_types) {
		$question_type_ids = array();
		foreach ($question_types as $question_type) {
			$question_type_ids[] = $question_type->getQuestionTypeID();
		}
	}
	
	if ($question_types && isset($_GET["type"]) && in_array($_GET["type"], $question_type_ids)) {
		$set_type = true;
		$type = clean_input($_GET["type"], "numeric");
	} else {
		$set_type = false;
		$type = 1;
	}

    if (isset($_GET["qquestion_id"]) && $tmp_input = clean_input($_GET["qquestion_id"], "int")) {
        $qquestion_group_id = $tmp_input;
    }
    
	if ($RECORD_ID) {
        $quiz_record = Models_Quiz::fetchRowByID($RECORD_ID);
		if ($quiz_record && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz_record->getQuizID()), "update")) {
			if ($ALLOW_QUESTION_MODIFICATIONS) {
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID, "title" => limit_chars($quiz_record->getQuizTitle(), 32));
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=add-question&id=".$RECORD_ID, "title" => "Add Quiz Question");

				/**
				 * Load the rich text editor.
				 */
				load_rte("advanced");

				if ($type == "3") {
					$STEP = 2;
					$_POST["question_text"] = "Page Break";
					$_POST["questiontype_id"] = $type;
					$_POST["post_action"] = "content";
				}

				// Error Checking
				switch ($STEP) {
					case 2 :
						/**
						 * Add this quiz_id of the quiz this question will be added to.
						 */
						$PROCESSED["quiz_id"] = $RECORD_ID;

						/**
						 * Required field "questiontype_id" / Question Type
						 * Currently only multile choice questions are supported, although
						 * this is something we will be expanding on shortly.
						 */
						if ((isset($_POST["questiontype_id"])) && ($tmp_input = clean_input($_POST["questiontype_id"], array("trim", "int")))) {
							$PROCESSED["questiontype_id"] = $tmp_input;
						} else {
							$PROCESSED["questiontype_id"] = 1;
						}

						/**
						 * Required field "question_text" / Quiz Question.
						 */
						if ((isset($_POST["question_text"])) && ($tmp_input = clean_input($_POST["question_text"], array("trim", "allowedtags")))) {
							$PROCESSED["question_text"] = $tmp_input;
						} else {
							add_error("The <strong>Quiz Question</strong> field is required.");
						}


						switch ($type) {
                            case 4 :
							case 3 :
							case 2 :
								$PROCESSED["question_points"] = 0;
							break;
							case 1 :
							default :
								$correct_response_found = false;
								$PROCESSED["quiz_question_responses"] = array();

								/**
								 * Required field "response_text" / Available Responses.
								 *
								 */
								if ((isset($_POST["response_text"])) && (is_array($_POST["response_text"]))) {
									$i = 1;
									foreach ($_POST["response_text"] as $response_key => $response_text) {
										$response_key		= clean_input($response_key, "int");
										$response_is_html	= 0;

										/**
										 * Check if this is response is in HTML or just plain text.
										 */
										if ((isset($_POST["response_is_html"])) && (is_array($_POST["response_is_html"])) && (isset($_POST["response_is_html"][$response_key])) && ($_POST["response_is_html"][$response_key] == 1)) {
											$response_is_html = 1;
										}

										if ($response_is_html) {
											$response_text	= clean_input($response_text, array("trim", "allowedtags"));
										} else {
											$response_text	= clean_input($response_text, array("trim"));
										}

										if (($response_key) && ($response_text != "")) {
											$PROCESSED["quiz_question_responses"][$i]["response_text"]	= $response_text;
											$PROCESSED["quiz_question_responses"][$i]["response_order"]	= $i;

											/**
											 * Check if this is the selected correct response or not.
											 */
											if ((isset($_POST["response_correct"])) && ($response_correct = clean_input($_POST["response_correct"], array("trim", "int"))) && ($response_key == $response_correct)) {
												$correct_response_found = true;
												$PROCESSED["quiz_question_responses"][$i]["response_correct"] = 1;
											} else {
												$PROCESSED["quiz_question_responses"][$i]["response_correct"] = 0;
											}

											$PROCESSED["quiz_question_responses"][$i]["response_is_html"] = $response_is_html;

											/**
											 * Check if there is feedback for this response.
											 */
											if ((isset($_POST["response_feedback"])) && (is_array($_POST["response_feedback"])) && (isset($_POST["response_feedback"][$response_key])) && ($tmp_input = clean_input($_POST["response_feedback"][$response_key], "notags"))) {
												$PROCESSED["quiz_question_responses"][$i]["response_feedback"] = $tmp_input;
											} else {
												$PROCESSED["quiz_question_responses"][$i]["response_feedback"] = "";
											}

											$i++;
										}
									}
								}

								/**
								 * There must be at least 2 possible responses to proceed.
								 */
								if (count($PROCESSED["quiz_question_responses"]) < 2) {
									add_error("You must provide at least <strong>two possible responses</strong> to this question.");
								}

								/**
								 * You must specify the correct response
								 */
								if (!$correct_response_found) {
									add_error("You must specify which of the responses is the <strong>correct response</strong>.");
								}

								/**
								 * Required field "question_points" / points for the correct response.
								 */
								if ((isset($_POST["question_points"])) && ($tmp_input = clean_input($_POST["question_points"], array("trim", "int")))) {
									$PROCESSED["question_points"] = $tmp_input;
								} else {
									add_error("You must provide the <strong>number of points</strong> given for the correct response to this question.");
								}

							break;
						}

						/**
						 * Get the next order of this question from the quiz_questions table.
						 */
                        $result["next_order"] = Models_Quiz_Question::fetchNextOrder($quiz_record->getQuizID());
						if ($result) {
							$PROCESSED["question_order"] = ($result["next_order"] + 1);
						} else {
							$PROCESSED["question_order"] = 0;
						}

						/**
						 * Non-Required field "randomize_responses" / response ordering radio buttons.
						 */
						if ((isset($_POST["randomize_responses"])) && ($tmp_input = clean_input($_POST["randomize_responses"], array("trim", "int")))) {
							$PROCESSED["randomize_responses"] = 1;
						} else {
							$PROCESSED["randomize_responses"] = 0;
						}

						if (isset($_POST["post_action"])) {
							switch (clean_input($_POST["post_action"], "alpha")) {
								case "new" :
									$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new".((int)clean_input($_POST["post_action"], "numeric") ? (int)clean_input($_POST["post_action"], "numeric") : 1);
								break;
								case "index" :
									$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
								break;
								case "content" :
								default :
									$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
								break;
							}
						} else {
							$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new1";
						}

						if (!$ERROR) {
                            
                            if (isset($type) && isset($qquestion_group_id) && $type == 1) {
                                $PROCESSED["qquestion_group_id"] = $qquestion_group_id;
                            }
							if($ENTRADA_ACL->amIAllowed(new QuizQuestionResource(null, $quiz_record->getQuizID()), 'create')) {
                                $quiz_question = new Models_Quiz_Question($PROCESSED);
								if ($quiz_question->insert()) {
									$qquestion_id = $quiz_question->getQquestionID();
                                    if ($qquestion_id) {
										/**
										 * Add the quiz question responses to the quiz_question_responses table.
										 * Ummm... we really need to switch to InnoDB tables to get transaction support.
										 */
										if ((is_array($PROCESSED["quiz_question_responses"])) && (count($PROCESSED["quiz_question_responses"]))) {
											foreach ($PROCESSED["quiz_question_responses"] as $quiz_question_response) {
												$PROCESSED = array (
																"qquestion_id"		=> $qquestion_id,
																"response_text"		=> $quiz_question_response["response_text"],
																"response_order"	=> $quiz_question_response["response_order"],
																"response_correct"	=> $quiz_question_response["response_correct"],
																"response_is_html"	=> $quiz_question_response["response_is_html"],
																"response_feedback"	=> $quiz_question_response["response_feedback"]
																);
                                                $quiz_question_response = new Models_Quiz_Question_Response($PROCESSED);
                                                if (!$quiz_question_response->insert()) {
													add_error("There was an error while trying to attach a <strong>Question Response</strong> to this quiz question.<br /><br />The system administrator was informed of this error; please try again later.");
													application_log("error", "Unable to insert a new quiz_question_responses record while adding a new quiz question [".$qquestion_id."] to quiz_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
												}
											}
										}

										switch (clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "alpha")) {
											case "new" :
												$url	= ENTRADA_URL."/admin/".$MODULE."?section=add-question&id=".$RECORD_ID."&type=".((int)clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "numeric") ? (int)clean_input($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"], "numeric") : 1);
												$msg	= "You will now be redirected to add another quiz question to this quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "index" :
												$url	= ENTRADA_URL."/admin/".$MODULE;
												$msg	= "You will now be redirected back to the quiz index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "content" :
											default :
												$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID;
												$msg	= "You will now be redirected back to the quiz; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
										}
										
										add_success("You have successfully added this question to your <strong>".html_encode($quiz_record->getQuizTitle())."</strong> quiz.<br /><br />".$msg);
										$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

										/**
										 * Unset the arrays used to construct this error checking.
										 */
										unset($PROCESSED);

										application_log("success", "New quiz question [".$qquestion_id."] added to quiz_id [".$RECORD_ID."].");
									} else {
										add_error("We were unable to add this question to your quiz at this time.<br /><br />The system administrator was informed of this error; please try again later.");
										application_log("error", "Failed to receive an Insert_Id() from the question insert to quiz_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
									}
								} else {
									add_error("There was a problem inserting this quiz question. The system administrator was informed of this error; please try again later.");
									application_log("error", "There was an error inserting a quiz question to quiz_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
								}
							} else {
								add_error("You do not have permission to create this quiz question. The system administrator was informed of this error; please try again later.");
								application_log("error", "There was an error inserting a quiz question to quiz_id [".$RECORD_ID."] because the user [".$ENTRADA_USER->getID()."] didn't have permission to create a quiz question.");
							}
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

				// Display Content
				switch ($STEP) {
					case 2 :
						if ($SUCCESS) {
							echo display_success();
						}
						if ($NOTICE) {
							echo display_notice();
						}
						if ($ERROR) {
							echo display_error();
						}
					break;
					case 1 :
					default :
						?>
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Quiz Information">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 22%" />
							<col style="width: 75%" />
						</colgroup>
						<thead>
							<tr>
								<td colspan="3"><h2 title="Quiz Content Information">Quiz Information</h2></td>
							</tr>
						</thead>
						<tbody id="quiz-content-information">
							<tr>
								<td></td>
								<td>Quiz Title</td>
								<td><strong><?php echo clean_input($quiz_record->getQuizTitle(), array("trim", "encode")); ?></strong></td>
							</tr>
							<?php
							if ($quiz_description = clean_input($quiz_record->getQuizDescription(), array("trim"))) {
								?>
								<tr>
									<td></td>
									<td style="vertical-align: top">Quiz Description</td>
									<td><?php echo $quiz_description; ?></td>
								</tr>
								<?php
							}
							?>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top">Quiz Authors</td>
								<td>
									<?php
                                    $quiz_authors = Models_Quiz_Contact::fetchAllRecords($RECORD_ID);
                                    if ($quiz_authors) {
										foreach ($quiz_authors as $quiz_author) {
                                            $author = User::fetchRowByID($quiz_author->getProxyID());
                                            if ($author->getID()) {
                                                $q_a[] = "<a href=\"mailto:".html_encode($author->getEmail())."\">".html_encode($author->getFullname(false))."</a>";
                                            }
										}
									}
                                    if (isset($q_a) && !empty($q_a)) {
                                        echo implode("; ", $q_a);
                                    }
									?>
								</td>
							</tr>
						</tbody>
						</table>
						<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add-question&amp;id=<?php echo $RECORD_ID; ?>&amp;type=<?php echo $type; ?><?php echo isset($qquestion_group_id) ? "&amp;qquestion_id=".$qquestion_group_id : ""; ?>&amp;step=2" method="post" id="addQuizQuestionForm" class="form-horizontal">
						<input type="hidden" name="questiontype_id" value="<?php echo $type; ?>" />
						<table style="width: 100%; margin-bottom: 25px" cellspacing="0" cellpadding="2" border="0" summary="Add Quiz Question">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 22%" />
							<col style="width: 75%" />
						</colgroup>
						<thead>
							<tr>
								<td colspan="3">
									<h2 title="Quiz Content Add Question">Add Quiz Question</h2>
                                    <?php if ($type != 1) { ?>
                                    <input type="hidden" id="question_points" name="question_points" value="0" />
                                    <?php } ?>
									<?php if ($type == 2 || $type == 4) { ?>
										<div class="display-generic"><strong>Note:</strong> The Descriptive Text will appear to users taking the quiz.</div>
									<?php } else if ($type == 3) { ?>
										<div class="display-generic"><strong>Note:</strong> The Descriptive Text will not appear to users taking the quiz, but will appear on the quiz management page.</div>
									<?php } ?>
									<?php
									if ($ERROR) {
										echo display_error();
									}

									if ($NOTICE) {
										echo display_notice();
									}
									?>
								</td>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td colspan="3" style="padding-top: 25px">
									<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="width: 25%; text-align: left">
											<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit&amp;id=<?php echo $RECORD_ID; ?>'" />
										</td>
										<td style="width: 75%; text-align: right; vertical-align: middle">
											<span class="content-small">After saving:</span>
											<select id="post_action" name="post_action">
												<option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Return to the quiz</option>
												<?php
												if (!$question_types || @count($question_types) <= 1) {
													?>
													<option value="new1"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new1") ? " selected=\"selected\"" : ""); ?>>Add another question</option>
													<?php
												} else {
													foreach ($question_types as $question_type) {
														echo "<option value=\"new".$question_type->getQuestionTypeID()."\"".(isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new".$question_type->getQuestionTypeID()) ? " selected=\"selected\"" : "").">Add ".($type == $question_type->getQuestionTypeID() ? "another " : "a new ").strtolower($question_type->getQuestionTypeTitle())."</option>";
													}
												}
												?>
												<option value="index"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to quiz index</option>
											</select>

											<input type="submit" class="btn btn-primary" value="Proceed" />
										</td>
									</tr>
									</table>
								</td>
							</tr>
						</tfoot>
						<tbody id="quiz-content-add-question">
							<tr>
								<td>&nbsp;</td>
								<td style="vertical-align: top">
									<label for="question_text" class="form-required"><?php echo ($type != 1 ? "Descriptive Text" : "Quiz Question"); ?></label>
								</td>
								<td>
									<textarea id="question_text" name="question_text" style="width: 98%; height: 100px"><?php echo ((isset($PROCESSED["question_text"])) ? clean_input($PROCESSED["question_text"], "encode") : ""); ?></textarea>
								</td>
							</tr>
							<?php if ($type == 1) { ?>
							<tr>
								<td>&nbsp;</td>
								<td style="padding-top: 5px; vertical-align: top">
									<label for="response_text_0" class="form-required">Available Responses</label>
								</td>
								<td style="padding-top: 5px">
									<table class="quiz-question" cellspacing="0" cellpadding="2" border="0" summary="Quiz Question Responses">
									<colgroup>
										<col style="width: 3%" />
										<col style="width: 77%" />
										<col style="width: 10%" />
										<col style="width: 10%" />
									</colgroup>
									<thead>
										<tr>
											<td colspan="2">&nbsp;</td>
											<td class="center" style="font-weight: bold; font-size: 11px">HTML</td>
											<td class="center" style="font-weight: bold; font-size: 11px">Correct</td>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach (range(1, 5) as $number) {
											$response_correct = (((isset($PROCESSED["quiz_question_responses"][$number]["response_correct"])) && ((int) $PROCESSED["quiz_question_responses"][$number]["response_correct"])) ? true : false);
											?>
											<tr>
												<td style="padding-top: 10px">
													<label for="response_text_<?php echo $number; ?>" class="form-<?php echo (($number > 2) ? "n" : ""); ?>required"><?php echo chr($number + 96); ?>)</label>
												</td>
												<td style="padding-top: 10px">
													<textarea class="expandable" id="response_text_<?php echo $number; ?>" name="response_text[<?php echo $number; ?>]" style="width: 99%; height: 20px"><?php echo ((isset($PROCESSED["quiz_question_responses"][$number]["response_text"])) ? clean_input($PROCESSED["quiz_question_responses"][$number]["response_text"], "encode") : ""); ?></textarea>
												</td>
												<td class="center" style="padding-top: 10px">
													<input type="checkbox" id="response_is_html_<?php echo $number; ?>" name="response_is_html[<?php echo $number; ?>]" value="1"<?php echo (((isset($PROCESSED["quiz_question_responses"][$number]["response_is_html"])) && ($PROCESSED["quiz_question_responses"][$number]["response_is_html"] == 1)) ? " checked=\"true\"" : ""); ?> onclick="toggleEditor('response_text_<?php echo $number; ?>')" />

													<?php
													if ((isset($PROCESSED["quiz_question_responses"][$number]["response_is_html"])) && ($PROCESSED["quiz_question_responses"][$number]["response_is_html"] == 1)) {
														?>
														<script type="text/javascript">
                                                        if (!CKEDITOR.instances['response_text_<?php echo $number; ?>']) {
                                                            CKEDITOR.replace('response_text_<?php echo $number; ?>');
                                                        }
														</script>
														<?php
													}
													?>
												</td>
												<td class="selectCorrect center" style="padding-top: 10px">
													<input type="radio" name="response_correct" id="feedback_term_<?php echo $number; ?>" value="<?php echo $number; ?>"<?php echo (($response_correct) ? " checked=\"true\"" : ""); ?> />
												</td>
											</tr>
											<tr>
												<td style="padding: 5px 0px 10px 0px; border-bottom: 1px #CCCCCC solid">&nbsp;</td>
												<td colspan="3" style="padding: 5px 0px 15px 0px; border-bottom: 1px #CCCCCC solid">
													<input type="checkbox" id="toggle_response_feedback_<?php echo $number; ?>" data-number="<?php echo html_encode($number); ?>" onclick="Effect.toggle('container_response_feedback_<?php echo $number; ?>', 'appear', { duration: 0.3 }); if (this.checked == true) setTimeout('$(\'response_feedback_<?php echo $number; ?>\').focus();', 50);"<?php echo (((isset($PROCESSED["quiz_question_responses"][$number]["response_feedback"])) && (trim($PROCESSED["quiz_question_responses"][$number]["response_feedback"]) != "")) ? " checked=\"true\"" : ""); ?> />
													<label for="toggle_response_feedback_<?php echo $number; ?>" class="form-nrequired" style="margin-left: 5px; vertical-align: middle"> Provide feedback if this response is chosen <span id="response_feedback_term_<?php echo $number; ?>" class="response_feedback_term <?php echo (($response_correct) ? "correctly" : "incorrectly"); ?>"><?php echo (($response_correct) ? "correctly" : "incorrectly"); ?></span>.</label>
													<div id="container_response_feedback_<?php echo $number; ?>" style="margin-left: 15px;<?php echo (((!isset($PROCESSED["quiz_question_responses"][$number]["response_feedback"])) || (trim($PROCESSED["quiz_question_responses"][$number]["response_feedback"]) == "")) ? " display: none" : ""); ?>">
														<textarea class="expandable" id="response_feedback_<?php echo $number; ?>" name="response_feedback[<?php echo $number; ?>]" style="width: 95%; height: 0px"><?php echo ((isset($PROCESSED["quiz_question_responses"][$number]["response_feedback"])) ? clean_input($PROCESSED["quiz_question_responses"][$number]["response_feedback"], "encode") : ""); ?></textarea>
													</div>
												</td>
											</tr>
											<?php
										}
										?>
									</tbody>
									</table>
									<script type="text/javascript">
                                    jQuery(document).ready(function () {
                                        jQuery("input[id^=toggle_response_feedback_]").on("change", function () {
                                            var number = jQuery(this).attr("data-number");
                                            if (jQuery(this).is(":checked")) {
                                                if (jQuery("#response_feedback_" + number).is(":disabled")) {
                                                    jQuery("#response_feedback_" + number).prop("disabled", false);
                                                }
                                            } else {
                                                jQuery("#response_feedback_" + number).prop("disabled", true);
                                            }
                                        });
                                    });    
                                        
									$$('table.quiz-question td.selectCorrect input[type=radio]').each(function (el) {
										$(el).observe('click', alterFeedbackTerm);
									});

									function alterFeedbackTerm(event) {
										var element = event.element();

										$$('span.response_feedback_term').each(function (el) {
											$(el.id).removeClassName('correctly');
											$(el.id).addClassName('incorrectly');
											$(el.id).innerHTML = 'incorrectly';
										});

										if (element.checked == true) {
											$('response_' + element.id).removeClassName('incorrectly');
											$('response_' + element.id).addClassName('correctly');
											$('response_' + element.id).innerHTML = 'correctly';
										}
									}
									</script>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td style="padding-top: 15px; vertical-align: top">
									<span class="form-required">Question Options</span>
								</td>
								<td style="padding: 15px 0px 15px 3%">
									<input type="radio" id="randomize_responses_0" name="randomize_responses" value="0"<?php echo (((!isset($PROCESSED["randomize_responses"])) || ($PROCESSED["randomize_responses"] == 0)) ? " checked=\"true\"" : ""); ?> /> <label for="randomize_responses_0" class="form-nrequired" style="margin-left: 5px; vertical-align: middle" /> Display these responses in the order I have provided.</label><br />
									<input type="radio" id="randomize_responses_1" name="randomize_responses" value="1"<?php echo (((isset($PROCESSED["randomize_responses"])) && ($PROCESSED["randomize_responses"] == 1)) ? " checked=\"true\"" : ""); ?> /> <label for="randomize_responses_1" class="form-nrequired" style="margin-left: 5px; vertical-align: middle"/> Display these responses in random order to the learner.</label>
									<br /><br />
									<input type="text" id="question_points" name="question_points" style="width: 25px; vertical-align: middle" maxlength="3" value="<?php echo (((isset($PROCESSED["question_points"])) && ($PROCESSED["question_points"] > 0) && ($PROCESSED["question_points"] < 1000)) ? $PROCESSED["question_points"] : 1); ?>" /><label for="question_points" class="form-nrequired" style="margin-left: 5px; vertical-align: middle">point(s) for the correct response.</label>
								</td>
							</tr>
							<?php } ?>
						</tbody>
						</table>
						</form>
						<?php
					break;
				}
			} else {
				add_error("You cannot add a question to a quiz that has already been taken. This precaution exists to protect the integrity of the data in the database.<br /><br />If you would like to add questions to this quiz you can <strong>copy the quiz</strong> from the <strong>Manage Quizzes</strong> index.");
				echo display_error();

				application_log("error", "Attempted to add a quiz question to quiz [".$RECORD_ID."] that has already been taken.");
			}
		} else {
			add_error("In order to add a question to a quiz, you must provide a valid quiz identifier.");
            echo display_error();

			application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to add a quiz question.");
		}
	} else {
        add_error("In order to add a question to a quiz, you must provide a quiz identifier.");
        echo display_error();

		application_log("notice", "Failed to provide a quiz identifier when attempting to add a quiz question.");
	}
}