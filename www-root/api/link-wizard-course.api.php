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
 * Loads the Course link wizard when a course director wants to add / edit
 * a linked resource on the Manage Courses > Content page.
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
	$IDENTIFIER			= 0;
	$LINK_ID			= 0;
	$JS_INITSTEP		= 1;

	if(isset($_GET["action"])) {
		$ACTION	= trim($_GET["action"]);
	}

	if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
		$STEP	= (int) trim($_GET["step"]);
	}

	if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$IDENTIFIER	= (int) trim($_GET["id"]);
	}

	if((isset($_GET["lid"])) && ((int) trim($_GET["lid"]))) {
		$LINK_ID	= (int) trim($_GET["lid"]);
	}

	$modal_onload = array();
	if($IDENTIFIER) {
		$query	= "	SELECT * FROM `courses` 
					WHERE `course_id` = ".$db->qstr($IDENTIFIER)."
					AND `course_active` = '1'";
		$result	= $db->GetRow($query);
		if($result) {
			if(!$ENTRADA_ACL->amIAllowed(new CourseContentResource($IDENTIFIER, $result['organisation_id']), 'update')) {
				$modal_onload[]	= "closeWizard()";

				$ERROR++;
				$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module. If you believe you are receiving this message in error please contact us for assistance.";

				echo display_error();

				application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to the link wizard.");
			} else {
				switch($ACTION) {
					case "edit" :
						/**
						 * Edit link form.
						 */
						if($LINK_ID) {
							$query	= "SELECT * FROM `course_links` WHERE `course_id` = ".$db->qstr($IDENTIFIER)." AND `id` = ".$db->qstr($LINK_ID);
							$result	= $db->GetRow($query);
							if($result) {
								// Error Checking
								switch($STEP) {
									case 2 :
										/**
										 * In this error checking we are working backwards along the internal javascript
										 * steps timeline. This is so the JS_INITSTEP variable is set to the lowest page
										 * number that contains errors.
										 */

										$PROCESSED["course_id"] = $IDENTIFIER;

										/**
										 * Step 2 Error Checking
										 * Because this unsets the $ERRORSTR array, only do this if there is not already an error.
										 * PITA, I know.
										 */
										if((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) {
											$timed_release		= validate_calendars("valid", false, false);

											if($ERROR) {
												$modal_onload[]		= "alert('".addslashes($ERRORSTR[0])."')";

												$ERROR			= 0;
												$ERRORSTR		= array();

												$ERROR++;
												$ERRORSTR[]		= "q4";
												$JS_INITSTEP	= 2;
											}

											if((isset($timed_release["start"])) && ((int) $timed_release["start"])) {
												$PROCESSED["valid_from"]	= (int) $timed_release["start"];
											}

											if((isset($timed_release["finish"])) && ((int) $timed_release["finish"])) {
												$PROCESSED["valid_until"]	= (int) $timed_release["finish"];
											}
										} else {
											$PROCESSED["valid_from"]	= 0;
											$PROCESSED["valid_until"]	= 0;
										}

										/**
										 * Step 3 Error Checking
										 */
										if((isset($_POST["link"])) && ($tmp_input = clean_input($_POST["link"], array("notags", "nows"))) && ($tmp_input != "http://")) {
											$PROCESSED["link"] = $tmp_input;
										} else {
											$ERROR++;
											$ERRORSTR[] = "q5";
											$JS_INITSTEP = 3;
										}

										if((isset($_POST["link_title"])) && ($tmp_input = clean_input($_POST["link_title"], array("trim", "notags")))) {
											$PROCESSED["link_title"] = $tmp_input;
										} else {
											$PROCESSED["link_title"] = "";
										}

										if((isset($_POST["link_notes"])) && ($tmp_input = clean_input($_POST["link_notes"], array("trim", "notags")))) {
											$PROCESSED["link_notes"] = $tmp_input;
										} else {
											$ERROR++;
											$ERRORSTR[] = "q7";
											$JS_INITSTEP = 3;
										}

										/**
										 * Step 1 Error Checking
										 */
										if((isset($_POST["proxify"])) && ($_POST["proxify"] == "yes") && ($PROXY_URLS["default"]["active"] != "")) {
											$PROCESSED["proxify"] = 1;
										} else {
											$PROCESSED["proxify"] = 0;
										}

										if((isset($_POST["required"])) && ($_POST["required"] == "yes")) {
											$PROCESSED["required"] = 1;
										} else {
											$PROCESSED["required"] = 0;
										}

										$PROCESSED["updated_date"]	= time();
										$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

										if(!$ERROR) {
											if($db->AutoExecute("course_links", $PROCESSED, "UPDATE", "id = ".$db->qstr($LINK_ID)." AND course_id = ".$db->qstr($IDENTIFIER))) {
												application_log("success", "Link ID ".$LINK_ID." was successfully update to the database for course [".$IDENTIFIER."].");
											} else {
												$modal_onload[]		= "alert('This update was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";

												$ERROR++;
												$ERRORSTR[]		= "q5";
												$JS_INITSTEP	= 3;

												application_log("error", "Unable to update this record in the database for course ID [".$IDENTIFIER."] and link ID [".$LINK_ID."]. Database said: ".$db->ErrorMsg());
											}
										}

										if($ERROR) {
											$STEP = 1;
										}
									break;
									case 1 :
									default :
										/**
										 * Since this is the first step, simply fill $PROCESSED with the $result value.
										 */

										$PROCESSED = $result;

										if(((int) $PROCESSED["valid_from"]) || ((int) $PROCESSED["valid_until"])) {
											$show_timed_release	= true;
										} else {
											$show_timed_release = false;
										}
									break;
								}

								// Display Edit Step
								switch($STEP) {
									case 2 :
										$modal_onload[] = "parentReload()";
										?>
										<div class="modal-dialog" id="link-edit-wizard-<?php echo $LINK_ID; ?>">
											<div id="wizard">
												<h3 class="border-below">Link Wizard <span class="content-small space-left large"><strong>Editing</strong> <?php echo html_encode($PROCESSED["link_title"]); ?></span></h3>
												<div id="body">
													<h2>Link Updated Successfully</h2>
		
													<div class="display-success">
														You have successfully updated <strong><?php echo html_encode($PROCESSED["link_title"]); ?></strong> in this course.
													</div>
		
													To <strong>re-edit this link</strong> or <strong>close this window</strong> please use the buttons below.
												</div>
												<div id="footer">
													<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
													<input type="button" class="btn btn-primary" value="Re-Edit Link" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/link-wizard-course.api.php?action=edit&amp;id=<?php echo $IDENTIFIER; ?>&amp;lid=<?php echo $LINK_ID; ?>')" style="float: right; margin: 4px 10px 4px 0px" />
												</div>
											</div>
										</div>
										<?php
									break;
									case 1 :
									default :
										$modal_onload[] = "initStep(".$JS_INITSTEP.")";

										if(((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) || ((isset($show_timed_release)) && ($show_timed_release))) {
											$modal_onload[] = "timedRelease('block')";
										} else {
											$modal_onload[] = "timedRelease('none')";
										}
										?>
										<div class="modal-dialog" id="link-edit-wizard-<?php echo $LINK_ID; ?>">
											<div id="wizard">
												<form target="upload-frame" id="wizard-form" action="<?php echo ENTRADA_URL; ?>/api/link-wizard-course.api.php?action=edit&amp;id=<?php echo $IDENTIFIER; ?>&amp;lid=<?php echo $LINK_ID; ?>&amp;step=2" method="post" style="display: inline">
												<h3 class="border-below">Link Wizard <span class="content-small space-left large"><strong>Editing</strong> <?php echo html_encode($PROCESSED["link_title"]); ?></span></h3>
												<div id="body">
													<h2 id="step-title"></h2>
													<div id="step1" style="display: none">
														<div id="q1" class="wizard-question<?php echo ((in_array("q1", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">Does this link require the proxy to be enabled?</div>
															<div style="padding-left: 65px">
																<input type="radio" id="proxify_no" name="proxify" value="no"<?php echo (((!isset($PROCESSED["proxify"])) || (!$PROCESSED["proxify"]) || ($PROXY_URLS["default"]["active"] == "")) ? " checked=\"checked\"" : ""); ?> /> <label for="proxify_no">no</label><br />
																<?php if($PROXY_URLS["default"]["active"] != "") : ?>
																<input type="radio" id="proxify_yes" name="proxify" value="yes"<?php echo (($PROCESSED["proxify"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="proxify_yes">yes</label><br />
																<?php endif; ?>
															</div>
														</div>
	
														<div id="q2" class="wizard-question<?php echo ((in_array("q2", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">Is the use of this resource required or optional by the learner?</div>
															<div style="padding-left: 65px">
																<input type="radio" id="required_no" name="required" value="no"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">optional</label><br />
																<input type="radio" id="required_yes" name="required" value="yes"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">required</label><br />
															</div>
														</div>
													</div>
	
													<div id="step2" style="display: none">
														<div id="q4" class="wizard-question<?php echo ((in_array("q4", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">Would you like to add timed release dates to this link?</div>
															<div style="padding-left: 65px">
																<input type="radio" id="timedrelease_no" name="timedrelease" value="no" onclick="timedRelease('none')"<?php echo ((((!isset($_POST["timedrelease"])) || ($_POST["timedrelease"] == "no")) && ((!isset($show_timed_release)) || (!$show_timed_release))) ? " checked=\"checked\"" : ""); ?> /> <label for="timedrelease_no">No, this link is accessible any time.</label><br />
																<input type="radio" id="timedrelease_yes" name="timedrelease" value="yes" onclick="timedRelease('block')"<?php echo ((((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) || ((isset($show_timed_release)) && ($show_timed_release))) ? " checked=\"checked\"" : ""); ?> /> <label for="timedrelease_yes">Yes, this link has timed release information.</label><br />
															</div>
	
															<div id="timed-release-info" style="display: none">
																<br />
																By checking the box on the left, you will enable the ability to select release / revoke dates and times for this link.
																<br /><br />
																<table style="width: 100%" cellspacing="0" cellpadding="4" border="0" summary="Timed Release Information">
																<colgroup>
																	<col style="width: 3%" />
																	<col style="width: 30%" />
																	<col style="width: 67%" />
																</colgroup>
																<?php echo generate_calendars("valid", "Accessible", true, false, ((isset($PROCESSED["valid_from"])) ? $PROCESSED["valid_from"] : 0), true, false, ((isset($PROCESSED["valid_until"])) ? $PROCESSED["valid_until"] : 0), true, true); ?>
																</table>
															</div>
														</div>
													</div>
	
													<div id="step3" style="display: none">
														<div id="q5" class="wizard-question<?php echo ((in_array("q5", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">Please provide the full URL of the link:</div>
															<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
																<label for="link" class="form-required">Link URL:</label> <span class="content-small"><strong>Example:</strong> http://meds.queensu.ca</span><br />
																<input type="text" id="link" name="link" value="<?php echo ((isset($PROCESSED["link"])) ? html_encode($PROCESSED["link"]) : ""); ?>" maxlength="500" style="width: 350px;" />
															</div>
														</div>
														<div id="q6" class="wizard-question<?php echo ((in_array("q6", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">You can <span style="font-style: oblique">optionally</span> provide a different title for this link.</div>
															<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
																<label for="link_title" class="form-nrequired">Link Title:</label> <span class="content-small"><strong>Example:</strong> Faculty of Health Sciences</span><br />
																<input type="text" id="link_title" name="link_title" value="<?php echo ((isset($PROCESSED["link_title"])) ? html_encode($PROCESSED["link_title"]) : ""); ?>" maxlength="128" style="width: 350px;" />
															</div>
														</div>
														<div id="q7" class="wizard-question<?php echo ((in_array("q7", $ERRORSTR)) ? " display-error" : ""); ?>">
															<div style="font-size: 14px">You <span style="font-style: oblique">must</span> provide a description for this link as well.</div>
															<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
																<label for="link_notes" class="form-required">Link Description:</label><br />
																<textarea id="link_notes" name="link_notes" style="width: 350px; height: 75px"><?php echo ((isset($PROCESSED["link_notes"])) ? html_encode($PROCESSED["link_notes"]) : ""); ?></textarea>
															</div>
														</div>
													</div>
												</div>
												<div id="footer">
													<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
													<input type="button" class="btn btn-primary" id="next-button" value="Next Step" onclick="nextStep()" style="float: right; margin: 4px 10px 4px 0px" />
													<input type="button" class="btn" id="back-button" value="Previous Step" onclick="prevStep()" style="display: none; float: right; margin: 4px 10px 4px 0px" />
												</div>
												<div id="uploading-window" style="width: 100%; height: 100%;">
													<div style="display: table; width: 100%; height: 100%; _position: relative; overflow: hidden">
														<div style=" _position: absolute; _top: 50%;display: table-cell; vertical-align: middle;">
															<div style="_position: relative; _top: -50%; width: 100%; text-align: center">
																<span style="color: #003366; font-size: 18px; font-weight: bold">
																	<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="Link Saving" title="Please wait while changes are being saved." style="vertical-align: middle" /> Please Wait: changes are being saved.
																</span>
															</div>
														</div>
													</div>
												</div>
												</form>
											</div>
										</div>
										<?php
									break;
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "The provided link identifier does not exist in the provided course.";

								echo display_error();

								application_log("error", "Link wizard was accessed with a link id that was not found in the database.");
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must provide a link identifier when using the link wizard.";

							echo display_error();

							application_log("error", "Link wizard was accessed without any link id.");
						}
					break;
					case "add" :
					default :
						/**
						 * Add link form.
						 */

						// Error Checking
						switch($STEP) {
							case 2 :
								/**
								 * In this error checking we are working backwards along the internal javascript
								 * steps timeline. This is so the JS_INITSTEP variable is set to the lowest page
								 * number that contains errors.
								 */

								$PROCESSED["course_id"] = $IDENTIFIER;

								/**
								 * Step 2 Error Checking
								 * Because this unsets the $ERRORSTR array, only do this if there is not already an error.
								 * PITA, I know.
								 */
								if((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) {
									$timed_release = validate_calendars("valid", false, false);

									if($ERROR) {
										$modal_onload[]		= "alert('".addslashes($ERRORSTR[0])."')";

										$ERROR			= 0;
										$ERRORSTR		= array();

										$ERROR++;
										$ERRORSTR[]		= "q4";
										$JS_INITSTEP	= 2;
									}

									if((isset($timed_release["start"])) && ((int) $timed_release["start"])) {
										$PROCESSED["valid_from"]	= (int) $timed_release["start"];
									}

									if((isset($timed_release["finish"])) && ((int) $timed_release["finish"])) {
										$PROCESSED["valid_until"] = (int) $timed_release["finish"];
									}
								}

								if(!$ERROR) {
									/**
									 * Step 3 Error Checking
									 */
									if((isset($_POST["link"])) && (trim($_POST["link"])) && (trim($_POST["link"]) != "http://")) {
										$PROCESSED["link"]	= trim($_POST["link"]);
									} else {
										$ERROR++;
										$ERRORSTR[]		= "q5";
										$JS_INITSTEP	= 3;
									}

									if((isset($_POST["link_title"])) && (trim($_POST["link_title"]))) {
										$PROCESSED["link_title"]	= trim($_POST["link_title"]);
									} else {
										$PROCESSED["link_title"]	= "";
									}

									if((isset($_POST["link_notes"])) && (trim($_POST["link_notes"]))) {
										$PROCESSED["link_notes"]	= trim($_POST["link_notes"]);
									} else {
										$ERROR++;
										$ERRORSTR[]		= "q7";
										$JS_INITSTEP	= 3;
									}

									/**
									 * Step 1 Error Checking
									 */
									if((isset($_POST["proxify"])) && ($_POST["proxify"] == "yes")) {
										$PROCESSED["proxify"] = 1;
									} else {
										$PROCESSED["proxify"] = 0;
									}

									if((isset($_POST["required"])) && ($_POST["required"] == "yes")) {
										$PROCESSED["required"] = 1;
									} else {
										$PROCESSED["required"] = 0;
									}

									$PROCESSED["updated_date"]	= time();
									$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

									if(!$ERROR) {
										$query	= "SELECT * FROM `course_links` WHERE `course_id` = ".$db->qstr($IDENTIFIER)." AND `link` = ".$db->qstr($PROCESSED["link"]);
										$result	= $db->GetRow($query);
										if($result) {
											$modal_onload[]		= "alert('A link to ".addslashes($PROCESSED["link"])." already exists in this course.')";

											$ERROR++;
											$ERRORSTR[]		= "q5";
											$JS_INITSTEP	= 3;
										} else {
											if((!$db->AutoExecute("course_links", $PROCESSED, "INSERT")) || (!$LINK_ID = $db->Insert_Id())) {
												$modal_onload[]		= "alert('The new link was not successfully saved. The MEdTech Unit has been informed of this error, please try again later.')";

												$ERROR++;
												$ERRORSTR[]		= "q5";
												$JS_INITSTEP	= 3;

												application_log("error", "Unable to insert the link into the database for course ID [".$IDENTIFIER."]. Database said: ".$db->ErrorMsg());
											}
										}
									}
								}

								if($ERROR) {
									$STEP = 1;
								}
							break;
							case 1 :
							default :
								continue;
							break;
						}

						// Display Add Step
						switch($STEP) {
							case 2 :
								$modal_onload[] = "parentReload()";
								?>
								<div class="modal-dialog" id="link-add-wizard">
									<div id="wizard">
										<h3 class="border-below">Link Wizard <span class="content-small space-left large"><strong>Adding</strong> new course link</span></h3>
										<div id="body">
											<h2>Link Added Successfully</h2>
		
											<div class="display-success">
												You have successfully added <strong><?php echo html_encode($PROCESSED["link"]); ?></strong> to this course.
											</div>
		
											To <strong>add another link</strong> or <strong>close this window</strong> please use the buttons below.
										</div>
										<div id="footer">
											<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
											<input type="button" class="btn btn-primary" value="Add Another Link" onclick="restartWizard('<?php echo ENTRADA_URL; ?>/api/link-wizard-course.api.php?id=<?php echo $IDENTIFIER; ?>&amp;action=add')" style="float: right; margin: 4px 10px 4px 0px" />
										</div>
									</div>
								</div>
								<?php
							break;
							case 1 :
							default :
								$modal_onload[] = "initStep(".$JS_INITSTEP.")";

								if((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) {
									$modal_onload[] = "timedRelease('block')";
								} else {
									$modal_onload[] = "timedRelease('none')";
								}
								?>
								<div class="modal-dialog" id="link-add-wizard">
									<div id="wizard">
										<form target="upload-frame" id="wizard-form" action="<?php echo ENTRADA_URL; ?>/api/link-wizard-course.api.php?action=add&amp;id=<?php echo $IDENTIFIER; ?>&amp;step=2" method="post" style="display: inline">
										<h3 class="border-below">Link Wizard <span class="content-small space-left large"><strong>Adding</strong> new course link</span></h3>
										<div id="body">
											<h2 id="step-title"></h2>
											<div id="step1" style="display: none">
												<div id="q1" class="wizard-question<?php echo ((in_array("q1", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">Does this link require the proxy to be enabled?</div>
													<div style="padding-left: 65px">
														<input type="radio" id="proxify_no" name="proxify" value="no"<?php echo (((!isset($PROCESSED["proxify"])) || (!$PROCESSED["proxify"])) ? " checked=\"checked\"" : ""); ?> /> <label for="proxify_no">no</label><br />
														<input type="radio" id="proxify_yes" name="proxify" value="yes"<?php echo (($PROCESSED["proxify"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="proxify_yes">yes</label><br />
													</div>
												</div>
	
												<div id="q2" class="wizard-question<?php echo ((in_array("q2", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">Is the use of this resource required or optional by the learner?</div>
													<div style="padding-left: 65px">
														<input type="radio" id="required_no" name="required" value="no"<?php echo (((!isset($PROCESSED["required"])) || (!$PROCESSED["required"])) ? " checked=\"checked\"" : ""); ?> /> <label for="required_no">optional</label><br />
														<input type="radio" id="required_yes" name="required" value="yes"<?php echo (($PROCESSED["required"] == 1) ? " checked=\"checked\"" : ""); ?> /> <label for="required_yes">required</label><br />
													</div>
												</div>
											</div>
	
											<div id="step2" style="display: none">
												<div id="q4" class="wizard-question<?php echo ((in_array("q4", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">Would you like to add timed release dates to this link?</div>
													<div style="padding-left: 65px">
														<input type="radio" id="timedrelease_no" name="timedrelease" value="no" onclick="timedRelease('none')"<?php echo (((!isset($_POST["timedrelease"])) || ($_POST["timedrelease"] == "no")) ? " checked=\"checked\"" : ""); ?> /> <label for="timedrelease_no">No, this link is accessible any time.</label><br />
														<input type="radio" id="timedrelease_yes" name="timedrelease" value="yes" onclick="timedRelease('block')"<?php echo (((isset($_POST["timedrelease"])) && ($_POST["timedrelease"] == "yes")) ? " checked=\"checked\"" : ""); ?> /> <label for="timedrelease_yes">Yes, this link has timed release information.</label><br />
													</div>
	
													<div id="timed-release-info" style="display: none">
														<br />
														By checking the box on the left, you will enable the ability to select release / revoke dates and times for this link.
														<br /><br />
														<table style="width: 100%" cellspacing="0" cellpadding="4" border="0" summary="Timed Release Information">
														<colgroup>
															<col style="width: 3%" />
															<col style="width: 30%" />
															<col style="width: 67%" />
														</colgroup>
														<?php echo generate_calendars("valid", "Accessible", true, false, ((isset($PROCESSED["valid_from"])) ? $PROCESSED["valid_from"] : 0), true, false, ((isset($PROCESSED["valid_until"])) ? $PROCESSED["valid_until"] : 0), true, true); ?>
														</table>
													</div>
												</div>
											</div>
	
											<div id="step3" style="display: none">
												<div id="q5" class="wizard-question<?php echo ((in_array("q5", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">Please provide the full URL of the link:</div>
													<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
														<label for="link" class="form-required">Link URL:</label> <span class="content-small"><strong>Example:</strong> http://meds.queensu.ca</span><br />
														<input type="text" id="link" name="link" value="<?php echo ((isset($PROCESSED["link"])) ? html_encode($PROCESSED["link"]) : "http://"); ?>" maxlength="500" style="width: 350px;" />
													</div>
												</div>
												<div id="q6" class="wizard-question<?php echo ((in_array("q6", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">You can <span style="font-style: oblique">optionally</span> provide a different title for this link.</div>
													<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
														<label for="link_title" class="form-nrequired">Link Title:</label> <span class="content-small"><strong>Example:</strong> Faculty of Health Sciences</span><br />
														<input type="text" id="link_title" name="link_title" value="<?php echo ((isset($PROCESSED["link_title"])) ? html_encode($PROCESSED["link_title"]) : ""); ?>" maxlength="128" style="width: 350px;" />
													</div>
												</div>
												<div id="q7" class="wizard-question<?php echo ((in_array("q7", $ERRORSTR)) ? " display-error" : ""); ?>">
													<div style="font-size: 14px">You <span style="font-style: oblique">must</span> provide a description for this link as well.</div>
													<div style="padding-left: 65px; padding-right: 10px; padding-top: 10px">
														<label for="link_notes" class="form-required">Link Description:</label><br />
														<textarea id="link_notes" name="link_notes" style="width: 350px; height: 75px"><?php echo ((isset($PROCESSED["link_notes"])) ? html_encode($PROCESSED["link_notes"]) : ""); ?></textarea>
													</div>
												</div>
											</div>
										</div>
										<div id="footer">
											<input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
											<input type="button" class="btn btn-primary" id="next-button" value="Next Step" onclick="nextStep()" style="float: right; margin: 4px 10px 4px 0px" />
											<input type="button" class="btn" id="back-button" value="Previous Step" onclick="prevStep()" style="display: none; float: right; margin: 4px 10px 4px 0px" />
										</div>
										<div id="uploading-window" style="width: 100%; height: 100%;">
											<div style="display: table; width: 100%; height: 100%; _position: relative; overflow: hidden">
												<div style=" _position: absolute; _top: 50%;display: table-cell; vertical-align: middle;">
													<div style="_position: relative; _top: -50%; width: 100%; text-align: center">
														<span style="color: #003366; font-size: 18px; font-weight: bold">
															<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="Link Uploading" title="Please wait while this link is being added." style="vertical-align: middle" /> Please Wait: this link is being added.
														</span>
													</div>
												</div>
											</div>
										</div>
										</form>
									</div>
								</div>
								<?php
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
				</div>
				<?php
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "The provided course identifier does not exist in this system.";

			echo display_error();

			application_log("error", "Link wizard was accessed without a valid course id.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "You must provide an course identifier when using the link wizard.";

		echo display_error();

		application_log("error", "Link wizard was accessed without any course id.");
	}
}