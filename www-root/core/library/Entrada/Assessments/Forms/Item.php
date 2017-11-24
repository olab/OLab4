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
class Entrada_Assessments_Forms_Item extends Entrada_Assessments_Forms_Base {

    protected $item_id = null;

    public function getID() {
        return $this->item_id;
    }

    public function setID($id) {
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
                return true;
            case "free_text":
            case "date":
                return false;
            default:
                return true;
        }
    }

    /**
     * Return whether this form object is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->item_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $count_in_use = count($this->dataset["meta"]["in_use_by"]["rubrics"]);
            $count_in_use += count($this->dataset["meta"]["in_use_by"]["forms"]);
            $count_in_use += count($this->dataset["meta"]["in_use_by"]["assessments"]);
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
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            return $this->dataset["meta"]["is_editable"];
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
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $in_use_by = $this->dataset["meta"]["in_use_by"];
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
        if (!$this->item_id) {
            // Can't dupe without item ID.
            $this->addErrorMessage($translate->_("No source item specified."));
            return false;
        }
        $this->clearStorage();
        if ($this->buildDataset()) {
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

        } else {

            // Unable to duplicate
            $this->addErrorMessage($translate->_("Unable to duplicate item."));
            application_log("error", "Unable to duplicate item, ID: '{$this->item_id}'");
            return false;
        }
    }

    /**
     * Take the current dataset and save it to the database.
     * @return bool
     */
    public function saveData() {
        // Iterate through $this->dataset and commit relevant pieces to the DB
        // Store errors in datastructure
        // Remove records that are no longer relevant to this item, e.g. responses, descriptors, objectives.
        if (is_array($this->dataset) && !empty($this->dataset)) {
            if ($this->dataset["meta"]["item_id"]) {
                // Item already exists, so let's make it consistent with what we've validated. That means we have to soft-delete anything that isn't in our new dataset.
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
        if (@$data["item"]["item_id"]) {
            if ($this->buildDataset()){
                $existing_data = $this->dataset;
            }
        }

        // Load the supplied data into a new dataset structure.
        if (empty($existing_data)) {
            $new_dataset = $this->buildDatasetAsNewItem($data);
        } else {
            $new_dataset = $this->buildDatasetAsExistingItem($data, $existing_data);
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
     * @return false|array
     */
    public function fetchData() {
        if (empty($this->dataset)) {
            $this->buildDataset();
        }
        return $this->dataset;
    }

    /**
     * Mark the current item as deleted.
     *
     * @return bool
     */
    public function delete() {
        global $translate;
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
    
    //-- Protected --//

    /**
     * Determine if this object can be edited.
     * If it is part of a form that has been delivered, then it is NOT editable.
     *
     * @return bool
     */
    protected function determineEditable() {
        // To determine what assessments have been delivered, we must check the distribtions that use this form.
        // NOTE: For CBME, we will have to also check a separate table for which assessment forms are directly being used

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

    /**
     * Using the given itemtype ID, fetch the shortname of the associated itemtype.
     *
     * @param int $itemtype_id
     * @return null
     */
    protected function fetchItemtypeShortnameByItemtypeID($itemtype_id) {
        if ($itemtype_id) {
            if ($itemtype_record = Models_Assessments_Itemtype::fetchRowByID($itemtype_id)) {
                return $itemtype_record->getShortname();
            }
        }
        return null;
    }

    //-- Private --//

    /**
     * Validate the given data.
     *
     * @param $data
     * @return bool
     */
    private function validate($data) {
        if (!is_array($data) || empty($data)) {
            return false;
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
            foreach ($this->dataset["responses"] as $response_data) {
                $new_response = new Models_Assessments_Item_Response(
                    array(
                        "order" => $response_data["order"],
                        "flag_response" => $response_data["flag_response"],
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
                        "objective_id" => $objective_data["objective_id"]
                    )
                );
                if (!$item_objective->insert()) {
                    application_log("error", "Forms_Item:: Unable to attach objective to item.");
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

        // Saved.
        return true;
    }

    /**
     * Load the given data into a dataset structure, treating it as a new item (i.e., one that does not already exist in the database).
     * This method returns the new dataset structure, and does not replace the existing one, if there is one.
     *
     * @param array $data_to_load
     * @return array (the new dataset)
     */
    private function buildDatasetAsNewItem($data_to_load) {

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

        // Determine the shortname based on the given itemtype ID
        $new_dataset["meta"]["itemtype_shortname"] = $this->fetchItemtypeShortnameByItemtypeID($data_to_load["item"]["itemtype_id"]);

        if ($this->canHaveResponses($new_dataset["meta"]["itemtype_shortname"])) {
            $order = 1;
            if (isset($data_to_load["responses"]) && is_array($data_to_load["responses"])) {
                foreach ($data_to_load["responses"] as $i => $load_response) {
                    $new_dataset["responses"][$i]["order"] = $order;
                    $new_dataset["responses"][$i]["text"] = $load_response;
                    $new_dataset["responses"][$i]["flag_response"] = @$data_to_load["flag_response"][$i] ? $data_to_load["flag_response"][$i] : 0;
                    $new_dataset["responses"][$i]["ardescriptor_id"] = @$data_to_load["descriptors"][$i] ? $data_to_load["descriptors"][$i] : null;
                    $order++;
                }
            }
            if (isset($data_to_load["descriptors"]) && is_array($data_to_load["descriptors"])) {
                foreach ($data_to_load["descriptors"] as $descriptor_id) {
                    $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($descriptor_id);
                    if ($descriptor) {
                        $new_dataset["descriptors"][$descriptor_id] = $descriptor->toArray();
                    } else {
                        // add error, descriptor not found
                        application_log("error", "Forms_Item::loadData: Response descriptor not found");
                    }
                }
            }
        }

        if ($this->canHaveObjectives($new_dataset["meta"]["itemtype_shortname"])) {

            if (isset($data_to_load["objectives"]) && is_array($data_to_load["objectives"])) {
                foreach ($data_to_load["objectives"] as $objective_id) {
                    $new_dataset["objectives"][$objective_id] = array(
                        "objective_id" => $objective_id,
                        "created_by" => $this->actor_proxy_id,
                        "created_date" => time(),
                        "item_id" => null
                    );
                }
            }
        }
        return $new_dataset;
    }

    /**
     * Load the given data, comparing it against the existing data specified, and return an updated dataset structure.
     * This does not affect the internal dataset property, it simply creates a new dataset that can be used to replace it.
     *
     * Contained in the dataset is all of the item related responses and metadata, as well as what items to prune (applied when saveData is called).
     *
     * @param array $data_to_load
     * @param array $existing_data
     * @return array (The new dataset)
     */
    private function buildDatasetAsExistingItem($data_to_load, $existing_data) {

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

        if ($this->canHaveResponses($new_dataset["meta"]["itemtype_shortname"])) {
            if (!empty($data_to_load["responses"])) {
                $order = 1;

                // Store all the given responses
                foreach ($data_to_load["responses"] as $i => $response_text) {
                    $new_dataset["responses"][$i]["order"] = $order;
                    $new_dataset["responses"][$i]["text"] = $response_text;
                    $new_dataset["responses"][$i]["flag_response"] = @$data_to_load["flag_response"][$i] ? $data_to_load["flag_response"][$i] : 0;
                    $new_dataset["responses"][$i]["ardescriptor_id"] = @$data_to_load["descriptors"][$i] ? $data_to_load["descriptors"][$i] : null;
                    $order++;
                }
                // Has duplicate descriptors?
                $has_duplicates = false;
                foreach ($new_dataset["responses"] as $i => $response_data_haystack) {
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
                                $new_flagging = $response_data["flag_response"];

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
                            "created_by" => $this->actor_proxy_id,
                            "created_date" => time()
                        );
                    }
                }

                // Prune ones that aren't in our new dataset
                foreach ($existing_data["objectives"] as $existing_objective) {
                    $found = false;
                    foreach ($new_dataset["objectives"] as $new_objective_data) {
                        if ($existing_objective["objective_id"] == $new_objective_data["objective_id"]) {
                            $found = true;
                        }
                    }
                    if (!$found) {
                        $new_dataset["meta"]["property_cleanup"]["objectives"][$existing_objective["aiobjective_id"]] = $existing_objective["aiobjective_id"];
                    }
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

        // Store authors
        $item_authors = Models_Assessments_Item_Author::fetchAllByItemID($this->item_id, $this->actor_organisation_id);
        $authors = array();
        if (is_array($item_authors)) {
            foreach ($item_authors as $author) {
                $authors[] = $author->toArray();
            }
        }
        $item_data["authors"] = $authors;

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

        // Fetch the associated tags for this item
        if ($existing_item_objectives = Models_Assessments_Item_Objective::fetchAllRecordsByItemID($this->item_id)) {
            if (is_array($existing_item_objectives)) {
                foreach ($existing_item_objectives as $objective) {
                    $item_data["objectives"][$objective->getID()] = $objective->toArray();
                    // Store the name as well
                    if ($single_objective = Models_Objective::fetchRow($objective->getObjectiveID())) {
                        $item_data["objective_names"][$objective->getObjectiveID()] = $single_objective->getName();
                    } else {
                        $item_data["objective_names"][$objective->getObjectiveID()] = ""; // couldn't find
                    }
                }
            }
        }


        // Fetch any relationships
        if ($item_relationships = Models_Assessments_Item_Relationship::fetchRowByItemID($this->item_id)) {
            if (is_array($item_relationships)) {
                foreach ($item_relationships as $relationship) {
                    $item_data["relationships"][$relationship->getID()] = $relationship->toArray();
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
        $item_data["meta"] = array();
        $item_data["meta"]["item_id"] = $this->item_id;
        $item_data["meta"]["itemtype_shortname"] = $this->fetchItemtypeShortnameByItemID();
        $item_data["meta"]["descriptor_count"] = 0;
        $item_data["meta"]["responses_count"] = 0;
        $item_data["meta"]["objectives_count"] = 0;
        $item_data["meta"]["is_editable"] = $this->determineEditable();
        $item_data["meta"]["in_use_by"] = $this->determineUsage();
        $item_data["meta"]["errors"] = array();
        $item_data["meta"]["property_cleanup"] = array("responses" => array(), "objectives" => array(), "authors" => array());
        $item_data["item"] = array();
        $item_data["authors"] = array();
        $item_data["responses"] = array();
        $item_data["descriptors"] = array();
        $item_data["objectives"] = array();
        $item_data["objective_names"] = array();
        $item_data["relationships"] = array();
        return $item_data;
    }
}