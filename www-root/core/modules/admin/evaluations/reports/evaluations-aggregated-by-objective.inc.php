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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?section=evaluations-aggregated-by-objective", "title" => "Objective Aggregate Evaluation Report ");
	switch ($STEP) {
        case 4 :
            if (isset($_GET["report_start"]) && $_GET["report_start"] && strtotime($_GET["report_start"])) {
                $report_start = trim($_GET["report_start"]);
                $PROCESSED["report_start"] = strtotime($report_start . " 00:00");
            } else {
                add_error("The <strong>Report Start</strong> date is required. Please set a date for the report to start from to continue.");
            }

            if (isset($_GET["report_finish"]) && $_GET["report_finish"] && strtotime($_GET["report_finish"]) && (!isset($PROCESSED["report_start"]) || $PROCESSED["report_start"] < strtotime($_GET["report_finish"]))) {
                $report_finish = trim($_GET["report_finish"]);
                $PROCESSED["report_finish"] = strtotime($report_finish . " 23:59");
            } else {
                add_error("The <strong>Report Finish</strong> date is required. Please set a date for the report to end on (which is higher than the start date) to continue.");
            }

            if (isset($_GET["objective_id"]) && ($tmp_objective_id = clean_input($_GET["objective_id"], "int"))) {
                $PROCESSED["specific_objective_id"] = $tmp_objective_id;
                $PROCESSED["objective_ids_string"] = "";
                $PROCESSED["objective_ids"] = array();
                $query = "SELECT `objective_id` FROM `global_lu_objectives` WHERE `objective_parent` = ".$db->qstr($tmp_objective_id);
                $objectives = $db->GetAll($query);
                if ($objectives) {
                    foreach ($objectives as $objective) {
                        $PROCESSED["objective_ids"][] = ((int)$objective["objective_id"]);
                        $PROCESSED["objective_ids_string"] .= ($PROCESSED["objective_ids_string"] ? ", " : "").$db->qstr($objective["objective_id"]);
                    }
                }
            } else {
                add_error("The <strong>Objectives</strong> are required. Please choose at least one objective to report on to continue.");
            }

            if (isset($_GET["objective_parents"]) && ($tmp_input = explode(",", $_GET["objective_parents"])) && @count($tmp_input)) {
                $PROCESSED["parent_objective_ids_string"] = "";
                $PROCESSED["parent_objective_id"] = 0;
                $PROCESSED["parent_objective_ids"] = array();
                foreach ($tmp_input as $parent_objective_id) {
                    $PROCESSED["parent_objective_id"] = ((int)$parent_objective_id);
                    $PROCESSED["parent_objective_ids"][] = ((int)$parent_objective_id);
                    $PROCESSED["parent_objective_ids_string"] .= ($PROCESSED["parent_objective_ids_string"] ? "," : "").((int)$parent_objective_id);
                }
            }

            if ((isset($_GET["target_id"])) && $_GET["target_id"] == "3,9") {
                $PROCESSED["target_id"] = "3,9";
                $PROCESSED["target_ids_string"] = $db->qstr(3).", ".$db->qstr(9);
            } elseif ((isset($_GET["target_id"])) && ($tmp_input = clean_input($_GET["target_id"], array("int")))) {
                $PROCESSED["target_id"] = $tmp_input;
                $PROCESSED["target_ids_string"] = $db->qstr($tmp_input);
            } else {
                add_error("The <strong>Target Type</strong> is required. Please choose a target type to report on to continue.");
            }

            if (isset($_GET["evaluation_ids_string"]) && ($temp_evaluation_ids = explode(",", $_GET["evaluation_ids_string"])) && @count($temp_evaluation_ids)) {
                $PROCESSED["evaluation_ids_string"] = "";
                $PROCESSED["evaluation_ids"] = array();
                foreach ($temp_evaluation_ids as $evaluation_id) {
                    $PROCESSED["evaluation_ids"][] = ((int)$evaluation_id);
                    $PROCESSED["evaluation_ids_string"] .= ($PROCESSED["evaluation_ids_string"] ? ", " : "").$db->qstr($evaluation_id);
                }
            } else {
                add_error("The <strong>Evaluations</strong> are required. Please choose at least one evaluation to report on to continue.");
            }

            $objectives = array();
            $available_targets = array();

            foreach ($PROCESSED["objective_ids"] as $objective_id) {
                $query = "SELECT a.`objective_name`, l.`equestion_id` AS 'equestion_id_1', j.`equestion_id` AS 'equestion_id_2', h.`equestion_id` AS 'equestion_id_3', f.`equestion_id` AS 'equestion_id_4', d.`equestion_id` AS 'equestion_id_5', b.`equestion_id` AS 'equestion_id_6' FROM `global_lu_objectives` AS a
                            LEFT JOIN `evaluation_question_objectives` AS b
                            ON a.`objective_id` = b.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS c
                            ON a.`objective_id` = c.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS d
                            ON c.`objective_id` = d.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS e
                            ON c.`objective_id` = e.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS f
                            ON e.`objective_id` = f.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS g
                            ON e.`objective_id` = g.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS h
                            ON g.`objective_id` = h.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS i
                            ON g.`objective_id` = i.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS j
                            ON i.`objective_id` = j.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS k
                            ON i.`objective_id` = k.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS l
                            ON k.`objective_id` = l.`objective_id`
                            WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                            AND (
                                l.`objective_id` IS NOT NULL
                                OR j.`objective_id` IS NOT NULL
                                OR h.`objective_id` IS NOT NULL
                                OR f.`objective_id` IS NOT NULL
                                OR d.`objective_id` IS NOT NULL
                                OR b.`objective_id` IS NOT NULL
                            )";
                $temp_question_ids = $db->GetAll($query);
                $question_ids = array();
                if ($temp_question_ids) {
                    foreach ($temp_question_ids as $question_id) {
                        if ($question_id["equestion_id_1"] && !in_array($question_id["equestion_id_1"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_1"];
                        }
                        if ($question_id["equestion_id_2"] && !in_array($question_id["equestion_id_2"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_2"];
                        }
                        if ($question_id["equestion_id_3"] && !in_array($question_id["equestion_id_3"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_3"];
                        }
                        if ($question_id["equestion_id_4"] && !in_array($question_id["equestion_id_4"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_4"];
                        }
                        if ($question_id["equestion_id_5"] && !in_array($question_id["equestion_id_5"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_5"];
                        }
                        if ($question_id["equestion_id_6"] && !in_array($question_id["equestion_id_6"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_6"];
                        }
                    }
                }

                $objectives[$objective_id] = array(
                    "objective_name" => $temp_question_ids[0]["objective_name"],
                    "temp_responses" => array()
                );

                if (!@count($question_ids)) {
                    add_error("No Evaluation Questions associated with the selected objectives were found in the system.");
                } else {
                    $question_ids_string = "";
                    foreach ($question_ids as $question_id) {
                        $question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($question_id);
                    }
                }

                $query = "SELECT d.`eprogress_id`, a.`efquestion_id`, d.`target_record_id` FROM `evaluation_form_questions` AS a
                                JOIN `evaluation_form_questions` AS b
                                ON a.`equestion_id` = b.`equestion_id`
                                JOIN `evaluations` AS c
                                ON b.`eform_id` = c.`eform_id`
                                JOIN `evaluation_progress` AS d
                                ON d.`evaluation_id` = c.`evaluation_id`
                                AND d.`progress_value` = 'complete'
                                JOIN `evaluation_forms` AS e
                                ON c.`eform_id` = e.`eform_id`
                                WHERE c.`updated_date` >= ".$db->qstr($PROCESSED["report_start"])."
                                AND c.`updated_date` <= ".$db->qstr($PROCESSED["report_finish"])."
                                AND a.`equestion_id` IN (".$question_ids_string.")
                                AND e.`target_id` IN (".$PROCESSED["target_ids_string"].")";
                $evaluation_questions = $db->GetAll($query);
                if ($evaluation_questions) {
                    $question_progress_matrix = array();
                    foreach ($evaluation_questions as $evaluation_question) {
                        if (!array_key_exists($evaluation_question["target_record_id"], $question_progress_matrix)) {
                            $question_progress_matrix[$evaluation_question["target_record_id"]] = array();
                        }
                        if (!array_key_exists($evaluation_question["efquestion_id"], $question_progress_matrix[$evaluation_question["target_record_id"]])) {
                            $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] = $db->qstr($evaluation_question["eprogress_id"]);
                        } else {
                            $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] .= ", ".$db->qstr($evaluation_question["eprogress_id"]);
                        }
                    }
                    foreach ($question_progress_matrix as $target_record_id => $question_progress) {
                        foreach ($question_progress as $efquestion_id => $eprogress_ids_string) {
                            $query = "SELECT `eqresponse_id`, COUNT(`eqresponse_id`) AS `responses` FROM `evaluation_responses`
                                        WHERE `efquestion_id` = ".$db->qstr($efquestion_id)."
                                        AND `eprogress_id` IN (".$eprogress_ids_string.")
                                        GROUP BY `eqresponse_id`";
                            $evaluation_responses = $db->GetAll($query);
                            if ($evaluation_responses) {
                                foreach ($evaluation_responses as $evaluation_response) {
                                    if (!array_key_exists($target_record_id, $available_targets)) {
                                        $available_targets[$target_record_id] = get_account_data("wholename", $target_record_id);
                                    }
                                    if (!array_key_exists($target_record_id, $objectives[$objective_id]["temp_responses"])) {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id] = array();
                                    }
                                    if (!array_key_exists($evaluation_response["eqresponse_id"], $objectives[$objective_id]["temp_responses"][$target_record_id])) {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] = ((int)$evaluation_response["responses"]);
                                    } else {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] += ((int)$evaluation_response["responses"]);
                                    }
                                }
                            }
                        }
                    }
                    $descriptors = array();
                    $other_responses = array();
                    if ($objectives[$objective_id]["temp_responses"]) {
                        foreach ($objectives[$objective_id]["temp_responses"] as $target_record_id => $target_responses) {
                            foreach ($target_responses as $eqresponse_id => $responses) {
                                $query = "SELECT b.* FROM `evaluation_question_response_descriptors` AS a
                                            JOIN `evaluations_lu_response_descriptors` AS b
                                            ON a.`erdescriptor_id` = b.`erdescriptor_id`
                                            WHERE a.`eqresponse_id` = ".$db->qstr($eqresponse_id)."
                                            ORDER BY `order` ASC";
                                $descriptor = $db->GetRow($query);
                                if ($descriptor) {
                                    if (!array_key_exists($descriptor["erdescriptor_id"], $descriptors)) {
                                        $descriptors[$descriptor["erdescriptor_id"]] = array(
                                            "text" => $descriptor["descriptor"],
                                            "selections" => ((int)$responses),
                                            "reportable" => ($descriptor["reportable"] ? 1 : 0)
                                        );
                                    } else {
                                        $descriptors[$descriptor["erdescriptor_id"]]["selections"] += ((int)$responses);
                                    }
                                } else {
                                    $query = "SELECT `response_text` FROM `evaluations_lu_question_responses`
                                                        WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id);
                                    $response_text = $db->GetOne($query);
                                    if (!array_key_exists($eqresponse_id, $other_responses)) {
                                        $other_responses[$eqresponse_id] = array(
                                            "text" => $response_text,
                                            "selections" => ((int)$responses),
                                            "reportable" => 1
                                        );
                                    } else {
                                        $other_responses[$eqresponse_id]["selections"] += ((int)$responses);
                                    }
                                }
                            }
                            $objectives[$objective_id]["responses"][$target_record_id] = array();
                            if (@count($descriptors)) {
                                foreach ($descriptors as $descriptor) {
                                    $objectives[$objective_id]["responses"][$target_record_id][] = $descriptor;
                                }
                            }
                            if (@count($other_responses)) {
                                foreach ($other_responses as $other_response) {
                                    $objectives[$objective_id]["responses"][$target_record_id][] = $other_response;
                                }
                            }
                            unset($objectives[$objective_id]["temp_responses"][$target_record_id]);
                        }
                    } else {
                        unset($objectives[$objective_id]);
                    }
                }
            }

            $query = "SELECT c.* FROM `global_lu_objectives` AS a
                            JOIN `evaluation_question_objectives` AS b
                            ON a.`objective_id` = b.`objective_id`
                            JOIN `evaluations_lu_questions` AS c
                            ON b.`equestion_id` = c.`equestion_id`
                            WHERE a.`objective_id` = ".$db->qstr($PROCESSED["specific_objective_id"]);
            $temp_questions = $db->GetAll($query);
            if ($temp_questions) {
                $questions = array();
                if ($temp_questions) {
                    foreach ($temp_questions as $question) {
                        $questions[$question["equestion_id"]] = array(
                            "question_text" => $question["question_text"],
                            "temp_responses" => array()
                        );
                    }
                }

                foreach ($questions as $equestion_id => $question) {
                    $query = "SELECT d.`eprogress_id`, a.`efquestion_id`, d.`target_record_id` FROM `evaluation_form_questions` AS a
                                    JOIN `evaluation_form_questions` AS b
                                    ON a.`equestion_id` = b.`equestion_id`
                                    JOIN `evaluations` AS c
                                    ON b.`eform_id` = c.`eform_id`
                                    JOIN `evaluation_progress` AS d
                                    ON d.`evaluation_id` = c.`evaluation_id`
                                    AND d.`progress_value` = 'complete'
                                    JOIN `evaluation_forms` AS e
                                    ON c.`eform_id` = e.`eform_id`
                                    WHERE c.`updated_date` >= ".$db->qstr($PROCESSED["report_start"])."
                                    AND c.`updated_date` <= ".$db->qstr($PROCESSED["report_finish"])."
                                    AND a.`equestion_id` = ".$db->qstr($equestion_id)."
                                    AND e.`target_id` IN (".$PROCESSED["target_ids_string"].")";
                    $evaluation_questions = $db->GetAll($query);
                    if ($evaluation_questions) {
                        $question_progress_matrix = array();
                        foreach ($evaluation_questions as $evaluation_question) {
                            if (!array_key_exists($evaluation_question["target_record_id"], $question_progress_matrix)) {
                                $question_progress_matrix[$evaluation_question["target_record_id"]] = array();
                            }
                            if (!array_key_exists($evaluation_question["efquestion_id"], $question_progress_matrix[$evaluation_question["target_record_id"]])) {
                                $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] = $db->qstr($evaluation_question["eprogress_id"]);
                            } else {
                                $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] .= ", ".$db->qstr($evaluation_question["eprogress_id"]);
                            }
                        }
                        foreach ($question_progress_matrix as $target_record_id => $question_progress) {
                            foreach ($question_progress as $efquestion_id => $eprogress_ids_string) {
                                $query = "SELECT `eqresponse_id`, COUNT(`eqresponse_id`) AS `responses` FROM `evaluation_responses`
                                            WHERE `efquestion_id` = ".$db->qstr($efquestion_id)."
                                            AND `eprogress_id` IN (".$eprogress_ids_string.")
                                            GROUP BY `eqresponse_id`";
                                $evaluation_responses = $db->GetAll($query);
                                if ($evaluation_responses) {
                                    foreach ($evaluation_responses as $evaluation_response) {
                                        if (!array_key_exists($target_record_id, $available_targets)) {
                                            $available_targets[$target_record_id] = get_account_data("wholename", $target_record_id);
                                        }
                                        if (!array_key_exists($target_record_id, $questions[$equestion_id]["temp_responses"])) {
                                            $questions[$equestion_id]["temp_responses"][$target_record_id] = array();
                                        }
                                        if (!array_key_exists($evaluation_response["eqresponse_id"], $questions[$equestion_id]["temp_responses"][$target_record_id])) {
                                            $questions[$equestion_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] = ((int)$evaluation_response["responses"]);
                                        } else {
                                            $questions[$equestion_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] += ((int)$evaluation_response["responses"]);
                                        }
                                    }
                                }
                            }
                        }
                        $descriptors = array();
                        $other_responses = array();
                        if ($questions[$equestion_id]["temp_responses"]) {
                            foreach ($questions[$equestion_id]["temp_responses"] as $target_record_id => $target_responses) {
                                foreach ($target_responses as $eqresponse_id => $responses) {
                                    $query = "SELECT `response_text` FROM `evaluations_lu_question_responses`
                                                        WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id);
                                    $response_text = $db->GetOne($query);
                                    if (!array_key_exists($eqresponse_id, $other_responses)) {
                                        $other_responses[$eqresponse_id] = array(
                                            "text" => $response_text,
                                            "selections" => ((int)$responses),
                                            "reportable" => 1
                                        );
                                    } else {
                                        $other_responses[$eqresponse_id]["selections"] += ((int)$responses);
                                    }
                                }
                                $questions[$equestion_id]["responses"][$target_record_id] = array();
                                if (@count($descriptors)) {
                                    foreach ($descriptors as $descriptor) {
                                        $questions[$equestion_id]["responses"][$target_record_id][] = $descriptor;
                                    }
                                }
                                if (@count($other_responses)) {
                                    foreach ($other_responses as $other_response) {
                                        $questions[$equestion_id]["responses"][$target_record_id][] = $other_response;
                                    }
                                }
                                unset($questions[$equestion_id]["temp_responses"][$target_record_id]);
                            }
                        } else {
                            unset($questions[$equestion_id]);
                        }
                    }
                }
            }

            $STEP = 3;
        break;
        case 3 :
            if (isset($_GET["report_start"]) && $_GET["report_start"] && strtotime($_GET["report_start"])) {
                $report_start = trim($_GET["report_start"]);
                $PROCESSED["report_start"] = strtotime($report_start . " 00:00");
            } else {
                add_error("The <strong>Report Start</strong> date is required. Please set a date for the report to start from to continue.");
            }

            if (isset($_GET["report_finish"]) && $_GET["report_finish"] && strtotime($_GET["report_finish"]) && (!isset($PROCESSED["report_start"]) || $PROCESSED["report_start"] < strtotime($_GET["report_finish"]))) {
                $report_finish = trim($_GET["report_finish"]);
                $PROCESSED["report_finish"] = strtotime($report_finish . " 23:59");
            } else {
                add_error("The <strong>Report Finish</strong> date is required. Please set a date for the report to end on (which is higher than the start date) to continue.");
            }

            if (isset($_GET["objective_ids_string"]) && ($temp_objective_ids = explode(",", $_GET["objective_ids_string"])) && @count($temp_objective_ids)) {
                $PROCESSED["objective_ids_string"] = "";
                $PROCESSED["objective_ids"] = array();
                foreach ($temp_objective_ids as $objective_id) {
                    $PROCESSED["objective_ids"][] = ((int)$objective_id);
                    $PROCESSED["objective_ids_string"] .= ($PROCESSED["objective_ids_string"] ? ", " : "").$db->qstr($objective_id);
                }
            } else {
                add_error("The <strong>Objectives</strong> are required. Please choose at least one objective to report on to continue.");
            }

            if ((isset($_GET["target_id"])) && $_GET["target_id"] == "3,9") {
                $PROCESSED["target_id"] = "3,9";
                $PROCESSED["target_ids_string"] = $db->qstr(3).", ".$db->qstr(9);
            } elseif ((isset($_GET["target_id"])) && ($tmp_input = clean_input($_GET["target_id"], array("int")))) {
                $PROCESSED["target_id"] = $tmp_input;
                $PROCESSED["target_ids_string"] = $db->qstr($tmp_input);
            } else {
                add_error("The <strong>Target Type</strong> is required. Please choose a target type to report on to continue.");
            }

            if (isset($_GET["evaluation_ids"]) && ((@count($_GET["evaluation_ids"]) && ($temp_evaluation_ids = $_GET["evaluation_ids"])) || (isset($_GET["evaluation_ids_string"]) && ($temp_evaluation_ids = explode(",", $_GET["evaluation_ids_string"])) && @count($temp_evaluation_ids)))) {
                $PROCESSED["evaluation_ids_string"] = "";
                $PROCESSED["evaluation_ids"] = array();
                foreach ($temp_evaluation_ids as $evaluation_id) {
                    $PROCESSED["evaluation_ids"][] = ((int)$evaluation_id);
                    $PROCESSED["evaluation_ids_string"] .= ($PROCESSED["evaluation_ids_string"] ? ", " : "").$db->qstr($evaluation_id);
                }
            } else {
                add_error("The <strong>Evaluations</strong> are required. Please choose at least one evaluation to report on to continue.");
            }

            $objectives = array();
            $available_targets = array();

            foreach ($PROCESSED["objective_ids"] as $objective_id) {
                $query = "SELECT a.`objective_name`, l.`equestion_id` AS 'equestion_id_1', j.`equestion_id` AS 'equestion_id_2', h.`equestion_id` AS 'equestion_id_3', f.`equestion_id` AS 'equestion_id_4', d.`equestion_id` AS 'equestion_id_5', b.`equestion_id` AS 'equestion_id_6' FROM `global_lu_objectives` AS a
                            LEFT JOIN `evaluation_question_objectives` AS b
                            ON a.`objective_id` = b.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS c
                            ON a.`objective_id` = c.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS d
                            ON c.`objective_id` = d.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS e
                            ON c.`objective_id` = e.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS f
                            ON e.`objective_id` = f.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS g
                            ON e.`objective_id` = g.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS h
                            ON g.`objective_id` = h.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS i
                            ON g.`objective_id` = i.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS j
                            ON i.`objective_id` = j.`objective_id`
                            LEFT JOIN `global_lu_objectives` AS k
                            ON i.`objective_id` = k.`objective_parent`
                            LEFT JOIN `evaluation_question_objectives` AS l
                            ON k.`objective_id` = l.`objective_id`
                            WHERE a.`objective_id` = ".$db->qstr($objective_id)."
                            AND (
                                l.`objective_id` IS NOT NULL
                                OR j.`objective_id` IS NOT NULL
                                OR h.`objective_id` IS NOT NULL
                                OR f.`objective_id` IS NOT NULL
                                OR d.`objective_id` IS NOT NULL
                                OR b.`objective_id` IS NOT NULL
                            )";
                $temp_question_ids = $db->GetAll($query);
                $question_ids = array();
                if ($temp_question_ids) {
                    foreach ($temp_question_ids as $question_id) {
                        if ($question_id["equestion_id_1"] && !in_array($question_id["equestion_id_1"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_1"];
                        }
                        if ($question_id["equestion_id_2"] && !in_array($question_id["equestion_id_2"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_2"];
                        }
                        if ($question_id["equestion_id_3"] && !in_array($question_id["equestion_id_3"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_3"];
                        }
                        if ($question_id["equestion_id_4"] && !in_array($question_id["equestion_id_4"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_4"];
                        }
                        if ($question_id["equestion_id_5"] && !in_array($question_id["equestion_id_5"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_5"];
                        }
                        if ($question_id["equestion_id_6"] && !in_array($question_id["equestion_id_6"], $question_ids)) {
                            $question_ids[] = $question_id["equestion_id_6"];
                        }
                    }
                }

                $objectives[$objective_id] = array(
                    "objective_name" => $temp_question_ids[0]["objective_name"],
                    "temp_responses" => array()
                );

                if (!@count($question_ids)) {
                    add_error("No Evaluation Questions associated with the selected objectives were found in the system.");
                } else {
                    $question_ids_string = "";
                    foreach ($question_ids as $question_id) {
                        $question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($question_id);
                    }
                }

                $query = "SELECT d.`eprogress_id`, a.`efquestion_id`, d.`target_record_id` FROM `evaluation_form_questions` AS a
                                JOIN `evaluation_form_questions` AS b
                                ON a.`equestion_id` = b.`equestion_id`
                                JOIN `evaluations` AS c
                                ON b.`eform_id` = c.`eform_id`
                                JOIN `evaluation_progress` AS d
                                ON d.`evaluation_id` = c.`evaluation_id`
                                AND d.`progress_value` = 'complete'
                                JOIN `evaluation_forms` AS e
                                ON c.`eform_id` = e.`eform_id`
                                WHERE c.`updated_date` >= ".$db->qstr($PROCESSED["report_start"])."
                                AND c.`updated_date` <= ".$db->qstr($PROCESSED["report_finish"])."
                                AND a.`equestion_id` IN (".$question_ids_string.")
                                AND e.`target_id` IN (".$PROCESSED["target_ids_string"].")";
                $evaluation_questions = $db->GetAll($query);
                if ($evaluation_questions) {
                    $question_progress_matrix = array();
                    foreach ($evaluation_questions as $evaluation_question) {
                        if (!array_key_exists($evaluation_question["target_record_id"], $question_progress_matrix)) {
                            $question_progress_matrix[$evaluation_question["target_record_id"]] = array();
                        }
                        if (!array_key_exists($evaluation_question["efquestion_id"], $question_progress_matrix[$evaluation_question["target_record_id"]])) {
                            $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] = $db->qstr($evaluation_question["eprogress_id"]);
                        } else {
                            $question_progress_matrix[$evaluation_question["target_record_id"]][$evaluation_question["efquestion_id"]] .= ", ".$db->qstr($evaluation_question["eprogress_id"]);
                        }
                    }
                    foreach ($question_progress_matrix as $target_record_id => $question_progress) {
                        foreach ($question_progress as $efquestion_id => $eprogress_ids_string) {
                            $query = "SELECT `eqresponse_id`, COUNT(`eqresponse_id`) AS `responses` FROM `evaluation_responses`
                                        WHERE `efquestion_id` = ".$db->qstr($efquestion_id)."
                                        AND `eprogress_id` IN (".$eprogress_ids_string.")
                                        GROUP BY `eqresponse_id`";
                            $evaluation_responses = $db->GetAll($query);
                            if ($evaluation_responses) {
                                foreach ($evaluation_responses as $evaluation_response) {
                                    if (!array_key_exists($target_record_id, $available_targets)) {
                                        $available_targets[$target_record_id] = get_account_data("wholename", $target_record_id);
                                    }
                                    if (!array_key_exists($target_record_id, $objectives[$objective_id]["temp_responses"])) {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id] = array();
                                    }
                                    if (!array_key_exists($evaluation_response["eqresponse_id"], $objectives[$objective_id]["temp_responses"][$target_record_id])) {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] = ((int)$evaluation_response["responses"]);
                                    } else {
                                        $objectives[$objective_id]["temp_responses"][$target_record_id][$evaluation_response["eqresponse_id"]] += ((int)$evaluation_response["responses"]);
                                    }
                                }
                            }
                        }
                    }
                    $descriptors = array();
                    $other_responses = array();
                    if ($objectives[$objective_id]["temp_responses"]) {
                        foreach ($objectives[$objective_id]["temp_responses"] as $target_record_id => $target_responses) {
                            foreach ($target_responses as $eqresponse_id => $responses) {
                                $query = "SELECT b.* FROM `evaluation_question_response_descriptors` AS a
                                            JOIN `evaluations_lu_response_descriptors` AS b
                                            ON a.`erdescriptor_id` = b.`erdescriptor_id`
                                            WHERE a.`eqresponse_id` = ".$db->qstr($eqresponse_id)."
                                            ORDER BY `order` ASC";
                                $descriptor = $db->GetRow($query);
                                if ($descriptor) {
                                    if (!array_key_exists($descriptor["erdescriptor_id"], $descriptors)) {
                                        $descriptors[$descriptor["erdescriptor_id"]] = array(
                                            "text" => $descriptor["descriptor"],
                                            "selections" => ((int)$responses),
                                            "reportable" => ($descriptor["reportable"] ? 1 : 0)
                                        );
                                    } else {
                                        $descriptors[$descriptor["erdescriptor_id"]]["selections"] += ((int)$responses);
                                    }
                                } else {
                                    $query = "SELECT `response_text` FROM `evaluations_lu_question_responses`
                                                        WHERE `eqresponse_id` = ".$db->qstr($eqresponse_id);
                                    $response_text = $db->GetOne($query);
                                    if (!array_key_exists($eqresponse_id, $other_responses)) {
                                        $other_responses[$eqresponse_id] = array(
                                            "text" => $response_text,
                                            "selections" => ((int)$responses),
                                            "reportable" => 1
                                        );
                                    } else {
                                        $other_responses[$eqresponse_id]["selections"] += ((int)$responses);
                                    }
                                }
                            }
                            $objectives[$objective_id]["responses"][$target_record_id] = array();
                            if (@count($descriptors)) {
                                foreach ($descriptors as $descriptor) {
                                    $objectives[$objective_id]["responses"][$target_record_id][] = $descriptor;
                                }
                            }
                            if (@count($other_responses)) {
                                foreach ($other_responses as $other_response) {
                                    $objectives[$objective_id]["responses"][$target_record_id][] = $other_response;
                                }
                            }
                            unset($objectives[$objective_id]["temp_responses"][$target_record_id]);
                        }
                    } else {
                        unset($objectives[$objective_id]);
                    }
                }
            }
        break;
        case 2 :
            if (isset($_GET["report_start"]) && $_GET["report_start"] && strtotime($_GET["report_start"])) {
                $report_start = trim($_GET["report_start"]);
                $PROCESSED["report_start"] = strtotime($report_start . " 00:00");
            } else {
                add_error("The <strong>Report Start</strong> date is required. Please set a date for the report to start from to continue.");
            }

            if (isset($_GET["report_finish"]) && $_GET["report_finish"] && strtotime($_GET["report_finish"]) && (!isset($PROCESSED["report_start"]) || $PROCESSED["report_start"] < strtotime($_GET["report_finish"]))) {
                $report_finish = trim($_GET["report_finish"]);
                $PROCESSED["report_finish"] = strtotime($report_finish . " 23:59");
            } else {
                add_error("The <strong>Report Finish</strong> date is required. Please set a date for the report to end on (which is higher than the start date) to continue.");
            }

            if (isset($_GET["objective_ids_string"]) && ($temp_objective_ids = explode(",", $_GET["objective_ids_string"])) && @count($temp_objective_ids)) {
                $PROCESSED["objective_ids_string"] = "";
                $PROCESSED["objective_ids"] = array();
                foreach ($temp_objective_ids as $objective_id) {
                    $PROCESSED["objective_ids"][] = ((int)$objective_id);
                    $PROCESSED["objective_ids_string"] .= ($PROCESSED["objective_ids_string"] ? ", " : "").$db->qstr($objective_id);
                }
            } else {
                add_error("The <strong>Objectives</strong> are required. Please choose at least one objective to report on to continue.");
            }

            if ((isset($_GET["target_id"])) && $_GET["target_id"] == "3,9") {
                $PROCESSED["target_id"] = "3,9";
                $PROCESSED["target_ids_string"] = $db->qstr(3).", ".$db->qstr(9);
            } elseif ((isset($_GET["target_id"])) && ($tmp_input = clean_input($_GET["target_id"], array("int")))) {
                $PROCESSED["target_id"] = $tmp_input;
                $PROCESSED["target_ids_string"] = $db->qstr($tmp_input);
            } else {
                add_error("The <strong>Target Type</strong> is required. Please choose a target type to report on to continue.");
            }
            $query = "SELECT l.`equestion_id` AS 'equestion_id_1', j.`equestion_id` AS 'equestion_id_2', h.`equestion_id` AS 'equestion_id_3', f.`equestion_id` AS 'equestion_id_4', d.`equestion_id` AS 'equestion_id_5', b.`equestion_id` AS 'equestion_id_6' FROM `global_lu_objectives` AS a
                        LEFT JOIN `evaluation_question_objectives` AS b
                        ON a.`objective_id` = b.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS c
                        ON a.`objective_id` = c.`objective_parent`
                        LEFT JOIN `evaluation_question_objectives` AS d
                        ON c.`objective_id` = d.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS e
                        ON c.`objective_id` = e.`objective_parent`
                        LEFT JOIN `evaluation_question_objectives` AS f
                        ON e.`objective_id` = f.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS g
                        ON e.`objective_id` = g.`objective_parent`
                        LEFT JOIN `evaluation_question_objectives` AS h
                        ON g.`objective_id` = h.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS i
                        ON g.`objective_id` = i.`objective_parent`
                        LEFT JOIN `evaluation_question_objectives` AS j
                        ON i.`objective_id` = j.`objective_id`
                        LEFT JOIN `global_lu_objectives` AS k
                        ON i.`objective_id` = k.`objective_parent`
                        LEFT JOIN `evaluation_question_objectives` AS l
                        ON k.`objective_id` = l.`objective_id`
                        WHERE a.`objective_id` IN (".$PROCESSED["objective_ids_string"].")
                        AND (
                            l.`objective_id` IS NOT NULL
                            OR j.`objective_id` IS NOT NULL
                            OR h.`objective_id` IS NOT NULL
                            OR f.`objective_id` IS NOT NULL
                            OR d.`objective_id` IS NOT NULL
                            OR b.`objective_id` IS NOT NULL
                        )";
            $temp_question_ids = $db->GetAll($query);
            $question_ids = array();
            if ($temp_question_ids) {
                foreach ($temp_question_ids as $question_id) {
                    if ($question_id["equestion_id_1"] && !in_array($question_id["equestion_id_1"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_1"];
                    }
                    if ($question_id["equestion_id_2"] && !in_array($question_id["equestion_id_2"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_2"];
                    }
                    if ($question_id["equestion_id_3"] && !in_array($question_id["equestion_id_3"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_3"];
                    }
                    if ($question_id["equestion_id_4"] && !in_array($question_id["equestion_id_4"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_4"];
                    }
                    if ($question_id["equestion_id_5"] && !in_array($question_id["equestion_id_5"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_5"];
                    }
                    if ($question_id["equestion_id_6"] && !in_array($question_id["equestion_id_6"], $question_ids)) {
                        $question_ids[] = $question_id["equestion_id_6"];
                    }
                }
            }

            if (!@count($question_ids)) {
                add_error("No Evaluation Questions associated with the selected objectives were found in the system.");
            } else {
                $question_ids_string = "";
                foreach ($question_ids as $question_id) {
                    $question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($question_id);
                }

                $query = "SELECT c.`evaluation_id`, c.`evaluation_title`, e.`form_title`, COUNT(DISTINCT d.`eprogress_id`) AS `completed` FROM `evaluation_form_questions` AS a
                            JOIN `evaluation_form_questions` AS b
                            ON a.`equestion_id` = b.`equestion_id`
                            JOIN `evaluations` AS c
                            ON b.`eform_id` = c.`eform_id`
                            JOIN `evaluation_progress` AS d
                            ON d.`evaluation_id` = c.`evaluation_id`
                            AND d.`progress_value` = 'complete'
                            JOIN `evaluation_forms` AS e
                            ON c.`eform_id` = e.`eform_id`
                            WHERE c.`updated_date` >= ".$db->qstr($PROCESSED["report_start"])."
                            AND c.`updated_date` <= ".$db->qstr($PROCESSED["report_finish"])."
                            AND a.`equestion_id` IN (".$question_ids_string.")
                            AND e.`target_id` IN (".$PROCESSED["target_ids_string"].")
                            GROUP BY c.`evaluation_id`";
                $evaluations = $db->GetAll($query);
                if (!$evaluations) {
                    add_error("No completed Evaluations associated with the selected objectives were found in the system.");
                }
            }

            if (has_error()) {
                $STEP = 1;
            }
        break;
    }
    switch ($STEP) {
        case 3 :
            if (count($available_targets) > 1) {
                if (isset($_GET["view_target"]) && clean_input($_GET["view_target"], "int")) {
                    $view_target = clean_input($_GET["view_target"], "int");
                }
                $sidebar_html  = "<ul class=\"menu\">\n";
                foreach ($available_targets as $target_id => $available_target) {
                    if (!isset($selected_target) && (!isset($view_target) || !array_key_exists($view_target, $available_targets))) {
                        $selected_target = $available_target;
                        $selected_target_id = $target_id;
                    } elseif (isset($view_target) && $view_target && $view_target == $target_id) {
                        $selected_target = $available_target;
                        $selected_target_id = $target_id;
                    }
                    $evaluation_ids_string = "";
                    foreach ($PROCESSED["evaluation_ids"] as $evaluation_id) {
                        $evaluation_ids_string .= ($evaluation_ids_string ? "," : "").$evaluation_id;
                    }
                    $sidebar_html .= "	<li class=\"".(isset($selected_target_id) && $selected_target_id == $target_id ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("view_target" => $target_id, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string))."\">".$available_target."</a></li>\n";
                }
                $sidebar_html .= "</ul>\n";

                new_sidebar_item("Targets to Review", $sidebar_html, "review-targets", "open", "1.9");
            } else {
                foreach ($available_targets as $target_id => $available_target) {
                    $selected_target = $available_target;
                    $selected_target_id = $target_id;
                }
            }

            if (isset($PROCESSED["parent_objective_ids"]) && @count($PROCESSED["parent_objective_ids"]) > 1) {
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 3, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => NULL, "objective_parents" => NULL)), "title" => "Aggregate of Objectives");
                $parent_objectives_string = "";
                foreach ($PROCESSED["parent_objective_ids"] as $parent_objective_id) {
                    $query = "SELECT `objective_name` FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($parent_objective_id);
                    $objective_name = $db->getOne($query);
                    if ($parent_objectives_string) {
                        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 4, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => $parent_objective_id, "objective_parents" => $parent_objectives_string)), "title" => $objective_name);
                    } else {
                        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 4, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => $parent_objective_id, "objective_parents" => NULL)), "title" => $objective_name);
                    }
                    $parent_objectives_string .= ($parent_objectives_string ? "," : "").$parent_objective_id;
                }
                $query = "SELECT `objective_name` FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($parent_objective_id);
                $objective_name = $db->getOne($query);
            } elseif (isset($PROCESSED["specific_objective_id"]) && $PROCESSED["specific_objective_id"]) {
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 3, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => NULL, "objective_parents" => NULL)), "title" => "Aggregate of Objectives");
                $query = "SELECT `objective_name` FROM `global_lu_objectives` WHERE `objective_id` = ".$db->qstr($PROCESSED["specific_objective_id"]);
                $objective_name = $db->getOne($query);
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 4, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => $PROCESSED["specific_objective_id"])), "title" => $objective_name);
            } else {
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("step" => 3, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_id" => NULL, "objective_parents" => NULL)), "title" => "Aggregate of Objectives");
            }

            echo "<h1>Objective Aggregate Evaluation Report </h1>";
            echo "<h2>Results for: ".$selected_target."</h2>";
            $count = 0;
            foreach ($objectives as $objective_id => $objective) {
                if (isset($objective["responses"][$selected_target_id]) && $objective["responses"][$selected_target_id]) {
                    $total_selections = 0;
                    $count++;
                    if ($count > 1) {
                        echo "<div class=\"row-fluid border-above-medium\">&nbsp;</div>\n";
                    }
                    echo "	<div class=\"row-fluid\">\n";
                    echo "		<span class=\"span10 row-fluid\">\n";
                    echo "			<h3 class=\"span3\">Objective #".$count.":</h3>\n";
                    $evaluation_ids_string = "";
                    foreach ($PROCESSED["evaluation_ids"] as $evaluation_id) {
                        $evaluation_ids_string .= ($evaluation_ids_string ? "," : "").$evaluation_id;
                    }
                    if (isset($PROCESSED["parent_objective_ids_string"]) && $PROCESSED["parent_objective_ids_string"]) {
                        echo "			<span class=\"span9 space-above medium\">".$objective["objective_name"]." [<a class=\"content-small\" href=\"".ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("objective_id" => $objective_id, "step" => 4, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_parents" => $PROCESSED["parent_objective_ids_string"].",".$objective_id))."\">View Children</a>]</span>\n";
                    } else {
                        echo "			<span class=\"span9 space-above medium\">".$objective["objective_name"]." [<a class=\"content-small\" href=\"".ENTRADA_URL."/admin/evaluations/reports?".replace_query(array("objective_id" => $objective_id, "step" => 4, "evaluation_ids[]" => NULL, "evaluation_ids_string" => $evaluation_ids_string, "objective_parents" => $objective_id))."\">View Children</a>]</span>\n";
                    }
                    echo "		</span>\n";
                    echo "	</div>\n";
                    echo "	<br />\n";
                    echo "	<div class=\"row-fluid\" style=\"font-weight: bold;\">\n";
                    echo "		<span class=\"span8 border-below\">Response</span>\n";
                    echo "		<span class=\"span2 border-below\">Percent</span>\n";
                    echo "		<span class=\"span2 border-below\">Selections</span>\n";
                    echo "	</div>\n";
                    foreach ($objective["responses"][$selected_target_id] as $response_id => $response) {
                        if ($response["reportable"]) {
                            $total_selections += $response["selections"];
                        }
                    }
                    foreach ($objective["responses"][$selected_target_id] as $response_id => $response) {
                        if ($response["reportable"]) {
                            echo "	<div class=\"row-fluid\">\n";
                            echo "		<span class=\"span8\">".$response["text"]."</span>\n";
                            echo "		<span class=\"span2\">".($response["selections"] ? round(($response["selections"] / $total_selections * 100), 1) : 0)."%</span>\n";
                            echo "		<span class=\"span2\">".$response["selections"]."</span>\n";
                            echo "	</div>\n";
                        }
                    }
                }
            }
            if (isset($questions) && $questions) {
                $count = 0;
                foreach ($questions as $equestion_id => $question) {
                    if (isset($question["responses"][$selected_target_id]) && $question["responses"][$selected_target_id]) {
                        $total_selections = 0;
                        $count++;
                        if ($count > 1) {
                            echo "<div class=\"row-fluid border-above-medium\">&nbsp;</div>\n";
                        }
                        echo "	<div class=\"row-fluid\">\n";
                        echo "		<span class=\"span10 row-fluid\">\n";
                        echo "			<h3 class=\"span3\">Question #".$count.":</h3>\n";
                        $evaluation_ids_string = "";
                        foreach ($PROCESSED["evaluation_ids"] as $evaluation_id) {
                            $evaluation_ids_string .= ($evaluation_ids_string ? "," : "").$evaluation_id;
                        }
                        echo "			<span class=\"span9 space-above medium\">".$question["question_text"]."</span>\n";
                        echo "		</span>\n";
                        echo "	</div>\n";
                        echo "	<br />\n";
                        echo "	<div class=\"row-fluid\" style=\"font-weight: bold;\">\n";
                        echo "		<span class=\"span8 border-below\">Response</span>\n";
                        echo "		<span class=\"span2 border-below\">Percent</span>\n";
                        echo "		<span class=\"span2 border-below\">Selections</span>\n";
                        echo "	</div>\n";
                        foreach ($question["responses"][$selected_target_id] as $response_id => $response) {
                            if ($response["reportable"]) {
                                $total_selections += $response["selections"];
                            }
                        }
                        foreach ($question["responses"][$selected_target_id] as $response_id => $response) {
                            if ($response["reportable"]) {
                                echo "	<div class=\"row-fluid\">\n";
                                echo "		<span class=\"span8\">".$response["text"]."</span>\n";
                                echo "		<span class=\"span2\">".($response["selections"] ? round(($response["selections"] / $total_selections * 100), 1) : 0)."%</span>\n";
                                echo "		<span class=\"span2\">".$response["selections"]."</span>\n";
                                echo "	</div>\n";
                            }
                        }
                    }
                }
            }

            break;
        case 2 :
            echo "<h1>Objective Aggregate Evaluation Report </h1>\n";
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }
            ?>
            <form name="frmReport" action="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports" method="get">
                <?php
                $objective_ids_string = "";
                foreach ($PROCESSED["objective_ids"] as $objective_id) {
                    $objective_ids_string .= ($objective_ids_string ? "," : "").((int)$objective_id);
                }
                echo "<input type=\"hidden\" name=\"objective_ids_string\" value=\"".$objective_ids_string."\" />\n";
                ?>
                <input type="hidden" name="section" value="evaluations-aggregated-by-objective" />
                <input type="hidden" name="step" value="3" />
                <input type="hidden" name="report_start" value="<?php echo $report_start; ?>" />
                <input type="hidden" name="report_finish" value="<?php echo $report_finish; ?>" />
                <input type="hidden" name="target_id" value="<?php echo $PROCESSED["target_id"]; ?>" />
                <table class="tableList" cellspacing="0" cellpadding="1" summary="List of Evaluated Services">
                    <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="title" />
                        <col class="submitted" />
                    </colgroup>
                    <thead>
                    <tr>
                        <td class="modified">&nbsp;</td>
                        <td width="title">Evaluation</td>
                        <td class="title">Evaluation Form</td>
                        <td class="submitted text-right">Completed</td>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="3" style="text-align: right; padding-top: 15px">
                            <input type="submit" class="btn btn-primary" value="Create Report" />
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    if ($evaluations) {
                        foreach ($evaluations as $evaluation) {
                            echo "<tr>\n";
                            echo "<td><input type=\"checkbox\" name=\"evaluation_ids[]\" value=\"".$evaluation["evaluation_id"]."\" /></td>\n";
                            echo "<td>".$evaluation["evaluation_title"]."</td>\n";
                            echo "<td>".$evaluation["form_title"]."</td>\n";
                            echo "<td>".$evaluation["completed"]."</td>\n";
                            echo "</tr>\n";
                        }
                    } else {
                        ?>
                        <td>&nbsp;</td>
                        <td colspan="3" style="padding-top: 15px">
                            <?php
                            echo display_notice("No completed evaluations were found associated with the selected objectives.");
                            ?>
                        </td>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </form>
            <?php
            break;
        case 1 :
        default :
            echo "<h1>Objective Aggregate Evaluation Report </h1>\n";
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }
            $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js\"></script>";
            $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives_evaluation_report.js\"></script>";
            $HEAD[]	= "<script type=\"text/javascript\"> var SITE_URL = '".ENTRADA_URL."'; </script>";
            ?>
                <script type="text/javascript">
                    jQuery (function($) {
                        jQuery('.datepicker').datepicker({
                            dateFormat: 'yy-mm-dd',
                            minDate: '05-01-01',
                            maxDate: '<?php echo date("y-m-d"); ?>'
                        });
                        jQuery('.add-on').on('click', function() {
                            if (jQuery(this).siblings('input').is(':enabled')) {
                                jQuery(this).siblings('input').focus();
                            }
                        });
                    });

                    function addObjective(id) {
                        var ids = id;

                        var alreadyAdded = false;
                        $$('input.objective_ids').each(
                            function (e) {
                                if (!ids) {
                                    ids = e.value;
                                } else {
                                    ids += ','+e.value;
                                }
                                if (e.value == id) {
                                    alreadyAdded = true;
                                }
                            }
                        );

                        $('objective_ids_string').value = ids;

                        if (!alreadyAdded) {
                            var attrs = {
                                type		: 'hidden',
                                className	: 'objective_ids',
                                id			: 'objective_ids_'+id,
                                value		: id,
                                name		:'objective_ids[]'
                            };

                            var newInput = new Element('input', attrs);
                            $('objectives_list').insert({bottom: newInput});
                        }
                    }

                    function removeObjective(id) {
                        var ids = "";

                        $('objective_ids_'+id).remove();

                        $$('input.objective_ids').each(
                            function (e) {
                                if (!ids) {
                                    ids = e.value;
                                } else {
                                    ids += ','+e.value;
                                }
                            }
                        );

                        $('objective_ids_string').value = ids;
                    }
                </script>
                <form name="frmReport" action="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports" method="get">
                    <input type="hidden" name="section" value="evaluations-aggregated-by-objective" />
                    <input type="hidden" name="step" value="2" />
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Report Start: </label>
                        <span class="controls span8">
                            <div class="input-append">
                                <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED["report_start"]) && $PROCESSED["report_start"] ? date("Y-m-d", $PROCESSED["report_start"]) : "2013-03-30"); ?>" name="report_start" id="report_start" />
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Report Finish: </label>
                        <span class="controls span8">
                            <div class="input-append">
                                <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED["report_finish"]) && $PROCESSED["report_finish"] ? date("Y-m-d", $PROCESSED["report_finish"]) : date("Y-m-d")); ?>" name="report_finish" id="report_finish" />
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </span>
                    </div>
                    <?php
                    /*
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Evaluation / Assessment Type: </label>
                        <span class="controls span8">
                            <select name="target_id">
                                <option value="0">-- Select Target Type --</option>
                                <?php
                                $query = "SELECT * FROM `evaluations_lu_targets`
                                            WHERE `target_active` = 1";
                                $evaluation_targets = $db->GetAll($query);
                                foreach ($evaluation_targets as $evaluation_target) {
                                    echo "<option value=\"".$evaluation_target["target_id"]."\"".(isset($PROCESSED["target_id"]) && $PROCESSED["target_id"] == $evaluation_target["target_id"] ? " selected=\"selected\"" : "").">".html_encode($evaluation_target["target_title"])."</option>";
                                }
                                ?>
                                <option value="3,9">Patient Encounter and Student Assessments</option>
                            </select>
                        </span>
                    </div>
                    */
                    ?>
                    <input type="hidden" name="target_id" value="3,9" />
                    <div class="control-group row-fluid">
                        <div id="objectives_list" class="hidden">
                            <?php
                            $objective_ids_string = "";
                            if (isset($question_data["objective_ids"]) && @count($question_data["objective_ids"])) {
                                foreach ($question_data["objective_ids"] as $objective_id) {
                                    $objective_ids_string .= ($objective_ids_string ? ", " : "").((int)$objective_id);
                                    ?>
                                    <input type="hidden" class="objective_ids" id="objective_ids_<?php echo $objective_id; ?>" name="objective_ids[]" value="<?php echo $objective_id; ?>" />
                                <?php
                                }
                            }
                            ?>
                            <input type="hidden" name="objective_ids_string" id="objective_ids_string" value="<?php echo ($PROCESSED["objective_ids_string"] ? $PROCESSED["objective_ids_string"] : ""); ?>" />
                        </div>
                        <?php
                        require_once("api-objectives-list.inc.php");
                        ?>
                    </div>
                    <input type="submit" class="btn btn-primary pull-right" value="Show Evaluations Available to Report On" />
                </form>
            <?php
            break;
    }
}