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
 * View class for rendering preview dialog on form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_PreviewDialog extends Views_Assessments_Forms_Sections_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("form_html"));
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="preview-dialog-content">
            <div id="form-preview-dialog" class="preview-dialog-content-inner">
                <div class="row-fluid space-below inner-content">
                    <?php if ($options["form_html"]) : ?>
                        <?php echo $options["form_html"]; ?>
                    <?php else: ?>
                        <?php echo $translate->_("Nothing to preview."); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

}