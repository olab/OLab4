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
 * View for the feedback and concerns checkboxes when creating/editing a form
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_FeedbackConcernsOptions extends Views_HTML {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        ?>
        <label class="checkbox" for="optional-concerns">
            <input type="hidden" name="concerns" id="optional-concerns" value="true" <?php echo isset($options["concerns"]) ? ($options["concerns"] == "true" ? "checked" : "") : "checked";?>>
        </label>
        <label class="checkbox" for="optional-feedback">
            <input type="hidden" name="feedback" id="optional-feedback" value="true" <?php echo isset($options["feedback"]) ? ($options["feedback"] == "true" ? "checked" : "") : "checked";?>>
        </label>
        <?php
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render Feedback and Concerns options."); ?></strong>
        </div>
        <?php
    }

}