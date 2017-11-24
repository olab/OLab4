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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: Undergraduate Medical Education
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
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/evaluations/reports?section=student-clerkship-evalutions", "title" => "Clerkship Core Rotation Evaluation Reports");
	switch ($STEP) {
        case 3 :
            $report_questions = array();
            $report_targets = array();

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

            if (isset($_POST["cohorts"]) && @count($_POST["cohorts"])) {
                $PROCESSED["cohorts"] = array();
                $PROCESSED["cohorts_string"] = "";
                $PROCESSED["category_ids"] = array();
                foreach ($_POST["cohorts"] as $cohort) {
                    $PROCESSED["cohorts"][] = ((int)$cohort);
                    $PROCESSED["cohorts_string"] .= ($PROCESSED["cohorts_string"] ? ", " : "").$db->qstr($cohort);
                    $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `group_id` = ".$db->qstr($cohort);
                    $category_id = $db->GetOne($query);
                    if ($category_id) {
                        $PROCESSED["category_ids"] = getCategoryChildren($category_id, $PROCESSED["category_ids"]);
                    }
                }
            } else {
                add_error("The <strong>Cohorts</strong> are required. Please choose at least one cohort to report on to continue.");
            }
            if (isset($_POST["checked"]) && is_array($_POST["checked"])) {
                foreach ($_POST["checked"] as $event_id) {
                    $query = "SELECT a.`event_title`, a.`rotation_id`, a.`region_id`, c.`region_name`, a.`category_id`, a.`event_id` FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                JOIN `evaluation_progress_clerkship_events` AS b
                                ON a.`event_id` = b.`event_id`
                                AND b.`preceptor_proxy_id` IS NULL
                                JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
                                ON a.`region_id` = c.`region_id`
                                JOIN `evaluation_progress` AS d
                                ON b.`eprogress_id` = d.`eprogress_id`
                                JOIN `".CLERKSHIP_DATABASE."`.`categories` AS e
                                ON a.`category_id` = e.`category_id`
                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS f
                                ON e.`category_parent` = f.`category_id`
                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS g
                                ON f.`category_parent` = g.`category_id`
                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS h
                                ON g.`category_parent` = h.`category_id`
                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS i
                                ON h.`category_parent` = i.`category_id`
                                WHERE a.`event_id` = ".$db->qstr(((int)$event_id))."
                                AND (
                                        (i.`category_id` IS NOT NULL
                                        AND i.`group_id` IS NOT NULL
                                        AND i.`group_id` != 0
                                        AND i.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        OR
                                        (h.`category_id` IS NOT NULL
                                        AND h.`group_id` IS NOT NULL
                                        AND h.`group_id` != 0
                                        AND h.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        OR
                                        (g.`category_id` IS NOT NULL
                                        AND g.`group_id` IS NOT NULL
                                        AND g.`group_id` != 0
                                        AND g.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        OR
                                        (f.`category_id` IS NOT NULL
                                        AND f.`group_id` IS NOT NULL
                                        AND f.`group_id` != 0
                                        AND f.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        OR
                                        (e.`category_id` IS NOT NULL
                                        AND e.`group_id` IS NOT NULL
                                        AND e.`group_id` != 0
                                        AND e.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                )";
                    $event = $db->getRow($query);
                    if ($event) {
                        if (in_array($event["category_id"], $PROCESSED["category_ids"])) {
                            $event_array = array("event_id" => $event["event_id"], "event_title" => $event["event_title"], "rotation_id" => $event["rotation_id"], "region_id" => $event["region_id"], "region_name" => $event["region_name"]);
                            if (!in_array($event_array, $report_targets)) {
                                $report_targets[] = $event_array;
                            }
                        }
                    }
                }
            }
            if ($report_targets) {
                $report_targets_responses_count = array();
                foreach ($report_targets as $report_target) {
                    $query = "SELECT e.`eform_id`, a.`category_id` FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                JOIN `evaluation_progress_clerkship_events` AS b
                                ON a.`event_id` = b.`event_id`
                                AND b.`preceptor_proxy_id` IS NULL
                                JOIN `evaluation_progress` AS c
                                ON b.`eprogress_id` = c.`eprogress_id`
                                JOIN `evaluations` AS d
                                ON c.`evaluation_id` = d.`evaluation_id`
                                JOIN `evaluation_forms` AS e
                                ON d.`eform_id` = e.`eform_id`
                                WHERE a.`event_id` = ".$db->qstr($report_target["event_id"])."
                                GROUP BY e.`eform_id`";
                    $form_query = $query;
                    $forms = $db->GetAll($query);
                    if ($forms) {
                        foreach ($forms as $form) {
                            if (in_array($form["category_id"], $PROCESSED["category_ids"])) {
                                $query = "SELECT a.*, b.`efquestion_id`, b.`eform_id` FROM `evaluations_lu_questions` AS a
                                            JOIN `evaluation_form_questions` AS b
                                            ON a.`equestion_id` = b.`equestion_id`
                                            WHERE b.`eform_id` = ".$db->qstr($form["eform_id"])."
                                            ORDER BY b.`question_order` ASC";
                                $temp_questions = $db->GetAll($query);
                                if ($temp_questions) {
                                    foreach ($temp_questions as $temp_question) {
                                        if (!array_key_exists($temp_question["equestion_id"], $report_questions)) {
                                            $query = "SELECT * FROM `evaluations_lu_question_responses`
                                                        WHERE `equestion_id` = ".$db->qstr($temp_question["equestion_id"])."
                                                        ORDER BY `response_order`";
                                            $temp_question["responses"] = array();
                                            $responses = $db->GetAll($query);
                                            if ($responses) {
                                                foreach ($responses as $response) {
                                                    $temp_question["responses"][$response["eqresponse_id"]] = $response;
                                                }
                                                $temp_question["efquestion_ids_string"] =  $db->qstr($temp_question["efquestion_id"]);
                                                $temp_question["efquestion_ids"] =  array($temp_question["eform_id"] => $temp_question["efquestion_id"]);
                                                $report_questions[$temp_question["equestion_id"]] = $temp_question;
                                            }
                                        } else {
                                            $report_questions[$temp_question["equestion_id"]]["efquestion_ids_string"] .=  ",".$db->qstr($temp_question["efquestion_id"]);
                                            $report_questions[$temp_question["equestion_id"]]["efquestion_ids"][$temp_question["eform_id"]] =  $temp_question["efquestion_id"];
                                        }
                                    }
                                }
                            }
                        }
                        if (@count($report_questions)) {
                            foreach ($report_questions as &$report_question) {
                                $query = "SELECT c.*, a.`category_id` FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                            JOIN `evaluation_progress_clerkship_events` AS b
                                            ON a.`event_id` = b.`event_id`
                                            AND b.`preceptor_proxy_id` IS NULL
                                            JOIN `evaluation_responses` AS c
                                            ON c.`eprogress_id` = b.`eprogress_id`
                                            JOIN `".CLERKSHIP_DATABASE."`.`categories` AS d
                                            ON a.`category_id` = d.`category_id`
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS e
                                            ON d.`category_parent` = e.`category_id`
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS f
                                            ON e.`category_parent` = f.`category_id`
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS g
                                            ON f.`category_parent` = g.`category_id`
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS h
                                            ON g.`category_parent` = h.`category_id`
                                            WHERE c.`efquestion_id` IN (".$report_question["efquestion_ids_string"].")
                                            AND a.`event_title` = ".$db->qstr($report_target["event_title"])."
                                            AND a.`rotation_id` = ".$db->qstr($report_target["rotation_id"])."
                                            AND a.`region_id` = ".$db->qstr($report_target["region_id"])."
                                            AND a.`event_finish` >= ".$db->qstr($PROCESSED["report_start"])."
                                            AND a.`event_finish` <= ".$db->qstr($PROCESSED["report_finish"])."
                                            AND (
                                                    (h.`category_id` IS NOT NULL
                                                    AND h.`group_id` IS NOT NULL
                                                    AND h.`group_id` != 0
                                                    AND h.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                    OR
                                                    (g.`category_id` IS NOT NULL
                                                    AND g.`group_id` IS NOT NULL
                                                    AND g.`group_id` != 0
                                                    AND g.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                    OR
                                                    (f.`category_id` IS NOT NULL
                                                    AND f.`group_id` IS NOT NULL
                                                    AND f.`group_id` != 0
                                                    AND f.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                    OR
                                                    (e.`category_id` IS NOT NULL
                                                    AND e.`group_id` IS NOT NULL
                                                    AND e.`group_id` != 0
                                                    AND e.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                    OR
                                                    (d.`category_id` IS NOT NULL
                                                    AND d.`group_id` IS NOT NULL
                                                    AND d.`group_id` != 0
                                                    AND d.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                            )";
                                $chosen_responses = $db->GetAll($query);
                                if ($chosen_responses) {
                                    if (!array_key_exists($report_target["event_id"], $report_targets_responses_count)) {
                                        $report_targets_responses_count[$report_target["event_id"]] = count($chosen_responses);
                                    }
                                    foreach ($chosen_responses as $chosen_response) {
                                        if (in_array($chosen_response["category_id"], $PROCESSED["category_ids"])) {
                                            if (!array_key_exists("chosen", $report_question["responses"][$chosen_response["eqresponse_id"]])) {
                                                $report_question["responses"][$chosen_response["eqresponse_id"]]["chosen"] = 0;
                                                $report_question["responses"][$chosen_response["eqresponse_id"]]["comments"] = array();
                                            }
                                            if (!array_key_exists("total_responses", $report_question)) {
                                                $report_question["total_responses"] = 0;
                                            }
                                            $report_question["responses"][$chosen_response["eqresponse_id"]]["chosen"]++;
                                            if (isset($chosen_response["comments"]) && $chosen_response["comments"]) {
                                                $report_question["responses"][$chosen_response["eqresponse_id"]]["comments"][] = $chosen_response["comments"];
                                            }
                                            $report_question["total_responses"]++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!$report_questions) {
                $STEP = 2;
                if (!has_error()) {
                    add_error("No evaluation data for the chosen services was found during the selected period.");
                }
            }
        break;
        case 2 :
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

            if (isset($_POST["cohorts"]) && @count($_POST["cohorts"])) {
                $PROCESSED["cohorts"] = array();
                $PROCESSED["cohorts_string"] = "";
                $PROCESSED["category_ids"] = array();
                foreach ($_POST["cohorts"] as $cohort) {
                    $PROCESSED["cohorts"][] = ((int)$cohort);
                    $PROCESSED["cohorts_string"] .= ($PROCESSED["cohorts_string"] ? ", " : "").$db->qstr($cohort);
                    $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `group_id` = ".$db->qstr($cohort);
                    $category_id = $db->GetOne($query);
                    if ($category_id) {
                        $PROCESSED["category_ids"] = getCategoryChildren($category_id, $PROCESSED["category_ids"]);
                    }
                }
            } else {
                add_error("The <strong>Cohorts</strong> are required. Please choose at least one cohort to report on to continue.");
            }

            if (has_error()) {
                $STEP = 1;
            }
        break;
    }
    switch ($STEP) {
        case 3 :
            if (!isset($_POST["export"])) {
                $targets_string = "";
                $total_events = 0;
                $total_completed = 0;
                foreach ($report_targets as $report_target) {
                    $query = "SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_id` = ".$db->qstr($report_target["rotation_id"]);
                    $rotation_title = $db->GetOne($query);
                    $query = "SELECT COUNT(a.`event_id`) FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                        JOIN `".CLERKSHIP_DATABASE."`.`categories` AS b
                                        ON a.`category_id` = b.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS c
                                        ON b.`category_parent` = c.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS d
                                        ON c.`category_parent` = d.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS e
                                        ON d.`category_parent` = e.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS f
                                        ON e.`category_parent` = f.`category_id`
                                        WHERE `event_finish` >= ".$db->qstr($PROCESSED["report_start"])."
                                        AND a.`event_finish` <= ".$db->qstr($PROCESSED["report_finish"])."
                                        AND a.`event_title` = ".$db->qstr($report_target["event_title"])."
                                        AND a.`region_id` = ".$db->qstr($report_target["region_id"])."
                                        AND a.`rotation_id` = ".$db->qstr($report_target["rotation_id"])."
                                        AND (
                                                (f.`category_id` IS NOT NULL
                                                AND f.`group_id` IS NOT NULL
                                                AND f.`group_id` != 0
                                                AND f.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (e.`category_id` IS NOT NULL
                                                AND e.`group_id` IS NOT NULL
                                                AND e.`group_id` != 0
                                                AND e.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (d.`category_id` IS NOT NULL
                                                AND d.`group_id` IS NOT NULL
                                                AND d.`group_id` != 0
                                                AND d.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (c.`category_id` IS NOT NULL
                                                AND c.`group_id` IS NOT NULL
                                                AND c.`group_id` != 0
                                                AND c.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (b.`category_id` IS NOT NULL
                                                AND b.`group_id` IS NOT NULL
                                                AND b.`group_id` != 0
                                                AND b.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        )";
                    $count = $db->GetOne($query);
                    $total_events += $count;
                    $total_completed += (array_key_exists($report_target["event_id"], $report_targets_responses_count) && ((int)$report_targets_responses_count[$report_target["event_id"]]) ? ((int)$report_targets_responses_count[$report_target["event_id"]]) : 0);
                    $targets_string .= "<strong>".html_encode($report_target["event_title"])."</strong> (<em>".html_encode($rotation_title)."</em> - <em>".html_encode($report_target["region_name"])."</em>) [<strong>".(array_key_exists($report_target["event_id"], $report_targets_responses_count) && ((int)$report_targets_responses_count[$report_target["event_id"]]) ? ((int)$report_targets_responses_count[$report_target["event_id"]]) : "0")."</strong> evaluations completed for <strong>".$count."</strong> events]<br />\n";
                }
                $targets_string .= "<br /><br />Total of <strong>".$total_completed."</strong> evaluations completed for <strong>".$total_events."</strong> events.";
                echo "<h1>Clerkship Core Rotation Evaluation Reports</h1>\n";
                echo "<div id=\"evaluation-report-body\">\n";
                echo "<div class=\"row-fluid\"><h2 class=\"span4\">Services reported on:</h2></div>\n";
                echo "<div class=\"row-fluid space-below\"><span class=\"span9 offset3 question-content\">".$targets_string."</span></div>\n";
                $count = 1;
                foreach ($report_questions as &$report_question) {
                    echo "<div class=\"row-fluid space-below".($count > 1 ? " border-above" : "")."\">\n";
                    echo "  <h3 class=\"span3\">Question #".$count.": </h3>\n";
                    echo "  <span class=\"span7 offset1 space-above medium question-content\">".$report_question["question_text"]."</span>\n";
                    echo "</div>\n";
                    echo "  <div class=\"row-fluid border-bottom space-below question-content\">\n";
                    echo "      <span class=\"span7\"><strong>Response</strong></span>\n";
                    echo "      <span class=\"span2\"><strong>Percent</strong></span>\n";
                    echo "      <span class=\"span2 offset1\"><strong>Selections</strong></span>\n";
                    echo "  </div>\n";
                    foreach ($report_question["responses"] as $response) {
                        echo "  <div class=\"row-fluid question-content\">\n";
                        echo "      <span class=\"span7\">".$response["response_text"]."</span>\n";
                        echo "      <span class=\"span2\">".(isset($response["chosen"]) && $response["chosen"] ? round((((float)$response["chosen"]) / ((float)$report_question["total_responses"]) * 100.0), 2) : "0")."% </span>\n";
                        echo "      <span class=\"span2 offset1\">".(isset($response["chosen"]) && $response["chosen"] ? $response["chosen"] : "0")."</span>\n";
                        echo "  </div>\n";
                    }
                    echo "	<div class=\"row-fluid comments-row\" style=\"padding-top: 20px;\">&nbsp;</div>\n";
                    echo "	<div class=\"row-fluid comments-row\">\n";
                    echo "  <ul>\n";
                    foreach ($report_question["responses"] as $response) {
                        if (isset($response["comments"]) && @count($response["comments"])) {
                            echo "      <li>".$response["response_text"].":\n";
                            echo "          <ul>\n";
                            foreach ($response["comments"] as $comment) {
                                echo "              <li>".$comment."</li>\n";
                            }
                            echo "          </ul>\n";
                            echo "      </li>\n";
                        }
                    }
                    echo "	</ul>\n";
                    echo "</div>\n";
                    $count++;
                }
                echo "</div>\n";
            }
            break;
        case 2 :
            echo "<h1>Clerkship Core Rotation Evaluation Reports</h1>\n";
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }
            $category_ids_string = "";
            foreach ($PROCESSED["category_ids"] as $category_id) {
                $category_ids_string .= ($category_ids_string ? ", " : "").$db->qstr($category_id);
            }
            $query = "SELECT a.`event_id`, b.`rotation_title`, a.`event_title`, d.`region_name`, d.`region_id`, a.`rotation_id`, COUNT(DISTINCT c.`eprogress_id`) AS `core_evaluations`, a.`category_id`
                        FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                        JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS b
                        ON a.`rotation_id` = b.`rotation_id`
                        JOIN `evaluation_progress_clerkship_events` AS c
                        ON a.`event_id` = c.`event_id`
                        AND c.`preceptor_proxy_id` IS NULL
                        JOIN `evaluation_progress` AS c1
                        ON c.`eprogress_id` = c1.`eprogress_id`
                        AND c1.`progress_value` = 'complete'
                        JOIN `".CLERKSHIP_DATABASE."`.`regions` AS d
                        ON a.`region_id` = d.`region_id`
                        JOIN `evaluation_evaluators` AS e
                        ON c1.`evaluation_id` = e.`evaluation_id`
                        AND e.`evaluator_type` = 'cohort'
                        JOIN `".CLERKSHIP_DATABASE."`.`categories` AS f
                        ON a.`category_id` = f.`category_id`
                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS g
                        ON f.`category_parent` = g.`category_id`
                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS h
                        ON g.`category_parent` = h.`category_id`
                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS i
                        ON h.`category_parent` = i.`category_id`
                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS j
                        ON i.`category_parent` = j.`category_id`
                        WHERE a.`event_finish` >= ".$db->qstr($PROCESSED["report_start"])."
                        AND a.`event_finish` <= ".$db->qstr($PROCESSED["report_finish"])."
                        AND a.`rotation_id` != 10
                        AND (
                                (j.`category_id` IS NOT NULL
                                AND j.`group_id` IS NOT NULL
                                AND j.`group_id` != 0
                                AND j.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                OR
                                (i.`category_id` IS NOT NULL
                                AND i.`group_id` IS NOT NULL
                                AND i.`group_id` != 0
                                AND i.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                OR
                                (h.`category_id` IS NOT NULL
                                AND h.`group_id` IS NOT NULL
                                AND h.`group_id` != 0
                                AND h.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                OR
                                (g.`category_id` IS NOT NULL
                                AND g.`group_id` IS NOT NULL
                                AND g.`group_id` != 0
                                AND g.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                OR
                                (f.`category_id` IS NOT NULL
                                AND f.`group_id` IS NOT NULL
                                AND f.`group_id` != 0
                                AND f.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                        )
                        GROUP BY a.`event_title`, a.`region_id`, a.`rotation_id`
                        ORDER BY b.`rotation_id`, a.`event_title`";
            $temp_services = $db->GetAll($query);
            $services = array();
            if ($temp_services) {
                foreach ($temp_services as $temp_service) {
                    $services[] = $temp_service;
                }
            }
            ?>
            <form name="frmReport" action="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-clerkship-evaluations&step=3" method="post">
                <?php
                foreach ($PROCESSED["cohorts"] as $cohort) {
                    echo "<input type=\"hidden\" name=\"cohorts[]\" value=\"".$cohort."\" />\n";
                }
                ?>
                <script type="text/javascript">
                    function checkAll(element, rotation_id) {
                        jQuery('.rotation_' + rotation_id + '_checkbox').attr("checked", jQuery(element).is(":checked"));
                    }
                </script>
                <input type="hidden" name="report_start" value="<?php echo $report_start; ?>" />
                <input type="hidden" name="report_finish" value="<?php echo $report_finish; ?>" />
                <table class="tableList" cellspacing="0" cellpadding="1" summary="List of Evaluated Services">
                    <colgroup>
                        <col class="modified" />
                        <col />
                        <col class="title" />
                        <col />
                        <col class="submitted" />
                    </colgroup>
                    <thead>
                    <tr>
                        <td class="modified">&nbsp;</td>
                        <td width="15%">Rotation Name</td>
                        <td class="title">Service Name</td>
                        <td width="13%">Region</td>
                        <td width="8%" class="text-right">Events</td>
                        <td class="submitted text-right">Completed</td>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="4" style="text-align: right; padding-top: 15px">
                            <input type="submit" class="btn btn-primary" value="Create Report" />
                        </td>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    if ($services) {
                        $last_rotation_id = 0;
                        foreach ($services as $service) {
                            if ($service["rotation_id"] != $last_rotation_id) {
                                $last_rotation_id = $service["rotation_id"];

                                echo "<tr class=\"odd\">\n";
                                echo "	<td class=\"modified\"><input type=\"checkbox\" onclick=\"checkAll(this, ".$last_rotation_id.")\" /></td>\n";
                                echo "	<td class=\"title\" colspan=\"2\"><strong>Select all <em>".html_encode($service["rotation_title"])."</em><strong></td>\n";
                                echo "	<td class=\"general\">&nbsp;</td>\n";
                                echo "	<td>&nbsp;</td>\n";
                                echo "	<td class=\"submitted\">&nbsp;</td>\n";
                                echo "</tr>\n";
                            }
                            $query = "SELECT COUNT(a.`event_id`) FROM `".CLERKSHIP_DATABASE."`.`events` AS a
                                        JOIN `".CLERKSHIP_DATABASE."`.`categories` AS b
                                        ON a.`category_id` = b.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS c
                                        ON b.`category_parent` = c.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS d
                                        ON c.`category_parent` = d.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS e
                                        ON d.`category_parent` = e.`category_id`
                                        LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS f
                                        ON e.`category_parent` = f.`category_id`
                                        WHERE `event_finish` >= ".$db->qstr($PROCESSED["report_start"])."
                                        AND a.`event_finish` <= ".$db->qstr($PROCESSED["report_finish"])."
                                        AND a.`event_title` = ".$db->qstr($service["event_title"])."
                                        AND a.`region_id` = ".$db->qstr($service["region_id"])."
                                        AND a.`rotation_id` = ".$db->qstr($service["rotation_id"])."
                                        AND (
                                                (f.`category_id` IS NOT NULL
                                                AND f.`group_id` IS NOT NULL
                                                AND f.`group_id` != 0
                                                AND f.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (e.`category_id` IS NOT NULL
                                                AND e.`group_id` IS NOT NULL
                                                AND e.`group_id` != 0
                                                AND e.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (d.`category_id` IS NOT NULL
                                                AND d.`group_id` IS NOT NULL
                                                AND d.`group_id` != 0
                                                AND d.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (c.`category_id` IS NOT NULL
                                                AND c.`group_id` IS NOT NULL
                                                AND c.`group_id` != 0
                                                AND c.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                                OR
                                                (b.`category_id` IS NOT NULL
                                                AND b.`group_id` IS NOT NULL
                                                AND b.`group_id` != 0
                                                AND b.`group_id` IN (".$PROCESSED["cohorts_string"]."))
                                        )";
                            $count = $db->GetOne($query);
                            echo "<tr>\n";
                            echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$service["event_id"]."\" class=\"rotation_".$last_rotation_id."_checkbox\" /></td>\n";
                            echo "	<td>".html_encode($service["rotation_title"])."</td>\n";
                            echo "	<td class=\"title\">".html_encode($service["event_title"])."</td>\n";
                            echo "	<td class=\"general\">".html_encode($service["region_name"])."</td>\n";
                            echo "	<td class=\"submitted text-right\">".html_encode(((int)$count ? (int)$count : "0"))."</td>\n";
                            echo "	<td class=\"submitted text-right\">".html_encode($service["core_evaluations"])."</td>\n";
                            echo "</tr>\n";
                        }
                    } else {
                        ?>
                        <td>&nbsp;</td>
                        <td colspan="4" style="padding-top: 15px">
                            <?php
                            echo display_notice("No completed clerkship events were found.");
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
            echo "<h1>Clerkship Core Rotation Evaluation Reports</h1>\n";
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
                </script>
                <form name="frmReport" action="<?php echo ENTRADA_URL; ?>/admin/evaluations/reports?section=student-clerkship-evaluations&step=2" method="post">
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
                    <div class="control-group row-fluid">
                        <label for="course_id" class="form-required span3">Cohorts: </label>
                        <span class="controls span8">
                            <div class="input-append">
                                <select multiple="multiple" name="cohorts[]" id="cohorts" style="height: 160px">
                                    <?php
                                    $query = "SELECT * FROM `groups` AS a
                                                JOIN `group_organisations` AS b
                                                ON a.`group_id` = b.`group_id`
                                                WHERE a.`group_type` = 'cohort'
                                                AND a.`group_active` = 1
                                                AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
                                    $cohorts = $db->GetAll($query);
                                    if ($cohorts) {
                                        foreach ($cohorts as $cohort) {
                                            echo "<option value=\"".$cohort["group_id"]."\"".(isset($PROCESSED["cohorts"]) && in_array($cohort["group_id"], $PROCESSED["cohorts"]) ? " selected=\"selected\"" : "").">".html_encode($cohort["group_name"])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </span>
                    </div>
                    <input type="submit" class="btn btn-primary pull-right" value="Show Services Available to Report On" />
                </form>
            <?php
            break;
    }
}

function getCategoryChildren ($category_id, $category_ids_array = array(), $level = 0) {
    global $db;

    $level++;
    if ($level > 99) {
        return $category_ids_array;
    }

    $query = "SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_parent` = ".$db->qstr($category_id);
    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            $category_ids_array[] = $result["category_id"];
            $category_ids_array = getCategoryChildren($result["category_id"], $category_ids_array, $level);
        }
    }

    return $category_ids_array;
}