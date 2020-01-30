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
 * A view for rendering the CBME filter search bar footer
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_Footer extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("reset_button_text", "apply_button_text", "form_reset_url"));
    }

    /**
     * Render the view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="filter-options-footer">
            <div class="list-filter-btn-group">
                <a href="<?php echo $options["form_reset_url"]; ?>" id="reset-filter-options" class="btn btn-default"><?php echo html_encode($options["reset_button_text"]) ?></a>
                <input type="submit" class="apply-filter-options btn btn-primary" value="<?php echo html_encode($options["apply_button_text"]) ?>">
            </div>
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
            <strong><?php echo $translate->_("Unable to render filter header."); ?></strong>
        </div>
        <?php
    }
}