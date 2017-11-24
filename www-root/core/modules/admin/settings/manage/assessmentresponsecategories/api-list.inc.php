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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

ob_clear_open_buffers();

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT_RESPONSE_CATEGORIES"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    $output = array("aaData" => array());
    $descriptors =  Models_Assessments_Response_Descriptor::fetchAllByOrganisationID($ORGANISATION_ID);
    $count = 0;
    if ($descriptors) {
        /*
         * Ordering
         */
        if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3))) {
            $aColumns = array("lapplication_id", "descriptor");
            $sort_array = array();
            foreach ($descriptors as $descriptor) {
                switch ($_GET["iSortCol_0"]) {
                    case 1 :
                    default :
                        $sort_array[] = $descriptor->getDescriptor();
                        break;
                }
            }
            array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), SORT_STRING, $descriptors);
        }
        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
            $start = (int)$_GET["iDisplayStart"];
            $limit = (int)$_GET["iDisplayLength"];
        } else {
            $start = 0;
            $limit = count($descriptors) - 1;
        }
        if ($_GET["sSearch"] != "") {
            $search_value = $_GET["sSearch"];
        }
        foreach ($descriptors as $descriptor) {
            if (!isset($search_value) || stripos(($descriptor->getDescriptor()), $search_value) !== false) {
                $url = ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?section=edit&id=".$descriptor->getID()."&org=".$ORGANISATION_ID;
                if ($count >= $start && $count < ($start + $limit)) {
                    $row = array();
                    $row["checkbox"] = "<input type=\"checkbox\" name=\"checked[".html_encode($descriptor->getID())."]\" value=\"".html_encode($descriptor->getID())."\" />";
                    $row["descriptor"] = "<a href=\"".$url."\">".html_encode(($descriptor->getDescriptor()))."</a>";
                    $output["aaData"][] = $row;
                }
                $count++;
            }
        }
    }
    $output["iTotalRecords"] = (is_array($descriptors) ? @count($descriptors) : 0);
    $output["iTotalDisplayRecords"] = $count;
    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
    if ($output && count($output)) {
        echo json_encode($output);
    }
    exit;
}