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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "create", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$HEAD[] = "<script type=\"text/javascript\">var SITE_URL = '".ENTRADA_URL."';</script>";
	if ($COURSE_ID) {
		$HEAD[] = "<script type=\"text/javascript\">var COURSE_ID = '".$COURSE_ID."';</script>";
	}
	$HEAD[] = "<script type=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives_assessment.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/gradebook/assessments.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/forms/view.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessments.css?release=".html_encode(APPLICATION_VERSION)."\" />";

	if (isset($_POST["mode"]) && $_POST["mode"] == "ajax") {

		ob_clear_open_buffers();

		$method = clean_input($_POST["method"], array("trim", "striptags"));

		switch ($method) {
			case "fetch-extended-options" :

				$current_type_id = clean_input($_POST["type"], "int");

				$query = "SELECT `type` FROM `assessments_lu_meta` WHERE `id` = ".$db->qstr($current_type_id);
				$current_type = $db->GetOne($query);

				if (in_array($current_type, array("reflection", "paper", "project"))) {
					$where = " WHERE `type` LIKE ".$db->qstr("%".$current_type."%");
				} else if (in_array($current_type, array("exam", "quiz"))) {
					$where = " WHERE `type` IS NULL ";
				} else {
					$where = " WHERE 'x' = 'y'";
				}
				$query = "SELECT `id`, `title` FROM `assessments_lu_meta_options`" . $where;
				$assessment_options = $db->GetAll($query);
				if ($assessment_options) {
					foreach ($assessment_options as $assessment_option) {
						echo "<label class=\"checkbox\" for=\"extended_option" . $assessment_option["id"] . "\"><input type=\"checkbox\" value=\"" . $assessment_option["id"] . "\" name=\"option[]\" id=\"extended_option" . $assessment_option["id"] . "\"/>" . $assessment_option["title"] . "</label>";
					}
				}

			break;
			default:
			continue;
		}

		exit;

	}

	if ($COURSE_ID) {
		$event = false;

		$query = "	SELECT * FROM `courses`
					WHERE `course_id` = ".$db->qstr($COURSE_ID)."
					AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);

		// get course info
		$course = Models_Course::fetchRowByID($COURSE_ID);

		// Load the Preference from the 'courses' Module, in order to get and set the selected_curriculum_period setting
		$PREFERENCES = preferences_load('courses');

		if (isset($_GET["cperiod_id"]) && $cperiod_id = clean_input($_GET["cperiod_id"], array("int", "trim"))) {

			$curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);

			$period_selector = new Views_Gradebook_Period(array(
					"id" => "select-period",
					"course" => $course,
					"class" => "pull-right cperiod-label",
					"label" => $translate->_("Period:"),
					"curriculum_periods" => $curriculum_period)
			);
		}

		$m_query = "	SELECT * FROM `assessment_marking_schemes`
						WHERE `enabled` = 1;";
		$MARKING_SCHEMES = $db->GetAll($m_query);

		$assessment_options_query = "SELECT `id`, `title`, `active`
									 FROM `assessments_lu_meta_options`
									 WHERE `active` = '1'";
		$assessment_options = $db->GetAll($assessment_options_query);
		if ($course_details && $MARKING_SCHEMES && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "update")) {
			function return_id($arr) {
				return $arr["id"];
			}

			$MARKING_SCHEME_IDS = array_map("return_id", $MARKING_SCHEMES);
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "edit", "id" => $COURSE_ID, "step" => false)), "title" => "Add Assessment");

			/**
			 * Process Graders
			 */
			if (isset($_POST["graders"]) && is_array($_POST["graders"])) {
				foreach ($_POST["graders"] as $grader) {
					if ($grader = clean_input($grader, array("trim","int"))) {
						$PROCESSED["graders"][] = $grader;
						if (isset($_POST["g_assignment_".$grader]) && is_array($_POST["g_assignment_".$grader])) {
							foreach ($_POST["g_assignment_".$grader] as $learner) {
								if ($learner = clean_input($learner, array("trim", "int"))) {
									$PROCESSED["g_assignment_".$grader][] = $learner;
								}
							}
						}
					}
				}
			}

			// Set defaults
			$PROCESSED["published"] = 1;
			$PROCESSED["type"] = "Summative";

			// Error Checking
			switch($STEP) {
				case 2 :
					$posted_objectives = array();

					$clinical_presentations = array();

					if (isset($_POST["clinical_presentations"])) {
						$tmp_input = $_POST["clinical_presentations"];
						foreach ($tmp_input as $presentation) {
							$PROCESSED["clinical_presentations"][] = clean_input($presentation, "int");
						}
					}

					if (isset($_POST["checked_objectives"]) && ($checked_objectives = $_POST["checked_objectives"]) && (is_array($checked_objectives))) {
						foreach ($checked_objectives as $objective_id) {
							if ($objective_id = (int) $objective_id) {
								if (isset($_POST["objective_text"][$objective_id]) && ($tmp_input = clean_input($_POST["objective_text"][$objective_id], array("notags")))) {
									$objective_text = $tmp_input;
								} else {
									$objective_text = false;
								}
								$PROCESSED["curriculum_objectives"][$objective_id] = $objective_text;
							}
						}
					}

					if((isset($_POST["name"])) && ($name = clean_input($_POST["name"], array("notags", "trim")))) {
						$PROCESSED["name"] = $name;
					} else {
						add_error("You must supply a valid <strong>Name</strong> for this assessment.");
					}

					$PROCESSED["cperiod_id"] = $cperiod_id;
					$PROCESSED["cohort"]=0;

					if((isset($_POST["grade_weighting"])) && ($_POST["grade_weighting"] !== NULL)) {
						$PROCESSED["grade_weighting"] = clean_input($_POST["grade_weighting"], "float");
					} else {
						add_error("You must supply a <strong>Grade Weighting</strong> for this assessment.");
					}

					if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
						$PROCESSED["description"] = $description;
					} else {
						$PROCESSED["description"] = "";
					}

					if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("trim")))) {
						if((@in_array($type, $ASSESSMENT_TYPES))) {
							$PROCESSED["type"] = $type;
						} else {
							add_error("You must supply a valid <strong>Type</strong> for this assessment. The submitted type is invalid.");
						}
					} else {
						add_error("You must pick a valid <strong>Type</strong> for this assessment.");
					}

					if((isset($_POST["marking_scheme_id"])) && ($marking_scheme_id = clean_input($_POST["marking_scheme_id"], array("trim","int")))) {
						if (@in_array($marking_scheme_id, $MARKING_SCHEME_IDS)) {
							$PROCESSED["marking_scheme_id"] = $marking_scheme_id;
						} else {
							add_error("The <strong>Marking Scheme</strong> you selected does not exist or is not enabled.");
						}
					} else {
						add_error("The <strong>Marking Scheme</strong> field is a required field.");
					}

					$due_date = validate_calendars("due", false, false,true);
					if ((isset($due_date["finish"])) && ((int) $due_date["finish"])) {
						$PROCESSED["due_date"] = (int) $due_date["finish"];
					} else {
						$PROCESSED["due_date"] = 0;
					}

					//Show in learner gradebook check
					if ((isset($_POST["show_learner_option"]))) {
						switch ($show_learner_option = clean_input($_POST["show_learner_option"], array("trim", "int"))) {
							case 0 :
								$PROCESSED["show_learner"] = $show_learner_option;
								$PROCESSED["release_date"] = 0;
								$PROCESSED["release_until"] = 0;
							break;
							case 1 :
								$PROCESSED["show_learner"] = $show_learner_option;
								$release_dates = validate_calendars("show", false, false);
								if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
									$PROCESSED["release_date"]	= (int) $release_dates["start"];
								} else {
									$PROCESSED["release_date"]	= 0;
								}
								if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
									$PROCESSED["release_until"]	= (int) $release_dates["finish"];
								} else {
									$PROCESSED["release_until"]	= 0;
								}
							break;
							default :
								$PROCESSED["show_learner"] = 0;
							break;
						}
					}

                    // narrative check
					if ((isset($_POST["narrative_assessment"])) && ($narrative = clean_input($_POST["narrative_assessment"], array("trim", "int")))) {
						$PROCESSED["narrative"] = $narrative;
					} else {
						$PROCESSED["narrative"] = 0;
					}

                    // Self assessment check
                    if ((isset($_POST["self_assessment"])) && ($narrative = clean_input($_POST["self_assessment"], array("trim", "int")))) {
                        $PROCESSED["self_assessment"] = $narrative;
                    } else {
                        $PROCESSED["self_assessment"] = 0;
                    }

                    if ((isset($_POST["group_assessment"])) && ($tmp_input = clean_input($_POST["group_assessment"], array("trim", "int")))) {
						$PROCESSED["group_assessment"] = $tmp_input;
					} else {
						$PROCESSED["group_assessment"] = 0;
					}

					if (isset($_POST["event_id"]) && $tmp_input = clean_input($_POST["event_id"], array("trim", "int"))) {
						$PROCESSED["event_id"] = $tmp_input;
						$event = Models_Event::get($PROCESSED["event_id"]);
					} else {
						$event = false;
					}

					//optional/required check
					$PROCESSED["required"] = isset($_POST["assessment_required"]) ? (clean_input($_POST["assessment_required"], array("trim", "int"))) : 0;

					// characteristic check
					if ((isset($_POST["assessment_characteristic"])) && ($assessment_characteristic = clean_input($_POST["assessment_characteristic"], array("trim", "int"))) == 0) {
						add_error("The <strong>Assessment Characteristic</strong> field is a required field.");
					} else if ((isset($_POST["assessment_characteristic"])) && ($assessment_characteristic = clean_input($_POST["assessment_characteristic"], array("trim", "int")))) {
						$PROCESSED["characteristic_id"] = $assessment_characteristic;
					}

					//extended options check
					if ((is_array($_POST["option"])) && (count($_POST["option"]))) {
						$assessment_options_selected = array();
						foreach ($_POST["option"] as $option_id) {
							if ($option_id = (int) $option_id) {
								$query = "SELECT * FROM `assessments_lu_meta_options` 
										  WHERE id = " .$db->qstr($option_id)."
										  AND `active` = '1'";
								$results = $db->GetAll($query);
								if ($results) {
									$assessment_options_selected[] = $option_id;
								}
							}
						}
					}
					// Sometimes requried field "number grade points total". Specifies what a numeric marking scheme assessment is "out of".
					// Only required when marking scheme is numeric, ID 3, hardcoded.
					if ((isset($_POST["numeric_grade_points_total"])) && ($points_total = clean_input($_POST["numeric_grade_points_total"], array("notags", "trim")))) {
						$PROCESSED["numeric_grade_points_total"] = $points_total;
					} else {
						$PROCESSED["numeric_grade_points_total"] = "";
						if(isset($PROCESSED["marking_scheme_id"])) {
							// Numberic marking scheme, hardcoded, lame
							if($PROCESSED["marking_scheme_id"] == 3) {
								add_error("The <strong>Maximum Points</strong> field is a required field when using the <strong>Numeric</strong> marking scheme.");
							}
						}
					}
					if (isset($_POST["post_action"])) {
						if(@in_array($_POST["post_action"], array("new", "index", "parent", "grade"))) {
							$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = $_POST["post_action"];
						} else {
							$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
						}
					} else {
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					}

					// Published 
					if ((isset($_POST["published"])) && ($published = clean_input($_POST["published"], array("trim", "int")))) {
						$PROCESSED["published"] = $published;
					} else {
						$PROCESSED["published"] = 1;
					}

                    // Notify
                    if ((isset($_POST["notify_threshold"])) && ($notify_threshold = clean_input($_POST["notify_threshold"], array("trim", "int")))) {
                        $PROCESSED["notify_threshold"] = $notify_threshold;
                    } else {
                        $PROCESSED["notify_threshold"] = 0;
                    }

                    if (isset($PROCESSED["notify_threshold"]) && $PROCESSED["notify_threshold"]) {
                        if ((isset($_POST["grade_threshold"])) && ($_POST["grade_threshold"] !== NULL) && ($tmp = clean_input($_POST["grade_threshold"], "float"))) {
                            $PROCESSED["grade_threshold"] = $tmp;
                        } else {
                            add_error("You must supply a valid <strong>Grade Threshold</strong> for this assessment.");
                        }
                    }

                    // Who to notify for grade threshold violations
                    if (isset($PROCESSED["notify_threshold"]) && $PROCESSED["notify_threshold"]) {
                        // process the notification info
                        $PROCESSED["notify_list"] = array();
                        if (isset($_POST["as_grade_threshold"]) && is_array($_POST["as_grade_threshold"])) {
                            $notify_list = array();
                            foreach ($_POST["as_grade_threshold"] as $notify_input) {
                                $notify_list[] = clean_input($notify_input, array("trim", "int"));
                            }
                            $PROCESSED["notify_list"] = array_unique($notify_list, SORT_NUMERIC);
                        }
                    }

					// Quizzes processing
					if ((isset($_POST["show_quiz_option"])) && ($show_quiz_option = clean_input($_POST["show_quiz_option"], array("trim", "int")))) {
						$PROCESSED["show_quiz_option"] = $show_quiz_option;
					} else {
						$PROCESSED["show_quiz_option"] = 0;
					}

					if ($PROCESSED["show_quiz_option"]) {
						if (isset($_POST["quiz_ids"])) {
							$quiz_ids = $_POST["quiz_ids"];
							for ($i=0 ; $i<count($quiz_ids) ; $i++) {
								$quiz_ids[$i] = clean_input($quiz_ids[$i], array("trim", "int"));
							}
							$PROCESSED["quiz_ids"] = $quiz_ids;

							if (isset($_POST["question_ids"])) {
								$question_ids = $_POST["question_ids"];
								for ($i=0 ; $i<count($question_ids) ; $i++) {
									$question_ids[$i] = clean_input($question_ids[$i], array("trim", "int"));
								}
								$PROCESSED["question_ids"] = $question_ids;
							}
						}
					}

					// Sanitize weights
					if (isset($_POST["item-weights"])) {
						foreach($_POST["item-weights"] as $afelement_id => $weight) {
							$PROCESSED["item-weights"][clean_input($afelement_id, array("trim", "int"))] = clean_input($weight, array("trim", "float"));
						}
					}

					// Sanitize scores
					if (isset($_POST["item-scores"])) {
						foreach($_POST["item-scores"] as $iresponse_id => $score) {

							// Because (float) null = 0, we specifically set the value to null if the string is empty
							$sanitized_score = strlen($score) > 0 ? clean_input($score, array("trim", "float")) : null;

							$PROCESSED["item-scores"][clean_input($iresponse_id, array("trim", "int"))] = $sanitized_score;
						}
					}
					
					// Sanitize form id
					if ((isset($_POST["form_id"])) && ($form_id = clean_input($_POST["form_id"], array("trim", "int")))) {
						$PROCESSED["form_id"] = $form_id;
					}

					// Sanitize group ids
					if (isset($_POST["as_groups"])) {
						foreach($_POST["as_groups"] as $group) {
							$PROCESSED["groups"][] = clean_input($group, array("trim", "int"));
						}
					}

					if ($PROCESSED["group_assessment"]) {
						if (!isset($PROCESSED["groups"]) || empty($PROCESSED["groups"])) {
							add_error("You must select at least one <strong>Course Group</strong> for a Group Assessment.");
						}
					}

					if (!$ERROR) {
						$PROCESSED["order"]			= Models_Gradebook_Assessment::fetchNextOrder($COURSE_ID, $PROCESSED["cohort"]);
						$PROCESSED["created_date"]	= time();
						$PROCESSED["created_by"]	= $ENTRADA_USER->getID();
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
						$PROCESSED["course_id"]		= $COURSE_ID;
						$PROCESSED["active"]		= 1;

						$assessment = new Models_Gradebook_Assessment($PROCESSED);

						if ($assessment->insert()) {
							$ASSESSMENT_ID = $assessment->getAssessmentID();

							if ($ASSESSMENT_ID) {
								application_log("success", "Successfully added assessment ID [".$ASSESSMENT_ID."]");
							} else {
								application_log("error", "Unable to fetch the newly inserted assessment identifier for this assessment.");
							}

							/**
							 * Process Graders
							 */
							Models_Gradebook_Assessment_Graders::deleteByAssessment($ASSESSMENT_ID);

							if (isset($PROCESSED["graders"]) && is_array($PROCESSED["graders"])) {
								foreach ($PROCESSED["graders"] as $grader) {
									if (isset($PROCESSED["g_assignment_".$grader]) && is_array($PROCESSED["g_assignment_".$grader])) {
										foreach ($PROCESSED["g_assignment_".$grader] as $learner_id) {
											$usr = Models_User::fetchRowByID($learner_id);
											if ($usr) {
												$gradebook_grader = new Models_Gradebook_Assessment_Graders(array(
													"assessment_id" => $ASSESSMENT_ID,
													"proxy_id"      => $usr->getID(),
													"grader_proxy_id" => $grader
												));

												if (!$gradebook_grader->insert()) {
													add_error("There was an error when trying to insert a grader-learner association. The system administrator was informed of this error; please try again later.");
													break;
												}
											}
										}
									}
								}
							}

							if (isset($PROCESSED["event_id"])) {
								$assessment_event_array = array(
									"assessment_id" => $ASSESSMENT_ID,
									"event_id" => $PROCESSED["event_id"],
									"updated_by" => $PROCESSED["updated_by"],
									"updated_date" => $PROCESSED["updated_date"],
									"active" => 1
								);

								$assessment_event = new Models_Assessment_AssessmentEvent($assessment_event_array);

								if (!$assessment_event->insert()) {
									application_log("error", "Unable insert the attached learning event.Database said: ".$db->ErrorMsg());
								} else {
									application_log("success", "Successfully attached learning event [".$PROCESSED["event_id"]."] to assessment ID [".$ASSESSMENT_ID."]");
								}
							}

							if ((is_array($PROCESSED["clinical_presentations"])) && (count($PROCESSED["clinical_presentations"]))) {
								foreach ($PROCESSED["clinical_presentations"] as $objective_id) {
									if (!$db->AutoExecute("assessment_objectives", array("assessment_id" => $ASSESSMENT_ID, "objective_id" => $objective_id, "objective_type" => "clinical_presentation", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
										add_error("There was an error when trying to insert a &quot;clinical presentation&quot; into the system. System administrators have been informed of this error; please try again later.");
										application_log("error", "Unable to insert a new clinical presentation to the database when adding a new event. Database said: ".$db->ErrorMsg());
									}
								}
							}

							if ((is_array($PROCESSED["curriculum_objectives"]) && count($PROCESSED["curriculum_objectives"]))) {
								foreach ($PROCESSED["curriculum_objectives"] as $objective_key => $objective_text) {
									if (!$db->AutoExecute("assessment_objectives", array("assessment_id" => $ASSESSMENT_ID, "objective_id" => $objective_key, "objective_details" => $objective_text, "objective_type" => "curricular_objective", "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()), "INSERT")) {
										add_error("There was an error when trying to insert a &quot;clinical presentation&quot; into the system. System administrators have been informed of this error; please try again later.");
										application_log("error", "Unable to insert a new clinical presentation to the database when adding a new event. Database said: ".$db->ErrorMsg());
									}
								}
							}

							if ($assessment_options) {
								foreach ($assessment_options as $assessment_option) {
									$query = "SELECT * FROM `assessments`
									            WHERE `assessment_id`  = ".$db->qstr($ASSESSMENT_ID)."
									            AND `active` = '1'";
									$results = $db->GetRow($query);
									if ($results) {
										$PROCESSED["assessment_id"] = $results["assessment_id"];
										$PROCESSED["option_id"] = $assessment_option["id"];
										if (is_array($assessment_options_selected) && in_array($assessment_option["id"], $assessment_options_selected)) {
											$PROCESSED["option_active"] = 1;
										} else{
											$PROCESSED["option_active"] = 0;
										}
									}
									$db->AutoExecute("assessment_options", $PROCESSED, "INSERT");
								}
							}

							Models_Gradebook_Assessment_Notifications::addNotificationsToAssessment($PROCESSED["notify_list"], $ASSESSMENT_ID, $COURSE_ID, $PROCESSED["notify_threshold"]);

							// Quizzes
							if ($PROCESSED["show_quiz_option"]) {
								if (isset($PROCESSED["quiz_ids"]) && count($PROCESSED["quiz_ids"])) {
									if ($assessment->attachQuizzes($PROCESSED["quiz_ids"])) {
										if (isset($PROCESSED["question_ids"]) && count($PROCESSED["question_ids"])) {
											$assessment->attachQuizQuestion($question_ids, $PROCESSED["quiz_ids"]);
										}
									}
								}
							}

							// Weights
							if (isset($PROCESSED['item-weights'])) {

								$model_form_element = new Models_Gradebook_Assessment_Form_Element(array('assessment_id' => $ASSESSMENT_ID));
								$current_weights = $model_form_element->fetchAllByAssessmentID();

								// Get array of current weights already stored
								$current_weight_ids = array();

								foreach($current_weights as $form_element) {
									if (array_key_exists($form_element->getAfelementID(), $PROCESSED["item-weights"])) {
										$current_weight_ids[$form_element->getID()] = $form_element->getAfelementID();
									}
								}

								// update form weights
								foreach($PROCESSED['item-weights'] as $afelement_id => $weight) {
									$item_weight = new Models_Gradebook_Assessment_Form_Element(array('assessment_id' => $ASSESSMENT_ID, 'afelement_id' => $afelement_id, 'weight' => $weight));

									// If weight is already stored in db, update that value, otherwise create new entry
									$gafelement_id = array_search($item_weight->getAfelementID(), $current_weight_ids);

									if ($gafelement_id) {
										$item_weight->setID($gafelement_id);
										$item_weight->update();
									}
									else {
										$item_weight->insert();
									}
								}
							}

							// Scores
							if (isset($PROCESSED['item-scores'])) {

								$model_item_response = new Models_Gradebook_Assessment_Item_Response(array('assessment_id' => $ASSESSMENT_ID));
								$current_scores = $model_item_response->fetchAllByAssessmentID();

								// Get array of current weights already stored
								$current_score_ids = array();

								foreach($current_scores as $item_response) {
									if (array_key_exists($item_response->getIresponseID(), $PROCESSED["item-scores"])) {
										$current_score_ids[$item_response->getID()] = $item_response->getIresponseID();
									}
								}

								// update form scores
								foreach($PROCESSED['item-scores'] as $iresponse_id => $score) {

									$item_score = new Models_Gradebook_Assessment_Item_Response(array('assessment_id' => $ASSESSMENT_ID, 'iresponse_id' => $iresponse_id, 'score' => $score));

									// If score is already stored in db, update that value, otherwise create new entry
									$gairesponse_id = array_search($item_score->getIresponseID(), $current_score_ids);

									if ($gairesponse_id) {
										$item_score->setID($gairesponse_id);
										$item_score->update();
									}
									else {
										$item_score->insert();
									}
								}
							}

							if (isset($PROCESSED["groups"]) && is_array($PROCESSED["groups"])) {
								// Add new groups
								foreach($PROCESSED["groups"] as $cgroup_id) {
									$group = new Models_Assessment_Group(array("assessment_id" => $ASSESSMENT_ID, "cgroup_id" => $cgroup_id));
									$insert_new_group = $group->insert();
								}
							}

							switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
								case "grade" :
									$url = ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("step" => false, "section" => "grade", "assessment_id" => $ASSESSMENT_ID));
									$msg = "You will now be redirected to the <strong>Grade Assessment</strong> page for \"<strong>".$PROCESSED["name"] . "</strong>\"; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
								case "new" :
									$url = ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("step" => false, "section" => "add"));
									$msg = "You will now be redirected to another <strong>Add Assessment</strong> page for the ". $course_details["course_name"] . " gradebook; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
								case "parent" :
									$url = ENTRADA_URL."/admin/".$MODULE;
									$msg = "You will now be redirected to the <strong>Gradebook</strong> index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
								case "index" :
								default :
									$url = ENTRADA_URL."/admin/gradebook?".replace_query(array("step" => false, "section" => "view", "assessment_id" => false));
									$msg = "You will now be redirected to the <strong>assessment index</strong> page for ". $course_details["course_name"] . "; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
							}

							add_success($msg);
							$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
						} else {
							add_error("There was a problem inserting this assessment into the system. The administrators have been informed of this error; please try again later.");

							application_log("error", "There was an error inserting an assessment. Database said: ".$db->ErrorMsg());
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
					/**
					 * Fetch the Clinical Presentation details.
					 */
					$clinical_presentations_list = array();
					$clinical_presentations = array();

					$results = fetch_clinical_presentations();
					if ($results) {
						foreach ($results as $result) {
							$clinical_presentations_list[$result["objective_id"]] = $result["objective_name"];
						}
					} else {
						$clinical_presentations_list = false;
					}

					if (isset($_POST["clinical_presentations_submit"]) && $_POST["clinical_presentations_submit"]) {
						if (((isset($_POST["clinical_presentations"])) && (is_array($_POST["clinical_presentations"])) && (count($_POST["clinical_presentations"])))) {
							foreach ($_POST["clinical_presentations"] as $objective_id) {
								if ($objective_id = clean_input($objective_id, array("trim", "int"))) {
									$query	= "SELECT a.`objective_id`
												FROM `global_lu_objectives` AS a
												JOIN `course_objectives` AS b
												ON b.`course_id` = ".$COURSE_ID."
                                                AND a.`objective_id` = b.`objective_id`
                                                AND b.`active` = '1'
												JOIN `objective_organisation` AS c
												ON a.`objective_id` = c.`objective_id`
												WHERE a.`objective_id` = ".$db->qstr($objective_id)."
												AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
												AND b.`objective_type` = 'event'
												AND a.`objective_active` = '1'";
									$result	= $db->GetRow($query);
									if ($result) {
										$clinical_presentations[$objective_id] = $clinical_presentations_list[$objective_id];
									}
								}
							}
						} else {
							$clinical_presentations = array();
						}
					} else {
						$query	 = "SELECT `objective_id`
									FROM `course_objectives`
									WHERE `course_id` = ".$COURSE_ID."
									AND `objective_type` = 'event'
                                    AND `active` = '1'";
						$results = $db->GetAll($query);
						if ($results) {
							foreach ($results as $result) {
								$clinical_presentations[$result["objective_id"]] = $clinical_presentations_list[$result["objective_id"]];
							}
						}
					}

					/**
					 * Fetch the Curriculum Objective details.
					 */
					list($curriculum_objectives_list, $top_level_id) = courses_fetch_objectives($ENTRADA_USER->getActiveOrganisation(), array($COURSE_ID), -1, 1, false, false, 0, true);

					$curriculum_objectives = array();

					if (isset($_POST["checked_objectives"]) && ($checked_objectives = $_POST["checked_objectives"]) && (is_array($checked_objectives))) {
						foreach ($checked_objectives as $objective_id => $status) {
							if ($objective_id = (int) $objective_id) {
								if (isset($_POST["objective_text"][$objective_id]) && ($tmp_input = clean_input($_POST["objective_text"][$objective_id], array("notags")))) {
									$objective_text = $tmp_input;
								} else {
									$objective_text = false;
								}

								$curriculum_objectives[$objective_id] = $objective_text;
							}
						}
					}

					// Generate page header
					$page_title = $translate->_("Add Assessment");
					$page_header = new Views_Gradebook_PageHeader(array("course" => $course, "module" => "gradebook", "page_title" => $page_title));
					$page_header->render();
					?>
				<div class="row-fluid">
					<div class="span6">
						<h1>Add Assessment</h1>
					</div>
					<div class="span6">
						<?php (isset($period_selector) ? $period_selector->render() : "") ?>
					</div>
				</div>
					<?php
					if ($ERROR) {
						echo display_error();
					}
					?>
					<form id="assessment-form" action="<?php echo ENTRADA_URL; ?>/admin/gradebook/assessments?<?php echo replace_query(array("step" => 2)); ?>" method="post" class="form-horizontal">
						<h2 title="Assessment Details Section">Assessment Details</h2>
						<div id="assessment-details-section">
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding a Gradebook Assessment">
								<colgroup>
									<col style="width: 3%" />
									<col style="width: 23%" />
									<col style="width: 74%" />
								</colgroup>
								<tbody>
								<tr>
									<td></td>
									<td><label for="name" class="form-required">Assessment Name:</label></td>
									<td><input type="text" id="name" name="name" value="<?php echo html_encode($PROCESSED["name"]); ?>" maxlength="255" class="span11" /></td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td style="vertical-align: top"><label for="description" class="form-nrequired">Assessment Description:</label></td>
									<td><textarea id="description" name="description" class="expandable span11"><?php echo html_encode($PROCESSED["description"]); ?></textarea></td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td></td>
									<td><label for="grade_weighting" class="form-nrequired">Assessment Weighting</label></td>
									<td>
										<input type="text" id="grade_weighting" name="grade_weighting" value="<?php echo (float) html_encode($PROCESSED["grade_weighting"]); ?>" maxlength="5" class="input-mini" autocomplete="off" />
                                        <span>%</span>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<label for="notify_threshold" class="checkbox form-nrequired">
											<input id="notify_threshold" name="notify_threshold" type="checkbox" value="1"<?php echo ($PROCESSED["notify_threshold"]) ? " checked=\"checked\"" : "";?>>Notify if grade is below :
										</label>
									</td>
									<td id="as_grade_threshold_search_container">
                                        <input type="text" id="grade_threshold" <?php echo (!$PROCESSED["notify_threshold"]) ? "disabled=\"disabled\"" : ""; ?> name="grade_threshold" value="<?php echo (float) html_encode($PROCESSED["grade_threshold"]); ?>" maxlength="5" class="input-mini" autocomplete="off" />
                                        <div id="div_threshold_notify_list" class="hide">
                                            <?php
                                            if (isset($PROCESSED["notify_list"]) && is_array($PROCESSED["notify_list"]) && count($PROCESSED["notify_list"]) ) {
                                                foreach( $PROCESSED["notify_list"] as $notify ) {
                                                    ?>
                                                    <input type="hidden" id="as_grade_threshold_<?php echo $notify;?>" name="as_grade_threshold[]" value="<?php echo $notify;?>" />
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                        <button id="as_grade_threshold_notify" class="btn" <?php echo (!$PROCESSED["notify_threshold"]) ? "disabled=\"disabled\"" : ""; ?>>
											<?php echo $translate->_("Select Who Gets Notified"); ?>&nbsp;
											<i class="icon-chevron-down btn-icon pull-right"></i>
										</button>
									</td>
								</tr>
								<tr id="threshold-notify-list-tr" class="hide">
									<td colspan="3" style="padding-bottom: 20px;">
										<div id="threshold_notify_list_container" >
											<h3 style="line-height: 15px;"><?php echo $translate->_("Notify");?></h3>
											<table id="notify-list-table"></table>
										</div>
                                        <div id="threshold_notify_list_inputs">
                                            <?php
                                            if (isset($PROCESSED["notify_list"]) && is_array($PROCESSED["notify_list"]) && count($PROCESSED["notify_list"]) ) {
                                                foreach( $PROCESSED["notify_list"] as $notify ) {
                                                    ?>
                                                    <input type="hidden" name="as_grade_threshold[]" value="<?php echo $notify;?>" id="as_grade_threshold_<?php echo $notify;?>"  />
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
									</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="3" id="assessment-due-date-td">
										<table>
											<?php echo generate_calendars("due", "Assessment Due Date", false, false, 0, true, false, ((isset($PROCESSED["due_date"])) ? $PROCESSED["due_date"] : 0), true, false, "", ""); ?>
										</table>
									</td>
								</tr>
								</tbody>
								<tbody id="assessment_required_options">
								<tr>
									<td colspan="3" style="padding-top: 20px">
										<label class="checkbox form-nrequired" for="assessment_required">
											<input type="checkbox" id="assessment_required" name="assessment_required" value="1" <?php echo (($PROCESSED["required"])) ? " checked=\"checked\"" : "" ?>>Learners are required to complete this assessment.
										</label>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<label class="checkbox form-nrequired" for="show_quiz_option">
											<input type="checkbox" id="show_quiz_option" name="show_quiz_option" value="1" <?php echo (($PROCESSED["show_quiz_option"] == 1)) ? " checked=\"checked\"" : "" ?>>Link existing online quizzes to this assessment.
										</label>
									</td>
								</tr>

								<tr id="quizzes_wrapper"<?php echo (($PROCESSED["show_quiz_option"] != 1)) ? " class=\"hide\"" : ""; ?>>
									<td colspan="3" style="padding-top: 10px">
										<a id="attach-quiz-button" href="#quiz-modal" class="btn btn-primary space-below" role="button" data-toggle="modal">
											<i class="icon-plus icon-white"></i> Attach Quiz
										</a>
										<table class="tableList accordion" cellspacing="0" summary="List of Attached Quizzes" id="quiz_list">
											<colgroup>
												<col class="modified" />
												<col class="title" />
												<col class="general" />
												<col class="modified" />
											</colgroup>
											<thead>
											<tr>
												<td class="modified">&nbsp;</td>
												<td class="title"><span>Quiz Title</span></td>
												<td class="general"><span>Quiz Questions</span></td>
												<td class="modified">&nbsp;</td>
											</tr>
											</thead>
											
										</table>
									</td>
								</tr>
								</tbody>
								<tbody>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td></td>
									<td><label for="assessment_characteristic" class="form-required">Characteristic:</label></td>
									<td>
										<select id="assessment_characteristic" name="assessment_characteristic" class="span8">
											<option value="">-- Select Assessment Characteristic --</option>
											<?php
											$query = "	SELECT *
                                                            FROM `assessments_lu_meta`
                                                            WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                                            AND `active` = '1'
                                                            ORDER BY `type` ASC, `title` ASC";
											$assessment_characteristics = $db->GetAll($query);
											if ($assessment_characteristics) {
												$type = "";
												foreach ($assessment_characteristics as $key => $characteristic) {
													if ($type != $characteristic["type"]) {
														if ($key) {
															echo "</optgroup>";
														}
														echo "<optgroup label=\"".ucwords(strtolower($characteristic["type"]))."s\">";

														$type = $characteristic["type"];
													}

													echo "<option value=\"" . $characteristic["id"] . "\" assessmenttype=\"" . $characteristic["type"] . "\"".(($PROCESSED["characteristic_id"] == $characteristic["id"]) ? " selected=\"selected\"" : "").">".$characteristic["title"]."</option>";
													if ($PROCESSED["characteristic_id"] == $characteristic["id"]) {
														$current_type = $characteristic["type"];
													}
												}
												echo "</optgroup>";
											}
											?>
										</select>
									</td>
								</tr>
								</tbody>
								<tbody id="assessment_options" style="display:none;">
								<tr>
									<td></td>
									<td style="vertical-align: top;"><label for="extended_option1" class="form-nrequired">Extended Options:</label></td>
									<td class="options"></td>
								</tr>
								</tbody>
								<tbody>
								<tr>
									<td></td>
									<td><label for="marking_scheme_id" class="form-required">Marking Scheme:</label></td>
									<td>
										<select id="marking_scheme_id" name="marking_scheme_id">
											<?php
											foreach ($MARKING_SCHEMES as $scheme) {
												echo "<option value=\"".$scheme["id"]."\"".(($PROCESSED["marking_scheme_id"] == $scheme["id"]) ? " selected=\"selected\"" : "").">".$scheme["name"]."</option>";
											}
											?>
										</select>
									</td>
								</tr>
								<tr id="numeric_marking_scheme_details" style="display: none;">
									<td></td>
									<td><label for="numeric_grade_points_total" class="form-required">Maximum Points:</label></td>
									<td>
										<input type="text" id="numeric_grade_points_total" name="numeric_grade_points_total" value="<?php echo html_encode($PROCESSED["numeric_grade_points_total"]); ?>" maxlength="5" style="width: 50px" />
										<span class="content-small"><strong>Tip:</strong> Maximum points possible for this assessment (i.e. <strong>20</strong> for &quot;X out of 20).</span>
									</td>
								</tr>
								<tr>
									<td></td>
									<td><label for="type" class="form-required">Assessment Type:</label></td>
									<td>
										<select id="type" name="type">
											<?php
											foreach ($ASSESSMENT_TYPES as $type) {
												echo "<option value=\"".$type."\"".(($PROCESSED["type"] == $type) ? " selected=\"selected\"" : "").">".$type."</option>";
											}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="3">
										<label class="checkbox form-nrequired" for="narrative_assessment">
											<input type="checkbox" id="narrative_assessment" name="narrative_assessment" value="1" <?php echo (($PROCESSED["narrative"] == 1)) ? " checked=\"checked\"" : ""?> /> This is a <strong>narrative assessment</strong>.
										</label>
									</td>
								</tr>
                                <tr>
                                    <td colspan="3">
                                        <label class="checkbox form-nrequired" for="self_assessment">
                                            <input type="checkbox" id="self_assessment" name="self_assessment" value="1" <?php echo (($PROCESSED["self_assessment"] == 1)) ? " checked=\"checked\"" : ""?> /> This is a <strong>self assessment</strong>.
                                        </label>
                                    </td>
                                </tr>
                                <tr>
									<td colspan="3">
										<label class="checkbox form-nrequired" for="group_assessment">
											<input type="checkbox" id="group_assessment" name="group_assessment" value="1" <?php echo (($PROCESSED["group_assessment"] == 1)) ? " checked=\"checked\"" : ""?> />
											<?php echo $translate->_("This is a "); ?><strong><?php echo $translate->_("group assessment"); ?></strong>.
										</label>
										<button id="as_groups" class="btn <?php echo (!$PROCESSED["group_assessment"]) ? "hide" : ""; ?>" <?php echo (!$PROCESSED["group_assessment"]) ? "disabled=\"disabled\"" : ""; ?>>
											<?php echo $translate->_("Select Groups"); ?>&nbsp;
											<i class="icon-chevron-down btn-icon pull-right"></i>
										</button>
									</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td colspan="3">
										<label class="radio form-nrequired" for="show_learner_option_0">
											<input type="radio" name="show_learner_option" value="0" id="show_learner_option_0" <?php echo (($PROCESSED["show_learner"] == 0)) ? " checked=\"checked\"" : "" ?> /> Don't Show this Assessment in Learner Gradebook
										</label>
										<label class="radio form-nrequired" for="show_learner_option_1">
											<input type="radio" name="show_learner_option" value="1" id="show_learner_option_1" <?php echo (($PROCESSED["show_learner"] == 1)) ? " checked=\"checked\"" : "" ?> /> Show this Assessment in Learner Gradebook
										</label>
									</td>
								</tr>
								</tbody>
								<tbody id="gradebook_release_options" style="display: none;">
								<?php echo generate_calendars("show", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0), true, false, " grades starting", " grades until"); ?>
								</tbody>
							</table>
						</div>

						<script type="text/javascript" charset="utf-8">
							var course_id = "<?php echo $COURSE_ID ?>";
                            var by_assessment_id = "<?php echo $ASSESSMENT_ID; ?>";
							var cperiod_id = "<?php echo $cperiod_id; ?>";

							<?php
							if (isset($PROCESSED["question_ids"]) && count($PROCESSED["question_ids"])) {
								echo "var qq_ids = [" . implode(",", $PROCESSED["question_ids"]) . "];";
							}
							?>

							function getAssessmentFormsByTitle (title) {
									cperiod_id = jQuery("#selector-select-period").val();
								jQuery.ajax({
									url: "<?php echo ENTRADA_URL; ?>/admin/assessments/forms/",
									data: "section=api-forms&method=get-forms&search_term=" + title + "&date_format=list",
									type: "GET",
									beforeSend: function () {
										jQuery("#assessment-form-search-msgs").empty();
										jQuery("#assessment-form-search-list").empty();
										jQuery("#assessment-form-loading").removeClass("hide");
									},
									success: function(data) {
										jQuery("#assessment-form-loading").addClass("hide");
										var response = JSON.parse(data);

										if (response.data) {
											jQuery.each(response.data.forms, function (key, assessmentForm) {
												buildAssessmentFormList(assessmentForm);
											});
										} else {
											display_notice(response.data, "#assessment-form-search-msgs", "append");
										}
									},
									error: function () {
										jQuery("#assessment-form-loading").addClass("hide");
									}
								});
							}

							jQuery(document).ready(function($){
								if (jQuery("input[name='show_learner_option']:checked").val() == 1) {
									jQuery('#gradebook_release_options').show();
								}
								else if (jQuery("input[name='show_learner_option']:checked").val() == 0) {
									jQuery('#gradebook_release_options').hide();
								}

                                // Custom rounding function
                                function roundDecimal(value, decimals) {
                                    return Number(Math.round(value + 'e' + decimals) + 'e-' + decimals);
                                }
								// Get total weight
								function getTotalWeight() {
            						var weights = $(".input-weight");
                					var totalWeight = 0;

                					weights.map(function() {
                						totalWeight += parseFloat($(this).val() || 0)
                					})

                                    return roundDecimal(totalWeight, 4);
            					}

            					// Update total weight
            					function updateTotalWeight() {
            						$(".input-weight").on("change input", function(e) {
                    					$(".cell-weight").removeClass("error");
                    					$(".alert-weight").addClass("hide");

                    					$("#weight-total-number").text(getTotalWeight());
                    				});
            					}

            					function submitCheckScores() {
            						$("#assessment-form").on("submit", function(e) {
                    					var form = this;
                    					
                    					if (getTotalWeight() === 100) {
                        					var $scores = $(".input-score");

                        					$scores.each(function(i) {
                        						var hasEmptyScores = false;

                        						if ($(this).val().length === 0) {
                        							$(this).closest(".control-group").addClass("error");
                        							hasEmptyScores = true;
                        						}

                        						// Display alert-score and prevent submission if empty scores are found
                        						if (hasEmptyScores) {
                        							e.preventDefault();
                        							$(".alert-score").removeClass("hide");
                        						}
                        					});
                    					}
                    					else {
                    						// Make all weights red if they don't add up to 100, and prevent submission
                    						e.preventDefault();
                    						$(".cell-weight").addClass("error");
                    						$(".alert-weight").removeClass("hide");
                    					}
                    				});
            					}

            					function removeScoreErrorsOnInput() {
            						$(".input-score").on("change input", function(e) {
                    					$(this).closest(".control-group").removeClass("error");
                    					$(".alert-score").addClass("hide");
                    				});
            					}

								// Attach assessment form
								$(".btn-attach-assessment-form").on("click", function(e) {
                                	var chosenForm = $("#assessment-form-search-list").children(".active");

                                	if (chosenForm) {
                                		var formId = chosenForm.attr('data-id');
                                		formId = parseInt(formId);

                                		// checks if the id is higher than zero after parsing it as integer
                                		if (formId > 0) {
                            				// load html from api
                            				$('#form').load(ENTRADA_URL + "/admin/assessments/forms?section=api-forms&method=get-rendered-form&form_id=" + formId + "&edit_weights=true&edit_scores=true&edit_comments=false", function(html) {
                            					var $this = $(this);

                            					$('.datepicker', $this).datepicker({
                            						dateFormat: 'yy-mm-dd'
                            					});

                            					// set form id value
                            					$("#form-id").val(formId);

                            					// Add new total weight and show floating weight bar
                            					$("#weight-total-number").text(getTotalWeight()).removeClass("hide");
                            					$('#weight-total').removeClass("hide");

                            					// Close modal and hide attach button
                                				$("#modal-attach-assessment-form").modal("hide");
                                				$('#btn-attach-assessment-form-modal').hide();
                                				$('#btn-remove-assessment-form').show();

                                				// Add submit handler for checking weights and scores
                                				submitCheckScores()

                                				// Hide weight errors upon change/input
                                				updateTotalWeight()

                                				// Hide score errors upon change/input
                                				removeScoreErrorsOnInput()
                            				});
                                		}
                                	}
                                });

								$('#btn-remove-assessment-form').on('click', function(e) {
									e.preventDefault();
                            		
									$.post(ENTRADA_URL + "/admin/assessments/forms", {
                        				section: 'api-forms',
                        				method: 'remove-form-from-assessment',
                        				assessment_id: <?php echo $ASSESSMENT_ID; ?>
                        			})
                        			.done(function(e) {
                        				// remove form from page
                        				$('#form').empty();

                        				// show and hide the right buttons
                        				$('#btn-attach-assessment-form-modal').show();
                        				$('#btn-remove-assessment-form').hide();

                        				// remove submit handler
                        				$("#assessment-form").off("submit");

                        				// Remove floating weight total, and any alerts
                        				$('#weight-total, .alert-score, .alert-weight').addClass("hide");
                        			})
								});

								// Enable datepicker
								$('.datepicker').datepicker({
									dateFormat: 'yy-mm-dd',
								});

								$("#modal-attach-assessment-form").on("hide", function () {
									if ($("#assessment-form-search-list").children().hasClass("active")) {
										$("#assessment-form-search-list").children().removeClass("active");
									}
									$("#assessment-form-search-list").empty();
									$("#assessment-form-title-search").val("");
								});
							});

							jQuery(function($) {
								//var search_type = "<?php echo (isset($PREFERENCES["assessment-event-search"]) ? $PREFERENCES["assessment-event-search"] : "calendar"); ?>";
								var timer;
								var done_interval = 600;

								

								<?php if ($ERROR && isset($assessment_options_selected) && $assessment_options_selected) { ?>
								var selected_options;
								selected_options = JSON.parse("<?php echo json_encode($assessment_options_selected); ?>");
								fetchOptions(jQuery('#assessment_characteristic'), selected_options);
								<?php } ?>

							});

							function buildAttachedEventList () {
								var event_title = jQuery("#assessment-event").attr("data-title");
								var event_date = jQuery("#assessment-event").attr("data-date");
								var event_li = document.createElement("li");
								var event_div = document.createElement("div");
								var remove_icon_div = document.createElement("div");
								var event_h3 = document.createElement("h3");
								var event_span = document.createElement("span");
								var remove_icon_span = document.createElement("span");
								var event_remove_icon = document.createElement("i");



								jQuery(remove_icon_span).attr({id: "remove-attched-assessment-event"}).addClass("btn btn-danger");
								jQuery(event_remove_icon).addClass("icon-remove icon-white");
								jQuery(remove_icon_span).append(event_remove_icon).append('&nbsp;<?php echo $translate->_('Remove Event') ?>');
								jQuery(remove_icon_div).append(remove_icon_span).attr({id: "remove-attched-assessment-event-div"});
								jQuery(event_h3).addClass("event-text").text(event_title).html();
								jQuery(event_span).addClass("event-text").addClass("muted").text(event_date).html();
								jQuery(event_div).append(event_h3).append(event_span);
								jQuery(event_li).append(event_div);
								jQuery(event_li).append(event_div).append(remove_icon_div);
								jQuery("#attached-event-list").empty();
								jQuery("#attached-event-list").append(event_li);
							}
						</script>

						<h2 title="Assessment Event Section" class="collapsable expanded"><?php echo $translate->_('Assessment Event') ?></h2>
						<div id="assessment-event-section">

							<a id="attach-event-button" href="#event-modal" class="btn btn-primary space-below" role="button" data-toggle="modal">
								<i class="icon-plus icon-white"></i> Attach Learning Event
							</a>
							<ul id="attached-event-list">
								<li>

								</li>
							</ul>
						</div>
						<div class="clearfix"></div>

                        <h2 class="subheading" title="Assessment Graders Section" class="collapsable expanded"><?php echo $translate->_('Assessment Graders') ?></h2>
                        <div id="assessment-graders-section">
                            <!-- Graders section -->
                            <script type="text/javascript">
                                var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>';
                                var COURSE_ID = '<?php echo $COURSE_ID; ?>';
                                var CPERIOD_ID = '<?php echo $cperiod_id; ?>';
                                var ASSIGN_TO_GRADER_TEXT = '<?php echo $translate->_("Assign to Grader"); ?>';
                                var ASSESSMENT_ID = '0';
                            </script>
                            <?php
                            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/gradebook/graders.js\"></script>\n";
                            ?>
                            <div class="row-fluid">
                                <div class="span6">
                                    <h3>Graders</h3>
                                    <div class="row-fluid">
                                        <div class="span10">
                                            <input class="search-icon input-large" type="text" id="grader_name" name="fullname" size="30" autocomplete="off" style="width: 97%; vertical-align: middle" onkeyup="checkItem('grader')" />
                                            <div class="ui-autocomplete" id="grader_name_auto_complete"></div>
                                            <input type="hidden" id="associated_grader" name="associated_grader" />
                                        </div>
                                        <div class="pull-right">
                                            <input type="button" class="btn pull-right" onclick="addGrader('grader');" value="Add" style="vertical-align: middle" />
                                            <input type="hidden" id="grader_ref" name="grader_ref" value="" />
                                            <input type="hidden" id="grader_id" name="grader_id" value="" />
                                        </div>
                                    </div>

                                    <div id="graders-assignments-container" class="hide">
                                        <?php
                                        if (isset($PROCESSED["graders"]) && is_array($PROCESSED["graders"])) {
                                            foreach ($PROCESSED["graders"] as $grader) {
                                                if (isset($PROCESSED["g_assignment_".$grader]) && is_array($PROCESSED["g_assignment_".$grader])) {
                                                    foreach ($PROCESSED["g_assignment_".$grader] as $learner) {
                                                        ?>
                                                        <input type="hidden" name="g_assignment_<?php echo $grader; ?>[]" value="<?php echo $learner; ?>" />
                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </div>

                                    <div class="clearfix" style="margin-bottom: 20px;"></div>

                                    <!-- Graders to learners table -->
                                    <div style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                                        <table id="table-graders-to-learners" class="table table-bordered table-striped" style="margin-bottom: 0;">
                                            <thead>
                                            <tr>
                                                <th>Grader</th>
                                                <th>Assigned Learners</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            if (isset($PROCESSED["graders"]) && is_array($PROCESSED["graders"])) {
                                                foreach ($PROCESSED["graders"] as $grader) {
                                                    $grader_user = Models_User::fetchRowByID($grader);
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <label for="grader_<?php echo $grader; ?>"><input id="grader_<?php echo $grader; ?>" name="chk_graders[]" value="<?php echo $grader; ?>" type="checkbox" data-name="<?php echo $grader_user->getFullname();?>" > <?php echo $grader_user->getFullname();?></label>
                                                            <input type="hidden" name="graders[]" value="<?php echo $grader; ?>">
                                                        </td>
                                                        <td id="td-graders-to-learner-<?php echo $grader; ?>" class="td-graders-to-learner">
                                                            <?php
                                                            if (isset($PROCESSED["g_assignment_".$grader]) && is_array($PROCESSED["g_assignment_".$grader])) {
                                                                foreach ($PROCESSED["g_assignment_".$grader] as $learner) {
                                                                    $learner_user = Models_User::fetchRowByID($learner); ?>
                                                                    <div style="margin-bottom: 10px;" data-id="<?php echo $learner; ?>" data-name="<?php echo $learner_user->getFullname();?>">
                                                                        <?php echo $learner_user->getFullname();?><img id="remove-learner-<?php echo $learner; ?>" src="/images/action-delete.gif" class="remove-learner pull-right" style="cursor: pointer;" data-id="<?php echo $learner; ?>" data-grader="<?php echo $grader; ?>">
                                                                    </div>
                                                                    <?php
                                                                }
                                                            } else { echo "<i>No learners assigned</i>"; }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Remove learner button -->
                                    <a id="btn-remove-graders" href="#modal-remove-grader" class="btn btn-default btn-sm" data-toggle="modal">Remove Selected Graders</a>

                                    <div class="clearfix" style="margin-bottom: 20px;"></div>
                                </div>
                                <div class="span6">
                                    <h3>Learners</h3>
                                    <button id="randomly-distribute-learners" class="btn btn-primary"><span class="icon-random icon-white"></span> Randomly Distribute Learners to Graders</button>

                                    <div class="clearfix" style="margin-bottom: 20px;"></div>

                                    <!-- Groups  table -->
                                    <div id="group-learner-table" class="<?php echo (!$assessment_details["group_assessment"]) ? "hide" : "learner-table"; ?>" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;"">
                                        <table id="table-groups-no-grader" class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th><label for="all-groups">
                                                    <input id="all-groups" value="1" type="checkbox"> Group</label>
                                                </th>
                                                <th>Assign Group</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Learners without graders table -->
                                    <div id="individual-learner-table" class="<?php echo ($assessment_details["group_assessment"]) ? "hide" : "learner-table"; ?>" style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                                        <table id="table-learners-no-grader" class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th><label for="all-learners"><input id="all-learners" value="1" type="checkbox"> Learner</label></th>
                                                <th>Assign Grader</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>

                                    <a id="btn-assign-learner" href="#assign-grader-modal" class="btn btn-default btn-sm">Assign Selected to Grader</a>
                                    <div class="clearfix" style="margin-bottom: 20px;"></div>
                                </div>
                            </div>

                            <?php
                            $modal_body = "<div class=\"alert hide\"></div>
                                <table id=\"table-assign-grader-modal\" class=\"table table-bordered table-striped\">
                                    <thead>
                                    <tr>
                                        <th>Grader</th>
                                    </tr>
                                    </thead>
                                    <tbody>
    
                                    </tbody>
                                </table>";

                            $modal_attach_assessment = new Views_Gradebook_Assignments_Modal(array(
                                "id" => "assign-grader-modal",
                                "title" => $translate->_("Assign Learner to Grader"),
                                "body" => $modal_body,
                                "dismiss_button" => array(
                                    "text" => $translate->_("Close"),
                                    "class" => "btn btn-default btn-sm close-assign-grader-modal"
                                ),
                                "success_button" => array(
                                    "text" => $translate->_("Assign Learner"),
                                    "class" => "btn btn-primary btn-sm btn-modal-assign-learner"
                                )
                            ));

                            $modal_attach_assessment->render();

                            $modal_body = "<div class=\"alert hide\"></div>
                                <table id=\"table-modal-remove-grader\" class=\"table table-bordered table-striped\">
                                    <thead>
                                    <tr>
                                        <th>Grader</th>
                                        <th>Assigned Learners</th>
                                    </tr>
                                    </thead>
                                    <tbody>
    
                                    </tbody>
                                </table>";

                            $modal_remove_grader = new Views_Gradebook_Assignments_Modal(array(
                                "id" => "modal-remove-grader",
                                "title" => $translate->_("Remove Grader"),
                                "body" => $modal_body,
                                "dismiss_button" => array(
                                    "text" => $translate->_("Cancel"),
                                    "class" => "btn btn-default btn-sm close-modal-remove-grader"
                                ),
                                "success_button" => array(
                                    "text" => $translate->_("Remove Grader"),
                                    "class" => "btn btn-primary btn-sm btn-modal-remove-grader"
                                )
                            ));

                            $modal_remove_grader->render();
                            ?>
                            <!-- End of the Graders interface -->
                            <div class="gap30"></div>
                        </div>

                        <h2 title="Assessment Form Section" class="collapsable expanded"><?php echo $translate->_('Assessment Form') ?></h2>
                        <div id="assessment-form-section">
                            <?php
                            $weight_alert = new Views_Gradebook_Alert(array(
                                "class" => "alert-error alert-weight hide",
                                "text" => $translate->_("Weights must add up to 100%.")
                            ));

                            $weight_alert->render();

                            $score_alert = new Views_Gradebook_Alert(array(
                                "class" => "alert-error alert-score hide",
                                "text" => $translate->_("Scores inputs must all have a value.")
                            ));

                            $score_alert->render();
                            ?>

                            <input id="form-id" name="form_id" value="" type="hidden" />

                            <a href="#modal-attach-assessment-form" id="btn-attach-assessment-form-modal" data-toggle="modal" role="button" class="btn btn-primary" <?php if ($assessment_details['form_id']): ?>style="display:none;"<?php endif; ?>>
                                <i class="icon-plus icon-white"></i> <?php echo $translate->_('Attach Assessment Form'); ?>
                            </a>

                            <div id="form" class="form-container">
                                <?php if ($assessment_details['form_id']) {
                                    $form_model = new Models_Assessments_Form(array('form_id' => $assessment_details['form_id']));
                                    $results = $form_model->getCompleteFormData($assessment_details['assessment_id']);

                                    $form = new Views_Gradebook_Assessments_Form(array('data' => $results));
                                    $form->render();
                                } ?>
                            </div>

                            <?php
                            $weight_alert->render();
                            $score_alert->render();
                            ?>

                            <a href="#" id="btn-remove-assessment-form" role="button" class="btn btn-danger" <?php if (!$assessment_details['form_id']): ?>style="display:none;"<?php endif; ?>>
                                <i class="icon-remove icon-white"></i> <?php echo $translate->_('Remove Form'); ?>
                            </a>
                        </div>
                        <div id="weight-total" class="weight-total-bar hide"><?php echo $translate->_("Total Weight:"); ?> <span id="weight-total-number"></span>%</div>

                        <?php
                        $query = "	SELECT a.* FROM `global_lu_objectives` a
									JOIN `objective_audience` b
									ON a.`objective_id` = b.`objective_id`
									AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
									WHERE (
											(b.`audience_value` = 'all')
											OR
											(b.`audience_type` = 'course' AND b.`audience_value` = ".$db->qstr($COURSE_ID).")
											OR
											(b.`audience_type` = 'event' AND b.`audience_value` = ".$db->qstr($EVENT_ID).")
										)
									AND a.`objective_parent` = '0'
									AND a.`objective_active` = '1'";
                        $objectives = $db->GetAll($query);
                        if ($objectives) {
                            ?>
                            <style type="text/css">
                                .mapped-objective {
                                    padding-left: 30px!important;
                                }
                            </style>
							<a name="assessment-objectives-section"></a>
							<h2 title="Assessment Objectives Section" class="collapsed">Assessment Objectives</h2>
							<div id="assessment-objectives-section">
								<?php
								$objective_name = $translate->_("events_filter_controls");
								$hierarchical_name = $objective_name["co"]["global_lu_objectives_name"];
								?>
								<div class="objectives half left">
									<h3>Curriculum Tag Sets</h3>
									<ul class="tl-objective-list" id="objective_list_0">
										<?php
										foreach ($objectives as $objective) {
											?>
											<li class = "objective-container objective-set assessment-objective"
												id = "objective_<?php echo $objective["objective_id"]; ?>"
												data-list="<?php echo $objective["objective_name"] == $hierarchical_name?'hierarchical':'flat'; ?>"
												data-id="<?php echo $objective["objective_id"]; ?>">
												<?php
												$title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
												?>
												<div class="objective-title"
													 id="objective_title_<?php echo $objective["objective_id"]; ?>"
													 data-title="<?php echo $title; ?>"
													 data-id = "<?php echo $objective["objective_id"]; ?>"
													 data-code = "<?php echo $objective["objective_code"]; ?>"
													 data-name = "<?php echo $objective["objective_name"]; ?>"
													 data-description = "<?php echo $objective["objective_description"]; ?>">
													<h4><?php echo $title; ?></h4>
												</div>
												<div class="objective-controls" id="objective_controls_<?php echo $objective["objective_id"];?>">
												</div>
												<div class="objective-children" id="children_<?php echo $objective["objective_id"]; ?>">
													<ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>"></ul>
												</div>
											</li>
											<?php
										}
										?>
									</ul>
								</div>

								<?php
								$query = "	SELECT a.*, COALESCE(b.`objective_details`,a.`objective_description`) AS `objective_description`, b.`objective_type` AS `objective_type`,
                                                    b.`importance`,
                                                    COALESCE(b.`cobjective_id`,0) AS `mapped_to_course`
                                                    FROM `global_lu_objectives` a
                                                    LEFT JOIN `course_objectives` b
                                                    ON a.`objective_id` = b.`objective_id`
                                                    AND b.`course_id` = ".$db->qstr($COURSE_ID)."
                                                    AND b.`active` = '1'
                                                    WHERE a.`objective_active` = '1'
                                                    AND b.`course_id` = ".$db->qstr($COURSE_ID)."
                                                    GROUP BY a.`objective_id`
                                                    ORDER BY a.`objective_id` ASC";
                                $mapped_objectives = $db->GetAll($query);
                                $primary = false;
                                $secondary = false;
                                $tertiary = false;
                                $hierarchical_objectives = array();
                                $flat_objectives = array();
                                $explicit_assessment_objectives = false;
                                $mapped_assessment_objectives = array();
                                if ($mapped_objectives) {
                                    foreach ($mapped_objectives as $objective) {
                                        if (in_array($objective["objective_type"], array("curricular_objective","course"))) {
                                            $hierarchical_objectives[] = $objective;
                                        } else {
                                            $flat_objectives[] = $objective;
                                        }

                                        if ($objective["mapped"]) {
                                            $mapped_assessment_objectives[] = $objective;
                                        }
                                    }
                                }
                                ?>
                                <div class="right droppable" style="display: inline-block; box-sizing: border-box;" id="mapped_objectives" data-resource-type="assessment" data-resource-id="<?php echo $ASSESSMENT_ID;?>">
									<h2><?php echo $translate->_("Mapped Objectives"); ?></h2>

									<div class="row-fluid space-below">
										<a href="javascript:void(0)" class="mapping-toggle btn btn-success btn-small pull-left" data-toggle="show" id="toggle_sets"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Map Additional Objectives"); ?></a>
									</div>

                                    <?php
                                    if ($hierarchical_objectives) {
                                        assessment_objectives_display_leafs($hierarchical_objectives, $COURSE_ID, 0);
                                    }

                                    if ($flat_objectives) {
                                        ?>
                                        <div id="clinical-list-wrapper">
                                            <a name="clinical-objective-list"></a>
                                            <h2 id="flat-toggle"  title="Clinical Objective List" class="collapsed list-heading">Other Objectives</h2>
                                            <div id="clinical-objective-list">
                                                <ul class="objective-list mapped-list" id="mapped_flat_objectives" data-importance="flat">
                                                    <?php
                                                    if ($flat_objectives) {
                                                        foreach ($flat_objectives as $objective) {
                                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                            ?>
                                                            <li class = "mapped-objective"
                                                                id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                                data-id = "<?php echo $objective["objective_id"]; ?>"
                                                                data-title="<?php echo $title;?>"
                                                                data-description="<?php echo htmlentities($objective["objective_description"]);?>">
                                                                <strong><?php echo $title; ?></strong>
                                                                <div class="objective-description">
                                                                    <?php
                                                                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                                    if ($set) {
                                                                        echo "Curriculum Tag Set: <strong>".$set["objective_name"]."</strong><br/>";
                                                                    }

                                                                    echo $objective["objective_description"];
                                                                    ?>
                                                                </div>

                                                                <div class="assessment-objective-controls">
                                                                    <input type="checkbox" class="checked-mapped" id="check_mapped_<?php echo $objective['objective_id'];?>" value="<?php echo $objective['objective_id'];?>" <?php echo $objective["mapped"]?' checked="checked"':''; ?>/>
                                                                </div>
                                                            </li>
                                                        <?php
                                                        }
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>

                                    <div id="assessment-list-wrapper" <?php echo ($explicit_assessment_objectives)?"":" style=\"display:none;\"";?>>
                                        <a name="assessment-objective-list"></a>
                                        <h2 id="assessment-toggle"  title="Assessment Objective List" class="collapsed list-heading">Assessment Specific Objectives</h2>
                                        <div id="assessment-objective-list">
                                            <ul class="objective-list mapped-list" id="mapped_assessment_objectives" data-importance="assessment">
                                                <?php
                                                if ($explicit_assessment_objectives) {
                                                    foreach($explicit_assessment_objectives as $objective) {
                                                        $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                        ?>
                                                        <li class = "mapped-objective"
                                                            id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                            data-id = "<?php echo $objective["objective_id"]; ?>"
                                                            data-title="<?php echo $title;?>"
                                                            data-description="<?php echo htmlentities($objective["objective_description"]);?>"
                                                            data-mapped="<?php echo $objective["mapped_to_course"]?1:0;?>">
                                                            <strong><?php echo $title; ?></strong>
                                                            <div class="objective-description">
                                                                <?php
                                                                $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                                if ($set) {
                                                                    echo "Curriculum Tag Set: <strong>".$set["objective_name"]."</strong><br/>";
                                                                }

                                                                echo $objective["objective_description"];
                                                                ?>
                                                            </div>

                                                            <div class="assessment-objective-controls">
                                                                <img 	src="<?php echo ENTRADA_URL;?>/images/action-delete.gif"
                                                                        class="objective-remove list-cancel-image"
                                                                        id="objective_remove_<?php echo $objective["objective_id"];?>"
                                                                        data-id="<?php echo $objective["objective_id"];?>">
                                                            </div>
                                                        </li>
                                                    <?php
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <select id="checked_objectives_select" name="checked_objectives[]" multiple="multiple" style="display:none;">
                                        <?php
                                        if ($mapped_assessment_objectives) {
                                            foreach ($mapped_assessment_objectives as $objective) {
                                                if (in_array($objective["objective_type"], array("curricular_objective","course"))) {
                                                    $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                    ?>
                                                    <option value="<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                    <select id="clinical_objectives_select" name="clinical_presentations[]" multiple="multiple" style="display:none;">
                                        <?php
                                        if ($mapped_assessment_objectives) {
                                            foreach ($mapped_assessment_objectives as $objective) {
                                                if (in_array($objective["objective_type"], array("clinical_presentation","event"))) {
                                                    $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                                    ?>
                                                    <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                            <?php
                        }
                        ?>
                        <table style="width: 100%; margin-top: 25px" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="width: 15%; text-align: left">
                                    <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/gradebook?<?php echo replace_query(array("step" => false, "section" => "view", "assessment_id" => false)); ?>'" />
								</td>
								<td>
									<label class="radio form-nrequired pull-left" for="published_draft"><input id="published_draft" type="radio" name="published" style="margin-right: 10px;" value="0"<?php echo ($PROCESSED["published"]==0) ? " checked" : "";?>>Draft</label>
									<label class="radio form-nrequired pull-left" for="published_publish"><input id="published_publish" type="radio" name="published" value="1" style="margin-left: 30px; margin-right: 10px;"<?php echo ($PROCESSED["published"]==1) ? " checked" : "";?>>Published</label>
                                </td>
                                <td style="width: 55%; text-align: right; vertical-align: middle">
                                    <span class="content-small">After saving:</span>
                                    <select id="post_action" name="post_action">
                                        <option value="grade"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "grade") ? " selected=\"selected\"" : ""); ?>>Grade assessment</option>
                                        <option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another assessment</option>
                                        <option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to assessment list</option>
                                        <option value="parent"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "parent") ? " selected=\"selected\"" : ""); ?>>Return to all gradebooks list</option>
                                    </select>
                                    <input type="submit" class="btn btn-primary" value="Save" />
                                </td>
                            </tr>
                        </table>
                        <?php
                        if ($event) {
                            ?>
                            <input type="hidden" id="assessment-event" name="event_id" value="<?php echo $event->getID(); ?>" data-title="<?php echo $event->getEventTitle(); ?>" data-date="<?php echo date("D M d/y g:ia", $event->getEventStart()) ?>" />
                            <?php
                        }
                        if (isset($PROCESSED["groups"]) && is_array($PROCESSED["groups"]) && count($PROCESSED["groups"])) {
							foreach($PROCESSED["groups"] as $group) {
								?>
								<input type="hidden" name="as_groups[]" value="<?php echo $group["cgroup_id"]; ?>" id="as_groups_<?php echo $group["cgroup_id"]; ?>"  data-id="<?php echo $group["cgroup_id"]; ?>" data-label="<?php echo $group["group_name"]; ?>" data-filter="as_groups" class="search-target-control as_groups_search_target_control">
								<?php
							}
						}
                        ?>
						<?php
						if (isset($PROCESSED["quiz_ids"]) && count($PROCESSED["quiz_ids"])) { ?>
							<?php
							foreach ($PROCESSED["quiz_ids"] as $quiz_id) { ?>
								<input type="hidden" name="quiz_ids[]" value="<?php echo $quiz_id; ?>">
								<?php
							}
						}
						?>

					</form>
                    <div id="event-modal" class="modal hide fade">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h3 id="event-modal-title">Search learning events to attach to this assessment</h3>
                                </div>
                                <div class="modal-body">
                                    <div id="title-search">
                                        <input type="text" placeholder="Search learning events by title" id="event-title-search" />
                                        <div id="event-search-msgs">
                                            <div class="alert alert-block">
                                                <ul>
                                                    <li>To search for learning events, begin typing the title of the event you wish to find in the search box.</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div id="events-search-wrap">
                                            <ul id="events-search-list"></ul>
                                        </div>
                                    </div>
                                    <div id="loading" class="hide"><img src="<?php echo ENTRADA_URL ?>/images/loading.gif" /></div>
                                </div>
                                <div id="event-modal-footer">
                                    <a href="#" class="btn" id="close-event-modal">Close</a>
                                    <a href="#" id="attach-learning-event" class="btn btn-primary pull-right">Attach Learning Event</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quizzes modal -->
                    <div id="quiz-modal" class="modal hide fade">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h3 id="quiz-modal-title"><?php echo $translate->_("Search quizzes to attach to this assessment"); ?></h3>
                                </div>
                                <div class="modal-body">
                                    <div id="title-search">
                                        <input type="text" placeholder="<?php echo $translate->_("Search learning quizzes by title");?>" id="quiz-title-search" />
                                        <div id="quiz-search-msgs">
                                            <div class="alert alert-block">
                                                <ul>
                                                    <li><?php echo $translate->_("To search for quizzes, begin typing the title of the quiz you wish to find in the search box."); ?></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div id="quizzes-search-wrap">
                                            <ul id="quizzes-search-list"></ul>
                                        </div>
                                    </div>
                                    <div id="loading" class="hide"><img src="<?php echo ENTRADA_URL ?>/images/loading.gif" /></div>
                                </div>
                                <div id="quiz-modal-footer">
                                    <a href="#" class="btn" id="close-quiz-modal">Close</a>
                                    <a href="#" id="attach-quiz" class="btn btn-primary pull-right"><?php echo $translate->_("Attach Quiz"); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Quizzes modal -->

					<?php
					$modal_attach_assessment = new Views_Gradebook_Modal(array(
						"id" => "modal-attach-assessment-form",
						"title" => $translate->_("Search assessment forms to attach to this assessment"),
						"dismiss_button" => array(
							"text" => $translate->_("Close"),
							"class" => "pull-left"
						),
						"success_button" => array(
							"text" => $translate->_("Attach Assessment Form"),
							"class" => "btn-primary btn-attach-assessment-form"
						)
					));

					$modal_attach_assessment->setBody('
								<div id="title-search">
										<input type="text" placeholder="Search assessment forms by title" id="assessment-form-title-search" />
	
										<div id="assessment-form-search-msgs">
											<div class="alert alert-block">
												<ul>
													<li>To search for assessment forms, begin typing the title of the form you wish to find in the search box.</li>
												</ul>
											</div>
										</div>
										<div id="assessment-form-search-wrap">
											<ul id="assessment-form-search-list"></ul>
										</div>
									</div>
									<div id="assessment-form-loading" class="hide"><img src="'.ENTRADA_URL.'/images/loading.gif" />
								</div>
							');

					$modal_attach_assessment->render();
					?>
					<?php
				break;
			}
		} else {
			add_error("In order to add an assignment to a gradebook you must provide a valid course identifier. The provided ID does not exist in this system.");

			echo display_error();

			application_log("notice", "Failed to provide a valid course identifier when attempting to add an assessment.");
		}
	} else {
		add_error("In order to add an assignment to a gradebook you must provide a valid course identifier. The provided ID does not exist in this system.");

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to add an assessment.");
	}
}