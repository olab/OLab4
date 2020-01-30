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
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_ASSESSMENTS_REPORTS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";
    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    /**
     * Head Scripts
     */
    Entrada_Utilities::addJavascriptTranslation("No Learners Found", "no_learners_found", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Learners", "filter_component_label", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_period_filter_label", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Please select both a start date and finish date", "improper_date_selection", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Your request is being processed. Please wait while the report(s) are generated", "loading_csv_data", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Start date must come before the end date", "start_date_before_end", "cbme_translations");
    Entrada_Utilities::addJavascriptTranslation("Start or end date was formatted incorrectly", "incorrect_date_format", "cbme_translations");
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css?release=" . html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/learner-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/reports/milestone-report.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;

    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
        $PROCESSED["proxy_id"] = $tmp_input;
    } else {
        add_error($translate->_("No proxy ID provided"));
    }

    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
        $PROCESSED["course_id"] = $tmp_input;
    } else {
        $PROCESSED["course_id"] = NULL;
    }

    if (isset(${$request_var}["assessment_tool"]) && $tmp_input = clean_input(${$request_var}["assessment_tool"], array("array"))) {
        $PROCESSED["assessment_tool"] = $tmp_input;
    } else {
        $PROCESSED["assessment_tool"] = NULL;
    }

    if (isset(${$request_var}["step"]) && $tmp_input = clean_input(${$request_var}["step"], array("trim", "int"))) {
        $PROCESSED["step"] = $tmp_input;
    } else {
        $PROCESSED["step"] = 1;
    }

    if (isset(${$request_var}["assessment_tool"]) && $tmp_input = clean_input(${$request_var}["assessment_tool"], array("array"))) {
        $PROCESSED["assessment_tool"] = $tmp_input;
    } else {
        $PROCESSED["assessment_tool"] = array();
    }

    if(!$ERROR) {
        $course_utility = new Models_CBME_Course();
        $courses = $course_utility->getActorCourses(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            $PROCESSED["proxy_id"]
        );

        /**
         * Instantiate the CBME visualization abstraction layer
         */
        $cbme_progress_api = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $PROCESSED["proxy_id"],
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress",
            "filters" => $_GET,
            "query_limit" => 12,
            "limit_dataset" => array("unread_assessment_count"),
            "courses" => $courses,
            "secondary_proxy_id" => $ENTRADA_USER->getActiveId()
        ));

        /**
         * Fetch the dataset that will be used by the view
         */
        $dataset = $cbme_progress_api->fetchData();
        $unread_assessment_count = 0;
        if ($dataset && is_array($dataset) && array_key_exists("unread_assessment_count", $dataset)) {
            $unread_assessment_count = $dataset["unread_assessment_count"];
        }

        /**
         * Instantiate and render the Assessment Tool List Template
         */
        $item_card_view = new Views_CBME_Templates_AssessmentToolListItem();
        $item_card_view->render();

        $learner = Models_User::fetchRowByID($PROCESSED["proxy_id"]);

        if (isset($PREFERENCES["learner_preference"])) {
            $learner_preferences = $PREFERENCES["learner_preference"];
        } else {
            $learner_preferences = array();
        }

        ?>
        <h1><?php echo $translate->_("Milestone Report") ?></h1>
        <?php
        $qstr = http_build_query($_GET);
        if($qstr) {
            $qstr = "?" . $qstr;
        }
        $navigation_urls = array(
            "stages" => ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . html_encode($PROCESSED["proxy_id"]),
            "assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed" . $qstr,
            "items" => ENTRADA_URL . "/assessments/learner/cbme/items" . $qstr,
            "trends" => ENTRADA_URL . "/assessments/learner/cbme/trends" . $qstr,
            "comments" => ENTRADA_URL . "/assessments/learner/cbme/comments" . $qstr,
            "assessment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/assessments" . $qstr,
            "item_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/items" . $qstr,
            "comment_pins" => ENTRADA_URL . "/assessments/learner/cbme/pins/comments" . $qstr,
            "completed_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/completed". $qstr,
            "inprogress_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/inprogress" . $qstr,
            "pending_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/pending". $qstr,
            "deleted_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/deleted". $qstr,
            "unread_assessments" => ENTRADA_URL . "/assessments/learner/cbme/assessments/unread". $qstr
        );

        if ($learner) {
            $learner_array = array(
                "proxy_id" => $learner->getID(),
                "number" => $learner->getNumber(),
                "firstname" => $learner->getFirstname(),
                "lastname" => $learner->getLastname(),
                "email" => $learner->getEmail(),
                "full_width" => true
            );
            $learner_card = new Views_User_Card();
            $learner_card->render($learner_array);

            $learner_picker = new Views_CBME_LearnerPicker();
            $learner_picker->render(
                array(
                    "learner_preference" => $learner_preferences,
                    "proxy_id" => $PROCESSED["proxy_id"],
                    "learner_name" => $learner->getFullname()
                )
            );

            /**
             * Instantiate and render the CBME navigation
             */
            $navigation_view = new Views_CBME_NavigationTabs();
            $navigation_view->render(array("active_tab" => "", "navigation_urls" => $navigation_urls, "proxy_id" => $learner->getID(), "pinned_view" => false, "unread_assessment_count" => $unread_assessment_count));
        }

        $forms_api = new Entrada_Assessments_Forms(
            array(
                "actor_proxy_id" => $PROCESSED["proxy_id"],
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
            )
        );

        switch ($PROCESSED["step"]) {
            case 2:
                if (isset(${$request_var}["form_ids"]) && $tmp_input = clean_input(${$request_var}["form_ids"], array("array"))) {
                    $clean_form_id = array();
                    foreach ($tmp_input as $form_id) {
                        $clean_form_id[] = clean_input($form_id, array("int", "striptags"));
                    }
                    $PROCESSED["form_ids"] = $clean_form_id;
                } else {
                    add_error($translate->_("There was no assessment tool specified."));
                }

                if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("int"))) {
                    $PROCESSED["course_id"] = $tmp_input;
                } else {
                    add_error($translate->_("There was no course specified."));
                }

                if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                    $PROCESSED["proxy_id"] = $tmp_input;
                } else {
                    add_error($translate->_("There was no proxy id specified."));
                }

                if (!$ERROR) {
                    $milestone_report_class = new Entrada_CBME_MilestoneReport(array("course_id" => $PROCESSED["course_id"], "actor_proxy_id" => $PROCESSED["proxy_id"], "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
                    $milestone_report_class->generateMilestoneReport($PROCESSED["form_ids"]);
                } else {
                    echo display_error();
                }
                break;
        }

    } else {
        echo display_error();
    }
        /**
         * Instantiate and render the Milestone Report view
         */
        $milestone_report_view = new Views_CBME_Reports_Milestone();
        $milestone_report_view->render(array("course_id" => $PROCESSED["course_id"], "proxy_id" => $PROCESSED["proxy_id"]));
}