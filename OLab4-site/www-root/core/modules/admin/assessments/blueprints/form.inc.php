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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_BLUEPRINTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    // Set by caller: $generate_pdf, $pdf_error, $PROCESSED (a copy of the form_blueprint_record)
    $form_in_use = false;
    $METHOD = "update";
    $render_page = true;

    load_rte();

    $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
    if ($flash_messages) {
        foreach ($flash_messages as $message_type => $messages) {
            switch ($message_type) {
                case "error" :
                    echo display_error($messages);
                    break;
                case "success" :
                    echo display_success($messages);
                    break;
                case "notice" :
                default :
                    echo display_notice($messages);
                    break;
            }
        }
    }

    if (isset($_GET["form_blueprint_id"]) && $tmp_input = clean_input($_GET["form_blueprint_id"], array("trim", "striptags"))) {
        $PROCESSED["form_blueprint_id"] = $tmp_input;
    } else {
        $PROCESSED["form_blueprint_id"] = null;
    }

    $user_courses = array();
    $user_courses_records = $forms_api->fetchUserCourseList(($ENTRADA_USER->getActiveRole() == "admin"));
    if (empty($user_courses_records)) {
        add_error(sprintf($translate->_("No %s Found."), $translate->_("Course")));
    } else {
        $user_courses = array_map(
            function ($r) {
                return array("course_id" => $r->getID(), "course_name" => $r->getCourseName());
            },
            $user_courses_records
        );
    }

    if (count($user_courses) == 1) {
        $only_course = end($user_courses);
        $PROCESSED["course_id"] = $only_course["course_id"];
    }

    $blueprint_data = $forms_api->fetchFormBlueprintData($PROCESSED["form_blueprint_id"]);
    if (!empty($blueprint_data)) {
        $PROCESSED["title"] = $blueprint_data["form_blueprint"]["title"];
        $PROCESSED["form_type_id"] = $blueprint_data["form_blueprint"]["form_type_id"];
        $PROCESSED["description"] = $blueprint_data["form_blueprint"]["description"];
        $PROCESSED["include_instructions"] = $blueprint_data["form_blueprint"]["include_instructions"];
        $PROCESSED["instructions"] = $blueprint_data["form_blueprint"]["instructions"];
    } else {
        add_error($translate->_("Unable to fetch template data."));
    }
    $PROCESSED["is_publishable"] = $forms_api->isFormBlueprintPublishable($PROCESSED["form_blueprint_id"]);

    if (empty($blueprint_data["components"])) {
        add_error($translate->_("This form template type is not configured."));
    }

    if ($forms_api->isFormBlueprintDeleted()) {
        add_error($translate->_("This form template was deleted."));
    }

    if ($ERROR) {
        echo display_error();
        $render_page = false;
    }

    if ($render_page) {

        switch ($STEP) {
            case 2 :
                if (isset($_POST["form_blueprint_title"]) && $tmp_input = clean_input($_POST["form_blueprint_title"], array("trim", "striptags"))) {
                    $PROCESSED["title"] = $tmp_input;
                } else {
                    add_error($translate->_("A form title is required."));
                }

                if (isset($_POST["form_type_id"]) && $tmp_input = clean_input($_POST["form_type_id"], array("trim", "int"))) {
                    $PROCESSED["form_type_id"] = $tmp_input;
                } else {
                    add_error($translate->_("A form type is required."));
                }

                if (isset($_POST["form_blueprint_description"]) && $tmp_input = clean_input($_POST["form_blueprint_description"], array("trim", "striptags"))) {
                    $PROCESSED["description"] = $tmp_input;
                }

                if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                    $PROCESSED["course_id"] = $tmp_input;
                }

                if (isset($_POST["blueprint_include_instructions"]) && $tmp_input = clean_input($_POST["blueprint_include_instructions"], array("trim", "int"))) {
                    $PROCESSED["include_instructions"] = $tmp_input;
                    if (isset($_POST["form_blueprint_instructions"]) && $tmp_input = clean_input($_POST["form_blueprint_instructions"], array("trim", "html"))) {
                        $PROCESSED["instructions"] = $tmp_input;
                    }
                } else {
                    $PROCESSED["include_instructions"] = 0;
                    $PROCESSED["instructions"] = null;
                }

                $STEP = 1;
                if (!$ERROR) {
                    if ($forms_api->saveFormBlueprint($PROCESSED)) {
                        $action_taken = $METHOD == "insert" ? $translate->_("created") : $translate->_("updated");
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully %s the form."), $action_taken), "success", $MODULE);
                        $url = ENTRADA_URL . "/admin/assessments/blueprints?section=edit-blueprint&form_blueprint_id={$forms_api->getFormBlueprintID()}";
                        header("Location: " . $url);

                    } else {
                        foreach ($forms_api->getErrorMessages() as $error_message) {
                            add_error($error_message);
                        }
                    }
                }
                break;
        }

        if ($SUCCESS) {
            echo display_success();
        }
        if ($ERROR) {
            echo display_error();
        }
        if ($NOTICE) {
            echo display_notice();
        }

        $scales_list["milestone_ec"] = $forms_api->fetchScalesList("milestone_ec");
        $scales_list["global_assessment"] = $forms_api->fetchScalesList("global_assessment");

        $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray();

        if ($blueprint_data["form_blueprint"]["course_id"]) {
            $epas_array = $forms_api->fetchMappedEPAs($blueprint_data["form_blueprint"]["course_id"],  "o.`objective_code`");
            $epas_desc = $forms_api->fetchEPADescriptionsArray($blueprint_data["form_blueprint"]["course_id"]);
            $epas = array();
            if (isset($epas_array) && is_array($epas_array)) {
                foreach ($epas_array as $epa) {
                    $epas[] = $epa["objective_id"];
                }
            }
            $contextual_variables = $forms_api->getDefaultContextualVarsMappingSets($blueprint_data["form_blueprint"]["course_id"], $epas);
        } else {
            $epas_array = array();
            $epas_desc = array();
            $contextual_variables = array();
        }

        $init_data = array();
        foreach ($blueprint_data["components"] as $order => $component) {
            $init_data[$order] = $forms_api->fetchBlueprintComponentData($PROCESSED["form_blueprint_id"], $order);
        }

        $authors =  Models_Assessments_Form_Blueprint_Author::fetchAllByBlueprintID($PROCESSED["form_blueprint_id"]);

        // Fetch the objective type for the EPAs, milestones or EC
        $objectives_title = $forms_api->fetchCourseObjectiveType($PROCESSED["course_id"]);

        // Fetch scales for each roles selector
        $component_scales = array();
        foreach ($blueprint_data["components"] as $order => $component) {
            if (isset($component["settings"]["scale_id"]) && intval($component["settings"]["scale_id"])) {
                $component_scales[$order] = $forms_api->fetchScaleData($component["settings"]["scale_id"]);
            }
        }

        $first_editable_index = 0;
        if ($first_component = $forms_api->fetchFirstEditableBlueprintComponent()) {
            $first_editable_index = $first_component["component_id"];
        }
        
        $forms = array();
        if ($blueprint_data["forms"]) {
            foreach ($blueprint_data["forms"] as $form_id) {
                $forms_api->setFormID($form_id);
                $forms_api->setFormDatasetLimit(array("objectives", "item_objectives"));
                $generated_form_dataset = $forms_api->fetchFormData();
                if (!empty($generated_form_dataset)) {
                    $pruned_dataset = $generated_form_dataset["form"];
                    $pruned_dataset["form_objectives"] = $generated_form_dataset["objectives"];
                    $pruned_dataset["item_objectives"] = $generated_form_dataset["item_objectives"];
                    $forms[$form_id] = $pruned_dataset;
                }
            }
        }

        $view_options = array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "published" => $blueprint_data["form_blueprint"]["published"],
            "forms_created" => $blueprint_data["form_blueprint"]["complete"],

            // Form values
            "form_blueprint_id" => $PROCESSED["form_blueprint_id"],
            "form_type_id" => $PROCESSED["form_type_id"],
            "title" => $PROCESSED["title"],
            "description" => $PROCESSED["description"],
            "include_instructions" => $PROCESSED["include_instructions"],
            "instructions" => $PROCESSED["instructions"],
            "authors" => $authors,
            "course_id" => $PROCESSED["course_id"],
            "courses_list" => $user_courses,
            "is_publishable" => $PROCESSED["is_publishable"],
            "objectives_title" => $objectives_title,
            "course_related" => $blueprint_data["form_type"]["course_related"],

            // Component values
            "forms" => $forms,
            "elements" => $blueprint_data["elements"],
            "components" => $blueprint_data["components"],
            "rubrics" => $blueprint_data["rubrics"],
            "standard_item_options" => $blueprint_data["standard_item_options"],
            "epas" => $epas_array,
            "epas_desc" => $epas_desc,
            "contextual_variables" => $contextual_variables,
            "contextual_vars_desc" => $contextual_vars_desc,
            "scales_list" => $scales_list,
            "component_scales" => $component_scales,
            "init_data" => $init_data,
            "first_editable_index" => $first_editable_index
        );

        // Render the form using the processed data.
        $page_view = new Views_Assessments_FormBlueprints_Pages_Form();
        $page_view->render($view_options);

        $modal_epa_milestones = new Views_Assessments_Modals_EPAMilestones();
        $modal_epa_milestones->render(array("objectives_title" => $objectives_title));

        $user_courses = array();
        $user_courses_records = $forms_api->fetchUserCourseList(($ENTRADA_USER->getActiveRole() == "admin"));
        $user_courses = array_map(
            function ($r) {
                return array("course_id" => $r->getID(), "course_name" => $r->getCourseName());
            },
            $user_courses_records
        );

        $modal_copy_blueprint = new Views_Assessments_Forms_Modals_CopyFormBlueprint();
        $modal_copy_blueprint->render(array(
            "action_url" => ENTRADA_URL . "/admin/assessments/blueprints",
            "form_blueprint_id" => $PROCESSED["form_blueprint_id"],
            "user_courses" => $user_courses,
            "prepopulate_text" => $PROCESSED["title"],
            "course_related" => $blueprint_data["form_type"]["course_related"]
        ));

        $modal_cvars_responses = new Views_Assessments_Modals_ContextualVariableResponses();
        $modal_cvars_responses->render();
    }
}