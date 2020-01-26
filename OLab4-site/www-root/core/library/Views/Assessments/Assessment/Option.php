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
 * View for rendering assessment options
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Assessment_Option extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateArray($options, array("assessment_options"));
    }

    /**
     * Render the meta related data.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        foreach ($options["assessment_options"] as $key => $option) {
            switch ($option["option_name"]) {
                case "assessment_cue":
                    $option_value_array = json_decode($option["option_value"], true);
                    if (isset($option_value_array["cue"])) : ?>
                        <div class="assessment-cue-card">
                            <h3 class="no-margin"><?php echo $translate->_("Assessment Cue"); ?></h3>
                            <div class="assessment-cue-text"><?php echo html_encode($option_value_array["cue"]); ?></div>
                        </div>
                        <?php
                    endif;
                break;
                // If there are other meta options to render, we can add them here.
                default:
                break;
            }
        }
    }


    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render form meta data"); ?></strong>
        </div>
        <?php
    }
}