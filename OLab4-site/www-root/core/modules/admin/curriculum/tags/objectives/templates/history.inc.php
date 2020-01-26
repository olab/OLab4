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
 * This file is used to add objectives in the entrada.global_lu_objectives table.
 *
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine - Medtech
 * @author Developer: Yacine Ghomri <yghomri@uottawa.ca>
 * @copyright Copyright 2017 University of Ottawa. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_OBJECTIVES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('objectivehistory', 'read', false)) {
    $ONLOAD[]   = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";
 
    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
 
    echo display_error();
 
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    echo "<h2>History</h2>";
    $history = Models_ObjectiveHistory::fetchHistory($objectiveId, $ENTRADA_USER->getActiveOrganisation());
    if (!empty($history)) {
        $previous_day = 0;
        foreach ($history as $key => $result) {
            $current_day = mktime(0, 0, 0, date("m", $result["history_timestamp"]), date("d", $result["history_timestamp"]), date("Y", $result["history_timestamp"]));
            if ($current_day != $previous_day) {
                $previous_day = $current_day;
                if ($key > 0) {
                    echo "</ul>";
                }
                echo "<strong>" . date("F j, Y", $current_day) . "</strong>";
                echo "<ul class=\"history\">";
            }
            $msg = "<li>";
            $msg .= date("g:ia ", $result["history_timestamp"]) . $result["fullname"];
            $msg .= " " . $result["history_message"];
            $msg .= "</li>";
            echo $msg;
        }
        echo "</ul>";
    } else {
        echo $translate->_("No history found for this tag.");
    }
}

?>
