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
 * Module:    External Assessor Assessment (single form)
 * Area:    Default pages
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2015, 2017 Queen's University. All Rights Reserved.
 *
 */
if (!defined("PARENT_INCLUDED")) exit;

$BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "/", "title" => $translate->_("Assessment Task"));
$JAVASCRIPT_TRANSLATIONS[] = "current_target = '" . $translate->_("Currently Assessing") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "hide_assessment_error = '" . $translate->_("Please enter a comment.") . "';";

$render_page = true;
$render_sidebar = false;
$redirecting = false;
$assessment_visibility_override = false;
$assessment_data = array();               // The entire assessment dataset, fetched from assessment API
$form_data = array();                     // The form dataset, fetched from forms API
$current_progress = array();              // The progress (including responses) of the current record (if one exists). Derived from the pre-fetched assessment dataset.
$current_target = array();                // An array describing the current target (target type, name, scope (internal/external)). Derived from the pre-fetched assessment dataset.
$assessor_id = null;
$assessor_scope = null;
$assessor_type = null;

$PROCESSED["adistribution_id"] = null;
$PROCESSED["objectives"] = array();
$PROCESSED["organisation_id"] = null;

// Either (atarget_id) OR (target_record_id and target_type) can be specified.
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

$PROCESSED["aprogress_id"] = null;
if (array_key_exists("aprogress_id", $_POST) && $tmp_input = clean_input($_POST["aprogress_id"], array("trim", "int"))) {
    $PROCESSED["aprogress_id"] = $tmp_input;
} else if (array_key_exists("aprogress_id", $_GET) && $tmp_input = clean_input($_GET["aprogress_id"], array("trim", "int"))) {
    $PROCESSED["aprogress_id"] = $tmp_input;
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

$PROCESSED["external_hash"] = null;
if (isset($_GET["external_hash"]) && $tmp_input = clean_input($_GET["external_hash"], array("trim", "alphanumeric"))) {
    $PROCESSED["external_hash"] = $tmp_input;
}

/**
 * Validate the external hash against the dassessment ID.
 **/

if (!$validated_external_assessment = Entrada_Assessments_Assessment::validateExternalHash($PROCESSED["dassessment_id"], $PROCESSED["external_hash"])) {
    add_error($translate->_("Access key is invalid."));
    $render_page = false;
}

/**
 * Check if this user is logged in already, in which case they can't fill out this assessment.
 * External assessments are for external entities that by deifnition have no login credentials
 **/

if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
    // URL to go to internal version of this assessment (only accessible if permission check passes)
    $url = ENTRADA_URL . "/assessments/assessment?dassessment_id={$PROCESSED["dassessment_id"]}";
    if ($PROCESSED["aprogress_id"]) {
        $url .= "&aprogress_id={$PROCESSED["aprogress_id"]}";
    }
    if ($PROCESSED["atarget_id"]) {
        $url .= "&atarget_id={$PROCESSED["atarget_id"]}";
    }
    add_notice(sprintf($translate->_("You are attempting to access an external assessment while logged in. If you wish to view or complete this assessment, <strong><a href='%s'>click here</a></strong>."), $url));
    $render_page = false;
}

/**
 * Store the external assessor ID.
 */

if (!has_error() && $render_page) {
    $PROCESSED["organisation_id"] = $validated_external_assessment->getOrganisationID();
    $assessor_id = $validated_external_assessment->getAssessorValue();
    $assessor_scope = $validated_external_assessment->getAssessorType();
    if ($assessor_scope == "external") {
        $external_assessor_record = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor_id);
        if ($external_assessor_record) {
            $assessor_type = "external_assessor_id";
        }
    }
    if ($assessor_type !== "external_assessor_id") {
        add_error($translate->_("Assessment is not configured (assessor is invalid)."));
    }
    //type = "external_assessor_id", scope = "external", id = assessor_value
}

/**
 * Instantiate our assessment object as an external assessor and fetch the dataset.
 **/

