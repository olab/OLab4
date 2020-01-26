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
 * Public assessment form.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a class=\"user-email\" href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $translate->_("Assessment Task"));
    $JAVASCRIPT_TRANSLATIONS[] = "current_target = '" . $translate->_("Currently Assessing") . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "hide_assessment_error = '" . $translate->_("Please enter a comment.") . "';";

    $render_page = true;                      // Render the page only when this is true.
    $render_sidebar = false;                  // Sidebar is not rendered by default (only rendered when there's a valid target or assessor)
    $redirecting = false;                     // Is the page going to redirect?
    $assessment_visibility_override = false;  // Override default visibility if certain conditions are met (ACL)
    $assessment_data = array();               // The entire assessment dataset, fetched from assessment API
    $form_data = array();                     // The form dataset, fetched from forms API
    $current_progress = array();              // The progress (including responses) of the current record (if one exists). Derived from the pre-fetched assessment dataset.
    $current_target = array();                // An array describing the current target (target type, name, scope (internal/external)). Derived from the pre-fetched assessment dataset.

    $user_is_admin = ($ENTRADA_USER->getActiveGroup() == "medtech" && $ENTRADA_USER->getActiveRole() == "admin") || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

    $PROCESSED["pdf_error"] = false;
    if (array_key_exists("pdf-error", $_GET)) {
        $PROCESSED["pdf_error"] = true;
    }

    $PROCESSED["adistribution_id"] = null;
    $PROCESSED["objectives"] = array();

    $PROCESSED["aprogress_id"] = null;
    if (array_key_exists("aprogress_id", $_POST) && $tmp_input = clean_input($_POST["aprogress_id"], array("trim", "int"))) {
        $PROCESSED["aprogress_id"] = $tmp_input;
    } else if (array_key_exists("aprogress_id", $_GET) && $tmp_input = clean_input($_GET["aprogress_id"], array("trim", "int"))) {
        $PROCESSED["aprogress_id"] = $tmp_input;
    }

    /**
     * Either (atarget_id) OR (target_record_id and target_type) can be specified.
     * If neither, then the latest atarget_id is used to determine target_record_id and target_type (derived from assessment record).
     *
     * Note that target record ID and target type are required BEFORE an aprogress_id. Specifying an aprogress_id, but not specifying an
     * atarget_id (or target record id/type) may produce an error ("unable to find progress") since the target referenced in the
     * progress record may not match the current target (a target derived from the assessment -- the first target).
     **/

    $PROCESSED["atarget_id"] = null;
    if (array_key_exists("atarget_id", $_POST) && $tmp_input = clean_input($_POST["atarget_id"], array("trim", "int"))) {
        $PROCESSED["atarget_id"] = $tmp_input;
    } else if (array_key_exists("atarget_id", $_GET) && $tmp_input = clean_input($_GET["atarget_id"], array("trim", "int"))) {
        $PROCESSED["atarget_id"] = $tmp_input;
    }

    $PROCESSED["target_record_id"] = null;
    if (array_key_exists("target_record_id", $_POST) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
        $PROCESSED["target_record_id"] = $tmp_input;
    } else if (array_key_exists("target_record_id", $_GET) && $tmp_input = clean_input($_GET["target_record_id"], array("trim", "int"))) {
        $PROCESSED["target_record_id"] = $tmp_input;
    }

    $PROCESSED["target_type"] = null;
    if (array_key_exists("target_type", $_POST) && $tmp_input = clean_input($_POST["target_type"], array("trim", "striptags"))) {
        $PROCESSED["target_type"] = $tmp_input;
    } else if (array_key_exists("target_type", $_GET) && $tmp_input = clean_input($_GET["target_type"], array("trim", "striptags"))) {
        $PROCESSED["target_type"] = $tmp_input;
    }

    $PROCESSED["dassessment_id"] = null;
    if (array_key_exists("dassessment_id", $_POST) && $tmp_input = clean_input($_POST["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;
    } else if (array_key_exists("dassessment_id", $_GET) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;
    }
    if (!$PROCESSED["dassessment_id"]) {
        add_error($translate->_("No assessment ID specified."));
        $render_page = false;
    }

    /**
     * Instantiate the assessment API and fetch data.
     */
    if (!has_error() && $render_page) {

        // Instantiate our assessments API
        $assessment_api = new Entrada_Assessments_Assessment(
            array(
                "dassessment_id" => $PROCESSED["dassessment_id"],
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "actor_group" => $ENTRADA_USER->getActiveGroup(),
                "actor_type" => "proxy_id",
                "actor_scope" => "internal",
                "fetch_deleted_targets" => true,
                "fetch_form_data" => false // We will fetch the form data via the forms API.
            )
        );
        $assessment_data = $assessment_api->fetchAssessmentData();
        if (empty($assessment_data)) {
            add_error($translate->_("Unable to fetch assessment data."));
            $render_page = false;
        } else {
            /**
             * Configure the assessment object in order to render the view. This updates PROCESSED with missing data, if necessary.
             */
            if ($assessment_api->configureForRender($PROCESSED)) {
                // Pull the current target and assessment progress (progress and responses) from the current dataset.
                // NOTE: $current_progress contains "target_type" and "target_scope"; target type is
                // specific, e.g. proxy_id or schedule_id etc, whereas scope is general, "internal" or "external"
                $current_target = $assessment_api->getCurrentAssessmentTarget($PROCESSED["target_record_id"], $PROCESSED["target_type"]);
                $current_progress = $assessment_api->getCurrentAssessmentProgress(); // OK to be empty

            } else {
                foreach ($assessment_api->getErrorMessages() as $error_message) {
                    add_error($error_message);
                    $render_page = false;
                }
            }
        }
    }

    /**
     * Determine assessment visibility override (apply ACL).
     */
    if (!has_error() && $render_page) {

        $assessment_visibility_override_primary = false;
        $assessment_visibility_override_secondary = false;
        
        // Check if the target is one of "my learners"
        if ($current_target["target_type"] == "proxy_id") {
            if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($current_target["target_record_id"]), "read")
                || $ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($current_target["target_record_id"]), "read")
            ) {
                if (empty($current_progress)|| $current_progress["progress_value"] == "complete") {
                    $assessment_visibility_override_secondary = true;
                }
            }
        }
        // If the current actor is an academic advisor or competency comittee member to the assessor, then grant view access to the assessment if it is not in progress.
        if ($assessment_data["assessor"]["type"] == "internal") {
            if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($assessment_data["assessor"]["assessor_id"]), "read")) {
                if (empty($current_progress)|| $current_progress["progress_value"] == "complete") {
                    $assessment_visibility_override_secondary = true;
                } else {
                    // Turn off the visibility if there's progress, even if it was previously set (via target check)
                    $assessment_visibility_override_secondary = false;
                }
            } else if ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($assessment_data["assessor"]["assessor_id"]), "read")) {
                if (empty($current_progress)
                    || (array_key_exists("shortname", $assessment_data["assessment_type"])
                        && $assessment_data["assessment_type"]["shortname"] == "cbme"
                        && $current_progress["progress_value"] == "complete"
                    )
                ) {
                    $assessment_visibility_override_secondary = true;
                } else {
                    // Turn off the visibility if there's progress, even if it was previously set (via target check)
                    $assessment_visibility_override_secondary = false;
                }
            }
        }
        // We can view in all cases when admin.
        if ($user_is_admin) {
            $assessment_visibility_override_primary = true;
        }
    }

    /**
     * Handle post submission, if any.
     */
    if (!has_error() && $render_page) {

        // Insantiate forms API object with the appropriate progress and form IDs.
        $forms_api = new Entrada_Assessments_Forms(
            array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "form_id" => $assessment_data["meta"]["form_id"],
                "aprogress_id" => $PROCESSED["aprogress_id"]
            )
        );

        $form_data = $forms_api->fetchFormData();
        if (empty($form_data)) {
            foreach ($forms_api->getErrorMessages() as $error_message) {
                add_error($error_message);
            }
            $render_page = false;
        }

        switch ($STEP) {

            /**
             * Handle approval submission request.
             */
            case 3:

                if ($assessment_api->handlePostedApprovalSubmission($_POST, true)) {
                    $success_view = new Views_Message_Redirect();
                    $success_view->render(
                        array(
                            "message_type" => "success",
                            "message_text" => $translate->_("You have successfully reviewed this assessment task, thank you."),
                            "redirection_url" => ENTRADA_URL . "/assessments",
                            "add_click_here_message" => true,
                            "add_redirect_message" => true
                        )
                    );
                    $render_page = false;
                    $redirecting = true;
                }
                break;

            /**
             * Handle assessment responses submission (the full set of POST'd data).
             * This happens if someone clicks "save as draft" or "submit".
             */
            case 2:

                /**
                 * Check if "Submit" button was pressed (they can POST in "Save as Draft" mode, which doesn't have the "submit_form" set)
                 */
                if (isset($_POST["submit_form"]) && !empty($_POST["submit_form"])) {
                    $finish_assessment = true;
                } else {
                    $finish_assessment = false;
                }

                /**
                 * Set parameters for submission.
                 */
                $submission_options = array(
                    "send_flagging_notifications" => $ENTRADA_SETTINGS->fetchValueByShortname(
                        "flagging_notifications",
                        $assessment_data["assessment"]["organisation_id"]
                    ),
                    "target_record_id" => $PROCESSED["target_record_id"],
                    "target_type" => $PROCESSED["target_type"],
                    "atarget_id" => $PROCESSED["atarget_id"],
                    "aprogress_id" => $PROCESSED["aprogress_id"],
                    "adistribution_id" => $PROCESSED["adistribution_id"]
                );

                /**
                 * Set the options and parameters for the applicable hooks.
                 */
                $hook_options = array(
                    "assessment_method" => array(
                        "enabled" => true
                    ),
                    "completion_statistic" => array(
                        "enabled" => true,
                        "module" => $MODULE,
                        "submodule" => $SUBMODULE
                    ),
                    "progress_time_statistic" => array(
                        "enabled" => true
                    ),
                    "notify_pending" => array(
                        "enabled" => true
                    ),
                    "notify_flag_severity_levels" => array(
                        "enabled" => true,
                        "notify_directors" => $ENTRADA_SETTINGS->fetchValueByShortname(
                            "flag_severity_notify_directors_and_coordinators",
                            $assessment_data["assessment"]["organisation_id"]
                        ),
                        "form_objectives" => $form_data["objectives"]
                    ),
                    "pin_validation" => array(
                        "enabled" => true,
                    ),
                    "feedback" => array(
                        "enabled" => true
                    )
                );

                if ($assessment_api->handlePostedAssessmentSubmission($_POST, $submission_options, $hook_options, $user_is_admin, false, false, $PROCESSED["target_type"], $PROCESSED["target_record_id"])) {
                    // Successful post, redirect away from this page.
                    $action_taken = $finish_assessment
                        ? $translate->_("completed")
                        : $translate->_("saved");
                    $redirect_place = $translate->_("the Dashboard");
                    $url = ENTRADA_URL . "/dashboard";
                    if ($assessment_data["assessment"]["adistribution_id"]) {
                        $redirect_place = $translate->_("your assessments page");
                        $url = ENTRADA_URL . "/assessments";
                    }
                    //If we came from the record assessment interface, return back to it.
                    if ($assessment_data["assessment_method"]["shortname"] == "faculty_triggered_assessment") {
                        if (is_array($assessment_data["assessment"]["assessment_method_data"]) && array_key_exists("referrer", $assessment_data["assessment"]["assessment_method_data"]) && $assessment_data["assessment"]["assessment_method_data"]["referrer"] === "backfill-assessment") {
                            $url = ENTRADA_URL . "/assessments?section=assessment-log";
                        }
                    }
                    $success_view = new Views_Message_Redirect();
                    $success_view->render(
                        array(
                            "message_type" => "success",
                            "message_text" => sprintf(
                                $translate->_("Successfully %s the form. You will now be redirected to %s. This will happen <strong>automatically</strong> in 5 seconds or <strong><a href='%s'>click here</a></strong> to continue."),
                                $action_taken,
                                $redirect_place,
                                $url
                            ),
                            "redirection_url" => $url,
                            "add_click_here_message" => false,
                            "add_redirect_message" => false
                        )
                    );
                    $redirecting = true;
                    $render_page = false;
                } else {
                    // Despite failure, we want to render the form
                    $render_page = true;
                    $render_sidebar = true;
                }
                break;

            default:
                break;
        }
    }

    /**
     * If we're not redirecting, then display any status messages.
     */
    if ($STEP >= 1 && !$redirecting) {
        display_status_messages();
    }

    /**
     * Render the assessment.
     */
    if ($render_page) {

        // Before rendering the rest of the page, if there was an issue with PDF, notify the user.
        if ($PROCESSED["pdf_error"]) {
            echo display_error($translate->_("Unable to generate PDF file. Please try again later."));
        }

        // Add a new statistic, only if we can (if there's a target record)
        if ($PROCESSED["target_record_id"]) {
            // Add statistic for the current user.
            $assessment_api->addAssessmentStatistic(
                $MODULE,
                $SUBMODULE,
                $ENTRADA_USER->getProxyId(),
                $PROCESSED["dassessment_id"],
                $PROCESSED["aprogress_id"],
                $PROCESSED["target_record_id"],
                "view",
                $PROCESSED["adistribution_id"]
            );
        }

        /**
         * If $assessment_form_html is non-false, then we can assume we have some HTML and that the render succeeded.
         * However, the API may have produced an error, and if so, we may not want to render the sidebar. Unless specifically
         * told to (e.g., after validation error on POST), we don't render the sidebar if there's an error.
         */
        $assessor_html = "";
        $target_html = "";
        $target_switcher_html = "";
        $attempts_html = "";

        /**
         * Render assessor sidebar entry
         **/
        $assessor_html = $assessment_api->renderAssessmentSidebarAssessor(true, false);

        /**
         * Render the target(s) side bar entry
         **/
        $target_html = $assessment_api->renderAssessmentSidebarTarget(
            $user_is_admin,
            false,
            $current_target["target_record_id"],
            $current_target["target_type"]
        );

        /**
         * Render the assessment target(s) switcher
         **/
        $target_switcher_html = $assessment_api->renderAssessmentTargetSwitcher(
            $user_is_admin,
            false,
            $current_target["target_record_id"],
            $current_target["target_type"]
        );

        /**
         * Render the "assessment attempts" sidebar listing
         **/
        $attempts_html = $assessment_api->renderAssessmentSidebarAttempts(
            $user_is_admin,
            false,
            $current_target["target_record_id"],
            $current_target["target_type"]
        );

        /**
         * Generate the Assessment here; the API sets the appropriate conditions for rendering the assessment
         * and populates its own error messages if applicable.
         */
        $assessment_form_html = $assessment_api->renderAssessment(
            array(
                "action_url" => $assessment_api->getAssessmentURL($current_target["target_record_id"], $current_target["target_type"]) . "&step=2",
                "form_dataset" => $form_data,
                "agent_contacts" => $AGENT_CONTACTS,
                "assessment_visibility_override_primary" => $assessment_visibility_override_primary,
                "assessment_visibility_override_secondary" => $assessment_visibility_override_secondary,
                "render_html" => $render_page, // in the case where we get an error, but want to render the form anyway (e.g., validation failure).
                "subheader_html" => $target_switcher_html . $assessor_html . $target_html . $attempts_html
            ),
            $user_is_admin,
            false,
            $current_target["target_record_id"],
            $current_target["target_type"]
        );
        $rendering_errors = $assessment_api->getErrorMessages();

        if ($assessment_form_html !== false) {
            /**
             * Output the rendered HTML (can be a simple error message)
             */
            echo $assessment_form_html;

        } else {

            /**
             * If the render attempt produced a boolean false return value, then there was an error to deal with.
             */
            if (empty($rendering_errors)) {
                echo display_error(array($translate->_("Error encountered when attempting to render assessment.")));
            } else {
                echo display_error($rendering_errors);
            }
        }

    }
}