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
 * A class to download PDFs for assessments.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Assessments_PDFDownload extends Entrada_Utilities_Assessments_Base {
    public function prepareDownloadSingle($PROCESSED) {
        global $ENTRADA_USER, $translate;
        $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();

        $date_range = (isset($PROCESSED["start_date"]) ? date("Y m d", $PROCESSED["start_date"]) : $translate->_("Not set")) . " " . (isset($PROCESSED["end_date"]) ? date("Y m d", $PROCESSED["end_date"]) : $translate->_("Not set"));
        $file_name = $assessment_pdf->buildFilename("Assessment Tasks" . $date_range, ".pdf");

        if ($PROCESSED["current_location"] == "distributions") {
            if (isset($PROCESSED["task_data"][0]["adistribution_id"])) {
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["task_data"][0]["adistribution_id"]);
                $file_name = $assessment_pdf->buildFilename("Assessment Tasks for " . $distribution->getTitle(), ".pdf");
            }
        } else if ($PROCESSED["current_location"] == "learner") {
            if (isset($PROCESSED["task_data"][0]["target_name"])) {
                $file_name = $assessment_pdf->buildFilename("Assessment Tasks for " . $PROCESSED["task_data"][0]["target_name"], ".pdf");
            }
        }

        if ($assessment_pdf->configure()) {
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $cache = new Entrada_Utilities_Cache();
            $organisation = Models_Organisation::fetchRowByID($ENTRADA_USER->getActiveOrganisation());
            if ($organisation) {
                $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$ENTRADA_USER->getActiveOrganisation()}", "image/png");
            }

            foreach ($PROCESSED["task_data"] as $task_data) {
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task_data["adistribution_id"]);
                $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());
                $assessment_user->cacheUserCardPhotos(array($task_data["target_id"] => array("id" => $task_data["target_id"])));

                if ($distribution && $form) {
                    $distribution_data = array(
                        "adistribution_id" => $task_data["adistribution_id"],
                        "proxy_id" => $task_data["assessor_value"],
                        "form_id" => $form->getID(),
                        "assessor_value" => $task_data["assessor_value"],
                        "target_record_id" => $task_data["target_id"],
                        "dassessment_id" => $task_data["dassessment_id"],
                        "aprogress_id" => $task_data["aprogress_id"]
                    );

                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_data["adistribution_id"]);
                    $assessment_record = Models_Assessments_Assessor::fetchRowByID($distribution_data["dassessment_id"]);
                    $progress_record = false;
                    if (!is_null($distribution_data["aprogress_id"])) {
                        $progress_record = Models_Assessments_Progress::fetchRowByID($distribution_data["aprogress_id"]);
                    }

                    $completed_date = "";
                    $assessor_name = $task_data["assessor_name"];
                    $target_name = $task_data["target_name"];
                    $event_name = $event_timeframe_start = $event_timeframe_end = "";
                    if ($progress_record && $progress_record->getProgressValue() == "complete") {
                        $completed_date = $progress_record->getUpdatedDate();
                    }

                    $header = "";
                    if ($assessment_record) {
                        $delivery_info_view = new Views_Assessments_Sidebar_DeliveryInfo();
                        $header = $delivery_info_view->render(array(
                            "assessment_record" => $assessment_record,
                            "distribution" => $distribution,
                            "distribution_schedule" => $distribution_schedule,
                            "is_pdf" => true,
                            "target_name" => $target_name,
                            "event_name" => $event_name,
                            "assessor_name" => $assessor_name,
                            "completed_date" => $completed_date,
                            "timeframe_start" => $event_timeframe_start,
                            "timeframe_end" => $event_timeframe_end
                        ), false
                        );
                    }

                    // Instantiate utility object, with optional parameters.
                    $forms_api = new Entrada_Assessments_Forms(array(
                            "form_id" => $form->getID(),
                            "adistribution_id" => @$PROCESSED["adistribution_id"] ? $PROCESSED["adistribution_id"] : null,
                            "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                            "dassessment_id" => @$PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        )
                    );
                    // Fetch form data using those params
                    $form_data = $forms_api->fetchFormData();

                    // Render the form in PDF mode
                    $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
                    $assessment_html = $form_view->render(
                        array(
                            "form_id" => $form->getID(),
                            "disabled" => false,
                            "form_elements" => $form_data["elements"],
                            "progress" => $form_data["progress"],
                            "rubrics" => $form_data["rubrics"],
                            "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                            "public" => true,
                            "objectives" => @$PROCESSED["objectives"]
                        ),
                        false
                    );

                    if ($assessment_html && $distribution->getFeedbackRequired()) {

                        $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($assessment_record->getID(), "internal", $assessment_record->getAssessorValue(), "internal", $task_data["target_id"]);

                        $approver_model = new Models_Assessments_Distribution_Approver();
                        $approver = $approver_model->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $distribution_data["adistribution_id"]);
                        $hide_from_approver = ($approver) ? true : false;

                        // Append the feedback, if it's specified
                        $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                        $assessment_html .= $feedback_view->render(
                            array(
                                "target_record_id" => $distribution_data["target_record_id"],
                                "distribution" => $distribution,
                                "hide_from_approver" => $hide_from_approver,
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "progress_record" => $progress_record,
                                "assessment_record" => $assessment_record,
                                "feedback_record" => $feedback_record,
                                "is_pdf" => true
                            ),
                            false
                        );
                    }

                    $html = $assessment_pdf->generateAssessmentHTML($assessment_html, $ENTRADA_USER->getActiveOrganisation(), $form->getTitle(), $header, $task_data["target_id"], $PROCESSED["description"]);
                    $assessment_pdf->addHTMLPage($html);
                }
            }

            if (!$assessment_pdf->send($file_name, null, false)) {
                ob_clear_open_buffers();
                $error_url = $assessment_pdf->buildURI("/assessments/assessment/", $_SERVER["REQUEST_URI"]);
                $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                Header("Location: $error_url");
                die();
            }
            exit;
        } else {
            display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
            application_log("error", "From API: Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
        }
    }

    public function prepareDownloadMultiple($PROCESSED, $last_task_for_user = array()) {
        global $ENTRADA_USER, $translate;
        $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $assessment_pdf_filenames = array();

        $date_range = (isset($PROCESSED["start_date"]) ? date("Y m d", $PROCESSED["start_date"]) : $translate->_("Not set")) . " " . (isset($PROCESSED["end_date"]) ? date("Y m d", $PROCESSED["end_date"]) : $translate->_("Not set"));
        $file_path = $assessment_pdf->buildFilename("Assessment Tasks " . $date_range, ".zip", true, true);

        if ($PROCESSED["current_location"] == "distributions") {
            if (isset($PROCESSED["task_data"][0]["adistribution_id"])) {
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["task_data"][0]["adistribution_id"]);
                $file_path = $assessment_pdf->buildFilename("Assessment Tasks for " . $distribution->getTitle(), ".zip", true, true);
            }
        } else if ($PROCESSED["current_location"] == "learner") {
            if (isset($PROCESSED["task_data"][0]["target_name"])) {
                $file_path = $assessment_pdf->buildFilename("Assessment Tasks for " . $PROCESSED["task_data"][0]["target_name"], ".zip", true, true);
            }
        }

        $individual_pdfs = false;
        if (empty($last_task_for_user) || !is_array($last_task_for_user)) {
            $individual_pdfs = true;
        }

        if ($assessment_pdf->configure()) {
            $zip = new ZipArchive();
            if ($zip->open($file_path, ZipArchive::CREATE) === true) {
                $assessment_user = new Entrada_Utilities_AssessmentUser();
                $cache = new Entrada_Utilities_Cache();
                $organisation = Models_Organisation::fetchRowByID($ENTRADA_USER->getActiveOrganisation());
                if ($organisation) {
                    $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$ENTRADA_USER->getActiveOrganisation()}", "image/png");
                }

                foreach ($PROCESSED["task_data"] as $i => $task_data) {
                    $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task_data["adistribution_id"]);
                    $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());
                    $assessment_user->cacheUserCardPhotos(array($task_data["target_id"] => array("id" => $task_data["target_id"])));

                    if ($distribution && $form) {
                        $distribution_data = array(
                            "adistribution_id" => $task_data["adistribution_id"],
                            "proxy_id" => $task_data["assessor_value"],
                            "form_id" => $form->getID(),
                            "assessor_value" => $task_data["assessor_value"],
                            "target_record_id" => $task_data["target_id"],
                            "dassessment_id" => $task_data["dassessment_id"],
                            "aprogress_id" => $task_data["aprogress_id"]
                        );

                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_data["adistribution_id"]);
                        $assessment_record = Models_Assessments_Assessor::fetchRowByID($distribution_data["dassessment_id"]);
                        $progress_record = false;
                        if (!is_null($distribution_data["aprogress_id"])) {
                            $progress_record = Models_Assessments_Progress::fetchRowByID($distribution_data["aprogress_id"]);
                        }

                        // ADRIAN-TODO: When eventtype progress page is completed, support PDF downloading here.
                        //$distribution_eventtypes = Models_Assessments_Distribution_Eventtype::fetchAllByDistributionID($distribution_data["adistribution_id"]);

                        $completed_date = "";
                        $assessor_name = $task_data["assessor_name"];
                        $target_name = $task_data["target_name"];
                        $event_name = $event_timeframe_start = $event_timeframe_end = "";
                        $form_name = $form->getTitle();
                        if ($progress_record && $progress_record->getProgressValue() == "complete") {
                            $completed_date = $progress_record->getUpdatedDate();
                        }

                        $header = "";
                        if ($assessment_record) {
                            $delivery_info_view = new Views_Assessments_Sidebar_DeliveryInfo();
                            $header = $delivery_info_view->render(
                                array(
                                    "assessment_record" => $assessment_record,
                                    "distribution" => $distribution,
                                    "distribution_schedule" => $distribution_schedule,
                                    "is_pdf" => true,
                                    "target_name" => $target_name,
                                    "event_name" => $event_name,
                                    "assessor_name" => $assessor_name,
                                    "completed_date" => $completed_date,
                                    "timeframe_start" => $event_timeframe_start,
                                    "timeframe_end" => $event_timeframe_end
                                ),
                                false
                            );
                        }

                        // Instantiate utility object, with optional parameters.
                        $forms_api = new Entrada_Assessments_Forms(array(
                                "form_id" => $form->getID(),
                                "adistribution_id" => @$PROCESSED["adistribution_id"] ? @$PROCESSED["adistribution_id"] : null,
                                "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                "dassessment_id" => @$PROCESSED["dassessment_id"] ? @$PROCESSED["dassessment_id"] : null,
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                            )
                        );
                        // Fetch form data using those params
                        $form_data = $forms_api->fetchFormData();

                        // Render the form in PDF mode
                        $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
                        $assessment_html = $form_view->render(
                            array(
                                "form_id" => $form->getID(),
                                "disabled" => false,
                                "form_elements" => $form_data["elements"],
                                "progress" => $form_data["progress"],
                                "rubrics" => $form_data["rubrics"],
                                "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                "public" => true,
                                "objectives" => @$PROCESSED["objectives"] ? @$PROCESSED["objectives"] : null
                            ),
                            false
                        );

                        if ($assessment_html && $distribution->getFeedbackRequired() && isset($PROCESSED["dassessment_id"])) {

                            $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $PROCESSED["target_record_id"]);

                            $approver_model = new Models_Assessments_Distribution_Approver();
                            $approver = $approver_model->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $distribution_data["adistribution_id"]);
                            $hide_from_approver = ($approver) ? true : false;

                            // Append the feedback, if it's specified
                            $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                            $assessment_html .= $feedback_view->render(
                                array(
                                    "target_record_id" => $distribution_data["target_record_id"],
                                    "distribution" => $distribution,
                                    "hide_from_approver" => $hide_from_approver,
                                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                    "progress_record" => $progress_record,
                                    "assessment_record" => $assessment_record,
                                    "feedback_record" => $feedback_record,
                                    "is_pdf" => true
                                )
                            );
                        }


                        if ($assessment_html) {
                            $html = $assessment_pdf->generateAssessmentHTML($assessment_html, $ENTRADA_USER->getActiveOrganisation(), $form->getTitle(), $header, $task_data["target_id"], $PROCESSED["description"]);

                            if ($individual_pdfs || $last_task_for_user[$i]) {
                                $ordinal = 0;
                                $file_title = "$target_name {$translate->_("assessment")} $date_range";
                                while (in_array($file_title, $assessment_pdf_filenames)) {
                                    $ordinal++;
                                    $file_title = "$target_name {$translate->_("assessment")} $date_range $ordinal";
                                }
                                $assessment_pdf_filenames[] = $file_title;
                                $pdf_filename = $assessment_pdf->buildFilename($file_title, ".pdf");
                                $zip->addFromString($pdf_filename, $assessment_pdf->toString($html));
                                $assessment_pdf->reset();
                            } else {
                                $assessment_pdf->addHTMLPage($html);
                            }
                        }
                    }
                }
                $zip->close();

                $file_name = basename($file_path);
                ob_clear_open_buffers();
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=" . $file_name);
                header("Content-Length: " . filesize($file_path));
                readfile($file_path);
                unlink($file_path);
                exit;
            } else {
                add_error($translate->_("Zip archive could not be created."));
            }
        } else {
            display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
            application_log("error", "From API: Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
        }
    }

    public function prepareDownloadMultipleReports($report_html, $form_title, $target_names, $include_comments = false) {
        global $translate;
        $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $file_path = $pdf_generator->buildFilename("Reports", ".zip", true, true);
        $report_pdf_filenames = array();

        if ($pdf_generator->configure()) {
            $zip = new ZipArchive();
            if ($zip->open($file_path, ZipArchive::CREATE) === true) {
                $ctr = 0;
                foreach ($report_html as $report) {
                    $pdf_title = "{$form_title} {$target_names[$ctr]}";
                    if ($include_comments) {
                        $pdf_title .= $translate->_(" with comments");
                    }

                    $ordinal = 0;
                    $file_title = "{$target_names[$ctr]} {$translate->_("reports")}";
                    while (in_array($file_title, $report_pdf_filenames)) {
                        $ordinal++;
                        $file_title = "$target_names[$ctr] {$translate->_("reports")} $ordinal";
                    }
                    $report_pdf_filenames[] = $file_title;
                    $pdf_filename = $pdf_generator->buildFilename($pdf_title, ".pdf");
                    $zip->addFromString($pdf_filename, $pdf_generator->toString($pdf_generator->generateAssessmentReportHTML($report)));
                    $pdf_generator->reset();
                    $ctr++;
                }
                $zip->close();

                $file_name = basename($file_path);
                ob_clear_open_buffers();
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=" . $file_name);
                header("Content-Length: " . filesize($file_path));
                readfile($file_path);
                unlink($file_path);
                exit;
            } else {
                add_error($translate->_("Zip archive could not be created."));
            }
        } else {
            display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
            application_log("error", "From API: Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
        }
    }

    public function prepareDownloadSingleCompletionReport($user_data, $date_range, $include_average_delivery_date, $description = null) {
        global $ENTRADA_USER, $translate;
        $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $file_name = $assessment_pdf->buildFilename("Timeliness of Completion Report", ".pdf");

        if ($assessment_pdf->configure()) {
            $cache = new Entrada_Utilities_Cache();
            $organisation = Models_Organisation::fetchRowByID($ENTRADA_USER->getActiveOrganisation());
            if ($organisation) {
                $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$ENTRADA_USER->getActiveOrganisation()}", "image/png");
            }

            $report_html  = "<table class='table table-bordered table-striped'>";
            $report_html .= "    <thead>";
            $report_html .= "        <th>" . $translate->_("Name") . "</th>";
            $report_html .= "        <th>" . $translate->_("Completed") . "</th>";
            $report_html .= "        <th>" . $translate->_("Delivered") . "</th>";
            $report_html .= "        <th>" . $translate->_("Average (End of Experience)") . "</th>";
            if ($include_average_delivery_date) :
                $report_html .= "    <th>" . $translate->_("Average (Delivery Date)") . "</th>";
            endif;
            $report_html .= "    <thead>";
            $report_html .= "    <tbody>";
            foreach ($user_data as $user) {
                $report_html .= "    <tr>";
                $report_html .= "        <td>" . $user["user_name"] . ($user["all_tasks_based_off_schedule"] ? "" : "*") . "</td>";
                $report_html .= "        <td>" . $user["completed_assessments"] . "</td>";
                $report_html .= "        <td>" . $user["total_delivered_assessments"] . "</td>";
                $report_html .= "        <td>" . $user["rotation_average_completion_time"] . "</td>";
                if ($include_average_delivery_date) :
                    $report_html .= "    <td>" . $user["average_completion_time"] . "</td>";
                endif;
                $report_html .= "    </tr>";
            }
            $report_html .= "    </tbody>";
            $report_html .= "</table>";
            $report_html .= "<p>" . $translate->_("* Signals that some assessments were not based off the schedule, therefore were not included in the average.") . "</p>";

            $html = $assessment_pdf->generateAssessmentHTML($report_html, $ENTRADA_USER->getActiveOrganisation(), $translate->_("Timeliness of Completion Report"), $date_range, $user["proxy_id"], $description);

            if (!$assessment_pdf->send($file_name, $html, true)) {
                ob_clear_open_buffers();
                $error_url = $assessment_pdf->buildURI("/assessments/assessment/", $_SERVER["REQUEST_URI"]);
                $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                Header("Location: $error_url");
                die();
            }
            exit;
        } else {
            display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
            application_log("error", "From API: Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
        }
    }

    public function prepareDownloadSingleLeaveByBlockReport($schedules_with_grouped_users, $description = null) {
        global $ENTRADA_USER, $translate;
        $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $file_name = $assessment_pdf->buildFilename("Leave by Block Report", ".pdf");

        if ($assessment_pdf->configure()) {
            $cache = new Entrada_Utilities_Cache();
            $organisation = Models_Organisation::fetchRowByID($ENTRADA_USER->getActiveOrganisation());
            if ($organisation) {
                $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$ENTRADA_USER->getActiveOrganisation()}", "image/png");
            }

            $report_html = "";
            foreach ($schedules_with_grouped_users as $schedule) {
                if (!empty($schedule["proxy_list"])) {
                    $report_html .= "<h2>" . $schedule["schedule_title"] . $translate->_(" - Start Date: ") . date("Y-m-d", (int)$schedule["schedule_start"]) . $translate->_(" End Date: ") . date("Y-m-d", (int)$schedule["schedule_end"]) . "</h2>";
                    $report_html .= "<table class='table table-bordered table-striped'>";
                    $report_html .= "    <thead>";
                    $report_html .= "        <th style='padding-bottom: 5px; padding-top: 5px;' width='30%'>" . $translate->_("Name") . "</th>";
                    $report_html .= "        <th style='padding-bottom: 5px; padding-top: 5px;' width='20%'>" . $translate->_("Leave Start") . "</th>";
                    $report_html .= "        <th style='padding-bottom: 5px; padding-top: 5px;' width='20%'>" . $translate->_("Leave End") . "</th>";
                    $report_html .= "        <th style='padding-bottom: 5px; padding-top: 5px;' width='30%'>" . $translate->_("Leave Type") . "</th>";
                    $report_html .= "    <thead>";
                    $report_html .= "    <tbody>";
                    foreach ($schedule["proxy_list"] as $user) {
                        $report_html .= "    <tr>";
                        $report_html .= "        <td style='padding-bottom: 5px; padding-top: 5px;'>" . $user["user_name"] . "</td>";
                        $report_html .= "        <td style='padding-bottom: 5px; padding-top: 5px;'>" . date("Y-m-d", (int)$user["leave_start"]) . "</td>";
                        $report_html .= "        <td style='padding-bottom: 5px; padding-top: 5px;'>" . date("Y-m-d", (int)$user["leave_end"]) . "</td>";
                        $report_html .= "        <td style='padding-bottom: 5px; padding-top: 5px;'>" . $user["leave_title"] . "</td>";
                        $report_html .= "    </tr>";
                    }
                    $report_html .= "    </tbody>";
                    $report_html .= "</table>";
                }
            }

            if ($report_html == "") {
                $report_html = "
                <div class=\"assessment-report-node\">
                    <table class=\"table table-striped table-bordered\">
                        <tbody>
                            <tr>
                                <td class=\"form-search-message text-center\" colspan=\"4\">
                                    <p class=\"no-search-targets space-above space-below medium\">" . $translate->_("No results found.") . "</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>";
            }

            $html = $assessment_pdf->generateAssessmentHTML($report_html, $ENTRADA_USER->getActiveOrganisation(), $translate->_("Leave by Block Report"), null, false, $description);

            if (!$assessment_pdf->send($file_name, $html, true)) {
                ob_clear_open_buffers();
                $error_url = $assessment_pdf->buildURI("/assessments/assessment/", $_SERVER["REQUEST_URI"]);
                $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                Header("Location: $error_url");
                die();
            }
            exit;
        } else {
            display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
            application_log("error", "From API: Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
        }
    }
}