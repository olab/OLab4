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
 * This is the schedule report file that renders the view
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
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
        } else {
            $specified_target_ids = array(clean_input($_GET["target_ids"], array("trim", "int")));
        }
    } else {
        $validated_inputs = false;
    }

    if (isset($_GET["previous_page"]) && $tmp_input = clean_input(strtolower($_GET["previous_page"]), array("trim", "striptags"))) {
        $PROCESSED["previous_page"] = $tmp_input;
    } else {
        $validated_inputs = false;
    }

    $PROCESSED["report-type"] = false;
    if (isset($_GET["report-type"]) && $tmp_input = clean_input(strtolower($_GET["report-type"]), array("trim", "striptags"))) {
        $PROCESSED["report-type"] = $tmp_input;
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

    $PROCESSED["description"] = null;
    if (isset($_GET["description"]) && $tmp_input = clean_input(strtolower($_GET["description"]), array("trim", "striptags"))) {
        $PROCESSED["description"] = $tmp_input;
    }

    // Ensure the page has what parameters it needs or display an error.
    if ($validated_inputs) {

        // Attempt to render.
        if ($validated_inputs) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/reports?section=distribution-schedule-report", "title" => $translate->_("Distribution Schedule Report"));
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/reports?section=distribution-schedule-report", "title" => $translate->_("Distribution Schedule Report"));
            $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
            $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

            $report_html = "";

            switch ($PROCESSED["report-type"]) {
                case "individual":

                    ini_set('memory_limit', '2056M');

                    $individual_tasks = array();
                    $existing_tasks_model = new Models_Assessments_ExistingTaskSnapshot();
                    $existing_tasks_snapshot = $existing_tasks_model->fetchAllByADistributionIDs($specified_target_ids);
                    /**
                     * @var $existing_task Models_Assessments_ExistingTaskSnapshot
                     */
                    foreach ($existing_tasks_snapshot as $existing_task) {
                        $individual_tasks[] = array(
                            $existing_task->getAdistributionID(),
                            $existing_task->getDistributionDeletedDate(),
                            $existing_task->getDistributionTitle(),
                            $existing_task->getAssessorName(),
                            $existing_task->getTargetName(),
                            $existing_task->getFormTitle(),
                            $existing_task->getScheduleDetails(),
                            $existing_task->getProgressDetails()
                        );
                    }

                    $details_view = new Views_Assessments_Reports_DistributionScheduleTaskDataTable();
                    $report_html = $details_view->render(array(
                        "datatable_formatted_data" => $individual_tasks,
                        "header_text" => $translate->_("Distribution Task Schedule Report"),
                        "start-date" => $PROCESSED["start-date"],
                        "end-date" => $PROCESSED["end-date"],
                        "description" => $PROCESSED["description"]
                    ),false);

                    break;
                case "summary":

                    $distributions = array();
                    $formatted_data = array();

                    foreach ($specified_target_ids as $target_id) {
                        if ($target_id) {
                            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($target_id);
                            if ($distribution) {
                                $distributions[$distribution->getID()] = $distribution;
                            }
                        }
                    }

                    foreach ($distributions as $distribution) {
                        $course = Models_Course::fetchRowByID($distribution->getCourseID());
                        $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($distribution->getFormID());

                        $formatted_array = array(
                            $distribution->getID(),
                            $distribution->getDeletedDate(),
                            $distribution->getTitle(),
                            ($course ? "{$course->getCourseCode()}: {$course->getCourseName()}" : ""),
                            ($form ? $form->getTitle() : ""),
                            $distribution->getStartDate() ? date("Y-m-d", $distribution->getStartDate()) : $translate->_("N/A"),
                            $distribution->getEndDate() ? date("Y-m-d", $distribution->getEndDate()) : $translate->_("N/A"),
                            $distribution->getDeliveryDate() ? date("Y-m-d", $distribution->getDeliveryDate()) : $translate->_("N/A"),
                            $distribution->getExpiryOffset() ? date("Y-m-d", ($distribution->getDeliveryDate() + $distribution->getExpiryOffset())) : $translate->_("N/A")
                        );

                        $formatted_data[] = $formatted_array;
                    }

                    $summary_view = new Views_Assessments_Reports_DistributionScheduleSummaryDataTable();
                    $report_html = $summary_view->render(array(
                        "datatable_formatted_data" => $formatted_data,
                        "header_text" => $translate->_("Distribution Schedule Summary Report"),
                        "start-date" => $PROCESSED["start-date"],
                        "end-date" => $PROCESSED["end-date"],
                        "description" => $PROCESSED["description"]
                    ),false);


                    break;
            }

            // Echo the rendered report HTML.
            echo $report_html;
        }

    } else {
        echo display_error($translate->_("Missing report parameters that must be specified."));
    }
}
