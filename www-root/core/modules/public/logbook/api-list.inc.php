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
 * This file outputs the list of encounter tracking entries pulled
 * from the entrada.logbook_entries table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ENCOUNTER_TRACKING"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('encounter_tracking', 'read') && !$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
    exit;
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $student_viewing = true;
    if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], array("trim", "int"))) {
        $proxy_id = $tmp_input;
        $student_viewing = false;
    } else {
        $proxy_id = $ENTRADA_USER->getID();
    }
    
    $output = array("aaData" => array());
	$entries = Models_Logbook_Entry::fetchAll($proxy_id);
    $count = 0;
    if ($entries) {
        /*
         * Ordering
         */
        if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3, 4))) {
            $aColumns = array("lentry_id", "course", "encounter_date", "institution", "location");
            $sort_array = array();
            foreach ($entries as $entry) {
                switch ($_GET["iSortCol_0"]) {
                    default :
                    case 1 :
                        $course = $entry->getCourseName();
                    break;
                    case 2 :
                        $sort_array[] = $entry->getEncounterDate();
                    break;
                    case 3 :
                        $sort_array[] = $entry->getInstitution();
                    break;
                    case 4 :
                        $sort_array[] = $entry->getLocation();
                    break;
                }
            }
            array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), ($_GET["iSotCol_0"] != 2 ? SORT_STRING : SORT_NUMERIC), $entries);
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
        foreach ($entries as $entry) {
            if (!isset($search_value) || stripos($entry->getCourseName(), $search_value) !== false || stripos(date("F jS, Y", $entry->getEncounterDate()), $search_value) !== false || stripos($entry->getInstitution(), $search_value) !== false || stripos($entry->getLocation(), $search_value) !== false) {
                $url = ENTRADA_URL . "/logbook?section=edit&entry_id=".html_encode($entry->getID());
                if ($proxy_id && $proxy_id > 0) {
                    $url .= "&proxy_id=" . $proxy_id;
                }
                if ($count >= $start && $count < ($start + $limit)) {
                    $row = array();
                    $row["checkbox"]    = "<input class=\"delete\" type=\"checkbox\" name=\"delete[".html_encode($entry->getID())."]\" value=\"".html_encode($entry->getID())."\" />";
                    $row["course"]      = "<a href=\"".$url."\">".html_encode($entry->getCourseName())."</a>";
                    $row["date"]        = "<a href=\"".$url."\">".html_encode(date("F jS, Y", $entry->getEncounterDate()))."</a>";
                    $row["institution"] = "<a href=\"".$url."\">".html_encode($entry->getInstitution())."</a>";
                    $row["location"]    = "<a href=\"".$url."\">".html_encode($entry->getLocation())."</a>";
                    $output["aaData"][] = $row;
                }
                $count++;
            }
        }
    }
    $output["iTotalRecords"] = (is_array($entries) ? @count($entries) : 0);
    $output["iTotalDisplayRecords"] = $count;
    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
    if ($output && count($output)) {
        echo json_encode($output);
    }
    exit;
}