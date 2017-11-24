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
 * This section is loaded when an individual wants to attempt to fill out an evaluation.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EVALUATIONS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if (isset($_POST["request_code"]) && ($code = clean_input($_POST["request_code"], "alphanumeric"))) {
    $query = "SELECT * FROM `evaluation_requests`
                WHERE `request_code` = ".$db->qstr($code)."
                AND `target_proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
                AND (
                    `request_expires` = 0
                    OR `request_expires` > ".$db->qstr(time())."
                )
                AND `request_fulfilled` = 0";
    $evaluation_request = $db->GetRow($query);
    if ($evaluation_request) {
        $RECORD_ID = $evaluation_request["evaluation_id"];
    }
} else {
    $code = false;
}

if ($RECORD_ID) {
    $cohort_ids = groups_get_enrolled_group_ids($ENTRADA_USER->getID(), false, $ENTRADA_USER->getActiveOrganisation(), false);
    $cohort_ids_string = "";
    if (isset($cohort_ids) && is_array($cohort_ids)) {
        foreach ($cohort_ids as $cohort_id) {
            $cohort_ids_string .= ($cohort_ids_string ? ", " : "").$db->qstr($cohort_id);
        }
    }
	
	$query = "SELECT a.`cgroup_id` FROM `course_group_audience` AS a
				JOIN `course_groups` AS b
				ON a.`cgroup_id` = b.`cgroup_id`
				WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
				AND a.`active` = 1
				AND b.`active` = 1";
	$course_groups = $db->GetAll($query);

	$cgroup_ids_string = "";
	if (isset($course_groups) && is_array($course_groups)) {
		foreach ($course_groups as $course_group) {
			if ($cgroup_ids_string) {
				$cgroup_ids_string .= ", ".$db->qstr($course_group["cgroup_id"]);
			} else {
				$cgroup_ids_string = $db->qstr($course_group["cgroup_id"]);
			}
		}
	}
	
	$query			= "	SELECT a.*, c.`eprogress_id`, e.`target_title`, c.`etarget_id`, b.`eevaluator_id`, e.`target_shortname`
						FROM `evaluations` AS a
						LEFT JOIN `evaluation_evaluators` AS b
						ON a.`evaluation_id` = b.`evaluation_id`
						LEFT JOIN `evaluation_progress` AS c
						ON a.`evaluation_id` = c.`evaluation_id`
						AND c.`progress_value` = 'inprogress'
						AND c.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
						LEFT JOIN `evaluation_responses` AS cr
						ON c.`eprogress_id` = cr.`eprogress_id`
						LEFT JOIN `evaluation_targets` AS d
						ON a.`evaluation_id` = d.`evaluation_id`
						LEFT JOIN `evaluation_forms` AS ef
						ON a.`eform_id` = ef.`eform_id`
						LEFT JOIN `evaluations_lu_targets` AS e
						ON ef.`target_id` = e.`target_id`
						WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
                        ".(defined("EVALUATION_LOCKOUT") && EVALUATION_LOCKOUT ? "AND a.`evaluation_finish` > ".$db->qstr(time() - EVALUATION_LOCKOUT) : "")."
						AND 
						(
							(
								b.`evaluator_type` = 'proxy_id'
								AND b.`evaluator_value` = ".$db->qstr($ENTRADA_USER->getID())."
							)
							OR
							(
								b.`evaluator_type` = 'organisation_id'
								AND b.`evaluator_value` = ".$db->qstr($_SESSION["details"]["organisation_id"])."
							)".(isset($cohort_ids_string) && $cohort_ids_string ? " OR (
								b.`evaluator_type` = 'cohort'
								AND b.`evaluator_value` IN (".$cohort_ids_string.")
							)" : "").($cgroup_ids_string ? " OR (
								b.`evaluator_type` = 'cgroup_id'
								AND b.`evaluator_value` IN (".$cgroup_ids_string.")
							)" : "")."
						)
						AND a.`evaluation_active` = '1'
						GROUP BY cr.`eprogress_id`";
	$evaluation_record	= $db->GetRow($query);
	if ($evaluation_record) {
		if ($evaluation_record["allow_target_request"] && !$evaluation_record["require_request_code"] && (!isset($evaluation_request) || !$evaluation_request)) {
            $evaluation_requests = Classes_Evaluation::getEvaluationRequests($RECORD_ID, $ENTRADA_USER->getID());
            if ($evaluation_requests && count($evaluation_requests)) {
                $evaluation_request = $evaluation_requests[0];
            } elseif ($evaluation_record["require_requests"]) {
                $evaluation_record = NULL;
            }
        }
    }
	if ($evaluation_record) {
        $temp_evaluation_record = Classes_Evaluation::getEvaluationDetails($evaluation_record, $ENTRADA_USER->getId());
        if ($temp_evaluation_record) {
            $evaluation_record = $temp_evaluation_record;
        }
		$PROCESSED = $evaluation_record;
			
		if (!isset($evaluation_record["completed_attempts"]) || !$evaluation_record["max_submittable"] || $evaluation_record["completed_attempts"] < $evaluation_record["max_submittable"]) {
			
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/".$MODULE."?section=attempt&id=".$RECORD_ID, "title" => limit_chars($evaluation_record["evaluation_title"], 32));
	
			/**
			 * Providing there is no release date, or the release date is in the past
			 * on the evaluation, allow them to continue.
			 */
			if ((((int) $evaluation_record["release_date"] === 0) || ($evaluation_record["release_date"] <= time()))) {
				/**
				 * Providing they can still still make attempts at this evaluation, allow them to continue.
				 */
				if (((int) $evaluation_record["max_submittable"] === 0) || ($evaluation_record["completed_attempts"] < $evaluation_record["max_submittable"])) {
					$problem_questions = array();

					echo "<div class=\"content-small\">".clean_input($evaluation_record["target_title"], array("trim", "encode"))." Form</div>";
					echo "<h1 class=\"evaluation-title\">".html_encode($evaluation_record["evaluation_title"])."</h1>";

					// Error checking
					switch ($STEP) {
						case 2 :
							$PROCESSED_CLERKSHIP_EVENT = array();
							if ((isset($_POST["event_id"])) && ($event_id = clean_input($_POST["event_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("rotation_core", "rotation_elective", "preceptor")) !== false) {
								$PROCESSED_CLERKSHIP_EVENT["event_id"] = $event_id;
								$query = "SELECT a.`etarget_id` FROM `evaluation_targets` AS a
											JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
											ON a.`target_value` = b.`rotation_id`
											AND a.`target_type` = 'rotation_id'
											WHERE a.`evaluation_id` = ".$db->qstr($PROCESSED["evaluation_id"])."
											AND b.`event_id` = ".$db->qstr($PROCESSED_CLERKSHIP_EVENT["event_id"]);
								$etarget_id = $db->GetOne($query);
								$PROCESSED["target_record_id"] = $event_id;
							}
                            $PROCESSED_PATIENT_ENCOUNTER = array();
                            if ($PROCESSED["target_shortname"] == "resident") {
                                if ((isset($_POST["encounter_complexity"])) && ($encounter_complexity = clean_input($_POST["encounter_complexity"], array("trim", "int"))) && in_array($encounter_complexity, array(1, 2, 3))) {
                                    $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] = $encounter_complexity;
                                    if (isset($_POST["encounter_name"]) && ($encounter_name = clean_input($_POST["encounter_name"], array("trim", "notags")))) {
                                        $PROCESSED_PATIENT_ENCOUNTER["encounter_name"] = $encounter_name;
                                    }
                                } else {
                                    $ERROR++;
                                    $ERRORSTR[] = "Please ensure you select an encounter complexity for the patient encounter being evaluated.";
                                }
                            }
							if ($PROCESSED["target_shortname"] == "preceptor") {
								if (isset($_POST["preceptor_proxy_id"]) && ($preceptor_proxy_id = clean_input($_POST["preceptor_proxy_id"], "trim", "alphanumeric")) && ((int)$preceptor_proxy_id || ($preceptor_proxy_id == "other"))) {
                                    if ($preceptor_proxy_id !== "other") {
                                        $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] = $preceptor_proxy_id;
                                    } else {
                                        $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] = "other";
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
                                    }
								} else {
									$ERROR++;
									$ERRORSTR[] = "Please ensure you have selected a valid preceptor to evaluate from the list.";
								}
							}
							if ((isset($etarget_id) && $etarget_id) || ((isset($_POST["evaluation_target"])) && ($etarget_id = clean_input($_POST["evaluation_target"], array("trim", "int"))))) {
								$query = "	SELECT * FROM `evaluation_targets` AS a 
											JOIN `evaluations_lu_targets` AS b 
											ON a.`target_id` = b.`target_id` 
											WHERE a.`evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])." 
											AND a.`etarget_id` = ".$db->qstr($etarget_id);
								$target_record = $db->GetRow($query);
								//If course_id or proxy_id, set based on target_value
								switch ($target_record["target_type"]) {
									case "cgroup_id" :
									case "cohort" :
										if (isset($_POST["target_record_id"]) && ($tmp_value = clean_input($_POST["target_record_id"], array("trim", "int")))) {
											$target_record_id = $tmp_value;
										}
									break;
									case "proxy_id" :
									case "course_id" :
									case "rotation_id" :
									default :
										$target_record_id = $target_record["target_value"];
									break;
								}
								if ((isset($target_record_id) && $target_record_id) || ((isset($_POST["target_record_id"])) && ($target_record_id = clean_input($_POST["target_record_id"], array("trim", "int"))))) {
									$evaluation_targets = Classes_Evaluation::getTargetsArray($RECORD_ID, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true, false, (isset($evaluation_request) && $evaluation_request ? $evaluation_request["erequest_id"] : false));
									foreach ($evaluation_targets as $evaluation_target) {
										switch ($evaluation_target["target_type"]) {
											case "cgroup_id" :
											case "cohort" :
											case "proxy_id" :
												if ($evaluation_target["proxy_id"] == $target_record_id) {
													$target_record = $evaluation_target;
												}
											break;
											case "rotation_core" :
											case "rotation_elective" :
											case "preceptor" :
												if ($evaluation_target["event_id"] == $target_record_id) {
													$target_record = $evaluation_target;
												}
											break;
											case "self" :
												$target_record = $evaluation_target;
											break;
											case "course" :
											default :
												if ($evaluation_target["course_id"] == $target_record_id) {
													$target_record = $evaluation_target;
												}
											break;
										}
										if (isset($target_record)) {
											break;
										}
									}
									if ($target_record) {
										if ($target_record["target_type"] == "proxy_id") {
											$query = "	SELECT `etarget_id` FROM `evaluations_progress`
														WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"])."
														AND `progress_value` = 'complete'
														AND `target_record_id` = ".$db->qstr($target_record_id)."
														AND `etarget_id` = ".$db->qstr($etarget_id);
											if ($db->GetOne($query)) {
												$ERROR++;
												$ERRORSTR[] = "You have already evaluated this ".$target_record["target_shortname"].". Please choose a new target to evaluate.";
											} else {
												$PROCESSED["etarget_id"] = $etarget_id;
												$PROCESSED["target_record_id"] = $target_record_id;
											}
										} else {
											$PROCESSED["etarget_id"] = $etarget_id;
											$PROCESSED["target_record_id"] = $target_record_id;
										}
									} else {
										$ERROR++;
										$ERRORSTR[] = "There was an issue with the target you have selected to evaluate. An administrator has been notified, please try again later.";
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "Please ensure you have selected a valid target to evaluate from the list.";
							}
							
							/**
							 * Check to see if they currently have any evaluation attempts underway, if they do then
							 * restart their session, otherwise start them a new session.
							 */
							$query				= "	SELECT *
													FROM `evaluation_progress` AS a
													JOIN `evaluations` AS b
													ON a.`evaluation_id` = b.`evaluation_id`
													WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
													AND a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
													AND a.`progress_value` = 'inprogress'
													ORDER BY a.`updated_date` ASC";
							$progress_record	= $db->GetRow($query);
							if ($progress_record) {
								$eprogress_id		= $progress_record["eprogress_id"];
								$PROCESSED_CLERKSHIP_EVENT["eprogress_id"] = $eprogress_id;
								$PROCESSED_PATIENT_ENCOUNTER["eprogress_id"] = $eprogress_id;

								if (((isset($_POST["responses"])) && (is_array($_POST["responses"])) && (count($_POST["responses"]) > 0)) || (isset($_POST["comments"]) && (count($_POST["comments"]) > 0))) {
									$questions_found = false;
									/**
									 * Get a list of all of the multiple choice questions in this evaluation so we
									 * can run through a clean set of questions.
									 */
									$query		= "	SELECT a.*, b.*
													FROM `evaluation_form_questions` AS a
													JOIN `evaluations_lu_questions` AS b
													ON a.`equestion_id` = b.`equestion_id`
													WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
													AND `questiontype_id` NOT IN (2, 4)
													ORDER BY a.`question_order` ASC";
									$questions	= $db->GetAll($query);
									if ($questions) {
										$questions_found = true;
										if (isset($_POST["responses"]) && (count($_POST["responses"])) != (count($questions))) {
											$ERROR++;
											$ERRORSTR[] = "In order to submit your evaluation, you must first answer all of the questions.";
										}

										foreach ($questions as $question) {
											/**
											 * Checking to see if the equestion_id was submitted with the
											 * response $_POST, and if they've actually answered the question.
											 */
											if ((isset($_POST["responses"][$question["equestion_id"]])) && ($eqresponse_id = clean_input($_POST["responses"][$question["equestion_id"]], "int"))) {
												if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
													$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
												} else {
													$comments = NULL;
												}
												if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], $eqresponse_id, $comments)) {
													$ERROR++;
													$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

													$problem_questions[] = $question["equestion_id"];
												}
											} else {
												$ERROR++;
												$problem_questions[] = $question["equestion_id"];
											}
										}
										if ($ERROR && empty($ERRORSTR)) {
											$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";
										}
									}
									$query		= "	SELECT a.*, b.*
													FROM `evaluation_form_questions` AS a
													JOIN `evaluations_lu_questions` AS b
													ON a.`equestion_id` = b.`equestion_id`
													WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
													AND `questiontype_id` = (4)
													ORDER BY a.`question_order` ASC";
									$questions	= $db->GetAll($query);
									if ($questions) {
										foreach ($questions as $question) {
											if ((isset($_POST["comments"][$question["equestion_id"]])) && clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"))) {
												$comments = clean_input($_POST["comments"][$question["equestion_id"]], array("trim", "notags"));
											} else {
												$comments = NULL;
											}
											if (!Classes_Evaluation::evaluation_save_response($eprogress_id, $progress_record["eform_id"], $question["equestion_id"], 0, $comments)) {
												$ERROR++;
												$ERRORSTR[] = "A problem was found storing a question response, please verify your responses and try again.";

												$problem_questions[] = $question["equestion_id"];
											}
										}
									} elseif (!$questions_found) {
										$ERROR++;
										$ERRORSTR[] = "An error occurred while attempting to save your evaluation responses. The system administrator has been notified of this error; please try again later.";

										application_log("error", "Unable to find any evaluation questions for evaluation_id [".$progress_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
									}

									/**
									 * We can now safely say that all questions have valid responses
									 * and that we have stored those responses evaluation_responses table.
									 */
									if (!$ERROR) {
                                        if (isset($PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) && $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] === "other") {
                                            if($PROCESSED_TEACHER["email"]) {
                                                $query	= "SELECT `id` FROM `".AUTH_DATABASE."`.`user_data` WHERE `email` = ".$db->qstr($PROCESSED_TEACHER["email"]);
                                                $result	= $db->GetRow($query);
                                                if($result) {
                                                    $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] = $result["id"];
                                                }
                                            }

                                            if(!(int) $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) {
                                                if(($db->AutoExecute(CLERKSHIP_DATABASE.".other_teachers", $PROCESSED_TEACHER, "INSERT")) && ($oteacher_id = $db->Insert_Id())) {
                                                    $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]	= "OT-".$oteacher_id;	
                                                } else {
                                                    $ERROR++;
                                                    $ERRORSTR[] = "We are unable to add your other teacher information at this time. This MEdTech Unit has been notified of this error, please try again later.";

                                                    application_log("error", "Unable to add new teacher to other_teachers table: ".$db->ErrorMsg());
                                                }
                                            }
                                        }
										$evaluation_progress_array	= array (
																	"progress_value" => "complete",
																	"evaluation_id" => $evaluation_record["evaluation_id"],
																	"etarget_id" => $PROCESSED["etarget_id"],
																	"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																	"updated_date" => time(),
																	"updated_by" => $ENTRADA_USER->getID()
																);

										if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "UPDATE", "eprogress_id = ".$db->qstr($eprogress_id))) {
                                            $query = "UPDATE `evaluations` SET `evaluation_completions` = (`evaluation_completions` + 1) WHERE `evaluation_id` = ".$db->qstr($evaluation_record["evaluation_id"]);
                                            if (!$db->Execute($query)) {
                                                application_log("error", "Unable to increment evaluation [".$progress_record["evaluation_id"]."] completions by one upon a completed attempt being submitted by a user [".$ENTRADA_USER->getID()."]. Database said: ".$db->ErrorMsg());
                                            }
                                            if ((isset($evaluation_request) && $evaluation_request) && !$db->AutoExecute("evaluation_requests", array("request_fulfilled" => 1), "UPDATE", "`erequest_id` = ".$db->qstr($evaluation_request["erequest_id"]))) {
                                                application_log("error", "Unable to mark evaluation request as completed [".$progress_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
                                            }
											if ($evaluation_record["threshold_notifications_type"] != "disabled") {
												$is_below_threshold = Classes_Evaluation::responsesBelowThreshold($evaluation_record["evaluation_id"], $eprogress_id);
												if ($is_below_threshold) {
													if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
														require_once("Classes/notifications/NotificationUser.class.php");
														require_once("Classes/notifications/Notification.class.php");
														$threshold_notification_recipients = Classes_Evaluation::getThresholdNotificationRecipients($evaluation_record["evaluation_id"], $eprogress_id, $PROCESSED["eevaluator_id"]);
														if (isset($threshold_notification_recipients) && $threshold_notification_recipients) {
															foreach ($threshold_notification_recipients as $threshold_notification_recipient) {
																$notification_user = NotificationUser::get($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																if (!$notification_user) {
																	$notification_user = NotificationUser::add($threshold_notification_recipient["proxy_id"], "evaluation_threshold", $evaluation_record["evaluation_id"], $ENTRADA_USER->getID());
																}
																Notification::add($notification_user->getID(), $ENTRADA_USER->getID(), $eprogress_id);
															}
														}
													}
												}
											}
											if (array_search($PROCESSED["target_shortname"], array("preceptor", "rotation_core", "rotation_elective")) !== false) {
												if (!$db->AutoExecute("evaluation_progress_clerkship_events", $PROCESSED_CLERKSHIP_EVENT, "INSERT")) {
                                                    $db->Execute("UPDATE `evaluation_progress` SET `progress_value` = 'inprogress' WHERE `eprogress_id` = ".$db->qstr($PROCESSED["eprogress_id"]));
													application_log("error", "Unable to record the final clerkship event details for eprogress_id [".$eprogress_id."] in the evaluation_progress_clerkship_events table. Database said: ".$db->ErrorMsg());

													$ERROR++;
													$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
												} else {
													/**
													 * Add a completed evaluation statistic.
													 */
													add_statistic("evaluations", "evaluation_complete", "evaluation_id", $RECORD_ID);

													application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$RECORD_ID."].");

													$url = ENTRADA_URL."/evaluations";

													$SUCCESS++;
													$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.<br /><br />You will now be redirected back to the evaluations index; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

													$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";
												}
											} elseif ($PROCESSED["target_shortname"] == "resident") {
                                                if (!$db->AutoExecute("evaluation_progress_patient_encounters", $PROCESSED_PATIENT_ENCOUNTER, "INSERT")) {
                                                    $db->Execute("UPDATE `evaluation_progress` SET `progress_value` = 'inprogress' WHERE `eprogress_id` = ".$db->qstr($PROCESSED["eprogress_id"]));
                                                    application_log("error", "Unable to record the final patient encounter details for eprogress_id [".$eprogress_id."] in the evaluation_progress_patient_encounters table. Database said: ".$db->ErrorMsg());

                                                    $ERROR++;
                                                    $ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
                                                } else {
                                                    /**
                                                     * Add a completed evaluation statistic.
                                                     */
                                                    add_statistic("evaluations", "evaluation_complete", "evaluation_id", $RECORD_ID);

                                                    application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$RECORD_ID."].");

                                                    $url = ENTRADA_URL."/evaluations";

                                                    $SUCCESS++;
                                                    $SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.<br /><br />You will now be redirected back to the evaluations index; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

                                                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";
                                                }
                                            } else {
												/**
												 * Add a completed evaluation statistic.
												 */
												add_statistic("evaluations", "evaluation_complete", "evaluation_id", $RECORD_ID);

												application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] has completed evaluation_id [".$RECORD_ID."].");

												$url = ENTRADA_URL."/evaluations";

												$SUCCESS++;
												$SUCCESSSTR[] = "Thank-you for completing the <strong>".html_encode($evaluation_record["evaluation_title"])."</strong> evaluation.<br /><br />You will now be redirected back to the evaluations index; this will happen <strong>automatically</strong> in 15 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

												$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 15000)";
											}
										} else {
											application_log("error", "Unable to record the final evaluation results for evaluation_id [".$RECORD_ID."] in the evaluation_progress table. Database said: ".$db->ErrorMsg());

											$ERROR++;
											$ERRORSTR[] = "We were unable to record the final results for this evaluation at this time. Please be assured that your responses are saved, but you will need to come back to this evaluation to re-submit it. This problem has been reported to a system administrator; please try again later.";
										}
									}
								} else {
									$ERROR++;
									$ERRORSTR[] = "In order to submit your evaluation for marking, you must first answer some of the questions.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "We were unable to locate an evaluation that is currently in progress.<br /><br />If you pressed your web-browsers back button, please refrain from doing this when you are posting evaluation information.";
								
								application_log("error", "Unable to locate an evaluation currently in progress when attempting to save an evaluation.");
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

					if (((int) $evaluation_record["max_submittable"] === 0) || ($evaluation_record["completed_attempts"] < $evaluation_record["max_submittable"])) {
						// Display Content
						switch ($STEP) {
							case 2 :
								if ($SUCCESS) {
									echo display_success();
								}
							break;
							case 1 :
							default :
								if ($evaluation_record["evaluation_finish"] < time() && $evaluation_record["min_submittable"] > $evaluation_record["completed_attempts"]) {
									add_notice("This evaluation has not been completed and was marked as to be completed by ".date(DEFAULT_DATE_FORMAT, $evaluation_record["evaluation_finish"]).". Please complete this evaluation now to continue using ".APPLICATION_NAME.".");
								}
                                if (isset($evaluation_record["identify_comments"]) && $evaluation_record["identify_comments"]) {
                                   add_notice("This evaluation is set to indicate your name on comments. Please be aware that the comment portion of the results will not be totally anonymous to reviewers, while the individual multiple choice and Grouped Item answers you select will still be unidentified.");
                                }
								
								if (isset($evaluation_record["evaluation_description"]) && $evaluation_record["evaluation_description"]) {
									echo "<div class=\"display-generic\">".$evaluation_record["evaluation_description"]."</div>";
								}
								/**
								 * Check to see if they currently have any evaluation attempts underway, if they do then
								 * restart their session, otherwise start them a new session.
								 */
								$query				= "	SELECT *
														FROM `evaluation_progress`
														WHERE `evaluation_id` = ".$db->qstr($RECORD_ID)."
														AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
														AND `progress_value` = 'inprogress'
														ORDER BY `updated_date` ASC";
								$progress_record	= $db->GetRow($query);
								if ($progress_record) {
									$eprogress_id		= $progress_record["eprogress_id"];
									$evaluation_start_time	= $progress_record["updated_date"];
								} else {
									$evaluation_start_time	= time();
									$evaluation_progress_array	= array (
																"evaluation_id" => $RECORD_ID,
																"proxy_id" => $ENTRADA_USER->getID(),
																"progress_value" => "inprogress",
																"etarget_id" => (isset($PROCESSED["etarget_id"]) && $PROCESSED["etarget_id"] ? $PROCESSED["etarget_id"] : 0),
																"target_record_id" => (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : 0),
																"updated_date" => $evaluation_start_time,
																"updated_by" => $ENTRADA_USER->getID()
															);
									if ($db->AutoExecute("evaluation_progress", $evaluation_progress_array, "INSERT"))  {
										$eprogress_id = $db->Insert_Id();
									} else {
										$ERROR++;
										$ERRORSTR[] = "Unable to create a progress entry for this evaluation, it is not advisable to continue at this time. The system administrator was notified of this error; please try again later.";

										application_log("error", "Unable to create an evaluation_progress entery when attempting complete an evaluation. Database said: ".$db->ErrorMsg());
									}
								}

								if ($eprogress_id) {
									if ((isset($_GET["proxy_id"])) && ($proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) && array_search($PROCESSED["target_shortname"], array("peer", "student", "teacher", "resident")) !== false) {
										$PROCESSED["target_record_id"] = $proxy_id;
									}
									?>
									<form name="evaluation-form" id="evaluation-form" action="<?php echo ENTRADA_URL."/".$MODULE; ?>?section=attempt&id=<?php echo $RECORD_ID; ?>" method="post">
									<?php
									add_statistic("evaluation", "evaluation_view", "evaluation_id", $RECORD_ID);
									if (!isset($evaluation_targets) || !count($evaluation_targets)) {
										$evaluation_targets = Classes_Evaluation::getTargetsArray($RECORD_ID, $PROCESSED["eevaluator_id"], $ENTRADA_USER->getID(), false, true, false, (isset($evaluation_request) && $evaluation_request ? $evaluation_request["erequest_id"] : false));
									}
                                    if (in_array($PROCESSED["target_shortname"], array("preceptor", "teacher", "peer", "resident"))) {
                                        $HEAD[] = "
                                                    <script type=\"text/javascript\">
                                                        function fetchTargetDetails(id, id_name) {
                                                            ".($PROCESSED["target_shortname"] == "preceptor" ? "
                                                            var preceptor_evaluation = jQuery('#event_id').val();
                                                            if(etarget_id == 'other') {
                                                                $('other_teacher_layer').style.display = 'block';
                                                                $('other_teacher_fname').focus();
                                                            } else {
                                                                $('other_teacher_layer').style.display = 'none';
                                                                " : "var preceptor_evaluation = false;
                                                            ")."
                                                                var etarget_id = (id_name == 'evaluation_target' ? id : false);
                                                                var proxy_id = (id_name == 'proxy_id' ? id : false);
                                                                if (((etarget_id && parseInt(etarget_id) == etarget_id) || (proxy_id && parseInt(proxy_id) == proxy_id))) {
                                                                    jQuery('#target-details-holder').load('".ENTRADA_URL."/evaluations?section=api-target-info&id=".$RECORD_ID."', {'etarget_id' : etarget_id, 'eevaluator_id' : '".$PROCESSED["eevaluator_id"]."', 'erequest_id' : ".(isset($evaluation_request) && $evaluation_request ? "'".$evaluation_request["erequest_id"]."'" : "false").", 'preceptor_evaluation' : preceptor_evaluation, 'proxy_id' : proxy_id} );
                                                                }
                                                            ".($PROCESSED["target_shortname"] == "preceptor" ? "}" : "")."
                                                            return;
                                                        }
                                                    </script>";
                                    }
									if ($evaluation_targets) {
										if (count($evaluation_targets) == 1) {
											echo "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
											if ($PROCESSED["target_shortname"] == "teacher") {
												echo "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
												$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
											} elseif ($PROCESSED["target_shortname"] == "resident") {
												echo "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
												$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
											} elseif ($PROCESSED["target_shortname"] == "course") {
												echo "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["course_id"]."\" />";
												$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_targets[0]["target_value"]));
											} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective" || $PROCESSED["target_shortname"] == "preceptor" ) {
												echo "<input type=\"hidden\" id=\"event_id\" name=\"event_id\" value=\"".$evaluation_targets[0]["event_id"]."\" />";
												$target_name = $evaluation_targets[0]["event_title"];
											} elseif ($PROCESSED["target_shortname"] == "self") {
												echo "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$ENTRADA_USER->getID()."\" />";
												$target_name = "Yourself";
											} else {
												if ($evaluation_targets[0]["target_type"] == "proxy_id") {
													echo "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
													$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
												} elseif ($evaluation_targets[0]["target_type"] == "cohort" || $evaluation_targets[0]["target_type"] == "cgroup_id") {
													echo "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
													echo "<input type=\"hidden\" id=\"target_record_id\" name=\"target_record_id\" value=\"".$evaluation_targets[0]["proxy_id"]."\" />";
													$target_name = $evaluation_targets[0]["firstname"]." ".$evaluation_targets[0]["lastname"];
												}
											}
											if ($target_name) {
												echo "<div class=\"content-small\">Evaluating <strong>".$target_name."</strong>.</div>";
                                                if ($PROCESSED["target_shortname"] == "preceptor") {
                                                    echo "<div id=\"preceptor_select\">\n";
                                                    echo Classes_Evaluation::getPreceptorSelect($RECORD_ID, $evaluation_targets[0]["event_id"], $ENTRADA_USER->getID(), (isset($PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) && $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] ? $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] : 0));
                                                    echo "</div>\n";
                                                } elseif ($PROCESSED["target_shortname"] == "resident") {
                                                    echo "<div class=\"content-small row-fluid\">\n";
                                                    echo "  <label for=\"encounter_complexity\" class=\"span7\">\n";
                                                    echo "      Please choose the complexity of this encounter:\n";
                                                    echo "  </label>\n";
                                                    echo "  <span class=\"span5\">\n";
                                                    echo "      <select id=\"encounter_complexity\" name=\"encounter_complexity\">\n";
                                                    echo "          <option value=\"0\"".(!isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) || !$PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] ? " selected=\"selected\"" : "").">-- Select the encounter complexity --</option>\n";
                                                    echo "          <option value=\"1\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 1 ? " selected=\"selected\"" : "").">Simple</option>\n";
                                                    echo "          <option value=\"2\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 2 ? " selected=\"selected\"" : "").">Complex, but frequently encountered</option>\n";
                                                    echo "          <option value=\"3\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 3 ? " selected=\"selected\"" : "").">Complex</option>\n";
                                                    echo "      </select>\n";
                                                    echo "  </span>\n";
                                                    echo "</div>";
                                                    echo "<div class=\"content-small row-fluid\">\n";
                                                    echo "  <label for=\"encounter\" class=\"span7\">\n";
                                                    echo "      Additionally, you may indicate the name of this encounter:\n";
                                                    echo "  </label>\n";
                                                    echo "  <span class=\"span5\">\n";
                                                    echo "      <input type=\"text\" id=\"encounter_name\" name=\"encounter_name\" style=\"margin-bottom: 0px;\" value=\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_name"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_name"] ? html_encode($PROCESSED_PATIENT_ENCOUNTER["encounter_name"]) : "")."\"/>\n";
                                                    echo "  </span>\n";
                                                    echo "</div>";
                                                }
											}
										} elseif ($PROCESSED["target_shortname"] == "teacher") {
											echo "<div class=\"content-small\">Please choose a teacher to evaluate: \n";
											echo "<select id=\"evaluation_target\" name=\"evaluation_target\" onchange=\"fetchTargetDetails(jQuery(this).val(), 'evaluation_target')\">";
											echo "<option value=\"0\">-- Select a teacher --</option>\n";
											foreach ($evaluation_targets as $evaluation_target) {
												if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
													echo "<option value=\"".$evaluation_target["etarget_id"]."\"".(($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"]) || ((!isset($PROCESSED["etarget_id"]) || !$PROCESSED["etarget_id"]) && isset($evaluation_target["requested"]) && $evaluation_target["requested"]) ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
												}
											}
											echo "</select>";
											echo "</div>";
										} elseif ($PROCESSED["target_shortname"] == "rotation_core" || $PROCESSED["target_shortname"] == "rotation_elective" || $PROCESSED["target_shortname"] == "preceptor") {
											echo "<div class=\"content-small\">Please choose a clerkship service to evaluate: \n";
											echo "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
											echo "<select id=\"event_id\" name=\"event_id\"".($PROCESSED["target_shortname"] == "preceptor" ? " onchange=\"loadPreceptors(this.options[this.selectedIndex].value)\"" : "").">";
											echo "<option value=\"0\">-- Select an event --</option>\n";
											foreach ($evaluation_targets as $evaluation_target) {
												echo "<option value=\"".$evaluation_target["event_id"]."\"".((isset($PROCESSED_CLERKSHIP_EVENT["event_id"]) && $PROCESSED_CLERKSHIP_EVENT["event_id"] == $evaluation_target["event_id"]) || ((!isset($PROCESSED_CLERKSHIP_EVENT["event_id"]) || !$PROCESSED_CLERKSHIP_EVENT["event_id"]) && isset($evaluation_target["requested"]) && $evaluation_target["requested"]) ? " selected=\"selected\"" : "").">".(strpos($evaluation_target["event_title"], $evaluation_target["rotation_title"]) === false ? $evaluation_target["rotation_title"]." - " : "").$evaluation_target["event_title"]."</option>\n";
											}
											echo "</select>";
											if ($PROCESSED["target_shortname"] == "preceptor") {
												echo "<div id=\"preceptor_select\">\n";
												if (isset($PROCESSED_CLERKSHIP_EVENT["event_id"]) && $PROCESSED_CLERKSHIP_EVENT["event_id"]) {
                                                    echo "<br /><div class=\"content-small\">Please choose a clerkship preceptor to evaluate: \n";
													echo Classes_Evaluation::getPreceptorSelect($RECORD_ID, $PROCESSED_CLERKSHIP_EVENT["event_id"], $ENTRADA_USER->getID(), (isset($PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) && $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] ? $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] : 0));
                                                    echo "</div>\n";
												} else {
													echo display_notice("Please select a <strong>Clerkship Service</strong> to evaluate a <strong>Preceptor</strong> for.");
												}
												echo "</div>\n";
											} 
											echo "</div>";
										} elseif ($PROCESSED["target_shortname"] == "course") {
											echo "<div class=\"content-small\">Please choose a course to evaluate: \n";
											echo "<select id=\"evaluation_target\" name=\"evaluation_target\">";
											echo "<option value=\"0\">-- Select a course --</option>\n";
											foreach ($evaluation_targets as $evaluation_target) {
												if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
													$target_name = $db->GetOne("SELECT `course_name` FROM `courses` WHERE `course_id` = ".$db->qstr($evaluation_target["target_value"]));
													if ($target_name) {
														echo "<option value=\"".$evaluation_target["etarget_id"]."\"".(($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"]) || ((!isset($PROCESSED["etarget_id"]) || !$PROCESSED["etarget_id"]) && isset($evaluation_target["requested"]) && $evaluation_target["requested"]) ? " selected=\"selected\"" : "").">".$target_name."</option>\n";
													}
												}
											}
											echo "</select>";
											echo "</div>";
										} elseif ($PROCESSED["target_shortname"] == "peer" || $PROCESSED["target_shortname"] == "student") {
											echo "<div class=\"content-small\">Please choose a learner to assess: \n";
											echo "<input type=\"hidden\" id=\"evaluation_target\" name=\"evaluation_target\" value=\"".$evaluation_targets[0]["etarget_id"]."\" />";
											echo "<select id=\"target_record_id\" name=\"target_record_id\" onchange=\"fetchTargetDetails(this.value, 'proxy_id')\">";
											echo "<option value=\"0\">-- Select a learner --</option>\n";
											foreach ($evaluation_targets as $evaluation_target) {
												if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
													echo "<option value=\"".$evaluation_target["proxy_id"]."\"".(($PROCESSED["target_record_id"] == $evaluation_target["proxy_id"]) || ((!isset($PROCESSED["target_record_id"]) || !$PROCESSED["target_record_id"]) && isset($evaluation_target["requested"]) && $evaluation_target["requested"]) ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
												}
											}
											echo "</select>";
											echo "</div>";
										} elseif ($PROCESSED["target_shortname"] == "resident") {
											echo "<div class=\"content-small row-fluid\">\n";
                                            echo "  <label for=\"evaluation_target\" class=\"span7\">\n";
                                            echo "      Please choose a resident to evaluate:\n";
                                            echo "  </label>\n";
                                            echo "  <span class=\"span5\">\n";
                                            echo "      <select id=\"evaluation_target\" name=\"evaluation_target\" onchange=\"fetchTargetDetails(this.value, 'evaluation_target')\">";
											echo "          <option value=\"0\">-- Select a resident --</option>\n";
											foreach ($evaluation_targets as $evaluation_target) {
												if (!isset($evaluation_target["eprogress_id"]) || !$evaluation_target["eprogress_id"]) {
													echo "          <option value=\"".$evaluation_target["etarget_id"]."\"".(($PROCESSED["etarget_id"] == $evaluation_target["etarget_id"] || $PROCESSED["target_record_id"] == $evaluation_target["proxy_id"]) || (((!isset($PROCESSED["etarget_id"]) || !$PROCESSED["etarget_id"]) || (!isset($PROCESSED["target_record_id"]) || !$PROCESSED["target_record_id"])) && isset($evaluation_target["requested"]) && $evaluation_target["requested"]) ? " selected=\"selected\"" : "").">".$evaluation_target["firstname"]." ".$evaluation_target["lastname"]."</option>\n";
												}
											}
											echo "      </select>";
                                            echo "  </span>\n";
                                            echo "</div>";
                                            echo "<div class=\"content-small row-fluid\">\n";
                                            echo "  <label for=\"encounter_complexity\" class=\"span7\">\n";
                                            echo "      Please choose the complexity of this encounter:\n";
                                            echo "  </label>\n";
                                            echo "  <span class=\"span5\">\n";
                                            echo "      <select id=\"encounter_complexity\" name=\"encounter_complexity\">\n";
                                            echo "          <option value=\"0\"".(!isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) || !$PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] ? " selected=\"selected\"" : "").">-- Select the encounter complexity --</option>\n";
                                            echo "          <option value=\"1\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 1 ? " selected=\"selected\"" : "").">Simple</option>\n";
                                            echo "          <option value=\"2\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 2 ? " selected=\"selected\"" : "").">Complex, but frequently encountered</option>\n";
                                            echo "          <option value=\"3\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_complexity"] == 3 ? " selected=\"selected\"" : "").">Complex</option>\n";
                                            echo "      </select>\n";
                                            echo "  </span>\n";
                                            echo "</div>";
                                            echo "<div class=\"content-small row-fluid\">\n";
                                            echo "  <label for=\"encounter\" class=\"span7\">\n";
                                            echo "      Additionally, you may indicate the name of this encounter:\n";
                                            echo "  </label>\n";
                                            echo "  <span class=\"span5\">\n";
                                            echo "      <input type=\"text\" id=\"encounter_name\" name=\"encounter_name\" style=\"margin-bottom: 0px;\" value=\"".(isset($PROCESSED_PATIENT_ENCOUNTER["encounter_name"]) && $PROCESSED_PATIENT_ENCOUNTER["encounter_name"] ? html_encode($PROCESSED_PATIENT_ENCOUNTER["encounter_name"]) : "")."\"/>\n";
                                            echo "  </span>\n";
                                            echo "</div>";
										}
									}
                                    if (isset($PROCESSED["etarget_id"]) && $PROCESSED["etarget_id"]) {
                                        if ($PROCESSED["target_shortname"] == "preceptor") {
                                            if (isset($PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) && $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) {
                                                $ONLOAD[] = "fetchTargetDetails(".((int)$PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]).", 'proxy_id');";
                                            }
                                        } elseif (in_array($PROCESSED["target_shortname"], array("peer", "student", "resident", "teacher"))) {
                                            $ONLOAD[] = "fetchTargetDetails(".((int)$PROCESSED["target_record_id"]).", 'proxy_id');";
                                        }
                                    }
                                    if ($PROCESSED["target_shortname"] == "preceptor") {
                                        ?>
                                            <div id="other_teacher_layer"<?php echo (!isset($PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"]) || $PROCESSED_CLERKSHIP_EVENT["preceptor_proxy_id"] !== "other" ? " style=\"display: none\"" : ""); ?>>
                                                <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Evaluating <?php echo html_encode($target_name); ?>">
                                                <colgroup>
                                                    <col style="width: 30%" />
                                                    <col style="width: 70%" />
                                                </colgroup>
                                                <tbody>
                                                    <tr>
                                                        <td><label for="other_teacher_fname" class="form-required">Firstname:</label></td>
                                                        <td><input type="text" id="other_teacher_fname" name="other_teacher_fname" style="width: 200px" value="<?php echo html_encode(trim($_POST["other_teacher_fname"])) ?>" maxlength="45" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td><label for="other_teacher_lname" class="form-required">Lastname:</label></td>
                                                        <td><input type="text" id="other_teacher_lname" name="other_teacher_lname" style="width: 200px" value="<?php echo html_encode(trim($_POST["other_teacher_lname"])) ?>" maxlength="45" /></td>
                                                    </tr>
                                                    <tr>
                                                        <td><label for="other_teacher_email" class="form-nrequired">E-Mail Address:</label></td>
                                                        <td><input type="text" id="other_teacher_email" name="other_teacher_email" style="width: 200px" value="<?php echo html_encode(trim($_POST["other_teacher_email"])) ?>" maxlength="125" /></td>
                                                    </tr>
                                                </tbody>
                                                </table>
                                            </div>
                                            <br />
                                        <?php
                                    }
									?>
                                    <div id="target-details-holder" class="space-above space-below"></div>
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
                                    if ($code) {
                                        ?>
                                        <input type="hidden" name="request_code" value="<?php echo $code; ?>" />
                                        <?php
                                    }
                                    ?>
									<input type="hidden" name="step" value="2" />
									<?php
									$query				= "	SELECT a.*, b.*, c.`questiontype_shortname`
															FROM `evaluation_form_questions` AS a
															JOIN `evaluations_lu_questions` AS b
															ON a.`equestion_id` = b.`equestion_id`
															JOIN `evaluations_lu_questiontypes` AS c
															ON b.`questiontype_id` = c.`questiontype_id`
															WHERE a.`eform_id` = ".$db->qstr($evaluation_record["eform_id"])."
															ORDER BY a.`question_order` ASC";
									$questions			= $db->GetAll($query);
									$total_questions	= 0;
									if ($questions) {
										$total_questions = count($questions);
                                        echo Classes_Evaluation::getQuestionAnswerControls($questions, $PROCESSED["eform_id"], false, true, $eprogress_id);
									} else {
										$ERROR++;
										$ERRORSTR[] = "There are no questions currently available for this evaluation. This problem has been reported to a system administrator; please try again later.";

										application_log("error", "Unable to locate any questions for evaluation [".$evaluation_record["evaluation_id"]."]. Database said: ".$db->ErrorMsg());
									}
									?>
									<div class="space-above medium">
										<input type="button" class="btn" style="float: left; margin-right: 10px" onclick="window.location = '<?php echo ENTRADA_URL; ?>/evaluations'" value="Exit Evaluation" />
										<input type="submit" class="btn btn-primary" style="float: right" value="Submit Evaluation" />
									</div>
									<div class="clear"></div>
									</form>
									<script type="text/javascript">
									function storeResponse(qid, rid, comments) {
										new Ajax.Request('<?php echo ENTRADA_URL."/".$MODULE; ?>', {
											method: 'post',
											parameters: { 'section' : 'save-response', 'id' : '<?php echo $RECORD_ID; ?>', 'qid' : qid, 'rid' : rid, 'comments' : comments},
											onSuccess: function(transport) {
												if (transport.responseText.match(200)) {
                                                    if (jQuery('tr#question_' + qid).length > 0) {
                                                        jQuery('tr#question_' + qid).removeClass('question-notice');
                                                    } else if (jQuery('li#question_' + qid).length > 0) {
                                                        jQuery('li#question_' + qid).removeClass('question-notice');
                                                    } else {
                                                        jQuery('#question_' + qid).removeClass('question-notice');
                                                    }

                                                    if ($$('#evaluation-questions-list li.notice').length <= 0) {
														$('display-unsaved-warning').fade({ duration: 0.5 });
													}
												} else {
                                                    if (jQuery('tr#question_' + qid).length > 0) {
                                                        jQuery('tr#question_' + qid).addClass('question-notice');
                                                    } else if (jQuery('li#question_' + qid).length > 0) {
                                                        jQuery('li#question_' + qid).addClass('question-notice');
                                                    } else {
                                                        jQuery('#question_' + qid).addClass('question-notice');
                                                    }

                                                    if ($('display-unsaved-warning').style.display == 'none') {
														$('display-unsaved-warning').appear({ duration: 0.5 });
													}
												}
											},
											onError: function() {
                                                if (jQuery('tr#question_' + qid).length > 0) {
                                                    jQuery('tr#question_' + qid).addClass('question-notice');
                                                } else if (jQuery('li#question_' + qid).length > 0) {
                                                    jQuery('li#question_' + qid).addClass('question-notice');
                                                } else {
                                                    jQuery('#question_' + qid).addClass('question-notice');
                                                }

                                                if ($('display-unsaved-warning').style.display == 'none') {
                                                    $('display-unsaved-warning').appear({ duration: 0.5 });
                                                }
                                            }
										});
									}
									function loadPreceptors(event_id) {
										var preceptor_proxy_id = 0;
										if ($('preceptor_proxy_id') && $('preceptor_proxy_id').selectedIndex > 0) {
											preceptor_proxy_id = $('preceptor_proxy_id').options[$('preceptor_proxy_id').selectedIndex].value;
										}
										new Ajax.Updater('preceptor_select', '<?php echo ENTRADA_URL."/".$MODULE; ?>?section=api-preceptor-select', {
											method: 'post',
											parameters: { 'id' : '<?php echo $RECORD_ID; ?>', 'event_id' : event_id, 'preceptor_proxy_id' : preceptor_proxy_id},
											onSuccess: function(transport) {
												$('preceptor_select').removeClassName('notice');
                                                $('other_teacher_layer').hide();
											},
											onError: function() {
													$('preceptor_select').addClassName('notice');

													$('preceptor_select').update('<ul><li>No <strong>Preceptors</strong> available for evaluation found in the system.</li></ul>');
											},
                                            onComplete: function() {
                                                if ($('preceptor_proxy_id') && $('preceptor_proxy_id').selectedIndex == 0) {
                                                    jQuery('#target-details-holder').html('');
                                                }
                                            }
										});
									}
									</script>
									<?php
									$sidebar_html = evaluation_generate_description((isset($evaluation_record["base_min_submittable"]) && $evaluation_record["base_min_submittable"] ? $evaluation_record["base_min_submittable"] : $evaluation_record["min_submittable"]), $total_questions, (isset($evaluation_record["base_max_submittable"]) && ($evaluation_record["base_max_submittable"] || ((int)$evaluation_record["base_max_submittable"] === 0)) ? $evaluation_record["base_max_submittable"] : $evaluation_record["max_submittable"]), $evaluation_record["evaluation_finish"]);
									new_sidebar_item("Evaluation Statement", $sidebar_html, "page-anchors", "open", "1.9");
								} else {
									$ERROR++;
									$ERRORSTR[] = "Unable to locate your progress information for this evaluation at this time. The system administrator has been notified of this error; please try again later.";

									echo display_error();

									application_log("error", "Failed to locate a eprogress_id [".$eprogress_id."] (either existing or created) when attempting to complete evaluation_id [".$RECORD_ID."] (eform_id [".$evaluation_record["eform_id"]."]).");
								}
							break;
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "You were only able to attempt this evaluation a total of <strong>".(int) $evaluation_record["max_submittable"]." time".(($evaluation_record["max_submittable"] != 1) ? "s" : "")."</strong>, and the time limit for your final attempt expired before completion.<br /><br />Please contact a teacher if you require further assistance.";

						echo display_error();

						application_log("notice", "Someone attempted to complete evaluation_id [".$RECORD_ID."] (eform_id [".$evaluation_record["eform_id"]."]) more than the total number of possible attempts [".$evaluation_record["max_submittable"]."] after their final attempt expired.");
					}
				} else {
					$NOTICE++;
					$NOTICESTR[] = "You were only able to attempt this evaluation a total of <strong>".(int) $evaluation_record["max_submittable"]." time".(($evaluation_record["max_submittable"] != 1) ? "s" : "")."</strong>.<br /><br />Please contact a teacher if you require further assistance.";

					echo display_notice();

					application_log("notice", "Someone attempted to complete evaluation_id [".$RECORD_ID."] (eform_id [".$evaluation_record["eform_id"]."]) more than the total number of possible attempts [".$evaluation_record["max_submittable"]."].");
				}
			} else {
				$NOTICE++;
				$NOTICESTR[] = "You cannot attempt this evaluation until <strong>".date(DEFAULT_DATE_FORMAT, $evaluation_record["release_date"])."</strong>.<br /><br />Please contact a teacher if you require further assistance.";
	
				echo display_notice();
	
				application_log("error", "Someone attempted to complete evaluation_id [".$RECORD_ID."] (eform_id [".$evaluation_record["eform_id"]."]) prior to the release date.");
			}
		} else {
			$NOTICE++;
			$NOTICESTR[] = "You have already completed <strong>".((int)$evaluation_record["completed_attempts"])."</strong> out of the allowable <strong>".$evaluation_record["max_submittable"]."</strong> attempts for this evaluation.<br /><br />Please contact a teacher if you require further assistance.";

			echo display_notice();

			application_log("error", "Someone attempted to complete evaluation_id [".$RECORD_ID."] (eform_id [".$evaluation_record["eform_id"]."]) when they had completed the maximum number of attempts previously.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to attempt an evaluation, you must provide a valid evaluation identifier.";

		echo display_error();

		application_log("error", "Failed to provide a valid evaluation_id identifer [".$RECORD_ID."] when attempting to take an evaluation.");
	}
} else {
    if (!$code) {
        $ERROR++;
        $ERRORSTR[] = "In order to attempt an evaluation, you must provide a valid evaluation identifier.";

        echo display_error();

        application_log("error", "Failed to provide an evaluation_id identifier when attempting to take an evaluation.");
    } else {
        $ERROR++;
        $ERRORSTR[] = "In order to attempt this evaluation, you must provide a valid evaluation request code.";

        echo display_error();

        application_log("error", "Provided an unusable request_code identifier when attempting to take an evaluation.");
    }
}