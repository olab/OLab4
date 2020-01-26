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
 * Base view class for all form-related views.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Base extends Views_Assessments_Base {

    /**
     * Special property for forms-related views.
     *
     * Modes available and used to all view types:
     *   "editor"               Editor mode
     *   "editor-readonly"      Editor mode with elements disabled
     *   "assessment"           Public display mode, elements populated if progress exists
     *   "assessment-blank"     Public display mode, no populated elements
     *   "pdf"                  PDF output elements popoulated if possible
     *   "pdf-blank"            PDF output for blank form
     */
    protected $mode = "assessment-blank";
    protected $valid_modes = array("editor", "editor-readonly", "assessment", "assessment-blank", "pdf", "pdf-blank", "assessment-complete");

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }

    protected function validateMode() {
        if (in_array($this->mode, $this->valid_modes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render form view"); ?></strong>
        </div>
        <?php
    }

}