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
 * A view for rendering CBME assessments
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Pins extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("assessments", "total_count", "filtered_count", "filters", "filter_list_data", "advanced_search_epas", "advanced_search_roles", "advanced_search_milestones", "course_assessment_tools", "rating_scales", "preferences", "course_id", "course_name", "courses", "query_limit", "course_stage_filters", "form_action_url", "form_reset_url", "navigation_urls"));
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $learner_number = array_key_exists("learner_number", $options) ? $options["learner_number"] : "";
        $learner_firstname = array_key_exists("learner_firstname", $options) ? $options["learner_firstname"] : "";
        $learner_lastname = array_key_exists("learner_lastname", $options) ? $options["learner_lastname"] : "";
        $learner_email = array_key_exists("learner_email", $options) ? $options["learner_email"] : "";

        $this->renderHead($options["advanced_search_epas"], $options["advanced_search_roles"], $options["advanced_search_milestones"], $options["course_stage_filters"], $options["query_limit"]); ?>
        <h1><?php echo $translate->_("CBME Dashboard") ?></h1>
        <?php

        /**
         * Instantiate and render the course picker
         */
        $course_picker_view = new Views_CBME_CoursePicker();
        $course_picker_view->render(array("course_id" => $options["course_id"], "course_name" => $options["course_name"], "courses" => $options["courses"]));

        if (isset($options["proxy_id"]) && $learner_firstname && $learner_lastname) {
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
         * Instantiate and render the CBME navigation
         */
        $navigation_view = new Views_CBME_NavigationTabs();
        $navigation_view->render(array("active_tab" => "pinned", "navigation_urls" => $options["navigation_urls"], "proxy_id" => $options["proxy_id"]));

        /**
         * Fetch the rotation schedules for filtering
         */
        $schedule_model = new Entrada_CBME_RotationSchedule();
        $schedule_filters = array();
        $rotation_schedule_audience_membership = Models_Schedule_Audience::fetchAllByProxyID($options["proxy_id"], true);
        if ($rotation_schedule_audience_membership) {
            foreach ($rotation_schedule_audience_membership as $audience) {
                $schedules = $schedule_model->fetchRotations($audience["schedule_parent_id"], null, $options["proxy_id"]);
                $parent_schedule = Models_Schedule::fetchRowByID($schedules[0]["schedule_parent_id"]);
                if ($schedules) {
                    if (count($schedules) > 1) {
                        foreach ($schedules as $key => $schedule) {
                            if ($schedules[0]["schedule_slot_id"] == $schedules[1]["schedule_slot_id"] - 1) {
                                //Consecutive
                                $start_date = $schedules[0]["start_date"];
                                $end_date = $schedules[1]["end_date"];
                                array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $start_date, "end_date" => $end_date, "schedule_title" => $parent_schedule->getTitle()));
                                break;
                            } else {
                                $start_date = $schedule["start_date"];
                                $end_date = $schedule["end_date"];
                                array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $start_date, "end_date" => $end_date, "schedule_title" => $parent_schedule->getTitle()));
                            }
                        }
                    } else {
                        array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $schedules[0]["start_date"], "end_date" => $schedules[0]["end_date"], "schedule_title" => $parent_schedule->getTitle()));
                    }
                }
            }
        }

        usort($schedule_filters, function ($a, $b) {
            $a = $a['start_date'];
            $b = $b['start_date'];
            if ($a == $b)  {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        /**
         * Instantiate the card view
         */
        $card_view = new Views_CBME_Assessment_Card();
        $item_card_view = new Views_CBME_Item_Card();
        ?>
        <div class="clearfix"></div>
        <?php
        $assessment_filter_view = new Views_CBME_Filter_Assessments();

        $assessment_filter_view->render(array(
            "filters" => $options["filters"],
            "filter_list_data" => $options["filter_list_data"],
            "course_assessment_tools" => $options["course_assessment_tools"],
            "rating_scales" => $options["rating_scales"],
            "preferences" => $options["preferences"],
            "course_id" => $options["course_id"],
            "assessments" => $options["assessments"],
            "total_count" => $options["total_count"],
            "filtered_count" => $options["filtered_count"],
            "query_limit" => $options["query_limit"],
            "section" => "assessments",
            "form_action_url" => $options["form_action_url"],
            "form_reset_url" => $options["form_reset_url"],
            "proxy_id" => $options["proxy_id"],
            "schedule_filters" => $schedule_filters
        ));

        /**
         * Instantiate and render the selected filter list view
         */
        $filter_list_view = new Views_CBME_Filter_List();
        foreach ($options["filter_list_data"] as $key => $filters) {
            switch ($key) {
                case "epas" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered EPAs"), "filter_type" => $key));
                    break;
                case "roles" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered CanMEDs Roles"), "filter_type" => $key));
                    break;
                case "milestones" :
                    foreach ($filters as $key => $filter) {
                        $filter_list_view->render(array("filter_list_data" => $filter, "filter_label" => sprintf($translate->_("Filtered %s Milestones"), $filter[0]["filter_type"]), "filter_type" => $key));
                    }
                    break;
                case "contextual_variables" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered Contextual Variables"), "filter_type" => $key));
                    foreach ($options["filter_list_data"][$key] as $filter) {
                        if (array_key_exists("objective_" . $filter["value"], $options["filter_list_data"])) {
                            $filter_list_view->render(array("filter_list_data" => $options["filter_list_data"]["objective_" . $filter["value"]], "filter_label" => sprintf($translate->_("Filtered by %s"), $filter["title"]), "filter_type" => $key));
                        }
                    }
                    break;
                case "selected_users" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered Assessors"), "filter_type" => $key));
                    break;
            }
        }
        if (isset($options["preferences"]["pinned_tab_view_preference"])) {
            $preferences = $options["preferences"]["pinned_tab_view_preference"];
        }
        ?>
        <div class="tab-content no-overflow">
            <div class="tab-pane <?php echo isset($options["preferences"]["pinned_tab_view_preference"]) && $options["preferences"]["pinned_tab_view_preference"] == "assessment" ? 'active' : ''; echo !(isset($options["preferences"]["pinned_tab_view_preference"])) ? ' active' : ''; ?>" id="pinned-assessments">
                <input type="hidden" name="pinned_only" value="true" />
                <h2><?php echo $translate->_("Assessments"); ?></h2>
                <?php if ($options["assessments"]) : ?>
                    <ul id="assessment-cards" class="list-card">
                        <?php
                        foreach ($options["assessments"] as $dassessment_id => $assessment) {
                            $card_view->render($assessment);
                        }
                        ?>
                    </ul>
                    <div class="clearfix"></div>
                    <a id="show-more-assessments-btn" href="#" class="btn btn-default btn-block" data-limit="<?php echo html_encode($options["query_limit"]) ?>" data-offset="0"><?php echo sprintf($translate->_("Showing %s of %s Filtered Assessments"), "<span id=\"displayed-count\">" . count($options["assessments"]) . "</span>", $options["pinned_assessment_count"]) ?></a>
                <?php else : ?>
                    <div class="alert alert-info"><?php echo $translate->_("No assessments found matching the provided filters.") ?></div>
                <?php endif; ?>
            </div>
            <div class="tab-pane <?php echo isset($options["preferences"]["pinned_tab_view_preference"]) && $options["preferences"]["pinned_tab_view_preference"] == "item" ? 'active' : ''; ?>" id="pinned-items">
                <h2><?php echo $translate->_("Items") ?></h2>
                <?php if ($options["items"]) : ?>
                    <ul id="item-cards" class="list-card">
                        <?php foreach ($options["items"] as $item) :
                            $item_card_view->render($item);
                        endforeach; ?>
                    </ul>
                    <div class="clearfix"></div>
                    <a id="show-more-items-btn" href="#" class="btn btn-default btn-block" data-limit="<?php echo html_encode($options["query_limit"]) ?>" data-offset="0"><?php echo sprintf($translate->_("Showing %s of %s Filtered Items"), "<span id=\"displayed-item-count\">" . count($options["items"]) . "</span>", "<span id=\"filtered-item-count\">" . $options["pinned_item_count"] . "</span>") ?></a>
                <?php else : ?>
                    <div class="alert alert-info"><?php echo $translate->_("No items found matching the provided filters.") ?></div>
                <?php endif; ?>
            </div>
            <div class="tab-pane <?php echo isset($options["preferences"]["pinned_tab_view_preference"]) && $options["preferences"]["pinned_tab_view_preference"] == "comment" ? 'active' : ''; ?>" id="pinned-comments">
                <h2><?php echo $translate->_("Comments") ?></h2>
                <?php if ($options["assessment_comments"]) :
                    $is_comment_card = true; ?>
                    <ul id="assessment-comment-cards" class="list-card">
                        <?php foreach ($options["assessment_comments"] as $assessment) :
                            $card_view->render(array(
                                "dassessment_id" => $assessment["dassessment_id"],
                                "atarget_id" => $assessment["atarget_id"],
                                "aprogress_id" => $assessment["aprogress_id"],
                                "form_type" => $assessment["form_type"],
                                "title" => $assessment["title"],
                                "created_date" => $assessment["created_date"],
                                "updated_date" => $assessment["updated_date"],
                                "assessor" => $assessment["assessor"],
                                "selected_iresponse_order" => $assessment["selected_iresponse_order"],
                                "rating_scale_responses" => $assessment["rating_scale_responses"],
                                "mapped_epas" => $assessment["mapped_epas"],
                                "comments" => $assessment["comments"],
                                "is_comment_card" => $is_comment_card
                            ));
                        endforeach; ?>
                    </ul>
                    <div class="clearfix"></div>
                    <a id="show-more-comments-btn" href="#" class="btn btn-default btn-block" data-limit="<?php echo html_encode($options["query_limit"]) ?>" data-offset="0"><?php echo sprintf($translate->_("Showing %s of %s Filtered Assessments"), "<span id=\"displayed-comment-count\">" . count($options["assessment_comments"]) . "</span>", "<span id=\"filtered-comment-count\">" . $options["total_count"] . "</span>") ?></a>
                <?php else : ?>
                    <div class="alert alert-info"><?php echo $translate->_("No assessments found matching the provided filters.") ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        /**
         * Instantiate and render the assessment card template
         */
        $assessment_card_view = new Views_CBME_Templates_AssessmentCard();
        $assessment_card_view->render();

        /**
         * Instantiate and render the assessment comment card template
         */
        $assessment_comment_card_view = new Views_CBME_Templates_AssessmentCommentCard();
        $assessment_comment_card_view->render();

        /**
         * Instantiate and render the comment template
         */
        $comment_template_view = new Views_CBME_Templates_Comment();
        $comment_template_view->render();

        /**
         * Instantiate and render the rating scale template
         */
        $rating_scale_view = new Views_CBME_Templates_RatingScale();
        $rating_scale_view->render(array("icon_type" => "star"));

        /**
         * Instantiate and render the EPA tag template
         */
        $epa_tag_view = new Views_CBME_Templates_EPATag();
        $epa_tag_view->render();
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead ($advanced_search_epas = array(), $advanced_search_roles = array(), $advanced_search_milestones = array(), $course_stage_filters = array(), $query_limit = 24) {
        global $translate;
        global $HEAD;
        global $JAVASCRIPT_TRANSLATIONS;
        global $BREADCRUMB;

        /**
         * Registers required JS string translations
         */
        $JAVASCRIPT_TRANSLATIONS[] = "var cbme_assessments = {};";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.user_filter = '" . addslashes($translate->_("Assessors")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.epa_filter = '" . addslashes($translate->_("Select EPAs")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.role_filter = '" . addslashes($translate->_("Select CanMEDs Roles")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.milestone_filter = '" . addslashes($translate->_("Select Milestones")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_user_response = '" . addslashes($translate->_("No Users found matching the search criteria.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.contextual_variable_filter = '" . addslashes($translate->_("Select Contextual Variables")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_epa_response = '" . addslashes($translate->_("No EPAs found matching the search criteria.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_role_response = '" . addslashes($translate->_("No Roles found matching the search criteria.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_milestone_response = '" . addslashes($translate->_("No Milestones found matching the search criteria.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_contextual_variable_response = '" . addslashes($translate->_("No Contextual Variables found matching the search criteria.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_assessments.no_contextual_variable_responses_response = '" . addslashes($translate->_("No Contextual Variables found matching the search criteria.")) . "';";
        Entrada_Utilities::addJavascriptTranslation("A problem occurred while attempting to load assessment comment data. Please try again later.", "assessment_comment_error", "cbme_assessments");
        Entrada_Utilities::addJavascriptTranslation("N/A", "no_comment", "cbme_assessments");
        Entrada_Utilities::addJavascriptTranslation("No Learners Found", "no_learners_found", "cbme_translations");
        Entrada_Utilities::addJavascriptTranslation("Learners", "filter_component_label", "cbme_translations");
        Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_period_filter_label", "cbme_translations");

        /**
         * Include required CSS files
         */
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.animated-notices.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

        /**
         * Include required js files
         */
        $HEAD[] = "<script type=\"text/javascript\">var query_limit = parseInt('". $query_limit ."');</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/assessments.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/course-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/learner-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_epas = " . json_encode($advanced_search_epas) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_roles = " . json_encode($advanced_search_roles) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_milestones = " . json_encode($advanced_search_milestones) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var course_stage_filters = " . json_encode($course_stage_filters) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.animated-notices.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME assessments"); ?></strong>
        </div>
        <?php
    }
}