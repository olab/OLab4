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
 * This file is used to generate a report based on the results to evaluation
 * questions from within a selected set of evaluations, correlated by the
 * objectives they're associated with, indicated as a differences over time.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
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
    echo "<h1>Learner Evaluations</h1>";
    
    function addChild($row, $child) {
        if ($child["objective_parent"] == $row["objective_id"]) {
            $row["children"][$child["objective_order"]] = $child;
        } elseif (isset($row["children"]) && @count($row["children"])) {
            $row = addChildRecursive($row, $child);
        }
        
        return $row;
    }

    function addChildRecursive($row, $child) {
        
        foreach ($row["children"] as $key => $possible_parent) {
            if ($child["objective_parent"] == $possible_parent["objective_id"]) {
                $row["children"][$key]["children"][$child["objective_order"]] = $child;
            } elseif (isset($possible_parent["children"]) && @count($possible_parent["children"])) {
                $row["children"][$key] = addChildRecursive($possible_parent, $child);
            }
        }
        return $row;
    }
    
    function hierarchicalIterator($hierarchical_list, $level = 0) {        
        if (!$level) {
            foreach ($hierarchical_list as $key => $row) {
                $hierarchical_list[$key] = hierarchicalIterator($row, $level + 1);
            }
        } elseif ($level < 99) {
            if (isset($hierarchical_list["equestion_ids"]) && @count($hierarchical_list["equestion_ids"])) {
                $hierarchical_list = addQuestionChild($hierarchical_list); 
            }
            if (isset($hierarchical_list["children"]) && @count($hierarchical_list["children"])) {
                foreach ($hierarchical_list["children"] as $key => $row) {
                    $hierarchical_list["children"][$key] = hierarchicalIterator($row, $level + 1);
                }
            }
        }
        
        return $hierarchical_list;
    }

    function addQuestionChild($row) {
        global $flat_questions;
        
        foreach ($row["equestion_ids"] as $question_id) {
            if (isset($flat_questions[$question_id])) {
                if (!isset($row["children"])) {
                    $row["children"] = array();
                }
                $flat_questions[$question_id]["objective_parent"] = $row["objective_id"];
                $row["children"][] = $flat_questions[$question_id];
            }
        }
        return $row;
    }

    function ksortRecursive($child_array) {
        ksort($child_array);
        foreach ($child_array as &$child) {
            if (isset($child["children"]) && @count($child["children"])) {
                $child["children"] = ksortRecursive($child["children"]);
            }
        }
        return $child_array;
    }

    function flattenObjectives ($list, $top_level_key = false, $flat_list = array()) {
        foreach ($list as $key => $record) {
            if ($top_level_key === false) {
                $top_level_key = $key;
            }
            if (!isset($record["type"]) || $record["type"] == "objective") {
                if (isset($record["children"]) && @count($record["children"])) {
                    $flat_list = flattenObjectives($record["children"], $top_level_key, $flat_list);
                }
                $record["top_level_key"] = $top_level_key;
                $flat_list[$record["objective_id"]] = $record;
            }
        }
        return $flat_list;
    }
    function flattenQuestions ($list) {
        $flat_list = array();
        foreach ($list as $key => $record) {
            if ($record["type"] == "question") {
                $record["top_level_key"] = $key;
                $flat_list[$record["equestion_id"]] = $record;
            }
        }
        return $flat_list;
    }
    
    switch ($STEP) {
        case 3 :
            if (isset($_POST["evaluation_ids"]) && is_array($_POST["evaluation_ids"]) && (@count($_POST["evaluation_ids"]))) {
                $evaluation_ids = $_POST["evaluation_ids"];
            } else {
                add_error("At least one <strong>Evaluation</strong> must be selected from the list to continue.");
            }
            
            if (isset($_POST["proxy_id"]) && ((int)$_POST["proxy_id"])) {
                $PROCESSED["proxy_id"] = ((int) $_POST["proxy_id"]);
            }

            if (isset($_POST["report_start"]) && $_POST["report_start"]) {
                $report_start = trim($_POST["report_start"]);
                $PROCESSED["report_start"] = $report_start;
            }

            if (isset($_POST["report_finish"]) && $_POST["report_finish"]) {
                $report_finish = trim($_POST["report_finish"]);
                $PROCESSED["report_finish"] = $report_finish;
            }
            
            if (has_error()) {
                $STEP = 2;
            } else {
                $evaluation_ids_string = "";
                foreach ($evaluation_ids as $evaluation_id) {
                    $evaluation_ids_string .= ($evaluation_ids_string ? ", " : "").$db->qstr($evaluation_id);
                }
                
                $evaluation_questions = array();
                $hidden_question_ids = array();
                $top_level_objectives = array();
                
                $query = "SELECT a.*, GROUP_CONCAT(b.`efquestion_id` SEPARATOR ', ') AS `efquestion_ids`, GROUP_CONCAT(c.`evaluation_id` SEPARATOR ', ') AS `evaluation_ids` FROM `evaluations_lu_questions` AS a
                            JOIN `evaluation_form_questions` AS b
                            ON a.`equestion_id` = b.`equestion_id`
                            JOIN `evaluations` AS c
                            ON b.`eform_id` = c.`eform_id`
                            WHERE c.`evaluation_id` IN (".$evaluation_ids_string.")
                            GROUP BY b.`equestion_id`";
                $temp_evaluation_questions = $db->GetAll($query);
                foreach ($temp_evaluation_questions as $counter => $temp_evaluation_question) {
                    $evaluation_question = $temp_evaluation_question;
                    $evaluation_ids = explode(",", $evaluation_question["evaluation_ids"]);
                    $evaluation_question["evaluation_ids"] = "";
                    $evaluation_question["evaluation_dates"] = array();
                    foreach ($evaluation_ids as $evaluation_id) {
                        $evaluation_question["evaluation_ids"] .= ($evaluation_question["evaluation_ids"] ? ", " : "").$db->qstr($evaluation_id);
                    }
                    $query = "SELECT `eprogress_id`, `updated_date` FROM `evaluation_progress` 
                                WHERE `evaluation_id` IN (".$evaluation_question["evaluation_ids"].") 
                                AND `target_record_id` = ".$db->qstr($PROCESSED["proxy_id"]);
                    $eprogress_ids = $db->GetAll($query);
                    if ($eprogress_ids) {
                        $evaluation_question["eprogress_ids"] = "";
                        foreach ($eprogress_ids as $eprogress_id) {
                            $evaluation_question["eprogress_ids"] .= ($evaluation_question["eprogress_ids"] ? ", " : "").$db->qstr($eprogress_id["eprogress_id"]);
                            $evaluation_question["evaluation_dates"][strtotime("12:00", $eprogress_id["updated_date"])]["eprogress_ids"][] = $eprogress_id["eprogress_id"];
                        }
                        $query = "SELECT * FROM `evaluations_lu_question_responses`
                                    WHERE `equestion_id` = ".$db->qstr($evaluation_question["equestion_id"])."
                                    ORDER BY `response_order` ASC";
                        $question_responses = $db->getAll($query);
                        if ($question_responses) {
                            $evaluation_question["question_responses"] = array();
                            foreach ($question_responses as $question_response) {
                                $query = "SELECT * FROM `evaluation_responses` 
                                            WHERE `eqresponse_id` = ".$db->qstr($question_response["eqresponse_id"])."
                                            AND `eprogress_id` IN (".$evaluation_question["eprogress_ids"].")";
                                $responses = $db->GetAll($query);
                                if ($responses) {
                                    $question_response["chosen"] = count($responses);
                                } else {
                                    $question_response["chosen"] = 0;
                                }
                                $evaluation_question["flat_responses"][$question_response["response_order"]] = ($responses ? count($responses) : 0);
                                $evaluation_question["question_responses"][$question_response["eqresponse_id"]] = $question_response;
                            }
                            $evaluation_question["question_parents"] = Classes_Evaluation::getQuestionParents($evaluation_question["question_parent_id"]);
                            $evaluation_question["question_parent_ids"] = array_keys($evaluation_question["question_parents"]);
                            $evaluation_question["question_objectives"] = Classes_Evaluation::getQuestionObjectives($evaluation_question["equestion_id"]);
                            $evaluation_question["question_objective_ids"] = array_keys($evaluation_question["question_objectives"]);
                            if (@count($evaluation_question["question_objectives"])) {
                                $hidden_question_ids[] = $evaluation_question["equestion_id"];
                                foreach ($evaluation_question["question_objectives"] as $objective) {
                                    if (!array_key_exists($objective["top_parent"]["objective_id"], $top_level_objectives)) {
                                        $top_level_objectives[$objective["top_parent"]["objective_id"]] = $objective["top_parent"];
                                        $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_question_ids"] = array($evaluation_question["equestion_id"]);
                                        $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_dates"] = $evaluation_question["evaluation_dates"];
                                    } elseif (!in_array($evaluation_question["equestion_id"], $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_question_ids"])) {
                                        if ($objective["objective_id"] == $objective["top_parent"]["objective_id"] && isset($objective["equestion_ids"]) && @count($objective["equestion_ids"])) {
                                            if (!isset($top_level_objectives[$objective["top_parent"]["objective_id"]]["equestion_ids"])) {
                                                $top_level_objectives[$objective["top_parent"]["objective_id"]]["equestion_ids"] = array();
                                            }
                                            foreach ($objective["equestion_ids"] as $question_id) {
                                                $top_level_objectives[$objective["top_parent"]["objective_id"]]["equestion_ids"][] = $question_id;
                                            }
                                        }
                                        $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_question_ids"][] = $evaluation_question["equestion_id"];
                                        foreach ($evaluation_question["evaluation_dates"] as $date => $eprogress_ids) {
                                            if (array_key_exists($date, $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_dates"])) {
                                                foreach ($eprogress_ids["eprogress_ids"] as $eprogress_id) {
                                                    if (!in_array($eprogress_id, $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_dates"][$date]["eprogress_ids"])) {
                                                        $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_dates"][$date]["eprogress_ids"][] = $eprogress_id;
                                                    }
                                                }
                                            } else {
                                                $top_level_objectives[$objective["top_parent"]["objective_id"]]["evaluation_dates"][$date] = $eprogress_ids;
                                            }
                                        }
                                    }
                                }
                            }
                            $evaluation_questions[$evaluation_question["equestion_id"]] = $evaluation_question;
                        }
                    }
                }
                $output_rows = array();
                if (isset($top_level_objectives) && @count($top_level_objectives)) {
                    foreach ($top_level_objectives as $objective_id => $objective) {
                        $row = $objective;
                        $row["title"] = $objective["objective_name"];
                        $row["type"] = "objective";
                        $row["children"] = array();
                        $row["responses"] = array();
                        $response_count = 0;
                        foreach ($objective["evaluation_question_ids"] as $equestion_id) {
                            foreach ($evaluation_questions[$equestion_id]["question_objectives"] as $question_objective) {
                                if (isset($question_objective["parents"]) && count($question_objective["parents"])) {
                                    if ((count($question_objective["parents"]) - 2) >= 0) {
                                        for ($i = (count($question_objective["parents"]) - 2); $i >= 0; $i--) {
                                            $found = false;
                                            foreach ($row["children"] as $child_key => $child_objective) {
                                                if ($child_objective["objective_id"] == $question_objective["parents"][$i]["objective_id"]) {
                                                    $found = $child_key;
                                                }
                                            }
                                            if (!isset($question_objective["parents"][$i]["evaluation_dates"])) {
                                                $question_objective["parents"][$i]["evaluation_dates"] = $evaluation_questions[$equestion_id]["evaluation_dates"];
                                            } else {
                                                foreach ($evaluation_questions[$equestion_id]["evaluation_dates"] as $date => $eprogress_ids) {
                                                    if (array_key_exists($date, $question_objective["parents"][$i]["evaluation_dates"])) {
                                                        foreach ($eprogress_ids as $eprogress_id) {
                                                            if (!in_array($eprogress_id, $question_objective["parents"][$i]["evaluation_dates"][$date])) {
                                                                $question_objective["parents"][$i]["evaluation_dates"][$date][] = $eprogress_id;
                                                            }
                                                        }
                                                    } else {
                                                        $question_objective["parents"][$i]["evaluation_dates"][$date] = $eprogress_ids;
                                                    }
                                                }
                                            }
                                            if ($found === false) {
                                                $count = 1;
                                                foreach ($evaluation_questions[$equestion_id]["question_responses"] as $question_response) {
                                                    if (!isset($question_objective["parents"][$i]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count])) {
                                                        $question_objective["parents"][$i]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] = 0;
                                                    }
                                                    $question_objective["parents"][$i]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] += $question_response["chosen"];
                                                    $count++;
                                                }
                                                if (!isset($question_objective["parents"][$i]["evaluation_question_ids"])) {
                                                    $question_objective["parents"][$i]["evaluation_question_ids"] = array();
                                                }
                                                $question_objective["parents"][$i]["evaluation_question_ids"][] = $equestion_id;
                                                $row["children"][$question_objective["parents"][$i]["objective_id"]] = $question_objective["parents"][$i];
                                            } else {
                                                $count = 1;
                                                foreach ($evaluation_questions[$equestion_id]["question_responses"] as $question_response) {
                                                    if (!isset($row["children"][$found]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count])) {
                                                        $row["children"][$found]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] = 0;
                                                    }
                                                    $row["children"][$found]["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] += $question_response["chosen"];
                                                    $count++;
                                                }
                                                if (!isset($row["children"][$found]["evaluation_question_ids"])) {
                                                    $row["children"][$found]["evaluation_question_ids"] = array();
                                                }
                                                $row["children"][$found]["evaluation_question_ids"][] = $equestion_id;
                                            }
                                        }
                                    }
                                    $temp_objective = $question_objective;
                                    unset($temp_objective["parents"]);
                                    unset($temp_objective["top_parent"]);
                                    if (!isset($temp_objective["evaluation_question_ids"])) {
                                        $temp_objective["evaluation_question_ids"] = array();
                                    }
                                    $temp_objective["evaluation_question_ids"][] = $equestion_id;
                                    if (!isset($temp_objective["evaluation_dates"])) {
                                        $temp_objective["evaluation_dates"] = $evaluation_questions[$equestion_id]["evaluation_dates"];
                                    } else {
                                        foreach ($evaluation_questions[$equestion_id]["evaluation_dates"] as $date => $eprogress_ids) {
                                            if (array_key_exists($date, $temp_objective["evaluation_dates"])) {
                                                foreach ($eprogress_ids["eprogress_ids"] as $eprogress_id) {
                                                    if (!in_array($eprogress_id, $temp_objective["evaluation_dates"][$date]["eprogress_ids"])) {
                                                        $temp_objective["evaluation_dates"][$date]["eprogress_ids"][] = $eprogress_id;
                                                    }
                                                }
                                            } else {
                                                $temp_objective["evaluation_dates"][$date] = $eprogress_ids;
                                            }
                                        }
                                    }
                                    $count = 1;
                                    foreach ($evaluation_questions[$equestion_id]["question_responses"] as $question_response) {
                                        if (!isset($temp_objective["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count])) {
                                            $temp_objective["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] = 0;
                                        }
                                        $temp_objective["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] += $question_response["chosen"];
                                        $count++;
                                    }
                                    if (!isset($row["children"][$temp_objective["objective_id"]])) {
                                        $row["children"][$temp_objective["objective_id"]] = $temp_objective;
                                    } else {
                                        if (isset($temp_objective["equestion_ids"]) && @count($temp_objective["equestion_ids"])) {
                                            if (!isset($row["children"][$temp_objective["objective_id"]]["equestion_ids"])) {
                                                $row["children"][$temp_objective["objective_id"]]["equestion_ids"] = array();
                                            }
                                            foreach ($temp_objective["equestion_ids"] as $equestion_id) {
                                                $row["children"][$temp_objective["objective_id"]]["equestion_ids"][] = $equestion_id;
                                            }
                                            if (!isset($row["children"][$temp_objective["objective_id"]]["evaluation_question_ids"])) {
                                                $row["children"][$temp_objective["objective_id"]]["evaluation_question_ids"] = array();
                                            }
                                            foreach ($temp_objective["evaluation_question_ids"] as $evaluation_question_ids) {
                                                $row["children"][$temp_objective["objective_id"]]["evaluation_question_ids"][] = $evaluation_question_ids;
                                            }
                                        }
                                        foreach ($temp_objective["evaluation_dates"] as $date => $array) {
                                            if (!isset($row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date])) {
                                               $row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date] = $array; 
                                            } else {
                                                foreach ($array["eprogress_ids"] as $eprogress_id) {
                                                    $row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date]["eprogress_ids"][] = $eprogress_id;
                                                }
                                                if (isset($array["responses"])) {
                                                    foreach ($array["responses"] as $response_key => $response_set) {
                                                        if (!isset($row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date]["responses"][$response_key])) {
                                                            $row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date]["responses"][$response_key] = $response_set;
                                                        } else {
                                                            foreach ($row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date]["responses"][$response_key] as $order => $response) {
                                                                $row["children"][$temp_objective["objective_id"]]["evaluation_dates"][$date]["responses"][$response_key][$order] = $response;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if (!$response_count) {
                                $response_count = count($evaluation_questions[$equestion_id]["question_responses"]);
                            } elseif ($response_count != count($evaluation_questions[$equestion_id]["question_responses"])) {
                                $row["unmatching_responses"] = true;
                            }
                            $count = 1;
                            foreach ($evaluation_questions[$equestion_id]["question_responses"] as $question_response) {
                                if (!isset($row["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count])) {
                                    $row["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] = 0;
                                }
                                $row["responses"][count($evaluation_questions[$equestion_id]["question_responses"])][$count] += $question_response["chosen"];
                                $count++;
                            }
                        }
                        $output_rows[] = $row;
                    }
                }
                foreach ($evaluation_questions as $question) {
                    $row = $question;
                    $row["title"] = $question["question_text"];
                    $row["type"] = "question";
                    $row["children"] = array();
                    $row["responses"][count($question["flat_responses"])] = $question["flat_responses"];
                    $row["display"] = (in_array($question["equestion_id"], $hidden_question_ids) ? false : true);
                    $output_rows[] = $row;
                }
                foreach ($output_rows as $key => $row) {
                    if (isset($row["children"]) && @count($row["children"])) {
                        foreach ($row["children"] as $child_key => $child_row) {
                            $response_count = 0;
                            $child_row["response_total"] = array();
                            foreach ($child_row["responses"] as $responses_count => $responses) {
                                foreach ($child_row["responses"][$responses_count] as $response_key => $response) {
                                    if (!isset($child_row["response_total"][$responses_count])) {
                                        $child_row["response_total"][$responses_count] = 0;
                                    }
                                    $child_row["response_total"][$responses_count] += ((($response_key) * $response) / count($child_row["responses"][$responses_count]));
                                    $response_count += $response;
                                }
                                $output_rows[$key]["children"][$child_key]["response_total"][$responses_count] = $child_row["response_total"][$responses_count] / $response_count;
                            }
                        }
                    }
                    $response_count = 0;
                    $row["response_total"] = array();
                    foreach ($row["responses"] as $responses_count => $responses) {
                        if (@count($row["responses"][$responses_count])) {
                            foreach ($row["responses"][$responses_count] as $response_key => $response) {
                                if (!isset($row["response_total"][$responses_count])) {
                                    $row["response_total"][$responses_count] = 0;
                                }
                                $row["response_total"][$responses_count] += (($response_key) * $response) / count($row["responses"][$responses_count]);
                                $response_count += $response;
                            }
                            $output_rows[$key]["response_total"] = $row["response_total"][$responses_count] / $response_count;
                        }
                    }
                }
                $temp_hierarchical_list = array();
                $hierarchical_list = array();
                
                foreach ($output_rows as $output_row) {
                    $temp_row = $output_row;
                    foreach ($output_row["evaluation_dates"] as $date => $date_info) {
                        $eprogress_ids_string = "";
                        foreach ($date_info["eprogress_ids"] as $eprogress_id) {
                            $eprogress_ids_string .= ($eprogress_ids_string ? ", " : "").$db->qstr($eprogress_id);
                        }
                        $equestion_ids_string = "";
                        if (!isset($temp_row["evaluation_dates"][$date]["responses"])) {
                            $temp_row["evaluation_dates"][$date]["responses"] = array();
                        }
                        if (!isset($temp_row["evaluation_dates"][$date]["detailed_responses"])) {
                            $temp_row["evaluation_dates"][$date]["detailed_responses"] = array();
                        }
                        if ($temp_row["type"] == "objective") {
                            foreach ($temp_row["evaluation_question_ids"] as $equestion_id) {
                                $query = "SELECT COUNT(b.`eqresponse_id`) AS `chosen`, a.`eqresponse_id`, a.`response_order`, a.`response_text` FROM `evaluations_lu_question_responses` AS a
                                            LEFT JOIN `evaluation_responses` AS b
                                            ON a.`eqresponse_id` = b.`eqresponse_id`
                                            AND b.`eprogress_id` IN (".$eprogress_ids_string.")
                                            WHERE a.`equestion_id` = ".$db->qstr($equestion_id)."
                                            GROUP BY a.`eqresponse_id`
                                            ORDER BY a.`response_order`";
                                $question_responses = $db->GetAll($query);
                                if ($question_responses) {
                                    if (!isset($temp_row["evaluation_dates"][$date]["responses"][count($question_responses)])) {
                                        $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)] = array();
                                    }
                                    if (!isset($temp_row["evaluation_dates"][$date]["detailed_responses"])) {
                                        $temp_row["evaluation_dates"][$date]["detailed_responses"] = array();
                                    }
                                    foreach ($question_responses as $question_response) {
                                        if (!isset($temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]])) {
                                            $temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]] = array("chosen" => 0, "text" => $question_response["response_text"]);
                                        }
                                        if (!isset($temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]])) {
                                            $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] = 0;
                                        }
                                        $temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]]["chosen"] += (int)$question_response["chosen"];
                                        $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] += (int)$question_response["chosen"];
                                    }
                                }
                            }
                        } else {
                            $query = "SELECT COUNT(b.`eqresponse_id`) AS `chosen`, a.`eqresponse_id`, a.`response_order`, a.`response_text` FROM `evaluations_lu_question_responses` AS a
                                        LEFT JOIN `evaluation_responses` AS b
                                        ON a.`eqresponse_id` = b.`eqresponse_id`
                                        AND b.`eprogress_id` IN (".$eprogress_ids_string.")
                                        WHERE a.`equestion_id` = ".$db->qstr($temp_row["equestion_id"])."
                                        GROUP BY a.`eqresponse_id`
                                        ORDER BY a.`response_order`";
                            $question_responses = $db->GetAll($query);
                            if ($question_responses) {
                                if (!isset($temp_row["evaluation_dates"][$date]["responses"][count($question_responses)])) {
                                    $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)] = array();
                                }
                                if (!isset($temp_row["evaluation_dates"][$date]["detailed_responses"])) {
                                    $temp_row["evaluation_dates"][$date]["detailed_responses"] = array();
                                }
                                foreach ($question_responses as $question_response) {
                                    if (!isset($temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]])) {
                                        $temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]] = array("chosen" => 0, "text" => $question_response["response_text"]);
                                    }
                                    if (!isset($temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]])) {
                                        $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] = 0;
                                    }
                                    $temp_row["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]]["chosen"] += (int)$question_response["chosen"];
                                    $temp_row["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] += (int)$question_response["chosen"];
                                }
                            }
                        }
                        
                        $response_count = 0;
                        $temp_row["evaluation_dates"][$date]["response_total"] = array();
                        foreach ($temp_row["evaluation_dates"][$date]["responses"] as $responses_count => $responses) {
                            if (@count($temp_row["evaluation_dates"][$date]["responses"][$responses_count])) {
                                foreach ($temp_row["evaluation_dates"][$date]["responses"][$responses_count] as $response_key => $response) {
                                    if (!isset($temp_row["evaluation_dates"][$date]["response_total"][$responses_count])) {
                                        $temp_row["evaluation_dates"][$date]["response_total"][$responses_count] = 0;
                                    }
                                    $temp_row["evaluation_dates"][$date]["response_total"][$responses_count] += (($response_key) * $response) / count($temp_row["evaluation_dates"][$date]["responses"][$responses_count]);
                                    $response_count += $response;
                                }
                                $temp_row["evaluation_dates"][$date]["response_total"][$responses_count] = $temp_row["evaluation_dates"][$date]["response_total"][$responses_count] / $response_count;
                            }
                        }
                    }
                    $temp_row["children"] = array();
                    if (isset($output_row["children"]) && @count($output_row["children"])) {
                        foreach ($output_row["children"] as $child) {
                            foreach ($child["evaluation_dates"] as $date => $date_info) {
                                $eprogress_ids_string = "";
                                foreach ($date_info["eprogress_ids"] as $eprogress_id) {
                                    $eprogress_ids_string .= ($eprogress_ids_string ? ", " : "").$db->qstr($eprogress_id);
                                }
                                $equestion_ids_string = "";
                                if (!isset($child["evaluation_dates"][$date]["responses"])) {
                                    $child["evaluation_dates"][$date]["responses"] = array();
                                }
                                if (!isset($child["evaluation_dates"][$date]["detailed_responses"])) {
                                    $child["evaluation_dates"][$date]["detailed_responses"] = array();
                                }
                                foreach ($child["evaluation_question_ids"] as $equestion_id) {
                                    $query = "SELECT COUNT(b.`eqresponse_id`) AS `chosen`, a.`eqresponse_id`, a.`response_order`, a.`response_text` FROM `evaluations_lu_question_responses` AS a
                                                LEFT JOIN `evaluation_responses` AS b
                                                ON a.`eqresponse_id` = b.`eqresponse_id`
                                                AND b.`eprogress_id` IN (".$eprogress_ids_string.")
                                                WHERE a.`equestion_id` = ".$db->qstr($equestion_id)."
                                                GROUP BY a.`eqresponse_id`
                                                ORDER BY a.`equestion_id`, a.`response_order`";
                                    $question_responses = $db->GetAll($query);
                                    if ($question_responses) {
                                        if (!isset($child["evaluation_dates"][$date]["responses"][count($question_responses)])) {
                                            $child["evaluation_dates"][$date]["responses"][count($question_responses)] = array();
                                        }
                                        if (!isset($child["evaluation_dates"][$date]["detailed_responses"])) {
                                            $child["evaluation_dates"][$date]["detailed_responses"] = array();
                                        }
                                        foreach ($question_responses as $question_response) {
                                            if (!isset($child["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]])) {
                                                $child["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]] = array("chosen" => 0, "text" => $question_response["response_text"]);
                                            }
                                            if (!isset($child["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]])) {
                                                $child["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] = 0;
                                            }
                                            $child["evaluation_dates"][$date]["responses"][count($question_responses)][$question_response["response_order"]] += (int)$question_response["chosen"];
                                            $child["evaluation_dates"][$date]["detailed_responses"][$question_response["eqresponse_id"]]["chosen"] += (int)$question_response["chosen"];
                                        }
                                    }
                                }
                        
                                $response_count = 0;
                                $child["evaluation_dates"][$date]["response_total"] = array();
                                foreach ($child["evaluation_dates"][$date]["responses"] as $responses_count => $responses) {
                                    if (@count($child["evaluation_dates"][$date]["responses"][$responses_count])) {
                                        foreach ($child["evaluation_dates"][$date]["responses"][$responses_count] as $response_key => $response) {
                                            if (!isset($child["evaluation_dates"][$date]["response_total"][$responses_count])) {
                                                $child["evaluation_dates"][$date]["response_total"][$responses_count] = 0;
                                            }
                                            $child["evaluation_dates"][$date]["response_total"][$responses_count] += (($response_key) * $response) / count($child["evaluation_dates"][$date]["responses"][$responses_count]);
                                            $response_count += $response;
                                        }
                                        $child["evaluation_dates"][$date]["response_total"][$responses_count] = $child["evaluation_dates"][$date]["response_total"][$responses_count] / $response_count;
                                    }
                                }
                            }
                            $temp_row = addChild($temp_row, $child);
                        }
                        $temp_row["children"] = ksortRecursive($temp_row["children"]);
                    }
                    $temp_hierarchical_list[] = $temp_row;
                }
                $flat_questions = flattenQuestions($temp_hierarchical_list);
                $temp_hierarchical_list = hierarchicalIterator($temp_hierarchical_list);
                $flat_objectives = flattenObjectives($temp_hierarchical_list);
                foreach ($temp_hierarchical_list as $record) {
                    if ($record["type"] == "objective" || $record["display"]) {
                        $hierarchical_list[] = $record;
                    }
                }
                unset($temp_hierarchical_list);
            }
        break;
        case 2 :
            if (isset($_POST["proxy_id"]) && ((int)$_POST["proxy_id"])) {
                $PROCESSED["proxy_id"] = ((int) $_POST["proxy_id"]);
            } else {
                add_error("A <strong>Learner</strong> must be selected from the list to continue.");
            }
            
            if (isset($_POST["report_start"]) && $_POST["report_start"] && strtotime($_POST["report_start"])) {
                $report_start = trim($_POST["report_start"]);
                $PROCESSED["report_start"] = strtotime($report_start . " 00:00");
            } else {
                add_error("The <strong>Report Start</strong> date is required. Please set a date for the report to start from to continue.");
            }
            
            if (isset($_POST["report_finish"]) && $_POST["report_finish"] && strtotime($_POST["report_finish"]) && (!isset($PROCESSED["report_start"]) || $PROCESSED["report_start"] < strtotime($_POST["report_finish"]))) {
                $report_finish = trim($_POST["report_finish"]);
                $PROCESSED["report_finish"] = strtotime($report_finish . " 23:59");
            } else {
                add_error("The <strong>Report Finish</strong> date is required. Please set a date for the report to end on (which is higher than the start date) to continue.");
            }
            
            if (has_error()) {
                $STEP = 1;
            }
            
        break;
    }
    
	switch($STEP) {
		case 3 :
            $ONLOAD[] = "renderDOM()";
            /**
             * Add PlotKit to the beginning of the $HEAD array.
             */
            array_unshift($HEAD,
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/MochiKit/MochiKit.js\"></script>",
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/excanvas.js\"></script>",
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Base.js\"></script>",
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Layout.js\"></script>",
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/Canvas.js\"></script>",
                "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/PlotKit/SweetCanvas.js\"></script>"
                );
            ?>
            <script type="text/javascript">
                var hasCanvas = CanvasRenderer.isSupported();
                var evaluation_questions = <?php echo json_encode($hierarchical_list); ?>;
                var flat_objectives = <?php echo json_encode($flat_objectives); ?>;
                var flat_questions = <?php echo json_encode($flat_questions); ?>;
                function renderDOM(row_type, row_id) {
                    var default_min_date = <?php echo ((int)$PROCESSED["report_start"]); ?>;
                    var default_max_date = <?php echo ((int)$PROCESSED["report_finish"]); ?>;
                    var min_date = parseInt(jQuery('#report_start').val() ? (new Date(jQuery('#report_start').val()).getTime() / 1000) : default_min_date);
                    var max_date = buildDate(min_date, jQuery('#report_period').val(), false, default_max_date);
                    min_date = buildDate(min_date, jQuery('#report_period').val(), true);
                    jQuery('#evaluation-question-breadcrumb').html('');
                    jQuery('#evaluation-question-title').html('');
                    if (typeof row_type === 'undefined' || typeof row_id === 'undefined') {
                        jQuery('#current_type').val('');
                        jQuery('#current_id').val('');
                        jQuery('#evaluation-question-dates').hide();
                        jQuery('#evaluation-question-list ul').html('');
                        for (var i in evaluation_questions) {
                            if (!evaluation_questions.hasOwnProperty(i)) {
                                continue;
                            }
                            jQuery('#evaluation-question-list ul').append('<li><a href="javascript: renderDOM(\''+evaluation_questions[i]['type']+'\', \''+(evaluation_questions[i]['type'] == 'objective' ? evaluation_questions[i]['objective_id'] : evaluation_questions[i]['equestion_id'])+'\')">' + evaluation_questions[i]['title'] + '</a></li>');
                        }
                        jQuery('#evaluation-question-details').html('<div class="display-notice">Please select an Objective or Evaluation Question from the left to view progress.</div>');
                    } else {
                        jQuery('#current_type').val(row_type);
                        jQuery('#current_id').val(row_id);
                        jQuery('#evaluation-question-details').html('<div><canvas id="canvasline" width="400" height="200"></canvas></div>');
                        if (row_type == 'objective' && typeof flat_objectives[row_id]['children'] !== 'undefined') {
                            jQuery('#evaluation-question-list ul').html('');
                            for (var i in flat_objectives[row_id]['children']) {
                                if (!flat_objectives[row_id]['children'].hasOwnProperty(i)) {
                                    continue;
                                }
                                if (typeof flat_objectives[row_id]['children'][i]['objective_id'] !== 'undefined') {
                                    jQuery('#evaluation-question-list ul').append('<li><a href="javascript: renderDOM(\'objective\', \''+ flat_objectives[row_id]['children'][i]['objective_id'] +'\')">' + flat_objectives[row_id]['children'][i]['objective_name'] + '</a></li>');
                                } else {
                                    jQuery('#evaluation-question-list ul').append('<li><a href="javascript: renderDOM(\'question\', \''+ flat_objectives[row_id]['children'][i]['equestion_id'] +'\')">' + flat_objectives[row_id]['children'][i]['question_text'] + '</a></li>');
                                }
                            }
                            
                        }
                        var dataset = [];
                        var xTicks = [];
                        if (row_type == 'objective') {
                            jQuery('#evaluation-question-title').html(flat_objectives[row_id]['objective_name']);
                            if (typeof flat_objectives[flat_objectives[row_id]['objective_parent']] !== 'undefined') {
                                var parent_id = flat_objectives[row_id]['objective_parent'];
                                while (parent_id && typeof flat_objectives[parent_id] !== 'undefined') {
                                    jQuery('#evaluation-question-breadcrumb').prepend(' / <a class="objective-link" href="javascript: renderDOM(\'objective\', '+ parent_id +')">'+flat_objectives[parent_id]['objective_name']+'</a>');
                                    parent_id = flat_objectives[parent_id]['objective_parent'];
                                }
                            }
                            if (typeof flat_objectives[row_id]['children'] !== 'undefined' || typeof flat_objectives[flat_objectives[row_id]['objective_parent']] !== 'undefined') {
                                jQuery('#evaluation-question-breadcrumb').prepend(' / <a class="objective-link" href="javascript: renderDOM()">All Questions</a>');
                            }
                            var count = 0;
                            jQuery('#evaluation-question-details').append('<br /><h3>Responses:</h3>');
                            for (var i in flat_objectives[row_id]['evaluation_dates']) {
                                if (!flat_objectives[row_id]['evaluation_dates'].hasOwnProperty(i) || (typeof min_date != 'undefined' && parseInt(i) < min_date) || (typeof max_date != 'undefined' && parseInt(i) > max_date)) {
                                    continue;
                                }
                                jQuery('#evaluation-question-details').append('<br /><h4>'+timeConverter(i)+'</h4>');
                                jQuery('#evaluation-question-details').append('<ul>');
                                for (var j in flat_objectives[row_id]['evaluation_dates'][i]['detailed_responses']) {
                                    if (!flat_objectives[row_id]['evaluation_dates'][i]['detailed_responses'].hasOwnProperty(j)) {
                                        continue;
                                    }
                                    
                                    jQuery('#evaluation-question-details').append('<li>'+flat_objectives[row_id]['evaluation_dates'][i]['detailed_responses'][j]['text']+' : '+flat_objectives[row_id]['evaluation_dates'][i]['detailed_responses'][j]['chosen']+'</li>');
                                }
                                for (var j in flat_objectives[row_id]['evaluation_dates'][i]['response_total']) {
                                    if (!flat_objectives[row_id]['evaluation_dates'][i]['response_total'].hasOwnProperty(j)) {
                                        continue;
                                    }
                                    if (typeof dataset[j] == 'undefined') {
                                        dataset[j] = [];
                                    }
                                    dataset[j][dataset[j].length] = [count, flat_objectives[row_id]['evaluation_dates'][i]['response_total'][j]];
                                }
                                jQuery('#evaluation-question-details').append('</ul>');
                                xTicks[xTicks.length] = {label: timeConverter(i), v: count};
                                count++;
                            }
                        } else {
                            if (row_type == 'question' && typeof flat_questions[row_id]['objective_parent'] !== 'undefined') {
                                if (typeof flat_objectives[flat_questions[row_id]['objective_parent']] !== 'undefined') {
                                    var parent_id = flat_questions[row_id]['objective_parent'];
                                    while (parent_id && typeof flat_objectives[parent_id] !== 'undefined') {
                                        jQuery('#evaluation-question-breadcrumb').prepend(' / <a class="objective-link" href="javascript: renderDOM(\'objective\', '+ parent_id +')">'+flat_objectives[parent_id]['objective_name']+'</a>');
                                        parent_id = flat_objectives[parent_id]['objective_parent'];
                                    }
                                }
                                jQuery('#evaluation-question-breadcrumb').prepend(' / <a class="objective-link" href="javascript: renderDOM()">All Questions</a>');
                            }
                            jQuery('#evaluation-question-title').html(flat_questions[row_id]['question_text']);
                            jQuery('#evaluation-question-details').append('<br />');
                            var count = 0;
                            for (var i in flat_questions[row_id]['evaluation_dates']) {
                                if (!flat_questions[row_id]['evaluation_dates'].hasOwnProperty(i) || (typeof min_date != 'undefined' && parseInt(i) < min_date) || (typeof max_date != 'undefined' && parseInt(i) > max_date)) {
                                    continue;
                                }
                                jQuery('#evaluation-question-details').append('<br /><h4>'+timeConverter(i)+'</h4>');
                                jQuery('#evaluation-question-details').append('<ul>');
                                for (var j in flat_questions[row_id]['evaluation_dates'][i]['detailed_responses']) {
                                    if (!flat_questions[row_id]['evaluation_dates'][i]['detailed_responses'].hasOwnProperty(j)) {
                                        continue;
                                    }
                                    
                                    jQuery('#evaluation-question-details').append('<li>'+flat_questions[row_id]['evaluation_dates'][i]['detailed_responses'][j]['text']+' : '+flat_questions[row_id]['evaluation_dates'][i]['detailed_responses'][j]['chosen']+'</li>');
                                }
                                for (var j in flat_questions[row_id]['evaluation_dates'][i]['response_total']) {
                                    if (!flat_questions[row_id]['evaluation_dates'][i]['response_total'].hasOwnProperty(j)) {
                                        continue;
                                    }
                                    if (typeof dataset[j] == 'undefined') {
                                        dataset[j] = [];
                                    }
                                    dataset[j][dataset[j].length] = [count, flat_questions[row_id]['evaluation_dates'][i]['response_total'][j]];
                                }
                                jQuery('#evaluation-question-details').append('</ul>');
                                xTicks[xTicks.length] = {label: timeConverter(i), v: count};
                                count++;
                            }
                        }
                        jQuery('#evaluation-question-dates').show();
                        jQuery('.datepicker').datepicker({
                            dateFormat: 'yy-mm-dd'
                        });
                        jQuery('.add-on').on('click', function() {
                            if (jQuery(this).siblings('input').is(':enabled')) {
                                jQuery(this).siblings('input').focus();
                            }
                        });
                        jQuery('#report_start').val(timeConverter(min_date, true));
                        jQuery('#report_end').val(timeConverter(max_date, true));
                        
                        var options = {
                           'IECanvasHTC' : '<?php echo ENTRADA_RELATIVE; ?>/javascript/plotkit/iecanvas.htc',
                            'drawYAxis' : false,
                            'xOriginIsZero' : false,
                            'yAxis' : [0, 1.1],
                            'xTicks' : xTicks
                        };
                        var layout	= new PlotKit.Layout('line', options);
                        for (var i in dataset) {
                            if (!dataset.hasOwnProperty(i)) {
                                continue;
                            }
                            layout.addDataset(j, dataset[j]);
                        }
                        layout.evaluate();

                        var canvas	= MochiKit.DOM.getElement('canvasline');
                        var plotter	= new PlotKit.SweetCanvasRenderer(canvas, layout, options);
                        plotter.render();
                    }
                }
                
                function timeConverter(UNIX_timestamp, datepicker){
                    if (typeof datepicker === 'undefined') {
                        var a = new Date(UNIX_timestamp*1000);
                        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        var year = a.getUTCFullYear();
                        var month = months[a.getUTCMonth()];
                        var date = a.getUTCDate();
                        var time = date+' '+month+', '+year;
                    } else {
                        var a = new Date(UNIX_timestamp*1000);
                        var year = a.getUTCFullYear();
                        var month = a.getUTCMonth() + 1;
                        var date = a.getUTCDate();
                        var time = year+'-'+month+'-'+date;
                    }
                    return time;
                }
                
                function buildDate(timestamp, period, start, default_max_date) {
                    if (typeof period === 'undefined') {
                        period = 'full';
                    }
                    if (typeof start === 'undefined') {
                        start = true;
                    }
                    
                    var temp_date = new Date(timestamp * 1000);
                    var year = temp_date.getYear() + 1900;
                    var month = temp_date.getUTCMonth();
                    var day = temp_date.getUTCDate();
                    var weekday = temp_date.getUTCDay();

                    if (start) {
                        if (period == 'year') {
                            month = 0;
                            day = 1;
                        } else if (period == 'month') {
                            day = 1;
                        } else if (period == 'week') {
                            day = day - weekday;
                        }
                    } else {
                        if (typeof default_max_date !== 'undefined' && period == 'full') {
                            temp_date = new Date(default_max_date * 1000);
                            year = temp_date.getUTCFullYear();
                            month = temp_date.getUTCMonth();
                            day = temp_date.getUTCDate();
                            weekday = temp_date.getUTCDay();
                        } else if (period == 'year') {
                            month = 0;
                            year = year + 1;
                            day = 0;
                        } else if (period == 'month') {
                            month = month + 1;
                            day = 0;
                        } else if (period == 'week') {
                            day = day + (6 - weekday);
                        }
                    }
                    
                    var output_timestamp = new Date(year, month, day);
                    return (output_timestamp.getTime() / 1000);
                }
                
                function addPeriod (previous) {
                    var current_date = parseInt(jQuery('#report_start').val() ? (new Date(jQuery('#report_start').val()).getTime() / 1000) : default_min_date);
                    current_date = new Date(current_date * 1000);
                    var year = current_date.getUTCFullYear();
                    var month = current_date.getUTCMonth();
                    var day = current_date.getUTCDate();
                    var weekday = current_date.getUTCDay();
                    var period = jQuery('#report_period').val();
                    if (typeof previous === 'undefined') {
                        if (period == 'year') {
                            year = year + 1;
                        } else if (period == 'month') {
                            month = month + 1;
                            if (month > 11) {
                                month = 0;
                                year = year + 1;
                            }
                        } else if (period == 'week') {
                            day = (day + 7) - weekday;
                        } else if (period == 'day') {
                            day = day + 1;
                        }
                    } else {
                        if (period == 'year') {
                            year = year - 1;
                        } else if (period == 'month') {
                            month = month - 1;
                            if (month < 0) {
                                month = 11;
                                year = year - 1;
                            }
                        } else if (period == 'week') {
                            day = day - (weekday + 7);
                        } else if (period == 'day') {
                            day = day - 1;
                        }
                    }
                    var output_timestamp = new Date(year, month, day);
                    jQuery('#report_start').val(timeConverter((output_timestamp.getTime() / 1000), true));
                    renderDOM(jQuery('#current_type').val(), jQuery('#current_id').val())
                    
                }
                
                jQuery(function($) {
                    $(".period-buttons button.period").click(function () {
                        $("#report_period").val($(this).val());
                        renderDOM(jQuery('#current_type').val(), jQuery('#current_id').val())
                    });
                    $(".period-buttons button.next").click(function () {
                        addPeriod();
                    });
                    $(".period-buttons button.prev").click(function () {
                        addPeriod(true);
                    });
                });
            </script>
            <input type="hidden" value="" id="current_type" />
            <input type="hidden" value="" id="current_id" />
            <div id="evaluation-question-breadcrumb"></div>
            <div id="evaluation-question-browser">
                
                <div id="evaluation-question-list">
                    <ul> 
                    </ul>
                </div>
                <div id="evaluation-question-container">
                    <h2 id="evaluation-question-title"></h2>
                    <div id="evaluation-question-dates" style="display: none;">
                        <div class="row-fluid space-above">
                            <span class="span1">&nbsp;</span>
                            <label class="control-label span3" for="report_start">Reporting Start: </label>
                            <span class="span8">
                                <div class="input-append">
                                    <input type="text" class="input-small datepicker" value="" name="report_start" id="report_start" onchange="renderDOM(jQuery('#current_type').val(), jQuery('#current_id').val())" />
                                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                </div>
                            </span>
                        </div>
                        <div class="row-fluid space-above">
                            <div class="offset1 span11">
                                <div class="btn-group period-buttons" data-toggle="buttons-radio">
                                    <button class="btn prev"><i class="icon-chevron-left"></i></button>
                                    <button class="btn period" value="day">Day</button>
                                    <button class="btn period" value="week">Week</button>
                                    <button class="btn period" value="month">Month</button>
                                    <button class="btn period" value="year">Year</button>
                                    <button class="btn period active" value="full">Full Period</button>
                                    <button class="btn next"><i class="icon-chevron-right"></i></button>
                                </div>
                                <input type="hidden" id="report_period" value="full" />
                            </div>
                        </div>
                    </div>
                    <div id="evaluation-question-details"></div>
                </div>
            </div>
            <?php
            
        break;
        case 2 :
			if($ERROR) {
				echo display_error($ERRORSTR);
			}
			if($SUCCESS) {
				echo display_success($SUCCESSSTR);
			}
            
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
            $HEAD[] = "<script type=\"text/javascript\">
                jQuery(function($) {
                    jQuery('#evaluations').dataTable(
                        {
                            'sPaginationType': 'full_numbers',
                            'bInfo': false,
                            'bAutoWidth': false,
                            'sAjaxSource': '?section=api-list',
                            'bServerSide': true,
                            'bProcessing': true,
                            'aoColumns': [
                                { 'mDataProp': 'checkbox', 'bSortable': false },
                                { 'mDataProp': 'evaluation_title' },
                                { 'mDataProp': 'evaluation_start' },
                                { 'mDataProp': 'evaluation_finish' },
                                { 'mDataProp': 'completions' }
                            ],
                            'oLanguage': {
                                'sEmptyTable': 'There are currently no learner evaluations in the system.',
                                'sZeroRecords': 'No evaluations found to display.'
                            },
                            'fnServerData': function ( sSource, aoData, fnCallback ) {
                                /* Add some extra data to the sender */
                                aoData.push({ 'name': 'proxy_id', 'value': '".$PROCESSED["proxy_id"]."' });
                                aoData.push({ 'name': 'report_start', 'value': '".$PROCESSED["report_start"]."' });
                                aoData.push({ 'name': 'report_finish', 'value': '".$PROCESSED["report_finish"]."' });
                                var evaluation_ids = $('#evaluations input:checkbox:checked').map(function(){
                                    return $(this).val();
                                }).get();
                                if (evaluation_ids != null) {
                                   for (x = 0; x < evaluation_ids.length; x++) {
                                      aoData.push({ 'name': 'evaluation_ids[' + x + ']', 'value': evaluation_ids[x] });
                                   }
                                }
                                $.getJSON( sSource, aoData, function (json) { 
                                    fnCallback(json)
                                } );
                            }
                        }
                    );
                });
            </script>";
 			?>
            <h2>Select one or more Evaluations:</h2>
            <form action="<?php echo html_encode(ENTRADA_URL); ?>/admin/evaluations/reports?section=learner-evaluations&step=3" method="POST" id="evaluation-report-form">
                <input type="hidden" name="proxy_id" value="<?php echo $PROCESSED["proxy_id"]; ?>" />
                <input type="hidden" name="report_start" value="<?php echo $PROCESSED["report_start"]; ?>" />
                <input type="hidden" name="report_finish" value="<?php echo $PROCESSED["report_finish"]; ?>" />
                <table class="tableList" id="evaluations" cellspacing="0" cellpadding="1" summary="List of Course Evaluations">
                    <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="date" />
                        <col class="date" />
                        <col class="general" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td class="modified"></td>
                            <td class="title">Evaluation Title</td>
                            <td class="date">Start Date</td>
                            <td class="date">Finish Date</td>
                            <td class="general">Responses</td>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div class="row-fluid space-above">
                    <a href="<?php echo html_encode(ENTRADA_URL); ?>/admin/evaluations/reports" class="btn">Cancel</a>

                    <input type="submit" value="Generate Report" class="btn btn-primary pull-right space-left" />
                </div>
            </form>
            <?php
        break;
        case 1 :
        default :
           $query = "SELECT *, a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a
                        JOIN `".AUTH_DATABASE."`.`user_access` AS b
                        ON a.`id` = b.`user_id`
                        WHERE b.`group` = 'student'
                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
            $students = $db->GetAll($query);
            if ($students) {
                $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
                $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\"></script>\n";
                $ONLOAD[] = "jQuery('.chosen-select').chosen({no_results_text: 'No students found matching'})";
                ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        $(".datepicker").datepicker({
                            dateFormat: "yy-mm-dd"
                        });
                        $(".add-on").on("click", function() {
                            if ($(this).siblings("input").is(":enabled")) {
                                $(this).siblings("input").focus();
                            }
                        });
                    });
                </script>
                <h2>Select a Learner and a Time Period:</h2>
                <form action="<?php echo html_encode(ENTRADA_URL); ?>/admin/evaluations/reports?section=learner-evaluations&step=2" method="POST" id="evaluation-report-form">
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Learner</label>
                        <span class="controls span8">
                            <?php 
                            echo "<select data-placeholder=\"Choose a learner...\"  name=\"proxy_id\" id=\"proxy_id\" class=\"chosen-select\">";
                            foreach ($students as $student) {
                                echo "<option value=\"".((int)$student["proxy_id"])."\"".(isset($PROCESSED["proxy_id"]) && $student["proxy_id"] == $PROCESSED["proxy_id"] ? " selected=\"selected\"" : "").">".html_encode(get_account_data("fullname", $student["proxy_id"]))."</option>";
                            }
                            echo "</select>";
                            ?>
                            </select>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Report Start: </label>
                        <span class="controls span8">
                            <div class="input-append">
                                <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED["report_start"]) && $PROCESSED["report_start"] ? date("Y-m-d", $PROCESSED["report_start"]) : ""); ?>" name="report_start" id="report_start" />
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </span>
                    </div>
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Report Finish: </label>
                        <span class="controls span8">
                            <div class="input-append">
                                <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED["report_finish"]) && $PROCESSED["report_finish"] ? date("Y-m-d", $PROCESSED["report_finish"]) : ""); ?>" name="report_finish" id="report_finish" />
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </span>
                    </div>
                    <div class="row-fluid space-above">
                        <a href="<?php echo html_encode(ENTRADA_URL); ?>/admin/evaluations/reports" class="btn">Cancel</a>

                        <input type="submit" value="Proceed" class="btn btn-primary pull-right space-left" />
                    </div>
                </form>
                <?php
            } else {
                echo display_error("There were no students found in the system for this organisation. Please change your active organisation from the \"My Organisations\" sidebar on the left, or if you believe you should be able to see students from this organisation, contact a System Administrator.");
            }
        break;
    }
}
?>
