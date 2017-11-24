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
 * Loads the Learning Event quiz wizard when a teacher / director wants to
 * attach a quiz on the Manage Events > Content page.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
	echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
	echo "if(window.opener) {\n";
	echo "	window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "	top.window.close();\n";
	echo "} else {\n";
	echo "	window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
	echo "}\n";
	echo "</div>\n";
	exit;
} else {
	$ACTION				= "add";
	$RECORD_ID			= 0;
	$AQUIZ_ID			= 0;

	if (isset($_GET["type"])) {
		$QUIZ_TYPE = trim($_GET["type"]);
	} else {
		$QUIZ_TYPE = "event";
	}

	if (isset($_GET["action"])) {
		$ACTION	= trim($_GET["action"]);
	}

	if ((isset($_POST["step"])) && ($tmp_step = clean_input($_POST["step"], "int"))) {
		$STEP = $tmp_step;
	}

	if ((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$RECORD_ID	= (int) trim($_GET["id"]);
	}

	if ((isset($_GET["qid"])) && ((int) trim($_GET["qid"]))) {
		$AQUIZ_ID = (int) trim($_GET["qid"]);
	}

	/**
	 * Defaulting values for require_attendance and random_order here because the community doesn't ask for it but will still need to enter a value
	 * Defaults in MySQL anyway but just for good measure giving them a value.
	 */
	$PROCESSED["require_attendance"] = 0;
	$PROCESSED["random_order"] = 0;

	$modal_onload = array();
	?>
	<div id="uploading-window" style="width: 100%; height: 100%;">
		<div style="display: table; width: 100%; height: 100%; _position: relative; overflow: hidden">
			<div style=" _position: absolute; _top: 50%;display: table-cell; vertical-align: middle;">
				<div style="_position: relative; _top: -50%; width: 100%; text-align: center">
					<span style="color: #003366; font-size: 18px; font-weight: bold">
						<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="File Saving" title="Please wait while changes are being saved." style="vertical-align: middle" /> Please Wait: changes are being saved.
					</span>
				</div>
			</div>
		</div>
	</div>
	<?php

	if ($QUIZ_TYPE == "event") {
		if ($RECORD_ID) {
			$query			= "	SELECT a.*, b.`organisation_id`
								FROM `events` AS a
								LEFT JOIN `courses` AS b
								ON b.`course_id` = a.`course_id`
								WHERE a.`event_id` = ".$db->qstr($RECORD_ID)."
								AND b.`course_active` = '1'";
			$event_record	= $db->GetRow($query);
			if($event_record) {
				$access_allowed = false;
				if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($RECORD_ID, $event_record["course_id"], $event_record["organisation_id"]), "update")) {
					$query = "SELECT * FROM `events` WHERE `parent_id` = ".$db->qstr($RECORD_ID);
					if ($sessions = $db->GetAll($query)) {
						foreach ($sessions as $session) {
							if ($ENTRADA_ACL->amIAllowed(new EventContentResource($session["event_id"], $event_record["course_id"], $event_record["organisation_id"]), "update")) {
								$access_allowed = true;
							}
						}
					}
				} else {
					$access_allowed = true;
				}
				if (!$access_allowed) {
					$modal_onload[]	= "closeWizard()";

					$ERROR++;
					$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module. If you believe you are receiving this message in error please contact us for assistance.";

					echo display_error();

					application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to the file wizard.");
				} else {
					$quiz_types_record = array();
					$query		= "SELECT * FROM `quizzes_lu_quiztypes` WHERE `quiztype_active` = '1' ORDER BY `quiztype_order` ASC";
					$results	= $db->Execute($query);
					if ($results) {
						foreach ($results as $result) {
							$quiz_types_record[$result["quiztype_id"]] = array("quiztype_title" => $result["quiztype_title"], "quiztype_description" => $result["quiztype_description"], "quiztype_order" => $result["quiztype_order"]);
						}
					}

					$existing_quiz_relationship = array();
					$query		= "SELECT `quiz_id` FROM `attached_quizzes` WHERE `content_type` = 'event' AND `content_id` = ".$db->qstr($RECORD_ID);
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$existing_quiz_relationship[] = $result["quiz_id"];
						}
					}

					switch($ACTION) {
						case "edit" :
							/**
							 * Edit quiz form.
							 */

							if ($AQUIZ_ID) {
								$query			= "SELECT * FROM `attached_quizzes` WHERE `aquiz_id` = ".$db->qstr($AQUIZ_ID);
								$equiz_record	= $db->GetRow($query);

								if ($equiz_record) {
									$PROCESSED = $equiz_record;

									if (isset($_POST["step"])) {
										/**
										 * Required field "quiz_title" / Attached Quiz Title.
										 */
										if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
											$PROCESSED["quiz_title"] = $tmp_input;
										} else {
											$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
											$PROCESSED["quiztype_id"] = 0;
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

										if (($STEP >= 3) && isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
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

										/**
										 * If the back button was pressed, we should not error
										 * check the current step, because someone could be just
										 * returning to the previous step without completing the
										 * current one.
										 */
										if (isset($_POST["step_back"]) && $_POST["step_back"]) {
											$STEP = ($STEP - 1);
										}

										/**
										 * Error Checking
										 * This error checking follows a different of a pattern than
										 * else where in this application. As the steps increase, error
										 * checking is continued on all previous steps.
										 */
										switch($STEP) {
											case 4 :
											case 3 :
												/**
												 * Required field "timeframe" / When should this quiz be taken in relation to the event?
												 */
												if (!array_key_exists($PROCESSED["timeframe"], $RESOURCE_TIMEFRAMES["event"])) {
													$ERROR++;
													$ERRORSTR[]	= "Please select a proper option when asked when the quiz should be taken in relation to the event.";
													$STEP		= 3;
												}

											case 2 :
												/**
												 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
												 */
												if (!array_key_exists($PROCESSED["quiztype_id"], $quiz_types_record)) {
													$ERROR++;
													$ERRORSTR[]	= "Please select a proper quiz type when asked what type of quiz this should be considered.";
													$STEP		= 2;
												}

											case 1 :
											default :
												/**
												 * Required field "quiz_title" / Attached Quiz Title.
												 */
												if ($PROCESSED["quiz_title"] == "") {
													$ERROR++;
													$ERRORSTR[] = "The <strong>Attached Quiz Title</strong> field is required.";
													$STEP		= 1;
												}

												if ((!isset($PROCESSED["quiz_id"])) || (!$PROCESSED["quiz_id"])) {
													$ERROR++;
													$ERRORSTR[] = "You must select an active quiz that you have authored in order to attach it to this learning event.";
													$STEP		= 1;
												}
											break;
										}

										/**
										 * If the next button was pressed, and we have
										 * successfully error checked the current step, then go
										 * ahead and move to the next one.
										 */
										if (!$ERROR) {
											if (isset($_POST["go_forward"]) && $_POST["go_forward"]) {
												$STEP = ($STEP + 1);
											}
										}
									} else {
										$STEP = 1;
									}

									/**
									 * If the release_date and release_until variables are set,
									 * set the additional variables required for validate_calendars().
									 */
									if ((int) $PROCESSED["release_date"]) {
										$PROCESSED["accessible_start"]		= 1;
										$PROCESSED["accessible_start_date"]	= date("Y-m-d", $PROCESSED["release_date"]);
										$PROCESSED["accessible_start_hour"]	= (int) date("G", $PROCESSED["release_date"]);
										$PROCESSED["accessible_start_min"]	= (int) date("i", $PROCESSED["release_date"]);
									} else {
										$PROCESSED["accessible_start"]		= 0;
										$PROCESSED["accessible_start_date"]	= "";
										$PROCESSED["accessible_start_hour"]	= 0;
										$PROCESSED["accessible_start_min"]	= 0;
									}
									if ((int) $PROCESSED["release_until"]) {
										$PROCESSED["accessible_finish"]			= 1;
										$PROCESSED["accessible_finish_date"]	= date("Y-m-d", $PROCESSED["release_until"]);
										$PROCESSED["accessible_finish_hour"]	= (int) date("G", $PROCESSED["release_until"]);
										$PROCESSED["accessible_finish_min"]		= (int) date("i", $PROCESSED["release_until"]);
									} else {
										$PROCESSED["accessible_finish"]			= 0;
										$PROCESSED["accessible_finish_date"]	= "";
										$PROCESSED["accessible_finish_hour"]	= 0;
										$PROCESSED["accessible_finish_min"]		= 0;
									}

									// Display Edit Step
									switch($STEP) {
										case 4 :
											$PROCESSED["content_type"]	= "event";
											$PROCESSED["content_id"]	= $RECORD_ID;
											$PROCESSED["updated_date"]	= time();
											$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

											if ($db->AutoExecute("attached_quizzes", $PROCESSED, "UPDATE", "aquiz_id = ".$db->qstr($AQUIZ_ID))) {
												$modal_onload[] = "parentReload()";
												?>
												<div class="modal-dialog" id="quiz-wizard">
													<div id="wizard">
                                                        <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached event quiz</span></h3>
														<div id="body">
															<h2>Quiz Updated Successfully</h2>

															<div class="display-success">
																You have successfully updated <strong><?php echo html_encode($PROCESSED["quiz_title"]); ?></strong> under this event.
															</div>

															To <strong>re-edit this quiz</strong> or <strong>close this window</strong> please use the buttons below.
														</div>
														<div id="footer">
															<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
															<button class="btn btn-primary" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>&amp;action=edit')" style="float: right; margin: 4px 10px 4px 0px">Edit Again</button>
														</div>
													</div>
												</div>
												<?php
												history_log($RECORD_ID, "Updated " . $PROCESSED["quiz_title"] . " event quiz.");
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating <strong>".html_encode($PROCESSED["quiz_title"])."</strong> under this event. The system administrator was informed of this error; please try again later.";

												application_log("error", "Unable to update an attached quiz [".$PROCESSED["quiz_id"]."] inder event [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
												?>
												<div class="modal-dialog" id="quiz-wizard">
													<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
													<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
													<?php
													foreach ($PROCESSED as $key => $value) {
														echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
													}
													?>
													<div id="wizard">
                                                        <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached event quiz</span></h3>
														<div id="body">
															<h2>Failed To Update Attached Quiz</h2>
															<?php
															if ($ERROR) {
																echo display_error();
															}
															if ($NOTICE) {
																echo display_notice();
															}
															?>
														</div>
														<div id="footer">
															<input type="hidden" name="go_back" id="go_back" value="0" />
															<input type="hidden" name="go_forward" id="go_forward" value="0" />
															<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
															<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
														</div>
													</div>
													</form>
												</div>
												<?php
											}
										break;
										case 3 :
                                            $modal_onload[] = "updateTime('accessible_start')";
                                            $modal_onload[] = "updateTime('accessible_finish')";

											/**
											 * Unset variables set by this page or they get posted twice.
											 */
											unset($PROCESSED["accessible_start"]);
											unset($PROCESSED["accessible_start_date"]);
											unset($PROCESSED["accessible_start_hour"]);
											unset($PROCESSED["accessible_start_min"]);

											unset($PROCESSED["accessible_finish"]);
											unset($PROCESSED["accessible_finish_date"]);
											unset($PROCESSED["accessible_finish_hour"]);
											unset($PROCESSED["accessible_finish_min"]);
											?>
											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached event quiz</span></h3>
													<div id="body">
														<h2>Step 3: Quiz Availability</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}

														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<div>Is attendance required for this quiz to be completed?</div>
															<div class="response-area">
																<input type="radio" id="require_attendance_no" name="require_attendance" value="0"<?php echo (((!isset($PROCESSED["require_attendance"])) || (!$PROCESSED["require_attendance"])) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_no">Not Required</label><br />
																<input type="radio" id="require_attendance_yes" name="require_attendance" value="1"<?php echo (($PROCESSED["require_attendance"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_yes">Required</label><br />
															</div>
														</div>

														<div class="wizard-question">
															<div>When should this quiz be taken in relation to the event?</div>
															<div class="response-area">
																<?php
																if (is_array($RESOURCE_TIMEFRAMES["event"])) {
																	foreach($RESOURCE_TIMEFRAMES["event"] as $key => $value) {
																		echo "<input type=\"radio\" id=\"timeframe_".$key."\" name=\"timeframe\" value=\"".$key."\" style=\"vertical-align: middle\"".(($PROCESSED["timeframe"]) ? (($PROCESSED["timeframe"] == $key) ? " checked=\"checked\"" : "") : (($key == "none") ? " checked=\"checked\"" : ""))." onclick=\"selectedTimeframe(this.value)\" /> <label for=\"timeframe_".$key."\">".$value."</label><br />";
																	}
																}
																?>
															</div>
														</div>

														<div class="wizard-question">
															<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Time Release Information">
																<colgroup>
																	<col style="width: 20%" />
																	<col style="width: 80%" />
																</colgroup>
																<tr>
																	<td colspan="2"><h2>Time Release Options</h2></td>
																</tr>
																<tr>
																	<td colspan="2">
																		<table class="date-time">
																<?php
																	if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
																		$query = "SELECT `quiztype_code` FROM `quizzes_lu_quiztypes` WHERE `quiztype_id` = ".$db->qstr($PROCESSED["quiztype_id"]);
																		$quiztype = $db->GetOne($query);
																		$quiz_results_delayed = ($quiztype == "delayed" ? true : false);
																	}
																	echo generate_calendars("accessible", "Accessible", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, $quiz_results_delayed, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), true, true);
																?>
																		</table>
																	</td>
																</tr>
															</table>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_back" id="go_back" value="0" />
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Finish" />
														<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
										case 2 :
											?>
											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached event quiz</span></h3>
													<div id="body">
														<h2>Step 2: Choose Quiz Options</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<div>Should completion of this quiz be considered optional or required?</div>
															<div class="response-area">
																<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
																<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
															</div>
														</div>

														<div class="wizard-question">
															<div>Should the order of the questions be shuffled for this quiz?</div>
															<div class="response-area">
																<input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
																<input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
															</div>
														</div>

														<div class="wizard-question">
															<div>How much time (in minutes) can the learner spend taking this quiz?</div>
															<div class="response-area">
																<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
															</div>
														</div>

														<div class="wizard-question">
															<div>How many attempts can a learner take at completing this quiz?</div>
															<div class="response-area">
																<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
															</div>
														</div>

														<div class="wizard-question" style="margin-bottom: 0px">
															<div>When should learners be allowed to view the results of the quiz?</div>
															<div class="response-area">
																<?php
																if ($quiz_types_record) {
																	foreach($quiz_types_record as $quiztype_id => $result) {
																		echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".(($PROCESSED["quiztype_id"]) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label><br />";
																		if ($result["quiztype_description"] != "") {
																			echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
																		}
																	}
																}
																?>
															</div>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_back" id="go_back" value="0" />
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
														<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
										case 1 :
										default :
											/**
											 * Load the rich text editor.
											 */
											load_rte();
											?>

											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached event quiz</span></h3>
													<div id="body">
														<h2>Step 1: Basic Quiz Information</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<div>You can <span style="font-style: oblique">optionally</span> provide a different title for this quiz.</div>
															<div class="response-area">
																<label for="quiz_title" class="form-required">Attached Quiz Title</label><br />
																<input type="text" id="quiz_title" name="quiz_title" value="<?php echo ((isset($PROCESSED["quiz_title"])) ? html_encode($PROCESSED["quiz_title"]) : ""); ?>" maxlength="128" style="width: 96%;" />
															</div>
														</div>
														<div class="wizard-question">
															<div>You can <span style="font-style: oblique">optionally</span> provide more detailed instructions for this quiz.</div>
															<div class="response-area">
																<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label><br />
																<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 225px"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
																<div class="content-small" style="margin-top: 5px"><strong>Hint:</strong> this information is visibile to the learners at the top of the quiz.</div>
															</div>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
									}
								} else {
									// Error, unable to find the aquiz_id record.
								}
							} else {
								// Error, there was no aquiz_id provided.
							}
						break;
						case "add" :
						default :
							/**
							 * Add quiz form.
							 */

							/**
							 * Required field "quiz_id" / Step 1
							 * Nothing at all can be done without this quiz_id.
							 */
							if ((isset($_POST["quiz_id"])) && ($tmp_input = clean_input($_POST["quiz_id"]))) {
								$query			= "	SELECT a.*
													FROM `quizzes` AS a
													LEFT JOIN `quiz_contacts` AS b
													ON a.`quiz_id` = b.`quiz_id`
													WHERE a.`quiz_id` = ".$db->qstr($tmp_input)."
													AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
								$quiz_record	= $db->GetRow($query);
								if ($quiz_record) {
									if ($quiz_record["quiz_active"] == 1) {
										$PROCESSED["quiz_id"] = $tmp_input;

										/**
										 * Required field "quiz_title" / Attached Quiz Title.
										 */
										if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
											$PROCESSED["quiz_title"] = $tmp_input;
										} else {
											$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
											$PROCESSED["quiztype_id"] = 0;
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

										if (($STEP >= 4) && isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
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

										if ((isset($_POST["accessible_start"])) && ($_POST["accessible_start"] == 1)) {
											$PROCESSED["accessible_start"]		= 1;
											$PROCESSED["accessible_start_date"]	= clean_input($_POST["accessible_start_date"], "credentials");
											$PROCESSED["accessible_start_hour"]	= clean_input($_POST["accessible_start_hour"], "int");
											$PROCESSED["accessible_start_min"]	= clean_input($_POST["accessible_start_min"], "int");
										} else {
											$PROCESSED["accessible_start"]		= 0;
											$PROCESSED["accessible_start_date"]	= "";
											$PROCESSED["accessible_start_hour"]	= 0;
											$PROCESSED["accessible_start_min"]	= 0;
										}

										if ((isset($_POST["accessible_finish"])) && ($_POST["accessible_finish"] == 1)) {
											$PROCESSED["accessible_finish"]			= 1;
											$PROCESSED["accessible_finish_date"]	= clean_input($_POST["accessible_finish_date"], "credentials");
											$PROCESSED["accessible_finish_hour"]	= clean_input($_POST["accessible_finish_hour"], "int");
											$PROCESSED["accessible_finish_min"]		= clean_input($_POST["accessible_finish_min"], "int");
										} else {
											$PROCESSED["accessible_finish"]			= 0;
											$PROCESSED["accessible_finish_date"]	= "";
											$PROCESSED["accessible_finish_hour"]	= 0;
											$PROCESSED["accessible_finish_min"]		= 0;

										}
									}
								}
							} else {
								$STEP = 1;
							}

							/**
							 * If the back button was pressed, we should not error
							 * check the current step, because someone could be just
							 * returning to the previous step without completing the
							 * current one.
							 */
							if (isset($_POST["step_back"]) && $_POST["step_back"]) {
								$STEP = ($STEP - 1);
							}

							/**
							 * Error Checking
							 * This error checking follows a different of a pattern than
							 * else where in this application. As the steps increase, error
							 * checking is continued on all previous steps.
							 */
							switch($STEP) {
								case 5 :
								case 4 :
									/**
									 * Required field "timeframe" / When should this quiz be taken in relation to the event?
									 */
									if (!array_key_exists($PROCESSED["timeframe"], $RESOURCE_TIMEFRAMES["event"])) {
										$ERROR++;
										$ERRORSTR[]	= "Please select a proper option when asked when the quiz should be taken in relation to the event.";
										$STEP		= 4;
									}

								case 3 :
									/**
									 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
									 */
									if (!array_key_exists($PROCESSED["quiztype_id"], $quiz_types_record)) {
										$ERROR++;
										$ERRORSTR[]	= "Please select a proper quiz type when asked what type of quiz this should be considered.";
										$STEP		= 3;
									}

								case 2 :
									/**
									 * Required field "quiz_title" / Attached Quiz Title.
									 */
									if ($PROCESSED["quiz_title"] == "") {
										$ERROR++;
										$ERRORSTR[] = "The <strong>Attached Quiz Title</strong> field is required.";
										$STEP		= 2;
									}
								case 1 :
								default :
									if ((!isset($PROCESSED["quiz_id"])) || (!$PROCESSED["quiz_id"])) {
										$ERROR++;
										$ERRORSTR[] = "You must select an active quiz that you have authored in order to attach it to this learning event.";
										$STEP		= 1;
									}
								break;
							}

							/**
							 * If the next button was pressed, and we have
							 * successfully error checked the current step, then go
							 * ahead and move to the next one.
							 */
							if (!$ERROR) {
								if (isset($_POST["go_forward"]) && $_POST["go_forward"]) {
									$STEP = ($STEP + 1);
								}
							}

							// Display Add Step
							switch ($STEP) {
								case 5 :
									$PROCESSED["content_type"]	= "event";
									$PROCESSED["content_id"]	= $RECORD_ID;
									$PROCESSED["accesses"]		= 0;
									$PROCESSED["updated_date"]	= time();
									$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

									if ($db->AutoExecute("attached_quizzes", $PROCESSED, "INSERT")) {
										$modal_onload[] = "parentReload()";
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
												<div id="body">
													<h2>Quiz Attached Successfully</h2>

													<div class="display-success">
														You have successfully attached <strong><?php echo html_encode($PROCESSED["quiz_title"]); ?></strong> to this <?php echo (isset($QUIZ_TYPE) && $QUIZ_TYPE == "community_page" ? "community_page" : "event"); ?>.
													</div>

													To <strong>attach another quiz</strong> or <strong>close this window</strong> please use the buttons below.
												</div>
												<div id="footer">
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<button class="btn btn-primary" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?id=<?php echo $RECORD_ID; ?>&amp;action=add')" style="float: right; margin: 4px 10px 4px 0px">Add Another</button>
												</div>
											</div>
										</div>
										<?php
										history_log($RECORD_ID, "Attached ".$PROCESSED["quiz_title"]." event quiz.");
									} else {
										$ERROR++;
										$ERRORSTR[] = "There was a problem attaching <strong>".html_encode($PROCESSED["quiz_title"])."</strong> to this event. The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to attach quiz [".$PROCESSED["quiz_id"]."] to event [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
											<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
											<?php
											foreach ($PROCESSED as $key => $value) {
												echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
											}
											?>
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
												<div id="body">
													<h2>Failed To Attach Quiz</h2>
													<?php
													if ($ERROR) {
														echo display_error();
													}
													if ($NOTICE) {
														echo display_notice();
													}
													?>
												</div>
												<div id="footer">
													<input type="hidden" name="go_back" id="go_back" value="0" />
													<input type="hidden" name="go_forward" id="go_forward" value="0" />
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
												</div>
											</div>
											</form>
										</div>
										<?php
									}
								break;
								case 4 :
                                    $modal_onload[] = "updateTime('accessible_start')";
                                    $modal_onload[] = "updateTime('accessible_finish')";

									/**
									 * Unset variables set by this page or they get posted twice.
									 */
									unset($PROCESSED["accessible_start"]);
									unset($PROCESSED["accessible_start_date"]);
									unset($PROCESSED["accessible_start_hour"]);
									unset($PROCESSED["accessible_start_min"]);

									unset($PROCESSED["accessible_finish"]);
									unset($PROCESSED["accessible_finish_date"]);
									unset($PROCESSED["accessible_finish_hour"]);
									unset($PROCESSED["accessible_finish_min"]);
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
											<div id="body">
												<h2>Step 4: Quiz Availability</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}

												if ($NOTICE) {
													echo display_notice();
												}
												?>

												<div class="wizard-question">
													<div>Is attendance required for this quiz to be completed?</div>
													<div class="response-area">
														<input type="radio" id="require_attendance_no" name="require_attendance" value="0"<?php echo (((!isset($PROCESSED["require_attendance"])) || (!$PROCESSED["require_attendance"])) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_no">Not Required</label><br />
														<input type="radio" id="require_attendance_yes" name="require_attendance" value="1"<?php echo (($PROCESSED["require_attendance"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="require_attendance_yes">Required</label><br />
													</div>
												</div>

												<div class="wizard-question">
													<div>When should this quiz be taken in relation to the event?</div>
													<div class="response-area">
														<?php
														if (is_array($RESOURCE_TIMEFRAMES["event"])) {
															foreach($RESOURCE_TIMEFRAMES["event"] as $key => $value) {
																echo "<input type=\"radio\" id=\"timeframe_".$key."\" name=\"timeframe\" value=\"".$key."\" style=\"vertical-align: middle\"".(($PROCESSED["timeframe"]) ? (($PROCESSED["timeframe"] == $key) ? " checked=\"checked\"" : "") : (($key == "none") ? " checked=\"checked\"" : ""))." onclick=\"selectedTimeframe(this.value)\" /> <label for=\"timeframe_".$key."\">".$value."</label><br />";
															}
														}
														?>
													</div>
												</div>

												<div class="wizard-question">
													<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Time Release Information">
													<colgroup>
														<col style="width: 3%" />
														<col style="width: 30%" />
														<col style="width: 67%" />
													</colgroup>
													<tr>
														<td colspan="3"><h2>Time Release Options</h2></td>
													</tr>
													<?php
                                                        $quiz_results_delayed = false;

														if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
															$query = "SELECT `quiztype_code` FROM `quizzes_lu_quiztypes` WHERE `quiztype_id` = ".$db->qstr($PROCESSED["quiztype_id"]);
															$quiztype = $db->GetOne($query);
															$quiz_results_delayed = ($quiztype == "delayed" ? true : false);
														}

														echo generate_calendars("accessible", "Accessible", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, $quiz_results_delayed, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), true, true);
													?>
													</table>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Finish" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 3 :
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
											<div id="body">
												<h2>Step 3: Choose Quiz Options</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}
												if ($NOTICE) {
													echo display_notice();
												}
												?>
												<div class="wizard-question">
													<div>Should completion of this quiz be considered optional or required?</div>
													<div class="response-area">
														<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
														<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
													</div>
												</div>

												<div class="wizard-question">
													<div>Should the order of the questions be shuffled for this quiz?</div>
													<div class="response-area">
														<input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
														<input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
													</div>
												</div>

												<div class="wizard-question">
													<div>How much time (in minutes) can the learner spend taking this quiz?</div>
													<div class="response-area">
														<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
													</div>
												</div>

												<div class="wizard-question">
													<div>How many attempts can a learner take at completing this quiz?</div>
													<div class="response-area">
														<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
													</div>
												</div>

												<div class="wizard-question" style="margin-bottom: 0px">
													<div>When should learners be allowed to view the results of the quiz?</div>
													<div class="response-area">
														<?php
														if ($quiz_types_record) {
															foreach($quiz_types_record as $quiztype_id => $result) {
																echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".(($PROCESSED["quiztype_id"]) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label><br />";
																if ($result["quiztype_description"] != "") {
																	echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
																}
															}
														}
														?>
													</div>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 2 :
									/**
									 * Load the rich text editor.
									 */
									load_rte();
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
											<div id="body">
												<h2>Step 2: Basic Quiz Information</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}
												if ($NOTICE) {
													echo display_notice();
												}
												?>
												<div class="wizard-question">
													<div>You can <span style="font-style: oblique">optionally</span> provide a different title for this quiz.</div>
													<div class="response-area">
														<label for="quiz_title" class="form-required">Attached Quiz Title</label><br />
														<input type="text" id="quiz_title" name="quiz_title" value="<?php echo ((isset($PROCESSED["quiz_title"])) ? html_encode($PROCESSED["quiz_title"]) : ""); ?>" maxlength="128" style="width: 96%;" />
													</div>
												</div>
												<div class="wizard-question">
													<div>You can <span style="font-style: oblique">optionally</span> provide more detailed instructions for this quiz.</div>
													<div class="response-area">
														<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label><br />
														<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 225px"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
														<div class="content-small" style="margin-top: 5px"><strong>Hint:</strong> this information is visibile to the learners at the top of the quiz.</div>
													</div>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 1 :
								default :
									$query		= "	SELECT a.*, COUNT(c.`quiz_id`) AS `question_total`
													FROM `quizzes` AS a
													LEFT JOIN `quiz_contacts` AS b
													ON a.`quiz_id` = b.`quiz_id`
													LEFT JOIN `quiz_questions` AS c
													ON a.`quiz_id` = c.`quiz_id`
													AND c.`question_active` = '1'
													WHERE a.`quiz_active` = '1'
													AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
													GROUP BY a.`quiz_id`
													ORDER BY a.`quiz_title` ASC";
									$results	= $db->GetAll($query);
									if ($results) {
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
											<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
											<?php
											foreach ($PROCESSED as $key => $value) {
												echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
											}
											?>
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
												<div id="body">
													<h2>Step 1: Select Quiz</h2>
													<?php
													if ((isset($_POST)) && (count($_POST))) {
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
													}
													?>
													<div class="wizard-question">
														<table class="tableList" cellspacing="0" summary="List of Quizzes">
														<colgroup>
															<col class="modified" />
															<col class="title" />
															<col class="completed" />
														</colgroup>
														<thead>
															<tr>
																<td class="modified">&nbsp;</td>
																<td class="title sortedASC"><div class="noLink">Quiz Title</div></td>
																<td class="completed">Questions</td>
															</tr>
														</thead>
														<tbody>
															<?php
															foreach ($results as $result) {
																echo "<tr id=\"quiz-".$result["quiz_id"]."\">\n";
																echo "	<td style=\"vertical-align: top\"><input type=\"radio\" id=\"quiz_id_".$result["quiz_id"]."\" name=\"quiz_id\" value=\"".$result["quiz_id"]."\"".(((isset($PROCESSED["quiz_id"])) && ($PROCESSED["quiz_id"] == $result["quiz_id"])) ? " checked=\"checked\"" : "")." /></td>\n";
																echo "	<td style=\"vertical-align: top\">\n";
																echo "		<label for=\"quiz_id_".$result["quiz_id"]."\" class=\"form-nrequired\" style=\"font-weight: bold\">".html_encode($result["quiz_title"])."</label>\n";
																echo "		<div class=\"content-small\" style=\"white-space: normal\">".clean_input(limit_chars($result["quiz_description"], 150), "allowedtags")."</div>\n";
																if (in_array($result["quiz_id"], $existing_quiz_relationship)) {
																	echo "<div class=\"display-notice-inline\"><img src=\"".ENTRADA_URL."/images/list-notice.gif\" width=\"11\" height=\"11\" alt=\"Notice\" title=\"Notice\" style=\"margin-right: 10px\" />This quiz is already attached to this event.</div>";
																}
																echo "	</td>\n";
																echo "	<td style=\"vertical-align: top\" class=\"completed\">".html_encode($result["question_total"])."</td>\n";
																echo "</tr>\n";
															}
															?>
														</tbody>
														</table>
													</div>
												</div>
												<div id="footer">
													<input type="hidden" name="go_forward" id="go_forward" value="0" />
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												</div>
											</div>
											</form>
										</div>
										<?php
									} else {
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new event quiz</span></h3>
												<div id="body" style="margin-top: 25px">
													<?php
													$NOTICE++;
													$NOTICESTR[] = "You have not yet created any quizzes to attach to this event.<br /><br />To author a new Quiz, close this window and click the <strong>Manage Quizzes</strong> tab.";

													echo display_notice();
													?>
												</div>
												<div id="footer">
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												</div>
											</div>
										</div>
										<?php
									}
								break;
							}
						break;
					}
					?>
					<div id="scripts-on-open" style="display: none;">
					<?php
                    foreach ($modal_onload as $string) {
                        echo $string.";\n";
                    }
					?>
                    window.selectedTimeframe = function (timeframe) {
                        switch (timeframe) {
                            case 'pre' :
                                $('accessible_start').checked	= false;
                                $('accessible_finish').checked	= true;

                                dateLock('accessible_start');
                                dateLock('accessible_finish');

                                $('accessible_start_date').value	= '';
                                $('accessible_start_hour').value	= '00';
                                $('accessible_start_min').value		= '00';

                                $('accessible_finish_date').value	= '<?php echo date("Y-m-d", $event_record["event_finish"]); ?>';
                                $('accessible_finish_hour').value	= '<?php echo date("G", $event_record["event_finish"]); ?>';
                                $('accessible_finish_min').value	= '<?php echo date("i", $event_record["event_finish"]); ?>';
                            break;
                            case 'during' :
                                $('accessible_start').checked	= true;
                                $('accessible_finish').checked	= true;

                                dateLock('accessible_start');
                                dateLock('accessible_finish');

                                $('accessible_start_date').value	= '<?php echo date("Y-m-d", $event_record["event_start"]); ?>';
                                $('accessible_start_hour').value	= '<?php echo date("G", $event_record["event_start"]); ?>';
                                $('accessible_start_min').value		= '<?php echo date("i", $event_record["event_start"]); ?>';

                                $('accessible_finish_date').value	= '<?php echo date("Y-m-d", $event_record["event_finish"]); ?>';
                                $('accessible_finish_hour').value	= '<?php echo date("G", $event_record["event_finish"]); ?>';
                                $('accessible_finish_min').value	= '<?php echo date("i", $event_record["event_finish"]); ?>';
                            break;
                            case 'post' :
                                $('accessible_start').checked	= true;
                                $('accessible_finish').checked	= <?php echo (isset($quiz_results_delayed) && $quiz_results_delayed ? "true" : "false"); ?>;

                                dateLock('accessible_start');
                                dateLock('accessible_finish');

                                $('accessible_start_date').value	= '<?php echo date("Y-m-d", $event_record["event_start"]); ?>';
                                $('accessible_start_hour').value	= '<?php echo date("G", $event_record["event_start"]); ?>';
                                $('accessible_start_min').value		= '<?php echo date("i", $event_record["event_start"]); ?>';

                                $('accessible_finish_date').value	= '';
                                $('accessible_finish_hour').value	= '00';
                                $('accessible_finish_min').value	= '00';
                            break;
                            default :
                                $('accessible_start').checked	= false;
                                $('accessible_finish').checked	= <?php echo (isset($quiz_results_delayed) && $quiz_results_delayed ? "true" : "false"); ?>;

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
					</div>
					<?php
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The provided event identifier does not exist in this system.";

				echo display_error();

				application_log("error", "Quiz wizard was accessed without a valid event id.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You must provide an event identifier when using the quiz wizard.";

			echo display_error();

			application_log("error", "Quiz wizard was accessed without any event id.");
		}
	} elseif ($QUIZ_TYPE == "community_page") {
		if ($RECORD_ID) {
			$query			= "	SELECT * FROM `community_pages`
								WHERE `cpage_id` = ".$db->qstr($RECORD_ID);
			$community_record	= $db->GetRow($query);
			if($community_record) {
				$query	= "	SELECT * FROM `community_members`
							WHERE `community_id` = ".$db->qstr($community_record["community_id"])."
							AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
							AND `member_active` = '1'
							AND `member_acl` = '1'";
				$result	= $db->GetRow($query);
				if (!$result) {
					$modal_onload[]	= "closeWizard()";

					$ERROR++;
					$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module. If you believe you are receiving this message in error please contact us for assistance.";

					echo display_error();

					application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to the file wizard.");
				} else {
					$quiz_types_record = array();
					$query		= "SELECT * FROM `quizzes_lu_quiztypes` WHERE `quiztype_active` = '1' ORDER BY `quiztype_order` ASC";
					$results	= $db->Execute($query);
					if ($results) {
						foreach ($results as $result) {
							$quiz_types_record[$result["quiztype_id"]] = array("quiztype_title" => $result["quiztype_title"], "quiztype_description" => $result["quiztype_description"], "quiztype_order" => $result["quiztype_order"]);
						}
					}

					$existing_quiz_relationship = array();
					$query		= "SELECT `quiz_id` FROM `attached_quizzes` WHERE `content_type` = 'event' AND `content_id` = ".$db->qstr($RECORD_ID);
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $result) {
							$existing_quiz_relationship[] = $result["quiz_id"];
						}
					}

					switch($ACTION) {
						case "edit" :
							/**
							 * Edit quiz form.
							 */

							if ($AQUIZ_ID) {
								$query			= "SELECT * FROM `attached_quizzes` WHERE `aquiz_id` = ".$db->qstr($AQUIZ_ID);
								$equiz_record	= $db->GetRow($query);

								if ($equiz_record) {
									$PROCESSED = $equiz_record;

									if (isset($_POST["step"])) {
										/**
										 * Required field "quiz_title" / Attached Quiz Title.
										 */
										if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
											$PROCESSED["quiz_title"] = $tmp_input;
										} else {
											$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
											$PROCESSED["quiztype_id"] = 0;
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

										if (($STEP >= 3) && isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
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

										/**
										 * If the back button was pressed, we should not error
										 * check the current step, because someone could be just
										 * returning to the previous step without completing the
										 * current one.
										 */
										if (isset($_POST["step_back"]) && $_POST["step_back"]) {
											$STEP = ($STEP - 1);
										}

										/**
										 * Error Checking
										 * This error checking follows a different of a pattern than
										 * else where in this application. As the steps increase, error
										 * checking is continued on all previous steps.
										 */
										switch($STEP) {
											case 4 :
											case 3 :
											case 2 :
												/**
												 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
												 */
												if (!array_key_exists($PROCESSED["quiztype_id"], $quiz_types_record)) {
													$ERROR++;
													$ERRORSTR[]	= "Please select a proper quiz type when asked what type of quiz this should be considered.";
													$STEP		= 2;
												}

											case 1 :
											default :
												/**
												 * Required field "quiz_title" / Attached Quiz Title.
												 */
												if ($PROCESSED["quiz_title"] == "") {
													$ERROR++;
													$ERRORSTR[] = "The <strong>Attached Quiz Title</strong> field is required.";
													$STEP		= 1;
												}

												if ((!isset($PROCESSED["quiz_id"])) || (!$PROCESSED["quiz_id"])) {
													$ERROR++;
													$ERRORSTR[] = "You must select an active quiz that you have authored in order to attach it to this community page.";
													$STEP		= 1;
												}
											break;
										}

										/**
										 * If the next button was pressed, and we have
										 * successfully error checked the current step, then go
										 * ahead and move to the next one.
										 */
										if (!$ERROR) {
											if (isset($_POST["go_forward"]) && $_POST["go_forward"]) {
												$STEP = ($STEP + 1);
											}
										}
									} else {
										$STEP = 1;
									}

									/**
									 * If the release_date and release_until variables are set,
									 * set the additional variables required for validate_calendars().
									 */
									if ((int) $PROCESSED["release_date"]) {
										$PROCESSED["accessible_start"]		= 1;
										$PROCESSED["accessible_start_date"]	= date("Y-m-d", $PROCESSED["release_date"]);
										$PROCESSED["accessible_start_hour"]	= (int) date("G", $PROCESSED["release_date"]);
										$PROCESSED["accessible_start_min"]	= (int) date("i", $PROCESSED["release_date"]);
									} else {
										$PROCESSED["accessible_start"]		= 0;
										$PROCESSED["accessible_start_date"]	= "";
										$PROCESSED["accessible_start_hour"]	= 0;
										$PROCESSED["accessible_start_min"]	= 0;
									}
									if ((int) $PROCESSED["release_until"]) {
										$PROCESSED["accessible_finish"]			= 1;
										$PROCESSED["accessible_finish_date"]	= date("Y-m-d", $PROCESSED["release_until"]);
										$PROCESSED["accessible_finish_hour"]	= (int) date("G", $PROCESSED["release_until"]);
										$PROCESSED["accessible_finish_min"]		= (int) date("i", $PROCESSED["release_until"]);
									} else {
										$PROCESSED["accessible_finish"]			= 0;
										$PROCESSED["accessible_finish_date"]	= "";
										$PROCESSED["accessible_finish_hour"]	= 0;
										$PROCESSED["accessible_finish_min"]		= 0;
									}

									// Display Edit Step
									switch($STEP) {
										case 4 :
											$PROCESSED["content_type"]	= "community_page";
											$PROCESSED["content_id"]	= $RECORD_ID;
											$PROCESSED["updated_date"]	= time();
											$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

											if ($db->AutoExecute("attached_quizzes", $PROCESSED, "UPDATE", "aquiz_id = ".$db->qstr($AQUIZ_ID))) {
												$modal_onload[] = "parentReload()";
												?>
												<div class="modal-dialog" id="quiz-wizard">
													<div id="wizard">
                                                        <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached community page quiz</span></h3>
														<div id="body">
															<h2>Quiz Updated Successfully</h2>

															<div class="display-success">
																You have successfully updated <strong><?php echo html_encode($PROCESSED["quiz_title"]); ?></strong> under this community page.
															</div>

															To <strong>re-edit this quiz</strong> or <strong>close this window</strong> please use the buttons below.
														</div>
														<div id="footer">
															<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
															<button class="btn btn-primary" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>&amp;action=edit&amp;type=community_page')" style="float: right; margin: 4px 10px 4px 0px">Edit Again</button>
														</div>
													</div>
												</div>
												<?php
												history_log($RECORD_ID, "updated $PROCESSED[quiz_title] community quiz.");
											} else {
												$ERROR++;
												$ERRORSTR[] = "There was a problem updating <strong>".html_encode($PROCESSED["quiz_title"])."</strong> under this community page. The system administrator was informed of this error; please try again later.";

												application_log("error", "Unable to update an attached quiz [".$PROCESSED["quiz_id"]."] under community page [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
												?>
												<div class="modal-dialog" id="quiz-wizard">
													<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
													<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
													<?php
													foreach ($PROCESSED as $key => $value) {
														echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
													}
													?>
													<div id="wizard">
                                                        <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached community page quiz</span></h3>
														<div id="body">
															<h2>Failed To Update Attached Quiz</h2>
															<?php
															if ($ERROR) {
																echo display_error();
															}
															if ($NOTICE) {
																echo display_notice();
															}
															?>
														</div>
														<div id="footer">
															<input type="hidden" name="go_back" id="go_back" value="0" />
															<input type="hidden" name="go_forward" id="go_forward" value="0" />
															<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
															<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
														</div>
													</div>
													</form>
												</div>
												<?php
											}
										break;
										case 3 :
                                            $modal_onload[] = "updateTime('accessible_start')";
                                            $modal_onload[] = "updateTime('accessible_finish')";

											/**
											 * Unset variables set by this page or they get posted twice.
											 */
											unset($PROCESSED["accessible_start"]);
											unset($PROCESSED["accessible_start_date"]);
											unset($PROCESSED["accessible_start_hour"]);
											unset($PROCESSED["accessible_start_min"]);

											unset($PROCESSED["accessible_finish"]);
											unset($PROCESSED["accessible_finish_date"]);
											unset($PROCESSED["accessible_finish_hour"]);
											unset($PROCESSED["accessible_finish_min"]);
											?>
											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached community page quiz</span></h3>
													<div id="body">
														<h2>Step 3: Quiz Availability</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}

														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Time Release Information">
															<colgroup>
																<col style="width: 3%" />
																<col style="width: 30%" />
																<col style="width: 67%" />
															</colgroup>
															<tr>
																<td colspan="3"><h2>Time Release Options</h2></td>
															</tr>
															<?php
																if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
																	$query = "SELECT `quiztype_code` FROM `quizzes_lu_quiztypes` WHERE `quiztype_id` = ".$db->qstr($PROCESSED["quiztype_id"]);
																	$quiztype = $db->GetOne($query);
																	$quiz_results_delayed = ($quiztype == "delayed" ? true : false);
																}
																echo generate_calendars("accessible", "Accessible", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, $quiz_results_delayed, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), true, true);
															?>
															</table>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_back" id="go_back" value="0" />
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Finish" />
														<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
										case 2 :
											?>
											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached community page quiz</span></h3>
													<div id="body">
														<h2>Step 2: Choose Quiz Options</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<div>Should completion of this quiz be considered optional or required?</div>
															<div class="response-area">
																<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
																<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
															</div>
														</div>

														<div class="wizard-question">
															<div>Should the order of the questions be shuffled for this quiz?</div>
															<div class="response-area">
																<input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
																<input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
															</div>
														</div>

														<div class="wizard-question">
															<div>How much time (in minutes) can the learner spend taking this quiz?</div>
															<div class="response-area">
																<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
															</div>
														</div>

														<div class="wizard-question">
															<div>How many attempts can a learner take at completing this quiz?</div>
															<div class="response-area">
																<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
															</div>
														</div>

														<div class="wizard-question" style="margin-bottom: 0px">
															<div>When should learners be allowed to view the results of the quiz?</div>
															<div class="response-area">
																<?php
																if ($quiz_types_record) {
																	foreach($quiz_types_record as $quiztype_id => $result) {
																		echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".(($PROCESSED["quiztype_id"]) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label><br />";
																		if ($result["quiztype_description"] != "") {
																			echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
																		}
																	}
																}
																?>
															</div>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_back" id="go_back" value="0" />
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
														<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
										case 1 :
										default :
											/**
											 * Load the rich text editor.
											 */
											load_rte();
											?>
											<div class="modal-dialog" id="quiz-wizard">
												<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=edit&amp;id=<?php echo $RECORD_ID; ?>&amp;qid=<?php echo $AQUIZ_ID; ?>" method="post">
												<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
												<?php
												foreach ($PROCESSED as $key => $value) {
													echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />\n";
												}
												?>
												<div id="wizard">
                                                    <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Updating</strong> attached community page quiz</span></h3>
													<div id="body">
														<h2>Step 1: Basic Quiz Information</h2>
														<?php
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
														?>
														<div class="wizard-question">
															<div>You can <span style="font-style: oblique">optionally</span> provide a different title for this quiz.</div>
															<div class="response-area">
																<label for="quiz_title" class="form-required">Attached Quiz Title</label><br />
																<input type="text" id="quiz_title" name="quiz_title" value="<?php echo ((isset($PROCESSED["quiz_title"])) ? html_encode($PROCESSED["quiz_title"]) : ""); ?>" maxlength="128" style="width: 96%;" />
															</div>
														</div>
														<div class="wizard-question">
															<div>You can <span style="font-style: oblique">optionally</span> provide more detailed instructions for this quiz.</div>
															<div class="response-area">
																<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label><br />
																<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 225px"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
																<div class="content-small" style="margin-top: 5px"><strong>Hint:</strong> this information is visibile to the learners at the top of the quiz.</div>
															</div>
														</div>
													</div>
													<div id="footer">
														<input type="hidden" name="go_forward" id="go_forward" value="0" />
														<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
														<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
													</div>
												</div>
												</form>
											</div>
											<?php
										break;
									}
								} else {
									// Error, unable to find the aquiz_id record.
								}
							} else {
								// Error, there was no aquiz_id provided.
							}
						break;
						case "add" :
						default :
							/**
							 * Add quiz form.
							 */

							/**
							 * Required field "quiz_id" / Step 1
							 * Nothing at all can be done without this quiz_id.
							 */
							if ((isset($_POST["quiz_id"])) && ($tmp_input = clean_input($_POST["quiz_id"]))) {
								$query			= "	SELECT a.*
													FROM `quizzes` AS a
													LEFT JOIN `quiz_contacts` AS b
													ON a.`quiz_id` = b.`quiz_id`
													WHERE a.`quiz_id` = ".$db->qstr($tmp_input)."
													AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
								$quiz_record	= $db->GetRow($query);
								if ($quiz_record) {
									if ($quiz_record["quiz_active"] == 1) {
										$PROCESSED["quiz_id"] = $tmp_input;

										/**
										 * Required field "quiz_title" / Attached Quiz Title.
										 */
										if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
											$PROCESSED["quiz_title"] = $tmp_input;
										} else {
											$PROCESSED["quiz_title"] = $quiz_record["quiz_title"];
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
											$PROCESSED["quiztype_id"] = 0;
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

										if (($STEP >= 4) && isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
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

										if ((isset($_POST["accessible_start"])) && ($_POST["accessible_start"] == 1)) {
											$PROCESSED["accessible_start"]		= 1;
											$PROCESSED["accessible_start_date"]	= clean_input($_POST["accessible_start_date"], "credentials");
											$PROCESSED["accessible_start_hour"]	= clean_input($_POST["accessible_start_hour"], "int");
											$PROCESSED["accessible_start_min"]	= clean_input($_POST["accessible_start_min"], "int");
										} else {
											$PROCESSED["accessible_start"]		= 0;
											$PROCESSED["accessible_start_date"]	= "";
											$PROCESSED["accessible_start_hour"]	= 0;
											$PROCESSED["accessible_start_min"]	= 0;
										}

										if ((isset($_POST["accessible_finish"])) && ($_POST["accessible_finish"] == 1)) {
											$PROCESSED["accessible_finish"]			= 1;
											$PROCESSED["accessible_finish_date"]	= clean_input($_POST["accessible_finish_date"], "credentials");
											$PROCESSED["accessible_finish_hour"]	= clean_input($_POST["accessible_finish_hour"], "int");
											$PROCESSED["accessible_finish_min"]		= clean_input($_POST["accessible_finish_min"], "int");
										} else {
											$PROCESSED["accessible_finish"]			= 0;
											$PROCESSED["accessible_finish_date"]	= "";
											$PROCESSED["accessible_finish_hour"]	= 0;
											$PROCESSED["accessible_finish_min"]		= 0;

										}
									}
								}
							} else {
								$STEP = 1;
							}

							/**
							 * If the back button was pressed, we should not error
							 * check the current step, because someone could be just
							 * returning to the previous step without completing the
							 * current one.
							 */
							if (isset($_POST["step_back"]) && $_POST["step_back"]) {
								$STEP = ($STEP - 1);
							}

							/**
							 * Error Checking
							 * This error checking follows a different of a pattern than
							 * else where in this application. As the steps increase, error
							 * checking is continued on all previous steps.
							 */
							switch($STEP) {
								case 5 :
								case 4 :
								case 3 :
									/**
									 * Required field "quiztype_id" / When should learners be allowed to view the results of the quiz?
									 */
									if (!array_key_exists($PROCESSED["quiztype_id"], $quiz_types_record)) {
										$ERROR++;
										$ERRORSTR[]	= "Please select a proper quiz type when asked what type of quiz this should be considered.";
										$STEP		= 3;
									}

								case 2 :
									/**
									 * Required field "quiz_title" / Attached Quiz Title.
									 */
									if ($PROCESSED["quiz_title"] == "") {
										$ERROR++;
										$ERRORSTR[] = "The <strong>Attached Quiz Title</strong> field is required.";
										$STEP		= 2;
									}
								case 1 :
								default :
									if ((!isset($PROCESSED["quiz_id"])) || (!$PROCESSED["quiz_id"])) {
										$ERROR++;
										$ERRORSTR[] = "You must select an active quiz that you have authored in order to attach it to this community page.";
										$STEP		= 1;
									}
								break;
							}

							/**
							 * If the next button was pressed, and we have
							 * successfully error checked the current step, then go
							 * ahead and move to the next one.
							 */
							if (!$ERROR) {
								if (isset($_POST["go_forward"]) && $_POST["go_forward"]) {
									$STEP = ($STEP + 1);
								}
							}

							// Display Add Step
							switch($STEP) {
								case 5 :
									$PROCESSED["content_type"]	= "community_page";
									$PROCESSED["content_id"]	= $RECORD_ID;
									$PROCESSED["accesses"]		= 0;
									$PROCESSED["updated_date"]	= time();
									$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

									if ($db->AutoExecute("attached_quizzes", $PROCESSED, "INSERT")) {
										$modal_onload[] = "parentReload()";
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
												<div id="body">
													<h2>Quiz Attached Successfully</h2>

													<div class="display-success">
														You have successfully attached <strong><?php echo html_encode($PROCESSED["quiz_title"]); ?></strong> to this community page.
													</div>

													To <strong>attach another quiz</strong> or <strong>close this window</strong> please use the buttons below.
												</div>
												<div id="footer">
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<button class="btn btn-primary" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?id=<?php echo $RECORD_ID; ?>&amp;action=add&amp;type=community_page')" style="float: right; margin: 4px 10px 4px 0px">Add Another</button>
												</div>
											</div>
										</div>
										<?php
										history_log($RECORD_ID, "attached $PROCESSED[quiz_title] community quiz.");
									} else {
										$ERROR++;
										$ERRORSTR[] = "There was a problem attaching <strong>".html_encode($PROCESSED["quiz_title"])."</strong> to this community page. The system administrator was informed of this error; please try again later.";

										application_log("error", "Unable to attach quiz [".$PROCESSED["quiz_id"]."] to community page [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
											<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
											<?php
											foreach ($PROCESSED as $key => $value) {
												echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
											}
											?>
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
												<div id="body">
													<h2>Failed To Attach Quiz</h2>
													<?php
													if ($ERROR) {
														echo display_error();
													}
													if ($NOTICE) {
														echo display_notice();
													}
													?>
												</div>
												<div id="footer">
													<input type="hidden" name="go_back" id="go_back" value="0" />
													<input type="hidden" name="go_forward" id="go_forward" value="0" />
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
												</div>
											</div>
											</form>
										</div>
										<?php
									}
								break;
								case 4 :
                                    $modal_onload[] = "updateTime('accessible_start')";
                                    $modal_onload[] = "updateTime('accessible_finish')";

									/**
									 * Unset variables set by this page or they get posted twice.
									 */
									unset($PROCESSED["accessible_start"]);
									unset($PROCESSED["accessible_start_date"]);
									unset($PROCESSED["accessible_start_hour"]);
									unset($PROCESSED["accessible_start_min"]);

									unset($PROCESSED["accessible_finish"]);
									unset($PROCESSED["accessible_finish_date"]);
									unset($PROCESSED["accessible_finish_hour"]);
									unset($PROCESSED["accessible_finish_min"]);
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
											<div id="body">
												<h2>Step 4: Quiz Availability</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}

												if ($NOTICE) {
													echo display_notice();
												}
												?>
												<div class="wizard-question">
													<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Time Release Information">
													<colgroup>
														<col style="width: 3%" />
														<col style="width: 30%" />
														<col style="width: 67%" />
													</colgroup>
													<tr>
														<td colspan="3"><h2>Time Release Options</h2></td>
													</tr>
													<?php
														if (isset($PROCESSED["quiztype_id"]) && $PROCESSED["quiztype_id"]) {
															$query = "SELECT `quiztype_code` FROM `quizzes_lu_quiztypes` WHERE `quiztype_id` = ".$db->qstr($PROCESSED["quiztype_id"]);
															$quiztype = $db->GetOne($query);
															$quiz_results_delayed = ($quiztype == "delayed" ? true : false);
														}
														echo generate_calendars("accessible", "Accessible", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, $quiz_results_delayed, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), true, true);
													?>
													</table>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Finish" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 3 :
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
											<div id="body">
												<h2>Step 3: Choose Quiz Options</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}
												if ($NOTICE) {
													echo display_notice();
												}
												?>
												<div class="wizard-question">
													<div>Should completion of this quiz be considered optional or required?</div>
													<div class="response-area">
														<input type="radio" id="required_no" name="required" value="0"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">Optional</label><br />
														<input type="radio" id="required_yes" name="required" value="1"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">Required</label><br />
													</div>
												</div>

                                                <div class="wizard-question">
                                                    <div>Should the order of the questions be shuffled for this quiz?</div>
                                                    <div class="response-area">
                                                        <input type="radio" id="random_order_no" name="random_order" value="0"<?php echo (((!isset($PROCESSED["random_order"])) || (!$PROCESSED["random_order"])) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_no">Not Shuffled</label><br />
                                                        <input type="radio" id="random_order_yes" name="random_order" value="1"<?php echo (($PROCESSED["random_order"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="random_order_yes">Shuffled</label><br />
                                                    </div>
                                                </div>

												<div class="wizard-question">
													<div>How much time (in minutes) can the learner spend taking this quiz?</div>
													<div class="response-area">
														<input type="text" id="quiz_timeout" name="quiz_timeout" value="<?php echo ((isset($PROCESSED["quiz_timeout"])) ? $PROCESSED["quiz_timeout"] : "0"); ?>" style="width: 50px" maxlength="4" /> min <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> time)</span>
													</div>
												</div>

												<div class="wizard-question">
													<div>How many attempts can a learner take at completing this quiz?</div>
													<div class="response-area">
														<input type="text" id="quiz_attempts" name="quiz_attempts" value="<?php echo ((isset($PROCESSED["quiz_attempts"])) ? $PROCESSED["quiz_attempts"] : "0"); ?>" style="width: 50px" maxlength="4" /> <span class="content-small">(<strong>Hint:</strong> enter 0 to allow <strong>unlimited</strong> attempts)</span>
													</div>
												</div>

												<div class="wizard-question" style="margin-bottom: 0px">
													<div>When should learners be allowed to view the results of the quiz?</div>
													<div class="response-area">
														<?php
														if ($quiz_types_record) {
															foreach($quiz_types_record as $quiztype_id => $result) {
																echo "<input type=\"radio\" id=\"quiztype_".$quiztype_id."\" name=\"quiztype_id\" value=\"".$quiztype_id."\" style=\"vertical-align: middle\"".(($PROCESSED["quiztype_id"]) ? (($PROCESSED["quiztype_id"] == $quiztype_id) ? " checked=\"checked\"" : "") : ((!(int) $result["quiztype_order"]) ? " checked=\"checked\"" : ""))." /> <label for=\"quiztype_".$quiztype_id."\">".html_encode($result["quiztype_title"])."</label><br />";
																if ($result["quiztype_description"] != "") {
																	echo "<div class=\"content-small\" style=\"margin: 0px 0px 10px 23px\">".html_encode($result["quiztype_description"])."</div>";
																}
															}
														}
														?>
													</div>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 2 :
									/**
									 * Load the rich text editor.
									 */
									load_rte();
									?>
									<div class="modal-dialog" id="quiz-wizard">
										<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
										<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
										<?php
										foreach ($PROCESSED as $key => $value) {
											echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
										}
										?>
										<div id="wizard">
                                            <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
											<div id="body">
												<h2>Step 2: Basic Quiz Information</h2>
												<?php
												if ($ERROR) {
													echo display_error();
												}
												if ($NOTICE) {
													echo display_notice();
												}
												?>
												<div class="wizard-question">
													<div>You can <span style="font-style: oblique">optionally</span> provide a different title for this quiz.</div>
													<div class="response-area">
														<label for="quiz_title" class="form-required">Attached Quiz Title</label><br />
														<input type="text" id="quiz_title" name="quiz_title" value="<?php echo ((isset($PROCESSED["quiz_title"])) ? html_encode($PROCESSED["quiz_title"]) : ""); ?>" maxlength="128" style="width: 96%;" />
													</div>
												</div>
												<div class="wizard-question">
													<div>You can <span style="font-style: oblique">optionally</span> provide more detailed instructions for this quiz.</div>
													<div class="response-area">
														<label for="quiz_notes" class="form-nrequired">Attached Quiz Instructions</label><br />
														<textarea id="quiz_notes" name="quiz_notes" style="width: 99%; height: 225px"><?php echo clean_input($PROCESSED["quiz_notes"], array("trim", "allowedtags", "encode")); ?></textarea>
														<div class="content-small" style="margin-top: 5px"><strong>Hint:</strong> this information is visibile to the learners at the top of the quiz.</div>
													</div>
												</div>
											</div>
											<div id="footer">
												<input type="hidden" name="go_back" id="go_back" value="0" />
												<input type="hidden" name="go_forward" id="go_forward" value="0" />
												<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												<input class="btn" type="button" id="back-button" name="back_button" onclick="quizPrevStep()" value="Previous Step" />
											</div>
										</div>
										</form>
									</div>
									<?php
								break;
								case 1 :
								default :
									$query		= "	SELECT a.*, COUNT(c.`quiz_id`) AS `question_total`
													FROM `quizzes` AS a
													LEFT JOIN `quiz_contacts` AS b
													ON a.`quiz_id` = b.`quiz_id`
													LEFT JOIN `quiz_questions` AS c
													ON a.`quiz_id` = c.`quiz_id`
													AND c.`question_active` = '1'
													WHERE a.`quiz_active` = '1'
													AND b.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
													GROUP BY a.`quiz_id`
													ORDER BY a.`quiz_title` ASC";
									$results	= $db->GetAll($query);
									if ($results) {
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<form id="wizard-form" target="upload-frame" action="<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=<?php echo $QUIZ_TYPE; ?>&amp;action=add&amp;id=<?php echo $RECORD_ID; ?>" method="post">
											<input type="hidden" name="step" value="<?php echo $STEP; ?>" />
											<?php
											foreach ($PROCESSED as $key => $value) {
												echo "<input type=\"hidden\" name=\"".html_encode($key)."\" value=\"".html_encode($value)."\" />";
											}
											?>
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
												<div id="body">
													<h2>Step 1: Select Quiz</h2>
													<?php
													if ((isset($_POST)) && (count($_POST))) {
														if ($ERROR) {
															echo display_error();
														}
														if ($NOTICE) {
															echo display_notice();
														}
													}
													?>
													<div class="wizard-question">
														<table class="table table-striped table-bordered" cellspacing="0" summary="List of Quizzes">
														<colgroup>
															<col class="modified" />
															<col class="title" />
															<col class="completed" />
														</colgroup>
														<thead>
															<tr>
																<td colspan="2" class="title sortedASC" style="border-left:none;"><div class="noLink">Quiz Title</div></td>
																<td class="completed" style="border-left:none;">Questions</td>
															</tr>
														</thead>
														<tbody>
															<?php
															foreach ($results as $result) {
																echo "<tr id=\"quiz-".$result["quiz_id"]."\">\n";
																echo "	<td class=\"center\" style=\"vertical-align: middle\"><input type=\"radio\" id=\"quiz_id_".$result["quiz_id"]."\" name=\"quiz_id\" value=\"".$result["quiz_id"]."\"".(((isset($PROCESSED["quiz_id"])) && ($PROCESSED["quiz_id"] == $result["quiz_id"])) ? " checked=\"checked\"" : "")." /></td>\n";
																echo "	<td style=\"vertical-align: middle\">\n";
																echo "		<label for=\"quiz_id_".$result["quiz_id"]."\" class=\"form-nrequired\" style=\"font-weight: bold\">".html_encode($result["quiz_title"])."</label>\n";
																echo "		<div class=\"content-small\" style=\"white-space: normal\">".clean_input(limit_chars($result["quiz_description"], 150), "allowedtags")."</div>\n";
																if (in_array($result["quiz_id"], $existing_quiz_relationship)) {
																	echo "<div class=\"display-notice-inline\"><img src=\"".ENTRADA_URL."/images/list-notice.gif\" width=\"11\" height=\"11\" alt=\"Notice\" title=\"Notice\" style=\"margin-right: 10px\" />This quiz is already attached to this community page.</div>";
																}
																echo "	</td>\n";
																echo "	<td class=\"center\" style=\"vertical-align: middle\" class=\"completed\">".html_encode($result["question_total"])."</td>\n";
																echo "</tr>\n";
															}
															?>
														</tbody>
														</table>
													</div>
												</div>
												<div id="footer">
													<input type="hidden" name="go_forward" id="go_forward" value="0" />
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
													<input class="btn btn-primary" type="button" id="next-button" name="next_button" onclick="quizNextStep()" value="Next Step" />
												</div>
											</div>
											</form>
										</div>
										<?php
									} else {
										?>
										<div class="modal-dialog" id="quiz-wizard">
											<div id="wizard">
                                                <h3 class="border-below">Quiz Wizard <span class="content-small space-left large"><strong>Attaching</strong> new community page quiz</span></h3>
												<div id="body" style="margin-top: 25px">
													<?php
													$NOTICE++;
													$NOTICESTR[] = "You have not yet created any quizzes to attach to this community page.<br /><br />To author a new Quiz, close this window and click the <strong>Manage Quizzes</strong> tab.";

													echo display_notice();
													?>
												</div>
												<div id="footer">
													<button class="btn" id="close-button" onclick="closeWizard()">Close</button>
												</div>
											</div>
										</div>
										<?php
									}
								break;
							}
						break;
					}
					?>
					<div id="scripts-on-open" style="display: none;">
					<?php
                    foreach ($modal_onload as $string) {
                        echo $string.";\n";
                    }
					?>
					window.selectedTimeframe = function (timeframe) {
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

						updateTime('accessible_start');
						updateTime('accessible_finish');
					}
					</div>
					<?php
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The provided community identifier does not exist in this system.";

				echo display_error();

				application_log("error", "Quiz wizard was accessed without a valid community page id.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You must provide an community page identifier when using the quiz wizard.";

			echo display_error();

			application_log("error", "Quiz wizard was accessed without any community page id.");
		}
	}
}