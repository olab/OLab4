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
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

switch($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]) {
	case "student" :
		if(isset($NOTIFICATION_ID) && ((int) $NOTIFICATION_ID)) {
			$query		= "	SELECT a.*, b.`region_id`, b.`event_title`, b.`event_desc`, b.`event_start`, b.`event_finish`, c.`form_id`, d.`form_type`, d.`form_title`, d.`form_desc`
							FROM `".CLERKSHIP_DATABASE."`.`notifications` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
							ON b.`event_id` = a.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`evaluations` AS c
							ON c.`item_id` = a.`item_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`eval_forms` AS d
							ON d.`form_id` = c.`form_id`
							WHERE a.`notification_id` = ".$db->qstr($NOTIFICATION_ID)."
							AND a.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
							AND a.`item_maxinstances` > '0'
							AND (
								a.`notification_status` <> 'complete'
								OR a.`notification_status` <> 'cancelled'
							)
							AND b.`event_finish` < ".$db->qstr(strtotime("00:00:00", time()))."
							AND b.`event_status` = 'published'
							AND c.`item_status` = 'published'
							AND d.`form_status` = 'published'";
			$evaluate	= $db->GetRow($query);
			if($evaluate) {
				$BREADCRUMB[]	= array("url" => "", "title" => "Complete Evaluation");

				// Error Checking
				switch($STEP) {
					case "2" :
						$question_num				= 0;
						$RESPONSES					= array();
						$PROCESSED["instructor_id"]	= 0;
						
						if((isset($_POST["form"])) && (is_array($_POST["form"])) && (isset($_POST["form"][$evaluate["form_id"]])) && (is_array($_POST["form"][$evaluate["form_id"]]))) {
							foreach($_POST["form"][$evaluate["form_id"]] as $question_id => $answers) {
								$question_num++;
								if(($question_id = clean_input($question_id, array("trim", "int"))) && (is_array($answers))) {
									$query	= "SELECT `question_required` FROM `".CLERKSHIP_DATABASE."`.`eval_questions` WHERE `question_id` = ".$db->qstr($question_id);
									$result	= $db->GetRow($query);
									if($result) {
										if((int) $result["question_required"]) {
											/**
											 * If it's required and there is no answer provided give an error.
											 */
											if(!array_key_exists("answer", $answers)) {
												$ERROR++;
												$ERRORSTR[]	= "Please select an answer for question number <strong>".$question_num."</strong> to continue.";
											} else {
												foreach($answers as $answer_type => $answer_id) {
													/**
													 * Add all answers to the $RESPONSES array.
													 */
													switch($answer_type) {
														case "comment" :
															/**
															 * This I don't quite understand and need to figure out.
															 */
															if($answer_id) {
																$RESPONSES[$question_num][$question_id]["comment"] = array(key($answer_id) => $answer_id[key($answer_id)]);
															}
														break;
														default :
															if($answer_id = (int) $answer_id) {
																$query 	= "SELECT `answer_value` FROM `".CLERKSHIP_DATABASE."`.`eval_answers` WHERE `answer_id` = ".$db->qstr((int) $answer_id);
																$result	= $db->GetRow($query);
																if($result) {
																	$RESPONSES[$question_num][$question_id]["answer"] = array($answer_id => $result["answer_value"]);
																}
															}
														break;
													}
												}
											}
										}
									}
								}
							}

							/**
							 * For specific error checking.
							 */
							switch($evaluate["form_type"]) {
								case "teacher" :
									if((isset($_POST["instructor_id"])) && (($_POST["instructor_id"] == "other") || ((int) $_POST["instructor_id"]))) {
										if($_POST["instructor_id"] == "other") {
											$PROCESSED_TEACHER = array();
											
											/**
											 * Required: other_teacher_fname / Firstname
											 */
											if((isset($_POST["other_teacher_fname"])) && ($other_teacher_fname = clean_input($_POST["other_teacher_fname"], array("trim", "notags")))) {
												$PROCESSED_TEACHER["firstname"] = $other_teacher_fname;
											} else {
												$ERROR++;
												$ERRORSTR[]	= "You have selected &quot;Other Teacher&quot; from the teacher list but have not provided their firstname.";
											}

											/**
											 * Required: other_teacher_lname / Lastname
											 */
											if((isset($_POST["other_teacher_lname"])) && ($other_teacher_lname = clean_input($_POST["other_teacher_lname"], array("trim", "notags")))) {
												$PROCESSED_TEACHER["lastname"] = $other_teacher_lname;
											} else {
												$ERROR++;
												$ERRORSTR[]	= "You have selected &quot;Other Teacher&quot; from the teacher list but have not provided their lastname.";
											}

											/**
											 * Not Required: other_teacher_email / E-Mail Address
											 */
											if((isset($_POST["other_teacher_email"])) && ($other_teacher_email = clean_input($_POST["other_teacher_email"], array("trim", "notags")))) {
												if(valid_address($other_teacher_email)) {
													$PROCESSED_TEACHER["email"] = $other_teacher_email;
												} else {
													$ERROR++;
													$ERRORSTR[]	= "You have selected &quot;Other Teacher&quot; from the teacher list but you have provided us with an invalid e-mail address.";
												}
											} else {
												$PROCESSED_TEACHER["email"] = "";
											}
			
											if(!$ERROR) {
												if($PROCESSED_TEACHER["email"]) {
													$query	= "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `email` = ".$db->qstr($PROCESSED_TEACHER["email"]);
													$result	= $db->GetRow($query);
													if($result) {
														$PROCESSED["instructor_id"] = $result["id"];
													}
												}
												
												if(!(int) $PROCESSED["instructor_id"]) {
													if(($db->AutoExecute(CLERKSHIP_DATABASE.".other_teachers", $PROCESSED_TEACHER, "INSERT")) && ($oteacher_id = $db->Insert_Id())) {
														$PROCESSED["instructor_id"]	= "OT-".$oteacher_id;	
													} else {
														$ERROR++;
														$ERRORSTR[] = "We are unable to add your other teacher information at this time. This MEdTech Unit has been notified of this error, please try again later.";
														
														application_log("error", "Unable to add new teacher to other_teachers table: ".$db->ErrorMsg());
													}
												}
											}
										} else {
											$PROCESSED["instructor_id"] = clean_input($_POST["instructor_id"], array("trim", "int"));
										}
									} else {													
										$ERROR++;
										$ERRORSTR[]	= "Please select a teacher out of the list that you wish to evaluate. If the teacher that you wish to evaluate is not in the list of teachers, please choose <strong>Other Teacher</strong> from the selection list and provide us with the required details.";
									}
									
									if(!$ERROR) {
										$query	= "
												SELECT a.`completed_id`
												FROM `".CLERKSHIP_DATABASE."`.`eval_completed` AS a
												LEFT JOIN `".CLERKSHIP_DATABASE."`.`notifications` AS b
												ON b.`notification_id` = a.`notification_id`
												WHERE a.`instructor_id` = ".$db->qstr($PROCESSED["instructor_id"])."
												AND b.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
										$result	= $db->GetRow($query);
										if($result) {
											$ERROR++;
											$ERRORSTR[] = "Our database indicates that you have already evaluated this teacher, please choose a different teacher to evaluate.";
										}
									}
								break;
								default :
									continue;
								break;
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "The form that you have submitted does not contain any responses, please try again. If you continue to have problems, please let the MEdTech Unit know.";
							
							application_log("error", "There was no form post submitted.");
						}
		
						if(!$ERROR) {
							$PROCESSED["notification_id"]	= $NOTIFICATION_ID;
							$PROCESSED["completed_status"]	= "complete"; // Note: the status needs to be determined for future implentations - current version only has 1 status which is complete.
							$PROCESSED["completed_lastmod"]	= time();							
		
							if(($db->AutoExecute(CLERKSHIP_DATABASE.".eval_completed", $PROCESSED, "INSERT")) && ($completed_id = $db->Insert_Id())) {
								$PROCESSED						= array();
								$PROCESSED["completed_id"]		= $completed_id;
								$PROCESSED["result_lastmod"]	= time();	
		
								$db->StartTrans();
								foreach($RESPONSES as $question_num => $question_numbers) {
									foreach($question_numbers as $question_ids) {
										foreach($question_ids as $question_answers) {
											foreach($question_answers as $answer_id => $answer_value) {
												if(($answer_id = clean_input($answer_id, array("trim", "int"))) && ($answer_value = clean_input($answer_value, "trim"))) {
													$PROCESSED["answer_id"] 	= $answer_id;
													$PROCESSED["result_value"]	= $answer_value;
												
													if(!$db->AutoExecute(CLERKSHIP_DATABASE.".eval_results", $PROCESSED, "INSERT")) {
														$ERROR++;
														$ERRORSTR[] = "Unable to process answers for question ".($question_num + 1).". The MEdTech Unit has been informed of this problem, please try again later.";
														
														application_log("error", "Failed to insert data into the eval_results table. Database said: ".$db->ErrorMsg());
													}
												}
											}
										}
									}
								}
								
								if(!$db->CompleteTrans()) {
									$ERROR++;
									$ERRORSTR[] = "Unable to save evaluation results at this time. We apologize for the inconvenience, please try again later.";
									
									application_log("error", "Unable to complete the evaluation save transaction: ".$db->ErrorMsg());
								}
															
								if(!$ERROR) {
									$PROCESSED						= array();
									$PROCESSED["item_maxinstances"]	= ($evaluate["item_maxinstances"] - 1);
									
									if(!(int) $PROCESSED["item_maxinstances"]) {
										$PROCESSED["notification_status"] = "complete";
									}
									
									if(!$db->AutoExecute(CLERKSHIP_DATABASE.".notifications", $PROCESSED, "UPDATE", "`user_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `notification_id` = ".$db->qstr($NOTIFICATION_ID))) {
										application_log("error", "Unable to update the status of a notification. Database said: ".$db->ErrorMsg());
									}
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "We are unable to process your evaluation form at the present time. The MEdTech Unit has been informed of this error, and is working to resolve it. Please try again later.";
								
								application_log("error", "Failed to insert evaluation results into the database. Database said: ".$db->ErrorMsg());
							}
						}
						
						if($ERROR) {
							$STEP = 1;	
						}
					break;
					default:
						continue;
					break;
				}
				
				// Display Content
				switch($STEP) {
					case 2:
						echo "<h1>Evaluation Completed</h1>";

						$query	= "
								SELECT `item_maxinstances`
								FROM `".CLERKSHIP_DATABASE."`.`notifications`
								WHERE `notification_id` = ".$db->qstr($NOTIFICATION_ID)."
								AND `item_maxinstances` > '0'
								AND `notification_status` <> 'complete'
								AND `notification_status` <> 'cancelled'";
						$result	= $db->GetRow($query);
						if($result) {
							$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/clerkship?section=evaluate&amp;nid=".$NOTIFICATION_ID."\'', 10000)";

							$SUCCESS++;
							$SUCCESSSTR[] = "You have successfully completed this evaluation, thank-you for your invaluable feedback.";
							
							echo display_success();
							
							echo "We now invite you to complete ".(($result["item_maxinstances"] != 1) ? " up to <strong>".$result["item_maxinstances"]."</strong> more of these evaluations" : " <strong>one last</strong> evaluation")." if you would like to.";
							echo "<br /><br />\n";
							echo "<div style=\"border-top: 2px #CCCCCC solid; padding: 10px 4px 4px 10px; margin-bottom: 15px\">\n";
							echo "	<form>";
							echo "	<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
							echo "	<tr>\n";
							echo "		<td style=\"width: 50%px; text-align: left\">\n";
							echo "			<input type=\"button\" class=\"btn\" value=\"Close\" onclick=\"window.location='".ENTRADA_URL."/clerkship'\" />\n";
							echo "		</td>\n";
							echo "		<td style=\"width: 50%; text-align: right\">\n";
							echo "			<input type=\"button\" class=\"btn btn-primary\" value=\"Proceed\" onclick=\"window.location='".ENTRADA_URL."/clerkship?section=evaluate&amp;nid=".$NOTIFICATION_ID."'\" />\n";
							echo "		</td>\n";
							echo "	</tr>\n";
							echo "	</table>\n";
							echo "	</form>\n";
							echo "</div>\n";
						} else {
							$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/clerkship\'', 5000)";
							
							$SUCCESS++;
							$SUCCESSSTR[] = "You have successfully completed this evaluation, thank-you for your invaluable feedback.<br /><br />You will now be returned to the Clerkship tab, or if you prefer not to wait please <a href=\"".ENTRADA_URL."/clerkship\">click here</a>.";
							
							echo display_success();

							echo "<div style=\"border-top: 2px #CCCCCC solid; padding: 10px 4px 4px 10px; margin-bottom: 15px\">\n";
							echo "	<form>\n";
							echo "	<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
							echo "	<tr>\n";
							echo "		<td style=\"text-align: right\">\n";
							echo "			<input type=\"button\" class=\"btn\" value=\"Return\" onclick=\"window.location='".ENTRADA_URL."/clerkship'\" />\n";
							echo "		</td>\n";
							echo "	</tr>\n";
							echo "	</table>\n";
							echo "	</form>\n";
							echo "</div>\n";
						}
					break;
					case 1 :
					default :
						echo "<h1>".html_encode($evaluate["form_title"])."</h1>";

						if($ERROR) {
							echo display_error();
						}

						if($NOTICE) {
							echo display_notice();
						}
						?>
						
						<form action="<?php echo ENTRADA_URL; ?>/clerkship?section=evaluate&amp;nid=<?php echo $NOTIFICATION_ID; ?>&amp;step=2" method="post">
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluating <?php echo html_encode($evaluate["event_title"]); ?>">
						<colgroup>
							<col style="width: 30%" />
							<col style="width: 70%" />
						</colgroup>
						<tfoot>
							<tr>
								<td colspan="2" style="text-align: right; padding-top: 15px">
									<input type="submit" class="btn" value="Submit" />
								</td>				
							</tr>
						</tfoot>
						<tbody>
							<tr>
								<td colspan="2"><h2>Rotation Information</h2></td>
							</tr>
							<tr>
								<td>Rotation Name:</td>
								<td><?php echo clean_input($evaluate["event_title"], array("htmlbrackets", "trim")); ?></td>
							</tr>
							<tr>
								<td>Rotation Region:</td>
								<td><?php echo html_encode(clerkship_region_name($evaluate["region_id"])); ?></td>
							</tr>
							<tr>
								<td>Rotation Started:</td>
								<td><?php echo date(DEFAULT_DATE_FORMAT, $evaluate["event_start"]); ?></td>
							</tr>
							<tr>
								<td>Rotation Ended:</td>
								<td><?php echo date(DEFAULT_DATE_FORMAT, $evaluate["event_finish"]); ?></td>
							</tr>
							<?php
							switch($evaluate["form_type"]) {
								case "teacher" :
									$ONLOAD[]				= "displayOtherTeacher()";
									$previously_evaluated	= array();
									
									$query		= "
												SELECT `instructor_id`
												FROM `".CLERKSHIP_DATABASE."`.`eval_completed`
												WHERE `notification_id` = ".$db->qstr($NOTIFICATION_ID);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											/**
											 * Determine if it's an "Other Teacher" or not.
											 */
											if(substr($result["instructor_id"], 0, 3) == "OT-") {
												if($tmp_instructor_id = (int) str_replace("OT-", "", $result["instructor_id"])) {
													$squery		= "
																SELECT CONCAT_WS(', ', `lastname`, `firstname`) AS `fullname`
																FROM `".CLERKSHIP_DATABASE."`.`other_teachers`
																WHERE `oteacher_id` = ".$db->qstr($tmp_instructor_id);
													$sresult	= $db->GetRow($squery);
													if($sresult) {
														$previously_evaluated[$result["instructor_id"]] = $sresult["fullname"];
													}
												}
											} else {
												$squery		= "
															SELECT CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
															FROM `".AUTH_DATABASE."`.`user_data` AS a
															WHERE a.`id` = ".$db->qstr($result["instructor_id"]);
												$sresult	= $db->GetRow($squery);
												if($sresult) {
													$previously_evaluated[$result["instructor_id"]] = $sresult["fullname"];
												}
											}
										}
										
										@asort($previously_evaluated);
									}
									?>
									<tr>
										<td colspan="2">&nbsp;</td>
									</tr>
									<tr>	
										<td style="vertical-align: top"><label for="instructor_id" class="form-required">Select Teacher:</label></td>
										<td style="vertical-align: top">
											<?php
											$query		= "
														SELECT a.`department_id`, c.`id` AS `proxy_id`, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`, d.`department_title`
														FROM `".CLERKSHIP_DATABASE."`.`category_departments` AS a
														LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS b
														ON b.`dep_id` = a.`department_id`
														LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
														ON c.`id` = b.`user_id`
														LEFT JOIN `".AUTH_DATABASE."`.`departments` AS d
														ON d.`department_id` = b.`dep_id`
														WHERE a.`category_id` = ".$db->qstr($evaluate["category_id"])."
														ORDER BY c.`lastname` ASC, c.`firstname` ASC";
											$results	= $db->GetAll($query);
											echo "<select id=\"instructor_id\" name=\"instructor_id\" onchange=\"displayOtherTeacher()\">\n";
											echo "	<option value=\"0\">-- Select Teacher To Evaluate --</option>\n";
											if($results) {
												foreach($results as $result) {
													if((!is_array($previously_evaluated)) || (!array_key_exists($result["proxy_id"], $previously_evaluated))) {
														echo "	<option value=\"".(int) $result["proxy_id"]."\"".(((isset($_POST["instructor_id"])) && ($_POST["instructor_id"] == $result["proxy_id"])) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"])."</option>\n";
													}
												}
											}
											echo "	<option value=\"\">----</option>\n";
											echo "	<option value=\"other\"".(((isset($_POST["instructor_id"])) && ($_POST["instructor_id"] == "other")) ? " selected=\"selected\"" : "").">Other Teacher</option>\n";
											echo "</select>\n";
											?>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div id="other_teacher_layer" style="display: none">
												<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluating <?php echo html_encode($evaluate["event_title"]); ?>">
												<colgroup>
													<col style="width: 30%" />
													<col style="width: 70%" />
												</colgroup>
												<tbody>
													<tr>
														<td><label for="other_teacher_fname" class="form-required">Firstname:</label></td>
														<td><input type="text" id="other_teacher_fname" name="other_teacher_fname" style="width: 200px" value="<?= html_encode(trim($_POST["other_teacher_fname"])) ?>" maxlength="45" /></td>
													</tr>
													<tr>
														<td><label for="other_teacher_lname" class="form-required">Lastname:</label></td>
														<td><input type="text" id="other_teacher_lname" name="other_teacher_lname" style="width: 200px" value="<?= html_encode(trim($_POST["other_teacher_lname"])) ?>" maxlength="45" /></td>
													</tr>
													<tr>
														<td><label for="other_teacher_email" class="form-nrequired">E-Mail Address:</label></td>
														<td><input type="text" id="other_teacher_email" name="other_teacher_email" style="width: 200px" value="<?= html_encode(trim($_POST["other_teacher_email"])) ?>" maxlength="125" /></td>
													</tr>
												</tbody>
												</table>
											</div>
										</td>
									</tr>
									<?php
									if((is_array($previously_evaluated)) && (count($previously_evaluated))) {
										?>
										<tr>
											<td>&nbsp;</td>
											<td style="padding-top: 5px">
												<div class="content-small" style="font-weight: bold">You have previously evaluated:</div>
												<ol class="content-small">
												<?php
												foreach($previously_evaluated as $fullname) {
													echo "<li>".html_encode($fullname)."</li>\n";
												}
												?>
												</ol>
											</td>
										</tr>
										<?php
									}
								break;
								default :
									continue;	
								break;
							}					
							?>
							<tr>
								<td colspan="2"><h2>Evaluation Questions</h2></td>
							</tr>
							<tr>
								<td colspan="2" style="border: 2px #EEEEEE solid">
								<?php
								$query			= "
												SELECT *
												FROM `".CLERKSHIP_DATABASE."`.`eval_questions`
												WHERE `form_id` = ".$db->qstr($evaluate["form_id"]);
								$questions		= $db->GetAll($query);
								if($questions) {
									foreach($questions as $question_num => $question) {
										$query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`eval_answers` WHERE `question_id` = ".$db->qstr($question["question_id"]);
										$answers 	= $db->GetAll($query);
										if($answers) {
											echo "<table style=\"width: 100%; background-color: ".(($question_num % 2) ? "#EEEEEE" : "#FFFFFF")."; margin-bottom: 2px\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\">\n";
											echo "<tbody>\n";
											echo "<tr>\n";
											echo "	<td style=\"font-weight: bold\">".($question_num + 1).". ".$question["question_text"]."</td>\n";
											echo "</tr>";
											echo "<tr>\n";
											echo "	<td>";
											switch($question["question_style"]) {
												case "vertical" :
													/**
													 * Not yet implemented.
													 */
												break;
												case "horizontal" :
												default :
													echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\">";
													echo "<tbody>";
													echo "	<tr>";
													for($i = 0; $i < count($answers); $i++) {
														/**
														 * Labels
														 */
														if($answers[$i]["answer_type"] == $FIELD_ANSWERTYPE["radio"]["value"]) {
															$column_width = (100 / count($answers))."%";
															/**
															 * Check if the answer_value is 0. If it 0, then it means this is actually
															 * a comment field, not a question.
															 */
															if($answers[$i]["answer_value"] == 0) {
																$comments[$i]["answer_id"]		= $answers[$i]["answer_id"];
														 		$comments[$i]["label"]			= $answers[$i]["answer_label"];	
														 		$comments[$i]["question_id"]	= $answers[$i]["question_id"];	
															} else {
																echo "	<td style=\"width: ".$column_width."; text-align: center\"><label for=\"".$evaluate["form_id"]."_".$answers[$i]["question_id"]."_answer_".$i."\">".html_encode($answers[$i]["answer_label"])."</label></td>";
															}
														}
													}
													echo "	</tr>";
													echo "	<tr>";
													for($i = 0; $i < count($answers); $i++) {
														/**
														 * Values
														 */
														if ($answers[$i]["answer_type"] == $FIELD_ANSWERTYPE["radio"]["value"]) {
															if ($answers[$i]["answer_value"] != 0) { 
																echo "<td align=\"center\">\n";
																echo "	<input type=\"radio\" id=\"".$evaluate["form_id"]."_".$answers[$i]["question_id"]."_answer_".$i."\" name=\"form[".$evaluate["form_id"]."][".$answers[$i]["question_id"]."][answer]\" value=\"".html_encode($answers[$i]["answer_id"])."\"".((isset($_POST["form"][$evaluate["form_id"]][$answers[$i]["question_id"]]["answer"]) && $_POST["form"][$evaluate["form_id"]][$answers[$i]["question_id"]]["answer"] == $answers[$i]["answer_id"]) ? " checked=\"checked\"" : "")." />\n";
																echo "</td>\n";
															}
														} 
													}
													echo "</tr>";
													echo "<tr>\n";
													echo "	<td colspan=\"".count($answers)."\" style=\"padding: 10px 5px 0px 23px\">";
													foreach($comments as $comment) {
														echo "	<label for=\"".$evaluate["form_id"]."_".$comment["question_id"]."_comment_".$comment["answer_id"]."\" class=\"form-nrequired\">".html_encode($comment["label"])."</label><br />\n";
														echo "	<textarea id=\"".$evaluate["form_id"]."_".$comment["question_id"]."_comment_".$comment["answer_id"]."\" name=\"form[".$evaluate["form_id"]."][".$comment["question_id"]."][comment][".$comment["answer_id"]."]\" rows=\"3\" cols=\"65\" style=\"width: 95%; height: 65px\">".((isset($_POST["form"][$evaluate["form_id"]][$comment["question_id"]]["comment"][$comment["answer_id"]])) ? clean_input($_POST["form"][$evaluate["form_id"]][$comment["question_id"]]["comment"][$comment["answer_id"]], array("trim", "notags", "encode")) : "")."</textarea>\n";
													}
													echo "		</td>\n";
													echo "	</tr>";
													echo "	<tr>\n";
													echo "		<td colspan=\"".count($answers)."\">&nbsp;</td>\n";
													echo "	</tr>\n";
													echo "</tbody>\n";
													echo "</table>\n";
												break;																			
											}
											echo "	</td>\n";
											echo "</tr>";
											echo "</table>";
										} else {
											application_log("error", "There were no possible answers for question_id [".$question["question_id"]."] so the question was not displayed. Database said: ".$db->ErrorMsg());
										}
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "There are no questions attached to this evaluation at this time. The MEdTech Unit has been informed of the problem and will resolve it shortly, please try again later.";
									
									echo display_error();
									
									application_log("error", "There were no questions attached to form_id [".$evaluate["form_id"]."]. Database said: ".$db->ErrorMsg());
								}
								?>
								</td>
							</tr>
						</tbody>
						</table>
						</form>
						<?php
					break;
				}
			} else {
				$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/clerkship\'', 10000)";
				
				$ERROR++;
				$ERRORSTR[] = "The evaluation that you are trying to complete is not available.<br /><br />You will be returned to the Clerkship tab in 10 seconds, or <a href=\"".ENTRADA_URL."/clerkship\"></a> to continue immediately.";
		
				echo display_error($ERRORSTR);
				
				application_log("error", "Unable to load the requested notification_id [".$NOTIFICATION_ID."] provided to the Clerkship evaluate section. Database said: ".$db->ErrorMsg());
			}
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/clerkship\'', 10000)";
			
			$ERROR++;
			$ERRORSTR[] = "The evaluation that you are trying to complete is not available.<br /><br />You will be returned to the Clerkship tab in 10 seconds, or <a href=\"".ENTRADA_URL."/clerkship\"></a> to continue immediately.";
	
			echo display_error($ERRORSTR);
		
			application_log("error", "There was no notification_id provided to the Clerkship evaluate section.");
		}
	break;
	default :
		/**
		 * This is in here, because I am sure at some point I will need to put
		 * in support for evaluation of students by faculty, staff, etc.
		 */
		application_log("error", "Someone other than a student attempted to access a Clerkship evaluation form.");
		
		header("Location: ".ENTRADA_URL."/clerkship");
		exit;
	break;
}
?>