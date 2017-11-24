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
$HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";

$JAVASCRIPT_TRANSLATIONS[] = "var assessments_index = {};";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Target = '" . $translate->_("Target") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Rotation = '" . $translate->_("Rotation") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Program = '" . $translate->_("Program") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Start_Date = '" . $translate->_("Start Date") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.End_Date = '" . $translate->_("End Date") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Due_Date = '" . $translate->_("Due Date") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Status = '" . $translate->_("Status") . "';";
$JAVASCRIPT_TRANSLATIONS[] = "assessments_index.Assessor = '" . $translate->_("Assessor") . "';";

// When a PDF fails to generate and/or send, we notify the user via this parameter.
if (isset($_GET["pdf-error"])) {
    add_error($translate->_("Unable to create PDF."));
    echo display_error();
}

// Determine if we show the "Tasks Completed on Me" tab.
// This tab should NOT be visible when the user has a Faculty role in the given organisation.
$show_tasks_completed_on_me = true;
$has_faculty_role = false;
$user_access_roles = Models_User_Access::fetchAllByUserIDOrganisationID($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
foreach ($user_access_roles as $access_role) {
    if ($access_role->getGroup() == "faculty") {
        $has_faculty_role = true;
    }
}

if ($has_faculty_role) {
    $show_tasks_completed_on_me = false;
}

$course_owner = Entrada_Utilities_Assessments_AssessmentTask::isCourseOwner($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

$course_names = array();
$courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
if ($courses) {
    foreach ($courses as $course) {
        $course_names[] = $course->getCourseName();
    }
}

if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["selected_filters"])) {
    $filters = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["selected_filters"];
} else {
    $filters = array();
}

if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["search_term"])) {
    $search_term = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["search_term"];
} else {
    $search_term = null;
}

if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["start_date"])) {
    $start_date = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["start_date"];
} else {
    $start_date = null;
}

if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["end_date"])) {
    $end_date = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["tasks"]["end_date"];
} else {
    $end_date = null;
}

if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"])) {
    $hidden_external_assessor_id_list = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"];
} else {
    $hidden_external_assessor_id_list = array();
}

$assessments_base = new Entrada_Utilities_Assessments_Base();
$assessments_base->updateAssessmentPreferences("assessments");

$limit = 10;
$offset = 0;

