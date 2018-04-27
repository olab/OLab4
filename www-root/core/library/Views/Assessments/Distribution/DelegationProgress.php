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
 * View class for rendering delegation progress page.
 *
 * This view is reliant on the Entrada_Utilities_Assessments_DistributionDelegation
 * class for certain data-related functionality.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Distribution_DelegationProgress extends Views_Assessments_Base {

    private $distribution_delegation;

    /**
     * Views_Assessments_Distribution_DelegationProgress constructor.
     * Constructed using parent settings.
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->distribution_delegation = false;
    }

    /**
     * Perform simple validation on the options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {

        // Check for the utility object
        if (!isset($options["distribution_delegation_utility"])) {
            return false;
        }
        if (!is_a($options["distribution_delegation_utility"], "Entrada_Utilities_Assessments_DistributionDelegation")) {
            return false;
        }

        // Check for our primitives
        if (!isset($options["active_tab"]) ||
            !isset($options["distribution_id"]) ||
            !isset($options["delegation_count_upcoming"]) ||
            !isset($options["delegation_count_pending"]) ||
            !isset($options["delegation_count_completed"])) {
            return false;
        }

        // Check for our arrays
        if (!isset($options["distribution_data"]) ||
            !isset($options["all_possible_tasks_list"]) ||
            !isset($options["all_possible_assessors"]) ||
            !isset($options["all_completed_tasks"]) ||
            !isset($options["upcoming_delegations"]) ||
            !isset($options["assignments_summary"]) ||
            !isset($options["delegation_summary"]) ||
            !isset($options["delegator_data"])) {
            return false;
        }

        // Check that the mission critical arrays have data in them
        if (empty($options["distribution_data"]) ||
            empty($options["delegator_data"])) {
            return false;
        }

        // Passed validation
        return true;
    }

    /**
     * Main render logic for this view. Renders the progress page.
     *
     * @param mixed $options
     * @return bool
     */
    protected function renderView($options = array()) {
        global $translate;

        // Extract our view-relevant variables from options array (already validated).

        // Entrada_Utilities_Assessments_DistributionDelegation object
        $this->distribution_delegation = $options["distribution_delegation_utility"];

        // Primitives
        $distribution_id            = $options["distribution_id"];
        $active_tab                 = $options["active_tab"];
        $upcoming_delegation_count  = $options["delegation_count_upcoming"];
        $pending_delegation_count   = $options["delegation_count_pending"];
        $completed_delegation_count = $options["delegation_count_completed"];

        // Arrays
        $distribution_data          = $options["distribution_data"];
        $all_task_list              = $options["all_possible_tasks_list"];
        $completed_tasks            = $options["all_completed_tasks"];
        $all_assessors              = $options["all_possible_assessors"];
        $upcoming_delegations       = $options["upcoming_delegations"];
        $assignments_summary        = $options["assignments_summary"];
        $delegation_summary         = $options["delegation_summary"];
        $delegator_data             = $options["delegator_data"];

        // The edit distribution link
        $edit_distribution_url      = ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=$distribution_id";

        // Start HTML generation for delegation progress interface
        ?>
        <?php $this->renderDefaultHiddenElements($distribution_data["distribution_delegator_name"]); ?>

        <div<?php echo $this->getIDString() ?><?php echo $this->getClassString() ?>>

            <!-- Block and Date Range Filters -->
            <?php //$this->renderFilterControls(); ?>

            <!-- Heading cards/tab selectors -->
            <?php $this->renderHeadingCards($active_tab, $pending_delegation_count, $completed_delegation_count, $upcoming_delegation_count); ?>

            <div class="clearfix space-below">

                <!-- Delete tasks button -->
                <a href="#delete-delegation-tasks-modal" class="btn btn-danger pull-left" title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo $distribution_id ?>">
                    <i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>

                <!-- Manage distribution button -->
                <?php $this->renderManageDistributionButton($distribution_id, $edit_distribution_url); ?>

            </div>

            <div class="row-fluid space-below">

                <!-- Distribution text summary -->
                <div class="distribution-summary-container span6">
                    <?php $this->renderDistributionSummary($distribution_data); ?>
                </div>

                <!-- Delegation text summary -->
                <div class="span6 text-right">
                    <?php $this->renderDelegationSummary($delegation_summary); ?>
                </div>

            </div>

            <div class="clearfix"></div>

            <!-- Search field -->
            <div class="pull-left clearfix space-below medium" id="target-search-block">
                <input type="text" placeholder="<?php echo $translate->_("Search...") ?>" id="target-search-input" class="search-icon">
            </div>

            <div class="clearfix"></div>

            <!-- Pending delegations tab -->
            <div id="targets-pending-container" class="targets-container <?php echo ($active_tab == "pending") ? "" : "hide"; ?>">
                <?php $this->renderDelegationsTablePending($distribution_id, $distribution_data, $all_task_list, $assignments_summary, $all_assessors, $delegator_data); ?>
            </div>

            <!-- Completed delegations tab -->
            <div id="targets-complete-container" class="targets-container <?php echo ($active_tab == "complete") ? "" : "hide"; ?>">
                <?php $this->renderDelegationsTableComplete($distribution_data, $assignments_summary, $completed_tasks, $delegator_data); ?>
            </div>

            <!-- Upcoming delegations tab -->
            <div id="targets-inprogress-container" class="targets-container <?php echo ($active_tab == "inprogress") ? "" : "hide"; ?>">
                <?php $this->renderDelegationsTableUpcoming($distribution_data, $upcoming_delegations); ?>
            </div>

        </div>
        <?php // end of delegation progress page render
    }

    /**
     * Render the error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger"><?php echo $translate->_("No delegator found for the current distribution."); ?></div>
        <?php
    }

    /**
     * Default hidden form elements, copied and reused by JavaScript.
     *
     * @param $delegator_name
     */
    private function renderDefaultHiddenElements($delegator_name) {
        ?>
        <div class="hide" style="display: none;visibility: hidden">
            <input id="hidden-delegator-name" type="hidden" class="hide" value="<?php echo html_encode($delegator_name)?>">
            <input id="delegation-progress-mode" type="hidden" class="hide" value="0">
            <div id="assessor-toggle-arrow-default-up">
                <?php $this->renderUpArrow(); ?>
            </div>
            <div id="assessor-toggle-arrow-default-down">
                <?php $this->renderDownArrow(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the expand bar for available assessors drawer
     */
    private function renderDownArrow() {
        global $translate; ?>
            <small><?php echo $translate->_("Click to Show All Available Assessors");?> &#9660;</small>
        <?php
    }

    /**
     * Render the collapse bar for available assessors drawer
     */
    private function renderUpArrow() {
        global $translate; ?>
            <small><?php echo $translate->_("Click to Hide All Available Assessors");?> &#9650;</small>
        <?php
    }

    /**
     * Render the manage distribution button.
     *
     * @param $distribution_id
     * @param $distribution_url
     */
    private function renderManageDistributionButton($distribution_id, $distribution_url) {
        global $translate; ?>
        <div class="pull-right assessment-distributions-container btn-group">
            <button title="<?php echo $translate->_("Manage Distribution Details"); ?>" data-toggle="dropdown" class="btn btn-primary dropdown-toggle no-printing">
                <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution"); ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a data-adistribution-id="<?php echo $distribution_id; ?>" title="<?php echo $translate->_("Edit Distribution Details");?>" class="edit-distribution" href="<?php echo $distribution_url; ?>"><?php echo $translate->_("Edit Distribution");?></a>
                </li>
                <li>
                    <a data-adistribution-id="<?php echo $distribution_id; ?>" data-toggle="modal" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" href="#reminder-modal"><?php echo $translate->_("Send Reminders"); ?></a>
                </li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render a summary of the delegation (for current occurrences of the delegation, not future).
     *
     * @param $delegation_summary
     */
    private function renderDelegationSummary($delegation_summary) {
        global $translate;
        ?>
        <div>
            <h2><?php echo $translate->_("Delegation Summary"); ?></h2>
            <p><?php echo sprintf($translate->_("Delegation created by <strong>%s</strong> %s on <strong>%s</strong>"),
                    $delegation_summary["creator_name"],
                    $delegation_summary["creator_role"],
                    $delegation_summary["created_date_string"]);?>
            </p>
            <?php // Draw the strings (if applicable) ?>
            <?php foreach (array($delegation_summary["blocks_string"], $delegation_summary["date_range_string"], $delegation_summary["cutoff_date_string"]) as $string): ?>
                <?php if ($string): ?>
                    <p><?php echo $string ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render the summary text blurb of the distribution data.
     *
     * @param $distribution_data
     */
    private function renderDistributionSummary($distribution_data) {
        global $translate;
        ?>
        <h2><?php echo $translate->_("Distribution Summary"); ?></h2>
        <div>
            <div class="row-fluid">
                <div class="span12">
                    <strong><?php echo "({$distribution_data["course"]->getCourseCode()}) {$distribution_data["course"]->getCourseName()}"; ?></strong>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span4"><?php echo $translate->_("Curriculum Period");?>:</div>
                <div class="span7 offset1"><?php
                    echo html_encode($distribution_data["curriculum_period"]->getCurriculumPeriodTitle());
                    echo sprintf(" %s {$translate->_("to")} %s",
                        strftime("%Y-%m-%d", $distribution_data["curriculum_period"]->getStartDate()),
                        strftime("%Y-%m-%d", $distribution_data["curriculum_period"]->getFinishDate())
                    );
                    ?>
                </div>
            </div>
            <?php if ($distribution_data["rotation_schedule"]["selected_schedule"]):?>
            <div class="row-fluid">
                <div class="span4"><?php echo $translate->_("Rotation");?>:</div>
                <div class="span7 offset1"><?php echo html_encode($distribution_data["rotation_schedule"]["selected_schedule"]->getTitle());?></div>
            </div>
            <?php endif; ?>
            <div class="row-fluid">
                <div class="span4"><?php echo $translate->_("Form");?>:</div>
                <div class="span7 offset1"><?php echo html_encode($distribution_data["distribution_form"]->getTitle()); ?></div>
            </div>
            <div class="row-fluid">
                <div class="span4"><?php echo $translate->_("Targets");?>:</div>
                <div class="span7 offset1">
                    <?php if (count($distribution_data["distribution_targets_summary"]) == 1): ?>
                        <p><?php echo html_encode($distribution_data["distribution_targets_summary"][0])?></p>
                    <?php else: ?>
                    <ul>
                        <?php foreach ($distribution_data["distribution_targets_summary"] as $ts): ?>
                            <li>
                                <?php echo html_encode($ts); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span4"><?php echo $translate->_("Assessors");?>:</div>
                <div class="span7 offset1">
                    <?php if (count($distribution_data["distribution_assessors_summary"]) == 1): ?>
                        <p><?php echo html_encode($distribution_data["distribution_assessors_summary"][0])?></p>
                    <?php else: ?>
                    <ul>
                        <?php foreach ($distribution_data["distribution_assessors_summary"] as $as): ?>
                            <li>
                                <?php echo html_encode($as); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        }

    /**
     * Renders the user badge. Optionally adds the user's avatar. The badge text will have the "highlighted" class added to it if the user id in the user_data exists
     * in the add_highlights array (a simple list of IDs).
     *
     * $user_data can either contain target or assessor specific data, and is standardized before displaying.
     *
     * @param array $user_data
     * @param bool $include_avatar
     * @param array $add_highlights
     */
    private function renderUserBadge($user_data, $include_avatar = true, $add_highlights = array()) {
        $user_data = $this->distribution_delegation->standardizeUserBadgeData($user_data);
        ?>
        <div class="userbadge">
            <?php if ($include_avatar) :?>
                <div class="userbadge-component userAvatar space-right">
                    <?php if ($user_data["type"] == "internal"): ?>
                        <img src="<?php echo webservice_url("photo", array($user_data["id"], "official")); ?>" width="32" height="42" alt="<?php echo $user_data["fullname"] ?>" class="img-polaroid" />
                    <?php else: ?>
                        <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif"; ?>" width="32" height="42" alt="<?php echo $user_data["fullname"] ?>" class="img-polaroid" />
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <div class="userbadge-component userbadge-details <?php if (in_array($user_data["id"], $add_highlights)) echo "highlighted"; ?>">
                <div>
                    <strong><?php echo $user_data["fullname"]; ?></strong>
                </div>

                <div>
                    <?php if ($user_data["email"]): ?>
                        <a href="mailto:<?php echo $user_data["email"];?>"><?php echo $user_data["email"]; ?></a>
                    <?php endif; ?>
                </div>

                <div>
                    <?php echo ((int)$user_data["number"]) ? $user_data["number"] : "&nbsp"; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders the heading cards/tab selector cards for each section; progress, complete, and upcoming.
     *
     * @param string $active_tab
     * @param int $pending_delegations
     * @param int $upcoming_delegations
     * @param int $completed_delegations
     */
    private function renderHeadingCards($active_tab, $pending_delegations, $completed_delegations, $upcoming_delegations) {
        global $translate; ?>
        <div class="clearfix space-below" id="assessment-block">
            <div class="span4 assessment-card" id="targets-pending-card">
                <h4 class="pending"><?php echo $translate->_("Pending");?></h4>
                <div class="assessment-card-count pending"><?php echo $pending_delegations; ?></div>
                <p class="assessment-card-description item pending">
                    <?php if ($pending_delegations == 1) : ?>
                        <?php echo $translate->_("There is 1 delegation that is in-progress or not started."); ?>
                    <?php else: ?>
                        <?php echo sprintf($translate->_("There are %s delegations that are in-progress or not started."), $pending_delegations); ?>
                    <?php endif; ?>
                </p>
                <a href="#" data-target-status="pending" id="targets-pending-btn" class="target-status-btn <?php echo ($active_tab == "pending") ? "active" : "";?>"><?php echo $translate->_("Pending Delegations"); ?></a>
            </div>
            <div class="span4 assessment-card" id="targets-complete-card">
                <h4 class="complete"><?php echo $translate->_("Completed"); ?></h4>
                <div class="assessment-card-count complete"><?php echo $completed_delegations; ?></div>
                <p class="assessment-card-description item complete">
                    <?php if ($completed_delegations == 1) : ?>
                        <?php echo $translate->_("There is 1 delegation marked complete."); ?>
                    <?php else: ?>
                        <?php echo sprintf($translate->_("There are %s delegations that are marked complete."), $completed_delegations); ?>
                    <?php endif; ?>
                </p>
                <a href="#" data-target-status="complete" id="targets-complete-btn" class="target-status-btn <?php echo ($active_tab == "complete") ? "active" : "";?>"><?php echo $translate->_("Completed Delegations"); ?></a>
            </div>
            <div class="span4 assessment-card" id="targets-inprogress-card">
                <h4 class="inprogress"><?php echo $translate->_("Upcoming"); ?></h4>
                <div class="assessment-card-count inprogress"><?php echo $upcoming_delegations; ?></div>
                <p class="assessment-card-description item inprogress">
                    <?php if ($upcoming_delegations == 1) : ?>
                        <?php echo $translate->_("There is 1 upcoming delegation."); ?>
                    <?php else: ?>
                        <?php echo sprintf($translate->_("There are %s upcoming delegations."), $upcoming_delegations); ?>
                    <?php endif; ?>
                </p>
                <a href="#" data-target-status="inprogress" id="targets-inprogress-btn" class="target-status-btn <?php echo ($active_tab == "inprogress") ? "active" : "";?>"><?php echo $translate->_("Upcoming Delegations")?></a>
            </div>
        </div>
        <?php
    }

    /**
     * Renders grid-style list of assessors.
     *
     * @param $container_ordinal
     * @param $progress_type
     * @param $assessor_list
     * @param $assignments
     * @param bool $show_labels
     * @param bool $show_avatar
     * @param bool $start_hidden
     */
    private function renderAllAvailableAssessorsContainer($container_ordinal, $progress_type, $assessor_list, $assignments, $show_labels = true, $show_avatar = true, $start_hidden = true) {
        global $translate;

        // Assemble the list of assessors that have been assigned to the related assessment task so we can highlight them.
        $assigned = array();
        foreach ($assignments as $assignment) {
            foreach ($assignment["assessors"] as $assigned_assessor) {
                $assigned[$assigned_assessor["assessor_value"]] = $assigned_assessor["assessor_value"];
            }
        }
        ?>
        <div class="<?php echo $start_hidden ? "hide" : ""?> all-available-assessors-container all-assessor-block-container all-assessor-block-container-<?php echo $progress_type?>-<?php echo $container_ordinal; ?>" data-progress-type="<?php echo $progress_type?>" data-toggle-ordinal="<?php echo $container_ordinal; ?>">
            <?php if ($show_labels): ?>
            <div class="padding-left padding-top">
                <p><strong><?php echo $translate->_("All Available Assessors");?></strong></p>
            </div>
            <?php endif; ?>
            <ul class="clearfix padding-left padding-right padding-bottom medium">
                <?php foreach ($assessor_list as $assessor): ?>
                    <li class="pull-left">
                        <?php $this->renderUserBadge($assessor, $show_avatar, $assigned); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * For the given target and delegator, render a checkbox
     *
     * @param $distribution_id
     * @param $delegation_id
     */
    private function renderDelegatorReminderControls($distribution_id, $delegation_id) {
        global $translate;
        $id_key = "$distribution_id-$delegation_id";
        ?>
        <label class="delegation-reminder-checkbox-container checkbox" for="delegator-reminder-checkbox-<?php echo $id_key ?>">
            <input type="checkbox"
                   class="delegator-notify-checkbox"
                   id="delegator-reminder-checkbox-<?php echo $id_key ?>"
                   data-delegation-task-id="<?php echo $delegation_id ?>"
                   data-distribution-id="<?php echo $distribution_id ?>">
            <?php echo sprintf($translate->_("Send a reminder to the delegator for this delegation task.")); ?>
        </label>
        <?php
    }

    /**
     * Render the table body for delegated assignments.
     *
     * @param array $target
     * @param array $delegation_task
     * @param Models_User|Models_Assessments_Distribution_ExternalAssessor $delegator
     */
    private function renderDelegationTableBodyAssessorAssignments($target, $delegation_task, $delegator) {
        global $translate;
        ?>
        <table class="assessment-delegation-assessor-sub-table<?php echo (empty($target["assessors"])) ? "" : " assessor-content-blocks";  ?>">
            <tbody>
            <?php if (empty($target["assessors"])): ?>

                <tr>
                    <td colspan="5" class="no-border-left">
                        <p><?php echo $translate->_("No assessors have been delegated for this target.");?></p>
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($target["assessors"] as $assignment):
                    $assignment = $this->distribution_delegation->addExpandedDelegationData($target, $assignment);
                    $assessor = Models_Assessments_Assessor::fetchRowByID($assignment["dassessment_id"]);
                    $progress = Models_Assessments_Progress::fetchRowByDassessmentID($assignment["dassessment_id"]);
                    ?>
                    <tr>
                        <td class="no-border-left">
                            <div>
                                <strong>
                                    <?php if ($assignment["assessor_type"] == "external") {
                                        $url = ENTRADA_URL . "/assessment?adistribution_id=" . $assignment["adistribution_id"] . "&target_record_id=" . $assignment["target_id"] . "&dassessment_id=" . $assignment["dassessment_id"] . "&assessor_value=" . $assignment["assessor_value"] . (isset($progress) && $progress ? "&aprogress_id=" . $progress->getID() : "") . "&external_hash=" . $assessor->getExternalHash() . "&from=progress"; ?>
                                        <a href="<?php echo $url; ?>"><?php echo $assignment["assessor_fullname"]; ?></a> <?php
                                    } else {
                                       echo $assignment["assessor_fullname"];
                                    } ?>
                                </strong>
                                <a target="_top" href="mailto:<?php echo $assignment["assessor_email"];?>"><?php echo $assignment["assessor_email"];?></a>
                                <span><?php echo $assignment["assessor_number"] ? $assignment["assessor_number"] : "&nbsp;"; ?></span>
                            </div>
                        </td>
                        <td>
                            <div><?php echo $assignment["delegated_date_string"]; ?></div>
                        </td>
                        <td>
                            <div><?php echo $assignment["assessment_status_string"]; ?></div>
                        </td>
                        <td>
                            <div>
                                <input type="checkbox" value="<?php echo $assignment["assessor_value"]; ?>"
                                       data-assessment-id="<?php echo $assignment["dassessment_id"]; ?>"
                                       data-assessor-name="<?php echo $assignment["assessor_fullname"]; ?>"
                                       name="remind[]" class="remind">
                            </div>
                        </td>
                        <td>
                            <div>
                                <input type="checkbox" value="<?php echo $assignment["dassessment_id"]; ?>"
                                       data-assessment-id="<?php echo $assignment["dassessment_id"]; ?>"
                                       data-assessor-type="<?php echo $assignment["assessor_type"]; ?>"
                                       data-assessor-value="<?php echo $assignment["assessor_value"]; ?>"
                                       data-assessor-name="<?php echo $assignment["assessor_fullname"]; ?>"
                                       data-target-id="<?php echo $assignment["target_value"]; ?>"
                                       data-target-name="<?php echo $assignment["target_name"]; ?>"
                                       data-target-type="<?php echo $assignment["target_type"]; ?>"
                                       data-delegation-id="<?php echo $assignment["addelegation_id"]?>"
                                       data-assignment-id="<?php echo $assignment["addassignment_id"]?>"
                                       name="delete[]" class="delete-delegation-assessment">
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render the header for a delegation task table.
     *
     * @param int $distribution_id
     * @param int $delegation_task_id
     * @param string $distribution_name
     * @param string $form_name
     * @param string $delegator_name
     * @param int $start_date
     * @param int $end_date
     * @param int $delivery_date
     * @param string $block_or_date_badge
     * @param string $delegation_url
     * @param bool $upcoming_task
     * @param bool $task_not_available
     * @param bool $show_notification_button
     */
    private function renderDelegationTableHeaderRow($distribution_id, $delegation_task_id, $distribution_name, $form_name, $delegator_name, $start_date, $end_date, $delivery_date, $block_or_date_badge, $delegation_url, $upcoming_task = false, $task_not_available = false, $show_notification_button = false) {
        global $translate; ?>
        <tr class="table-header-summary-row<?php echo ($task_not_available) ? "-not-available" : ""; ?>">
            <th colspan="6">
                <div class="pull-left">
                    <p><strong><?php echo html_encode($form_name); ?></strong></p>
                    <p><?php echo $block_or_date_badge; ?></p>
                    <p><?php echo strftime("%Y-%m-%d", $start_date) . " {$translate->_("to")} " . strftime("%Y-%m-%d", $end_date); ?></p>
                </div>
                <div class="pull-right text-right">
                    <?php /*<p><strong><?php echo html_encode($distribution_name); ?></strong></p>*/ ?>
                    <?php if ($upcoming_task): ?>

                        <p><?php echo sprintf($translate->_("Will be delivered to <strong>%s</strong> (Delegator) on %s"), $delegator_name, strftime("%Y-%m-%d", $delivery_date)); ?></p>

                    <?php elseif($task_not_available): ?>

                        <p><?php echo $translate->_("This task is not yet available."); ?></p>
                        <p><?php echo sprintf($translate->_("It will be delivered to <strong>%s</strong> (Delegator) on %s."), $delegator_name, strftime("%Y-%m-%d", $delivery_date)); ?></p>

                    <?php else: ?>

                        <p><?php echo sprintf($translate->_("Delivered to <strong>%s</strong> (Delegator) on %s"), $delegator_name, strftime("%Y-%m-%d", $delivery_date)); ?></p>

                    <?php endif; ?>
                    <?php if ($delegation_url): ?>
                        <p><a href="<?php echo $delegation_url; ?>" target="_blank"><?php echo $translate->_("View this delegation in a new window"); ?></a></p>
                    <?php endif; ?>
                    <?php if ($show_notification_button) {
                        $this->renderDelegatorReminderControls($distribution_id, $delegation_task_id);
                    } ?>
                </div>
            </th>
        </tr>
        <?php if ($upcoming_task || $task_not_available): ?>
            <tr>
                <th width="30%"><?php echo $translate->_("Target"); ?></th>
                <th><?php echo $translate->_("Potential Assessors"); ?></th>
            </tr>
        <?php else: ?>
            <tr class="table-header-headings-row">
                <th><?php echo $translate->_("Targets"); ?></th>
                <th><?php echo $translate->_("Assessors"); ?></th>
                <th><?php echo $translate->_("Delegated On"); ?></th>
                <th><?php echo $translate->_("Assessment Status"); ?></th>
                <th class="heading-icon"><i class="icon-bell"></i></th>
                <th class="heading-icon"><i class="icon-trash"></i></th>
            </tr>
        <?php endif;
    }

    /**
     * Render pending delegations, including those not created by cron yet.
     *
     * @param int $distribution_id
     * @param array $distribution_data
     * @param array $all_tasks
     * @param array $assignments_summary
     * @param array $all_available_assessors
     * @param Models_Assessments_Distribution_Delegator $delegator
     */
    private function renderDelegationsTablePending($distribution_id, $distribution_data, $all_tasks, $assignments_summary, $all_available_assessors, $delegator) {
        global $translate;
        $container_ordinal = 0;
        ?>
        <h2 class="pull-left"><?php echo $translate->_("Pending Delegations");?></h2>

        <div class="pull-right space-above medium">
            <div class="controls">
                <?php /*
                <label for="pending-collapse-all" class="checkbox">
                    <input type="checkbox" value="1" id="pending-collapse-all" class="delegation-progress-collapse-all-all-assessors" data-progress-type="pending">
                    <?php echo $translate->_("Collapse all available assessor drawers"); ?>
                </label>
                */?>
                <label for="pending-hide-empty" class="checkbox">
                    <input type="checkbox" value="1" id="pending-hide-empty" class="delegation-progress-hide-empty" data-progress-type="pending">
                    <?php echo $translate->_("Hide empty delegations"); ?>
                </label>
            </div>
        </div>

        <div class="clearfix"></div>

        <?php foreach ($all_tasks[$distribution_id] as $task):

            if (!$task["meta"]["should_exist"] || $task["meta"]["deleted_date"]) {
                continue; // skip "upcoming" or deleted
            } else {
                if ($task["current_record"]) {
                    if ($task["current_record"]->getCompletedDate()) {
                        continue; // Skip completed records
                    }
                    $addelegation_id = $task["current_record"]->getID();
                    $target_assignments = $assignments_summary[$addelegation_id];
                } else {
                    $addelegation_id = null;
                    $target_assignments = array();
                }
            }
            $container_ordinal++;
            $has_assignments = 0;
            foreach ($target_assignments as $ta) {
                if (!empty($ta["assessors"])) {
                    $has_assignments = 1;
                }
            }
            ?>
            <div class="delegation-progress-table-pending delegation-progress-table-pending-<?php echo $container_ordinal?>" data-has-assignments="<?php echo $has_assignments; ?>" data-progress-type="pending">
                <table class="table table-striped table-bordered assessment-delegation-progress-table">
                <?php if ($addelegation_id && !empty($target_assignments)): ?>
                    <thead>
                        <?php
                        $start_date = $distribution_data["delegations"][$addelegation_id]->getStartDate();
                        $end_date = $distribution_data["delegations"][$addelegation_id]->getEndDate();
                        $delegation_url = ENTRADA_URL . "/assessments/delegation?addelegation_id=$addelegation_id&adistribution_id={$distribution_data["distribution"]->getID()}";

                        $this->renderDelegationTableHeaderRow(
                            $distribution_data["distribution"]->getID(),
                            $addelegation_id,
                            $distribution_data["distribution"]->getTitle(),
                            $distribution_data["distribution_form"]->getTitle(),
                            $distribution_data["distribution_delegator_name"],
                            $start_date,
                            $end_date,
                            $distribution_data["delegations"][$addelegation_id]->getDeliveryDate(),
                            $this->distribution_delegation->getConcatenatedBlockOrDateString($start_date, $end_date, null, false),
                            $delegation_url,
                            false,
                            false,
                            true
                        );
                        ?>
                    </thead>
                    <tbody>
                    <?php if (empty($target_assignments)):?>

                        <tr>
                            <td colspan="6"><?php echo $translate->_("There are no targets associated with this delegation.");?></td>
                        </tr>

                    <?php else:?>

                        <?php foreach ($target_assignments as $target): ?>
                            <tr class="target-pending-block target-block assessment-delegation-target-block">
                                <td>
                                    <div class="distribution-progress-row assessment-delegation-target-block">
                                        <?php $this->renderUserBadge($target, $target["use_members"]); ?>
                                    </div>
                                </td>
                                <td colspan="5" class="no-margin no-padding no-border-bottom">
                                    <?php $this->renderDelegationTableBodyAssessorAssignments($target, $distribution_data["delegations"][$addelegation_id], $delegator); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        /* NOTE: Disabling the container and toggle bar for now. This functionality can be leveraged later once more UI requirements are added.
                        <tr class="all-assessor-block-container">
                            <td class="no-padding" colspan="6">
                                <?php $this->renderAllAvailableAssessorsContainer($container_ordinal, "pending", $all_available_assessors, $target_assignments); ?>
                            </td>
                        </tr>
                        <tr class="all-assessor-toggle-bar text-center assessor-toggle-pending-<?php echo $container_ordinal?>" data-progress-type="pending" data-toggle-ordinal="<?php echo $container_ordinal?>">
                            <td colspan="6" class="no-border-radius text-center assessor-toggle-arrow-pending-<?php echo $container_ordinal?>">
                                <div>
                                    <?php $this->renderDownArrow(); ?>
                                </div>

                            </td>
                        </tr>
                        */ ?>
                    <?php endif; ?>
                    </tbody>

                <?php else: ?>

                    <thead>
                        <?php
                        $this->renderDelegationTableHeaderRow(
                            $distribution_id,
                            null,
                            $distribution_data["distribution"]->getTitle(),
                            $distribution_data["distribution_form"]->getTitle(),
                            $distribution_data["distribution_delegator_name"],
                            $task["meta"]["start_date"],
                            $task["meta"]["end_date"],
                            $task["meta"]["delivery_date"],
                            $this->distribution_delegation->getConcatenatedBlockOrDateString($task["meta"]["start_date"], $task["meta"]["end_date"], null, false),
                            null,
                            false,
                            true
                        );
                        ?>
                    </thead>
                    <tbody>
                    <?php if (empty($task["targets"])):?>

                        <tr>
                            <td colspan="2"><?php echo $translate->_("There are no targets associated with this delegation.");?></td>
                        </tr>

                    <?php else:?>

                        <?php foreach ($task["targets"] as $target): ?>
                            <tr class="target-pending-block target-block assessment-delegation-target-block">
                                <td>
                                    <div class="distribution-progress-row assessment-delegation-target-block">
                                        <?php $this->renderUserBadge($target, $target["use_members"]); ?>
                                    </div>
                                </td>
                                <td class="no-margin no-padding no-border-bottom">
                                    <?php $this->renderAllAvailableAssessorsContainer($container_ordinal, "pending-but-not-existing", $task["assessors"], array(), false, false, false); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                <?php endif; ?>
                </table>
            </div>
        <?php endforeach; ?>

        <?php if ($container_ordinal == 0): ?>
            <div class="no-pending-delegations">
                <p><?php echo $translate->_("There are no pending delegations.");?></p>
            </div>
        <?php endif;?>
        <?php
    }

    /**
     * Render completed delegations table.
     *
     * @param array $distribution_data
     * @param array $assignments_summary
     * @param array $completed_delegations
     * @param Models_Assessments_Distribution_Delegator $delegator
     */
    private function renderDelegationsTableComplete($distribution_data, $assignments_summary, $completed_delegations, $delegator) {
        global $translate;
        $container_ordinal = 0;
        ?>
        <h2 class="pull-left"><?php echo $translate->_("Completed Delegations");?></h2>

        <div class="pull-right space-above medium">
            <div class="controls">
                <?php /*
                <label for="complete-collapse-all" class="checkbox">
                    <input type="checkbox" value="1" id="complete-collapse-all" class="delegation-progress-collapse-all-all-assessors" data-progress-type="complete">
                    <?php echo $translate->_("Collapse all available assessor drawers"); ?>
                </label>
                */?>
                <label for="complete-hide-empty" class="checkbox">
                    <input type="checkbox" value="1" id="complete-hide-empty" class="delegation-progress-hide-empty" data-progress-type="complete">
                    <?php echo $translate->_("Hide empty delegations"); ?>
                </label>
            </div>
        </div>

        <div class="clearfix"></div>

        <?php foreach ($assignments_summary as $addelegation_id => $target_assignments):
            if (!isset($completed_delegations[$addelegation_id])) {
                continue; // The given assignments are for an record that isn't in the completed list, so skip it.
            }
            /*if (empty($target_assignments)) {
                continue; // In the case where a delegation was made and there are no targets for it, yet it was marked complete, we don't show it. This edge case is no longer possible, but still exists in the data.
            }*/
            if (!$completed_delegations[$addelegation_id]->getCompletedDate()) {
                continue; // skip in progress delegations
            }
            if ($completed_delegations[$addelegation_id]->getDeletedDate()) {
                continue; // skip deleted delegations
            }
            $container_ordinal++;
            $has_assignments = 0;
            foreach ($target_assignments as $ta) {
                if (!empty($ta["assessors"])) {
                    $has_assignments = 1;
                }
            }
            ?>
            <div class="delegation-progress-table-complete delegation-progress-table-complete-<?php echo $container_ordinal?>" data-has-assignments="<?php echo $has_assignments; ?>" data-progress-type="complete">
                <table class="table table-striped table-bordered assessment-delegation-progress-table">
                    <thead>
                    <?php
                    $start_date = $completed_delegations[$addelegation_id]->getStartDate();
                    $end_date = $completed_delegations[$addelegation_id]->getEndDate();
                    $distribution_id = $completed_delegations[$addelegation_id]->getAdistributionID();
                    $delegation_url = ENTRADA_URL . "/assessments/delegation?addelegation_id=$addelegation_id&adistribution_id={$distribution_id}";

                    $this->renderDelegationTableHeaderRow(
                        $distribution_data["distribution"]->getID(),
                        $addelegation_id,
                        $distribution_data["distribution"]->getTitle(),
                        $distribution_data["distribution_form"]->getTitle(),
                        $distribution_data["distribution_delegator_name"],
                        $start_date,
                        $end_date,
                        $completed_delegations[$addelegation_id]->getDeliveryDate(),
                        $this->distribution_delegation->getConcatenatedBlockOrDateString($start_date, $end_date, null, false),
                        $delegation_url
                    );
                    ?>
                    </thead>
                    <tbody>
                    <?php if (empty($target_assignments)):?>

                        <tr>
                            <td colspan="6"><?php echo $translate->_("There are no targets associated with this delegation.");?></td>
                        </tr>

                    <?php else:?>

                        <?php foreach ($target_assignments as $target): ?>
                            <tr class="target-pending-block target-block assessment-delegation-target-block">
                                <td class="table-target-td">
                                    <div class="distribution-progress-row assessment-delegation-target-block">
                                        <?php $this->renderUserBadge($target, $target["use_members"]); ?>
                                    </div>
                                </td>
                                <td colspan="5" class="no-margin no-padding no-border-bottom">
                                    <?php $this->renderDelegationTableBodyAssessorAssignments($target, $completed_delegations[$addelegation_id], $delegator); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        /* NOTE: Disabling the container and toggle bar for now. This functionality can be leveraged later once more UI requirements are added.
                        <tr class="all-assessor-block-container">
                            <td class="no-padding" colspan="6">
                                <?php $this->renderAllAvailableAssessorsContainer($container_ordinal, "complete", $all_available_assessors, $target_assignments); ?>
                            </td>
                        </tr>
                        <tr class="all-assessor-toggle-bar text-center assessor-toggle-complete-<?php echo $container_ordinal?>" data-progress-type="complete" data-toggle-ordinal="<?php echo $container_ordinal?>">
                            <td colspan="6" class="no-border-radius text-center assessor-toggle-arrow-complete-<?php echo $container_ordinal?>">
                                <div>
                                    <?php $this->renderDownArrow(); ?>
                                </div>
                            </td>
                        </tr>
                        */
                        ?>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <?php if ($container_ordinal == 0): ?>
            <div class="no-pending-delegations">
                <p><?php echo $translate->_("There are no completed delegations.");?></p>
            </div>
        <?php endif;?>
        <?php
    }

    /**
     * Render the upcoming delegations table.
     *
     * @param array $distribution_data
     * @param array $upcoming_delegations
     */
    private function renderDelegationsTableUpcoming($distribution_data, $upcoming_delegations) {
        global $translate;
        $container_ordinal = 0;
        ?>
        <h2><?php echo $translate->_("Upcoming Delegations");?></h2>
        <?php foreach ($upcoming_delegations as $upcoming_delegation):
            $container_ordinal++;
            ?>
            <div class="delegation-progress-table-upcoming delegation-progress-table-upcoming-<?php echo $container_ordinal?>" data-progress-type="upcoming">
                <table class="table table-striped table-bordered assessment-delegation-progress-table">
                    <thead>
                    <?php
                    $this->renderDelegationTableHeaderRow(
                        $distribution_data["distribution"]->getID(),
                        null,
                        $distribution_data["distribution"]->getTitle(),
                        $distribution_data["distribution_form"]->getTitle(),
                        $distribution_data["distribution_delegator_name"],
                        $upcoming_delegation["start_date"],
                        $upcoming_delegation["end_date"],
                        $upcoming_delegation["delivery_date"],
                        $this->distribution_delegation->getConcatenatedBlockOrDateString($upcoming_delegation["start_date"], $upcoming_delegation["end_date"], null, false),
                        null,
                        true
                    );
                    ?>
                    </thead>
                    <tbody>
                    <?php if (empty($upcoming_delegation["targets"])):?>

                        <tr>
                            <td colspan="2"><?php echo $translate->_("There are no targets associated with this delegation.");?></td>
                        </tr>

                    <?php else:?>

                        <?php foreach ($upcoming_delegation["targets"] as $target): ?>
                            <tr class="target-pending-block target-block assessment-delegation-target-block">
                                <td>
                                    <div class="distribution-progress-row assessment-delegation-target-block">
                                        <?php $this->renderUserBadge($target, $target["use_members"]); ?>
                                    </div>
                                </td>
                                <td class="no-margin no-padding no-border-bottom">
                                    <?php $this->renderAllAvailableAssessorsContainer($container_ordinal, "upcoming", $upcoming_delegation["assessors"], array(), false, false, false); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        <?php if ($container_ordinal == 0): ?>
            <div class="no-pending-delegations">
                <p><?php echo $translate->_("There are no upcoming delegations.");?></p>
            </div>
        <?php endif;?>
        <?php
    }
}