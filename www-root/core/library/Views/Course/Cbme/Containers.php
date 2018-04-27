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
class Views_Course_Cbme_Containers extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id"))) {
            return false;
        }

        if (!$this->validateIsArray($options, array("assessment_plan_containers"))) {
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
        <h1 class="muted"><?php echo $translate->_("Assessment Plans") ?></h1>
        <?php
        /**
         * Render the Course CBME subnavigation
         */
        $navigation_view = new Views_Course_Cbme_Navigation();
        $navigation_view->render(array(
            "course_id" => $options["course_id"],
            "active_tab" => "assessment_plans"
        ));
        ?>
        <div class="row-fluid space-below space-above medium">
            <a href="<?php echo ENTRADA_URL ?>/admin/courses/cbme/plans?section=container&id=<?php echo $options["course_id"] ?>" class="btn btn-success pull-right"><?php echo $translate->_("Add Assessment Plan") ?></a>
        </div>
        <?php if ($options["assessment_plan_containers"]) : ?>
            <form method="post" action="<?php echo ENTRADA_URL ?>/admin/courses/cbme/plans?id=<?php echo $options["course_id"] ?>&step=2">
                <table class="table table-striped table-bordered">
                    <tr>
                        <th width="5%"></th>
                        <th width="65%"><?php echo $translate->_("Title") ?></th>
                        <th width="30%"><?php echo $translate->_("Curriculum Period") ?></th>
                    </tr>
                    <?php foreach ($options["assessment_plan_containers"] as $assessment_plan_container) : ?>
                        <?php $url = ENTRADA_URL . "/admin/courses/cbme/plans?section=container&id=" . $options["course_id"] . "&assessment_plan_container_id=" . $assessment_plan_container["assessment_plan_container_id"]; ?>
                        <tr>
                            <td><input type="checkbox" name="assessment_plan_containers[]" value="<?php echo $assessment_plan_container["assessment_plan_container_id"] ?>" data-title="<?php echo html_encode($assessment_plan_container["title"]) ?>" /></td>
                            <td><a href="<?php echo $url ?>"><?php echo html_encode($assessment_plan_container["title"]) ?></a></td>
                            <td><a href="<?php echo $url ?>"><?php echo sprintf($translate->_("%s to %s"), date("Y-m-d", $assessment_plan_container["curriculum_period"]["start_date"]), date("Y-m-d", $assessment_plan_container["curriculum_period"]["finish_date"])) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="row-fluid">
                    <a href="#" class="btn btn-default"><?php echo $translate->_("Cancel") ?></a>
                    <a href="#delete-plan-modal" id="delete-plan-btn" data-toggle="modal" class="btn btn-danger pull-right"><?php echo $translate->_("Remove Assessment Plans") ?></a>
                </div>
            </form>
        <?php else : ?>
            <div class="alert alert-warning">
                <?php echo $translate->_("This course does not have any assessment plans associated with it. To add an assessment plan click the <strong>Add Assessment Plan button</strong> above.") ?>
            </div>
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
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/cbme/assessment-plans.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/assessment-plans.css\" />";
    }
}