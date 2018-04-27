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
    $validated_inputs = true;
    $specified_target_id = array();
    $specified_form_id = 0;
    $specified_strip_comments = false;
    $include_commenter_id = false;
    $include_commenter_name = false;
    $generate_pdf = false;
    $pdf_error = false;

    // Get our $_GET variables
    $specified_target_ids = array();
    if (isset($_GET["target_ids"])) {
        if (is_array($_GET["target_ids"])) {
            $specified_target_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["target_ids"]
            );
        }
    } else {
        $validated_inputs = false;
    }

    if (isset($_GET["form_id"]) && ($tmp_input = clean_input($_GET["form_id"], array("trim", "int")))) {
        $specified_form_id = $tmp_input;
    } else {
        $validated_inputs = false;
    }

    if (isset($_GET["strip"]) && ($tmp_input = clean_input($_GET["strip"], array("trim", "int")))) {
        $specified_strip_comments = $tmp_input;
    }

    if (!$specified_strip_comments) {
        if (isset($_GET["commenter-id"]) && ($tmp_input = clean_input($_GET["commenter-id"], array("trim", "int")))) {
            $include_commenter_id = $tmp_input;
        }
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
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["end-date"] = strtotime($tmp_input);
        } else {
            $PROCESSED["end-date"] = null;
        }
    } else {
        $PROCESSED["end-date"] = null;
    }

    $specified_cperiod_ids = array();
    if (isset($_GET["cperiod_ids"])) {
        if (is_array($_GET["cperiod_ids"])) {
            $specified_cperiod_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $_GET["cperiod_ids"]
            );
        }
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
        $distinct_titles = array();
        $specified_distribution_ids = array();
        $specified_associated_record_type = $PROCESSED["associated_record_type"];
        $specified_associated_record_ids = $associated_record_ids;
        $specified_schedule_target_ids = array();
        $specified_eventtype_target_ids = array();
        $full_target_list_details = "";
        $type = "";
        $scope = "self";
        $specified_proxy_id = 0;

        foreach ($specified_target_ids as $target_id) {
            $cperiod = 0;
            $title = "";

            if ($PROCESSED["previous_page"] == "rotations") {
                $schedule = Models_Assessments_Distribution_Schedule::fetchRowByID($target_id);
                if ($schedule) {
                    $specified_distribution_ids[] = $schedule->getAdistributionID();
                    $distribution_target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($schedule->getAdistributionID());

                    if ($distribution_target->getTargetType() == "proxy_id") {
                        $progress_target_ids = Models_Assessments_Progress::fetchAllTargetsByFormIDDistributionID($specified_form_id, $schedule->getAdistributionID());
                        if ($progress_target_ids) {
                            foreach ($progress_target_ids as $progress_target_id)
                            $specified_schedule_target_ids[] = $progress_target_id["target_record_id"];
                        }
                        $type = "proxy_id";
                    } else {
                        $specified_schedule_target_ids[] = $schedule->getScheduleID();
                        $type = "schedule_id";
                    }

                    $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($schedule->getAdistributionID());
                    if ($distribution) {
                        $cperiod = $distribution->getCperiodID();
                        $title = $distribution->getTitle();
                    }
                }
            } else if ($PROCESSED["previous_page"] == "learning-events") {
                $type = "eventtype_id";
                $target = Models_Assessments_Distribution_Eventtype::fetchRowByID($target_id);
                if ($target) {
                    $specified_eventtype_target_ids[] = $target->getEventtypeID();
                    $specified_distribution_ids[] = $target->getAdistributionID();
                    $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($target->getAdistributionID());
                    $event_model = Models_EventType::get($target->getEventtypeID());

                    if ($distribution && $event_model) {
                        $cperiod = $distribution->getCperiodID();
                        $title = $distribution->getTitle() . " " . $event_model->getEventTypeTitle();
                    }
                }

            } else {
                $type = "proxy_id";
                $specified_proxy_id = $target_id;
                break;
            }

            if ($cperiod) {
                $target_cperiod = Models_Curriculum_Period::fetchRowByID($cperiod);
                $cperiod_label = $title;

                if ($target_cperiod) {
                    if ($target_cperiod->getCurriculumPeriodTitle()) {
                        $cperiod_label .= " " . $target_cperiod->getCurriculumPeriodTitle();
                    }

                    if ($target_cperiod->getStartDate() && $target_cperiod->getFinishDate()) {
                        $cperiod_label .= " " . date("Y-m-d", $target_cperiod->getStartDate()) . " - " . date("Y-m-d", $target_cperiod->getFinishDate());
                    }
                }

                $full_target_list_details .= "<li>" . $cperiod_label . "</li>";

                if (!in_array($title, $distinct_titles)) {
                    $distinct_titles[] = $title;
                }
            }
        }

        if (!is_null($PROCESSED["start-date"]) || !is_null($PROCESSED["end-date"])) {
            $full_target_list_details .= "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start-date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end-date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end-date"])) . "</li>";
        }

        if ($PROCESSED["previous_page"] == "faculty") {
            if ($specified_proxy_id) {
                $user = Models_User::fetchRowByID($specified_proxy_id);
                if ($user) {
                    $target_header = $user->getFullname(false);
                }
            }
        } else {
            $target_header = implode(", ", $distinct_titles);
        }

        $specified_form = Models_Assessments_Form::fetchRowByID($specified_form_id);

        if ($target_header != "" && $type != "" && $specified_form) {
            $breadcrumb_title = sprintf($translate->_("Report on %s"), $specified_form->getTitle());
            if ($PROCESSED["previous_page"] == "rotations") {
                $breadcrumb_sub_section = "rotation-evaluations";
                $breadcrumb_sub_title = "Rotation Evaluations";
            } else if ($PROCESSED["previous_page"] == "learning-events") {
                $breadcrumb_sub_section = "learning-event-evaluations";
                $breadcrumb_sub_title = "Learning Event Evaluations";
            } else {
                $breadcrumb_sub_section = "faculty-evaluations";
                $breadcrumb_sub_title = "Faculty Evaluations";
            }
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/reports?section=$breadcrumb_sub_section", "title" => $translate->_($breadcrumb_sub_title));
            $BREADCRUMB[] = array("url" => "", "title" => html_encode($breadcrumb_title));
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

            // Create the reporting utility object
            if ($PROCESSED["previous_page"] == "rotations") {
                $construction = array(
                    "target_value" => $specified_schedule_target_ids,
                    "target_type" => $type,
                    "target_scope" => $scope,
                    "form_id" => $specified_form_id,
                    "adistribution_id" => $specified_distribution_ids,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );
            } else if ($PROCESSED["previous_page"] == "learning-events") {
                $construction = array(
                    "target_value" => $specified_eventtype_target_ids,
                    "target_type" => $type,
                    "target_scope" => $scope,
                    "form_id" => $specified_form_id,
                    "adistribution_id" => $specified_distribution_ids,
                    "associated_record_type" => $specified_associated_record_type,
                    "associated_record_ids" => $specified_associated_record_ids,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );
            } else {
                $construction = array(
                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                    "target_value" => $specified_proxy_id,
                    "target_type" => "proxy_id",
                    "form_id" => $specified_form_id,
                    "cperiod_id" => $specified_cperiod_ids,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );
            }

            $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
            $report_data = $reporting_utility->generateReport(); // generate (or fetch from cache) the report

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
                                "title"     => $event->getEventTitle(),
                                "date"      => date("Y-m-d", $event->getEventStart()),
                                "teachers"  => $teachers
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

            // Generate header html
            $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
            $header_html = $header_view->render(
                array(
                    "target_name" => $target_header,
                    "form_name" => $specified_form->getTitle(),
                    "enable_pdf_button" => true,
                    "subheader_html" => $associated_record_subheaders,
                    "pdf_configured" => $pdf_configured,
                    "generate_pdf" => $generate_pdf,
                    "pdf_generation_url" => $pdf_generator->buildURI("/admin/assessments/reports", $_SERVER["REQUEST_URI"] . "&generate-pdf=1"),
                    "list_info" => $full_target_list_details,
                    "description" => $PROCESSED["description"]
                ),
                false
            );

            // Generate the report HTML
            $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
            $report_html = $report_view->render(
                array(
                    "report_data" => $report_data,
                    "strip_comments" => $specified_strip_comments,
                    "include_commenter_id" => $include_commenter_id,
                    "include_commenter_name" => $include_commenter_name,
                    "is_evaluation" => true,
                    "additional_statistics" => $PROCESSED["include_statistics"],
                    "include_positivity" => $PROCESSED["include_positivity"]
                ),
                false
            );

            // Output in PDF format, or render to screen
            if ($generate_pdf && $pdf_configured) {

                // Create a filename based on the report
                $pdf_title = "{$specified_form->getTitle()} {$target_header}";

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
                    $error_url = $pdf_generator->buildURI("/admin/assessments/reports", $_SERVER["REQUEST_URI"]);
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
            add_error($translate->_("Please ensure you have specified a valid user and form."));
            echo display_error();
        }
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }
}