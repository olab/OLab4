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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_ASSESSMENTS_LEARNERS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";

    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {

    if (isset($PROXY_ID) && $PROXY_ID) {
        $assessment_learner = Models_User::fetchRowByID($PROXY_ID);
        if ($assessment_learner) {
            if ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($PROXY_ID), "read", true) || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)) {
                $BREADCRUMB[] = array("url" => "", "title" => html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname()) . "'s Assessments");

                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var proxy_id = " . $PROXY_ID . ";</script>";
                $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
                $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
                $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

                $JAVASCRIPT_TRANSLATIONS[] = "var assessments_index = {};";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Target = '" . $translate->_("Target") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Rotation = '" . $translate->_("Rotation") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Program = '" . $translate->_("Program") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Start_Date = '" . $translate->_("Start Date") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.End_Date = '" . $translate->_("End Date") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Due_Date = '" . $translate->_("Due Date") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Status = '" . $translate->_("Status") . "';";
                $JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Assessor = '" . $translate->_("Assessor") . "';"; ?>

                <?php
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["selected_filters"])) {
                    $filters = $_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["selected_filters"];
                } else {
                    $filters = array();
                }

                if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["search_term"])) {
                    $search_term = $_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["search_term"];
                } else {
                    $search_term = null;
                }

                if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["start_date"])) {
                    $start_date = $_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["start_date"];
                } else {
                    $start_date = null;
                }

                if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["end_date"])) {
                    $end_date = $_SESSION[APPLICATION_IDENTIFIER]["learner"]["tasks"]["end_date"];
                } else {
                    $end_date = null;
                }

                $assessments_base = new Entrada_Utilities_Assessments_Base();
                $assessments_base->updateAssessmentPreferences("learner");
                $assessments_base->getAssessmentPreferences("learner");

                $assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllFilteredTasks($PROXY_ID, $filters, $search_term, $start_date, $end_date, "assessments", "learner");
                $progress_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($PROXY_ID, $ENTRADA_USER->getActiveOrganisation(), "learner", true, 1, $filters, $search_term, $start_date, $end_date);
                $complete_tasks = $progress_tasks["complete"];
                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($complete_tasks, $filters);
                $inprogress_tasks = $progress_tasks["inprogress"];
                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($inprogress_tasks, $filters);

                $future_tasks_on_learner = Models_Assessments_FutureTaskSnapshot::fetchAllByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc("proxy_id", $PROXY_ID, "learner", $filters, $search_term, $start_date, $end_date);
                if ($future_tasks_on_learner) {
                    $grouped_tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasks($future_tasks_on_learner);
                    Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($grouped_tasks, $filters);
                }

                $current_tasks = Models_Assessments_CurrentTaskSnapshot::fetchAllByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc("proxy_id", $PROXY_ID, "learner", $filters, $search_term, $start_date, $end_date);
                if ($current_tasks) {
                    $grouped_current_tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasksObjects($current_tasks);
                    Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($grouped_current_tasks, $filters);

                    if ($grouped_current_tasks && $inprogress_tasks) {
                        $grouped_current_tasks = array_merge($grouped_current_tasks, $inprogress_tasks);
                    }
                }

                $delegation_assignments = Models_Assessments_Distribution_DelegationAssignment::fetchAllByTargetValue($PROXY_ID, "learner", $filters, $search_term, $start_date, $end_date);
                $progress_records = Models_Assessments_Progress::fetchAllByTargetRecordID($PROXY_ID);
                if ($delegation_assignments && $progress_records) {
                    foreach ($delegation_assignments as $key => $delegation_assignment) {
                        foreach ($progress_records as $progress_record) {
                            if ($delegation_assignment->getAdistributionID() == $progress_record->getAdistributionID() && $delegation_assignment->getDassessmentID() == $progress_record->getDassessmentID() && $progress_record->getProgressValue() != "inprogress") {
                                unset($delegation_assignments[$key]);
                            }
                        }
                    }
                }

                $future_tasks = Models_Assessments_FutureTaskSnapshot::fetchAllByAssessorTypeAssessorValueSortDeliveryDateRotationDatesDesc($PROXY_ID, "learner", $filters, $search_term, $start_date, $end_date, false);
                if ($future_tasks) {
                    $grouped_future_tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasks($future_tasks);
                    Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($grouped_future_tasks, $filters);
                }
                ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        <?php if($search_term == "") { ?>
                            $("#task-search").val("");
                        <?php }?>

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
                            api_url : "<?php echo ENTRADA_URL . "/" . $MODULE . "?section=api-tasks" ; ?>",
                            resource_url: ENTRADA_URL,
                            filters : {
                                distribution_method : {
                                    label : "<?php echo $translate->_("Distribution Method"); ?>",
                                    data_source : "get-distribution-methods"
                                },
                                cperiod : {
                                    label : "<?php echo $translate->_("Curriculum Period"); ?>",
                                    data_source : "get-user-cperiod"
                                },
                                program : {
                                    label : "<?php echo $translate->_("Program"); ?>",
                                    data_source : "get-user-program"
                                }
                            },
                            no_results_text: "<?php echo $translate->_("No tasks found matching the search criteria"); ?>",
                            results_parent: $("#assessment_tasks_filter_container"),
                            search_target_form_action: "<?php echo ENTRADA_URL . "/" . $MODULE . "/learner?proxy_id=" . $PROXY_ID?>",
                            width: 350,
                            reload_page_flag: true,
                            save_filters_method: "save-learner-filters",
                            remove_filters_method: "remove-learner-filters",
                            list_selections: false
                        });
                    });
                </script>
                <h1><?php echo $translate->_(html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname() . "'s Assessments")); ?></h1>
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
                    <div id="assessment_tasks_filter_container">
                        <div class="row-fluid space-below">
                            <div class="input-append">
                                <input type="text" id="task-search" placeholder="<?php echo $translate->_("Search Tasks..."); ?>" <?php echo ($search_term) ? "value=\"$search_term\"" : ""; ?> class="input-large search-icon"/>
                                <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                            </div>
                            <div class="input-append space-left">
                                <input id="task_start_date" placeholder="<?php echo $translate->_("Delivery Start"); ?>" type="text" class="input-small datepicker" <?php echo ($start_date) ? "value=\"" . date("Y-m-d", $start_date) . "\"" : ""; ?>  name="task_start_date"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                            <div class="input-append space-left">
                                <input id="task_end_date" placeholder="<?php echo $translate->_("Delivery End"); ?>" type="text" class="input-small datepicker" <?php echo ($end_date) ? "value=\"" . date("Y-m-d", $end_date) . "\"" : ""; ?> name="task_end_date"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                            <input type="button" class="btn btn-success space-left" id="apply_filters" value="<?php echo $translate->_("Apply Filters"); ?>"/>
                            <input type="button" class="btn btn-default space-left" id="remove_filters" value="<?php echo $translate->_("Remove Filters"); ?>"/>
                        </div>
                        <input type="hidden" name="current-section" id="current_section" value="learner"/>
                        <input type="hidden" name="current-learner" id="current_learner" value="<?php echo html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname()); ?>"/>
                        <input type="hidden" name="proxy-id" id="proxy_id" value="<?php echo html_encode($PROXY_ID); ?>"/>
                        <input type="hidden" name="organisation-id" id="organisation_id" value="<?php echo html_encode($ENTRADA_USER->getActiveOrganisation()); ?>"/>
                        <div id="active-filters"></div>
                    </div>
                    <div class="tab-pane" id="form_results">
                        <input type="hidden" name="current-page" class="current_page" value="completed_on_me"/>
                        <h2 class="task-list-heading"><?php echo sprintf($translate->_("Tasks Completed on %s"), html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname())); ?></h2>
                        <div class="btn-group pull-right space-below space-left">
                            <button type="button" class="select-all-to-download btn pull-left">
                                <span class="label-select"><?php echo $translate->_("Select All");?></span>
                                <span class="label-unselect hide"><?php echo $translate->_("Unselect All");?></span>
                            </button>
                            <a class="btn btn-default pull-left generate-pdf-btn completed_on_me" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
                        </div>
                        <a href="<?php echo ENTRADA_URL . "/assessments/reports/?proxy_id=$PROXY_ID&role=learner" ?>" id="learner-reports-button" class="btn pull-right space-below"><?php echo $translate->_("Reports for this Learner");?></a>
                        <div class="clearfix"></div>
                        <ul id="assessment-tasks" class="completed_on_me">
                        <?php
                            if ($complete_tasks) {
                                foreach ($complete_tasks as $task) {
                                    ?>
                                    <li>
                                        <div class="assessment-task">
                                            <div class="assessment-task-wrapper">
                                                <div class="distribution">
                                                    <div>
                                                        <span class="assessment-task-title"><?php echo html_encode($task->getTitle()); ?></span>
                                                    </div>
                                                    <?php
                                                    if ($task->getScheduleDetails()) {
                                                        ?>
                                                        <div class="label assessment-task-schedule-info-badge">
                                                            <?php echo html_encode($task->getScheduleDetails()); ?>
                                                        </div>
                                                        <?php
                                                    }
                                                    if ($task->getRotationStartDate() && $task->getRotationEndDate()) {
                                                        ?>
                                                        <div class="assessment-task-date-range">
                                                            <em><?php echo html_encode(date("M j, Y", $task->getRotationStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getRotationEndDate())); ?></em>
                                                        </div>
                                                        <?php
                                                    } else if ($task->getStartDate() && $task->getEndDate()) {
                                                        ?>
                                                        <div class="assessment-task-date-range">
                                                            <em><?php echo html_encode(date("M j, Y", $task->getStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getEndDate())); ?></em>
                                                        </div>
                                                        <?php
                                                    }
                                                    if ($task->getDeliveryDate()) {
                                                        ?>
                                                        <div class="assessment-task-date">
                                                            <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())); ?></strong>
                                                        </div>
                                                        <?php
                                                    }
                                                    if ($task->getCompletedDate()) {
                                                        ?>
                                                        <div class="assessment-task-date">
                                                            <?php echo $translate->_("Completed on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getCompletedDate())); ?></strong>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="details">
                                                    <?php echo html_encode($task->getDescription()); ?>
                                                </div>
                                                <div class="assessor">
                                                    <div>Assessor: <strong><?php echo html_encode($task->getAssessor()); ?></strong></div>
                                                    <div class="label assessment-task-meta">
                                                        <?php
                                                        if ($task->getGroup() && $task->getRole() != "external") {
                                                            echo html_encode(ucfirst($task->getGroup())) . " • " . html_encode(str_replace("_", " ", ucfirst($task->getRole())));
                                                        } else {
                                                            echo "External";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="assessment-task-select">
                                                    <div class="fa-wrapper">
                                                        <span class="fa fa-download"></span>
                                                    </div>
                                                    <label class="checkbox">
                                                        <input class="generate-pdf" type="checkbox" name="generate-pdf[]"
                                                               data-assessor-name="<?php echo $task->getAssessor(); ?>"
                                                               data-assessor-value="<?php echo $task->getAssessorValue(); ?>"
                                                               data-targets="<?php echo html_encode(json_encode($task->getTargetInfo())); ?>"
                                                               data-assessment-id="<?php echo $task->getDassessmentID(); ?>"
                                                               data-adistribution-id="<?php echo $task->getDistributionID(); ?>"
                                                               value="<?php echo $task->getDeliveryDate() ? $task->getDeliveryDate() : false; ?>"
                                                        /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks.") ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="assessment-task-link">
                                                <a href="<?php echo html_encode($task->getUrl()); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                                <div class="clearfix"></div>
                            <?php
                            } else {
                                ?>
                                <div class="form-search-message"><?php echo $translate->_("Learner currently has no Assessments completed on them."); ?></div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="tab-pane" id="upcoming_forms_on_learner">
                        <input type="hidden" name="current-page" class="current_page" value="upcoming"/>
                        <h2 class="task-list-heading"><?php echo sprintf($translate->_("Upcoming Tasks on %s"), html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname())); ?></h2>
                        <ul id="assessment-tasks" class="upcoming">
                        <?php
                            if (isset($grouped_tasks) && $grouped_tasks) {
                                foreach ($grouped_tasks as $task) {
                                    $remove_data = array(
                                        "assessor_type"     => $task["assessor_type"],
                                        "assessor_value"    => $task["assessor_value"],
                                        "target_id"         => $task["targets"][0]["target_value"],
                                        "distribution_id"   => $task["adistribution_id"],
                                        "assessment_id"     => null,
                                        "delivery_date"     => $task["delivery_date"]
                                    );
                                    ?>
                                    <li>
                                        <div class="assessment-task">
                                            <div class="assessment-task-wrapper">
                                                <div class="distribution">
                                                    <div>
                                                        <span class="assessment-task-title"><?php echo html_encode($task["title"]); ?></span>
                                                    </div>
                                                    <?php
                                                    if ($task["schedule_details"]) {
                                                        ?>
                                                        <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($task["schedule_details"]); ?></div>
                                                        <?php
                                                    }
                                                    if ($task["rotation_start_date"] && $task["rotation_end_date"]) { ?>
                                                        <div class="assessment-task-date-range">
                                                            <em><?php echo html_encode(date("M j, Y", $task["rotation_start_date"]) . " " . $translate->_("to") . " " . date("M j, Y", $task["rotation_end_date"])); ?></em>
                                                        </div>
                                                        <?php
                                                    }
                                                    if ($task["delivery_date"]) { ?>
                                                        <div class="assessment-task-date">
                                                            <?php echo $translate->_("Will be delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task["delivery_date"])); ?></strong>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="assessor">
                                                    <?php
                                                    if ($task["assessor_value"] && $task["assessor_type"]) {
                                                        if ($task["assessor_type"] == "external") {
                                                            $internal_external = "external";
                                                            $assessor_details = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($task["assessor_value"]);
                                                        } else {
                                                            $internal_external = "internal";
                                                            $assessor_details = Models_User::fetchRowByID($task["assessor_value"]);
                                                        }
                                                        ?>
                                                        <div><?php echo $translate->_("Assessor: "); ?><strong><?php echo html_encode($assessor_details->getFirstname() . " " . $assessor_details->getLastName()); ?></strong></div>
                                                        <div class="label assessment-task-meta">
                                                        <?php
                                                        if ($task["assessor_type"] == "external") {
                                                            echo $translate->_("External");
                                                        } else {
                                                            if ($task["assessor_group"]) {
                                                                echo html_encode(ucfirst($task["assessor_group"])) . " • ";
                                                            }
                                                            echo html_encode(ucfirst($task["assessor_role"]));
                                                        }
                                                        ?>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="assessment-task-link">
                                                <span class="remove" data-assessment='<?php echo json_encode($remove_data);?>' data-toggle="modal" data-target="#remove_form_modal">
                                                    <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                                <div class="clearfix"></div>
                                <?php
                            } else {
                                ?>
                                <div class="form-search-message"><?php echo $translate->_("No Upcoming Tasks on learner."); ?></div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="tab-pane" id="forms_to_complete">
                        <input type="hidden" name="current-page" class="current_page" value="incomplete"/>
                        <h2 class="task-list-heading"><?php echo sprintf($translate->_("%s's Assessment Tasks"), html_encode($assessment_learner->getFirstname() . " " . $assessment_learner->getLastname())); ?></h2>
                        <div class="btn-group pull-right space-below">
                            <button type="button" class="select-all-to-remind btn pull-left">
                                <span class="label-select"><?php echo $translate->_("Select All");?></span>
                                <span class="label-unselect hide"><?php echo $translate->_("Unselect All");?></span>
                            </button>
                            <a href="#reminder-modal" class="reminder-btn btn pull-left" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"><?php echo $translate->_("Send Reminders") ?></a>
                        </div>
                        <div class="clearfix"></div>
                        <ul id="assessment-tasks" class="incomplete">
                        <?php
                            $task_displayed = false;
                            if ($assessment_tasks) {
                                foreach ($assessment_tasks as $task) {
                                    if (!$task->getDistributionDeletedDate()) {
                                        switch ($task->getType()) {
                                            case "assessment" :
                                                if ($task->getMaxOverallAttempts() > $task->getCompletedAttempts()) {
                                                    $task_displayed = true;
                                                    $remove_data = array(
                                                        "assessor_type"     => "internal",
                                                        "assessor_value"    => $task->getAssessorValue(),
                                                        "target_id"         => $task->getTargetID(),
                                                        "distribution_id"   => $task->getDistributionID(),
                                                        "assessment_id"     => $task->getDassessmentID(),
                                                        "delivery_date"     => $task->getDeliveryDate()
                                                    );
                                                    ?>
                                                    <li>
                                                        <div class="assessment-task">
                                                            <div class="assessment-task-wrapper">
                                                                <div class="distribution">
                                                                    <div>
                                                                        <span class="assessment-task-title"><?php echo html_encode($task->getTitle()); ?></span>
                                                                    </div>
                                                                    <?php
                                                                    if ($task->getScheduleDetails()) {
                                                                        ?>
                                                                        <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($task->getScheduleDetails()); ?></div>
                                                                        <?php
                                                                    }
                                                                    if ($task->getEventDetails()) {
                                                                        ?>
                                                                        <div class="label assessment-task-event-info-badge"><?php echo $task->getEventDetails(); ?></div>
                                                                        <div class="assessment-task-date-range">
                                                                            <em><?php echo html_encode($task->getEventTimeframeStart() . " " . $translate->_("to") . " " . $task->getEventTimeframeEnd()); ?></em>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    if ($task->getRotationStartDate() && $task->getRotationEndDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date-range">
                                                                            <em><?php echo html_encode(date("M j, Y", $task->getRotationStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getRotationEndDate())); ?></em>
                                                                        </div>
                                                                        <?php
                                                                    } else if ($task->getStartDate() && $task->getEndDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date-range">
                                                                            <em><?php echo html_encode(date("M j, Y", $task->getStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getEndDate())); ?></em>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    if ($task->getDeliveryDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date">
                                                                            <?php echo $translate->_("Delivered on ")?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())); ?></strong>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <div class="assessment-progress">
                                                                    <span class="progress-title"><?php echo $translate->_("Progress"); ?></span>
                                                                    <?php
                                                                    if ($task->getTotalTargets() > 1) {
                                                                        ?>
                                                                        <span class="pending">
                                                                            <a class="progress-circle tooltip-tag" href="<?php echo ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . html_encode($task->getDistributionID()) . "&dassessment_id=" . html_encode($task->getDassessmentID()) . "&target_status_view=pending"; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesPending()); ?>">
                                                                                <div><?php echo $task->getTargetsPending(); ?></div>
                                                                            </a>
                                                                        </span>
                                                                        <span class="inprogress">
                                                                            <a class="progress-circle tooltip-tag" href="<?php echo ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . html_encode($task->getDistributionID()) . "&dassessment_id=" . html_encode($task->getDassessmentID()) . "&target_status_view=inprogress"; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesInprogress()); ?>">
                                                                                <div><?php echo $task->getTargetsInprogress(); ?></div>
                                                                            </a>
                                                                        </span>
                                                                        <?php
                                                                    } else {
                                                                        if ($task->getTargetsPending()) {
                                                                            echo "<span class=\"pending\"><a class=\"progress-circle tooltip-tag\" href=\"" . html_encode($task->getUrl()) . "\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" . $translate->_($task->getTargetNamesPending()) . "\"><div>" . html_encode($task->getTargetsPending()) . "</div></a></span>";
                                                                        }
                                                                        if ($task->getTargetsInprogress()) {
                                                                            echo "<span class=\"inprogress\"><a class=\"progress-circle tooltip-tag\" href=\"" . html_encode($task->getUrl()) . "\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" . $translate->_($task->getTargetNamesInprogress()) . "\"><div>" . html_encode($task->getTargetsInprogress()) . "</div></a></span>";
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <div class="clearfix"></div>
                                                                </div>
                                                                <div class="details">
                                                                    <?php echo html_encode($task->getDetails()); ?>
                                                                </div>
                                                                <div class="assessment-task-select">
                                                                    <div class="fa-wrapper">
                                                                        <span class="fa fa-bell"></span>
                                                                    </div>
                                                                    <label class="checkbox">
                                                                        <input class="remind" type="checkbox" name="remind[]"
                                                                               data-assessor-name="<?php echo $assessment_learner->getFirstname() . " " . $assessment_learner->getLastname(); ?>"
                                                                               data-assessor-id="<?php echo $assessment_learner->getID() ?>"
                                                                               value="<?php echo html_encode($task->getDassessmentID()); ?>"
                                                                        /><?php echo $translate->_(" Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks."); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="assessment-task-link btn-group">
                                                                <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                                                <span class="remove" data-assessment='<?php echo json_encode($remove_data);?>' data-toggle="modal" data-target="#remove_form_modal">
                                                                    <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <?php
                                                }
                                            break;
                                            case "delegation" :
                                                if (!$task->getDelegationCompleted()) {
                                                    $task_displayed = true;
                                                    $remove_data = array(
                                                        "assessor_type" => null,
                                                        "assessor_value" => $task->getAssessorValue(),
                                                        "target_id" => null,
                                                        "distribution_id" => $task->getDistributionID(),
                                                        "assessment_id" => $task->getDassessmentID(),
                                                        "task_type" => "delegation",
                                                        "delivery_date" => $task->getDeliveryDate()
                                                    );
                                                    ?>
                                                    <li>
                                                        <div class="assessment-task">
                                                            <div class="assessment-task-wrapper">
                                                                <div class="distribution">
                                                                    <div>
                                                                        <span class="assessment-task-title"><?php echo html_encode($task->getTitle()); ?></span>
                                                                    </div>
                                                                    <?php
                                                                    if ($task->getScheduleDetails()) {
                                                                        ?>
                                                                        <div class="label assessment-task-schedule-info-badge"><?php echo $task->getScheduleDetails(); ?></div>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                    <div class="label assessment-task-delegation-badge"><?php echo $translate->_("Delegation Task"); ?></div>
                                                                    <?php
                                                                    if ($task->getRotationStartDate() && $task->getRotationEndDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date-range">
                                                                            <em><?php echo html_encode(date("M j, Y", $task->getRotationStartDate()) . " " .  $translate->_("to") . " " . date("M j, Y", $task->getRotationEndDate())); ?></em>
                                                                        </div>
                                                                        <?php
                                                                    } else if ($task->getStartDate() && $task->getEndDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date-range">
                                                                            <em><?php echo html_encode(date("M j, Y", $task->getStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getEndDate())); ?></em>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    if ($task->getDeliveryDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date">
                                                                            <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())); ?></strong>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    if ($task->getDelegationCompletedDate()) {
                                                                        ?>
                                                                        <div class="assessment-task-date">
                                                                            <?php echo $translate->_("Delegated on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getDelegationCompletedDate())); ?></strong>
                                                                        </div>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </div>
                                                                <div class="assessment-progress">
                                                                    <span class="progress-title no-margin"><?php echo $translate->_("Progress") . " <strong>" . $translate->_("N/A"); ?></strong></span>
                                                                    <div class="clearfix"></div>
                                                                </div>
                                                                <div class="details">
                                                                    <?php echo html_encode($task->getDetails()); ?>
                                                                </div>
                                                                <div class="assessment-task-select">
                                                                    <div class="fa-wrapper">
                                                                        <span class="fa fa-bell"></span>
                                                                    </div>
                                                                    <label class="checkbox">
                                                                        <input class="remind" type="checkbox" name="remind[]"
                                                                               data-assessor-name="<?php echo $assessment_learner->getFirstname() . " " . $assessment_learner->getLastname(); ?>"
                                                                               data-assessor-id="<?php echo $assessment_learner->getID(); ?>"
                                                                               data-adistribution-id="<?php echo $task->getDistributionID(); ?>"
                                                                               data-addelegation-id="<?php echo $task->getDassessmentID(); ?>"
                                                                               data-task-type="delegation"
                                                                               value="<?php echo html_encode($task->getDassessmentID()); ?>"
                                                                        /><?php echo $translate->_(" Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks."); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="assessment-task-link btn-group">
                                                                <a href="<?php echo html_encode($task->getUrl()); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                                                <span class="remove"
                                                                      data-assessment='<?php echo json_encode($remove_data); ?>'
                                                                      data-toggle="modal"
                                                                      data-target="#remove_form_modal">
                                                                    <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <?php
                                                }
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <div class="clearfix"></div>
                                <?php
                            }
                            if (!$task_displayed) {
                                ?>
                                <div class="form-search-message"><?php echo $translate->_("Learner has no Assessments to complete"); ?></div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="tab-pane" id="pending_forms_on_learner">
                        <input type="hidden" name="current-page" class="current_page" value="pending"/>
                        <h2 class="task-list-heading"><?php echo html_encode(sprintf($translate->_("Pending Tasks on %s"),$assessment_learner->getFirstname() . " " . $assessment_learner->getLastname())); ?></h2>
                        <div class="btn-group pull-right space-below">
                            <button type="button" class="select-all-to-remind btn pull-left">
                                <span class="label-select"><?php echo $translate->_("Select All");?></span>
                                <span class="label-unselect hide"><?php echo $translate->_("Unselect All");?></span>
                            </button>
                            <a href="#reminder-modal" class="reminder-btn btn pull-left" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"><?php echo $translate->_("Send Reminders") ?></a>
                        </div>
                        <div class="clearfix"></div>
                        <ul id="assessment-tasks" class="pending">
                            <?php
                            $row_display = false;
                            if (isset($grouped_current_tasks) && $grouped_current_tasks) {
                                $row_display = true;

                                foreach ($grouped_current_tasks as $task) {
                                    $target_id = $task->getTargetID();
                                    if (is_null($target_id)) {
                                        $task_array = $task->toArray();
                                        $target_id = $task_array["targets"][0]["target_value"];
                                    }
                                    $remove_data = array(
                                        "assessor_type"     => $task->getAssessorType(),
                                        "assessor_value"    => $task->getAssessorValue(),
                                        "target_id"         => $target_id,
                                        "distribution_id"   => $task->getDistributionID(),
                                        "assessment_id"     => $task->getDassessmentID(),
                                        "delivery_date"     => $task->getDeliveryDate()
                                    );

                                        if ($task->getAssessorType() == "external") {
                                            $internal_external = "external";
                                            $assessor_details = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($task->getAssessorValue());
                                        } else {
                                            $internal_external = "internal";
                                            $assessor_details = Models_User::fetchRowByID($task->getAssessorValue());
                                        }
                                        ?>
                                        <li>
                                            <div class="assessment-task">
                                                <div class="assessment-task-wrapper">
                                                    <div class="distribution">
                                                        <div>
                                                            <span class="assessment-task-title"><?php echo html_encode($task->getTitle()); ?></span>
                                                        </div>
                                                        <?php
                                                        if ($task->getScheduleDetails()) {
                                                            ?>
                                                            <div class="label assessment-task-schedule-info-badge">
                                                                <?php echo html_encode($task->getScheduleDetails()); ?>
                                                            </div>
                                                            <?php
                                                        }
                                                        if ($task->getRotationStartDate() && $task->getRotationEndDate()) { ?>
                                                            <div class="assessment-task-date-range">
                                                                <em><?php echo html_encode(date("M j, Y", $task->getRotationStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getRotationEndDate())); ?></em>
                                                            </div>
                                                            <?php
                                                        } else if ($task->getStartDate() && $task->getEndDate()) {
                                                            ?>
                                                            <div class="assessment-task-date-range">
                                                                <em><?php echo html_encode(date("M j, Y", $task->getStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $task->getEndDate())); ?></em>
                                                            </div>
                                                            <?php
                                                        }
                                                        if ($task->getDeliveryDate()) { ?>
                                                            <div class="assessment-task-date">
                                                                <?php echo $translate->_("Delivered on ")?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())); ?></strong>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="assessor">
                                                        <?php
                                                        if ($task->getAssessorValue() && $task->getAssessorType()) {
                                                            ?>
                                                            <div><?php echo $translate->_("Assessor: "); ?><strong><?php echo html_encode($assessor_details->getFirstname() . " " . $assessor_details->getLastName()); ?></strong></div>
                                                            <div class="label assessment-task-meta">
                                                            <?php
                                                            if ($task->getAssessorType() == "external") {
                                                                echo $translate->_("External");
                                                            } else {
                                                                if ($task->getGroup()) {
                                                                    echo html_encode(ucfirst($task->getGroup())) . " • ";
                                                                }
                                                                echo html_encode(ucfirst($task->getRole()));
                                                            }
                                                            ?>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if ($task->getDassessmentID()) { ?>
                                                        <div class="assessment-task-select">
                                                            <div class="fa-wrapper">
                                                                <span class="fa fa-bell"></span>
                                                            </div>
                                                            <label class="checkbox">
                                                                <input class="remind" type="checkbox" name="remind[]"
                                                                       data-assessor-name="<?php echo html_encode($assessor_details->getFirstname() . " " . $assessor_details->getLastName()); ?>"
                                                                       data-assessor-id="<?php echo html_encode($task->getAssessorValue()); ?>"
                                                                       value="<?php echo html_encode($task->getDassessmentID()); ?>"
                                                                /><?php echo $translate->_(" Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks."); ?>
                                                            </label>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                                if ($task->getDassessmentID()) {
                                                    ?>
                                                    <div class="assessment-task-link">
                                                        <span class="remove" data-assessment='<?php echo json_encode($remove_data);?>' data-toggle="modal" data-target="#remove_form_modal">
                                                            <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                        </span>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <?php

                                }
                                if ($delegation_assignments) {
                                    foreach ($delegation_assignments as $delegation_assignment) {
                                        $distribution = Models_Assessments_Distribution::fetchRowByID($delegation_assignment->getAdistributionID());
                                        $assessment_utility = new Entrada_Utilities_Assessments_Base();
                                        $assessor = $assessment_utility->getUserByType($delegation_assignment->getAssessorValue(), ($delegation_assignment->getAssessorType() == "external" ? "external" : false));

                                        $assessment = Models_Assessments_Assessor::fetchRowByID($delegation_assignment->getDassessmentID());

                                        $schedule_details = "";
                                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($delegation_assignment->getAdistributionID());
                                        if ($distribution_schedule && $assessment) {
                                            $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                            if ($schedule_record) {
                                                $schedule_details = Entrada_Utilities_Assessments_Base::getConcatenatedBlockString($delegation_assignment->getDassessmentID(), $schedule_record, $assessment->getRotationStartDate(), $assessment->getRotationEndDate(), $distribution->getOrganisationID());
                                            }
                                        }

                                        if ($assessment && $distribution) {
                                            $row_display = true;
                                            $remove_data = array(
                                                "assessor_type" => $delegation_assignment->getAssessorType(),
                                                "assessor_value" => $delegation_assignment->getAssessorValue(),
                                                "target_id" => $delegation_assignment->getTargetValue(),
                                                "distribution_id" => $delegation_assignment->getAdistributionID(),
                                                "assessment_id" => $delegation_assignment->getDassessmentID(),
                                                "delivery_date" => $assessment->getDeliveryDate()
                                            );
                                            ?>
                                            <li>
                                                <div class="assessment-task">
                                                    <div class="assessment-task-wrapper">
                                                        <div class="distribution">
                                                            <div>
                                                                <span class="assessment-task-title"><?php echo html_encode($distribution->getTitle()); ?></span>
                                                            </div>
                                                            <?php
                                                            if ($schedule_details != "") {
                                                                ?>
                                                                <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($schedule_details); ?></div>
                                                                <?php
                                                            }
                                                            if ($assessment->getRotationStartDate() && $assessment->getRotationEndDate()) {
                                                                ?>
                                                                <div class="assessment-task-date-range">
                                                                    <em><?php echo html_encode(date("M j, Y", $assessment->getRotationStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $assessment->getRotationEndDate())); ?></em>
                                                                </div>
                                                                <?php
                                                            } elseif ($assessment->getStartDate() && $assessment->getEndDate()) {
                                                                ?>
                                                                <div class="assessment-task-date-range">
                                                                    <em><?php echo html_encode(date("M j, Y", $assessment->getStartDate()) . " " . $translate->_("to") . " " . date("M j, Y", $assessment->getEndDate())); ?></em>
                                                                </div>
                                                                <?php
                                                            }
                                                            if ($assessment->getDeliveryDate()) {
                                                                ?>
                                                                <div class="assessment-task-date">
                                                                    <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $assessment->getDeliveryDate())); ?></strong>
                                                                </div>
                                                                <?php
                                                            } elseif ($delegation_assignment->getCreatedDate()) {
                                                                ?>
                                                                <div class="assessment-task-date">
                                                                    <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $delegation_assignment->getCreatedDate())); ?></strong>
                                                                </div>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="assessor">
                                                            <div><?php echo $translate->_("Assessor: "); ?><strong><?php echo html_encode($assessor->getFirstname() . " " . $assessor->getLastName()); ?></strong></div>
                                                            <?php
                                                            if ($delegation_assignment->getAssessorType() == "external") {
                                                                ?>
                                                                <div class="label assessment-task-meta">
                                                                    <?php echo $translate->_("External"); ?>
                                                                </div>
                                                                <?php
                                                            } else {
                                                                $user_access = Models_User_Access::fetchAllByUserIDOrganisationID($delegation_assignment->getAssessorValue(), $delegation_assignment->getAdistributionID());
                                                                if ($user_access) {
                                                                    ?>
                                                                    <div class="label assessment-task-meta">
                                                                        <?php echo html_encode(ucfirst($user_access[0]->getGroup()) . " • " . html_encode(str_replace("_", " ", ucfirst($user_access[0]->getRole())))); ?>
                                                                    </div>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="assessment-task-select">
                                                            <div class="fa-wrapper">
                                                                <span class="fa fa-bell"></span>
                                                            </div>
                                                            <label class="checkbox">
                                                                <input class="remind" type="checkbox" name="remind[]"
                                                                       data-assessor-name="<?php echo html_encode($assessor->getFirstname() . " " . $assessor->getLastName()); ?>"
                                                                       data-assessor-id="<?php echo html_encode($delegation_assignment->getAssessorValue()); ?>"
                                                                       value="<?php echo html_encode($delegation_assignment->getDassessmentID()); ?>"
                                                                /><?php echo $translate->_(" Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected assessment tasks."); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="assessment-task-link">
                                                        <span class="remove" data-assessment='<?php echo json_encode($remove_data); ?>' data-toggle="modal" data-target="#remove_form_modal">
                                                            <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </li>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <div class="clearfix"></div>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                        if (!$row_display) {
                            ?>
                            <div class="form-search-message"><?php echo $translate->_("No Pending Tasks on learner."); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="tab-pane" id="upcoming_forms_for_learner">
                        <input type="hidden" name="current-page" class="current_page" value="future"/>
                        <h2 class="task-list-heading"><?php echo html_encode(sprintf($translate->_("%s's Upcoming Tasks"),$assessment_learner->getFirstname() . " " . $assessment_learner->getLastname())); ?></h2>
                        <ul id="assessment-tasks" class="future">
                        <?php
                            if (isset($grouped_future_tasks) && $grouped_future_tasks) {
                                foreach ($grouped_future_tasks as $task) {
                                    $remove_data = array(
                                        "assessor_type"     => $task["assessor_type"],
                                        "assessor_value"    => $task["assessor_value"],
                                        "target_id"         => $task["targets"][0]["target_value"],
                                        "distribution_id"   => $task["adistribution_id"],
                                        "assessment_id"     => null,
                                        "delivery_date"     => $task["delivery_date"] ? $task["delivery_date"] : null
                                    );
                                    ?>
                                    <li>
                                        <div class="assessment-task">
                                            <div class="assessment-task-wrapper future">
                                                <div class="distribution future">
                                                    <div>
                                                        <span class="assessment-task-title"><?php echo html_encode($task["title"]); ?></span>
                                                    </div>
                                                    <?php
                                                    if ($task["schedule_details"]) {
                                                        ?>
                                                        <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($task["schedule_details"]); ?></div>
                                                        <?php
                                                    }
                                                    if ($task["rotation_start_date"] && $task["rotation_end_date"]) {
                                                        ?>
                                                        <div class="assessment-task-date-range">
                                                            <em><?php echo html_encode(date("M j, Y", $task["rotation_start_date"]) . " " . $translate->_("to") . " " . date("M j, Y", $task["rotation_end_date"])); ?></em>
                                                        </div>
                                                        <?php
                                                    }
                                                    if ($task["delivery_date"]) {
                                                        ?>
                                                        <div class="assessment-task-date">
                                                            <?php echo $translate->_("Will be delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task["delivery_date"])); ?></strong>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="assessor future">
                                                    <?php
                                                    if ($task["targets"] && is_array($task["targets"])) {
                                                        foreach ($task["targets"] as $target) {
                                                        ?>
                                                            <div>Target: <strong><?php echo html_encode($target["name"]); ?></strong></div>
                                                            <?php
                                                            if ($target["target_type"] == "proxy_id") {
                                                                ?>
                                                                <div class="label assessment-task-meta">
                                                                    <?php
                                                                    if ($target["group"]) {
                                                                        echo html_encode(ucfirst($target["group"])) . " • ";
                                                                    }
                                                                    echo html_encode(ucfirst($target["role"]));
                                                                    ?>
                                                                </div>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="assessment-task-link">
                                                <span class="remove" data-assessment='<?php echo json_encode($remove_data);?>' data-toggle="modal" data-target="#remove_form_modal">
                                                    <a href="#remove_form_modal"><?php echo $translate->_("Remove Task"); ?></a>
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                                <div class="clearfix"></div>
                            <?php
                            } else {
                                ?>
                                <div class="form-search-message"><?php echo $translate->_("Learner has no Upcoming Tasks."); ?></div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <div class="modal hide fade" id="reminder-modal">
                    <form name="reminder-modal-form" id="reminder-modal-form" method="POST" action="<?php echo ENTRADA_URL ?>/assessments/assessment?section=api-assessment">
                        <input type="hidden" name="step" value="2"/>
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>
                            <h3><?php echo $translate->_("Send Reminders"); ?></h3>
                        </div>
                        <div class="modal-body">
                            <div id="reminders-success" class="space-above space-below hide">
                                <?php echo display_success($translate->_("Reminders sent successfully.")); ?>
                            </div>
                            <div id="reminders-error" class="hide">
                                <?php echo $translate->_("No tasks selected for reminders."); ?>
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
                                <a href="#" id="reminder-modal-confirm" name="reminder-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm Reminders"); ?></a>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
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

    $options = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID();
    ?>
    <div id="remove_form_modal" class="modal hide fade">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3><?php echo $translate->_("Remove Task"); ?></h3>
        </div>
        <div class="modal-body">
            <input type="hidden" id="removing_reason" name="removing_reason" value="" />
            <input type="hidden" id="removing_reason_id" name="removing_reason_id" value="" />
            <input type="hidden" id="current_record_data" name="current_record_data" value="" />
            <div id="remove-msgs"></div>
            <form class="form-horizontal">
                <div class="control-group">
                    <label class="control-label"><?php echo $translate->_("Reason to remove:"); ?></label>
                    <div class="controls">
                        <?php
                        foreach ($options as $option) { ?>
                            <label class="radio">
                                <input data-reason="<?php echo html_encode($option->getDetails()) ?>" type="radio" name="reason" value="<?php echo $option->getID();?>" />
                                <?php echo html_encode($option->getDetails()) ?>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                    <label class="control-label"><?php echo $translate->_("Notes:"); ?></label>
                    <div id="other_reason_div" class="control-group space-above medium">
                        <div class="controls">
                            <textarea rows="4" class="span10" id="other_reason" name="other_reason"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <input type="button" class="btn pull-left" id="cancel_remove" data-dismiss="modal" value="Cancel"/>
            <input type="button" class="btn btn-danger" id="save_remove" value="<?php echo $translate->_("Remove Task"); ?>"/>
        </div>
    </div>
    <?php
    if (!empty($filters)) {
        echo "<form id=\"search-targets-form\" method=\"post\" action=\"". ENTRADA_URL . "/assessments/learner?proxy_id=" . $PROXY_ID . "\">";
        foreach ($filters as $key => $filter_type) {
            foreach ($filter_type as $target_id => $target_label) {
                echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
            }
        }
        echo "</form>";
    }

    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments/distributions?section=api-distributions&current-location=learner"
    ));

    $template_view = new Views_Assessments_Templates_AssessmentCard();
    $template_view->render();
}