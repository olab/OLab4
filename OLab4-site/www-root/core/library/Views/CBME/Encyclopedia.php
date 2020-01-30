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
 * This view is for rendering information about a users EPAs
 * Description, Entrustment and Program Map are the data points included for each EPA
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Encyclopedia extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("stage_data", "courses"))) {
           return false;
        }
        if (!$this->validateIsSet($options, array("number_of_items_displayed", "epa_assessments_view_preferences", "tree_json"))) {
            return false;
        }

        return true;
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->renderHead($options["tree_json"]);
        ?>
        <h1><?php echo $translate->_("EPA Encyclopedia")?></h1>
        <?php
        $course_picker_view = new Views_CBME_CoursePicker();
        $course_picker_view->render(array("course_id" => $options["course_id"], "course_name" => $options["course_name"], "courses" => $options["courses"])); ?>
        <div class="clearfix"></div>
        <input placeholder="<?php echo $translate->_("Search EPAs..."); ?>" type="text" class="task-table-search search-icon stage-container-search wider-search">
        <div id="stage-container">
            <div id="no-results-found"></div>
            <div id="filter-container"></div>
            <?php if ($options["stage_data"]) :?>
                <?php foreach ($options["stage_data"] as $stages) :
                    if ($stages && isset($stages) && is_array($stages)) : ?>
                    <?php foreach ($stages as $stage => $stage_data) : ?>
                        <?php $view_preference = (isset($options["epa_assessments_view_preferences"][$stage_data["objective_code"]]) && $options["epa_assessments_view_preferences"][$stage_data["objective_code"]] == "collapsed" ? "collapsed" : "expanded"); ?>
                        <div class="stage-container">
                            <h2 class="pull-left" stage-id="<?php echo html_encode($stage_data["objective_code"]) ?>" data-stage="<?php echo html_encode($stage_data["objective_code"]) ?>"><?php echo html_encode($stage_data["objective_name"]) ?></h2>
                            <a class="stage-toggle pull-right <?php echo html_encode($view_preference) ?>" href="#" data-stage="<?php echo html_encode($stage_data["objective_code"]) ?>"><span class="stage-toggle-label"><?php echo ($view_preference == "collapsed"  ? $translate->_("Show") : $translate->_("Hide")) ?></span><span class="fa fa-angle-up"></span></a>
                            <div class="clearfix"></div>
                            <div class="epa-container <?php echo $view_preference == "collapsed"  ? "hide" : "" ?>" id="<?php echo html_encode($stage_data["objective_code"]) ?>">
                                <?php if (isset($stage_data["progress"])) : ?>
                                        <?php foreach ($stage_data["progress"] as $epa_progress) : ?>
                                        <ul stage="<?php echo html_encode($stage_data["objective_code"]) ?>" class="list-set stage-<?php echo html_encode($stage_data["objective_code"]) ?> bottom-gap <?php echo $epa_progress["objective_id"] == $options["objective_id"] ? "move-up" : "" ?>">
                                            <li>
                                                <div class="list-set-item-cell list-set-item-epa list-set-item-epa-encyclopedia">
                                                        <span class="list-set-item-title"><?php echo html_encode($epa_progress["objective_code"]) ?></span>
                                                        <span class="list-set-item-epa-description encyclopedia-desc objective-name"><?php echo html_encode($epa_progress["objective_name"]) ?></span>
                                                </div>
                                                <div class="description-container">
                                                    <?php if ($epa_progress["objective_description"]) : ?>
                                                        <a data-stage="<?php echo html_encode($epa_progress["objective_code"]) ?>" href="#" tabindex="-1" class="epa-progress-toggle list-set-item-cell description-header top-border">
                                                            <span class="list-set-item-epa-description encyclopedia-desc epa-name"><?php echo $translate->_("Detailed Description");?></span>
                                                            <span id="<?php echo html_encode($epa_progress["objective_code"]) ?>-show-hide" class="fa fa-angle-down dark-icon pull-right"></span>
                                                        </a>
                                                        <div class="epa-description-block collapsed" id="<?php echo html_encode($epa_progress["objective_code"]) ?>">
                                                            <?php echo html_entity_decode($epa_progress["objective_description"]); ?>
                                                        </div>
                                                    <?php endif;
                                                    if ($epa_progress["objective_secondary_description"] != "") : ?>
                                                        <a data-stage="<?php echo html_encode($epa_progress["objective_code"]) ?>-secondary" href="#" tabindex="-1" class="epa-progress-toggle-secondary description-header list-set-item-cell">
                                                            <span class="encyclopedia-desc list-set-item-epa-description  epa-name"><?php echo $translate->_("Entrustment");?> </span>
                                                            <span id="<?php echo html_encode($epa_progress["objective_code"]) ?>-secondary-show-hide" class="fa fa-angle-down dark-icon pull-right"></span>
                                                        </a>
                                                        <div class="epa-description-block-secondary collapsed" id="<?php echo html_encode($epa_progress["objective_code"]) ?>-secondary">
                                                            <?php echo html_entity_decode($epa_progress["objective_secondary_description"]); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                        <a data-stage="<?php echo html_encode($epa_progress["objective_code"]) ?>" href="#" tabindex="-1" class="epa-map-toggle description-header list-set-item-cell">
                                                            <span class="encyclopedia-desc list-set-item-epa-description  epa-name"><?php echo $translate->_("Program Map");?> </span>
                                                            <span id="<?php echo html_encode($epa_progress["objective_code"]) ?>-map-show-hide" class="fa fa-angle-down dark-icon pull-right"></span>
                                                        </a>
                                                        <div id="cbme-curriculum-map-<?php echo html_encode($epa_progress["objective_code"]) ?>" class="curriculum-map collapsed" id="<?php echo html_encode($epa_progress["objective_code"]) ?>-map">
                                                        </div>
                                                </div>
                                            </li>
                                        </ul>
                                        <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="muted">
                                        <?php echo $translate->_("No EPAs found within this Stage.") ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php $count = 0; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead ($tree_json) {
        global $HEAD;
        global $JAVASCRIPT_TRANSLATIONS;
        global $translate;

        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/encyclopedia.css\" />";
        $HEAD[] = "<script type=\"text/javascript\">var tree_json = $tree_json; </script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/d3.min.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/curriculum-tags.css\" />";

        $JAVASCRIPT_TRANSLATIONS[] = "var cbme_progress_dashboard = {};";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.hide = '" . addslashes($translate->_("Hide")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show = '" . addslashes($translate->_("Show")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show_more = '" . addslashes($translate->_("Show More")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "cbme_progress_dashboard.show_less = '" . addslashes($translate->_("Show Less")) . "';";

        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/encyclopedia.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/course-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
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