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
 * HTML view for a CBME assessment blueprint.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Blueprint extends Views_HTML {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        $disabled               = array_key_exists("disabled", $options)                ? $options["disabled"] : null;
        $elements               = array_key_exists("elements", $options)                ? $options["elements"] : null;
        $components             = array_key_exists("components", $options)              ? $options["components"] : null;
        $epas                   = array_key_exists("epas", $options)                    ? $options["epas"] : null;
        $standard_roles         = array_key_exists("standard_roles", $options)          ? $options["standard_roles"] : null;
        $course_id              = array_key_exists("course_id", $options)               ? $options["course_id"] : null;
        $form_blueprint_id      = array_key_exists("form_blueprint_id", $options)       ? $options["form_blueprint_id"] : null;
        $scales_list            = array_key_exists("scales_list", $options)             ? $options["scales_list"] : null;
        $init_data              = array_key_exists("init_data", $options)               ? $options["init_data"] : null;
        $epas_desc              = array_key_exists("epas_desc", $options)               ? $options["epas_desc"] : null;
        $rubrics                = array_key_exists("rubrics", $options)                 ? $options["rubrics"] : null;
        $actor_proxy_id         = array_key_exists("actor_proxy_id", $options)          ? $options["actor_proxy_id"] : null;
        $actor_organisation_id  = array_key_exists("actor_organisation_id", $options)   ? $options["actor_organisation_id"] : null;
        $component_scales       = array_key_exists("component_scales", $options)        ? $options["component_scales"] : null;
        $objectives_title       = array_key_exists("objectives_title", $options)        ? $options["objectives_title"] : $translate->_("milestones");
        $contextual_variables   = array_key_exists("contextual_variables", $options)    ? $options["contextual_variables"] : null;
        $standard_item_options  = array_key_exists("standard_item_options", $options)   ? $options["standard_item_options"] : null;
        $contextual_vars_desc   = array_key_exists("contextual_vars_desc", $options)    ? $options["contextual_vars_desc"] : null;
        $first_editable_index   = array_key_exists("first_editable_index", $options)    ? $options["first_editable_index"] : null;

        /**
         * Render an invisible version of each components that were not in the elements list
         */
        foreach ($components as $index => $component) {
            // $index corresponds to blueprint_element->component_order
            $corresponding_element = array();
            foreach ($elements as $element) {
                if ($element["component_order"] == $index) {
                    $corresponding_element = $element;
                }
            }

            if ($index == $first_editable_index
                || ($component["shortname"] == "standard_item")
                || (isset($init_data[$index - 1])
                    && is_array($init_data[$index - 1])
                    && count($init_data[$index - 1])
                )
            ) {
                $visible = true;
            } else {
                $visible = false;
            }

            $view_options = array(
                "disabled" => $disabled,
                "visible" => $visible,
                "component_type" => $component["shortname"],
                "course_id" => $course_id,
                "form_blueprint_id" => $form_blueprint_id,
                "component_id" => $index,
                "init_data" => $init_data[$index],
                "settings" => $component["settings"]
            );
            switch ($component["shortname"]) {
                case "epa_selector":
                    $view_options = array_merge($view_options, array(
                        "epas" => $epas,
                        "epas_desc" => $epas_desc,
                        "render_types" => array("markup", "template"),
                        "display_section_header" => true,
                        "entrada_url" => ENTRADA_URL,
                        "objectives_title" => $objectives_title
                    ));
                    $component_view = new Views_Assessments_FormBlueprints_Components_EPASelector();
                    break;

                case "contextual_variable_list":
                    $view_options = array_merge($view_options, array(
                        "epas" => $epas,
                        "render_types" => array("markup", "template"),
                        "display_section_header" => true,
                        "contextual_variables" => $contextual_variables,
                        "epas_desc" => $epas_desc,
                        "contextual_vars_desc" => $contextual_vars_desc
                    ));
                    $component_view = new Views_Assessments_FormBlueprints_Components_ContextualVariableList();
                    break;

                case "role_selector":
                    $view_options = array_merge($view_options, array(
                        "standard_roles" => $standard_roles,
                        "component_scale" => (array_key_exists($index, $component_scales) && is_array($component_scales[$index])) ? $component_scales[$index] : array()
                    ));
                    $component_view = new Views_Assessments_FormBlueprints_Components_RoleSelector();
                    break;

                case "ms_ec_scale":
                    $view_options = array_merge($view_options, array(
                        "render_types" => array("markup", "template"),
                        "display_section_header" => true,
                        "all_scale_types" => @$scales_list["milestone_ec"], // all of the scales of the given type for this blueprint_component
                        "scale_type" => $component["shortname"]
                    ));
                    $component_view = new Views_Assessments_FormBlueprints_Components_ScaleSelector();
                    break;

                case "entrustment_scale":
                    $view_options = array_merge($view_options, array(
                        "render_types" => array("markup", "template"),
                        "display_section_header" => true,
                        "all_scale_types" => @$scales_list["global_assessment"], // all of the scales of the given type for this blueprint_component
                        "scale_type" => $component["shortname"]
                    ));
                    $component_view = new Views_Assessments_FormBlueprints_Components_ScaleSelector();
                    break;

                case "free_text_element":
                    $view_options = array_merge($view_options, array("element_text" => $component["settings"]["element_text"]));
                    $component_view = new Views_Assessments_FormBlueprints_Components_FreeText();
                    break;

                case "standard_item":
                    if (empty($corresponding_element)) {
                        // If there's no corresponding element, notify the renderer that there was a standard item missing
                        $view_options["error_text"] = $translate->_("Unable to render standard item. This item is not defined.");
                        $component_view = new Views_Assessments_FormBlueprints_Components_Error();
                    } else {
                        if ($corresponding_element["element_type"] == "rubric") {
                            // Set view options for a rubric.
                            $rubric_data = @$rubrics[$corresponding_element["element_value"]];
                            if (!empty($rubric_data)) {
                                $view_options = array(
                                    "actor_proxy_id" => $actor_proxy_id,
                                    "actor_organisation_id" => $actor_organisation_id,
                                    "rubric_id" => $corresponding_element["element_value"],
                                    "rubric_data" => $rubric_data,
                                    "draw_overlay" => true
                                );
                            }
                            // Note: If the rubric was not found, the default view options will be passed to the rubric view.
                            // The view will not render and throw the default error in that case.
                            $component_view = new Views_Assessments_Forms_Rubric(array("mode" => "editor", "rubric_state" => "editor-readonly"));
                        } else {
                            // Set view options for a standard item.
                            $view_options = @$standard_item_options[$corresponding_element["element_value"]];
                            $view_options["disabled"] = true;
                            $component_view = new Views_Assessments_FormBlueprints_Components_StandardItem();
                        }
                    }
                    break;

                default:
                    $view_options["error_text"] = $translate->_("Unable to render component: unknown type.");
                    $component_view = new Views_Assessments_FormBlueprints_Components_Error();
                    break;
            }
            $component_view->render($view_options);
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render template form"); ?></strong>
        </div>
        <?php
    }
}