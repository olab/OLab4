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
 * This class acts as the primary point of interaction with assessment
 * form related functionality with respect to rubrics. All input/output of
 * for rubric data manipulation should utilize this class.
 *
 * There are two ways to fetch the data of a rubric, either by specifying
 * the ID of the rubric, or fetching the form that contains the rubric.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Assessments_Forms_Rubric extends Entrada_Assessments_Forms_Base {

    protected $rubric_id = null;

    // Optionals
    protected $adistribution_id = null;
    protected $dassessment_id = null;
    protected $aprogress_id = null;
    protected $associated_elements = array();

    public function getRubricID() {
        return $this->rubric_id;
    }

    /**
     * Return whether this rubric is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->rubric_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $count_in_use = count($this->dataset["meta"]["in_use_by"]["forms"]);
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
     * Return whether this rubric is editable.
     *
     * @return bool
     */
    public function isEditable() {
        if ($this->rubric_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            return $this->dataset["meta"]["is_editable"];
        }
        return true;
    }

    /**
     * Fetch which forms are using this rubric.
     *
     * @return array
     */
    public function inUseBy() {
        $in_use_by = array();
        if ($this->rubric_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $in_use_by = $this->dataset["meta"]["in_use_by"];
        }
        return $in_use_by;
    }

    /**
     * Fetch which forms are using this rubric.
     *
     * @return array
     */
    public function itemsInUseBy() {
        $in_use_by = array();
        if ($this->rubric_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $in_use_by = $this->dataset["meta"]["items_used_by"];
        }
        return $in_use_by;
    }

    /**
     * Load the primitive values into the dataset. Does not need to change any
     * item relationships.
     *
     * @param array
     * @param bool $validate
     * @return bool
     */
    public function loadData($data, $validate = true) {
        $given_rubric_id = $data["rubric_id"];
        $old_id = $this->rubric_id;
        if ($given_rubric_id) {
            // We were given a rubric ID, so fetch its data
            $this->rubric_id = $given_rubric_id;
            $new_dataset = $this->assembleRubricDataByRubricID();
        } else {
            // No rubric ID, so treat this as a new item
            $this->rubric_id = null;
            $new_dataset = $this->buildDefaultRubricStructure();
        }

        // We now have a dataset (either new or populated)
        // Update the added fields
        $new_dataset["rubric"]["rubric_id"] = $given_rubric_id; // can be null
        $new_dataset["rubric"]["rubric_title"] = $data["rubric_title"];
        $new_dataset["rubric"]["rubric_description"] = $data["rubric_description"];
        $new_dataset["rubric"]["rubric_item_code"] = $data["rubric_item_code"];

        // All other dataset properties should remain the same

        // Validate if specified
        if ($validate) {
            if ($this->validate($new_dataset)) {
                $this->dataset = $new_dataset;
            } else {
                $this->rubric_id = $old_id; // restore old ID (dataset hasn't been trashed yet)
                return false; // Failed validation
            }
        } else {
            $this->dataset = $new_dataset;
        }
        return true;
    }

    /**
     * Create or update rubric using the internal dataset. Verify that the consistuent items are consistent on the forms that use it.
     *
     * @return bool
     */
    public function saveData() {

        $this->setErrorStatus(false);

        // Save the dataset.
        if ($this->rubric_id) {
            // Save data of existing rubric
            if (!$this->updateRubricPrimitives(
                $this->dataset["rubric"]["rubric_title"],
                $this->dataset["rubric"]["rubric_description"],
                $this->dataset["rubric"]["rubric_item_code"])) {
                // Saved OK
                return $this->getErrorStatus(); // errors already added by updateRubricPrimitives
            }
        } else {
            // Saving new rubric
            if (!$this->saveEmptyRubric(
                $this->dataset["rubric"]["rubric_title"],
                $this->dataset["rubric"]["rubric_description"],
                $this->dataset["rubric"]["rubric_item_code"])) {
                return $this->getErrorStatus(); // errors already added by saveEmptyRubric
            }
        }

        $status = true;

        // After save is complete and successful, verify rubric consistency on forms.
        // For all of the items in this dataset, verify that every form that uses this is consistent with it.

        // For all of the forms that use this, check their current state and update them as necessary.
        // We do this regardless of assessment status. The form should not be editable, therefore the rubric should not
        // be editable, but if we're executing this action, then we've already passed that check. Regardless,
        // the form must be kept consistent with the form.
        if (is_array($this->dataset["meta"]["in_use_by"]["forms"])) {
            foreach ($this->dataset["meta"]["in_use_by"]["forms"] as $form_id) {
                if (!$this->formConsistencyCheckAndRepair($form_id, true)) {
                    $status = false;
                }
            }
        }
        return $this->setErrorStatus($status);
    }

    /**
     * Update the non-relational data of a rubric record.
     *
     * @param string $title
     * @param string $description
     * @param string $code
     * @return bool
     */
    public function updateRubricPrimitives($title, $description, $code) {
        global $translate;
        $this->setErrorStatus(false);
        if ($rubric = Models_Assessments_Rubric::fetchRowByID($this->rubric_id)) {
            $updates = array();
            $updates["rubric_title"] = $title;
            $updates["rubric_description"] = $description;
            $updates["rubric_item_code"] = $code;
            $updates["updated_date"] = time();
            $updates["updated_by"] = $this->actor_proxy_id;
            $rubric->fromArray($updates);
            if (!$rubric->update()) {
                $this->addErrorMessage($translate->_("Unable to update rubric."));
                application_log("error", "updateRubricPrimities failed: rubric_id = '{$this->rubric_id}' title: '$title' description: '$description' code: '$code'");
            } else {
                $this->setErrorStatus(true); // OK
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to update rubric as it was not found."));
        }
        return $this->getErrorStatus();
    }

    /**
     * Save an empty rubric, with default author (this current actor).
     *
     * @param string $rubric_title
     * @param string $rubric_description
     * @param string $rubric_item_code
     * @return bool
     */
    public function saveEmptyRubric($rubric_title = null, $rubric_description = null, $rubric_item_code = null) {
        global $translate;

        $this->setErrorStatus(false);

        $rubric_data = array();
        $rubric_data["rubric_title"] = $rubric_title;
        $rubric_data["organisation_id"] = $this->actor_organisation_id;
        $rubric_data["created_date"] = time();
        $rubric_data["created_by"] = $this->actor_proxy_id;
        $rubric_data["rubric_description"] = $rubric_description;
        $rubric_data["rubric_item_code"] = $rubric_item_code;

        // Create a new rubric
        $rubric = new Models_Assessments_Rubric($rubric_data);
        if ($rubric->insert()) {
            $this->rubric_id = $rubric->getID();
            $author = array(
                "rubric_id" => $rubric->getID(),
                "rubric_title" => $rubric_title,
                "author_type" => "proxy_id",
                "author_id" => $this->actor_proxy_id,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id
            );
            $a = new Models_Assessments_Rubric_Author($author);
            if ($a->insert()) {
                $this->setErrorStatus(true); // all OK
            } else {
                $this->addErrorMessage($translate->_("There was an error when attempting to set access to this rubric."));
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to create a new rubric."));
        }

        return $this->getErrorStatus();
    }

    /**
     * Fetch all related data points, return in a data structure.
     *
     * @return false|array
     */
    public function fetchData() {
        if (empty($this->dataset)) {
            $this->dataset = $this->assembleRubricDataByRubricID(); // builds dataset
        }
        return $this->dataset;
    }

    /**
     * Clear internal storage and reload the internal dataset.
     * This sets the internal dataset to a fresh copy; does not return it.
     */
    public function refreshDataset() {
        $this->clearStorage();
        $this->dataset = $this->assembleRubricDataByRubricID(); // builds dataset
    }

    /**
     * Safely attach an item by ID to a rubric.
     *
     * @param int $item_id
     * @param int|null $new_order
     * @return int
     */
    public function attachItem($item_id, $new_order = null) {
        global $translate;

        $this->setErrorStatus(false);

        if (!$this->isEditable()) {
            $this->addErrorMessage($translate->_("This rubric is locked and cannot have new items added to it."));
            return $this->getErrorStatus();
        }

        $item = Models_Assessments_Item::fetchRowByID($item_id);
        if (!$item) {
            $this->addErrorMessage($translate->_("Item not found."));
            return $this->getErrorStatus();
        }

        $existing_rubric_element = Models_Assessments_Rubric_Item::fetchRowByItemIDRubricID($item_id, $this->rubric_id);
        if ($existing_rubric_element) {
            $this->addErrorMessage($translate->_("This item is already attached to this rubric."));
            return $this->getErrorStatus();
        }

        // Before adding the item to this rubric, we have to check if there's no conflict for any of the forms this rubric is associated with
        $conflicting_forms = $this->findFormIDsConflictingWithRubricAddition($item_id);
        if (!empty($conflicting_forms)) {
            //$rubric_conflict_url = ENTRADA_URL . "/assessments/items"; // TODO: Build a page for this, pass along the form ids
            $error_message_conflicting = sprintf($translate->_("This item (\"%s\") cannot be added to the rubric. The item already exists on one or more forms that this rubric is a part of."), $item->getItemText());
            //$error_message_conflicting .= sprintf($translate->_("You can view the conflicting forms by following <a href='%s' target='_blank'>this link</a> (opens in a new window)."), $rubric_conflict_url);
            $this->addErrorMessage($error_message_conflicting);
            return $this->getErrorStatus();
        }

        $ritem_order = Models_Assessments_Rubric_Item::fetchNextOrder($this->rubric_id);
        $rubric_item_data = array(
            "rubric_id"         => $this->rubric_id,
            "item_id"           => $item_id,
            "order"             => ($new_order !== null) ? $new_order : $ritem_order,
            "enable_flagging"   => 0,
        );
        $rubric_item = new Models_Assessments_Rubric_Item($rubric_item_data);
        if (!$rubric_item->insert()) {
            $this->addErrorMessage($translate->_("Unable to attach item to rubric."));
            return $this->getErrorStatus();
        }

        // We've attached a new item to the rubric, so we have to ensure that the rubric is still consistent with all forms that use it.
        $checks = array();
        $used_by = $this->inUseBy();
        foreach ($used_by["forms"] as $form_id) {
            $checks[$form_id] = $this->formConsistencyCheckAndRepair($form_id, true);
        }

        $status = true;
        foreach ($checks as $check_status) {
            if (!$check_status) {
                $status = false;
                $this->addErrorMessage($translate->_("Unable to update existing forms with the new rubric item."));
            }
        }

        return $this->setErrorStatus($status);
    }

    /**
     * Safely remove an item by ID from a rubric.
     *
     * @param $item_id
     */
    public function removeItem($item_id) {
        global $translate;

        $this->setErrorStatus(false);

        if (!$this->isEditable()) {
            $this->addErrorMessage($translate->_("This rubric is locked and cannot have items removed from it."));
            return $this->getErrorStatus();
        }

        $item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($item_id);
        if (!$item) {
            $this->addErrorMessage($translate->_("This Item was not found."));
            return $this->getErrorStatus();
        }

        // We have the item, and it is editable (not-delivered)

        // Fetch the rubric item record
        $removal = Models_Assessments_Rubric_Item::fetchRowByItemIDRubricID($item_id, $this->rubric_id);
        if (!$removal) {
            $this->addErrorMessage($translate->_("Cannot remove the item since it is not attached to the rubric."));
            return $this->getErrorStatus();
        }

        // Remove it
        $update = $removal->toArray();
        $update["deleted_date"] = time();
        $removal->fromArray($update);
        if (!$removal->update()) {
            $this->addErrorMessage($translate->_("Unable to update rubric item."));
            return $this->getErrorStatus();
        }

        // Find all instances of forms that use that item_id in a rubric and delete them.
        $items_for_this_rubric_on_forms = Models_Assessments_Form_Element::fetchAllByItemIDRubricID($item_id, $this->rubric_id);
        if (empty($items_for_this_rubric_on_forms)) {
            // None found, we're done
            return $this->setErrorStatus(true);
        }

        // Assuming isEditable is accurate (it should be!), we modify all existing forms by removing the item from those forms.
        // isEditable will return false if any of the associated forms are delivered, making this rubric ineligble for item removals, thus making
        // it impossible to ever reach this functionality.
        foreach ($items_for_this_rubric_on_forms as $form_rubric_item) {
            $form_element = $form_rubric_item->toArray();
            $form_element["deleted_date"] = time();
            $form_element["updated_date"] = time();
            $form_element["updated_by"] = $this->actor_proxy_id;
            $form_rubric_item->fromArray($form_element);
            if (!$form_rubric_item->update()) {
                application_log("error", "Unable to remove rubric item from form (item id '$item_id', rubric id '{$this->rubric_id}'");
                $this->addErrorMessage("Unable to remove item from existing form.");
            }
        }

        // Finished
        return $this->setErrorStatus(true);
    }

    /**
     * Copy the given rubric record; set this object to reflect the new rubric.
     *
     * @param $old_rubric_id
     * @param $new_rubric_title
     * @param bool $include_deleted_items
     * @return bool
     */
    public function copyRubric($old_rubric_id, $new_rubric_title, $include_deleted_items = false) {
        global $translate;

        $this->setErrorStatus(false);

        if (!$new_rubric_title) {
            $this->addErrorMessage($translate->_("Please specify a title for the new rubric."));
            return $this->getErrorStatus();
        }

        $previous_rubric_id = $this->rubric_id;
        $this->rubric_id = $old_rubric_id;
        $old_rubric_dataset = $this->assembleRubricDataByRubricID();
        if (empty($old_rubric_dataset)) {
            $this->rubric_id = $previous_rubric_id; // restore ID
            $this->addErrorMessage($translate->_("Cannot copy rubric: unable to fetch old rubric."));
            return $this->getErrorStatus();
        } else {
            $this->rubric_id = $previous_rubric_id; // restore ID
        }

        $new_rubric_data = $old_rubric_dataset["rubric"];
        $new_rubric_data["rubric_id"] = null;
        $new_rubric_data["rubric_title"] = $new_rubric_title;
        $new_rubric_data["created_date"] = time();
        $new_rubric_data["updated_date"] = time();
        $new_rubric_data["created_by"] = $this->actor_proxy_id;
        $new_rubric_data["updated_by"] = $this->actor_proxy_id;
        $new_rubric_data["organisation_id"] = $this->actor_organisation_id;

        $copy_rubric = new Models_Assessments_Rubric($new_rubric_data);
        $inserted = $copy_rubric->insert();
        if (!$inserted) {
            $this->addErrorMessage($translate->_("Unable to save new rubric."));
            return $this->getErrorStatus();
        }

        $new_rubric_id = $copy_rubric->getID();
        $error_status = true;

        // Attach all the previous rubric's items to this new rubric
        foreach ($old_rubric_dataset["lines"] as $rubric_line_data) {
            if (!empty($rubric_line_data["rubric_item_record"])) {
                if ($include_deleted_items) {
                    $do_insert = true;
                } else {
                    $do_insert = ($rubric_line_data["item"]["deleted_date"]) ? false : true;
                }

                if ($do_insert) {
                    $rubric_item_data = array(
                        "rubric_id"             => $new_rubric_id,
                        "item_id"               => $rubric_line_data["rubric_item_record"]["item_id"],
                        "order"                 => $rubric_line_data["rubric_item_record"]["order"],
                        "enable_flagging"       => $rubric_line_data["rubric_item_record"]["enable_flagging"],
                    );
                    $rubric_item = new Models_Assessments_Rubric_Item($rubric_item_data);
                    if (!$rubric_item->insert()) {
                        $this->addErrorMessage($translate->_("An error occurred while adding a rubric item."));
                        application_log("error", "Unable to add new rubric item to rubric (rubric id= '$new_rubric_id')'");
                        $error_status = false; // indicate that there was ane error, but don't quit
                    }
                }
            }
        }

        // Retrieve all authors from the previous rubric
        $authors = Models_Assessments_Rubric_Author::fetchAllByRubricID($old_rubric_id, $this->actor_organisation_id);
        if ($authors) {
            // Insert copies of the authors with the newly created rubric's ID
            foreach ($authors as $author) {
                $author_data = array(
                    "rubric_id"             => $new_rubric_id,
                    "author_type"           => $author->getAuthorType(),
                    "author_id"             => $author->getAuthorId(),
                    "created_date"          => time(),
                    "created_by"            => $this->actor_proxy_id,
                    "updated_date"          => $author->getUpdatedDate(),
                    "updated_by"            => $author->getUpdatedBy()
                );
                $author = new Models_Assessments_Rubric_Author($author_data);
                if (!$author->insert()) {
                    add_error($translate->_("An error occurred while adding an author to the rubric. Rubric ID: '$new_rubric_id''"));
                    $this->addErrorMessage($translate->_("There was an error adding an author to the rubric."));
                    $error_status = false; // Flag the error, but don't quit.
                }
            }
        }

        $this->rubric_id = $new_rubric_id;
        $this->refreshDataset(); // rebuild dataset using new rubric id
        return $this->setErrorStatus($error_status);
    }

    /**
     * Mark the current rubric as deleted.
     *
     * @return bool
     */
    public function delete() {
        global $translate;
        if ($this->rubric_id) {
            $rubric = Models_Assessments_Rubric::fetchRowByIDIncludeDeleted($this->rubric_id);
            if ($rubric) {
                $rubric->fromArray(
                    array(
                        "deleted_date" => time(),
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id
                    )
                );
                if ($rubric->update()) {
                    return true;
                } else {
                    $this->addErrorMessage($translate->_("Unable to delete a Grouped Item."));
                }
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to delete item without item id."));
        }
        return false;

    }

    /**
     * Given the aritem_id, delete the rubric item and keep the rubric consistent across all places that use it.
     *
     * @param $aritem_id
     * @return bool
     */
    public function deleteRubricItem($aritem_id) {
        global $translate;

        if (!$this->rubric_id) {
            $this->addErrorMessage($translate->_("Please specify which rubric to delete from."));
            return false;
        }

        if (!$rubric_item = Models_Assessments_Rubric_Item::fetchRowByID($aritem_id)) {
            $this->addErrorMessage($translate->_("Rubric item was not found."));
            return false;
        }

        // Fetch data if our dataset isn't populated.
        if (empty($this->dataset)) {
            $this->fetchData();
        }

        // If our dataset is still empty after fetching, we can't proceed.
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric data."));
            return false;
        }

        $rubric_item_data = $rubric_item->toArray();
        $rubric_item_data["deleted_date"] = time();
        $rubric_item->fromArray($rubric_item_data);
        if (!$rubric_item->update()) {
            $this->addErrorMessage($translate->_("Encountered and error when attempting to delete the rubric item."));
            return false;
        }

        // Deleted
        $status = true;

        // Reset the internal datastructure since we've deleted some items.
        $this->refreshDataset();

        // Rubric item has been deleted, so perform a consistency check, and repair all instances of this across all forms.
        if (is_array($this->dataset["meta"]["in_use_by"]["forms"])) {
            foreach ($this->dataset["meta"]["in_use_by"]["forms"] as $form_id) {
                if (!$this->formConsistencyCheckAndRepair($form_id, true)) {
                    $status = false;
                    $this->addErrorMessage($translate->_("Rubric item deleted, but failed consistency check; forms that use this rubric may be out of sync with this rubric."));
                }
            }
        }
        return $status;
    }

    /**
     * Update all of the responses with the new descriptor based on their order.
     *
     * @param $new_descriptor_id
     * @param $position
     * @return mixed
     */
    public function updateResponseDescriptor($new_descriptor_id, $position) {
        global $translate;
        $this->setErrorStatus(false);
        if (!$this->rubric_id) {
            $this->addErrorMessage($translate->_("No rubric specified."));
            return $this->getErrorStatus();
        }

        $this->fetchData();
        if (empty($this->dataset)) {
            // No rubric data found
            $this->addErrorMessage($translate->_("No rubric data found."));
            return $this->getErrorStatus();
        }

        $status = true;
        foreach ($this->dataset["lines"] as $rubric_line_data) {
            foreach ($rubric_line_data["responses"] as $response_data) {
                if ($response_data["response_order"] == $position) {
                    // Change this descriptors for everything in this column
                    $response_data["ardescriptor_id"] = $new_descriptor_id;
                    $updated = new Models_Assessments_Item_Response($response_data);
                    if (!$updated->update()) {
                        $status = false;
                        $this->addErrorMessage($translate->_("Unable to save new descriptor(s)."));
                    }
                }
            }
        }
        return $this->setErrorStatus($status);
    }

    /**
     * Get the order of the item Id specified in this rubric.
     *
     * @param $item_id
     * @return bool
     */
    public function getItemOrder($item_id) {
        if (!empty($this->dataset)) {
            foreach ($this->dataset["lines"] as $rubric_line_data) {
                if ($rubric_line_data["rubric_item_record"]["item_id"] == $item_id) {
                    return $rubric_line_data["rubric_item_record"]["order"];
                }
            }
        }
        return false;
    }

    /**
     * Allow the direct replacement of the internal dataset, in the case where the parameter is an array.
     *
     * @param $dataset
     */
    public function setDataset($dataset = null) {
        if (is_array($dataset)) {
            $this->dataset = $dataset;
        }
    }

    /**
     * Perform a consistency check on the form, compare it to this rubric.
     * Repair if specified.
     *
     * Return false when form exists and is inconsistent with the current rubric state.
     *
     * @param $form_id
     * @param bool $repair
     * @param bool $refresh_dataset
     * @return bool
     */
    public function formConsistencyCheckAndRepair($form_id, $repair = false, $refresh_dataset = true) {
        global $translate;

        if (!$this->rubric_id) {
            // This is a new rubric, not saved to db yet (can't exist on forms yet)
            return true;
        }
        if (empty($this->dataset)) {
            // This rubric object isn't configured
            $this->addErrorMessage("Can't run consistency check on empty rubric object.");
            return false;
        }
        if (!$form_id) {
            // No form specified, ignore
            return true;
        }
        if (!$form_record = Models_Assessments_Form::fetchRowByIDIncludeDeleted($form_id)) {
            // The form record does not exist, we can ignore
            return true;
        }
        $form_elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($form_id, $this->rubric_id);
        if (empty($form_elements)) {
            // The form has no elements associated with this rubric; how did we get this $form_id?
            // This means that it's not attached, and therefore technically consistent.
            return true;
        }

        if ($refresh_dataset) {
            $this->refreshDataset();
        }

        $elements_on_form_that_are_not_part_of_rubric = array();

        // Search for form elements that match this rubric ID, but aren't actually part of the rubric.
        foreach ($form_elements as $form_element) {
            $element_found = false;
            foreach ($this->dataset["lines"] as $rubric_line_data) {
                if ($rubric_line_data["item"]["item_id"] == $form_element->getElementID() && $form_element->getRubricID() == $this->rubric_id) {
                    $element_found = true;
                }
            }
            if (!$element_found) {
                $elements_on_form_that_are_not_part_of_rubric[] = $form_element;
            }
        }

        $rubric_lines_that_are_not_on_the_form = array();

        // Search for rubric lines that do not appear on the form
        foreach ($this->dataset["lines"] as $rubric_line_data) {
            $line_found = false;
            foreach ($form_elements as $form_element) {
                if ($rubric_line_data["rubric_item_record"]["item_id"] == $form_element->getElementID() && $form_element->getRubricID() == $this->rubric_id) {
                    $line_found = true;
                }
            }
            if (!$line_found) {
                $rubric_lines_that_are_not_on_the_form[] = $rubric_line_data;
            }
        }

        if ($repair) {

            $consistency_fix_result = true;
            // We always assume the rubric is correct, so we must update the form to match it.
            foreach ($elements_on_form_that_are_not_part_of_rubric as $extraneous_element) {
                $element_data = $extraneous_element->toArray();
                $element_data["deleted_date"] = time();
                $element_data["updated_date"] = time();
                $element_data["updated_by"] = 1; // Cleaned up by the system, not this actor
                $extraneous_element->fromArray($element_data);

                if (!$extraneous_element->update()) {
                    $this->addErrorMessage($translate->_("Unable to remove extraneous item from form."));
                    $consistency_fix_result = false; // flag that we weren't able to fix, and that it's still inconsistent
                }
            }

            foreach ($rubric_lines_that_are_not_on_the_form as $missing_line_data) {
                // Create and add these missing lines to the form
                $new_element_data = array();
                $new_element_data["form_id"] = $form_id;
                $new_element_data["element_type"] = "item";
                $new_element_data["element_id"] = $missing_line_data["rubric_item_record"]["item_id"];
                $new_element_data["rubric_id"] = $this->rubric_id;
                $new_element_data["order"] = $missing_line_data["rubric_item_record"]["order"]; // is this correct?
                $new_element_data["enable_flagging"] = $missing_line_data["rubric_item_record"]["enable_flagging"]; // This value is ignored, if there is a lower order on the form for this rubric already.
                $new_element_data["allow_comments"] = 0; // Rubrics have no rubric-level comments; they have them built in, on the item level, if applicable
                $new_element_data["updated_date"] = time();
                $new_element_data["updated_by"] = 1; // We're cleaning this up as the system, not this actor
                $new_element = new Models_Assessments_Form_Element($new_element_data);
                if (!$new_element->insert()) {
                    $this->addErrorMessage($translate->_("Unable to add rubric item to form."));
                    $consistency_fix_result = false;
                }
            }
            return $consistency_fix_result;

        } else {

            if (empty($rubric_lines_that_are_not_on_the_form) && empty($elements_on_form_that_are_not_part_of_rubric)) {
                // No extraneous items found
                return true;
            } else {
                // Forms are not consistent with rubric
                return false;
            }
        }
    }

    //-- Protected --//

    /**
     * Validate the dataset.
     *
     * @param array $dataset
     * @return bool
     */
    protected function validate($dataset) {
        global $translate;
        if (!isset($dataset["rubric"]["rubric_title"])) {
            $this->addErrorMessage($translate->_("No title specified."));
            return false;
        }
        return true;
    }

    /**
     * Find whether this rubric is in use.
     *
     * @return bool
     */
    protected function determineUsage() {
        // Check if any forms use this rubric. If so, store their IDs.
        $in_use_by = array("forms" => array(), "assessments" => array());
        if ($this->rubric_id) {
            $in_use_by["forms"] = Models_Assessments_Form_Element::fetchFormIDsByRubricD($this->rubric_id);
            if (is_array($in_use_by["forms"]) && !empty($in_use_by)) {
                $in_use_by["assessments"] = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs($in_use_by["forms"]);
            }
        }
        return $in_use_by;
    }

    /**
     * Find the form and rubric IDs that also use the items in this rubric.
     * Result set does not include this rubric.
     *
     * @return bool
     */
    protected function determineItemsUsage() {
        // Check the usage of the items that this rubric uses (check what else uses them)
        $in_use_by = array("forms" => array(), "rubrics" => array());
        if ($this->rubric_id) {
            $usage_to_find = array();
            $all_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($this->rubric_id);
            if (is_array($all_rubric_items)) {
                foreach ($all_rubric_items as $item) {
                    $usage_to_find[$item->getItemID()] = (int)$item->getItemID();
                }
            }

            if (empty($usage_to_find)) {
                return $in_use_by; // nothing to find!
            }

            $in_use_by["forms"] = Models_Assessments_Form_Element::fetchFormIDsByItemIDList($usage_to_find);
            $in_use_by["rubrics"] = Models_Assessments_Rubric_Item::fetchRubricIDsByItemIDList($usage_to_find);
            foreach ($in_use_by["rubrics"] as $rubric_id => $data) {
                if ($rubric_id == $this->rubric_id) {
                    unset($in_use_by["rubrics"][$rubric_id]);
                }
            }
        }
         return $in_use_by;
    }

    /**
     * Determine if this rubric can be edited.
     *
     * A rubric can not be edited when it is part of a form that is delivered (assessment created).
     *
     * @return bool
     */
    protected function determineEditable() {
        if (!$this->rubric_id) {
            return true; // This is a new rubric.
        }

        // Fetch all forms that have used this rubric
        $usage = Models_Assessments_Form_Element::fetchFormIDsByRubricD($this->rubric_id);
        if (empty($usage)) {
            return true; // Not in use, so it is editable.
        }

        // Before we iterate through all the distributions that may be using this rubric, we check for progress.
        // If there's in-progress or complete progress records, we immediately return false.
        $progress_ids = Models_Assessments_Progress_Response::fetchInProgressAndCompleteProgressResponseIDsByFormIDs($usage);
        if (!empty($progress_ids)) {
            return false; // There's some in-progress or complete data using a form that uses this rubric. That means it's not editable.
        }

        $dassessment_ids = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs($usage);
        if (!empty($dassessment_ids)) {
            // Assessment IDs were found, meaning assessment tasks were delivered that use this rubric. It is not editable.
            return false;
        }

        // Passed all checks, so we return true, indicating that it is editable.
        return true;
    }

    //-- Private methods --//

    /**
     * Fetch all rubric data based on the internal rubric_id, and build a dataset.
     *
     * @return array
     */
    private function assembleRubricDataByRubricID() {
        global $translate;
        $rubric_data = array();
        if ($this->rubric_id) {

            $rubric_data = $this->buildDefaultRubricStructure();

            // Fetch all rubric data by ID
            if ($this->isInStorage("rubric", $this->rubric_id)) {
                $rubric_record = $this->fetchFromStorage("rubric", $this->rubric_id);
            } else {
                $rubric_record = Models_Assessments_Rubric::fetchRowByIDIncludeDeleted($this->rubric_id);
                $this->addToStorage("rubric", $this->rubric_id);
            }

            if (!$rubric_record) {
                // Given rubric ID is invalid.
                $this->addErrorMessage($translate->_("Rubric ID is invalid."));
                return array();
            }

            // Save original rubric record
            $rubric_data["rubric"] = $rubric_record->toArray();

            // Fetch Rubric authors
            if ($this->isInStorage("rubric-authors", $this->rubric_id)) {
                $rubric_authors = $this->fetchFromStorage("rubric-authors", $this->rubric_id);
            } else {
                $rubric_authors = array();
                $authors_objects = Models_Assessments_Rubric_Author::fetchAllByRubricID($this->rubric_id, $this->actor_organisation_id);
                if (is_array($authors_objects) && !empty($authors_objects)) {
                    foreach ($authors_objects as $author) {
                        $rubric_authors[] = $author->toArray();
                    }
                }
                $this->addToStorage("rubric-authors", $rubric_authors, $this->rubric_id);
            }
            $rubric_data["authors"] = $rubric_authors;

            // Fetch progress data, if we have a progress ID
            $progress = array();
            if ($this->aprogress_id) {
                // A specific progress ID was given, so fetch that progress data
                if ($progress_record = Models_Assessments_Progress::fetchRowByID($this->aprogress_id)) {
                    $progress = $progress_record->toArray();
                    $progress["progress_responses"] = array();

                    // Find any progress responses, if any
                    $progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($progress_record->getID());
                    if (!empty($progress_responses)) {
                        foreach ($progress_responses as $progress_response) {
                            $progress["progress_responses"][$progress_response->getID()] = $progress_response->toArray();
                        }
                    }
                }
            }

            // Find lines and descriptors
            $rubric_lines = Models_Assessments_Rubric_Item::fetchAllByRubricIDOrdered($this->rubric_id); // Custom result set, includes deleted items
            $items_encountered = array();
            $includes_a_deleted_item = false;
            if (!empty($rubric_lines)) {
                foreach ($rubric_lines as $rubric_line) {
                    if ($rubric_line["deleted_date"]) {
                        $includes_a_deleted_item = true;
                    }
                    $item_id = $rubric_line["item_id"];
                    $rubric_data["lines"][$item_id]["item"] = $rubric_line;
                    $original_rubric_item_record = Models_Assessments_Rubric_Item::fetchRowByID($rubric_line["aritem_id"]);
                    $rubric_data["lines"][$item_id]["rubric_item_record"] = array();
                    if ($original_rubric_item_record) {
                        $rubric_data["lines"][$item_id]["rubric_item_record"] = $original_rubric_item_record->toArray();
                    }

                    // Fetch the label for this item
                    if ($label = Models_Assessments_Rubric_Label::fetchRowByRubricIDItemID($this->rubric_id, $item_id)) {
                        $rubric_data["labels"][$item_id] = $label->toArray();
                    }

                    // This field is meant to track the position of the rubric on a form. Since we are fetching regardless of a form, this value is 0
                    $rubric_data["lines"][$item_id]["element_order"] = 0;
                    $responses_object_list = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item_id);
                    if (is_array($responses_object_list) && !empty($responses_object_list)) {
                        foreach ($responses_object_list as $i => $response) {
                            $rubric_data["lines"][$item_id]["responses"][$response->getID()] = $response->toArray();
                            $rubric_data["lines"][$item_id]["responses"][$response->getID()]["response_text"] = $response->getText();
                            $rubric_data["lines"][$item_id]["responses"][$response->getID()]["response_order"] = $response->getOrder();
                            $rubric_data["lines"][$item_id]["responses"][$response->getID()]["response_descriptor_text"] = $response->getText();
                            $rubric_data["lines"][$item_id]["responses"][$response->getID()]["is_selected"] = false;
                            if (!$response->getText()) {
                                if ($this->isInStorage("response-descriptors", $response->getARDescriptorID())) {
                                    $descriptor = $this->fetchFromStorage("response-descriptors", $response->getARDescriptorID());
                                } else {
                                    $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($response->getARDescriptorID());
                                    $this->addToStorage("response-descriptors", $descriptor, $response->getARDescriptorID());
                                }
                                if ($descriptor) {
                                    $rubric_data["lines"][$item_id]["responses"][$response->getID()]["response_descriptor_text"] = $descriptor->getDescriptor();
                                }
                            }

                            // Default is that this line item is NOT selected. If we have progress records, though, we check if it actually is selected.
                            if (!empty($this->associated_elements) && !empty($progress)) {

                                // If we've been given associated form elements, then check if this item has been selected
                                foreach ($this->associated_elements as $afelement_id => $element_data) {
                                    foreach ($element_data["responses"] as $element_response) {
                                        if ($element_response["iresponse_id"] == $response->getID()) {
                                            $rubric_data["lines"][$item_id]["responses"][$response->getID()]["is_selected"] = $this->isResponseSelected($progress, $element_response["iresponse_id"]);
                                        }
                                    }
                                }
                            }

                            // Determine whether to show the comments box (only have to set it one time per item)
                            if (!isset($rubric_data["lines"][$item_id]["item"]["render_comment_container"])) {
                                $flagging_info = $this->getCommentFlaggingInfo(
                                    $rubric_data["lines"][$item_id]["item"]["comment_type"],
                                    $rubric_data["lines"][$item_id]["responses"],
                                    $progress["progress_responses"]);

                                $rubric_data["lines"][$item_id]["item"]["render_comment_container"] = $flagging_info["render_comment_container"];
                                $rubric_data["lines"][$item_id]["item"]["comment_container_visible"] = $flagging_info["comment_container_visible"];
                                $rubric_data["lines"][$item_id]["item"]["comment_related_afelement_id"] = $flagging_info["afelement_id"];
                                $rubric_data["lines"][$item_id]["item"]["item_comment_text"] = $flagging_info["comment_text"];

                                if ($rubric_data["lines"][$item_id]["responses"][$response->getID()]["is_selected"] &&
                                    $response->getFlagResponse() &&
                                    $rubric_data["lines"][$item_id]["item"]["comment_type"] != "disabled") {
                                    // For "comment on flagged" comment types, we only display the container when a flagged item is selected.
                                    $rubric_data["lines"][$item_id]["item"]["render_comment_container"] = true;
                                    $rubric_data["lines"][$item_id]["item"]["comment_container_visible"] = true;
                                }
                            }
                        }
                    } else {
                        $rubric_data["lines"][$item_id]["responses"] = array();
                        $rubric_data["meta"]["consistent"] = false; // No responses, so we mark as inconsistent
                    }

                    // Save the meta data counts (if we haven't already)
                    if (!in_array($item_id, $items_encountered)) {
                        $rubric_data["meta"]["responses_count"] += count($rubric_data["lines"][$item_id]["responses"]);
                        $rubric_data["meta"]["lines_count"]++;
                        $items_encountered[] = $item_id;
                    }

                    // The width is equal to the number of responses of line with the most responses.
                    // All rubric lines should have the same number of descriptors and responses; however, this logic will use the widest line as the baseline.
                    $rubric_data["meta"]["width"] = (count($rubric_data["lines"][$item_id]["responses"]) > $rubric_data["meta"]["width"])
                        ? count($rubric_data["lines"][$item_id]["responses"])
                        : $rubric_data["meta"]["width"];

                    if ($rubric_data["meta"]["first_position"] === false) {
                        // Save the first element's order as the position of this rubric on the form.
                        $rubric_data["meta"]["first_position"] = $rubric_data["lines"][$item_id]["element_order"];

                        // Fetch the first item's descriptors, and use them to title the rubric columns. All descriptors should be consistent.
                        // It is possible for there to be no descriptors, or only some, so we have to store them by position (left to right, 0 to n)
                        if (empty($rubric_data["descriptors"])) {
                            if (!empty($rubric_data["lines"][$item_id]["responses"])) {
                                $column_position = 0;
                                foreach ($rubric_data["lines"][$item_id]["responses"] as $response) {
                                    $current_response = array("ardescriptor_id" => null, "response_descriptor_text" => "");
                                    if ($response["ardescriptor_id"]) {
                                        if ($this->isInStorage("response-descriptors", $response["ardescriptor_id"])) {
                                            $descriptor = $this->fetchFromStorage("response-descriptors", $response["ardescriptor_id"]);
                                        } else {
                                            $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($response["ardescriptor_id"]);
                                            $this->addToStorage("response-descriptors", $descriptor, $response["ardescriptor_id"]);
                                        }
                                        if ($descriptor) {
                                            $current_response = array(
                                                "ardescriptor_id" => $descriptor->getID(),
                                                "response_descriptor_text" => $descriptor->getDescriptor(),
                                            );
                                        }
                                    }
                                    $rubric_data["descriptors"][$column_position] = $current_response;
                                    $column_position++;
                                }
                                $rubric_data["meta"]["descriptor_count"] = count($rubric_data["descriptors"]);
                            }
                        }
                    }
                }
                $rubric_data["meta"]["contains_deleted_items"] = $includes_a_deleted_item;
            }
        }
        return $rubric_data;
    }

    /**
     * Find all form IDs that conflict with this rubric addition.
     *
     * @param $item_id
     * @return array|false
     */
    private function findFormIDsConflictingWithRubricAddition($item_id) {
        // We are attempting to add $item_id to a rubric. If this rubric is in use in the system, we have to first
        // check if the same item is already in use on any of the forms that use this rubric.
        $attached_to_forms = array();
        if (!$item_id) {
            return false;
        }

        // First check if the item is valid.
        $item = Models_Assessments_Item::fetchRowByID($item_id);
        if (!$item) {
            return false;
        }

        // Check if this item we are adding exists on a form where this rubric is present
        $forms_with_this_rubric = Models_Assessments_Form::fetchAllByAttachedRubric($this->rubric_id); // Select all forms that contain this rubric
        if (is_array($forms_with_this_rubric) && !empty($forms_with_this_rubric)) {
            foreach ($forms_with_this_rubric as $form) {
                $form_elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricIDNull($form->getID()); // Get all the non-rubric items for this form
                if (is_array($form_elements) && !empty($form_elements)) {
                    foreach ($form_elements as $form_element) {
                        if ($form_element->getElementID() == $item_id) {
                            // it's already attached to a form. Save the form ID to make a list later.
                            $attached_to_forms[] = $form_element->getFormID();
                        }
                    }
                }
            }
        }
        if (!empty($attached_to_forms)) {
            return $attached_to_forms; // is associated already, so return the list. Otherwise, we return false.
        }
        return false;
    }

    /**
     * Build the default dataset structure.
     *
     * @return array
     */
    private function buildDefaultRubricStructure() {
        $rubric_data = array();
        $rubric_data["meta"] = array();
        $rubric_data["meta"]["rubric_id"] = $this->rubric_id;
        $rubric_data["meta"]["consistent"] = true;
        $rubric_data["meta"]["width"] = 0; // Should be items_count ÷ lines_count
        $rubric_data["meta"]["responses_count"] = 0;
        $rubric_data["meta"]["lines_count"] = 0;
        $rubric_data["meta"]["descriptor_count"] = 0;
        $rubric_data["meta"]["first_position"] = false;
        $rubric_data["meta"]["contains_deleted_items"] = false;
        $rubric_data["meta"]["is_editable"] = $this->determineEditable();
        $rubric_data["meta"]["in_use_by"] = $this->determineUsage();
        $rubric_data["meta"]["items_used_by"] = $this->determineItemsUsage();
        $rubric_data["meta"]["errors"] = array();
        $rubric_data["rubric"] = array();
        $rubric_data["lines"] = array();
        $rubric_data["authors"] = array();
        $rubric_data["descriptors"] = array();
        $rubric_data["labels"] = array();
        return $rubric_data;
    }
}