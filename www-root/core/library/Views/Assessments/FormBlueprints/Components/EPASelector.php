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
 * View class for blueprint EPA selector section.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_EPASelector extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("epas", "render_types", "component_id", "course_id", "form_blueprint_id", "disabled"))) {
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

    protected function buildDisabledOverlay() {
        ?>
        <div class="assessment-item-disabled-overlay"></div>
        <?php
    }

    /**
     * Render the section Header for the EPA selector
     *
     * @param $component_id
     * @param $visible
     * @param $disabled
     * @param $settings
     */
    protected function renderSectionHeader($component_id, $visible, $disabled, $settings) {
        global $translate;
        $title = isset($settings["component_title"]) ? $settings["component_title"] : $translate->_("EPA Selection");
        ?>
        <tr class="type">
            <td class="save-control">
                <span data-component-id="<?php echo $component_id; ?>" class="component-type"><?php echo $title; ?></span>
                <div class="pull-right">
                    <?php if (!$disabled) : ?>
                    <div class="btn-group">
                        <a href="javascript://" title="<?php echo $translate->_("Save"); ?>" data-method="update-blueprint-epa-selection" class="btn blueprint-component-controls blueprint-component-save-data<?php echo (!$visible || $disabled) ? " hide" : ""; ?>">
                            <?php echo $translate->_("Save"); ?> <i class="icon-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr class="heading">
            <td id="component-heading-1" colspan="4">
                <h3><?php echo $translate->_("Select the EPA(s) for which this form can be used to assess."); ?></h3>
            </td>
        </tr>
        <?php /*
        <?php if (!$disabled): ?>
        <tr>
            <td class="blueprint-component-epa-checks">
                <a href="javascript://" id="epa_check_all">Check All</a>&nbsp;|&nbsp;<a href="javascript://" id="epa_uncheck_all">Uncheck All</a>
            </td>
        </tr>
        <?php endif; ?>
        */ ?>
        <?php
    }

    /**
     * Render the html markup for the EPA selector
     *
     * @param $options
     */
    private function renderMarkup($options) {
        global $translate;
        $epas                       = is_array($options["epas"]) ? $options["epas"] : array();
        $epas_desc                  = $options["epas_desc"];
        $component_id               = $options["component_id"];
        $form_blueprint_id          = $options["form_blueprint_id"];
        $course_id                  = $options["course_id"];
        $disabled                   = array_key_exists("disabled", $options) ? $options["disabled"] : false;
        $visible                    = array_key_exists("visible", $options) ? $options["visible"] : true;
        $selected_epas              = array_key_exists("init_data", $options) && is_array($options["init_data"]) ? $options["init_data"] : array();
        $settings                   = array_key_exists("settings", $options) && is_array($options["settings"]) ?  $options["settings"] : array();
        $display_section_header     = array_key_exists("display_section_header", $options) ? $options["display_section_header"] : true;
        $allow_milestone_selection  = array_key_exists("allow_milestones_selection", $settings) ? $settings["allow_milestones_selection"] : true;
        ?>
        <div id="blueprint-components-information-error-msg-<?php echo $component_id; ?>" class="blueprint-components-information-error-msg"></div>
        <div data-component-type="epa_selector" data-component_id="<?php echo $component_id; ?>" id="blueprint-epa-selector-markup-<?php echo $component_id; ?>" class="blueprint-component-section">
            <?php if (!$visible || $disabled): ?>
                <?php $this->buildDisabledOverlay(); ?>
            <?php endif; ?>
            <div id="blueprint-component-loading-overlay-<?php echo $component_id; ?>" class="blueprint-component-loading-overlay" style="display: none;"></div>
            <form id="update-blueprint-epa-selection-form-<?php echo $component_id; ?>" class="form-horizontal blueprint-epa-selection-form">
                <input type="hidden" id="epa-selection-form-<?php echo $component_id; ?>-course-id" name="course_id" value="<?php echo $course_id;?>" />
                <input type="hidden" name="form_blueprint_id" value="<?php echo $form_blueprint_id; ?>" />
                <input type="hidden"
                       id="max-milestones-<?php echo $component_id; ?>"
                       value="<?php echo isset($settings["max_milestones"]) ? (int)$settings["max_milestones"] : 0 ?>" />
                <input type="hidden"
                       id="allow-milestones-selection-<?php echo $component_id; ?>"
                       value="<?php echo isset($settings["allow_milestones_selection"]) ? (int)$settings["allow_milestones_selection"] : 0 ?>" />

                <?php if (empty($epas)) {
                    echo display_error(sprintf($translate->_("EPAs are not defined. In order to use this feature, EPAs must be mapped for this course.")));
                } ?>
                <table data-component-id="<?php echo $component_id; ?>" class="blueprint-component-table">
                    <tbody>
                        <?php
                        /**
                         * Display section header
                         */
                        if ($display_section_header) {
                            $this->renderSectionHeader($component_id, $visible, $disabled, $settings);
                        }
                        ?>
                        <tr>
                            <td class="epa-list-td">
                                <?php echo $this->renderAdvancedSearch($epas, $selected_epas, $options["entrada_url"], $component_id, $settings) ?>
                                <div id="epa-select-container">
                                    <div class="control-group">
                                        <label for="select-eap-btn" class="control-label"><?php echo $translate->_("Select Course EPAs") ?></label>
                                        <div class="controls">
                                            <button id="select-eap-btn" class="btn btn-default"><?php echo $translate->_("Click here to select EPAs") ?> <i class="icon-chevron-down"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <?php /*
                                <!--<?php foreach ($epas as $epa):
                                    if ($selected_epas === null) {
                                        $checked_tag = " checked=\"checked\"";
                                    } else {
                                        $checked_tag = in_array($epa["objective_id"],$selected_epas) ? " checked=\"checked\"" : "";
                                    }
                                    $disabled_tag = $disabled ? " disabled=\"disabled\"" : "";
                                    ?>
                                    <input type="checkbox"
                                           name="selected_epa[]"
                                           id="selected_epa_<?php echo $epa["objective_id"] ?>"
                                           value="<?php echo $epa["objective_id"] ?>"
                                        <?php echo $checked_tag . $disabled_tag; ?>>

                                    <label for="selected_epa_<?php echo $epa["objective_id"] ?>"><?php echo $epa["objective_code"];?>: <?php echo $epa["objective_name"];?></label>
                                    <br />
                                <?php endforeach; ?>
                                -->*/ ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php
                if ($epas) {
                    foreach ($epas as $epa) {
                        if (array_key_exists($epa["objective_id"], $selected_epas)) {
                            $epa_count = count($selected_epas[$epa["objective_id"]]);
                            $milestone_selection_badge = $allow_milestone_selection
                                ? "<a class='selected-milestones-badge badge' id='epa-selected-milestones-{$component_id}-{$epa["objective_id"]}'>{$epa_count}/{$epas_desc[$epa["objective_id"]]["milestones_count"]}</a>"
                                : "";
                            ?>
                            <input type="hidden" value="<?php echo $epa["objective_id"]?>"
                                   id="<?php echo "selected_epa_{$epa["objective_id"]}" ?>"
                                   class="search-target-control selected_epa_search_target_control"
                                   name="selected_epa[]"
                                   data-label="<?php echo "{$epa["objective_code"]}: {$epa["objective_name"]} $milestone_selection_badge"; ?>"
                            />
                            <?php
                            foreach ($selected_epas[$epa["objective_id"]] as $milestone): ?>
                                <input type="hidden" name="<?php echo "milestones_{$component_id}_{$epa["objective_id"]}[]" ?>" value="<?php echo $milestone ?>">
                            <?php endforeach;
                        }
                    }
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the template used with javascript
     *
     * @param $options
     */
    private function renderTemplate($options) {
        ?>
        <script type="text/html" id="epa-selector-template">
            <input data-template-bind='[{"attribute": "id", "value":"tpl_epa_selector_element_id"}]' type="checkbox" name="selected_epa[]">
            <label data-template-bind='[{"attribute": "for", "value":"tpl_epa_selector_element_id"}]'>

            </label>
            <br />
        </script>
        <?php
    }

    protected function renderAdvancedSearch($epas, $selected_eaps, $entrada_url, $component_id, $settings) {
        global $translate;
        $epa_datasource = array();
        $selected_epas_flag = 0;

        if ($epas) {
            foreach ($epas as $epa) {
                $epa_datasource[] = array("target_id" => $epa["objective_id"], "target_label" => $epa["objective_code"] . ":" . " " . $epa["objective_name"] );
            }
        }

        if ($selected_eaps) {
            $selected_epas_flag = 1;
        }
        ?>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                var selected_epas_flag = <?php echo $selected_epas_flag  ?>;
                var epa_datasource = <?php echo json_encode($epa_datasource) ?>;
                var component_id = <?php echo $component_id; ?>;
                var allow_milestones_selection = <?php echo (isset($settings["allow_milestones_selection"])) ? intval($settings["allow_milestones_selection"]) : 1; ?>;
                var max_milestones = <?php echo $settings["max_milestones"]; ?>;

                /**
                 * If no EPAs have been selected when the instance of advancedSearch loads,
                 * then select all EPAs by default.
                 */
                if (!selected_epas_flag) {
                    $.each(epa_datasource, function (i, epa) {
                        if ($("#selected_epa_" + epa.target_id).length == 0) {
                            var input = $(document.createElement("input")).attr({type: "hidden", value: epa.target_id, id: "selected_epa_" + epa.target_id, name: "selected_epa[]", "data-label": epa.target_label }).addClass("search-target-control selected_epa_search_target_control");
                            $("#update-blueprint-epa-selection-form-0").append(input);
                        }
                    });
                }

                var filters = {
                    selected_epa : {
                        label : "<?php echo $translate->_("Course EPAs"); ?>",
                        data_source : epa_datasource
                    }
                };

                $("#select-eap-btn").advancedSearch({
                    api_url : "<?php echo $entrada_url ?>",
                    resource_url: "<?php echo $entrada_url ?>",
                    filters : filters,
                    build_selected_filters: false,
                    control_class: "course-epa-selector",
                    no_results_text: "<?php echo $translate->_("No EPAs found matching the search criteria"); ?>",
                    parent_form: $("#update-blueprint-epa-selection-form-0"),
                    width: 400
                });

                var epa_settings = $("#select-eap-btn").data("settings");
                epa_settings.build_list();

                if (!selected_epas_flag) {
                    var epas_array = '';

                    $("#selected_epa_list_container li").each(function () {
                        var epa_id = $(this).data("id");
                        if ($("#epa-selected-milestones-" + component_id + "-" + epa_id).length < 1) {
                            epas_array += '&epas_id[]=' + epa_id;
                        }
                    });

                    if (epas_array.length && allow_milestones_selection > 0) {
                        $(".blueprint-component-save-data").prop("disabled", true);
                        $(".blueprint-component-save-data").addClass("disabled");
                        $("#blueprint-component-loading-overlay-" + component_id).show();

                        $.ajax({
                            url: "?section=api-blueprints",
                            type: "GET",
                            data: "method=get-epa-array-milestones" + epas_array + "&course_id=" + COURSE_ID,
                            success: function (data) {
                                var jsonResponse = safeParseJson(data, "");
                                $.each(jsonResponse.data, function(epa_id, epa_data) {
                                    var selected_count = 0;
                                    var badge = jQuery(document.createElement("a"));
                                    badge.addClass("selected-milestones-badge badge").attr("id", "epa-selected-milestones-" + component_id + "-" + epa_id);

                                    $.each(epa_data.data, function(i, objective) {
                                        var hidden = jQuery(document.createElement("input"));
                                        hidden.attr({
                                            "type": "hidden",
                                            "name": "milestones_" + component_id + "_" + epa_id + "[]"
                                        }).val(objective.objective_id);

                                        $("#update-blueprint-epa-selection-form-" + component_id).append(hidden);
                                        selected_count++;
                                        if (max_milestones > 0) {
                                            return selected_count < max_milestones;
                                        }
                                    });

                                    badge.text(selected_count + "/" + epa_data.count);
                                    $(".selected_epa_" + epa_id).append(" ").append(badge);
                                });

                                $(".blueprint-component-save-data").prop("disabled", false);
                                $(".blueprint-component-save-data").removeClass("disabled");
                                $("#blueprint-component-loading-overlay-" + component_id).hide();
                            }
                        });
                    }
                }
            });
        </script>
        <?php
    }
}