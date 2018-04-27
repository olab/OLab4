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

class Views_Assessments_FormBlueprints_Components_RoleSelector extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("component_id", "form_blueprint_id", "settings", "init_data", "standard_roles")) ) {
            return false;
        }

        if (!$this->validateArray($options, array("component_scale"))) {
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
        $title = isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("Roles Selection");
        ?>
        <tr class="type">
            <td class="save-control">
                <span data-component-id="<?php echo html_encode($component_id); ?>" class="component-type"><?php echo html_encode($title); ?></span>
                <div class="pull-right">
                    <?php if (!$disabled) : ?>
                        <div class="btn-group">
                            <a href="javascript://" title="<?php echo $translate->_("Save"); ?>" data-method="update-blueprint-roles-selector-element" class="btn blueprint-component-controls blueprint-component-save-data<?php echo (!$visible || $disabled) ? " hide" : ""; ?>">
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
        $this->renderMarkup($options);
    }

    private function renderMarkup($options) {
        $component_id       = $options["component_id"];
        $form_blueprint_id  = $options["form_blueprint_id"];
        $settings           = Entrada_Utilities::arrayValueOrDefault($options, "settings", array());
        $disabled           = Entrada_Utilities::arrayValueOrDefault($options, "disabled", false);
        $visible            = Entrada_Utilities::arrayValueOrDefault($options, "visible", true);
        $locked             = Entrada_Utilities::multidimensionalArrayValue($options, false, "settings", "locked");
        $standard_roles     = Entrada_Utilities::multidimensionalArrayValue($options, array(), "settings", "roles_list");
        $init_data          = Entrada_Utilities::arrayValueOrDefault($options, "init_data", array());
        $scale              = Entrada_Utilities::arrayValueOrDefault($options, "component_scale", array());
        if ($locked) {
            $disabled = true;
        }
        $display_section_header = Entrada_Utilities::arrayValueOrDefault($options, "display_section_header", true);
        ?>
        <div id="blueprint-components-information-error-msg-<?php echo html_encode($component_id); ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="roles_selector" data-component_id="<?php echo html_encode($component_id); ?>" id="blueprint-roles-selector-markup-<?php echo html_encode($component_id); ?>" class="blueprint-component-section">
            <?php if (!$visible || $disabled) {
                $this->buildDisabledOverlay();
            } ?>
            <div id="blueprint-component-loading-overlay-<?php echo html_encode($component_id); ?>" class="blueprint-component-loading-overlay" style="display: none;"></div>
            <form id="update-blueprint-roles-selector-form-<?php echo html_encode($component_id); ?>" class="form-horizontal blueprint-roles-selector-form">
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
                            <table class="table table-bordered table-striped rubric-table ui-sortable space-above">
                                <tr>
                                    <th>&nbsp;</th>
                                    <?php
                                    if (!empty($scale)) {
                                        foreach ($scale["descriptors"] as $descriptor) {
                                            echo "<th>{$descriptor["descriptor"]}</th>";
                                        }
                                    }
                                    ?>
                                </tr>
                            <?php

                            foreach ($standard_roles as $objective_id => $role) {
                                if (isset($settings["locked"]) && intval($settings["locked"])) {
                                    $checked_tag = " checked=\"checked\"";
                                    $var_disabled_tag = " disabled=\"disabled\"";
                                } else {
                                    $checked_tag = in_array($objective_id, $init_data) ? " checked=\"checked\"" : "";
                                    $var_disabled_tag = "";
                                }
                                ?>
                                <tr>
                                    <td>
                                        <label style="display: inline-block;" class="checkbox" for="role-<?php echo html_encode($component_id);?>-<?php echo $objective_id; ?>">
                                            <input type="checkbox"
                                                   name="roles_<?php echo html_encode($component_id);?>[]"
                                                   id="role-<?php echo html_encode($component_id);?>-<?php echo html_encode($objective_id); ?>"
                                                   class="roles_checkbox"
                                                   value="<?php echo html_encode($objective_id); ?>" <?php echo $checked_tag . $var_disabled_tag; ?> />
                                            <?php if ($var_disabled_tag != "" && !$disabled ) { ?>
                                                <input type="hidden"
                                                       name="roles_<?php echo html_encode($component_id);?>[]"
                                                       value="<?php echo html_encode($objective_id); ?>" />
                                            <?php } ?>
                                            <span><b><?php echo html_encode($role["objective_code"]); ?>&nbsp;</b><?php echo html_encode($role["objective_name"]); ?></span>
                                        </label>
                                    </td>
                                    <?php
                                    $row = 0;
                                    foreach ($role["responses"] as $response) {
                                        if ($row) {
                                            echo "</tr><tr>";
                                            echo "<td>&nbsp;</td>";
                                        }
                                        $row++;
                                        $ordinal = 0;
                                        foreach ($response["responses_text"] as $response_text) {
                                            $ordinal++;
                                            $checked_tag = isset($settings["default_response"]) && $settings["default_response"] == $ordinal ? " checked=\"checked\"" : "";
                                            echo "<td><input type=\"radio\" class=\"item-control\" disabled='disabled'{$checked_tag}><label class=\"rubric-response-text\">" . nl2br($response_text) . "</label></td>";
                                        }

                                    } ?>
                                </tr>
                            <?php } ?>
                            </table>
                        </td></tr>
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
            <strong><?php echo $translate->_("Unable to render Roles selector element."); ?></strong>
        </div>
        <?php
    }

}