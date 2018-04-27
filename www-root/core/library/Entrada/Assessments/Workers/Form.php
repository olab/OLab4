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
 * form related functionality and data. All input/output of for data and form
 * manipulation should utilize this class.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Form extends Entrada_Assessments_Workers_Base {
    protected $form_id = null;
    protected $adistribution_id = null;
    protected $aprogress_id = null;
    protected $rubric_limit_dataset = array();
    protected $global_storage = "Entrada_Assessments_Workers_GlobalStorage";

    public function getID() {
        return $this->form_id;
    }

    public function setID($id) {
        $this->form_id = $id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function setFormID($id) {
        $this->form_id = $id;
    }

    public function isDeleted() {
        if ($this->form_id) {
            $this->fetchData(); // builds this->dataset
            if (empty($this->dataset)) {
                return false;
            }
            if ($this->dataset["form"]["deleted_date"]) {
                return true;
            }
        }
        return false;
    }
    /**
     * Return whether this form is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->form_id) {
            $this->fetchData(); // builds this->dataset
            if (empty($this->dataset)) {
                return false;
            }
            $count_in_use = is_array($this->dataset["meta"]["in_use_by"]["assessments"]) ? count($this->dataset["meta"]["in_use_by"]["assessments"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["distributions"]) ? count($this->dataset["meta"]["in_use_by"]["distributions"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["progress_responses"]) ? count($this->dataset["meta"]["in_use_by"]["progress_responses"]) : 0;
            $count_in_use += is_array($this->dataset["meta"]["in_use_by"]["gradebook_assessments"]) ? count($this->dataset["meta"]["in_use_by"]["gradebook_assessments"]) : 0;

            if ($count_in_use == 0) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Return whether this form is editable.
     *
     * @return bool
     */
    public function isEditable() {
        if ($this->form_id) {
            $this->fetchData(); // builds this->dataset
            if (!empty($this->dataset)) {
                return $this->dataset["meta"]["is_editable"];
            }
        }
        return true;
    }

    /**
     * Fetch which assessments and distributions are using this form.
     *
     * @return array
     */
    public function inUseBy() {
        $in_use_by = array();
        if ($this->form_id) {
            $this->fetchData();
            if (!empty($this->dataset)) {
                $in_use_by = $this->dataset["meta"]["in_use_by"];
            }
        }
        return $in_use_by;
    }

    /**
     * Return whether this form should appear disabled.
     *
     * @return bool
     */
    public function isDisabled() {
        if ($this->form_id) {
            $this->fetchData();
            if (!empty($this->dataset)) {
                return $this->dataset["meta"]["is_disabled"];
            }
        }
        return false;
    }

    /**
     * Get an element's order on the form
     *
     * @param $element_id
     * @param $element_type
     * @return bool
     */
    public function getElementOrder($element_id, $element_type = "item") {
        if (!empty($this->dataset)) {
            foreach ($this->dataset["elements"] as $element) {
                if ($element["element"]["element_id"] == $element_id && $element["element"]["element_type"] == $element_type) {
                    return $element["element"]["order"];
                }
            }
        }
        return false;
    }

    /**
     * Attach an item to a form by making an element record.
     *
     * @param $item_id
     * @param int $specific_order
     * @param int|null $rubric_id
     * @param bool $ignore_when_already_attached
     * @return int
     */
    public function attachItem($item_id, $specific_order = null, $rubric_id = null, $ignore_when_already_attached = false) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        if (!$item = Models_Assessments_Item::fetchRowByID($item_id)) {
            $this->addErrorMessage($translate->_("Item not found."));
            return false;
        }
        if (!$itemtype = Models_Assessments_Itemtype::fetchRowByID($item->getItemTypeID())) {
            $this->addErrorMessage($translate->_("Sorry, we were unable to determine the item type."));
            return false;
        }
        if (!$form_record = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->form_id)) {
            $this->addErrorMessage($translate->_("Unable to attach item. Form record was not found."));
            return false;
        }
        if (!$form_type = Models_Assessments_Form_Type::fetchRowByID($form_record->getFormTypeID())) {
            $this->addErrorMessage($translate->_("Invalid form type ID."));
            return false;
        }
        $existing = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item_id, $this->form_id, "item");
        if ($existing) {
            if ($ignore_when_already_attached) {
                return true;
            } else {
                $this->addErrorMessage(sprintf($translate->_("This item (\"%s\") is already attached to the form."), $item->getItemText()));
                return false;
            }
        }
        /**
         * The specified item is not attached to the form. Now we must validate that it is possible to actually add the item, and if not, throw an error.
         */
        $encoded_attributes = $form_record->getAttributes();
        $decoded_attributes = @json_decode($encoded_attributes, true);
        if (is_array($decoded_attributes)) {
            if ($course_id = Entrada_Utilities::arrayValueOrDefault($decoded_attributes, "course_id")) {
                if (!$this->checkFormItemObjectiveConflicts($course_id, $item_id)) {
                    return false;
                }
            }
        }
        /**
         * It is possible for us to add this item to the form (no conflicts, not already attached).
         */
        $order = $specific_order
            ? $specific_order
            : Models_Assessments_Form_Element::fetchNextOrder($this->form_id);

        $form_element_data = array(
            "form_id" => $this->form_id,
            "element_type" => "item",
            "element_id" => $item->getID(),
            "one45_element_id" => $item->getOne45ElementID(),
            "order" => $order,
            "allow_comments" => 1,
            "enable_flagging" => 0,
            "updated_date" => time(),
            "updated_by" => $this->actor_proxy_id,
            "rubric_id" => $rubric_id // add it as part of a rubric, if specified
        );
        $form_element = new Models_Assessments_Form_Element($form_element_data);
        if (!$form_element->insert()) {
            $this->addErrorMessage($translate->_("Sorry, we were unable to add this item to the form."));
            return false;
        }
        $this->removeFromStorage("form_dataset", $this->form_id);
        return true;
    }

    /**
     * Attach each rubric line to the form as individual elements.
     *
     * @param int $rubric_id
     * @param int $attach_at_position
     * @param bool $ignore_when_already_attached
     * @return mixed
     */
    public function attachRubric($rubric_id, $attach_at_position = null, $ignore_when_already_attached = false) {
        global $translate;

        if (!$this->validateActor()) {
            return false;
        }
        if (!$rubric_record = Models_Assessments_Rubric::fetchRowByIDIncludeDeleted($rubric_id)) {
            $this->addErrorMessage($translate->_("The specified rubric does not exist."));
            return false;
        }
        $rubric_title = $rubric_record->getRubricTitle();
        $can_be_attached = $this->canRubricBeAttached($rubric_id);
        if (!$can_be_attached && $ignore_when_already_attached) {
            return true;
        }
        if (!$can_be_attached) {
            $this->addErrorMessage(sprintf($translate->_("We were unable to add the <strong>Grouped Item</strong> \"<strong>%s</strong>\" to the form, as it contains one or more <strong>Items</strong> that are already attached to the form."), $rubric_title));
            return false;
        }
        if ($rubric_attached = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($this->form_id, $rubric_id)) {
            $this->addErrorMessage(sprintf($translate->_("The selected <strong>Grouped Item</strong> \"<strong>%s</strong>\" is already attached to this form."), $rubric_title));
            return false;
        }
        $assessment_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($rubric_id);
        if (!$assessment_rubric_items) {
            $assessment_rubric_items = array();
        }
        if (empty($assessment_rubric_items)) {
            $this->addErrorMessage(sprintf($translate->_("The <strong>Grouped Item</strong> \"<strong>%s</strong>\" has no <strong>Items</strong> to attach."), $rubric_title));
            return false;
        }
        if (!$form_record = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->form_id)) {
            $this->addErrorMessage($translate->_("Unable to attach item. Form record was not found."));
            return false;
        }
        if (!$form_type = Models_Assessments_Form_Type::fetchRowByID($form_record->getFormTypeID())) {
            $this->addErrorMessage($translate->_("Invalid form type ID."));
            return false;
        }

        /**
         * Ensure that the items in the rubric do not conflict with the given course (if any).
         */
        $encoded_attributes = $form_record->getAttributes();
        $decoded_attributes = @json_decode($encoded_attributes, true);
        foreach ($assessment_rubric_items as $assessment_rubric_item) {
            if (is_array($decoded_attributes)) {
                if ($course_id = Entrada_Utilities::arrayValueOrDefault($decoded_attributes, "course_id")) {
                    if (!$this->checkFormItemObjectiveConflicts($course_id, $assessment_rubric_item->getItemID())) {
                        return false; // Conflicts found.
                    }
                }
            }
        }

        /**
         * No conflicts, so add a copy of each rubric line item to the from as an element.
         */
        if ($attach_at_position !== null) {
            $new_position = $attach_at_position;
        } else {
            $new_position = Models_Assessments_Form_Element::fetchNextOrder($this->form_id);
        }
        $failed_to_add = 0;
        foreach ($assessment_rubric_items as $rubric_item) {
            $form_element_data = array(
                "form_id" => $this->form_id,
                "element_type" => "item",
                "element_id" => $rubric_item->getItemID(),
                "rubric_id" => $rubric_id,
                "order" => $new_position++,
                "allow_comments" => 0,
                "enable_flagging" => 0,
                "updated_date" => time(),
                "updated_by" => $this->actor_proxy_id
            );
            $form_element = new Models_Assessments_Form_Element($form_element_data);
            if (!$form_element->insert()) {
                $failed_to_add++;
            }
        }
        if ($failed_to_add == count($assessment_rubric_items)) {
            // Failed to add ALL items
            $this->addErrorMessage($translate->_("Unfortunately, we were unable to add the selected <strong>Grouped Item</strong> to the form."));
            return false;
        } else if ($failed_to_add) {
            $this->addErrorMessage($translate->_("Unfortunately, we were unable to add one or more of the items associated with the selected <strong>Grouped Item</strong> to the form."));
        }
        $this->removeFromStorage("form_dataset", $this->form_id);
        return true;
    }

    /**
     * Remove the given rubric from this form.
     *
     * @param $rubric_id
     * @return bool
     */
    public function removeRubricFromForm($rubric_id) {
        global $translate;

        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->form_id) {
            $this->addErrorMessage($translate->_("Unable to remove rubric: No form ID specified."));
            return false;
        }
        if (!$rubric_id) {
            $this->addErrorMessage($translate->_("No rubric specified."));
            return false;
        }

        // Fetch the elements of the rubric that are attached to the form.
        $elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($this->form_id, $rubric_id);
        if (!is_array($elements) || empty($elements)) {
            $this->addErrorMessage($translate->_("The specified rubric is not attached to this form."));
            return false;
        }

        $return_status = true;
        // Clear the form elements for the given rubric ID.
        foreach ($elements as $element) {
            $element_data = $element->toArray();
            $element_data["deleted_date"] = time();
            $element_data["updated_date"] = time();
            $element_data["updated_by"] = $this->actor_proxy_id;
            $element->fromArray($element_data);
            if (!$element->update()) {
                $this->addErrorMessage($translate->_("Unable to remove the rubric element from the form."));
                application_log("error", "Tried to remove element (from rubric) from a form but failed. (afelement id= {$element->getID()})");
                $return_status = false;
            }
        }
        if ($return_status) {
            $this->removeFromStorage("form_dataset", $this->form_id);
        }
        // Done; item should be clear, or errors logged
        return $return_status;
    }

    /**
     * Remove a form element of type "item" by item_id from this form.
     *
     * @param $item_id
     * @return bool
     */
    public function removeItemFromForm($item_id) {
        global $translate;

        if (!$this->form_id) {
            $this->addErrorMessage($translate->_("Unable to remove item: No form ID specified."));
            return false;
        }

        if (!$item_id) {
            $this->addErrorMessage($translate->_("No item specified."));
            return false;
        }

        // Fetch the elements of the rubric that are attached to the form.
        if (!$form_element = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item_id, $this->form_id, "item")) {
            $this->addErrorMessage($translate->_("The specified item is not attached to this form."));
            return false;
        }

        $form_element_data = $form_element->toArray();
        $form_element_data["deleted_date"] = time();
        $form_element_data["updated_date"] = time();
        $form_element_data["updated_by"] = $this->actor_proxy_id;

        $form_element->fromArray($form_element_data);
        if (!$form_element->update()) {
            $this->addErrorMessage($translate->_("Unable to remove the rubric element from the form."));
            application_log("error", "Tried to remove item element from a form but failed. (afelement id= {$form_element->getID()})");
            $status = false;
        } else {
            $status = true;
        }
        if ($status) {
            $this->removeFromStorage("form_dataset", $this->form_id);
        }
        // Done; item should be clear, or errors logged
        return $status;
    }

    /**
     * Validate and load data into the dataset.
     *
     * This only validates the Form title and description. Element attachment is done on a per item basis.
     *
     * ADRIAN-TODO: This should leverage the default structure to populate the dataset.
     *
     * @param array
     * @param bool $validate
     * @return bool
     */
    public function loadData($data, $validate = true) {
        global $translate;

        //$default_struct = $this->buildDefaultFormStructure();
        $struct = $this->fetchData();
        $struct["form"] = $data;

        if (!isset($struct["form"]["title"])) {
            $this->addErrorMessage($translate->_("Please specify a form title."));
            return false;
        }
        if (!isset($struct["form"]["form_type_id"])) {
            $this->addErrorMessage($translate->_("Please specify a form type."));
            return false;
        }

        $this->dataset = $struct;
        return true;
    }

    /**
     * Mark the current dataset as stale and remove it from global cache.
     */
    public function invalidateDataset() {
        $this->setStale();
        if ($this->form_id) {
            $this->removeFromStorage("form_dataset", $this->form_id);
        }
    }

    /**
     * Clear internal storage and reload the internal dataset. Builds the dataset, does not return it.
     */
    public function refreshDataset() {
        $this->removeFromStorage("form_dataset", $this->form_id);
        $this->dataset = null;
        $this->buildDataset();
    }

    /**
     * Create a top-level form record. This does not affect the associations.
     *
     * @param int $course_id Optional course_id to add a form_author record for
     * @return bool
     */
    public function saveData($course_id = null) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $save_data = $this->dataset["form"];
        $save_data["form_id"] = $this->form_id;
        $save_data["form_type_id"] = isset($this->dataset["form"]["form_type_id"]) ? $this->dataset["form"]["form_type_id"] : 1; // default to the first type
        $save_data["organisation_id"] = isset($this->dataset["form"]["organisation_id"]) ? $this->dataset["form"]["organisation_id"] : $this->actor_organisation_id;
        $save_data["updated_by"] = $this->actor_proxy_id;
        $save_data["updated_date"] = time();
        $save_data["created_by"] = isset($this->dataset["form"]["created_by"]) ? $this->dataset["form"]["created_by"] : $this->actor_proxy_id;
        $save_data["created_date"] = isset($this->dataset["form"]["created_date"]) ? $this->dataset["form"]["created_date"] : time();
        $save_data["title"] = $this->dataset["form"]["title"];
        $save_data["description"] = isset($this->dataset["form"]["description"]) ? $this->dataset["form"]["description"] : null;

        $form = new Models_Assessments_Form($save_data);
        if ($this->form_id) {
            $method = "update";
        } else {
            $method = "insert";
        }

        if ($form->{$method}()) {
            $this->setID($form->getID());
            // Form saved, so invalidate cache
            if ($method == "insert") {
                $author = array(
                    "form_id" => $form->getID(),
                    "author_type" => "proxy_id",
                    "author_id" => $this->actor_proxy_id,
                    "updated_date" => time(),
                    "updated_by" => $this->actor_proxy_id,
                    "created_date" => time(),
                    "created_by" => $this->actor_proxy_id
                );
                $a = new Models_Assessments_Form_Author($author);
                $a->insert();
                if ($course_id) {
                    $author = array(
                        "form_id" => $form->getID(),
                        "author_type" => "course_id",
                        "author_id" => $course_id,
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id,
                        "created_date" => time(),
                        "created_by" => $this->actor_proxy_id
                    );
                    $a = new Models_Assessments_Form_Author($author);
                    $a->insert();
                }
            }
            $this->removeFromStorage("form_dataset", $this->form_id);
            return true;

        } else {
            $this->addErrorMessage($translate->_("Unable to save form data."));
            application_log("error", "Unable to save form id = '{$this->form_id}', method = '$method'");
            return false;
        }
    }

    /**
     * Fetch all form data points, return in a data structure. Override existing internal dataset.
     * Returns the dataset; doesn't need to, though, as the internal dataset is built as required by buildDataset.
     *
     * @param bool $cached
     * @return array
     */
    public function fetchData($cached = true) {
        if ($cached) {
            // Attempt to find a cached version of the dataset
            if ($this->isInStorage("form_dataset", $this->form_id)) {
                $this->dataset = $this->fetchFromStorage("form_dataset", $this->form_id);
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
            $this->addToStorage("form_dataset", $this->dataset, $this->form_id);
        }
        return $this->dataset;
    }

    /**
     * Update the form's primitive fields (description/title)
     *
     * @param string $form_title
     * @param string $description
     * @return bool
     */
    public function updateFormPrimitives($form_title, $description) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $return_status = false;
        if ($form = Models_Assessments_Form::fetchRowByID($this->form_id)) {
            $updates = $form->toArray();
            $updates["title"] = $form_title;
            $updates["description"] = $description;
            $updates["updated_date"] = time();
            $updates["updated_by"] = $this->actor_proxy_id;
            $form->fromArray($updates);
            if (!$form->update()) {
                $this->addErrorMessage($translate->_("Unable to update form."));
                application_log("error", "updateFormPrimitives failed: form_id = '{$this->form_id}' title: '$form_title' description: '$description'");
            } else {
                $return_status = true;
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to update form as it was not found."));
        }
        if ($return_status) {
            $this->removeFromStorage("form_dataset", $this->form_id);
        }
        return $return_status;
    }

    /**
     * Copy the form by form ID given and create the relevant elements and authors.
     * Returns the ID of the created form, false on failure.
     *
     * TODO: in the future, use the loadData/saveData/duplicate pattern in the items object.
     *
     * @param $old_form_id
     * @param string $new_form_title
     * @return bool|int
     */
    public function copy($old_form_id, $new_form_title = null) {
        global $translate;
        $old_form = Models_Assessments_Form::fetchRowByID($old_form_id);

        $element_errors = 0;
        $author_errors = 0;

        if ($new_form_title !== null) {
            $new_form_data["title"] = $new_form_title;
        }
        // Create a new form to copy to
        $new_form_data["organisation_id"] = $this->actor_organisation_id;
        $new_form_data["created_date"] = time();
        $new_form_data["updated_date"] = time();
        $new_form_data["form_type_id"] = $old_form->getFormTypeID();
        $new_form_data["created_by"] = $this->actor_proxy_id;
        $new_form_data["updated_by"] = $this->actor_proxy_id;
        $new_form_data["description"] = $old_form->getDescription();
        $new_form_data["attributes"] = $old_form->getAttributes();
        $form = new Models_Assessments_Form($new_form_data);
        if (!$form->insert()) {
            $this->addErrorMessage($translate->_("Failed to copy form."));
            return false;
        }

        // Retrieve all authors from the previous form
        $authors = Models_Assessments_Form_Author::fetchAllByFormID($old_form_id, $this->actor_organisation_id);
        if ($authors) {
            // Insert copies of the authors with the newly created form's ID
            foreach ($authors as $author) {
                $author_data = array(
                    "form_id"               => $form->getID(),
                    "author_type"           => $author->getAuthorType(),
                    "author_id"             => $author->getAuthorId(),
                    "created_date"          => time(),
                    "created_by"            => $this->actor_proxy_id,
                    "updated_date"          => $author->getUpdatedDate(),
                    "updated_by"            => $author->getUpdatedBy()
                );
                $author = new Models_Assessments_Form_Author($author_data);
                if (!$author->insert()) {
                    if (!$author_errors) {
                        $this->addErrorMessage($translate->_("An error occured while adding an author to the form."));
                    }
                    $author_errors++;
                }
            }
        }

        // Retrieve the elements from the previous form
        $elements = Models_Assessments_Form_Element::fetchAllByFormID($old_form_id);
        if ($elements) {
            // Insert copies of elements with the newly created form's ID
            foreach ($elements as $element) {
                if ($element->getElementId() && $element->getElementType() == "item") {
                    if (!$item_record = Models_Assessments_Item::fetchRowByID($element->getElementId())) {
                        continue; // it's been deleted, so skip it (don't copy it to the new form)
                    }
                }
                $element_data = array(
                    "form_id"           => $form->getID(),
                    "element_type"      => $element->getElementType(),
                    "element_id"        => $element->getElementId(),
                    "element_text"      => $element->getElementText(),
                    "rubric_id"         => $element->getRubricId(),
                    "order"             => $element->getOrder(),
                    "allow_comments"    => $element->getAllowComments(),
                    "enable_flagging"   => $element->getEnableFlagging(),
                    "updated_date"      => time(),
                    "updated_by"        => $this->actor_proxy_id
                );
                $element = new Models_Assessments_Form_Element($element_data);
                if (!$element->insert()) {
                    if (!$element_errors) {
                        $this->addErrorMessage($translate->_("An error occurred while adding an element to a form."));
                    }
                    $element_errors++;
                }
            }
        }
        if ($element_errors) {
            return false;
        }

        // For author errors, we don't care; the records were all created and are still usable.
        return $form->getID();
    }

    //-- Protected --//

    /**
     * Determine if a form should appear disabled.
     *
     * The form should appear disabled if:
     * - The distribution is deleted
     * - The assessment is deleted
     * - The progress record that exists does not belong to the viewer
     * - The progress record that exists is not "inprogress"
     *
     * Currently, these checks are handled by the calling pages, not this object.
     *
     * @return bool
     */
    protected function determineDisabled() {
        // check $this->actor_proxy_id /actor_org id?
        if ($this->aprogress_id && $this->determine_meta) {
            if ($progress_record = Models_Assessments_Progress::fetchRowByID($this->aprogress_id)) {
                if ($progress_record->getProgressValue() != "inprogress") {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if a form should appear editable.
     *
     * @return bool
     */
    protected function determineEditable() {
        // A form is editable no progress has been submitted on it.

        if (!$this->form_id || !$this->determine_meta) {
            return true;
        }

        // Check if any progress records exist for this form
        $progress_ids = Models_Assessments_Progress_Response::fetchInProgressAndCompleteProgressResponseIDsByFormIDs(array($this->form_id));
        if (!empty($progress_ids)) {
            return false; // There's some in-progress or complete data using a form that uses this item. That means it's not editable.
        }

        // Check if any assessments are delivered, but not started, that use this form
        $assessment_ids = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs(array($this->form_id));
        if (!empty($assessment_ids)) {
            // There are some assessments that use this form ID.
            return false;
        }

        // Not delivered, so return true.
        return true;
    }

    /**
     * Find whether this form is in use.
     *
     * @return array
     */
    protected function determineUsage() {
        $in_use_by = array(
            "distributions" => array(),
            "assessments" => array(),
            "progress_responses" => array(),
            "gradebook_assessments" => array()
        );
        if ($this->form_id && $this->determine_meta) {

            // Check if any distributions use this form
            if ($distributions = Models_Assessments_Distribution::fetchAllByFormID($this->form_id)) { // honours deleted date
                foreach ($distributions as $distribution) {
                    $in_use_by["distributions"][$distribution->getID()] = $distribution->getID();
                }
            }

            // Fetch relevant assessment IDs. This call returns all IDs, except when the assessment record is deleted.
            $in_use_by["assessments"] = Models_Assessments_Assessor::fetchDassessmentIDsByFormIDs(array($this->form_id));

            // Fetch relevant progress response IDs
            $in_use_by["progress_responses"] = Models_Assessments_Progress_Response::fetchInProgressAndCompleteProgressResponseIDsByFormIDs(array($this->form_id));

            // Fetch relevant gradebook assessment IDs
            $gradebook_assessment_model = new Models_Gradebook_Assessment();
            $gradebook_assessment_ids = $gradebook_assessment_model->fetchAssessmentIDsByFormID($this->form_id);
            if ($gradebook_assessment_ids && is_array($gradebook_assessment_ids) && !empty($gradebook_assessment_ids)) {
                foreach ($gradebook_assessment_ids as $gradebook_assessment) {
                    $in_use_by["gradebook_assessments"][] = $gradebook_assessment["assessment_id"];
                }
            }
        }
        return $in_use_by;
    }

    //-- Private methods --//

    /**
     * Given the rubric id, check if the items contained therein are individually attached to the form already. Return true
     * when the rubric can be attached (meaning no items in the rubric are already attached).
     *
     * @param $rubric_id
     * @return bool
     */
    private function canRubricBeAttached($rubric_id) {
        if ($this->form_id && $rubric_id) {

            // Fetch all rubric items, check if any of them are already in use on this form
            $current_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($rubric_id);
            if (!is_array($current_rubric_items) || empty($current_rubric_items)) {
                return true; // No items in the rubric, so technically yes, we can add the empty rubric to the form.
            }

            // Fetch all form elements, and compare them to the rubric items
            $current_form_elements = Models_Assessments_Form_Element::fetchAllByFormID($this->form_id);
            if (is_array($current_form_elements)) {
                foreach ($current_form_elements as $form_element) {
                    foreach ($current_rubric_items as $rubric_item) {
                        if ($rubric_item->getItemID() == $form_element->getElementID()) {
                            // It exists on the form already, we can't add this rubric
                            return false;
                        }
                    }
                }
                return true; // If we've gotten this far, then we didn't find a match, so we can return true.
            } else {
                return true; // no form elements, so we can say yes to attaching the rubric.
            }
        }
        return false;
    }

    /**
     * Check if the given item ID has any objectives that are mapped to an objective tree that conflicts with the given course ID.
     *
     * @param $course_id
     * @param $item_id
     * @return bool
     */
    private function checkFormItemObjectiveConflicts($course_id, $item_id) {
        global $translate;
        if (!$form_record = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->form_id)) {
            $this->addErrorMessage($translate->_("Unable to attach item. Form record was not found."));
            return false;
        }
        if (!$form_type = Models_Assessments_Form_Type::fetchRowByID($form_record->getFormTypeID())) {
            $this->addErrorMessage($translate->_("Invalid form type ID."));
            return false;
        }
        /**
         * Check if the given item has any objectives that conflict with the given course ID, when the form type is not CBME
         * Based on whatever form category we're working on, we can remove ineligble item IDs
         **/
        $category = $form_type->getCategory();
        switch ($category) {
            case "cbme_form":
                /**
                 * For "CBME forms", we have to ensure the items being added to the form are
                 * tagged with objectives from the correct tree, otherwise we reject them.
                 */
                $cbme_objective_tree = new Entrada_CBME_ObjectiveTree($this->buildActorArray(array("course_id" => $course_id)));
                $singleton_item = new Entrada_Assessments_Workers_Item(array("item_id" => $item_id, "limit_dataset" => array("objectives")));
                $item_data = $singleton_item->fetchData();
                foreach ($item_data["objectives_tree_ids"] as $objective_id => $objective_tree_id) {
                    if (!$found_objective = $cbme_objective_tree->findNodesByObjectiveID($objective_id)) {
                        // The objective was not found in the tree for this course, so we can deny adding it to the form.
                        $this->addErrorMessage($translate->_("The selected item(s) cannot be added due to conflicting objective information. The objective(s) currently attached to the item are for a different program."));
                        return false;
                    }
                }
                break;
            case "form":
            case "blueprint":
            default:
                // Default is to not check for objective conflicts.
                break;
        }
        return true;
    }

    /**
     * Fetch all related data for a given form ID.
     *
     * @return bool
     */
    private function buildDataset() {

        if (!$this->form_id) {
            application_log("error", "fetchFormElements: Unable to fetch form without ID");
            return false;
        }

        // We always fetch the form record.
        $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->form_id);
        if (!$form) {
            // Invalid form ID
            application_log("error", "fetchFormElements: Invalid form ID (form record doesn't exist)");
            return false;
        }

        $json_form_attributes = $form->getAttributes();
        $decoded_attributes = @json_decode($json_form_attributes, true);
        if (is_array($decoded_attributes)) {
            $form_attributes = $decoded_attributes;
        } else {
            $form_attributes = array();
        }

        $progress = array();
        if (empty($this->limit_dataset) || in_array("progress", $this->limit_dataset)) {
            // Fetch progress data, if we have a progress ID
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
        }

        $authors = array();
        if (empty($this->limit_dataset) || in_array("authors", $this->limit_dataset)) {
            // Fetch authors
            // TODO: Convert this, and all usage of it, to being array based, instead of storing the objects.
            $authors = Models_Assessments_Form_Author::fetchAllRecords($this->form_id);
            if (!is_array($authors)) {
                $authors = array();
            }
        }

        // Fetch form elements
        $elements = array();
        $rubrics = array();
        if (empty($this->limit_dataset) || in_array("elements", $this->limit_dataset)) {
            $form_elements = Models_Assessments_Form_Element::fetchAllByFormIDOrdered($this->form_id);
            if ($form_elements && !empty($form_elements)) {

                // We have a set of form elements for a given form, so let's iterate through it and fetch responses for each one.
                foreach ($form_elements as $iresponse_id => $form_element_object) {
                    $afelement_id = $form_element_object->getID();
                    $item_id = $form_element_object->getElementID();
                    $elements[$afelement_id]["element"] = $form_element_object->toArray();
                    $elements[$afelement_id]["element"]["organisation_id"] = $form->getOrganisationID(); // Pass the org ID along to the element records.
                    $elements[$afelement_id]["item"] = array();
                    $elements[$afelement_id]["responses"] = array();
                    $elements[$afelement_id]["tags"] = array();
                    if ($form_element_object->getElementType() == "item") {
                        $elements[$afelement_id]["item"] = $this->fetchItemByItemID($item_id);
                        $elements[$afelement_id]["responses"] = $this->fetchResponsesByItemID($item_id);
                        $elements[$afelement_id]["tags"] = $this->fetchTagsByItemID($item_id);
                    }
                    foreach ($elements[$afelement_id]["responses"] as $i => $response) {
                        // Mark if this response has been selected (if there is no progress data, this is always false)
                        $elements[$afelement_id]["responses"][$i]["is_selected"] = $this->isResponseSelected($progress, $response["iresponse_id"]);
                    }
                    if (!empty($elements[$afelement_id]["item"])) {
                        $progress_responses_copy = isset($progress["progress_responses"]) ? $progress["progress_responses"] : array(); // passed by reference
                        // Iterate through the progress array and check if any comments are flagged or set
                        $flagging_info = $this->getCommentFlaggingInfo(
                            $elements[$afelement_id]["item"]["comment_type"],
                            $elements[$afelement_id]["responses"],
                            $progress_responses_copy,
                            $elements[$afelement_id]["item"]["allow_default"],
                            $elements[$afelement_id]["item"]["default_response"]
                        );
                        $elements[$afelement_id]["item"]["render_comment_container"] = $flagging_info["render_comment_container"];
                        $elements[$afelement_id]["item"]["comment_container_visible"] = $flagging_info["comment_container_visible"];
                        $elements[$afelement_id]["item"]["comment_related_afelement_id"] = $flagging_info["afelement_id"];
                        $elements[$afelement_id]["item"]["item_comment_text"] = $flagging_info["comment_text"];

                        // For "comment on flagged" comment types, we only display the container when a flagged item is selected.
                        foreach ($elements[$afelement_id]["responses"] as $i => $response) {
                            if ($response["is_selected"] && $response["flag_response"] && $elements[$afelement_id]["item"]["comment_type"] != "disabled") {
                                $elements[$afelement_id]["item"]["render_comment_container"] = true; // override
                                $elements[$afelement_id]["item"]["comment_container_visible"] = true; // override
                            }
                        }
                    }
                }

                // Add response descriptor text
                foreach ($elements as $afelement_id => $element_data) {
                    if (!empty($element_data["responses"])) {
                        foreach ($element_data["responses"] as $iresponse_id => $response) {

                            $ardescriptor_id = $response["ardescriptor_id"];
                            $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = "";

                            // If there is a descriptor ID set, we fetch it and store it.
                            // If there's a response descriptor set, and no response text, we show the response descriptor.
                            if ($ardescriptor_id) {
                                if ($this->isInStorage("response-descriptors", $ardescriptor_id)) {
                                    if ($descriptor_record = $this->fetchFromStorage("response-descriptors", $ardescriptor_id)) {
                                        $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = $descriptor_record->getDescriptor();
                                    }
                                } else {
                                    $descriptor_text = null;
                                    if ($response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($ardescriptor_id)) {
                                        $descriptor_text = $response_descriptor->getDescriptor();
                                    }
                                    $this->addToStorage("response-descriptors", $response_descriptor, $ardescriptor_id);
                                    $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = $descriptor_text;
                                }
                                if ($elements[$afelement_id]["item"]["itemtype_id"]) {
                                    // Adjust the response text if none is specified, but a descriptor is.
                                    if (!$elements[$afelement_id]["responses"][$iresponse_id]["text"] &&
                                        $elements[$afelement_id]["responses"][$iresponse_id]["ardescriptor_id"] &&
                                        $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] &&
                                        !Entrada_Assessments_Workers_Item::usesDescriptorInsteadOfResponseText($elements[$afelement_id]["item"]["shortname"])
                                    ) {
                                        $elements[$afelement_id]["responses"][$iresponse_id]["text"] = $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"];
                                    }
                                }
                            }
                        }
                    }
                }

                if (empty($this->limit_dataset) || in_array("rubrics", $this->limit_dataset)) {
                    // Assemble the rubrics
                    foreach ($elements as $afelement_id => $element_data) {
                        if ($element_data["element"]["rubric_id"]) {
                            $rubric_id = $element_data["element"]["rubric_id"];
                            if (!isset($rubrics[$rubric_id])) {
                                $rubric_construction = array(
                                    "rubric_id" => $rubric_id,
                                    "form_id" => $this->form_id,
                                    "aprogress_id" => $this->aprogress_id,
                                    "associated_elements" => $elements,
                                );
                                $rubric_construction = array_merge($rubric_construction, array("limit_dataset" => $this->rubric_limit_dataset));
                                $rubric_object = new Entrada_Assessments_Workers_Rubric($this->buildActorArray($rubric_construction));
                                $rubrics[$rubric_id] = $rubric_object->fetchData();
                            }
                        }
                    }
                }
            }
        }

        $distribution = array();
        if (empty($this->limit_dataset) || in_array("distribution", $this->limit_dataset)) {
            if ($this->adistribution_id) {
                // Fetch the specific distribution this form is used by
                $distribution_record = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($this->adistribution_id);
                if ($distribution_record) {
                    $distribution = $distribution_record->toArray();
                }
            }
        }

        $form_meta = array();
        if (empty($this->limit_dataset) || in_array("form_type_meta", $this->limit_dataset)) {
            // Fetch the form type metadata (not the same as this object's meta array)
            if ($form_metadata = Models_Assessments_Form_TypeMeta::fetchAllByFormTypeIDOrganisationID($form->getFormTypeID(), $this->actor_organisation_id)) {
                foreach ($form_metadata as $meta) {
                    $form_meta[$meta->getID()] = $meta->toArray();
                }
            }
        }

        $collected_form_objectives = array();
        if (empty($this->limit_dataset) || in_array("objectives", $this->limit_dataset)) {
            // Fetch objectives tagged to the form
            if ($form_objectives = Models_Assessments_Form_Objective::fetchAllByFormID($this->form_id)) { // returns array
                foreach ($form_objectives as $form_objective) {
                    $collected_form_objectives[] = $form_objective;
                }
            }
        }

        $collected_item_objectives = array();
        if (empty($this->limit_dataset) || in_array("item_objectives", $this->limit_dataset)) {
            // Fetch objectives tagged to items used by the form
            if ($item_objectives = Models_Assessments_Item_Objective::fetchObjectiveDataByFormID($this->form_id)) {
                foreach ($item_objectives as $item_objective) {
                    $collected_item_objectives[] = $item_objective;
                }
            }
        }

        // Build and return the result set
        $resultset = $this->buildDefaultFormStructure();
        $resultset["meta"]["rubric_count"] = count($rubrics);
        $resultset["meta"]["element_count"] = count($elements);
        $resultset["form"] = $form->toArray();
        $resultset["form_attributes"] = $form_attributes;
        $resultset["form_type_meta"] = $form_meta;
        $resultset["authors"] = $authors;
        $resultset["elements"] = $elements;
        $resultset["rubrics"] = $rubrics;
        $resultset["progress"] = $progress;
        $resultset["distribution"] = $distribution;
        $resultset["objectives"] = $collected_form_objectives;
        $resultset["item_objectives"] = $collected_item_objectives;

        $this->dataset = $resultset;
        return true;
    }

    /**
     * Build the default form data structure.
     *
     * @return mixed
     */
    private function buildDefaultFormStructure() {
        $dataset = array();
        $dataset["is_stale"] = false;
        $dataset["meta"] = array();
        $dataset["meta"]["form_id"] = $this->form_id;
        $dataset["meta"]["in_use_by"] = $this->determineUsage();
        $dataset["meta"]["is_editable"] = $this->determineEditable();
        $dataset["meta"]["is_disabled"] = $this->determineDisabled();
        $dataset["meta"]["element_count"] = 0;
        $dataset["meta"]["rubric_count"] = 0;
        $dataset["form"] = array();
        $dataset["form_attributes"] = array();
        $dataset["form_type_meta"] = array();
        $dataset["authors"] = array();
        $dataset["elements"] = array();
        $dataset["rubrics"] = array();
        $dataset["progress"] = array();
        $dataset["distribution"] = array();
        $dataset["objectives"] = array();
        $dataset["item_objectives"] = array();
        return $dataset;
    }
}