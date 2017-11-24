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
 * This file outputs the list of evaluations pulled
 * from the entrada.evaluations table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('evaluation', 'read')) {
    exit;
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    
    if (isset($_GET["proxy_id"]) && clean_input($_GET["proxy_id"], "int")) {
        $proxy_id = $_GET["proxy_id"];
    }
    
    if (isset($_GET["report_start"]) && clean_input($_GET["report_start"], "int")) {
        $report_start = $_GET["report_start"];
    }
    
    if (isset($_GET["report_finish"]) && clean_input($_GET["report_finish"], "int")) {
        $report_finish = $_GET["report_finish"];
    }
    
    if (isset($_GET["evaluation_ids"]) && is_array($_GET["evaluation_ids"]) && (@count($_GET["evaluation_ids"]))) {
        $evaluation_ids = $_GET["evaluation_ids"];
    } else {
        $evaluation_ids = array();
    }
    
    $output = array("aaData" => array());
    if (isset($proxy_id) && $proxy_id && isset($report_start) && $report_start && isset($report_finish) && $report_finish) {
        $query = "SELECT a.`evaluation_id`, a.`evaluation_title`, a.`evaluation_start`, a.`evaluation_finish`, COUNT(DISTINCT d.`proxy_id`) AS `completions` 
                    FROM `evaluations` AS a
                    JOIN `evaluation_forms` AS b
                    ON a.`eform_id` = b.`eform_id`
                    JOIN `evaluations_lu_targets` AS c
                    ON b.`target_id` = c.`target_id`
                    JOIN `evaluation_progress` AS d
                    ON a.`evaluation_id` = d.`evaluation_id`
                    AND d.`progress_value` = 'complete'
                    AND d.`target_record_id` = ".$db->qstr($proxy_id)."
                    AND d.`updated_date` BETWEEN ".$db->qstr($report_start)." AND ".$db->qstr($report_finish)."
                    WHERE a.`evaluation_active` = 1
                    AND c.`target_shortname` IN ('self', 'resident', 'teacher', 'student', 'peer')
                    GROUP BY a.`evaluation_id`";
        $evaluations = $db->getAll($query);
        $count = 0;
        if ($evaluations) {
            /*
             * Ordering
             */
            if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3, 4))) {
                $aColumns = array("evaluation_id", "evaluation_title", "evaluation_start", "evaluation_finish", "completions");
                $sort_array = array();
                foreach ($evaluations as $evaluation) {
                    $checked_array[] = (in_array($evaluation["evaluation_id"], $evaluation_ids) ? 1 : 0);
                    $sort_array[] = $evaluation[$aColumns[$_GET["iSortCol_0"]]];
                }
                array_multisort($checked_array, SORT_DESC, SORT_NUMERIC, $sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), ($_GET["iSortCol_0"] == 1 ? SORT_STRING : SORT_NUMERIC), $evaluations);
            }
            if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
                $start = (int)$_GET["iDisplayStart"];
                $limit = (int)$_GET["iDisplayLength"];
            } else {
                $start = 0;
                $limit = count($entries) - 1;
            }
            if ($_GET["sSearch"] != "") {
                $search_value = $_GET["sSearch"];
            }
            foreach ($evaluations as $evaluation) {
                if ($evaluation_ids && in_array($evaluation["evaluation_id"], $evaluation_ids)) {
                    $url = ENTRADA_URL . "/admin/evaluations?section=edit&evaluation_id=".html_encode($evaluation["evaluation_id"]);
                    if ($evaluation_ids && in_array($evaluation["evaluation_id"], $evaluation_ids)) {
                        $row = array();
                        $row["checkbox"] = "<input class=\"evaluation_ids\" type=\"checkbox\" name=\"evaluation_ids[]\" value=\"".html_encode($evaluation["evaluation_id"])."\" ".(($evaluation_ids && in_array($evaluation["evaluation_id"], $evaluation_ids)) ? "checked=\"checked\" " : "")."/>";
                        $row["evaluation_title"] = "<a href=\"".$url."\">".html_encode($evaluation["evaluation_title"])."</a>";
                        $row["evaluation_start"] = "<a href=\"".$url."\">".html_encode(date("F jS, Y", $evaluation["evaluation_start"]))."</a>";
                        $row["evaluation_finish"] = "<a href=\"".$url."\">".html_encode(date("F jS, Y", $evaluation["evaluation_finish"]))."</a>";
                        $row["completions"] = "<a href=\"".$url."\">".html_encode($evaluation["completions"])."</a>";
                        $output["aaData"][] = $row;
                    }
                }
            }
            foreach ($evaluations as $evaluation) {
                if ((!$evaluation_ids || !in_array($evaluation["evaluation_id"], $evaluation_ids)) && (!isset($search_value) || stripos($evaluation["evaluation_title"], $search_value) !== false || stripos(date("F jS, Y", $evaluation["evaluation_start"]), $search_value) !== false || stripos(date("F jS, Y", $evaluation["evaluation_finish"]), $search_value) !== false || stripos($evaluation["completions"], $search_value) !== false)) {
                    $url = ENTRADA_URL . "/admin/evaluations?section=edit&evaluation_id=".html_encode($evaluation["evaluation_id"]);
                    if ((!$evaluation_ids || !in_array($evaluation["evaluation_id"], $evaluation_ids)) && ($count >= $start && $count < ($start + $limit))) {
                        $row = array();
                        $row["checkbox"] = "<input class=\"evaluation_ids\" type=\"checkbox\" name=\"evaluation_ids[]\" value=\"".html_encode($evaluation["evaluation_id"])."\" />";
                        $row["evaluation_title"] = "<a href=\"".$url."\">".html_encode($evaluation["evaluation_title"])."</a>";
                        $row["evaluation_start"] = "<a href=\"".$url."\">".html_encode(date("F jS, Y", $evaluation["evaluation_start"]))."</a>";
                        $row["evaluation_finish"] = "<a href=\"".$url."\">".html_encode(date("F jS, Y", $evaluation["evaluation_finish"]))."</a>";
                        $row["completions"] = "<a href=\"".$url."\">".html_encode($evaluation["completions"])."</a>";
                        $output["aaData"][] = $row;
                    }
                    $count++;
                }
            }
        }
    }
    $output["iTotalRecords"] = (is_array($evaluations) ? @count($evaluations) : 0);
    $output["iTotalDisplayRecords"] = $count;
    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
    if ($output && count($output)) {
        echo json_encode($output);
    }
    exit;
}