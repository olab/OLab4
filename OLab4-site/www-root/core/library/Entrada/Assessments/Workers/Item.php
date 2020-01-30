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
 * This class encapsulates data manipulation of form items.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Item extends Entrada_Assessments_Workers_Base {
    protected $item_id = null;
    protected $global_storage = "Entrada_Assessments_Workers_GlobalStorage";

    public function getID() {
        return $this->item_id;
    }

    public function setID($id) {
        $this->item_id = $id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function setItemID($id) {
        $this->item_id = $id;
    }

    public function setItemText($text) {
        if (isset($this->dataset["item"]["item_text"])) {
            $this->dataset["item"]["item_text"] = $text;
            return true;
        }
        return false;
    }

    public function getItemResponseCount() {
        if (!empty($this->dataset)) {
            $max_count = $this->dataset["meta"]["responses_count"];
            if ($this->dataset["meta"]["descriptor_count"] > $max_count) {
                $max_count = $this->dataset["meta"]["descriptor_count"];
            }
            return $max_count;
        }
        return 0;
    }

    /**
     * Based on form element type, and item type, determine whether this select box option can be rendered.
     * This function serves as the determining logic for filtering the item types from item editing interfaces.
     * Forms will only render the item types in these lists. In the future, we can add some fields to the item types table
     * to accomplish this.
     *
     * @param $element_type
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canRenderOption($element_type, $itemtype_shortname) {
        if ($element_type == "rubric") {
            // Do not display item types without responses to be added to rubrics (they won't have anything to render if added to a rubric)
            switch ($itemtype_shortname) {
                case "horizontal_multiple_choice_single":
                case "vertical_multiple_choice_single":
                case "horizontal_multiple_choice_multiple":
                case "vertical_multiple_choice_multiple":
                case "selectbox_single":
                case "selectbox_multiple":
                case "scale":
                    return true;
                default:
                    return false;
            }
        } else { // Non rubric element
            switch ($itemtype_shortname) {
                // Only display these. "User" type excluded (not supported).
                case "rubric_line":
                case "horizontal_multiple_choice_single":
                case "vertical_multiple_choice_single":
                case "horizontal_multiple_choice_multiple":
                case "vertical_multiple_choice_multiple":
                case "selectbox_single":
                case "selectbox_multiple":
                case "free_text":
                case "date":
                case "numeric":
                case "scale":
                case "fieldnote":
                    return true;
                default:
                    return false;
            }
        }
    }

    /**
     * Based on item type, determine whether the item can be commented on.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canHaveComments($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "scale":
                return true;
            default:
                return false;
        }
    }

    /**
     * Based on the item type, determine whether the item can have a default response
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canHaveDefaultResponse($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "scale":
                return true;
            default:
                return false;
        }
    }

    /**
     * Based on item type, determine whether the form item is flaggable (can be commented on if flagged).
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canFlagOption($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "scale":
                return true;
            default:
                return false;
        }
    }

    /**
     * Based on item type, determine whether the item can have responses.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canHaveResponses($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "scale":
                return true;
            default:
                return false;
        }
    }

    /**
     * Based on item type, determine whether the item can have objectives associated.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function canHaveObjectives($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "scale":
            case "fieldnote":
            case "numeric":
            case "free_text":
                return true;
            case "date":
                return false;
            default:
                return true;
        }
    }

    /**
     * Based on item type, determine whether an item view prefers to display the response descriptor instead of the response text.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    static public function usesDescriptorInsteadOfResponseText($itemtype_shortname) {
        switch ($itemtype_shortname) {
            case "rubric_line":
            case "scale":
                return true;
            default:
                return false;
        }
    }

    /**
     * Return whether this form object is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->item_id) {
            $this->fetchData();
            if (empty($this->dataset)) {
                return false;
            }
            $count_in_use = is_array($this->dataset["meta"]["in_use_by"]["rubrics"]) ? count($this->dataset["meta"]["in_use_by"]["rubrics"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["forms"]) ? count($this->dataset["meta"]["in_use_by"]["forms"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["assessments"]) ? count($this->dataset["meta"]["in_use_by"]["assessments"]) : 0;
            if ($count_in_use == 0) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Return whether this item is editable.
     *
     * @return bool
     */
    public function isEditable() {
        if ($this->item_id) {
            $this->fetchData();
            if (!empty($this->dataset)) {
                return $this->dataset["meta"]["is_editable"];
            }
        }
        return true;
    }

    /**
     * Fetch which forms and rubrics are using this item.
     *
     * @return array
     */
    public function inUseBy() {
        $in_use_by = array();
        if ($this->item_id) {
            $this->fetchData();
            if (!empty($this->dataset)) {
                $in_use_by = $this->dataset["meta"]["in_use_by"];
            }
        }
        return $in_use_by;
    }

    /**
     * Duplicate the dataset of an item, but without the item_id. This allows the caller to manipulate
     * the dataset, and save at a later time.
     *
     * @param bool $clone
     * @return bool
     */
    public function duplicate($clone = false) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->item_id) {
            // Can't dupe without item ID.
            $this->addErrorMessage($translate->_("No source item specified."));
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            // Unable to duplicate
            $this->addErrorMessage($translate->_("Unable to duplicate item."));
            application_log("error", "Unable to duplicate item, ID: '{$this->item_id}'");
            return false;
        }

        // Successfully loaded new dataset
        $originating_item_id = $this->item_id;

        $this->item_id = null; // Clear our item ID, as this is a new item
        $this->dataset["meta"]["item_id"] = null;

        // Update the item data (no item id/current dates)
        $this->dataset["item"]["item_id"] = null;
        $this->dataset["item"]["created_date"] = time();
        $this->dataset["item"]["created_by"] = $this->actor_proxy_id;
        $this->dataset["item"]["organisation_id"] = $this->actor_organisation_id;
        $this->dataset["item"]["updated_date"] = null;
        $this->dataset["item"]["updated_by"] = null;
        $this->dataset["item"]["deleted_date"] = null;

        // Duplicate responses, without item_ids/primary keys
        foreach ($this->dataset["responses"] as $i => $response_data) {
            unset($this->dataset["responses"][$i]["iresponse_id"]);
            $this->dataset["responses"][$i]["item_id"] = null;
        }

        // Duplicate objectives, without item_ids/primary keys
        foreach ($this->dataset["objectives"] as $i => $response_data) {
            unset($this->dataset["objectives"][$i]["aiobjective_id"]);
            $this->dataset["objectives"][$i]["item_id"] = null;
        }

        // Duplicate authors, without item_ids/primary keys
        foreach ($this->dataset["authors"] as $i => $response_data) {
            unset($this->dataset["authors"][$i]["aiauthor_id"]);
            $this->dataset["authors"][$i]["item_id"] = null;
        }

        // Clear previous relationships
        $this->dataset["relationships"] = array();

        // If we're cloning, prepare the new item relationship.
        if ($clone) {
            $first_parent_id = null;
            if ($first_parent = Models_Assessments_Item_Relationship::fetchRowByItemID($originating_item_id)) {
                $first_parent_id = $first_parent->getFirstParentID();
            }

            $this->dataset["relationships"][] = array(
                "irelationship_id" => null, // to be filled in when this is saved
                "item_id" => null, // to be filled in when this is saved
                "first_parent_id" => $first_parent_id ? $first_parent_id : $originating_item_id,
                "immediate_parent_id" => $originating_item_id
            );
        }

        // Success. Item is duplicated; it is ready to be saved.
        return true;
    }

    /**
     * Fetch the current dataset in a format that is "loadable" via the loadData function.
     *
     * @param bool $fetch_dataset
     * @return array|bool
     */
    public function fetchLoadableData($fetch_dataset = true) {
        global $translate;
        if ($fetch_dataset) {
            if (!$this->item_id) {
                $this->addErrorMessage($translate->_("Can't fetch data without a valid item ID."));
                return false;
            }
            $this->fetchData();
        }
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Failed to fetch dataset."));
            return false;
        }
        $loadable = array();
        $loadable["item"] = $this->dataset["item"];
        $loadable["flag_response"] = array();
        $loadable["responses"] = array();
        $loadable["descriptors"] = array();
        $loadable["objectives"] = array();
        $ordinal = 0;
        foreach ($this->dataset["responses"] as $response) {
            $ordinal++;
            $loadable["flag_response"][$ordinal] = $response["flag_response"];
            $loadable["responses"][$ordinal] = $response["text"];
            $loadable["descriptors"][$ordinal] = $response["ardescriptor_id"];
        }
        if (is_array($this->dataset["objectives"])) {
            foreach ($this->dataset["objectives"] as $objective_id) {
                $loadable["objectives"][] = $objective_id;
            }
        }
        return $loadable;
    }

    /**
     * Take the current dataset and save it to the database.
     *
     * @return bool
     */
    public function saveData() {
        // Iterate through $this->dataset and commit relevant pieces to the DB
        // Store errors in datastructure
        // Remove records that are no longer relevant to this item, e.g. responses, descriptors, objectives.
        if (is_array($this->dataset) && !empty($this->dataset)) {
            if ($this->dataset["meta"]["item_id"]) {
                // Item already exists, so let's make it consistent with what we've validated.
                // That means we have to soft-delete anything that isn't in our new dataset.
                return $this->saveDatasetAsExistingItem();
            } else {
                // Item does not already exist, so let's create it.
                return $this->saveDatasetAsNewItem();
            }
        }
        return false;
    }

    /**
     * Load and validate data, store in the dataset.
     *
     * loadData will populate the internal dataset structure with the given data. If the item type has changed, then we
     * clear the existing records via soft delete. If the type hasn't changed, then sanity check the new configuration,
     * and store it for saving later.
     *
     * @param array $data
     * @param bool $validate
     * @return bool
     */
    public function loadData($data, $validate = true) {

        // Check for an ID, and check if the item is in the DB already (and fetch it)
        $existing_data = array();
        if (isset($data["item"]["item_id"]) && $data["item"]["item_id"]) {
            $this->setID($data["item"]["item_id"]);
            $this->buildDataset();
            $existing_data = $this->dataset;
        }

        // Load the supplied data into a new dataset structure.
        if (empty($existing_data)) {
            $new_dataset = $this->buildDatasetAsNewItem($data);
        } else {
            $new_dataset = $this->buildDatasetAsExistingItem($data, $existing_data);
        }
        if (!$new_dataset) {
            return false;
        }

        // Update the metadata after the load
        $new_dataset["meta"]["responses_count"] = count($new_dataset["responses"]);
        $new_dataset["meta"]["descriptor_count"] = count($new_dataset["descriptors"]);
        $new_dataset["meta"]["objectives_count"] = count($new_dataset["objectives"]);

        // Validate if specified
        if ($validate) {
            if ($this->validate($new_dataset)) {
                $this->dataset = $new_dataset;
            } else {
                return false; // Failed validation
            }
        } else {
            $this->dataset = $new_dataset;
        }
        return true;
    }

    /**
     * Fetch all related data points, return in a data structure.
     *
     * @param bool $cached
     * @return false|array
     */
    public function fetchData($cached = true) {
        if ($cached) {
            // Attempt to find a cached version of the dataset
            if ($this->isInStorage("item_dataset", $this->item_id)) {
                $this->dataset = $this->fetchFromStorage("item_dataset", $this->item_id);
            }
        }
        if (empty($this->dataset)) {
            $this->buildDataset();
        } else {
            if (!array_key_exists("is_stale", $this->dataset) || $this->dataset["is_stale"]) {
                $this->buildDataset();
            }
        }
        if ($cached) {
            $this->addToStorage("item_dataset", $this->dataset, $this->item_id);
        }
        return $this->dataset;
    }

    /**
     * Mark the current dataset as stale and remove it from global cache.
     */
    public function invalidateDataset() {
        $this->setStale();
        if ($this->item_id) {
            $this->removeFromStorage("item_dataset", $this->item_id);
        }
    }

    /**
     * Mark the current item as deleted.
     *
     * @return bool
     */
    public function delete() {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        if ($this->item_id) {
            if ($item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($this->item_id)) {
                $item->fromArray(
                    array(
                        "deleted_date" => time(),
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id
                    )
                );
                if ($item->update()) {
                    return true;
                } else {
                    $this->addErrorMessage($translate->_("Unable to delete an Item."));
                }
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to delete item without item id."));
        }
        return false;

    }

    /**
     * Set the item objectives for this item. This method forces the item to have the
     * given objectives; the $objectives array is a flat list of objective IDs that
     * override any existing objectives that are set.
     *
     * @param array $objectives
     * @return bool
     */
    public function setItemObjectives($objectives = array()) {
        global $translate;
        if (!$this->item_id) {
            $this->addErrorMessage($translate->_("Cannot set item objectives without item ID."));
            return false;
        }
        $item_data = $this->fetchData();
        if (empty($item_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch item dataset."));
            return false;
        }
        if (!is_array($objectives)) { // empty array is OK, but the param must be an array
            $this->addErrorMessage($translate->_("No objectives were specified."));
            return false;
        }
        // Whatever objectives that are given to us, we set.
        // If anything is not in the given list, then it will be deleted.
        // If the array is empty, then we're just going to clear all existing ones.

        // First, delete any objectives that aren't in the given list
        foreach ($item_data["objectives"] as $existing_objective) {
            if (in_array($existing_objective["objective_id"], $objectives)) {
                continue;
            }
            if (!Models_Assessments_Item_Objective::deleteByObjectiveIDItemID($existing_objective["objective_id"], $this->item_id)) {
                $this->addErrorMessage($translate->_("Failed to remove item objective."));
                return false;
            }
        }
        // Add anything that is missing
        foreach ($objectives as $new_objective) {
            // Check if we have this objective already
            $already_set = false;
            foreach ($item_data["objectives"] as $item_objective) {
                if ($item_objective["objective_id"] == $new_objective) {
                    $already_set = true;
                }
            }
            if ($already_set) {
                continue;
            }
            // It's not found, so let's create it.
            $new_item_record = new Models_Assessments_Item_Objective(
                array(
                    "item_id" => $this->item_id,
                    "objective_id" => $new_objective,
                    "created_date" => time(),
                    "created_by" => $this->actor_proxy_id
                )
            );
            if (!$new_item_record->insert()) {
                $this->addErrorMessage($translate->_("Failed to insert new item objective record."));
                return false;
            }
        }
        $this->setStale();
        $this->removeFromStorage("item_dataset", $this->item_id);
        return true;
    }

    //-- Static helper --//

    /**
     * Using the given itemtype ID, fetch the shortname of the associated itemtype.
     *
     * @param int $itemtype_id
     * @return null
     */
    public static function fetchItemtypeShortnameByItemtypeID($itemtype_id) {
        if ($itemtype_id) {
            if ($itemtype_record = Models_Assessments_Itemtype::fetchRowByID($itemtype_id)) {
                return $itemtype_record->getShortname();
            }
        }
        return null;
    }

    //-- Protected --//

    /**
     * Determine if this object can be edited.
     * If it is part of a form that has been delivered, then it is NOT editable.
     *
     * @return bool
     */
    protected function determineEditable() {

        // If we're explicitly told to not fetch metadata, we simply return true.
        if (!$this->determine_meta) {
            return true;
        }

        if (!$this->item_id) {
            return true; // No item ID, it's a new item, so it's editable.
        }

        // Check which forms use this item (this includes rubrics that use the item, since all rubric items are individually added to forms)
        $form_ids = Models_Assessments_Form_Element::fetchFormIDsByItemID($this->item_id);
        if (empty($form_ids)) {
            return true; // None found, so it's editable
        }

        // This item is a part of some forms, so let's find out if there are progress records for them
        $progress_ids = Models_Assessments_Progress_Response::fetchInProgressAndCompleteProgressResponseIDsByFormIDs($form_ids);
        if (!empty($progress_ids)) {
            return false; // There's some in-progress or complete data using a form that uses this item. That means it's not editable.
        }

        // There's no progress records, so let's check if there are any assessment tasks delivered, but not started.
        $dassessment_ids = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs($form_ids);
        if (!empty($dassessment_ids)) {
            // Assessment IDs were found, meaning assessment tasks were delivered that use this item. It is not editable.
            return false;
        }
        return true; //passed checks, it's editable
    }

    /**
     * Find the IDs of the rubrics and forms that use this item.
     *
     * @return array
     */
    protected function determineUsage() {
        // If we're explicitly told to not fetch metadata, we simply return empty array.
        if (!$this->determine_meta) {
            return array();
        }

        $in_use_by = array(
            "rubrics" => array(),
            "forms" => array(),
            "assessments" => array()
        );
        if ($this->item_id) {
            $in_use_by["rubrics"] = Models_Assessments_Rubric_Item::fetchRubricIDsByItemID($this->item_id);
            $in_use_by["forms"] = Models_Assessments_Form_Element::fetchFormIDsByItemID($this->item_id);
            if (is_array($in_use_by["forms"]) && !empty($in_use_by["forms"])) {
                $in_use_by["assessments"] = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs($in_use_by["forms"]);
            }
        }
        return $in_use_by;
    }

    /**
     * Using the internal item ID, fetch the shortname of the associated itemtype.
     *
     * @return null
     */
    protected function fetchItemtypeShortnameByItemID() {
        if ($this->item_id) {
            if ($item_record = Models_Assessments_Item::fetchRowByID($this->item_id)) {
                if ($itemtype_record = Models_Assessments_Itemtype::fetchRowByID($item_record->getItemtypeID())) {
                    return $itemtype_record->getShortname();
                }
            }
        }
        return null;
    }

    //-- Private --//

    /**
     * Validate the given data.
     *
     * @param array $data
     * @return bool
     */
    private function validate($data) {
        global $translate;

        if (!is_array($data) || empty($data)) {
            return false;
        }
        if (!$data["meta"]["itemtype_shortname"]) {
            $this->addErrorMessage($translate->_("Invalid item type."));
            return false;
        }

        $items_model = new Models_Assessments_Item();
        $comment_type_enum = $items_model->getCommentTypeEnumValues();

        // The given comment type is not a valid value
        if (!in_array($data["item"]["comment_type"], $comment_type_enum)) {
            $this->addErrorMessage($translate->_("Invalid comment type."));
            return false;
        }

        if ($this->canHaveResponses($data["meta"]["itemtype_shortname"])) {
            // If a scale is given, we can ignore any specified descriptors as we will inherit the ones from the scale.
            if ($data["item"]["rating_scale_id"]) {
                if (count($data["rating_scale_descriptors"]) == 0) {
                    $this->addErrorMessage($translate->_("The specified Rating Scale is invalid."));
                    return false;
                }
            } else { // If a rating scale is not specified, we have to verify that responses are supplied
                // Item type should have responses, but none were supplied.
                if (empty($data["responses"])) {
                    $this->addErrorMessage($translate->_("Responses are required for this item type."));
                    return false;
                }
            }
        } else {
            if (!empty($data["meta"]["in_use_by"]["rubrics"])) {
                $this->addErrorMessage($translate->_("The item type of this rubric can not be changed since this item is in use by one or more rubric(s)."));
                // Technically, it can, but we're disallowing changing from an item type that requires responses to one that doesn't.
                return false;
            }
        }

        // If there are any rubrics that use this item, check the descriptors of the new set, and refuse the change if they don't match
        if (!empty($data["meta"]["in_use_by"]["rubrics"])) {
            $rubric_id = end($data["meta"]["in_use_by"]["rubrics"]); // take a rubric off of the list. They /should/ be all the same, so let's fetch one and compare.
            $rubric_object = new Entrada_Assessments_Workers_Rubric($this->buildActorArray(array("rubric_id" => $rubric_id)));
            $rubric_data = $rubric_object->fetchData();
            if (empty($rubric_data)) {
                // This rubric is invalid, we can't perform the consistency check, so we have to fail
                application_log("error", "Rubric ID specified is invalid, unable to fetch rubric dataset (rubric_id = $rubric_id)");
                $this->addErrorMessage($translate->_("Failed to save item. Unable to verify associated descriptor integrity."));
                return false;
            }
            if (!empty($data["responses"]) && empty($data["descriptors"])) {
                $this->addErrorMessage($translate->_("This item requires response descriptors for each response."));
                return false;
            }
            if (!empty($data["responses"]) && count($data["responses"]) != count($rubric_data["descriptors"])) {
                $this->addErrorMessage($translate->_("The supplied number of responses does not match the required number of response descriptors."));
                return false;
            }
            if (!empty($data["descriptors"]) && count($data["descriptors"]) != count($rubric_data["descriptors"])) {
                $this->addErrorMessage($translate->_("The supplied number of response descriptors does not match the rubric(s) this item is attached to."));
                return false;
            }
            if (!empty($data["descriptors"])) {
                $i = 0;
                foreach ($data["descriptors"] as $desc) {
                    if ($rubric_data["descriptors"][$i]["ardescriptor_id"] != $desc["ardescriptor_id"]) {
                        $this->addErrorMessage($translate->_("The supplied response descriptors do not match the Rating Scale."));
                        return false;
                    }
                    $i++;
                }
            }
        }
        return true;
    }

    /**
     * Save the current dataset as a new item in the database.
     * Optionally save a cloned relationship.
     *
     * @return bool
     */
    private function saveDatasetAsNewItem() {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        if (empty($this->dataset)) {
            // Can't save empty dataset
            $this->addErrorMessage($translate->_("Unable to save new item with empty dataset."));
            return false;
        }

        // Save the item
        $item = new Models_Assessments_Item($this->dataset["item"]);
        if (!$item->insert()) {
            application_log("error", "Forms_Item:: Failed to insert new item.");
            $this->addErrorMessage($translate->_("Unable to save new item to the database."));
            return false;
        }

        $this->item_id = $item->getID();

        // Save responses
        if (is_array($this->dataset["responses"])) {
            foreach ($this->dataset["responses"] as &$response_data) {
                $new_response = new Models_Assessments_Item_Response(
                    array(
                        "order" => $response_data["order"],
                        "flag_response" => intval($response_data["flag_response"]),
                        "item_id" => $this->item_id,
                        "allow_html" => 0,
                        "ardescriptor_id" => $response_data["ardescriptor_id"],
                        "text" => $response_data["text"]
                    )
                );
                if (!$new_response->insert()) {
                    application_log("error", "Forms_Item:: Failed to insert new response. Data may be inconsistent.");
                    $this->addErrorMessage($translate->_("Unable to save new item response to the database."));
                    return false;
                }
                $response_data["iresponse_id"] = $new_response->getID();
            }
        }

        // Save current author
        $item_author = new Models_Assessments_Item_Author(
            array(
                "item_id" => $this->item_id,
                "author_type" => "proxy_id",
                "author_id" => $this->actor_proxy_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id
            )
        );
        if (!$item_author->insert()) {
            application_log("error", "Forms_Item:: Unable to add item author");
        }

        // Save any other authors given
        if (is_array($this->dataset["authors"])) {
            foreach ($this->dataset["authors"] as $author) {
                if ($author["author_id"] != $this->actor_proxy_id) {
                    $author["item_id"] = $this->item_id;
                    $author["created_date"] = time();
                    $author["created_by"] = $this->actor_proxy_id;
                    $new_author = new Models_Assessments_Item_Author($author);
                    if (!$new_author->insert()) {
                        application_log("error", "Forms_Item:: Unable to add additional item author");
                    }
                }
            }
        }

        // Save specified objectives
        if (is_array($this->dataset["objectives"])) {
            foreach ($this->dataset["objectives"] as $objective_data) {
                $item_objective = new Models_Assessments_Item_Objective(
                    array(
                        "item_id" => $this->item_id,
                        "created_date" => time(),
                        "created_by" => $this->actor_proxy_id,
                        "objective_id" => $objective_data["objective_id"],
                        "objective_metadata" => $objective_data["objective_metadata"]
                    )
                );
                if (!$item_objective->insert()) {
                    application_log("error", "Forms_Item:: Unable to attach objective to item.");
                }
            }
        }

        if (is_array($this->dataset["item_response_objectives"])) {
            foreach($this->dataset["responses"] as $order => $response) {
                foreach ($this->dataset["item_response_objectives"] as $item_response_objective) {
                    if ($item_response_objective["item_response_order"] == $order) {
                        $objective_response_model = new Models_Assessments_Item_Response_Objective(
                            array(
                                "irobjective_id" => NULL,
                                "iresponse_id" => $response["iresponse_id"],
                                "objective_id" => $item_response_objective["objective_id"],
                                "created_date" => time(),
                                "created_by" => $this->actor_proxy_id
                            )
                        );
                        if (!$objective_response_model->insert()) {
                            application_log("error", "Forms_Item:: Failed to insert new item response objective.");
                            $this->addErrorMessage($translate->_("Unable to save a new item response objective."));
                        }
                    }
                }
            }
        }

        // Save relationship when the item is cloned
        if (is_array($this->dataset["relationships"]) && !empty($this->dataset["relationships"])) {
            foreach ($this->dataset["relationships"] as $new_relationship_data) {
                if (!$new_relationship_data["irelationship_id"]) {
                    $new_relationship_data["item_id"] = $this->item_id;
                    $item_relationship = new Models_Assessments_Item_Relationship($new_relationship_data);
                    if (!$item_relationship->insert()) {
                        add_error($translate->_("Failed to insert the Item Relationship after cloning."));
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save the current dataset to the database, updating the existing item.
     *
     * @return bool
     */
    private function saveDatasetAsExistingItem() {
        global $translate;

        if (empty($this->dataset)) {
            // Can't save empty dataset
            $this->addErrorMessage($translate->_("Unable to update item with empty dataset."));
            return false;
        }

        $item_id = $this->dataset["meta"]["item_id"];

        // If there are items to prune, we do so.
        foreach ($this->dataset["meta"]["property_cleanup"] as $property_type => $ids) {
            switch ($property_type) {
                case "responses":
                    foreach ($ids as $delete_id) {
                        if (!Models_Assessments_Item_Response::deleteByID($delete_id)) { // mark the extraneous as deleted
                            application_log("error", "Unable to mark response as deleted for id '$delete_id'");
                        }
                    }
                    break;
                case "objectives":
                    foreach ($ids as $delete_id) {
                        if (!Models_Assessments_Item_Objective::deleteByID($delete_id)) { // mark the extraneous as deleted
                            application_log("error", "Unable to mark item objective as deleted for id '$delete_id'");
                        }
                    }
                    break;
                case "authors":
                    foreach ($ids as $delete_id) {
                        if (!Models_Assessments_Item_Author::deleteByID($delete_id)) { // mark the extraneous as deleted
                            application_log("error", "Unable to mark author as deleted for id '$delete_id'");
                        }
                    }
                    break;
            }
        }

        // Update the item record
        $item = new Models_Assessments_Item($this->dataset["item"]);
        if (!$item->update()) {
            application_log("error", "Forms_Item:: Failed to update existing item (item id = '{$this->dataset["meta"]["item_id"]}'.");
            $this->addErrorMessage($translate->_("Failed to update item."));
            return false;
        }

        $this->item_id = $item_id;

        // Save any new or updated responses
        if (is_array($this->dataset["responses"])) {
            foreach ($this->dataset["responses"] as $response_data) {
                if (isset($response_data["iresponse_id"])) {

                    // Existing response, so let's update it
                    $existing_response = new Models_Assessments_Item_Response($response_data);
                    if (!$existing_response->update()) {
                        application_log("error", "Forms_Item::saveData: Failed to update existing item response ({$existing_response->getID()})");
                    }
                } else {

                    // New response
                    $new_response = new Models_Assessments_Item_Response(
                        array(
                            "order" => $response_data["order"],
                            "flag_response" => $response_data["flag_response"],
                            "item_id" => $item->getID(),
                            "allow_html" => 0, // When does this get used?
                            "ardescriptor_id" => $response_data["ardescriptor_id"],
                            "text" => $response_data["text"]
                        )
                    );

                    if (!$new_response->insert()) {
                        application_log("error", "Forms_Item::saveData: Failed to insert new or update response. Data may be inconsistent.");
                        $this->addErrorMessage($translate->_("Unable to save item response to the database."));
                    }
                }
            }
        }

        // Save any new objectives
        if (is_array($this->dataset["objectives"])) {
            foreach ($this->dataset["objectives"] as $objective) {
                if (!isset($objective["aiobjective_id"])) {
                    // This is a new one, so let's create it
                    $new_objective = new Models_Assessments_Item_Objective($objective);
                    if (!$new_objective->insert()) {
                        application_log("error", "Unable to save new item objective record for item id = '$item_id'");
                    }
                }
            }
        }

        if (is_array($this->dataset["item_response_objectives"])) {
            // Clear any existing item response objectives
            foreach ($this->dataset["responses"] as $order => $response) {
                if (!Models_Assessments_Item_Response_Objective::deleteByIresponseID($response["iresponse_id"], $this->actor_proxy_id)) { // mark the extraneous as deleted
                    application_log("error", "Unable to mark item response objective as deleted for id '{$response["iresponse_id"]}'");
                }
                foreach ($this->dataset["item_response_objectives"] as $item_response_objective) {
                    if ($item_response_objective["item_response_order"] == $order) {
                        $objective_response_model = new Models_Assessments_Item_Response_Objective(
                            array(
                                "irobjective_id" => NULL,
                                "iresponse_id" => $response["iresponse_id"],
                                "objective_id" => $item_response_objective["objective_id"],
                                "created_date" => time(),
                                "created_by" => $this->actor_proxy_id
                            )
                        );
                        if (!$objective_response_model->insert()) {
                            application_log("error", "Forms_Item:: Failed to insert new item response objective.");
                            $this->addErrorMessage($translate->_("Unable to save a new item response objective."));
                        }
                    }
                }
            }
        }

        // Save any new authors
        if (is_array($this->dataset["authors"])) {
            foreach ($this->dataset["authors"] as $author) {
                if (!isset($author["aiauthor_id"])) {
                    // This is a new one, so let's create it
                    $new_author = new Models_Assessments_Item_Author($author);
                    if (!$new_author->insert()) {
                        application_log("error", "Unable to save new item author record for item id = '$item_id'");
                    }
                }
            }
        }

        // We intentionally ignore relationships here. They can only be added at creation time, and never modified via any UI.

        // Saved. Cleanup cache.
        $this->removeFromStorage("item_dataset", $this->item_id);
        return true;
    }

    /**
     * Load the given data into a dataset structure, treating it as a new item (i.e., one that does not already exist in the database).
     * This method returns the new dataset structure, and does not replace the existing one, if there is one.
     *
     * Dataset limits do not apply here since the data is given to us.
     *
     * @param array $data_to_load
     * @return bool|array (the new dataset)
     */
    private function buildDatasetAsNewItem($data_to_load) {
        global $translate;

        if (!$this->validateActor()) {
            return false;
        }

        /* This is a brand new item. */

        $new_dataset = $this->buildDefaultItemStructure();

        $new_dataset["item"]["organisation_id"] = $this->actor_organisation_id;
        $new_dataset["item"]["created_date"] = time();
        $new_dataset["item"]["created_by"] = $this->actor_proxy_id;
        $new_dataset["item"]["updated_date"] = time();
        $new_dataset["item"]["updated_by"] = $this->actor_proxy_id;
        $new_dataset["item"]["deleted_date"] = null;
        $new_dataset["item"]["item_id"] = null;
        $new_dataset["item"]["one45_element_id"] = null;
        $new_dataset["item"]["itemtype_id"] = $data_to_load["item"]["itemtype_id"];
        $new_dataset["item"]["item_code"] = $data_to_load["item"]["item_code"];
        $new_dataset["item"]["item_text"] = $data_to_load["item"]["item_text"];
        $new_dataset["item"]["item_description"] = $data_to_load["item"]["item_description"];
        $new_dataset["item"]["mandatory"] = $data_to_load["item"]["mandatory"];
        $new_dataset["item"]["comment_type"] = $data_to_load["item"]["comment_type"];
        $new_dataset["item"]["attributes"] = Entrada_Utilities::arrayValueOrDefault($data_to_load["item"], "attributes");
        $new_dataset["item"]["allow_default"] = $data_to_load["item"]["allow_default"];
        $new_dataset["item"]["default_response"] = $data_to_load["item"]["default_response"];
        $new_dataset["item"]["hide_from_index"] = Entrada_Utilities::arrayValueOrDefault($data_to_load["item"], "hide_from_index", 0);
        $new_dataset["item"]["rating_scale_id"] = null; // gets reset if applicable

        // Convert attributes raw JSON field to its own array entry in the dataset
        $attributes = @json_decode($new_dataset["item"]["attributes"], true);
        $new_dataset["item_attributes"] = is_array($attributes) ? $attributes : array();

        // Item grouping should default null for new items, unless otherwise specified.
        $new_dataset["item"]["item_group_id"] = isset($data_to_load["item"]["item_group_id"]) ? $data_to_load["item"]["item_group_id"] : null;

        // Determine the shortname based on the given itemtype ID
        $new_dataset["meta"]["itemtype_shortname"] = $this->fetchItemtypeShortnameByItemtypeID($data_to_load["item"]["itemtype_id"]);

        if ($this->canHaveResponses($new_dataset["meta"]["itemtype_shortname"])) {
            $new_dataset["item"]["rating_scale_id"] = $data_to_load["item"]["rating_scale_id"];
            $order = 1;
            if (isset($data_to_load["responses"]) && is_array($data_to_load["responses"])) {
                foreach ($data_to_load["responses"] as $i => $load_response) {
                    $new_dataset["responses"][$i]["order"] = $order;
                    $new_dataset["responses"][$i]["text"] = $load_response;
                    $new_dataset["responses"][$i]["flag_response"] = isset($data_to_load["flag_response"][$i]) ? intval($data_to_load["flag_response"][$i]) : 0;
                    $new_dataset["responses"][$i]["ardescriptor_id"] = isset($data_to_load["descriptors"][$i]) ? $data_to_load["descriptors"][$i] : null;
                    $order++;
                }
            }
            if ($new_dataset["item"]["rating_scale_id"]) {
                $scale = new Entrada_Assessments_Workers_Scale($this->buildActorArray(array("rating_scale_id" => $new_dataset["item"]["rating_scale_id"])));
                $scale_data = $scale->fetchData();
                if (!empty($scale_data)) {
                    // Store scale record, type, and descriptors
                    $new_dataset["rating_scale"] = $scale_data["rating_scale"];
                    $new_dataset["rating_scale_type"] = $scale_data["rating_scale_type"];
                    $new_dataset["rating_scale_descriptors"] = $scale_data["descriptors"];

                    // Since we're using a scale, set the descriptors of the item to those of the scale, and update the given responses to use the scale's descriptor IDs
                    $i = 1; // Responses always start at 1, and go to n sequentially
                    foreach ($scale_data["descriptors"] as $ardescriptor_id => $descriptor) {
                        $new_dataset["descriptors"][$ardescriptor_id] = $descriptor;
                        $new_dataset["responses"][$i]["order"] = $i;
                        $new_dataset["responses"][$i]["flag_response"] = isset($data_to_load["flag_response"][$i]) ? intval($data_to_load["flag_response"][$i]) : 0;
                        $new_dataset["responses"][$i]["text"] = isset($data_to_load["responses"][$i]) ? $data_to_load["responses"][$i] : "";
                        $new_dataset["responses"][$i]["ardescriptor_id"] = $ardescriptor_id;
                        $i++;
                    }

                    // Sanity check the responses vs the scale.
                    if (count($new_dataset["responses"]) != count($scale_data["descriptors"])) {
                        $this->addErrorMessage($translate->_("The number of responses does not match the selected rating scale."));
                        $new_dataset["item"]["rating_scale_id"] = null;
                        return false;
                    }

                    $array_item = reset($scale_data["responses"]);
                    foreach ($new_dataset["responses"] as $i => $response_check) {
                        if ($response_check["ardescriptor_id"] != $array_item["ardescriptor_id"]) {
                            $this->addErrorMessage($translate->_("The supplied item response descriptors do not match the specified rating scale."));
                            $new_dataset["item"]["rating_scale_id"] = null;
                            return false;
                        }
                        $array_item = next($scale_data["responses"]);
                    }

                } else {
                    $this->addErrorMessage($translate->_("Invalid Rating Scale specified for item."));
                    return false;
                }
            } else {

                // If there isn't a scale set, use the specified descriptors (if any)
                if (isset($data_to_load["descriptors"]) && is_array($data_to_load["descriptors"])) {
                    foreach ($data_to_load["descriptors"] as $ardescriptor_id) {
                        $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($ardescriptor_id);
                        if ($descriptor) {
                            $new_dataset["descriptors"][$ardescriptor_id] = $descriptor->toArray();
                        } else {
                            // add error, descriptor not found
                            application_log("error", "Forms_Item::loadData: Response descriptor not found");
                        }
                    }
                }
            }
        }

        if ($this->canHaveObjectives($new_dataset["meta"]["itemtype_shortname"])) {

            if (isset($data_to_load["objectives"]) && is_array($data_to_load["objectives"])) {
                foreach ($data_to_load["objectives"] as $objective_id) {
                    $tree_node_ids = (isset($data_to_load["objectives_tree_ids"][$objective_id])) ? $data_to_load["objectives_tree_ids"][$objective_id] : null;

                    if ($tree_node_ids && is_array($tree_node_ids) && count($tree_node_ids)) {
                        foreach ($tree_node_ids as $tree_node_id) {
                            $objective_breadcrumb = ($tree_node_id && isset($data_to_load["objectives_breadcrumbs"][$tree_node_id])) ? $data_to_load["objectives_breadcrumbs"][$tree_node_id] : null;
                            $metadata = json_encode(array("tree_node_id" => $tree_node_id, "breadcrumb" => $objective_breadcrumb));
                            $new_dataset["objectives"][] = array(
                                "objective_id" => $objective_id,
                                "objective_metadata" => $metadata,
                                "created_by" => $this->actor_proxy_id,
                                "created_date" => time(),
                                "item_id" => null
                            );
                        }
                    } else {
                        $new_dataset["objectives"][] = array(
                            "objective_id" => $objective_id,
                            "objective_metadata" => null,
                            "created_by" => $this->actor_proxy_id,
                            "created_date" => time(),
                            "item_id" => null
                        );
                    }
                }
            }

            $new_dataset["item_response_objectives"] = Entrada_Utilities::arrayValueOrDefault($data_to_load, "item_response_objectives", array());

        }
        return $new_dataset;
    }

    /**
     * Load the given data, comparing it against the existing data specified, and return an updated dataset structure.
     * This does not affect the internal dataset property, it simply creates a new dataset that can be used to replace it.
     *
     * Contained in the dataset is all of the item related responses and metadata, as well as what items to prune (applied when saveData is called).
     *
     * Dataset limits do not apply here since the data is given to us.
     *
     * @param array $data_to_load
     * @param array $existing_data
     * @return bool|array (The new dataset)
     */
    private function buildDatasetAsExistingItem($data_to_load, $existing_data) {
        global $translate;

        if (!$this->validateActor()) {
            return false;
        }

        /* Data exists already, so match the required parts, mark extraneous to be deleted. */

        $new_dataset = $this->buildDefaultItemStructure();

        $item_id = $new_dataset["meta"]["item_id"] = $existing_data["item"]["item_id"];
        $new_dataset["meta"]["itemtype_shortname"] = $this->fetchItemtypeShortnameByItemtypeID($data_to_load["item"]["itemtype_id"]); // Set the new itemtype shortname

        $new_dataset["item"] = $existing_data["item"];
        $new_dataset["item"]["updated_date"] = time();
        $new_dataset["item"]["updated_by"] = $this->actor_proxy_id;
        $new_dataset["item"]["itemtype_id"] = $data_to_load["item"]["itemtype_id"];
        $new_dataset["item"]["item_code"] = $data_to_load["item"]["item_code"];
        $new_dataset["item"]["item_text"] = $data_to_load["item"]["item_text"];
        $new_dataset["item"]["item_description"] = $data_to_load["item"]["item_description"];
        $new_dataset["item"]["mandatory"] = $data_to_load["item"]["mandatory"];
        $new_dataset["item"]["comment_type"] = $data_to_load["item"]["comment_type"];
        $new_dataset["item"]["hide_from_index"] = Entrada_Utilities::arrayValueOrDefault($data_to_load["item"], "hide_from_index", 0);
        $new_dataset["item"]["attributes"] = $data_to_load["item"]["attributes"];
        $new_dataset["item"]["allow_default"] = $data_to_load["item"]["allow_default"];
        $new_dataset["item"]["default_response"] = $data_to_load["item"]["default_response"];
        $new_dataset["item"]["rating_scale_id"] = null;
        $new_dataset["item"]["attributes"] = Entrada_Utilities::arrayValueOrDefault($data_to_load["item"], "attributes");
        $new_dataset["item_response_objectives"] = Entrada_Utilities::arrayValueOrDefault($data_to_load, "item_response_objectives", array());

        // Convert attributes raw JSON field to its own array entry in the dataset
        $attributes = @json_decode($new_dataset["item"]["attributes"], true);
        $new_dataset["item_attributes"] = is_array($attributes) ? $attributes : array();

        // Grouping ID should be inherited from the previous record, and only optionally overwritten.
        $new_dataset["item"]["item_group_id"] = array_key_exists("item_group_id", $data_to_load["item"]) ?
            $data_to_load["item"]["item_group_id"] :
            $existing_data["item"]["item_group_id"];

        if ($this->canHaveResponses($new_dataset["meta"]["itemtype_shortname"])) {
            $new_dataset["item"]["rating_scale_id"] = $data_to_load["item"]["rating_scale_id"];
            // We clean up the the existing items if the scale changes (whole-sale record replacement when this happens)
            if ($existing_data["item"]["rating_scale_id"] != $data_to_load["item"]["rating_scale_id"]) {
                foreach ($existing_data["responses"] as $existing_response) {
                    // Mark all for deletion
                    $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["iresponse_id"]] = $existing_response["iresponse_id"];
                }
            }

            if ($new_dataset["item"]["rating_scale_id"]) {
                $scale = new Entrada_Assessments_Workers_Scale($this->buildActorArray(array("rating_scale_id" => $new_dataset["item"]["rating_scale_id"])));
                $scale_data = $scale->fetchData();
                if (!empty($scale_data)) {
                    // Store scale record, type, and descriptors
                    $new_dataset["rating_scale"] = $scale_data["rating_scale"];
                    $new_dataset["rating_scale_type"] = $scale_data["rating_scale_type"];
                    $new_dataset["rating_scale_descriptors"] = $scale_data["descriptors"];
                }
            }

            // Load the new responses
            if (!empty($data_to_load["responses"])) {
                $order = 1;

                // Store all the given responses
                foreach ($data_to_load["responses"] as $i => $response) {
                    $new_dataset["responses"][$i]["order"] = $order;
                    $new_dataset["responses"][$i]["text"] = $response;
                    $new_dataset["responses"][$i]["flag_response"] = isset($data_to_load["flag_response"][$i]) ? intval($data_to_load["flag_response"][$i]) : 0;
                    $new_dataset["responses"][$i]["ardescriptor_id"] = isset($data_to_load["descriptors"][$i]) ? $data_to_load["descriptors"][$i] : null;
                    $order++;
                }
                // Has duplicate descriptors?
                $has_duplicates = false;
                foreach ($new_dataset["responses"] as $i => $response_data_haystack) {
                    if (!$response_data_haystack["ardescriptor_id"]) {
                        continue;
                    }
                    foreach ($new_dataset["responses"] as $j => $response_data_needles) {
                        if ($i != $j) {
                            if ($response_data_needles["ardescriptor_id"] == $response_data_haystack["ardescriptor_id"]) {
                                $has_duplicates = true;
                            }
                        }
                    }
                }

                if ($has_duplicates) {
                    // There are duplicate descriptors, so we can't reliably update the ordering of existing items.
                    // We must clear all responses and make new ones.
                    foreach ($existing_data["responses"] as $existing_response) {
                        // Mark all for deletion
                        $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["iresponse_id"]] = $existing_response["iresponse_id"];
                    }

                } else {

                    // There are no duplicate descriptors, so we can examine the existing data set and reorder as necessary.
                    foreach ($new_dataset["responses"] as $i => $response_data) {
                        // Search the existing data for these responses.
                        foreach ($existing_data["responses"] as $iresponse_id => $load_response) {
                            if ($response_data["text"] == $load_response["text"] &&
                                $response_data["ardescriptor_id"] == $load_response["ardescriptor_id"]) {
                                // Found the exact item.

                                // Preserve new ordering
                                $new_order = $response_data["order"];
                                $new_flagging = isset($response_data["flag_response"]) ? intval($response_data["flag_response"]) : 0;

                                // Keep this
                                $new_dataset["responses"][$i] = $load_response;

                                // Update the response order
                                $new_dataset["responses"][$i]["order"] = $new_order;
                                $new_dataset["responses"][$i]["flag_response"] = $new_flagging;
                            }
                        }
                    }

                    // If there are responses in the database that do not match our current dataset, then mark them for deletion
                    foreach ($existing_data["responses"] as $existing_response) {
                        $found = false;
                        foreach ($new_dataset["responses"] as $new_response) {
                            if (isset($new_response["iresponse_id"])) { // Check for an existing response (item_struct can contain new items as well as old ones)
                                if ($existing_response["iresponse_id"] == $new_response["iresponse_id"]) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            // The existing response is not in the new set, so mark it for deletion
                            $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["iresponse_id"]] = $existing_response["iresponse_id"];
                        }
                    }
                }
            }

            // Keep new descriptors. In this case, we don't really care about the previous descriptors, we just save the new ones. There's no related table
            // that contains this information, as it is included on the item response record.
            if (!empty($data_to_load["descriptors"])) {
                foreach ($data_to_load["descriptors"] as $i => $new_descriptor) {
                    if (isset($existing_data["descriptors"][$new_descriptor])) {
                        $new_dataset["descriptors"][$new_descriptor] = $existing_data["descriptors"][$new_descriptor];
                    } else {
                        if ($descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($new_descriptor)) {
                            $new_dataset["descriptors"][$descriptor->getID()] = $descriptor->toArray();
                        }
                    }
                }
            }

            // If a rating scale is specified, we override the given response descriptors with those of the scale.
            if ($new_dataset["item"]["rating_scale_id"] && $existing_data["item"]["rating_scale_id"] != $data_to_load["item"]["rating_scale_id"]) {
                if (!empty($scale_data)) {
                    // Since we're using a scale, set the descriptors of the item to those of the scale, and update the given responses to use the scale's descriptor IDs
                    // We're overriding the input data, so that the logic below can update the new dataset as appropriate for all circumstances.
                    $i = 1;
                    foreach ($scale_data["descriptors"] as $ardescriptor_id => $descriptor) {
                        $new_dataset["descriptors"][$ardescriptor_id] = $descriptor;
                        $new_dataset["responses"][$i]["order"] = $i;
                        $new_dataset["responses"][$i]["text"] = isset($data_to_load["responses"][$i]) ? $data_to_load["responses"][$i] : "";
                        $new_dataset["responses"][$i]["ardescriptor_id"] = $ardescriptor_id;
                        // NOTE: We use the existing flagging
                        $i++;
                    }

                    // Sanity check the responses vs the scale (if using a scale)
                    if (count($new_dataset["responses"]) != count($scale_data["descriptors"])) {
                        $this->addErrorMessage($translate->_("The number of responses does not match the selected rating scale."));
                        $new_dataset["item"]["rating_scale_id"] = null;
                        return false;
                    }
                    $array_item = reset($scale_data["responses"]);
                    foreach ($new_dataset["responses"] as $i => $response_check) {
                        if ($response_check["ardescriptor_id"] != $array_item["ardescriptor_id"]) {
                            $this->addErrorMessage($translate->_("The supplied item response descriptors do not match the specified rating scale."));
                            $new_dataset["item"]["rating_scale_id"] = null;
                            return false;
                        }
                        $array_item = next($scale_data["responses"]);
                    }

                } else {
                    $this->addErrorMessage($translate->_("Invalid Rating Scale specified for item."));
                    return false;
                }
            }

        } else { // This item type does not support responses

            // If there are responses currently in the database, but this item type doesn't support responses, we tag them to be removed.
            foreach ($existing_data["responses"] as $existing_response) {
                $new_dataset["meta"]["property_cleanup"]["responses"][$existing_response["iresponse_id"]] = $existing_response["iresponse_id"];
            }
        }

        // Keep existing authors. (We let an external API call modify authorship).
        foreach ($existing_data["authors"] as $i => $load_authors) {
            $new_dataset["authors"][$i] = $load_authors;
        }

        // Fetch new objectives, prune old.
        if ($this->canHaveObjectives($new_dataset["meta"]["itemtype_shortname"])) {

            if (!empty($data_to_load["objectives"])) {

                // Objectives were given, so we store them.
                // Use existing records where possible.
                foreach ($data_to_load["objectives"] as $new_objective_id) {
                    $tree_node_ids = (isset($data_to_load["objectives_tree_ids"][$new_objective_id])) ? $data_to_load["objectives_tree_ids"][$new_objective_id] : null;

                    if ($tree_node_ids && is_array($tree_node_ids) && count($tree_node_ids)) {
                        foreach ($tree_node_ids as $tree_node_id) {
                            // Search the existing data for these objectives.
                            $found = false;
                            foreach ($existing_data["objectives"] as $aiobjective_id => $objective_data) {
                                $metadata = json_decode($objective_data["objective_metadata"]);
                                $objective_tree_node_id = isset($metadata->tree_node_id) ? $metadata->tree_node_id : null;
                                if ($new_objective_id == $objective_data["objective_id"] && $objective_tree_node_id == $tree_node_id) {
                                    // Found this objective in our existing dataset.
                                    $new_dataset["objectives"][] = $objective_data;
                                    $found = true;
                                }
                            }
                            if (!$found) {
                                $objective_breadcrumb = ($tree_node_id && isset($data_to_load["objectives_breadcrumbs"][$tree_node_id])) ? $data_to_load["objectives_breadcrumbs"][$tree_node_id] : null;
                                $metadata = json_encode(array("tree_node_id" => $tree_node_id, "breadcrumb" => $objective_breadcrumb));
                                $new_dataset["objectives"][] = array(
                                    "item_id" => $item_id,
                                    "objective_id" => $new_objective_id,
                                    "objective_metadata" => $metadata,
                                    "created_by" => $this->actor_proxy_id,
                                    "created_date" => time()
                                );
                            }
                        }
                    } else {
                        // Search the existing data for these objectives.
                        $found = false;
                        foreach ($existing_data["objectives"] as $aiobjective_id => $objective_data) {
                            if ($new_objective_id == $objective_data["objective_id"]) {
                                // Found this objective in our existing dataset.
                                $new_dataset["objectives"][] = $objective_data;
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $new_dataset["objectives"][] = array(
                                "item_id" => $item_id,
                                "objective_id" => $new_objective_id,
                                "objective_metadata" => null,
                                "created_by" => $this->actor_proxy_id,
                                "created_date" => time()
                            );
                        }
                    }

                }

            }

            // Prune ones that aren't in our new dataset
            foreach ($existing_data["objectives"] as $existing_objective) {
                $found = false;

                $tree_node_id = 0;

                if ($existing_objective["objective_metadata"]) {
                    $metadata = json_decode($existing_objective["objective_metadata"]);
                    $tree_node_id = isset($metadata->tree_node_id) ? $metadata->tree_node_id : null;
                }

                if ($new_dataset["objectives"]) {
                    foreach ($new_dataset["objectives"] as $new_objective_data) {
                        if ($tree_node_id) {
                            $node_collection = (isset($data_to_load["objectives_tree_ids"][$existing_objective["objective_id"]])) ? $data_to_load["objectives_tree_ids"][$existing_objective["objective_id"]] : array();
                            if ($existing_objective["objective_id"] == $new_objective_data["objective_id"] && in_array($tree_node_id, $node_collection)) {
                                $found = true;
                            }
                        } else {
                            if ($existing_objective["objective_id"] == $new_objective_data["objective_id"]) {
                                $found = true;
                            }
                        }
                    }
                }
                if (!$found) {
                    $new_dataset["meta"]["property_cleanup"]["objectives"][$existing_objective["aiobjective_id"]] = $existing_objective["aiobjective_id"];
                }
            }

        } else { // Can't have objectives

            // This item doesn't support objectives, so let's prune all existing ones.
            if (!empty($existing_data["objectives"])) {
                foreach ($existing_data["objectives"] as $existing_objective) {
                    $new_dataset["meta"]["property_cleanup"]["objectives"][$existing_objective["aiobjective_id"]] = $existing_objective["aiobjective_id"];
                }
            }
        }

        // Keep relationships (this is only populated on clone and can't be modified via any UI)
        $new_dataset["relationships"] = $existing_data["relationships"];
        return $new_dataset;
    }

    /**
     * Assemble the related data for this item. Fetches data based on the internal property item_id.
     * Dataset limits apply here.
     *
     * @return bool
     */
    private function buildDataset() {
        global $translate;

        if (!$this->item_id) {
            application_log("error", "fetchItemData: Unable to fetch item without ID");
            $this->addErrorMessage($translate->_("Please specify and item identifier."));
            return false;
        }

        $item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($this->item_id);
        if (!$item) {
            // Invalid item ID
            application_log("error", "fetchItemData: Invalid item ID (form record doesn't exist)");
            $this->addErrorMessage($translate->_("Item not found."));
            return false;
        }

        $item_data = $this->buildDefaultItemStructure();
        $item_data["meta"]["item_id"] = $this->item_id;

        // Store base item record
        $item_data["item"] = $item->toArray();

        // Convert attributes raw JSON field to its own array entry in the dataset
        $attributes = @json_decode($item_data["item"]["attributes"], true);
        $item_data["item_attributes"] = is_array($attributes) ? $attributes : array();

        if (empty($this->limit_dataset) || in_array("rating_scale", $this->limit_dataset)) {
            // Store the rating scale and type
            if ($item_data["item"]["rating_scale_id"]) {
                // Fetch the record.
                if ($rating_scale_record = Models_Assessments_RatingScale::fetchRowByIDIncludeDeleted($item_data["item"]["rating_scale_id"])) {
                    // Store scale record
                    $item_data["rating_scale"] = $rating_scale_record->toArray();

                    // Store type
                    $rating_scale_type_id = $rating_scale_record->getRatingScaleType();
                    if ($scale_type_record = Models_Assessments_RatingScale_Type::fetchRowByIDIncludeDeleted($rating_scale_type_id)) {
                        $item_data["rating_scale_type"] = $scale_type_record->toArray();
                    }

                    // Load scale descriptors
                    $scale_descriptors = array();
                    if ($scale_responses = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleID($item_data["item"]["rating_scale_id"])) {
                        $scale_descriptors = array_map(function ($p) {
                            return $p->toArray();
                        }, $scale_responses);
                    }
                    $item_data["rating_scale_descriptors"] = $scale_descriptors;
                }
            }
        }

        if (empty($this->limit_dataset) || in_array("authors", $this->limit_dataset)) {
            // Store authors
            // NOTE: actor_organisation_id might be NULL, so this would return false. This means that the item authors won't be visible to the caller.
            $item_authors = Models_Assessments_Item_Author::fetchAllByItemID($this->item_id, $this->actor_organisation_id);
            $authors = array();
            if (is_array($item_authors)) {
                foreach ($item_authors as $author) {
                    $authors[] = $author->toArray();
                }
            }
            $item_data["authors"] = $authors;
        }

        if (empty($this->limit_dataset) || in_array("responses", $this->limit_dataset)) {
            // Store possible responses
            if ($responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($this->item_id)) {
                if (is_array($responses)) {
                    foreach ($responses as $response) {
                        $item_data["responses"][$response->getID()] = $response->toArray();

                        // Check for descriptors
                        if ($this->isInStorage("response-descriptors", $response->getARDescriptorID())) {
                            $descriptor = $this->fetchFromStorage("response-descriptors", $response->getARDescriptorID());
                        } else {
                            $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($response->getARDescriptorID());
                            $this->addToStorage("response-descriptors", $descriptor, $response->getARDescriptorID());
                        }
                        if ($descriptor) {
                            if (!isset($item_data["descriptors"][$descriptor->getID()])) {
                                $item_data["descriptors"][$descriptor->getID()] = $descriptor->toArray();
                            }
                        }
                    }
                }
            }
        }

        // Fetch the associated tags for this item
        if (empty($this->limit_dataset) || in_array("objectives", $this->limit_dataset)) {
            if ($existing_item_objectives = Models_Assessments_Item_Objective::fetchAllRecordsByItemID($this->item_id)) {
                if (is_array($existing_item_objectives)) {
                    foreach ($existing_item_objectives as $objective) {
                        $item_data["objectives"][$objective->getID()] = $objective->toArray();
                        $objective_metadata = json_decode($objective->getObjectiveMetadata());
                        // Keep track of the tree node id if any
                        if (isset($objective_metadata->tree_node_id)) {
                            $item_data["objectives_tree_ids"][$objective->getObjectiveID()] = $objective_metadata->tree_node_id;
                        }
                        // Store the name as well
                        if ($single_objective = Models_Objective::fetchRow($objective->getObjectiveID())) {
                            $item_data["objective_names"][$objective->getObjectiveID()] = $single_objective->getName();
                        } else {
                            $item_data["objective_names"][$objective->getObjectiveID()] = ""; // couldn't find
                        }
                    }
                }
            }
        }

        if (empty($this->limit_dataset) || in_array("relationships", $this->limit_dataset)) {
            // Fetch any relationships
            if ($item_relationships = Models_Assessments_Item_Relationship::fetchRowByItemID($this->item_id)) {
                if (is_array($item_relationships)) {
                    foreach ($item_relationships as $relationship) {
                        $item_data["relationships"][$relationship->getID()] = $relationship->toArray();
                    }
                }
            }
        }

        $item_data["meta"]["responses_count"] = count($item_data["responses"]);
        $item_data["meta"]["descriptor_count"] = count($item_data["descriptors"]);
        $item_data["meta"]["objectives_count"] = count($item_data["objectives"]);
        $this->dataset = $item_data;
        return true;
    }

    /**
     * Create the default dataset structure.
     *
     * @return array
     */
    private function buildDefaultItemStructure() {
        $item_data = array();
        $item_data["is_stale"] = false;
        $item_data["meta"] = array();
        $item_data["meta"]["item_id"] = $this->item_id;
        $item_data["meta"]["itemtype_shortname"] = $this->fetchItemtypeShortnameByItemID();
        $item_data["meta"]["descriptor_count"] = 0;
        $item_data["meta"]["responses_count"] = 0;
        $item_data["meta"]["objectives_count"] = 0;
        $item_data["meta"]["is_editable"] = $this->determineEditable();
        $item_data["meta"]["in_use_by"] = $this->determineUsage();
        $item_data["meta"]["property_cleanup"] = array("responses" => array(), "objectives" => array(), "authors" => array());
        $item_data["item"] = array();
        $item_data["item_attributes"] = array();
        $item_data["rating_scale"] = array();
        $item_data["rating_scale_type"] = array();
        $item_data["authors"] = array();
        $item_data["responses"] = array();
        $item_data["descriptors"] = array();
        $item_data["rating_scale_descriptors"] = array();
        $item_data["objectives"] = array();
        $item_data["objective_names"] = array();
        $item_data["objectives_tree_ids"] = array();
        $item_data["item_response_objectives"] = array();
        $item_data["relationships"] = array();
        return $item_data;
    }
}