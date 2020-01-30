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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Trends extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("assessments", "total_count", "filtered_count", "filters", "filter_list_data", "advanced_search_epas", "advanced_search_roles", "advanced_search_milestones", "course_assessment_tools", "rating_scales", "preferences", "course_id", "course_name", "courses", "query_limit", "trends_query_limit","form_action_url", "form_reset_url", "navigation_urls", "rotation_schedule", "unread_assessment_count"))) {
            return false;
        }

        if (!$this->validateArray($options, array("charts"))) {
            return false;
        }

        return true;
    }

    protected function renderView($options = array()) {
        global $translate;
        $learner_number = array_key_exists("learner_number", $options) ? $options["learner_number"] : "";
        $learner_firstname = array_key_exists("learner_firstname", $options) ? $options["learner_firstname"] : "";
        $learner_lastname = array_key_exists("learner_lastname", $options) ? $options["learner_lastname"] : "";
        $learner_email = array_key_exists("learner_email", $options) ? $options["learner_email"] : "";

        $this->renderHead($options["advanced_search_epas"], $options["advanced_search_roles"], $options["advanced_search_milestones"], $options["course_stage_filters"], $options["query_limit"], $options["trends_query_limit"]); ?>
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
        $navigation_view->render(array("active_tab" => "trends", "navigation_urls" => $options["navigation_urls"], "proxy_id" => $options["proxy_id"], "pinned_view" => false, "unread_assessment_count" => $options["unread_assessment_count"]));

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
            "form_action_url" => $options["form_action_url"],
            "form_reset_url" => $options["form_reset_url"],
            "section" => "trends",
            "proxy_id" => $options["proxy_id"],
            "schedule_filters" => $options["rotation_schedule"],
            "triggered_by" => $options["triggered_by"]
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

        $has_charts = false;
        if (count($options["charts"])) {
            foreach ($options["charts"] as $chart_data) {
                if (isset($chart_data["charts"]) && is_array($chart_data["charts"]) && count($chart_data["charts"])) {
                    $has_charts = true;
                }
            }
        }
        
        if ($has_charts):
            $tabs = array_keys($options["charts"]);
            $active_tab = isset($options["preferences"]["trends_selected_tab"]) && array_key_exists($options["preferences"]["trends_selected_tab"], $options["charts"])
                ? $options["preferences"]["trends_selected_tab"]
                : $tabs[0];

            $chart_view = new Views_CBME_Chart_Line();
            ?>
            <div class="clearfix"></div>
            <ul class="nav nav-tabs" role="tablist">
            <?php
            $tab_count = 0;
            foreach ($options["charts"] as $chart_type => $chart_data): ?>
                <li role="presentation"<?php echo ($chart_type == $active_tab) ? " class=\"active\"" : ""; ?>>
                    <a id="<?php echo $chart_type; ?>_tab" href="#<?php echo html_encode($chart_type); ?>" class="trend-tabs" data-chart-type="<?php echo $chart_type; ?>" aria-controls="home" role="tab" data-toggle="tab"><?php echo html_encode($chart_data["title"]); ?></a>
                </li>
                <script type="text/javascript">
                        jQuery(document).ready(function () {
                        jQuery("#<?php echo $chart_type; ?>_tab").on("click", function() {
                            if (!<?php echo $chart_type?>_loaded) {
                                var waitTillActive = setInterval(function () {
                                    if (jQuery("#<?php echo $chart_type; ?>").hasClass("active")) {
                                        jQuery("#<?php echo $chart_type; ?>").trigger('isNowActive')
                                        clearInterval(waitTillActive);
                                    }
                                }, 100);
                                <?php echo $chart_type?>_loaded = true;
                            }
                        });
                        var <?php echo $chart_type?>_loaded = false;
                    });
                </script>
            <?php endforeach;?>
            </ul>

            <div class="tab-content no-overflow">
            <?php
            $tab_count = 0;
            foreach ($options["charts"] as $chart_type => $chart_data): ?>
                <div role="tabpanel" class="tab-pane<?php echo ($chart_type == $active_tab) ? " active" : ""; ?>" id="<?php echo html_encode($chart_type); ?>">
                <?php foreach($chart_data["charts"] as $charts):
                    $lazyload_tab = ($chart_type != $active_tab) ? $chart_type : false;
                    $chart_view->render(array_merge(array("lazyload_tab" => $lazyload_tab, "trends_query_limit" => $options["trends_query_limit"]), $charts));
                endforeach; ?>
                </div>
           <?php
           endforeach;
        else: ?>
            <div class="alert alert-info"><?php echo $translate->_("No data available."); ?></div>
        <?php endif;
    }

    protected function renderHead ($advanced_search_epas = array(), $advanced_search_roles = array(), $advanced_search_milestones = array(), $course_stage_filters = array(), $query_limit = 24, $trends_query_limit = 40) {
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

        $HEAD[] = "<script>var ENTRADA_URL='".ENTRADA_URL."';</script>";
        $HEAD[] = "<script type=\"text/javascript\">var query_limit = parseInt('". $query_limit ."'); var trends_query_limit = parseInt('". $trends_query_limit ."');</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/cbme/trends.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/assessments.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/course-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/learner-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_epas = " . json_encode($advanced_search_epas) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_roles = " . json_encode($advanced_search_roles) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var advanced_search_milestones = " . json_encode($advanced_search_milestones) . ";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var course_stage_filters = " . json_encode($course_stage_filters) . ";</script>";
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME assessment items"); ?></strong>
        </div>
        <?php
    }
}