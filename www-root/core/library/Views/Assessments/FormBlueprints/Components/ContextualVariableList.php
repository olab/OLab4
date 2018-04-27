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
 * View class for Contextual Variable selector list.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_ContextualVariableList extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("render_types", "component_id", "form_blueprint_id", "init_data", "disabled"))) {
            return false;
        }
        if (!$this->validateArray($options, array("epas", "contextual_variables", "contextual_vars_desc"))) {
            return false;
        }

        return true;
    }

    /**
     * Call the appropriate rendering functions based on the mode
     *  EPA => Contextual variables will be lonked to EPA objective(s)
     *  Standalone => Contextual variables not linked to any objectives.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        $render_types = $options["render_types"];
        $component_id = $options["component_id"];

        // If standalone is true, the component doesn't rely on EPA(s) being selected in the first place;
        $settings = is_array($options["settings"]) ? $options["settings"] : array();
        $mode = isset($settings["mode"]) ? $settings["mode"] : "epa";

        if (!is_array($render_types)) {
            $render_types = array($render_types);
        }

        /**
         * Render the html markup
         */
        if (in_array("markup", $render_types)) {
            switch($mode) {
                case "standalone":
                    $this->renderStandaloneMarkup($options);
                    break;

                default:
                    $this->renderEPAMarkup($options);
                    break;
            }
        }

        /**
         * Render the template if required
         */
        if (in_array("template", $render_types)) {
            switch ($mode) {
                case "standalone":
                    // No template needed for standalone
                    break;
                default:
                    $this->renderEPATemplate($component_id);
            }
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div data-component-type="contextual_variable_list" data-component_id="z" id="blueprint-contextual-vars-selector-markup-z" class="blueprint-component-section space-below">
            <table data-component-id="z" id="table-contextual-vars-z" class="blueprint-component-table">
                <tbody>
                    <tr class="type">
                        <td class="save-control">
                            <span data-component-id="z" class="component-type"><?php echo $translate->_("Contextual Variables"); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td id="component-heading-z">
                            <h3 class="padding-top padding-bottom medium">
                                <?php echo $translate->_("Contextual variables are not configured. Contextual variables must be defined in order to use this feature."); ?>
                            </h3>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    protected function buildDisabledOverlay() {
        ?>
        <div class="assessment-item-disabled-overlay"></div>
        <?php
    }

    /**
     * Render the component table header
     *
     * @param string $mode
     */
    protected function buildRowHeader($mode = "epa") {
        global $translate;

        switch($mode) {
            case "standalone":
                $tdwidth = "100%";
                break;

            default:
            case "epa":
                $tdwidth = "35%";
                break;
        }
        ?>
        <tr class="heading">
            <th width="<?php echo $tdwidth; ?>"><?php echo $translate->_("Contextual Variables"); ?></th>
            <?php if ($mode=="epa") { ?>
            <th><?php echo $translate->_("EPAs"); ?></th>
            <?php } ?>
        </tr>
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
        $title = isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("Contextual Variables");
        ?>
        <tr class="type">
            <td colspan="2" class="save-control">
                <span data-component-id="<?php echo $component_id; ?>" class="component-type"><?php echo html_encode($title); ?></span>
                <div class="pull-right">
                    <?php if (!$disabled) : ?>
                        <div class="btn-group">
                            <a href="javascript://" title="<?php echo $translate->_("Save"); ?>" data-method="update-blueprint-contextual-vars-selection" class="btn blueprint-component-controls blueprint-component-save-data<?php echo (!$visible || $disabled) ? " hide" : ""; ?>">
                                <?php echo $translate->_("Save"); ?> <i class="icon-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr class="heading">
            <td id="component-heading-<?php echo $component_id; ?>" colspan="2">
                <h3><?php echo $translate->_("Select the contextual variables for this form."); ?></h3>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the EPA linked version of the component
     *
     * @param $options
     */
    private function renderEPAMarkup($options) {
        global $translate;

        $epas_desc = $options["epas_desc"];
        $contextual_vars_desc = $options["contextual_vars_desc"];
        $contextual_variables = $options["contextual_variables"];
        $component_id = $options["component_id"];
        $form_blueprint_id = $options["form_blueprint_id"];
        $disabled = isset($options["disabled"]) ? $options["disabled"] : false;
        $visible = isset($options["visible"]) ? $options["visible"] : true;
        $display_section_header = isset($options["display_section_header"]) ? $options["display_section_header"] : true;
        $init_data = is_array($options["init_data"]) ? $options["init_data"] : array();
        $settings = is_array($options["settings"]) ? $options["settings"] : array();
        $mode = "epa";
        $display_heading = in_array("display_heading", $settings) ? $settings["display_heading"] : true;

        if (count($init_data)) {
            $vars_set = array();
            foreach ($init_data as $epa => $cv_list) {
                $cv_arr = array();
                foreach ($cv_list as $cv => $responses) {
                    $cv_arr[] = $cv.",".implode(",", $responses);
                }
                $vars_set[implode(",", $cv_arr)][] = $epa;
            }
        }

        ?>
        <div id="blueprint-components-information-error-msg-<?php echo $component_id; ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="contextual_variable_list" data-component_id="<?php echo $component_id; ?>" id="blueprint-contextual-vars-selector-markup-<?php echo $component_id; ?>" class="blueprint-component-section">
            <?php if (!$visible || $disabled) {
                $this->buildDisabledOverlay();
            } ?>
            <form id="update-blueprint-contextual-vars-selection-form-<?php echo $component_id; ?>">
                <input type="hidden" name="form_blueprint_id" value="<?php echo $form_blueprint_id; ?>" />
                <input id="input-hidden-eepa" type="hidden" name="mode" value="epa" />
                <table data-component-id="<?php echo $component_id; ?>" id="table-contextual-vars-<?php echo $component_id; ?>" class="blueprint-component-table">
                    <tbody>
                    <?php
                    /**
                     * Display section header
                     */
                    if ($display_section_header) {
                        $this->renderSectionHeader($component_id, $visible, $disabled, $settings);
                    }

                    $set_no = 0;

                    if (isset($vars_set) && count($vars_set)) {
                        foreach ($vars_set as $variables => $var_epas) {
                            $set_no++;

                            if (isset($init_data) && is_array($init_data)) {
                                $is_selected = false;
                                foreach ($var_epas as $epa) {
                                    if (array_key_exists($epa, $init_data)) {
                                        $is_selected = true;
                                        break;
                                    }
                                }
                                if (!$is_selected) {
                                    continue;
                                }
                            }
                            $this->buildRowHeader($mode);
                            ?>
                            <tr class="contextual-vars-selector-row" data-set="<?php echo html_encode($set_no); ?>" id="<?php echo "contextual-vars-selector-row-{$component_id}-{$set_no}"; ?>">
                                <td class="contextual-vars-list-td">
                                    <input id="<?php echo "input-hidden-set-no-{$component_id}-{$set_no}"; ?>"
                                           class="input-hidden-set-no"
                                           type="hidden"
                                           name="<?php echo "set_no_{$component_id}[]"; ?>"
                                           value="<?php echo $set_no; ?>" />
                                    <?php
                                    foreach ($contextual_vars_desc as $variable):
                                        if (in_array($variable["objective_code"], $settings["required_types"])) {
                                            $checked_tag = " checked=\"checked\"";
                                            $var_disabled_tag = " disabled=\"disabled\"";
                                        } else {
                                            $checked_tag = (isset($init_data) && is_array($init_data) && array_key_exists($variable["objective_id"], $init_data[$var_epas[0]]))
                                                ? " checked=\"checked\""
                                                : "";
                                            $var_disabled_tag = $disabled ? " disabled=\"disabled\"" : "";
                                        }
                                        $disabled_tag = $disabled ? " disabled=\"disabled\"" : "";
                                        $badge_tag = $checked_tag=="" ? " hide" : "";
                                        ?>
                                        <div>
                                            <div class="checkbox checkbox-div-label">
                                                <input type="checkbox"
                                                    name="<?php echo "contextual_vars_{$component_id}_{$set_no}[]"; ?>"
                                                    id="<?php echo "contextual-var-{$component_id}-{$set_no}-{$variable["objective_id"]}"; ?>"
                                                    class="cvars_checkbox"
                                                    value="<?php echo $variable["objective_id"]; ?>"
                                                    <?php echo $checked_tag ?>
                                                    <?php echo $var_disabled_tag; ?>
                                                />
                                                <?php if ($var_disabled_tag != "" && !$disabled ) { ?>
                                                    <input type="hidden"
                                                           name="contextual_vars_<?php echo $component_id;?>_<?php echo html_encode($set_no); ?>[]"
                                                           value="<?php echo html_encode($variable["objective_id"]); ?>" />
                                                <?php } ?>
                                                <span><?php echo html_encode($variable["objective_name"]); ?></span>
                                            </div>
                                            <span id="contextual-vars-selected-responses-<?php echo $set_no."-".$variable["objective_id"]; ?>"
                                                  class="selected-responses-badge badge<?php echo $badge_tag; ?>"
                                                  style="cursor: pointer">
                                                  <?php echo isset($init_data[$var_epas[0]][$variable["objective_id"]]) ?
                                                        count($init_data[$var_epas[0]][$variable["objective_id"]]) : 0; ?>/<?php echo $variable["responses_count"]; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php foreach ($init_data[$var_epas[0]] as $var_id => $responses):
                                        foreach ($responses as $response): ?>
                                            <input type="hidden"
                                                   name="cvariable_responses_<?php echo "{$component_id}_{$set_no}_{$var_id}"; ?>[]"
                                                   value="<?php echo $response; ?>"/>
                                        <?php endforeach;
                                    endforeach; ?>

                                    <?php foreach ($contextual_vars_desc as $var_id => $variable):
                                        if (in_array($variable["objective_code"], $settings["required_types"]) && !array_key_exists($var_id, $init_data[$var_epas[0]])):
                                            foreach ($contextual_variables[0]['responses'][$var_id] as $response): ?>
                                                <input type="hidden"
                                                       name="cvariable_responses_<?php echo "{$component_id}_{$set_no}_{$var_id}"; ?>[]"
                                                       value="<?php echo $response; ?>"/>
                                            <?php endforeach; ?>
                                        <?php endif;
                                    endforeach; ?>
                                </td>
                                <td class="epa-list-td">
                                    <?php foreach ($var_epas as $epa): ?>
                                        <div>
                                            <label class="checkbox epa-tooltip"
                                                   data-toggle="tooltip"
                                                   title="<?php echo html_encode($epas_desc[$epa]["objective_code"] . ": " . $epas_desc[$epa]["objective_name"]); ?>"
                                                   for="contextual-epa-<?php echo $component_id;?>-<?php echo html_encode($epa) ?>">
                                                <input type="checkbox"
                                                       name="<?php echo "contextual_vars_epa_{$component_id}_{$set_no}[]" ?>"
                                                       id="contextual-epa-<?php echo $component_id;?>-<?php echo html_encode($epa) ?>"
                                                       class="cvars_epas_checkbox"
                                                       value="<?php echo html_encode($epa) ?>" checked="checked"<?php echo $disabled_tag; ?>>
                                                <span class="contextual-variable-epa"><?php echo html_encode("{$epas_desc[$epa]["objective_code"]}: {$epas_desc[$epa]["objective_name"]}"); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php
                        }
                    } else { ?>
                        <tr>
                            <td colspan="2" align="center" class="no-contextual-variables"><?php echo $translate->_("No contextual variables, make sure you select at least one EPA for that form."); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * Render the jQuery template for the EPA linked version of the component.
     *
     * @param $component_id
     */
    private function renderEPATemplate($component_id) {
        global $translate;
        ?>
        <script type="text/html" id="contextual-vars-selector-header-row-template">
            <th width="35%"><?php echo $translate->_("Contextual Variables"); ?></th>
            <th><?php echo $translate->_("EPAs"); ?></th>
        </script>

        <script type="text/html" id="contextual-vars-selector-row-template">
            <td data-template-bind='[{"attribute": "id", "value":"tpl_cvars_selector_tr_vars_td_id"}]' class="contextual-vars-list-td">
                <input data-template-bind='[{"attribute": "id", "value":"tpl_cvars_selector_set_no"}]' type="hidden" class="input-hidden-set-no" name="set_no_<?php echo $component_id; ?>[]" value="" />
            </td>
            <td data-template-bind='[{"attribute": "id", "value":"tpl_cvars_selector_tr_epas_td_id"}]' class="epa-list-td"></td>
        </script>

        <script type="text/html" id="contextual-vars-selector-template">
            <div class="checkbox checkbox-div-label">
                <input data-value="tpl_cvars_checkbox_value" data-template-bind='[
                    {"attribute": "id", "value":"tpl_cvars_selector_element_id"},
                    {"attribute": "name", "value":"tpl_cvars_selector_element_name"}
                  ]' type="checkbox" class="cvars_checkbox" />
                <span data-content="tpl_cvars_label_content"></span>
            </div>
            <span data-template-bind='[{"attribute": "id", "value":"tpl_cvars_selected_responses_id"}]'
                  data-content="tpl_cvars_selected_responses_content"
                  class="selected-responses-badge badge hide" style="cursor: pointer"></span>
        </script>

        <script type="text/html" id="contextual-vars-epas-selector-template">
            <label class="checkbox epa-tooltip" data-template-bind='[{"attribute": "for", "value":"tpl_cvars_epas_selector_element_id"}, {"attribute": "title", "value": "tpl_cvars_epas_label_content"}]'">
            <input data-value="tpl_cvars_epas_checkbox_value" data-template-bind='[
                    {"attribute": "id", "value":"tpl_cvars_epas_selector_element_id"},
                    {"attribute": "name", "value":"tpl_cvars_epas_selector_element_name"}
                  ]' type="checkbox" checked="checked" class="cvars_epas_checkbox">
            <span data-content="tpl_cvars_epas_label_content" class="contextual-variable-epa"></span>
            </label>
        </script>
        <?php
    }

    /**
     * Render the markup for the Standalone version of the component
     *
     * @param $options
     */
    private function renderStandaloneMarkup($options) {
        $contextual_vars_desc = $options["contextual_vars_desc"];
        $contextual_variables = $options["contextual_variables"];
        $component_id = $options["component_id"];
        $form_blueprint_id = $options["form_blueprint_id"];
        $disabled = isset($options["disabled"]) ? $options["disabled"] : false;
        $visible = isset($options["visible"]) ? $options["visible"] : true;
        $display_section_header = isset($options["display_section_header"]) ? $options["display_section_header"] : true;
        $init_data = is_array($options["init_data"]) ? $options["init_data"] : array();
        $settings = is_array($options["settings"]) ? $options["settings"] : array();
        $mode = "standalone";
        $display_heading = array_key_exists("display_heading", $settings) ? $settings["display_heading"] : true;

        ?>
        <div id="blueprint-components-information-error-msg-<?php echo $component_id; ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="contextual_variable_list_standalone" data-component_id="<?php echo $component_id; ?>" id="blueprint-contextual-vars-selector-standalone-markup-<?php echo $component_id; ?>" class="blueprint-component-section">
            <?php if (!$visible || $disabled) {
                $this->buildDisabledOverlay();
            } ?>
            <form id="update-blueprint-contextual-vars-selection-form-<?php echo $component_id; ?>">
                <input type="hidden" name="form_blueprint_id" value="<?php echo html_encode($form_blueprint_id); ?>" />
                <table data-component-id="<?php echo $component_id; ?>" id="table-contextual-vars-<?php echo $component_id; ?>" class="blueprint-component-table">
                    <tbody>
                    <?php
                    /**
                     * Display section header
                     */
                    if ($display_section_header) {
                        $this->renderSectionHeader($component_id, $visible, $disabled, $settings);
                    }

                    $set_no = 1;

                    if ($display_heading) {
                        $this->buildRowHeader($mode);
                    }
                    ?>
                    <tr class="contextual-vars-selector-row" data-set="<?php echo $set_no; ?>" id="contextual-vars-selector-row-<?php echo $component_id;?>">
                        <td class="contextual-vars-list-td">
                            <input id="input-hidden-set-no-<?php echo $component_id; ?>-<?php echo $set_no; ?>"
                                   class="input-hidden-set-no"
                                   type="hidden"
                                   name="set_no_<?php echo $component_id; ?>[]"
                                   value="<?php echo html_encode($set_no); ?>" />
                            <input id="input-hidden-standalone" type="hidden" name="mode" value="standalone" />
                            <?php
                            foreach ($contextual_vars_desc as $variable):
                                if (in_array($variable["objective_code"], $settings["required_types"])) {
                                    $checked_tag = " checked=\"checked\"";
                                    $var_disabled_tag = " disabled=\"disabled\"";

                                    if (in_array($variable["objective_code"], $settings["required_types"]) && !array_key_exists($variable["objective_id"], $init_data)) {
                                        foreach ($contextual_variables[0]['responses'][$variable["objective_id"]] as $response) {
                                            $init_data[$variable["objective_id"]][] = $response;
                                        }
                                    }
                                } else {
                                    $checked_tag = (isset($init_data) && is_array($init_data) && array_key_exists($variable["objective_id"], $init_data))
                                        ? " checked=\"checked\""
                                        : "";
                                    $var_disabled_tag = $disabled ? " disabled=\"disabled\"" : "";
                                }
                                $disabled_tag = $disabled ? " disabled=\"disabled\"" : "";
                                $badge_tag = $checked_tag=="" ? " hide" : "";
                                ?>
                                <div>
                                    <label style="display: inline-block;" class="checkbox" for="contextual-var-<?php echo $component_id;?>-<?php echo html_encode($set_no); ?>-<?php echo $variable["objective_id"]; ?>">
                                        <input type="checkbox"
                                               name="contextual_vars_<?php echo $component_id;?>_<?php echo $set_no; ?>[]"
                                               id="contextual-var-<?php echo $component_id;?>-<?php echo $set_no; ?>-<?php echo $variable["objective_id"]; ?>"
                                               class="cvars_checkbox"
                                               value="<?php echo $variable["objective_id"]; ?>" <?php echo $checked_tag . $var_disabled_tag; ?>
                                        />
                                        <?php if ($var_disabled_tag != "" && !$disabled ) : ?>
                                            <input type="hidden"
                                                   name="contextual_vars_<?php echo $component_id;?>_<?php echo $set_no; ?>[]"
                                                   value="<?php echo $variable["objective_id"]; ?>" />
                                        <?php endif; ?>
                                        <span><?php echo html_encode($variable["objective_name"]); ?></span>
                                    </label>
                                    <span id="contextual-vars-selected-responses-<?php echo "{$set_no}-{$variable["objective_id"]}"; ?>"
                                        class="selected-responses-badge badge<?php echo $badge_tag; ?>"
                                        style="cursor: pointer">
                                        <?php echo isset($init_data[$variable["objective_id"]]) ?
                                              count($init_data[$variable["objective_id"]]) : 0; ?>/<?php echo $variable["responses_count"]; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($init_data as $var_id => $responses):
                                foreach ($responses as $response): ?>
                                    <input type="hidden"
                                           name="<?php echo "cvariable_responses_{$component_id}_{$set_no}_{$var_id}[]"; ?>"
                                           value="<?php echo $response; ?>"
                                    />
                                <?php endforeach;
                            endforeach; ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }
}