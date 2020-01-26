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
 * Module:    Assessments
 * Area:    Public index
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $HEAD[] = "<script type=\"text/javascript\">var proxy_id = " . $ENTRADA_USER->getActiveId() . ";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var assessment_index_view_preference = '" . (isset($PREFERENCES["assessment_index_view_preference"]) ? $PREFERENCES["assessment_index_view_preference"] : "table") . "';</script>";
    $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/image/user-photo-upload.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script src=\"". ENTRADA_URL . "/javascript/jquery/jquery.imgareaselect.min.js\" type=\"text/javascript\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/imgareaselect-default.css\" />";

    // When a PDF fails to generate and/or send, we notify the user via this parameter.
    if (array_key_exists("pdf-error", $_GET)) {
        add_error($translate->_("Unable to create PDF."));
        echo display_error();
    }

    $entrada_actor = array(
        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
    );
    $subject = array(
        "subject_id" => $entrada_actor["actor_proxy_id"],
        "subject_type" => "proxy_id",
        "subject_scope" => "internal"
    );

    $assessment_tasks = new Entrada_Assessments_Tasks($entrada_actor);
    $task_card = new Views_Assessments_Assessment_Card();
    $load_more_button = new Views_Assessments_Controls_LoadMoreButton();

    $assessment_tasks->addCommonJavascriptTranslations();

    $course_names = array();
    $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
    if ($courses) {
        foreach ($courses as $course) {
            $course_names[] = $course->getCourseName();
        }
    }

    // Display the Faculty tab if the current user is a course owner.
    $is_course_owner = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::isCourseOwner($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

    $hidden_external_assessor_id_list = isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"])
        ? $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"]
        : array();

    // Display the learners tab if the user is a tutor/academic advisor or a report admin.
    $render_my_learners = false;
    if (($ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) 
            && Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($ENTRADA_USER->getProxyId(), $ENTRADA_USER->getActiveOrganisation())
        )
        || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)
        || $is_course_owner
        || $ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)
    ) {
        $render_my_learners = true;
    }

    /**
     * Pull the raw filters from the session (needed for labeling purposes).
     */
    $raw_filters = isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["selected_filters"])
        ? $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["selected_filters"]
        : array();

    /**
     * Build an array of the user's filters (currently set in the session), in a consistent format for the task object to use.
     */
    $user_filters = $assessment_tasks->getFilterValuesFromSession("assessments");

    $pending_limit = 60; // Limit to 60 for pending assessments
    $pending_offset = 0;

    $completed_limit = 9; // Default limit on non-pending queries is 9 at a time (3 rows of 3)
    $completed_offset = 0;

    /**
     * Query for the non-complete tasks
     */
    $pending_tasks_lists = array("assessor-pending");
    $pending_tasks_filters = array(
        "limit" => $pending_limit,
        "offset" => $pending_offset,
        "sort_order" => "asc",
        "sort_column" => 28 // Column #28 is delivery date
    );
    $assessment_tasks->setFilters(array_merge($pending_tasks_filters, $user_filters));
    $pending_tasks = $assessment_tasks->fetchAssessmentTaskList($pending_tasks_lists, $entrada_actor["actor_proxy_id"]);
    $pending_tasks_counts = $assessment_tasks->fetchAssessmentTaskListCount($pending_tasks_lists, $entrada_actor["actor_proxy_id"]);

    /**
     * Query for the completed tasks where this user is the assessor.
     */
    $assessor_completed_tasks_lists = array("assessor-completed");
    $assessor_completed_tasks_filters = array(
        "limit" => $completed_limit,
        "offset" => $completed_offset,
        "sort_order" => "desc",
        "sort_column" => 6 // Column #6 is task completion date
    );
    $assessor_completed_filters_merged = array_merge($assessor_completed_tasks_filters, $user_filters);
    $assessment_tasks->setFilters($assessor_completed_filters_merged);
    $assessor_completed_tasks = $assessment_tasks->fetchAssessmentTaskList($assessor_completed_tasks_lists, $entrada_actor["actor_proxy_id"]);
    $assessor_completed_tasks_counts = $assessment_tasks->fetchAssessmentTaskListCount($assessor_completed_tasks_lists, $entrada_actor["actor_proxy_id"]);

    /**
     * Query for the completed tasks where this user is the target
     **/
    $target_completed_tasks_lists = array("target-completed");
    $target_completed_tasks_filters = array(
        "limit" => $completed_limit,
        "offset" => $completed_offset,
        "sort_order" => "desc",
        "sort_column" => 6 // Column #6 is task completion date
    );
    $target_completed_filters_merged = array_merge($target_completed_tasks_filters, $user_filters);

    $user_filters["task_type"] = array("assessments");
    $assessment_tasks->setFilters($target_completed_filters_merged);
    $target_completed_tasks = $assessment_tasks->fetchAssessmentTaskList($target_completed_tasks_lists, $entrada_actor["actor_proxy_id"]);
    $target_completed_tasks_counts = $assessment_tasks->fetchAssessmentTaskListCount($target_completed_tasks_lists, $entrada_actor["actor_proxy_id"]);

    /**
     * Show load more buttons if there are more targets than we've displayed. We support loading more in all cases, but only
     * effectively "load more" only for the completed tasks.
     */
    $load_more_assessor_pending = $assessment_tasks->determineMoreToLoad("assessor-pending", $pending_tasks_counts, $pending_limit);
    $load_more_target_completed = $assessment_tasks->determineMoreToLoad("target-completed", $target_completed_tasks_counts, $completed_limit);
    $load_more_assessor_completed = $assessment_tasks->determineMoreToLoad("assessor-completed", $assessor_completed_tasks_counts, $completed_limit);

    $assessment_tasks->updateAssessmentPreferences("assessments");
    $assessment_tasks->getAssessmentPreferences("assessments");
    ?>
    <script type="text/javascript">
        jQuery(function ($) {

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

            $("#advanced-search").advancedSearch({
                api_url: "<?php echo ENTRADA_URL . "/assessments?section=api-tasks"; ?>",
                resource_url: ENTRADA_URL,
                filters: {
                    distribution_method: {
                        label: "<?php echo $translate->_("Distribution Method"); ?>",
                        data_source: "get-distribution-methods"
                    },
                    cperiod: {
                        label: "<?php echo $translate->_("Curriculum Period"); ?>",
                        data_source: "get-user-cperiod"
                    },
                    course: {
                        label: "<?php echo $translate->_("Course"); ?>",
                        data_source: "get-user-course"
                    },
                    task_status: {
                        label: "<?php echo $translate->_("Task Status"); ?>",
                        data_source: "get-task-status-list"
                    }
                },
                no_results_text: "<?php echo $translate->_("No tasks found matching the search criteria"); ?>",
                results_parent: $("#assessment_tasks_filter_container"),
                search_target_form_action: "<?php echo ENTRADA_URL . "/" . $MODULE ?>",
                width: 350,
                reload_page_flag: true,
                save_filters_method: "save-assessments-filters",
                remove_filters_method: "remove-assessments-filters",
                list_selections: false
            });
        });
    </script>

    <h1><?php echo $translate->_("Assessment &amp; Evaluation"); ?></h1>

    <a href="<?php echo ENTRADA_URL . "/assessments/reports/?proxy_id=".$ENTRADA_USER->getActiveID()."&role=target" ?>" id="target-reports-button" class="btn pull-right space-below"><?php echo $translate->_("My Reports");?></a>

    <ul id="form_index_tabs" class="nav nav-tabs assessments">
        <li class="task-tab"><a href="#forms_to_complete" data-toggle="tab"><?php echo $translate->_("Assessment Tasks"); ?></a></li>
        <li class="task-tab"><a href="#form_results" data-toggle="tab"><?php echo $translate->_("Tasks Completed on Me"); ?></a></li>
        <li class="task-tab"><a href="#completed_forms" data-toggle="tab"><?php echo $translate->_("My Completed Tasks"); ?></a></li>
        <?php if ($render_my_learners): ?>
            <li class="task-tab"><a href="#learners" data-toggle="tab"><?php echo $translate->_("My Learners"); ?></a></li>
        <?php endif; ?>
        <?php if ($is_course_owner): // Display the faculty tab content if the user is a course owner. ?>
            <li class="task-tab"><a href="#faculty" data-toggle="tab"><?php echo $translate->_("Faculty"); ?></a></li>
        <?php endif; ?>
    </ul>

    <div id="assessments" class="tab-content">
        <?php
            // Render the filter controls. This also renders what filters are currently active.
            $filter_view = new Views_Assessments_Controls_AssessmentFilters(array("id" => "assessment_tasks_filter_container"));
            $filter_view->render(
                array(
                    "filter_mode" => "assessments",
                    "start_date" => $user_filters["start_date"],
                    "end_date" => $user_filters["end_date"],
                    "search_term" => $user_filters["search_term"],
                    "selected_filters" => $user_filters,
                    "filter_labels" => $raw_filters
                )
            );
        ?>
        <div class="tab-pane" id="forms_to_complete">
            <input type="hidden" name="current-page" class="current_page" value="incomplete"/>
            <h2><?php echo $translate->_("Assessment Tasks"); ?></h2>
            <div class="btn-group pull-right space-below">
                <button type="button" class="select-all-to-download select-all-incomplete btn pull-left">
                    <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                    <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                </button>
                <a class="btn btn-default pull-left generate-pdf-btn incomplete"
                   href="#"
                   title="<?php echo $translate->_("Download PDF(s)"); ?>"
                   data-pdf-unavailable="0">
                    <?php echo $translate->_("Download PDF(s)") ?>
                </a>
            </div>
            <div class="clearfix"></div>
            <?php if (empty($pending_tasks["assessor"]["pending"])) : ?>
                <div class="form-search-message"><?php echo $translate->_("You currently have no Assessments to complete."); ?></div>
            <?php else: ?>
                <ul class="assessment-tasks task-list-assessor-pending incomplete">
                    <?php foreach ($pending_tasks["assessor"]["pending"] as $task): ?>
                        <li>
                            <?php $task_card->render($task); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($load_more_assessor_pending):
                $load_more_button->render(
                    array(
                        "limit" => $pending_limit,
                        "offset" => $pending_limit, // offset = the next amount from 0, i.e., the current limit
                        "append_to" => "task-list-assessor-pending",
                        "fetch_type" => "assessor-pending",
                        "fetch_mode" => "assessments",
                        "search_term" => $user_filters["search_term"],
                        "start_date" => $user_filters["start_date"],
                        "end_date" => $user_filters["end_date"],
                        "subject" => urlencode(json_encode($subject)),
                        "filters" => urlencode(json_encode($user_filters)),
                    )
                );
            endif; ?>
        </div>

        <div class="tab-pane" id="form_results">
            <input type="hidden" name="current-page" class="current_page" value="completed_on_me"/>
            <h2><?php echo $translate->_("Tasks Completed on Me"); ?></h2>
            <div class="btn-group pull-right space-below">
                <button type="button" class="select-all-to-download completed_on_me btn pull-left">
                    <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                    <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                </button>
                <a class="btn btn-default pull-left generate-pdf-btn completed_on_me" href="#" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0"><?php echo $translate->_("Download PDF(s)") ?></a>
            </div>
            <div class="clearfix"></div>
            <?php if (empty($target_completed_tasks["target"]["completed"])): ?>
                <div class="form-search-message"><?php echo $translate->_("No assessments have been completed on you."); ?></div>
            <?php else: ?>
                <ul class="assessment-tasks task-list-target-completed completed_on_me">
                    <?php foreach ($target_completed_tasks["target"]["completed"] as $task): ?>
                        <li>
                            <?php $task_card->render($task); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($load_more_target_completed):
                $load_more_button->render(
                    array(
                        "limit" => $completed_limit,
                        "offset" => $completed_limit, // offset = the next amount from 0, i.e., the current limit
                        "append_to" => "task-list-target-completed",
                        "fetch_type" => "target-completed",
                        "fetch_mode" => "assessments",
                        "search_term" => $user_filters["search_term"],
                        "start_date" => $user_filters["start_date"],
                        "end_date" => $user_filters["end_date"],
                        "subject" => urlencode(json_encode($subject)),
                        "filters" => urlencode(json_encode($user_filters)),
                    )
                );
            endif; ?>
        </div>

        <div class="tab-pane" id="completed_forms">
            <h2><?php echo $translate->_("Forms I've Completed"); ?></h2>
            <input type="hidden" name="current-page" class="current_page" value="completed"/>
            <div class="btn-group pull-right space-below">
                <button type="button" class="select-all-to-download completed btn pull-left">
                    <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                    <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                </button>
                <a class="btn btn-default pull-left generate-pdf-btn completed" href="#" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0"><?php echo $translate->_("Download PDF(s)") ?></a>
            </div>
            <div class="clearfix"></div>
            <?php if (empty($assessor_completed_tasks["assessor"]["completed"])) : ?>
                <div class="form-search-message"><?php echo $translate->_("You currently have no completed Assessments to review."); ?></div>
            <?php else: ?>
                <ul class="assessment-tasks completed task-list-assessor-completed">
                    <?php foreach ($assessor_completed_tasks["assessor"]["completed"] as $task): ?>
                        <li>
                            <?php $task_card->render($task); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($load_more_assessor_completed):
                $load_more_button->render(
                    array(
                        "limit" => $completed_limit,
                        "offset" => $completed_limit, // offset = the next amount from 0, i.e., the current limit
                        "append_to" => "task-list-assessor-completed",
                        "fetch_type" => "assessor-completed",
                        "fetch_mode" => "assessments",
                        "search_term" => $user_filters["search_term"],
                        "start_date" => $user_filters["start_date"],
                        "end_date" => $user_filters["end_date"],
                        "subject" => urlencode(json_encode($subject)),
                        "filters" => urlencode(json_encode($user_filters)),
                    )
                );
                ?>
            <?php endif; ?>
        </div>

        <?php
        if ($render_my_learners) {
            $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
            $curriculum_periods = false;
            if ($curriculum_types) {
                foreach ($curriculum_types as $curriculum_type) {
                    $periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                    if ($periods) {
                        foreach ($periods as $period) {
                            $curriculum_periods[] = $period;
                        }
                    }
                }
            }

            $assessment_tasks->getAssessmentPreferences("assessments");
            $cperiod_id_preference = isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["learners"]["cperiod_id"])
                ? $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["learners"]["cperiod_id"]
                : false;

            if (isset($_GET["generate-pdf"])) {
                $selected_cperiod = $_GET["selected-cperiod"];
                $header = $translate->_("Program(s): ");
                $names = array();
                foreach ($course_names as $key => $course_name) {
                    $names[] = $course_name;
                }
                $header .= implode("<br>", $names);
                $header .= "<br>" . $translate->_("Curriculum Period: ");

                $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                $assessment_user = new Entrada_Utilities_AssessmentUser();
                $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin);

                if ($selected_cperiod != 0) {
                    $curriculum_period = Models_Curriculum_Period::fetchRowByID($selected_cperiod);
                    $header .= date("Y-m-d", $curriculum_period->getStartDate()) . " - " . date("Y-m-d", $curriculum_period->getFinishDate()) . ($curriculum_period->getCurriculumPeriodTitle() ? " " . $curriculum_period->getCurriculumPeriodTitle() : "");
                    foreach ($learners as $key => $learner) {
                        if (!in_array($selected_cperiod, $learner["cperiod_ids"])) {
                            unset($learners[$key]);
                        }
                    }
                } else {
                    $header .= $translate->_("All");
                }

                $search_term = "";
                if (isset($_GET["search-term"])) {
                    $search_term = $_GET["search-term"];
                }

                if ($search_term != "") {
                    foreach ($learners as $key => $learner) {
                        $full_name = strtolower($learner->getFirstname() . " " . $learner->getLastname());
                        if (strpos($full_name, $search_term) === false) {
                            unset($learners[$key]);
                        }
                    }
                }

                $assessment_user->cacheUserCardPhotos($learners);
                $list = new Views_Assessments_UserCard_List(
                    array("id" => "learner-cards",
                        "class" => "learner-card",
                        "users" => $learners,
                        "assessment_label" => "",
                        "view_assessment_label" => $translate->_("View assessments &rtrif;"),
                        "no_results_label" => $translate->_("No users found matching your search.")
                    )
                );
                $assessment_html = $list->render(array("hide" => "true"), false);

                if ($assessment_html) {
                    $target_user = Models_User::fetchRowByID($ENTRADA_USER->getActiveId());
                    $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                    if ($assessment_pdf->configure() && $target_user) {
                        $title = sprintf($translate->_("Learners for %s"), $target_user->getFullname(false));
                        $html = $assessment_pdf->generateEnrollmentHTML($assessment_html, $title, $header);
                        $filename = $assessment_pdf->buildFilename($title, ".pdf");
                        if (!$assessment_pdf->send($filename, $html)) {
                            // Unable to send, so redirect away from this page and show an error.
                            ob_clear_open_buffers();
                            $error_url = $assessment_pdf->buildURI("/assessments", $_SERVER["REQUEST_URI"]);
                            $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                            Header("Location: $error_url");
                            die();
                        }
                    } else {
                        echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
                        application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
                    }
                }
            }
            ?>
            <div class="tab-pane" id="learners">
                <input type="hidden" name="current-page" class="current_page" value="learner_cards"/>
                <div id="msgs"></div>
                <div id="assessment-learner-container">
                    <form id="learner-search-form" class="learner-search-form" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2"; ?>" method="POST">
                        <div id="search-bar" class="search-bar">
                            <div class="row-fluid space-below medium">
                                <input type="text" id="learner-search" placeholder="<?php echo $translate->_("Search Learners"); ?>" class="input-large search-icon">
                                <a id="generate-pdf" href="?generate-pdf=true" name="generate-pdf" class="btn btn-success"><?php echo $translate->_("Download Enrolment"); ?></a>
                                <div class="control-group pull-right">
                                    <label for="learner-curriculum-period-select"><?php echo $translate->_("Curriculum Period: "); ?></label>
                                    <select id="learner-curriculum-period-select">
                                        <option value="0"><?php echo $translate->_("All"); ?></option>
                                        <?php foreach ($curriculum_periods as $curriculum_period) : ?>
                                            <option value="<?php echo $curriculum_period->getCperiodID(); ?>" <?php echo($cperiod_id_preference == $curriculum_period->getCperiodID() ? "selected=\"selected\"" : ""); ?>>
                                                <?php echo date("Y-m-d", $curriculum_period->getStartDate()) . " - " . date("Y-m-d", $curriculum_period->getFinishDate()) . ($curriculum_period->getCurriculumPeriodTitle() ? " " . $curriculum_period->getCurriculumPeriodTitle() : ""); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="search-container" class="hide space-below medium"></div>
                        <div id="learner-summary"></div>
                        <div id="assessment-msgs">
                            <div id="assessment-learners-loading" class="hide">
                                <p><?php echo $translate->_("Loading Learners..."); ?></p>
                                <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
                            </div>
                        </div>
                        <div id="learner-detail-container">
                            <?php
                            // Instantiate the AssessmentUser utility class and fetch the users that will populate the "My Learners" tab
                            $assessment_user = new Entrada_Utilities_AssessmentUser();
                            $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                            $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin);
                            $assessment_user->cacheUserCardPhotos($learners);

                            if ($learners) {
                                foreach ($learners as &$learner) {
                                    $learner["stage_code"] = "";
                                    $learner["stage_name"] = "";
                                    /**
                                     * Instantiate the CBME visualization abstraction layer
                                     */
                                    $cbme_progress_api = new Entrada_CBME_Visualization(array(
                                        "actor_proxy_id" => $learner["id"],
                                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                        "datasource_type" => "progress",
                                    ));

                                    $learner_stage = $cbme_progress_api->getLearnerStage($learner["id"], $learner["course_id"]);
                                    if ($learner_stage) {
                                        $learner["stage_code"] = $learner_stage["objective_code"];
                                        $learner["stage_name"] = $learner_stage["objective_name"];
                                    }
                                }
                            }
                            // Instantiate the Assessment_Learner view and render the list of learners
                            $list = new Views_Assessments_UserCard_List(
                                array(
                                    "id" => "learner-cards",
                                    "class" => "learner-card",
                                    "users" => $learners,
                                    "assessment_label" => "",
                                    "view_assessment_label" => $translate->_("Assessments &rtrif;"),
                                    "no_results_label" => $translate->_("No users found matching your search."),
                                    "logbook_url" => ENTRADA_URL . "/logbook?proxy_id="
                                )
                            );
                            $list->render();
                            ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }

        if ($is_course_owner) : ?>
            <div class="tab-pane" id="faculty">
                <input type="hidden" name="current-page" class="current_page" value="faculty_cards"/>
                <div id="internal-faculty">
                    <div id="msgs"></div>
                    <div id="assessment-faculty-container">
                        <form id="faculty-search-form" class="faculty-search-form" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2"; ?>" method="POST">
                            <div id="search-bar" class="search-bar">
                                <div class="row-fluid space-below medium">
                                    <input type="text" id="faculty-search" placeholder="<?php echo $translate->_("Search Faculty"); ?>" class="input-large search-icon">
                                    <input type="button" id="change-faculty-card-visibility" data-show-faculty="hide" value="<?php echo $translate->_("Show Hidden Faculty"); ?>" class="space-left btn btn-default">
                                </div>
                            </div>
                            <div id="faculty-detail-container">
                                <?php
                                // Instantiate the AssessmentUser utility class and fetch the users that will populate the "My Faculty" tab
                                $assessment_user = new Entrada_Utilities_AssessmentUser();
                                $course_contact = new Entrada_Utilities_AssessmentUser();

                                $faculty = $course_contact->getAssessorFacultyList($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation());
                                $assessment_user->cacheUserCardPhotos($faculty);

                                // Instantiate the Assessment_Learner view and render the list of learners
                                $list = new Views_Assessments_UserCard_List(
                                    array(
                                        "id" => "faculty-cards",
                                        "class" => "faculty-card",
                                        "users" => $faculty,
                                        "assessment_label" => "",
                                        "view_assessment_label" => $translate->_("View assessment tasks &rtrif;"),
                                        "no_results_label" => $translate->_("No users found matching your search."),
                                        "group" => "faculty"
                                    )
                                );
                                $list->render(
                                    array(
                                        "url" => ENTRADA_URL,
                                        "hidden_external_assessor_id_list" => $hidden_external_assessor_id_list
                                    )
                                );
                                ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($raw_filters)) : ?>
        <form id="search-targets-form" method="post" action="<?php echo ENTRADA_URL ?>/assessments">
            <?php foreach ($raw_filters as $key => $filter_type) :
                foreach ($filter_type as $target_id => $target_label) : ?>
                    <input id="<?php echo "{$key}_{$target_id}" ?>"
                           class="search-target-control <?php echo "{$key}_search_target_control" ?>"
                           type="hidden"
                           name="<?php echo "{$key}[]" ?>"
                           value="<?php echo $target_id ?>"
                           data-id="<?php echo $target_id ?>"
                           data-filter="<?php echo $key ?>"
                           data-label="<?php echo html_encode($target_label) ?>"
                    />
                <?php endforeach;
            endforeach; ?>
        </form>
    <?php endif;

    $edit_external_email_modal = new Views_Assessments_Modals_EditExternalEmail();
    $edit_external_email_modal->render();

    $deletion_reasons = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID();
    $remove_task_modal = new Views_Assessments_Modals_RemoveTask();
    $remove_task_modal->render(array("deletion_reasons" => $deletion_reasons));

    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array("action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment"));

    $template_view = new Views_Assessments_Templates_AssessmentCard();
    $template_view->render();

    $image_upload_modal = new Views_User_ImageUploadModal();
    $image_upload_modal->render();
}