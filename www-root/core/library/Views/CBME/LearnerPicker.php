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
 * A view for rendering the CBME learner picker
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_LearnerPicker extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("learner_name"));
    }

    /**
     * Render the learner picker view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $learner_preference = "";
        $learner_proxy = array_key_exists("proxy_id", $options) ? $options["proxy_id"] : null;

        if ($learner_proxy) {
            $learner_preference = isset($options["learner_preference"][$learner_proxy])
                ? $options["learner_preference"][$learner_proxy]
                : null;
        }
        ?>
        <form id="learner-form">
            <?php if ($learner_proxy) : ?>
                <input type="hidden"
                       value="<?php echo html_encode($learner_proxy); ?>"
                       id="learners_<?php echo html_encode($learner_proxy); ?>"
                       data-label="<?php echo html_encode($learner_preference); ?>"
                       class="search-target-control learners_search_target_control learner-selector">
            <?php endif; ?>
            <div class="control-group space-above" id="form-selector">
                <div class="controls">
                    <a id="cbme-learner-picker"
                       class="btn"
                       type="button">
                        <span class="space-right"><?php echo $options["learner_name"] ? html_encode($options["learner_name"]) : $translate->_("Browse Learners "); ?></span>
                        <i class="icon-chevron-down"></i>
                    </a>
                </div>
            </div>
        </form>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render learner picker"); ?></strong>
        </div>
        <?php
    }
}