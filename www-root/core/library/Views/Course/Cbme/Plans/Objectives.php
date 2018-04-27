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
 * View class for rendering the assessment plans interface
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Plans_Objectives extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id", "objectives", "assessment_plan_container_id"))) {
            return false;
        }
        return true;
    }

    /**
     * Render the EPA editor
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->renderHead();
        ?>
        <hr />
        <h2><?php echo $translate->_("Plan EPAs") ?></h2>
        <?php if ($options["objectives"]) : ?>
            <div class="control-group">
                <label for="objective-search-control"><?php echo $translate->_("Search EPAs"); ?></label>
                <div class="controls">
                    <input id="objective-search-control" placeholder="<?php echo $translate->_("Begin typing to search EPAs"); ?>" type="text" class="search-icon input-block-level">
                </div>
            </div>

            <div id="no-search-results" class="hide"><?php echo $translate->_("No EPAs found matching the provided search text.") ?></div>

            <ul id="objective-list-set" class="list-set assessment-plan-objective-list">
            <?php foreach ($options["objectives"] as $objective) : ?>
                <li class="list-set-item objective-list-item">
                    <?php $url = ENTRADA_URL . "/admin/courses/cbme/plans?section=plan&id=" . $options["course_id"] . "&assessment_plan_container_id=" . $options["assessment_plan_container_id"] . "&objective_id=" . $objective["objective_id"] . "&cbme_objective_tree_id=" . $objective["cbme_objective_tree_id"] ?>
                    <a href="<?php echo $url ?>" class="list-set-item-cell objective-code"><?php echo html_encode($objective["objective_code"]); ?></a>
                    <a href="<?php echo $url ?>" class="list-set-item-cell full-width objective-name"><?php echo html_encode($objective["objective_name"]); ?></a>
                    <div class="list-set-item-cell assessment-plan-list-item-status">
                        <?php if ($objective["assessment_plan_id"]) : ?>
                            <?php if (($objective["assessment_plan_published"]) &&
                                    ($objective["active_from"] == null || $objective["active_from"] <= time()) &&
                                    ($objective["active_until"] == null || $objective["active_until"] >= time())) : ?>
                                <span class="fa fa-check-circle status-icon has-plan"></span>
                            <?php elseif (!$objective["assessment_plan_published"]) : ?>
                                <span class="fa fa-exclamation-circle status-icon plan-warning"></span>
                            <?php else : ?>
                                <span class="fa fa-exclamation-circle status-icon plan-error"></span>
                            <?php endif; ?>
                        <?php else :  ?>
                            <span class="fa fa-circle status-icon no-plan"></span>
                        <?php endif; ?>
                        <span class="fa fa-chevron-right arrow-icon"></span>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php
    }

    /**
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead() {
        global $HEAD;

        /**
         * Add all JavaScript translations
         */
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/assessment-plans.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/cbme/assessment-plans.js\"></script>";
    }

}