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
 * This file is used by quiz authors to add / edit or remove quiz questions
 * from a particular quiz.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationquestion", "update", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/questions?section=edit", "title" => "Edit Question");
	
	$query = "	SELECT * FROM `evaluations_lu_questions`
				WHERE `equestion_id` = ".$db->qstr($QUESTION_ID);
	$question_record = $db->GetRow($query);
	if ($question_record && $ENTRADA_ACL->amIAllowed(new EvaluationQuestionResource($QUESTION_ID, $question_record["organisation_id"], true), "update")) {
		$default_parent_id = (isset($question_record["question_parent_id"]) && $question_record["question_parent_id"] ? $question_record["question_parent_id"] : $QUESTION_ID);
		$PROCESSED = $question_record;
		
		$query = "SELECT COUNT(`equestion_id`) FROM `evaluation_form_questions` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID);
		$question_used = $db->GetOne($query);
		if ($question_used) {
			if (isset($FORM_ID) && $FORM_ID) {
				$query = "SELECT COUNT(`equestion_id`) FROM `evaluation_form_questions` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID)." AND `eform_id` <> ".$db->qstr($FORM_ID);
				$question_used_other_forms = $db->GetOne($query);
				if (!$question_used_other_forms && $question_used == 1 ) {
					$question_used = 0;
				}
			}

            if ($question_used) {
                $PROCESSED["question_parent_id"] = $QUESTION_ID;
            }
		}
		
		if ($PROCESSED["questiontype_id"] == 3) {
			$query = "SELECT `erubric_id` FROM `evaluation_rubric_questions`
						WHERE `equestion_id` = ".$db->qstr($QUESTION_ID);
			$erubric_id = $db->GetOne($query);
			if ($erubric_id) {
				$query = "SELECT * FROM `evaluations_lu_rubrics`
							WHERE `erubric_id` = ".$db->qstr($erubric_id);
				$rubric = $db->GetRow($query);
				if ($rubric) {
					$question_record["question_title"] = $rubric["rubric_title"];
					$PROCESSED["rubric_title"] = $rubric["rubric_title"];
					$PROCESSED["rubric_description"] = $rubric["rubric_description"];
				}
				$query = "SELECT * FROM `evaluation_rubric_questions` AS a
							JOIN `evaluations_lu_questions` AS b
							ON a.`equestion_id` = b.`equestion_id`
							WHERE a.`erubric_id` = ".$db->qstr($erubric_id)."
							AND b.`questiontype_id` = 3
							ORDER BY a.`question_order` ASC";
				$categories = $db->GetAll($query);
				if ($categories) {
					$PROCESSED["evaluation_rubric_categories"] = array();
					$PROCESSED["evaluation_rubric_category_criteria"] = array();
					foreach ($categories as $index => $category) {
						if (!isset($PROCESSED["allow_comments"])) {
							$PROCESSED["allow_comments"] = $category["allow_comments"];
						}
						$PROCESSED["evaluation_rubric_categories"][$index + 1] = array();
						$PROCESSED["evaluation_rubric_categories"][$index + 1]["objective_ids"] = array();
						$PROCESSED["evaluation_rubric_categories"][$index + 1]["category"] = $category["question_text"];
                        $PROCESSED["evaluation_rubric_categories"][$index + 1]["category_description"] = $category["question_description"];
						$PROCESSED["evaluation_rubric_categories"][$index + 1]["question_parent_id"] = $category["question_parent_id"];

						$query = "SELECT * FROM `evaluation_question_objectives` AS a
									JOIN `global_lu_objectives` AS b
									ON a.`objective_id` = b.`objective_id`
									WHERE a.`equestion_id` = ".$db->qstr($category["equestion_id"])."
									AND b.`objective_active` = 1
									GROUP BY b.`objective_id`
									ORDER BY a.`objective_id`";
						$objectives = $db->GetAll($query);
						if ($objectives) {
							foreach ($objectives as $objective) {
								$PROCESSED["evaluation_rubric_categories"][$index + 1]["objective_ids"][] = $objective["objective_id"];
							}
						}
						
						$query = "SELECT * FROM `evaluations_lu_question_responses`
									WHERE `equestion_id` = ".$db->qstr($category["equestion_id"])."
									ORDER BY `response_order` ASC";
						$columns = $db->GetAll($query);
						if ($columns) {
							$PROCESSED["evaluation_question_responses"] = array();
							$PROCESSED["evaluation_rubric_category_criteria"][$index + 1] = array();
							$PROCESSED["columns_count"] = count($columns);
							foreach ($columns as $cindex => $column) {
                                $query = "SELECT `erdescriptor_id` FROM `evaluation_question_response_descriptors`
                                                WHERE `eqresponse_id` = ".$db->qstr($column["eqresponse_id"]);
                                $erdescriptor_id = $db->getOne($query);
                                if ($erdescriptor_id) {
                                    $column["erdescriptor_id"] = $erdescriptor_id;
                                }
								$PROCESSED["evaluation_question_responses"][$cindex + 1] = $column;

								$query = "SELECT * FROM `evaluations_lu_question_response_criteria`
											WHERE `eqresponse_id` = ".$db->qstr($column["eqresponse_id"]);
								$criteria = $db->GetRow($query);
								if ($criteria) {
									$PROCESSED["evaluation_rubric_category_criteria"][$index + 1][$cindex + 1] = array();
									$PROCESSED["evaluation_rubric_category_criteria"][$index + 1][$cindex + 1]["criteria"] = $criteria["criteria_text"];
								}
							}
						}
					}
					$PROCESSED["categories_count"] = count($categories);
				}
			}
		} elseif (in_array($PROCESSED["questiontype_id"], array(1, 4, 5, 6))) {
			$query = "SELECT a.`objective_id` FROM `evaluation_question_objectives` AS a
						JOIN `global_lu_objectives` AS b
						ON a.`objective_id` = b.`objective_id`
						WHERE a.`equestion_id` = ".$db->qstr($QUESTION_ID)."
						AND b.`objective_active` = 1
						GROUP BY b.`objective_id`
						ORDER BY a.`objective_id`";
			$objectives = $db->GetAll($query);
			if ($objectives) {
				foreach ($objectives as $objective) {
					$PROCESSED["objective_ids"][] = $objective["objective_id"];
				}
			}
		}
		/**
		 * Required field "questiontype_id" / Question Type
		 * Currently only multile choice questions are supported, although
		 * this is something we will be expanding on shortly.
		 */
		if ((isset($_POST["questiontype_id"])) && ($tmp_input = clean_input($_POST["questiontype_id"], array("trim", "int")))) {
			$PROCESSED["questiontype_id"] = $tmp_input;
		} elseif ((isset($_GET["qtype_id"])) && ($tmp_input = clean_input($_GET["qtype_id"], array("trim", "int")))) {
			$PROCESSED["questiontype_id"] = $tmp_input;
		}
		/**
		 * Non-required field "questiontype_id" / Question Type
		 * Currently only multile choice questions are supported, although
		 * this is something we will be expanding on shortly.
		 */
		if ((isset($_POST["efquestion_id"])) && ($tmp_input = clean_input($_POST["efquestion_id"], array("trim", "int")))) {
			$PROCESSED["efquestion_id"] = $tmp_input;
		} elseif ((isset($_GET["efquestion_id"])) && ($tmp_input = clean_input($_GET["efquestion_id"], array("trim", "int")))) {
			$PROCESSED["efquestion_id"] = $tmp_input;
		}
		
		// Error Checking
		switch ($STEP) {
			case 2 :
				//If Rubric question type.
				switch ($PROCESSED["questiontype_id"]) {
					case 3 :
						/**
						 * Required field "rubric_title" / Rubric Title.
						 */
						if ((isset($_POST["rubric_title"])) && ($tmp_input = clean_input($_POST["rubric_title"], array("trim")))) {
							$PROCESSED["rubric_title"] = $tmp_input;
						} else {
							$PROCESSED["rubric_title"] = "";
						}

						/**
						 * Non-required field "question_text" / Question.
						 */
						if ((isset($_POST["rubric_description"])) && ($tmp_input = clean_input($_POST["rubric_description"], array("trim", "allowedtags")))) {
							$PROCESSED["rubric_description"] = $tmp_input;
						} else {
							$PROCESSED["rubric_description"] = "";
						}

						if ((isset($_POST["categories_count"])) && ($tmp_input = clean_input($_POST["categories_count"], array("int")))) {
							$PROCESSED["categories_count"] = $tmp_input;
						} else {
							$PROCESSED["categories_count"] = "";
						}

						if ((isset($_POST["columns_count"])) && ($tmp_input = clean_input($_POST["columns_count"], array("int")))) {
							$PROCESSED["columns_count"] = $tmp_input;
						} else {
							$PROCESSED["columns_count"] = "";
						}
						/**
						 * Required field "allow_comments" / Allow Question Comments.
						 */
						if ((isset($_POST["allow_comments"])) && clean_input($_POST["allow_comments"], array("bool"))) {
							$PROCESSED["allow_comments"] = true;
						} else {
							$PROCESSED["allow_comments"] = false;
						}
					break;
					case 1 :
					case 5 :
					case 6 :
					default :
						/**
						 * Required field "allow_comments" / Allow Question Comments.
						 */
						if ((isset($_POST["allow_comments"])) && clean_input($_POST["allow_comments"], array("bool"))) {
							$PROCESSED["allow_comments"] = true;
						} else {
							$PROCESSED["allow_comments"] = false;
						}
					case 4 :
						/**
						 * Non-required field "question_code" / Question Code.
						 */
						if ((isset($_POST["question_code"])) && ($tmp_input = clean_input($_POST["question_code"], array("trim", "notags")))) {
							$PROCESSED["question_code"] = $tmp_input;
						}
						$PROCESSED["objective_ids"] = array();
						if ((isset($_POST["objective_ids_1"])) && (is_array($_POST["objective_ids_1"]))) {
							foreach ($_POST["objective_ids_1"] as $objective_id) {
								$objective_id = clean_input($objective_id, array("trim", "int"));
								if ($objective_id && isset($PROCESSED["objective_ids"]) && @count($PROCESSED["objective_ids"])) {
									foreach ($PROCESSED["objective_ids"] as $temp_objective_id) {
										if ($temp_objective_id == $objective_id) {
											add_error("You cannot have more than one identical <strong>objective</strong> in a question.");
										}
									}
								}
								$PROCESSED["objective_ids"][] = $objective_id;
							}
						}
					case 2 :
						/**
						 * Required field "question_text" / Question.
						 */
						if ((isset($_POST["question_text"])) && ($tmp_input = clean_input($_POST["question_text"], array("trim", "allowedtags")))) {
							$PROCESSED["question_text"] = $tmp_input;
						} else {
							add_error("The <strong>Question Text</strong> field is required.");
						}
					break;
				}

				if ($PROCESSED["questiontype_id"] != 2 && $PROCESSED["questiontype_id"] != 4) {
					/**
					 * Required field "response_text" / Available Responses.
					 *
					 */
					$minimum_passing_level_found = false;
					$PROCESSED["evaluation_question_responses"] = array();
					$PROCESSED["evaluation_rubric_categories"] = array();
					$PROCESSED["evaluation_rubric_category_criteria"] = array();
					if ((isset($_POST["response_text"])) && (is_array($_POST["response_text"]))) {
						$i = 1;
						foreach ($_POST["response_text"] as $response_key => $response_text) {
							$response_key = clean_input($response_key, "int");
							$response_is_html = 0;

							/**
							 * Check if this is response is in HTML or just plain text.
							 */
							if ((isset($_POST["response_is_html"])) && (is_array($_POST["response_is_html"])) && (isset($_POST["response_is_html"][$response_key])) && ($_POST["response_is_html"][$response_key] == 1)) {
								$response_is_html = 1;
							}

							if ($response_is_html) {
								$response_text = clean_input($response_text, array("trim", "allowedtags"));
							} else {
								$response_text = clean_input($response_text, array("trim"));
							}

							if (($response_key) && ($response_text != "")) {
								if (is_array($PROCESSED["evaluation_question_responses"]) && !empty($PROCESSED["evaluation_question_responses"])) {
									foreach ($PROCESSED["evaluation_question_responses"] as $value) {
										if ($value["response_text"] == $response_text) {
											add_error("You cannot have more than one <strong>identical response</strong> in a question.");
										}
									}
								}

								$PROCESSED["evaluation_question_responses"][$i]["response_text"] = $response_text;
								$PROCESSED["evaluation_question_responses"][$i]["response_order"] = $i;

                                if (isset($_POST["response_descriptor_id"][$response_key]) && clean_input($_POST["response_descriptor_id"][$response_key], "int")) {
                                    $PROCESSED["evaluation_question_responses"][$i]["erdescriptor_id"] = clean_input($_POST["response_descriptor_id"][$response_key], "int");
                                }

								/**
								 * Check if this is the selected minimum passing level or not.
								 */
								if ((isset($_POST["minimum_passing_level"])) && ($minimum_passing_level = clean_input($_POST["minimum_passing_level"], array("trim", "int"))) && ($response_key == $minimum_passing_level)) {
									$minimum_passing_level_found = true;
									$PROCESSED["evaluation_question_responses"][$i]["minimum_passing_level"] = 1;
								} else {
									$PROCESSED["evaluation_question_responses"][$i]["minimum_passing_level"] = 0;
								}

								$PROCESSED["evaluation_question_responses"][$i]["response_is_html"] = $response_is_html;

								$i++;
							}
						}
					}
				}
				if ((isset($_POST["category"])) && (is_array($_POST["category"]))) {
					$i = 1;
					foreach ($_POST["category"] as $category_key => $category) {
						$category_key = clean_input($category_key, "int");

						$category = clean_input($category, array("trim"));

						if (($category_key) && ($category != "")) {
							if (is_array($PROCESSED["evaluation_rubric_categories"]) && !empty($PROCESSED["evaluation_rubric_categories"])) {
								foreach ($PROCESSED["evaluation_rubric_categories"] as $value) {
									if ($value["category"] == $category) {
										add_error("You cannot have more than one <strong>identical category</strong> in a rubric.");
									}
								}
							}

							$PROCESSED["evaluation_rubric_categories"][$i]["category"] = $category;
                            if (isset($_POST["category_description"][$i]) && ($tmp_input = clean_input($_POST["category_description"][$i], array("trim", "notags")))) {
                                $PROCESSED["evaluation_rubric_categories"][$i]["category_description"] = $tmp_input;
                            }
							$PROCESSED["evaluation_rubric_categories"][$i]["category_order"] = $i;
							$PROCESSED["evaluation_rubric_categories"][$i]["objective_ids"] = array();
							if ((isset($_POST["objective_ids_".$i])) && (is_array($_POST["objective_ids_".$i]))) {
								foreach ($_POST["objective_ids_".$i] as $objective_id) {
									$objective_id = clean_input($objective_id, array("trim", "int"));
									if ($objective_id && isset($PROCESSED["evaluation_rubric_categories"][$i]["objective_ids"]) && @count($PROCESSED["evaluation_rubric_categories"][$i]["objective_ids"])) {
										foreach ($PROCESSED["evaluation_rubric_categories"][$i]["objective_ids"] as $temp_objective_id) {
											if ($temp_objective_id == $objective_id) {
												add_error("You cannot have more than one identical <strong>objective</strong> in a category.");
											}
										}
									}
									$PROCESSED["evaluation_rubric_categories"][$i]["objective_ids"][] = $objective_id;
								}
							}
							
							if ((isset($_POST["criteria"][$i])) && (is_array($_POST["criteria"][$i]))) {
								$j = 1;
								foreach ($_POST["criteria"][$i] as $criteria_key => $criteria) {
									$criteria_key = clean_input($criteria_key, "int");

									$criteria = clean_input($criteria, array("trim", "notags"));

									if (($criteria_key)) {
										if ($criteria != "" && is_array($PROCESSED["evaluation_rubric_category_criteria"][$i]) && !empty($PROCESSED["evaluation_rubric_category_criteria"][$i])) {
											foreach ($PROCESSED["evaluation_rubric_category_criteria"][$i] as $value) {
												if ($value["criteria"] == $criteria) {
													add_error("You cannot have more than one <strong>identical criteria</strong> in a category.");
												}
											}
										}

										$PROCESSED["evaluation_rubric_category_criteria"][$i][$j]["criteria"] = $criteria;
										$PROCESSED["evaluation_rubric_category_criteria"][$i][$j]["criteria_order"] = $j;

										$j++;
									}
								}
							}

							$i++;
						}
					}
				}

				/**
				 * There must be at least 2 possible responses to proceed.
				 */
				if (count($PROCESSED["evaluation_question_responses"]) < 2 && $PROCESSED["questiontype_id"] != 2 && $PROCESSED["questiontype_id"] != 4) {
					add_error("You must provide at least 2 responses in the <strong>Available Responses</strong> section.");
				}

				if (isset($_POST["post_action"])) {
					switch ($_POST["post_action"]) {
						case "new" :
							$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
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
					$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
				}
				if (!has_error()) {
					if($ENTRADA_ACL->amIAllowed("evaluationquestion", "create", false)) {
						$PROCESSED_RELATED_QUESTION = array();
						$query = "SELECT COUNT(`eqresponse_id`) FROM `evaluations_lu_question_responses` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID);
						$response_count = $db->GetOne($query);
						if ((int)count($PROCESSED["evaluation_question_responses"]) != (int)$response_count) {
							if ($question_record["question_parent_id"]) {
								$PROCESSED_RELATED_QUESTION["related_equestion_id"] = $question_record["question_parent_id"];
							}
						}
						if ($question_used) {
							if ($PROCESSED["questiontype_id"] == 3) {
								if ($db->AutoExecute("evaluations_lu_rubrics", $PROCESSED, "INSERT") && ($erubric_id = $db->Insert_Id())) {
									$old_questions = array();
									$min_order = false;
									if (isset($FORM_ID) && $FORM_ID) {
										$query = "SELECT b.* FROM `evaluation_rubric_questions` AS a
													JOIN `evaluations_lu_questions` AS b
													ON a.`equestion_id` = b.`equestion_id`
													WHERE a.`erubric_id` = (
														SELECT `erubric_id` FROM `evaluation_rubric_questions` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID)." GROUP BY `erubric_id`
													)
													ORDER BY a.`question_order` ASC";
										$categories = $db->GetAll($query);
										foreach ($categories as $category) {
											$query = "SELECT * FROM `evaluation_form_questions` WHERE `eform_id` = ".$db->qstr($FORM_ID)." AND `equestion_id` = ".$db->qstr($category["equestion_id"]);
											$question = $db->GetRow($query);
											if ($question) {
												$old_questions[] = $question;
												if (!$min_order || $question["question_order"] < $min_order) {
													$min_order = $question["question_order"];
												}
												$db->Execute("DELETE FROM `evaluation_form_questions` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"])." AND `eform_id` = ".$db->qstr($FORM_ID));
											}
										}
									}
									if (count($old_questions) != count($PROCESSED["evaluation_rubric_categories"])) {
										$order_offset = count($PROCESSED["evaluation_rubric_categories"]) - count($old_questions);
										$db->Execute("UPDATE `evaluation_form_questions` SET `question_order` = (`question_order` + (".$db->qstr((int)$order_offset).")) WHERE `question_order` > ".$db->qstr($min_order)." AND `eform_id` = ".$db->qstr($FORM_ID));
									}
									$form_question_order = $min_order;
									$rubric_order = 1;
									foreach ($PROCESSED["evaluation_rubric_categories"] as $index => $category) {
										$PROCESSED_QUESTION = array("questiontype_id" => 3,
																	"organisation_id" => $PROCESSED["organisation_id"],
																	"question_text" => $category["category"],
																	"question_code" => $category["category"],
																	"question_description" => (isset($category["category_description"]) && $category["category_description"] ? $category["category_description"] : NULL),
																	"allow_comments" => $PROCESSED["allow_comments"],
																	"question_parent_id" => (isset($category["category_parent_id"]) && (int)$category["category_parent_id"] ? (int)$category["category_parent_id"] : $QUESTION_ID));
										$equestion_id = 0;
										if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED_QUESTION, "INSERT") && ($equestion_id = $db->Insert_Id()) &&
											$db->AutoExecute("evaluation_rubric_questions", array("erubric_id" => $erubric_id, "equestion_id" => $equestion_id, "question_order" => $rubric_order), "INSERT")) {
											$rubric_order++;
											if (isset($FORM_ID) && $FORM_ID) {
												if (!$db->AutoExecute("evaluation_form_questions", array("equestion_id" => $equestion_id, "question_order" => $form_question_order), "INSERT")) {
													add_error("There was an error while trying to attach the updated question to the form.<br /><br />The system administrator was informed of this error; please try again later.");

													application_log("error", "Unable to insert a new evaluation_form_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
												} else {
													$form_question_order++;
												}
											}
											if (count($PROCESSED_RELATED_QUESTION)) {
												$PROCESSED_RELATED_QUESTION["equestion_id"] = $equestion_id;
												if (!$db->AutoExecute("evaluations_related_questions", $PROCESSED_RELATED_QUESTION, "INSERT")) {
													add_error("There was an error while trying to attach a related question to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

													application_log("error", "Unable to insert a new evaluations_related_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
												}
											}
											/**
											 * Add the question responses to the evaluation_question_responses table.
											 */
											if ((is_array($PROCESSED["evaluation_question_responses"])) && (count($PROCESSED["evaluation_question_responses"]))) {
												foreach ($PROCESSED["evaluation_question_responses"] as $subindex => $question_response) {
													$PROCESSED_RESPONSE = array (
																	"equestion_id" => $equestion_id,
																	"response_text" => $question_response["response_text"],
																	"response_order" => $question_response["response_order"],
																	"response_is_html" => $question_response["response_is_html"],
																	"minimum_passing_level"	=> $question_response["minimum_passing_level"]
																	);
													$eqresponse_id = 0;
													if ($db->AutoExecute("evaluations_lu_question_responses", $PROCESSED_RESPONSE, "INSERT") && ($eqresponse_id = $db->Insert_Id())) {
                                                        $db->Execute("DELETE FROM `evaluation_question_response_descriptors` WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id));
                                                        if (isset($question_response["erdescriptor_id"]) && $question_response["erdescriptor_id"]) {
                                                            $PROCESSED_RESPONSE_DESCRIPTOR = array(
                                                                "eqresponse_id" => $eqresponse_id,
                                                                "erdescriptor_id" => $question_response["erdescriptor_id"]
                                                            );
                                                            if (!$db->AutoExecute("evaluation_question_response_descriptors", $PROCESSED_RESPONSE_DESCRIPTOR, "INSERT")) {
                                                                add_error("There was an error while trying to attach a <strong>Response Descriptor</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

                                                                application_log("error", "Unable to insert a new evaluation_question_response_descriptors record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
                                                            }
                                                        }
														/**
														 * Add the responses criteria to the evaluation_rubric_criteria table.
														 */
														if ((isset($PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"])) && ($PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"] || $PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"] === "")) {
															$PROCESSED_CRITERIA = array (
																			"eqresponse_id" => $eqresponse_id,
																			"criteria_text" => $PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"],
																			);

															if (!$db->AutoExecute("evaluations_lu_question_response_criteria", $PROCESSED_CRITERIA, "INSERT")) {
																add_error("There was an error while trying to attach a <strong>Criteria</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

																application_log("error", "Unable to insert a new evaluations_lu_question_response_criteria record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
															}
														}
													} else {
														add_error("There was an error while trying to attach a <strong>Question Response</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

														application_log("error", "Unable to insert a new evaluations_lu_question_responses record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
													}
												}
											}
											/**
											 * Add the question objectives to the evaluation_question_objectives table.
											 */
											if ((isset($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"])) && (@count($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"]))) {
												foreach ($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"] as $objective_id) {
													$PROCESSED_OBJECTIVE = array (
																	"equestion_id" => $equestion_id,
																	"objective_id" => $objective_id,
																	"updated_date" => time(),
																	"updated_by" => $ENTRADA_USER->getID()
																	);
													if (!$db->AutoExecute("evaluation_question_objectives", $PROCESSED_OBJECTIVE, "INSERT")) {
														add_error("There was an error while trying to attach a <strong>Question Objective</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

														application_log("error", "Unable to insert a new evaluation_question_objectives record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
													}
												}
											}
											if (!isset($FORM_ID) || !$FORM_ID) {
												switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
													case "new" :
														$url = ENTRADA_URL."/admin/evaluations/questions?section=add";
														$msg = "You will now be redirected to add another evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
													case "index" :
														$url = ENTRADA_URL."/admin/evaluations/questions";
														$msg = "You will now be redirected back to the evaluation questions index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
													case "content" :
													default :
														$url = ENTRADA_URL."/admin/evaluations/questions?section=edit&id=".$equestion_id;
														$msg = "You will now be redirected back to the evaluation question you just added; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
												}
											} else {
												$url = ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID;
												$msg = "You will now be redirected back to the form you were editing; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											}
										} else {
											add_error("There was a problem inserting this evaluation question. The system administrator was informed of this error; please try again later.");

											application_log("error", "There was an error inserting an evaluation question. Database said: ".$db->ErrorMsg());
										}
									}
									if (!has_error()) {
										$SUCCESS++;
										$SUCCESSSTR[] = "You have successfully added this evaluation question to the system.<br /><br />".$msg;
										$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";


										application_log("success", "New evaluation question [".$equestion_id."] added.");
									}
								}
							} else {
								$PROCESSED["equestion_id"] = null;
								if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED, "INSERT") && ($equestion_id = $db->Insert_Id())) {
									if (isset($FORM_ID) && $FORM_ID && isset($PROCESSED["efquestion_id"]) && $PROCESSED["efquestion_id"]) {
										if (!$db->AutoExecute("evaluation_form_questions", array("equestion_id" => $equestion_id), "UPDATE", "`efquestion_id` = ".$db->qstr($PROCESSED["efquestion_id"]))) {
											add_error("There was an error while trying to attach the updated question to the form.<br /><br />The system administrator was informed of this error; please try again later.");

											application_log("error", "Unable to insert a new evaluation_form_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
										}
									}
									if (count($PROCESSED_RELATED_QUESTION)) {
										$PROCESSED_RELATED_QUESTION["equestion_id"] = $equestion_id;
										if (!$db->AutoExecute("evaluations_related_questions", $PROCESSED_RELATED_QUESTION, "INSERT")) {
											add_error("There was an error while trying to attach a related question to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

											application_log("error", "Unable to insert a new evaluations_related_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
										}
									}
									
									/**
									 * Add the question responses to the evaluations_lu_question_responses table.
									 * Ummm... we really need to switch to InnoDB tables to get transaction support.
									 */
									if ((is_array($PROCESSED["evaluation_question_responses"])) && (count($PROCESSED["evaluation_question_responses"]))) {
										foreach ($PROCESSED["evaluation_question_responses"] as $question_response) {
											$PROCESSED_RESPONSE = array (
															"equestion_id" => $equestion_id,
															"response_text" => $question_response["response_text"],
															"response_order" => $question_response["response_order"],
															"response_is_html" => $question_response["response_is_html"],
															"minimum_passing_level"	=> $question_response["minimum_passing_level"]
															);

											if ($db->AutoExecute("evaluations_lu_question_responses", $PROCESSED_RESPONSE, "INSERT") && ($eqresponse_id = $db->Insert_Id())) {
                                                $db->Execute("DELETE FROM `evaluation_question_response_descriptors` WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id));
                                                if (isset($question_response["erdescriptor_id"]) && $question_response["erdescriptor_id"]) {
                                                    $PROCESSED_RESPONSE_DESCRIPTOR = array(
                                                        "eqresponse_id" => $eqresponse_id,
                                                        "erdescriptor_id" => $question_response["erdescriptor_id"]
                                                    );
                                                    if (!$db->AutoExecute("evaluation_question_response_descriptors", $PROCESSED_RESPONSE_DESCRIPTOR, "INSERT")) {
                                                        add_error("There was an error while trying to attach a <strong>Response Descriptor</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

                                                        application_log("error", "Unable to insert a new evaluation_question_response_descriptors record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            } else {
												add_error("There was an error while trying to attach a <strong>Question Response</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

												application_log("error", "Unable to insert a new evaluations_lu_question_responses record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
											}
										}
									}
									
									/**
									 * Add the question objectives to the evaluation_question_objectives table.
									 */
									if ((isset($PROCESSED["objective_ids"])) && (@count($PROCESSED["objective_ids"]))) {
										foreach ($PROCESSED["objective_ids"] as $objective_id) {
											$PROCESSED_OBJECTIVE = array (
															"equestion_id" => $equestion_id,
															"objective_id" => $objective_id,
															"updated_date" => time(),
															"updated_by" => $ENTRADA_USER->getID()
															);
											if (!$db->AutoExecute("evaluation_question_objectives", $PROCESSED_OBJECTIVE, "INSERT")) {
												add_error("There was an error while trying to attach a <strong>Question Objective</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

												application_log("error", "Unable to insert a new evaluation_question_objectives record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
											}
										}
									}

									if (!isset($FORM_ID) || !$FORM_ID) {
										switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
											case "new" :
												$url = ENTRADA_URL."/admin/evaluations/questions?section=add";
												$msg = "You will now be redirected to add another evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "index" :
												$url = ENTRADA_URL."/admin/evaluations/questions";
												$msg = "You will now be redirected back to the evaluation question index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "content" :
											default :
												$url = ENTRADA_URL."/admin/evaluations/questions?section=edit&id=".$equestion_id;
												$msg = "You will now be redirected back to the evaluation question you just added; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
										}
									} else {
										$url = ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID;
										$msg = "You will now be redirected back to the form you were editing; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									}

									$SUCCESS++;
									$SUCCESSSTR[] = "You have successfully added this evaluation question to the system.<br /><br />".$msg;
									$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

									/**
									 * Unset the arrays used to construct this error checking.
									 */
									unset($PROCESSED);

									application_log("success", "New evaluation question [".$equestion_id."] added.");
								} else {
									add_error("There was a problem inserting this evaluation question. The system administrator was informed of this error; please try again later.");

									application_log("error", "There was an error inserting an evaluation question. Database said: ".$db->ErrorMsg());
								}
							}
						} else {
							if ($PROCESSED["questiontype_id"] == 3) {
								if ($db->AutoExecute("evaluations_lu_rubrics", $PROCESSED, "UPDATE", "`erubric_id` = ".$db->qstr($erubric_id))) {
									$old_questions = array();
									$min_order = false;
									$query = "SELECT b.* FROM `evaluation_rubric_questions` AS a
												JOIN `evaluations_lu_questions` AS b
												ON a.`equestion_id` = b.`equestion_id`
												WHERE a.`erubric_id` = ".$db->qstr($erubric_id)."
												ORDER BY a.`question_order` ASC";
									$categories = $db->GetAll($query);
									foreach ($categories as $category) {
										$db->Execute("DELETE FROM `evaluations_lu_questions` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"]));
										$db->Execute("DELETE FROM `evaluations_lu_question_responses` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"]));
										$db->Execute("DELETE FROM `evaluation_rubric_questions` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"]));
										$db->Execute("DELETE FROM `evaluation_question_objectives` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"]));
										if (isset($FORM_ID) && $FORM_ID) {
											$query = "SELECT * FROM `evaluation_form_questions` WHERE `eform_id` = ".$db->qstr($FORM_ID)." AND `equestion_id` = ".$db->qstr($category["equestion_id"]);
											$question = $db->GetRow($query);
											if ($question) {
												$old_questions[] = $question;
												if (!$min_order || $question["question_order"] < $min_order) {
													$min_order = $question["question_order"];
												}
												$db->Execute("DELETE FROM `evaluation_form_questions` WHERE `equestion_id` = ".$db->qstr($category["equestion_id"])." AND `eform_id` = ".$db->qstr($FORM_ID));
											}
										}
									}
									if (count($old_questions) != count($PROCESSED["evaluation_rubric_categories"])) {
										$order_offset = count($PROCESSED["evaluation_rubric_categories"]) - count($old_questions);
										if (isset($FORM_ID) && $FORM_ID) {
											$db->Execute("UPDATE `evaluation_form_questions` SET `question_order` = (`question_order` + (".$db->qstr((int)$order_offset).")) WHERE `question_order` > ".$db->qstr($min_order)." AND `eform_id` = ".$db->qstr($FORM_ID));
										}
									}
									$form_question_order = $min_order;
									$rubric_order = 1;
									foreach ($PROCESSED["evaluation_rubric_categories"] as $index => $category) {
										$PROCESSED_QUESTION = array("questiontype_id" => 3,
																	"organisation_id" => $PROCESSED["organisation_id"],
																	"question_text" => $category["category"],
																	"question_code" => $category["category"],
																	"allow_comments" => $PROCESSED["allow_comments"],
																	"question_parent_id" => (isset($category["category_parent_id"]) && (int)$category["category_parent_id"] ? (int)$category["category_parent_id"] : $default_parent_id));
										$equestion_id = 0;
										if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED_QUESTION, "INSERT") && ($equestion_id = $db->Insert_Id()) &&
												$db->AutoExecute("evaluation_rubric_questions", array("erubric_id" => $erubric_id, "equestion_id" => $equestion_id, "question_order" => $rubric_order), "INSERT")) {
											$rubric_order++;
											if (isset($FORM_ID) && $FORM_ID) {
												if (!$db->AutoExecute("evaluation_form_questions", array("equestion_id" => $equestion_id, "eform_id" => $FORM_ID, "question_order" => $form_question_order), "INSERT")) {
													add_error("There was an error while trying to attach the updated question to the form.<br /><br />The system administrator was informed of this error; please try again later.");

													application_log("error", "Unable to insert a new evaluation_form_questions record while e a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
												} else {
													$form_question_order++;
												}
											}
											if (count($PROCESSED_RELATED_QUESTION)) {
												$PROCESSED_RELATED_QUESTION["equestion_id"] = $equestion_id;
												if (!$db->AutoExecute("evaluations_related_questions", $PROCESSED_RELATED_QUESTION, "INSERT")) {
													add_error("There was an error while trying to attach a related question to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

													application_log("error", "Unable to insert a new evaluations_related_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
												}
											}
											/**
											 * Add the question objectives to the evaluation_question_objectives table.
											 */
											if ((isset($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"])) && (@count($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"]))) {
												foreach ($PROCESSED["evaluation_rubric_categories"][$index]["objective_ids"] as $objective_id) {
													$PROCESSED_OBJECTIVE = array (
																	"equestion_id" => $equestion_id,
																	"objective_id" => $objective_id,
																	"updated_date" => time(),
																	"updated_by" => $ENTRADA_USER->getID()
																	);
													if (!$db->AutoExecute("evaluation_question_objectives", $PROCESSED_OBJECTIVE, "INSERT")) {
														add_error("There was an error while trying to attach a <strong>Question Objective</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

														application_log("error", "Unable to insert a new evaluation_question_objectives record while editing an evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
													}
												}
											}
											/**
											 * Add the question responses to the evaluation_question_responses table.
											 */
											if ((is_array($PROCESSED["evaluation_question_responses"])) && (count($PROCESSED["evaluation_question_responses"]))) {
												foreach ($PROCESSED["evaluation_question_responses"] as $subindex => $question_response) {
													$PROCESSED_RESPONSE = array (
																	"equestion_id" => $equestion_id,
																	"response_text" => $question_response["response_text"],
																	"response_order" => $question_response["response_order"],
																	"response_is_html" => $question_response["response_is_html"],
																	"minimum_passing_level"	=> $question_response["minimum_passing_level"]
																	);
													$eqresponse_id = 0;
													if ($db->AutoExecute("evaluations_lu_question_responses", $PROCESSED_RESPONSE, "INSERT") && ($eqresponse_id = $db->Insert_Id())) {
                                                        $db->Execute("DELETE FROM `evaluation_question_response_descriptors` WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id));
                                                        if (isset($question_response["erdescriptor_id"]) && $question_response["erdescriptor_id"]) {
                                                            $PROCESSED_RESPONSE_DESCRIPTOR = array(
                                                                "eqresponse_id" => $eqresponse_id,
                                                                "erdescriptor_id" => $question_response["erdescriptor_id"]
                                                            );
                                                            if (!$db->AutoExecute("evaluation_question_response_descriptors", $PROCESSED_RESPONSE_DESCRIPTOR, "INSERT")) {
                                                                add_error("There was an error while trying to attach a <strong>Response Descriptor</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

                                                                application_log("error", "Unable to insert a new evaluation_question_response_descriptors record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
                                                            }
                                                        }
														/**
														 * Add the responses criteria to the evaluation_rubric_criteria table.
														 */
														if ((isset($PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"])) && ($PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"] || $PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"] === "")) {
															$PROCESSED_CRITERIA = array (
																			"eqresponse_id" => $eqresponse_id,
																			"criteria_text" => $PROCESSED["evaluation_rubric_category_criteria"][$index][$subindex]["criteria"],
																			);

															if (!$db->AutoExecute("evaluations_lu_question_response_criteria", $PROCESSED_CRITERIA, "INSERT")) {
																add_error("There was an error while trying to attach a <strong>Criteria</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

																application_log("error", "Unable to insert a new evaluations_lu_question_response_criteria record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
															}
														}
													} else {
														add_error("There was an error while trying to attach a <strong>Question Response</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

														application_log("error", "Unable to insert a new evaluations_lu_question_responses record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
													}
												}
											}

											if (!isset($FORM_ID) || !$FORM_ID) {
												switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
													case "new" :
														$url = ENTRADA_URL."/admin/evaluations/questions?section=add";
														$msg = "You will now be redirected to add another evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
													case "index" :
														$url = ENTRADA_URL."/admin/evaluations/questions";
														$msg = "You will now be redirected back to the evaluation questions index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
													case "content" :
													default :
														$url = ENTRADA_URL."/admin/evaluations/questions?section=edit&id=".$equestion_id;
														$msg = "You will now be redirected back to the evaluation question you just edited; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
													break;
												}
											} else {
												$url = ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID;
												$msg = "You will now be redirected back to the form you were editing; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											}
										} else {
											add_error("There was a problem inserting this evaluation question. The system administrator was informed of this error; please try again later.");

											application_log("error", "There was an error inserting an evaluation question. Database said: ".$db->ErrorMsg());
										}
									}
									if (!has_error()) {
										$SUCCESS++;
										$SUCCESSSTR[] = "You have successfully edited this evaluation question in the system.<br /><br />".$msg;
										$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";


										application_log("success", "New evaluation question [".$equestion_id."] added.");
									}
								}
							} else {
								if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED, "UPDATE", "`equestion_id` = ".$db->qstr($QUESTION_ID))) {
									if (count($PROCESSED_RELATED_QUESTION)) {
										$PROCESSED_RELATED_QUESTION["equestion_id"] = $QUESTION_ID;
										if (!$db->AutoExecute("evaluations_related_questions", $PROCESSED_RELATED_QUESTION, "INSERT")) {
											add_error("There was an error while trying to attach a related question to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

											application_log("error", "Unable to insert a new evaluations_related_questions record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
										}
									}
									$db->Execute("DELETE FROM `evaluations_lu_question_responses` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID));
									$db->Execute("DELETE FROM `evaluation_question_objectives` WHERE `equestion_id` = ".$db->qstr($QUESTION_ID));
									
									/**
									 * Add the question responses to the evaluations_lu_question_responses table.
									 * Ummm... we really need to switch to InnoDB tables to get transaction support.
									 */
									if ((is_array($PROCESSED["evaluation_question_responses"])) && (count($PROCESSED["evaluation_question_responses"]))) {
										foreach ($PROCESSED["evaluation_question_responses"] as $question_response) {
											$PROCESSED_RESPONSE = array (
															"equestion_id" => $QUESTION_ID,
															"response_text" => $question_response["response_text"],
															"response_order" => $question_response["response_order"],
															"response_is_html" => $question_response["response_is_html"],
															"minimum_passing_level"	=> $question_response["minimum_passing_level"]
															);

											if ($db->AutoExecute("evaluations_lu_question_responses", $PROCESSED_RESPONSE, "INSERT") && ($eqresponse_id = $db->Insert_Id())) {
                                                $db->Execute("DELETE FROM `evaluation_question_response_descriptors` WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id));
                                                if (isset($question_response["erdescriptor_id"]) && $question_response["erdescriptor_id"]) {
                                                    $PROCESSED_RESPONSE_DESCRIPTOR = array(
                                                        "eqresponse_id" => $eqresponse_id,
                                                        "erdescriptor_id" => $question_response["erdescriptor_id"]
                                                    );
                                                    if (!$db->AutoExecute("evaluation_question_response_descriptors", $PROCESSED_RESPONSE_DESCRIPTOR, "INSERT")) {
                                                        add_error("There was an error while trying to attach a <strong>Response Descriptor</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

                                                        application_log("error", "Unable to insert a new evaluation_question_response_descriptors record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            } else {
												add_error("There was an error while trying to attach a <strong>Question Response</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

												application_log("error", "Unable to insert a new evaluations_lu_question_responses record while adding a new evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
											}
										}
									}
									
									/**
									 * Add the question objectives to the evaluation_question_objectives table.
									 */
									if ((isset($PROCESSED["objective_ids"])) && (@count($PROCESSED["objective_ids"]))) {
										foreach ($PROCESSED["objective_ids"] as $objective_id) {
											$PROCESSED_OBJECTIVE = array (
															"equestion_id" => $QUESTION_ID,
															"objective_id" => $objective_id,
															"updated_date" => time(),
															"updated_by" => $ENTRADA_USER->getID()
															);
											if (!$db->AutoExecute("evaluation_question_objectives", $PROCESSED_OBJECTIVE, "INSERT")) {
												add_error("There was an error while trying to attach a <strong>Question Objective</strong> to this evaluation question.<br /><br />The system administrator was informed of this error; please try again later.");

												application_log("error", "Unable to insert a new evaluation_question_objectives record while editing an evaluation question [".$equestion_id."]. Database said: ".$db->ErrorMsg());
											}
										}
									}

									if (!isset($FORM_ID) || !$FORM_ID) {
										switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
											case "new" :
												$url = ENTRADA_URL."/admin/evaluations/questions?section=add";
												$msg = "You will now be redirected to add another evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "index" :
												$url = ENTRADA_URL."/admin/evaluations/questions";
												$msg = "You will now be redirected back to the evaluation question index page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
											case "content" :
											default :
												$url = ENTRADA_URL."/admin/evaluations/questions?section=edit&id=".$QUESTION_ID;
												$msg = "You will now be redirected back to the evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
											break;
										}
									} else {
										$url = ENTRADA_URL."/admin/evaluations/forms?section=edit&id=".$FORM_ID;
										$msg = "You will now be redirected back to the form you were editing; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									}

									$SUCCESS++;
									$SUCCESSSTR[] = "You have successfully added this evaluation question to the system.<br /><br />".$msg;
									$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

									/**
									 * Unset the arrays used to construct this error checking.
									 */
									unset($PROCESSED);

									application_log("success", "Evaluation question [".$QUESTION_ID."] updated.");
								} else {
									add_error("There was a problem inserting this evaluation question. The system administrator was informed of this error; please try again later.");

									application_log("error", "There was an error inserting an evaluation question. Database said: ".$db->ErrorMsg());
								}
							}
						}
					} else {
						add_error("You do not have permission to create this evaluation question. The system administrator was informed of this error; please try again later.");

						application_log("error", "There was an error inserting an evaluation question because the user [".$ENTRADA_USER->getID()."] didn't have permission to create an evaluation question.");
					}
				}

				if ($ERROR) {
					$STEP = 1;
				}
			break;
			case 1 :
			default :
				$PROCESSED["evaluation_question_responses"] = array();

				$query = "	SELECT a.*
							FROM `evaluations_lu_question_responses` AS a
							WHERE a.`equestion_id` = ".$db->qstr($QUESTION_ID)."
							ORDER BY a.`response_order` ASC";
				$results = $db->GetAll($query);
				if ($results) {
					$i = 1;
					$minimum_passed = false;
					$PROCESSED["responses_count"] = count($results);

					foreach ($results as $result) {
						if ($result["minimum_passing_level"]) {
							$minimum_passed = true;
						}
						$PROCESSED["evaluation_question_responses"][$i]["response_order"] = $result["response_order"];
						$PROCESSED["evaluation_question_responses"][$i]["response_correct"] = ($minimum_passed ? true : false);
						$PROCESSED["evaluation_question_responses"][$i]["response_is_html"] = $result["response_is_html"];
						$PROCESSED["evaluation_question_responses"][$i]["minimum_passing_level"] = $result["minimum_passing_level"];
                        $query = "SELECT `erdescriptor_id` FROM `evaluation_question_response_descriptors`
                                                WHERE `eqresponse_id` = ".$db->qstr($result["eqresponse_id"]);
                        $erdescriptor_id = $db->getOne($query);
                        if ($erdescriptor_id) {
                            $PROCESSED["evaluation_question_responses"][$i]["erdescriptor_id"] = $erdescriptor_id;
                        }

						if ($result["response_is_html"]) {
							$response_text = clean_input($result["response_text"], array("trim", "allowedtags"));
						} else {
							$response_text = clean_input($result["response_text"], array("trim"));
						}

						$PROCESSED["evaluation_question_responses"][$i]["response_text"] = $response_text;

						$i++;
					}
				}
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
				if (has_error() || has_notice()) {
					echo display_status_messages();
				}
				require_once("javascript/evaluations.js.php");
				$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js\"></script>";
				$HEAD[]	= "<script type=\"text/javascript\"> var SITE_URL = '".ENTRADA_URL."'; </script>";
                if (!in_array($PROCESSED["questiontype_id"], array(2, 4))) {
                    $HEAD[]	= "
                    <script type=\"text/javascript\">
                        jQuery(document).ready(function() {
                            modalDescriptorDialog = new Control.Modal($('false-link'), {
                                position:		'center',
                                overlayOpacity:	0.75,
                                closeOnClick:	'overlay',
                                className:		'modal',
                                fade:			true,
                                fadeDuration:	0.30,
                                width: 455
                            });
                        });

                        function openDescriptorDialog(response_number, erdescriptor_id) {
                            var url = '".ENTRADA_URL."/admin/evaluations/questions?section=api-descriptors&response_number='+response_number+'&organisation_id=".$ENTRADA_USER->getActiveOrganisation()."&erdescriptor_id='+erdescriptor_id;
                            new Ajax.Request(url, {
                                method: 'get',
                                onComplete: function(transport) {
                                    loaded = [];
                                    modalDescriptorDialog.container.update(transport.responseText);
                                    modalDescriptorDialog.open();
                                }
                            });
                        }
                    </script>";
                }
				if ($PROCESSED["questiontype_id"] == 3) {
					$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives_evaluation_rubric.js\"></script>";
					$HEAD[] = "<script type=\"text/javascript\">
					var modalObjectiveDialog;
					var ajax_url = '';

					jQuery(document).ready(function() {
						modalObjectiveDialog = new Control.Modal($('false-link'), {
							position:		'center',
							overlayOpacity:	0.75,
							closeOnClick:	'overlay',
							className:		'modal',
							fade:			true,
							fadeDuration:	0.30,
							width: 755,
							beforeOpen: function () {
								jQuery('#mapped_objectives').width('55%');
								jQuery('#default_objective_notice').hide();
								jQuery('#alternate_objective_notice').show();
							}
						});
					});

					function openObjectiveDialog (rownum) {
						var url = '".ENTRADA_URL."/api/evaluations-objectives-list.api.php?qrow='+rownum+'&ids='+$('objective_ids_string_'+rownum).value;
						if (url && (url != ajax_url)) {
							ajax_url = url;
							new Ajax.Request(ajax_url, {
								method: 'get',
								onComplete: function(transport) {
									loaded = [];
									modalObjectiveDialog.container.update(transport.responseText);
									modalObjectiveDialog.open();
								}
							});
						} else {
							modalObjectiveDialog.open();
						}
					}
					</script>";
				} else {
					$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives_evaluation_question.js\"></script>";
				}
				if (isset($PROCESSED["question_parent_id"]) && $PROCESSED["question_parent_id"]) {
					$query = "SELECT a.*, b.`questiontype_shortname`, b.`questiontype_title`
								FROM `evaluations_lu_questions` AS a
								JOIN `evaluations_lu_questiontypes` AS b
								ON a.`questiontype_id` = b.`questiontype_id`
								LEFT JOIN `evaluation_rubric_questions` AS c
								ON a.`equestion_id` = c.`equestion_id`
								LEFT JOIN `evaluations_related_questions` AS d
								ON a.`question_parent_id` = d.`related_equestion_id`
								WHERE (
									d.`equestion_id` = ".$db->qstr($PROCESSED["question_parent_id"])."
								    OR a.`question_parent_id` = ".$db->qstr($PROCESSED["question_parent_id"])."
									OR a.`equestion_id` = ".$db->qstr($PROCESSED["question_parent_id"])."
									OR a.`equestion_id` IN (
										SELECT d.`equestion_id` FROM `evaluation_rubric_questions` AS d
										JOIN `evaluation_rubric_questions` AS e
										ON d.`erubric_id` = e.`erubric_id`
										WHERE e.`equestion_id` = ".$db->qstr($PROCESSED["question_parent_id"])."
									)
								)
								GROUP BY a.`equestion_id`
								ORDER BY c.`erubric_id`, c.`question_order`, b.`questiontype_id`";
					$question_revisions = $db->GetAll($query);
					if ($question_revisions && count($question_revisions) > 1) {
						$question_revision_controls = Classes_Evaluation::getQuestionControlsArray($question_revisions);
						$HEAD[] = "<script type=\"text/javascript\">
						var question_controls = ".json_encode($question_revision_controls).";
						var modalDialog;
						var modalQuestionDialog;
						
						jQuery(document).ready(function() {
							modalDialog = new Control.Window($('false-link'), {
								position:		'center',
								className:		'default-tooltip',
								closeOnClick:	'overlay',
								width: 735
							});
							modalQuestionDialog = new Control.Modal($('question-revision-link'), {
								position:		'center',
								overlayOpacity:	0.75,
								closeOnClick:	'overlay',
								className:		'default-tooltip',
								fade:			true,
								fadeDuration:	0.30,
								width: 755
							});
						});

						function openDialog (equestion_id) {
							if (equestion_id) {
								modalDialog.container.update('<div id=\"form-questions-list\"><div style=\"float: right;\"><a href=\"javascript: modalDialog.close()\">Close</a></div>'+question_controls[equestion_id]+'</div>');
								modalDialog.open();
							} else {
								modalDialog.open();
							}
						}
						</script>";
						?>
						<div style="float: right;">
							<ul class="page-action">
								<li><a id="question-revision-link" style="cursor: pointer;" href="#modal-question-selector">Select a different Revision of this Question</a></li>
							</ul>
						</div>
						<div id="modal-question-selector" style="display: none">
							<table class="tableList" cellspacing="0" summary="List of Evaluation Questions">
							<colgroup>
								<col class="modified" />
								<col class="title" />
								<col class="type-title" />
								<col class="actions" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="title">Question</td>
									<td class="type-title">Question Type</td>
									<td class="actions" style="font-size: 12px">&nbsp;</td>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach($question_revisions as $result) {
									if (isset($question_revision_controls[$result["equestion_id"]])) {
										echo "<tr id=\"equestion-".$result["equestion_id"]."\" class=\"equestion\">\n";
										echo "	<td class=\"modified\">&nbsp;</td>\n";
										echo "	<td class=\"title\"><div class=\"evaluation-questions-list\">".$question_revision_controls[$result["equestion_id"]]."</div></td>\n";
										echo "	<td class=\"type-title\">".html_encode($result["questiontype_title"])."</td>\n";
										echo "	<td class=\"actions\"><img style=\"cursor: pointer;\" height=\"16\" width=\"16\" src=\"".ENTRADA_URL."/images/magnify.gif\" onclick=\"openDialog(".$result["equestion_id"].")\" alt=\"View Evaluation Question Full Size\" title=\"View Evaluation Question Full Size\" /> <a href=\"".ENTRADA_URL."/admin/evaluations/questions?".replace_query(array("id" => $result["equestion_id"]))."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Edit Evaluation Question\" title=\"Edit Evaluation Question\" border=\"0\" /></a></td>\n";
										echo "</tr>\n";
									}
								}
								?>
							</tbody>
							</table>
						</div>
						<?php
					}
				}
				?>
				<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?<?php echo replace_query(array("step" => 2)); ?>" method="post" id="addEvaluationQuestionForm">
				<table style="width: 100%; margin-bottom: 25px" cellspacing="0" cellpadding="2" border="0" summary="Add Evaluation Question">
				<colgroup>
					<col style="width: 20%" />
					<col style="width: 80%" />
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="2" style="padding-top: 25px">
							<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/evaluations/questions'" />
								</td>
								<td style="width: 75%; text-align: right; vertical-align: middle">
									<?php
									if (!isset($FORM_ID) || !$FORM_ID) { 
										?>
										<span class="content-small">After saving:</span>
										<select id="post_action" name="post_action">
											<option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Edit this question</option>
											<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another question</option>
											<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to the evaluation questions index</option>
										</select>
										<?php
									}
									?>
									<input type="submit" class="btn btn-primary" value="Proceed" />
								</td>
							</tr>
							</table>
						</td>
					</tr>
				</tfoot>
				<tbody id="form-content-add-question">
					<tr>
						<td style="vertical-align: top">
							<label for="questiontype_id" class="form-required">Question Type</label>
						</td>
						<td>
							<select onchange="window.location = '<?php echo ENTRADA_URL ?>/admin/evaluations/questions?section=edit&qtype_id='+this.options[this.selectedIndex].value+'&id=<?php echo $QUESTION_ID; ?>'" name="questiontype_id" id="questiontype_id">
								<option value="0"> --- Choose a question type --- </option>
								<?php
									$query = "SELECT * FROM `evaluations_lu_questiontypes`
												WHERE `questiontype_active` = 1";
									$questiontypes = $db->GetAll($query);
									if ($questiontypes) {
										foreach ($questiontypes as $questiontype) {
											echo "<option ".((isset($PROCESSED["questiontype_id"]) && $PROCESSED["questiontype_id"] == $questiontype["questiontype_id"]) || ((!isset($PROCESSED["questiontype_id"]) || !$PROCESSED["questiontype_id"]) && $questiontype["questiontype_id"] == 1) ? "selected=\"selected\" " : "")."value=\"".$questiontype["questiontype_id"]."\">".$questiontype["questiontype_title"]."</option>\n";
										}
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							&nbsp;
						</td>
					</tr>
					<?php
						echo Classes_Evaluation::getEditQuestionControls($PROCESSED);
					?>
				</tbody>
				</table>
				</form>
				<?php
			break;
		}
	} else {
		add_error("In order to edit a question in an evaluation form you must provide a valid identifier.");

		echo display_error();

		application_log("notice", "Failed to provide a valid identifer [".$FORM_ID."] when attempting to edit an evaluation form question.");
	}
}