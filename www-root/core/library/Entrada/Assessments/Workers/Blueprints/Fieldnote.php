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
 * Abstraction layer for Field Note Blueprint form generation.
 * This can also be referred to as a Blueprint Type object.
 * This class implements the required abstract Blueprints_Base class methods
 * giving the Entrada_Assessments_Workers_Blueprint object a standard
 * way to manipulate blueprint forms.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Blueprints_Fieldnote extends Entrada_Assessments_Workers_Blueprints_Base {
    protected $form_type_id, $form_type_shortname, $form_blueprint_id;

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function setFormTypeID($form_type_id) {
        $this->form_type_id = $form_type_id;
    }

    public function getFormTypeShortname() {
        return $this->form_type_shortname;
    }

    public function setFormTypeShortname($form_type_shortname) {
        $this->form_type_shortname = $form_type_shortname;
    }

    public function getFormBlueprintID() {
        return $this->form_blueprint_id;
    }

    public function setFormBlueprintID($form_blueprint_id) {
        $this->form_blueprint_id = $form_blueprint_id;
    }

    /**
     * Publish the CBME blueprint. Create forms for the relevant EPAs. Disable existing CBME blueprints.
     *
     * ADRIAN-TODO: Implement the deactivation of previous blueprints/forms
     *
     * @param array $related_dataset
     * @return bool
     */
    public function publish($related_dataset) {
        global $translate, $db;

        // Assuming this is only called from publishBlueprint
        $cbme_form_title = isset($related_dataset["form_blueprint"]["title"]) ? $related_dataset["form_blueprint"]["title"] : $translate->_("Field note Assessment");
        $course_id = isset($related_dataset["form_blueprint"]["course_id"]) ? $related_dataset["form_blueprint"]["course_id"] : null;
        $include_instructions = isset($related_dataset["form_blueprint"]["include_instructions"]) ? intval($related_dataset["form_blueprint"]["include_instructions"]) : 0;
        $instructions = isset($related_dataset["form_blueprint"]["instructions"]) ? $related_dataset["form_blueprint"]["instructions"] : null;

        $epa_selector = reset($related_dataset["components"]);
        if ($epa_selector["shortname"] != "epa_selector") {
            $this->addErrorMessage($translate->_("Invalid component type found when attempting to publish blueprint."));
            return false;
        }
        $element = $this->getCorrespondingElement($related_dataset["elements"], 0); // EPA selector should be first (index 0)
        if (!isset($related_dataset["objectives"][$element["afblueprint_element_id"]])) {
            $this->addErrorMessage($translate->_("No EPAs defined for blueprint."));
            return false;
        }
        $forms = array();
        $epas = array();
        $milestones = array();
        $form = new Entrada_Assessments_Workers_Form($this->buildActorArray());

        foreach ($related_dataset["objectives"][$element["afblueprint_element_id"]] as $epa_data) {
            if (intval($epa_data["associated_objective_id"])) {
                // This is a milestone, skip;
                continue;
            }

            $form->setID(null);
            $load_status = $form->loadData(array(
                "title" => $cbme_form_title,
                "organisation_id" => $this->actor_organisation_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id,
                "form_type_id" => $related_dataset["form_type"]["form_type_id"],
                "originating_id" => $this->form_blueprint_id,
                "origin_type" => "blueprint"
            ));
            if (!$load_status) {
                $this->addErrorMessages($form->getErrorMessages());
                return false;
            }
            if (!$form->saveData($course_id)) {
                $this->addErrorMessages($form->getErrorMessages());
                return false;
            }
            $forms[] = $form->getID();
            $epas[$form->getID()] = $this->fetchObjectiveRecordByID($epa_data["objective_id"]);
        }

        if (empty($forms) || empty($epas)) {
            $this->addErrorMessage($translate->_("Failed to create forms for the selected EPAs."));
            application_log("error", "Failed to create form records for CBME forms. DB said : ". $db->ErrorMsg());
            return false;
        }

        // Fetch all of the milestones for our EPAs
        foreach ($epas as $epa) {
            $milestones[$epa->getID()] = array();
            // $milestones[$epa->getID()] = $this->fetchSelectedMilestonesForEPA($epa->getID(), $element["afblueprint_element_id"]);
        }

        $form_objectives = array();

        // Forms have been created, so let's add the components for them.
        foreach ($forms as $form_id) {
            $form->setID($form_id);
            $form->refreshDataset();

            // build a list of objectives to tag elements with
            $epa_objective = $epas[$form_id];
            $objectives = array($epa_objective->getID());

            // If a set of instructions is to be included, add it here;
            if ($include_instructions && $instructions && trim($instructions) != "") {
                $this->addFreeTextElement($form_id, $instructions);
            }

            // Using this form object, create the items, then attach the items to it
            foreach ($related_dataset["components"] as $component_order => $component) {
                if ($component["shortname"] == "epa_selector") {
                    continue; // ignore EPA selector (it's already dealt with)
                }
                if (!$element = $this->getCorrespondingElement($related_dataset["elements"], $component_order)) {
                    $this->addErrorMessage($translate->_("Corresponding element was not found. No match for component."));
                    continue;
                }
                switch ($component["shortname"]) {
                    case "contextual_variable_list":
                        // Top-level CVs create dropdown (select boxes) with the child CV text in it

                        $item_ids = $this->addContextualVariableDropdownItem($epas[$form_id], $related_dataset["objectives"][$element["afblueprint_element_id"]], $milestones);
                        if (empty($item_ids)) {
                            return false;
                        }
                        // Attach the new drop-downs to the form
                        foreach ($item_ids as $item_id_to_attach) {
                            $form->attachItem($item_id_to_attach);
                        }
                        break;
                    case "standard_item":
                        // Standard item will be whatever is defined by the element
                        switch ($element["element_type"]) {
                            case "item":
                                if (!$this->addFormBlueprintItem($form_id, $element["element_value"], $objectives)) {
                                    return false;
                                }
                                break;
                            case "rubric":
                                if (!$this->addFormBlueprintRubric($form_id, $element["element_value"], $objectives)) {
                                    return false;
                                }
                                break;
                        }
                        break;
                    case "entrustment_scale":
                        // Create an item with this scale, set the item text, attach it to form
                        $item_id = $this->addItemFromScale($related_dataset["scales"][$element["afblueprint_element_id"]], $element, $objectives, "CBME-fieldnote-entrustment_scale");
                        if ($item_id === false) {
                            return false;
                        }
                        $form->attachItem($item_id);
                        break;

                    case "free_text_element":
                        $editor_state = json_decode($element["editor_state"], true);
                        if (empty($editor_state)) {
                            $editor_state["element_text"] = $component["settings"]["element_text"];
                        }
                        if ((isset($component["settings"]["is_instruction"])
                                && intval($component["settings"]["is_instruction"])
                                && $include_instructions
                            ) || (!isset($component["settings"]["is_instruction"])
                                || !intval($component["settings"]["is_instruction"])
                            )
                        ) {
                            $this->addFreeTextElement($form_id, $editor_state["element_text"]);
                        }
                        break;

                    default:
                        // Unknown, skip
                        break;
                }
            }
            // Make a form objective object to insert later (once all form blueprints are created)
            $form_objective_model = new Models_Assessments_Form_Objective();
            $form_objective_data = $form_objective_model->toArray();
            $form_objective_data["form_id"] = $form_id;
            $form_objective_data["objective_id"] = $epas[$form_id]->getID();
            $form_objective_data["organisation_id"] = $this->actor_organisation_id;
            $form_objective_data["course_id"] = $course_id;
            $form_objectives[] = $form_objective_model->fromArray($form_objective_data);
        }
        // We have successfully created the items and forms, so let's tag the forms with the EPAs.
        foreach ($form_objectives as $form_objective) {
            if (!$form_objective->insert()) {
                $this->addErrorMessage($translate->_("Unable to save form objective record."));
                return false;
            }
        }
        // Finished
        return true;
    }

    /**
     * Implementation of abstract getComponents(). Returns the components for a filed note form.
     *
     * @return array|bool
     */
    public function getComponents() {
        $fetched = array();

        // A Field Note form is defined as having these components (in order):
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("epa_selector");              // 0 // EPAs (no milestones)
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("contextual_variable_list");  // 1 // CV selector
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("free_text_element");         // 2 // Free-text: Feedback to Resident
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 3 // Continue...
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 4 // Consider...
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("entrustment_scale");         // 5 // MS/EC selector
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 6 // Next Steps
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 7 // Concerns
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 8 // Form Feedback

        // Validate that the components fetched are valid
        $components = array();
        foreach ($fetched as $component_order => $component) {
            if (!$component) {
                return false;
            }
            // Save this component by ID in the resultset
            $component_array = $component->toArray();
            $settings = Models_Assessments_Form_Blueprint_ComponentSettings::fetchRowByFormTypeComponentOrder($this->form_type_id, $component_order);
            $component_array["settings"] = ($settings) ? json_decode($settings->getSettings(), true) : array();

            $components[] = $component_array;
        }
        return $components;
    }

    /**
     * Initialize the Field note form by building the standard items.
     *
     * @return bool
     */
    public function initialize() {
        global $translate;
        if (!$this->form_blueprint_id) {
            $this->addErrorMessage($translate->_("Form blueprint ID was not specified."));
            return false;
        }
        if (!$this->form_type_id) {
            $this->addErrorMessage($translate->_("Form type ID was not specified."));
            return false;
        }
        // Fetch items definition from the item template table and create the first instance
        // of each one. They will be mapped to the EPAs/Milestones at publishing time.
        $item_templates = Models_Assessments_Form_Blueprint_ItemTemplate::fetchAllByFormTypeParentID($this->form_type_id);
        if ($item_templates) {
            foreach ($item_templates as $item_template) {
                $item_definition = json_decode($item_template->getItemDefinition(), true);
                if (!$item_definition) {
                    $this->addErrorMessage($translate->_("Invalid item definition."));
                    return false;
                }
                switch ($item_definition["element_type"]) {
                    case "item":
                        $element_definition = $item_definition["element_definition"];
                        $item_id = $this->addStandardItem(
                            $element_definition["item_text"],
                            $element_definition["itemtype_shortname"],
                            $element_definition["item_code"],
                            $element_definition["responses"],
                            $element_definition["comment_type"],
                            $element_definition["rating_scale_id"],
                            $element_definition["flagged_response"],
                            $element_definition["descriptors"],
                            $element_definition["objectives"],
                            $element_definition["item_description"],
                            $element_definition["mandatory"],
                            $element_definition["item_group_id"],
                            isset($element_definition["allow_default"]) ? $element_definition["allow_default"] : 0,
                            isset($element_definition["default_response"]) ? $element_definition["default_response"] : null,
                            isset($element_definition["attributes"]) ? $element_definition["attributes"] : null
                        );
                        if (!$item_id) {
                            $this->addErrorMessage($translate->_("Failed to add standard item."));
                            return false;
                        }
                        // Add the blueprint element record
                        $this->addBlueprintElement($this->form_blueprint_id, "item", $item_id, $item_template->getComponentOrder(), $element_definition["comment_type"]);
                        break;

                    case "rubric":
                        // Get Items attached to the rubric
                        $rubric_item_templates = Models_Assessments_Form_Blueprint_ItemTemplate::fetchAllByFormTypeParentID($this->form_type_id, $item_template->getID());
                        if (!$rubric_item_templates) {
                            $this->addErrorMessage($translate->_("Failed to fetch the items for the rubric."));
                            return false;
                        }
                        $item_ids_for_rubric = array();
                        foreach ($rubric_item_templates as $rubric_item_template) {
                            $rubric_item_definition = json_decode($rubric_item_template->getItemDefinition(), true);
                            if (!$rubric_item_definition) {
                                $this->addErrorMessage($translate->_("Invalid rubric item definition."));
                                return false;
                            }
                            $element_definition = $rubric_item_definition["element_definition"];
                            $item_id = $this->addStandardItem(
                                $element_definition["item_text"],
                                $element_definition["itemtype_shortname"],
                                $element_definition["item_code"],
                                $element_definition["responses"],
                                $element_definition["comment_type"],
                                $element_definition["rating_scale_id"],
                                $element_definition["flagged_response"],
                                $element_definition["descriptors"],
                                $element_definition["objectives"],
                                $element_definition["item_description"],
                                $element_definition["mandatory"],
                                $element_definition["item_group_id"],
                                isset($element_definition["allow_default"]) ? $element_definition["allow_default"] : 0,
                                isset($element_definition["default_response"]) ? $element_definition["default_response"] : null,
                                isset($element_definition["attributes"]) ? $element_definition["attributes"] : null
                            );
                            if (!$item_id) {
                                $this->addErrorMessage($translate->_("Failed to add rubric standard item."));
                                return false;
                            }
                            $item_ids_for_rubric[] = $item_id;
                        }
                        // Create the rubric and then attach the items;
                        $rubric = new Entrada_Assessments_Workers_Rubric($this->buildActorArray());
                        $saved = $rubric->saveEmptyRubric(
                            $item_definition["element_definition"]["item_text"],
                            "",
                            "CBME_rubric_from_scale",
                            $item_definition["element_definition"]["rating_scale_id"],
                            $item_definition["element_definition"]["item_group_id"],
                            null,
                            isset($item_definition["element_definition"]["collapsible"]) ? $item_definition["element_definition"]["collapsible"] : false,
                            isset($item_definition["element_definition"]["collapsed"]) ? $item_definition["element_definition"]["collapsed"] : false,
                            isset($item_definition["element_definition"]["reorderable_in_form"]) ? $item_definition["element_definition"]["reorderable_in_form"] : false
                        );
                        if (!$saved) {
                            $this->addErrorMessages($rubric->getErrorMessages());
                            return false;
                        }
                        $rubric_id = $rubric->getRubricID();
                        foreach ($item_ids_for_rubric as $item_id_to_attach) {
                            if (!$rubric->attachItem($item_id_to_attach)) {
                                $this->addErrorMessages($rubric->getErrorMessages());
                                return false;
                            }
                        }
                        $this->addBlueprintElement($this->form_blueprint_id, "rubric", $rubric_id, $item_template->getComponentOrder(), $item_definition["element_definition"]["comment_type"]);
                        break;
                    default:
                        $this->addErrorMessage($translate->_("Invalid element type"));
                        return false;
                }
            }
        }

        // Add a dummy element for each free text elements.
        $components = $this->getComponents();
        foreach ($components as $component_order => $component) {
            if ($component["shortname"] == "free_text_element") {
                $this->addBlueprintElement($this->form_blueprint_id, "blueprint_component", $component["blueprint_component_id"], $component_order, "disabled");
            }
        }

        return true;
    }

    /**
     * Execute the various required components of a form blueprint.
     *
     * For the Filed note blueprint, we must update 3 different types of component types.
     *
     * @param $component_progress_data
     * @return bool
     */
    public function updateComponentProgress($component_progress_data) {
        global $translate;

        switch ($component_progress_data["blueprint_component_type"]) {
            case "epa_selector":
                return $this->updateBlueprintElementEPASelector($this->form_blueprint_id, $component_progress_data["component_data"], $component_progress_data["components"], 0);
            case "contextual_variable_list":
                return $this->updateBlueprintElementContextualVariableList($this->form_blueprint_id, $component_progress_data["component_data"], $component_progress_data["components"], 1);
            case "entrustment_scale":
                return $this->updateBlueprintFieldnoteEntrustmentScale($this->form_blueprint_id, $component_progress_data["components"], $component_progress_data["component_data"], 5, $component_progress_data["comment_type"], $component_progress_data["flagged_responses"], $component_progress_data["editor_state"], $component_progress_data["default"]); // $editor_state in this case is a string of plain text (the item text for the item this scale is attached to).
            default:
                $this->addErrorMessage($translate->_("Unknown component type."));
                return false;
        }
    }

    /**
     * Update blueprint element records for EPA selection widget.
     * Called by updateComponentProgress. Can be overridden by child.
     *
     * @param int $form_blueprint_id
     * @param array $objective_list
     * @param array $components
     * @param int $component_order
     * @return bool
     */
    protected function updateBlueprintElementEPASelector($form_blueprint_id, $objective_list, $components, $component_order) {
        global $translate;

        $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $this->actor_proxy_id, "actor_organisation_id" => $this->actor_organisation_id));
        $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);

        // Validate the component data
        if (empty($objective_list)) {
            $this->addErrorMessage($translate->_("Empty component data."));
            return false;
        }

        // The objective list for an EPA selector for the field note should be a flat array of int.
        if (array_filter($objective_list, "is_int") !== $objective_list) {
            $this->addErrorMessage($translate->_("Malformed component data."));
            return false;
        }

        // Find the component in our list of applicable components
        $blueprint_component_data = array();
        foreach ($components as $component) {
            if ($component["shortname"] == "epa_selector") {
                $blueprint_component_data = $component;
            }
        }
        if (empty($blueprint_component_data)) {
            $this->addErrorMessage($translate->_("Unable to find blueprint component."));
            return false;
        }

        // Add/update blueprint element record
        // Add it at position 1, EPA selector is first on the list
        if (!$blueprint_element = $this->addBlueprintElement($form_blueprint_id, "blueprint_component", $blueprint_component_data["blueprint_component_id"], $component_order, "disabled")) {
            $this->addErrorMessage($translate->_("Unable to add or update blueprint element record."));
            return false;
        }

        // Delete existing EPAs before saving new ones.
        if (!$this->deleteExistingBlueprintObjectives($blueprint_element->getID())) {
            $this->addErrorMessage($translate->_("Unable to mark old blueprint items as deleted."));
            return false;
        }

        // Add the new selected EPAs
        foreach ($objective_list as $objective_id) {
            // EPA
            if (!$epa_id = $this->addBlueprintObjective($blueprint_element->getID(),$objective_id)) {
                $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                return false;
            }
        }

        // Delete any previously saved contextual variables
        foreach ($blueprint_data["components"] as $component_order => $component) {
            if ($component["shortname"] == "contextual_variable_list") {
                foreach ($blueprint_data["elements"] as $element) {
                    if ($element["component_order"] == $component_order) {
                        if (!$this->deleteExistingBlueprintObjectives($element["afblueprint_element_id"])) {
                            $this->addErrorMessage($translate->_("Unable to mark old blueprint contextual variables as deleted."));
                            return false;
                        }
                        if (!$this->deleteBlueprintElement($element["afblueprint_element_id"])) {
                            $this->addErrorMessage($translate->_("Unable to mark old blueprint contextual variables element deleted."));
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update and validate the contextual variable list.
     * Called by updateComponentProgress. Can be overridden by child.
     *
     * @param int $form_blueprint_id
     * @param array $cv_arrays
     * @param array $components
     * @param int $component_order
     * @return bool
     */
    protected function updateBlueprintElementContextualVariableList($form_blueprint_id, $cv_arrays, $components, $component_order) {
        global $translate;

        // Validate the component data
        if (empty($cv_arrays)) {
            $this->addErrorMessage($translate->_("Empty component data."));
            return false;
        }

        // Fetch CVs
        $contextual_variables = $this->fetchContextualVariables();
        if (empty($contextual_variables)) {
            $this->addErrorMessage($translate->_("There are no contextual variables defined."));
            return false;
        }

        // The objective list for an CV selector should be an array of arrays.
        if (array_filter($cv_arrays, "is_array") !== $cv_arrays) {
            $this->addErrorMessage($translate->_("Malformed component data."));
            return false;
        }

        // Find the component in our list of applicable components
        $blueprint_component_data = array();
        foreach ($components as $component) {
            if ($component["shortname"] == "contextual_variable_list") {
                $blueprint_component_data = $component;
            }
        }
        if (empty($blueprint_component_data)) {
            $this->addErrorMessage($translate->_("Unable to find blueprint component."));
            return false;
        }

        // Validate cv array data before saving
        $min_variables = isset($blueprint_component_data["settings"]["min_variables"]) ? $blueprint_component_data["settings"]["min_variables"] : 0;
        $max_variables = isset($blueprint_component_data["settings"]["max_variables"]) ? $blueprint_component_data["settings"]["max_variables"] : 0;

        $validation_failures = $this->validateContextualVariableSelection($cv_arrays, $blueprint_component_data["settings"]);
        if (!empty($validation_failures)) {
            foreach ($validation_failures as $epa_objective_id => $failure_tokens) {
                $epa_objective = $this->fetchObjectiveRecordByID($epa_objective_id); // local cache
                $epa_code = $epa_objective->getCode();
                foreach ($failure_tokens as $failure_token) {
                    switch ($failure_token) {
                        case "too_few_variables":
                        case "too_many_variables":
                            $this->addErrorMessage(sprintf($translate->_("Please select at least %s to maximum of %s contextual variables for EPA \"%s\"."), $min_variables, $max_variables, $epa_code));
                            break;
                        case "missing_required":
                        default: // Unknown failure
                            $this->addErrorMessage(sprintf($translate->_("\"Case Complexity\" is a required contextual variable for EPA \"%s\"."), $epa_code));
                            break;
                    }
                }
            }
            return false;
        }

        // Passed validation, so save the records

        // Add/update blueprint element record
        // Add it at position 2 (CV list is second on the form)
        if (!$blueprint_element = $this->addBlueprintElement($form_blueprint_id, "blueprint_component", $blueprint_component_data["blueprint_component_id"], $component_order, "disabled")) {
            $this->addErrorMessage($translate->_("Unable to add or update blueprint element record."));
            return false;
        }

        // Delete existing objectives before saving new ones.
        if (!$this->deleteExistingBlueprintObjectives($blueprint_element->getID())) {
            $this->addErrorMessage($translate->_("Unable to mark old blueprint items as deleted."));
            return false;
        }

        // Save new objectives
        foreach ($cv_arrays as $epa_objective_id => $cv_objectives) {
            // EPA
            if (!$epa_id = $this->addBlueprintObjective($blueprint_element->getID(),$epa_objective_id)) {
                $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                return false;
            }

            // Variables to EPAs
            foreach ($cv_objectives as $cv_objective_id => $responses) {
                if (!$cv_id = $this->addBlueprintObjective($blueprint_element->getID(), $cv_objective_id, $epa_id)) {
                    $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                    return false;
                }

                // Response to Variables
                foreach($responses as $response_id) {
                    if (!$this->addBlueprintObjective($blueprint_element->getID(), $response_id, $cv_id)) {
                        $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Add an entrustment scale item, with given item text, for a specified scale.
     *
     * @param $form_blueprint_id
     * @param $components
     * @param $component_data
     * @param $component_order
     * @param $comment_type
     * @param $flagged_responses
     * @param $item_text
     * @param $default
     * @return bool
     */
    private function updateBlueprintFieldnoteEntrustmentScale($form_blueprint_id, $components, $component_data, $component_order, $comment_type, $flagged_responses, $item_text, $default = null) {
        global $translate;
        $component_shortname = "entrustment_scale";
        $scales = $this->fetchScalesByShortname("milestone_ec");
        if (empty($scales)) {
            $this->addErrorMessage($translate->_("No rating scales found."));
            return false;
        }
        if (empty($component_data)) {
            $this->addErrorMessage($translate->_("No rating scale specified."));
            return false;
        }
        // $component_data should be a flat array containing scale IDs
        if (array_filter($component_data, "is_int") !== $component_data) {
            $this->addErrorMessage($translate->_("Malformed component data."));
            return false;
        }
        // Verify that the scales are for the current org
        foreach ($component_data as $scale_id) {
            if (!$scale_record = $this->fetchScaleRecordByID($scale_id)) {
                // Not found
                $this->addErrorMessage($translate->_("The specified rating scale is not accessible."));
                return false;
            }
        }
        // Scales are appropriate
        // So store them for this blueprint
        /// Find the component in our list of applicable components
        $blueprint_component_data = array();
        foreach ($components as $component) {
            if ($component["shortname"] == $component_shortname) {
                $blueprint_component_data = $component;
            }
        }
        if (empty($blueprint_component_data)) {
            $this->addErrorMessage($translate->_("Unable to find blueprint component."));
            return false;
        }
        $encoded_editor_state = json_encode(
            array(
                "item_text" => $item_text,
                "flagged_response_descriptors" => $flagged_responses,
                "default" => $default
            )
        );
        // Add/update blueprint element record
        if (!$blueprint_element = $this->addBlueprintElement($form_blueprint_id, "blueprint_component", $blueprint_component_data["blueprint_component_id"], $component_order, $comment_type, $encoded_editor_state)) {
            $this->addErrorMessage($translate->_("Unable to add or update blueprint element record."));
            return false;
        }
        // Delete all existing blueprint scale items for this element
        if (!$this->deleteExistingBlueprintRatingScales($blueprint_element->getID())) {
            $this->addErrorMessage($translate->_("Unable to mark old blueprint rating scale items as deleted."));
            return false;
        }
        // Add the rating scale to the blueprint.
        foreach ($component_data as $rating_scale_id) {
            if (!$this->addBlueprintRatingScale($blueprint_element->getID(), $rating_scale_id)) {
                $this->addErrorMessage($translate->_("Unable to add rating scale to form blueprint."));
                return false;
            }
        }
        return true;
    }
}