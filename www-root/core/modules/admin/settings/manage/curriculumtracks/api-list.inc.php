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
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

ob_clear_open_buffers();

if((!defined("PARENT_INCLUDED")) || (!defined("IN_TRACKS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    $output = array("aaData" => array());
    $curriculum_tracks = Models_Curriculum_Track::fetchAllByOrg($ORGANISATION_ID);
    $count = 0;
    if ($curriculum_tracks) {
        /*
         * Ordering
         */
        if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3))) {
            $aColumns = array("lapplication_id", "curriculum_track_name");
            $sort_array = array();
            foreach ($curriculum_tracks as $curriculum_track) {
                switch ($_GET["iSortCol_0"]) {
                    case 1 :
                    default :
                        $sort_array[] = $curriculum_track->getCurriculumTrackName();
                        break;
                }
            }
            array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), SORT_STRING, $curriculum_tracks);
        }
        if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
            $start = (int)$_GET["iDisplayStart"];
            $limit = (int)$_GET["iDisplayLength"];
        } else {
            $start = 0;
            $limit = count($curriculum_tracks) - 1;
        }
        if ($_GET["sSearch"] != "") {
            $search_value = $_GET["sSearch"];
        }
        foreach ($curriculum_tracks as $curriculum_track) {
            if (!isset($search_value) || stripos(($curriculum_track->getCurriculumTrackName()), $search_value) !== false) {
                $url = ENTRADA_URL . "/admin/settings/manage/curriculumtracks?section=edit&id=".$curriculum_track->getID()."&org=".$ORGANISATION_ID;
                if ($count >= $start && $count < ($start + $limit)) {
                    $row = array();
                    $row["id"] = $curriculum_track->getID();
                    $row["checkbox"] = "<input class=\"delete\" type=\"checkbox\" name=\"delete[".html_encode($curriculum_track->getID())."]\" value=\"".html_encode($curriculum_track->getID())."\" />";
                    $row["curriculum_track_name"] = "<a href=\"".$url."\">".html_encode(($curriculum_track->getCurriculumTrackName()))."</a>";
                    $output["aaData"][] = $row;
                }
                $count++;
            }
        }
    }
    $output["iTotalRecords"] = (is_array($curriculum_tracks) ? @count($curriculum_tracks) : 0);
    $output["iTotalDisplayRecords"] = $count;
    $output["sEcho"] = clean_input($_GET["sEcho"], "int");
    if ($output && count($output)) {
        echo json_encode($output);
    }
    exit;
}