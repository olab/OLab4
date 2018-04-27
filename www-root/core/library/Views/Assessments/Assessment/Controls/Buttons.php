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
 * HTML view for assessment forms that have an assessment type of send_blank_form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Assessment_Controls_Buttons extends Views_HTML {

    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the curriculum mapping form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $button_text = array_key_exists("button_text", $options) ? $options["button_text"] : $translate->_("Submit"); // Default when no button text is specified.
        $button_classes = array_key_exists("button_classes", $options) ? $options["button_classes"] : "";
        $has_selections = array_key_exists("has_selections", $options) ? $options["has_selections"] : false;
        $display_modal = Entrada_Utilities::arrayValueOrDefault($options, "is_learner", false);

        $disabled_text = $has_selections ? "" : "disabled";
        ?>
        <div class="row-fluid">
            <div class="pull-right">
                <input type="submit" id="save-form" class="btn btn-warning" name="save_form_progress" value="<?php echo $translate->_("Save as Draft") ?>" />
                <span class="or"><?php echo $translate->_("or") ?></span>
                <?php if ($display_modal == 1) : ?>
                    <a href="#assessment-cue-modal" class="btn btn-primary assessment-cue-btn <?php echo $button_classes ?> <?php echo $disabled_text?> " <?php echo $disabled_text ?> data-toggle="modal"><?php echo html_encode($button_text) ?></a>
                <?php else : ?>
                    <input class="btn btn-primary <?php echo $button_classes ?> <?php echo $disabled_text?>" <?php echo $disabled_text ?> type="submit" id="submit_form" name="submit_form" value="<?php echo html_encode($button_text) ?>" />
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render a custom error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render assessment buttons"); ?></strong>
        </div>
        <?php
    }
}