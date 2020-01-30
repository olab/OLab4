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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-responses" :
                    $response_array = array();
                    $answered_element_array = array();
                    $missing_elements = array();
                    $error = array();
                    $saved = array();

                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], "int")) {
                        $PROCESSED["post_id"] = $tmp_input;
                    }

                    if (isset($request["flagged_elements"]) && is_array($request["flagged_elements"]) && !empty($request["flagged_elements"])) {
                        foreach ($request["flagged_elements"] as $flagged) {
                            $PROCESSED["flagged"][$flagged["element_id"]]["flag"] = $flagged["flagged"];
                            $PROCESSED["flagged"][$flagged["element_id"]]["updated"] = 0;
                        }
                    }

                    if (isset($request["marked_for_faculty"]) && is_array($request["marked_for_faculty"]) && !empty($request["marked_for_faculty"])) {
                        $marked_for_faculty = array();
                        foreach ($request["marked_for_faculty"] as $exam_element_id => $marked) {
                            $marked_for_faculty[$exam_element_id] = array(
                                "updated" => 0,
                                "checked" => $marked["checked"]
                            );
                        }
                    }

                    if (isset($request["learner_comments"]) && is_array($request["learner_comments"]) && !empty($request["learner_comments"])) {
                        $learner_comments = array();
                        foreach ($request["learner_comments"] as $exam_element_id => $comments) {
                            $learner_comments[$exam_element_id] = array(
                                "updated" => 0,
                                "comments" => $comments
                            );;
                        }
                    }

                    //compiles the elements to update the strike outs.
                    if (isset($request["striked"]) && is_array($request["striked"]) && !empty($request["striked"])) {
                        $strike_out = array();
                        foreach ($request["striked"] as $exam_element_id => $answer_elements_striked) {
                            $strike_out[$exam_element_id] = array(
                                "updated"  => 0,
                                "elements" => $answer_elements_striked
                            );
                        }
                    }

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);

                    if (isset($progress) && is_object($progress) && $progress->getProgressValue() == "inprogress") {
                        if (isset($request["elements"]) && is_array($request["elements"])) {
                            foreach ($request["elements"] as $response) {
                                if (!in_array($response, $response_array)) {
                                    $response_array[] = (int)$response;
                                }
                            }
                        }

                        if (isset($request["responses"]) && is_array($request["responses"])) {
                            foreach ($request["responses"] as $response) {
                                if (isset($response["exam_element_id"]) && $tmp_input = clean_input($response["exam_element_id"], "int")) {
                                    $exam_element_id = $tmp_input;
                                    if (!in_array($exam_element_id, $answered_element_array)) {
                                        $answered_element_array[] = (int)$exam_element_id;
                                    }
                                }
                            }

                            foreach ($response_array as $exam_element_id) {
                                if (!in_array($exam_element_id, $answered_element_array)) {
                                    //$exam_element_id is missing from the set of answered elements and should be marked as deleted
                                    $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);
                                    if (isset($epr) && is_object($epr)) {
                                        if (isset($PROCESSED["flagged"][$exam_element_id])) {
                                            $epr->setFlagQuestion($PROCESSED["flagged"][$exam_element_id]["flag"]);
                                            $PROCESSED["flagged"][$exam_element_id]["update"] = 1;
                                        }
                                        //get answers and set as NULL
                                        $epr_answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($epr->getID());
                                        if (isset($epr_answers) && is_array($epr_answers)) {
                                            $saved_a = array();
                                            $error_a = array();
                                            $answer_count = count($epr_answers);
                                            foreach ($epr_answers as $epr_answer) {
                                                $epr_answer->setResponseValue(NULL);
                                                $epr_answer->setResponseElementOrder(NULL);
                                                $epr_answer->setResponseElementLetter(NULL);
                                                $epr_answer->setUpdateDate(time());
                                                if ($epr_answer->update()) {
                                                    $saved_a[] = $epr->getID();
                                                } else {
                                                    //error
                                                    $error_a[] = $epr->getID();
                                                }
                                            }
                                        }

                                        $epr->setUpdateDate(time());
                                        if (!$epr->update() || $answer_count !== count($saved_a)) {
                                            if (!in_array($exam_element_id, $error)) {
                                                $error[] = $exam_element_id;
                                            }
                                        } else {
                                            if (!in_array($exam_element_id, $saved)) {
                                                $saved[] = $exam_element_id;
                                            }
                                        }
                                    }
                                }
                            }

                            foreach ($request["responses"] as $response) {
                                if (isset($response["exam_element_id"]) && $tmp_input = clean_input($response["exam_element_id"], "int")) {
                                    $exam_element_id = $tmp_input;
                                }

                                if (isset($response["type"]) && $tmp_input = clean_input($response["type"], array("trim", "allowedtags"))) {
                                    $question_type = $tmp_input;
                                }

                                if (isset($response["qanswer_id"]) && $tmp_input = clean_input($response["qanswer_id"], "int")) {
                                    $eqa_id = $tmp_input;
                                } else {
                                    $eqa_id = NULL;
                                }

                                if (isset($response["response_value"]) && $tmp_input = clean_input($response["response_value"], array("trim"))) {
                                    $response_value = $tmp_input;
                                    if ($response_value == "true") {
                                        $response_value = 1;
                                    } else if ($response_value == "false") {
                                        $response_value = 0;
                                    }
                                } else {
                                    $response_value = NULL;
                                }

                                if (isset($response["order"])) {
                                    $tmp_input = clean_input($response["order"], "int");
                                    $order = $tmp_input;
                                } else {
                                    $order = NULL;
                                }

                                if (isset($response["match_order"])) {
                                    $tmp_input = clean_input($response["match_order"], "int");
                                    $match_order = $tmp_input;
                                } else {
                                    $match_order = NULL;
                                }

                                if (isset($response["match_id"])) {
                                    $tmp_input = clean_input($response["match_id"], "int");
                                    $match_id = $tmp_input;
                                } else {
                                    $match_id = NULL;
                                }

                                if (isset($response["letter"]) && $tmp_input = clean_input($response["letter"], array("trim", "allowedtags"))) {
                                    $letter = $tmp_input;
                                } else {
                                    $letter = NULL;
                                }

                                $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);
                                if (isset($epr) && is_object($epr)) {
                                    switch ($question_type) {
                                        case "mc_h_m" :
                                        case "mc_v_m":
                                        case "drop_m":
                                        case "fnb" :
                                            //get all possible answer elements
                                            $epr_answer = Models_Exam_Progress_Response_Answers::fetchRowByAnswerElement($epr->getID(), $eqa_id);
                                            if (isset($epr_answer) && is_object($epr_answer)) {
                                                $epr_answer->setResponseValue($response_value);
                                                $epr_answer->setUpdateDate(time());
                                                $epr_answer->setUpdatedBy($PROCESSED["proxy_id"]);

                                                if ($epr_answer->update()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            } else {
                                                //answer not saved yet
                                                $new_answer = new Models_Exam_Progress_Response_Answers(array(
                                                    "epr_id"                    => $epr->getID(),
                                                    "eqa_id"                    => $eqa_id,
                                                    "eqm_id"                    => NULL,
                                                    "response_value"            => $response_value,
                                                    "response_element_order"    => $order,
                                                    "response_element_letter"   => $letter,
                                                    "created_date"              => time(),
                                                    "created_by"                => $PROCESSED["proxy_id"],
                                                    "updated_date"              => NULL,
                                                    "updated_by"                => NULL
                                                ));

                                                if ($new_answer->insert()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            }
                                            break;
                                        case "match":
                                            //get all possible answer elements
                                            $epr_answer = Models_Exam_Progress_Response_Answers::fetchRowByResponseIdMatchId($epr->getID(), $match_id);
                                            if (isset($epr_answer) && is_object($epr_answer)) {
                                                $epr_answer->setAnswerID($eqa_id);
                                                $epr_answer->setResponseValue($response_value);
                                                $epr_answer->setResponseElementLetter($letter);
                                                $epr_answer->setResponseElementOrder($order);

                                                $epr_answer->setUpdateDate(time());
                                                $epr_answer->setUpdatedBy($PROCESSED["proxy_id"]);

                                                if ($epr_answer->update()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            } else {
                                                //answer not saved yet
                                                $new_answer = new Models_Exam_Progress_Response_Answers(array(
                                                    "epr_id"                    => $epr->getID(),
                                                    "eqa_id"                    => $eqa_id,
                                                    "eqm_id"                    => $match_id,
                                                    "response_value"            => $response_value,
                                                    "response_element_order"    => $order,
                                                    "response_element_letter"   => $letter,
                                                    "created_date"              => time(),
                                                    "created_by"                => $PROCESSED["proxy_id"],
                                                    "updated_date"              => NULL,
                                                    "updated_by"                => NULL
                                                ));

                                                if ($new_answer->insert()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            }
                                            break;
                                        case "short":
                                        case "essay":
                                            $epr_answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($epr->getID());
                                            if (isset($epr_answers) && is_array($epr_answers) && !empty($epr_answers)) {
                                                foreach ($epr_answers as $epr_answer) {
                                                    $epr_answer->setAnswerElementID(NULL);
                                                    $epr_answer->setResponseValue($response_value);
                                                    $epr_answer->setResponseElementOrder(NULL);
                                                    $epr_answer->setResponseElementLetter(NULL);
                                                    $epr_answer->setUpdateDate(time());
                                                    $epr_answer->setUpdatedBy($PROCESSED["proxy_id"]);

                                                    if ($epr_answer->update()) {
                                                        if (!in_array($exam_element_id, $saved)) {
                                                            $saved[] = $exam_element_id;
                                                        }
                                                    } else {
                                                        if (!in_array($exam_element_id, $error)) {
                                                            $error[] = $exam_element_id;
                                                        }
                                                    }
                                                }
                                            } else {
                                                //answer not saved yet
                                                $new_answer = new Models_Exam_Progress_Response_Answers(array(
                                                    "epr_id"                => $epr->getID(),
                                                    "eqa_id"                => NULL,
                                                    "response_value"        => $response_value,
                                                    "response_element_order"    => NULL,
                                                    "response_element_letter"   => NULL,
                                                    "created_date"              => time(),
                                                    "created_by"                => $PROCESSED["proxy_id"],
                                                    "updated_date"          => NULL,
                                                    "updated_by"            => NULL
                                                ));

                                                if ($new_answer->insert()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            }
                                            break;
                                        case "drop_s":
                                        case "mc_h":
                                        case "mc_v":
                                            $epr_answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($epr->getID());
                                            if (isset($epr_answers) && is_array($epr_answers) && !empty($epr_answers)) {
                                                foreach ($epr_answers as $epr_answer) {
                                                    $epr_answer->setAnswerElementID($eqa_id);
                                                    $epr_answer->setResponseValue($response_value);
                                                    $epr_answer->setResponseElementOrder($order);
                                                    $epr_answer->setResponseElementLetter($letter);
                                                    $epr_answer->setUpdateDate(time());
                                                    $epr_answer->setUpdatedBy($PROCESSED["proxy_id"]);

                                                    if ($epr_answer->update()) {
                                                        if (!in_array($exam_element_id, $saved)) {
                                                            $saved[] = $exam_element_id;
                                                        }
                                                    } else {
                                                        if (!in_array($exam_element_id, $error)) {
                                                            $error[] = $exam_element_id;
                                                        }
                                                    }
                                                }
                                            } else {
                                                //answer not saved yet
                                                $new_answer = new Models_Exam_Progress_Response_Answers(array(
                                                    "epr_id"                => $epr->getID(),
                                                    "eqa_id"                => $eqa_id,
                                                    "response_value"        => $response_value,
                                                    "response_element_order"    => $order,
                                                    "response_element_letter"   => $letter,
                                                    "created_date"              => time(),
                                                    "created_by"                => $PROCESSED["proxy_id"],
                                                    "updated_date"          => NULL,
                                                    "updated_by"            => NULL
                                                ));

                                                if ($new_answer->insert()) {
                                                    if (!in_array($exam_element_id, $saved)) {
                                                        $saved[] = $exam_element_id;
                                                    }
                                                } else {
                                                    if (!in_array($exam_element_id, $error)) {
                                                        $error[] = $exam_element_id;
                                                    }
                                                }
                                            }
                                            break;
                                    }

                                    $epr->setQuestionType($question_type);
                                    $epr->setUpdateDate(time());

                                    if (!$epr->update()) {
                                        if (!in_array($exam_element_id, $error)) {
                                            $error[] = $exam_element_id;
                                        }
                                    } else {
                                        if (!in_array($exam_element_id, $saved)) {
                                            $saved[] = $exam_element_id;
                                        }
                                    }

                                    if (isset($strike_out) && is_array($strike_out)) {
                                        if (isset($strike_out[$exam_element_id]) && is_array($strike_out[$exam_element_id]) && !empty($strike_out[$exam_element_id]) && $strike_out[$exam_element_id]["updated"] == 0) {
                                            $current_strike_out = unserialize($epr->getStrikeOutAnswers());
                                            if (!$current_strike_out) {
                                                $current_strike_out = array();
                                            }

                                            foreach ($strike_out[$exam_element_id]["elements"] as $element_strike => $action) {
                                                if ($action == "add") {
                                                    $current_strike_out[$element_strike] = 1;
                                                } else if ($action == "remove") {
                                                    $current_strike_out[$element_strike] = 0;
                                                }
                                            }

                                            $epr->setStrikeOutAnswers(serialize($current_strike_out));
                                            $strike_out[$exam_element_id]["updated"] =  1;
                                        }
                                    }

                                    if (isset($marked_for_faculty) && is_array($marked_for_faculty)) {
                                        if (isset($marked_for_faculty[$exam_element_id]) && is_array($marked_for_faculty[$exam_element_id]) && !empty($marked_for_faculty[$exam_element_id]) && $marked_for_faculty[$exam_element_id]["updated"] == 0) {
                                            $epr->setMarkFacultyReview($marked_for_faculty[$exam_element_id]["checked"]);
                                            $marked_for_faculty[$exam_element_id]["update"] = 1;
                                        }
                                    }

                                    if (isset($learner_comments) && is_array($learner_comments)) {
                                        if (isset($learner_comments[$exam_element_id]) && is_array($learner_comments[$exam_element_id]) && !empty($learner_comments[$exam_element_id]) && $learner_comments[$exam_element_id]["updated"] == 0) {
                                            $epr->setLearnerComments($learner_comments[$exam_element_id]["comments"]);
                                            $learner_comments[$exam_element_id]["update"] = 1;
                                        }
                                    }

                                    if (isset($PROCESSED["flagged"][$exam_element_id])) {
                                        $epr->setFlagQuestion($PROCESSED["flagged"][$exam_element_id]["flag"]);
                                        $PROCESSED["flagged"][$exam_element_id]["update"] = 1;
                                    }

                                    if (!$epr->update()) {
                                        if (!in_array($exam_element_id, $error)) {
                                            $error[] = $exam_element_id;
                                        }
                                    } else {
                                        if (!in_array($exam_element_id, $saved)) {
                                            $saved[] = $exam_element_id;
                                        }
                                    }
                                } else {
                                    $error[] = $exam_element_id;
                                }
                            }
                        }

                        if (isset($PROCESSED["flagged"]) && is_array($PROCESSED["flagged"]) && !empty($PROCESSED["flagged"])) {
                            foreach ($PROCESSED["flagged"] as $exam_element_id => $flagged_element) {
                                if ($flagged_element["updated"] == 0) {
                                    //update answer $flagged_element["flag"]
                                    $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);
                                    $current_strike_out = unserialize($epr->getStrikeOutAnswers());
                                    if (!$current_strike_out) {
                                        $current_strike_out = array();
                                    }
                                    if (isset($epr) && is_object($epr)) {
                                        if (isset($PROCESSED["flagged"][$exam_element_id])) {
                                            $epr->setFlagQuestion($PROCESSED["flagged"][$exam_element_id]["flag"]);
                                            $epr->setUpdateDate(time());
                                            $PROCESSED["flagged"][$exam_element_id]["update"] = 1;
                                        }

                                        if (isset($strike_out[$exam_element_id]) && is_array($strike_out[$exam_element_id]) && !empty($strike_out[$exam_element_id]) && $strike_out[$exam_element_id]["updated"] == 0) {
                                            foreach ($strike_out[$exam_element_id]["elements"] as $element_strike => $action) {
                                                if ($action == "add") {
                                                    $current_strike_out[$element_strike] = 1;
                                                } else if ($action == "remove") {
                                                    $current_strike_out[$element_strike] = 0;
                                                }
                                            }
                                            $epr->setStrikeOutAnswers(serialize($current_strike_out));
                                            $strike_out[$exam_element_id]["updated"] =  1;
                                        }

                                        if (!$epr->update()) {
                                            if (!in_array($exam_element_id, $error)) {
                                                $error[] = $exam_element_id;
                                            }
                                        } else {
                                            if (!in_array($exam_element_id, $saved)) {
                                                $saved[] = $exam_element_id;
                                            }
                                        }
                                    } else {
                                        $error[] = $exam_element_id;
                                    }
                                }
                            }
                        }

                        if (isset($strike_out) && is_array($strike_out)) {
                            foreach ($strike_out as $exam_element_id => $strike_data) {
                                //data hasn't been updated yet so update alone
                                if (isset($strike_data) && is_array($strike_data) && !empty($strike_data)) {
                                    if ($strike_data["updated"] == 0) {
                                        $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);

                                        $current_strike_out = unserialize($epr->getStrikeOutAnswers());
                                        if (!$current_strike_out) {
                                            $current_strike_out = array();
                                        }

                                        foreach ($strike_data["elements"] as $element_strike => $action) {
                                            if ($action == "add") {
                                                $current_strike_out[$element_strike] = 1;
                                            } else if ($action == "remove") {
                                                $current_strike_out[$element_strike] = 0;
                                            }
                                        }
                                        $epr->setStrikeOutAnswers(serialize($current_strike_out));
                                        $strike_out[$exam_element_id]["updated"] =  1;

                                        if (!$epr->update()) {
                                            if (!in_array($exam_element_id, $error)) {
                                                $error[] = $exam_element_id;
                                            }
                                        } else {
                                            if (!in_array($exam_element_id, $saved)) {
                                                $saved[] = $exam_element_id;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($marked_for_faculty) && is_array($marked_for_faculty)) {
                            foreach ($marked_for_faculty as $exam_element_id => $marked) {
                                if ($marked["updated"] == 0) {
                                    $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);
                                    $epr->setMarkFacultyReview($marked_for_faculty[$exam_element_id]["checked"]);
                                    $marked_for_faculty[$exam_element_id]["update"] = 1;

                                    if (!$epr->update()) {
                                        if (!in_array($exam_element_id, $error)) {
                                            $error[] = $exam_element_id;
                                        }
                                    } else {
                                        if (!in_array($exam_element_id, $saved)) {
                                            $saved[] = $exam_element_id;
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($learner_comments) && is_array($learner_comments)) {
                            foreach ($learner_comments as $exam_element_id => $comment) {
                                if ($comment["updated"] == 0) {
                                    $epr = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["exam_id"], $PROCESSED["post_id"], $PROCESSED["proxy_id"], $exam_element_id);
                                    $epr->setLearnerComments($learner_comments[$exam_element_id]["comments"]);
                                    $learner_comments[$exam_element_id]["update"] = 1;

                                    if (!$epr->update()) {
                                        if (!in_array($exam_element_id, $error)) {
                                            $error[] = $exam_element_id;
                                        }
                                    } else {
                                        if (!in_array($exam_element_id, $saved)) {
                                            $saved[] = $exam_element_id;
                                        }
                                    }
                                }
                            }
                        }

                        $progress->setUpdatedDate(time());
                        $progress->setUpdateBy($PROCESSED["proxy_id"]);
                        $progress->update();

                        //gets the current progress bar
                        $progress_view = new Views_Exam_Progress($progress);
                        $progress_bar = $progress_view->renderExamProgressBar();

                        if (count($error) <= 0) {
                            if (count($saved) <= 0) {
                                echo json_encode(array("status" => "warning", "data" => $translate->_("Saved") . " " . count($saved) . " " .  ($saved > 1 ? $translate->_("question") : $translate->_("questions")) . "."));
                            } else {
                                echo json_encode(array("status" => "success", "data" => $translate->_("Saved") . " " . count($saved) . " " .  ($saved > 1 ? $translate->_("question") : $translate->_("questions")) . ".", "bar" => $progress_bar, "saved" => $saved));
                            }

                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to save") . " " . count($error) . " " . ($error > 1 ? $translate->_("question") : $translate->_("questions")) . "."));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Exam already submitted.")));
                    }

                    break;
                case "instructions-viewed" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);
                    if (isset($progress) && is_object($progress)) {
                        $progress->setStartedDate(time());
                        if ($progress->update()){
                            $post = $progress->getExamPost();
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["instructions_viewed"] = true;
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["attempt_in_progress"] = true;
                            echo json_encode(array("status" => "success", "data" => array("message"=> $translate->_("Set exam start date successfully"), "date" => $progress->getStartedDate())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to set exam start date")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to set exam start date")));
                    }

                    break;
                case "submit-exam" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);

                    if (isset($progress) && is_object($progress)) {
                        $progress_view = new Views_Exam_Progress($progress);
                        $submitted = $progress_view->gradeExam();
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to submit exam.")));
                    }

                    if (isset($submitted) && $submitted === true) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Submitted exam successfully.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to submit exam.")));
                    }
                    break;
                case "check-responses" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    $response_array = array();
                    $response_order = array();
                    $question_numbers_missing = array();
                    $count_missing = 0;

                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], "int")) {
                        $PROCESSED["post_id"] = $tmp_input;
                    }

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);

                    if (isset($progress) && is_object($progress)) {
                        $responses = Models_Exam_Progress_Responses::fetchAllByProgressID($progress->getID());
                        if (isset($responses) && is_array($responses)) {
                            foreach ($responses as $response) {
                                if (isset($response) && is_object($response)) {
                                    $response_question_count = $response->getQuestionCount();

                                    //gets all the response answer for the current question that are clicked
                                    //if it's set and has a value then the question has been answered.
                                    $answer_response = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseIDTrue($response->getID());

                                    if (isset($response_question_count) && !empty($response_question_count)) {
                                        if (isset($answer_response) && is_array($answer_response) && count($answer_response) && $answer_response[0]->getResponseValue() != NULL) {

                                        } else {
                                            $count_missing++;
                                            $question_numbers_missing[] = $response_question_count;
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($question_numbers_missing) && is_array($question_numbers_missing) && !empty($question_numbers_missing)) {
                            $missing = implode(", ", $question_numbers_missing);
                        }

                        if (isset($count_missing) && $count_missing > 0) {
                            echo json_encode(array("status" => "warning", "count" => $count_missing, "missing" => $missing));
                        } else {
                            echo json_encode(array("status" => "success"));
                        }
                    }

                    break;
                case "update-flagged" :

                    if (isset($request["response_id"]) && $tmp_input = clean_input($request["response_id"], "int")) {
                        $PROCESSED["response_id"] = $tmp_input;
                    }

                    $updated = 0;
                    $response = Models_Exam_Progress_Responses::fetchRowByID($PROCESSED["response_id"]);

                    if (isset($response) && is_object($response)) {
                        $version_id = $response->getElement()->getElementID();

                        $flagged = $response->getFlagQuestion();
                        if ($flagged == 1) {
                            $response->setFlagQuestion(0);
                            if ($response->update()) {
                                $updated = 1;
                            }
                        }
                    }

                    if ($updated === 1) {
                        echo json_encode(array("status" => "success", "version_id" => $version_id, "data" => $translate->_("Updated flagged item successfully.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update flagged item.")));
                    }

                    break;
                case "save-highlight" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    }

                    if (isset($request["element_id"]) && $tmp_input = clean_input($request["element_id"], "int")) {
                        $PROCESSED["element_id"] = $tmp_input;
                    }

                    if (isset($request["order"])) {
                        $tmp_input = clean_input($request["order"], "int");
                        if ($tmp_input != null) {
                            $PROCESSED["q_order"] = $tmp_input;
                        } else {
                            $PROCESSED["q_order"] = 0;
                        }
                    } else {
                        $PROCESSED["q_order"] = 0;
                    }

                    if (isset($request["type"])) {
                        $tmp_input = clean_input($request["type"], array("trim"));
                        $PROCESSED["type"] = $tmp_input;
                    }

                    if (isset($request["highlight_text"]) && $tmp_input = clean_input($request["highlight_text"], array("trim"))) {
                        $PROCESSED["highlight_text"] = $tmp_input;
                    }

                    $updated = 1;

                    switch ($PROCESSED["type"]) {
                        case "question_text":
                            $current_highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionId($PROCESSED["exam_progress_id"], $PROCESSED["proxy_id"], $PROCESSED["element_id"], $PROCESSED["type"]);
                            if ($current_highlight) {
                                $current_highlight->setQuestionText($PROCESSED["highlight_text"]);
                                $current_highlight->setUpdatedDate(time());
                                $current_highlight->setUpdatedBy($ENTRADA_USER->getID());
                                $current_highlight->setOrder(NULL);

                                if (!$current_highlight->update()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "update";
                                }
                            } else {
                                $current_highlight = new Models_Exam_Question_Version_Highlight(array(
                                    "version_id"        => $PROCESSED["element_id"],
                                    "question_text"     => $PROCESSED["highlight_text"],
                                    "type"              => $PROCESSED["type"],
                                    "exam_progress_id"  => $PROCESSED["exam_progress_id"],
                                    "proxy_id"          => $PROCESSED["proxy_id"],
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID()
                                ));

                                if (!$current_highlight->insert()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "insert";
                                }
                            }
                            break;
                        case "element_text":
                            $current_highlight = Models_Exam_Exam_Element_Highlight::fetchRowByProgressIdProxyIdElementId($PROCESSED["exam_progress_id"], $PROCESSED["proxy_id"], $PROCESSED["element_id"]);
                            if ($current_highlight) {
                                $current_highlight->setElementText($PROCESSED["highlight_text"]);
                                $current_highlight->setUpdatedDate(time());
                                $current_highlight->setUpdatedBy($ENTRADA_USER->getID());

                                if (!$current_highlight->update()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "update";
                                }
                            } else {
                                $current_highlight = new Models_Exam_Exam_Element_Highlight(array(
                                    "exam_element_id"   => $PROCESSED["element_id"],
                                    "element_text"      => $PROCESSED["highlight_text"],
                                    "exam_progress_id"  => $PROCESSED["exam_progress_id"],
                                    "proxy_id"          => $PROCESSED["proxy_id"],
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID()
                                ));

                                if (!$current_highlight->insert()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "insert";
                                }
                            }
                            break;
                        case "answer_text":
                        case "match_text":
                        case "fnb_text":
                            $current_highlight = Models_Exam_Question_Version_Highlight::fetchRowByProgressIdProxyIdQVersionIdOrder($PROCESSED["exam_progress_id"], $PROCESSED["proxy_id"], $PROCESSED["element_id"], $PROCESSED["q_order"], $PROCESSED["type"]);
                            if ($current_highlight) {
                                $current_highlight->setQuestionText($PROCESSED["highlight_text"]);
                                $current_highlight->setUpdatedDate(time());
                                $current_highlight->setUpdatedBy($ENTRADA_USER->getID());

                                if (!$current_highlight->update()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "update";
                                }
                            } else {
                                $current_highlight = new Models_Exam_Question_Version_Highlight(array(
                                    "version_id"        => $PROCESSED["element_id"],
                                    "q_order"           => $PROCESSED["q_order"],
                                    "type"              => $PROCESSED["type"],
                                    "question_text"     => $PROCESSED["highlight_text"],
                                    "exam_progress_id"  => $PROCESSED["exam_progress_id"],
                                    "proxy_id"          => $PROCESSED["proxy_id"],
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID()
                                ));

                                if (!$current_highlight->insert()) {
                                    // error
                                    $updated = 0;
                                    $type_failure = "insert";
                                }
                            }

                            break;
                    }

                    if ($updated === 1) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Updated highlighted item successfully.")));
                    } else {
                        if ($type_failure === "update") {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update highlighted item.")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to insert highlighted item.")));
                        }
                    }
                break;
                case "save_active_card_preference":
                    if (isset($request["card"]) && $tmp_input = clean_input($request["card"], array("trim", "allowedtags"))) {
                        $new_card = $tmp_input;
                    }

                    $current_card = preference_module_set("card_selected", $new_card, "", "exams");
                    preferences_update("exams");
                    break;

                case "save-self-timer" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    }

                    if (isset($request["use_self_timer"]) && $tmp_input = clean_input($request["use_self_timer"], "int")) {
                        $PROCESSED["use_self_timer"] = $tmp_input;
                    }

                    if (isset($request["time_limit_hours"]) && $tmp_input = clean_input($request["time_limit_hours"], "int")) {
                        $PROCESSED["time_limit_hours"] = $tmp_input;
                    } else {
                        $PROCESSED["time_limit_hours"] = 0;
                    }

                    if (isset($request["time_limit_mins"]) && $tmp_input = clean_input($request["time_limit_mins"], "int")) {
                        $PROCESSED["time_limit_mins"] = $tmp_input;
                    } else {
                        $PROCESSED["time_limit_mins"] = 0;
                    }

                    if (isset($PROCESSED["time_limit_hours"]) && isset($PROCESSED["time_limit_mins"])) {
                        $hours      = (int)$PROCESSED["time_limit_hours"] * 60;
                        $minutes    = (int)$PROCESSED["time_limit_mins"];
                        $PROCESSED["time_limit"] = $hours + $minutes;
                    }

                    $updated = 0;

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);

                    if ($progress && is_object($progress)) {
                        $time = time();
                        $progress->setUseSelfTimer($PROCESSED["use_self_timer"]);
                        $progress->setSelfTimerLength($PROCESSED["time_limit"]);
                        $progress->setSelfTimerStart($time);

                        if ($progress->update()) {
                            $updated = 1;
                        }
                    }

                    if ($updated === 1) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Updated timer successfully."), "start_time" => $time , "start_length" => $PROCESSED["time_limit"]));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update timer")));
                    }

                    break;
                case "verify-exam-password" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], "int")) {
                        $PROCESSED["post_id"] = $tmp_input;
                    }

                    if (isset($request["password"]) && $tmp_input = clean_input($request["password"], "notags", "trim")) {
                        $PROCESSED["password"] = $tmp_input;
                    } else {
                        add_error("Please provide a password");
                    }

                    if (isset($PROCESSED["post_id"]) && isset($PROCESSED["password"])) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        if ($post && ($post->getResumePassword() == $PROCESSED["password"])) {
                            $_SESSION["ExamAuth"] = array();
                            $_SESSION["ExamAuth"][$PROCESSED["post_id"]] = true;
                            add_success("Valid password");
                        } else {
                            add_error("Invalid password");
                        }
                    }
                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $SUCCESSSTR));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;

                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;

            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-menu-item" :
                    if (isset($request["response_id"]) && $tmp_input = clean_input($request["response_id"], "int")) {
                        $PROCESSED["response_id"] = $tmp_input;
                        $response = Models_Exam_Progress_Responses::fetchRowByID($PROCESSED["response_id"]);
                    } else {
                        if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                            $PROCESSED["exam_progress_id"] = $tmp_input;
                        }

                        if (isset($request["element_id"]) && $tmp_input = clean_input($request["element_id"], "int")) {
                            $PROCESSED["element_id"] = $tmp_input;
                        }

                        $response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($PROCESSED["exam_progress_id"], $PROCESSED["element_id"]);
                    }

                    if (isset($request["flagged"]) && $tmp_input = clean_input($request["flagged"], "int")) {
                        $PROCESSED["flagged"] = $tmp_input;
                    }

                    if (isset($request["comment"]) && $tmp_input = clean_input($request["comment"], "int")) {
                        $PROCESSED["comment"] = $tmp_input;
                    }

                    if (isset($request["current_page"]) && $tmp_input = clean_input($request["current_page"], "int")) {
                        $PROCESSED["current_page"] = $tmp_input;
                    }

                    if (isset($response) && is_object($response)) {
                        $exam_element = $response->getElement();

                        if ($PROCESSED["flagged"] && $PROCESSED["flagged"] == 1) {
                            $response->setFlagQuestion(1);
                        }

                        if ($PROCESSED["comment"] && $PROCESSED["comment"] == 1) {
                            $response->setLearnerComments(1);
                        } else {
                            $response->setLearnerComments("");
                        }

                        $response_view = new Views_Exam_Progress_Response($response, $exam_element);
                        $li = 0;
                        $html .= $response_view->renderProgressMenuItem($li, true, false, $PROCESSED["current_page"]);
                    }

                    echo json_encode(array("status" => "success", "html" => $html));
                break;

                case "get-menu" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], "int")) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    }

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], "int")) {
                        $PROCESSED["post_id"] = $tmp_input;
                    }

                    if (isset($request["status"]) && $tmp_input = clean_input($request["status"], "int")) {
                        $PROCESSED["status"] = $tmp_input;
                    }

                    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);

                    if (isset($progress) && is_object($progress)) {
                        $html = "";
                        $progress->setMenuOpen($PROCESSED["status"]);
                        $progress->update();
                        $post = $progress->getExamPost();

                        $progress_view = new Views_Exam_Progress($progress);
                        if (isset($progress_view) && is_object($progress_view)) {
                            $use_time_limit = $post->getUseTimeLimit();
                            $use_calculator = $post->getUseCalculator();
//                            if ($use_time_limit) {
//                                $html .= $progress_view->renderClockHeader();
//                                $html .= $progress_view->renderClock();
//                            }
                            if ($use_calculator) {
                                $html .= $progress_view->renderCalculatorHeader();
                                $html .= $progress_view->renderCalculator();
                            }
                            $html .= $progress_view->renderSideBarHeader();
                            $html .= $progress_view->renderSideBar();
                        }

                        echo json_encode(array("status" => "success", "html" => $html));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("The exam progress could not be found.")));
                    }

                    break;
                case "get-activity-rows":
                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], "int")) {
                        $PROCESSED["post_id"] = $tmp_input;
                    }
                    $MODULE_TEXT    = $translate->_($MODULE);
                    $SECTION_TEXT   = $MODULE_TEXT["posts"];

                    $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProxyID($PROCESSED["post_id"], $ENTRADA_USER->getID());
                    if (isset($progress_attempts) && is_array($progress_attempts) && !empty($progress_attempts)) {
                        $html .= "<table class=\"table table-bordered table-striped\" id=\"exam-progress-records\">\n";
                            $html .= "<tr class=\"headers\">\n";
                                $html .= "<th>" . $SECTION_TEXT["table_headers"]["progress_value"] . "</th>\n";
                                $html .= "<th>" . $SECTION_TEXT["table_headers"]["submission_date"] . "</th>\n";
                                $html .= "<th>" . $SECTION_TEXT["table_headers"]["exam_points"] . "</th>\n";
                                $html .= "<th>" . $SECTION_TEXT["table_headers"]["exam_value"] . "</th>\n";
                                $html .= "<th>" . $SECTION_TEXT["table_headers"]["exam_score"] . "</th>\n";
                                $html .= "<th></th>\n";
                            $html .= "</tr>\n";
                            foreach ($progress_attempts as $progress) {
                                if (isset($progress) && is_object($progress)) {
                                    $progress_view = new Views_Exam_Progress($progress);
                                    $html .= $progress_view->renderPublicRow();
                                }
                            }
                        $html .= "</table>\n";

                        echo json_encode(array("status" => "success", "html" => $html));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No attempts could be found.")));
                    }

                    break;
            }
            break;
        default :
            echo json_encode(array("status" => "error", "data" => $translate->_("Invalid request method.")));
            break;
    }

    exit;

}