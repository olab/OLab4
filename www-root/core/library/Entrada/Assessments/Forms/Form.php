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
class Entrada_Assessments_Forms_Form extends Entrada_Assessments_Forms_Base {

    protected $form_id = null;
    protected $adistribution_id = null;
    protected $dassessment_id = null;
    protected $aprogress_id = null;

    public function getID() {
        return $this->form_id;
    }

    public function setID($id) {
        $this->form_id = $id;
    }

    /**
     * Return whether this form is in use.
     *
     * @return bool
     */
    public function isInUse() {
        if ($this->form_id) {
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $count_in_use = count($this->dataset["meta"]["in_use_by"]["assessments"]);
            $count_in_use += count($this->dataset["meta"]["in_use_by"]["distributions"]);
            $count_in_use += count($this->dataset["meta"]["in_use_by"]["progress_responses"]);
            $count_in_use += count($this->dataset["meta"]["in_use_by"]["gradebook_assessments"]);

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
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            return $this->dataset["meta"]["is_editable"];
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
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            $in_use_by = $this->dataset["meta"]["in_use_by"];
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
            if (empty($this->dataset)) {
                $this->fetchData();
            }
            return $this->dataset["meta"]["is_disabled"];
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

        $this->setErrorStatus(false); // false = has error

        if ($item = Models_Assessments_Item::fetchRowByID($item_id)) {
            if ($itemtype = Models_Assessments_Itemtype::fetchRowByID($item->getItemTypeID())) {
                $existing = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item_id, $this->form_id, "item");
                if (!$existing) {
                    $form_element_data = array(
                        "form_id" => $this->form_id,
                        "element_type" => "item",
                        "element_id" => $item->getID(),
                        "one45_element_id" => $item->getOne45ElementID(),
                        "order" => $specific_order ? $specific_order : Models_Assessments_Form_Element::fetchNextOrder($this->form_id),
                        "allow_comments" => 1,
                        "enable_flagging" => 0,
                        "updated_date" => time(),
                        "updated_by" => $this->actor_proxy_id,
                        "rubric_id" => $rubric_id // add it as part of a rubric, if specified
                    );
                    $form_element = new Models_Assessments_Form_Element($form_element_data);
                    if ($form_element->insert()) {
                        $this->setErrorStatus(true); // Status true = no error
                    } else {
                        $this->addErrorMessage($translate->_("Sorry, we were unable to add this item to the form."));
                    }
                } else {
                    if ($ignore_when_already_attached) {
                        return $this->setErrorStatus(true);
                    } else {
                        $this->addErrorMessage(sprintf($translate->_("This item (\"%s\") is already attached to the form."), $item->getItemText()));
                    }
                }
            } else{
                $this->addErrorMessage($translate->_("Sorry, we were unable to determine the item type."));
            }
        } else {
            // Item not found
            $this->addErrorMessage($translate->_("Item not found."));
        }
        return $this->getErrorStatus();
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

        $this->setErrorStatus(false); // default: false = error
        $failed_to_add = false;

        $can_be_attached = $this->canRubricBeAttached($rubric_id);
        if (!$can_be_attached && $ignore_when_already_attached) {
            return $this->setErrorStatus(true);
        }

        if (!$rubric_record = Models_Assessments_Rubric::fetchRowByIDIncludeDeleted($rubric_id)) {
            $this->addErrorMessage($translate->_("The specified rubric does not exist."));
            return $this->getErrorStatus();
        }

        $rubric_title = $rubric_record->getRubricTitle();

        if ($can_be_attached) {
            $rubric_attached = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($this->form_id, $rubric_id);
            if (!$rubric_attached) {
                $assessment_rubric_items = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($rubric_id);
                if (!$assessment_rubric_items) {
                    $assessment_rubric_items = array();
                }
                if ($attach_at_position !== null) {
                    $new_position = $attach_at_position;
                } else {
                    $new_position = Models_Assessments_Form_Element::fetchNextOrder($this->form_id);
                }
                if (!empty($assessment_rubric_items)) {
                    // Add a copy of each rubric line item to the from as an element.
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
                        if ($form_element->insert()) {
                            $this->setErrorStatus(true);
                        } else {
                            $failed_to_add = true;
                        }
                    }
                    if ($failed_to_add) {
                        $this->setErrorStatus(false);
                        $this->addErrorMessage($translate->_("Unfortunately, we were unable to add one or more of the items associated with the selected <strong>Grouped Items</strong> to the form."));
                    }
                } else {
                    $this->addErrorMessage(sprintf($translate->_("The <strong>Grouped Item</strong> \"<strong>%s</strong>\" has no <strong>Items</strong> to attach."), $rubric_title));
                }
            } else {
                $this->addErrorMessage(sprintf($translate->_("The selected <strong>Grouped Item</strong> \"<strong>%s</strong>\" is already attached to this form."), $rubric_title));
            }
        } else {
            $this->addErrorMessage(sprintf($translate->_("We were unable to add the <strong>Grouped Item</strong> \"<strong>%s</strong>\" to the form, as it contains one or more <strong>Items</strong> that are already attached to the form."), $rubric_title));
        }
        return $this->getErrorStatus();
    }

    /**
     * Remove the given rubric from this form.
     *
     * @param $rubric_id
     * @return bool
     */
    public function removeRubricFromForm($rubric_id) {
        global $translate;
        $this->setErrorStatus(false);

        if (!$this->form_id) {
            $this->addErrorMessage($translate->_("Unable to remove rubric: No form ID specified."));
            return $this->getErrorStatus();
        }

        if (!$rubric_id) {
            $this->addErrorMessage($translate->_("No rubric specified."));
            return $this->getErrorStatus();
        }

        // Fetch the elements of the rubric that are attached to the form.
        $elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($this->form_id, $rubric_id);
        if (!is_array($elements) || empty($elements)) {
            $this->addErrorMessage($translate->_("The specified rubric is not attached to this form."));
            return $this->getErrorStatus();
        }

        $status = true;
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
                $status = false;
            }
        }

        // Done; item should be clear, or errors logged
        return $this->setErrorStatus($status);
    }

    /**
     * Remove a form element of type "item" by item_id from this form.
     *
     * @param $item_id
     * @return mixed
     */
    public function removeItemFromForm($item_id) {
        global $translate;
        $this->setErrorStatus(false);

        if (!$this->form_id) {
            $this->addErrorMessage($translate->_("Unable to remove item: No form ID specified."));
            return $this->getErrorStatus();
        }

        if (!$item_id) {
            $this->addErrorMessage($translate->_("No item specified."));
            return $this->getErrorStatus();
        }

        // Fetch the elements of the rubric that are attached to the form.
        if (!$form_element = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item_id, $this->form_id, "item")) {
            $this->addErrorMessage($translate->_("The specified item is not attached to this form."));
            return $this->getErrorStatus();
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

        // Done; item should be clear, or errors logged
        return $this->setErrorStatus($status);
    }

    /**
     * Validate and load data into the dataset.
     *
     * This only validates the Form title and description. Element attachment is done on a per item basis.
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

        if (!$struct["form"]["title"]) {
            $this->addErrorMessage($translate->_("Please specify a form title."));
            return false;
        }
        $this->dataset = $struct;
        return true;
    }

    /**
     * Clear internal storage and reload the internal dataset. Builds the dataset, does not return it.
     */
    public function refreshDataset() {
        $this->clearStorage();
        $this->dataset = null;
        $this->buildDataset();
    }

    /**
     * Create a top-level form record. This does not affect the associations.
     */
    public function saveData() {
        global $translate;

        $save_data = $this->dataset["form"];
        $save_data["form_id"] = $this->form_id;
        $save_data["organisation_id"] = @$this->dataset["form"]["organisation_id"] ? $this->dataset["form"]["organisation_id"] : $this->actor_organisation_id;
        $save_data["updated_by"] = $this->actor_proxy_id;
        $save_data["updated_date"] = time();
        $save_data["created_by"] = @$this->dataset["form"]["created_by"] ? $this->dataset["form"]["created_by"] : $this->actor_proxy_id;
        $save_data["created_date"] = @$this->dataset["form"]["created_date"] ? $this->dataset["form"]["created_date"] : time();
        $save_data["title"] = $this->dataset["form"]["title"];
        $save_data["description"] = $this->dataset["form"]["description"];

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
            }
            return true;

        } else {
            $this->addErrorMessage($translate->_("Unable to save form data."));
            application_log("error", "Unable to save form id = '{$this->form_id}', method = '$method'");
            return false;
        }
    }

    /**
     * Fetch all form data points, return in a data structure. Override existing internal dataset.
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
     * Update the form's primitive fields (description/title)
     *
     * @param string $form_title
     * @param string $description
     * @return bool
     */
    public function updateFormPrimitives($form_title, $description) {
        global $translate;
        $this->setErrorStatus(false);
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
                $this->setErrorStatus(true); // OK
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to update form as it was not found."));
        }
        return $this->getErrorStatus();
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
        // check $this->actor_proxy_id /actor_org id
        if ($this->aprogress_id) {
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

        if (!$this->form_id) {
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
        if ($this->form_id) {

            // Check if any distributions use this form
            if ($distributions = Models_Assessments_Distribution::fetchAllByFormID($this->form_id)) { // honours deleted date
                foreach ($distributions as $distribution) {
                    $in_use_by["distributions"][$distribution->getID()] = $distribution->getID();
                }
            }

            // Fetch relevant assessment IDs
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
     * Fetch all related data for a given form ID.
     *
     * @return bool
     */
    private function buildDataset() {

        if (!$this->form_id) {
            application_log("error", "fetchFormElements: Unable to fetch form without ID");
            return false;
        }

        $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->form_id);
        if (!$form) {
            // Invalid form ID
            application_log("error", "fetchFormElements: Invalid form ID (form record doesn't exist)");
            return false;
        }

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

        // Fetch authors
        $authors = Models_Assessments_Form_Author::fetchAllRecords($this->form_id);
        if (!is_array($authors)) {
            $authors = array();
        }

        // Fetch form elements
        $elements = array();
        $rubrics = array();
        $form_elements = Models_Assessments_Form_Element::fetchAllByFormIDOrdered($this->form_id);
        if ($form_elements && !empty($form_elements)) {

            // We have a set of form elements for a given form, so let's iterate through it and fetch responses for each one.
            foreach ($form_elements as $iresponse_id => $form_element_object) {
                $afelement_id = $form_element_object->getID();
                $item_id = $form_element_object->getElementID();
                $elements[$afelement_id]["element"] = $form_element_object->toArray();
                $elements[$afelement_id]["item"] = $this->fetchItemByItemID($item_id);
                $elements[$afelement_id]["tags"] = $this->fetchTagsByItemID($item_id);
                $elements[$afelement_id]["responses"] = $this->fetchResponsesByItemID($item_id);

                foreach ($elements[$afelement_id]["responses"] as $i => $response) {
                    // Mark if it this response has been selected (if no progress, this is always false)
                    $elements[$afelement_id]["responses"][$i]["is_selected"] = $this->isResponseSelected($progress, $response["iresponse_id"]);
                }

                if (!empty($elements[$afelement_id]["item"])) {

                    $progress_responses_copy = @$progress["progress_responses"] ? $progress["progress_responses"] : array();
                    // Iterate through the progress array and check if any comments are flagged or set
                    $flagging_info = $this->getCommentFlaggingInfo(
                        $elements[$afelement_id]["item"]["comment_type"],
                        $elements[$afelement_id]["responses"],
                        $progress_responses_copy);

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
                        $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = $elements[$afelement_id]["responses"][$iresponse_id]["response_text"]; // default is to the the response text

                        // If there is a descriptor ID set, we fetch it and override the response text.
                        if ($ardescriptor_id) {
                            if ($this->isInStorage("response-descriptors", $ardescriptor_id)) {
                                $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = $this->fetchFromStorage("response-descriptors", $ardescriptor_id);
                            } else {
                                $descriptor_text = null;
                                if ($response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($ardescriptor_id)) {
                                    $descriptor_text = $response_descriptor->getDescriptor();
                                }
                                $this->addToStorage("response-descriptors", $descriptor_text, $ardescriptor_id);
                                $elements[$afelement_id]["responses"][$iresponse_id]["response_descriptor_text"] = $descriptor_text;
                            }
                        }
                    }
                }
            }

            // Assemble the rubrics
            foreach ($elements as $afelement_id => $element_data) {
                if ($element_data["element"]["rubric_id"]) {
                    $rubric_id = $element_data["element"]["rubric_id"];
                    if (!isset($rubrics[$rubric_id])) {
                        $rubric_object = new Entrada_Assessments_Forms_Rubric(array(
                                "actor_proxy_id" => $this->actor_proxy_id,
                                "actor_organisation_id" => $this->actor_organisation_id,
                                "rubric_id" => $rubric_id,
                                "form_id" => $this->form_id,
                                "aprogress_id" => $this->aprogress_id,
                                "associated_elements" => $elements
                            )
                        );
                        $rubrics[$rubric_id] = $rubric_object->fetchData();
                    }
                }
            }
        }

        $distribution = array();
        if ($this->adistribution_id) {
            // Fetch the specific distribution this form is used by
            $distribution_record = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($this->adistribution_id);
            if ($distribution_record) {
                $distribution = $distribution_record->toArray();
            }
        }

        // Build and return the result set
        $resultset = $this->buildDefaultFormStructure();
        $resultset["meta"]["rubric_count"] = count($rubrics);
        $resultset["meta"]["element_count"] = count($elements);
        $resultset["form"] = $form->toArray();
        $resultset["authors"] = $authors;
        $resultset["elements"] = $elements;
        $resultset["rubrics"] = $rubrics;
        $resultset["progress"] = $progress;
        $resultset["distribution"] = $distribution;

        $this->dataset = $resultset;
        return true;
    }

    /**
     * Build the default form data structure.
     *
     * @return mixed
     */
    private function buildDefaultFormStructure() {
        $resultset["meta"] = array();
        $resultset["meta"]["form_id"] = $this->form_id;
        $resultset["meta"]["in_use_by"] = $this->determineUsage();
        $resultset["meta"]["is_editable"] = $this->determineEditable();
        $resultset["meta"]["is_disabled"] = $this->determineDisabled();
        $resultset["meta"]["errors"] = array();
        $resultset["meta"]["element_count"] = 0;
        $resultset["meta"]["rubric_count"] = 0;
        $resultset["form"] = array();
        $resultset["authors"] = array();
        $resultset["elements"] = array();
        $resultset["rubrics"] = array();
        $resultset["progress"] = array();
        $resultset["distribution"] = array();
        return $resultset;
    }
}