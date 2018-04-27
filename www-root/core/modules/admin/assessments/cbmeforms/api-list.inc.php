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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_FORMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'update', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();
    
    if (!$ERROR) {
        $output             = array("aaData" => array());
        $columns_array      = array(1 => "title", 2 => "description");
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

        $forms = Models_Assessments_Form::fetchAllRecordsBySearchTerm($search_value, $start, $limit, $sort_direction, $sort_column);
        $total_items = Models_Assessments_Form::fetchAllRecords();
        $count = 0;
        if ($forms) {
            if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
                $start = (int)$_GET["iDisplayStart"];
                $limit = (int)$_GET["iDisplayLength"];
            } else {
                $start = 0;
                $limit = 25;
            }

            foreach ($forms as $form) {
                $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE ."?section=edit-form&id=" . $form["form_id"];

                $row = array();
                $row["modified"]    = "<input type=\"checkbox\" name=\"delete[]\" value=\"".html_encode($form["form_id"])."\" />";
                $row["form_title"]  = "<a href=\"".$url."\">".html_encode(strlen($form["title"]) < 37 ? $form["title"] : substr($form["title"], 0, 37) . "...")."</a>";
                $row["form_desc"]   = "<a href=\"".$url."\">".html_encode(strlen($form["description"]) < 33 ? $form["description"] : substr($form["description"], 0, 33) . "...")."</a>";
                $row["form_items"]  = "<a href=\"".$url."\">".($form["item_count"] ? html_encode(strlen($form["item_count"]) < 37 ? $form["item_count"] : substr($form["item_count"], 0, 37) . "...") : $translate->_("N/A"))."</a>";
                $output["aaData"][] = $row;
                $count++;
            }
        }

        $output["iTotalRecords"]            = count($forms);
        $output["iTotalDisplayRecords"]     = ($search_value ? count($forms) : count($total_items));
        $output["sEcho"]                    = clean_input($_GET["sEcho"], "int");

        if ($output && count($output)) {
            echo json_encode($output);
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}
?>