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
if ((!defined("PARENT_INCLUDED"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE, "title" => "CBME Assessment Report");

    /**
     * Load module preferences
     */
    $PREFERENCES = preferences_load("cbme_assessments");

    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/report.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

    echo display_error();

    $validated_inputs = true;
    $specified_strip_comments = false;
    $include_commenter_id = false;
    $include_commenter_name = false;
    $generate_pdf = false;
    $pdf_error = false;

    // Get our $_GET variables

    if (isset($_GET["target_type"]) && $tmp_input = clean_input(strtolower($_GET["target_type"]), array("trim", "striptags"))) {
        $PROCESSED["target_type"] = $tmp_input;
    } else {
        $validated_inputs = false;
    }

    $rating_scale_ids = array();
    if (isset($_GET["rating_scale_ids"])) {
        if (is_array($_GET["rating_scale_ids"])) {
            $rating_scale_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["rating_scale_ids"]
            );
        }
    }

    $specified_target_id = false;
    if (isset($_GET["target_id"]) && ($tmp_input = clean_input($_GET["target_id"], array("trim", "int")))) {
        $specified_target_id = $tmp_input;
    } else {
        $validated_inputs = false;
    }

    $specified_form_ids = false;
    if (isset($_GET["form_ids"])) {
        if (is_array($_GET["form_ids"])) {
            $specified_form_ids = array();
            $specified_form_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["form_ids"]
            );
        }
    }

    $specified_strip_comments = false;
    if (isset($_GET["strip"]) && ($tmp_input = clean_input($_GET["strip"], array("trim", "int")))) {
        $specified_strip_comments = $tmp_input;
    }

    if (!$specified_strip_comments) {
        $include_commenter_id = false;
        if (isset($_GET["commenter-id"]) && ($tmp_input = clean_input($_GET["commenter-id"], array("trim", "int")))) {
            $include_commenter_id = $tmp_input;
        }
        $include_commenter_name = false;
        if (isset($_GET["commenter-name"]) && ($tmp_input = clean_input($_GET["commenter-name"], array("trim", "int")))) {
            $include_commenter_name = $tmp_input;
        }
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

    $PROCESSED["description"] = null;
    if (isset($_GET["description"]) && $tmp_input = clean_input(strtolower($_GET["description"]), array("trim", "striptags"))) {
        $PROCESSED["description"] = $tmp_input;
    }

    $PROCESSED["associated_record_type"] = null;
    if (isset($_GET["associated_record_type"]) && $tmp_input = clean_input(strtolower($_GET["associated_record_type"]), array("trim", "striptags"))) {
        $PROCESSED["associated_record_type"] = $tmp_input;
    }

    $associated_record_ids = null;
    if (isset($_GET["associated_record_ids"])) {
        if (is_array($_GET["associated_record_ids"])) {
            $associated_record_ids = array();
            $associated_record_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["associated_record_ids"]
            );
        }
    }

    $progress_ids = null;
    if (isset($_GET["progress_ids"])) {
        if (is_array($_GET["progress_ids"])) {
            $progress_ids = array();
            $progress_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["progress_ids"]
            );
        }
    }

    $iresponse_ids = null;
    if (isset($_GET["iresponse_ids"])) {
        if (is_array($_GET["iresponse_ids"])) {
            $iresponse_ids = array();
            $iresponse_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["iresponse_ids"]
            );
        }
    }

    $PROCESSED["include_statistics"] = false;
    if (isset($_GET["include_statistics"]) && ($tmp_input = clean_input($_GET["include_statistics"], array("trim"))) && ($tmp_input == "true")) {
        $PROCESSED["include_statistics"] = true;
    }

    $PROCESSED["include_positivity"] = false;
    if ($PROCESSED["include_statistics"]) {
        if (isset($_GET["include_positivity"]) && ($tmp_input = clean_input($_GET["include_positivity"], array("trim"))) && ($tmp_input == "true")) {
            $PROCESSED["include_positivity"] = true;
        }
    }

    $PROCESSED["include_associated_records_subheaders"] = false;
    if (isset($_GET["include_associated_records_subheaders"]) && ($tmp_input = clean_input($_GET["include_associated_records_subheaders"], array("trim"))) && ($tmp_input == "true")) {
        $PROCESSED["include_associated_records_subheaders"] = true;
    }

    // Attempt to render.
    if ($validated_inputs) {
        /**
         * CBME report rendering.
         */
        $distinct_titles = array();
        $specified_distribution_ids = array();
        $specified_associated_record_type = $PROCESSED["associated_record_type"];
        $specified_associated_record_ids = $associated_record_ids;
        $specified_schedule_target_ids = array();
        $specified_eventtype_target_ids = array();
        $full_target_list_details = "";
        $type = $PROCESSED["target_type"];
        $scope = "self";
        $target_header = "";

        if ($PROCESSED["target_type"] == "proxy_id") {
            $user = Models_User::fetchRowByID($specified_target_id);
            if ($user) {
                $target_header .= " " . $user->getFullname(false) . " ";
            }
        }

        if (!is_null($PROCESSED["start-date"]) || !is_null($PROCESSED["end-date"])) {
            $full_target_list_details .= "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start-date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end-date"])) . "</li>";
        }

        if ($target_header != "" && $type != "") {
            $forms = array();
            foreach ($specified_form_ids as $specified_form_id) {
                $specified_form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($specified_form_id);
                if ($specified_form) {
                    $forms[$specified_form_id] = $specified_form;
                }
            }

            if (!empty($forms)) {
                // Sort forms by date so that we can output a sequential version.
                usort($forms, function ($a, $b) {
                    $a = $a->getCreatedDate();
                    $b = $b->getCreatedDate();
                    if ($a == $b) {
                        return 0;
                    }
                    return ($a < $b) ? -1 : 1;
                });

                $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
                ?>
                <script type="text/javascript">
                    var assessment_reports = {};
                    assessment_reports.default_error_message = "<?php echo $translate->_("Unable to fetch report data.")?>";
                    assessment_reports.pdf_unavailable = "<?php echo $translate->_("PDF download is currently unavailable. PDF generator library is not configured.")?>";
                </script>
                <?php

                $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                $pdf_configured = $pdf_generator->configure();

                $course_utility = new Models_CBME_Course();
                $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());
                $courses = $course_utility->getActorCourses(
                    $ENTRADA_USER->getActiveGroup(),
                    $ENTRADA_USER->getActiveRole(),
                    $ENTRADA_USER->getActiveOrganisation(),
                    $ENTRADA_USER->getActiveId(),
                    $specified_target_id,
                    $cperiods
                );

                $all_html = "";
                $report_html = "";
                $ctr = 0;
                foreach ($forms as $specified_form) {

                    // Create the reporting utility object.
                    $construction = array(
                        "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                        "target_value" => $specified_target_id,
                        "target_type" => $type,
                        "form_id" => $specified_form->getID(),
                        "start_date" => $PROCESSED["start-date"],
                        "end_date" => $PROCESSED["end-date"],
                        "course_id" => $courses["course_id"],
                        "aprogress_id" => $progress_ids,
                        "iresponse_id" => $iresponse_ids
                    );

                    // If the user is the target, hide blacklisted items.
                    if ($specified_target_id == $ENTRADA_USER->getActiveID()) {
                        $item_group_blacklist = array(
                            "cbme_supervisor_rubric_concerns",
                            "cbme_supervisor_rubric_concerns_item_1",
                            "cbme_supervisor_rubric_concerns_item_2",
                            "cbme_supervisor_rubric_concerns_item_3",
                            "cbme_fieldnote_rubric_concerns",
                            "cbme_fieldnote_rubric_concerns_item_1",
                            "cbme_fieldnote_rubric_concerns_item_2",
                            "cbme_fieldnote_rubric_concerns_item_3",
                            "cbme_multisource_rubric_concerns",
                            "cbme_multisource_rubric_concerns_item_1",
                            "cbme_multisource_rubric_concerns_item_2",
                            "cbme_multisource_rubric_concerns_item_3",
                            "cbme_procedure_rubric_concerns",
                            "cbme_procedure_rubric_concerns_item_1",
                            "cbme_procedure_rubric_concerns_item_2",
                            "cbme_procedure_rubric_concerns_item_3",
                            "cbme_fieldnote_feedback",
                            "cbme_procedure_feedback",
                            "cbme_supervisor_feedback",
                            "cbme_ppa_feedback",
                            "cbme_ppa_concerns",
                            "cbme_ppa_concerns_item_1",
                            "cbme_ppa_concerns_item_2",
                            "cbme_ppa_concerns_item_3",
                            "cbme_rubric_feedback",
                            "cbme_rubric_concerns",
                            "cbme_rubric_concerns_item_1",
                            "cbme_rubric_concerns_item_2",
                            "cbme_rubric_concerns_item_3",
                            "cbme_multisource_feedback"
                        );
                        $construction["item_group_blacklist"] = $item_group_blacklist;
                    }

                    $reporting_utility = new Entrada_Utilities_Assessments_CBMEReports($construction);
                    $report_data = $reporting_utility->generateReport(); // Generate (or fetch from cache) the report.

                    $associated_record_subheaders = array();
                    // Extra headers based on specific associated records passed in.
                    if ($PROCESSED["include_associated_records_subheaders"] && $specified_associated_record_type && $associated_record_ids) {
                        switch ($specified_associated_record_type) {
                            case "event_id" :

                                $events = array();
                                foreach ($associated_record_ids as $associated_record_id) {
                                    $event = Models_Event::fetchRowByID($associated_record_id);
                                    if (!$event) {
                                        continue;
                                    }

                                    $teachers = array();
                                    $event_contacts = Models_Event_Contacts::fetchAllByEventID($event->getID());
                                    if ($event_contacts) {
                                        foreach ($event_contacts as $event_contact) {
                                            if ($event_contact->getContactRole() != "auditor") {
                                                $teacher_user = Models_User::fetchRowByID($event_contact->getProxyID());
                                                if ($teacher_user) {
                                                    $teachers[] = $teacher_user->getFullname(false);
                                                }
                                            }
                                        }
                                    }

                                    $events[$event->getID()] = array(
                                        "title" => $event->getEventTitle(),
                                        "date" => date("Y-m-d", $event->getEventStart()),
                                        "teachers" => $teachers
                                    );
                                }

                                $subheader_view = new Views_Assessments_Reports_Subheaders_LearningEvents(array());
                                $subheader_html = $subheader_view->render(array("events" => $events), false);

                                if ($subheader_html) {
                                    $associated_record_subheaders[] = $subheader_html;
                                }
                                break;
                            default:
                                break;
                        }
                    }

                    if ($report_data) {
                        // Generate header html
                        $header_view = new Views_Assessments_Reports_Header(array("class" => "space-above space-below medium"));
                        $header_html = $header_view->render(
                            array(
                                "target_name" => $target_header,
                                "form_name" => null,
                                "enable_pdf_button" => true,
                                "subheader_html" => $associated_record_subheaders,
                                "pdf_configured" => $pdf_configured,
                                "generate_pdf" => $generate_pdf,
                                "pdf_generation_url" => $pdf_generator->buildURI("/cbme/report", $_SERVER["REQUEST_URI"] . "&generate-pdf=1"),
                                "list_info" => $full_target_list_details,
                                "description" => $PROCESSED["description"]
                            ),
                            false
                        );

                        // Generate the report HTML
                        $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                        $report = $report_view->render(
                            array(
                                "report_data" => $report_data,
                                "strip_comments" => $specified_strip_comments,
                                "include_commenter_id" => $include_commenter_id,
                                "include_commenter_name" => $include_commenter_name,
                                "is_evaluation" => false,
                                "additional_statistics" => $PROCESSED["include_statistics"],
                                "include_positivity" => $PROCESSED["include_positivity"],
                                "include_assessor_names" => true
                            ),
                            false
                        );

                        $deleted = $specified_form->getDeletedDate() ? " - " . date("Y-m-d", $specified_form->getDeletedDate()) : "";
                        $dates = date("Y-m-d", $specified_form->getCreatedDate()) . $deleted;
                        $ctr++;

                        $expand = $generate_pdf || $ctr == 1 ? true : false;

                        $report_html .= "<div id=\"accordion-form-{$specified_form->getID()}-container\" class=\"collapsible-card\">
                                            <div class=\"collapsible-card-heading\">
                                                <h3 class=\"accordion-title\">v{$ctr}. {$specified_form->getTitle()} ($dates)</h3>
                                                <i class=\"fa fa-chevron-down collapsible-card-icon" . ($expand ? " active" : "") . "\"></i>
                                            </div>
                                            <div class=\"collapsible-card-body" . ($expand ? "" : " collapsed") . "\" id=\"accordion-form-{$specified_form->getID()}\">
                                                {$report}
                                            </div>
                                         </div>";

                        $all_html = $header_html;
                    }
                }

                if ($report_html) {
                    $all_html .= $report_html;
                }

                if ($all_html) {
                    // Output in PDF format, or render to screen
                    if ($generate_pdf && $pdf_configured) {

                        // Create a filename based on the report
                        $pdf_title = "$target_header";

                        if (!$specified_strip_comments) {
                            $pdf_title .= $translate->_(" with comments");
                        }
                        $pdf_filename = $pdf_generator->buildFilename($pdf_title, ".pdf");

                        if (!$pdf_generator->send($pdf_filename, $pdf_generator->generateAssessmentReportHTML($all_html))) {
                            // Failed to generate PDF for this report, so redirect back with error
                            ob_clear_open_buffers();
                            $error_url = $pdf_generator->buildURI("/admin/assessments/reports", $_SERVER["REQUEST_URI"]);
                            $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                            Header("Location: $error_url");
                            die();
                        }
                    } else {

                        // Echo the rendered HTML
                        if ($pdf_error) {
                            add_error($translate->_("Unable to create PDF file. Please try again later."));
                            echo display_error();
                        }
                        echo $all_html;
                    }
                }

            }
        } else {
            add_error($translate->_("Please ensure you have specified a valid user and form."));
            echo display_error();
        }

        /**
         * Trends chart rendering.
         */
        $course_utility = new Models_CBME_Course();
        $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());
        $courses = $course_utility->getActorCourses(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            $specified_target_id,
            $cperiods
        );

        // Instantiate the CBME visualization abstraction layer
        $cbme_progress_api = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $specified_target_id,
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress",
            "filters" => array(
                "rating_scale_id" => $rating_scale_ids,
                "aprogress_ids" => $progress_ids,
                "iresponse_ids" => $iresponse_ids,
            ),
            "limit_dataset" => array(
                "rating_scales_charts"
            ),
            "courses"  => $courses,
            "secondary_proxy_id" => $specified_target_id
        ));

        // Fetch the dataset that will be used by the view
        $dataset = $cbme_progress_api->fetchData();

        // Get the rating scales charts to render
        $charts = array();
        $charts = $dataset["rating_scales_charts"];

        $cbme_trends_view = new Views_CBME_TrendsChart();
        $cbme_trends_view->render(array(
            "charts" => $charts,
            "rating_scales" => $dataset["rating_scales"],
            "preferences" => $PREFERENCES,
            "course_id" => $cbme_progress_api->getCourseID(),
            "course_name" => $cbme_progress_api->getCourseName(),
            "courses" => $cbme_progress_api->getCourses(),
            "trends_query_limit" => $cbme_progress_api->getScaleTrendsAssessmentsLimit(),
            "proxy_id" => $specified_target_id
        ));
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }
}