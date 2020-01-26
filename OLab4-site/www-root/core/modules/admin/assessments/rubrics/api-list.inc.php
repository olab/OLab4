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
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_RUBRICS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();

    if (!$ERROR) {
        $aColumns = Models_Assessments_Rubric::getDisplayColumns();
        $sort_column = (isset($_GET["iSortCol_0"]) && isset($aColumns[((int)$_GET["iSortCol_0"])]) ? $aColumns[((int)$_GET["iSortCol_0"])] : $aColumns[1]);
        $search_value = (isset($_GET["sSearch"]) && $_GET["sSearch"] ? $_GET["sSearch"] : false);
        $sort_direction = (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? "DESC" : "ASC");

        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1") {
            $start = (int)$_GET["iDisplayStart"];
            $limit = (int)$_GET["iDisplayLength"];
        } else {
            $start = 0;
            $limit = 25;
        }

        $rubrics = Models_Assessments_Rubric::fetchAllFiltered($search_value, $start, $limit, $sort_direction, $sort_column);
        $total_rubrics = Models_Assessments_Rubric::fetchAllRecords();

        $output = array("aaData" => array());
        $count = 0;
        if ($rubrics) {
            $PROCESSED["rubric_items"] = array();
            foreach ($rubrics as $rubric) {
                //Get and count the number of Item responses per Item, this is the rubric width.
                $rubric_width = 0;
                $rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($rubric["rubric_id"]);
                //Assume that the width is constant, so we can grab any item and use it's width as the overall width.
                if ($rubric_items) {
                    $items = Models_Assessments_Item_Response::fetchAllRecordsByItemID($rubric_items[0]->getItemID());
                    if ($items) {
                        $rubric_width = count($items);
                    }
                }

                $url = ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id=".$rubric["rubric_id"].(isset($rubric_width) ? "&rubric_width=".$rubric_width : "");

                $row = array();
                $row["modified"] = "<input type=\"checkbox\" name=\"delete[]\" value=\"" . html_encode($rubric["rubric_id"]) . "\" />";
                $row["rubric_title"] = "<a href=\"" . $url . "\">" . html_encode($rubric["rubric_title"]) . "</a>";
                $row["rubric_description"] = "<a href=\"" . $url . "\">" . ($rubric["rubric_description"] ? html_encode($rubric["rubric_description"]) : $translate->_("N/A")) . "</a>";
                $output["aaData"][] = $row;
                $count++;
            }
        }
        $output["iTotalRecords"] = count($rubrics);
        $output["iTotalDisplayRecords"] = ($search_value ? count($rubrics) : count($total_rubrics));
        $output["sEcho"] = clean_input($_GET["sEcho"], "int");

        if ($output && count($output)) {
            echo json_encode($output);
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $translate->_("An error has occurred and we were unable to load any Grouped Items.")));
    }
    exit;
}