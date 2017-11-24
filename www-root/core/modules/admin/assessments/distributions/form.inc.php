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
 * This file is used to create, copy or edit distributions.
 *
 * @author Organisation: Queen's univeristy
 * @author Unit: Education Technology unit
 * @author Developer:  Adrian Mellognio
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_DISTRIBUTIONS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    $ERROR++;
    $ERRORSTR[] = $translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
    echo display_error();
    application_log("error", "Group [" . $GROUP . "] and role [" . $ROLE . "] do not have access to this module [" . $MODULE . "]");
} else {
    $DISTRIBUTION_ID = 0;

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $tmp_input;
    } elseif (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $tmp_input;
    }

    if (isset($_GET["dtype"]) && $tmp_input = clean_input($_GET["dtype"], array("trim", "striptags"))) {
        $DTYPE = $tmp_input;
    } else {
        $DTYPE = "";
    }

    if (isset($_GET["mode"]) && $tmp_input = clean_input($_GET["mode"], array("trim", "striptags"))) {
        $mode = strtolower($tmp_input);
    } else {
        $mode = "";
    }

    if (isset($_GET["step"]) && $tmp_input = clean_input($_GET["step"], array("int", "striptags"))) {
        $step = $tmp_input;
    } else {
        $step = "1";
    }

    if ($NOTICE) {
        echo display_notice();
    }
    if ($ERROR) {
        echo display_error();
    }

    if (!$ERROR) {

        /**
         * Extract the referrer from the session. Ensure that we're redirecting to the appropriate page -- don't trust the session's referrer variable. If we detect
         * an invalid referrer link (any page with a distribution id different than ours), redirect to the index.
         */
        $DISTRIBUTION_REFERRER = false;
        if (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"]["url"])) {
            if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"]["from_index"] == false &&
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"]["adistribution_id"] > 0 &&
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"]["adistribution_id"] == $DISTRIBUTION_ID) {
                    $DISTRIBUTION_REFERRER = html_encode($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"]["url"]);
            }
        }

        // Distribution related variables (for initial page load)
        $date_range_start = 0;
        $date_range_end = 0;
        $distribution_method = null;
        $distribution_method_label = "";
        $distribution = null;
        $distribution_data = null;
        $distribution_schedule = null;
        $user_courses = null;
        $rotation_schedule = null;

        // Fetch the distribution data (and set the mode appropriately if unset or set improperly).
        // This data is also used to populate the first step of the form.
        if (!$DISTRIBUTION_ID) {
            $mode = "create";
        } else {
            $distribution = Models_Assessments_Distribution::fetchRowByIDOrganisationID($DISTRIBUTION_ID, $ENTRADA_USER->getActiveOrganisation());
            if (!$distribution) { // Distribution was not found, so assume create mode
                $DISTRIBUTION_ID = 0;
                $mode = "create";
            } else {
                // ID given was good, so fetch data.
                $distribution_data = Models_Assessments_Distribution::fetchDistributionData($distribution->getID());


                // The only valid modes are copy and edit.
                if ($mode != "edit" && $mode != "copy") {
                    $mode = "edit";
                }
            }
        }

        // Populate the autocomplete search data-source for the distribution method selector.
        $ds_distribution_methods = array();
        $ds_distribution_methods[] = array("target_id" => "rotation_schedule", "target_label" => $translate->_("Rotation Schedule"));
        $ds_distribution_methods[] = array("target_id" => "delegation", "target_label" => $translate->_("Delegation"));
        $ds_distribution_methods[] = array("target_id" => "eventtype", "target_label" => $translate->_("Learning Event Schedule"));
        $ds_distribution_methods[] = array("target_id" => "date_range", "target_label" => $translate->_("Date Range"));

        // Populate data source for Curriculum period selector
        $curriculum_periods = array();
        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                $cperiods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                if ($cperiods) {
                    foreach ($cperiods as $curriculum_period) {
                        $curriculum_periods[$curriculum_period->getCperiodID()] = array("target_id" => $curriculum_period->getCperiodID(), "target_label" => $curriculum_type->getCurriculumTypeName() . ": " . ($curriculum_period->getCurriculumPeriodTitle() ? $curriculum_period->getCurriculumPeriodTitle() : date("Y-m-d", $curriculum_period->getStartDate()) . " to " . date("Y-m-d", $curriculum_period->getFinishDate())));
                    }
                }
            }
        }

        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/distributions?" . replace_query(array("section" => "editor", "id" => $DISTRIBUTION_ID)), "title" => "Distribution Editor");

        $HEAD[] = "<script type=\"text/javascript\" >var distribution_id =\"" . $DISTRIBUTION_ID . "\"</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var MODULE = '" . $MODULE . "';</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/distribution-progress.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/distributions/index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-targets.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/distributions/progress.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" >var internal_assessor_label = '" . $translate->_("Internal assessor") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var external_assessor_label = '" . $translate->_("External assessor") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var individual_author_label = '" . $translate->_("Individual") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var course_author_label = '" . $translate->_("Course") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var organisation_author_label = '" . $translate->_("Organisation") . "';</script>";
        $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
        ?>
        <script type="text/javascript">
            var distribution_form = {};
            distribution_form.save_success = "<?php echo sprintf($translate->_("Your distribution was successfully saved. You will be automatically redirected in 5 seconds, or you can <a href='%s'>click here</a> if you do not wish to wait."), $DISTRIBUTION_REFERRER ? html_decode($DISTRIBUTION_REFERRER) : html_decode(ENTRADA_URL ."/admin/assessments/distributions")) ?> ";
            jQuery(document).ready(function ($) {

                $(".datepicker").datepicker({
                    dateFormat: "yy-mm-dd",
                    minDate: "",
                    maxDate: ""
                });

                $(".add-on").on("click", function () {
                    if ($(this).siblings("input").is(":enabled")) {
                        $(this).siblings("input").focus();
                    }
                });

                $("#choose-method-btn").advancedSearch({
                    filters: {
                        distribution_method: {
                            label: "<?php echo $translate->_("Distribution Methods"); ?>",
                            data_source: <?php echo json_encode($ds_distribution_methods); ?>,
                            mode: "radio",
                            selector_control_name: "distribution_method",
                            search_mode: false
                        }
                    },
                    control_class: "distribution-method-selector",
                    no_results_text: "<?php echo $translate->_(""); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-cperiod-btn").advancedSearch({
                    filters: {
                        curriculum_period: {
                            label: "<?php echo $translate->_("Curriculum Period"); ?>",
                            data_source: <?php echo json_encode(array_values($curriculum_periods)); ?>,
                            mode: "radio",
                            selector_control_name: "cperiod_id",
                            search_mode: false
                        }
                    },
                    control_class: "curriculum-period-selector",
                    no_results_text: "<?php echo $translate->_(""); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 350,
                    modal: false
                });

                $("#choose-course-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        rs_course: {
                            label: "<?php echo $translate->_("Course"); ?>",
                            data_source: "get-courses",
                            mode: "radio",
                            selector_control_name: "course_id",
                            search_mode: false
                        }
                    },
                    control_class: "choose-course-selector",
                    no_results_text: "<?php echo $translate->_(""); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-assessors-rs-individual-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        individual_assessor_learners: {
                            label: "<?php echo $translate->_("Learners"); ?>",
                            data_source: "get-course-learners",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessors-rs-individual-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-form-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        form: {
                            label: "<?php echo $translate->_("Form"); ?>",
                            data_source: "get-user-forms",
                            mode: "radio",
                            selector_control_name: "distribution_form"
                        }
                    },
                    control_class: "form-selector",
                    no_results_text: "<?php echo $translate->_("No Forms found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-delegator-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        delegator: {
                            label: "<?php echo $translate->_("Delegators"); ?>",
                            data_source: "get-delegators",
                            mode: "radio",
                            selector_control_name: "delegator_id"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No Delegators found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    control_class: "delegator-selector",
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#rs-choose-rotation-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        rs_schedule: {
                            label: "<?php echo $translate->_("Rotation Schedule"); ?>",
                            data_source : "get-schedules",
                            secondary_data_source : "get-schedule-children",
                            mode: "radio",
                            selector_control_name: "schedule_id"
                        }
                    },
                    control_class: "rs-rotation-selector",
                    no_results_text: "<?php echo $translate->_("No Rotation Schedules found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-assessors-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        assessor_cohort: {
                            label: "<?php echo $translate->_("Cohort"); ?>",
                            data_source: "get-cohorts",
                            mode: "radio",
                            selector_control_name: "assessor_cohort_id"
                        },
                        assessor_course_audience: {
                            label: "<?php echo $translate->_("Course Audience"); ?>",
                            data_source: "get-user-courses",
                            mode: "radio",
                            selector_control_name: "assessor_course_id"
                        }
                    },
                    control_class: "assessor-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-assessors-faculty-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    select_all_enabled: true,
                    resource_url: ENTRADA_URL,
                    filters: {
                        assessor_faculty: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-course-faculty",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessors-faculty-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-faculty-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        target_faculty: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-faculty-staff",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-targets-faculty-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No users found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        target_cohort: {
                            label: "<?php echo $translate->_("Cohort"); ?>",
                            data_source: "get-cohorts",
                            mode: "radio",
                            selector_control_name: "target_cohort_id"
                        },
                        target_course_audience: {
                            label: "<?php echo $translate->_("Course Audience"); ?>",
                            data_source: "get-user-courses",
                            mode: "radio",
                            selector_control_name: "target_course_audience_id"
                        }
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-assessors-rs-additional-learners").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        additional_assessor_learners: {
                            label: "<?php echo $translate->_("Learners"); ?>",
                            data_source: "get-organisation-learners",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessors-rs-additional-learners-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-rs-additional-learners").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        additional_target_learners: {
                            label: "<?php echo $translate->_("Learners"); ?>",
                            data_source: "get-organisation-learners",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-targets-rs-additional-learners-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-assessors-rs-faculty").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        additional_assessor_faculty: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-faculty-staff",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessors-rs-faculty-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "assessor-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-rs-faculty").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        additional_target_faculty: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-faculty-staff",
                            mode: "checkbox"
                        }
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-rs-individual-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        individual_target_learner: {
                            label: "<?php echo $translate->_("Learners"); ?>",
                            data_source: "get-course-learners",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-targets-rs-individual-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-target-course-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        target_course: {
                            label: "<?php echo $translate->_("Course"); ?>",
                            data_source: "get-courses",
                            mode: "radio",
                            selector_control_name: "target_course_id",
                            search_mode: false
                        }
                    },
                    control_class: "choose-target-course-selector",
                    no_results_text: "<?php echo $translate->_(""); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#distribution-review-results").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        distribution_results_user: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-faculty-staff",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessment-reviewers-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "target-audience-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#choose-targets-eventtype").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        eventtypes: {
                            label: "<?php echo $translate->_("Event Types"); ?>",
                            data_source: "get-eventtypes",
                            mode: "checkbox"
                        }
                    },
                    control_class: "target-eventtype-selector",
                    no_results_text: "<?php echo $translate->_("No Event Types found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });

                $("#choose-assessors-eventtype-faculty-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        additional_assessor_eventtype_faculty: {
                            label: "<?php echo $translate->_("Faculty"); ?>",
                            data_source: "get-faculty-staff",
                            mode: "checkbox"
                        }
                    },
                    control_class: "assessor-eventtype-faculty-selector",
                    no_results_text: "<?php echo $translate->_("No Cohorts found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    lazyload: true,
                    modal: false
                });

                $("#distribution-approver-results").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/" + MODULE + "/distributions?section=api-distributions",
                    resource_url: ENTRADA_URL,
                    filters: {
                        distribution_approvers: {
                            label: "<?php echo $translate->_("Reviewer"); ?>",
                            data_source: "get-distribution-approvers",
                            mode: "checkbox"
                        }
                    },
                    list_data: {
                        selector: "#selected-assessment-approvers-container",
                        background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                    },
                    control_class: "distribution-approver-selector",
                    no_results_text: "<?php echo $translate->_("No Reviewers found matching the search criteria"); ?>",
                    parent_form: $("#distribution-data-form"),
                    width: 300,
                    modal: false
                });
            });
        </script>

        <div id="distribution-editor-container">
            <div id="distribution-editor-title" class="text-center">
                <?php if ($mode == 'edit') : ?>
                    <h2 id="editor_title"><?php echo $translate->_("Modify a Distribution"); ?><span id="overall-distribution-title" name="overall-title"><?php if ($distribution) echo " : " . html_encode($distribution->getTitle()); ?></span></h2>
                <?php elseif ($mode == 'copy') : ?>
                    <h2 id="editor_title"><?php echo $translate->_("Copy a Distribution as New"); ?><span id="overall-distribution-title" name="overall-title"><?php if ($distribution) echo " : " . html_encode($distribution->getTitle()); ?></span></h2>
                <?php else : // ($mode == 'create') ?>
                    <h2 id="editor_title"><?php echo $translate->_("Create a New Distribution"); ?><span id="overall-distribution-title" name="overall-title"></span></h2>
                <?php endif; ?>
                <input type="hidden" />
            </div>

            <div id="distribution-editor-body">
                <div class="distribution-wizard-step-container">
                    <ul class="distribution-wizard-steps clearfix">
                        <li id="wizard-nav-item-1" class="wizard-nav-item active" data-step="1">
                            <a href="#"><?php echo $translate->_("<span>1</span> Form"); ?></a>
                        </li>
                        <li id="wizard-nav-item-2" class="wizard-nav-item" data-step="2">
                            <a href="#"><?php echo $translate->_("<span>2</span> Method"); ?></a>
                        </li>
                        <li id="wizard-nav-item-3" class="wizard-nav-item" data-step="3">
                            <a href="#"><?php echo $translate->_("<span>3</span> Targets"); ?></a>
                        </li>
                        <li id="wizard-nav-item-4" class="wizard-nav-item" data-step="4">
                            <a href="#"><?php echo $translate->_("<span>4</span> Assessors"); ?></a>
                        </li>
                        <li id="wizard-nav-item-5" class="wizard-nav-item" data-step="5">
                            <a href="#"><?php echo $translate->_("<span>5</span> Results"); ?></a>
                        </li>
                    </ul>
                </div>

                <div id="msgs"></div>

                <div id="distribution-loading" class="hide">
                    <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
                    <p id="distribution-loading-msg"></p>
                </div>

                <form id="distribution-data-form" class="form-horizontal">
                    <input id="wizard-step-input" type="hidden" name="wizard_step" value="1"/>
                    <input id="wizard-editor-mode" type="hidden" name="mode" value="<?php echo $mode ?>" />
                    <input id="wizard-previous-step-input" type="hidden" name="previous_wizard_step" value="0"/>
                    <div id="wizard-step-1" class="wizard-step">
                        <div class="control-group">
                            <label for="distribution-title" class="control-label form-required"><?php echo $translate->_("Distribution Title"); ?></label>
                            <div class="controls">
                                <input id="distribution-title" type="text" name="title" value="<?php if ($distribution) echo html_encode($distribution->getTitle()); ?>"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="distribution-description" class="control-label"><?php echo $translate->_("Distribution Description"); ?></label>
                            <div class="controls">
                                <textarea id="distribution-description" name="description"><?php if ($distribution) echo $distribution->getDescription(); ?></textarea>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="assessment-mandatory" class="control-label"><?php echo $translate->_("Assessment Mandatory"); ?></label>
                            <div class="controls">
                                <label class="checkbox" for="assessment-mandatory">
                                    <input id="assessment-mandatory" type="checkbox" name="mandatory" value="1" <?php echo ($distribution && $distribution->getMandatory() == 0) ? "" : "checked" ; ?> />
                                    <?php echo $translate->_("Require this assessment to be completed by all assessors"); ?>
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="choose-form-btn" class="control-label form-required"><?php echo $translate->_("Select Form"); ?></label>
                            <div class="controls">
                                <button id="choose-form-btn" class="btn">
                                    <?php echo (isset($distribution_data["form_title"])) ? html_encode($distribution_data["form_title"]) : $translate->_("Browse Forms"); ?>
                                    <i class="icon-chevron-down btn-icon pull-right"></i></button>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="choose-cperiod-btn" class="control-label form-required"><?php echo $translate->_("Select a Curriculum Period"); ?></label>
                            <div class="controls">
                                <button id="choose-cperiod-btn" class="btn btn-search-filter">
                                    <?php echo (isset($distribution_data["cperiod_id"]) && isset($curriculum_periods[$distribution_data["cperiod_id"]])) ? html_encode($curriculum_periods[$distribution_data["cperiod_id"]]["target_label"]) : $translate->_("Browse Curriculum Periods"); ?>
                                    <i class="icon-chevron-down btn-icon pull-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="choose-course-btn" class="control-label form-required"><?php echo $translate->_("Select a Course"); ?></label>
                            <div class="controls">
                                <button id="choose-course-btn" class="btn btn-search-filter">
                                    <?php echo (isset($distribution_data["course_name"])) ? html_encode($distribution_data["course_name"]) : $translate->_("Browse Courses"); ?>
                                    <i class="icon-chevron-down btn-icon pull-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-2" class="wizard-step hide">
                        <div id="distribution-method-choice-container" class="control-group">
                            <label for="choose-method-btn" class="control-label form-required"><?php echo $translate->_("Distribution Method"); ?></label>
                            <div class="controls">
                                <button id="choose-method-btn" class="btn">
                                    <?php if ($distribution_method) {
                                        foreach ($ds_distribution_methods as $method_info) {
                                            if ($method_info["target_id"] == $distribution_method) {
                                                echo $method_info["target_label"];
                                            }
                                        }
                                    } else {
                                        echo $translate->_("Browse Distribution Methods");
                                    } ?>
                                    <i class="icon-chevron-down btn-icon pull-right"></i></button>
                            </div>
                        </div>

                        <div id="distribution-delegator-options" class="hide distribution-options">
                            <div class="control-group">
                                <label for="choose-delegator-btn" class="control-label form-required"><?php echo $translate->_("Delegator"); ?></label>
                                <div class="controls">
                                    <input id="choose-delegator-btn-default-label" type="hidden" value="<?php echo $translate->_("Browse Delegators"); ?>">
                                    <button id="choose-delegator-btn" class="btn"><?php echo $translate->_("Browse Delegators"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i></button>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label form-required"><?php echo $translate->_("Delegation Options"); ?></label>
                                <div id="delegation-options-container" class="controls">
                                    <label class="radio" for="delegator_timeframe_date_range">
                                        <input type="radio" name="distribution_delegator_timeframe" id="delegator_timeframe_date_range" value="date_range">
                                        <?php echo $translate->_("Delegation will be based on a date range"); ?>
                                    </label>
                                    <label class="radio" for="delegator_timeframe_rotation_schedule">
                                        <input type="radio" name="distribution_delegator_timeframe" id="delegator_timeframe_rotation_schedule" value="rotation_schedule">
                                        <?php echo $translate->_("Delegation will be based on a rotation schedule"); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-specific-date-options" class="<?php if ($distribution_method != "date_range") echo "hide"; ?> distribution-options">
                            <div class="control-group">
                                <label for="distribution_start_date" class="control-label form-required"><?php echo $translate->_("Start Date"); ?></label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="distribution_start_date" type="text"
                                               class="input-small datepicker"
                                               value="<?php echo (isset($distribution_data["start_date"]) && $distribution_data["start_date"]) ? date("Y-m-d", $distribution_data["start_date"]) : date("Y-m-d", strtotime("today")); ?>"
                                               name="distribution_start_date"/>
                                        <span class="add-on pointer">
                                            <i class="icon-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="input-append hide">
                                        <input id="distribution_start_time" type="text"
                                               class="input-mini timepicker"
                                               value="00:00"
                                               name="distribution_start_time"/>
                                        <span class="add-on pointer">
                                            <i class="icon-time"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="distribution_end_date" class="control-label form-required"><?php echo $translate->_("End Date"); ?></label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="distribution_end_date" type="text" class="input-small datepicker"
                                               value="<?php echo (isset($distribution_data["end_date"]) && $distribution_data["end_date"]) ? date("Y-m-d", $distribution_data["end_date"]) : date("Y-m-d", strtotime("today + 1 week")) ?>"
                                               name="distribution_end_date"/>
                                        <span class="add-on pointer">
                                            <i class="icon-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="input-append hide">
                                        <input id="distribution_end_time" type="text" class="input-mini timepicker"
                                               value="23:59"
                                               name="distribution_end_time"/>
                                        <span class="add-on pointer">
                                            <i class="icon-time"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="distribution_delivery_date" class="control-label form-required"><?php echo $translate->_("Delivery Date"); ?></label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="distribution_delivery_date" type="text"
                                               class="input-small datepicker"
                                               value="<?php echo (isset($distribution_data["delivery_date"]) && $distribution_data["delivery_date"]) ? date("Y-m-d", $distribution_data["delivery_date"]) : date("Y-m-d", strtotime("today")); ?>"
                                               name="distribution_delivery_date"/>
                                        <span class="add-on pointer">
                                            <i class="icon-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-rotation-schedule-options" class="hide distribution-options">
                            <div class="control-group" id="rs-rotation-schedule-options">
                                <label for="rs-choose-rotation-btn" class="control-label form-required"><?php echo $translate->_("Rotation Schedule"); ?></label>
                                <div class="controls">
                                    <input id="rs-choose-rotation-btn-default-label" type="hidden" value="<?php echo $translate->_("Browse Rotation Schedules"); ?>">
                                    <button id="rs-choose-rotation-btn" class="btn">
                                        <?php echo $translate->_("Browse Rotation Schedules"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-rotation-delivery-options" class="<?php if (!$distribution_schedule) echo "hide"; ?>">
                            <div class="control-group">
                                <label class="control-label release-date-tooltip" data-toggle="tooltip" title="<?php echo $translate->_("The release date tells the distribution wizard how far back in time to go in order to create assessment tasks. For example: If you set the release date to the first of this month, tasks will only be created based on scheduled events or rotations that take place after the first of this month within the curriculum period chosen in Step 1."); ?>"><?php echo $translate->_("Release Date "); ?><i class="icon-question-sign"></i></label>
                                <div class="controls">
                                    <label id="rotation-release-option-label" for="rotation-release-option" class="checkbox">
                                        <input type="checkbox" id="rotation-release-option" name="rotation_release_option" value="1" <?php echo isset($distribution_data["release_date"])? "checked" : ""; ?> />
                                    </label>
                                    <div id="rotation-release-control" style="display:<?php echo (!isset($distribution_data["release_date"]) || !$distribution_data["release_date"]) ? "none" : "inline-block" ?>">
                                        <div class="input-append space-right release-calendar">
                                            <input type="text" id="rotation-release-date" name="rotation_release_date" class="input-small datepicker" value="<?php echo ($distribution_data["release_date"]) ? date('Y-m-d', $distribution_data["release_date"]) : "" ;?>"/>
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="rotation_schedule_delivery_type" class="control-group">
                                <label for="" class="control-label form-required"><?php echo $translate->_("Delivery Period"); ?></label>
                                <div class="controls">
                                    <label class="radio">
                                        <input type="radio" name="schedule_delivery_type"
                                               value="repeat" class="schedule_delivery_type" data-timeline-options="repeat" autocomplete="off"
                                               /> <?php echo $translate->_("Deliver repeatedly"); ?>
                                    </label>
                                    <label class="radio">
                                        <input type="radio" name="schedule_delivery_type" value="block"
                                               class="schedule_delivery_type block" data-timeline-options="once-per"
                                               autocomplete="off"
                                               /> <?php echo $translate->_("Deliver once per block"); ?>
                                    </label>
                                    <label class="radio">
                                        <input type="radio" name="schedule_delivery_type" value="rotation"
                                               class="schedule_delivery_type rotation"
                                               data-timeline-options="once-per"
                                               autocomplete="off"
                                               /> <?php echo $translate->_("Deliver once per rotation"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="rotation-schedule-delivery-offset" class="hide">
                                <label for="rotation-schedule_delivery_type[1]" class="control-label form-nrequired"><?php echo $translate->_("Delivery Timeline Options") ?></label>
                                <div class="controls">
                                    <div id="timeline-option-repeat" class="">
                                        <label for="frequency"><?php echo $translate->_("Deliver every "); ?></label>
                                        <input type="text" style="width: 30px" name="frequency" id="frequency" value="1"/>
                                        <label for="frequency"><?php echo $translate->_(" days during the Rotation."); ?></label>
                                    </div>

                                    <div id="timeline-option-once-per" class="">
                                        <?php echo $translate->_("Deliver "); ?>
                                        <input type="text" name="period_offset_days" id="period_offset_days" value="1" class="input-mini" />
                                        <?php echo $translate->_(" days "); ?>
                                        <select name="delivery_period" id="delivery_period" class="input-medium">
                                            <option value="after-start"><?php echo $translate->_("after the start"); ?></option>
                                            <option value="before-middle"><?php echo $translate->_("before the middle"); ?></option>
                                            <option value="after-middle"><?php echo $translate->_("after the middle"); ?></option>
                                            <option value="before-end"><?php echo $translate->_("before the end"); ?></option>
                                            <option value="after-end"><?php echo $translate->_("after the end"); ?></option>
                                        </select>
                                        <span class="once-per-rotation hide"><?php echo $translate->_(" of the Rotation."); ?></span>
                                        <span class="once-per-block hide"><?php echo $translate->_(" of each Block."); ?></span>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-eventtype-options" class="hide distribution-options">
                            <div class="control-group">
                                <label class="control-label form-required" for="choose-targets-eventtype"><?php echo $translate->_("Select Event Types"); ?></label>
                                <div class="controls">
                                    <button id="choose-targets-eventtype" class="btn btn-search-filter"><?php echo $translate->_("Browse Event Types"); ?><i class="icon-chevron-down btn-icon pull-right"></i></button>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label release-date-tooltip" data-toggle="tooltip" title="<?php echo $translate->_("The release date tells the distribution wizard how far back in time to go in order to create assessment tasks. For example: If you set the release date to the first of this month, tasks will only be created based on scheduled events or rotations that take place after the first of this month within the curriculum period chosen in Step 1."); ?>"><?php echo $translate->_("Release Date "); ?><i class="icon-question-sign"></i></label>
                                <div class="controls">
                                    <label id="eventtype-release-option-label" for="eventtype-release-option" class="checkbox">
                                        <input type="checkbox" id="eventtype-release-option" name="eventtype_release_option" value="1" <?php echo isset($distribution_data["release_date"])? "checked" : ""; ?> />
                                    </label>
                                    <div id="eventtype-release-control" style="display:<?php echo (!isset($distribution_data["release_date"]) || !$distribution_data["release_date"]) ? "none" : "inline-block" ?>">
                                        <div class="input-append space-right release-calendar">
                                            <input type="text" id="eventtype-release-date" name="eventtype_release_date" class="input-small datepicker" value="<?php echo ($distribution_data["release_date"]) ? date('Y-m-d', $distribution_data["release_date"]) : "" ;?>"/>
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div id="wizard-step-3" class="wizard-step hide">
                        <div class="distribution-instruction">
                            <!--<h2><?php echo $translate->_("Choose Targets"); ?></h2>-->
                            <!--<p><?php echo $translate->_("Browse and select the targets for this distribution using the button below. Possible target choices include <strong>Cohort</strong>, <strong>Course Audience</strong>, <strong>Organisations</strong> or <strong>Individuals</strong>"); ?></p>-->
                        </div>
                        <div id="distribution-target-delegation-warning" class="alert alert-info hide">
                            <?php echo $translate->_("<strong>Note</strong>: <strong>Delegation</strong> was the selected distribution method, users selected in this step will be presented as a list to the delegator, who will then select the targets from that list."); ?>
                        </div>
                        <div id="eventtype-target-options" class="target-option hide">
                            <div class="control-group">
                                <label class="control-label form-required"><?php echo $translate->_("Assessments will be delivered for"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-eventtype-target-eventtype">
                                        <input type="radio" name="distribution_eventtype_target_option" id="distribution-eventtype-target-eventtype" value="learner" />
                                        <?php echo $translate->_("Learners who are enrolled in events with the selected event types"); ?>
                                    </label>
                                    <label class="radio" for="distribution-eventtype-target-faculty">
                                        <input type="radio" name="distribution_eventtype_target_option" id="distribution-eventtype-target-faculty" value="faculty" />
                                        <?php echo $translate->_("Faculty who taught events with the selected event types"); ?>
                                    </label>
                                    <label class="radio" for="distribution-eventtype-target-event">
                                        <input type="radio" name="distribution_eventtype_target_option" id="distribution-eventtype-target-event" value="event" />
                                        <?php echo $translate->_("Events with the selected event types"); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="rotation_schedule_target_options" class="target-options">
                            <div class="control-group">
                                <label class="control-label form-required"><?php echo $translate->_("Assessments will be delivered for"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-rs-target-self">
                                        <input type="radio" name="distribution_rs_target_option"
                                               id="distribution-rs-target-self" value="self"/>
                                        <?php echo $translate->_("The targets for this Distribution are the assessors (self assessment)"); ?>
                                    </label>
                                    <label class="radio" for="distribution-rs-target-learner">
                                        <input type="radio" name="distribution_rs_target_option"
                                               id="distribution-rs-target-learner" value="learner"/>
                                        <?php echo $translate->_("The targets for this Distribution are learners"); ?>
                                    </label>
                                    <label class="radio" for="distribution-rs-target-faculty">
                                        <input type="radio" name="distribution_rs_target_option"
                                               id="distribution-rs-target-faculty" value="faculty"/>
                                        <?php echo $translate->_("The targets for this Distribution are faculty members"); ?>
                                    </label>
                                    <label class="radio" for="distribution-rs-target-block">
                                        <input type="radio" name="distribution_rs_target_option"
                                               id="distribution-rs-target-block" value="block"/>
                                        <?php echo $translate->_("The target for this Distribution is the rotation itself"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="rs-target-learner-options" class="hide rs-target-option">
                                <div class="control-group">
                                    <label class="control-label form-required"><?php echo $translate->_("Learner Options"); ?></label>
                                    <div class="controls">
                                        <label class="radio" for="distribution-rs-target-all">
                                            <input type="radio" name="distribution_rs_target_learner_option"
                                                   id="distribution-rs-target-all" value="all"/>
                                            <?php echo $translate->_("All learners in this rotation"); ?>
                                        </label>
                                        <label class="radio" for="distribution-rs-target-individual">
                                            <input type="radio" name="distribution_rs_target_learner_option"
                                                   id="distribution-rs-target-individual" value="individual"/>
                                            <?php echo $translate->_("Specific learners in this rotation"); ?>
                                        </label>
                                    </div>
                                </div>
                                <div id="rs-target-learner-service" class="hide rs-target-learner-sub-option">
                                    <div class="control-group">
                                        <label class="control-label form-required"><?php echo $translate->_("Learners from"); ?></label>
                                        <div class="controls">
                                            <label class="checkbox" for="distribution-rs-target-onservice">
                                                <input type="checkbox"
                                                       name="distribution_rs_target_learner_service[]"
                                                       id="distribution-rs-target-onservice" value="onservice"/>
                                                <?php echo $translate->_("My Program"); ?>
                                            </label>
                                            <label class="checkbox" for="distribution-rs-target-offservice">
                                                <input type="checkbox"
                                                       name="distribution_rs_target_learner_service[]"
                                                       id="distribution-rs-target-offservice" value="offservice"/>
                                                <?php echo $translate->_("Outside of my program"); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="rs-target-learner-individual" class="hide rs-target-learner-sub-option">
                                    <div class="control-group">
                                        <label class="control-label form-required" for="choose-targets-rs-individual-btn"><?php echo $translate->_("Learners from"); ?></label>
                                        <div class="controls">
                                            <button id="choose-targets-rs-individual-btn"
                                                    class="btn btn-search-filter"><?php echo $translate->_("Browse Learners"); ?>
                                                <i class="icon-chevron-down btn-icon pull-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="rs-target-additional-learners" class="control-group hide">
                                    <label class="control-label"><?php echo $translate->_("Additional Learners"); ?></label>
                                    <div class="controls">
                                        <label class="checkbox" for="distribution-rs-target-additional-learners">
                                            <input type="checkbox" name="distribution_rs_target_additional_learners"
                                                   id="distribution-rs-target-additional-learners" value="yes"/>
                                            <?php echo $translate->_("I'd like to add specific learners from outside this program"); ?>
                                        </label>
                                    </div>
                                </div>
                                <div id="rs-target-individual-learners" class="control-group hide">
                                    <label class="control-label" for="choose-targets-rs-additional-learners"><?php echo $translate->_("Add Additional Learners"); ?></label>
                                    <div class="controls">
                                        <button id="choose-targets-rs-additional-learners"
                                                class="btn btn-search-filter"><?php echo $translate->_("Browse Additional Learners"); ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="rs-target-faculty-options" class="hide rs-target-option">
                                <div class="control-group">
                                    <label for="choose-targets-rs-faculty" class="control-label form-required"><?php echo $translate->_("Add Faculty Members"); ?></label>
                                    <div class="controls">
                                        <button id="choose-targets-rs-faculty"
                                                class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty"); ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                        <button class="btn space-left choose-associated-faculty-btn" data-state="select"><?php echo $translate->_("Select Associated Faculty"); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="specific_dates_target_options" class="target-options">
                            <div class="control-group">
                                <label for="target-options" class="control-label form-required"><?php echo $translate->_("Assessments will be delivered for"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-target-self">
                                        <input type="radio" name="distribution_target_option"
                                               id="distribution-target-self" value="self"/>
                                        <?php echo $translate->_("The targets for this Distribution are the assessors (self assessment)"); ?>
                                    </label>
                                    <label class="radio" for="distribution-target-faculty">
                                        <input type="radio" name="distribution_target_option"
                                               id="distribution-target-faculty" value="faculty"/>
                                        <?php echo $translate->_("Select faculty members <span class=\"muted\"><strong>Course Contacts</strong></span>"); ?>
                                    </label>
                                    <label class="radio" for="distribution-target-internal">
                                        <input type="radio" name="distribution_target_option"
                                               id="distribution-target-internal" value="grouped_users"/>
                                        <?php echo $translate->_("Select grouped learners by <span class=\"muted\"><strong>Cohorts</strong> or <strong>Courses</strong></span>"); ?>
                                    </label>
                                    <label class="radio" for="distribution-target-course">
                                        <input type="radio" name="distribution_target_option"
                                               id="distribution-target-course" value="course"/>
                                        <?php echo $translate->_("Select a Course"); ?>
                                    </label>
                                    <label class="radio" for="distribution-target-external">
                                        <input type="radio" name="distribution_target_option"
                                               id="distribution-target-external" value="individual_users"/>
                                        <?php echo $translate->_("Select individuals regardless of role"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="select-targets-grouped" class="control-group hide target-option">
                                <label for="choose-targets-btn" class="control-label form-required"><?php echo $translate->_("Select Targets"); ?></label>
                                <div class="controls">
                                    <input id="choose-targets-btn-default-text" type="hidden" value="<?php echo $translate->_("Browse Targets"); ?>">
                                    <button id="choose-targets-btn" class="btn btn-search-filter"><?php echo $translate->_("Browse Targets"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="select-targets-faculty" class="control-group hide target-option">
                                <label for="choose-targets-faculty-btn" class="control-label form-required"><?php echo $translate->_("Select Targets"); ?></label>
                                <div class="controls">
                                    <button id="choose-targets-faculty-btn"
                                            class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty Members"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                    <button class="btn space-left choose-associated-faculty-btn" data-state="select"><?php echo $translate->_("Select Associated Faculty"); ?></button>
                                </div>
                            </div>
                            <div id="select-targets-course" class="control-group hide target-option">
                                <label for="choose-target-course-btn" class="control-label form-required"><?php echo $translate->_("Select a Course"); ?></label>
                                <div class="controls">
                                    <button id="choose-target-course-btn"
                                            class="btn btn-search-filter"><?php echo $translate->_("Browse Courses"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="select-targets-individual" class="hide targets-type-selector target-option">
                                <div class="control-group">
                                    <label for="targets-search" class="control-label form-required"><?php echo $translate->_("Select Targets"); ?></label>
                                    <div id="autocomplete-container" class="controls">
                                        <input id="targets-search" type="text" class="form-control search"
                                               name="targets_search"
                                               placeholder="<?php echo $translate->_("Type to search for targets..."); ?>">
                                        <div>
                                            <div id="target-autocomplete-list-container"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="target-lists">
                                    <div id="target-list-internal" class="hide">
                                        <h3 id="selected-targets-list-heading"><?php echo $translate->_("Targets"); ?></h3>
                                        <div id="internal-targets-list-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="attempt-limit-controls" class="control-group">
                            <label class="control-label form-required"><?php echo $translate->_("Target Attempt Options"); ?></label>
                            <div class="controls">
                                <label for="attempts-scope-targets" class="radio">
                                    <input id="attempts-scope-targets" type="radio" name="attempts_scope" value="targets" />
                                    <?php
                                    $input_min = "<input type=\"text\" name=\"min_target_attempts\" id=\"min_target_attempts\" value=\"1\" class=\"input-mini\" disabled=\"disabled\" />";
                                    $input_max = "<input type=\"text\" name=\"max_target_attempts\" id=\"max_target_attempts\" value=\"1\" class=\"input-mini\" disabled=\"disabled\" />";
                                    echo sprintf($translate->_("Assessors can assess <strong>each</strong> target a minimum of %1\$s times and a maximum of %2\$s times."), $input_min, $input_max);
                                    ?>
                                </label>
                            </div>
                            <div class="controls">
                                <label for="attempts-scope-overall" class="radio">
                                    <input id="attempts-scope-overall" type="radio" name="attempts_scope" value="overall" />
                                    <?php
                                    $input_min = "<input type=\"text\" name=\"min_overall_attempts\" id=\"min_overall_attempts\" value=\"1\" class=\"input-mini\" disabled=\"disabled\" />";
                                    $input_max = "<input type=\"text\" name=\"max_overall_attempts\" id=\"max_overall_attempts\" value=\"1\" class=\"input-mini\" disabled=\"disabled\" />";
                                    echo sprintf($translate->_("Assessors are asked to complete a minimum of %1\$s and a maximum of %2\$s attempts across all targets."), $input_min, $input_max);
                                    ?>
                                </label>
                            </div>
                        </div>
                        <div id="repeat-target-controls" class="control-group hide">
                            <label class="control-label"><?php echo $translate->_("Multiple Target Assessments"); ?></label>
                            <div class="controls">
                                <label for="repeat-targets" class="checkbox">
                                    <input id="repeat-targets" type="checkbox" name="repeat_targets"/>
                                    <?php echo $translate->_("Assessors can assess the same target multiple times"); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-4" class="wizard-step hide">
                        <div class="distribution-instruction">
                            <!--<h2><?php echo $translate->_("Choose Assessors"); ?></h2>-->
                            <!--<p><?php echo sprintf($translate->_("Please select an option to indicate if the assessor for this distribution are %s users, or users external to %s."), APPLICATION_NAME, APPLICATION_NAME); ?></p>-->
                        </div>
                        <div id="distribution-delegation-warning" class="alert alert-info hide">
                            <?php echo $translate->_("<strong>Note</strong>: <strong>Delegation</strong> was the selected distribution method, users selected in this step will be presented as a list to the delegator, who will then select the assessors from that list."); ?>
                        </div>
                        <div id="eventtype-assessor-options" class="hide assessor-options">
                            <div class="control-group">
                                <label class="control-label form-required"><?php echo $translate->_("Assessor Options"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-eventtype-assessor-learner">
                                        <input type="radio" name="distribution_eventtype_assessor_option" id="distribution-eventtype-assessor-learner" value="learner"/>
                                        <?php echo $translate->_("The assessors for this Distribution are learners enrolled in the event"); ?>
                                    </label>
                                    <label class="radio" for="distribution-eventtype-assessor-faculty">
                                        <input type="radio" name="distribution_eventtype_assessor_option" id="distribution-eventtype-assessor-faculty" value="faculty"/>
                                        <?php echo $translate->_("The assessors for this Distribution are faculty members associated with the event"); ?>
                                    </label>
                                    <label class="radio" for="distribution-eventtype-assessor-external">
                                        <input type="radio" name="distribution_eventtype_assessor_option" id="distribution-eventtype-assessor-external" value="individual_users"/>
                                        <?php echo $translate->_("Select individuals regardless of role <span class=\"muted\"><strong>Individuals</strong> or <strong>external</strong> assessors</span>"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="eventtype-assessor-learner-options" class="hide eventtype-assessor-option">
                                <div class="control-group">
                                    <label class="control-label form-required"><?php echo $translate->_("Learner Options"); ?></label>
                                    <div class="controls">
                                        <label class="radio" for="distribution-eventtype-learners-attended">
                                            <input type="radio" name="distribution_eventtype_learners" id="distribution-eventtype-learners-attended" value="attended"/>
                                            <?php echo $translate->_("Send this assessment to all enrolled learners that attended the event"); ?>
                                        </label>
                                        <label class="radio" for="distribution-eventtype-learners">
                                            <input type="radio" name="distribution_eventtype_learners" id="distribution-eventtype-learners" value="all-learners"/>
                                            <?php echo $translate->_("Send this assessment to all enrolled learners, even if they <strong>did not</strong> attend the event"); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <!--
                            <div id="eventtype-assessor-faculty-options" class="hide eventtype-assessor-option">
                                <div class="control-group">
                                    <label for="choose-assessors-eventtype-faculty-btn" class="control-label form-required"><?php echo $translate->_("Add additional faculty"); ?></label>
                                    <div class="controls">
                                        <button id="choose-assessors-eventtype-faculty-btn" class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty"); ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            -->
                            <div id="eventtype-select-assessors-individual" class="hide eventtype-assessor-option">
                                <div class="control-group">
                                    <label for="eventtype-external-assessors-search" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                    <div id="eventtype-autocomplete-container" class="controls">
                                        <input id="eventtype-external-assessors-search" type="text" class="form-control search" name="eventtype_external_assessors_search" placeholder="<?php echo $translate->_("Type to search for assessors..."); ?>">
                                        <div id="eventtype-autocomplete">
                                            <div id="eventtype-autocomplete-list-container"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="eventtype-assessor-lists">
                                    <div id="eventtype-assessor-list-internal" class="">
                                        <h3 id="eventtype-selected-assessors-list-heading"><?php echo $translate->_("Assessors"); ?></h3>
                                        <div id="eventtype-assessors-list-container"></div>
                                    </div>
                                </div>
                                <div id="eventtype-external-assessors-controls" class="hide">
                                    <div class="form-inline">
                                        <input id="eventtype-assessor-firstname" name="assessor_firstname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("Firstname"); ?>"/>
                                        <input id="eventtype-assessor-lastname" name="assessor_lastname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("Lastname"); ?>"/>
                                        <input id="eventtype-assessor-email" name="assessor_email" class="form-control input-medium" type="text" placeholder="<?php echo $translate->_("Email Address"); ?>"/>
                                        <a id="eventtype-add-external-user-btn" href="#" class="btn btn-mini btn-success"><?php echo $translate->_("Add Assessor"); ?></a>
                                        <a id="eventtype-cancel-assessor-btn" href="#" class="btn btn-mini">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="rotation_schedule_assessor_options" class="hide assessor-options">
                            <div class="control-group">
                                <label class="control-label form-required"><?php echo $translate->_("Assessor Options"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-rs-assessor-learner">
                                        <input type="radio" name="distribution_rs_assessor_option" id="distribution-rs-assessor-learner" value="learner"/>
                                        <?php echo $translate->_("The assessors for this Distribution are learners"); ?>
                                    </label>
                                    <label class="radio" for="distribution-rs-assessor-faculty">
                                        <input type="radio" name="distribution_rs_assessor_option" id="distribution-rs-assessor-faculty" value="faculty"/>
                                        <?php echo $translate->_("The assessors for this Distribution are faculty members"); ?>
                                    </label>
                                    <label class="radio" for="distribution-rs-assessor-external">
                                        <input type="radio" name="distribution_rs_assessor_option" id="distribution-rs-assessor-external" value="individual_users"/>
                                        <?php echo $translate->_("Select individuals regardless of role <span class=\"muted\"><strong>Individuals</strong> or <strong>external</strong> assessors</span>"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="rs-assessor-learner-options" class="hide rs-assessor-option">
                                <div class="control-group">
                                    <label class="control-label form-required"><?php echo $translate->_("Learner Options"); ?></label>
                                    <div id="distribution-rs-assessor-all-container" class="controls">
                                        <label class="radio" for="distribution-rs-assessor-all">
                                            <input type="radio" name="distribution_rs_assessor_learner_option"
                                                   id="distribution-rs-assessor-all" value="all"/>
                                            <?php echo $translate->_("All learners in this rotation"); ?>
                                        </label>
                                        <label class="radio" for="distribution-rs-assessor-individual">
                                            <input type="radio" name="distribution_rs_assessor_learner_option"
                                                   id="distribution-rs-assessor-individual" value="individual"/>
                                            <?php echo $translate->_("Specific learners in this rotation"); ?>
                                        </label>
                                    </div>
                                </div>
                                <div id="rs-assessor-learner-service" class="hide rs-assessor-learner-sub-option">
                                    <div class="control-group">
                                        <label class="control-label form-required"><?php echo $translate->_("Learners from"); ?></label>
                                        <div class="controls">
                                            <label class="checkbox" for="distribution-rs-onservice">
                                                <input type="checkbox" name="distribution_rs_learner_service[]"
                                                       id="distribution-rs-onservice" value="onservice"/>
                                                <?php echo $translate->_("My Program"); ?>
                                            </label>
                                            <label class="checkbox" for="distribution-rs-offservice">
                                                <input type="checkbox" name="distribution_rs_learner_service[]"
                                                       id="distribution-rs-offservice" value="offservice"/>
                                                <?php echo $translate->_("Outside of my program"); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="rs-assessor-learner-individual" class="hide rs-assessor-learner-sub-option">
                                    <div class="control-group">
                                        <label class="control-label form-required" for="choose-assessors-rs-individual-btn"><?php echo $translate->_("Learners from"); ?></label>
                                        <div class="controls">
                                            <button id="choose-assessors-rs-individual-btn"
                                                    class="btn btn-search-filter"><?php echo $translate->_("Browse Learners"); ?>
                                                <i class="icon-chevron-down btn-icon pull-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="rs-additional-learners" class="control-group hide">
                                    <label class="control-label form-required"><?php echo $translate->_("Additional Learners"); ?></label>
                                    <div class="controls">
                                        <label class="checkbox" for="distribution-rs-additional-learners">
                                            <input type="checkbox" name="distribution_rs_additional_learners"
                                                   id="distribution-rs-additional-learners" value="yes"/>
                                            <?php echo $translate->_("I'd like to add specific learners from outside this program"); ?>
                                        </label>
                                    </div>
                                </div>
                                <div id="rs-individual-learners" class="control-group hide">
                                    <label class="control-label form-required" for="choose-assessors-rs-additional-learners"><?php echo $translate->_("Add Additional Learners"); ?></label>
                                    <div class="controls">
                                        <button id="choose-assessors-rs-additional-learners"
                                                class="btn btn-search-filter"><?php echo $translate->_("Browse Additional Learners"); ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="rs-assessor-faculty-options" class="hide rs-assessor-option">
                                <div class="control-group">
                                    <label for="choose-assessors-rs-faculty" class="control-label form-required"><?php echo $translate->_("Add Faculty Members"); ?></label>
                                    <div class="controls">
                                        <button id="choose-assessors-rs-faculty"
                                                class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty"); ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                        <button class="btn space-left choose-associated-faculty-btn" data-state="select"><?php echo $translate->_("Select Associated Faculty"); ?></button>
                                    </div>
                                </div>
                            </div>
                            <div id="rs-select-assessors-individual" class="hide assessor-type-selector rs-assessor-option">
                                <div class="control-group">
                                    <label for="rs-external-assessors-search" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                    <div id="rs-autocomplete-container" class="controls">
                                        <input id="rs-external-assessors-search" type="text" class="form-control search" name="external_assessors_search" placeholder="<?php echo $translate->_("Type to search for assessors..."); ?>">
                                        <div id="rs-autocomplete">
                                            <div id="rs-autocomplete-list-container"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="rs-assessor-lists">
                                    <div id="rs-assessor-list-internal" class="hide">
                                        <h3 id="rs-selected-assessors-list-heading"><?php echo $translate->_("Assessors"); ?></h3>
                                        <div id="rs-internal-assessors-list-container"></div>
                                    </div>
                                </div>
                                <div id="rs-external-assessors-controls" class="hide">
                                    <div class="form-inline">
                                        <input id="rs-assessor-firstname" name="assessor_firstname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("Firstname"); ?>"/>
                                        <input id="rs-assessor-lastname" name="assessor_lastname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("Lastname"); ?>"/>
                                        <input id="rs-assessor-email" name="assessor_email" class="form-control input-medium" type="text" placeholder="<?php echo $translate->_("Email Address"); ?>"/>
                                        <a id="rs-add-external-user-btn" href="#" class="btn btn-mini btn-success"><?php echo $translate->_("Add Assessor"); ?></a>
                                        <a id="rs-cancel-assessor-btn" href="#" class="btn btn-mini">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="specific_dates_assessor_options" class="hide assessor-options">
                            <div class="control-group">
                                <label for="assessor-options" class="control-label form-required"><?php echo $translate->_("Assessor Options"); ?></label>
                                <div class="controls">
                                    <label class="radio" for="distribution-assessor-faculty">
                                        <input type="radio" name="distribution_assessor_option"
                                               id="distribution-assessor-faculty" value="faculty"/>
                                        <?php echo $translate->_("Select faculty members <span class=\"muted\"><strong>Course Contacts</strong></span>"); ?>
                                    </label>
                                    <label class="radio" for="distribution-assessor-internal">
                                        <input type="radio" name="distribution_assessor_option"
                                               id="distribution-assessor-internal" value="grouped_users"/>
                                        <?php echo $translate->_("Select grouped learners <span class=\"muted\"><strong>Cohorts</strong> or <strong>courses</strong></span>"); ?>
                                    </label>
                                    <label class="radio" for="distribution-assessor-external">
                                        <input type="radio" name="distribution_assessor_option"
                                               id="distribution-assessor-external" value="individual_users"/>
                                        <?php echo $translate->_("Select individuals <span class=\"muted\"><strong>Individuals</strong> or <strong>external</strong> assessors</span>"); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="select-assessors-grouped" class="control-group hide assessor-option">
                                <label for="choose-assessors-btn" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                <div class="controls">
                                    <input id="choose-assessors-btn-default-text" type="hidden" value="<?php echo $translate->_("Browse Assessors"); ?>">
                                    <button id="choose-assessors-btn" class="btn btn-search-filter"><?php echo $translate->_("Browse Assessors"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i></button>
                                </div>
                            </div>
                            <div id="select-assessors-faculty" class="control-group hide assessor-option">
                                <label for="choose-assessors-faculty-btn" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                <div class="controls">
                                    <button id="choose-assessors-faculty-btn" class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty Members"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                    <button class="btn space-left choose-associated-faculty-btn" data-state="select"><?php echo $translate->_("Select Associated Faculty"); ?></button>
                                </div>
                            </div>
                            <div id="select-assessors-individual" class="hide assessor-type-selector assessor-option">
                                <div class="control-group">
                                    <label for="external-assessors-search" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                    <div id="autocomplete-container" class="controls">
                                        <input id="external-assessors-search" type="text"
                                               class="form-control search"
                                               name="external_assessors_search"
                                               placeholder="<?php echo $translate->_("Type to search for assessors..."); ?>">
                                        <div id="assessor-autocomplete">
                                            <div id="autocomplete-list-container"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="assessor-lists">
                                    <div id="assessor-list-internal" class="hide">
                                        <h3 id="selected-assessors-list-heading"><?php echo $translate->_("Assessors"); ?></h3>
                                        <div id="internal-assessors-list-container"></div>
                                    </div>
                                </div>
                                <div id="external-assessors-controls" class="hide">
                                    <div class="form-inline">
                                        <input id="assessor-firstname" name="assessor_firstname"
                                               class="form-control input-small" type="text"
                                               placeholder="<?php echo $translate->_("Firstname"); ?>"/>
                                        <input id="assessor-lastname" name="assessor_lastname"
                                               class="form-control input-small" type="text"
                                               placeholder="<?php echo $translate->_("Lastname"); ?>"/>
                                        <input id="assessor-email" name="assessor_email"
                                               class="form-control input-medium" type="text"
                                               placeholder="<?php echo $translate->_("Email Address"); ?>"/>
                                        <a id="add-external-user-btn" href="#"
                                           class="btn btn-mini btn-success"><?php echo $translate->_("Add Assessor"); ?></a>
                                        <a id="cancel-assessor-btn" href="#" class="btn btn-mini">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-feedback-options" class="hide">
                            <div class="control-group">
                                <label class="control-label"><?php echo $translate->_("Feedback Options"); ?></label>
                                <div class="controls">
                                    <label for="feedback-required" class="checkbox">
                                        <input id="feedback-required" type="checkbox" name="feedback_required"
                                               value="1"/>
                                        <?php echo $translate->_("Feedback is required for this assessment"); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="wizard-step-5" class="wizard-step hide">
                        <?php echo display_generic($translate->_("That's it, just hit <strong>Save Distribution</strong> below and you're finished! <br /><br /> If you would like to set up additional reviewers aside from admin staff with implicit permission to review the results of this distribution, just check off the box below and fill in the form provided.")); ?>
                        <div id="select-authors" class="authors-type-selector author-option space-below">
                            <div class="control-group">
                                <label for="author-type" class="control-label"><?php echo $translate->_("Author Type"); ?></label>
                                <div class="controls">
                                    <select class="span5" name="author_type" id="author-type" class="span3">
                                        <?php foreach ($DEFAULT_TEXT_LABELS["contact_types"] as $contact_type => $contact_type_name) : ?>
                                            <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="authors-search" class="control-label"><?php echo $translate->_("Distribution Authors"); ?></label>
                                <div id="autocomplete-container" class="controls">
                                    <input id="authors-search" type="text" class="form-control search"
                                           name="authors_search"
                                           placeholder="<?php echo $translate->_("Type to search for Authors..."); ?>">
                                    <div>
                                        <div id="author-autocomplete-list-container"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="author-lists" class="space-below">
                                <div id="author-list-section" class="hide">
                                    <h3 id="selected-authors-list-heading"><?php echo $translate->_("Authors"); ?></h3>
                                    <div id="authors-list-container"></div>
                                </div>
                            </div>
                        </div>
                        <div id="distribution-approver-option" class="hide">
                            <div class="control-group">
                                <label class="control-label"><?php echo $translate->_("Reviewer Option"); ?></label>
                                <div class="controls">
                                    <label for="approver-required" class="checkbox">
                                        <input id="approver-required" type="checkbox" name="approver_required" value="1"/>
                                        <?php echo $translate->_("Reviewer is required for this assessment"); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="select-approvers" class="hide space-above">
                            <div class="control-group">
                                <label for="distribution-approver-results" class="control-label form-nrequired"><?php echo $translate->_("Distribution Reviewer"); ?></label>
                                <div class="controls">
                                    <button id="distribution-approver-results" class="btn btn-search-filter"><?php echo $translate->_("Browse Reviewers"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="flagging_notifications" class="control-label form-required"><?php echo $translate->_("Flagged Response Notifications"); ?></label>
                            <div class="controls">
                                <select name="flagging_notifications" id="flagging_notifications">
                                    <option value="disabled">-- Disabled --</option>
                                    <option value="reviewers"><?php echo $translate->_("Assessment Reviewers"); ?></option>
                                    <option value="pcoordinators"><?php echo $translate->_("Program Coordinators"); ?></option>
                                    <option value="directors"><?php echo $translate->_("Program Directors"); ?></option>
                                    <option value="authors"><?php echo $translate->_("Distribution Authors"); ?></option>
                                </select>
                                <div class="content-small"><?php echo $translate->_("Send <strong>Email Notifications</strong> to the selected group whenever a <i>flagged</i> response is selected"); ?></div>
                            </div>
                        </div>
                        <div id="review-options" class="hide space-above">
                            <div class="control-group">
                                <label for="distribution-review-results" class="control-label form-required"><?php echo $translate->_("Assessment Reviewers"); ?></label>
                                <div class="controls">
                                    <button id="distribution-review-results" class="btn btn-search-filter"><?php echo $translate->_("Browse Users"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                            <?php /* // This code will be revisited later when requirements for it are reevaluated.
                            <div id="reviewer-release-options" class="hide">
                                <div class="control-group">
                                    <label for="distribution-results-start-date" class="control-label form-required"><?php echo $translate->_("Release Results Starting"); ?></label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="distribution-results-start-date" type="text"
                                                   class="input-small datepicker"
                                                   value="<?php echo date("Y-m-d", strtotime("today")) ?>"
                                                   name="distribution_results_start_date"/>
                                <span class="add-on pointer">
                                    <i class="icon-calendar"></i>
                                </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="distribution-results-start-time" type="text"
                                                   class="input-mini timepicker" value="00:00"
                                                   name="distribution_results_start_time"/>
                                <span class="add-on pointer">
                                    <i class="icon-time"></i>
                                </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label for="distribution-results-end-date" class="control-label form-required"><?php echo $translate->_("Release Results Until"); ?></label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="distribution-results-end-date" type="text"
                                                   class="input-small datepicker"
                                                   value="<?php echo date("Y-m-d", strtotime("today + 1 week")) ?>"
                                                   name="distribution_results_end_date"/>
                                <span class="add-on pointer">
                                    <i class="icon-calendar"></i>
                                </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="distribution-results-end-time" type="text"
                                                   class="input-mini timepicker" value="23:59"
                                                   name="distribution_results_end_time"/>
                                <span class="add-on pointer">
                                    <i class="icon-time"></i>
                                </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label for="distribution-results-response-quantity" class="control-label form-required"><?php echo $translate->_("Quantity of responses before results can be reviewed"); ?></label>
                                    <div class="controls">
                                        <input id="distribution-results-response-quantity" class="input-small" type="text" name="distribution_results_response_quantity" value="0"/>
                                    </div>
                                </div>
                            </div>
                         */ ?>
                        </div>
                    </div>

                    <div id="wizard-step-6" class="wizard-step hide"></div>
                    <input type="hidden" id="selected-delegation-option" name="selected_delegation_option" value="">
                    <?php if ($distribution_data) : ?>
                        <?php if ($distribution_data && isset($distribution_data["form_id"]) && isset($distribution_data["course_id"])) : ?>
                            <input id="form_<?php echo $distribution_data["form_id"]?>" class="search-target-control form_search_target_control form-selector" type="hidden" name="distribution_form" value="<?php echo $distribution_data["form_id"]; ?>" data-label="<?php echo html_encode($distribution_data["form_title"]); ?>">
                            <input id="rs_course_<?php echo $distribution_data["course_id"]?>" class="search-target-control rs_course_search_target_control choose-course-selector" type="hidden" name="course_id" value="<?php echo $distribution_data["course_id"]; ?>" data-label="<?php echo $distribution_data["course_name"]; ?>">
                        <?php endif;?>
                        <?php if ($distribution_data && isset($distribution_data["cperiod_id"]) && isset($distribution_data["cperiod_id"])) : ?>
                            <input id="curriculum_period_<?php echo $distribution_data["cperiod_id"]?>" class="search-target-control curriculum_period_search_target_control curriculum-period-selector" type="hidden" name="cperiod_id" value="<?php echo $distribution_data["cperiod_id"]; ?>" data-label="<?php echo (isset($distribution_data["cperiod_id"]) && isset($curriculum_periods[$distribution_data["cperiod_id"]])) ? html_encode($curriculum_periods[$distribution_data["cperiod_id"]]["target_label"]) : ""; ?>">
                        <?php endif;?>
                    <?php endif;?>
                    <input type="hidden" id="distribution-referrer-url" data-refurl="<?php echo $DISTRIBUTION_REFERRER ?>" />
                </form>
            </div>
            <div class="distribution-editor-footer">
                <div class="row-fluid">
                    <?php if (!$DISTRIBUTION_REFERRER || $mode=="create"): ?>
                        <a href="<?php echo ENTRADA_URL ?>/admin/assessments/distributions" id="distribution-cancel-close-btn" class="btn btn-default pull-left"><?php echo $translate->_("Cancel"); ?></a>
                        <a href="<?php echo ENTRADA_URL ?>/admin/assessments/distributions" id="distribution-success-close-btn" class="hide btn btn-default pull-left"><?php echo $translate->_("Close Editor"); ?></a>
                    <?php else: ?>
                        <a href="<?php echo html_decode($DISTRIBUTION_REFERRER) ?>" id="distribution-cancel-close-btn" class="btn btn-default pull-left"><?php echo $translate->_("Cancel and Return to Previous Page"); ?></a>
                        <a href="<?php echo html_decode($DISTRIBUTION_REFERRER) ?>" id="distribution-success-close-btn" class="hide btn btn-default pull-left"><?php echo $translate->_("Return to Previous Page"); ?></a>
                    <?php endif; ?>
                    <button id="distribution-previous-step" class="btn btn-default hide"><?php echo $translate->_("Previous Step"); ?></button>
                    <button id="distribution-next-step" class="btn btn-primary"><?php echo $translate->_("Next Step"); ?></button>
                </div>
            </div>
        </div>
        <?php if ($distribution_data): ?>
        <input type="hidden" id="editor-load-distribution-flag" class="hide" data-adistribution-id="<?php echo $DISTRIBUTION_ID?>" value="1"/>
        <?php else: ?>
        <input type="hidden" id="editor-load-distribution-flag" class="hide" data-adistribution-id="0" data-create-mode="1" value="1"/>
        <?php endif; ?>
        <?php
    } // endif !ERROR
} // endif ACL allowed
?>