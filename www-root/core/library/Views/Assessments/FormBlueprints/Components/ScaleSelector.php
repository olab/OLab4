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
 * View class for scale selection section.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_ScaleSelector extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("render_types", "component_id", "form_blueprint_id", "init_data", "scale_type", "disabled"))) {
            return false;
        }
        if (!$this->validateArray($options, array("all_scale_types"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        $render_types = $options["render_types"];
        if (!is_array($render_types)) {
            $render_types = array($render_types);
        }

        /**
         * Render the html markup
         */
        if (in_array("markup", $render_types)) {
            $this->renderMarkup($options);
        }

        /**
         * Render the template
         */
        if (in_array("template", $render_types)) {
            $this->renderTemplate($options);
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render scale selector."); ?></strong>
        </div>
        <?php
    }

    protected function buildDisabledOverlay($disabled) {
        ?>
        <div class="assessment-item-disabled-overlay <?php echo ($disabled) ? "" : "hide"; ?>"></div>
        <?php
    }

    /**
     * Render the section Header for the Scale selector
     *
     * @param string $scale_type
     * @param int $component_id
     * @param bool $visible
     * @param bool $disabled
     * @param array $settings
     * @param string $component_heading_override
     */
    protected function renderSectionHeader($scale_type, $component_id, $visible, $disabled, $settings, $component_heading_override = null) {
        global $translate;

        $title = array(
            "ms_ec_scale" => isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("Enabling Competency / Milestone Scale Selector"),
            "entrustment_scale" => isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("Global Rating Scale Selector")
        );
        $component_heading = ($component_heading_override) ? $component_heading_override : sprintf($translate->_("Select the scale to use for the %s"), $title[$scale_type]);
        $mode = Entrada_Utilities::arrayValueOrDefault($settings, "mode", "blueprint");
        ?>
        <tr class="type">
            <td colspan="2" class="save-control">
                <span data-component-id="<?php echo html_encode($component_id) ; ?>" class="component-type"><?php echo html_encode($title[$scale_type]); // specifically change this ?></span>
                <div class="pull-right">
                    <?php if (!$disabled && $mode != "form") : ?>
                        <div class="btn-group">
                            <a href="javascript://" title="<?php echo $translate->_("Save"); ?>" data-method="update-blueprint-scale-selection" class="btn blueprint-component-controls blueprint-component-save-data<?php echo (!$visible || $disabled) ? " hide" : ""; ?>">
                                <?php echo $translate->_("Save"); ?> <i class="icon-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr class="heading">
            <td id="component-heading-1" colspan="2">
                <h3><?php echo html_encode($component_heading) ?></h3>
            </td>
        </tr>
        <?php
    }

    protected function renderFreeTextField($component_id, $value, $disabled, $scale_question_texts = array()) {
        global $translate;
        $disabled_tag = $disabled ? " readonly=\"readonly\"" : "";
        if (!empty($scale_question_texts)): ?>
            <script>
                jQuery(function($){
                    function setScaleQuestionText(scale_id) {
                        var scale_question_text = "";
                        switch (Number(scale_id)) {
                        <?php foreach($scale_question_texts as $scale_id => $scale_question_text): // build the overridden scale selection options ?>
                        <?php if ($scale_id == "default"): ?>

                            default:
                                scale_question_text = "<?php echo html_encode($scale_question_text) ?>";
                                break;

                        <?php else: ?>

                            case <?php echo $scale_id ?>:
                                scale_question_text = "<?php echo html_encode($scale_question_text) ?>";
                                break;

                        <?php endif; ?>
                        <?php endforeach; ?>
                        }
                        jQuery("textarea#item-text-<?php echo $component_id ?>").html(scale_question_text);
                    }
                    $("#rating_scale_<?php echo $component_id ?>").on("change", function(e){
                        e.preventDefault();
                        var scale_id = $(this).val();
                        setScaleQuestionText(scale_id);
                    });
                    if ($("#rating_scale_<?php echo $component_id ?>").val()) {
                        setScaleQuestionText($("#rating_scale_<?php echo $component_id ?>").val());
                    }
                });
            </script>
        <?php endif; ?>
        <div class="control-group">
            <label class="control-label" for="item-text-<?php echo html_encode($component_id); ?>"><?php echo $translate->_("Item Text"); ?></label>
            <div class="controls">
                <textarea id="item-text-<?php echo html_encode($component_id); ?>" name="item_text" class="expandable span12"<?php echo $disabled_tag; ?> style="height: 48px;"><?php echo html_encode($value); ?></textarea>
            </div>
        </div>
        <?php
    }

    protected function renderMarkup($options) {
        global $translate;
        $scales_list = $options["all_scale_types"];
        $init_data = $options["init_data"];
        $scale_type = $options["scale_type"];
        $component_id = $options["component_id"];
        $form_blueprint_id = $options["form_blueprint_id"];
        $disabled = array_key_exists("disabled", $options) ? $options["disabled"] : false;
        $visible = array_key_exists("visible", $options) ? $options["visible"] : true;
        $settings = Entrada_Utilities::arrayValueOrDefault($options, "settings", array());
        $freetext_disabled = $disabled;
        $component_heading_override = false;
        $allow_select_default = true; // allow selecting their own text
        $scale_question_texts = array();
        $mode = "blueprint";

        // If there are any settings, use them!
        foreach ($settings as $setting_type => $setting) {
            switch ($setting_type) {
                case "component_header":
                    // A component heading was specified
                    $component_heading_override = $setting;
                    break;
                case "scale_question_texts":
                    foreach ($setting as $i => $question_text) {
                        $scale_question_texts[$i] = $question_text;
                    }
                    if (!empty($scale_question_texts)) {
                        // We're by default not allowing them to replace the presets, if presets are defined.
                        $freetext_disabled = true;
                    }
                    break;
                case "limit_scale_ids":
                    // If limited scales are specified for this component, remove them from the scale list.
                    $allowed_scales = $setting;
                    foreach ($scales_list as $i => $scale_list) {
                        if (!in_array($scale_list["rating_scale_id"], $allowed_scales)) {
                            unset($scales_list[$i]);
                        }
                    }
                    break;
                case "allow_default_response":
                    $allow_select_default = $setting;
                    break;

                case "mode":
                    $mode = $setting;
                    break;
            }
        }
        $comments_types = array(
            'disabled' => $translate->_("Disabled"),
            'optional' => $translate->_("Optional"),
            'mandatory' => $translate->_("Mandatory"),
            'flagged' => $translate->_("Prompted")
        );
        $display_section_header = isset($options["display_section_header"]) ? $options["display_section_header"] : true;
        ?>
        <div id="blueprint-components-information-error-msg-<?php echo html_encode($component_id); ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="<?php echo html_encode($scale_type);?>" data-component_id="<?php echo html_encode($component_id); ?>" id="blueprint-scale-selector-markup-<?php echo html_encode($component_id); ?>" class="blueprint-component-section">
            <?php $this->buildDisabledOverlay($disabled || !$visible); ?>
            <?php if ($mode != "form") : ?>
            <form id="update-blueprint-scale-selection-form-<?php echo html_encode($component_id); ?>" class="form-horizontal">
            <?php endif; ?>
                <input type="hidden" name="form_blueprint_id" value="<?php echo html_encode($form_blueprint_id); ?>" />
                <input type="hidden" name="scale_type" value="<?php echo html_encode($scale_type); ?>" />
                <table id="table-scale-selector-<?php echo html_encode($component_id); ?>" data-component-id="<?php echo html_encode($component_id); ?>" class="blueprint-component-table">
                    <tbody>
                    <?php
                    /**
                     * Display section header
                     */
                    if ($display_section_header) {
                        $this->renderSectionHeader($scale_type, $component_id, $visible, $disabled, $settings, $component_heading_override);
                    }
                    ?>
                    <tr>
                        <td class="scale-selectior-td" colspan="2">
                            <?php if ($scale_type == "entrustment_scale") {
                                $text_value = isset($init_data["item_text"]) ? $init_data["item_text"] : "";
                                $this->renderFreeTextField($component_id, $text_value, $freetext_disabled, $scale_question_texts);
                            }
                            ?>
                            <div class="control-group">
                                <label class="control-label" for="rating_scale_<?php echo html_encode($component_id); ?>"><?php echo $translate->_("Select Scale"); ?></label>
                                <div class="controls">
                                    <select name="rating_scale" id="rating_scale_<?php echo $component_id; ?>" class="scale-seletor-dropdown">
                                        <option><?php echo $translate->_("-- Select Scale --"); ?></option>
                                        <?php foreach ($scales_list as $scale_item):
                                            $selected_tag = (isset($init_data["selected_scale_id"]) && $init_data["selected_scale_id"]==$scale_item["rating_scale_id"]) ? " selected=\"selected\"" : ""; ?>
                                            <option value="<?php echo html_encode($scale_item["rating_scale_id"]); ?>"<?php echo html_encode($selected_tag); ?>>
                                                <?php echo html_encode($scale_item["rating_scale_title"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label" for="rating_scale_comments_<?php echo html_encode($component_id); ?>"><?php echo $translate->_("Comments"); ?></label>
                                <div class="controls">
                                    <select name="rating_scale_comments" id="rating_scale_comments_<?php echo html_encode($component_id); ?>" class="scale-seletor-comments-dropdown">
                                        <?php foreach ($comments_types as $code => $text):
                                            $selected_tag = (isset($init_data["comment_type"]) && $init_data["comment_type"] == $code) ? " selected=\"selected\"" : "";
                                            ?>
                                            <option value="<?php echo html_encode($code); ?>"<?php echo $selected_tag; ?>>
                                                <?php echo html_encode($text); ?>
                                            </option>
                                        <?php  endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php if ($allow_select_default): ?>
                                <div class="control-group allow-default-response-selector">
                                    <label class="control-label" for="allow_default_<?php echo html_encode($component_id); ?>"><?php echo $translate->_("Allow default response"); ?></label>
                                    <div class="controls">
                                        <input name="allow_default"
                                               type="checkbox"
                                               id="allow_default_<?php echo html_encode($component_id); ?>"
                                               class="allow_default_checkbox"
                                               value="1"
                                            <?php echo (isset($init_data["allow_default"]) && $init_data["allow_default"]) ? " checked=\"checked\"" : ""; ?>
                                        />
                                    </div>
                                </div>
                            <?php endif;
                            if ($allow_select_default == false) {
                                $default_column_class = " hide";
                            } else {
                                $default_column_class = (isset($init_data["allow_default"]) && $init_data["allow_default"]) ? "" : " hide";
                            }
                            ?>
                            <div class="control-group">
                                <label class="control-label" for=""><?php echo $translate->_("Responses"); ?></label>
                                <div class="controls" id="flag-response-controls-<?php echo html_encode($component_id); ?>">
                                    <table id="response-table-<?php echo $component_id; ?>" class="<?php echo isset($init_data["scale_items"]) ? "" : " hide"; ?>">
                                        <tr>
                                            <th>&nbsp;</th>
                                            <th><?php echo $translate->_("Flag"); ?></th>
                                            <th class="td_default_selection_<?php echo $component_id;?><?php echo $default_column_class; ?>"><?php echo $translate->_("Default"); ?></th>
                                        </tr>
                                        <?php if (isset($init_data["scale_items"])) :
                                            $count = 0;
                                            foreach ($init_data["scale_items"]["descriptors"]  as $descriptors) :
                                                $count++;
                                                $flagged_tag = (in_array($descriptors["ardescriptor_id"], $init_data["flagged_response_descriptors"])) ? " checked=\"checked\"" : "";
                                                $default_tag = ($descriptors["ardescriptor_id"] == Entrada_Utilities::arrayValueOrDefault($init_data, "default_response")) ? " checked=\"checked\"" : "";
                                                ?>
                                                <tr>
                                                    <td>
                                                        <label for="scale-selector-flag-<?php echo $component_id; ?>-<?php echo $descriptors["ardescriptor_id"]; ?>">
                                                            <?php echo html_encode($descriptors["descriptor"]); ?>
                                                        </label></td>
                                                    <td>
                                                        <input name="scale_reponse_flag[]"
                                                               type="checkbox"
                                                               id="scale-selector-flag-<?php echo $component_id; ?>-<?php echo $descriptors["ardescriptor_id"]; ?>"
                                                               value="<?php echo $descriptors["ardescriptor_id"]; ?>"
                                                               class="text-center"<?php echo $flagged_tag; ?> />
                                                    </td>
                                                    <td class="td_default_selection_<?php echo $component_id;?><?php echo $default_column_class; ?>">
                                                        <input name="scale_default_response"
                                                               type="radio"
                                                               id="scale-selector-default-<?php echo $component_id; ?>-<?php echo $descriptors["ardescriptor_id"]; ?>"
                                                               value="<?php echo $descriptors["ardescriptor_id"]; ?>"
                                                               class="text-center"<?php echo $default_tag; ?> />
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <span class="no-response-flags"><?php echo $translate->_("Select a scale to enable response flags.") ?></span>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            <?php if ($mode != "form") : ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function renderTemplate($options) {
        global $translate;
        $component_id = $options["component_id"];
        ?>
        <script type="text/html" id="scale-selector-repsonse-row-template">
            <td><label data-template-bind='[{"attribute": "for", "value":"tpl_scale_selector_flag_id"}]' data-content="tpl_response_descriptor_text"></label></td>
            <td>
                <input data-template-bind='[{"attribute": "id", "value":"tpl_scale_selector_flag_id"}]'
                       name="scale_reponse_flag[]"
                       type="checkbox"
                       class="text-center" />
            </td>
            <td class="td_default_selection_<?php echo $component_id;?> hide">
                <input data-template-bind='[{"attribute": "id", "value":"tpl_scale_selector_default_response_id"}]'
                       name="scale_default_response"
                       type="radio"
                       class="text-center" />
            </td>
        </script>
        <?php
    }
}