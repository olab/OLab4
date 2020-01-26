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
 * View class for rendering learning events distribution progress pages.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Distribution_LearningEventProgress extends Views_Assessments_Base {

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

        if (!$this->validateIsSet($options, array("distribution", "events", "assessment_permissions"))) {
            return false;
        }

        if (!is_object($options["distribution"])) {
            return false;
        }

        // Passed validation
        return true;
    }

    /**
     * Main render logic for this view. Renders the progress page.
     *
     * @param mixed $options
     */
    protected function renderView($options = array()) {
        $distribution = $options["distribution"];

        $event_id = Entrada_Utilities::arrayValueOrDefault($options, "event_id");

        if ($event_id) {
            $this->renderDetailsView($distribution, $event_id, $options);
        } else {
            $this->renderListView($distribution, $options);
        }
    }

    /**
     * Render the learning event list for the distribution
     *
     * @param $distribution
     * @param $options
     */
    protected function renderListView($distribution, $options) {
        global $translate;
        $events = $options["events"];

        /**
         * Render filtering
         */
        $search_term = Entrada_Utilities::arrayValueOrDefault($options, "search_term", "");
        $date_range_start = Entrada_Utilities::arrayValueOrDefault($options, "date_range_start", $distribution->getStartDate());
        $date_range_end = Entrada_Utilities::arrayValueOrDefault($options, "date_range_end", $distribution->getEndDate());
        $this->renderEventListFiltering($date_range_start, $date_range_end, $search_term);
        ?>
        <table id="learning-events-progress" class="table table-striped table-bordered">
            <thead>
            <tr>
                <th width="30%"><?php echo $translate->_("Title"); ?></th>
                <th width="30%"><?php echo $translate->_("Date"); ?></th>
                <th width="10%" class="th-pending"><?php echo $translate->_("Not Started"); ?></th>
                <th width="15%" class="th-inprogress"><?php echo $translate->_("In Progress"); ?></th>
                <th width="10%" class="th-complete"><?php echo $translate->_("Complete"); ?></th>
                <th width="5%"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event_id => $event): ?>
                <tr class="data-row event-block">
                    <td class="event-block-event-details">
                        <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                            <?php echo html_encode($event["event_title"]); ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                        <?php if ($event["event_start_date"] || $event["event_end_date"]) : ?>
                            <?php echo date(DEFAULT_DATE_FORMAT, $event["event_start_date"]); ?>
                        <?php endif; ?>
                        </a>
                    </td>
                    <td>
                        <div class="assessment-card-count pending">
                            <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>&active_tab=pending" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                                <?php echo $event["progress_counts"]["pending"]; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="assessment-card-count inprogress">
                            <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>&active_tab=inprogress" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                                <?php echo $event["progress_counts"]["inprogress"]; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="assessment-card-count complete">
                            <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>&active_tab=complete" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                                <?php echo $event["progress_counts"]["complete"]; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-mini dropdown-toggle" title="Learning Event Options" data-toggle="dropdown">
                                <i class="fa fa-cog" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu toggle-left">
                                <li>
                                    <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=form&adistribution_id=<?php echo $distribution->getID(); ?>" target="_blank" title="<?php echo $translate->_("Edit Distribution"); ?>" class="edit-distribution" data-distribution-id="<?php echo $distribution->getID(); ?>">
                                        <?php echo $translate->_("View Distribution"); ?>
                                    </a>
                                </li><li>
                                    <a href="<?php echo ENTRADA_URL; ?>/events?rid=<?php echo $event_id; ?>" title="<?php echo $translate->_("Edit Learning Event"); ?>" target="_blank" class="edit-learning-event" data-event-id="<?php echo $event_id; ?>">
                                        <?php echo $translate->_("View Event"); ?>
                                    </a>
                                </li><li>
                                    <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/distributions?section=progress&adistribution_id=<?php echo $distribution->getID(); ?>&event_id=<?php echo $event_id; ?>" target="_blank" title="<?php echo $translate->_("View Learning Event Report"); ?>">
                                        <?php echo $translate->_("Progress"); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Generate the filtering section.
     *
     * @param $date_range_start
     * @param $date_range_end
     * @param string $search_term
     */
    protected function renderEventListFiltering($date_range_start, $date_range_end, $search_term = "") {
        global $translate;

        ?>
        <div class="row-fluid space-below">
            <div class="span4">
                <div id="event-search-block" class="clearfix space-below medium">
                    <input class="search-icon event-search-input" type="text" id="event-search-input" placeholder="<?php echo $translate->_("Search Events...") ?>" value="<?php echo $search_term; ?>"/>
                </div>
            </div>
            <div class="span8">
                <form name="date_range_from" id="date_range_from" class="form-inline" method="POST">
                    <label class="control-label form-nrequired span5 text-right" for="date-range-start">
                        <strong><?php echo $translate->_("Start"); ?>: </strong>
                        <div class="input-append">
                            <input id="date-range-start" name="date_range_start" class="input-small datepicker" type="text" value="<?php echo isset($date_range_start) ? html_encode(date("Y-m-d", $date_range_start)) : ""; ?>"/>
                            <span class="add-on pointer"><i class="icon-calendar"></i></span>
                        </div>
                    </label>

                    <label class="control-label form-nrequired span5 text-right" for="date-range-end">
                        <strong><?php echo $translate->_("End"); ?>: </strong>
                        <div class="input-append">
                            <input id="date-range-end" name="date_range_end" class="input-small datepicker" type="text" value="<?php echo isset($date_range_end) ? html_encode(date("Y-m-d", $date_range_end)) : ""; ?>"/>
                            <span class="add-on pointer"><i class="icon-calendar"></i></span>
                        </div>
                    </label>
                    <div class="span2">
                        <button type="submit" id="submit" class="btn btn-success pull-right space-left"><?php echo $translate->_("Filter"); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php
    }

    /**
     * Render a single learning event details page
     *
     * @param $distribution
     * @param $event_id
     * @param $options
     */
    protected function renderDetailsView($distribution, $event_id, $options) {
        $events = $options["events"];

        if (!$event = Entrada_Utilities::arrayValueOrDefault($events, $event_id)) {
            return;
        }

        $this->renderProgressSummary($event, $options);
        $this->renderPending($distribution, $event, $options);
        $this->renderInProgress($distribution, $event, $options);
        $this->renderComplete($distribution, $event, $options);

    }

    /**
     * Render single learning event summary
     *
     * @param $event
     * @param $options
     */
    protected function renderProgressSummary($event, $options) {
        global $translate;
        $PREFERENCES = isset($options["preferences"]) ? $options["preferences"] : array();
        $active_tab = Entrada_Utilities::arrayValueOrDefault($options, "active_tab");

        // Active tab is based on user preference, unless they followed a link that overrode this using the "active_tab" parameter.
        $active = isset($PREFERENCES["target_status_view"]) ? $PREFERENCES["target_status_view"] : "pending";
        if ($active_tab) {
            $active = $active_tab;
        }

        $pending = $event["progress_counts"]["pending"];
        $in_progress = $event["progress_counts"]["inprogress"];
        $complete = $event["progress_counts"]["complete"];
        ?>
        <div id="assessment-block" class="clearfix space-below">
            <div id="targets-pending-card" class="span4 assessment-card">
                <h4 class="pending"><?php echo $translate->_("Not Started"); ?></h4>
                <div class="assessment-card-count pending"><?php echo(isset($pending) && $pending > 0 ? ($pending < 10 ? "0" . $pending : $pending) : "0"); ?></div>
                <p class="assessment-card-description item pending"><?php echo sprintf($translate->_("There are %s form(s) that have not been started."), (isset($pending) && $pending ? $pending : "0")); ?></p>
                <a class="target-status-btn <?php echo ($active == "pending" ? "active" : ""); ?>" id="targets-pending-btn" data-target-status="pending" href="#"><?php echo $translate->_("Pending Assessments"); ?></a>
            </div>
            <div id="targets-inprogress-card" class="span4 assessment-card">
                <h4 class="inprogress"><?php echo $translate->_("In Progress"); ?></h4>
                <div class="assessment-card-count inprogress"><?php echo(isset($in_progress) && $in_progress > 0 ? ($in_progress < 10 ? "0" . $in_progress : $in_progress) : "0"); ?></div>
                <p class="assessment-card-description item inprogress"><?php echo sprintf($translate->_("There are %s form(s) that are in progress but not complete."), (isset($in_progress) && $in_progress ? $in_progress : "0")); ?></p>
                <a class="target-status-btn <?php echo ($active == "inprogress" ? "active" : ""); ?>" id="targets-inprogress-btn" data-target-status="inprogress" href="#"><?php echo $translate->_("Assessments In Progress"); ?></a>
            </div>
            <div id="targets-complete-card" class="span4 assessment-card">
                <h4 class="complete"><?php echo $translate->_("Completed"); ?></h4>
                <div class="assessment-card-count complete"><?php echo(isset($complete) && $complete > 0 ? ($complete < 10 ? "0" . $complete : $complete) : "0"); ?> </div>
                <p class="assessment-card-description item complete"><?php echo sprintf($translate->_("There are %s form(s) that are complete."), (isset($complete) && $complete ? $complete : "0")); ?></p>
                <a class="target-status-btn <?php echo ($active == "complete" ? "active" : ""); ?>" id="targets-complete-btn" data-target-status="complete" href="#"><?php echo $translate->_("Completed Assessments"); ?></a>
            </div>
        </div>
        <div id="target-search-block" class="clearfix space-below medium">
            <input class="search-icon" type="text" id="target-progress-search-input" placeholder="<?php echo $translate->_("Search Targets...") ?>"/>
        </div>
        <div id="distribution-load-error" class="alert alert-block alert-danger hide">
            <button type="button" class="close distribution-load-error-msg">&times;</button>
            <p><?php echo $translate->_("Failed to load distribution data. Please try again."); ?></p>
        </div>
        <?php
    }

    /**
     * Render the pending assessments tab for a single learning event details page
     *
     * @param $distribution
     * @param $event
     * @param $options
     */
    protected function renderPending($distribution, $event, $options) {
        global $translate;
        $assessment_permissions = $options["assessment_permissions"];
        $PREFERENCES = isset($options["preferences"]) ? $options["preferences"] : array();

        $assessments = $event["assessments"];
        $count = $event["progress_counts"]["pending"];

        echo "<div id=\"targets-pending-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "pending" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Pending Assessments") . " </h2>";

        if(!$count): ?>
            <div>
                <p id="no-pending-targets"
                   class="no-targets"><?php echo $translate->_("There are currently no assessments pending."); ?></p>
            </div>
            </div>
        <?php
            return;
        endif;
        if ($count) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left"
                   title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>"
                   data-toggle="modal"
                   data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>">
                    <i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown"
                            title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>"
                               class="edit-distribution"
                               title="<?php echo $translate->_("Edit Distribution Details"); ?>"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <li>
                            <a href="#reminder-modal"
                               title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }
        foreach ($assessments as $assessment) {
            if (isset($assessment->progress_list) && isset($assessment->progress_list["pending"])) { ?>
                <div id="targets-pending-table"
                     class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                    <?php $this->renderAssessorTable($distribution, $assessment, "pending");?>
                </div>
                <?php
            }
        } // endforeach assessments

        echo "</div>";
    }

    /**
     * Render the in progress assessments tab for a single learning event details page
     *
     * @param $distribution
     * @param $event
     * @param $options
     */
    protected function renderInProgress($distribution, $event, $options) {
        global $translate;
        $assessment_permissions = $options["assessment_permissions"];
        $PREFERENCES = isset($options["preferences"]) ? $options["preferences"] : array();

        $assessments = $event["assessments"];
        $count = $event["progress_counts"]["inprogress"];

        echo "<div id=\"targets-inprogress-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "inprogress" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Assessments In Progress") . " </h2>";

        if (!$count): ?>
            <div>
                <p id="no-inprogress-targets"
                   class="no-targets"><?php echo $translate->_("There are currently no assessments in progress."); ?></p>
            </div>
            </div>
        <?php
            return;
        endif;
        if ($count) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left"
                   title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal"
                   data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><i
                            class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown"
                            title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>"
                               class="edit-distribution"
                               title="<?php echo $translate->_("Edit Distribution Details"); ?>"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <li>
                            <a href="#reminder-modal"
                               title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }
        foreach ($assessments as $assessment) {
            if (isset($assessment->progress_list) && isset($assessment->progress_list["inprogress"])) { ?>
                <div id="targets-inprogress-table"
                     class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                    <?php $this->renderAssessorTable($distribution, $assessment, "inprogress"); ?>
                </div>
                <?php
            }
        } // endforeach assessments

        echo "</div>";
    }

    /**
     * Render the completed assessments tab for a single learning event details page
     *
     * @param $distribution
     * @param $event
     * @param $options
     */
    protected function renderComplete($distribution, $event, $options) {
        global $translate;
        $assessment_permissions = $options["assessment_permissions"];
        $PREFERENCES = isset($options["preferences"]) ? $options["preferences"] : array();

        $assessments = $event["assessments"];
        $count = $event["progress_counts"]["complete"];

        echo "<div id=\"targets-complete-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "complete" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Completed Assessments") . " </h2>";

        if (!$count): ?>
            <div>
                <p id="no-complete-targets"
                   class="no-targets"><?php echo $translate->_("There are currently no assessments completed."); ?></p>
            </div>
            </div>
        <?php
            return;
        endif;
        if ($count) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left"
                   title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal"
                   data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><i
                            class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown"
                            title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>"
                               class="edit-distribution"
                               title="<?php echo $translate->_("Edit Distribution Details"); ?>"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <li>
                            <a href="#reminder-modal"
                               title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"
                               data-adistribution-id="<?php echo html_encode($distribution->getID()) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }
        foreach ($assessments as $assessment) {
            if (isset($assessment->progress_list) && isset($assessment->progress_list["complete"])) { ?>
                <div id="targets-complete-table"
                     class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                    <?php $this->renderAssessorTable($distribution, $assessment, "complete", false);?>
                </div>
                <?php
            }
        } // endforeach assessments

        echo "</div>";
    }

    /**
     * Render the assessor table
     *
     * @param $distribution
     * @param $assessment
     * @param $progress_value
     * @param bool $show_notification_checkbox
     */
    protected function renderAssessorTable($distribution, $assessment, $progress_value, $show_notification_checkbox = true) {
        global $translate;

        if (is_array($assessment->progress_list)) {
            $progress_records = (!empty($assessment->progress_list[$progress_value]) ? $assessment->progress_list[$progress_value] : array());

            if (!count($progress_records)) {
                return;
            }
        }

        ?>
        <table class="table table-striped table-bordered assessment-learning-events-progress-table">
            <thead>
            <tr class="table-header-assessor-row">
                <th colspan="4">
                    <div>
                        <?php echo $translate->_("Assessor: "); ?>
                        <strong>
                            <?php echo(isset($assessment->assessor["name"]) ? html_encode($assessment->assessor["name"]) : "N/A"); ?>
                        </strong>
                        <?php
                        if (isset($assessment->assessor["email"])) {
                            echo "<a href=\"mailto:" . html_encode($assessment->assessor["email"]) . "\" target=\"_top\">" . html_encode($assessment->assessor["email"]) . "</a>";
                        }
                        ?>
                    </div>
                </th>
            </tr>
            <tr>
                <th width="44%"><?php echo $translate->_("Target(s)"); ?></th>
                <th width="41%"><?php echo $translate->_("Delivery Date"); ?></th>
                <th width="5%" class="heading-icon"><i class="icon-bell"></th>
                <th width="5%" class="heading-icon"><i class="icon-trash"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($progress_records as $target) { ?>
                <tr class="target-block<?php echo(!$target["should_exist"] && $progress_value != "complete" ? " deleted" : ""); ?>">
                    <td class="target-block-target-details">
                        <strong>
                            <?php
                            if ($assessment->getID() && ($target["should_exist"] || $progress_value == "complete")) {
                                $assessment_api = new Entrada_Assessments_Assessment(array("limit_dataset" => array("targets")));
                                $url = $assessment_api->getAssessmentURL($target["id"], $target["target_type"], false, $assessment->getID(), null);
                                echo "<a href=\"" . html_encode($url) . "\" title=\"View Form for " . html_encode($target["name"]) . "\">" . html_encode($target["name"]) . "</a>";
                            } else {
                                echo html_encode($target["name"]);
                                if (!$target["should_exist"] && $progress_value != "complete") {
                                    echo "<div class=\"task-deleted\"><i class=\"icon-trash\"></i>" . $translate->_(" (Deleted)") . "</div>";
                                }
                            }
                            ?>
                        </strong>
                    </td>
                    <td>
                        <?php
                        echo "<div><strong>";
                        if ( $assessment->getDeliveryDate() ) {
                            echo html_encode(date("Y-m-d", $assessment->getDeliveryDate()));
                        } else {
                            echo "N/A";
                        }
                        echo "</strong></div>";
                        ?>
                    </td>
                    <td>
                        <?php if ($show_notification_checkbox && $progress_value != "complete" && $target["should_exist"] && $assessment->getID()) { ?>
                            <div>
                                <input class="remind" type="checkbox" name="remind[]"
                                       data-assessor-name="<?php echo html_encode($assessment->assessor["name"]) ?>"
                                       data-assessment-id="<?php echo html_encode($assessment->getID()) ?>"
                                       value="<?php echo html_encode($assessment->assessor["id"]) ?>"/>
                            </div>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($target["should_exist"] && $progress_value != "complete") { ?>
                            <div>
                                <?php
                                if (!empty($target["current_record"])) {
                                    ?>
                                    <input class="delete" type="checkbox" name="delete[]"
                                           data-target-name="<?php echo html_encode($target["name"]); ?>"
                                           data-assessor-name="<?php echo html_encode($assessment->assessor["name"]); ?>"
                                           data-atarget-id="<?php echo html_encode($target["current_record"][0]->getID()); ?>"
                                           value="<?php echo html_encode($assessment->getDeliveryDate()); ?>"/>
                                <?php } else { ?>
                                    <input class="delete" type="checkbox" name="delete[]"
                                           data-target-name="<?php echo html_encode($target["name"]); ?>"
                                           data-target-id="<?php echo html_encode($target["id"]); ?>"
                                           data-target-type="<?php echo html_encode($target["target_type"]); ?>"
                                           data-assessor-name="<?php echo html_encode($assessment->assessor["name"]); ?>"
                                           data-assessor-value="<?php echo html_encode($assessment->assessor["id"]); ?>"
                                           data-assessor-type="<?php echo html_encode($assessment->getAssessorType()); ?>"
                                           data-form-id="<?php echo html_encode($assessment->getFormID()); ?>"
                                           data-organisation-id="<?php echo html_encode($assessment->getOrganisationID()); ?>"
                                           data-delivery-date="<?php echo html_encode($assessment->getDeliveryDate()); ?>"
                                           data-feedback-required="<?php echo html_encode($assessment->getFeedbackRequired()); ?>"
                                           data-min-submittable="<?php echo html_encode($assessment->getMinSubmittable()); ?>"
                                           data-max-submittable="<?php echo html_encode($assessment->getMaxSubmittable()); ?>"
                                           data-start-date="<?php echo html_encode($assessment->getStartDate()); ?>"
                                           data-end-date="<?php echo html_encode($assessment->getEndDate()); ?>"
                                           data-rotation-start-date="<?php echo html_encode($assessment->getRotationStartDate()); ?>"
                                           data-rotation-end-date="<?php echo html_encode($assessment->getRotationEndDate()); ?>"
                                           data-associated-record-type="<?php echo html_encode($assessment->getAssociatedRecordType()) ?>"
                                           data-associated-record-id="<?php echo html_encode($assessment->getAssociatedRecordID()); ?>"
                                           data-additional-task="<?php echo html_encode($assessment->getAdditionalAssessment() ? 1 : 0); ?>"
                                           data-task-type="<?php echo html_encode($distribution->getAssessmentType()); ?>"
                                           value="<?php echo html_encode($assessment->getDeliveryDate()); ?>"/>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <tr class="hide no-search-targets">
                <td colspan="5"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
            </tr>
            </tbody>
        </table>
        <?php
    }


    /**
     * Render the error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger"><?php echo $translate->_("No event(s) found for the current distribution."); ?></div>
        <?php
    }

}