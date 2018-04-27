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
 * A view for rendering CBME items
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Items extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("items", "total_count", "filtered_count", "advanced_search_epas", "advanced_search_roles", "advanced_search_milestones", "filters", "filter_list_data", "course_assessment_tools", "rating_scales", "preferences", "course_id", "course_name", "courses", "query_limit", "course_stage_filters", "form_action_url", "form_reset_url", "navigation_urls", "rotation_schedule", "unread_assessment_count"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $learner_number = array_key_exists("learner_number", $options) ? $options["learner_number"] : "";
        $learner_firstname = array_key_exists("learner_firstname", $options) ? $options["learner_firstname"] : "";
        $learner_lastname = array_key_exists("learner_lastname", $options) ? $options["learner_lastname"] : "";
        $learner_email = array_key_exists("learner_email", $options) ? $options["learner_email"] : "";

        $this->renderHead($options["advanced_search_epas"], $options["advanced_search_roles"], $options["advanced_search_milestones"], $options["course_stage_filters"], $options["query_limit"]); ?>
        <h1><?php echo $translate->_("CBME Dashboard"); ?></h1>
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

        if (isset($options["learner_picker"]) && $options["learner_picker"]) {
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
        $navigation_view->render(array("active_tab" => "items", "navigation_urls" => $options["navigation_urls"], "proxy_id" => $options["proxy_id"], "pinned_view" => $options["pinned_view"], "unread_assessment_count" => $options["unread_assessment_count"]));

        /**
         * Instantiate the card view
         */
        $card_view = new Views_CBME_Item_Card();
        ?>
        <div class="clearfix"></div>
        <?php
        /**
         * Instantiate and render the CBME filter view
         */
        $assessment_filter_view = new Views_CBME_Filter_Assessments();
        $assessment_filter_view->render(array(
            "filters" => $options["filters"],
            "filter_list_data" => $options["filter_list_data"],
            "course_assessment_tools" => $options["course_assessment_tools"],
            "rating_scales" => $options["rating_scales"],
            "preferences" => $options["preferences"],
            "course_id" => $options["course_id"],
            "total_count" => $options["total_count"],
            "filtered_count" => $options["filtered_count"],
            "query_limit" => $options["query_limit"],
            "section" => "items",
            "form_action_url" => $options["form_action_url"],
            "form_reset_url" => $options["form_reset_url"],
            "proxy_id" => $options["proxy_id"],
            "schedule_filters" => $options["rotation_schedule"],
            "secondary_proxy_id" => $options["secondary_proxy_id"],
            "triggered_by" => $options["triggered_by"])
        );

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
                case "schedule_id" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered Rotation Schedules"), "filter_type" => $key));
                    break;
                case "form_types" :
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered Assessment Tools"), "filter_type" => $key,  "allow_close" => false));
                    break;
                case "rating_scale":
                    $filter_list_view->render(array("filter_list_data" => $filters, "filter_label" => $translate->_("Filtered Rating Scales"), "filter_type" => $key,  "allow_close" => false));
                    break;
            }
        }

        if ($options["pinned_view"]) {
            /**
             * Instantiate and render the pin sub navigation
             */
            $pin_navigation_view = new Views_CBME_PinNavigationTabs();
            $pin_navigation_view->render(array(
                "active_tab" => "items",
                "navigation_urls" => $options["navigation_urls"],
                "proxy_id" => $options["proxy_id"],
            ));
        }
        ?>
        <h2><?php echo $translate->_("Assessment Items"); ?></h2>
        <?php if ($options["items"]) : ?>
        <input type="hidden" name="pinned_only" value="<?php echo html_encode($options["pinned_view"]) ?>" />
        <ul id="item-cards" class="list-card">
            <?php foreach ($options["items"] as $item) :
                $card_view->render($item);
            endforeach; ?>
        </ul>
        <?php else : ?>
            <div class="alert alert-info"><?php echo $translate->_("No items found matching the provided filters.") ?></div>
        <?php endif; ?>
        <div class="clearfix"></div>
        <a id="show-more-items-btn" href="#" class="btn btn-default btn-block" data-limit="<?php echo html_encode($options["query_limit"]) ?>" data-offset="0"><?php echo sprintf($translate->_("Showing %s of %s Filtered Items"), "<span id=\"displayed-item-count\">" . count($options["items"]) . "</span>", $options["filtered_count"]) ?></a>
        <?php

        /**
         * Instantiate and render the item card template
         */
        $item_card_view = new Views_CBME_Templates_AssessmentItemCard();
        $item_card_view->render();

        /**
         * Instantiate and render the rating scale template
         */
        $rating_scale_view = new Views_CBME_Templates_RatingScale();
        $rating_scale_view->render(array("icon_type" => "circle"));

        /**
         * Instantiate and render the EPA tag template
         */
        $epa_tag_view = new Views_CBME_Templates_EPATag();
        $epa_tag_view->render();
    }

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
        Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_period");
        Entrada_Utilities::addJavascriptTranslation("Rotations", "rotations");
        Entrada_Utilities::addJavascriptTranslation("No Rotations Found", "no_rotations_found");
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

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME assessment items"); ?></strong>
        </div>
        <?php
    }
}