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

    // Specific to type
    protected $form_id = null;
    protected $cbme_form_id = null;
    protected $item_id = null;
    protected $rubric_id = null;
    protected $rating_scale_id = null;
    protected $form_blueprint_id = null;
    protected $global_storage = "Entrada_Assessments_Workers_GlobalStorage";

    // Optionals
    protected $adistribution_id = null;
    protected $dassessment_id = null;
    protected $aprogress_id = null;

    // Worker objects
    protected $form, $item, $rubric, $rating_scale, $form_blueprint, $cbme_form;

    // Dataset limits
    private $dataset_limits = array(
        "form" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        ),
        "item" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        ),
        "rubric" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        ),
        "rating_scale" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        ),
        "form_blueprint" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        ),
        "cbme_form" => array(
            "limit_dataset" => array(),
            "determine_meta" => true
        )
    );

    public function __construct($arr = array()) {
        parent::__construct($arr);
        Entrada_Utilities_FormStorageSessionHelper::configure();

        // Build the default worker objects.
        $this->form             = new Entrada_Assessments_Workers_Form($this->buildActorArray(array("form_id" => $this->form_id)));
        $this->cbme_form        = new Entrada_Assessments_Workers_CBMEForm($this->buildActorArray(array("form_id" => $this->cbme_form_id)));
        $this->rubric           = new Entrada_Assessments_Workers_Rubric($this->buildActorArray(array("rubric_id" => $this->rubric_id)));
        $this->rating_scale     = new Entrada_Assessments_Workers_Scale($this->buildActorArray(array("rating_scale_id" => $this->rating_scale_id)));
        $this->item             = new Entrada_Assessments_Workers_Item($this->buildActorArray(array("item_id" => $this->item_id)));
        $this->form_blueprint   = new Entrada_Assessments_Workers_Blueprint($this->buildActorArray(array("form_blueprint_id" => $this->form_blueprint_id)));
    }

    //-- Getters & Setters --//

    public function setFormDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("form", $dataset_limits, $determine_meta);
    }

    public function setRubricDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("rubric", $dataset_limits, $determine_meta);
    }

    public function setItemDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("item", $dataset_limits, $determine_meta);
    }

    public function setRatingScaleDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("rating_scale", $dataset_limits, $determine_meta);
    }

    public function setFormBlueprintDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("form_blueprint", $dataset_limits, $determine_meta);
    }

    public function setCBMEFormDatasetLimit($dataset_limits = array(), $determine_meta = true) {
        $this->setWorkerDatasetLimit("cbme_form", $dataset_limits, $determine_meta);
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

    public function getFormBlueprintID() {
        return $this->form_blueprint_id;
    }

    public function getScaleID() {
        return $this->rating_scale_id;
    }

    public function getCBMEFormID() {
        return $this->cbme_form_id;
    }

    public function setFormID($id) {
        $this->form_id = $id;
        $this->setWorkerStale("form");
    }

    public function setFormBlueprintID($id) {
        $this->form_blueprint_id = $id;
        $this->setWorkerStale("form_blueprint");
    }

    public function setRubricID($id) {
        $this->rubric_id = $id;
        $this->setWorkerStale("rubric");
    }

    public function setItemID($id) {
        $this->item_id = $id;
        $this->setWorkerStale("item");
    }

    public function setScaleID($id) {
        $this->rating_scale_id = $id;
        $this->setWorkerStale("rating_scale");
    }

    public function setCBMEFormID($id) {
        $this->cbme_form_id = $id;
        $this->setWorkerStale("cbme_form");
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function setAprogressID($id) {
        $this->aprogress_id = $id;
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
        if (is_array($element)
            && is_array($progress)
            && isset($progress["progress_responses"])
            && is_array($progress["progress_responses"])
            && !empty($progress["progress_responses"])
        ) {
            $afelement_id = $element["afelement_id"];
            foreach ($progress["progress_responses"] as $epresponse_id => $progress_record) {
                if ($progress_record["afelement_id"] == $afelement_id) {
                    if ($progress_record["comments"] !== null) {
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

    /**
     * Fetch the classification of a form category (rubric type, or blueprint type).
     *
     * @param $form_type_id
     * @return bool
     */
    public static function getFormCategory($form_type_id) {
        if (!$form_type = Models_Assessments_Form_Type::fetchRowByID($form_type_id)) {
            return false;
        }
        return $form_type->getCategory();
    }

    /**
     * Using the progress data and form element data (fetched from Forms dataset), determine if a selection has been made on the form.
     * This method returns true if at least 1 item has been selected, OR there's at least 1 default set on the form.
     *
     * @param array $progress_data
     * @param array $form_element_data
     * @return bool
     */
    public static function hasSelectionBeenMade(&$progress_data, &$form_element_data) {
        if (!empty($progress_data)) {
            return true;
        }
        if (empty($form_element_data)) {
            return false;
        }
        foreach ($form_element_data as $elements) {
            if (empty($elements)) {
                return false;
            }
            if (!isset($elements["element"]["element_type"])) {
                continue; // Malformed dataset, but we can still continue
            }
            if ($elements["element"]["element_type"] == "item") {
                // Check if there are any items with defaults.
                // If there is at least 1, then we can consider that a selection has been made.
                if (!isset($elements["item"]["allow_default"])) {
                    continue;
                }
                if ($elements["item"]["allow_default"]) {
                    return true;
                }
            }
        }
        return false;
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
        return Entrada_Assessments_Workers_Item::canRenderOption($element_type, $itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the form item is flaggable (can be commented on if flagged).
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canFlagOption($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::canFlagOption($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can be commented on.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveComments($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::canHaveComments($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can have a default response.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveDefaultResponse($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::canHaveDefaultResponse($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can have responses.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveResponses($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::canHaveResponses($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether the item can have objectives associated.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function canHaveObjectives($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::canHaveObjectives($itemtype_shortname);
    }

    /**
     * Based on item type, determine whether an item view prefers to display the response descriptor instead of the response text.
     *
     * @param $itemtype_shortname
     * @return bool
     */
    public static function usesDescriptorInsteadOfResponseText($itemtype_shortname) {
        return Entrada_Assessments_Workers_Item::usesDescriptorInsteadOfResponseText($itemtype_shortname);
    }

    /**
     * Fetch an itemtype shortname.
     *
     * @param $itemtype_id
     * @return string
     */
    public static function fetchItemtypeShortname($itemtype_id) {
        return Entrada_Assessments_Workers_Item::fetchItemtypeShortnameByItemtypeID($itemtype_id);
    }

    /**
     * For the given rubric line data (from a rubric dataset), find if any non-default responses are selected.
     * If any responses are selected and they are not the default, return true.
     * If the rubric is configured to not allow a default, then this will return true if ANY response is selected.
     *
     * @param $lines
     * @return bool
     */
    public static function isNonDefaultRubricResponseSelected(&$lines) {
        return Entrada_Assessments_Workers_Rubric::isNonDefaultRubricResponseSelected($lines);
    }

    /**
     * For the given rubric lines, check if all of the lines have a default set.
     * @param $lines
     * @return bool
     */
    public static function itemLinesHaveDefaults(&$lines) {
        return Entrada_Assessments_Workers_Rubric::isAllowDefaultPresentInAllRubricLines($lines);
    }

    /**
     * Given the item dataset, for the specified attributes, build a list of the mutators specified in the JSON item attributes field.
     *
     * @param $item_data
     * @return array|mixed
     */
    public static function buildItemMutatorList(&$item_data) {
        if (!is_array($item_data)) {
            return array();
        }
        if (!array_key_exists("attributes", $item_data)) {
            return array();
        }
        $decoded = json_decode(@$item_data["attributes"], true);
        if (!is_array($decoded)) {
            return array();
        }
        if (!array_key_exists("mutators", $decoded)) {
            return array();
        }
        return $decoded["mutators"];
    }

    /**
     * Same as buildItemMutatorList, except using an item object (which is converted to an array compatible with buildItemMutatorList).
     *
     * @param $item_object
     * @return array|mixed
     */
    public static function buildItemMutatorListFromItemObject(&$item_object) {
        if (is_a($item_object, "Models_Assessments_Item")) {
            $item_data = $item_object->toArray();
            return self::buildItemMutatorList($item_data);
        }
        return array();
    }

    //-- Public Form Logic --//

    /**
     * Fetch form data, complete with all items and rubric data. 
     * Returns empty array on failure.
     *
     * @param $specified_id
     * @return array
     */
    public function fetchFormData($specified_id = false) {
        $form_id = $this->whichID("form_id", $specified_id);
        $options = array(
            "aprogress_id" => $this->aprogress_id,
            "adistribution_id" => $this->adistribution_id,
            "dassessment_id" => $this->dassessment_id
        );
        if ($this->buildFormObject($form_id, $options)) {
            return $this->form->fetchData();
        }
        return array();
    }

    /**
     * Copy a form.
     * If set_properties is false, the copy operation will happen, but the internal properties of this API will not be modified to
     * point to the new form.
     *
     * @param $old_form_id
     * @param string $new_form_title
     * @param bool $set_properties
     * @return bool
     */
    public function copyForm($old_form_id, $new_form_title = null, $set_properties = true) {
        global $translate;
        $form_id = $this->whichID("form_id", $old_form_id);
        if (!$form_id) {
            $this->addErrorMessage($translate->_("No form ID to copy specified"));
            return false;
        }
        if (!$this->buildFormObject($form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $new_form_id = $this->form->copy($old_form_id, $new_form_title);
        if ($new_form_id === false) {
            return false;
        }
        $this->setWorkerStale("form");
        if ($set_properties) {
            $this->setFormID($new_form_id);
        }
        return true;
    }

    /**
     * Find which assessments use this form.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getFormUsageInAssessments($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            $in_use_by = $this->form->inUseBy();
            return $in_use_by["assessments"];
        }
        return array();
    }

    /**
     * Find which distributions use this form.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getFormUsageInDistributions($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            $in_use_by = $this->form->inUseBy();
            return $in_use_by["distributions"];
        }
        return array();
    }

    /**
     * Check if a form is in use.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormInUse($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            return $this->form->isInUse();
        }
        return false;
    }

    /**
     * Check if a form is deleted (deleted_date not null)
     *
     * @param bool $specified_id
     * @return bool
     */
    public function isFormDeleted($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            return $this->form->isDeleted();
        }
        return false;
    }

    /**
     * Check if the form is editable.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormEditable($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            return $this->form->isEditable();
        }
        return false;
    }

    /**
     * Check if a form is in use.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormDisabled($specified_id = false) {
        $id = $this->whichID("form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            return $this->form->isDisabled();
        }
        return false;
    }

    /**
     * Update a form. A form is little more than links to items and some free text.
     * Saving a form is simply adjusting the orders of the items and updating the form permissions.
     *
     * @param array $form_data
     * @param int|bool $form_id
     * @return bool
     */
    public function saveForm($form_data, $form_id = false) {
        $id = $this->whichID("form_id", $form_id);
        if (!$this->buildFormObject($id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->form->loadData($form_data)) {
            $this->addErrorMessages($this->form->getErrorMessages());
            return false;
        }
        if (!$this->form->saveData()) {
            $this->addErrorMessages($this->form->getErrorMessages());
            return false;
        }
        $this->setFormID($this->form->getID());
        return true;
    }

    /**
     * Create a new empty form, with specified form title (required).
     *
     * @param $form_title
     * @param $form_type_id
     * @return bool
     */
    public function createEmptyForm($form_title, $form_type_id) {
        global $translate;
        if ($form_title === null) {
            $this->addErrorMessage($translate->_("Please specify a form title."));
        }
        if (!$this->buildFormObject(null)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->form->loadData(array("title" => $form_title, "form_type_id" => $form_type_id))) {
            $this->addErrorMessages($this->form->getErrorMessages());
            return false;
        }
        if (!$this->form->saveData()) {
            $this->addErrorMessages($this->form->getErrorMessages());
            return false;
        }
        $this->setFormID($this->form->getID());
        return true;
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
        if (!$this->buildFormObject($this->form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->form->updateFormPrimitives($title, $description)) {
            $this->addErrorMessage($translate->_("Unable to update form."));
            return false;
        }
        $this->setWorkerStale("form");
        return true;
    }

    /**
     * Add items to a form (add an element record for each ID given).
     * Returns the number of items attached.
     * Does not use or alter any internal objects.
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
        if (!$form_object = $this->buildWorkerObject("form", $form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
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
        if (!$form_object = $this->buildWorkerObject("form", $form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($rubric_ids as $rubric_id) {
            if ($form_object->attachRubric($rubric_id, null, $ignore_when_already_attached)) {
                $attachments++;
            }
        }
        if (!$attachments) {
            $this->addErrorMessage($translate->_("Unable to attach rubric to form."));
        }
        $this->addErrorMessages($form_object->getErrorMessages());
        return $attachments;
    }

    //-- Public Rubric Logic --//

    /**
     * Fetch rubric data.
     *
     * @param bool|int $specified_id
     * @return array
     */
    public function fetchRubricData($specified_id = false) {
        $rubric_id = $this->whichID("rubric_id", $specified_id);
        $options = array(
            "aprogress_id" => $this->aprogress_id,
            "adistribution_id" => $this->adistribution_id,
            "dassessment_id" => $this->dassessment_id
        );
        if ($this->buildRubricObject($rubric_id, $options)) {
            return $this->rubric->fetchData();
        }
        return array();
    }

    /**
     * Check if a rubric is in use.
     *
     * @param bool|int $specified_id
     * @return mixed
     */
    public function isRubricInUse($specified_id = false) {
        $id = $this->whichID("rubric_id", $specified_id);
        if ($this->buildRubricObject($id)) {
            return $this->rubric->isInUse();
        }
        return false;
    }

    /**
     * Check if a rubric is editable.
     *
     * @param bool|int $specified_id
     * @return mixed
     */
    public function isRubricEditable($specified_id = false) {
        $id = $this->whichID("rubric_id", $specified_id);
        if ($this->buildRubricObject($id)) {
            return $this->rubric->isEditable();
        }
        return false;

    }

    /**
     * Find which forms use this rubric.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getRubricUsageInForms($specified_id = false) {
        $id = $this->whichID("rubric_id", $specified_id);
        if ($this->buildRubricObject($id)) {
            $in_use_by = $this->rubric->inUseBy();
            return $in_use_by["forms"];
        }
        return array();
    }

    /**
     * Find which rubrics and forms use the items contained in this rubric.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getRubricItemUsage($specified_id = false) {
        $id = $this->whichID("rubric_id", $specified_id);
        if ($this->buildRubricObject($id)) {
            $items_in_use_by = $this->rubric->itemsInUseBy();
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
        if (!$this->buildFormObject($this->form_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch form object for consistency check."));
            return false;
        }
        $form_data = $this->form->fetchData();
        if (empty($form_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch data for specified form."));
            // Nothing to check; invalid form ID
            return false;
        }
        $checks = array();
        if ($form_data["meta"]["rubric_count"] > 0) {
            // There are rubrics to check.
            foreach ($form_data["rubrics"] as $rubric_id => $rubric_data) {
                if ($rubric_object = $this->buildWorkerObject("rubric", $rubric_id)) {
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
        if ($repair) {
            $this->setWorkerStale("form");
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
     * @param $rating_scale_id
     * @param $referrer_form_id
     * @return bool
     */
    public function saveRubric($rubric_title, $rubric_description, $item_code, $rating_scale_id, $referrer_form_id = null) {
        global $translate;
        $rubric_data = array(
            "rubric_id" => $this->rubric_id, // can be null
            "rubric_title" => $rubric_title,
            "rubric_description" => $rubric_description,
            "rubric_item_code" => $item_code,
            "rating_scale_id" => $rating_scale_id
        );
        if (!$this->buildRubricObject($this->rubric_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->rubric->loadData($rubric_data)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }

        if (!$this->rubric->saveData()) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }

        $this->setRubricID($this->rubric->getRubricID());

        // If we've been given a referrer form, attach the rubric to that form.
        if ($referrer_form_id) {
            if (!$this->attachRubricsToForm($referrer_form_id, array($this->rubric_id), true)) {
                $this->addErrorMessage($translate->_("Rubric was saved, but could not be attached to the form."));
            }
        }
        return true;
    }

    /**
     * Update the rubric record primitive values, not any relationships.
     *
     * @param string $title
     * @param string $description
     * @param string $code
     * @param int $rating_scale_id
     * @return bool
     */
    public function updateRubricPrimitives($title, $description, $code, $rating_scale_id = null) {
        if (!$this->buildRubricObject($this->rubric_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->rubric->updateRubricPrimitives($title, $description, $code, $rating_scale_id)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("rubric");
        return true;
    }

    /**
     * Save an empty rubric, with optional title.
     *
     * @param string $rubric_title
     * @param int $rating_scale_id
     * @param int $item_group_id
     * @return bool
     */
    public function createEmptyRubric($rubric_title = null, $rating_scale_id = null, $item_group_id = null) {
        if (!$this->buildRubricObject(null)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        // Create a new blank rubric
        if (!$this->rubric->saveEmptyRubric($rubric_title, null, null, $rating_scale_id, $item_group_id)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }
        $this->setRubricID($this->rubric->getRubricID());
        return true;
    }

    /**
     * For a given rubric, remove the old item and add a new item.
     *
     * @param $rubric_id
     * @param $old_item_id
     * @param $new_item_id
     * @return bool
     */
    public function replaceItemInRubric($rubric_id, $old_item_id, $new_item_id) {
        if (!$this->buildRubricObject($rubric_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->rubric->removeItem($old_item_id)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }
        if (!$this->rubric->attachItem($new_item_id)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("rubric");
        return true;
    }

    /**
     * Attach items to an arbitrary rubric.
     * Returns the number of items attached.
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
        if (!$this->validateActor()) {
            return false;
        }
        if (!$rubric_object = $this->buildWorkerObject("rubric", $rubric_id)) {
            return 0;
        }
        foreach ($item_ids as $item_id) {
            if ($rubric_object->attachItem($item_id)) {
                $attachments++;
            }
        }
        if ($attachments == 0) {
            $this->addErrorMessage($translate->_("Unable to add lines to rubric."));
        }
        $this->addErrorMessages($this->rubric->getErrorMessages()); // add errors, if any
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
        if (!$this->buildRubricObject(null)) {
            return false;
        }
        if (!$this->buildFormObject($form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $form_data = $this->form->fetchData();
        if (empty($form_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch form data."));
            return false;
        }
        // Copy the rubric (but don't copy the deleted items)
        if (!$this->rubric->copyRubric($old_rubric_id, $new_rubric_title)) {
            $this->addErrorMessage($translate->_("Unable to copy the old rubric."));
            return false;
        }
        // Save the ID (this triggers refresh)
        $this->setRubricID($this->rubric->getRubricID());
        // Preserve the element ordering
        $new_order = null;
        foreach ($form_data["elements"] as $element) {
            if ($element["element"]["rubric_id"] == $old_rubric_id && $new_order === null) {
                $new_order = $element["element"]["order"];
            }
        }
        // Remove existing references on the form
        if (!$this->form->removeRubricFromForm($old_rubric_id)) {
            $this->addErrorMessage($translate->_("Unable to clear rubric from form."));
            return false;
        }
        // Attach the new rubric
        if (!$this->form->attachRubric($this->rubric->getRubricID(), $new_order)) {
            $this->addErrorMessage($translate->_("Unable to attach new rubric to form."));
            return false;
        }
        return true;
    }

    /**
     * For the given rubric id, copy it, optionally specifying new title.
     * This does not create new items for the rubric; the lines of the rubric pointed to are maintained.
     *
     * @param $old_rubric_id
     * @param $new_rubric_title
     * @return bool
     */
    public function copyRubric($old_rubric_id, $new_rubric_title) {
        global $translate;
        if (!$this->buildRubricObject(null)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        // Copy the rubric
        if (!$this->rubric->copyRubric($old_rubric_id, $new_rubric_title)) {
            $this->addErrorMessage($translate->_("Unable to copy the old rubric."));
            return false;
        }
        $this->setRubricID($this->rubric->getRubricID());
        return true;
    }

    /**
     * Completely copy a rubric, creating all new lines and associations.
     *
     * @param $old_rubric_id
     * @return bool
     */
    public function deepCopyRubric($old_rubric_id) {
        if (!$this->buildRubricObject($old_rubric_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->rubric->deepCopy()) {
            $this->addErrorMessages($this->rubric->getRubricID());
            return false;
        }
        $this->setRubricID($this->rubric->getRubricID());
        return true;
    }

    /**
     * Mark rubrics as deleted.
     *
     * @param $delete_rubric_ids
     * @return array|bool
     */
    public function deleteRubrics($delete_rubric_ids) {
        global $translate;
        $deleted_rubrics = array();
        if (!is_array($delete_rubric_ids)) {
            $this->addErrorMessage($translate->_("Please specify which rubrics to delete."));
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($delete_rubric_ids as $rubric_id) {
            if ($rubric_object = $this->buildWorkerObject("rubric", $rubric_id)) {
                if ($rubric_object->delete()) {
                    $deleted_rubrics[] = $rubric_id;
                } else {
                    $this->addErrorMessages($rubric_object->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find rubric object to delete."));
            }
        }
        if (in_array($this->rubric_id, $delete_rubric_ids)) {
            $this->setWorkerStale("rubric");
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
        if (!$this->validateActor()) {
            return false;
        }
        // Find rubric ID from given aritem_id
        // TODO: This check should probably be contained in the rubric object and not a call to a model from here.
        if (!$rubric_item = Models_Assessments_Rubric_Item::fetchRowByID($aritem_id)) {
            $this->addErrorMessage($translate->_("Unable to delete the specified rubric item."));
            return false;
        }
        if (!$rubric_id = $rubric_item->getRubricID()) {
            $this->addErrorMessage($translate->_("Unable to determine which rubric this item is attached to."));
            return false;
        }
        // Fetch rubric object and delete the rubric item
        if (!$this->buildRubricObject($rubric_id)) {
            return false;
        }
        if (!$this->rubric->deleteRubricItem($aritem_id)) {
            $this->addErrorMessages($this->rubric->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("rubric");
        return true;
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
        if (!$this->buildRubricObject($this->rubric_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $rubric_data = $this->rubric->fetchData();
        if (empty($rubric_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch rubric data."));
            return false;
        }
        foreach ($rubric_data["descriptors"] as $order => $descriptor_data) {
            if ($order + 1 == $position) {
                continue;
            }
            if ($descriptor_data["ardescriptor_id"] == $new_descriptor_id) {
                // Already in use
                $this->addErrorMessage($translate->_("This descriptor is already assigned to this rubric."));
                return false;
            }
        }
        $status = $this->rubric->updateResponseDescriptor($new_descriptor_id, $position);
        if ($status) {
            $this->setWorkerStale("rubric");
        }
        return $status;
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
        $rubric_items_with_scale_ids_set = $this->getRubricItemsUsingScales(); // Fetch an array of item IDs that have scale IDs set (making the descriptors read-only)

        $has_scale = false;
        if (count($rubric_items_with_scale_ids_set) > 0) {
            $has_scale = true;
        }
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
                // We have to examine if any of the lines of the rubric have scale
                if ($has_scale) {
                    // Editable, but lock descriptors
                    return "editable-descriptors-locked-has-scale";
                } else {
                    // Not read-only, not in use
                    return "editable";
                }

            } else if (count($rubric_used_by) == 0 && (count($rubric_items_used_by["forms"]) > 0)) {

                if ($referrer_form_id && count($rubric_items_used_by["forms"]) == 1 && (reset($rubric_items_used_by["forms"]) == $referrer_form_id)) {
                    // We've been referred by the only form that uses this rubric.
                    if ($has_scale) {
                        return "editable-descriptors-locked-has-scale";
                    } else {
                        return "editable-attached";
                    }
                } else {
                    // The rubric isn't in use, but some of the items are, and there is no referrer
                    return "editable-descriptors-locked";
                }

            } else { // rubric_used_by > 0, rubric_items_used_by >= 0

                if (!$referrer_form_id) {
                    // No referrer.
                    // The rubric is in use in multiple places, but none of them are delivered.
                    if ($has_scale) {
                        return "editable-descriptors-locked-has-scale";
                    } else {
                        return "editable-attached-multiple";
                    }

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
                                if ($has_scale) {
                                    return "editable-descriptors-locked-has-scale";
                                } else {
                                    return "editable-attached";
                                }
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

    /**
     * Find which items contained in the rubric have rating_scale_ids set.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getRubricItemsUsingScales($specified_id = false) {
        $id = $this->whichID("rubric_id", $specified_id);
        if ($this->buildRubricObject($id)) {
            $items_using_scales = $this->rubric->itemsWithScaleIDs();
            return $items_using_scales;
        }
        return array();
    }

    //-- Public Item Logic --//

    /**
     * Fetch a single item's data.
     *
     * @param bool|int $specified_id
     * @param bool $cached
     * @return array
     */
    public function fetchItemData($specified_id = false, $cached = true) {
        $item_id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($item_id)) {
            return $this->item->fetchData($cached);
        }
        return array();
    }

    /**
     * Check if an item is in use.
     *
     * @param bool|int $specified_id
     * @return mixed
     */
    public function isItemInUse($specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            return $this->item->isInUse();
        }
        return false;
    }

    /**
     * Find which rubrics use this item.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getItemUsageInRubrics($specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            $in_use_by = $this->item->inUseBy();
            return $in_use_by["rubrics"];
        }
        return array();
    }

    /**
     * Find which forms use this item.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getItemUsageInForms($specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            $in_use_by = $this->item->inUseBy();
            return $in_use_by["forms"];
        }
        return array();
    }

    /**
     * Find which delivered (not started/completed/inprogress) assessments use this item.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getItemUsageInAssessments($specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            $in_use_by = $this->item->inUseBy();
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
        if ($this->buildItemObject($this->item_id)) {
            return $this->item->getItemResponseCount();
        }
        return 0;
    }

    /**
     * Update/save an item.
     *
     * @param array $item_data
     * @param int|bool $specified_id
     * @return bool
     */
    public function saveItem($item_data, $specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if (!$this->buildItemObject($id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->item->loadData($item_data)) {
            $this->addErrorMessages($this->item->getErrorMessages()); // add errors, if any
            return false;
        }
        // Loaded OK, so save it.
        if (!$this->item->saveData()) {
            $this->addErrorMessages($this->item->getErrorMessages()); // add errors, if any
            return false;
        }
        $this->setItemID($this->item->getID());
        return true;
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
        if (!$this->validateActor()) {
            return false;
        }
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
     * @param bool|int $specified_id
     * @return bool
     */
    public function loadItemData($item_data, $specified_id = false) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            $item_data["item"]["item_id"] = $id;
            return $this->item->loadData($item_data, false); // don't validate
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
        if (!$this->buildItemObject($old_item_id)) {
            return false;
        }
        // Duplicate it (creates a new dataset, without current ID)
        if (!$this->item->duplicate($clone)) {
            $this->addErrorMessages($this->item->getErrorMessages()); // add errors, if any
            return false;
        }
        if ($new_text !== null) {
            $this->item->setItemText($new_text); // Update the item text if applicable
        }
        if (!$this->item->saveData()) { // Commit it to the database, and save this object ID
            $this->addErrorMessages($this->item->getErrorMessages()); // add errors, if any
            application_log("error", "Entrada_Assessments_Forms::saveItem->Failed to COPY item for old_item_id = '$old_item_id'");
            return false;
        }
        $this->setItemID($this->item->getID());
        return true;
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
        if ($form_id) {
            if (!$this->buildFormObject($form_id)) {
                $this->addErrorMessage($translate->_("Unable to fetch form object for given ID."));
                return false;
            }
            $form_data = $this->fetchFormData($form_id);
            if (empty($form_data)) {
                $this->addErrorMessage($translate->_("Invalid form specified."));
                return false;
            }
        }
        if ($rubric_id) {
            if (!$this->buildRubricObject($rubric_id)) {
                $this->addErrorMessage($translate->_("Unable to fetch rubric object for the given ID."));
                return false;
            }
            $rubric_data = $this->fetchRubricData($rubric_id);
            if (empty($rubric_data)) {
                $this->addErrorMessage($translate->_("Invalid rubric specified."));
                return false;
            }
        }
        if (!$this->buildItemObject($old_item_id)) {
            return false;
        }
        if (!$this->item->duplicate(true)) {
            $this->addErrorMessages($this->item->getErrorMessages());
            return false;
        }
        if ($new_text !== null) {
            $this->item->setItemText($new_text); // Update the item text
        }
         if (!$this->item->saveData()) { // commit it to the database, and save this object ID
             $this->addErrorMessages($this->item->getErrorMessages());
             return false;
         }
        $this->setItemID($this->item->getID());
        $status = false;
        if ($rubric_id) {
            $old_rubric_item_order = $this->rubric->getItemOrder($old_item_id);
            if ($this->rubric->removeItem($old_item_id)) {
                // Attach the new item to the rubric
                if ($this->rubric->attachItem($this->item->getID(), $old_rubric_item_order)) {
                    $status = true;
                } else {
                    $this->addErrorMessages($this->rubric->getErrorMessages());
                }
            } else {
                $this->addErrorMessages($this->rubric->getErrorMessages());
            }
        }
        if ($form_id) {
            $old_item_order = $this->form->getElementOrder($old_item_id);
            if ($this->form->removeItemFromForm($old_item_id)) {
                // Attach the new item to the form
                if ($this->form->attachItem($this->item->getID(), $old_item_order, $rubric_id)) {
                    $status = true;
                } else {
                    $this->addErrorMessages($this->form->getErrorMessages());
                }
            } else {
                $this->addErrorMessages($this->form->getErrorMessages());
            }
        }
        return $status;
    }

    /**
     * Mark arbitrary items as deleted.
     *
     * @param array $delete_item_ids
     * @return array|bool
     */
    public function deleteItems($delete_item_ids) {
        global $translate;
        $deleted_items = array();
        if (!is_array($delete_item_ids)) {
            $this->addErrorMessage($translate->_("Please specify which items to delete."));
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($delete_item_ids as $item_id) {
            if ($item_object = $this->buildWorkerObject("item", $item_id)) {
                if ($item_object->delete()) {
                    $deleted_items[] = $item_id;
                } else {
                    $this->addErrorMessages($item_object->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find item object to delete."));
            }
        }
        if (in_array($this->item_id, $delete_item_ids)) {
            $this->setWorkerStale("item");
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

        $item_has_scale = false;
        if ($item_data["item"]["rating_scale_id"]) {
            // Has a rating scale associated with it
            $item_has_scale = true;
        }

        $referrer_form_data = array();
        if ($referrer_form_id) {
            $referrer_form_data = $this->fetchFormData($referrer_form_id);
            if (empty($referrer_form_data)) {
                // This form is invalid; we can safely ignore it.
                $referrer_form_id = null;
            }
        }

        $referrer_rubric_has_scale = false;
        $referrer_rubric_data = array();
        if ($referrer_rubric_id) {
            $referrer_rubric_data = $this->fetchRubricData($referrer_rubric_id);
            if (empty($referrer_rubric_data)) {
                // The referred rubric is invalid, we can safely ignore it.
                $referrer_rubric_id = null;
            } else {
                $lines_of_referrer_rubric = $referrer_rubric_data["meta"]["lines_count"];
                if ($referrer_rubric_data["rubric"]["rating_scale_id"]) {
                    $referrer_rubric_has_scale = true;
                } else {
                    if (!empty($referrer_rubric_data["meta"]["items_with_scale_id"])) {
                        if ($item_data["item"]["item_id"] != end($referrer_rubric_data["meta"]["items_with_scale_id"])) {
                            $referrer_rubric_has_scale = true;
                        }
                    }
                }
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
                        if ($lines_of_referrer_rubric == 1 && !$referrer_rubric_has_scale) {
                            // We can allow full editability when referrers match, and there's only 1 line in the rubric
                            return "editable-by-attached-form-and-rubric-unlocked-descriptors";
                        }
                        if ($lines_of_referrer_rubric == 1 && $referrer_rubric_has_scale) {
                            return "editable-by-attached-form-and-rubric-has-scale";
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
                            if ($referrer_rubric_has_scale) {
                                // Has a scale, so disable descriptors
                                return "editable-by-attached-rubric-has-scale";

                            } else {
                                // The rubric that it is attached to is our referrer
                                return "editable-by-attached-rubric-unlocked-descriptors";
                            }
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
                        // Otherwise notify that multiple forms use this (but this referrer isn't one of them)
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

    /**
     * Wrapper for new item creation.
     * The arrays are flat arrays, indexed by order; Response #2 has data in the array[2] position in the flagged_responses, response_descriptors, and responses arrays.
     * The objectives array is a flat array of objective IDs that uses natural index order (0-indexed).
     * Comment type is an enum value defined in the DB schema; possible values are: disabled, mandatory, optional, flagged.
     * Default response refers to the response order; e.g., $default_value == 3 means the item with item_record.`response_order` == 3.
     *
     * @param int $item_text
     * @param int $itemtype_id
     * @param string $item_code
     * @param string $item_description
     * @param int $rating_scale_id
     * @param int $mandatory
     * @param string $comment_type
     * @param array $responses
     * @param array $flagged_responses
     * @param array $response_descriptors
     * @param array $objectives
     * @param int $item_group_id
     * @param bool $allow_default
     * @param int $default_value
     * @param string $attributes // json_encoded string
     * @return bool
     */
    public function createItem($item_text, $itemtype_id, $item_code, $item_description, $rating_scale_id, $mandatory, $comment_type, $responses, $flagged_responses, $response_descriptors, $objectives = array(), $item_group_id = null, $allow_default = false, $default_value = null, $attributes = null) {
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->buildItemObject(null)) {
            return false;
        }
        $item_data = array(
            "item" => array(
                "item_id" => null,
                "itemtype_id" => $itemtype_id,
                "item_code" => $item_code,
                "item_text" => $item_text,
                "item_description" => $item_description,
                "rating_scale_id" => $rating_scale_id,
                "mandatory" => $mandatory,
                "comment_type" => $comment_type,
                "organisation_id" => $this->actor_organisation_id,
                "item_group_id" => $item_group_id,
                "allow_default" => $allow_default ? 1 : 0,
                "default_response" => $default_value,
                "attributes" => $attributes
            ),
            "flag_response" => $flagged_responses,
            "responses" => $responses,
            "descriptors" => $response_descriptors,
            "objectives" => $objectives
        );
        if (!$this->item->loadData($item_data)) {
            $this->addErrorMessages($this->item->getErrorMessages());
            return false;
        }
        // Loaded OK, so save it.
        if (!$this->item->saveData()) {
            $this->addErrorMessages($this->item->getErrorMessages());
            return false;
        }
        $this->setItemID($this->item->getID());
        return true;
    }

    /**
     * Shortcut for rendering an item on-the-fly.
     * Renders the current item, or a specified one.
     *
     * @param bool|int $specified_id
     * @param bool $disabled
     * @param string $assessment_mode
     * @param bool $render_as_disabled
     * @param string $referrer_hash
     * @param array $construction_options
     */
    public function renderItem($specified_id = false, $disabled = false, $assessment_mode = null, $render_as_disabled = false, $referrer_hash = null, $construction_options = array("mode" => "assessment-blank")) {
        $id = $this->whichID("item_id", $specified_id);
        if ($this->buildItemObject($id)) {
            if ($assessment_mode) {
                $construction_options = array_merge($construction_options, array("mode" => $assessment_mode));
            }
            $item_view = new Views_Assessments_Forms_Item($construction_options);
            $item_view->render($this->buildItemViewOptionsForRender($this->fetchItemData($id), $disabled, $referrer_hash, $render_as_disabled));
        }
    }

    //-- Public Scale Logic --//

    /**
     * Fetch a single scale's data.
     *
     * @param bool|int $specified_id
     * @return array
     */
    public function fetchScaleData($specified_id = false) {
        $scale_id = $this->whichID("rating_scale_id", $specified_id);
        if ($this->buildScaleObject($scale_id)) {
            return $this->rating_scale->fetchData();
        }
        return array();
    }

    /**
     * Update/save a rating scale.
     *
     * @param $scale_data
     * @param bool|int $specified_id
     * @return bool
     */
    public function saveScale($scale_data, $specified_id = false) {
        $id = $this->whichID("rating_scale_id", $specified_id);
        if (!$this->buildScaleObject($id)) {
            return false;
        }
        if (!$this->rating_scale->loadData($scale_data)) {
            $this->addErrorMessages($this->rating_scale->getErrorMessages()); // add errors, if any
            return false;
        }
        // Loaded OK, so save it.
        if (!$this->rating_scale->saveData()) {
            return false;
        }
        $this->setScaleID($this->rating_scale->getID());
        return true;
    }

    /**
     * Check if a rating scale is in use
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isScaleInUse($specified_id = false) {
        $id = $this->whichID("rating_scale_id", $specified_id);
        if ($this->buildScaleObject($id)) {
            return $this->rating_scale->isInUse();
        }
        return false;
    }

    /**
     * Delete scales specified by ids
     *
     * @param $delete_scales_ids
     * @return bool|int
     */
    public function deleteScales($delete_scales_ids) {
        global $translate;
        if (!is_array($delete_scales_ids)) {
            $this->addErrorMessage($translate->_("Please specify which scales to delete."));
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $deletions = 0;
        $deleted_scales = array();
        foreach ($delete_scales_ids as $scale_id) {
            if ($scale_object = $this->buildWorkerObject("rating_scale", $scale_id) ) {
                if ($scale_object->delete()) {
                    $deleted_scales[] = $scale_id;
                    $deletions++;
                } else {
                    $this->addErrorMessage($scale_object->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find scale object to delete."));
            }
        }
        if (in_array($this->rating_scale_id, $delete_scales_ids)) {
            $this->setWorkerStale("rating_scale");
        }
        return $deletions;
    }

    /**
     * Fetch a list of scales by a given type.
     *
     * @param $scale_type_shortname
     * @return array
     */
    public function fetchScalesList($scale_type_shortname) {
        if (!$this->validateActor()) {
            return array();
        }
        // ADRIAN-TODO: Move this to the scale worker
        // Fetch all scales in the system of the given type
        $scales = Models_Assessments_RatingScale_Type::fetchRatingScalesByShortnameOrganisationID($scale_type_shortname, $this->actor_organisation_id);
        if (empty($scales)) {
            return array();
        }
        return $scales;
    }

    /**
     * Get a list of scale types, only include the types that have scales associated with them.
     *
     * @param int $organisation_id
     * @param bool $include_default
     * @param bool $set_default_shortname
     * @return array
     */
    public function getScaleTypesInUse($organisation_id, $include_default = false, $set_default_shortname = true) {
        global $translate;
        // ADRIAN-TODO: Move this to the scale worker
        $types_in_use = array();
        if ($include_default) {
            $no_typed_scale_count = Models_Assessments_RatingScale::fetchCountRatingScalesWithoutTypesInUse();
            if ($no_typed_scale_count > 0) {
                $default_shortname = null;
                if ($set_default_shortname) {
                    $default_shortname = clean_input($translate->_("Default"), array("alpha", "trim", "lower"));
                }
                $types_in_use[] = array("rating_scale_type_id" => 0, "title" => $translate->_("Default"), "shortname" => $default_shortname);
            }
        }
        $scale_types = Models_Assessments_RatingScale_Type::fetchRatingScaleTypesInUse($organisation_id);
        if (!empty($scale_types)) {
            $types_in_use = array_merge($types_in_use, $scale_types);
        }
        return $types_in_use;
    }

    //-- Public Form Blueprint/Template Functionality --//

    /**
     * Fetch a form blueprint's data.
     *
     * @param bool|int $specified_id
     * @return array
     */
    public function fetchFormBlueprintData($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->fetchData();
        }
        return array();
    }

    /**
     * Can this blueprint be published?
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormBlueprintPublishable($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->isPublishable();
        }
        return false;
    }

    /**
     * Is this form complete? A complete form blueprint is one that had its forms created (published).
     *
     * @param bool $specified_id
     * @return bool
     */
    public function isFormBlueprintComplete($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->isComplete();
        }
        return false;
    }

    /**
     * Is this blueprint published?
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormBlueprintPublished($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->isPublished();
        }
        return false;
    }

    /**
     * Is this blueprint deleted?
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormBlueprintDeleted($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->isDeleted();
        }
        return false;
    }

    /**
     * Is this blueprint the active one for this organisation?
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isFormBlueprintActive($specified_id = false) {
        $id = $this->whichID("form_blueprint_id", $specified_id);
        if ($this->buildFormBlueprintObject($id)) {
            return $this->form_blueprint->isActive();
        }
        return false;
    }

    /**
     * Fetch the list of procedure forms (if any) for the current blueprint.
     *
     * @param int|null $objective_id
     * @return array|bool
     */
    public function fetchFormBlueprintProcedureList($objective_id = null) {
        global $translate;
        if (!$this->form_blueprint_id) {
            $this->addErrorMessage($translate->_("Form Template ID not specified."));
            return false;
        }
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        $objective_set_model = new Models_ObjectiveSet();
        $objective_set_record = $objective_set_model->fetchRowByShortname("contextual_variable_responses");
        if (!$objective_set_record) {
            $this->addErrorMessage($translate->_("Invalid objective set ID."));
            return false;
        }
        return $this->form_blueprint->fetchFormListByType("procedure", $objective_set_record->getID());
    }

    /**
     * Save a form blueprint.
     *
     * @param $form_blueprint_data
     * @param bool|int $form_blueprint_id
     * @return bool
     */
    public function saveFormBlueprint($form_blueprint_data, $form_blueprint_id = false) {
        $id = $this->whichID("form_blueprint_id", $form_blueprint_id);
        if (!$this->buildFormBlueprintObject($id)) {
            return false;
        }
        if (!$this->form_blueprint->loadData($form_blueprint_data)) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages());
            return false;
        }
        if (!$this->form_blueprint->saveData()) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages());
        }
        $this->setFormBlueprintID($this->form_blueprint->getID());
        return true;
    }

    /**
     * Set a form blueprint as published.
     *
     * @return bool
     */
    public function setFormBlueprintAsPublished() {
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        if (!$this->form_blueprint->setPublished()) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("form_blueprint");
        return true;
    }

    /**
     * Publish the current blueprint, creating all the forms and associations for it.
     * This also deactivates any other active blueprints of the same type.
     *
     * This method is expensive and time consuming for the more significant blueprints.
     * A separate utility, executed via cron, calls this functionality.
     *
     * @return bool
     */
    public function publishFormBlueprint() {
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        if (!$this->form_blueprint->publishBlueprint()) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("form_blueprint");
        return true;
    }

    /**
     * Publish the current CBME form, associating EPAs for mapped items' objectives.
     *
     * @return bool
     */
    public function publishCBMEForm() {
        if (!$this->buildCBMEFormObject($this->cbme_form_id)) {
            return false;
        }
        if (!$this->cbme_form->publishCBMEForm()) {
            $this->addErrorMessage($this->cbme_form->getErrorMessages());
            return false;
        }
        $this->setWorkerStale("cbme_form");

        return true;
    }

    /**
     * Update the form blueprint with component data. This can be transitional state-based data.
     *
     * @param $blueprint_id
     * @param $blueprint_component_type
     * @param $component_data
     * @param $component_order
     * @param string $comment_type
     * @param array $flagged_response_descriptors
     * @param string $editor_state
     * @param int $default
     * @return bool
     */
    public function updateBlueprintProgress($blueprint_id, $blueprint_component_type, $component_data, $component_order, $comment_type = "disabled", $flagged_response_descriptors = array(), $editor_state = null, $default = null) {
        global $translate;
        if (!$this->buildFormBlueprintObject($blueprint_id)) {
            return false;
        }
        $blueprint_data = $this->form_blueprint->fetchData();
        if (empty($blueprint_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch blueprint data."));
            return false;
        }
        // ADRIAN-TODO: Might need authorship check here?
        if (!$this->form_blueprint->isAuthorizedOrganisation()) {
            $this->addErrorMessage($translate->_("Unable to access blueprint."));
            return false;
        }
        if ($this->form_blueprint->getFormTypeCategory() != "blueprint") {
            $this->addErrorMessage($translate->_("The specified form is not a blueprint."));
            return false;
        }
        // Check if the specified component type matches what is appropriate for the given blueprint.
        if (!$this->form_blueprint->isValidComponentType($blueprint_component_type)) {
            $this->addErrorMessage($translate->_("The specified component type is not valid for this blueprint."));
            return false;
        }
        // Attempt to update the component
        if (!$this->form_blueprint->updateBlueprintComponentProgress($blueprint_component_type, $component_data, $component_order, $comment_type, $flagged_response_descriptors, $editor_state, $default)) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages()); // pass along the error message(s)
            return false;
        }
        // Updated OK
        $this->setWorkerStale("form_blueprint");
        return true;
    }

    /**
     * This method return the next component in line for the blueprint specified by form_blueprint_id
     *
     * @param $current_component
     * @return false|array
     */
    public function fetchNextBlueprintComponent($current_component) {
        global $translate;
        if (!$this->form_blueprint_id) {
            $this->addErrorMessage($translate->_("Form Blueprint ID not specified."));
            return false;
        }
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        $blueprint_data = $this->form_blueprint->fetchData();
        if (empty($blueprint_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch blueprint data."));
            return false;
        }
        if (empty($blueprint_data["components"])) {
            // No components; no next one to fetch!
            $this->addErrorMessage($translate->_("There are no components associated with this blueprint."));
            return false;
        }
        for ($i = $current_component + 1; $i <= count($blueprint_data["components"]); $i++) {
            if (isset($blueprint_data["components"][$i])
                && !in_array($blueprint_data["components"][$i]["shortname"], array("standard_item"))
            ) {
                if ($blueprint_data["components"][$i]["shortname"] == "free_text_element" ) {
                    if (!isset($blueprint_data["components"][$i]["settings"]["editable"]) || $blueprint_data["components"][$i]["settings"]["editable"]!=1) {
                        // This free text is not editable, jump to next component
                        continue;
                    }
                }
                if (isset($blueprint_data["components"][$i]["settings"]["locked"]) && $blueprint_data["components"][$i]["settings"]["locked"] == 1) {
                    continue;
                }

                $component = $blueprint_data["components"][$i];
                $component["component_id"] = $i;
                return $component;
            }
        }
        return array();
    }

    /**
     * For the current blueprint, fetch all of the objectives for the given component.
     * Optionally filter the list by objective_id, objective set, objective code, and associated objective ID. Typically, this would be an EPA.
     * NOTE: the associated objective ID field relates to the afblueprint_objective_id record ID, not the actual objective ID.
     *
     * @param string $component_shortname
     * @param int|null $objective_id
     * @param int|null $assoc_objective_id
     * @param string|null $objective_set_shortname
     * @param string|null $objective_code
     * @param bool $group_by_objective_id
     * @return array|bool
     */
    public function fetchBlueprintComponentObjectives($component_shortname, $objective_id = null, $assoc_objective_id = null, $objective_set_shortname = null, $objective_code = null, $group_by_objective_id = false) {
        global $translate;
        if (!$component_shortname) {
            $this->addErrorMessage($translate->_("Component shortname not specified."));
            return false;
        }
        if (!$this->form_blueprint_id) {
            $this->addErrorMessage($translate->_("Form Blueprint ID not specified."));
            return false;
        }
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        $blueprint_data = $this->form_blueprint->fetchData();
        if (empty($blueprint_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch blueprint data."));
            return false;
        }
        $component_id = null;
        foreach ($blueprint_data["components"] as $component) {
            if ($component["shortname"] == $component_shortname) {
                $component_id = $component["blueprint_component_id"];
                break;
            }
        }
        if (!$component_id) {
            $this->addErrorMessage($translate->_("Unable to find specified component."));
            return false;
        }
        $element_id = null;
        foreach ($blueprint_data["elements"] as $element) {
            if ($element["element_type"] == "blueprint_component"
                && $element["element_value"] == $component_id
            ) {
                $element_id = $element["afblueprint_element_id"];
                break;
            }
        }
        if (!$element_id) {
            $this->addErrorMessage($translate->_("Unable to find specified template element."));
            return false;
        }
        $collected_objectives = array();
        // Gather up the objectives for this component/element
        foreach ($blueprint_data["objectives"] as $ob_element_id => $objectives) {
            if ($ob_element_id == $element_id) {
                foreach ($objectives as $objective) {
                    $matched = ($objective_set_shortname ? $objective_set_shortname == $objective["objective_set_shortname"] : true)
                        && ($objective_code ? $objective_code == $objective["objective_code"] : true)
                        && ($objective_id ? $objective_id == $objective["objective_id"] : true)
                        && ($assoc_objective_id ? $assoc_objective_id == $objective["associated_objective_id"] : true);
                    if ($matched) {
                        if ($group_by_objective_id) {
                            $collected_objectives[$objective["objective_id"]] = $objective;
                        } else {
                            $collected_objectives[] = $objective;
                        }

                    }
                }
            }
        }
        return $collected_objectives;
    }

    /**
     * Get the first editable blueprint component from the dataset.
     *
     * @return array|bool
     */
    public function fetchFirstEditableBlueprintComponent() {
        global $translate;
        if (!$this->form_blueprint_id) {
            $this->addErrorMessage($translate->_("Form Blueprint ID not specified."));
            return false;
        }
        if (!$this->buildFormBlueprintObject($this->form_blueprint_id)) {
            return false;
        }
        $blueprint_data = $this->form_blueprint->fetchData();
        if (empty($blueprint_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch blueprint data."));
            return false;
        }
        if (empty($blueprint_data["components"])) {
            // No components; no next one to fetch!
            $this->addErrorMessage($translate->_("There are no components associated with this blueprint."));
            return false;
        }
        for ($i = 0; $i <= count($blueprint_data["components"]); $i++) {
            if (isset($blueprint_data["components"][$i]) && !in_array($blueprint_data["components"][$i]["shortname"], array("standard_item"))) {
                if ($blueprint_data["components"][$i]["shortname"] == "free_text_element") {
                    if (!isset($blueprint_data["components"][$i]["settings"]["editable"]) || $blueprint_data["components"][$i]["settings"]["editable"] != 1) {
                        // This free text is not editable, jump to next component
                        continue;
                    }
                }
                // Skip locked roles
                if ($blueprint_data["components"][$i]["shortname"] == "role_selector") {
                    if (!isset($blueprint_data["components"][$i]["settings"]["locked"]) || $blueprint_data["components"][$i]["settings"]["locked"] == 1) {
                        // This role selector is locked, jump to next component
                        continue;
                    }
                }
                $component = $blueprint_data["components"][$i];
                $component["component_id"] = $i;
                return $component;
            }
        }
        return array();
    }

    /**
     * Fetch blueprint data for UI.
     *
     * @param $form_blueprint_id
     * @param int $component_id
     * @return array|mixed|null
     */
    public function fetchBlueprintComponentData($form_blueprint_id, $component_id = 0) {
        global $translate;
        if (!$form_blueprint_id) {
            $this->addErrorMessage($translate->_("Invalid template ID"));
            return false;
        }
        if (!$this->buildFormBlueprintObject($form_blueprint_id)) {
            return false;
        }
        $component_data = $this->form_blueprint->fetchComponentData($component_id);
        if (empty($component_data)) {
            $this->addErrorMessages($this->form_blueprint->getErrorMessages());
            return false;
        }
        return $component_data;
    }

    /**
     * Mark form blueprint as deleted.
     *
     * @param array $delete_blueprint_ids
     * @return array|bool
     */
    public function deleteBlueprints($delete_blueprint_ids) {
        global $translate;
        $deleted_items = array();
        if (!is_array($delete_blueprint_ids)) {
            $this->addErrorMessage($translate->_("Please specify which items to delete."));
            return array();
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($delete_blueprint_ids as $blueprint_id) {
            if ($this->buildFormBlueprintObject($blueprint_id)) {
                if ($this->form_blueprint->delete()) {
                    $deleted_items[] = $blueprint_id;
                } else {
                    $this->addErrorMessages($this->form_blueprint->getErrorMessages());
                }
            } else {
                $this->addErrorMessage($translate->_("Unable to find form template object to delete."));
            }
        }
        if (in_array($this->form_blueprint_id, $delete_blueprint_ids)) {
            $this->setWorkerStale("form_blueprint");
        }
        return $deleted_items;
    }

    /**
     * Copy a blueprint.
     *
     * @param int $from_id
     * @param $new_blueprint_title
     * @param $new_course_id
     * @param bool $set_properties
     * @return bool
     */
    public function copyBlueprint($from_id, $new_blueprint_title, $new_course_id, $set_properties = true) {
        global $translate;
        $form_blueprint_id = $this->whichID("form_id", $from_id);
        if (!$form_blueprint_id) {
            $this->addErrorMessage($translate->_("No form ID to copy specified"));
            return false;
        }
        if (!$this->buildFormBlueprintObject($form_blueprint_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $new_form_blueprint_id = $this->form_blueprint->copy($from_id, $new_blueprint_title, $new_course_id);
        if ($new_form_blueprint_id === false) {
            return false;
        }
        $this->setWorkerStale("form");
        if ($set_properties) {
            $this->setFormBlueprintID($new_form_blueprint_id);
        }
        return true;
    }

    /**
     * Disable the forms for a given type and course.
     *
     * @param int $form_type_id
     * @param int $course_id
     * @return bool|mixed
     */
    public function disablePreviousForms($form_type_id, $course_id = 0) {
        global $translate;
        if (!$form_type_id) {
            $this->addErrorMessage($translate->_("Form type not specified."));
            return false;
        }
        return Models_Assessments_Form::deleteByFormTypeCourseID($form_type_id, $this->actor_proxy_id, $course_id);
    }

    /**
     * Delete all other blueprints from the form type except for the one specified
     *
     * @param $form_type_id
     * @param $form_blueprint_id
     * @return bool|mixed
     */
    public function deleteOtherFormTypeBlueprints($form_type_id, $form_blueprint_id) {
        if (!$form_type_id) {
            $this->addErrorMessage("Form Type ID cannot be null");
            return false;
        }

        if (!$form_blueprint_id) {
            $this->addErrorMessage("Form Template ID cannot be null");
            return false;
        }

        return Models_Assessments_Form_Blueprint::deleteOtherFormTypeBlueprint($form_type_id, $form_blueprint_id, $this->actor_proxy_id);
    }

    //-- Public CBME Form Logic --//

    /**
     * Fetch CBME form data, complete with all items and rubric data.
     * Returns empty array on failure.
     *
     * @param $specified_id
     * @return array
     */
    public function fetchCBMEFormData($specified_id = false, $use_cache = true) {
        $form_id = $this->whichID("cbme_form_id", $specified_id);
        $options = array(
            "aprogress_id" => $this->aprogress_id,
            "adistribution_id" => $this->adistribution_id,
            "dassessment_id" => $this->dassessment_id
        );
        if ($this->buildCBMEFormObject($form_id, $options)) {
            return $this->cbme_form->fetchData($use_cache);
        }
        return array();

    }

    /**
     * Copy a form.
     * If set_properties is false, the copy operation will happen, but the internal properties of this API will not be modified to
     * point to the new form.
     *
     * @param $old_form_id
     * @param string $new_form_title
     * @param bool $set_properties
     * @return bool
     */
    public function copyCBMEForm($old_form_id, $new_form_title = null, $set_properties = true) {
        global $translate;
        $cbme_form_id = $this->whichID("cbme_form_id", $old_form_id);
        if (!$cbme_form_id) {
            $this->addErrorMessage($translate->_("No form ID to copy specified"));
            return false;
        }
        if (!$this->buildCBMEFormObject($cbme_form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        $new_form_id = $this->cbme_form->copy($old_form_id, $new_form_title);
        if ($new_form_id === false) {
            return false;
        }
        $this->setWorkerStale("cbme_form");
        if ($set_properties) {
            $this->setCBMEFormID($new_form_id);
        }
        return true;
    }

    /**
     * Find which assessments use this form.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getCBMEFormUsageInAssessments($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildCBMEFormObject($id)) {
            $in_use_by = $this->cbme_form->inUseBy();
            return $in_use_by["assessments"];
        }
        return array();
    }

    /**
     * Find which distributions use this form.
     *
     * @param bool|int $specified_id
     * @return array of ids
     */
    public function getCBMEFormUsageInDistributions($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildFormObject($id)) {
            $in_use_by = $this->cbme_form->inUseBy();
            return $in_use_by["distributions"];
        }
        return array();
    }

    /**
     * Check if a form is in use.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isCBMEFormInUse($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildCBMEFormObject($id)) {
            return $this->cbme_form->isInUse();
        }
        return false;
    }

    /**
     * Check if a form is deleted (deleted_date not null)
     *
     * @param bool $specified_id
     * @return bool
     */
    public function isCBMEFormDeleted($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildCBMEFormObject($id)) {
            return $this->cbme_form->isDeleted();
        }
        return false;
    }

    /**
     * Check if the form is editable.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isCBMEFormEditable($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildCBMEFormObject($id)) {
            return $this->form->isEditable();
        }
        return false;
    }

    /**
     * Check if a form is in use.
     *
     * @param bool|int $specified_id
     * @return bool
     */
    public function isCBMEFormDisabled($specified_id = false) {
        $id = $this->whichID("cbme_form_id", $specified_id);
        if ($this->buildCBMEFormObject($id)) {
            return $this->cbme_form->isDisabled();
        }
        return false;
    }

    /**
     * Update a form. A form is little more than links to items and some free text.
     * Saving a form is simply adjusting the orders of the items and updating the form permissions.
     *
     * @param array $form_data
     * @param int|bool $cbme_form_id
     * @return bool
     */
    public function saveCBMEForm($form_data, $cbme_form_id = false) {
        $id = $this->whichID("cbme_form_id", $cbme_form_id);
        if (!$this->buildCBMEFormObject($id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->cbme_form->loadData($form_data)) {
            $this->addErrorMessages($this->cbme_form->getErrorMessages());
            return false;
        }
        if (!$this->cbme_form->saveData()) {
            $this->addErrorMessages($this->cbme_form->getErrorMessages());
            return false;
        }
        $this->setCBMEFormID($this->cbme_form->getID());
        return true;
    }

    /**
     * Create a new empty form, with specified form title (required).
     *
     * @param $form_title
     * @param $form_type_id
     * @return bool
     */
    public function createEmptyCBMEForm($form_title, $form_type_id, $attributes) {
        global $translate;
        if ($form_title === null) {
            $this->addErrorMessage($translate->_("Please specify a form title."));
            return false;
        }
        if (!isset($attributes["course_id"])) {
            $this->addErrorMessage($translate->_("Please specify a course id."));
            return false;
        }
        if (!$this->buildCBMEFormObject(null)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->cbme_form->loadData(array("title" => $form_title, "form_type_id" => $form_type_id, "attributes" => $attributes))) {
            $this->addErrorMessages($this->form->getErrorMessages());
            return false;
        }
        if (!$this->cbme_form->saveData()) {
            $this->addErrorMessages($this->cbme_form->getErrorMessages());
            return false;
        }
        $this->setCBMEFormID($this->cbme_form->getID());
        return true;
    }

    /**
     * Save the non-relational data parts of form record.
     *
     * @param $title
     * @param $description
     * @return bool
     */
    public function updateCBMEFormPrimitives($title, $description) {
        global $translate;
        if (!$this->buildCBMEFormObject($this->cbme_form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->form->updateCBMEFormPrimitives($title, $description)) {
            $this->addErrorMessage($translate->_("Unable to update form."));
            return false;
        }
        $this->setWorkerStale("cbme_form");
        return true;
    }

    /**
     * Add items to a form (add an element record for each ID given).
     * Returns the number of items attached.
     * Does not use or alter any internal objects.
     *
     * @param int $form_id
     * @param array $item_ids integers
     * @param int $as_part_of_rubric_id
     * @return int
     */
    public function attachItemsToCBMEForm($form_id, $item_ids = array(), $as_part_of_rubric_id = null) {
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
        if (!$form_object = $this->buildWorkerObject("cbme_form", $form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($item_ids as $item_id) {
            if ($form_object->attachItem($item_id, null, $as_part_of_rubric_id, $as_part_of_rubric_id ? true : false)) {
                $attachments++;
            }
        }
        $this->addErrorMessages($this->cbme_form->getErrorMessages());
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
    public function attachRubricsToCBMEForm($form_id, $rubric_ids = array(), $ignore_when_already_attached = false) {
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
        if (!$form_object = $this->buildWorkerObject("cbme_form", $form_id)) {
            return false;
        }
        if (!$this->validateActor()) {
            return false;
        }
        foreach ($rubric_ids as $rubric_id) {
            if ($form_object->attachRubric($rubric_id, null, $ignore_when_already_attached)) {
                $attachments++;
            }
        }
        if (!$attachments) {
            $this->addErrorMessage($translate->_("Unable to attach rubric to form."));
        }
        $this->addErrorMessages($this->cbme_form->getErrorMessages());
        return $attachments;
    }

    //-- Public Objectives and Objective Tree Logic --//

    /**
     * Fetch all mapped EPAs for a given course ID. This is limited by actor organisation and course.
     *
     * @param $course_id
     * @param $ordering
     * @return bool|mixed
     */
    public function fetchMappedEPAs($course_id, $ordering = "o.`objective_code`") {
        if (!$this->validateActor()) {
            return false;
        }
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "course_id" => $course_id,
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id
        ));
        return $tree_object->fetchTreeNodesByObjectiveSetShortname("epa", $ordering);
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
            return $tree_object->fetchLeafNodes($objective_record->getID(), "o.`objective_order`");
        }
        return false;
    }

    /**
     * Fetch all mapped roles for a given course ID. This is limited by actor organisation and course.
     *
     * @param $course_id
     * @return bool|mixed
     */
    public function fetchMappedRoles($course_id) {
        if (!$this->validateActor()) {
            return false;
        }
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "course_id" => $course_id,
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id
        ));
        if ($objectives = $tree_object->fetchTreeNodesByObjectiveSetShortname("role")) {
            $mapped = array();
            foreach ($objectives as $objective) {
                $mapped[$objective["objective_id"]] = array(
                    "objective_id" => $objective["objective_id"],
                    "objective_code" => $objective["objective_code"],
                    "objective_name" => $objective["objective_name"]
                );
            }

            return $mapped;
        }

        return false;
    }

    /**
     * Fetch the type of objectives (either milestones or ECs) for a given course.
     *
     * @param $course_id
     * @return bool
     */
    public function fetchCourseObjectiveType($course_id) {
        if (! $epas = $this->fetchMappedEPAs($course_id) ) {
            return false;
        }
        if (! $epa = reset($epas)) {
            return false;
        }
        if (! $objectives = $this->fetchMappedMilestonesForEPA($course_id, $epa["objective_id"]) ) {
            return false;
        }
        if (! $objective = reset($objectives)) {
            return false;
        }
        if (!$objective_object = Models_Objective::fetchRow($objective["objective_id"]) ) {
            return false;
        }
        $objectiveset_object = new Models_ObjectiveSet();
        if (!$objective_set = $objectiveset_object->fetchRowByID($objective_object->getObjectiveSetID()) ) {
            return false;
        }
        return $objective_set->getTitle();
    }

    /**
     * Fetch the contextual variables for this organisation
     *
     * @param string $order_by
     * @return array
     */
    public function fetchContextualVariables($order_by = "") {
        if (!$this->validateActor()) {
            return array();
        }
        $objective_set = new Models_ObjectiveSet();
        $contextual_variables = $objective_set->fetchAllParentObjectivesByShortname("contextual_variable", $this->actor_organisation_id, $order_by);
        if (!empty($contextual_variables)) {
            return $contextual_variables;
        }
        return array();
    }

    /**
     * Fetch the responses associated to a contextual variable
     *
     * @param $cvar_id
     * @param $course_id
     * @return array
     */
    public function fetchContextualVariableResponses($cvar_id, $course_id) {
        $responses = array();
        if (!$this->validateActor()) {
            return array();
        }
        if ($child_objectives = Models_Objective::fetchAllByParentIDCBMECourseObjective($cvar_id, $course_id, $this->actor_organisation_id)) {
            foreach ($child_objectives as $objective) {
                $responses[] = $objective->getID();
            }
        }
        return $responses;
    }

    /**
     * Fetch the number of responses (child objectives) for the contextual variable
     * specified by objective_id
     *
     * @param $objective_id
     * @param $course_id
     * @return int
     */
    public function fetchContextualVariableResponseCount($objective_id, $course_id) {
        if (!$this->validateActor()) {
            return 0;
        }
        if ($responses = Models_Objective::fetchAllByParentIDCBMECourseObjective($objective_id, $course_id, $this->actor_organisation_id)) {
            $responses_count = count($responses);
        } else {
            $responses_count = 0;
        }
        return $responses_count;
    }

    /**
     * This method returns an array of type array[var_id] = var_desc
     *
     * TODO: This function needs work.
     *
     * @param $course_id
     * @param $selected_epas
     * @return array|bool
     */
    public function getDefaultContextualVarsMappingSets($course_id, $selected_epas) {
        global $translate;
        $cv_list = $this->fetchContextualVariables();
        $epas_list = $this->fetchMappedEPAs($course_id);

        if (empty($cv_list)) {
            $this->addErrorMessage($translate->_("Contextual variables not configured."));
            return false;
        }
        if (empty($epas_list)) {
            $this->addErrorMessage($translate->_("EPAs are not mapped."));
            return false;
        }

        $blueprint_data = $this->fetchFormBlueprintData();
        $contextual_vars_desc = array();
        $cvar_responses = array();
        if (isset($cv_list) && is_array($cv_list)) {
            foreach ($cv_list as $cv) {
                $contextual_variables_ids[] = $cv['objective_id'];
                $contextual_vars_desc[$cv['objective_id']] = array(
                    "objective_id" => $cv['objective_id'],
                    "objective_code" => $cv['objective_code'],
                    "objective_name" => $cv['objective_name']
                );

                $cvar_responses[$cv['objective_id']] = $this->fetchContextualVariableResponses($cv['objective_id'], $blueprint_data["form_blueprint"]["course_id"]);
            }
        }

        $epas_desc = array();
        foreach ($selected_epas as $epa) {
            $arr[$epa] = $contextual_variables_ids;
        }

        if (isset($epas_list) && is_array($epas_list)) {
            foreach ($epas_list as $tmp_epa) {
                if (in_array($tmp_epa["objective_id"], $selected_epas)) {
                    $epas_desc[$tmp_epa["objective_id"]] = array(
                        "objective_id" => $tmp_epa["objective_id"],
                        "objective_code" => $tmp_epa["objective_code"],
                        "objective_name" => $tmp_epa["objective_name"]
                    );
                }
            }
        }

        /**
         * This code find each distinct contextual variables set, and arrange the EPAs/Variables in each set,
         * so that the javascript can render one table row per set.
         */
        if (isset($arr) && is_array($arr) && count($arr)) {
            $vars_sets = array();
            foreach ($arr as $epa => $cvars) {
                $vars_sets[implode(",", $cvars)][] = $epa;
            }
        }

        $data = array();
        if (isset($vars_sets) && is_array($vars_sets)) {
            foreach ($vars_sets as $index => $epas) {
                $data[] = array(
                    "epas" => $epas,
                    "vars" => $arr[$epas[0]],
                    "responses" => $cvar_responses
                );
            }
        }
        return $data;
    }

    /**
     * This method returns an array of type array[var_id] = var_desc
     * This is use as helper for javascript in the api
     *
     * @return array
     */
    public function fetchContextualVarsDescriptionArray($course_id = null) {
        $cv_list = $this->fetchContextualVariables();
        $blueprint_data = $this->fetchFormBlueprintData();
        $contextual_vars_desc = array();

        if (!$course_id && is_array($blueprint_data) && isset($blueprint_data["form_blueprint"]) ) {
            $course_id = $blueprint_data["form_blueprint"]["course_id"];
        }

        foreach ($cv_list as $cv) {
            $contextual_vars_desc[$cv['objective_id']] = array(
                "objective_id" => $cv['objective_id'],
                "objective_code" => $cv['objective_code'],
                "objective_name" => $cv['objective_name'],
                "responses_count" => $this->fetchContextualVariableResponseCount($cv['objective_id'], $course_id)
            );
        }
        return $contextual_vars_desc;
    }

    /**
     * This method returns an array of type array[epa_id] = epa_desc
     * This is use as helper for javascript in the api
     *
     * @param $course_id
     * @return array
     */
    public function fetchEPADescriptionsArray($course_id) {
        $epas_list = $this->fetchMappedEPAs($course_id);
        $epas_desc = array();
        if (isset($epas_list) && is_array($epas_list)) {
            foreach ($epas_list as $tmp_epa) {
                $epas_desc[$tmp_epa["objective_id"]] = array(
                    "objective_id" => $tmp_epa["objective_id"],
                    "objective_code" => $tmp_epa["objective_code"],
                    "objective_name" => $tmp_epa["objective_name"],
                    "milestones_count" => count($this->fetchMappedMilestonesForEPA($course_id, $tmp_epa["objective_id"]))
                );
            }
        }
        return $epas_desc;
    }

    /**
     * This method returns an array of epa tree nodes for a course, that are linked to forms
     *
     * @param int $course_id
     * @return array
     */
    public function fetchEPANodesTaggedToForms($course_id = null) {
        $course_epa_objectives = array();
        $tagged_form_objectives = array();
        $tagged_epas = array();
        $objective_ids = array();

        $form_objectives_model = new Models_Assessments_Form_Objective();

        /**
         * Initialize an objective tree object
         */
        $tree_object = new Entrada_CBME_ObjectiveTree($this->buildActorArray(array("course_id" => $course_id)));

        /**
         * Fetch this course's EPAs and populate a flat array
         */
        $course_epas = $tree_object->fetchTreeNodesByObjectiveSetShortname("epa");
        if ($course_epas) {
            foreach ($course_epas as $course_epa) {
                $course_epa_objectives[] = $course_epa["objective_id"];
            }
        }

        /**
         * Use the $course_epa_objectives array to fetch any tagged forms
         */
        $objectives_tagged = $form_objectives_model->fetchAllByObjectiveListCourseIDOrganisationID($course_epa_objectives, $course_id, $this->actor_organisation_id, $this->actor_proxy_id, false);
        if ($objectives_tagged) {

            /**
             * Populate a flat array of epa objectives that are tagged to forms
             */
            foreach ($objectives_tagged as $objective_tagged) {
                $tagged_form_objectives[] = $objective_tagged["objective_id"];
            }

            /**
             * Iterate the course epas again, if any of them are in $tagged_form_objectives then store the EPA data in $tagged_epas
             */
            foreach ($course_epas as $course_epa) {
                if (in_array($course_epa["objective_id"], $tagged_form_objectives)) {
                    $tagged_epas[] = $course_epa;
                    $objective_ids[] = $course_epa["objective_id"];
                }
            }
        }

        array_multisort($tagged_epas, SORT_NUMERIC, $objective_ids);

        return $tagged_epas;
    }

    /**
     * This method returns an array of forms that are tagged to any objectives within a branch of a course's tree
     *
     * @param int $node
     * @param int $course_id
     * @param int $subject_id
     * @return array
     */
    public function fetchFormsTaggedToTreeBranch($node = null, $course_id = null, $subject_id = null) {
        $objectives = array();
        $form_objectives_model = new Models_Assessments_Form_Objective();

        /**
         * Initialize an objective tree object
         */
        $tree_object = new Entrada_CBME_ObjectiveTree($this->buildActorArray(array("course_id" => $course_id)));

        /**
         * Fetch an entire branch by the supplied node and populate a flat array of their objective ids
         */
        $branch_nodes = $tree_object->fetchBranch($node);
        if ($branch_nodes) {
            foreach ($branch_nodes as $branch_node) {
                $objectives[] = $branch_node["objective_id"];
            }
        }

        /**
         * Use the $objectives array to fetch any tagged forms
         */
        $forms_tagged = $form_objectives_model->fetchAllByObjectiveListCourseIDOrganisationID($objectives, $course_id, $this->actor_organisation_id, ($subject_id ? $subject_id : $this->actor_proxy_id), false, true);
        return $forms_tagged;
    }

    /**
     * For the given array of objectives, apply filtering based on form type. Filtering rules are applied by the worker, and determine based on the
     * particular form type's filtering implementation. If the worker does not have a defined filter method, the base class's version is invoked, which
     * simply returns the list of unfiltered objectives.
     *
     * @param array $unfiltered_objectives
     * @param int $course_id
     * @param int $form_type_id
     * @return array|bool
     */
    public function filterBlueprintObjectivesByFormType($unfiltered_objectives, $course_id, $form_type_id) {
        global $translate;
        if (!is_array($unfiltered_objectives) || empty($unfiltered_objectives)) {
            return array();
        }
        $blueprint_object = $this->buildWorkerObject("form_blueprint", null);
        $filtered_objectives = $blueprint_object->fetchFilteredObjectives($unfiltered_objectives, $form_type_id, array("course_id" => $course_id));
        if (empty($filtered_objectives)) {
            $this->addErrorMessage($blueprint_object->getErrorMessages());
            return false;
        }
        return $filtered_objectives;
    }

    //-- Other Public Logic --//

    /**
     * Clear the internal storage mechanism, invalidating any previous internal caches.
     */
    public function clearInternalStorage() {
        $this->clearStorage(true, true);
    }

    /**
     * Fetch all forms tagged to the provided objectives
     * @param array $objectives
     * @param int $course_id
     */
    public function FetchFormsTaggedToObjectives($objectives = array(), $course_id = 0) {
        $form_objectives_model = new Models_Assessments_Form_Objective();
        $forms_tagged = $form_objectives_model->fetchAllByObjectiveListCourseIDOrganisationID($objectives, $course_id, $this->actor_organisation_id, $this->actor_proxy_id, false, true);
        return $forms_tagged;
    }

    /**
     * Build item options array for rendering an item from outside the context of a form or assessment.
     * This is a read-only version that does not support progress responses.
     *
     * This only builds the options array. The view mode is still specified via the View's constructor.
     *
     * @param array $item_data
     * @param bool $disabled
     * @param string $referrer_hash
     * @param bool $render_as_disabled
     * @return array
     */
    public static function buildItemViewOptionsForRender($item_data, $disabled = false, $referrer_hash = null, $render_as_disabled = false) {
        if (!is_array($item_data) || empty($item_data)) {
            // Need item data to be able to render it.
            return array();
        }

        // Check responses for items other than free text.
        if ($item_data["meta"]["itemtype_shortname"] != "free_text" && empty($item_data["responses"])) {
            return array();
        }

        // Add the extra array fields required by the view to the responses array (flatten the item record for the view's consumption)
        $itemtype = Models_Assessments_Itemtype::fetchRowByShortname($item_data["meta"]["itemtype_shortname"]);
        $item_data["item"]["item_type_name"] = $itemtype ? $itemtype->getName() : "";
        $item_data["item"]["render_comment_container"] = false;
        $item_data["item"]["comment_container_visible"] = false;
        $item_data["item"]["comment_related_afelement_id"] = null;
        $item_data["item"]["shortname"] = $item_data["meta"]["itemtype_shortname"];

        foreach ($item_data["responses"] as &$response) {
            $response["response_descriptor_text"] = isset($item_data["descriptors"][$response["ardescriptor_id"]]["descriptor"])
                ? $item_data["descriptors"][$response["ardescriptor_id"]]["descriptor"]
                : "";
            $response["response_order"] = $response["order"];
            $response["is_selected"] = false;

            // For situations where response text is not set, but it is expected, but a response descriptor IS set. Then, we use the descriptor as the text.
            if (!$response["text"]
                && $response["response_descriptor_text"]
                && !Entrada_Assessments_Workers_Item::usesDescriptorInsteadOfResponseText($item_data["meta"]["itemtype_shortname"])
            ) {
                $response["text"] = $response["response_descriptor_text"];
            }
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
            "referrer_hash" => $referrer_hash,
            "draw_overlay" => $render_as_disabled
        );
    }

    /**
     * Fetch all of the courses for a given user. Admins can access everything.
     *
     * @param bool $is_administrator
     * @return array|bool
     */
    public function fetchUserCourseList($is_administrator = false) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        if ($is_administrator) {
            // Fetch all courses for admin
            $course_list = Models_Course::fetchAllByOrgSortByName($this->actor_organisation_id);
        } else {
            $course_list = Models_Course::getActiveUserCoursesByProxyIDOrganisationID($this->actor_proxy_id, $this->actor_organisation_id);
        }

        if (!is_array($course_list)) {
            $this->addErrorMessage($translate->_("No courses found"));
            return false;
        }

        return $course_list;
    }

    /**
     * Fetch form specific contextual variables
     * @param int $form_id
     * @param int $objective_id
     * @return array
     */
    public function fetchFormContextualVariablesByObjectiveID($form_id = 0, $objective_id = 0) {
        global $db;
        $results = array();
        $query = "  SELECT glo.`objective_id`, glo.`objective_code`, glo.`objective_name`, glo.`objective_description`  FROM `cbl_assessments_lu_forms` AS alf
                    JOIN `cbl_assessment_form_objectives` AS afo
                    ON alf.`form_id` = afo.`form_id`
                    JOIN `cbl_assessments_lu_form_blueprints` AS alfb
                    ON alf.`originating_id` = alfb.`form_blueprint_id`
                    JOIN `cbl_assessments_form_blueprint_elements` AS afbe
                    ON alfb.`form_blueprint_id` = afbe.`form_blueprint_id`
                    JOIN `cbl_assessments_form_blueprint_objectives` AS alfbo
                    ON afbe.`afblueprint_element_id` = alfbo.`afblueprint_element_id`
                    JOIN `global_lu_objectives` AS glo
                    ON alfbo.`objective_id` = glo.`objective_id`
                    JOIN `global_lu_objective_sets` AS glos
                    ON glo.`objective_set_id` = glos.`objective_set_id`
                    JOIN `cbl_assessments_form_blueprint_objectives` AS alfboe
                    ON alfbo.`associated_objective_id` = alfboe.`afblueprint_objective_id`
                    JOIN `global_lu_objectives` AS gloe
                    ON alfboe.`objective_id` = gloe.`objective_id`
                    JOIN global_lu_objective_sets AS glose
                    ON gloe.`objective_set_id` = glose.`objective_set_id`
                    WHERE alf.`form_id` = ?
                    AND alf.`origin_type` = 'blueprint'
                    AND afo.`objective_id` = ?
                    AND glos.`shortname` = 'contextual_variable'
                    AND glose.`shortname` = 'epa'
                    AND gloe.`objective_id` = ?
                    AND alf.`deleted_date` IS NULL
                    AND afo.`deleted_date` IS NULL
                    AND alfb.`deleted_date` IS NULL
                    AND alfbo.`deleted_date` IS NULL
                    AND afbe.`deleted_date` IS NULL
                    AND glo.`objective_active` = 1
                    AND glos.`deleted_date` IS NULL
                    AND alfboe.`deleted_date` IS NULL
                    AND gloe.`objective_active` = 1
                    AND glose.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($form_id, $objective_id, $objective_id));
        return $results;
    }

    //-- Private Methods --//

    /**
     * Build a worker object of the given type.
     *
     * @param $object_type
     * @param $id
     * @param array $construction_options
     * @return bool|mixed
     */
    private function buildWorkerObject($object_type, $id, $construction_options = array()) {
        global $translate;
        $options = array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "disable_internal_storage" => $this->disable_internal_storage
        );
        if (isset($this->dataset_limits[$object_type])) {
            $options = array_merge($options, $this->dataset_limits[$object_type]);
        }
        $options = array_merge($options, $construction_options);
        switch ($object_type) {
            case "form":
                $options["form_id"] = $id;
                return new Entrada_Assessments_Workers_Form($options);
            case "cbme_form":
                $options["form_id"] = $id;
                return new Entrada_Assessments_Workers_CBMEForm($options);
            case "rubric":
                $options["rubric_id"] = $id;
                return new Entrada_Assessments_Workers_Rubric($options);
            case "item":
                $options["item_id"] = $id;
                return new Entrada_Assessments_Workers_Item($options);
            case "form_blueprint":
                $options["form_blueprint_id"] = $id;
                return new Entrada_Assessments_Workers_Blueprint($options);
            case "rating_scale":
                $options["rating_scale_id"] = $id;
                return new Entrada_Assessments_Workers_Scale($options);
        }
        $this->addErrorMessage(sprintf($translate->_("Unable to fetch \"%s\" object."), str_replace("_", " ", $object_type)));
        return false;
    }

    /**
     * Set the dataset limits and behaviour of the worker.
     * Resets the internal worker object and clears the relevant cache.
     *
     * @param string $worker_type
     * @param array $dataset_limits
     * @param bool $determine_meta
     */
    private function setWorkerDatasetLimit($worker_type, $dataset_limits, $determine_meta) {
        $this->dataset_limits[$worker_type]["limit_dataset"] = $dataset_limits;
        $this->dataset_limits[$worker_type]["determine_meta"] = $determine_meta;

        $id = $this->{$worker_type}->getID();
        $new_worker = $this->buildWorkerObject($worker_type, $id);
        $new_worker->setDatasetLimit($dataset_limits);
        $new_worker->setDetermineMeta($determine_meta);

        $this->removeFromStorage("{$worker_type}_dataset", $id);
        $this->{$worker_type} = $new_worker;
        $this->setWorkerStale($worker_type);
    }

    /**
     * Build the internal form object property if required.
     *
     * @param $form_id
     * @param array $more_options
     * @return bool
     */
    private function buildFormObject($form_id, $more_options = array()) {
        if (!$this->form->isStale()
            && $form_id == $this->form_id
            && $this->form->getID() == $form_id
        ) {
            return true; // We already have this object
        }
        // Form ID is null (or 0) and/or different from our internal form_id.
        // If form_id is null, we're creating a new one, regardless of whether we have one already or not.
        if ($new_worker = $this->buildWorkerObject("form", $form_id, $more_options)) {
            $this->form_id = $form_id; // can be null
            $this->form = $new_worker;
            return true;
        }
        // Failed to build a worker. Error is returned in buildWorkerObject method.
        return false;
    }

    /**
     * Build the internal form object property if required.
     *
     * @param $cbme_form_id
     * @param array $more_options
     * @return bool
     */
    private function buildCBMEFormObject($cbme_form_id, $more_options = array()) {
        if (!$this->cbme_form->isStale()
            && $cbme_form_id == $this->cbme_form_id
            && $this->cbme_form->getID() == $cbme_form_id
        ) {
            return true; // We already have this object
        }
        // Form ID is null (or 0) and/or different from our internal form_id.
        // If form_id is null, we're creating a new one, regardless of whether we have one already or not.
        if ($new_worker = $this->buildWorkerObject("cbme_form", $cbme_form_id, $more_options)) {
            $this->cbme_form_id = $cbme_form_id; // can be null
            $this->cbme_form = $new_worker;
            return true;
        }
        // Failed to build a worker. Error is returned in buildWorkerObject method.
        return false;
    }

    /**
     * Build the internal item object property if required.
     *
     * @param $item_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function buildItemObject($item_id, $more_options = array()) {
        if (!$this->item->isStale()
            && $item_id == $this->item_id
            && $this->item->getID() == $item_id
        ) {
            return true;
        }
        if ($new_worker = $this->buildWorkerObject("item", $item_id, $more_options)) {
            $this->item_id = $item_id; // can be null
            $this->item = $new_worker;
            return true;
        }
        return false;
    }

    /**
     * Build internal rubric object.
     *
     * @param $rubric_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function buildRubricObject($rubric_id, $more_options = array()) {
        if (!$this->rubric->isStale()
            && $rubric_id == $this->rubric_id
            && $this->rubric->getID() == $rubric_id
        ) {
            return true;
        }
        if ($new_worker = $this->buildWorkerObject("rubric", $rubric_id, $more_options)) {
            $this->rubric_id = $rubric_id; // can be null
            $this->rubric = $new_worker;
            return true;
        }
        return false;
    }

    /**
     * Build internal blueprint object.
     *
     * @param $form_blueprint_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function buildFormBlueprintObject($form_blueprint_id, $more_options = array()) {
        if (!$this->form_blueprint->isStale()
            && $form_blueprint_id == $this->form_blueprint_id
            && $this->form_blueprint->getID() == $form_blueprint_id
        ) {
            return true;
        }
        if ($new_worker = $this->buildWorkerObject("form_blueprint", $form_blueprint_id, $more_options)) {
            $this->form_blueprint_id = $form_blueprint_id; // can be null
            $this->form_blueprint = $new_worker;
            return true;
        }
        return false;
    }

    /**
     * Build internal scale object.
     *
     * @param $rating_scale_id
     * @param array $more_options
     * @return bool|mixed
     */
    private function buildScaleObject($rating_scale_id, $more_options = array()) {
        if (!$this->rating_scale->isStale()
            && $rating_scale_id == $this->rating_scale_id
            && $this->rating_scale->getID() == $rating_scale_id
        ) {
            return true;
        }
        if ($new_worker = $this->buildWorkerObject("rating_scale", $rating_scale_id, $more_options)) {
            $this->rating_scale_id = $rating_scale_id; // can be null
            $this->rating_scale = $new_worker;
            return true;
        }
        return false;
    }

    /**
     * Fetches all of the forms that have milestones tagged to them
     * @param $course_id
     * @param $proxy_id
     * @param $organisation_id
     * @return array
     */
    public function fetchFormsTaggedToMilestones($course_id, $proxy_id, $organisation_id) {
        global $db;

        $results = array();

        $query = "  SELECT f.*, go.`objective_code` FROM `cbl_distribution_assessments` AS a
                    JOIN `cbl_assessment_lu_types` AS b
                    ON a.`assessment_type_id` = b.`assessment_type_id`
                    JOIN `cbl_assessment_type_organisations` AS c
                    ON b.`assessment_type_id` = c.`assessment_type_id`
                    JOIN `cbl_distribution_assessment_targets` AS d
                    ON a.`dassessment_id` = d.`dassessment_id`
                    LEFT JOIN `cbl_assessment_progress` AS e
                    ON a.`dassessment_id` = e.`dassessment_id`
                    JOIN `cbl_assessments_lu_forms` AS f
                    ON a.`form_id` = f.`form_id`
                    JOIN `cbl_assessments_lu_form_types` AS g
                    ON f.`form_type_id` = g.`form_type_id`
                    JOIN `cbl_assessment_form_elements` as fe
					ON f.`form_id` = fe.`form_id`
					JOIN `cbl_assessments_lu_items` as i
					ON i.`item_id` = fe.`element_id`
					JOIN `cbl_assessment_item_objectives` as io
					ON io.`item_id` = i.`item_id`
					JOIN `global_lu_objectives` as go
					ON go.`objective_id` = io.`objective_id`
					JOIN `global_lu_objective_sets` as obs
					ON obs.`objective_set_id` = go.`objective_set_id`
                    WHERE a.`course_id` = ?
                    AND c.`organisation_id` = ?
                    AND a.`assessor_type` = 'internal'
                    AND d.`target_type` = 'proxy_id'
                    AND d.`target_value` = ?
                    AND obs.`shortname` = 'milestone'
                    AND f.`form_type_id` NOT IN (
                        SELECT `form_type_id`
                        FROM `cbl_assessment_form_type_meta`
                        WHERE `organisation_id` = ?
                        AND `meta_name` = 'hide_from_dashboard'
                        AND `meta_value` = 1
                        AND `deleted_date` IS NULL
                    )
                    AND NOT (a.`assessor_type` = 'internal' AND a.`assessor_value` = d.`target_value`)
                        AND e.`target_type` = 'proxy_id'
                        AND e.`target_record_id` = ?
                        AND d.`deleted_reason_id` IS NULL
                        AND e.`progress_value` = 'complete'
                        GROUP BY a.`form_id`";

        $results = $db->GetAll($query, array($course_id, $organisation_id, $proxy_id, $organisation_id, $proxy_id));

        return $results;
    }

}