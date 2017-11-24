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
 * This file is used by quiz authors to attach a quiz to a particular learning
 * event.
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
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($QUIZ_TYPE == "event") {
		if ($RECORD_ID) {
			$quiz = Models_Quiz::fetchRowByID($RECORD_ID);
			$quiz_record	= $quiz->toArray();
			if ($quiz_record && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz_record["quiz_id"]), 'update')) {
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID, "title" => limit_chars($quiz_record["quiz_title"], 32));
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=attach&id=".$RECORD_ID, "title" => "Attach To Learning Event");

				/**
				 * Used to store cleaned processed events ids this quiz will be
				 * attached to.
				 */
				$PROCESSED["event_ids"]	= array();

				/**
				 * Check to see if there is an event already being passed in the URL, if so we need to
				 * go directly to step 2, as step 1 is simply choosing the event.
				 *
				 * @todo I really need to finish the program coordinator and course director moves to the
				 * course_contacts table so that they can also easily do this. It's a pain having that in
				 * information in two different tables right now.
				 *
				 * @todo Allow course directors to do this.
				 */
				if ((isset($_GET["event"])) && ($tmp_input = clean_input($_GET["event"], array("int")))) {
					$STEP = 2;
					$_POST["event_ids"] = array($tmp_input);
				}

				/**
				 * Required field "event_ids" / Learning Event
				 * This processing applies to all steps.
				 */
				if (isset($_POST["event_ids"])) {
					if (is_array($_POST["event_ids"])) {
						foreach ($_POST["event_ids"] as $value) {
							if ($tmp_input = clean_input($value, array("int"))) {
								$query	= "	SELECT a.`event_id`, a.`course_id`, b.`organisation_id`
											FROM `events` AS a
											LEFT JOIN `courses` AS b
											ON b.`course_id` = a.`course_id`
											WHERE a.`event_id` = ".$db->qstr($tmp_input)."
											AND b.`course_active` = '1'";
								$result	= $db->GetRow($query);
								if ($result) {
									if ($ENTRADA_ACL->amIAllowed(new EventContentResource($result["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
										$PROCESSED["event_ids"][] = $tmp_input;
									}
								}
							}
						}
					}
				}

				$quiz_types_record = array();
                $quiz_types = Models_Quiz_QuizType::fetchAllRecords();
                if ($quiz_types) {
					foreach ($quiz_types as $quiz_type) {
						$quiz_types_record[$quiz_type->getQuizTypeID()] = array("quiztype_title" => $quiz_type->getQuizTypeTitle(), "quiztype_description" => $quiz_type->getQuizTypeDescription(), "quiztype_order" => $quiz_type->getQuizTypeOrder());
					}
				}

				$existing_event_relationship = array();
                $attached_events = Models_Quiz_Attached_Event::fetchAllByQuizID($RECORD_ID);
				if ($attached_events) {
					foreach ($attached_events as $attached_event) {
						$existing_event_relationship[] = $attached_event->getEventID();
					}
				}

				$default_event_start	= 0;
				$default_event_finish	= 0;
				$selected_learning_events = array();
				if (count($PROCESSED["event_ids"]) > 0) {
					$query		= "SELECT * FROM `events` WHERE `event_id` IN (".implode(", ", $PROCESSED["event_ids"]).")";
					$results	= $db->Execute($query);
					if ($results) {
						foreach ($results as $result) {
							$selected_learning_events[$result["event_id"]] = array("event_title" => $result["event_title"], "event_start" => $result["event_start"], "event_finish" => $result["event_finish"], "event_duration" => $result["event_duration"]);

							if ((!$default_event_start) || ($result["event_start"] < $default_event_start)) {
								$default_event_start = $result["event_start"];
							}

							if ((!$default_event_finish) || ($result["event_finish"] > $default_event_finish)) {
								$default_event_finish = $result["event_finish"];
							}
						}
					}
				}

				echo "<h1>Attach To Learning Event</h1>\n";

				// Error Checking
				switch ($STEP) {
					case 3 :
						$PROCESSED["quiz_id"]	= $RECORD_ID;
						$PROCESSED["accesses"]	= 0;

						/**
						 * Ensure there are valid event_ids provided.
						 */
						if (count($PROCESSED["event_ids"]) < 1) {
							add_error("To attach a quiz to a learning event, select a learning event you are involved with from the list below.");
						}

						/**
						 * Required field "quiz_title" / Attached Quiz Title.
						 */
						if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
							$PROCESSED["quiz_title"] = $tmp_input;
						} else {
							add_error("The <strong>Attached Quiz Title</strong> field is required.");
						}

						/**
						 * Non-Required field "quiz_notes" / Attached Quiz Instructions.
						 */
						if ((isset($_POST["quiz_notes"])) && ($tmp_input = clean_input($_POST["quiz_notes"], array("trim", "allowedtags")))) {
							$PROCESSED["quiz_notes"] = $tmp_input;
						} else {
							$PROCESSED["quiz_notes"] = "";
						}

						/**
						 * Non-required field "required" / Should completion of this quiz by the learner be considered optional or required?
						 */
						if ((isset($_POST["required"])) && ($_POST["required"] == 1)) {
							$PROCESSED["required"] = 1;
						} else {
							$PROCESSED["required"] = 0;
						}

                        /**
                         * Non-required field "random_order" / Should quiz question order be shuffled?
                         */
                        if ((isset($_POST["random_order"])) && ($_POST["random_order"] == 1)) {
                            $PROCESSED["random_order"] = 1;
                        } else {
                            $PROCESSED["random_order"] = 0;
                        }

						/**
						 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
						 */
						if ((isset($_POST["quiztype_id"])) && (array_key_exists($_POST["quiztype_id"], $quiz_types_record)) && ($tmp_input = clean_input($_POST["quiztype_id"], "int"))) {
							$PROCESSED["quiztype_id"] = $tmp_input;
						} else {
							add_error("Please select a proper quiz type when asked what type of quiz this should be considered.");
						}

						/**
						 * Non-required field "quiz_timeout" / How much time (in minutes) can the learner spend completing this quiz?
						 * 0 indicates unlimited
						 */
						if (isset($_POST["quiz_timeout"])) {
							$tmp_input = clean_input($_POST["quiz_timeout"], "int");
							if ((($tmp_input === 0) || ($tmp_input > 0)) && ($tmp_input <= 2880)) {
								$PROCESSED["quiz_timeout"] = $tmp_input;
							} else {
								$PROCESSED["quiz_timeout"] = 0;
							}
						} else {
							$PROCESSED["quiz_timeout"] = 0;
						}

						/**
						 *
						 * Non-required field "quiz_attempts" / How many attempts can a learner take at completing this quiz?
						 * 0 indicates unlimited
						 */

						if (isset($_POST["quiz_attempts"])) {
							$tmp_input = clean_input($_POST["quiz_attempts"], "int");
							if ((($tmp_input === 0) || ($tmp_input > 0)) && ($tmp_input <= 999)) {
								$PROCESSED["quiz_attempts"] = $tmp_input;
							} else {
								$PROCESSED["quiz_attempts"] = 0;
							}
						} else {
							$PROCESSED["quiz_attempts"] = 0;
						}

                        /**
                         * Required field "timeframe" / When should this quiz be taken in relation to the event?
                         */
                        if ((isset($_POST["timeframe"])) && ($tmp_input = clean_input($_POST["timeframe"], "trim")) && (array_key_exists($tmp_input, $RESOURCE_TIMEFRAMES["event"]))) {
                            $PROCESSED["timeframe"] = $tmp_input;
                        } else {
                            $PROCESSED["timeframe"] = "";
                        }

                        /**
                         * Non-required field "require_attendance" / Should completion of this quiz be limited by the learner's event attendance?
                         */
                        if ((isset($_POST["require_attendance"])) && ($_POST["require_attendance"] == 1)) {
                            $PROCESSED["require_attendance"] = 1;
                        } else {
                            $PROCESSED["require_attendance"] = 0;
                        }

						/**
						 * Non-required field "release_date" / Accessible Start (validated through validate_calendars function).
						 * Non-required field "release_until" / Accessible Finish (validated through validate_calendars function).
						 */
						switch ($PROCESSED["timeframe"]) {
							case "pre" :
								$require_start	= false;
								$require_finish	= true;
							break;
							case "during" :
								$require_start	= true;
								$require_finish	= true;
							break;
							case "post" :
								$require_start	= true;
								$require_finish	= false;
							break;
							default :
								$require_start	= false;
								$require_finish	= false;
							break;
						}

                        if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
                            $quiztype = Models_Quiz_QuizType::fetchRowByID($PROCESSED["quiztype_id"]);
                            if ($quiztype) {
                                if ($quiztype->getQuizTypeCode() == "delayed") {
                                    $require_finish = true;
                                }
                            }
                        }

						$viewable_date = validate_calendars("accessible", $require_start, $require_finish);
						if ((isset($viewable_date["start"])) && ((int) $viewable_date["start"])) {
							$PROCESSED["release_date"] = (int) $viewable_date["start"];
						} else {
							$PROCESSED["release_date"] = 0;
						}

						if ((isset($viewable_date["finish"])) && ((int) $viewable_date["finish"])) {
							$PROCESSED["release_until"] = (int) $viewable_date["finish"];
						} else {
							$PROCESSED["release_until"] = 0;
						}

						if (!$ERROR) {
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

							/**
							 * Adding this quiz to each of the selected events.
							 */
							foreach ($PROCESSED["event_ids"] as $event_id) {
								$PROCESSED["content_id"] = $event_id;
								$PROCESSED["content_type"] = "event";

                                $attached_quiz = new Models_Quiz_Attached($PROCESSED);
                                
								if ($attached_quiz->insert()) {
                                    $event_resource_entity = new Models_Event_Resource_Entity(
                                        array(
                                            "event_id" => $event_id,
                                            "entity_type" => 8,
                                            "entity_value" => $attached_quiz->getAquizID(),
                                            "release_date" => 0,
                                            "release_until" => 0,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID(),
                                            "active" => 1
                                        )
                                    );

                                    if (!$event_resource_entity->insert()) {
                                        $ERROR++;
                                        $ERRORSTR[] = "There was an error while trying to save the selected <strong>Event File</strong> for this event $EVENT_ID.<br /><br />The system administrator was informed of this error; please try again later.";
                                        application_log("error", "Unable to insert a new event_file record while copying a new event $EVENT_ID. Database said: ".$db->ErrorMsg());
                                    }

                                    add_success("You have successfully attached <strong>".html_encode($quiz_record["quiz_title"])."</strong> as <strong>".html_encode($PROCESSED["quiz_title"])."</strong> to <strong>".html_encode($selected_learning_events[$PROCESSED["event_id"]]["event_title"])."</strong>.");

                                    application_log("success", "Quiz [".$RECORD_ID."] was successfully attached to Event [".$PROCESSED["event_id"]."].");

								} else {
									add_error("There was a problem attaching this quiz to <strong>".html_encode($selected_learning_events[$PROCESSED["event_id"]]["event_title"])."</strong>. The system administrator was informed of this error; please try again later.");

									application_log("error", "There was an error attaching quiz [".$RECORD_ID."] to event [".$PROCESSED["event_id"]."]. Database said: ".$db->ErrorMsg());
								}
							}

							if ($SUCCESS) {
								$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID;
								$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

								$SUCCESSSTR[(count($SUCCESSSTR) - 1)] .= "<br /><br />You will now be redirected back to the quiz page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							} elseif ($ERROR) {
								$STEP = 2;
							}
						} else {
							$STEP = 2;
						}
					break;
					case 2 :
						/**
						 * Ensure there are valid event_ids provided.
						 */
						if (count($PROCESSED["event_ids"]) < 1) {
							
							$ERRORSTR[] = "To attach a quiz to a learning event, select a learning event you are involved with from the list below.";
						}

						/**
						 * If the quiz title is not set, give it the default one.
						 */
						if (!isset($_POST["quiz_title"])) {
							$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
					case 3 :
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
					case 2 :
						/**
						 * Load the rich text editor.
						 */
						load_rte();
						$total_event_ids = count($PROCESSED["event_ids"]);

						?>
						<h2 style="color: #CCCCCC">Step 1: Select Learning Events</h2>
						<ul class="menu">
							<?php
							if ($selected_learning_events) {
								foreach ($selected_learning_events as $event_id => $result) {
									echo "<li class=\"checkmark\">";
									echo "	<a href=\"".ENTRADA_URL."/events?id=".$event_id."\" style=\"font-weight: bold; color: #666666\">".html_encode($result["event_title"])."</a> <span class=\"content-small\">Event on ".date(DEFAULT_DATE_FORMAT, $result["event_start"])."</span>";
									if (in_array($event_id, $existing_event_relationship)) {
										echo "<span class=\"display-notice-inline\"><img src=\"".ENTRADA_URL."/images/list-notice.gif\" width=\"11\" height=\"11\" alt=\"Notice\" title=\"Notice\" style=\"margin-right: 10px\" />This specific quiz is already attached to this event.</span>";
									}
									echo "</li>\n";
								}
							}
							?>
						</ul>
						<h2>Step 2: Choose Quiz Options</h2>
						<?php
						if($ERROR) {
							echo display_error();
						}

						if($NOTICE) {
							echo display_notice();
						}
						?>
						<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;id=<?php echo $RECORD_ID; ?>" method="post">
						<input type="hidden" name="step" value="3" />
						<?php
						foreach ($PROCESSED["event_ids"] as $event_id) {
							echo "<input type=\"hidden\" name=\"event_ids[]\" value=\"".$event_id."\" />\n";
						}
						?>
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Quiz">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 23%" />
							<col style="width: 77%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="3" style="padding-top: 50px">
									<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="width: 25%; text-align: left">
											<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
										</td>
										<td style="width: 75%; text-align: right; vertical-align: middle">
											<input type="submit" class="btn btn-primary" value="Proceed" />
										</td>
									</tr>
									</table>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td colspan="3">
									<div class="display-generic">
										In order to attach this quiz to <?php echo (($total_event_ids != 1) ? "these learning events" : "this learning event"); ?>, we need just a bit more information. Please answer the following questions <strong>as they pertain</strong> to the learning event<?php echo (($total_event_ids != 1) ? "s" : ""); ?> you selected in step 1.
									</div>
								</td>
							</tr>
							<tr>
								<td></td>
								<td><label for="quiz_title" class="form-required">Attached Quiz Title</label></td>
								<td><input type="text" id="quiz_title" name="quiz_title" value="<?php echo html_encode($PROCESSED["quiz_title"]); ?>" maxlength="128" style="width: 96%" /></td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label> <span class="content-small">(<strong>Tip:</strong> this information is visibile to students at the top of the quiz)</span>
								</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 125px" cols="70" rows="10"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									Should completion of this quiz be considered optional or required?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
									<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									Should the order of the questions be shuffled for this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
                                    <input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
                                    <input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									How much time (in minutes) can the learner spend taking this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									How many attempts can a learner take at completing this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									When should learners be allowed to view the results of the quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<?php
									if ($quiz_types_record) {
										foreach($quiz_types_record as $quiztype_id => $result) {
											echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".((isset($PROCESSED["quiztype_id"])) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label>";
											if ($result["quiztype_description"] != "") {
												echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
											}
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									Is attendance required for this quiz to be completed?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
                                    <input type="radio" id="require_attendance_no" name="require_attendance" value="0"<?php echo (((!isset($PROCESSED["require_attendance"])) || (!$PROCESSED["require_attendance"])) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_no">Not Required</label><br />
                                    <input type="radio" id="require_attendance_yes" name="require_attendance" value="1"<?php echo (($PROCESSED["require_attendance"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_yes">Required</label><br />
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									When should this quiz be taken in relation to the event?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<?php
									if (is_array($RESOURCE_TIMEFRAMES["event"])) {
										foreach($RESOURCE_TIMEFRAMES["event"] as $key => $value) {
											echo "<input type=\"radio\" id=\"timeframe_".$key."\" name=\"timeframe\" value=\"".$key."\" style=\"vertical-align: middle\"".((isset($PROCESSED["timeframe"])) ? (($PROCESSED["timeframe"] == $key) ? " checked=\"checked\"" : "") : (($key == "none") ? " checked=\"checked\"" : ""))." onclick=\"selectedTimeframe(this.value)\" /> <label for=\"timeframe_".$key."\">".$value."</label><br />";
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td colspan="3"><h2>Time Release Options</h2></td>
							</tr>
							<?php echo generate_calendars("accessible", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
						</tbody>
						</table>
						</form>
						<script type="text/javascript">
						function selectedTimeframe(timeframe) {
							switch (timeframe) {
								case 'pre' :
									$('accessible_start').checked	= false;
									$('accessible_finish').checked	= true;

									dateLock('accessible_start');
									dateLock('accessible_finish');

									$('accessible_start_date').value	= '';
									$('accessible_start_hour').value	= '00';
									$('accessible_start_min').value		= '00';

									$('accessible_finish_date').value	= '<?php echo date("Y-m-d", $default_event_start); ?>';
									$('accessible_finish_hour').value	= '<?php echo date("H", $default_event_start); ?>';
									$('accessible_finish_min').value	= '<?php echo date("i", $default_event_start); ?>';
								break;
								case 'during' :
									$('accessible_start').checked	= true;
									$('accessible_finish').checked	= true;

									dateLock('accessible_start');
									dateLock('accessible_finish');

									$('accessible_start_date').value	= '<?php echo date("Y-m-d", $default_event_start); ?>';
									$('accessible_start_hour').value	= '<?php echo date("H", $default_event_start); ?>';
									$('accessible_start_min').value		= '<?php echo date("i", $default_event_start); ?>';

									$('accessible_finish_date').value	= '<?php echo date("Y-m-d", $default_event_finish); ?>';
									$('accessible_finish_hour').value	= '<?php echo date("H", $default_event_finish); ?>';
									$('accessible_finish_min').value	= '<?php echo date("i", $default_event_finish); ?>';
								break;
								case 'post' :
									$('accessible_start').checked	= true;
									$('accessible_finish').checked	= false;

									dateLock('accessible_start');
									dateLock('accessible_finish');

									$('accessible_start_date').value	= '<?php echo date("Y-m-d", $default_event_finish); ?>';
									$('accessible_start_hour').value	= '<?php echo date("H", $default_event_finish); ?>';
									$('accessible_start_min').value		= '<?php echo date("i", $default_event_finish); ?>';

									$('accessible_finish_date').value	= '';
									$('accessible_finish_hour').value	= '00';
									$('accessible_finish_min').value	= '00';
								break;
								default :
									$('accessible_start').checked	= false;
									$('accessible_finish').checked	= false;

									dateLock('accessible_start');
									dateLock('accessible_finish');

									$('accessible_start_date').value	= '';
									$('accessible_start_hour').value	= '00';
									$('accessible_start_min').value		= '00';

									$('accessible_finish_date').value	= '';
									$('accessible_finish_hour').value	= '00';
									$('accessible_finish_min').value	= '00';
								break;
							}

							updateTime('accessible_start');
							updateTime('accessible_finish');
						}
						</script>
						<?php
					break;
					case 1 :
					default :
						$NOTICE++;
						$NOTICESTR[] = "Please begin by selecting one or more of your existing learning events to attach this quiz to.<br /><br />To select learning events simply check the box to the left of the event and click the &quot;Attach Selected&quot; button.";
						?>
						<h2>Step 1: Select Learning Events</h2>
						<?php
						if($ERROR) {
							echo display_error();
						}

						if($NOTICE) {
							echo display_notice();
						}

						/**
						 * Update requested timestamp to display.
						 * Valid: Unix timestamp
						 */
						if((isset($_GET["dlength"])) && ($dlength = (int) trim($_GET["dlength"])) && ($dlength >= 1) && ($dlength <= 4)) {
							$_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] = $dlength;

							$_SERVER["QUERY_STRING"] = replace_query(array("dlength" => false));
						} else {
							if(!isset($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"])) {
								$_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] = 2; // Defaults to this term.
							}
						}

						switch($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"]) {
							case 1 :	// Last Term
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, (date("Y", time()) - 1));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, (date("Y", time()) - 1));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
								}
							break;
							case 3 :	// This Month
								$display_duration["start"]		= mktime(0, 0, 0, date("n", time()), 1, date("Y", time()));
								$display_duration["end"]		= mktime(0, 0, 0, date("n", time()), date("t", time()), date("Y", time()));
							break;
							case 4 :	// Next Term
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, (date("Y", time()) + 1));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, (date("Y", time()) + 1));
								}
							break;
							case 2 :	// This Term
							default :
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
								}
							break;
						}
						?>
						<div style="float: right; margin-bottom: 10px">
							<form id="dlength_form" action="<?php echo ENTRADA_URL."/admin/".$MODULE; ?>" method="get">
							<input type="hidden" name="section" value="attach" />
							<input type="hidden" name="id" value="<?php echo $RECORD_ID; ?>" />
							<label for="dlength" class="content-small">Show Events Taking Place:</label>
							<select id="dlength" name="dlength" onchange="$('dlength_form').submit()">
							<option value="1"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] == 1) ? " selected=\"selected\"" : ""); ?>>Last Term</option>
							<option value="2"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] == 2) ? " selected=\"selected\"" : ""); ?>>This Term</option>
							<option value="3"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] == 3) ? " selected=\"selected\"" : ""); ?>>This Month</option>
							<option value="4"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] == 4) ? " selected=\"selected\"" : ""); ?>>Next Term</option>
							</select>
							</form>
						</div>
						<div style="clear: both"></div>
						<?php
						$query		= "	SELECT a.*, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`
										FROM `events` AS a
										LEFT JOIN `event_contacts` AS b
										ON b.`event_id` = a.`event_id`
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
										ON c.`id` = b.`proxy_id`
										WHERE (a.`event_start` BETWEEN ".$db->qstr($display_duration["start"])." AND ".$db->qstr($display_duration["end"]).")
										AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
										ORDER BY a.`event_start` ASC";
						$results	= $db->GetAll($query);
						if($results) {
							$total_events_found = count($results);
							?>
							<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;id=<?php echo $RECORD_ID; ?>" method="post">
							<input type="hidden" name="step" value="2" />
							<div class="tableListTop">
								<img src="<?php echo ENTRADA_URL; ?>/images/lecture-info.gif" width="15" height="15" alt="" title="" style="vertical-align: middle" />
								<?php echo "Found ".$total_events_found." event".(($total_events_found != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $display_duration["start"])."</strong> to <strong>".date("D, M jS, Y", $display_duration["end"])."</strong>.\n"; ?>
							</div>
							<table class="tableList" cellspacing="0" summary="My Teaching Events">
							<colgroup>
								<col class="modified" />
								<col class="date" />
								<col class="title" />
								<col class="attachment" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="date sortedASC"><div class="noLink">Date &amp; Time</div></td>
									<td class="title">Event Title</td>
									<td class="attachment">&nbsp;</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td></td>
									<td colspan="3" style="padding-top: 10px">
										<input type="submit" class="btn btn-primary" value="Attach Selected" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<?php
								foreach ($results as $result) {
									$attachments	= attachment_check($result["event_id"]);
									$url			= ENTRADA_URL."/admin/events?section=content&id=".$result["event_id"];

									$allow_attachment = true;

									echo "<tr id=\"event-".$result["event_id"]."\" class=\"event".((!$allow_attachment) ? " disabled" : "")."\">\n";
									echo "	<td".((!$allow_attachment) ? " class=\"disabled\"" : "")."><input type=\"checkbox\" name=\"event_ids[]\" value=\"".$result["event_id"]."\"".((!$allow_attachment) ? " disabled=\"disabled\"" : "")." /></td>\n";
									echo "	<td".((!$allow_attachment) ? " class=\"disabled\"" : "")."><a href=\"".$url."\">".date(DEFAULT_DATE_FORMAT, $result["event_start"])."</a></td>\n";
									echo "	<td".((!$allow_attachment) ? " class=\"disabled\"" : "")."><a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">".html_encode($result["event_title"])."</a></td>\n";
									echo "	<td".((!$allow_attachment) ? " class=\"disabled\"" : "").">".(($attachments) ? "<img src=\"".ENTRADA_URL."/images/attachment.gif\" width=\"16\" height=\"16\" alt=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" title=\"Contains ".$attachments." attachment".(($attachments != 1) ? "s" : "")."\" />" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />")."</td>\n";
									echo "</tr>\n";
								}
								?>
							</tbody>
							</table>
							</form>
							<?php
						} else {
							?>
							<div class="display-generic" style="margin-top: 0px">
								There is no record of any teaching events in the system for
								<?php
								switch($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"]) {
									case 1 :
										echo "<strong>last term</strong>.";
									break;
									case 3 :
										echo "<strong>this month</strong>.";
									break;
									case 4 :
										echo "<strong>next term</strong>.";
										break;
									case 2 :
									default :
										echo "<strong>this term</strong>.";
									break;
								}
								?>
								<br /><br />
								You can switch the display period by selecting a different date period in the &quot;Show Events Taking Place&quot; select box above.
							</div>
							<?php
						}
					break;
				}
			} else {
				
				$ERRORSTR[] = "In order to attach this quiz to a learning event, you must provide a valid quiz identifier.";

				echo display_error();

				application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to attach a quiz to a learning event.");
			}
		} else {
			
			$ERRORSTR[] = "In order to attach this quiz to a learning event, you must provide a quiz identifier.";

			echo display_error();

			application_log("notice", "Failed to provide a quiz identifier to attach a quiz to a learning event.");
		}
	} else {
		if ($RECORD_ID) {
			$quiz = Models_Quiz::fetchRowByID($RECORD_ID);
			$quiz_record	= $quiz->toArray();
			if ($quiz_record && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz_record["quiz_id"]), 'update')) {
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID, "title" => limit_chars($quiz_record["quiz_title"], 32));
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?section=attach&id=".$RECORD_ID, "title" => "Attach To Community Page");

				/**
				 * Used to store cleaned processed events ids this quiz will be
				 * attached to.
				 */
				$PROCESSED["page_ids"]	= array();

				/**
				 * Check to see if there is an page already being passed in the URL, if so we need to
				 * go directly to step 2, as step 1 is simply choosing the event.
				 */
				if ((isset($_GET["page_id"])) && ($tmp_input = clean_input($_GET["page_id"], array("int")))) {
					$STEP = 2;
					$_POST["page_ids"] = array($tmp_input);
				}

				/**
				 * Required field "event_ids" / Community Page
				 * This processing applies to all steps.
				 */
				if (isset($_POST["page_ids"])) {
					if (is_array($_POST["page_ids"])) {
						foreach ($_POST["page_ids"] as $value) {
							if ($tmp_input = clean_input($value, array("int"))) {
								$query	= "	SELECT a.`community_id`, b.`cpage_id`
											FROM `communities` AS a
											JOIN `community_pages` AS b
											ON b.`community_id` = a.`community_id`
											JOIN `community_members` AS c
											ON c.`community_id` = a.`community_id`
											AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
											AND c.`member_acl` = '1'
											WHERE b.`cpage_id` = ".$db->qstr($tmp_input)."
											AND a.`community_active` = '1'
											AND b.`page_active` = '1'";
								$result	= $db->GetRow($query);
								if ($result) {
									$PROCESSED["page_ids"][] = $tmp_input;
								}
							}
						}
					}
				}

				
				$quiz_types_record = array();
                $quiz_types = Models_Quiz_QuizType::fetchAllRecords();
				if ($quiz_types) {
					foreach ($quiz_types as $quiz_type) {
						$quiz_types_record[$quiz_type->getQuizTypeID()] = array("quiztype_title" => $quiz_type->getQuizTypeTitle(), "quiztype_description" => $quiz_type->getQuizTypeDescription(), "quiztype_order" => $quiz_type->getQuizTypeOrder());
					}
				}

				$existing_page_relationship = array();
                $attached_pages = Models_Quiz_Attached_CommunityPage::fetchAllByQuizID($RECORD_ID);
				if ($attached_events) {
					foreach ($attached_pages as $attached_page) {
						$existing_page_relationship[] = $attached_page->getCommunityID();
					}
				}

				$default_event_start	= 0;
				$default_event_finish	= 0;
				$selected_community_pages = array();
				if (count($PROCESSED["page_ids"]) > 0) {
					$query		= "	SELECT a.`cpage_id`, CONCAT('[', b.`community_title`, '] ', a.`page_title`) AS `page_title`
									FROM `community_pages` AS a
									JOIN `communities` AS b
									ON a.`community_id` = b.`community_id`
									WHERE `cpage_id` IN (".implode(", ", $PROCESSED["page_ids"]).")";
					$results	= $db->Execute($query);
					if ($results) {
						foreach ($results as $result) {
							$selected_community_pages[$result["cpage_id"]] = array("page_title" => $result["page_title"]);
						}
					}
				}

				echo "<h1>Attach To Community Page</h1>\n";

				// Error Checking
				switch ($STEP) {
					case 3 :
						$PROCESSED["quiz_id"]	= $RECORD_ID;
						$PROCESSED["accesses"]	= 0;

						/**
						 * Ensure there are valid event_ids provided.
						 */
						if (count($PROCESSED["page_ids"]) < 1) {
							
							$ERRORSTR[] = "To attach a quiz to a community page, select a community page you are involved with from the list below.";
						}

						/**
						 * Required field "quiz_title" / Attached Quiz Title.
						 */
						if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
							$PROCESSED["quiz_title"] = $tmp_input;
						} else {
							
							$ERRORSTR[] = "The <strong>Attached Quiz Title</strong> field is required.";
						}

						/**
						 * Non-Required field "quiz_notes" / Attached Quiz Instructions.
						 */
						if ((isset($_POST["quiz_notes"])) && ($tmp_input = clean_input($_POST["quiz_notes"], array("trim", "allowedtags")))) {
							$PROCESSED["quiz_notes"] = $tmp_input;
						} else {
							$PROCESSED["quiz_notes"] = "";
						}

						/**
						 * Non-required field "required" / Should completion of this quiz by the learner be considered optional or required?
						 */
						if ((isset($_POST["required"])) && ($_POST["required"] == 1)) {
							$PROCESSED["required"] = 1;
						} else {
							$PROCESSED["required"] = 0;
						}

                        /**
                         * Non-required field "random_order" / Should quiz question order be shuffled?
                         */
                        if ((isset($_POST["random_order"])) && ($_POST["random_order"] == 1)) {
                            $PROCESSED["random_order"] = 1;
                        } else {
                            $PROCESSED["random_order"] = 0;
                        }

						/**
						 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
						 */
						if ((isset($_POST["quiztype_id"])) && (array_key_exists($_POST["quiztype_id"], $quiz_types_record)) && ($tmp_input = clean_input($_POST["quiztype_id"], "int"))) {
							$PROCESSED["quiztype_id"] = $tmp_input;
						} else {
							$ERRORSTR[]	= "Please select a proper quiz type when asked what type of quiz this should be considered.";
						}

						/**
						 * Non-required field "quiz_timeout" / How much time (in minutes) can the learner spend completing this quiz?
						 * 0 indicates unlimited
						 */
						if (isset($_POST["quiz_timeout"])) {
							$tmp_input = clean_input($_POST["quiz_timeout"], "int");
							if ((($tmp_input === 0) || ($tmp_input > 0)) && ($tmp_input <= 2880)) {
								$PROCESSED["quiz_timeout"] = $tmp_input;
							} else {
								$PROCESSED["quiz_timeout"] = 0;
							}
						} else {
							$PROCESSED["quiz_timeout"] = 0;
						}

						/**
						 *
						 * Non-required field "quiz_attempts" / How many attempts can a learner take at completing this quiz?
						 * 0 indicates unlimited
						 */

						if (isset($_POST["quiz_attempts"])) {
							$tmp_input = clean_input($_POST["quiz_attempts"], "int");
							if ((($tmp_input === 0) || ($tmp_input > 0)) && ($tmp_input <= 999)) {
								$PROCESSED["quiz_attempts"] = $tmp_input;
							} else {
								$PROCESSED["quiz_attempts"] = 0;
							}
						} else {
							$PROCESSED["quiz_attempts"] = 0;
						}

                        /**
                         * Required field "timeframe" / When should this quiz be taken in relation to the event?
                         */
                        if ((isset($_POST["timeframe"])) && ($tmp_input = clean_input($_POST["timeframe"], "trim")) && (array_key_exists($tmp_input, $RESOURCE_TIMEFRAMES["event"]))) {
                            $PROCESSED["timeframe"] = $tmp_input;
                        } else {
                            $PROCESSED["timeframe"] = "";
                        }

                        /**
                         * Non-required field "require_attendance" / Should completion of this quiz be limited by the learner's event attendance?
                         */
                        if ((isset($_POST["require_attendance"])) && ($_POST["require_attendance"] == 1)) {
                            $PROCESSED["require_attendance"] = 1;
                        } else {
                            $PROCESSED["require_attendance"] = 0;
                        }

						/**
						 * Non-required field "release_date" / Accessible Start (validated through validate_calendars function).
						 * Non-required field "release_until" / Accessible Finish (validated through validate_calendars function).
						 */
						switch ($PROCESSED["timeframe"]) {
							case "pre" :
								$require_start	= false;
								$require_finish	= true;
							break;
							case "during" :
								$require_start	= true;
								$require_finish	= true;
							break;
							case "post" :
								$require_start	= true;
								$require_finish	= false;
							break;
							default :
								$require_start	= false;
								$require_finish	= false;
							break;
						}

                        if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
                            $query = "SELECT `quiztype_code` FROM `quizzes_lu_quiztypes` WHERE `quiztype_id` = ".$db->qstr($PROCESSED["quiztype_id"]);
                            $quiztype = $db->GetOne($query);
                            if ($quiztype == "delayed") {
                                $require_finish = true;
                            }
                        }

						$viewable_date = validate_calendars("accessible", $require_start, $require_finish);
						if ((isset($viewable_date["start"])) && ((int) $viewable_date["start"])) {
							$PROCESSED["release_date"] = (int) $viewable_date["start"];
						} else {
							$PROCESSED["release_date"] = 0;
						}

						if ((isset($viewable_date["finish"])) && ((int) $viewable_date["finish"])) {
							$PROCESSED["release_until"] = (int) $viewable_date["finish"];
						} else {
							$PROCESSED["release_until"] = 0;
						}

						if (!$ERROR) {
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

							/**
							 * Adding this quiz to each of the selected events.
							 */
							foreach ($PROCESSED["page_ids"] as $page_id) {
								$PROCESSED["content_id"] = $page_id;
								$PROCESSED["content_type"] = "community_page";
								if ($db->AutoExecute("attached_quizzes", $PROCESSED, "INSERT")) {
									$SUCCESS++;
									$SUCCESSSTR[]	= "You have successfully attached <strong>".html_encode($quiz_record["quiz_title"])."</strong> as <strong>".html_encode($PROCESSED["quiz_title"])."</strong> to <strong>".html_encode($selected_community_pages[$PROCESSED["cpage_id"]]["page_title"])."</strong>.";

									application_log("success", "Quiz [".$RECORD_ID."] was successfully attached to Community Page [".$PROCESSED["cpage_id"]."].");

								} else {
									
									$ERRORSTR[] = "There was a problem attaching this quiz to <strong>".html_encode($selected_community_pages[$PROCESSED["cpage_id"]]["page_title"])."</strong>. The system administrator was informed of this error; please try again later.";

									application_log("error", "There was an error attaching quiz [".$RECORD_ID."] to community page [".$PROCESSED["cpage_id"]."]. Database said: ".$db->ErrorMsg());
								}
							}

							if ($SUCCESS) {
								$url = ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID;
								$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

								$SUCCESSSTR[(count($SUCCESSSTR) - 1)] .= "<br /><br />You will now be redirected back to the quiz page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							} elseif ($ERROR) {
								$STEP = 2;
							}
						} else {
							$STEP = 2;
						}


					break;
					case 2 :
						/**
						 * Ensure there are valid event_ids provided.
						 */
						if (count($PROCESSED["page_ids"]) < 1) {
							
							$ERRORSTR[] = "To attach a quiz to a community page, select a community page you are involved with from the list below.";
						}

						/**
						 * If the quiz title is not set, give it the default one.
						 */
						if (!isset($_POST["quiz_title"])) {
							$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
					case 3 :
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
					case 2 :
						/**
						 * Load the rich text editor.
						 */
						load_rte();
						$total_page_ids = count($PROCESSED["page_ids"]);

						?>
						<h2 style="color: #CCCCCC">Step 1: Select Community Pages</h2>
						<ul class="menu">
							<?php
							if ($selected_community_pages) {
								foreach ($selected_community_pages as $page_id => $result) {
									echo "<li class=\"checkmark\">";
									echo "	<a href=\"".ENTRADA_URL."/community".$result["community_url"].":".$result["page_url"]."\" style=\"font-weight: bold; color: #666666\">".html_encode($result["page_title"])."</a>";
									if (in_array($page_id, $existing_page_relationship)) {
										echo "<span class=\"display-notice-inline\"><img src=\"".ENTRADA_URL."/images/list-notice.gif\" width=\"11\" height=\"11\" alt=\"Notice\" title=\"Notice\" style=\"margin-right: 10px\" />This specific quiz is already attached to this page.</span>";
									}
									echo "</li>\n";
								}
							}
							?>
						</ul>
						<h2>Step 2: Choose Quiz Options</h2>
						<?php
						if($ERROR) {
							echo display_error();
						}

						if($NOTICE) {
							echo display_notice();
						}
						?>
						<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;community=true&amp;id=<?php echo $RECORD_ID; ?>" method="post">
						<input type="hidden" name="step" value="3" />
						<?php
						foreach ($PROCESSED["page_ids"] as $page_id) {
							echo "<input type=\"hidden\" name=\"page_ids[]\" value=\"".$page_id."\" />\n";
						}
						?>
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Quiz">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 23%" />
							<col style="width: 77%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="3" style="padding-top: 50px">
									<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="width: 25%; text-align: left">
											<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
										</td>
										<td style="width: 75%; text-align: right; vertical-align: middle">
											<input type="submit" class="btn btn-primary" value="Proceed" />
										</td>
									</tr>
									</table>
								</td>
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td colspan="3">
									<div class="display-generic">
										In order to attach this quiz to <?php echo (($total_page_ids != 1) ? "these community pages" : "this community page"); ?>, we need just a bit more information. Please answer the following questions <strong>as they pertain</strong> to the community page<?php echo (($total_page_ids != 1) ? "s" : ""); ?> you selected in step 1.
									</div>
								</td>
							</tr>
							<tr>
								<td></td>
								<td><label for="quiz_title" class="form-required">Attached Quiz Title</label></td>
								<td><input type="text" id="quiz_title" name="quiz_title" value="<?php echo html_encode($PROCESSED["quiz_title"]); ?>" maxlength="128" style="width: 96%" /></td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label> <span class="content-small">(<strong>Tip:</strong> this information is visibile to students at the top of the quiz)</span>
								</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 125px" cols="70" rows="10"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									Should completion of this quiz be considered optional or required?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
									<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									Should the order of the questions be shuffled for this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
                                    <input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
                                    <input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									How much time (in minutes) can the learner spend taking this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									How many attempts can a learner take at completing this quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td colspan="2">
									When should learners be allowed to view the results of the quiz?
								</td>
							</tr>
							<tr>
								<td colspan="2"></td>
								<td>
									<?php
									if ($quiz_types_record) {
										foreach($quiz_types_record as $quiztype_id => $result) {
											echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".((isset($PROCESSED["quiztype_id"])) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label>";
											if ($result["quiztype_description"] != "") {
												echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
											}
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td colspan="3"><h2>Time Release Options</h2></td>
							</tr>
							<?php echo generate_calendars("accessible", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
						</tbody>
						</table>
						</form>
						<script type="text/javascript">
						function selectedTimeframe(timeframe) {
							switch (timeframe) {
								default :
									$('accessible_start').checked	= false;
									$('accessible_finish').checked	= false;

									dateLock('accessible_start');
									dateLock('accessible_finish');

									$('accessible_start_date').value	= '';
									$('accessible_start_hour').value	= '00';
									$('accessible_start_min').value		= '00';

									$('accessible_finish_date').value	= '';
									$('accessible_finish_hour').value	= '00';
									$('accessible_finish_min').value	= '00';
								break;
							}

							updateTime('accessible_start');
							updateTime('accessible_finish');
						}
						</script>
						<?php
					break;
					case 1 :
					default :
						?>
						<h2>Step 1: Select Community Pages</h2>
						<div class="display-generic">
							Please select <strong>one or more</strong> of your <strong>existing community pages</strong> to attach this quiz to.
						</div>
						<?php
						if($ERROR) {
							echo display_error();
						}

						if($NOTICE) {
							echo display_notice();
						}

						/**
						 * Update requested timestamp to display.
						 * Valid: Unix timestamp
						 */
						if((isset($_GET["dlength"])) && ($dlength = (int) trim($_GET["dlength"])) && ($dlength >= 1) && ($dlength <= 4)) {
							$_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] = $dlength;

							$_SERVER["QUERY_STRING"] = replace_query(array("dlength" => false));
						} else {
							if(!isset($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"])) {
								$_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"] = 2; // Defaults to this term.
							}
						}

						switch($_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["dlength"]) {
							case 1 :	// Last Term
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, (date("Y", time()) - 1));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, (date("Y", time()) - 1));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
								}
							break;
							case 3 :	// This Month
								$display_duration["start"]		= mktime(0, 0, 0, date("n", time()), 1, date("Y", time()));
								$display_duration["end"]		= mktime(0, 0, 0, date("n", time()), date("t", time()), date("Y", time()));
							break;
							case 4 :	// Next Term
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, (date("Y", time()) + 1));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, (date("Y", time()) + 1));
								}
							break;
							case 2 :	// This Term
							default :
								if(date("n", time()) <= 6) {
									$display_duration["start"]	= mktime(0, 0, 0, 1, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 6, 30, date("Y", time()));
								} else {
									$display_duration["start"]	= mktime(0, 0, 0, 7, 1, date("Y", time()));
									$display_duration["end"]	= mktime(0, 0, 0, 12, 31, date("Y", time()));
								}
							break;
						}
						?>
						<div style="clear: both"></div>
						<?php
						$query		= "	SELECT b.*, bp.*, CONCAT('[', b.`community_title`, '] ', bp.`menu_title`) AS `page_title`, CONCAT_WS(', ', d.`lastname`, d.`firstname`) AS `fullname`
										FROM `communities` AS b
										JOIN `community_pages` AS bp
										ON bp.`community_id` = b.`community_id`
										JOIN `community_members` AS c
										ON c.`community_id` = b.`community_id`
										AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
										AND c.`member_acl` = '1'
										JOIN `".AUTH_DATABASE."`.`user_data` AS d
										ON d.`id` = c.`proxy_id`
										WHERE b.`community_active` = '1'
										AND bp.`page_active` = '1'
										AND bp.`page_type` = 'quizzes'
										ORDER BY b.`community_title` ASC";
						$results = $db->GetAll($query);
						if ($results) {
							$total_pages_found = count($results);
							?>
							<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;community=true&amp;id=<?php echo $RECORD_ID; ?>" method="post">
							<input type="hidden" name="step" value="2" />
							<div class="tableListTop">
								<img src="<?php echo ENTRADA_URL; ?>/images/lecture-info.gif" width="15" height="15" alt="" title="" style="vertical-align: middle" />
								<?php echo "Found ".$total_pages_found." page".(($total_pages_found != 1) ? "s" : "")." from <strong>".date("D, M jS, Y", $display_duration["start"])."</strong> to <strong>".date("D, M jS, Y", $display_duration["end"])."</strong>.\n"; ?>
							</div>
							<table class="tableList" cellspacing="0" summary="My Teaching Events">
							<colgroup>
								<col class="modified" />
								<col class="title" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="title sortedASC">Community Page Title</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td></td>
									<td style="padding-top: 10px">
										<input type="submit" class="btn btn-primary" value="Attach Selected" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<?php
								foreach ($results as $result) {
									$url = ENTRADA_URL."/community".$result["community_url"].":".$result["page_url"];

									echo "<tr id=\"page-".$result["cpage_id"]."\" class=\"page\">\n";
									echo "	<td><input type=\"checkbox\" name=\"page_ids[]\" value=\"".$result["cpage_id"]."\" /></td>\n";
									echo "	<td><a href=\"".$url."\" title=\"Community Page Title: ".html_encode($result["page_title"])."\">".html_encode($result["page_title"])."</a></td>\n";
									echo "</tr>\n";
								}
								?>
							</tbody>
							</table>
							</form>
							<?php
						} else {
							?>
							<div class="display-notice">
								There is no record of any community pages in the system that you have administrative rights over.
								<br /><br />
								Please ensure that you a <strong>community administrator</strong> in the community that you want this quiz added to, and that there is a page in that community with a &quot;<strong>Page Type</strong>&quot; of &quot;<strong>Quizzes</strong>&quot;.
							</div>
							<?php
						}
					break;
				}
			} else {
				
				$ERRORSTR[] = "In order to attach this quiz to a community page, you must provide a valid quiz identifier.";

				echo display_error();

				application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to attach a quiz to a community page.");
			}
		} else {
			
			$ERRORSTR[] = "In order to attach this quiz to a community page, you must provide a quiz identifier.";

			echo display_error();

			application_log("notice", "Failed to provide a quiz identifier to attach a quiz to a community page.");
		}
	}
}