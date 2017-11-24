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
 * This is an abstraction layer for assessment forms.
 * This is the main point of interaction between any (pseudo) controller
 * and/or view functionality.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Forms extends Entrada_Assessments_Base {

    protected $actor_proxy_id = null;
    protected $actor_organisation_id = null;

    private $error_messages = array();

    // Specific to type
    protected $form_id = null;
    protected $item_id = null;
    protected $rubric_id = null;

    // Optionals
    protected $adistribution_id = null;
    protected $dassessment_id = null;
    protected $aprogress_id = null;

    public function __construct($arr = array()) {
        parent::__construct($arr);
        Entrada_Utilities_FormStorageSessionHelper::configure();
    }

    //-- Getters & Setters --//

    public function getErrorMessages() {
        return $this->error_messages;
    }

    public function addErrorMessage($single_error_string) {
        $this->error_messages[] = $single_error_string;
    }

    public function addErrorMessages($error_strings) {
        $this->error_messages = array_merge($this->error_messages, $error_strings);
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getRubricID() {
        return $this->rubric_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function setFormID($id) {
        $this->form_id = $id;
    }

    public function setRubricID($id) {
        $this->rubric_id = $id;
    }

    public function setItemID($id) {
        $this->item_id = $id;
    }

    //-- View Helpers --//

    /**
     * Fetch the associated comment for an element given the related progress responses data.
     *
     * @param array $element
     * @param array $progress
     * @return null
     */
    public static function getItemComment(&$element, &$progress) {
        if (is_array($element) && is_array($progress) && isset($progress["progress_responses"]) && is_array($progress["progress_responses"]) && !empty($progress["progress_responses"])) {
            $afelement_id = $element["afelement_id"];
            foreach ($progress["progress_responses"] as $epresponse_id => $progress_record) {
                if ($progress_record["afelement_id"] == $afelement_id) {
                    if ($progress_record["comments"]) {
                        return $progress_record["comments"];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Fetch an array containing only valid item types ( array([itemtype_id] => object) ) for rubrics.
     *
     * @return array
     */
    public static function getAllowableRubricItemTypes() {
        $rubric_types = array();
        // TODO: In the future, we can move this to a DB query on a table, making rubric types configurable.
        $allowable = array("rubric_line", "scale");
        foreach ($allowable as $shortname) {
            if ($record = Models_Assessments_Itemtype::fetchRowByShortname($shortname)) {
                $rubric_types[$record->getID()] = $record;
            }
        }
        return $rubric_types;
    }

    /**
     * Fetch an array of allowable item types. All are available except what is in the exclusions array.
     *
     * @return array
     */
    public static function getAllowableItemTypes() {
        $allowable = array();

        // Everything is available, except for individual selector.
        $exclusions = array("user");

        $all_itemtypes = Models_Assessments_Itemtype::fetchAllRecords();
        if (is_array($all_itemtypes)) {
            foreach ($all_itemtypes as $itemtype) {
                if (!in_array($itemtype->getShortname(), $exclusions)) {
                    $allowable[$itemtype->getID()] = $itemtype;
                }
            }
        }
        return $allowable;
    }

    //-- More View Helpers (Pass-through functions) --//

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
    public static function canRenderOption($element_type, $itemtype_shortname) {
        return Entrada_Assessments_Forms_Item::canRenderOption($element_type, $itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the form item is flaggable (can be commented on if flagged).
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canFlagOption($itemtype_shortname) {
        return Entrada_Assessments_Forms_Item::canFlagOption($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can be commented on.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveComments($itemtype_shortname) {
        return Entrada_Assessments_Forms_Item::canHaveComments($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can have responses.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveResponses($itemtype_shortname) {
        return Entrada_Assessments_Forms_Item::canHaveResponses($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can have objectives associated.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveObjectives($itemtype_shortname) {
        return Entrada_Assessments_Forms_Item::canHaveObjectives($itemtype_shortname);
    }

    //-- Public Form Logic --//

    /**
     * Fetch form data, complete with all items and rubric data.
     *
     * @param $specified_form_id
     * @return bool|array
     */
    public function fetchFormData($specified_form_id = null) {
        $form_id = $this->whichID("form_id", $specified_form_id);
        $options = array(
            "aprogress_id" => $this->aprogress_id,
            "adistribution_id" => $this->adistribution_id,
            "dassessment_id" => $this->dassessment_id
        );
        if ($form_object = $this->getFormObject($form_id, $options)) {
            return $form_object->fetchData();
        } else {
            return array();
        }
    }

    /**
     * Find which assessments use this form.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getFormUsageInAssessments($specified_item_id = null) {
        $id = $this->whichID("form_id", $specified_item_id);
        if ($object = $this->getFormObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["assessments"];
        }
        return array();
    }

    /**
     * Find which distributions use this form.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getFormUsageInDistributions($specified_item_id = null) {
        $id = $this->whichID("form_id", $specified_item_id);
        if ($object = $this->getFormObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["distributions"];
        }
        return array();
    }

    /**
     * Check if a form is in use.
     *
     * @param null $specified_form_id
     * @return bool
     */
    public function isFormInUse($specified_form_id = null) {
        $id = $this->whichID("form_id", $specified_form_id);
        if ($object = $this->getFormObject($id)) {
            return $object->isInUse();
        }
        return false;
    }

    /**
     * Check if the form is editable.
     *
     * @param null $specified_form_id
     * @return bool
     */
    public function isFormEditable($specified_form_id = null) {
        $id = $this->whichID("form_id", $specified_form_id);
        if ($object = $this->getFormObject($id)) {
            return $object->isEditable();
        }
        return false;
    }

    /**
     * Check if a form is in use.
     *
     * @param null $specified_form_id
     * @return bool
     */
    public function isFormDisabled($specified_form_id = null) {
        $id = $this->whichID("form_id", $specified_form_id);
        if ($object = $this->getFormObject($id)) {
            return $object->isDisabled();
        }
        return false;
    }

    /**
     * Update a form. A form is little more than links to items and some free text.
     * Saving a form is simply adjusting the orders of the items and updating the form permissions.
     *
     * @param array $form_data
     * @param int $form_id
     * @return bool
     */
    public function saveForm($form_data, $form_id = null) {
        $id = $this->whichID("form_id", $form_id);
        if ($object = $this->getFormObject($id)) {
            if ($object->loadData($form_data)) {
                if ($object->saveData()) {
                    $this->setFormID($object->getID());
                    return true;
                } else {
                    // add error
                    $this->addErrorMessages($object->getErrorMessages());
                }
            } else {
                $this->addErrorMessages($object->getErrorMessages());
            }
        }
        return false;
    }

    /**
     * Save the non-relational data parts of form record.
     *
     * @param $title
     * @param $description
     * @return bool
     */
    public function updateFormPrimitives($title, $description) {
        global $translate;
        $status = false;
        if ($object = $this->getFormObject($this->form_id)) {
            return $object->updateFormPrimitives($title, $description);
        } else {
            $this->addErrorMessage($translate->_("Unable to update form."));
        }
        return $status;
    }

    /**
     * Add items to a form (add an element record for each ID given).
     * Returns the number of items attached.
     *
     * @param int $form_id
     * @param array $item_ids integers
     * @param int $as_part_of_rubric_id
     * @return int
     */
    public function attachItemsToForm($form_id, $item_ids = array(), $as_part_of_rubric_id = null) {
        global $translate;

        if (empty($item_ids)) {
            $this->addErrorMessage($translate->_("Please specify items to attach."));
            return 0;
        }

        if (!$form_id) {
            if (count($item_ids) > 1) {
                $this->addErrorMessage($translate->_("Please specify a form to attach these items to."));
            } else {
                $this->addErrorMessage($translate->_("Please specify a form to attach this item to."));
            }
            return 0;
        }

        $attachments = 0;
        $form_object = $this->getFormObject($form_id);
        foreach ($item_ids as $item_id) {
            if ($form_object->attachItem($item_id, null, $as_part_of_rubric_id, $as_part_of_rubric_id ? true : false)) {
                $attachments++;
            }
        }
        $this->addErrorMessages($form_object->getErrorMessages());
        return $attachments;
    }

    /**
     * Attach rubrics to a form.
     *
     * @param int $form_id
     * @param array $rubric_ids
     * @param bool $ignore_when_already_attached
     * @return int
     */
    public function attachRubricsToForm($form_id, $rubric_ids = array(), $ignore_when_already_attached = false) {
        global $translate;

        if (empty($rubric_ids)) {
            $this->addErrorMessage($translate->_("Please specify rubrics to attach."));
            return 0;
        }

        if (!$form_id) {
            if (count($rubric_ids) > 1) {
                $this->addErrorMessage($translate->_("Please specify a form to attach these rubric to."));
            } else {
                $this->addErrorMessage($translate->_("Please specify a form to attach this rubric to."));
            }
            return 0;
        }

        $attachments = 0;
        $form_object = $this->getFormObject($form_id);
        foreach ($rubric_ids as $rubric_id) {
            if ($form_object->attachRubric($rubric_id, null, $ignore_when_already_attached)) {
                $attachments++;
            }
        }
        $this->addErrorMessages($form_object->getErrorMessages());
        return $attachments;
    }

    //-- Public Rubric Logic --//

    /**
     * Fetch rubric data.
     *
     * @param null $specified_rubric_id
     * @return array
     */
    public function fetchRubricData($specified_rubric_id = null) {
        $rubric_id = $this->whichID("rubric_id", $specified_rubric_id);
        $options = array(
            "aprogress_id" => $this->aprogress_id,
            "adistribution_id" => $this->adistribution_id,
            "dassessment_id" => $this->dassessment_id
        );
        if ($rubric_object = $this->getRubricObject($rubric_id, $options)) {
            return $rubric_object->fetchData();
        } else {
            return array();
        }
    }

    /**
     * Check if a rubric is in use.
     *
     * @param null $specified_rubric_id
     * @return mixed
     */
    public function isRubricInUse($specified_rubric_id = null) {
        $id = $this->whichID("rubric_id", $specified_rubric_id);
        if ($object = $this->getRubricObject($id)) {
            return $object->isInUse();
        }
        return true;
    }

    /**
     * Check if a rubric is editable.
     *
     * @param null $specified_rubric_id
     * @return mixed
     */
    public function isRubricEditable($specified_rubric_id = null) {
        $id = $this->whichID("rubric_id", $specified_rubric_id);
        if ($object = $this->getRubricObject($id)) {
            $var = $object->isEditable();
            return $var;
        }
        return true;
    }

    /**
     * Find which forms use this rubric.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getRubricUsageInForms($specified_item_id = null) {
        $id = $this->whichID("rubric_id", $specified_item_id);
        if ($object = $this->getRubricObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["forms"];
        }
        return array();
    }

    /**
     * Find which rubrics and forms use the items contained in this rubric.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getRubricItemUsage($specified_item_id = null) {
        $id = $this->whichID("rubric_id", $specified_item_id);
        if ($object = $this->getRubricObject($id)) {
            $items_in_use_by = $object->itemsInUseBy();
            return $items_in_use_by;
        }
        return array();
    }

    /**
     * For the current form, perform a consistency check, comparing
     * current form elements to any rubric elements that are part of it. If the associated rubrics
     * are missing, or the rubrics have more items in them than are contained on the form, then the
     * consistency check fails.
     *
     * By defualt, this method repairs what it finds.
     *
     * @param bool $repair
     * @return bool
     */
    public function formRubricsConsistencyCheck($repair = true) {
        global $translate;
        if (!$form_object = $this->getFormObject($this->form_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch form object for consistency check."));
            return false;
        }

        $form_data = $form_object->fetchData();
        if (empty($form_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch data for specified form."));
            // Nothing to check; invalid form ID
            return false;
        }

        $checks = array();
        if ($form_data["meta"]["rubric_count"] > 0) {
            // There are rubrics to check.
            foreach ($form_data["rubrics"] as $rubric_id => $rubric_data) {
                if ($rubric_object = $this->getRubricObject($rubric_id)) {
                    // Each form dataset contains the relevant rubric datasets.
                    // We can save ourselves the processing time, and simply replace the current rubric dataset
                    $rubric_object->setDataset($rubric_data);
                    $checks[$rubric_id] = $rubric_object->formConsistencyCheckAndRepair($this->form_id, $repair, false); // return the result of the consistency check
                }
            }
        }

        // Our consistency checks have been executed, so return true if they all passed. False if any have failed.
        foreach ($checks as $check_result) {
            if (!$check_result) {
                return false;
            }
        }

        // All passed OK
        return true;
    }

    /**
     * Save the rubric data, and keep it consistent across all instances of its usage.
     *
     * @param $rubric_title
     * @param $rubric_description
     * @param $item_code
     * @param $referrer_form_id
     * @return bool
     */
    public function saveRubric($rubric_title, $rubric_description, $item_code, $referrer_form_id = null) {
        global $translate;
        $rubric_data = array(
            "rubric_id" => $this->rubric_id, // can be null
            "rubric_title" => $rubric_title,
            "rubric_description" => $rubric_description,
            "rubric_item_code" => $item_code
        );
        if ($object = $this->getRubricObject($this->rubric_id)) {
            if ($object->loadData($rubric_data)) {
                if ($object->saveData()) {
                    $this->setRubricID($object->getRubricID());
                    // If we've been given a referrer form, attach the rubric to that form.
                    if ($referrer_form_id) {
                        if (!$this->attachRubricsToForm($referrer_form_id, array($this->rubric_id), true)) {
                            $this->addErrorMessage($translate->_("Rubric was saved, but could not be attached to the form."));
                        }
                    }
                    return true;
                } else {
                    // add error
                    $this->addErrorMessages($object->getErrorMessages());
                }
            } else {
                $this->addErrorMessages($object->getErrorMessages());
            }
        }
        return false;
    }

    /**
     * Update the rubric record primitive values, not any relationships.
     *
     * @param string $title
     * @param string $description
     * @param string $code
     * @return bool
     */
    public function updateRubricPrimitives($title, $description, $code) {
        $status = false;
        if ($object = $this->getRubricObject($this->getRubricID())) {
            $status = $object->updateRubricPrimitives($title, $description, $code);
        } else {
            $this->addErrorMessage("Unable to access rubric.");
        }
        return $status;
    }

    /**
     * Save an empty rubric, with optional title.
     *
     * @param string $rubric_title
     * @return bool
     */
    public function createEmptyRubric($rubric_title = null) {
        global $translate;
        if ($rubric_object = $this->getRubricObject(null)) {
            // Create a new blank rubric
            if ($rubric_object->saveEmptyRubric($rubric_title)) {
                $this->setRubricID($rubric_object->getRubricID());
                // Empty rubric created.
                return true;
            } else {
                $this->addErrorMessages($rubric_object->getErrorMessages());
            }
        } else {
            // Failed to fetch object
            application_log("error", "Entrada_Assessments_Forms::createEmptyRubric->failed fetch rubric object");
            $this->addErrorMessage($translate->_("Unable to create rubric."));
        }
        return false;
    }

    /**
     * For a given rubric, remove the old item and add a new item.
     *
     * @param $rubric_id
     * @param $old_item_id
     * @param $new_item_id
     * @return bool
     */
    public function replaceItemOnRubric($rubric_id, $old_item_id, $new_item_id) {
        global $translate;
        if (!$rubric_object = $this->getRubricObject($rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric object."));
            return false;
        }
        if (!$rubric_object->removeItem($old_item_id)) {
            $this->addErrorMessages($rubric_object->getErrorMessages());
            return false;
        }
        if (!$rubric_object->attachItem($new_item_id)) {
            $this->addErrorMessages($rubric_object->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Attach items to a rubric.
     *
     * @param int $rubric_id
     * @param array $item_ids
     * @return int
     */
    public function attachItemsToRubric($rubric_id, $item_ids = array()) {
        global $translate;

        if (empty($item_ids)) {
            $this->addErrorMessage($translate->_("Please specify items to attach."));
            return 0;
        }

        if (!$rubric_id) {
            if (count($item_ids) > 1) {
                $this->addErrorMessage($translate->_("Please specify a rubric to attach these items to."));
            } else {
                $this->addErrorMessage($translate->_("Please specify a rubric to attach this item to."));
            }
            return 0;
        }

        $attachments = 0;
        $rubric_object = $this->getRubricObject($rubric_id);
        foreach ($item_ids as $item_id) {
            if ($rubric_object->attachItem($item_id)) {
                $attachments++;
            }
        }
        $this->addErrorMessages($rubric_object->getErrorMessages()); // add errors, if any
        return $attachments;
    }

    /**
     * For the given form, replace the rubric on it with a new copy of it.
     *
     * @param $form_id
     * @param $old_rubric_id
     * @param $new_rubric_title
     * @return bool
     */
    public function copyRubricAndReplaceOnForm($form_id, $old_rubric_id, $new_rubric_title) {
        global $translate;
        if (!$rubric_object = $this->getRubricObject($old_rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric object."));
            return false;
        }
        if (!$form_object = $this->getFormObject($form_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch form object."));
            return false;
        }

        $form_data = $form_object->fetchData();
        if (empty($form_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch form data."));
            return false;
        }

        // Copy the rubric (but don't copy the deleted items)
        if (!$rubric_object->copyRubric($old_rubric_id, $new_rubric_title)) {
            $this->addErrorMessage($translate->_("Unable to copy the old rubric."));
            return false;
        }

        // Preserve the element ordering
        $new_order = null;
        $this->rubric_id = $rubric_object->getRubricID();
        foreach ($form_data["elements"] as $element) {
            if ($element["element"]["rubric_id"] == $old_rubric_id && $new_order === null) {
                $new_order = $element["element"]["order"];
            }
        }

        // Remove existing references on the form
        if (!$form_object->removeRubricFromForm($old_rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to clear rubric from form."));
            return false;
        }

        // Attach the new rubric
        if (!$form_object->attachRubric($rubric_object->getRubricID(), $new_order)) {
            $this->addErrorMessage($translate->_("Unable to attach new rubric to form."));
            return false;
        }
        return true;
    }

    /**
     * For the given rubric id, copy it, optionally specifying new title.
     *
     * @param $old_rubric_id
     * @param $new_rubric_title
     * @return bool
     */
    public function copyRubric($old_rubric_id, $new_rubric_title) {
        global $translate;
        if (!$rubric_object = $this->getRubricObject($old_rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric object."));
            return false;
        }

        // Copy the rubric
        if (!$rubric_object->copyRubric($old_rubric_id, $new_rubric_title)) {
            $this->addErrorMessage($translate->_("Unable to copy the old rubric."));
            return false;
        }

        $this->rubric_id = $rubric_object->getRubricID();
        return true;
    }

    /**
     * Mark rubrics as deleted.
     *
     * @param $delete_rubric_ids
     * @return array
     */
    public function deleteRubrics($delete_rubric_ids) {
        global $translate;

        $deleted_rubrics = array();
        if (!is_array($delete_rubric_ids)) {
            $this->addErrorMessage($translate->_("Please specify which rubrics to delete."));
            return array();
        }
        foreach ($delete_rubric_ids as $rubric_id) {
            if ($rubric_object = $this->getRubricObject($rubric_id)) {
                if ($rubric_object->delete()) {
                    $deleted_rubrics[] = $rubric_id;
                } else {
                    $this->addErrorMessages($rubric_object->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find rubric object to delete."));
            }
        }
        return $deleted_rubrics;
    }

    /**
     * Delete a rubric item by aritem_id
     *
     * @param $aritem_id
     * @return bool
     */
    public function deleteRubricItem($aritem_id) {
        global $translate;

        if (!$aritem_id) {
            $this->addErrorMessage($translate->_("No rubric item specified."));
            return false;
        }

        // Find rubric ID from given aritem_id
        if (!$rubric_item = Models_Assessments_Rubric_Item::fetchRowByID($aritem_id)) {
            $this->addErrorMessage($translate->_("Unable to delete the specified rubric item."));
            return false;
        }

        if (!$rubric_id = $rubric_item->getRubricID()) {
            $this->addErrorMessage($translate->_("Unable to determine which rubric this item is attached to."));
            return false;
        }

        $this->rubric_id = $rubric_id;

        // Fetch rubric object and delete the rubric item
        if ($rubric_object = $this->getRubricObject($rubric_id)) {
            if ($rubric_object->deleteRubricItem($aritem_id)) {
                return true;
            } else {
                $this->addErrorMessages($rubric_object->getErrorMessages());
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to find rubric object to remove item from."));
        }
        return false;
    }

    /**
     * Give then new descriptor ID and the column in which it appears, update all columns of a rubric to match it.
     * Calling this function with position of "3" would set the third response descriptor of each rubric line
     * to $new_descriptor_id.
     *
     * @param $new_descriptor_id
     * @param $position
     * @return bool
     */
    public function updateRubricResponseDescriptor($new_descriptor_id, $position) {
        global $translate;
        if (!$this->rubric_id) {
            $this->addErrorMessage($translate->_("Unable to update response descriptor without rubric ID."));
            return false;
        }
        if ($rubric_object = $this->getRubricObject($this->rubric_id)) {
            $rubric_data = $rubric_object->fetchData();
            if (empty($rubric_data)) {
                $this->addErrorMessage($translate->_("Unable to fetch rubric data."));
                return false;
            }
            foreach ($rubric_data["descriptors"] as $order => $descriptor_data) {
                if ($order+1 == $position) {
                    continue;
                }
                if ($descriptor_data["ardescriptor_id"] == $new_descriptor_id) {
                    // Already in use
                    $this->addErrorMessage($translate->_("This descriptor is already assigned to this rubric."));
                    return false;
                }
            }
            return $rubric_object->updateResponseDescriptor($new_descriptor_id, $position);
        } else {
            $this->addErrorMessage($translate->_("Unable to fetch rubric object."));
        }
        return false;
    }

    /**
     * Determine how editable the rubric is based on what's using it and who's calling it.
     * Returns a token indicating the rubric state.
     *
     * @param null $referrer_form_id
     * @return bool|string
     */
    public function getRubricEditabilityState($referrer_form_id = null) {
        global $translate;

        if (!$this->rubric_id) {
            $this->addErrorMessage($translate->_("Unable to determine editability; please specify a rubric ID."));
            return false;
        }

        // Check the editability of this current rubric.
        $rubric_readonly = !$this->isRubricEditable();
        $rubric_used_by = $this->getRubricUsageInForms(); // Fetch an array of the IDs that use this rubric
        $rubric_items_used_by = $this->getRubricItemUsage(); // Fetch an array of the IDs that use the individual items in this rubric

        if ($rubric_readonly) {

            // Check the editability of the referring form.
            if ($referrer_form_id) {

                // Referrer specified
                if ($this->isFormEditable($referrer_form_id)) {
                    // The rubric is in use, but the the form is editable
                    return "readonly-attached-editable";
                } else {
                    // Rubric is in use, and the form is not editable
                    return "readonly";
                }

            } else {
                // No referrer, it's readonly
                return "readonly";
            }

        } else { // The rubric is not read-only. So we determine just how editable it is.

            if (count($rubric_used_by) == 0 && count($rubric_items_used_by["forms"]) == 0 && count($rubric_items_used_by["rubrics"]) == 0) {
                // Not read-only, not in use
                return "editable";

            } else if (count($rubric_used_by) == 0 && (count($rubric_items_used_by["forms"]) > 0)) {

                if ($referrer_form_id && count($rubric_items_used_by["forms"]) == 1 && (reset($rubric_items_used_by["forms"]) == $referrer_form_id)) {
                    // We've been referred by the only form that uses this rubric.
                    return "editable-attached";

                } else {
                    // The rubric isn't in use, but some of the items are, and there is no referrer
                    return "editable-descriptors-locked";
                }

            } else { // rubric_used_by > 0, rubric_items_used_by >= 0

                if (!$referrer_form_id) {
                    // No referrer.
                    // The rubric is in use in multiple places, but none of them are delivered.
                    return "editable-attached-multiple";

                } else {

                    // Has a referrer. Check how many forms use this rubric. If 1, then check if it's the one referred.
                    if (count($rubric_used_by) == 1) {

                        if (reset($rubric_used_by) == $referrer_form_id) {

                            if (!empty($rubric_items_used_by["rubrics"])) {
                                // There are rubrics that use some of the items in this current rubric (this data excludes the current rubric)
                                return "editable-attached-items-in-use-descriptors-locked";

                            } else {
                                // No items in our rubric are being used elsewhere, and the referrer is the only form that uses this. So, full edit mode.
                                // We were referred to by the only form that uses this rubric, so allow full editing
                                return "editable-attached";

                            }

                        } else {
                            // None of the forms this is attached to are delivered, so we allow editing (no descriptor editing), but notify them
                            // that they will affect changes across the board.
                            return "editable-attached-multiple-descriptors-locked";

                        }
                    } else if (count($rubric_used_by) > 1) {
                        return "editable-attached-descriptors-locked";

                    }
                }
            }
        }
        return "readonly";
    }

    //-- Public Item Logic --//

    /**
     * Fetch a single item's data.
     *
     * @param null $specified_item_id
     * @return array
     */
    public function fetchItemData($specified_item_id = null) {
        $item_id = $this->whichID("item_id", $specified_item_id);
        if ($item_object = $this->getItemObject($item_id)) {
            return $item_object->fetchData();
        } else {
            return array();
        }
    }

    /**
     * Check if an item is in use.
     *
     * @param null $specified_item_id
     * @return mixed
     */
    public function isItemInUse($specified_item_id = null) {
        $id = $this->whichID("item_id", $specified_item_id);
        if ($object = $this->getItemObject($id)) {
            return $object->isInUse();
        }
        return false;
    }

    /**
     * Find which rubrics use this item.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getItemUsageInRubrics($specified_item_id = null) {
        $id = $this->whichID("item_id", $specified_item_id);
        if ($object = $this->getItemObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["rubrics"];
        }
        return array();
    }

    /**
     * Find which forms use this item.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getItemUsageInForms($specified_item_id = null) {
        $id = $this->whichID("item_id", $specified_item_id);
        if ($object = $this->getItemObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["forms"];
        }
        return array();
    }

    /**
     * Find which delivered (not started/completed/inprogress) assessments use this item.
     *
     * @param null $specified_item_id
     * @return array of ids
     */
    public function getItemUsageInAssessments($specified_item_id = null) {
        $id = $this->whichID("item_id", $specified_item_id);
        if ($object = $this->getItemObject($id)) {
            $in_use_by = $object->inUseBy();
            return $in_use_by["assessments"];
        }
        return array();
    }

    /**
     * Get the count of the number of responses for an item.
     *
     * @return int
     */
    public function getItemResponseCount() {
        if ($object = $this->getItemObject($this->item_id)) {
            return $object->getItemResponseCount();
        }
        return 0;
    }

    /**
     * Update/save an item.
     *
     * @param array $item_data
     * @param int $item_id
     * @return bool
     */
    public function saveItem($item_data, $item_id = null) {
        $id = $this->whichID("item_id", $item_id);
        if ($object = $this->getItemObject($id)) {
            if ($object->loadData($item_data)) {
                // Loaded OK, so save it.
                if ($object->saveData()) {
                    $this->setItemID($object->getID());
                    return true;
                } else {
                    // Failed to save, fetch error
                    application_log("error", "Entrada_Assessments_Forms::saveItem->Failed to save item for item_id = '$id'");
                }
            } else {
                // failed to validate, fetch error from object
                $this->addErrorMessages($object->getErrorMessages()); // add errors, if any
            }
        }
        return false;
    }

    /**
     * Save additional objectives based on the submitted field note data.
     * This functionality simply adds additional item objectives based on specific ID arrays. 
     *
     * TODO: In the future, maybe support saving this data in the loadData/saveData semantics of the Item object. This probably should be contained in the item object.
     *
     * @param $responses
     * @param $flag_responses
     * @param $ardescriptor_ids
     * @param $objective_ids
     * @return bool
     */
    public function saveItemFieldNoteProperties($responses, $flag_responses, $ardescriptor_ids, $objective_ids) {
        global $translate;

        // Given the input arrays, save the data for the current item
        if (!$this->item_id) {
            $this->addErrorMessage($translate->_("Unable to save field note."));
            return false;
        }

        if (!is_array($responses) || !is_array($flag_responses) || !is_array($ardescriptor_ids) || !is_array($objective_ids)) {
            $this->addErrorMessage($translate->_("Invalid data submitted for field note."));
            return false;
        }

        $item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($this->item_id);
        if (!$item) {
            $this->addErrorMessage($translate->_("This item does not exist."));
            return false;
        }
        
        if (isset($responses) && is_array($responses)) {
            $item_responses = $item->getItemResponses();
            if ($item_responses) {
                foreach ($item_responses as $response) {
                    $response->fromArray(array("deleted_date" => time()))->update();
                }
            }
            $order = 1;

            foreach ($responses as $key => $response) {
                if (isset($flag_responses) && is_array($flag_responses) && array_key_exists($key, $flag_responses)) {
                    $PROCESSED_RESPONSE["flag_response"] = 1;
                } else {
                    $PROCESSED_RESPONSE["flag_response"] = 0;
                }

                if (isset($ardescriptor_ids) && is_array($ardescriptor_ids) && array_key_exists($key, $ardescriptor_ids)) {
                    $PROCESSED_RESPONSE["ardescriptor_id"] = $ardescriptor_ids[$key];
                } else {
                    $PROCESSED_RESPONSE["ardescriptor_id"] = NULL;
                }

                $PROCESSED_RESPONSE["item_id"] = $item->getID();
                $PROCESSED_RESPONSE["text"] = $response;
                $PROCESSED_RESPONSE["order"] = $order;
                $PROCESSED_RESPONSE["allow_html"] = 0;
                $PROCESSED_RESPONSE["minimum_pass"] = 0;

                $response = new Models_Assessments_Item_Response($PROCESSED_RESPONSE);
                if (!$response->insert()) {
                    $this->addErrorMessage($translate->_("An error occurred while attempting to create one of the item responses."));
                }
                $order++;
            }
        }
        
        $existing_item_objectives = Models_Assessments_Item_Objective::fetchAllRecordsByItemID($this->item_id);
        $existing_item_objective_ids = array();
        foreach ($existing_item_objectives as $existing_item_objective) {
            $existing_item_objective_ids[] = $existing_item_objective->getObjectiveID();
            if (!in_array($existing_item_objective->getObjectiveID(), $objective_ids)) {
                $tmp_objective_array = $existing_item_objective->toArray();
                $tmp_objective_array["updated_date"] = time();
                $tmp_objective_array["updated_by"] = $this->actor_proxy_id;
                $tmp_objective_array["deleted_date"] = time();

                if (!$existing_item_objective->fromArray($tmp_objective_array)->update()) {
                    application_log("error", "Unable to deactivate an objective [".$existing_item_objective->getObjectiveID()."] associated with an assessment item [{$this->item_id}]");
                }
            }
        }

        foreach ($objective_ids as $objective_id) {
            if (!in_array($objective_id, $existing_item_objective_ids)) {
                $PROCESSED_OBJECTIVE = array(
                    "item_id" => $this->item_id,
                    "objective_id" => $objective_id,
                    "created_date" => time(),
                    "updated_date" => time(),
                    "updated_by" => $this->actor_proxy_id,
                    "created_by" => $this->actor_proxy_id
                );
                $item_objective = new Models_Assessments_Item_Objective($PROCESSED_OBJECTIVE);
                if (!$item_objective->insert()) {
                    $this->addErrorMessage($translate->_("There was an error while trying to attach an <strong>Objective</strong> to this item.<br /><br />The system administrator was informed of this error; please try again later."));
                    global $db;
                    application_log("error", "Unable to insert a new cbl_item_objectives record while managing an item [" . $this->item_id . "]. Database said: " . $db->ErrorMsg());
                }
            }
        }
        return true;
    }

    /**
     * Load item data into the item object, ignoring validation.
     *
     * @param $item_data
     * @param null $item_id
     * @return bool
     */
    public function loadItemData($item_data, $item_id = null) {
        $id = $this->whichID("item_id", $item_id);
        if ($object = $this->getItemObject($id)) {
            $item_data["item"]["item_id"] = $id;
            return $object->loadData($item_data, false); // don't validate
        }
        return false;
    }

    /**
     * Copy an item, optionally replacing the item text.
     *
     * @param $old_item_id
     * @param null $new_text
     * @param bool $clone
     * @return bool
     */
    public function copyItem($old_item_id, $new_text = null, $clone = false) {
        // Instantiate the old item
        if ($object = $this->getItemObject($old_item_id)) {
            if ($object->duplicate($clone)) { // Duplicate it (creates a new dataset, without current ID)
                $object->setItemText($new_text); // Update the item text
                if ($object->saveData()) { // commit it to the database, and save this object ID
                    $this->setItemID($object->getID());
                    return true;
                } else {
                    application_log("error", "Entrada_Assessments_Forms::saveItem->Failed to COPY item for old_item_id = '$old_item_id'");
                    $this->addErrorMessages($object->getErrorMessages()); // add errors, if any
                }
            }
        }
        return false;
    }

    /**
     * Copy an item and gracefully replace it on the associated form and/or rubric.
     *
     * @param $old_item_id
     * @param $form_id
     * @param $rubric_id
     * @param null $new_text
     * @return bool
     */
    public function copyItemAndReplaceOnFormOrRubric($old_item_id, $form_id, $rubric_id, $new_text = null) {
        global $translate;
        if (!$form_id && !$rubric_id) {
            $this->addErrorMessage($translate->_("No form or rubric ID specified."));
            return false;
        }
        $rubric_data = $form_data = array();

        if ($form_id && !$form_object = $this->getFormObject($form_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch form object."));
            return false;
        } else {
            if ($form_data = $this->fetchFormData($form_id)) {
                if (empty($form_data)) {
                    $this->addErrorMessage($translate->_("Invalid form specified."));
                    return false;
                }
            }
        }

        if ($rubric_id && !$rubric_object = $this->getRubricObject($rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric object."));
            return false;
        } else {
            if ($rubric_data = $this->fetchRubricData($rubric_id)) {
                if (empty($rubric_data)) {
                    $this->addErrorMessage($translate->_("Invalid rubric specified."));
                    return false;
                }
            }
        }

        if (!$item_object = $this->getItemObject($old_item_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch item object."));
            return false;
        }

        $status = false;
        if ($item_object->duplicate(true)) { // Duplicate it (creates a new dataset, without current ID)
            $item_object->setItemText($new_text); // Update the item text
            if ($item_object->saveData()) { // commit it to the database, and save this object ID
                $this->setItemID($item_object->getID());
                if ($rubric_id) {
                    $old_rubric_item_order = $rubric_object->getItemOrder($old_item_id);
                    if ($rubric_object->removeItem($old_item_id)) {
                        // Attach the new item to the rubric
                        if ($rubric_object->attachItem($item_object->getID(), $old_rubric_item_order)) {
                            $status = true;
                        } else {
                            $this->addErrorMessages($rubric_object->getErrorMessages());
                        }
                    } else {
                        $this->addErrorMessages($rubric_object->getErrorMessages());
                    }
                }
                if ($form_id) {
                    $old_item_order = $form_object->getElementOrder($old_item_id, "item");
                    if ($form_object->removeItemFromForm($old_item_id)) {
                        // Attach the new item to the form
                        if ($form_object->attachItem($item_object->getID(), $old_item_order, $rubric_id)) {
                            $status = true;
                        } else {
                            $this->addErrorMessages($form_object->getErrorMessages());
                        }
                    } else {
                        $this->addErrorMessages($form_object->getErrorMessages());
                    }
                }
            } else {
                application_log("error", "Entrada_Assessments_Forms::saveItem->Failed to COPY AND REPLACE item for old_item_id = '$old_item_id'");
                $this->addErrorMessages($item_object->getErrorMessages()); // add errors, if any
            }
        } else {
            $this->addErrorMessage($translate->_("Unable to copy item."));
        }

        return $status;
    }

    /**
     * Mark items as deleted.
     *
     * @param array $delete_item_ids
     * @return array
     */
    public function deleteItems($delete_item_ids) {
        global $translate;

        $deleted_items = array();
        if (!is_array($delete_item_ids)) {
            $this->addErrorMessage($translate->_("Please specify which items to delete."));
            return array();
        }
        foreach ($delete_item_ids as $item_id) {
            if ($item_object = $this->getItemObject($item_id)) {
                if ($item_object->delete()) {
                    $deleted_items[] = $item_id;
                } else {
                    $this->addErrorMessages($item_object->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find item object to delete."));
            }
        }
        return $deleted_items;
    }

    /**
     * Determine what state the item is in, based on what uses it.
     *
     * @param int|null $referrer_form_id
     * @param int|null $referrer_rubric_id
     * @return bool|string
     */
    public function getItemEditabilityState($referrer_form_id = null, $referrer_rubric_id = null) {
        global $translate;

        if (!$this->item_id) {
            $this->addErrorMessage($translate->_("No item specified."));
            return false;
        }

        $item_data = $this->fetchItemData();
        if (empty($item_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch item data."));
            return false;
        }

        $form_count = count($item_data["meta"]["in_use_by"]["forms"]);
        $rubric_count = count($item_data["meta"]["in_use_by"]["rubrics"]);

        $only_used_form_id = null;
        $only_used_rubric_id = null;
        $lines_of_referrer_rubric = 0;

        if (isset($item_data["meta"]["in_use_by"]["forms"])) {
            if (count($item_data["meta"]["in_use_by"]["forms"]) == 1) {
                $only_used_form_id = reset($item_data["meta"]["in_use_by"]["forms"]);
            }
        }
        if (isset($item_data["meta"]["in_use_by"]["rubrics"])) {
            if (count($item_data["meta"]["in_use_by"]["rubrics"])) {
                $only_used_rubric_id = reset($item_data["meta"]["in_use_by"]["rubrics"]);
            }
        }

        $referrer_form_data = array();
        if ($referrer_form_id) {
            $referrer_form_data = $this->fetchFormData($referrer_form_id);
            if (empty($referrer_form_data)) {
                // This form is invalid; we can safely ignore it.
                $referrer_form_id = null;
            }
        }

        $referrer_rubric_data = array();
        if ($referrer_rubric_id) {
            $referrer_rubric_data = $this->fetchRubricData($referrer_rubric_id);
            if (empty($referrer_rubric_data)) {
                // The referred rubric is invalid, we can safely ignore it.
                $referrer_rubric_id = null;
            } else {
                $lines_of_referrer_rubric = $referrer_rubric_data["meta"]["lines_count"];
            }
        }

        if ($item_data["meta"]["is_editable"]) {

            if ($rubric_count == 0 && $form_count == 0) {
                // Not used in any forms or rubrics
                return "editable";
            }

            if ($referrer_form_id && $referrer_rubric_id) {
                // Referred by a rubric and a form
                if ($rubric_count == 1 && $form_count == 1) {
                    // In use by exactly one form and one rubric
                    if ($only_used_form_id == $referrer_form_id && $only_used_rubric_id == $referrer_rubric_id) {
                        if ($lines_of_referrer_rubric == 1) {
                            // We can allow full editability when referrers match, and there's only 1 line in the rubric
                            return "editable-by-attached-form-and-rubric-unlocked-descriptors";
                        }
                    }

                    // Otherwise, allow the rubric to edit this item in a restricted mode
                    return "editable-by-attached-rubric";

                } else if ($rubric_count == 0 && $form_count == 1) {
                    // Not used in any rubrics, used in exactly 1 form
                    if ($referrer_form_id == $only_used_form_id) {
                        // The form it's used on is the one we were referred to by. So, allow editable.
                        return "editable-by-attached-form";
                    } else {
                        // Otherwise, allow editable, but notify that it's in use elsewhere
                        return "editable-attached-form-multiple";
                    }

                } else if ($rubric_count == 1 && $form_count == 0) {
                    // Not used on any forms, but used in exactly 1 rubric
                    if ($referrer_rubric_id == $only_used_rubric_id) {
                        if ($lines_of_referrer_rubric == 1) {
                            // The rubric that it is attached to is our referrer
                            return "editable-by-attached-rubric-unlocked-descriptors";
                        }
                    }
                    // Otherwise, allow the referring rubric to edit in restricted mode
                    return "editable-by-attached-rubric";

                } else if ($rubric_count > 1 && $form_count == 0) {
                    // Used in more than one rubric and no forms
                    return "editable-attached-rubric-multiple";

                } else if ($rubric_count == 0 && $form_count > 1) {
                    // Used in more than one form and no rubrics
                    return "editable-attached-form-multiple";

                } else if ($rubric_count == 1 && $form_count > 0) {
                    if ($referrer_rubric_id == $only_used_rubric_id) {
                        if ($lines_of_referrer_rubric == 1) {
                            // The rubric that it is attached to is our referrer
                            return "editable-by-attached-rubric-unlocked-descriptors";
                        } else {
                            // The referrer rubric is editable, but has more than 1 line
                            return "editable-by-attached-rubric";
                        }
                    }
                    // Otherwise, notify multiple forms/rubrics using this, allow edit in restricted mode
                    return "editable-attached-multiple";

                } else if ($rubric_count > 1 && $form_count == 1) {
                    if ($referrer_form_id == $only_used_form_id) {
                        // The referring form is the only form that uses this
                        return "editable-attached-rubric-multiple";
                    }
                    // Otherwise, notify multiple forms/rubrics using this, allow edit in restricted mode
                    return "editable-attached-multiple";

                } else {
                    // Notify multiple forms/rubrics using this, allow edit in restricted mode
                    return "editable-attached-multiple";
                }

            } else if ($referrer_rubric_id && !$referrer_form_id) {
                // Referred by a rubric, but not a form

                if ($form_count > 0) {
                    // Used in multiple forms, but not referred by any of them
                    return "editable-attached-multiple";

                } else if ($rubric_count == 1) { // form_count == 0

                    // Not used on any forms, but used in exactly 1 rubric
                    if ($referrer_rubric_id == $only_used_rubric_id) {
                        if ($lines_of_referrer_rubric == 1) {
                            // The rubric that it is attached to is our referrer
                            return "editable-by-attached-rubric-unlocked-descriptors";
                        }
                    }
                    // Otherwise, notify multiple forms/rubrics using this, allow edit in restricted mode
                    return "editable-by-attached-rubric";

                } else if ($rubric_count > 1) {
                    // Used in more than one rubric and no forms
                    return "editable-attached-rubric-multiple";
                }

            } else if (!$referrer_rubric_id && $referrer_form_id) {
                // Referred by a form, but not a rubric

                if ($rubric_count > 0) {
                    // In use by one or more rubrics, but we weren't referred by any of them
                    return "editable-attached-multiple";

                } else if ($form_count > 0) { // rubric_count == 0
                    // Not used on any rubrics, but used in one or more forms
                    if ($referrer_form_id == $only_used_form_id) {
                        // We were referred to by a form that uses this item
                        return "editable-by-attached-form";
                    } else {
                        // Otherwise notify that multiple form use this (but this referrer isn't one of them)
                        return "editable-attached-form-multiple";
                    }
                }

            } else {
                // Referred by nothing

                if ($form_count > 0 && $rubric_count == 0) {
                    // Attached to one or more forms, but not a rubric
                    return "editable-attached-form-multiple";

                } else if ($form_count == 0 && $rubric_count > 0) {
                    // Attached to one or more rubrics, but not forms
                    return "editable-attached-rubric-multiple";

                } else { // if ($form_count > 0 && $rubric_count > 0)
                    // Attached to one or more rubrics and forms
                    return "editable-attached-multiple";
                }
            }

        } else {

            // Item is not editable. So return a state token based on referrer as to what the referrer can do with it.
            if ($referrer_rubric_id) {

                if ($referrer_rubric_data["meta"]["is_editable"]) {
                    return "readonly-clone-to-rubric";
                }

            } else if ($referrer_form_id) {

                if ($referrer_form_data["meta"]["is_editable"]) {
                    return "readonly-clone-to-form";
                }
            }
        }

        // Default is read-only state
        return "readonly";
    }

    // -- Other Public Functionality --//

    /**
     * Build item options array for rendering an item from outside the context of a form or assessment.
     * This is a read-only version that does not support progress responses.
     *
     * This only builds the options array. The view mode is still specified via the View's constructor.
     *
     * @param int $item_id
     * @param bool $disabled
     * @param string $referrer_hash
     * @return array|bool
     */
    public function buildItemViewOptionsForRender($item_id, $disabled = false, $referrer_hash = null) {
        global $translate;

        $item_data = $this->fetchItemData($item_id);
        if (empty($item_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch item data for view."));
            return false;
        }

        // Add the extra array fields required by the view to the responses array (flatten the item record for the view's consumption)
        $itemtype = Models_Assessments_Itemtype::fetchRowByShortname($item_data["meta"]["itemtype_shortname"]);
        $item_data["item"]["item_type_name"] = $itemtype ? $itemtype->getName() : "";
        $item_data["item"]["render_comment_container"] = false;
        $item_data["item"]["comment_container_visible"] = false;
        $item_data["item"]["comment_related_afelement_id"] = null;
        $item_data["item"]["shortname"] = $item_data["meta"]["itemtype_shortname"];

        foreach ($item_data["responses"] as &$response) {
            $response["response_text"] = $response["text"];
            $response["response_order"] = $response["order"];
            $response["is_selected"] = false;
            $response["response_descriptor_text"] = @$item_data["descriptors"][$response["ardescriptor_id"]] ? $item_data["descriptors"][$response["ardescriptor_id"]]["descriptor"] : null;
        }

        // Standard set of data passed to form items
        return array(
            "afelement_id" => null,
            "item" => $item_data["item"],
            "itemtype_shortname" => $item_data["meta"]["itemtype_shortname"],
            "element" => null,
            "responses" => $item_data["responses"],
            "progress" => null,
            "tags" => null,
            "disabled" => $disabled,
            "header_html" => null,
            "referrer_hash" => $referrer_hash
        );
    }

    /**
     * Clear the internal storage mechanism, invalidating any previous internal caches.
     */
    public function clearInternalStorage() {
        $this->clearStorage();
    }

    //-- Private Methods --//

    /**
     * Simple function to determine which ID to use; the internal one or a specific one (which can be null).
     *
     * @param $id_type
     * @param $specified_id
     * @return int|null
     */
    private function whichID($id_type, $specified_id) {
        $return_id = null;
        if (property_exists("Entrada_Assessments_Forms", $id_type)) {
            $return_id = $this->$id_type;
            if ($specified_id !== null) {
                $return_id = $specified_id;
            }
            if ($return_id === null) {
                application_log("error", "Forms API error: no valid ID given to whichID (type = '$id_type', specified id = '$specified_id'");
                return null;
            }
        }
        return $return_id;
    }

    /**
     * Retrieve a form, rubric, or item object.
     *
     * @param $object_type
     * @param $id
     * @param array $construction_options
     * @return bool|mixed
     */
    private function getObject($object_type, $id, $construction_options = array()) {
        if ($this->isInStorage($object_type, $id)) {
            return $this->fetchFromStorage($object_type, $id);
        } else {
            $options = array(
                "actor_proxy_id" => $this->actor_proxy_id,
                "actor_organisation_id" => $this->actor_organisation_id
            );
            $options = array_merge($options, $construction_options);
            $class_name = null;
            switch ($object_type) {
                case "form":
                    $class_name = "Entrada_Assessments_Forms_Form";
                    $options["form_id"] = $id;
                    break;
                case "rubric":
                    $class_name = "Entrada_Assessments_Forms_Rubric";
                    $options["rubric_id"] = $id;
                    break;
                case "item":
                    $class_name = "Entrada_Assessments_Forms_Item";
                    $options["item_id"] = $id;
                    break;
            }
            $object = false;
            if ($class_name) {
                try {
                    $object = new $class_name($options);
                } catch (Exception $e) {
                    $object = false; // bad class name
                }
            }
            $this->addToStorage($object_type, $object, $id);
            return $object;
        }
    }

    /**
     * Wrapper for getObject, returning a form object.
     *
     * @param $form_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function getFormObject($form_id, $more_options = array()) {
        return $this->getObject("form", $form_id, $more_options);
    }

    /**
     * Wrapper for getObject, returning an item object.
     *
     * @param $item_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function getItemObject($item_id, $more_options = array()) {
        return $this->getObject("item", $item_id, $more_options);
    }
    
    /**
     * Wrapper for getObject, returning a rubric object.
     *
     * @param $rubric_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function getRubricObject($rubric_id, $more_options = array()) {
        return $this->getObject("rubric", $rubric_id, $more_options);
    }
}