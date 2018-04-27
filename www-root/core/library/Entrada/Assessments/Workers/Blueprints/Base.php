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
 * Base class for shared functionality between form blueprint type objects.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
abstract class Entrada_Assessments_Workers_Blueprints_Base extends Entrada_Assessments_Base {

    protected $global_storage = "Entrada_Assessments_Workers_GlobalStorage";

    //-- Abstract methods that must be implemented by child classes --//

    /**
     * Initialize the form by building the required standard items (if applicable).
     *
     * @return bool
     */
    abstract public function initialize();

    /**
     * Define and return all of the required components.
     *
     * @return array|bool
     */
    abstract public function getComponents();

    /**
     * Given the array of related component data, update the blueprint component records.
     *
     * @param $component_progress_data
     * @return array|bool
     */
    abstract public function updateComponentProgress($component_progress_data);

    //-- Standard functionality for creating form blueprint items (these are methods used across all form blueprint types, but can be overridden by children --//

    /**
     * For the given element, create a horizontal single select item based on the scale (using the scale as text and descriptors).
     *
     * @param array $scale
     * @param array $blueprint_element
     * @param array $objectives
     * @param string $item_code
     * @param array $attributes
     * @return bool|null
     */
    public function addItemFromScale($scale, $blueprint_element, $objectives, $item_code = NULL, $attributes = array()) {
        global $translate;
        if (empty($scale)) {
            $this->addErrorMessage($translate->_("Empty scale data."));
            return false;
        }
        if (!$scale = array_shift($scale)) {
            $this->addErrorMessage($translate->_("Invalid scale data."));
            return false;
        }
        $editor_state_variables = json_decode($blueprint_element["editor_state"], true);
        if (empty($editor_state_variables)) {
            $this->addErrorMessage($translate->_("Invalid editor state."));
            return false;
        }
        if (!$editor_state_variables["item_text"]) {
            $this->addErrorMessage($translate->_("Item text must be specified."));
            return false;
        }
        $scale_object = new Entrada_Assessments_Workers_Scale($this->buildActorArray(array("rating_scale_id" => $scale["rating_scale_id"])));
        $scale_data = $scale_object->fetchData();
        if (empty($scale_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch scale data."));
            return false;
        }
        $responses = $descriptors = array();
        $i = 1;
        foreach ($scale_data["descriptors"] as $descriptor) {
            $responses[$i] = $descriptor["descriptor"];
            $descriptors[$i] = $descriptor["ardescriptor_id"];
            $i++;
        }

        $allow_default = 0;
        $default_repsonse = null;

        // Rebuild the flagging array to match the responses
        $flagging_array = array();
        foreach ($descriptors as $i => $descriptor) {
            if (in_array($descriptor, $editor_state_variables["flagged_response_descriptors"])) {
                $flagging_array[$i] = 1;
            } else {
                $flagging_array[$i] = 0;
            }
            if (array_key_exists("default", $editor_state_variables) && $descriptor == $editor_state_variables["default"]) {
                $allow_default = 1;
                $default_repsonse = $i;
            }
        }
        // Add the item and its responses
        $item_id = $this->addStandardItem(
            $editor_state_variables["item_text"],
            "horizontal_multiple_choice_single",
            ($item_code ? $item_code : "CBME_generated_item"),
            $responses,
            $blueprint_element["comment_type"],
            $scale["rating_scale_id"],
            $flagging_array,
            $descriptors,
            $objectives,
            null,
            true,
            null,
            $allow_default,
            $default_repsonse,
            $attributes
        );
        if (!$item_id) {
            $this->addErrorMessage($translate->_("Failed to add standard item."));
            return false;
        }
        return $item_id;
    }

    public function addFreeTextElement($form_id, $element_text) {
        global $translate;

        // TODO: Should be implemented in form api
        $element_data = array(
            "form_id"           => $form_id,
            "element_type"      => "text",
            "element_text"      => $element_text,
            "order"             => Models_Assessments_Form_Element::fetchNextOrder($form_id),
            "allow_comments"    => "1",
            "enable_flagging"   => "0",
            "updated_date"      => time(),
            "updated_by"        => $this->actor_proxy_id
        );

        $element = new Models_Assessments_Form_Element($element_data);
        if (!$element->insert()) {
            $this->addErrorMessage($translate->_("Error creating element."));
        }

        return true;
    }

    /**
     * Create a rubric, using a scale, for the milestones supplied.
     *
     * @param Models_Objective $epa_objective
     * @param array $scales
     * @param array $milestones
     * @param array $related_element
     * @return array|bool
     */
    public function addRubricFromScale($epa_objective, $scales, $milestones, $related_element, $attributes = array()) {
        $rubric_ids = array();
        $item_ids = array();

        $hidden_columns = array_key_exists("hidden_columns", $attributes) ? $attributes["hidden_columns"] : null;
        $collapsible = array_key_exists("collapsible", $attributes) ? $attributes["collpapsible"] : false;
        $collapsed = array_key_exists("collapsed", $attributes) ? $attributes["collapsed"] : false;
        $reorderable_in_form = array_key_exists("reorderable_in_form", $attributes) ? $attributes["reorderable_in_form"] : false;

        foreach ($scales as $scale) {
            $scale_object = new Entrada_Assessments_Workers_Scale($this->buildActorArray(array("rating_scale_id" => $scale["rating_scale_id"])));
            $scale_data = $scale_object->fetchData();
            foreach ($milestones[$epa_objective->getID()] as $milestone_line) {
                $decoded_flagged = json_decode($related_element["editor_state"], true);
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
                $item_ids[] = $this->addStandardItem(
                    $milestone_line["objective_name"],
                    "rubric_line",
                    "CBME_rubric_from_scale",
                    $responses_tmp,
                    $related_element["comment_type"],
                    $scale["rating_scale_id"],
                    $flagging_array,
                    $descriptors_tmp,
                    array(
                        $epa_objective->getID(),
                        $milestone_line["objective_id"]
                    ),
                    null,
                    true,
                    null,
                    $allow_default,
                    $default_repsonse
                );
            }
            global $translate;
            $rubric = new Entrada_Assessments_Workers_Rubric($this->buildActorArray());
            $rubric->saveEmptyRubric(
                $translate->_("Milestones"),
                "",
                "CBME_rubric_from_scale",
                $scale["rating_scale_id"],
                null,
                $hidden_columns,
                $collapsible,
                $collapsed,
                $reorderable_in_form
            );
            $rubric_id = $rubric->getRubricID();
            if (!$rubric_id) {
                $this->addErrorMessages($rubric->getErrorMessages());
                return false;
            }
            foreach ($item_ids as $item_id) {
                $attachments[] = $rubric->attachItem($item_id);
            }
            $rubric_ids[] = $rubric_id;
        }
        return $rubric_ids;
    }

    /**
     * Add a blueprint-defined item to a form (at publish time)
     *
     * @param $form_id
     * @param $item_id
     * @param $objectives
     * @return bool
     */
    public function addFormBlueprintItem($form_id, $item_id, $objectives) {
        $item_object = new Entrada_Assessments_Workers_Item($this->buildActorArray(array("item_id" => $item_id)));

        // Fetch a "loadable" version of the dataset
        $loadable_data = $item_object->fetchLoadableData();
        if ($item_object->isInUse()) {
            $item_object->setID(null);
            $loadable_data["item"]["item_id"] = null; // create a new item
        }
        $loadable_data["objectives"] = $objectives; // Update the new objectives

        if (!$item_object->loadData($loadable_data)) {
            $this->addErrorMessages($item_object->getErrorMessages());
            return false;
        }
        if (!$item_object->saveData()) {
            $this->addErrorMessages($item_object->getErrorMessages());
            return false;
        }
        // Item is saved, either the original or a new one
        $form_object = new Entrada_Assessments_Workers_Form($this->buildActorArray(array("form_id" => $form_id)));
        if (!$form_object->attachItem($item_object->getID())) {
            $this->addErrorMessages($item_object->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Add a blueprint-defined rubric to a form (at publish time).
     *
     * @param $form_id
     * @param $rubric_id
     * @param $objectives
     * @return bool
     */
    public function addFormBlueprintRubric($form_id, $rubric_id, $objectives) {
        $rubric_object = new Entrada_Assessments_Workers_Rubric($this->buildActorArray(array("rubric_id" => $rubric_id)));
        if ($rubric_object->isInUse()) {

            // The given rubric is already in use somewhere, so we want to duplicate it and affect changes on the copy.
            // We completely and immediately copy the entire rubric, including all of its lines.
            if (!$rubric_object->deepCopy()) {
                $this->addErrorMessage($rubric_object->getErrorMessages());
                return false;
            }
            $rubric_object->setStale();
            $new_rubric_data = $rubric_object->fetchData(false);
            if (empty($new_rubric_data)) {
                $this->addErrorMessages($rubric_object->getErrorMessages());
                return false;
            }
            foreach ($new_rubric_data["lines"] as $item_id => $rubric_line) {
                // The line and item already exist (deepCopy duplicated everything). So we just need to change the objectives.
                $item_object = new Entrada_Assessments_Workers_Item($this->buildActorArray(array("item_id" => $item_id, "limit_dataset" => array("objectives"))));
                if (!$item_object->setItemObjectives($objectives)) {
                    $this->addErrorMessages($item_object->getErrorMessages());
                    return false;
                }
            }
        } else {

            // Rubric is not in use, so we can apply our changes to it.
            // In this case, we are not worried about attaching objectives to the items of this rubric since we should have just created them.
            $rubric_data = $rubric_object->fetchData(false);
            foreach ($rubric_data["lines"] as $item_id => $rubric_line) {
                $item_object = new Entrada_Assessments_Workers_Item($this->buildActorArray(array("item_id" => $item_id, "limit_dataset" => array("objectives"))));
                if (!$item_object->setItemObjectives($objectives)) {
                    $this->addErrorMessages($item_object->getErrorMessages());
                    return false;
                }
            }
        }
        $rubric_object->invalidateDataset();
        // Item is saved, either the original or a new one, so attach it to the form.
        $form_object = new Entrada_Assessments_Workers_Form($this->buildActorArray(array("form_id" => $form_id)));
        if (!$form_object->attachRubric($rubric_object->getID())) {
            $this->addErrorMessages($rubric_object->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Create select box items using the given objectives, and return the IDs of the items created.
     * IDs in the exceptions list are ignored.
     *
     * Particular objectives can be excluded by specifying their code in the $exception_codes array.
     * For example, to prevent a dropdown of type "case_type" from being created, that "case_type" string
     * should be added to the exception_codes array (this array is flat).
     *
     * @param Models_Objective $epa_objective
     * @param array $objectives
     * @param array $sub_objectives
     * @param array $exception_codes
     * @return array
     */
    public function addContextualVariableDropdownItem($epa_objective, $objectives, $sub_objectives, $exception_codes = array()) {
        $item_ids = array();
        $new_objective_array = array();
        foreach ($objectives as $objective) {
            $new_objective_array[$objective["afblueprint_objective_id"]] = $objective;
        }
        $objectives = $new_objective_array;
        $variables_array = array();
        $responses_definition = array();
        if ($epa_objective) {
            $tagged_objectives = $epa_objective ? array($epa_objective->getID()) : array();
            if (isset($sub_objectives[$epa_objective->getID()]) && is_array($sub_objectives[$epa_objective->getID()])) {
                foreach ($sub_objectives[$epa_objective->getID()] as $milestone) {
                    $tagged_objectives[] = $milestone["objective_id"];
                }
            }
            // EPA dependant
            foreach ($objectives as $epa_objective_id => $epa_objective_data) {
                if (!$epa_objective_data["associated_objective_id"]
                    && $epa_objective_data["objective_id"] == $epa_objective->getID()
                ) {
                    // Variable level
                    foreach ($objectives as $var_id => $var_obj) {
                        if ($var_obj["associated_objective_id"] == $epa_objective_id) {
                            $variables_array[$var_obj["objective_id"]] = $var_obj;
                            // Response level
                            foreach ($objectives as $resp_obj) {
                                if ($resp_obj["associated_objective_id"] == $var_id) {
                                    $responses_definition[$var_obj["objective_id"]][] = $resp_obj;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Standalone, EPA is null
            foreach ($objectives as $var_id => $var_obj) {
                if (!$var_obj["associated_objective_id"]) {
                    $variables_array[$var_obj["objective_id"]] = $var_obj;
                    // Response level
                    foreach ($objectives as $resp_obj) {
                        if ($resp_obj["associated_objective_id"] == $var_id) {
                            $responses_definition[$var_obj["objective_id"]][] = $resp_obj;
                        }
                    }
                }
            }
            $tagged_objectives = $sub_objectives;
        }
        foreach ($variables_array as $cvar_id => $cvar) {
            if ($objective = $this->fetchObjectiveRecordByID($cvar["objective_id"])) {
                if (in_array($objective->getCode(), $exception_codes)) {
                    continue;
                }
                $item_response_objectives = array();
                $responses = array();
                $i = 0;
                $to_tag = array_merge($tagged_objectives, array($objective->getID()));
                foreach ($responses_definition[$cvar_id] as $resp) {
                    $tmp_obj = $this->fetchObjectiveRecordByID($resp["objective_id"]);
                    if (in_array($objective->getCode(), $exception_codes)) {
                        continue;
                    }
                    if ($tmp_obj) {
                        $i++;
                        $responses[$i] = $tmp_obj->getName();
                        $item_response_objectives[] = array("item_response_order" => $i, "objective_id" => $tmp_obj->getID());
                    }
                }
                $item_id = $this->addStandardItem(
                    $objective->getName(),
                    "selectbox_single",
                    "CBME_contextual_variables",
                    $responses,
                    "disabled",
                    null,
                    array(), // flagged
                    array(), // descriptors
                    $to_tag,
                    "",
                    true,
                    null,
                    0,
                    null,
                    array(),
                    1,
                    $item_response_objectives
                );
                $item_ids[] = $item_id;
            }
        }
        return $item_ids;
    }

     /**
     * Insert or update a blueprint element record.
     *
     * @param int $form_blueprint_id
     * @param string $element_type
     * @param mixed $element_value
     * @param int $component_order
     * @param string $comment_type
     * @param string $encoded_flags
     * @return bool|Models_Assessments_Form_Blueprint_Element
     */
    public function addBlueprintElement($form_blueprint_id, $element_type, $element_value, $component_order, $comment_type, $encoded_flags = null) {
        global $translate, $db;
        $do_update = $do_insert = false;
        switch ($element_type) {
            case "blueprint_component":
                $blueprint_element = $this->buildBlueprintElementRecord($form_blueprint_id, "blueprint_component", $element_value, $component_order, $comment_type, $encoded_flags);
                break;
            case "item":
                $blueprint_element = $this->buildBlueprintElementRecord($form_blueprint_id, "item", $element_value, $component_order, $comment_type);
                break;
            case "rubric":
                $blueprint_element = $this->buildBlueprintElementRecord($form_blueprint_id, "rubric", $element_value, $component_order, $comment_type, $encoded_flags);
                break;
            default:
                $this->addErrorMessage($translate->_("Invalid blueprint element component type."));
                return false;
        }
        if ($blueprint_element->getID()) {
            $do_update = true;
        } else {
            $do_insert = true;
        }
        // Save the element record
        if ($do_update) {
            if (!$blueprint_element->update()) {
                application_log("error", "FAILED to update blueprint element record (id = '{$blueprint_element->getID()}'), db said: " . $db->ErrorMsg());
                return false;
            }
        } else if ($do_insert) {
            if (!$blueprint_element->insert()) {
                application_log("error", "FAILED to insert new blueprint element record, db said: " . $db->ErrorMsg());
                return false;
            }
        }
        return $blueprint_element;
    }

    /**
     * Add a blueprint objective record to the database. Returns ID of the new record, or false on failure.
     *
     * @param int $afblueprint_element_id
     * @param int $objective_id
     * @param int $associated_objective_id
     * @return bool|int
     */
    public function addBlueprintObjective($afblueprint_element_id, $objective_id, $associated_objective_id = null) {
        global $db;
        $bp_objective = new Models_Assessments_Form_Blueprint_Objective();
        $bp_objective->fromArray(
            array(
                "afblueprint_objective_id" => null,
                "organisation_id" => $this->actor_organisation_id,
                "objective_id" => $objective_id,
                "associated_objective_id" => $associated_objective_id,
                "afblueprint_element_id" => $afblueprint_element_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id
            )
        );
        if (!$bp_objective->insert()) {
            application_log("error", "FAILED to insert new blueprint element objective record, db said: " . $db->ErrorMsg());
            return false;
        }
        return $bp_objective->getID();
    }

    /**
     * Add a blueprint rating scale record to the database. Returns ID of the new record, or false on failure.
     *
     * @param int $afblueprint_element_id
     * @param int $rating_scale_id
     * @return bool|int
     */
    public function addBlueprintRatingScale($afblueprint_element_id, $rating_scale_id) {
        global $db;
        $bp_scale = new Models_Assessments_Form_Blueprint_RatingScales();
        $bp_scale->fromArray(
            array(
                "afblueprint_objective_id" => null,
                "organisation_id" => $this->actor_organisation_id,
                "rating_scale_id" => $rating_scale_id,
                "afblueprint_element_id" => $afblueprint_element_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id
            )
        );
        if (!$bp_scale->insert()) {
            application_log("error", "FAILED to insert new blueprint element rating scale record, db said: " . $db->ErrorMsg());
            return false;
        }
        return $bp_scale->getID();
    }

    /**
     * Add a complete form item to the database.
     *
     * @param string $item_text
     * @param string $itemtype_shortname
     * @param string $item_code
     * @param array $responses
     * @param string $comment_type
     * @param int|null $scale_id
     * @param array $flagged_responses
     * @param array $descriptors
     * @param array $objectives
     * @param string $item_description
     * @param bool $mandatory
     * @param int|null $item_group_id
     * @param int $allow_default
     * @param int|null $default_response
     * @param array $attributes
     * @param int $hide_from_index
     * @param array $item_response_objectives
     * @return bool|null
     */
    public function addStandardItem(
        $item_text,
        $itemtype_shortname,
        $item_code,
        $responses,
        $comment_type = "disabled",
        $scale_id = null,
        $flagged_responses = array(),
        $descriptors = array(),
        $objectives = array(),
        $item_description = null,
        $mandatory = true,
        $item_group_id = null,
        $allow_default = 0,
        $default_response = null,
        $attributes = array(),
        $hide_from_index = 1,
        $item_response_objectives = array()
    ) {
        if (!is_array($attributes)) {
            $attributes = array();
        }
        $forms_item = new Entrada_Assessments_Workers_Item($this->buildActorArray());
        $item_data = array(
            "item" => array(
                "item_id" => null,
                "itemtype_id" => $this->fetchItemtypeIDByShortname($itemtype_shortname),
                "item_code" => $item_code,
                "item_text" => $item_text,
                "item_description" => $item_description,
                "rating_scale_id" => $scale_id,
                "item_group_id" => $item_group_id,
                "mandatory" => $mandatory,
                "comment_type" => $comment_type,
                "allow_default" => $allow_default,
                "default_response" => $default_response,
                "hide_from_index" => $hide_from_index,
                "organisation_id" => $this->actor_organisation_id,
                "attributes" => json_encode($attributes)
            ),
            "flag_response" => $flagged_responses,
            "responses" => $responses,
            "descriptors" => $descriptors,
            "objectives" => $objectives,
            "item_response_objectives" => $item_response_objectives
        );
        if ($forms_item->loadData($item_data)) {
            // Loaded OK, so save it.
            if ($forms_item->saveData()) {
                return $forms_item->getID();
            } else {
                // Failed to save, fetch error
                application_log("error", "Entrada_Assessments_Workers_Blueprint::addStandardItem->Failed to save new item");
                $this->addErrorMessages($forms_item->getErrorMessages());
                return false;
            }
        } else {
            // failed to validate, fetch error from object
            $this->addErrorMessages($forms_item->getErrorMessages()); // add errors, if any
        }
        return false;
    }

    /**
     * Fetch a locally cached scale record.
     *
     * @param $scale_id
     * @return bool|Models_Assessments_RatingScale
     */
    public function fetchScaleRecordByID($scale_id) {
        if ($this->isInStorage("scale_record", "$scale_id-{$this->actor_organisation_id}")) {
            return $this->fetchFromStorage("scale_record", "$scale_id-{$this->actor_organisation_id}");
        } else {
            $scale_record = Models_Assessments_RatingScale::fetchRowByIDOrganisationID($scale_id, $this->actor_organisation_id);
            $this->addToStorage("scale_record", $scale_record, "$scale_id-{$this->actor_organisation_id}");
            return $scale_record;
        }
    }

    /**
     * Fetch a locally cached objective record.
     *
     * @param int $objective_id
     * @return bool|Models_Objective
     */
    public function fetchObjectiveRecordByID($objective_id) {
        if ($this->isInStorage("objective_record", $objective_id)) {
            return $this->fetchFromStorage("objective_record", $objective_id);
        } else {
            $objective_record = Models_Objective::fetchRow($objective_id);
            $this->addToStorage("objective_record", $objective_record, $objective_id);
            return $objective_record;
        }
    }

    /**
     * Fetch a locally cached objective set record.
     *
     * @param $objective_set_id
     * @return bool|Models_ObjectiveSet
     */
    public function fetchObjectiveSetRecordByID($objective_set_id) {
        if ($this->isInStorage("objective_set_record", $objective_set_id)) {
            return $this->fetchFromStorage("objective_set_record", $objective_set_id);
        } else {
            $objective_record =  new Models_ObjectiveSet();
            $objective_record = $objective_record->fetchRowByID($objective_set_id);
            $this->addToStorage("objective_set_record", $objective_record, $objective_set_id);
            return $objective_record;
        }
    }

    /**
     * Fetch all scales by shortname (type).
     * Locally stored.
     *
     * @param string $scale_type_shortname
     * @return bool|Models_Assessments_RatingScale_Type
     */
    public function fetchScalesByShortname($scale_type_shortname) {
        if ($this->isInStorage("rating_scales", "all-{$this->actor_organisation_id}")) {
            return $this->fetchFromStorage("rating_scales", "all-{$this->actor_organisation_id}");
        } else {
            $scales = Models_Assessments_RatingScale_Type::fetchRatingScalesByShortnameOrganisationID($scale_type_shortname, $this->actor_organisation_id);
            $this->addToStorage("rating_scales", $scales, "all-{$this->actor_organisation_id}");
            return $scales;
        }
    }

    /**
     * Fetch the top level contextual variables for this organisation.
     * Stored in local cache.
     *
     * @return array|bool
     */
    public function fetchContextualVariables() {
        if ($this->isInStorage("contextual_variables", "all")) {
            return $this->fetchFromStorage("contextual_variables", "all");
        } else {
            $objective_set = new Models_ObjectiveSet();
            $contextual_variables = $objective_set->fetchAllParentObjectivesByShortname("contextual_variable", $this->actor_organisation_id);
            $this->addToStorage("contextual_variables", $contextual_variables, "all");
            if (empty($contextual_variables)) {
                return false;
            }
            return $contextual_variables;
        }
    }

    /**
     * Fetch the milestones (or ECs) for a given EPA.
     *
     * @param $course_id
     * @param $objective_id
     * @return bool|mixed
     */
    public function fetchMappedMilestonesForEPA($course_id, $objective_id) {
        $tree_object = new Entrada_CBME_ObjectiveTree($this->buildActorArray(array("course_id" => $course_id)));
        if ($objective_record = $tree_object->findNodesByObjectiveID($objective_id)) {
            $objective_record = array_shift($objective_record);
            return $tree_object->fetchLeafNodes($objective_record->getID());
        }
        return false;
    }

    /**
     * Fetch the selected EPA's milestones (or ECs) for a form blueprint.
     *
     * @param $objective_id
     * @param $element_id
     * @return array|bool
     */
    public function fetchSelectedMilestonesForEPA($objective_id, $element_id) {
        if ($milestones = Models_Assessments_Form_Blueprint_Objective::fetchAllByElementIDAssociatedObjectiveID($element_id, $objective_id)) {
            return $milestones;
        }
        return false;
    }

    /**
     * Fetch the itemtype ID for a given shortname. Uses local cache.
     *
     * @param $itemtype_shortname
     * @return int
     */
    public function fetchItemtypeIDByShortname($itemtype_shortname) {
        if ($this->isInStorage("itemtype_id", $itemtype_shortname)) {
            return $this->fetchFromStorage("itemtype_id", $itemtype_shortname);
        } else {
            if ($itemtype = Models_Assessments_Itemtype::fetchRowByShortname($itemtype_shortname)) {
                $itemtype_id = $itemtype->getID();
            } else {
                $itemtype_id = null;
            }
            $this->addToStorage("itemtype_id", $itemtype_id, $itemtype_shortname);
            return $itemtype_id;
        }
    }

    /**
     * Delete all blueprint objectives for the given blueprint element ID.
     *
     * @param $blueprint_element_id
     * @return bool
     */
    public function deleteExistingBlueprintObjectives($blueprint_element_id) {
        global $db;
        $bp_objective = new Models_Assessments_Form_Blueprint_Objective();
        if (!$bp_objective->deleteAllByOrganisationIDAfblueprintElementID($this->actor_organisation_id, $blueprint_element_id, $this->actor_proxy_id)) {
            application_log("error", "FAILED to delete blueprint element objective records, db said: " . $db->ErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Delete a blueprint element
     *
     * @param $element_id
     * @return bool
     */
    public function deleteBlueprintElement($element_id) {
        global $db;
        $bp_element = new Models_Assessments_Form_Blueprint_Element();
        if (!$bp_element->deleteByElementID($element_id, $this->actor_proxy_id)) {
            application_log("error", "FAILED to delete blueprint element records, db said: " . $db->ErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Delete all blueprint rating scales for the given blueprint element ID.
     *
     * @param $blueprint_element_id
     * @return bool
     */
    public function deleteExistingBlueprintRatingScales($blueprint_element_id) {
        global $db;
        $bp_objective = new Models_Assessments_Form_Blueprint_RatingScales();
        if (!$bp_objective->deleteAllByOrganisationIDAfblueprintElementID($this->actor_organisation_id, $blueprint_element_id, $this->actor_proxy_id)) {
            application_log("error", "FAILED to delete blueprint element rating scale records, db said: " . $db->ErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Default implementation of objective filtering. The base implementation is to not filter, and simply return the list.
     * Child classes can override this method and apply filtering.
     *
     * @param array $objective_list
     * @param array $filtering_options
     * @return mixed
     */
    public function filterObjectiveList($objective_list = array(), $filtering_options = array()) {
        return $objective_list;
    }

    //-- Protected --//

    /**
     * Fetch the element corresponding to component order (in the dataset).
     *
     * @param array $elements
     * @param $component_order
     * @return bool|array
     */
    protected function getCorrespondingElement($elements, $component_order) {
        foreach ($elements as $element) {
            if ($element["component_order"] == $component_order) {
                return $element;
            }
        }
        return false;
    }

    /**
     * Fetch the component corresponding to the given element in the dataset.
     *
     * @param array $components
     * @param $element
     * @return bool
     */
    protected function getCorrespondingComponent($components, $element) {
        // Components are array-indexed by order (this order matches the element record's component_order)
        foreach ($components as $index => $component) {
            if ($index == $element["component_order"]) {
                return $component;
            }
        }
        return false; // not found
    }

    /**
     * Add a rating scale to the blueprint.
     * An entrustment rating scale is simply an MS/EC type rating scale.
     * Called by updateComponentProgress. Can be overridden by child.
     *
     * @param int $form_blueprint_id
     * @param string $scale_type_shortname
     * @param string $component_shortname
     * @param array $components
     * @param array $component_data
     * @param int $component_order
     * @param $comment_type
     * @param $flagged_responses
     * @param $default
     * @return bool
     */
    protected function updateBlueprintElementScale($form_blueprint_id, $scale_type_shortname, $component_shortname, $components, $component_data, $component_order, $comment_type, $flagged_responses, $default) {
        global $translate;
        $scales = $this->fetchScalesByShortname($scale_type_shortname);
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
        // Find the component in our list of applicable components
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
        $flagged_responses_encoded = json_encode(array(
            "flagged_response_descriptors" => $flagged_responses,
            "default" => $default
        ));
        // Add/update blueprint element record
        if (!$blueprint_element = $this->addBlueprintElement($form_blueprint_id, "blueprint_component", $blueprint_component_data["blueprint_component_id"], $component_order, $comment_type, $flagged_responses_encoded)) {
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

    //-- Private --//

    /**
     * Build a blueprint element record as a blueprint_component.
     *
     * @param int $form_blueprint_id
     * @param string $element_type
     * @param int $element_value
     * @param int $component_order
     * @param string $comment_type
     * @param string $encoded_flags
     * @param $default
     * @return Models_Assessments_Form_Blueprint_Element
     */
    private function buildBlueprintElementRecord($form_blueprint_id, $element_type, $element_value, $component_order, $comment_type, $encoded_flags = null, $default = null) {
        // Find the blueprint element record.
        if ($blueprint_element = Models_Assessments_Form_Blueprint_Element::fetchRowByBlueprintIDElementTypeElementValue($form_blueprint_id, $element_type, $element_value)) {
            // Add updated date
            $blueprint_element_data = $blueprint_element->toArray();
            $blueprint_element_data["updated_date"] = time();
            $blueprint_element_data["comment_type"] = $comment_type;
            $blueprint_element_data["editor_state"] = $encoded_flags;
            $blueprint_element_data["default"] = $default;
            $blueprint_element_data["updated_by"] = $this->actor_proxy_id;
            $blueprint_element->fromArray($blueprint_element_data);
        } else {
            // Configure new record.
            $blueprint_element_data = array();
            $blueprint_element_data["form_blueprint_id"] = $form_blueprint_id;
            $blueprint_element_data["element_type"] = $element_type;
            $blueprint_element_data["element_value"] = $element_value;
            $blueprint_element_data["created_date"] = time();
            $blueprint_element_data["created_by"] = $this->actor_proxy_id;
            $blueprint_element_data["comment_type"] = $comment_type;
            $blueprint_element_data["component_order"] = $component_order;
            $blueprint_element_data["editor_state"] = $encoded_flags;
            $blueprint_element_data["default"] = $default;
            $blueprint_element = new Models_Assessments_Form_Blueprint_Element();
            $blueprint_element->fromArray($blueprint_element_data);
        }
        return $blueprint_element;
    }

    /**
     * For the given array of contextual variables, ensure the variables meet blueprint requirements.
     *
     * @param array $variables
     * @param array $settings
     * @return array|bool
     */
    protected function validateContextualVariableSelection($variables, $settings) {
        global $translate;

        $min_variables = isset($settings["min_variables"]) ? $settings["min_variables"] : 0;
        $max_variables = isset($settings["max_variables"]) ? $settings["max_variables"] : 0;
        $required_types = isset($settings["required_types"]) ? $settings["required_types"] : array();

        // Check if the given contextual variables are appropriate for the component.
        // In the future, we can apply a more robust ruleset, but for now, we're going to use some specific conditionals.
        $contextual_variables = $this->fetchContextualVariables();
        if (empty($contextual_variables)) {
            $this->addErrorMessage($translate->_("No contextual variables found."));
            return false;
        }
        $validation_result = array();
        $contextual_variable_counts = array();
        foreach ($variables as $epa_objective_id => $cv_list) {
            $cv_list = array_keys($cv_list);
            if (count($required_types)) {
                foreach($required_types as $required_type) {
                    $contextual_variable_counts[$epa_objective_id][$required_type] = $this->countContextualVariablesWithCode($required_type, $cv_list);
                }
            }
            $contextual_variable_counts[$epa_objective_id]["total"] = count($cv_list);
        }
        foreach ($contextual_variable_counts as $epa_objective_id => $counts) {
            if ($min_variables && $counts["total"] < $min_variables) {
                $validation_result[$epa_objective_id][] = "too_few_variables";
            }
            if ($max_variables && $counts["total"] > $max_variables) {
                $validation_result[$epa_objective_id][] = "too_many_variables";
            }
            if (count($required_types)) {
                foreach ($required_types as $required_type) {
                    if ($counts[$required_type] == 0) {
                        $validation_result[$epa_objective_id][] = "missing_required";
                    }
                }
            }
        }
        return $validation_result;
    }

    /**
     * For the given array of contextual variables, ensure the variables meet blueprint requirements.
     *
     * @param array $variables
     * @param array $settings
     * @return array|bool
     */
    protected function validateStandaloneContextualVariableSelection($variables, $settings) {
        global $translate;

        $min_variables = isset($settings["min_variables"]) ? $settings["min_variables"] : 0;
        $max_variables = isset($settings["max_variables"]) ? $settings["max_variables"] : 0;
        $required_types = isset($settings["required_types"]) ? $settings["required_types"] : array();

        // Check if the given contextual variables are appropriate for the component.
        // In the future, we can apply a more robust ruleset, but for now, we're going to use some specific conditionals.
        $contextual_variables = $this->fetchContextualVariables();
        if (empty($contextual_variables)) {
            $this->addErrorMessage($translate->_("No contextual variables found."));
            return false;
        }
        $validation_result = array();
        $contextual_variable_counts = array();

        $cv_list = array_keys($variables);
        if (count($required_types)) {
            foreach($required_types as $required_type) {
                $contextual_variable_counts[$required_type] = $this->countContextualVariablesWithCode($required_type, $cv_list);
            }
        }
        $contextual_variable_counts["total"] = count($cv_list);

        if ($min_variables && $contextual_variable_counts["total"] < $min_variables) {
            $validation_result[] = "too_few_variables";
        }
        if ($max_variables && $contextual_variable_counts["total"] > $max_variables) {
            $validation_result[] = "too_many_variables";
        }
        if (count($required_types)) {
            foreach ($required_types as $required_type) {
                if ($contextual_variable_counts[$required_type] == 0) {
                    $validation_result[] = "missing_required";
                }
            }
        }
        return $validation_result;
    }

    /**
     * For the given array, count which objective IDs match the given objective code.
     *
     * @param $objective_code
     * @param $contextual_variable_objective_ids
     * @return bool|int
     */
    private function countContextualVariablesWithCode($objective_code, $contextual_variable_objective_ids) {
        global $translate;
        $contextual_variables = $this->fetchContextualVariables();
        if (empty($contextual_variables)) {
            $this->addErrorMessage($translate->_("No contextual variables found."));
            return false;
        }
        $count = 0;
        foreach ($contextual_variable_objective_ids as $cv_id) {
            foreach ($contextual_variables as $cv_data) {
                if ($cv_data["objective_id"] == $cv_id && $cv_data["objective_code"] == $objective_code) {
                    $count++;
                }
            }
        }
        return $count;
    }

}