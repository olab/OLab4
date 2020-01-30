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
 * This file is used by administrators to add evaluation questions to the system.
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
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationquestion", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/questions?section=add", "title" => "Add Question");
	/**
	 * Required field "questiontype_id" / Question Type
	 * Currently only multile choice questions are supported, although
	 * this is something we will be expanding on shortly.
	 */
	if ((isset($_POST["questiontype_id"])) && ($tmp_input = clean_input($_POST["questiontype_id"], array("trim", "int")))) {
		$PROCESSED["questiontype_id"] = $tmp_input;
	} elseif ((isset($_GET["qtype_id"])) && ($tmp_input = clean_input($_GET["qtype_id"], array("trim", "int")))) {
		$PROCESSED["questiontype_id"] = $tmp_input;
	} else {
		$PROCESSED["questiontype_id"] = 1;
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
							$response_text = clean_input($response_text, array("trim", "notags"));
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
					if ($PROCESSED["questiontype_id"] == 3) {
						if ($db->AutoExecute("evaluations_lu_rubrics", $PROCESSED, "INSERT") && ($erubric_id = $db->Insert_Id())) {
							$PROCESSED["question_order"]--;
							foreach ($PROCESSED["evaluation_rubric_categories"] as $index => $category) {
								$PROCESSED["question_order"]++;
								$PROCESSED_QUESTION = array("questiontype_id" => 3,
															"question_text" => $category["category"],
															"question_code" => $category["category"],
                                                            "question_description" => (isset($category["category_description"]) && $category["category_description"] ? $category["category_description"] : NULL),
															"organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
															"allow_comments" => $PROCESSED["allow_comments"]);
								$equestion_id = 0;
								if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED_QUESTION, "INSERT") && ($equestion_id = $db->Insert_Id()) &&
									$db->AutoExecute("evaluation_rubric_questions", array("erubric_id" => $erubric_id, "equestion_id" => $equestion_id, "question_order" => $PROCESSED["question_order"]), "INSERT")) {
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
						$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
						if ($db->AutoExecute("evaluations_lu_questions", $PROCESSED, "INSERT") && ($equestion_id = $db->Insert_Id())) {	
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

									if (!$db->AutoExecute("evaluations_lu_question_responses", $PROCESSED_RESPONSE, "INSERT") && ($eqresponse_id = $db->Insert_Id())) {
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
									$msg = "You will now be redirected back to the evaluation question; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
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
                        new Ajax.Request('".ENTRADA_URL."/admin/evaluations/questions?section=api-descriptors&response_number='+response_number+'&organisation_id=".$ENTRADA_USER->getActiveOrganisation()."&erdescriptor_id='+erdescriptor_id, {
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
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/evaluations/questions?section=add&amp;step=2" method="post" id="addEvaluationQuestionForm">
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
								<span class="content-small">After saving:</span>
								<select id="post_action" name="post_action">
									<option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Edit this question</option>
									<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another question</option>
									<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to the evaluation questions index</option>
								</select>

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
						<select onchange="window.location = '<?php echo ENTRADA_URL ?>/admin/evaluations/questions?section=add&qtype_id='+this.options[this.selectedIndex].value" name="questiontype_id" id="questiontype_id">
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
                if (in_array($questiontype["questiontype_shortname"], array("rubric", "selectbox", "matrix_single", "vertical_matrix"))) {


                }
            ?>
			<?php
		break;
	}
}