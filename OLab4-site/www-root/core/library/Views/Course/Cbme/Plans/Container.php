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
 * View class for rendering the add assessment plan interface
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Plans_Container extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id", "curriculum_periods", "assessment_plan_container", "assessment_plan_container_id", "assessment_plan_cperiods"))) {
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
        $this->renderHead($options["course_id"], $options["assessment_plan_container_id"]);
        ?>
        <h1 class="muted"><?php echo $translate->_("Assessment Plans"); ?></h1>
        <?php
        /**
         * Render the Course CBME subnavigation
         */
        $navigation_view = new Views_Course_Cbme_Navigation();
        $navigation_view->render(array(
            "course_id" => $options["course_id"],
            "active_tab" => "assessment_plans"
        ));

        if (isset($options["assessment_plan_container"]) && is_array($options["assessment_plan_container"]) && !empty($options["assessment_plan_container"]["title"])) {
            ?>
            <h2 class="muted"><?php echo html_encode($options["assessment_plan_container"]["title"]); ?></h2>
            <?php
        } else {
            ?>
            <h2 class="muted"><?php echo $translate->_("Add Assessment Plan") ?></h2>
            <?php
        }
        ?>

        <form method="post" action="<?php echo ENTRADA_URL ?>/admin/courses/cbme/plans?section=container&id=<?php echo $options["course_id"] ?>&step=2<?php echo ($options["assessment_plan_container_id"] ? "&assessment_plan_container_id=" . $options["assessment_plan_container_id"] : "") ?>">
            <div class="control-group">
                <label for="plan-title" class="control-label form-required"><?php echo $translate->_("Title") ?></label>
                <div class="controls">
                    <input id="plan-title" type="text" name="title" class="input-block-level" value="<?php echo (isset($options["assessment_plan_container"]) && is_array($options["assessment_plan_container"]) && array_key_exists("title", $options["assessment_plan_container"]) ? html_encode($options["assessment_plan_container"]["title"]) : "") ?>" />
                </div>
            </div>

            <div class="control-group">
                <label for="description" class="control-label"><?php echo $translate->_("Description") ?></label>
                <div class="controls">
                    <textarea id="description" name="description" class="input-block-level" rows="3"><?php echo (isset($options["assessment_plan_container"]) && is_array($options["assessment_plan_container"]) && array_key_exists("description", $options["assessment_plan_container"]) ? html_encode($options["assessment_plan_container"]["description"]) : "") ?></textarea>
                </div>
            </div>

            <?php if ($options["curriculum_periods"]) : ?>
                <div class="control-group">
                    <label for="curriculum-period" class="control-label form-required"><?php echo $translate->_("Curriculum Period") ?></label>
                    <div class="controls">
                        <select name="cperiod_id" id="curriculum-period">
                            <option value="0"><?php echo $translate->_("Select a curriculum period") ?></option>
                            <?php foreach ($options["curriculum_periods"] as $curriculum_period) : ?>
                                <?php
                                $plan_cperiod_id = Entrada_Utilities::multidimensionalArrayValue($options, null, "assessment_plan_container", "cperiod_id");
                                $option_selected = ($plan_cperiod_id == $curriculum_period["cperiod_id"]) ? "selected=\"selected\"" : "";
                                $option_disabled = (in_array($curriculum_period["cperiod_id"], $options["assessment_plan_cperiods"])) ? "disabled" : "";
                                ?>
                                <option value="<?php echo $curriculum_period["cperiod_id"] ?>" <?php echo $option_selected ?> <?php echo $option_disabled ?>>
                                    <?php echo sprintf($translate->_("%s to %s"), date("Y-m-d", $curriculum_period["start_date"]), date("Y-m-d", $curriculum_period["finish_date"])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row-fluid space-above">
                <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme/plans?id=" . $options["course_id"]; ?>" class="btn"><?php echo $translate->_("Cancel"); ?></a>
                <input type="submit" value="<?php echo $translate->_("Save Plan") ?>" class="btn btn-success pull-right" />
            </div>
        </form>
        <?php
    }

    /**
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     *
     * @param int $course_id
     * @param string $assessment_plan_container_id
     */
    protected function renderHead ($course_id = 0, $assessment_plan_container_id = 0) {
        global $translate, $BREADCRUMB;
        $BREADCRUMB[] = array("url" => ENTRADA_URL ."/admin/courses/cbme?".replace_query(array("section" => "container", "id" => $course_id, "assessment_plan_container_id" => $assessment_plan_container_id)), "title" => $translate->_("Plan"));
    }
}