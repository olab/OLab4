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
 * Abstraction layer for Procedure form blueprints.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Blueprints_Procedure extends Entrada_Assessments_Workers_Blueprints_Base {
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
     * Initialize the Procedure form by building the standard items.
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
        if ($item_templates = Models_Assessments_Form_Blueprint_ItemTemplate::fetchAllByFormTypeParentID($this->form_type_id)) {
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
     * Reduce the number of objectives in the given list by the filter options specified.
     * In this case, we are reducing the objective_list to only procedures.
     *
     * @param array $objective_list
     * @param array $filtering_options
     * @return array|bool
     */
    public function filterObjectiveList($objective_list = array(), $filtering_options = array()) {
        global $translate;
        $filtered = array();
        $objective_model = new Models_Objective();
        $objective_set_model = new Models_ObjectiveSet();

        if (!$procedure_set_record = $objective_set_model->fetchRowByShortname("procedure_attribute")) {
            $this->addErrorMessage($translate->_("Objective set was not found."));
            return false;
        }
        $procedure_set_id = $procedure_set_record->getID();

        // For procedures, we want to iterate through the objective list and check if they have at least 1 procedure_attribute.
        foreach ($objective_list as $objective) {
            if ($objective->getCode() == "procedure") {
                // For a procedure, check if it has at least 1 procedure attribute
                // If it does, then we include it in the filtered list.

                // Select one from global_lu_objectives where set_id = procedure_attribute
                $count = $objective_model->fetchChildrenCountByObjectiveSetID($objective->getID(), $procedure_set_id);
                if ($count) {
                    $filtered[] = $objective;
                }

            } else {
                $filtered[] = $objective;
            }
        }
        return $filtered;
    }

    /**
     * Implementation of abstract getComponents(). Returns the components for a procedure form.
     *
     * @return array|bool
     */
    public function getComponents() {
        global $translate;
        $fetched = array();

        // A procedure form is defined as having these components (in order):
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("epa_selector");              // 0 // EPAs (no milestones)
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("contextual_variable_list");  // 1 // CV list (procedure required)
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("ms_ec_scale");               // 2 // MS/EC Scale selector
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("entrustment_scale");         // 3 // Entrustment question (Global Assessment)
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 4 // Next Steps
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 5 // Concerns Rubric
        $fetched[] = Models_Assessments_Form_Blueprint_Component::fetchRowByShortname("standard_item");             // 6 // Form Feedback

        // Validate that the components fetched are valid
        $components = array();
        foreach ($fetched as $component_order => $component) {
            if (!$component) {
                $this->addErrorMessage($translate->_("Invalid component."));
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
     * Publish the CBME blueprint. Create forms for the relevant EPAs. Disable existing CBME blueprints.
     *
     * - For all EPAs selected
     *   - For each Procedure selected
     *     - Fetch all of the procedure responses for this procedure
     *     - Create a blank form
     *     - Add CVs selectors for non-procedure
     *     - Add Procedure elements from procedure tree
     *     - Add standard items
     *     * (tag items with EPA, Procedure CV, Procedure Tree Response)
     *
     * @param array $related_dataset
     * @return bool
     */
    public function publish($related_dataset) {
        global $translate;

        $include_instructions = (int)Entrada_Utilities::multidimensionalArrayValue($related_dataset, 0, "form_blueprint", "include_instructions");
        $instructions = Entrada_Utilities::multidimensionalArrayValue($related_dataset, null, "form_blueprint", "instructions");

        $epa_list = array();
        $epa_selector_element = $this->getCorrespondingElement($related_dataset["elements"], 0); // EPA selector should be first (index 0)
        if (!isset($related_dataset["objectives"][$epa_selector_element["afblueprint_element_id"]])) {
            $this->addErrorMessage($translate->_("No EPAs defined for blueprint."));
            return false;
        }
        // Build the list of EPAs
        foreach ($related_dataset["objectives"][$epa_selector_element["afblueprint_element_id"]] as $epa_data) {
            if (intval($epa_data["associated_objective_id"])) {
                // This is a milestone, skip;
                continue;
            }
            $epa_list[$epa_data["objective_id"]] = $epa_data;
        }

        // In the objectives array, we only care about those for the CV selector
        $cv_selector_element_id = null;

        // Locate the CV selector component ID
        foreach ($related_dataset["objectives"] as $afblueprint_element_id => $element_objectives) {
            if (!isset($related_dataset["elements"][$afblueprint_element_id])) {
                $this->addErrorMessage($translate->_("Malformed dataset: invalid element(s)"));
                return false;
            }
            $corresponding_component = $this->getCorrespondingComponent(
                $related_dataset["components"],
                $related_dataset["elements"][$afblueprint_element_id]
            );
            if (empty($corresponding_component)) {
                $this->addErrorMessage($translate->_("Malformed dataset: No corresponding component for this element."));
                continue;
            }
            if ($corresponding_component["shortname"] == "contextual_variable_list") {
                // There should only be 1 CV list, so this is our ID
                $cv_selector_element_id = $afblueprint_element_id;
                break;
            }
        }
        if ($cv_selector_element_id == null) {
            $this->addErrorMessage($translate->_("Failed to identify contextual variable selection component."));
            return false;
        }

        /**
         * The procedure form will only create forms for the relevant selected procedures (via the CV selector component).
         *
         * Of the CV component's objectives, we distinguish between the EPA, the CV, or the CV Response.
         * To do that, we make a tree (an array with child-arrays) out of the flat data, using associated objective ID as the parent-child relationship.
         * The tree will be 3 levels; top is EPA, middle is CV, bottom is the CV response.
         *
         * The bottom level (the CV responses, which are the responses for all of the selected CVs, not just "procedure") might be a "procedure" (a Contextual
         * Variable Response representing a "procedure"), so in those cases, we query the DB for the
         * procedure attributes (which are another objective set, with a parent ID of this procedure). The procedures attributes are
         * recursively built; the tree can be n levels deep, if procedure attributes exist.
         */

        $sorted_objectives  = $this->createObjectiveArrayTreeFromRelationship($related_dataset["objectives"][$cv_selector_element_id]);
        $cbme_form_title    = Entrada_Utilities::multidimensionalArrayValue($related_dataset, $translate->_("Procedure Assessment"), "form_blueprint", "title");
        $course_id          = Entrada_Utilities::multidimensionalArrayValue($related_dataset, null, "form_blueprint", "course_id");
        $form               = new Entrada_Assessments_Workers_Form($this->buildActorArray());
        $forms_to_build     = array();

        /**
         * For each EPA, for each procedure, we will create a form.
         * To do that, we have to filter the tree. The $sorted_objectives contains a complete tree of CVs, but we are only interested in the procedures.
         * So we recursively build another tree, this time, containing only the data we will be using to create forms and rubrics.
         */
        foreach ($sorted_objectives as $epa_objective_id => $epa_objectives) {
            // Next level is the CV list
            foreach ($epa_objectives as $cv_objective_id => $cv_objective_tree) {
                if (empty($cv_objective_tree)) {
                    continue;
                }
                // Check the objective tree, see if any of the children contain more objectives
                foreach ($cv_objective_tree as $cv_response_id => $cv_response_tree) {
                    if (empty($cv_response_tree)) {
                        // No children
                        continue;
                    }
                    $procedure_rubric = array();
                    if (!empty($cv_response_tree["children"])) {
                        // This will create an array that contains all of the rubrics for this procedure, sorted by heading
                        $procedure_rubric = $this->recursiveBuildProcedureRubricDataFromProcedureTree($cv_response_tree["children"]);
                    }
                    if (!empty($procedure_rubric)) {
                        $forms_to_build[] = array(
                            "epa_objective_id" => $epa_objective_id,
                            "procedure_name" => $cv_response_tree["objective_name"],
                            "procedure_cv_objective_id" => $cv_response_tree["objective_id"],
                            "rubrics" => $procedure_rubric
                        );
                    }
                }
            }
        }

        /**
         * Create each form.
         *
         * The $forms_to_build array contains the EPA objective ID, the procedure name (the form name), and an array describing the rubrics.
         * The rubrics are nested objectives, related by parent-child relationship, n levels deep, with each level being a sub-heading (positioned
         * in that order on the page), the lowest level being the rubric response lines.
         * The form also contains other components, so they are also handled here.
         **/
        $assessment_form_objectives = array();
        foreach ($forms_to_build as $epa_children) {
            $array_levels = $this->arrayLevelCount($epa_children["rubrics"]);
            $depth = (floor($array_levels) > 0) ? floor(floor($array_levels) / 2) : 0;
            if ($depth == 1) {
                $epa_children = $this->shiftProcedureResponseTree($epa_children);
            }
            $epa_objective = $this->fetchObjectiveRecordByID($epa_children["epa_objective_id"]);
            if (!$epa_objective) {
                $this->addErrorMessage($translate->_("Objective record not found."));
                return false;
            }
            $form->setID(null);
            $load_status = $form->loadData(
                array(
                    "title" => $cbme_form_title,
                    "organisation_id" => $this->actor_organisation_id,
                    "created_date" => time(),
                    "created_by" => $this->actor_proxy_id,
                    "form_type_id" => $related_dataset["form_type"]["form_type_id"],
                    "originating_id" => $this->form_blueprint_id,
                    "origin_type" => "blueprint"
                )
            );
            if (!$load_status) {
                $this->addErrorMessages($form->getErrorMessages());
                return false;
            }
            if (!$form->saveData($course_id)) {
                $this->addErrorMessages($form->getErrorMessages());
                return false;
            }

            // Form is created, add the EPA to the list we will add to assessment_form_objectives at the end of publish.
            $assessment_form_objectives[] = array("form_id" => $form->getFormID(), "epa_objective_id" => $epa_children["epa_objective_id"]);

            // This procedure gets tagged with the CV and the EPA
            $tag_objectives = array(
                (int)$epa_children["procedure_cv_objective_id"],
                (int)$epa_children["epa_objective_id"]
            );

            // If a set of instructions is to be included, add it here;
            if ($include_instructions && $instructions && trim($instructions) != "") {
                $this->addFreeTextElement($form->getFormID(), $instructions);
            }

            // For every component, we create the related items.
            foreach ($related_dataset["components"] as $component_order => $component) {
                if (!$element = $this->getCorrespondingElement($related_dataset["elements"], $component_order)) {
                    $this->addErrorMessage($translate->_("Corresponding element was not found. No match for component."));
                    continue;
                }
                switch ($component["shortname"]) {

                    case "contextual_variable_list":
                        // The CV selector generates a single-select dropdown. The elements in the select box are the CV responses.
                        // The exception is "procedure". We exclude it since procedure related data is handled by the ms_ec_scale selector.
                        $item_ids = $this->addContextualVariableDropdownItem(
                            $epa_objective,
                            $related_dataset["objectives"][$element["afblueprint_element_id"]],
                            $tag_objectives,
                            array("procedure") // ignore procedure coded objectives (i.e., all procedures)
                        );
                        if (empty($item_ids)) {
                            $this->addErrorMessage($translate->_("Please select a Contextual Variable in addition to \"Procedure\"."));
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
                                if (!$this->addFormBlueprintItem($form->getID(), $element["element_value"], $tag_objectives)) {
                                    return false;
                                }
                                break;
                            case "rubric":
                                if (!$this->addFormBlueprintRubric($form->getID(), $element["element_value"], $tag_objectives)) {
                                    return false;
                                }
                                break;
                        }
                        break;

                    case "ms_ec_scale":
                        // For the procedure form, the selected ms/ec scale generates a rubric per each procedure, organized by headings/sub-headings (free text preceding the rubric)
                        // The procedure attributes define this tree structure.
                        // recursiveAddProcedureRubrics creates the rubrics for this EPA (applying the selected scale), and attaches it to the form.
                        $status = $this->recursiveAddProcedureRubrics(
                            $form->getID(),
                            $epa_objective->getID(),
                            $element,
                            $related_dataset["scales"][$element["afblueprint_element_id"]],
                            $epa_children["rubrics"],
                            $tag_objectives
                        );
                        if (!$status) {
                            $this->addErrorMessage($translate->_("Failed to add procedure rubrics."));
                            return false;
                        }
                        break;

                    case "entrustment_scale":
                        // Entrustment scale follows previous forms' semantics. Take some text and a scale, and create a single select horizontal box.
                        $item_id = $this->addItemFromScale($related_dataset["scales"][$element["afblueprint_element_id"]], $element, $tag_objectives, "CBME-procedure-entrustment_scale");
                        if ($item_id === false) {
                            return false;
                        }
                        $form->attachItem($item_id);
                        break;

                    case "free_text_element":
                        $this->addFreeTextElement($form->getID(), $component["settings"]["element_text"]);
                        break;

                    default:
                        break;
                }
            }
        }

        // The resulting forms will use the procedure
        $form_objectives = array();

        // Forms are created and tagged. Add form objectives
        foreach ($assessment_form_objectives as $assessment_form_objective) {
            // Make a form objective object to insert later (once all form blueprints are created)
            $form_objective_model = new Models_Assessments_Form_Objective();
            $form_objective_data = $form_objective_model->toArray();
            $form_objective_data["form_id"] = $assessment_form_objective["form_id"];
            $form_objective_data["objective_id"] = $assessment_form_objective["epa_objective_id"];
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

    //-- Update functionality (configure the components) --//

    /**
     * Execute the various required components of a form blueprint.
     *
     * For the Procedure blueprint, we must update 3 different types of component types.
     *
     * @param $component_progress_data
     * @return bool
     */
    public function updateComponentProgress($component_progress_data) {
        global $translate;
        switch ($component_progress_data["blueprint_component_type"]) {
            case "epa_selector":
                return $this->updateBlueprintElementEPASelector(
                    $this->form_blueprint_id,
                    $component_progress_data["component_data"],
                    $component_progress_data["components"],
                    0
                );
            case "contextual_variable_list":
                return $this->updateBlueprintElementContextualVariableList(
                    $this->form_blueprint_id,
                    $component_progress_data["component_data"],
                    $component_progress_data["components"],
                    1
                );
            case "ms_ec_scale":
                // For the procedure form, the ms_ec_scale selector component refers to the scale that we will apply to the list of procedures.
                return $this->updateBlueprintProcedureScale(
                    $this->form_blueprint_id,
                    $component_progress_data["components"],
                    $component_progress_data["component_data"],
                    2,
                    $component_progress_data["comment_type"],
                    $component_progress_data["flagged_responses"],
                    null, // inherits the component type name
                    $component_progress_data["editor_state"],
                    $component_progress_data["default"]
                );
            case "entrustment_scale":
                return $this->updateBlueprintProcedureEntrustmentScale(
                    $this->form_blueprint_id,
                    $component_progress_data["components"],
                    $component_progress_data["component_data"],
                    3,
                    $component_progress_data["comment_type"],
                    $component_progress_data["flagged_responses"],
                    $component_progress_data["editor_state"],
                    $component_progress_data["default"]
                ); // $editor_state in this case is a string of plain text (the item text for the item this scale is attached to).
            default:
                $this->addErrorMessage($translate->_("Unknown component type."));
                return false;
        }
    }

    /**
     * Update the procedure entrustment scale selection.
     *
     * @param $form_blueprint_id
     * @param $components
     * @param $component_data
     * @param $component_order
     * @param $comment_type
     * @param $flagged_responses
     * @param $item_text
     * @param null $default
     * @return bool
     */
    private function updateBlueprintProcedureEntrustmentScale($form_blueprint_id, $components, $component_data, $component_order, $comment_type, $flagged_responses, $item_text, $default = null) {
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
    private function updateBlueprintElementEPASelector($form_blueprint_id, $objective_list, $components, $component_order) {
        global $translate;
        $forms_api = new Entrada_Assessments_Forms($this->buildActorArray());
        $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
        // Validate the component data
        if (empty($objective_list)) {
            $this->addErrorMessage($translate->_("Empty component data."));
            return false;
        }
        // The objective list for an EPA selector for the procedure form should be a flat array of int.
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
        $blueprint_element = $this->addBlueprintElement(
            $form_blueprint_id,
            "blueprint_component",
            $blueprint_component_data["blueprint_component_id"],
            $component_order,
            "disabled"
        );
        if (!$blueprint_element) {
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
            if (!$epa_id = $this->addBlueprintObjective($blueprint_element->getID(), $objective_id)) {
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
     * Update the EPA selector in milestone mode. In order for this to work, the form type component settings record must have allow_milestones_selection = 1.
     * This method is only here if we want to change the procedure form to limit by milestones, although that would require more updates to the object's
     * handling of data.
     *
     * @param $form_blueprint_id
     * @param $objective_list
     * @param $components
     * @param $component_order
     * @return bool
     */
    private function updateBlueprintElementEPASelectorWithMilestones($form_blueprint_id, $objective_list, $components, $component_order) {
        global $translate;
        $forms_api = new Entrada_Assessments_Forms($this->buildActorArray());
        $blueprint_data = $forms_api->fetchFormBlueprintData($form_blueprint_id);
        // Validate the component data
        if (empty($objective_list)) {
            $this->addErrorMessage($translate->_("Empty component data."));
            return false;
        }
        // The objective list for an EPA selector should be an array of int.
        if (array_filter($objective_list, "is_array") !== $objective_list) {
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
        foreach ($objective_list as $objective_id => $milestones) {
            // EPA
            if (!$epa_id = $this->addBlueprintObjective($blueprint_element->getID(), $objective_id)) {
                $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                return false;
            }
            foreach ($milestones as $milestone) {
                if (!$this->addBlueprintObjective($blueprint_element->getID(), $milestone, $epa_id)) {
                    $this->addErrorMessage($translate->_("Unable to save objective for this form component."));
                    return false;
                }
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
    private function updateBlueprintElementContextualVariableList($form_blueprint_id, $cv_arrays, $components, $component_order) {
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
     * Add a Milestone/Ec type scale (to denote the Entrustment Scale) for the EPA/procedure combo.
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
    private function updateBlueprintProcedureScale($form_blueprint_id, $components, $component_data, $component_order, $comment_type, $flagged_responses, $item_text = null, $editor_state = null, $default = null) {
        global $translate;
        $component_shortname = "ms_ec_scale";
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
        // Scales are appropriate.
        // So store them for this blueprint.
        // Find the component in our list of applicable components.
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
        if ($item_text === null || $item_text === "") {
            $item_text = $translate->_("MS/EC Scale Selector");
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

    //-- Helpers for publishing this type of form --//

    /**
     * Counts the array depth.
     *
     * @param $procedure_tree
     * @param string $children_index
     * @return int
     */
    private function arrayLevelCount($procedure_tree, $children_index = 'objective_children') {
        if (!empty($procedure_tree[$children_index])) {
            $procedure_tree = $procedure_tree[$children_index];
        }
        $max_depth = 1;
        foreach ($procedure_tree as $value) {
            if (is_array($value)) {
                $depth = $this->arrayLevelCount($value, $children_index) + 1;
                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }

    private function fetchCachedScaleObject($scale_id) {
        if ($this->isInStorage("rating-scale-object", $scale_id)) {
            return $this->fetchFromStorage("rating-scale-object", $scale_id);
        } else {
            $scale = new Entrada_Assessments_Workers_Scale(
                $this->buildActorArray(
                    array(
                        "rating_scale_id" => $scale_id,
                        "determine_meta" => false,
                        "limit_dataset" => array("responses")
                    )
                )
            );
            $scale->fetchData(); // Prepopulate the scale object's dataset
            $this->addToStorage("rating-scale-object", $scale, $scale_id);
            return $scale;
        }
    }

    /**
     * For the given procedure tree, we create the rubrics. The lowest level of the tree is where the rubric responses are. All other levels are free-text headings.
     * We create and attach the rubrics in order, under each sub heading.
     *
     * @param $form_id
     * @param $epa_objective_id
     * @param $related_blueprint_element
     * @param $scale_record_data
     * @param $procedure_tree
     * @param $tag_objectives
     * @return bool
     */
    private function recursiveAddProcedureRubrics($form_id, $epa_objective_id, $related_blueprint_element, $scale_record_data, &$procedure_tree, $tag_objectives) {
        global $translate;

        if (empty($scale_record_data)) {
            $this->addErrorMessages($translate->_("Related scale data not found"));
            return false;
        }
        $scale_record = reset($scale_record_data); // dataset returns an array, but there will only be 1 item in it.
        $scale = $this->fetchCachedScaleObject($scale_record["rating_scale_id"]);
        $scale_data = $scale->fetchData();
        if (empty($scale_data)) {
            $this->addErrorMessages($scale->getErrorMessages());
            return false;
        }
        $level_count = $this->arrayLevelCount($procedure_tree); // counts the array levels; each level is actually 2
        if ($level_count) {
            $level_count = $level_count / 2; // 1 level = 2 depth steps (the objective name, and then the objective_children[n])
        }

        // Determine what the item creation options are (parse the "editor_state" field)
        $decoded_flagged = json_decode($related_blueprint_element["editor_state"], true);
        $responses_tmp = $descriptors_tmp = array();
        $i = 1;
        foreach ($scale_data["descriptors"] as $descriptor) {
            $responses_tmp[$i] = "";
            $descriptors_tmp[$i] = $descriptor["ardescriptor_id"];
            $i++;
        }
        // Rebuild the flagging array to match the responses
        $flagging_array = array();
        $allow_default = 0;
        $default_repsonse = null;
        foreach ($descriptors_tmp as $i => $descriptor) {
            if (in_array($descriptor, $decoded_flagged["flagged_response_descriptors"])) {
                $flagging_array[$i] = 1;
            } else {
                $flagging_array[$i] = 0;
            }
            if (array_key_exists("default", $decoded_flagged) && $descriptor == $decoded_flagged["default"]) {
                $allow_default = 1;
                $default_repsonse = $i;
            }
        }

        // Create the rubrics
        foreach ($procedure_tree as $procedure_data) {
            if ($level_count == 2)  {

                /**
                 * This node is 2 levels deep: this node has children, but its children have no children.
                 * This is the level we create a rubric with.
                 **/

                $item_ids = array();
                foreach ($procedure_data["objective_children"] as $objective_child) {
                    $item_ids[] = $this->addStandardItem(
                        $objective_child["objective_name"],
                        "rubric_line",
                        "CBME_procedure_item",
                        array(),
                        $related_blueprint_element["comment_type"],
                        $scale_record["rating_scale_id"],
                        $flagging_array,
                        $descriptors_tmp,
                        $tag_objectives,
                        isset($element_options["item_text"]) ? $element_options["item_text"] : null,
                        true,
                        null,
                        $allow_default,
                        $default_repsonse
                    );
                }
                // Create a rubric, and add the items to it
                $rubric = new Entrada_Assessments_Workers_Rubric($this->buildActorArray());
                $r_save_status = $rubric->saveEmptyRubric(
                    $procedure_data["objective_name"],
                    "",
                    "CBME_rubric_from_scale",
                    $scale_record["rating_scale_id"],
                    null,
                    null,
                    true
                );
                if (!$r_save_status) {
                    $this->addErrorMessage($rubric->getErrorMessages());
                    return false;
                }
                $rubric_id = $rubric->getRubricID();
                foreach ($item_ids as $item_id) {
                    $attachments[] = $rubric->attachItem($item_id);
                }
                //$rubric_ids[] = $rubric_id;
                // attach the rubric_id to the form
                $forms = new Entrada_Assessments_Workers_Form($this->buildActorArray());
                $forms->setFormID($form_id);
                if (!$forms->attachRubric($rubric_id)) {
                    $this->addErrorMessages($forms->getErrorMessages());
                    return false;
                }

            } else if ($level_count <= 1) {

                /**
                 * This node is 0 or 1 levels deep, meaning it has no children.
                 *
                 * Since this is a recursive method, we don't want to render anything on the bottom level.
                 * There are no children at this level, so we ignore it.
                 *
                 * The level directly above this renders the entire rubric, including the rubric title.
                 **/

            } else if ($level_count > 2) {

                /**
                 * This node is 3 or more levels deep; this node has children, and its children have children.
                 * This is treated as a heading/sub-heading.
                 **/

                // Add the objective name as a free-text element for the heading
                $this->addFreeTextElement($form_id, "<h2>{$procedure_data["objective_name"]}</h2>");

                // Recurse and perform this logic on that level
                $this->recursiveAddProcedureRubrics($form_id, $epa_objective_id, $related_blueprint_element, $scale_record_data, $procedure_data["objective_children"], $tag_objectives);
            }
        }
        return true;
    }

    /**
     * When the procedure responses tree only has 1 level, the items on that level can be treated as the responses for the rubric.
     * However, in order for the calling logic to create a rubric, it requires at least two levels -- the rubric title and the responses.
     * This method shifts the items at level 1 to level 2, and adds a new level 1 (the name of the procedure) in order for the
     * calling logic to be able to generate the rubric.
     *
     * @param array $procedure_tree
     * @return array
     */
    private function shiftProcedureResponseTree($procedure_tree) {
        $shifted = array();
        $shifted["epa_objective_id"] = $procedure_tree["epa_objective_id"];
        $shifted["procedure_name"] = $procedure_tree["procedure_name"];
        $shifted["procedure_cv_objective_id"] = $procedure_tree["procedure_cv_objective_id"];
        $shifted["rubrics"] = array();
        $shifted["rubrics"][0] = array(
            "objective_name" => $procedure_tree["procedure_name"],
            "objective_children" => $procedure_tree["rubrics"]
        );
        return $shifted;
    }

    /**
     * Create an array tree (an array with sub-arrays) containing the hierarchy of objectives.
     * The given source objectives array contains parent-child relationships, denoted via
     * $source["afblueprint_objective_id"] (node id) and
     * $source["associated_objective_id"] (denotes parent node id)
     *
     * The objectives will be arranged like so (all objective IDs):
     * EPA ID -> Contextual Variable ID (e.g. a "procedure") -> CV Response (e.g. a specific procedure)
     *
     * @param $source
     * @return array
     */
    private function createObjectiveArrayTreeFromRelationship($source) {
        $top_level_nodes = array();
        $nodes = array();

        // Locate the top nodes
        foreach ($source as $node) {
            if ($node["associated_objective_id"] == null) {
                $top_level_nodes[$node["afblueprint_objective_id"]] = $node;
            }
        }
        // For each of the top level nodes, recursively match their direct descendants
        foreach ($top_level_nodes as $top_level_node) {
            $nodes[$top_level_node["objective_id"]] = $this->recursiveObjectiveArrayTreeBuild($source, $top_level_node);
        }
        // Prune the empty placeholder arrays
        $this->pruneEmptyObjectiveArrayTree($nodes);
        return $nodes;
    }

    /**
     * Remove any empty tree branches from the given tree of nodes.
     *
     * @param array $nodes
     * @return mixed
     */
    private function pruneEmptyObjectiveArrayTree(&$nodes) {
        foreach ($nodes as $key => $value) {
            if (is_array($value)) {
                $nodes[$key] = $this->pruneEmptyObjectiveArrayTree($nodes[$key]);
            }
            if (empty($nodes[$key])) {
                unset($nodes[$key]);
            }
        }
        return $nodes;
    }

    /**
     * Called by createObjectiveArrayTreeFromRelationship().
     *
     * Finds the current children, and recursively finds their children.
     * When at the bottom of a given branch, attempt to fetch any procedure child objectives
     * for the given objective ID. There may or may not be objectives to fetch; if there are,
     * then a sub array is populated with another recursively built tree of procedure objectives.
     *
     * @param $source
     * @param $comparison_node
     * @param int $epa_objective_id
     * @return array
     */
    private function recursiveObjectiveArrayTreeBuild($source, $comparison_node, $epa_objective_id = 0) {
        $children = array();
        $nodes = array();

        if ($comparison_node["objective_set_shortname"] == "epa") {
            $epa_objective_id = $comparison_node["objective_id"];
        }

        // In the source, gather all the items that have a parent of the given comparison node
        foreach ($source as $item) {
            if ($item["associated_objective_id"] == $comparison_node["afblueprint_objective_id"]) {
                $children[] = $item;
            }
        }
        // Iterate through children and fetch their children
        foreach ($children as $child) {
            $nodes[$child["objective_id"]] = $this->recursiveObjectiveArrayTreeBuild($source, $child, $epa_objective_id);
            if (empty($nodes[$child["objective_id"]])) {
                // Node was empty, so attempt to fetch the procedures
                $fetched_children = array();
                // Recursively build the procedure list (expand out the "procedure" objective set)
                $this->recursiveFetchObjectiveChildrenForProcedure($fetched_children, $child["objective_id"], $epa_objective_id);
                if (!empty($fetched_children)) {
                    $cv_objective = $this->fetchObjectiveRecordByID($child["objective_id"]);
                    if (!$cv_objective) {
                        return array();
                    }
                    $procedure_objectives = array(
                        "objective_id" => $cv_objective->getID(),
                        "objective_name" => $cv_objective->getName(),
                        "objective_code" => $cv_objective->getCode(),
                        "objective_set_id" => $cv_objective->getObjectiveSetID(),
                        "objective_parent" => $cv_objective->getParent(),
                        "children" => $fetched_children["children"]
                    );
                    $nodes[$child["objective_id"]] = $procedure_objectives;
                }
            }
        }
        return $nodes;
    }

    /**
     * Called by recursiveObjectiveArrayTreeBuild().
     *
     * Recursively builds a nested list of objectives.
     * This is intentionally not added as part of the objectives model, as it is expensive and should not
     * called outside of this particular blueprint publishing context.
     *
     * @param $procedure_objectives
     * @param $objective_id
     * @param int $epa_objective_id
     */
    private function recursiveFetchObjectiveChildrenForProcedure(&$procedure_objectives, $objective_id, $epa_objective_id = 0) {
        global $db;

        $params = array($objective_id);
        $join = "";
        $where = "";

        if ($epa_objective_id) {
            $join = "JOIN `cbme_procedure_epa_attributes` AS c ON a.`objective_id` = c.`attribute_objective_id`";
            $where = "AND c.`epa_objective_id` = ? AND c.`deleted_date` IS NULL";
            $params[] = $epa_objective_id;
        }

        $query =  " SELECT a.`objective_id`, a.`objective_name`, a.`objective_code`, a.`objective_set_id`, a.`objective_parent`, a.`objective_active` 
                    FROM `global_lu_objectives` AS a 
                    JOIN `global_lu_objective_sets` AS b ON a.`objective_set_id` = b.`objective_set_id`
                    {$join}
                    WHERE a.`objective_parent` = ?
                    {$where} 
                    AND a.`objective_active` = 1
                    AND b.`shortname` = 'procedure_attribute'"; // TODO: parameterize this

        $objective_children = $db->GetAll($query, $params);
        if (!empty($objective_children)) {
            foreach ($objective_children as &$objective_child) {
                $objective_child["children"] = array();
                $this->recursiveFetchObjectiveChildrenForProcedure($objective_child, $objective_child["objective_id"]);
                $procedure_objectives["children"][$objective_child["objective_id"]] = $objective_child;
            }
        }
    }

    /**
     * This is called by publish().
     *
     * Recursively create a tree containing only the objective name and children out of the procedure objective tree.
     * The $procedure_objective_tree is created by createObjectiveArrayTreeFromRelationship().
     * This method serves as a pruning operation; we are only returning valid trees in order to create forms for them (the original
     * tree contains more than just procedure-attribute trees, it contains arrays for all Contextual Variables (which we don't want to iterate on).
     *
     * @param array $procedure_objective_tree
     * @return array
     */
    private function recursiveBuildProcedureRubricDataFromProcedureTree($procedure_objective_tree) {
        $procedure_rubric = array();
        foreach ($procedure_objective_tree as $procedure_attribute_id => $procedure_trees) {
            $local_rubric = array();
            $local_rubric["objective_name"] = $procedure_trees["objective_name"];
            $local_rubric["objective_children"] = array();
            if (!empty($procedure_trees["children"])) {
                $local_rubric["objective_children"] = $this->recursiveBuildProcedureRubricDataFromProcedureTree($procedure_trees["children"]);
            }
            $procedure_rubric[] = $local_rubric;
        }
        return $procedure_rubric;
    }
}