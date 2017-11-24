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
 * API to handle assessment reporting interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS_REPORTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
    ob_clear_open_buffers();
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
    echo json_encode(array("status"=>"error", "msg" => $ERRORSTR));
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
    exit;
} else {

    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};
    $current_id = $ENTRADA_USER->getActiveId();

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {

            }
            break;

        case "GET" :
            switch ($request["method"]) {
                case "save-preferences" :
                    $group_by_distribution = false;
                    if (isset($request["group_by_distribution"])) {
                        $group_by_distribution = clean_input($request["group_by_distribution"], array("bool"));
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_reports"]["group_by_distribution"] = $group_by_distribution;

                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully updated preferences"), "data" => $group_by_distribution));
                    break;
                case "set-curriculum-period":
                    $cperiod_id = null;
                    if (isset($request["report_cperiod_id"])) {
                        $cperiod_id = clean_input($request["report_cperiod_id"], array("int"));
                    }
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_reports"]["report_cperiod_id"] = $cperiod_id;

                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully changed curriculum period setting"), "data" => $cperiod_id));
                    break;
            }
            break;
    }
    exit;
}