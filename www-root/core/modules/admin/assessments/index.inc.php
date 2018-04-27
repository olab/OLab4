<?php
/*
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
 * The default file that is loaded when /admin/assessments is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE, "title" => "Dashboard");
    //$HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/dashboard/index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css?release=". html_encode(APPLICATION_VERSION) ."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

    $task_count = array();
    $task_types = array("assessment", "evaluation");
    foreach ($task_types as $task_type) {
        $task_count["incomplete-" . $task_type] = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasksForAssociatedLearnersAssociatedFaculty($task_type, $ENTRADA_USER->getActiveOrganisation(), 0, 0, true);
        $task_count["upcoming-" . $task_type]   = Models_Assessments_FutureTaskSnapshot::fetchAllFutureTasksForAssociatedLearnersAssociatedFaculty($task_type, $ENTRADA_USER->getActiveOrganisation(), 0, 0, true);
        $task_count["deleted-" . $task_type]    = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllDeletedTasksForAssociatedLearnersAssociatedFaculty($task_type, $ENTRADA_USER->getActiveOrganisation(),0, 0, true);
    }

    $schedule_data = Models_Schedule::fetchAllScheduleChangesByProxyIDOrganisationID($ENTRADA_USER->getProxyID(), $ENTRADA_USER->getActiveOrganisation());
    $tracked_vacation = Models_Leave_Tracking::fetchAllByAssociatedLearnerFacultyProxyList(strtotime("today"));
    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array(
        "active" => "dashboard",
        "group" => $ENTRADA_USER->getActiveGroup(),
        "role" => $ENTRADA_USER->getActiveRole()
    )); ?>

    <h1><?php echo $translate->_("Assessment & Evaluation"); ?></h1>
    <div class="well">
        <?php echo $translate->_("Welcome to the Assessment &amp; Evaluation system. The <strong>Distributions</strong> section is used to set up assessment task deliveries, which can be modified at any time. To create new form items and to group items use the <strong>Items</strong> section. Items and grouped items can be assembled into assessment forms in the <strong>Forms</strong> section."); ?>
    </div>
    <?php
    $dashboard_task_lists = new Views_Assessments_Dashboard_TaskLists();
    $dashboard_task_lists->render(array("task_count" => $task_count, "log_assessment_url" => ENTRADA_URL . "/assessments?section=assessment-log" ));
    ?>

    <div id="additional-dashboard-details">
        <?php
        $leave = new Views_Assessments_Dashboard_CurrentAndUpcomingLeave();
        $leave->render(array("tracked_vacation" => $tracked_vacation));

        $schedule_changes = new Views_Assessments_Dashboard_ScheduleChanges();
        $schedule_changes->render(array("schedule_data" => $schedule_data));
        ?>
    </div>
    <?php
}