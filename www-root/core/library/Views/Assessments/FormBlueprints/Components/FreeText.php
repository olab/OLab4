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
 * View class for free text element in blueprints.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_FreeText extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("element_text", "component_id", "form_blueprint_id", "settings", "init_data")) ) {
            return false;
        }

        return true;
    }

    protected function buildDisabledOverlay() {
        ?>
        <div class="assessment-item-disabled-overlay"></div>
        <?php
    }

    /**
     * Render the section Header
     *
     * @param $component_id
     * @param $visible
     * @param $disabled
     * @param $settings
     */
    protected function renderSectionHeader($component_id, $visible, $disabled, $settings) {
        global $translate;
        $title = isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("Free Text");
        ?>
        <tr class="type">
            <td class="save-control">
                <span data-component-id="<?php echo html_encode($component_id); ?>" class="component-type"><?php echo html_encode($title); ?></span>
                <div class="pull-right">
                    <?php if (!$disabled) : ?>
                        <div class="btn-group">
                            <a href="javascript://" title="<?php echo $translate->_("Save"); ?>" data-method="update-blueprint-free-text-element" class="btn blueprint-component-controls blueprint-component-save-data<?php echo (!$visible || $disabled) ? " hide" : ""; ?>">
                                <?php echo $translate->_("Save"); ?> <i class="icon-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    protected function renderView($options = array()) {
        $component_id       = $options["component_id"];
        $form_blueprint_id  = $options["form_blueprint_id"];
        $disabled           = isset($options["disabled"]) ? $options["disabled"] : false;
        $visible            = isset($options["visible"]) ? $options["visible"] : true;
        $settings           = $options["settings"];
        $render_type        = (isset($settings["editable"]) && $settings["editable"]==1) ? "editable" : "readonly";
        $init_data          = $options["init_data"];
        $element_text       = isset($init_data["element_text"]) ? $init_data["element_text"] : $options["element_text"];

        $display_section_header = isset($options["display_section_header"]) ? $options["display_section_header"] : true;
        ?>
        <div id="blueprint-components-information-error-msg-<?php echo html_encode($component_id); ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="free_text" data-component_id="<?php echo html_encode($component_id); ?>" id="blueprint-free-text-markup-<?php echo html_encode($component_id); ?>" class="blueprint-component-section">
            <?php if (!$visible || $disabled) {
                $this->buildDisabledOverlay();
            } ?>
            <div id="blueprint-component-loading-overlay-<?php echo html_encode($component_id); ?>" class="blueprint-component-loading-overlay" style="display: none;"></div>
            <form id="update-blueprint-free-text-form-<?php echo html_encode($component_id); ?>" class="form-horizontal blueprint-free-text-form">
                <input type="hidden" name="form_blueprint_id" value="<?php echo html_encode($form_blueprint_id); ?>" />
                <table data-component-id="<?php echo html_encode($component_id); ?>" class="blueprint-component-table">
                    <tbody>
                    <?php
                    /**
                     * Display section header
                     */
                    if ($display_section_header) {
                        $this->renderSectionHeader($component_id, $visible, $disabled, $settings);
                    }
                    ?>
                    <tr><td>
                    <?php if ($render_type == "editable") {
                        ?>
                        <textarea id="element-text-<?php echo html_encode($component_id); ?>" name="element_text">
                            <?php echo html_encode($element_text); ?>
                        </textarea>
                        <?php
                    } else {
                        $view_to_render = new Views_Assessments_Forms_Controls_FreeTextLabel(array("mode" => "assessment"));
                        $view_to_render->render($options);
                    } ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

            </form>
        </div>

        <?php
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render Form Free Text element."); ?></strong>
        </div>
        <?php
    }

}