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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_RUBRICS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {

    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'update', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();

    if (isset($_GET["rubric_id"]) && $tmp_input = clean_input($_GET["rubric_id"], array("trim", "int"))) {
        $PROCESSED["rubric_id"] = $tmp_input;
    } else {
        add_error($translate->_("No Grouped Item has been specified."));
    }

    if (!$ERROR) {
        $output             = array("aaData" => array());
        $columns_array      = array(1 => "item_text", 2 => "name", 3 => "item_code", 4 => "responses");
        $sort_column        = (isset($_GET["iSortCol_0"]) && isset($columns_array[((int)$_GET["iSortCol_0"])]) ? $columns_array[((int)$_GET["iSortCol_0"])] : $columns_array[1]);
        $search_value       = (isset($_GET["sSearch"]) && $_GET["sSearch"] ? $_GET["sSearch"] : false);
        $sort_direction     = (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? "DESC" : "ASC");

        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
            $start = (int)$_GET["iDisplayStart"];
            $limit = (int)$_GET["iDisplayLength"];
        } else {
            $start = 0;
            $limit = 25;
        }

        $items = Models_Assessments_Item::fetchAllRecordsBySearchTermItemType($search_value, $start, $limit, $sort_direction, $sort_column);
        $total_items = Models_Assessments_Item::fetchAllRecords();

        $my_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($PROCESSED["rubric_id"]);
        $my_items = array();
        if ($my_rubric_items) {
            foreach($my_rubric_items as $item) {
                $my_items[] = $item->getItemID();
            }
        }
        $count = 0;

        if ($items) {
            if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
                $start = (int)$_GET["iDisplayStart"];
                $limit = (int)$_GET["iDisplayLength"];
            } else {
                $start = 0;
                $limit = 25;
            }

            foreach ($items as $item) {
                $url = ENTRADA_URL . "";
                $checked = (in_array($item["item_id"], $my_items) ? "CHECKED=CHECKED" : "");

                $row = array();
                $row["DT_RowClass"]     = "add-rubric-item-row";
                $row["modified"]        = "<input ".$checked." class=\"add-rubric-item\" type=\"checkbox\" name=\"add-rubric-item[]\" value=\"".html_encode($item["item_id"])."\" />";
                $row["item_text"]       = "<a href=\"".$url."\">".html_encode(strlen($item["item_text"]) < 37 ? $item["item_text"] : substr($item["item_text"], 0, 37) . "...")."</a>";
                $row["name"]            = "<a href=\"".$url."\">".html_encode(strlen($item["name"]) < 33 ? $item["name"] : substr($item["name"], 0, 33) . "...")."</a>";
                $row["item_code"]       = "<a href=\"".$url."\">".($item["item_code"] ? html_encode(strlen($item["item_code"]) < 37 ? $item["item_code"] : substr($item["item_code"], 0, 37) . "...") : $translate->_("N/A"))."</a>";
                $row["responses"]       = "<a href=\"".$url."\">".($item["responses"] ? html_encode($item["responses"]) : "N/A"). "</a>";
                $output["aaData"][]     = $row;
                $count++;
            }
        }

        $output["iTotalRecords"]            = count($items);
        $output["iTotalDisplayRecords"]     = ($search_value ? count($items) : count($total_items));
        $output["sEcho"]                    = clean_input($_GET["sEcho"], "int");

        if ($output && count($output)) {
            echo json_encode($output);
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR, "aaData" => array()));
    }
    exit;
}
?>