if (!has_error() && $render_page) {

    // Instantiate our assessments API
    $assessment_api = new Entrada_Assessments_Assessment(
        array(
            "dassessment_id" => $PROCESSED["dassessment_id"],
            "actor_proxy_id" => $assessor_id,
            "actor_organisation_id" => $PROCESSED["organisation_id"],
            "actor_group" => "external_assessors",
            "actor_type" => "external_assessor_id",
            "actor_scope" => "external",
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

    $PROCESSED["adistribution_id"] = $assessment_data["meta"]["adistribution_id"]; // Can be null

    if (!empty($assessment_data["distribution"]["distribution_creator"])) {
        $task_creator_firstname = $assessment_data["distribution"]["distribution_creator"]["firstname"];
        $task_creator_lastname = $assessment_data["distribution"]["distribution_creator"]["lastname"];
        $task_creator_email = $assessment_data["distribution"]["distribution_creator"]["email"];
    } else if (!empty($assessment_data["creator"])) {
        $task_creator_firstname = $assessment_data["creator"]["firstname"];
        $task_creator_lastname = $assessment_data["creator"]["lastname"];
        $task_creator_email = $assessment_data["creator"]["email"];
    }
}

/**
 * Handle post submission, if any.
 */
if (!has_error() && $render_page) {

    // Insantiate forms API object with the appropriate progress and form IDs.
    $forms_api = new Entrada_Assessments_Forms(
        array(
            "actor_proxy_id" => null,
            "actor_organisation_id" => $PROCESSED["organisation_id"],
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
         * Handle assessment responses submission (the full set of POST'd data)
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
                    "submodule" => null
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
                "feedback" => array(
                    "enabled" => true
                )
            );

            if ($assessment_api->handlePostedAssessmentSubmission($_POST, $submission_options, $hook_options, false, false, false, $PROCESSED["target_type"], $PROCESSED["target_record_id"])) {
                // Successful post, redirect away from this page.
                $action_taken = $finish_assessment
                    ? $translate->_("completed")
                    : $translate->_("saved");
                $redirect_place = $translate->_("the Dashboard");
                $url = ENTRADA_URL . "/dashboard";
                if ($assessment_data["assessment"]["adistribution_id"]) {
                    $redirect_place = $translate->_("your assessments page");
                    $url = $assessment_api->getAssessmentURL($PROCESSED["target_record_id"], $PROCESSED["target_type"], true);
                }
                $success_view = new Views_Message_Redirect();
                $success_view->render(
                    array(
                        "message_type" => "success",
                        "message_text" => sprintf(
                            $translate->_("Successfully %s the form."),
                            $action_taken,
                            $redirect_place,
                            $url
                        ),
                        "redirection_url" => $url
                    )
                );
                $redirecting = true;
                $render_page = false;
                $render_sidebar = true;
            } else {
                // Despite failure, we want to render the form
                $render_page = true;
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
 * Passed all checks, fetched all applicable data without errors, so we can render the form now.
 **/

if ($render_page) {

    /**
     * If the assessment is already complete, add a notice indicating so.
     */
    $sub_header_notification = "";
    if ($assessment_api->isAssessmentCompleted()) {
        ob_start(); ?>
        <div class="alert alert-success">
            <?php
            echo sprintf(
                $translate->_("Thank you for completing this <strong>assessment</strong>. If you need to make changes, please contact <strong>%s</strong>."),
                $task_creator_email
                    ? "$task_creator_firstname $task_creator_lastname (<span class=\"user-email\">$task_creator_email</span>)"
                    : "$task_creator_firstname $task_creator_lastname"
            );
            ?>
        </div>
        <?php $sub_header_notification = ob_get_clean();
    }

    // Add a new statistic, only if we can (if there's a target record)
    if ($PROCESSED["target_record_id"]) {
        // Add statistic for the current user.
        $assessment_api->addAssessmentStatistic(
            $MODULE,
            null,
            $assessor_id,
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
            "assessment_mode" => "external",
            "agent_contacts" => $AGENT_CONTACTS,
            "action_url" => $assessment_api->getAssessmentURL($current_target["target_record_id"], $current_target["target_type"], true) . "&step=2",
            "form_dataset" => $form_data,
            "render_html" => $render_page, // in the case where we get an error, but want to render the form anyway (e.g., validation failure).
            "subheader_html" => $sub_header_notification . $target_switcher_html . $assessor_html . $target_html . $attempts_html,
            "can_download" => false // No PDF for externals
        ),
        false,
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