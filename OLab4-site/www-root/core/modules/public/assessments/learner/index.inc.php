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
 * This page displays several different lists of learner assessment
 * tasks to their academic advisors.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_ASSESSMENTS_LEARNERS")) {
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
    $learner_fullname = "";
    if (isset($PROXY_ID) && $PROXY_ID) {
        $assessment_learner = Models_User::fetchRowByID($PROXY_ID);
        if ($assessment_learner) {
            $learner_fullname = "{$assessment_learner->getFirstname()} {$assessment_learner->getLastname()}";
            $learner_assessment_page_title = sprintf($translate->_("%s's Assessments"), $learner_fullname);
            if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($PROXY_ID), "read", true) 
                || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)
                || $ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($PROXY_ID), "read", true)
            ) {
                $BREADCRUMB[] = array("url" => "", "title" => $learner_assessment_page_title);
            } else {
                add_error($translate->_("Unfortunately, you do not have permission to review the completed assessments for this learner."));
                echo display_error();
            }
        } else {
            add_error($translate->_("Please ensure you provide a valid user id."));
            echo display_error();
        }
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }

    if (has_error()) {
        echo display_error();
    }

    if (!has_error()) {
        $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\">var proxy_id = " . $PROXY_ID . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
        $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css?release=" . html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";

        $entrada_actor = array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
        );
        $subject = array(
            "subject_id" => $PROXY_ID,
            "subject_type" => "proxy_id",
            "subject_scope" => "internal"
        );
        $assessment_tasks = new Entrada_Assessments_Tasks($entrada_actor);
        $task_card = new Views_Assessments_Assessment_Card();
        $load_more_button = new Views_Assessments_Controls_LoadMoreButton();

        $assessment_tasks->addCommonJavascriptTranslations();

        /**
         * Pull the raw filters from the session (needed for labeling purposes).
         */
        $raw_filters = isset($_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["selected_filters"])
            ? $_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["selected_filters"]
            : array();

        /**
         * Build an array of the user's filters (currently set in the session), in a consistent format for the task object to use.
         */

        $user_filters = $assessment_tasks->getFilterValuesFromSession("learner");

        $course_utility = new Models_CBME_Course();
        $course_list = $course_utility->getActorCourses(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            $PROXY_ID
        );

        $course_ids = array();
        if (!empty($course_list["courses"])) {
            foreach ($course_list["courses"] as $course_array) {
                $course_ids[] = $course_array["course_id"];
            }
        }
        $user_filters["limit_course"] = $course_ids; // Hard limit to the courses of the viewer

        $pending_limit = 60;
        $pending_offset = 0;
        $completed_limit = 9; // Default limit on non-pending queries is 9 at a time (3 rows of 3)
        $completed_offset = 0;

        /**
         * Query for Learner tasks (non-completed)
         */
        $pending_task_lists = array("target-upcoming", "assessor-upcoming", "target-pending", "assessor-pending");
        $pending_task_filters = array(
            "limit" => $pending_limit,
            "offset" => $pending_offset,
            "sort_order" => "asc",
            "sort_column" => 28 // delivery date
        );
        $assessment_tasks->setFilters(array_merge($pending_task_filters, $user_filters));
        $pending_tasks = $assessment_tasks->fetchAssessmentTaskList($pending_task_lists, $PROXY_ID);
        $pending_tasks_counts = $assessment_tasks->fetchAssessmentTaskListCount($pending_task_lists, $PROXY_ID);

        /**
         * Query for Learner tasks (completed)
         */
        $completed_task_lists = array("target-completed");
        $completed_task_filters = array(
            "limit" => $completed_limit,
            "offset" => $completed_offset,
            "sort_order" => "desc",
            "sort_column" => 6 // completed date
        );
        $assessment_tasks->setFilters(array_merge($completed_task_filters, $user_filters));
        $completed_tasks = $assessment_tasks->fetchAssessmentTaskList($completed_task_lists, $PROXY_ID);
        $completed_tasks_counts = $assessment_tasks->fetchAssessmentTaskListCount($completed_task_lists, $PROXY_ID);

        /**
         * Show load more buttons if there are more targets than we've displayed. We support loading more in all cases, but only effectively "load more" only for the completed tasks.
         */
        $load_more_assessor_pending = $assessment_tasks->determineMoreToLoad("assessor-pending", $pending_tasks_counts, $pending_limit);
        $load_more_target_pending = $assessment_tasks->determineMoreToLoad("target-pending", $pending_tasks_counts, $pending_limit);
        $load_more_assessor_upcoming = $assessment_tasks->determineMoreToLoad("assessor-upcoming", $pending_tasks_counts, $pending_limit);
        $load_more_target_upcoming = $assessment_tasks->determineMoreToLoad("target-upcoming", $pending_tasks_counts, $pending_limit);
        $load_more_target_completed = $assessment_tasks->determineMoreToLoad("target-completed", $completed_tasks_counts, $completed_limit);

        $assessment_tasks->updateAssessmentPreferences("learner");
        $assessment_tasks->getAssessmentPreferences("learner");
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
                    search_target_form_action: "<?php echo ENTRADA_URL . "/assessments/learner?proxy_id=" . $PROXY_ID?>",
                    width: 350,
                    reload_page_flag: true,
                    save_filters_method: "save-learner-filters",
                    remove_filters_method: "remove-learner-filters",
                    list_selections: false
                });
            });
        </script>
        <h1><?php echo $learner_assessment_page_title; ?></h1>
        <ul id="form_index_tabs" class="nav nav-tabs assessments">
            <li class="task-tab">
                <a href="#form_results" data-toggle="tab"><?php echo $translate->_("Tasks Completed on Learner"); ?></a>
            </li>
            <li class="task-tab">
                <a href="#pending_forms_on_learner" data-toggle="tab"><?php echo $translate->_("Pending Tasks on Learner"); ?></a>
            </li>
            <li class="task-tab">
                <a href="#upcoming_forms_on_learner" data-toggle="tab"><?php echo $translate->_("Upcoming Tasks on Learner"); ?></a>
            </li>
            <li class="task-tab">
                <a href="#forms_to_complete" data-toggle="tab" class="active"><?php echo $translate->_("Learner's Current Tasks"); ?></a>
            </li>
            <li class="task-tab">
                <a href="#upcoming_forms_for_learner" data-toggle="tab"><?php echo $translate->_("Learner's Upcoming Tasks"); ?></a>
            </li>
        </ul>
        <div id="api-messages" class="space-above space-below hide"></div>
        <div id="assessments" class="tab-content">
            <h2 id="tab_title"></h2>

            <?php
            // Render the filter controls. This also renders what filters are currently active.
            $filter_view = new Views_Assessments_Controls_AssessmentFilters(array("id" => "assessment_tasks_filter_container"));
            $filter_view->render(
                array(
                    "filter_mode" => "learner",
                    "start_date" => $user_filters["start_date"],
                    "end_date" => $user_filters["end_date"],
                    "search_term" => $user_filters["search_term"],
                    "selected_filters" => $user_filters,
                    "filter_labels" => $raw_filters
                )
            );
            ?>
            <div class="tab-pane" id="form_results">
                <input type="hidden" name="current-page" class="current_page" value="completed_on_me"/>
                <h2 class="task-list-heading"><?php echo sprintf($translate->_("Tasks Completed on %s"), $learner_fullname); ?></h2>
                <div class="btn-group pull-right space-below space-left">
                    <button type="button" class="select-all-to-download btn pull-left">
                        <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                        <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                    </button>
                    <a class="btn btn-default pull-left generate-pdf-btn completed_on_me" href="#" title="<?php echo $translate->_("Download PDF(s)"); ?>"><?php echo $translate->_("Download PDF(s)") ?></a>
                </div>
                <a href="<?php echo ENTRADA_URL . "/assessments/reports/?proxy_id=$PROXY_ID&role=learner" ?>" id="learner-reports-button" class="btn pull-right space-below"><?php echo $translate->_("Reports for this Learner"); ?></a>
                <div class="clearfix"></div>
                <?php if (empty($completed_tasks["target"]["completed"])): ?>
                    <div class="form-search-message"><p class="no-search-targets"><?php echo $translate->_("Learner currently has no Assessments completed on them."); ?></p></div>
                <?php else: ?>
                    <ul class="assessment-tasks task-list-target-completed completed_on_me">
                        <?php foreach ($completed_tasks["target"]["completed"] as $task): ?>
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
                            "fetch_mode" => "learner",
                            "search_term" => $user_filters["search_term"],
                            "start_date" => $user_filters["start_date"],
                            "end_date" => $user_filters["end_date"],
                            "subject" => urlencode(json_encode($subject)),
                            "filters" => urlencode(json_encode($user_filters)),
                        )
                    );
                endif; ?>
            </div>
            <div class="tab-pane" id="upcoming_forms_on_learner">
                <input type="hidden" name="current-page" class="current_page" value="upcoming"/>
                <h2 class="task-list-heading"><?php echo sprintf($translate->_("Upcoming Tasks on %s"), $learner_fullname); ?></h2>
                <div class="clearfix"></div>
                <?php if (empty($pending_tasks["target"]["upcoming"])): ?>
                    <div class="form-search-message"><p class="no-search-targets"><?php echo $translate->_("No Upcoming Tasks on learner."); ?></p></div>
                <?php else: ?>
                    <ul class="assessment-tasks task-list-target-upcoming upcoming">
                        <?php foreach ($pending_tasks["target"]["upcoming"] as $task): ?>
                            <li>
                                <?php $task_card->render($task); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($load_more_target_upcoming):
                    $load_more_button->render(
                        array(
                            "limit" => $pending_limit,
                            "offset" => $pending_limit, // offset = the next amount from 0, i.e., the current limit
                            "append_to" => "task-list-target-upcoming",
                            "fetch_type" => "target-upcoming",
                            "fetch_mode" => "learner",
                            "search_term" => $user_filters["search_term"],
                            "start_date" => $user_filters["start_date"],
                            "end_date" => $user_filters["end_date"],
                            "subject" => urlencode(json_encode($subject)),
                            "filters" => urlencode(json_encode($user_filters)),
                        )
                    );
                endif; ?>
            </div>
            <div class="tab-pane" id="forms_to_complete">
                <input type="hidden" name="current-page" class="current_page" value="incomplete"/>
                <h2 class="task-list-heading"><?php echo sprintf($translate->_("%s's Assessment Tasks"), $learner_fullname); ?></h2>
                <div class="btn-group pull-right space-below space-left">
                    <button type="button" class="select-all-to-remind btn pull-left">
                        <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                        <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                    </button>
                    <a href="#reminder-modal" class="reminder-btn btn pull-left" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"><?php echo $translate->_("Send Reminders") ?></a>
                </div>
                <div class="btn-group pull-right space-below space-left">
                    <button type="button" class="select-all-to-download btn pull-left">
                        <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                        <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                    </button>
                    <a class="btn btn-default pull-left generate-pdf-btn" href="#"
                       title="<?php echo $translate->_("Download PDF(s)"); ?>"><?php echo $translate->_("Download PDF(s)") ?></a>
                </div>
                <div class="clearfix"></div>
                <?php if (empty($pending_tasks["assessor"]["pending"])): ?>
                    <div class="form-search-message"><p class="no-search-targets"><?php echo $translate->_("Learner has no Assessments to complete"); ?></p></div>
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
                            "fetch_mode" => "learner",
                            "search_term" => $user_filters["search_term"],
                            "start_date" => $user_filters["start_date"],
                            "end_date" => $user_filters["end_date"],
                            "subject" => urlencode(json_encode($subject)),
                            "filters" => urlencode(json_encode($user_filters)),
                        )
                    );
                endif; ?>
            </div>
            <div class="tab-pane" id="pending_forms_on_learner">
                <input type="hidden" name="current-page" class="current_page" value="pending"/>
                <h2 class="task-list-heading"><?php echo sprintf($translate->_("Pending Tasks on %s"), $learner_fullname); ?></h2>
                <div class="btn-group pull-right space-below space-left">
                    <button type="button" class="select-all-to-remind btn pull-left">
                        <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                        <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                    </button>
                    <a href="#reminder-modal" class="reminder-btn btn pull-left" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"><?php echo $translate->_("Send Reminders") ?></a>
                </div>
                <div class="btn-group pull-right space-below space-left">
                    <button type="button" class="select-all-to-download btn pull-left">
                        <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                        <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                    </button>
                    <a class="btn btn-default pull-left generate-pdf-btn" href="#"
                       title="<?php echo $translate->_("Download PDF(s)"); ?>"><?php echo $translate->_("Download PDF(s)") ?></a>
                </div>
                <div class="clearfix"></div>
                <?php if (empty($pending_tasks["target"]["pending"])): ?>
                    <div class="form-search-message"><p class="no-search-targets"><?php echo $translate->_("No Pending Tasks on learner."); ?></p></div>
                <?php else: ?>
                    <ul class="assessment-tasks task-list-target-pending pending">
                        <?php foreach ($pending_tasks["target"]["pending"] as $task): ?>
                            <li>
                                <?php $task_card->render($task); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($load_more_target_pending):
                    $load_more_button->render(
                        array(
                            "limit" => $pending_limit,
                            "offset" => $pending_limit, // offset = the next amount from 0, i.e., the current limit
                            "append_to" => "task-list-target-pending",
                            "fetch_type" => "target-pending",
                            "fetch_mode" => "learner",
                            "search_term" => $user_filters["search_term"],
                            "start_date" => $user_filters["start_date"],
                            "end_date" => $user_filters["end_date"],
                            "subject" => urlencode(json_encode($subject)),
                            "filters" => urlencode(json_encode($user_filters)),
                        )
                    );
                endif; ?>
            </div>
            <div class="tab-pane" id="upcoming_forms_for_learner">
                <input type="hidden" name="current-page" class="current_page" value="future"/>
                <h2 class="task-list-heading"><?php echo sprintf($translate->_("%s's Upcoming Tasks"), $learner_fullname); ?></h2>

                <?php if (empty($pending_tasks["assessor"]["upcoming"])): ?>
                    <div class="form-search-message"><p class="no-search-targets"><?php echo $translate->_("Learner has no Upcoming Tasks."); ?></p></div>
                <?php else: ?>
                    <ul class="assessment-tasks task-list-assessor-upcoming future">
                        <?php foreach ($pending_tasks["assessor"]["upcoming"] as $task): ?>
                            <li>
                                <?php $task_card->render($task); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($load_more_assessor_upcoming):
                    $load_more_button->render(
                        array(
                            "limit" => $pending_limit,
                            "offset" => $pending_limit, // offset = the next amount from 0, i.e., the current limit
                            "append_to" => "task-list-assessor-upcoming",
                            "fetch_type" => "assessor-upcoming",
                            "fetch_mode" => "learner",
                            "search_term" => $user_filters["search_term"],
                            "start_date" => $user_filters["start_date"],
                            "end_date" => $user_filters["end_date"],
                            "subject" => urlencode(json_encode($subject)),
                            "filters" => urlencode(json_encode($user_filters)),
                        )
                    );
                endif; ?>
            </div>
        </div>
        <?php // TODO: Move this to a modal view ?>
        <div class="modal hide fade" id="reminder-modal">
            <form name="reminder-modal-form" id="reminder-modal-form" method="POST" action="<?php echo ENTRADA_URL ?>/assessments/?section=api-tasks">
                <input type="hidden" name="step" value="2"/>
                <input type="hidden" id="subject_id" name="subject_id" value="<?php echo $PROXY_ID; ?>" />
                <input type="hidden" id="subject_type" name="subject_type" value="proxy_id" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>
                    <h3><?php echo $translate->_("Send Reminders"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="reminders-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Reminders sent successfully.")); ?>
                    </div>
                    <div id="reminders-error" class="hide">
                    </div>
                    <div id="reminder-details-section" class="hide">
                        <strong><?php echo $translate->_("A reminder will be sent for the following assessor(s):"); ?></strong>
                        <div id="reminder-details" class="space-below space-above hide">
                            <table id="reminder-summary-table" class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="70%"><?php echo $translate->_("Assessor Name") ?></th>
                                    <th width="30%"><?php echo $translate->_("Number of Notifications") ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="no-reminders-selected" class="hide">
                        <?php echo $translate->_("No tasks selected for reminders."); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <button id="reminder-modal-confirm" name="reminder-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm Reminders"); ?></>
                    </div>
                </div>
            </form>
        </div>
        <?php if (!empty($raw_filters)): ?>
            <form id="search-targets-form" method="post" action="<?php echo ENTRADA_URL . "/assessments/learner?proxy_id=" . $PROXY_ID; ?>">
            <?php foreach ($raw_filters as $key => $filter_type):
                foreach ($filter_type as $target_id => $target_label) {
                    echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
                }
            endforeach; ?>
            </form>
        <?php endif;

        $deletion_reasons = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID();
        $remove_task_modal = new Views_Assessments_Modals_RemoveTask();
        $remove_task_modal->render(array("deletion_reasons" => $deletion_reasons));

        $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
        $pdf_modal->render(array("action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment"));

        $template_view = new Views_Assessments_Templates_AssessmentCard();
        $template_view->render();
    }
}