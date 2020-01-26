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
 * A view for rendering the milestone report interface
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Reports_Milestone extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("course_id", "proxy_id"));
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <form method="POST" id="generate-milestone-reports-form" action="<?php echo ENTRADA_URL . "/assessments/reports/milestone"; ?>">
            <input type="hidden" name="proxy_id" value="<?php echo $options["proxy_id"] ?>" />
            <input type="hidden" name="course_id" value="<?php echo $options["course_id"] ?>" />
            <input type="hidden" name="step" value="2" />
            <div class="control-group space-above">
                <div class="date-error-msgs"></div>
                <label class="control-label" for="rating_scale"><?php echo $translate->_("Select Date Range"); ?></label>
                <div class="controls">
                    <div class="input-append">
                        <input type="text" name="start_date" class="datepicker input-small" value=""/>
                        <span class="add-on pointer"><i class="icon-calendar"></i></span>
                    </div>
                    <span><?php echo $translate->_("to"); ?></span>
                    <div class="input-append">
                        <input type="text" name="finish_date" class="datepicker input-small" value=""/>
                        <span class="add-on pointer"><i class="icon-calendar"></i></span>
                    </div>
                    <button type="button" id="get-assessment-tools" class="btn btn-primary space-below space-left" ><?php echo $translate->_("Get Tools"); ?></button>
                    <button type="button" id="select-all-tools" class="btn btn-default space-below space-left disabled pull-right"><?php echo $translate->_("Select All Tools"); ?></button>
                </div>
                <div class="space-above space-below report-instructions">
                    <?php echo $translate->_("Begin by selecting a date range and clicking Get Tools to see a list of tools which can be used to generate the report"); ?>
                </div>
                <div id="assessment-tool-loading" class="hide space-above space-below">
                    <span><?php echo $translate->_("Loading Assessment Tools"); ?></span>
                    <img class="load-more-tasks-spinner" src="<?php echo ENTRADA_URL ?>/images/indicator.gif"/>
                </div>
                <div class="assessment-tool-section hide">
                    <label class="control-label" for="assessment_tool"><?php echo $translate->_("Select Assessment Tool"); ?></label>
                    <ul class="assessment-tool-list milestone-report-assessment-tool-list">
                    </ul>
                    <div class="text-right">
                        <input type="submit" class="btn btn-primary space-above" id="generate-milestone-report" value="<?php echo $translate->_("Generate Report"); ?>" />
                    </div>
                </div>
            </div>
        </form>
        <?php
    }
}
