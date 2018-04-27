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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>, Adrian Mellognio <adrian.mellogno@queensu.ca>
 * @copyright Copyright 2014, 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_BLUEPRINTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};


    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "blueprint-session-update-components" :

                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $PROCESSED["form_blueprint_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid form template identifier provided."));
                    }

                    if (isset($request["component_updates"]) && is_array($request["component_updates"])) {
                        add_error($translate->_("Component progress data is required."));
                    }

                    if (!$ERROR) {

                    } else {
                        echo json_encode(array("status" => "error", "errors" => $ERRORSTR, "data" => $forms_api->getErrorMessages()));
                    }

                    break;
                case "update-blueprint-epa-selection":

                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $form_blueprint_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid template ID"))));
                        exit();
                    }

                    if (isset($request["selected_epa"])) {
                        $epas = array_map('intval', $request["selected_epa"]);
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("You must select at least one EPA."))));
                        exit();
                    }

                    if (isset($request["component_id"]) && $tmp_input = clean_input($request["component_id"], array("trim", "int"))) {
                        $component_id = $tmp_input;
                    } else {
                        $component_id = 0;
                    }

                    $forms_api->setFormBlueprintID($form_blueprint_id);
                    $blueprint_data = $forms_api->fetchFormBlueprintData();
                    if (empty($blueprint_data)) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $settings = isset($blueprint_data["components"][$component_id]["settings"]) ? $blueprint_data["components"][$component_id]["settings"] : array();
                    $allow_milestones_selection = isset($settings["allow_milestones_selection"]) ? intval($settings["allow_milestones_selection"]) : 1;

                    /* Validation and component data structure building */
                    $component_data = array();
                    foreach ($epas as $epa) {
                        if ($allow_milestones_selection) {
                            if (isset($request["milestones_{$component_id}_{$epa}"])) {
                                $component_data[$epa] = array_map('intval', $request["milestones_{$component_id}_{$epa}"]);
                            } else {
                                echo json_encode(array("status" => "error", "missing_data" => '1', "data" => array($translate->_("You must select at least one Milestone or Enabling Competency for each EPA."))));
                                exit();
                            }
                        } else {
                            $component_data[] = $epa;
                        }
                    }

                    $blueprint_component_type = "epa_selector";
                    if (!$forms_api->updateBlueprintProgress($form_blueprint_id, $blueprint_component_type, $component_data, $component_id /* Component order = component_id */)) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $next_component = $forms_api->fetchNextBlueprintComponent($component_id);
                    if ($next_component === false) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }
                    if (empty($next_component)) {
                        /** Blueprint is complete */
                        $next_component["component_id"] = "";
                        $next_component["shortname"] = "";
                        $next_component["settings"] = array();
                    }
                    if ($blueprint_data["form_type"]["course_related"]) {
                        $epas_list = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"]);
                        $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
                        $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
                    } else {
                        $epas_list = array();
                        $epas_desc = array();
                        $contextual_variables = array();
                    }
                    $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();

                    $next_data = $forms_api->fetchBlueprintComponentData($form_blueprint_id, $next_component["component_id"]);

                    echo json_encode(array(
                        "status" => "success",
                        "component_id" => $next_component["component_id"], // Should be the element id from 'cbl_assessments_form_blueprint_elements'
                        "component_type" => $next_component["shortname"],
                        "component_settings" => $next_component["settings"],
                        "epas" => $epas_list,
                        "epas_desc" => $epas_desc,
                        "contextual_variables" => $contextual_variables,
                        "vars_desc" => $contextual_vars_desc,
                        "init_data" => $next_data
                    ));
                    break;
                case "update-blueprint-contextual-vars-selection":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $form_blueprint_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid template ID"))));
                        exit();
                    }

                    if (isset($request["component_id"]) && $tmp_input = clean_input($request["component_id"], array("trim", "int")) ) {
                        $component_id = $tmp_input;
                    } else {
                        $component_id = 0;
                    }

                    if (isset($request["set_no_{$component_id}"])) {
                        $sets_config = array_map("intval",$request["set_no_{$component_id}"]);
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No Contextual Variables for EPA found"))));
                        exit();
                    }

                    if (isset($request["mode"]) && $tmp_input = clean_input($request["mode"], array("trim", "striptags", "lower"))) {
                        $mode = $tmp_input;
                    } else {
                        $mode = "epa";
                    }

                    $epa_to_vars_map = array();

                    foreach ($sets_config as $set) {
                        if (!isset($request["contextual_vars_{$component_id}_{$set}"])) {
                            switch ($mode) {
                                case "standalone":
                                    echo json_encode(array(
                                        "status" => "error",
                                        "data" => array($translate->_("You must select at least one contextual variable."))
                                    ));
                                    break;
                                default:
                                    echo json_encode(array(
                                        "status" => "error",
                                        "data" => array($translate->_("You must select at least one contextual variable for each EPA set."))
                                    ));
                            }

                            exit();
                        }
                        /** Check that at least one response for each contextual variables has been selected */
                        foreach ($request["contextual_vars_{$component_id}_{$set}"] as $tmp_cvar) {
                            if (!isset($request["cvariable_responses_{$component_id}_{$set}_{$tmp_cvar}"])) {
                                echo json_encode(array(
                                    "status" => "error",
                                    "missing_data" => '1',
                                    "data" => array($translate->_("You must select at least one response for each contextual variable."))
                                ));
                                exit();
                            }
                        }

                        switch ($mode) {
                            case "standalone":
                                if (intval($set) && ($vars_list = $request["contextual_vars_{$component_id}_{$set}"]) && is_array($vars_list) ) {
                                    foreach ($vars_list as $var_id) {
                                        $epa_to_vars_map[$var_id] = array_map("intval", $request["cvariable_responses_{$component_id}_{$set}_{$var_id}"]);
                                    }
                                }
                                break;

                            default:
                                if (intval($set) &&
                                    ($vars_list = $request["contextual_vars_{$component_id}_{$set}"]) &&
                                    is_array($vars_list) &&
                                    ($epas_list = $request["contextual_vars_epa_{$component_id}_{$set}"]) &&
                                    is_array($epas_list)) {

                                    foreach($epas_list as $epa) {
                                        foreach ($vars_list as $var_id) {
                                            $epa_to_vars_map[intval($epa)][$var_id] = array_map("intval", $request["cvariable_responses_{$component_id}_{$set}_{$var_id}"]);
                                        }
                                    }
                                }
                        }
                    }

                    $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
                    $blueprint_component_type = "contextual_variable_list";
                    if (!$forms_api->updateBlueprintProgress($form_blueprint_id, $blueprint_component_type, $epa_to_vars_map, $component_id) ) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $next_component = $forms_api->fetchNextBlueprintComponent($component_id);

                    if ($next_component === false) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }
                    if (empty($next_component)) {
                        /** Blueprint is complete */
                        $next_component["component_id"] = "";
                        $next_component["shortname"] = "";
                        $next_component["settings"] = array();
                    }

                    if ($blueprint_data["form_type"]["course_related"]) {
                        $epas_list = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"]);
                        if (empty($epas_list)) {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No EPAs are defined."))));
                            exit();
                        }
                        $epas = array();
                        foreach ($epas_list as $epa) {
                            $epas[] = $epa["objective_id"];
                        }
                        $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
                        $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
                    } else {
                        $epas_desc = array();
                        $contextual_variables = array();
                        $epas_list = array();
                    }
                    $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();
                    $next_data = $forms_api->fetchBlueprintComponentData($form_blueprint_id, $next_component["component_id"]);


                    echo json_encode(array(
                        "status" => "success",
                        "component_id" => $next_component["component_id"], // Should be the element id from 'cbl_assessments_form_blueprint_elements'
                        "component_type" => $next_component["shortname"],
                        "component_settings" => $next_component["settings"],
                        "epas" => $epas_list,
                        "epas_desc" => $epas_desc,
                        "contextual_variables" => $contextual_variables,
                        "vars_desc" => $contextual_vars_desc,
                        "init_data" => $next_data
                    ));

                    break;
                case "update-blueprint-scale-selection":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $form_blueprint_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid template ID"))));
                        exit();
                    }

                    if (isset($request["component_id"]) && $tmp_input = clean_input($request["component_id"], array("trim", "int"))) {
                        $component_id = $tmp_input;
                    } else {
                        $component_id = 0;
                    }

                    if (isset($request["rating_scale"]) && $tmp_input = clean_input($request["rating_scale"], array("trim", "int"))) {
                        $rating_scale_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No scale selected"))));
                        exit();
                    }

                    if (isset($request["scale_type"]) && $tmp_input = clean_input($request["scale_type"], array("trim", "striptags", "lower"))) {
                        $blueprint_component_type = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid component type"))));
                        exit();
                    }

                    $item_text = null;
                    $text_error = false;
                    if ($blueprint_component_type == "entrustment_scale") {
                        $posted_item_text = Entrada_Utilities::arrayValueOrDefault($request, "item_text");
                        if ($posted_item_text) {
                            $tmp_input = clean_input($posted_item_text, array("trim", "striptags"));
                            if ($tmp_input) {
                                $item_text = $tmp_input;
                            } else {
                                $text_error = true;
                            }
                        } else {
                            $text_error = true;
                        }
                    }
                    if ($text_error) {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Please specify item text for the selected scale."))));
                        exit();
                    }

                    if (isset($request["rating_scale_comments"]) && $tmp_input = clean_input($request["rating_scale_comments"], array("trim", "striptags"))) {
                        $rating_scale_comments = $tmp_input;
                    } else {
                        $rating_scale_comments = "disabled";
                    }

                    if (isset($request["scale_reponse_flag"])) {
                        $scale_reponse_flag = array_map("intval", $request["scale_reponse_flag"]);
                    } else {
                        $scale_reponse_flag = array();
                    }

                    if (isset($request["allow_default"]) && $tmp_input = clean_input($request["allow_default"], array("trim", "int"))) {
                        $allow_default = $tmp_input;
                        if (isset($request["scale_default_response"]) && $tmp_input = clean_input($request["scale_default_response"], array("trim", "int"))) {
                            $scale_default_response = $tmp_input;
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Default response not specified"))));
                            exit();
                        }
                    } else {
                        $allow_default = 0;
                        $scale_default_response = null;
                    }

                    $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
                    $update_status = $forms_api->updateBlueprintProgress(
                        $form_blueprint_id,
                        $blueprint_component_type,
                        array($rating_scale_id),
                        $component_id,
                        $rating_scale_comments,
                        $scale_reponse_flag,
                        $item_text,
                        $scale_default_response
                    );
                    if (!$update_status) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $next_component = $forms_api->fetchNextBlueprintComponent($component_id);
                    if ($next_component === false) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }
                    if (empty($next_component)) {
                        /** Blueprint is complete */
                        $next_component["component_id"] = "";
                        $next_component["shortname"] = "";
                        $next_component["settings"] = array();
                    }

                    if ($blueprint_data["form_type"]["course_related"]) {
                        $epas_list = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"]);
                        if (empty($epas_list)) {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No EPAs are defined."))));
                            exit();
                        }
                        $epas = array();
                        foreach ($epas_list as $epa) {
                            $epas[] = $epa["objective_id"];
                        }
                        $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
                        $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
                    } else {
                        $epas_desc = array();
                        $contextual_variables = array();
                        $epas_list = array();
                    }
                    $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();
                    $next_data = $forms_api->fetchBlueprintComponentData($form_blueprint_id, $next_component["component_id"]);


                    echo json_encode(array(
                        "status" => "success",
                        "component_id" => $next_component["component_id"], // Should be the element id from 'cbl_assessments_form_blueprint_elements'
                        "component_type" => $next_component["shortname"],
                        "component_settings" => $next_component["settings"],
                        "epas" => $epas_list,
                        "epas_desc" => $epas_desc,
                        "contextual_variables" => $contextual_variables,
                        "vars_desc" => $contextual_vars_desc,
                        "init_data" => $next_data
                    ));

                    break;
                case "update-blueprint-free-text-element":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $form_blueprint_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid blueprint ID"))));
                        exit();
                    }

                    if (isset($request["component_id"]) && $tmp_input = clean_input($request["component_id"], array("trim", "int"))) {
                        $component_id = $tmp_input;
                    } else {
                        $component_id = 0;
                    }

                    if (isset($request["element_text"]) && $tmp_input = clean_input($request["element_text"], array("trim"))) {
                        $element_text = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Element text is missing"))));
                        exit();
                    }

                    $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
                    $blueprint_component_type = "free_text_element";
                    if (!$forms_api->updateBlueprintProgress($form_blueprint_id, $blueprint_component_type, $element_text, $component_id)) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $next_component = $forms_api->fetchNextBlueprintComponent($component_id);
                    if ($next_component === false) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }
                    if (empty($next_component)) {
                        /** Blueprint is complete */
                        $next_component["component_id"] = "";
                        $next_component["shortname"] = "";
                        $next_component["settings"] = array();
                    }

                    if ($blueprint_data["form_type"]["course_related"]) {
                        $epas_list = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"]);
                        if (empty($epas_list)) {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No EPAs are defined."))));
                            exit();
                        }
                        $epas = array();
                        foreach ($epas_list as $epa) {
                            $epas[] = $epa["objective_id"];
                        }
                        $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
                        $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
                    } else {
                        $epas_desc = array();
                        $contextual_variables = array();
                        $epas_list = array();
                    }
                    $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();
                    $next_data = $forms_api->fetchBlueprintComponentData($form_blueprint_id, $next_component["component_id"]);

                    echo json_encode(array(
                        "status" => "success",
                        "component_id" => $next_component["component_id"], // Should be the element id from 'cbl_assessments_form_blueprint_elements'
                        "component_type" => $next_component["shortname"],
                        "component_settings" => $next_component["settings"],
                        "epas" => $epas_list,
                        "epas_desc" => $epas_desc,
                        "contextual_variables" => $contextual_variables,
                        "vars_desc" => $contextual_vars_desc,
                        "init_data" => $next_data
                    ));
                    break;
                case "update-blueprint-roles-selector-element":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $form_blueprint_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid template ID"))));
                        exit();
                    }

                    if (isset($request["component_id"]) && $tmp_input = clean_input($request["component_id"], array("trim", "int"))) {
                        $component_id = $tmp_input;
                    } else {
                        $component_id = 0;
                    }

                    if (isset($request["roles_{$component_id}"]) && is_array($request["roles_{$component_id}"])) {
                        $roles = array_map("intval", $request["roles_{$component_id}"]);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No roles selected")));
                        exit();
                    }

                    $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
                    $blueprint_component_type = "role_selector";
                    if (!$forms_api->updateBlueprintProgress($form_blueprint_id, $blueprint_component_type,$roles, $component_id)) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    $next_component = $forms_api->fetchNextBlueprintComponent($component_id);
                    if ($next_component === false) {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                        exit();
                    }

                    if (empty($next_component)) {
                        /** Blueprint is complete */
                        $next_component["component_id"] = "";
                        $next_component["shortname"] = "";
                        $next_component["settings"] = array();
                    }

                    if ($blueprint_data["form_type"]["course_related"]) {
                        $epas_list = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"]);
                        if (empty($epas_list)) {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No EPAs are defined."))));
                            exit();
                        }
                        $epas = array();
                        foreach ($epas_list as $epa) {
                            $epas[] = $epa["objective_id"];
                        }
                        $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
                        $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
                    } else {
                        $epas_desc = array();
                        $contextual_variables = array();
                        $epas_list = array();
                    }
                    $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();
                    $next_data = $forms_api->fetchBlueprintComponentData($form_blueprint_id, $next_component["component_id"]);

                    echo json_encode(array(
                        "status" => "success",
                        "component_id" => $next_component["component_id"], // Should be the element id from 'cbl_assessments_form_blueprint_elements'
                        "component_type" => $next_component["shortname"],
                        "component_settings" => $next_component["settings"],
                        "epas" => $epas_list,
                        "epas_desc" => $epas_desc,
                        "contextual_variables" => $contextual_variables,
                        "vars_desc" => $contextual_vars_desc,
                        "init_data" => $next_data
                    ));
                    break;
                case "publish-blueprint":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $PROCESSED["form_blueprint_id"] = $tmp_input;
                    }
                    if (isset($request["publish"]) && $tmp_input = clean_input($request["publish"], array("trim", "int"))) {
                        $PROCESSED["publish"] = $tmp_input;
                    }

                    $forms_api->setFormBlueprintID($PROCESSED["form_blueprint_id"]);

                    if (!$forms_api->isFormBlueprintPublishable()) {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("This template is incomplete and cannot be published."))));
                        exit();
                    }

                    if ($forms_api->isFormBlueprintPublished()) {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("This template is already published."))));
                        exit();
                    }

                    if ($forms_api->setFormBlueprintAsPublished()) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Template successfully published.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $forms_api->getErrorMessages()));
                    }
                    break;
                case "remove-permission" :
                    if (isset($request["afbauthor_id"]) && $tmp_input = clean_input($request["afbauthor_id"], "int")) {
                        $PROCESSED["afbauthor_id"] = $tmp_input;
                    }

                    if ($PROCESSED["afbauthor_id"]) {

                        $author = Models_Assessments_Form_Blueprint_Author::fetchRowByID($PROCESSED["afbauthor_id"]);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                            if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                echo json_encode(array("status" => "success", $translate->_("success.")));
                            } else {
                                echo json_encode(array("status" => "error", $translate->_("You can't delete yourself.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                        }

                    } else {
                        echo json_encode(array("status" => "error"));
                    }
                    break;
                case "add-permission" :
                    if (isset($request["member_id"]) && $tmp_input = clean_input($request["member_id"], "int")) {
                        $PROCESSED["member_id"] = $tmp_input;
                    }

                    if (isset($request["member_type"]) && $tmp_input = clean_input($request["member_type"], array("trim", "striptags"))) {
                        $PROCESSED["member_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["form_blueprint_id"] = $tmp_input;
                    }

                    if (isset($PROCESSED["member_id"]) && isset($PROCESSED["member_type"]) && isset($PROCESSED["form_blueprint_id"])) {
                        $added = 0;
                        $a = Models_Assessments_Form_Blueprint_Author::fetchRowByFormIDAuthorIDAuthorType($PROCESSED["form_blueprint_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Template author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Assessments_Form_Blueprint_Author(
                                array(
                                    "form_blueprint_id" => $PROCESSED["form_blueprint_id"],
                                    "author_type"       => $PROCESSED["member_type"],
                                    "author_id"         => $PROCESSED["member_id"],
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getActiveID(),
                                    "created_date"      => time(),
                                    "created_by"        => $ENTRADA_USER->getActiveID()
                                )
                            );
                            if ($a->insert()) {
                                $added++;
                            }
                        }

                        if ($added >= 1) {
                            echo json_encode(array("status" => "success", "data" => array("author_id" => $a->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to add author"))));
                        }
                    }
                    break;
                case "set-filter-preferences" :
                    if (isset($request["curriculum_tag"]) && is_array($request["curriculum_tag"])) {
                        $PROCESSED["filters"]["curriculum_tag"] = array_filter($request["curriculum_tag"], function ($curriculum_tag) {
                            return (int) $curriculum_tag;
                        });
                    }

                    if (isset($request["author"]) && is_array($request["author"])) {
                        $PROCESSED["filters"]["author"] = array_filter($request["author"], function ($author) {
                            return (int) $author;
                        });
                    }

                    if (isset($request["course"]) && is_array($request["course"])) {
                        $PROCESSED["filters"]["course"] = array_filter($request["course"], function ($course) {
                            return (int) $course;
                        });
                    }

                    if (isset($request["organisation"]) && is_array($request["organisation"])) {
                        $PROCESSED["filters"]["organisation"] = array_filter($request["organisation"], function ($organisation) {
                            return (int) $organisation;
                        });
                    }

                    if (isset($request["milestones"]) && is_array($request["milestones"])) {
                        $PROCESSED["filters"]["milestones"] = array_filter($request["milestones"], function ($milestone) {
                            return (int) $milestone;
                        });
                    }

                    if (isset($request["epas"]) && is_array($request["epas"])) {
                        $PROCESSED["filters"]["epas"] = array_filter($request["epas"], function ($epa) {
                            return (int) $epa;
                        });
                    }

                    if (isset($request["contextual_variables"]) && is_array($request["contextual_variables"])) {
                        $PROCESSED["filters"]["contextual_variables"] = array_filter($request["contextual_variables"], function ($cv_responses) {
                            return (int) $cv_responses;
                        });
                    }

                    if (isset($request["form_types"]) && is_array($request["form_types"])) {
                        $PROCESSED["filters"]["form_types"] = array_filter($request["form_types"], function ($form_types) {
                            return (int) $form_types;
                        });
                    }

                    foreach (array("curriculum_tag", "author", "course", "organisation", "milestones", "epas", "contextual_variables", "form_types") as $filter_type) {
                        Entrada_Utilities_AdvancedSearchHelper::cleanupSessionFilters($request, $MODULE, $SUBMODULE, $filter_type);
                    }

                    if (isset($PROCESSED["filters"])) {
                        // $assessments_base = new Entrada_Utilities_Assessments_Base();
                        Models_Assessments_Form_Blueprint::saveFilterPreferences($PROCESSED["filters"]);
                        // $assessments_base->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Filters were unable to be saved")));
                    }
                    break;
                case "remove-filter" :
                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter type provided."));
                    }

                    if (isset($request["filter_target"]) && $tmp_input = clean_input($request["filter_target"], array("trim", "int"))) {
                        $PROCESSED["filter_target"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter target provided."));
                    }

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"]);
                            }
                        }

                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "remove-all-filters" :
                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"]);
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "delete-form-blueprints" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $rubric_id) {
                            $tmp_input = clean_input($rubric_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_forms = $forms_api->deleteBlueprints($PROCESSED["delete_ids"]);

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d form template(s)."), count($deleted_forms)), "form_ids" => $deleted_forms));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete one or more form template.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "copy-form-blueprint":
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], array("trim", "int"))) {
                        $PROCESSED["form_blueprint_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid form template identifier provided.")));
                        exit();
                    }

                    // Ensure that a new form blueprint title was entered
                    if (isset($_POST["new_form_title"]) && $tmp_input = clean_input($_POST["new_form_title"], array("trim", "striptags"))) {
                        $PROCESSED["title"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Sorry, a form template title is required")));
                        exit();
                    }

                    // Ensure a course id is provided:
                    if (isset($request["new_course_id"]) && $tmp_input = clean_input($request["new_course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        $PROCESSED["course_id"] =0;
                    }

                    $forms_api->copyBlueprint($PROCESSED["form_blueprint_id"], $PROCESSED["title"], $PROCESSED["course_id"]);
                    if (!$ERROR) {
                        $new_form_blueprint_id = $forms_api->getFormBlueprintID();
                        $blueprint_data = $forms_api->fetchFormBlueprintData($new_form_blueprint_id);
                        $form_type = Models_Assessments_Form_Type::fetchRowByID($blueprint_data["form_blueprint"]["form_type_id"]);

                        // Disable previous form templates if multisource.
                        if ($form_type->getShortname() == "cbme_multisource_feedback") {
                            $forms_api->deleteOtherFormTypeBlueprints($form_type->getID(), $new_form_blueprint_id);
                        }

                        Entrada_Utilities_Flashmessenger::addMessage("Successfully copied form", "success", $MODULE);
                        $url = ENTRADA_URL."/admin/assessments/blueprints?section=edit-blueprint&form_blueprint_id=". $new_form_blueprint_id;
                        echo json_encode(array("status" => "success", "url" => $url));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-form-blueprints" :

                    // ADRIAN-TODO: Needs implementation

                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"];
                    }

                    if (isset($request["search_term"]) && $tmp_input = clean_input($request["search_term"], array("trim", "striptags"))) {
                        $PROCESSED["search_term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["search_term"] = "";
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input($request["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 50;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input($request["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset($request["sort_direction"]) && $tmp_input = clean_input($request["sort_direction"], array("trim", "int", "lower"))) {
                        $PROCESSED["sort_direction"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_direction"] = "ASC";
                    }

                    if (isset($request["sort_column"]) && $tmp_input = clean_input($request["sort_column"], array("trim", "int"))) {
                        $PROCESSED["sort_column"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_column"] = "form_id";
                    }

                    if (isset($request["date_format"]) && $tmp_input = clean_input($request["date_format"], array("trim", "striptags"))) {
                        $PROCESSED["date_format"] = $tmp_input;
                    } else {
                        $PROCESSED["date_format"] = "";
                    }

                    if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], array("trim", "int"))) {
                        $PROCESSED["rubric_id"] = $tmp_input;
                    } else {
                        $PROCESSED["rubric_id"] = null;
                    }

                    if (isset($request["item_id"]) && $tmp_input = clean_input($request["item_id"], array("trim", "int"))) {
                        $PROCESSED["item_id"] = $tmp_input;
                    } else {
                        $PROCESSED["item_id"] = null;
                    }

                    //$forms = Models_Assessments_Form::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"], $PROCESSED["rubric_id"], $PROCESSED["item_id"]);
                    $forms = false;

                    if ($forms) {
                        $data = array();

                        $date_format = $PROCESSED["date_format"] == "list" ? "D M d/y h:ia" : "Y-m-d";

                        foreach ($forms as $form) {
                            $data[] = array("form_id" => $form["form_id"], "title" => $form["title"], "created_date" => ($form["created_date"] && !is_null($form["created_date"]) ? date($date_format, $form["created_date"]) : $translate->_("N/A")), "item_count" => $form["item_count"]);
                        }
                        echo json_encode(array("results" => count($data), "data" => array("total_forms" => Models_Assessments_Form::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["filters"], $PROCESSED["rubric_id"], $PROCESSED["item_id"]), "forms" => $data)));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No Forms Found")));

                    }
                    break;
                case "get-form-blueprint-data" :
                    $PROCESSED["form_blueprint_id"] = null;
                    if (isset($request["form_blueprint_id"]) && $tmp_input = clean_input($request["form_blueprint_id"], "int")) {
                        $PROCESSED["form_blueprint_id"] = $tmp_input;
                    }

                    $blueprint_data = $forms_api->fetchFormBlueprintData($PROCESSED["form_blueprint_id"]);
                    if ($blueprint_data === false) {
                        echo json_encode(array(
                            "status" => "error",
                            "errors" => $forms_api->getErrorMessages(),
                            "results" => 0,
                            "data" => $blueprint_data
                        ));
                    } else {
                        if (empty($blueprint_data)) {
                            echo json_encode(array("status" => "success", "errors" => array(), "results" => "0", "data" => array($translate->_("No results"))));
                        } else {
                            echo json_encode(array("status" => "success", "errors" => array(), "results" => 1, "data" => $blueprint_data));
                        }
                    }
                    break;
                case "get-blueprint-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $authors = false;
                    // ADRIAN-TODO: Add model call for authors
                    //$authors = Models_Assessments_Form_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                            $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                        }
                        echo json_encode(array("status" => "success", "errors" => array(), "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "errors" => array($translate->_("No authors were found.")), "data" => array()));
                    }
                    break;
                case "get-filtered-audience" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = "%".$tmp_input."%";
                    }

                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    $results = Models_Assessments_Form_Blueprint_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["form_id"], $PROCESSED["search_value"]);
                    if ($results) {
                        echo json_encode(array("results" => count($results), "data" => $results));
                    } else {
                        echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                    }
                    break;
                case "get-user-courses" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($user_courses) {
                        $data = array();
                        foreach ($user_courses as $course) {
                            $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                    break;
                case "get-user-courses-filter" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    echo Models_Course::getUserCoursesAsTargets($PROCESSED["search_value"]);
                    break;
                case "get-user-organisations" :
                    $user_organisations = $ENTRADA_USER->getAllOrganisations();
                    if ($user_organisations) {
                        $data = array();
                        foreach ($user_organisations as $key => $organisation) {
                            $data[] = array("target_id" => $key, "target_label" => $organisation);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No organisations were found.")));
                    }
                    break;
                case "get-scale-for-rendering":
                    if (isset($request["scale_id"]) && $tmp_input = clean_input($request["scale_id"], array("trim", "int"))) {
                        $PROCESSED["scale_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("Invalid/No scale ID specified")));
                        exit();
                    }

                    $scale_data = $forms_api->fetchScaleData($PROCESSED["scale_id"]);

                    // Re-index the responses to work around a javascript issue
                    $responses = array();
                    foreach ($scale_data["responses"] as $response) {
                        $responses[] = $response;
                    }

                    $scale_data["responses"] = $responses;

                    echo json_encode(array("status" => "success", "data" => $scale_data));
                    break;
                case "get-objectives" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["parent_id"]) && $tmp_input = clean_input($request["parent_id"], array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                    $objectives = Models_Objective::fetchByOrganisationSearchValue($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["parent_id"]);

                    if ($objectives) {
                        $data = array();
                        foreach ($objectives as $objective) {
                            $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                    }

                    break;
                case "get-child-objectives" :
                    if (isset($request["parent_id"]) && $tmp_input = clean_input($request["parent_id"], array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }
                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Course ID not specified.")));
                        exit();
                    }
                    if (isset($request["form_type_id"]) && $tmp_input = clean_input($request["form_type_id"], array("trim", "int"))) {
                        $PROCESSED["form_type_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Unknown template type.")));
                        exit();
                    }

                    $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                    $unfiltered_objectives = Models_Objective::fetchAllByParentIDCBMECourseObjective($PROCESSED["parent_id"], $PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation());
                    $child_objectives = $forms_api->filterBlueprintObjectivesByFormType($unfiltered_objectives, $PROCESSED["course_id"], $PROCESSED["form_type_id"]);

                    if ($child_objectives) {
                        $data = array();
                        foreach ($child_objectives as $objective) {
                            $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                    }
                break;
                case "get-cvar-responses-count":
                    if (isset($request["cvar_id"]) && $tmp_input = clean_input($request["cvar_id"], array("trim", "int"))) {
                        $PROCESSED["cvar_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No contextual variable ID specified")));
                        exit();
                    }
                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Course ID not specified.")));
                        exit();
                    }
                    if (isset($request["form_type_id"]) && $tmp_input = clean_input($request["form_type_id"], array("trim", "int"))) {
                        $PROCESSED["form_type_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Unknown template type.")));
                        exit();
                    }

                    $responses = Models_Objective::fetchAllByParentIDCBMECourseObjective($PROCESSED["cvar_id"], $PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation());
                    $filtered_responses = $forms_api->filterBlueprintObjectivesByFormType($responses, $PROCESSED["course_id"], $PROCESSED["form_type_id"]);
                    $responses_count = is_array($filtered_responses) ? count($filtered_responses) : 0;

                    echo json_encode(array("status" => "success", "data" => array("response_count" => $responses_count, "selected" => 0)));
                    break;
                case "get-form-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $courses = Models_Course::getUserCourses($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());
                    $authors = Models_Assessments_Form_Blueprint_Author::fetchByAuthorTypeProxyIDCourseID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $courses);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                            $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No authors were found.")));
                    }
                break;
                case "get-blueprints" :
                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"];
                    }

                    if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["search_term"] = "";
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 50;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset($request["sort_direction"]) && $tmp_input = clean_input(strtolower($request["sort_direction"]), array("trim", "int"))) {
                        $PROCESSED["sort_direction"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_direction"] = "ASC";
                    }

                    if (isset($request["sort_column"]) && $tmp_input = clean_input(strtolower($request["sort_column"]), array("trim", "int"))) {
                        $PROCESSED["sort_column"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_column"] = "form_blueprint_id";
                    }

                    $blueprints = Models_Assessments_Form_Blueprint::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"]);
                    $blueprints_total = Models_Assessments_Form_Blueprint::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], 0, 0, $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"]);

                    if ($blueprints) {
                        $data = array();

                        $date_format = (isset($PROCESSED["date_format"]) && $PROCESSED["date_format"] == "list") ? "D M d/y h:ia" : "Y-m-d";

                        foreach ($blueprints as $blueprint) {
                            $data[] = array(
                                "form_blueprint_id" => $blueprint["form_blueprint_id"],
                                "title" => $blueprint["title"],
                                "created_date" => ($blueprint["created_date"] && !is_null($blueprint["created_date"]) ? date($date_format, $blueprint["created_date"]) : $translate->_("N/A")),
                                "item_count" => $blueprint["item_count"],
                                "form_type" => $blueprint["form_type_title"]
                            );
                        }
                        echo json_encode(array("results" => count($data), "data" => array("total_blueprints" => count($blueprints_total), "blueprints" => $data)));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No templates found")));
                    }
                    break;
                case "get-epa-milestones":
                    if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid course id.")));
                        exit();
                    }

                    if (isset($request["epa_id"]) && $tmp_input = clean_input(strtolower($request["epa_id"]), array("trim", "int"))) {
                        $PROCESSED["epa_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid EPA id.")));
                        exit();
                    }

                    $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(),"actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),"course_id" => $PROCESSED["course_id"]));
                    if (! $objective_record = $tree_object->findNodesByObjectiveID($PROCESSED["epa_id"])) {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to retrieve objective nodes")));
                        exit();
                    }

                    $objective_record = array_shift($objective_record);
                    echo json_encode(array("status" => "success", "data" => $tree_object->fetchLeafNodes($objective_record->getID(), "o.`objective_order`")));

                    break;
                case "get-milestones":
                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        $PROCESSED["course_id"] = 0;
                    }
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if ($PROCESSED["course_id"] == 0) {
                        echo Models_Course::getUserCoursesAsTargets($PROCESSED["search_value"]);
                        break;
                    } else {
                        $course = Models_Course::get($PROCESSED["course_id"]);
                        $objectives = new Models_Objective();
                        $objective_set = new Models_ObjectiveSet();
                        $objective_set = $objective_set->fetchRowByShortname("milestone");
                        $objectives = $objectives->fetchAllChildrenByObjectiveSetIDCourseID($objective_set->getID(), $PROCESSED["course_id"]);
                    }

                    if ($objectives) {
                        $data = array();
                        foreach ($objectives as $objective) {
                            $objective_name = ($objective->getCode() ? $objective->getCode() : "N/A");
                            $data[] = array("target_id" => $objective->getID(), "target_label" => strlen($objective_name . " " . $objective->getName()) > 60 ? substr($objective_name . " " . $objective->getName(),0,60)."..." : $objective_name . " " . $objective->getName(), "target_title" => $objective->getName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name"=> $course ? $course->getCourseName() : "0"));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No milestones were found.")));
                    }
                    break;
                case "get-epas":
                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        $PROCESSED["course_id"] = 0;
                    }
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if ($PROCESSED["course_id"] == 0) {
                        echo Models_Course::getUserCoursesAsTargets($PROCESSED["search_value"]);
                        break;
                    } else {
                        $course = Models_Course::get($PROCESSED["course_id"]);
                        $objectives = new Models_Objective();
                        $objective_set = new Models_ObjectiveSet();
                        $objective_set = $objective_set->fetchRowByShortname("epa");
                        $objectives = $objectives->fetchAllChildrenByObjectiveSetIDCourseID($objective_set->getID(), $PROCESSED["course_id"]);
                    }

                    if ($objectives) {
                        $data = array();
                        foreach ($objectives as $objective) {
                            $objective_name = ($objective->getCode() ? $objective->getCode() : "N/A");
                            $data[] = array("target_id" => $objective->getID(), "target_label" => strlen($objective_name . " " . $objective->getName()) > 60 ? substr($objective_name . " " . $objective->getName(),0,60)."..." : $objective_name . " " . $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()), "target_title" => $objective->getName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name"=> $course ? $course->getCourseName() : "0"));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No EPAs were found.")));
                    }
                    break;
                case "get-contextual-variables":
                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        $PROCESSED["course_id"] = 0;
                    }
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    $objectives = new Models_Objective();
                    $objectives = $objectives->fetchAllByObjectiveSetShortnameOrganisationID("contextual_variable", $ENTRADA_USER->getActiveOrganisation());
                    $data = array();
                    if ($objectives) {
                        foreach ($objectives as $objective) {
                            $objective_name = ($objective->getName() ? $objective->getName() : "N/A");
                            $data[] = array("target_id" => $objective->getID(), "target_label" => $objective_name);
                        }
                    }
                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0"));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Contextual Variable Responses were found.")));
                    }
                    break;
                case "get-epa-array-milestones":
                    $epas_data = array();

                    if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid course id.")));
                        exit();
                    }

                    if (isset($request["epas_id"]) && is_array($request["epas_id"]) && $tmp_input = array_map("intval", $request["epas_id"])) {
                        $PROCESSED["epas_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid EPA ids.")));
                        exit();
                    }

                    $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(), "course_id" => $PROCESSED["course_id"]));

                    foreach ($PROCESSED["epas_id"] as $epa_id) {
                        if (!$objective_record = $tree_object->findNodesByObjectiveID($epa_id)) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to retrieve objective nodes")));
                            exit();
                        }

                        $objective_record = array_shift($objective_record);
                        $milestones = $tree_object->fetchLeafNodes($objective_record->getID(), "o.`objective_order`");
                        $epas_data[$epa_id]["count"] = count($milestones);
                        $epas_data[$epa_id]["data"] = $milestones;
                    }

                    echo json_encode(array("status" => "success", "data" => $epas_data));

                    break;
                case "get-epa-milestones-count":
                    if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid course id.")));
                        exit();
                    }

                    if (isset($request["epa_id"]) && $tmp_input = clean_input(strtolower($request["epa_id"]), array("trim", "int"))) {
                        $PROCESSED["epa_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid EPA id.")));
                        exit();
                    }

                    $tree_object = new Entrada_CBME_ObjectiveTree(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(),"actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),"course_id" => $PROCESSED["course_id"]));
                    if (! $objective_record = $tree_object->findNodesByObjectiveID($PROCESSED["epa_id"])) {
                        echo json_encode(array("status" => "error", "data" => "Failed to retrieve objective nodes"));
                        exit();
                    }

                    $objective_record = array_shift($objective_record);
                    echo json_encode(array("status" => "success", "data" => count($tree_object->fetchLeafNodes($objective_record->getID()))));

                    break;
                case "get-form-types" :
                    $form_type_model = new Models_Assessments_Form_Type();
                    $form_types = $form_type_model->fetchAllByOrganisationIDCategory($ENTRADA_USER->getActiveOrganisation(), "blueprint");

                    $data = array();
                    if ($form_types) {
                        foreach ($form_types as $type) {
                            $data[] = array("target_id" => $type->getID(), "target_label" => $type->getTitle());
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Form Types were found.")));
                    }
                break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
            }
            break;
        default :
            echo json_encode(array("status" => "error", "data" => $translate->_("Invalid request method.")));
            break;
    }

    exit;

}