$incomplete_assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllFilteredTasks($ENTRADA_USER->getActiveID(), $filters, $search_term, $start_date, $end_date, "assessments", "assessments", false, true);
$limited_complete_assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllFilteredTasks($ENTRADA_USER->getActiveID(), $filters, $search_term, $start_date, $end_date, "assessments", "assessments", false, false, $limit, $offset);
$progress_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), "assessments", true, 1, $filters, $search_term, $start_date, $end_date);
$complete_tasks = $progress_tasks["complete"];
Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($complete_tasks, $filters);
?>
    <script type="text/javascript">
        jQuery(function ($) {
            <?php
            if($search_term == "") {
            ?>
            $("#task-search").val("");
            <?php
            }
            ?>

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
                api_url: "<?php echo ENTRADA_URL . "/" . $MODULE . "?section=api-tasks"; ?>",
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
                    program: {
                        label: "<?php echo $translate->_("Program"); ?>",
                        data_source: "get-user-program"
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
    <ul id="form_index_tabs" class="nav nav-tabs assessments">
        <li class="task-tab"><a href="#forms_to_complete" data-toggle="tab"><?php echo $translate->_("Assessment Tasks"); ?></a></li>
        <?php
        if ($show_tasks_completed_on_me) {
            ?>
            <li class="task-tab"><a href="#form_results" data-toggle="tab"><?php echo $translate->_("Tasks Completed on Me"); ?></a></li>
            <?php
        }
        ?>
        <li class="task-tab"><a href="#completed_forms" data-toggle="tab"><?php echo $translate->_("My Completed Tasks"); ?></a></li>
        <?php if (($ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && (Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($ENTRADA_USER->getProxyId(), $ENTRADA_USER->getActiveOrganisation()))) || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true) || $course_owner): // Display the learners tab if the user is a tutor/academic advisor or a report admin. ?>
            <li class="task-tab"><a href="#learners" data-toggle="tab"><?php echo $translate->_("My Learners"); ?></a></li>
        <?php endif; ?>
        <?php if ($course_owner): // Display the faculty tab content if the user is a course owner. ?>
            <li class="task-tab"><a href="#faculty" data-toggle="tab"><?php echo $translate->_("Faculty"); ?></a></li>
        <?php endif; ?>
    </ul>
<div id="assessments" class="tab-content">
    <h2 id="tab_title"></h2>
    <div id="assessment_tasks_filter_container">
        <div class="row-fluid space-below">
            <div class="input-append">
                <input type="text" id="task-search" placeholder="<?php echo $translate->_("Search Tasks..."); ?>" <?php echo ($search_term) ? "value=\"$search_term\"" : ""; ?> class="input-large search-icon" data-append="false" />
                <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
            </div>
            <div class="input-append space-left">
                <input id="task_start_date" placeholder="<?php echo $translate->_("Delivery Start"); ?>" type="text" class="input-small datepicker" <?php echo ($start_date) ? "value=\"" . date("Y-m-d", $start_date) . "\"" : ""; ?> name="task_start_date"/>
                <span class="add-on pointer"><i class="icon-calendar"></i></span>
            </div>
            <div class="input-append space-left">
                <input id="task_end_date" placeholder="<?php echo $translate->_("Delivery End"); ?>" type="text" class="input-small datepicker" <?php echo ($end_date) ? "value=\"" . date("Y-m-d", $end_date) . "\"" : ""; ?> name="task_end_date"/>
                <span class="add-on pointer"><i class="icon-calendar"></i></span>
            </div>
            <input type="button" class="btn btn-success space-left" id="apply_filters" value="<?php echo $translate->_("Apply Filters"); ?>"/>
            <input type="button" class="btn btn-default space-left" id="remove_filters" value="<?php echo $translate->_("Remove Filters"); ?>"/>
        </div>
        <input type="hidden" name="current-section" id="current_section" value="assessments"/>
        <input type="hidden" name="proxy-id" id="proxy_id" value="<?php echo html_encode($ENTRADA_USER->getActiveID()); ?>"/>
        <input type="hidden" name="organisation-id" id="organisation_id" value="<?php echo html_encode($ENTRADA_USER->getActiveOrganisation()); ?>"/>
        <input type="hidden" name="offset" id="offset" value="<?php echo $offset; ?>"/>
        <div id="active-filters"></div>
    </div>
    <div class="tab-pane" id="forms_to_complete">
        <input type="hidden" name="current-page" class="current_page" value="incomplete"/>
        <h2><?php echo $translate->_("Assessment Tasks"); ?></h2>
        <div class="btn-group pull-right space-below">
            <button type="button" class="select-all-to-download incomplete btn pull-left">
                <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
            </button>
            <a class="btn btn-default pull-left generate-pdf-btn incomplete" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
        </div>
        <div class="clearfix"></div>
        <ul id="assessment-tasks" class="incomplete">
        <?php
        $task_displayed = false;
        if ($incomplete_assessment_tasks) {
            foreach ($incomplete_assessment_tasks as $task) {
                // presetting data-set to pass it to the front-end
                $remove_data = array(
                    'assessor_type' => 'internal',
                    'assessor_value' => $task->getAssessorValue(),
                    'target_id' => $task->getTargetID(),
                    'distribution_id' => $task->getDistributionID(),
                    'assessment_id' => $task->getDassessmentID(),
                    'delivery_date' => $task->getDeliveryDate() ? $task->getDeliveryDate() : null
                );
                if (!$task->getDistributionDeletedDate()) {
                    ?>
                    <?php if ($task->getType() == "assessment") {
                        if ($task->getMaxOverallAttempts() > $task->getCompletedAttempts()) {
                            $task_displayed = true;
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
                                                    <?php echo $translate->_("Delivered on ") ?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())) ?></strong>
                                                </div>
                                                <?php
                                            }
                                            if ($task->getDelegatedBy()) {
                                                ?>
                                                <div class="assessment-task-date">
                                                    <?php echo $translate->_("Delegated by ") ?><strong><?php echo html_encode($task->getDelegatedBy()) ?></strong>
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
                                                <?php $asm_url = ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . html_encode($task->getDistributionID()) . "&dassessment_id=" . html_encode($task->getDassessmentID()); ?>
                                                <span class="pending">
                                                    <a class="progress-circle tooltip-tag" href="<?php echo "$asm_url&target_status_view=pending"; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesPending()); ?>">
                                                        <div><?php echo $task->getTargetsPending(); ?></div>
                                                    </a>
                                                </span>
                                                <span class="inprogress">
                                                    <a class="progress-circle tooltip-tag" href="<?php echo "$asm_url&target_status_view=inprogress"; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesInprogress()); ?>">
                                                        <div><?php echo $task->getTargetsInprogress(); ?></div>
                                                    </a>
                                                </span>
                                                <?php
                                            } else {
                                                if ($task->getTargetsPending()) {
                                                    ?>
                                                    <span class="pending">
                                                        <a class="progress-circle tooltip-tag"
                                                           href="<?php echo html_encode($task->getUrl()) ?>"
                                                           data-toggle="tooltip"
                                                           data-placement="bottom"
                                                           title="<?php echo $translate->_($task->getTargetNamesPending()); ?>">
                                                            <div><?php echo html_encode($task->getTargetsPending()); ?></div>
                                                        </a>
                                                    </span>
                                                    <?php
                                                }
                                                if ($task->getTargetsInprogress()) {
                                                    ?>
                                                    <span class="inprogress">
                                                        <a class="progress-circle tooltip-tag"
                                                           href="<?php echo html_encode($task->getUrl()) ?>"
                                                           data-toggle="tooltip"
                                                           data-placement="bottom"
                                                           title="<?php echo $translate->_($task->getTargetNamesInprogress()); ?>">
                                                            <div><?php echo html_encode($task->getTargetsInprogress()); ?></div>
                                                        </a>
                                                    </span>
                                                    <?php
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
                                                /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks."); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="assessment-task-link btn-group">
                                        <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                        <span class="remove" data-assessor-type="<?php echo html_encode("internal"); ?>" data-assessor-value="<?php echo html_encode($task->getAssessorValue()) ?>" data-target-id="<?php echo html_encode($task->getTargetID()) ?>" data-distribution-id="<?php echo html_encode($task->getDistributionID()) ?>" data-assessment-id="<?php echo html_encode($task->getDassessmentID()) ?>" data-delivery-date="<?php echo html_encode(($task->getDeliveryDate() ? $task->getDeliveryDate() : null)) ?>" data-toggle="modal" data-target="#remove_form_modal">
                                            <a href="#"><?php echo $translate->_("Remove Task"); ?></a>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                    } elseif ($task->getType() == "delegation") {
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
                                            ?>
                                        </div>
                                        <div class="assessment-progress">
                                            <span class="progress-title"><?php echo $translate->_("Progress") . " <strong>" . $translate->_("N/A"); ?></strong></span>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="details">
                                            <?php echo html_encode($task->getDetails()); ?>
                                        </div>
                                    </div>
                                    <div class="assessment-task-link btn-group">
                                        <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
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
                    } elseif ($task->getType() == "approver") {
                        $approver_approvals = new Models_Assessments_Distribution_Approvals();
                        $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($task->getProgressID(), $task->getDistributionID());
                        if (!$approver_record) {
                            $task_displayed = true;
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
                                            <div class="label assessment-task-release-schedule-info-badge"><?php echo $translate->_("Reviewer Task"); ?></div>
                                            <?php
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
                                            ?>
                                        </div>
                                        <div class="assessment-progress">
                                            <span class="progress-title"><?php echo $translate->_("Progress") . " <strong>" . $translate->_("N/A"); ?></strong></span>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="details">
                                            <?php echo html_encode($task->getDetails()); ?>
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
                                                /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks."); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="assessment-task-link">
                                        <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                    }
                }
            }
            ?>
            <div class="clearfix"></div>
            <?php
        } ?>
        </ul>
        <?php
        if (!$task_displayed) {
            ?>
            <div class="form-search-message"><?php echo $translate->_("You currently have no Assessments to complete."); ?></div>
            <?php
        }
        ?>
    </div>

    <?php
    if ($show_tasks_completed_on_me) {
        ?>
        <div class="tab-pane" id="form_results">
            <input type="hidden" name="current-page" class="current_page" value="completed_on_me"/>
            <h2><?php echo $translate->_("Tasks Completed on Me"); ?></h2>
            <div class="btn-group pull-right space-below">
                <button type="button" class="select-all-to-download completed_on_me btn pull-left">
                    <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                    <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
                </button>
                <a class="btn btn-default pull-left generate-pdf-btn completed_on_me" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
            </div>
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
                                        <div class="label assessment-task-schedule-info-badge"><?php echo $task->getScheduleDetails(); ?></div>
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
                                    ?>
                                    <!--
                                    <div class="assessment-task-date">
                                        <?php echo $translate->_("Completed on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getEndDate())); ?></strong>
                                    </div>
                                    -->

                                </div>
                                <div class="details">
                                    <?php echo $translate->_("This assessment was completed on") . " " . html_encode(date("M j, Y", $task->getCompletedDate())); ?>
                                </div>
                                <div class="assessor">
                                    <div><?php echo $translate->_("Assessor: "); ?><strong><?php echo html_encode($task->getAssessor()); ?></strong>
                                    </div>
                                    <span class="label assessment-task-meta"><?php echo html_encode(ucfirst($task->getGroup())) . " • " . html_encode(str_replace("_", " ", ucfirst($task->getRole()))) ?></span>
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
                                        /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks."); ?>
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
            } ?>
            </ul>
            <?php
            if (!$complete_tasks) {
                ?>
                <div class="form-search-message"><?php echo $translate->_("No assessments have been completed on you."); ?></div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    ?>
    <div class="tab-pane" id="completed_forms">
        <h2><?php echo $translate->_("Forms I've Completed"); ?></h2>
        <input type="hidden" name="current-page" class="current_page" value="completed"/>
        <div class="btn-group pull-right space-below">
            <button type="button" class="select-all-to-download completed btn pull-left">
                <span class="label-select"><?php echo $translate->_("Select All"); ?></span>
                <span class="label-unselect hide"><?php echo $translate->_("Unselect All"); ?></span>
            </button>
            <a class="btn btn-default pull-left generate-pdf-btn completed" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
        </div>
        <div class="clearfix"></div>
        <ul id="assessment-tasks" class="completed">
        <?php
        $task_displayed = false;
        if ($limited_complete_assessment_tasks) {
            foreach ($limited_complete_assessment_tasks as $task) {
                if ($task->getType() == "assessment" && $task->getCompletedAttempts() >= 1) {
                $task_displayed = true;
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
                                        <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate()));?></strong></div>
                                    <?php
                                }
                                if ($task->getCompletedDate()) {
                                    ?>
                                    <div class="assessment-task-date">
                                        <?php echo $translate->_("Completed on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getCompletedDate())); ?></strong>
                                    </div>
                                    <?php
                                }
                                if ($task->getDelegatedBy()) {
                                    ?>
                                    <div class="assessment-task-date">
                                        <?php echo $translate->_("Delegated by ")?><strong><?php echo html_encode($task->getDelegatedBy()) ?></strong>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="assessment-progress">
                                <span class="progress-title"><?php echo $translate->_("Completed"); ?></span>
                                <?php
                                if ($task->getTotalTargets() > 1) {
                                    ?>
                                    <span class="complete">
                                        <a class="progress-circle tooltip-tag" href="<?php echo ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . html_encode($task->getDistributionID()) . "&dassessment_id=" . html_encode($task->getDassessmentID()) . "&target_status_view=complete"; ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesComplete()); ?>"><div><?php echo $task->getCompletedAttempts(); ?></div></a>
                                    </span>
                                    <?php
                                } else {
                                    if ($task->getTargetsComplete()) {
                                        ?>
                                        <span class="complete">
                                            <a class="progress-circle tooltip-tag" href="<?php echo html_encode($task->getUrl());?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo $translate->_($task->getTargetNamesComplete());?> "><div><?php echo html_encode($task->getTargetsComplete()); ?></div></a>
                                        </span>
                                        <?php
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
                                    /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks."); ?>
                                </label>
                            </div>
                        </div>
                        <div class="assessment-task-link">
                            <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                        </div>
                    </div>
                </li>
                <?php
                } elseif ($task->getType() == "delegation") {
                    if ($task->getDelegationCompleted()) {
                        $task_displayed = true;
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
                                        <span class="progress-title"><?php echo $translate->_("Completed") . " <strong>" . $translate->_("N/A"); ?></strong></span>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="details">
                                        <?php echo html_encode($task->getDetails()); ?>
                                    </div>
                                </div>
                                <div class="assessment-task-link">
                                    <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                </div>
                            </div>
                        </li>
                        <?php
                    }
                } elseif ($task->getType() == "approver") {
                    $approver_approvals = new Models_Assessments_Distribution_Approvals();
                    $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($task->getProgressID(), $task->getDistributionID());
                    if ($approver_record) {
                        $task_displayed = true;
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
                                            <div class="label assessment-task-release-schedule-info-badge"><?php echo $task->getScheduleDetails(); ?></div>
                                            <?php
                                        }
                                        ?>
                                        <div class="label assessment-task-release-schedule-info-badge"><?php echo $translate->_("Reviewer Task"); ?></div>
                                        <?php
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
                                        ?>
                                        <?php
                                        if ($task->getDeliveryDate()) {
                                            ?>
                                            <div class="assessment-task-date">
                                                <?php echo $translate->_("Delivered on "); ?><strong><?php echo html_encode(date("M j, Y", $task->getDeliveryDate())); ?></strong>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="assessment-progress">
                                        <span class="progress-title"><?php echo $translate->_("Completed") . " <strong>" . $translate->_("N/A"); ?></strong></span>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="details">
                                        <?php echo html_encode($task->getDetails()); ?>
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
                                            /><?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected assessment tasks."); ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="assessment-task-link">
                                    <a href="<?php echo $task->getUrl(); ?>"><?php echo $translate->_("View Task "); ?>&rtrif;</a>
                                </div>
                            </div>
                        </li>
                        <?php
                    }
                }
            }
            ?>
            <div class="clearfix"></div>
            <?php
        } ?>
        </ul>
        <?php
        if (!$task_displayed) {
            ?>
            <div class="form-search-message"><?php echo $translate->_("You currently have no completed Assessments to review."); ?></div>
            <?php
        }
        if ($limit): ?>
            <input type="button" id="load-tasks" class="btn btn-block" value="<?php echo $translate->_("Load More Tasks"); ?>" data-append="true" />
        <?php endif; ?>
    </div>
    <?php if ($ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && (Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($ENTRADA_USER->getProxyId(), $ENTRADA_USER->getActiveOrganisation())) || $course_owner || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)) {
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

        $assessments_base->getAssessmentPreferences("assessments");
        $cperiod_id_preference = (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["learners"]["cperiod_id"]) ? $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["learners"]["cperiod_id"] : false);

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
            $list = new Views_Assessments_UserCard_List(array("id" => "learner-cards", "class" => "learner-card", "users" => $learners, "assessment_label" => "", "view_assessment_label" => $translate->_("View assessments &rtrif;"), "no_results_label" => $translate->_("No users found matching your search.")));
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
                                    <?php
                                    foreach ($curriculum_periods as $curriculum_period) {
                                        ?>
                                        <option value="<?php echo $curriculum_period->getCperiodID(); ?>" <?php echo ($cperiod_id_preference == $curriculum_period->getCperiodID() ? "selected=\"selected\"" : ""); ?>>
                                            <?php echo date("Y-m-d", $curriculum_period->getStartDate()) . " - " . date("Y-m-d", $curriculum_period->getFinishDate()) . ($curriculum_period->getCurriculumPeriodTitle() ? " " . $curriculum_period->getCurriculumPeriodTitle() : ""); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
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

                        // Instantiate the Assessment_Learner view and render the list of learners
                        $list = new Views_Assessments_UserCard_List(array("id" => "learner-cards", "class" => "learner-card", "users" => $learners, "assessment_label" => "", "view_assessment_label" => $translate->_("View Assessments &rtrif;"), "no_results_label" => $translate->_("No users found matching your search."), "logbook_url" => ENTRADA_URL . "/logbook?proxy_id="));
                        $list->render();
                        ?>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    if ($course_owner) {
        ?>
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
                            $list = new Views_Assessments_UserCard_List(array("id" => "faculty-cards", "class" => "faculty-card", "users" => $faculty, "assessment_label" => "", "view_assessment_label" => $translate->_("View assessment tasks &rtrif;"), "no_results_label" => $translate->_("No users found matching your search."), "group" => "faculty"));
                            $list->render(array("url" => ENTRADA_URL, "hidden_external_assessor_id_list" => $hidden_external_assessor_id_list));
                            ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    </div>
	<!-- Deletion Form -->
    <?php

    $edit_external_email_modal = new Views_Assessments_Modals_EditExternalEmail();
    $edit_external_email_modal->render();

	$options = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID(); ?>
    <div id="remove_form_modal" class="modal hide fade">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3><?php echo $translate->_("Reason to Remove an Assessment"); ?></h3>
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
	<!-- Deletion Form -- end -->
    <?php
    if (!empty($filters)) {
        echo "<form id=\"search-targets-form\" method=\"post\" action=\"". ENTRADA_URL . "/assessments\">";
        foreach ($filters as $key => $filter_type) {
            foreach ($filter_type as $target_id => $target_label) {
                echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
            }
        }
        echo "</form>";
    }

    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment"
    ));

    $template_view = new Views_Assessments_Templates_AssessmentCard();
    $template_view->render();
}