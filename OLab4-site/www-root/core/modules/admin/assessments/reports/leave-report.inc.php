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
 * This is the leave report file that renders the view
 * Also handles the pdf generation
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    global $ENTRADA_USER;
    $validated_inputs = true;
    $specified_target_id = array();
    $generate_pdf = false;
    $pdf_error = false;
    $comments = "";

    // Get our $_GET variables
    $specified_target_ids = array();
    if (isset($_GET["target_ids"])) {
        if (is_array($_GET["target_ids"])) {
            $specified_target_ids = array_map(
                function ($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["target_ids"]
            );
        }
    } else {
        $validated_inputs = false;
    }

    if (isset($_GET["previous_page"]) && $tmp_input = clean_input(strtolower($_GET["previous_page"]), array("trim", "striptags"))) {
        $PROCESSED["previous_page"] = $tmp_input;
    } else {
        $validated_inputs = false;
    }

    if (isset($_GET["start-date"]) && $tmp_input = clean_input($_GET["start-date"], array("nows", "notags"))) {
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["start-date"] = strtotime($tmp_input);
        } else {
            $PROCESSED["start-date"] = null;
        }
    } else {
        $PROCESSED["start-date"] = null;
    }

    if (isset($_GET["end-date"]) && $tmp_input = clean_input($_GET["end-date"], array("nows", "notags"))) {
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input );
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["end-date"] = strtotime($tmp_input . " 23:59:59");
        } else {
            $PROCESSED["end-date"] = null;
        }
    } else {
        $PROCESSED["end-date"] = null;
    }

    if (isset($_GET["comments"]) && ($tmp_input = clean_input($_GET["comments"], array("trim", "int")))) {
        $comments = $tmp_input;
    }

    $PROCESSED["description"] = null;
    if (isset($_GET["description"]) && $tmp_input = clean_input(strtolower($_GET["description"]), array("trim", "striptags"))) {
        $PROCESSED["description"] = $tmp_input;
    }

    // Attempt to render.
    if ($validated_inputs) {
        $full_target_list_details = "";
        $type = "";
        $scope = "self";
        $specified_proxy_id = 0;
        $target_header = "";
        $type="proxy_id";
        $target_names = array();

        foreach($specified_target_ids as $target_id) {
            if ($target_id) {
                $user = Models_User::fetchRowByID($target_id);
                if ($user) {
                    $target_names[$target_id] = $user->getFullname(false);
                    $target_header .= $target_names[$target_id] . ", ";

                }
                $leave[$target_id] = Models_Leave_Tracking::fetchAllByProxyID($target_id, $PROCESSED["start-date"], $PROCESSED["end-date"]);
            }
        }
        $target_header = rtrim($target_header, ", ");
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/reports?section=rotation-leave-report", "title" => $translate->_("Leave Report"));
        $BREADCRUMB[] = array("url" => "", "title" => html_encode($target_header));
        $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
        $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

        $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
        $header_html = $header_view->render(
            array(
                "target_name" => $target_header,
                "enable_pdf_button" => false,
                "pdf_configured" => "",
                "generate_pdf" => "",
                "pdf_generation_url" => "",
                "list_info" => $full_target_list_details,
                "use_leave_title" => true
            ),
            false
        );

        $leave_view = new Views_Assessments_Reports_LeaveReport();
        $report_html = $leave_view->render(array(
                "report_data" => $leave,
                "target_names" => $target_names,
                "comments" => $comments,
                "start-date" => $PROCESSED["start-date"],
                "end-date" => $PROCESSED["end-date"],
                "description" => $PROCESSED["description"]
            ),
        false);

        // Echo the rendered HTML
        echo $header_html;
        echo $report_html;

    }
}
