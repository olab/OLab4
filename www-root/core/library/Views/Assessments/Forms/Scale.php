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
 * HTML view for an assessment form item. This view acts as a form item
 * view factory, instantiating item views based on the specified type.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Scale extends Views_Assessments_Forms_Base
{
    protected $rubric_state = null;
    protected $referrer = null;
    private $visibility_flags = array();

    /**
     * Views_Assessments_Forms_Rubrics_Rubric constructor.
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->configureVisibility();
    }

    private function configureVisibility() {
        $this->setDefaultVisibility();
        if (!$this->rubric_state) {
            $this->setRubricStateByMode();
        }
        $this->setVisibilityByRubricState();
    }

    private function setDefaultVisibility() {
        $this->visibility_flags = array(
            "show_header_title" => true,
            "show_header_control_bar" => false,
            "show_header_arrows" => false,
            "show_header_pencil" => false,
            "show_header_trash" => false,
            "show_header_checkbox" => false,
            "show_header_description" => true,
            "show_header_deleted_rubric_notice" => true,
            "show_subheader_control_bars" => false,
            "show_subheader_arrows" => false,
            "show_subheader_pencil" => false,
            "show_subheader_trash" => false,
            "show_subheader_checkbox" => false,
            "show_subheader_deleted_item_notice" => true,
            "show_descriptor_edit_controls" => false,
            "show_label_edit_controls" => false,
            "disable_inputs" => false,
            "disable_header_controls" => true,
            "disable_subheader_controls" => true,
            "disable_descriptor_edit_controls" => true,
            "disable_label_edit_controls" => true,
            "disable_subheader_pencil" => true
        );
    }

    private function setRubricStateByMode() {
        if ($this->validateMode()) {
            switch ($this->mode) {
                case "assessment":
                case "assessment-blank":
                case "pdf":
                case "pdf-blank":
                    $this->rubric_state = "assessment";
                    break;
                case "editor":
                case "editor-readonly":
                    $this->rubric_state = "form-edit-clean";
                    break;
            }
        }
    }

    protected function validateOptions($options = array())
    {
        if (!$this->validateIsSet($options, array("itemtype_shortname"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array())
    {

    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render rating scale"); ?></strong>
        </div>
        <?php
    }
}