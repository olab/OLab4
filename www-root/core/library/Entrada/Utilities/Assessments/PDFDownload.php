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

class Entrada_Utilities_Assessments_PDFDownload extends Entrada_Assessments_Base {

    public $pdf_generator;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
        $this->pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
    }

    /**
     * Generate HTML for all applicable assessments.
     *
     * @param int $dassessment_id
     * @param int $aprogress_id
     * @return array|bool
     */
    private function renderAssessments($dassessment_id, $aprogress_id = null) {
        $all_rendered_assessments = array();

        if (!$this->validateActor()) {
            return false;
        }

        $assessment_api = new Entrada_Assessments_Assessment($this->buildActorArray());
        $assessment_data = $assessment_api->fetchAssessmentData($dassessment_id, $aprogress_id);
        if (empty($assessment_data)) {
            return false;
        }

        $form_id = $assessment_data["assessment"]["form_id"];

        // Template-specific logo for header
        $cache = new Entrada_Utilities_Cache();
        $tpl_organisation_id = $assessment_data["assessment"]["organisation_id"];
        if ($tpl_organisation = Models_Organisation::fetchRowByID($tpl_organisation_id)) {
            $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$tpl_organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$tpl_organisation_id}", "image/png");
        }

        // Check if the current user is the approver for this assessment
        $approval_data = $assessment_api->getAssessmentApprovalData();
        $viewer_is_approver = false;
        if (!empty($approval_data)) {
            $viewer_is_approver = ($this->actor_proxy_id == $approval_data["approver_proxy_id"]);
        }

        // Prepare event timeframe related data
        $event_name = null;
        $timeframe_start = null;
        $timeframe_end = null;

        if ($assessment_data["assessment"]["associated_record_type"] == "event_id") {
            if (!empty($assessment_data["associated_record"])) {
                $event_name = $assessment_data["associated_record"]["associated_entity_name"];
                $timeframe_strings = Entrada_Utilities_Assessments_DistributionLearningEvent::buildTimeframeStrings(
                    $assessment_data["associated_record"]["start_date"],
                    $assessment_data["associated_record"]["end_date"]
                );
                $timeframe_start = $timeframe_strings["timeframe_start"];
                $timeframe_end = $timeframe_strings["timeframe_end"];
            }
        }

        // Determine what to render; find all the progress for this target (there can be many), or just use the blank target
        $applicable_targets = array();
        if ($aprogress_id) {
            $current_target = $assessment_api->getCurrentAssessmentTarget();
            if (empty($current_target)) {
                return false;
            }
            $applicable_targets = array(
                array_merge(
                    array("aprogress_id" => $aprogress_id, "target_value" => $current_target["target_record_id"]),
                    $current_target
                )
            );
        } else {
            foreach ($assessment_data["targets"] as $target) {
                $found = false;
                foreach ($assessment_data["progress"] as $progress) {
                    if ($progress["target_type"] == $target["target_type"]
                        && $progress["target_record_id"] == $target["target_value"]
                    ) {
                        $target_with_progress = $target;
                        $target_with_progress["aprogress_id"] = $progress["aprogress_id"];
                        $applicable_targets[] = $target_with_progress;
                        $found = true;
                    }
                }
                if (!$found) {
                    $target["aprogress_id"] = null;
                    $applicable_targets[] = $target;
                }
            }
        }

        global $ENTRADA_USER, $ENTRADA_ACL;
        $globals_set = (isset($ENTRADA_ACL) && !empty($ENTRADA_ACL) && isset($ENTRADA_USER) && !empty($ENTRADA_USER));

        // Admin assessment override.
        $assessment_visibility_override_primary = $globals_set && ($ENTRADA_USER->getActiveGroup() == "medtech" && $ENTRADA_USER->getActiveRole() == "admin") || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

        // For all of the applicable targets, render their forms
        foreach ($applicable_targets as $current_target) {

            // Permissions checks for secondary override.
            $assessment_visibility_override_secondary = false;
            if ($current_target["target_type"] == "proxy_id" && $globals_set) {
                if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($current_target["target_value"]), "read") ||
                    $ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($current_target["target_value"]), "read")
                ) {
                    $assessment_visibility_override_secondary = true;
                }
            }

            if (!$assessment_api->canUserViewAssessment($assessment_visibility_override_primary, $assessment_visibility_override_secondary, $current_target["target_type"], $current_target["target_value"])) {
                continue;
            }

            $atarget_id = $current_target["atarget_id"];
            $target_aprogress_id = $current_target["aprogress_id"];
            $target_id = $current_target["target_value"];
            $target_type = $current_target["target_type"];
            $target_name = $current_target["target_name"];
            $completed_date = null;
            if ($target_aprogress_id && !empty($assessment_data["progress"])) {
                foreach ($assessment_data["progress"] as $progress_record) {
                    if ($progress_record["aprogress_id"] == $target_aprogress_id
                        && $progress_record["progress_value"] == "complete"
                    ) {
                        $completed_date = $progress_record["updated_date"];
                    }
                }
            }

            // Cache the target image for the header.
            if ($target_type == "proxy_id") {
                $cache->cacheImage(STORAGE_USER_PHOTOS . "/{$target_id}-official", $target_id);
            }

            $forms_api = new Entrada_Assessments_Forms(
                $this->buildActorArray(array(
                        "dassessment_id" => $dassessment_id,
                        "aprogress_id" => $target_aprogress_id,
                        "form_id" => $form_id
                    )
                )
            );
            $forms_api->clearInternalStorage();
            $form_data = $forms_api->fetchFormData($form_id);
            if (empty($form_data)) {
                return false;
            }

            // Generate HTML for the form metadata
            $assessment_form_meta = new Views_Assessments_Assessment_MetaData();
            $meta_data_html = $assessment_form_meta->render($form_data, false);

            // Generate the form HTML (the body of the assessment)
            $form_view_options = array(
                "form_id" => $form_id,
                "disabled" => false,
                "elements" => $form_data["elements"],
                "progress" => $form_data["progress"],
                "rubrics" => $form_data["rubrics"],
                "aprogress_id" => $target_aprogress_id,
                "public" => true,
                "form_mutators" => $assessment_api->buildFormMutatorList(),
                "objectives" => $assessment_api->getSelectedFormElementsObjectiveHierarchy() // ADRIAN-TODO: return to this
            );
            $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
            $rendered_assessment_html = $form_view->render($form_view_options, false);

            // Generate feedback HTML if required
            // TODO: Move this logic to a standard place so that it doesn't get duplicated here and on the assessment pages
            $feedback_html = "";
            if ($assessment_api->isAssessmentFeedbackRequired()) {
                $feedback_data = $assessment_api->getAssessmentFeedbackForTarget($target_id, $target_type);
                $feedback_options = array();
                $feedback_options["actor_id"] = $this->actor_proxy_id;
                $feedback_options["actor_type"] = "internal";
                $feedback_options["assessor_id"] = $assessment_data["assessor"]["assessor_id"];
                $feedback_options["assessor_type"] = $assessment_data["assessor"]["type"];
                $feedback_options["assessment_complete"] = $assessment_api->isAssessmentCompleted();
                $feedback_options["feedback_actor_is_target"] = false;
                if ($target_type == "proxy_id" &&
                    $target_id == $this->actor_proxy_id
                ) {
                    $feedback_options["feedback_actor_is_target"] = true;
                }
                // Determine at what state the feedback window is in, and what data to display.
                if (empty($feedback_data)) {
                    $feedback_pending = true;
                } else {
                    if ($feedback_options["feedback_actor_is_target"]
                        && $feedback_data["target_progress_value"] != "complete"
                    ) {
                        $feedback_pending = true;
                    } else {
                        $feedback_pending = false;
                    }
                    $feedback_options["assessor_feedback"] = $feedback_data["assessor_feedback"];
                    $feedback_options["target_feedback"] = $feedback_data["target_feedback"];
                    $feedback_options["comments"] = $feedback_data["comments"];
                    $feedback_options["target_progress_value"] = $feedback_data["target_progress_value"];
                }
                if (!$feedback_pending && $feedback_data["target_progress_value"] == "complete") {
                    $feedback_options["include_preceptor_label"] = true;
                }
                if ($viewer_is_approver
                    || ($this->actor_proxy_id == $assessment_data["assessor"]["assessor_id"]
                        && $assessment_data["assessor"]["type"] == "internal")
                ) {
                    // The assessor (and approver) never get to see target comments
                    $feedback_options["hide_target_comments"] = true;
                }
                $feedback_options["edit_state"] = "readonly";
                $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                $feedback_html = $feedback_view->render($feedback_options, false);
            }

            // Generate delivery info header for the PDF
            $delivery_info_view = new Views_Assessments_Sidebar_DeliveryInfo(); // ADRIAN-TODO: This needs refactor to not use models as options
            $header_html = $delivery_info_view->render(
                array(
                    "is_pdf" => true,
                    "assessment_record" => new Models_Assessments_Assessor($assessment_data["assessment"]),
                    "distribution" => new Models_Assessments_Distribution($assessment_data["distribution"]["distribution_record"]),
                    "distribution_schedule" => new Models_Assessments_Distribution_Schedule($assessment_data["distribution"]["distribution_schedule"]),
                    "target_name" => $target_name,
                    "assessor_name" => $assessment_data["assessor"]["full_name"],
                    "completed_date" => $completed_date,
                    "event_name" => $event_name,
                    "timeframe_start" => $timeframe_start,
                    "timeframe_end" => $timeframe_end
                ),
                false
            );

            $all_rendered_assessments[] = array(
                "aprogress_id" => $target_aprogress_id,
                "dassessment_id" => $assessment_api->getDassessmentID(),
                "organisation_id" => $this->actor_organisation_id,
                "atarget_id" => $atarget_id,
                "target_name" => $target_name,
                "target_id" => $target_id,
                "target_type" => $target_type,
                "assessor_name" => $assessment_data["assessor"]["full_name"],
                "form_title" => $form_data["form"]["title"],
                "header_html" => $header_html,
                "metadata_html" => $meta_data_html,
                "form_html" => $rendered_assessment_html,
                "feedback_html" => $feedback_html,
                "error_redirect_path" => $assessment_api->getAssessmentURL($target_id, $target_type) // ??
            );
        }
        return $all_rendered_assessments;
    }

    /**
     * Generate a cookie that the calling JS will use to know whether the download is finished or not.
     *
     * @param $value
     * @param string $name
     */
    private function setDownloadCookie($value, $name = "assessment-pdf-download") {
        setcookie($name, $value, time() + 3600, "/"); // an hour
    }

    /**
     * Generate tasks as ZIP or PDF and send them to the client browser.
     *
     * @param array $tasks
     * @param string $download_token
     * @param string $format
     * @return bool
     */
    public function sendAssessmentTasks($tasks, $download_token, $format = "pdf") {
        global $translate;

        //$error_url = $assessment_pdf->buildURI("/assessments/assessment/", $_SERVER["REQUEST_URI"]);
        if (!$this->pdf_generator->configure()) {
            //echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
            application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
            return false;
        }

        $all_rendered_assessments = array();

        // In all cases, we want to generate the assessment HTML.
        // After that's done, we package it and send it off depending on the format parameter.
        foreach ($tasks as $task) {
            $dassessment_id = $task["dassessment_id"] ? (int)$task["dassessment_id"] : null;
            $aprogress_id = $task["aprogress_id"] ? (int)$task["aprogress_id"] : null;
            $task_assessments_rendered = $this->renderAssessments($dassessment_id, $aprogress_id);
            if (!empty($task_assessments_rendered)) {
                $all_rendered_assessments = array_merge($all_rendered_assessments, $task_assessments_rendered);
            }
        }

        if (empty($all_rendered_assessments)) {

            // No assessments
            return false;

        } else if (count($all_rendered_assessments) == 1) {

            // Send the single assessment in PDF format
            $rendered_assessment = end($all_rendered_assessments);
            $compiled_assessment_html = $rendered_assessment["metadata_html"] . $rendered_assessment["form_html"] . $rendered_assessment["feedback_html"];
            $error_url = ENTRADA_URL . "/assessments/?pdf-error=true";

            // Create the PDF-boilerplated assessment HTML
            $html = $this->pdf_generator->generateAssessmentHTML(
                $compiled_assessment_html,
                $rendered_assessment["organisation_id"],
                $rendered_assessment["form_title"],
                $rendered_assessment["header_html"],
                $rendered_assessment["target_id"]
            );
            $timestamp = strftime("%Y%m%d", time());
            $file_title = "{$rendered_assessment["target_name"]} {$translate->_("assessment")} {$timestamp}.pdf";
            $this->setDownloadCookie($download_token);
            $result = $this->pdf_generator->send($file_title, $html);
            if (!$result) {
                // Unable to send PDF, so redirect away from this page and show an error.
                ob_clear_open_buffers();
                //$error_url = str_replace("generate-pdf=", "pdf-error=", $error_redirect_path);
                Header("Location: $error_url");
                exit();
            }

        } else {

            if ($format == "zip") {
                // Generate all the PDF files, then combine them into a zip
                $timestamp = strftime("%Y%m%d", time());
                $zip_file_path = $this->pdf_generator->buildFilename("assessment_tasks_{$timestamp}", ".zip", true, true);
                $assessment_pdf_filenames = array();
                $zip = new ZipArchive();
                if ($zip->open($zip_file_path, ZipArchive::CREATE) === true) {
                    foreach ($all_rendered_assessments as $rendered_assessment) {
                        $compiled_assessment_html = $rendered_assessment["metadata_html"] . $rendered_assessment["form_html"] . $rendered_assessment["feedback_html"];
                        $html = $this->pdf_generator->generateAssessmentHTML(
                            $compiled_assessment_html,
                            $rendered_assessment["organisation_id"],
                            $rendered_assessment["form_title"],
                            $rendered_assessment["header_html"],
                            $rendered_assessment["target_id"]
                        );
                        $ordinal = 0;
                        $file_title = "{$rendered_assessment["target_name"]} {$translate->_("assessment")} {$timestamp}";
                        while (in_array($file_title, $assessment_pdf_filenames)) {
                            $ordinal++;
                            $file_title = "{$rendered_assessment["target_name"]} {$translate->_("assessment")} {$timestamp} $ordinal";
                        }
                        $assessment_pdf_filenames[] = $file_title;
                        $zip->addFromString(
                            $this->pdf_generator->buildFilename($file_title, ".pdf"),
                            $this->pdf_generator->toString($html)
                        );
                    }
                    $zip->close();
                }
                $zip_file_name = basename($zip_file_path);
                $this->setDownloadCookie($download_token);
                ob_clear_open_buffers();
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=" . $zip_file_name);
                header("Content-Length: " . filesize($zip_file_path));
                readfile($zip_file_path);
                unlink($zip_file_path);
                exit;

            } else {

                // Roll all the assessments into one PDF file
                $timestamp = strftime("%Y%m%d", time());
                $aggregate_file_path = $this->pdf_generator->buildFilename("assessment tasks {$timestamp}", ".pdf", true, false);
                $pages = array();
                foreach ($all_rendered_assessments as $rendered_assessment) {
                    $compiled_assessment_html = $rendered_assessment["metadata_html"] . $rendered_assessment["form_html"] . $rendered_assessment["feedback_html"];
                    $html = $this->pdf_generator->generateAssessmentHTML(
                        $compiled_assessment_html,
                        $rendered_assessment["organisation_id"],
                        $rendered_assessment["form_title"],
                        $rendered_assessment["header_html"],
                        $rendered_assessment["target_id"]
                    );
                    if ($html) {
                        $pages[] = $html;
                    }
                }
                $added = false;
                foreach ($pages as $page) {
                    $added = true;
                    $this->pdf_generator->addHTMLPage($page, array(), $this->pdf_generator->getHtmlTypeConst());
                }
                $result = false;
                if ($added) {
                    $this->setDownloadCookie($download_token);
                    $result = $this->pdf_generator->send($aggregate_file_path, null, false);
                }
                if (!$result) {
                    // Unable to send PDF, so redirect away from this page and show an error.
                    ob_clear_open_buffers();
                    // ADRIAN-TODO: Fix replacement
                    //$error_url = str_replace("generate-pdf=", "pdf-error=", "");
                    $error_url = ENTRADA_URL . "/assessments/?pdf-error=true";
                    Header("Location: $error_url");
                    exit();

                }
            }
        }
        return true;
    }

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
                    if (!$assessment_record) {
                        $assessment_record = Models_Assessments_Assessor::fetchRowByID($distribution_data["dassessment_id"], time());
                    }

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
                            ), false
                        );

                        // Instantiate utility object, with optional parameters.
                        $forms_api = new Entrada_Assessments_Forms(array(
                                "form_id" => $form->getID(),
                                "adistribution_id" => @$PROCESSED["adistribution_id"] ? $PROCESSED["adistribution_id"] : null,
                                "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                "dassessment_id" => @$PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "disable_internal_storage" => true
                            )
                        );

                        $forms_api->clearStorage();

                        // Fetch form data using those params
                        $form_data = $forms_api->fetchFormData();

                        // Render the form in PDF mode
                        $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
                        $assessment_html = $form_view->render(
                            array(
                                "form_id" => $form->getID(),
                                "disabled" => false,
                                "elements" => $form_data["elements"],
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

                            //$approver_model = new Models_Assessments_Distribution_Approver();
                            //$approver = $approver_model->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $distribution_data["adistribution_id"]);

                            // Append the feedback, if it's specified
                            $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                            $assessment_html .= $feedback_view->render(
                                array(
                                    "actor_id" => $ENTRADA_USER->getActiveId(),
                                    "actor_type" => "internal",
                                    "feedback_actor_is_target" => false,
                                    "assessment_complete" => $progress_record ? $progress_record->getProgressValue() == "complete" ? true : false : false,
                                    "assessor_id" => $task_data["assessor_value"],
                                    "assessor_type" => $assessment_record->getAssessorType(),
                                    "assessor_feedback" => $feedback_record ? $feedback_record->getAssessorFeedback() : null,
                                    "hide_target_comments" => false,
                                    "target_feedback" => $feedback_record ? $feedback_record->getTargetFeedback() : null,
                                    "target_progress_value" => $feedback_record ? $feedback_record->getTargetProgressValue() : null,
                                    "comments" => $feedback_record ? $feedback_record->getComments() : null
                                ),
                                false
                            );
                        }

                        $html = $assessment_pdf->generateAssessmentHTML($assessment_html, $ENTRADA_USER->getActiveOrganisation(), $form->getTitle(), $header, $task_data["target_id"], isset($PROCESSED["description"]) ? $PROCESSED["description"] : false);
                        $assessment_pdf->addHTMLPage($html);
                    }
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
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "disable_internal_storage" => true
                            )
                        );

                        $forms_api->clearStorage();

                        // Fetch form data using those params
                        $form_data = $forms_api->fetchFormData();

                        // Render the form in PDF mode
                        $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
                        $assessment_html = $form_view->render(
                            array(
                                "form_id" => $form->getID(),
                                "disabled" => false,
                                "elements" => $form_data["elements"],
                                "progress" => $form_data["progress"],
                                "rubrics" => $form_data["rubrics"],
                                "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                "public" => true,
                                "objectives" => @$PROCESSED["objectives"] ? @$PROCESSED["objectives"] : null
                            ),
                            false
                        );

                        if ($assessment_html && $distribution->getFeedbackRequired() && $task_data["dassessment_id"]) {

                            $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($task_data["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $task_data["target_id"]);

                            /*
                            $approver_model = new Models_Assessments_Distribution_Approver();
                            $approver = $approver_model->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $distribution_data["adistribution_id"]);
                            $hide_from_approver = ($approver) ? true : false;
                            */

                            // Append the feedback, if it's specified
                            $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                            $assessment_html .= $feedback_view->render(
                                array(
                                    "actor_id" => $ENTRADA_USER->getActiveId(),
                                    "actor_type" => "internal",
                                    "feedback_actor_is_target" => false,
                                    "assessment_complete" => $progress_record ? $progress_record->getProgressValue() == "complete" ? true : false : false,
                                    "assessor_id" => $task_data["assessor_value"],
                                    "assessor_type" => $assessment_record->getAssessorType(),
                                    "assessor_feedback" => $feedback_record ? $feedback_record->getAssessorFeedback() : null,
                                    "hide_target_comments" => false,
                                    "target_feedback" => $feedback_record ? $feedback_record->getTargetFeedback() : null,
                                    "target_progress_value" => $feedback_record ? $feedback_record->getTargetProgressValue() : null,
                                    "comments" => $feedback_record ? $feedback_record->getComments() : null
                                ),
                                false
                            );
                        }

                        if ($assessment_html) {
                            $html = $assessment_pdf->generateAssessmentHTML($assessment_html, $ENTRADA_USER->getActiveOrganisation(), $form->getTitle(), $header, $task_data["target_id"], @$PROCESSED["description"]);

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

    public function prepareDownloadMultipleReports($report_html, $form_title, $target_names, $include_comments = false, $include_commenter_id = false) {
        global $translate;
        $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $file_path = $pdf_generator->buildFilename("Reports", ".zip", true, true);
        $report_pdf_filenames = array();

        if ($pdf_generator->configure()) {
            $zip = new ZipArchive();
            if ($zip->open($file_path, ZipArchive::CREATE) === true) {
                $ctr = 0;
                foreach ($report_html as $report) {
                    $title = (is_array($form_title) ? $form_title[$ctr] : $form_title);
                    $pdf_title = "{$title} {$target_names[$ctr]}";
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
                    $pdf_filename = $pdf_generator->buildFilename($pdf_title, ".pdf", true);
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

    public function prepareDownloadSingleCompletionReport($user_data, $date_range, $include_average_delivery_date, $description = null, $overall_average_completion = false, $overall_rotation_average_completion = false) {
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

            $report_html .= "    <tr>";
            $report_html .= "    <td colspan='3'>" . $translate->_("Overall Average") . "</td>";
            $report_html .= "    <td>" . $overall_rotation_average_completion . "</td>";
            if ($include_average_delivery_date) :
                $report_html .= "    <td>" . $overall_average_completion . "</td>";
            endif;
            $report_html .= "    </tr>";

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

    public function prepareDownloadSingleRotationLeaveReport($leave, $description = null, $target_header, $target_names, $start_date, $end_date, $comments = null) {
        global $translate, $ENTRADA_USER;
        $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
        $pdf_configured = $pdf_generator->configure();
        $generate_pdf = true;
        $pdf_error = false;


        $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
        $header_html = $header_view->render(
            array(
                "target_name" => $target_header,
                "enable_pdf_button" => false,
                "pdf_configured" => $pdf_configured,
                "generate_pdf" => $generate_pdf,
                "pdf_generation_url" => $pdf_generator->buildURI("/admin/assessments/reports", $_SERVER["REQUEST_URI"] . "&generate-pdf=1"),
                "list_info" => "",
                "use_leave_title" => true
            ),
            false
        );

        $leave_view = new Views_Assessments_Reports_LeaveReport();
        $report_html = $leave_view->render(
            array(
                "generate_pdf" => $generate_pdf,
                "report_data" => $leave,
                "target_names" => $target_names,
                "comments" => $comments,
                "start-date" => $start_date,
                "end-date" => $end_date,
                "description" => $description
            ),
            false);



        if ($pdf_configured) {

            $cache = new Entrada_Utilities_Cache();
            $organisation = Models_Organisation::fetchRowByID($ENTRADA_USER->getActiveOrganisation());
            if ($organisation) {
                $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$ENTRADA_USER->getActiveOrganisation()}", "image/png");
            }

            $pdf_filename = $pdf_generator->buildFilename("LeaveTracking".$target_header, ".pdf");
            $all_html = $header_html;
            $all_html .= $report_html;

            if (!$pdf_generator->send($pdf_filename, $pdf_generator->generateAssessmentHTML($all_html, $ENTRADA_USER->getActiveOrganisation(), $translate->_("Rotation Leave Report"), null, false, $description))){
                ob_clear_open_buffers();
                $error_url = $pdf_generator->buildURI("/admin/assessments/reports", $_SERVER["REQUEST_URI"]);
                $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                Header("Location: $error_url");
                die();
            }

        } else {
            if ($pdf_error) {
                add_error($translate->_("Unable to create PDF file. Please try again later."));
                echo display_error();
            }
        }
    }
}