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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_ASSESSMENTS_REPORTS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";
    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $validated_inputs = false;
    $specified_role = null;
    $specified_proxy_id = 0;
    $specified_cperiod_id = 0;
    $specified_form_id = 0;
    $specified_distribution_id = 0;

    $specified_strip_comments = false;
    $generate_pdf = false;
    $pdf_error = false;
    $prune_empty_rubrics = true;
    $group_by_distribution = false;

    // Get our $_GET variables
    if (isset($_GET["target_id"]) && ($tmp_input = clean_input($_GET["target_id"], array("trim", "int")))) {
        $specified_proxy_id = $tmp_input;
    }
    if (isset($_GET["adistribution_id"]) && ($tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int")))) {
        $specified_distribution_id = $tmp_input;
    }
    if (isset($_GET["cperiod_id"]) && ($tmp_input = clean_input($_GET["cperiod_id"], array("trim", "int")))) {
        $specified_cperiod_id = $tmp_input;
    }
    if (isset($_GET["form_id"]) && ($tmp_input = clean_input($_GET["form_id"], array("trim", "int")))) {
        $specified_form_id = $tmp_input;
    }
    if (isset($_GET["role"]) && ($tmp_input = clean_input($_GET["role"], array("trim", "notags")))) {
        $specified_role = $tmp_input;
    }
    if (isset($_GET["strip"]) && ($tmp_input = clean_input($_GET["strip"], array("trim", "int")))) {
        $specified_strip_comments = $tmp_input;
    }
    if (isset($_GET["generate-pdf"]) && ($tmp_input = clean_input($_GET["generate-pdf"], array("trim","lower")))) {
        if ($tmp_input == "1" || $tmp_input == "true") {
            $generate_pdf = true;
        }
    }
    if (isset($_GET["pdf-error"]) && ($tmp_input = clean_input($_GET["pdf-error"], array("trim","lower")))) {
        if ($tmp_input == "1" || $tmp_input == "true") {
            $pdf_error = true;
        }
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
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["end-date"] = strtotime($tmp_input);
        } else {
            $PROCESSED["end-date"] = null;
        }
    } else {
        $PROCESSED["end-date"] = null;
    }

    // Perform simple validation on them.
    if (($specified_role == "learner" ||
        $specified_role == "faculty") &&
        $specified_proxy_id &&
        $specified_form_id &&
        $specified_cperiod_id) {
        $validated_inputs = true;
    }

    // Attempt to render.
    if ($validated_inputs) {
        $assessment_user = Models_User::fetchRowByID($specified_proxy_id);
        $specified_form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($specified_form_id);
        $target_cperiod = Models_Curriculum_Period::fetchRowByID($specified_cperiod_id);

        $cperiod_label = "";
        if ($target_cperiod) {
            if ($target_cperiod->getCurriculumPeriodTitle()) {
                $cperiod_label .= " " . $target_cperiod->getCurriculumPeriodTitle();
            }

            if ($target_cperiod->getStartDate() && $target_cperiod->getFinishDate()) {
                $cperiod_label .= " " . date("Y-m-d", $target_cperiod->getStartDate()) . " - " . date("Y-m-d", $target_cperiod->getFinishDate());
            }
        }

        $date_range_info = "";
        if (!is_null($PROCESSED["start-date"]) || !is_null($PROCESSED["end-date"])) {
            $date_range_info = "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start-date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end-date"])) . "</li>";
        }

        if ($assessment_user && $specified_form) {
            if ($specified_role == "faculty") {
                $override_permission = Entrada_Utilities_Assessments_AssessmentTask::getFacultyAccessOverrideByCourseFacultyOrWhitelist($ENTRADA_USER, $specified_proxy_id);
            } else {
                $override_permission = null;
            }
            if (Entrada_Utilities_Assessments_Reports::hasReportAccess($ENTRADA_ACL, $ENTRADA_USER, $specified_proxy_id, $specified_role, $override_permission)) {
                $breadcrumb_title = sprintf($translate->_("Report on %s for %s"), $specified_form->getTitle(), "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}");
                $BREADCRUMB[] = array("url" => "", "title" => html_encode($breadcrumb_title));
                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var proxy_id = '" . $specified_proxy_id . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
                $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
                ?>
                <script type="text/javascript">
                    var assessment_reports = {};
                    assessment_reports.default_error_message = "<?php echo $translate->_("Unable to fetch report data.")?>";
                    assessment_reports.pdf_unavailable = "<?php echo $translate->_("PDF download is currently unavailable. PDF generator library is not configured.")?>";
                </script>
                <?php
                $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                $pdf_configured = $pdf_generator->configure();

                // Group by distribution if the ID is set
                if ($specified_distribution_id) {
                    $group_by_distribution = true;
                }

                // Create the reporting utility object
                $construction = array(
                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(), // Limit to this org
                    "target_value" => $specified_proxy_id, // Limit to this user
                    "target_type" => "proxy_id",
                    "form_id" => $specified_form_id, // Limit to this form
                    "group_by_distribution" => $group_by_distribution,
                    "prune_empty_rubrics" => true,
                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                    "cperiod_id" => $specified_cperiod_id,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );

                $distribution = false;
                $distribution_name = false;
                if ($specified_distribution_id) {
                    $construction["adistribution_id"] = $specified_distribution_id; // optionally limit to distribution id
                    if ($distribution = Models_Assessments_Distribution::fetchRowByID($specified_distribution_id)) {
                        $distribution_name = $distribution->getTitle();
                    }
                }
                $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                $report_data = $reporting_utility->generateReport(); // generate (or fetch from cache) the report

                // Generate header html
                $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                $header_html = $header_view->render(
                    array(
                        "target_name" => "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}",
                        "form_name" => $specified_form->getTitle() . $cperiod_label,
                        "distribution_name" => $distribution_name,
                        "enable_pdf_button" => true,
                        "pdf_configured" => $pdf_configured,
                        "generate_pdf" => $generate_pdf,
                        "pdf_generation_url" => $pdf_generator->buildURI("/assessments/reports/report", $_SERVER["REQUEST_URI"] . "&generate-pdf=1"),
                        "list_info" => $date_range_info
                    ),
                    false
                );

                // Generate the report HTML
                $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                $report_html = $report_view->render(
                    array(
                        "report_data" => $report_data,
                        "strip_comments" => $specified_strip_comments,
                    ),
                    false
                );

                // Output in PDF format, or render to screen
                if ($generate_pdf && $pdf_configured) {

                    // Create a filename based on the report
                    $pdf_title = "{$specified_form->getTitle()} {$cperiod_label} {$assessment_user->getFirstname()} {$assessment_user->getLastname()}";
                    if (!$specified_strip_comments) {
                        $pdf_title .= $translate->_(" with comments");
                    }
                    $pdf_filename = $pdf_generator->buildFilename($pdf_title, ".pdf");

                    // The HTML for the output PDF
                    $all_html = $header_html;
                    $all_html .= $report_html;

                    if (!$pdf_generator->send($pdf_filename, $pdf_generator->generateAssessmentReportHTML($all_html))){
                        // Failed to generate PDF for this report, so redirect back with error
                        ob_clear_open_buffers();
                        $error_url = $pdf_generator->buildURI("/assessments/reports/", $_SERVER["REQUEST_URI"]);
                        $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                        Header("Location: $error_url");
                        die();
                    }

                } else {

                    // Echo the rendered HTML
                    echo $header_html;
                    if ($pdf_error) {
                        add_error($translate->_("Unable to create PDF file. Please try again later."));
                        echo display_error();
                    }
                    echo $report_html;
                }

            } else {
                add_error($translate->_("Unfortunately, you do not have permission to access this report."));
                echo display_error();
            }
        } else {
            add_error($translate->_("Please ensure you have specified a valid user and form."));
            echo display_error();
        }
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }
}