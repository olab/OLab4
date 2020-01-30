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
 * A view for rendering CBME Progress
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Progress extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet(
            $options,
            array(
                "stage_data",
                "number_of_items_displayed",
                "epa_assessments_view_preferences",
                "courses",
                "course_name",
                "course_id",
                "navigation_urls",
                "proxy_id",
                "course_settings",
                "hide_trigger_assessment",
                "hide_meetings_log",
                "unread_assessment_count"
            )
        );
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        // TODO: Remove ENTRADA_USER from this context
        global $translate, $ENTRADA_USER;

        $this->renderHead();
        $learner_number = array_key_exists("learner_number", $options) ? $options["learner_number"] : "";
        $learner_firstname = array_key_exists("learner_firstname", $options) ? $options["learner_firstname"] : "";
        $learner_lastname = array_key_exists("learner_lastname", $options) ? $options["learner_lastname"] : "";
        $learner_email = array_key_exists("learner_email", $options) ? $options["learner_email"] : "";
        ?>
        <h1><?php echo $translate->_("CBME Dashboard") ?></h1>
        <?php if ($options["hide_trigger_assessment"] == false) : ?>
            <div class="row space-below medium">
                <a id="trigger-assessment"
                   href="<?php echo ENTRADA_URL . "/assessments?section=tools?&course_id=" . html_encode($options["course_id"]); ?>"
                   class="btn btn-success pull-right"><?php echo $translate->_("Trigger Assessment") ?></a>
                <a href="<?php echo ENTRADA_URL . "/meetings"; ?>"
                   class="btn btn-info space-right pull-right"><i class="fa fa-calendar-check-o fa-lg" aria-hidden="true"></i>
                    <?php echo $translate->_("My Meetings") ?></a>
            </div>
        <?php endif; ?>
        <?php if ($options["hide_meetings_log"] == false) : ?>
            <div class="row space-below medium text-right">
                <a id="milestone-report"
                   href="<?php echo ENTRADA_URL . "/assessments/reports/milestone?proxy_id=" . html_encode($options["proxy_id"]) . "&course_id=" . html_encode($options["course_id"]); ?>"
                   class="btn btn-default space-right">
                    <?php echo $translate->_("Milestone Report") ?></a>
                <a id="log-meeting"
                   href="<?php echo ENTRADA_URL . "/assessments/meetings?proxy_id=" . html_encode($options["proxy_id"]); ?>"
                   class="btn btn-info space-right pull-right"><i class="fa fa-calendar-check-o fa-lg" aria-hidden="true"></i>
                    <?php echo $translate->_("Log Meeting") ?></a>
            </div>
        <?php endif; ?>
        <?php
        /**
         * Instantiate and render the course picker
         */
        $course_picker_view = new Views_CBME_CoursePicker();
        $course_picker_view->render(
            array(
                "course_id" => $options["course_id"],
                "course_name" => $options["course_name"],
                "courses" => $options["courses"]
            )
        );

        if ($options["proxy_id"] && $learner_firstname && $learner_lastname) {
            $learner_array = array(
                "proxy_id" => $options["proxy_id"],
                "number" => $learner_number,
                "firstname" => $learner_firstname,
                "lastname" => $learner_lastname,
                "email" => $learner_email,
                "full_width" => true
            );
            $learner_card = new Views_User_Card();
            $learner_card->render($learner_array);
        }

        if (isset($options["learner_picker"])) {
            $learner_picker = new Views_CBME_LearnerPicker();
            $learner_picker->render(
                array("learner_preference" => $options["learner_preference"],
                    "proxy_id" => $options["proxy_id"],
                    "learner_name" => $options["learner_name"]
                )
            );
        }

        /**
         * Attempt to decode course setting json
         */
        $dashboard_enabled = true;
        if (isset($options["course_settings"]) && is_array($options["course_settings"]) && !empty($options["course_settings"])) {
            foreach ($options["course_settings"] as $setting) {
                if ($setting["shortname"] == "cbme_dashboard") {
                    $cbme_dashboard_setting = @json_decode($setting["value"], true);
                    if ($cbme_dashboard_setting && is_array($cbme_dashboard_setting) && !empty($cbme_dashboard_setting)) {
                        $dashboard_enabled = $cbme_dashboard_setting["enabled"];
                    }
                }
            }
        }
        /**
         * Get the users rotation schedule
         */
        $objectives = null;
        $current_objective = null;
        $schedule = Models_Schedule::fetchRowByAudienceValueAudienceType($options["proxy_id"], "proxy_id", true, time());
        /**
         * Get all of the objectives for the current schedule
         */
        if ($schedule) {
            $course_objective_model = new Models_Schedule_CourseObjective();
            $objectives = $course_objective_model->fetchAllByScheduleIDCourseID($schedule->getCourseID(), $schedule->getScheduleParentID());
        }
        /**
         * Determine if viewing user is a competencies committee member
         */
        $is_ccmember = isset($options["is_ccmember"]) ? $options["is_ccmember"] : false;

        /**
         * Instantiate and render the Assessment List Item Template
         */
        $item_card_view = new Views_CBME_Templates_AssessmentListItem();
        $item_card_view->render();

        /**
         * Instantiate and render the CBME navigation
         */
        if ($dashboard_enabled) {
            $navigation_view = new Views_CBME_NavigationTabs();
            $navigation_view->render(array("active_tab" => "stages", "navigation_urls" => $options["navigation_urls"], "proxy_id" => $options["proxy_id"], "unread_assessment_count" => $options["unread_assessment_count"], "pinned_view" => false));
        } ?>
        <div class="clearfix"></div>
        <?php if ($dashboard_enabled) : ?>
        <div id="stage-container">
        <?php if ($options["stage_data"]) :?>
            <?php foreach ($options["stage_data"] as $stage => $stage_data) : ?>
                <?php $view_preference = (isset($options["epa_assessments_view_preferences"][$stage_data["objective_code"]]) && $options["epa_assessments_view_preferences"][$stage_data["objective_code"]] == "collapsed" ? "collapsed" : "expanded"); ?>
                <div class="stage-container">
                    <h2 class="pull-left" data-stage="<?php echo html_encode($stage_data["objective_code"]) ?>"><?php echo html_encode($stage_data["objective_name"]) ?></h2>
                    <a class="stage-toggle pull-left <?php echo html_encode($view_preference) ?>" href="#" data-stage="<?php echo html_encode($stage_data["objective_code"]) ?>"><span class="fa fa-angle-up"></span></a>
                    <a href="#" class="pull-right list-set-item-cell list-item-status-<?php echo !$stage_data["completed"] ? 'in' : ''; ?>complete list-set-item-status<?php echo $is_ccmember ? "" : " disabled" ?>"<?php echo $is_ccmember ? " data-id=\"" . html_encode($stage) . "\"" : ""; ?>>
                        <span id="span-toggle-objective-<?php echo $stage; ?>"
                                     class="stage-toggle-objective list-set-item-icon fa <?php echo $stage_data["completed"] ? 'fa-check-circle-o item-complete' : 'fa-circle-o'; ?><?php echo $is_ccmember ? "" : " disabled" ?>"
                                     data-toggle="tooltip"
                                     data-objective-set="<?php echo $translate->_("Stage"); ?>"
                                     title="<?php echo $stage_data["completed"] ? $translate->_("Completed") : $translate->_("In Progress")?>">

                        </span>
                    </a>
                    <div class="clearfix"></div>
                    <div class="epa-container" style="display:<?php echo ($view_preference == "collapsed"  ? " none" : " block") ?>;">
                        <?php if (isset($stage_data["progress"])) : ?>
                            <?php foreach ($stage_data["progress"] as $epa_progress) : ?>
                                <?php
                                if ($objectives) {
                                    foreach ($objectives as $objective) {
                                        if ($objective["objective_code"] === $epa_progress["objective_code"] && $epa_progress["completed"] == 0) {
                                            $current_objective = $objective;
                                            break;
                                        } else {
                                            $current_objective = null;
                                        }
                                    }
                                }
                                ?>
                                <ul class="list-set stage-<?php echo html_encode($stage_data["objective_code"]); echo $current_objective ? " highlighted-epa-row" : ""; ?>">
                                    <li class="list-set-item inline-block">
                                        <a href="<?php echo $options["navigation_urls"]["assessments"] . ($options["proxy_id"] == $ENTRADA_USER->getID() ? "?epas[]=" . html_encode($epa_progress["objective_id"]) : "&epas[]=" . html_encode($epa_progress["objective_id"])) ?>" class="list-set-item-cell list-set-item-epa">
                                            <span class="list-set-item-title"><?php echo html_encode($epa_progress["objective_code"]) ?></span>
                                        </a>
                                        <a href="<?php echo $options["navigation_urls"]["assessments"] . ($options["proxy_id"] == $ENTRADA_USER->getID() ? "?epas[]=" . html_encode($epa_progress["objective_id"]) : "&epas[]=" . html_encode($epa_progress["objective_id"])) ?>" tabindex="-1" class="list-set-item-cell list-set-item-epa-description-cell">
                                            <span class="list-set-item-epa-description"><?php echo html_encode($epa_progress["objective_name"]) ?></span>
                                        </a>
                                        <div class="list-set-item-cell assessment-liklihood-priority">
                                            <?php
                                            if ($current_objective) {
                                                if ($current_objective["priority"] == "1") {
                                                    ?><span data-toggle="tooltip" title="<?php echo $translate->_("Priority"); ?>" class="fa fa-exclamation-circle priority-star inline-block"></span><?php
                                                } else {
                                                    ?><span data-toggle="tooltip" title="<?php echo $translate->_("Not Priority"); ?>" class="fa fa-exclamation-circle not-priority-star inline-block"></span><?php
                                                }
                                                switch ($current_objective["shortname"]) {
                                                    case "unlikely":
                                                        ?><img data-toggle="tooltip" class="inline-block space-left" title="<?php echo $translate->_("Unlikely"); ?>" src="<?php echo ENTRADA_URL; ?>/images/not_very_likely.svg"><?php
                                                    break;
                                                    case "likely":
                                                        ?><img data-toggle="tooltip" class="inline-block space-left" title="<?php echo $translate->_("Likely"); ?>" src="<?php echo ENTRADA_URL; ?>/images/likely.svg"><?php
                                                    break;
                                                    case "very_likely":
                                                        ?><img data-toggle="tooltip" class="inline-block space-left" title="<?php echo $translate->_("Very Likely"); ?>" src="<?php echo ENTRADA_URL; ?>/images/very_likely.svg"><?php
                                                    break;
                                                }
                                            } else { ?>
                                                <span class="fa fa-exclamation-circle not-priority-star inline-block"></span>
                                                <img class="space-left inline-block" src="<?php echo ENTRADA_URL; ?>/images/very_likely_disabled.svg">
                                        <?php } ?>
                                        </div>
                                        <a href="#" class="list-set-item-cell list-item-status-<?php echo !$epa_progress["completed"] ? 'in' : ''; ?>complete list-set-item-status<?php echo $is_ccmember ? "" : " disabled" ?>"<?php echo $is_ccmember ? " data-id=\"". html_encode($epa_progress["objective_id"]) . "\"" : ""; ?>>
                                            <span id="span-toggle-objective-<?php echo $epa_progress["objective_id"]; ?>"
                                                  class="list-set-item-icon fa <?php echo $epa_progress["completed"] ? 'fa-check-circle-o item-complete' : 'fa-circle-o'; ?><?php echo $is_ccmember ? "" : " disabled" ?>"
                                                  data-toggle="tooltip"
                                                  data-objective-set="<?php echo $translate->_("EPA"); ?>"
                                                  title="<?php echo $epa_progress["completed"] ? $translate->_("Completed") : $translate->_("In Progress")?>"
                                            ></span>
                                        </a>
                                        <div class="assessment-details-container" style="display: none;">
                                            <div class="assessment-data-container">
                                                <ul class="assessment-list" id="assessment-data-container-<?php echo $epa_progress["objective_id"]; ?>"></ul>
                                            </div>
                                        </div>
                                        <div class="stage-footer">
                                            <a href="#assessment-breakdown-modal"
                                               data-id="<?php echo $epa_progress["assessment_count"] ?>"
                                               data-html="<?php echo html_encode($epa_progress["objective_code"]) ?>"
                                               objective-id="<?php echo $epa_progress["objective_id"]; ?>"
                                               class="list-set-item-cell list-set-item-assessment-count"
                                               data-toggle="modal">
                                                <span class="list-set-item-label" data-toggle="tooltip" title="<?php echo html_encode($translate->_("View Assessment Breakdown")) ?>"><?php echo sprintf($translate->_("%s Assessments"), (int)$epa_progress["assessment_count"]) ?></span>
                                            </a>
                                            <div class="inline-block pull-right space-right loading-drawer-block">
                                                <img class='loading_spinner assessment-breakdown-spinner hide' src="<?php echo ENTRADA_URL ?>/images/loading.gif"/>
                                                <i class="fa fa-chevron-down assessment-breakdown-drawer" data-toggle="tooltip" title="<?php echo html_encode($translate->_("View Assessment Details")); ?>" data-proxy-id="<?php echo $options["proxy_id"] ?>" data-course-id="<?php echo $options["course_id"] ?>" data-objective-id="<?php echo $epa_progress["objective_id"]; ?>" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <?php endforeach; ?>
                        <?php else : ?>
                            <div class="alert alert-info">
                                <?php echo $translate->_("No Assessments found within this Stage.") ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alert alert-danger">
                <?php echo $translate->_("This program has no CBME progress to display for this learner."); ?>
            </div>
        <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="responsive-modal">
            <div class="modal fade breakdown-modal" id="assessment-breakdown-modal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="breakdown-title"></h3>
                    <p class="no-margin assessment-count"><?php echo sprintf($translate->_("Total (%u)"), $epa_progress["assessment_count"]); ?></p>
                    <input id="proxy-id" type="hidden" value="<?php echo $options["proxy_id"]; ?>">
                    <input id="course-id" type="hidden" value="<?php echo $options["course_id"]; ?>">
                </div>
                <div class="modal-body no-padding">
                    <div class="full-width text-center hide" id="loading-spinner">
                        <img class="loading_spinner space-below" src="<?php echo ENTRADA_URL . '/images/loading.gif'; ?>"/>
                        <p><?php echo $translate->_("Loading Assessment Data"); ?></p>
                    </div>
                    <div class="modal-body-contents">
                        <h4 class="no-margin form-title"></h4>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead () {
        global $HEAD;
        global $JAVASCRIPT_TRANSLATIONS;
        global $translate;

        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

        $JAVASCRIPT_TRANSLATIONS[] = "var cbme_progress_dashboard = {};";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.hide = '" . addslashes($translate->_("Hide")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show = '" . addslashes($translate->_("Show")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show_more = '" . addslashes($translate->_("Show More")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show_less = '" . addslashes($translate->_("Show Less")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.breakdown_title = '" . html_encode($translate->_("%s Assessments")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.assessment_total = '" . html_encode($translate->_("Total (%s)")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.error_message = '" . html_encode($translate->_("Unknown Server Error")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.no_assessments = '" . html_encode($translate->_("There were no assessments found")) . "';";
        Entrada_Utilities::addJavascriptTranslation("No Learners Found", "no_learners_found", "cbme_translations");
        Entrada_Utilities::addJavascriptTranslation("Learners", "filter_component_label", "cbme_translations");
        Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_period_filter_label", "cbme_translations");

        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/dashboard.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/course-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/learner-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME data page"); ?></strong>
        </div>
        <?php
    }
}