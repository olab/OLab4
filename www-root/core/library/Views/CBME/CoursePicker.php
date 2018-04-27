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
 * A view for rendering the CBME course picker
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_CoursePicker extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("course_id", "course_name", "courses"));
    }

    /**
     * Render the course picker view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div id="cbme-course-picker-container">
            <h2 class="cbme-course-heading pull-left"><Label for="cbme-course-picker"><?php echo html_decode($options["course_name"]) ?></label></h2>
            <?php if (count($options["courses"]) > 1) : ?>
            <select id="cbme-course-picker" class="pull-right">
                <?php foreach ($options["courses"] as $course) : ?>
                    <option value="<?php echo html_encode($course["course_id"]) ?>" <?php echo ($options["course_id"] == $course["course_id"] ? "selected=\"selected\""  : "") ?>><?php echo html_encode($course["course_name"]) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <div class="clearfix"></div>
        </div>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render course picker"); ?></strong>
        </div>
        <?php
    }
}