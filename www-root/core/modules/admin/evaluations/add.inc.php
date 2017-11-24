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
 * This file is used to create new evaluations in the entrada.evaluations table.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations?section=add", "title" => "Create Evaluation");

	echo "<h1>Create Evaluation</h1>\n";

	$PROCESSED["evaluation_evaluators"] = array();
	$PROCESSED["evaluation_targets"] = array();

	// Error Checking
	switch($STEP) {
		case 2 :
			/**
			 * Processing for evaluations table.
			 */

			/**
			 * Required field "evaluation_title" / Evaluation Title.
			 */
			if ((isset($_POST["evaluation_title"])) && ($evaluation_title = clean_input($_POST["evaluation_title"], array("notags", "trim")))) {
				$PROCESSED["evaluation_title"] = $evaluation_title;
			} else {
				add_error("The <strong>Evaluation Title</strong> field is required.");
			}

			/**
			 * Non-required field "evaluation_description" / Special Instructions.
			 */
			if ((isset($_POST["evaluation_description"])) && ($evaluation_description = clean_input($_POST["evaluation_description"], array("notags", "trim")))) {
				$PROCESSED["evaluation_description"] = $evaluation_description;
			} else {
				$PROCESSED["evaluation_description"] = "";
			}

			$evaluation_target_id = 0;
			$evaluation_target_type = "";

			/**
			 * Required field "eform_id" / Evaluation Form
			 */
			if (isset($_POST["eform_id"]) && ($eform_id = clean_input($_POST["eform_id"], "int"))) {
				$query = "	SELECT a.*, b.`target_id`, b.`target_shortname`
							FROM `evaluation_forms` AS a
							LEFT JOIN `evaluations_lu_targets` AS b
							ON b.`target_id` = a.`target_id`
							WHERE a.`eform_id` = ".$db->qstr($eform_id)."
							AND a.`form_active` = '1'";
				$result = $db->GetRow($query);
				if ($result) {
					$evaluation_target_id = $result["target_id"];
					$evaluation_target_type = $result["target_shortname"];

					$PROCESSED["eform_id"] = $eform_id;
					$ONLOAD[] = "initFormOptions()";
				} else {
					add_error("The <strong>Evaluation Form</strong> that you selected is not currently available for use.");
				}
			} else {
				add_error("You must select an <strong>Evaluation Form</strong> to use during this evaluation.");
			}

			/**
			 * Non-required field "release_date" / Viewable Start (validated through validate_calendars function).
			 * Non-required field "release_until" / Viewable Finish (validated through validate_calendars function).
			 */
			$viewable_date = validate_calendars("evaluation", false, false);
			if ((isset($viewable_date["start"])) && ((int) $viewable_date["start"])) {
				$PROCESSED["evaluation_start"] = (int) $viewable_date["start"];
			} else {
				$PROCESSED["evaluation_start"] = 0;
			}
			if ((isset($viewable_date["finish"])) && ((int) $viewable_date["finish"])) {
				$PROCESSED["evaluation_finish"] = (int) $viewable_date["finish"];
			} else {
				$PROCESSED["evaluation_finish"] = 0;
			}

			/**
			 * Non-required field "evaluation_mandatory" / Evaluation Mandatory
			 */
			if (isset($_POST["evaluation_mandatory"]) && ($_POST["evaluation_mandatory"])) {
				$PROCESSED["evaluation_mandatory"] = true;
			} else {
				$PROCESSED["evaluation_mandatory"] = false;
			}

			/**
			 * Non-required field "allow_target_review" / Allow Target Review
			 */
			if (isset($_POST["allow_target_review"]) && ($_POST["allow_target_review"])) {
				$PROCESSED["allow_target_review"] = true;
			} else {
				$PROCESSED["allow_target_review"] = false;
			}

			/**
			 * Non-required field "allow_target_request" / Allow Target Request
			 */
			if (isset($_POST["allow_target_request"]) && ($_POST["allow_target_request"])) {
				$PROCESSED["allow_target_request"] = true;

                /**
                 * Non-required field "require_requests" / Require Target Requests
                 */
                if (isset($_POST["require_requests"]) && ($_POST["require_requests"])) {
                    $PROCESSED["require_requests"] = true;
                } else {
                    $PROCESSED["require_requests"] = false;
                }

                /**
                 * Non-required field "require_request_code" / Require Request Code
                 */
                if (isset($_POST["require_request_code"]) && ($_POST["require_request_code"])) {
                    $PROCESSED["require_request_code"] = true;
                } else {
                    $PROCESSED["require_request_code"] = false;
                }

                /**
                 * Required field "request_timeout" / Request Timeout in Minutes
                 */
                if (isset($_POST["request_timeout"]) && ($request_timeout = clean_input($_POST["request_timeout"], "int")) && ($request_timeout >= 1)) {
                    $PROCESSED["request_timeout"] = $request_timeout;
                } else {
                    $PROCESSED["request_timeout"] = 0;
                }
			} else {
				$PROCESSED["allow_target_request"] = false;
			}

			/**
			 * Non-required field "allow_repeat_targets" / Allow Multiple Attempts on Each Target
			 */
			if (isset($_POST["allow_repeat_targets"]) && ($_POST["allow_repeat_targets"])) {
				$PROCESSED["allow_repeat_targets"] = true;
			} else {
				$PROCESSED["allow_repeat_targets"] = false;
			}

			/**
			 * Non-required field "show_comments" / Show Comments
			 */
			if (isset($_POST["show_comments"]) && ($_POST["show_comments"])) {
				$PROCESSED["show_comments"] = true;
			} else {
				$PROCESSED["show_comments"] = false;
			}

            /**
             * Non-required field "identify_comments" / Identify Comments
             */
            if (isset($_POST["identify_comments"]) && ($_POST["identify_comments"])) {
                $PROCESSED["identify_comments"] = true;
            } else {
                $PROCESSED["identify_comments"] = false;
            }

			/**
			 * Required field "min_submittable" / Min Submittable
			 */
			if (isset($_POST["min_submittable"]) && ($min_submittable = clean_input($_POST["min_submittable"], "int")) && ($min_submittable >= 1)) {
				$PROCESSED["min_submittable"] = $min_submittable;
			} elseif ($evaluation_target_type == "peer") {
				$PROCESSED["min_submittable"] = 0;
			} else {
				add_error("The evaluation <strong>Min Submittable</strong> field is required and must be greater than 1.");
			}

			/**
			 * Required field "max_submittable" / Max Submittable
			 */
			if (isset($_POST["max_submittable"]) && (($max_submittable = clean_input($_POST["max_submittable"], "int")) || ($max_submittable === 0)) && ($max_submittable <= 999)) {
				$PROCESSED["max_submittable"] = $max_submittable;
			} elseif ($evaluation_target_type == "peer") {
				$PROCESSED["max_submittable"] = 0;
			} else {
				add_error("The evaluation <strong>Max Submittable</strong> field is required and must be less than 999.");
			}

			if ($PROCESSED["min_submittable"] > $PROCESSED["max_submittable"] && $PROCESSED["max_submittable"] !== 0) {
				add_error("Your <strong>Min Submittable</strong> value may not be greater than your <strong>Max Submittable</strong> value.");
			}

			/**
			 * Non-required field "release_date" / Viewable Start (validated through validate_calendars function).
			 * Non-required field "release_until" / Viewable Finish (validated through validate_calendars function).
			 */
			$viewable_date = validate_calendars("viewable", false, false);
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
			 * Processing for evaluation_evaluators table.
			 */
			if (isset($_POST["target_group_type"]) && in_array($_POST["target_group_type"], array("cohort", "percentage", "proxy_id", "faculty", "cgroup_id"))) {
				switch ($_POST["target_group_type"]) {
					case "cohort" :
						if (isset($_POST["cohort"]) && ($cohort = clean_input($_POST["cohort"], array("trim", "int")))) {
							$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "cohort", "evaluator_value" => $cohort);
						} else {
							add_error("Please provide a valid cohort to complete this evaluation.");
						}
					break;
					case "percentage" :
						if (isset($_POST["percentage_cohort"]) && ($cohort = clean_input($_POST["percentage_cohort"], array("alphanumeric")))) {
							$percentage = clean_input($_POST["percentage_percent"], "int");
							if (($percentage >= 100) || ($percentage < 1)) {
								$percentage = 100;

								$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "cohort", "evaluator_value" => $cohort);
							} else {
								$query = "	SELECT a.`id` AS `proxy_id`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											JOIN `group_members` AS c
											ON a.`id` = c.`proxy_id`
											WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
											AND b.`account_active` = 'true'
											AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
											AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
											AND b.`group` = 'student'
											AND c.`group_id` = ".$db->qstr($cohort);
								$results = $db->GetAll($query);
								if ($results) {
									$total_students = count($results);
								}

								$percentage = round($total_students * $percentage / 100);

								$query .= "	ORDER BY RAND()
											LIMIT 0, ".$percentage;

								$results = $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "proxy_id", "evaluator_value" => $result["proxy_id"]);
									}
								}
							}
						} else {
							add_error("Please provide a valid class to complete this evaluation.");
						}
					break;
					case "cgroup_id" :
						if (isset($_POST["cgroup_ids"]) && is_array($_POST["cgroup_ids"])) {
							$cgroup_ids = $_POST["cgroup_ids"];
							foreach ($cgroup_ids as $cgroup_id) {
								$evaluator_values[] = (int)$cgroup_id;
							}
							if (!empty($evaluator_values)) {
								$evaluator_values = array_unique($evaluator_values);

								$query = "	SELECT a.`cgroup_id`
											FROM `course_groups` AS a
											JOIN `courses` AS b
											ON b.`course_id` = a.`course_id`
											WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
											AND a.`active` = 1
											AND b.`course_active` = 1
											AND a.`cgroup_id` IN (".implode(", ", $evaluator_values).")";
								$results = $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "cgroup_id", "evaluator_value" => $result["cgroup_id"]);
									}
								}
							}
						}
					break;
					case "proxy_id" :
						if ((isset($_POST["associated_student"]))) {
							$evaluator_values = array();

							$associated_student = explode(",", $_POST["associated_student"]);

							if (is_array($associated_student) && !empty($associated_student)) {
								foreach($associated_student as $proxy_id) {
									$proxy_id = clean_input($proxy_id, "int");

									if ($proxy_id) {
										$evaluator_values[] = $proxy_id;
									}
								}
							}

							if (!empty($evaluator_values)) {
								$evaluator_values = array_unique($evaluator_values);

								$query = "	SELECT a.`id` AS `proxy_id`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
											AND b.`account_active` = 'true'
											AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
											AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
											AND a.`id` IN (".implode(", ", $evaluator_values).")";
								$results = $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "proxy_id", "evaluator_value" => $result["proxy_id"]);
									}
								}
							}
						} else {
							add_error("You must select at least one individual to act as an evaluator.");
						}
					break;
					case "faculty" :
						if ((isset($_POST["associated_evalfaculty"]))) {
							$evaluator_values = array();

							$associated_faculty = explode(",", $_POST["associated_evalfaculty"]);

							if (is_array($associated_faculty) && !empty($associated_faculty)) {
								foreach($associated_faculty as $proxy_id) {
									$proxy_id = clean_input($proxy_id, "int");

									if ($proxy_id) {
										$evaluator_values[] = $proxy_id;
									}
								}
							}

							if (!empty($evaluator_values)) {
								$evaluator_values = array_unique($evaluator_values);

								$query = "	SELECT a.`id` AS `proxy_id`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
											AND b.`account_active` = 'true'
											AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
											AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
											AND a.`id` IN (".implode(", ", $evaluator_values).")";
								$results = $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => "proxy_id", "evaluator_value" => $result["proxy_id"]);
									}
								}
							}
						} else {
							add_error("You must select at least one individual to act as an evaluator.");
						}
					break;
				}

				if (empty($PROCESSED["evaluation_evaluators"])) {
					add_error("Please select an appropriate type of evaluator (i.e. entire class, percentage, etc).");
				}
			} elseif ($evaluation_target_type != "peer") {
				add_error("Please select an appropriate type of evaluator (i.e. entire class, percentage, etc).");
			}

			$PROCESSED = Classes_Evaluation::processTargets($_POST, $PROCESSED);

			/**
			 * Non-required field "associated_reviewer" / Associated Reviewers (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if ((isset($_POST["associated_reviewer"]))) {
				$associated_reviewers = explode(",", $_POST["associated_reviewer"]);
				foreach($associated_reviewers as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_reviewers"][(int) $contact_order] = $proxy_id;
					}
				}
			}

			/**
			 * Processing for evaluation_evaluators table.
			 */
			if (isset($_POST["threshold_notifications_type"]) && in_array($_POST["threshold_notifications_type"], array("reviewers","tutors","directors","pcoordinators","authors","disabled"))) {
				$PROCESSED["threshold_notifications_type"] = $_POST["threshold_notifications_type"];
			} else {
				$PROCESSED["threshold_notifications_type"] = "disabled";
			}

			if (!$ERROR) {
				$PROCESSED["evaluation_active"] = 1;
				$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				/**
				 * Insert the evaluation record into the evalutions table.
				 */
				if ($db->AutoExecute("evaluations", $PROCESSED, "INSERT") && ($evaluation_id = $db->Insert_Id())) {

					/**
					 * If there are reviewers associated with this event, add them
					 * to the evaluation_contacts table.
					 */
					if ((is_array($PROCESSED["associated_reviewers"])) && (count($PROCESSED["associated_reviewers"]))) {
						foreach($PROCESSED["associated_reviewers"] as $contact_order => $proxy_id) {
							$contact_details =  array(	"evaluation_id" => $evaluation_id,
														"proxy_id" => $proxy_id,
														"contact_role" => "reviewer",
														"contact_order" => (int) $contact_order,
														"updated_date" => time(),
														"updated_by" => $ENTRADA_USER->getID());
							if (!$db->AutoExecute("evaluation_contacts", $contact_details, "INSERT")) {
								add_error("There was an error while trying to attach an <strong>Associated Reviewer</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to insert a new evaluation_contact record while adding a new evaluation. Database said: ".$db->ErrorMsg());
							}
						}
					}

					/**
					 * Insert the target records into the evaluation_targets table.
					 */
					if (!empty($PROCESSED["evaluation_targets"])) {
						foreach ($PROCESSED["evaluation_targets"] as $target_value) {
							$record = array(
								"evaluation_id" => $evaluation_id,
								"target_id" => $evaluation_target_id,
								"target_value" => $target_value["target_value"],
								"target_type" => $target_value["target_type"],
								"target_active" => 1,
								"updated_date" => time(),
								"updated_by" => $ENTRADA_USER->getID()
							);

							if ($evaluation_target_type == "peer") {
								$PROCESSED["evaluation_evaluators"][] = array("evaluator_type" => $record["target_type"], "evaluator_value" => $record["target_value"]);
							}

							if (!$db->AutoExecute("evaluation_targets", $record, "INSERT") || (!$etarget_id = $db->Insert_Id())) {
								add_error("Unable to attach an evaluation target to this evaluation. The system administrator has been notified of this error, please try again later.");
								application_log("Unable to attach target_id [".$evaluation_target_id."] / target_value [".$target_value."] to evaluation_id [".$evaluation_id."]. Database said: ".$db->ErrorMsg());
							}
						}
					}

					/**
					 * Insert the target records into the evaluation_evaluators table.
					 */
					if (!empty($PROCESSED["evaluation_evaluators"])) {
						foreach ($PROCESSED["evaluation_evaluators"] as $result) {
							$record = array(
								"evaluation_id" => $evaluation_id,
								"evaluator_type" => $result["evaluator_type"],
								"evaluator_value" => $result["evaluator_value"],
								"updated_date" => time(),
								"updated_by" => $ENTRADA_USER->getID()
							);

							if (!$db->AutoExecute("evaluation_evaluators", $record, "INSERT") || (!$eevaluator_id = $db->Insert_Id())) {
								add_error("Unable to attach an evaluator to this evaluation. The system administrator has been notified of this error, please try again later.");
								application_log("Unable to attach target_id [".$evaluation_target_id."] / target_value [".$target_value."] to evaluation_id [".$evaluation_id."]. Database said: ".$db->ErrorMsg());
							}
						}
					}

					if (!$ERROR) {
						$url = ENTRADA_URL."/admin/evaluations";
						$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
						add_success("You have successfully added <strong>".html_encode($PROCESSED["evaluation_title"])."</strong> to the system.<br /><br />You will now be redirected to the evaluation index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
						application_log("success", "New evaluation [".$evaluation_id."] added to the system.");
					}
				} else {
					add_error("Unable to create new evaluation record at this time. The system administrator has been notified of this error, please try again later.");

					application_log("Unable to create new evaluation record. Database said: ".$db->ErrorMsg());
				}

				/**
				 * If there are errors, remove the already inserted records.
				 */
				if ($ERROR && $evaluation_id) {
					$db->Execute("DELETE FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($evaluation_id));
					$db->Execute("DELETE FROM `evaluation_targets` WHERE `evaluation_id` = ".$db->qstr($evaluation_id));
					$db->Execute("DELETE FROM `evaluation_evaluators` WHERE `evaluation_id` = ".$db->qstr($evaluation_id));
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
	switch($STEP) {
		case 2 :
			display_status_messages();
		break;
		case 1 :
		default :
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
			$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/elementresizer.js\" type=\"text/javascript\"></script>\n";

			/**
			 * Compiles the full list of faculty members.
			 */
			$REVIEWER_LIST = array();
			$query = "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
						FROM `".AUTH_DATABASE."`.`user_data` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						WHERE b.`app_id` = '".AUTH_APP_ID."'
						AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer') OR b.`group` = 'staff' OR b.`group` = 'medtech')
						ORDER BY a.`lastname` ASC, a.`firstname` ASC";
			$results = $db->GetAll($query);
			if ($results) {
				foreach($results as $result) {
					$REVIEWER_LIST[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
			}

			if (has_error() || has_notice()) {
				echo display_status_messages();
			}
			?>
			<script type="text/javascript">
			function initFormOptions() {
				if ($('target_type_rotations') != undefined) {
					var target_type = 'rotations';
					if ($(target_type + '_options') != undefined) {

						$(target_type + '_options').addClassName('multiselect-processed');

						multiselect[target_type] = new Control.SelectMultiple('evaluation_target_'+target_type, target_type + '_options', {
							checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
							nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
							filter: target_type + '_select_filter',
							resize: target_type + '_scroll',
							afterCheck: function(element) {
								var tr = $(element.parentNode.parentNode);
								tr.removeClassName('selected');

								if (element.checked) {
									tr.addClassName('selected');

									addTarget(element.id, target_type);
								} else {
									removeTarget(element.id, target_type);
								}
							}
						});

						if ($(target_type + '_cancel') != undefined) {
							$(target_type + '_cancel').observe('click', function(event) {
								this.container.hide();

								$('target_type').options.selectedIndex = 0;
								$('target_type').show();

								return false;
							}.bindAsEventListener(multiselect[target_type]));
						}

						if ($(target_type + '_close') != undefined) {
							$(target_type + '_close').observe('click', function(event) {
								this.container.hide();

								$('target_type').clear();

								return false;
							}.bindAsEventListener(multiselect[target_type]));
						}

						multiselect[target_type].container.show();
					}
				}
				if ($('scripts-on-open') != undefined && $('scripts-on-open').innerHTML) {
					eval($('scripts-on-open').innerHTML);
				}
			}
			function updateFormOptions() {
				if ($F('eform_id') > 0)  {

					var currentLabel = $('eform_id').options[$('eform_id').selectedIndex].up().readAttribute('label');

					if (currentLabel != selectedFormType) {
						selectedFormType = currentLabel;

						$('evaluation_options').show();
						$('evaluation_options').update('<tr><td colspan="2">&nbsp;</td><td><div class="content-small" style="vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Please wait while <strong>evaluation options</strong> are loaded ... </div></td></tr>');

						new Ajax.Updater('evaluation_options', '<?php echo ENTRADA_RELATIVE; ?>/admin/evaluations?section=api-form-options', {
							evalScripts : true,
							parameters : {
								ajax : 1,
								form_id : $F('eform_id')
							},
							onSuccess : function (response) {
								if (response.responseText == "") {
									$('evaluation_options').update('');
									$('evaluation_options').hide();
								}
							},
							onFailure : function (response) {
								$('evaluation_options').update('');
								$('evaluation_options').hide();
							},
							onComplete : function () {
								if ($('target_type_rotations') != undefined) {
									var target_type = 'rotations';
									if ($(target_type + '_options') != undefined) {

										$(target_type + '_options').addClassName('multiselect-processed');

										multiselect[target_type] = new Control.SelectMultiple('evaluation_target_'+target_type, target_type + '_options', {
											checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
											nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
											filter: target_type + '_select_filter',
											resize: target_type + '_scroll',
											afterCheck: function(element) {
												var tr = $(element.parentNode.parentNode);
												tr.removeClassName('selected');

												if (element.checked) {
													tr.addClassName('selected');

													addTarget(element.id, target_type);
												} else {
													removeTarget(element.id, target_type);
												}
											}
										});

										if ($(target_type + '_cancel') != undefined) {
											$(target_type + '_cancel').observe('click', function(event) {
												this.container.hide();

												$('target_type').options.selectedIndex = 0;
												$('target_type').show();

												return false;
											}.bindAsEventListener(multiselect[target_type]));
										}

										if ($(target_type + '_close') != undefined) {
											$(target_type + '_close').observe('click', function(event) {
												this.container.hide();

												$('target_type').clear();

												return false;
											}.bindAsEventListener(multiselect[target_type]));
										}

										multiselect[target_type].container.show();
									}
								}
								if ($('scripts-on-open') != undefined && $('scripts-on-open').innerHTML) {
									eval($('scripts-on-open').innerHTML);
								}
							}
						});
					} else {
						$('evaluation_options').show();
					}
				} else {
					$('evaluation_options').hide();
				}
			}

			function selectTargetGroupOption(type) {
				$$('input[type=radio][value=' + type + ']').each(function(el) {
					$(el.id).checked = true;
				});

				$$('.target_group').invoke('hide');
				$$('.' + type + '_target').invoke('show');
			}

			var multiselect = [];
			var target_type;

			function showMultiSelect() {
				$$('select_multiple_container').invoke('hide');
				target_type = $F('target_type');
				form_id = $('eform_id').options[$('eform_id').selectedIndex].value;
				var cohorts = ($('evaluation_target_cohorts') ? $('evaluation_target_cohorts').value : "");
				var course_groups = ($('evaluation_target_course_groups') ? $('evaluation_target_course_groups').value : "");
				var students = ($('evaluation_target_students') ? $('evaluation_target_students').value : "");
				var rotations = ($('evaluation_target_rotations') ? $('evaluation_target_rotations').value : "");

				if (multiselect[target_type]) {
					multiselect[target_type].container.show();
				} else {
					if (target_type) {
						new Ajax.Request('<?php echo ENTRADA_RELATIVE; ?>/admin/evaluations?section=api-target-selector', {
							evalScripts : true,
							parameters: {
								'options_for' : target_type,
								'form_id' : form_id,
								'ajax' : 1,
								'evaluation_target_cohorts' : cohorts,
								'evaluation_target_course_groups' : course_groups,
								'evaluation_target_students' : students,
								'evaluation_target_rotations' : rotations
							},
							method: 'post',
							onLoading: function() {
								$('options_loading').show();
							},
							onSuccess: function(response) {
								if (response.responseText) {
									$('options_container').insert(response.responseText);

									if ($(target_type + '_options') != undefined) {

										$(target_type + '_options').addClassName('multiselect-processed');

										multiselect[target_type] = new Control.SelectMultiple('evaluation_target_'+target_type, target_type + '_options', {
											checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
											nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
											filter: target_type + '_select_filter',
											resize: target_type + '_scroll',
											afterCheck: function(element) {
												var tr = $(element.parentNode.parentNode);
												tr.removeClassName('selected');

												if (element.checked) {
													tr.addClassName('selected');

													addTarget(element.id, target_type);
												} else {
													removeTarget(element.id, target_type);
												}
											}
										});

										if ($(target_type + '_cancel') != undefined) {
											$(target_type + '_cancel').observe('click', function(event) {
												this.container.hide();

												$('target_type').options.selectedIndex = 0;
												$('target_type').show();

												return false;
											}.bindAsEventListener(multiselect[target_type]));
										}

										if ($(target_type + '_close') != undefined) {
											$(target_type + '_close').observe('click', function(event) {
												this.container.hide();

												$('target_type').clear();

												return false;
											}.bindAsEventListener(multiselect[target_type]));
										}

										multiselect[target_type].container.show();
									}
								} else {
									new Effect.Highlight('target_type', {startcolor: '#FFD9D0', restorecolor: 'true'});
									new Effect.Shake('target_type');
								}
							},
							onError: function() {
								alert("There was an error retrieving the requested target. Please try again.");
							},
							onComplete: function() {
								$('options_loading').hide();
							}
						});
					}
				}
				return false;
			}

			function addTarget(element, target_id) {
				if (!$('target_'+element)) {
					$('target_list').innerHTML += '<li class="' + (target_id == 'students' ? 'user' : 'group') + '" id="target_'+element+'" style="cursor: move;">'+$($(element).value+'_label').innerHTML+'<img src="<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif" onclick="removeTarget(\''+element+'\', \''+target_id+'\');" class="list-cancel-image" /></li>';
					$$('#target_list div').each(function (e) { e.hide(); });

					Sortable.destroy('target_list');
					Sortable.create('target_list');
				}
				var tr = $(element).parentNode.parentNode;
				if (tr.hasClassName('category')) {
					tr.siblings().each(
						function (el) {
							el.addClassName('selected');
						}
					);
					$$('#rotations_scroll .select_multiple_checkbox input').each(
						function (e) {
							e.checked = true;
							e.disable();
						}
					);
				} else if (tr.hasClassName('cat_enabled')) {
					$$('#rotations_scroll .select_multiple_checkbox_category input').each(
						function (e) {
							e.disable();
						}
					);
				}
			}

			function removeTarget(element, target_id) {
				if ($(element) != undefined) {
					var tr = $(element).parentNode.parentNode;
					if (tr.hasClassName('category')) {
						tr.siblings().each(
							function (e) {
								e.removeClassName('selected');
							}
						);
						$$('#rotations_scroll .select_multiple_checkbox input').each(
							function (e) {
								e.enable();
								e.checked = false;
							}
						)
						$('evaluation_target_'+target_id).value = "";
					} else {
						$$('#rotations_scroll .select_multiple_checkbox_category input').each(
							function (el) {
								el.enable();
								el.checked = false;
							}
						);
					}
				}
				if ($('target_'+element) != undefined) {
					$('target_'+element).remove();
				}
				Sortable.destroy('target_list');
				Sortable.create('target_list');
				if ($(element) != undefined) {
					$(element).checked = false;
				}
				var target = $('evaluation_target_'+target_id).value.split(',');
				for (var i = 0; i < target.length; i++) {
					if (target[i] == element) {
						target.splice(i, 1);
						break;
					}
				}
				$('evaluation_target_'+target_id).value = target.join(',');
			}

			function updateAudienceOptions() {
				var form_id = $('eform_id').options[$('eform_id').selectedIndex].value;
				if (form_id > 0)  {

					var selectedForm = '';

					var currentLabel = $('eform_id').options[$('eform_id').selectedIndex].innerHTML;

					if (currentLabel != selectedForm) {
						selectedForm = currentLabel;
						var cohorts = ($('evaluation_target_cohorts') ? $('evaluation_target_cohorts').getValue() : '');
						var course_groups = ($('evaluation_target_course_groups') ? $('evaluation_target_course_groups').getValue() : '');
						var students = ($('evaluation_target_students') ? $('evaluation_target_students').getValue() : '');

						$('audience-options').show();
						$('audience-options').update('<tr><td colspan="2">&nbsp;</td><td><div class="content-small" style="vertical-align: middle"><img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" /> Please wait while <strong>audience options</strong> are being loaded ... </div></td></tr>');

						new Ajax.Updater('audience-options', '<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=api-audience-options', {
							evalScripts : true,
							parameters : {
								'ajax' : 1,
								'form_id' : form_id,
								'evaluation_target_students': students,
								'evaluation_target_course_groups': course_groups,
								'evaluation_target_cohorts': cohorts
							},
							onSuccess : function (response) {
								if (response.responseText == "") {
									$('audience-options').update('');
									$('audience-options').hide();
								}
							},
							onFailure : function () {
								$('audience-options').update('');
								$('audience-options').hide();
							}
						});
					}
				} else {
					$('audience-options').update('');
					$('audience-options').hide();
				}
			}
			</script>
			<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations?section=add&amp;step=2" method="post" name="addEvaluationForm" id="addEvaluationForm" class="form-horizontal">
			<h2>Evaluation Details</h2>
			<div class="control-group">
				<label for="evaluation_title" class="form-required control-label">Evaluation Title:</label>
				<div class="controls">
					<input type="text" id="evaluation_title" name="evaluation_title" value="<?php echo html_encode((isset($PROCESSED["evaluation_title"]) && $PROCESSED["evaluation_title"] ? $PROCESSED["evaluation_title"] : "")); ?>" maxlength="255" style="width: 95%" />
				</div>
			</div>
			<div class="control-group">
				<label for="evaluation_description" class="form-nrequired control-label">Special Instructions:</label>
				<div class="controls">
					<textarea id="evaluation_description" name="evaluation_description" class="expandable" style="width: 95%; height:50px" cols="50" rows="15"><?php echo html_encode((isset($PROCESSED["evaluation_description"]) && $PROCESSED["evaluation_description"] ? $PROCESSED["evaluation_description"] : "")); ?></textarea>
				</div>
			</div>
			<h2>Evaluation Form Options</h2>
			<div class="control-group">
				<label for="eform_id" class="form-required control-label">Evaluation Form:</label>
				<div class="controls">
					<select id="eform_id" name="eform_id" style="width:328px">
								<option value="0"> -- Select Evaluation Form -- </option>
								<?php
								$query	= "	SELECT a.*, b.`target_shortname`, b.`target_title`
											FROM `evaluation_forms` AS a
											LEFT JOIN `evaluations_lu_targets` AS b
											ON b.`target_id` = a.`target_id`
											WHERE a.`form_active` = '1'
											ORDER BY b.`target_title` ASC";
								$results = $db->GetAll($query);
								if ($results) {
									$total_forms = count($results);
									$optgroup_label = "";

									foreach ($results as $key => $result) {
										if ($result["target_title"] != $optgroup_label) {
											$optgroup_label = $result["target_title"];
											if ($key > 0) {
												echo "</optgroup>";
											}
											echo "<optgroup label=\"".html_encode($optgroup_label)." Forms\">";
										}
										echo "<option value=\"".(int) $result["eform_id"]."\"".(($PROCESSED["eform_id"] == $result["eform_id"]) ? " selected=\"selected\"" : "")."> ".html_encode($result["form_title"])."</option>";
									}
									echo "</optgroup>";
								}
								?>
								</select>
				</div>
			</div> <!--/control-group-->
            <table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
                <colgroup>
                    <col style="width: 3%" />
                    <col style="width: 20%" />
                    <col style="width: 77%" />
                </colgroup>
                <tbody id="evaluation_options"<?php echo (!isset($PROCESSED["eform_id"]) || (!$PROCESSED["eform_id"]) ? " style=\"display: none\"" : ""); ?>>
                    <?php
                    if (isset($PROCESSED["eform_id"]) && $PROCESSED["eform_id"]) {
                        require_once(ENTRADA_ABSOLUTE."/core/modules/admin/evaluations/api-form-options.inc.php");
                    }
                    ?>
                </tbody>
            </table>
			<div class="control-group">
				<label for="evaluation_mandatory" class="form-required control-label">Evaluation Mandatory:</label>
				<div class="controls">
					<label class="checkbox">
						<input type="checkbox" id="evaluation_mandatory" name="evaluation_mandatory"<?php echo (!isset($PROCESSED["evaluation_mandatory"]) || $PROCESSED["evaluation_mandatory"] ? " checked=\"checked\"" : ""); ?> />
						Require this evaluation be completed by all evaluators.
					</label>
				</div>
			</div>
			<div class="control-group">
				<label for="allow_target_review" class="form-required control-label">Allow Target Review:</label>
				<div class="controls">
					<label class="checkbox">
						<input type="checkbox" id="allow_target_review" name="allow_target_review"<?php echo (isset($PROCESSED["allow_target_review"]) && $PROCESSED["allow_target_review"] ? " checked=\"checked\"" : ""); ?> />
						Allow targets (or users with "ownership" permissions of the target) to review the results for this evaluation.
					</label>
				</div>
			</div>
			<div class="control-group">
				<label for="allow_target_request" class="form-required control-label">Allow Target Request:</label>
				<div class="controls">
					<label class="checkbox">
                        <input type="checkbox" onclick="$('evaluation_requests').toggle(this.checked);" id="allow_target_request" name="allow_target_request"<?php echo (isset($PROCESSED["allow_target_request"]) && $PROCESSED["allow_target_request"] ? " checked=\"checked\"" : ""); ?> />
						Allow targets to trigger a request to be sent to a valid evaluator to fill out an evaluation for them.
					</label>
				</div>
			</div>
            <div class="control-group" id="evaluation_requests"<?php echo (isset($PROCESSED["allow_target_request"]) && $PROCESSED["allow_target_request"] ? "" : "style=\"display: none;\""); ?>>
                <div class="controls">
                    <table style="width: 100%;" cellspacing="0" cellpadding="2" border="0" summary="Evaluation Requests">
                        <colgroup>
                            <col style="width: 3%" />
                            <col style="width: 30%" />
                            <col style="width: 67%" />
                        </colgroup>
                        <tr>
                            <td></td>
                            <td class="align-top">
                                <label for="require_requests" class="form-nrequired">Require Requests</label>
                            </td>
                            <td>
                                <input type="checkbox" id="require_requests" name="require_requests"<?php echo (isset($PROCESSED["require_requests"]) && $PROCESSED["require_requests"] ? " checked=\"checked\"" : ""); ?> />
                                <div style="float: right; width: 91%" class="content-small">Require an open request before allowing an evaluator to attempt this evaluation.</div>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="align-top">
                                <label for="require_request_code" class="form-nrequired">Require a Request Code</label>
                            </td>
                            <td>
                                <input type="checkbox" id="require_request_code" name="require_request_code"<?php echo (isset($PROCESSED["require_request_code"]) && $PROCESSED["require_request_code"] ? " checked=\"checked\"" : ""); ?> />
                                <div style="float: right; width: 91%" class="content-small">Generate a <strong>Request Code</strong> upon each request being made, to be inputted before allowing an evaluator to attempt this evaluation.</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">&nbsp;</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="align-top">
                                <label for="request_timeout" class="form-nrequired">Request Timeout</label>
                            </td>
                            <td>
                                <input type="text" id="request_timeout" name="request_timeout" value="<?php echo (isset($PROCESSED["request_timeout"]) && $PROCESSED["request_timeout"] ? $PROCESSED["request_timeout"] : 0); ?>" />
                                <div class="content-small">How long (in minutes) before requests time out. To disable timeouts, set to 0.</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
			<div class="control-group">
				<label for="allow_repeat_targets" class="form-required control-label">Allow Multiple Attempts on Each Target:</label>
				<div class="controls">
					<label class="checkbox">
						<input type="checkbox" id="allow_repeat_targets" name="allow_repeat_targets"<?php echo (isset($PROCESSED["allow_repeat_targets"]) && $PROCESSED["allow_repeat_targets"] ? " checked=\"checked\"" : ""); ?> />
						Allows evaluators to submit evaluations for the same target multiple times.
					</label>
				</div>
			</div>
			<div class="control-group">
				<label for="show_comments" class="form-required control-label">Show Comments in Review:</label>
				<div class="controls">
					<label class="checkbox">
						<input type="checkbox" onclick="$('identify_comments_holder').toggle(this.checked);" id="show_comments" name="show_comments"<?php echo (!isset($PROCESSED["show_comments"]) || $PROCESSED["show_comments"] ? " checked=\"checked\"" : ""); ?> />
						When reviewing this evaluation, show comments to reviewers (or targets able to review results).
					</label>
				</div>
			</div>
            <div class="control-group">
                <div class="controls">
                    <div class="control-group" id="identify_comments_holder"<?php echo (!isset($PROCESSED["show_comments"]) || $PROCESSED["show_comments"] ? "\"" : " style=\"display: none;\""); ?>>
                        <label for="identify_comments" class="form-required control-label">Identify Comments:</label>
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" id="identify_comments" name="identify_comments"<?php echo (isset($PROCESSED["identify_comments"]) && $PROCESSED["identify_comments"] ? " checked=\"checked\"" : ""); ?> />
                                When reviewing this evaluation, show the name of the evaluator who left each comment.
                            </label>
                        </div>
                    </div>
                </div>
            </div>
			<div class="control-group">
				<label for="min_submittable" class="form-required control-label">Min Submittable:</label>
				<div class="controls">
					<input type="text" id="min_submittable" name="min_submittable" value="<?php echo (isset($PROCESSED["min_submittable"]) ? $PROCESSED["min_submittable"] : 1); ?>" maxlength="3" style="width: 30px; margin-right: 10px" />
					<span class="content-small"><strong>Tip:</strong> The minimum number of times each evaluator must complete this evaluation.</span>
				</div>
			</div>
			<div class="control-group">
				<label for="max_submittable" class="form-required control-label">Max Submittable:</label>
				<div class="controls">
					<input type="text" id="max_submittable" name="max_submittable" value="<?php echo (isset($PROCESSED["max_submittable"]) ? $PROCESSED["max_submittable"] : 1); ?>" maxlength="3" style="width: 30px; margin-right: 10px" />
					<span class="content-small"><strong>Tip:</strong> The maximum number of times each evaluator may complete this evaluation.</span>
				</div>
			</div>
			<div class="control-group">
				<div class="controls" id="submittable_notice"></div>
			</div>
			<div class="control-group">
				<table>
				<?php echo generate_calendars("evaluation", "Evaluation", true, true, ((isset($PROCESSED["evaluation_start"])) ? $PROCESSED["evaluation_start"] : 0), true, true, ((isset($PROCESSED["evaluation_finish"])) ? $PROCESSED["evaluation_finish"] : 0)); ?>
				</table>
			</div>
			<div class="control-group">
				<label for="evaluation_reviewers" class="control-label form-nrequired">Evaluation Reviewers:</label>
				<div class="controls">
					<input type="text" id="reviewer_name" name="fullname" size="30" autocomplete="off" style="width: 203px; vertical-align: middle" />
					<?php
					$ONLOAD[] = "reviewer_list = new AutoCompleteList({ type: 'reviewer', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=facultyorstaff', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
					?>
					<div class="autocomplete" id="reviewer_name_auto_complete"></div>
					<input type="hidden" id="associated_reviewer" name="associated_reviewer" />
					<input type="button" class="btn btn-small" id="add_associated_reviewer" value="Add" style="vertical-align: middle" />
					<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
					<ul id="reviewer_list" class="menu" style="margin-top: 15px">
						<?php
						if (isset($PROCESSED["associated_reviewers"]) && @count($PROCESSED["associated_reviewers"])) {
							foreach ($PROCESSED["associated_reviewers"] as $reviewer) {
								if ((array_key_exists($reviewer, $REVIEWER_LIST)) && is_array($REVIEWER_LIST[$reviewer])) {
									?>
									<li class="user" id="reviewer_<?php echo $REVIEWER_LIST[$reviewer]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $REVIEWER_LIST[$reviewer]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="reviewer_list.removeItem('<?php echo $REVIEWER_LIST[$reviewer]["proxy_id"]; ?>');" class="list-cancel-image" /></li>
									<?php
								}
							}
						}
						?>
					</ul>
					<input type="hidden" id="reviewer_ref" name="reviewer_ref" value="" />
					<input type="hidden" id="reviewer_id" name="reviewer_id" value="" />
				</div>
			</div>
			<div class="control-group">
				<label for="threshold_notifications_type" class="form-nrequired control-label">Mandatory Threshold Notifications:</label>
				<div class="controls">
					<select  name="threshold_notifications_type" id="threshold_notifications_type">
									<option value="disabled"<?php echo (!isset($PROCESSED["threshold_notifications_type"]) || !$PROCESSED["threshold_notifications_type"] || $PROCESSED["threshold_notifications_type"] == "disabled" ? " checked=\"checked\"" : ""); ?>>-- Disabled --</option>
									<option value="reviewers"<?php echo ($PROCESSED["threshold_notifications_type"] == "reviewers" ? " selected=\"selected\"" : ""); ?>>Evaluation Reviewers</option>
									<option value="authors"<?php echo ($PROCESSED["threshold_notifications_type"] == "authors" ? " selected=\"selected\"" : ""); ?>>Evaluation Form Authors</option>
									<option<?php echo ((!in_array($evaluation_target_type, array("student", "peer", "self"))) ? " style=\"display: none;\"" : ""); ?> value="tutors"<?php echo ($PROCESSED["threshold_notifications_type"] == "tutors" ? " selected=\"selected\"" : ""); ?> id="tutors_select">Tutors</option>
									<option<?php echo ((!in_array($evaluation_target_type, array("course", "rotation_core", "preceptor"))) ? " style=\"display: none;\"" : ""); ?> value="directors"<?php echo ($PROCESSED["threshold_notifications_type"] == "directors" ? " selected=\"selected\"" : ""); ?> id="directors_select">Course Directors</option>
									<option<?php echo ((!in_array($evaluation_target_type, array("course", "rotation_core", "preceptor"))) ? " style=\"display: none;\"" : ""); ?> value="pcoordinators"<?php echo ($PROCESSED["threshold_notifications_type"] == "pcoordinators" ? " selected=\"selected\"" : ""); ?> id="pcoordinators_select">Program Coordinators</option>
								</select>
								<div class="content-small">
									<strong>Note</strong>: When set to a value other than Disabled, notifications will be sent out to the identified user(s) whenever an evaluation is submitted in which a question is answered below the <strong>Minimum Pass</strong> set for that question.
								</div>
				</div>
			</div>
			<h2>Time Release Options</h2>
			<div class="control-group">
				<table>
					<?php echo generate_calendars("viewable", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), 1); ?>
				</table>
			</div>
			<div class="form-actions">
				<input type="submit" class="btn btn-primary" value="Proceed" />
				<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/evaluations'" />
			</div>
			</form>
			<script type="text/javascript">
			document.observe("dom:loaded", function() {
				$('eform_id').observe('change', function() {
					updateFormOptions();
				});

				$('addEvaluationForm').observe('submit', function() {
					selIt();
				});

				selectedFormType = (($('eform_id') && $('eform_id').selectedIndex) ? $('eform_id').options[$('eform_id').selectedIndex].up().readAttribute('label') : '');
			});
			</script>
			<?php
		break;
	}
}
