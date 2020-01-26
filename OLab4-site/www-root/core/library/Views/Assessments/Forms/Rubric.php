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
 * View class for rendering a rubric.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

/*
 * Rubric states:
 *
 * Public Assessment
 *  No header controls
 *  Title in header
 *  No descriptor edit controls
 *  No subheader controls
 *  Elements enabled
 *  Comments automatic via flagging
 *  **Populated with selections via progress records (if available)
 *  Label Edit NOT Visible
 *
 * Form Edit (Part of a form that IS in use)
 *  Read only/disabled header controls (checkbox and edit pencil)
 *  Title in header
 *  No descriptor edit controls
 *  No subheader controls
 *  Elements Disabled
 *  Comments box shown, no comments box if flagged only or disabled
 *  Label Edit NOT Visible
 *
 * Form Edit (Part of a form that is NOT in use)
 *  Enabled header controls (checkbox, pencil, movement arrows)
 *  Title in header
 *  No response descriptor edit controls
 *  No subheader controls
 *  Elements disabled
 *  Comments box shown, no comments box if flagged only or disabled
 *  Label Edit NOT Visible
 *
 * Rubric Edit (Part of a form that IS in use)
 *  No Header controls
 *  No title in header
 *  No descriptor edit controls
 *  Subheader controls read-only (disabled pencil)
 *  Elements Disabled
 *  Comments box shown, no comments box if flagged only or disabled
 *  Label Edit NOT Visible
 *
 * Rubric Edit (Part of a form that IS NOT use)
 *  No Header controls
 *  No title in header
 *  Descriptor edit controls VISIBLE
 *  Subheader controls clickable (edit pencil, trash can, move arrow)
 *  Elements Disabled
 *  Comments box shown, no comments box if flagged only or disabled
 *  Label Edit IS Visible
 *
 */

class Views_Assessments_Forms_Rubric extends Views_Assessments_Forms_Base {

    protected $rubric_state = null;
    protected $rubric_id = null;
    protected $referrer = null;
    private $visibility_flags = array();

    /**
     * Views_Assessments_Forms_Rubrics_Rubric constructor.
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->configureVisibility();
    }

    private function configureVisibility() {
        $this->setDefaultVisibility();
        if (!$this->rubric_state) {
            $this->setRubricStateByMode();
        }
        $this->setVisibilityByRubricState();
    }

    private function setRubricStateByMode() {
        if ($this->validateMode()) {
            switch ($this->mode) {
                case "assessment":
                case "assessment-blank":
                case "pdf":
                case "pdf-blank":
                    $this->rubric_state = "assessment";
                    break;
                case "editor":
                case "editor-readonly":
                    $this->rubric_state = "form-edit-clean";
                    break;
            }
        }
    }

    /**
     * Set the visibility flags based on the rubric state.
     *
     * Public Assessment
     *  No header controls
     *  Title in header
     *  No descriptor edit controls
     *  No subheader controls
     *  Elements enabled
     *  Comments automatic via flagging
     *  **Populated with selections via progress records (if available)
     *  Label Edit NOT Visible
     *
     * Form Edit (Part of a form that IS in use)
     *  Read only/disabled header controls (checkbox and edit pencil)
     *  Title in header
     *  No descriptor edit controls
     *  No subheader controls
     *  Elements Disabled
     *  Comments box shown, no comments box if flagged only or disabled
     *  Label Edit NOT Visible
     *
     * Form Edit (Part of a form that is NOT in use)
     *  Enabled header controls (checkbox, pencil, movement arrows)
     *  Title in header
     *  No response descriptor edit controls
     *  No subheader controls
     *  Elements disabled
     *  Comments box shown, no comments box if flagged only or disabled
     *  Label Edit NOT Visible
     *
     * Rubric Edit (Part of a form that IS in use)
     *  No Header controls
     *  No title in header
     *  No descriptor edit controls
     *  Subheader controls read-only (disabled pencil)
     *  Elements Disabled
     *  Comments box shown, no comments box if flagged only or disabled
     *  Label Edit NOT Visible
     *
     * Rubric Edit (Part of a form that IS NOT use)
     *  No Header controls
     *  No title in header
     *  Descriptor edit controls VISIBLE
     *  Subheader controls clickable (edit pencil, trash can, move arrow)
     *  Elements Disabled
     *  Comments box shown, no comments box if flagged only or disabled
     *  Label Edit IS Visible
     *
     */
    private function setVisibilityByRubricState() {
        switch ($this->rubric_state) {
            case "assessment":
                // Rubric accessed from an incomplete assessment: display in submission mode (only headers), allow clickable
                break;
            case "assessment-complete":
                // Rubric accessed from a completed assessment: all inputs and headers disabled, other than title header, display disabled
                $this->visibility_flags["disable_inputs"] = true;
                break;
            case "form-edit-clean":
                // Rubric accessed from form editor: rubric can be modified on the form; allow header edit controls.
                $this->visibility_flags["show_header_control_bar"] = true;
                $this->visibility_flags["show_header_arrows"] = true;
                $this->visibility_flags["show_header_pencil"] = true;
                $this->visibility_flags["show_header_checkbox"] = true;
                $this->visibility_flags["show_response_flag_icon"] = true;
                $this->visibility_flags["disable_inputs"] = true;
                $this->visibility_flags["disable_header_controls"] = false;
                break;
            case "form-edit-inuse":
                // Rubric accessed from form editor: rubric is in use and locked.
                $this->visibility_flags["show_header_control_bar"] = true;
                $this->visibility_flags["show_header_pencil"] = true;
                $this->visibility_flags["show_header_checkbox"] = true;
                $this->visibility_flags["show_response_flag_icon"] = true;
                $this->visibility_flags["disable_inputs"] = true;
                break;
            case "rubric-edit-modify":
                // Rubric accessed by Rubric editor: rubric can be modified, but is in use, so descriptors and labels are locked
                $this->visibility_flags["show_header_title"] = false;
                $this->visibility_flags["show_header_control_bar"] = false;
                $this->visibility_flags["show_header_description"] = false;
                $this->visibility_flags["show_subheader_control_bars"] = true;
                $this->visibility_flags["show_subheader_pencil"] = true;
                $this->visibility_flags["show_subheader_trash"] = true;
                $this->visibility_flags["show_subheader_arrows"] = true;
                $this->visibility_flags["show_descriptor_edit_controls"] = false;
                $this->visibility_flags["show_response_flag_icon"] = true;
                $this->visibility_flags["disable_inputs"] = true;
                $this->visibility_flags["disable_subheader_controls"] = false;
                $this->visibility_flags["disable_header_controls"] = true;
                $this->visibility_flags["disable_descriptor_edit_controls"] = true;
                $this->visibility_flags["disable_subheader_pencil"] = false;
                break;
            case "rubric-edit-clean":
                // Rubric accessed by Rubric editor: Rubric is fully editable; even allow descriptor and label editing
                $this->visibility_flags["show_header_title"] = false;
                $this->visibility_flags["show_header_control_bar"] = false;
                $this->visibility_flags["show_header_description"] = false;
                $this->visibility_flags["show_subheader_control_bars"] = true;
                $this->visibility_flags["show_subheader_pencil"] = true;
                $this->visibility_flags["show_subheader_trash"] = true;
                $this->visibility_flags["show_subheader_arrows"] = true;
                $this->visibility_flags["show_descriptor_edit_controls"] = true;
                $this->visibility_flags["show_response_flag_icon"] = true;
                $this->visibility_flags["disable_inputs"] = true;
                $this->visibility_flags["disable_subheader_controls"] = false;
                $this->visibility_flags["disable_header_controls"] = true;
                $this->visibility_flags["disable_descriptor_edit_controls"] = false;
                $this->visibility_flags["disable_subheader_pencil"] = false;
                break;
            case "rubric-edit-inuse":
                // Rubric accessed by Rubric editor: Rubric is locked. No editing.
                $this->visibility_flags["show_header_title"] = false;
                $this->visibility_flags["show_header_control_bar"] = false;
                $this->visibility_flags["show_header_description"] = false;
                $this->visibility_flags["show_subheader_control_bars"] = true;
                $this->visibility_flags["show_subheader_pencil"] = true;
                $this->visibility_flags["show_response_flag_icon"] = true;
                $this->visibility_flags["disable_inputs"] = true;
                $this->visibility_flags["disable_pencil_input"] = true;
                $this->visibility_flags["disable_subheader_controls"] = true;
                $this->visibility_flags["disable_header_controls"] = true;
                break;
            default:
                break;
        }
    }

    private function setDefaultVisibility() {
        $this->visibility_flags = array(
            "show_header_title" => true,
            "show_header_control_bar" => false,
            "show_header_arrows" => false,
            "show_header_pencil" => false,
            "show_header_trash" => false,
            "show_header_checkbox" => false,
            "show_header_description" => true,
            "show_header_deleted_rubric_notice" => true,
            "show_subheader_control_bars" => false,
            "show_subheader_arrows" => false,
            "show_subheader_pencil" => false,
            "show_subheader_trash" => false,
            "show_subheader_checkbox" => false,
            "show_subheader_deleted_item_notice" => true,
            "show_descriptor_edit_controls" => false,
            "show_label_edit_controls" => false,
            "show_response_flag_icon" => false,
            "disable_inputs" => false,
            "disable_header_controls" => true,
            "disable_subheader_controls" => true,
            "disable_descriptor_edit_controls" => true,
            "disable_label_edit_controls" => true,
            "disable_subheader_pencil" => true,
        );
    }

    protected function validateOptions($options = array()) {
        if (isset($options["disabled"]) && $options["disabled"]) {
            $this->visibility_flags["disable_inputs"] = true;
        }
        if (!$this->validateIsSet($options, array("rubric_id"))) {
            return false;
        }
        if (!$this->validateArrayNotEmpty($options, array("rubric_data"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $is_disabled                = $this->visibility_flags["disable_inputs"];
        $rubric_id                  = $options["rubric_id"];
        $rubric_data                = $options["rubric_data"];
        $width                      = $rubric_data["meta"]["width"] + 1;
        $rubric_is_deleted          = isset($rubric_data["rubric"]["deleted_date"]) ? $rubric_data["rubric"]["deleted_date"] : null;
        $render_disabled_overlay    = array_key_exists("draw_overlay", $options) ? $options["draw_overlay"] : null;
        $referrer_hash              = array_key_exists("referrer_hash", $options) ? $options["referrer_hash"] : null;
        $element_id                 = array_key_exists("afelement_id", $options) ? $options["afelement_id"] : null;
        $all_response_descriptors   = array_key_exists("all_descriptors", $options) ? $options["all_descriptors"] : array();
        $rubric_edit_url            = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$rubric_id}", $referrer_hash);
        $has_defaults               = Entrada_Assessments_Forms::itemLinesHaveDefaults($rubric_data["lines"]);
        $specified_mutators         = Entrada_Utilities::arrayValueOrDefault($options, "mutators", array()); // Mutators we apply to relevant items

        /**
         * Supported optional rubric attributes:
         */
        $hidden_columns     = array(); // Which columns to hide (positions zero indexed, e.g. 2 = hide the third column)
        $collapsible        = false;   // Whether a rubric can be collapsed via click
        $collapsed          = false;   // Render as collapsed or not (use the appropiate hard or soft overrides)
        $collapsed_hard     = null;    // A hard override to force the rubric to render as collapsed or not. If !== null, use this value.
        $collapsed_soft     = false;   // A soft override to collapse the rubric only if there's no progress, or non-default progress present. Superceded by collapsed_hard.
        $reorderable_in_form = false;   // Whether items in the rubric can be reordered or not.

        /**
         * Decode the rubric attributes (if any), and set them
         */
        $rubric_attributes = @json_decode(
            isset($rubric_data["rubric"]["attributes"])
                ? $rubric_data["rubric"]["attributes"]
                : "[]",
            true
        );
        if ($rubric_attributes
            && is_array($rubric_attributes)
            && !empty($rubric_attributes)
        ) {
            // If hidden columns are specified, adjust our width
            $hidden_columns = array_key_exists("hidden_columns", $rubric_attributes) ? $rubric_attributes["hidden_columns"] : array();
            if (count($hidden_columns)) {
                // Adjust the width to reflect the hidden columns
                $width -= count($hidden_columns);
            }
            // Collapse semantics
            $collapsible = array_key_exists("collapsible", $rubric_attributes) ? $rubric_attributes["collapsible"] : false;
            $collapsed_hard = array_key_exists("collapsed", $rubric_attributes) ? $rubric_attributes["collapsed"] : null; // If forcibly specified, collapse the rubric
            if ($this->mode == "assessment" && $collapsible) {
                // Soft collapse is set when there's progress for this rubric, and the selected items are not the default items
                $collapsed_soft = Entrada_Assessments_Forms::isNonDefaultRubricResponseSelected($rubric_data["lines"]) ? false : true;
                if ($collapsed_soft && !$has_defaults) {
                    // We only collapse if there are defaults present. If not, then don't!
                    $collapsed_soft = false;
                }
            } else if ($this->mode == "assessment-complete" && $collapsible) {
                // Completed assessments can be collapsible, but are open by default, regarldess of setting.
                $collapsed_hard = false;
            }
            $collapsed = ($collapsed_hard !== null) ? $collapsed_hard : $collapsed_soft;

            // Check if the form is actually capable of being reordered, and allow it only if appropriate.
            if (array_key_exists("reorderable_in_form", $rubric_attributes)
                && $rubric_attributes["reorderable_in_form"]
                && ($this->rubric_state == "form-edit-clean" || $this->rubric_state == "form-edit-inuse")
            ) {
                $this->visibility_flags["show_subheader_control_bars"] = true;
                $this->visibility_flags["show_subheader_arrows"] = true;
                $reorderable_in_form = true;
            }

            if (array_key_exists("disable_edit", $rubric_attributes) && $rubric_attributes["disable_edit"]) {
                $this->visibility_flags["show_header_pencil"] = false;
            }
        }

        /**
         * Apply item-scope mutators:
         * Item-scope mutators can be applied on a per-item basis, as the options are passed to each view independently.
         */
        $ignore_render = array();
        if (!empty($rubric_data["lines"])) {
            foreach ($rubric_data["lines"] as $rubric_line_id => $rubric_line) {
                $mutator_list = Entrada_Assessments_Forms::buildItemMutatorList($rubric_line["item"]);

                /**
                 * Checking for the invisiblity mutator, which would hide this item from the form.
                 */
                if (Entrada_Utilities::inBothArrays("invisible", $specified_mutators, $mutator_list)) {
                    // Don't render invisible items (if the mutator is present)
                    $ignore_render[] = $rubric_line_id;
                }
            }
            if (count($rubric_data["lines"]) == count($ignore_render)) {
                // We're ignoring all items in this rubric, so we won't render it.
                return;
            }
        }

        if ($element_id): // If this rubric is being rendered on a form, then wrap it in a form-item tag. ?>
        <div class="form-item" data-afelement-id="<?php echo $element_id ?>">
        <?php endif; ?>
            <div class="rubric-error-msg"></div>
            <div data-item-id="<?php echo $rubric_id; ?>" class="assessment-horizontal-choice-rubric rubric-container">
                <?php if ($render_disabled_overlay): ?>
                    <div class="assessment-item-disabled-overlay"></div>
                <?php endif; ?>
                <div class="table-responsive table-overflow">
                    <table class="table table-bordered table-striped rubric-table ui-sortable">
                        <thead>
                        <?php if ($this->visibility_flags["show_header_control_bar"]): ?>
                            <tr class="rubric-controls">
                                <th colspan="<?php echo $width?>">
                                    <div class="pull-right btn-group">
                                        <div class="btn-group">
                                            <?php if ($this->visibility_flags["show_header_deleted_rubric_notice"] && $rubric_is_deleted): ?>
                                                <span class="rubric-header-warning-text">
                                                <?php echo $translate->_("This rubric has been deleted and can not be edited."); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($this->visibility_flags["show_header_checkbox"]): ?>
                                                <span class="btn">
                                                <input type="checkbox" class="delete" name="delete[]" value="<?php echo $element_id; ?>">
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group">
                                            <?php if (!$rubric_is_deleted): ?>
                                                <?php if ($this->visibility_flags["show_header_pencil"]): ?>
                                                    <a href="<?php echo $rubric_edit_url; ?>" title="<?php $translate->_("Edit Item"); ?>" class="btn edit-item"><i class="icon-pencil"></i></a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($this->visibility_flags["show_header_arrows"]): ?>
                                                <a class="btn always-enabled" id="move-form-item" title="<?php echo $translate->_("Move"); ?>"><i class="icon-move"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->visibility_flags["show_header_title"]): ?>
                            <tr class="rubric-title <?php echo ($collapsible) ? "rubric-content-collapsible clickable" : ""?>" data-rubric-id="<?php echo $rubric_id ?>">
                                <th colspan="<?php echo $width; ?>">
                                    <div class="pull-left">
                                        <h2><?php echo html_decode($rubric_data["rubric"]["rubric_title"]); ?></h2>
                                    </div>
                                    <?php if ($collapsible): ?>
                                    <div class="pull-right">
                                        <i class="<?php echo (!$collapsed) ? "hide" : "" ?> rubric-chevron-down-<?php echo $rubric_id ?>  fa fa-chevron-down" aria-hidden="true"></i>
                                        <i class="<?php echo ($collapsed) ? "hide" : "" ?>  rubric-chevron-up-<?php echo $rubric_id ?> fa fa-chevron-up" aria-hidden="true"></i>
                                    </div>
                                    <?php endif; ?>
                                </th>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->visibility_flags["show_header_description"] && $rubric_data["rubric"]["rubric_description"]): ?>
                            <tr class="rubric-description">
                                <th colspan="<?php echo $width; ?>">
                                    <div class="pull-left">
                                        <p><?php echo html_decode($rubric_data["rubric"]["rubric_description"]); ?></p>
                                    </div>
                                </th>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($rubric_data["descriptors"])): ?>

                            <tr class="rubric-descriptors rubric-content-collapsible-<?php echo $rubric_id ?> <?php echo ($collapsed) ? "hide" : ""?>">
                                <?php if (!in_array("0", $hidden_columns)) : ?>
                                    <th></th>
                                <?php endif; ?>
                                <?php $position = 0;
                                foreach ($rubric_data["descriptors"] as $response_descriptors):
                                    $position++;
                                    if (in_array($position, $hidden_columns)) {
                                        continue;
                                    }
                                    ?>
                                    <th class="label-cell <?php echo $this->visibility_flags["show_descriptor_edit_controls"] ? "category-editable" : ""; ?>">
                                        <h3 data-column-number="<?php echo $position ?>" <?php echo $response_descriptors["ardescriptor_id"] ? "data-ardescriptor-id='{$response_descriptors["ardescriptor_id"]}' id='response-descriptor-{$response_descriptors["ardescriptor_id"]}'" : ""; ?>>
                                            <?php echo html_decode($response_descriptors["response_descriptor_text"]); ?>
                                        </h3>

                                        <?php if ($this->visibility_flags["show_descriptor_edit_controls"]): ?>
                                            <?php if ($all_response_descriptors): ?>
                                                <select class="descriptor-select full-width pull-left" style="display: none;">
                                                    <?php foreach ($all_response_descriptors as $descriptor): ?>
                                                        <option value="<?php echo $descriptor->getID(); ?>" <?php echo ($response_descriptors["ardescriptor_id"] == $descriptor->getID()) ? "selected='selected'" : ""; ?>>
                                                            <?php echo html_decode($descriptor->getDescriptor()); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                            <a class="btn btn-mini btn-category-remove pull-left" style="display: none;"><span class="fa fa-close"></span></a>
                                            <a class="btn btn-mini btn-success btn-category-ok pull-left" style="display: none;" data-descriptor-id="<?php echo @$response_descriptors["ardescriptor_id"] ?>" data-rubric-id="<?php echo $rubric_id ?>"><span class="fa fa-check"></span></a>
                                            <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>" class="category-loading" style="display: none;">
                                            <i class="icon-pencil icon-category-pencil" style="display: none;"></i>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                        </thead>
                        <?php foreach ($rubric_data["lines"] as $rubric_line_id => $rubric_line):
                            if (in_array($rubric_line_id, $ignore_render)) {
                                // Skip this one.
                                continue;
                            }
                            $item_id = $rubric_line["item"]["item_id"];
                            $default_response = null;
                            if ($rubric_line["item"]["allow_default"] && $rubric_line["item"]["default_response"]) {
                                $default_response = $rubric_line["item"]["default_response"]; // default_response is the response order (i.e., the nth item is the default response)
                            }
                            $selected_response = $default_response; // null or the default order
                            $response_number = 0;
                            foreach ($rubric_line["responses"] as $response) {
                                $response_number++;
                                if ($response["is_selected"]) {
                                    $selected_response = $response_number;
                                }
                            }
                            $placeholder_text = sprintf("placeholder=\"%s\"", html_decode($translate->_("Please select a response before adding a comment.")));
                            if ($this->mode == "editor" || $this->mode == "editor-readonly") {
                                $placeholder_text = ""; // no placeholder when in editor mode
                            }
                            if ($selected_response) {
                                $placeholder_text = ""; // no need for a placeholder when a response is selected.
                            }
                            $rowspan = $rubric_line["item"]["comment_container_visible"] ? $rowspan = "rowspan='1'" : "rowspan='2'";
                            $edit_rubric_line_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id={$rubric_line["item"]["item_id"]}", $referrer_hash, null, null, $rubric_id);
                            ?>
                            <tbody data-aritem-id="<?php echo $rubric_line["rubric_item_record"]["aritem_id"]; ?>"
                                   data-comment-type="<?php echo $rubric_line["item"]["comment_type"]; ?>"
                                   data-rubric-id="<?php echo $rubric_id ?>"
                                   class="sortable-rubric-item ui-draggable <?php echo ($collapsed) ? "hide" : ""?> <?php echo ($collapsible) ? "rubric-content-collapsible-$rubric_id" : "" ?>">

                            <?php if ($this->visibility_flags["show_subheader_control_bars"]): ?>
                                <tr class="rubric-controls">
                                    <td colspan="<?php echo $width ?>">
                                        <div class="btn-group rubric-actions pull-right">
                                            <?php if ($this->visibility_flags["show_subheader_deleted_item_notice"] && $rubric_line["item"]["deleted_date"]): ?>
                                                <span class="btn rubric-header-warning-text">
                                                        <?php echo $translate->_("This item has been deleted and can not be edited."); ?>
                                                    </span>
                                            <?php endif; ?>

                                            <?php if ($this->visibility_flags["show_subheader_pencil"] && !$rubric_line["item"]["deleted_date"]): ?>
                                                <a class="btn edit-rubric-item <?php echo $this->visibility_flags["disable_subheader_pencil"] ? "disabled" : "" ?>" href="<?php echo $edit_rubric_line_url; ?>" <?php echo $this->visibility_flags["disable_subheader_pencil"] ? "disabled" : "" ?>><i class="icon-pencil"></i></a>
                                            <?php endif; ?>
                                            <?php if ($this->visibility_flags["show_subheader_trash"]): ?>
                                                <a class="btn delete-rubric-item" data-toggle="modal" href="#delete-rubric-item-modal"><i class="icon-trash"></i></a>
                                            <?php endif; ?>
                                            <?php if ($this->visibility_flags["show_subheader_arrows"]): ?>
                                                <a class="btn move-rubric-item <?php echo $reorderable_in_form ? "always-enabled" : ""; ?>" href="#"><i class="icon-move"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr data-aritem-id="<?php echo $rubric_line["rubric_item_record"]["aritem_id"]; ?>" class="rubric-response-input item-response-view">
                                <?php if (!in_array(0, $hidden_columns)) : ?>
                                    <td class="rubric-response" width="30%" rowspan="2">
                                        <div class="rubric-item-text"><?php echo html_decode($rubric_line["item"]["item_text"]); ?>
                                            <?php if ($this->visibility_flags["show_label_edit_controls"]): ?>
                                                <br/>
                                                <span data-item-id="<?php echo $item_id; ?>" id="" class="edit-rubric-label" title="<?php echo $translate->_("Click to add description"); ?>">
                                                    <?php if (isset($rubric_data["labels"][$item_id]["label"])): ?>
                                                        <?php echo html_decode($rubric_data["labels"][$item_id]["label"]); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <a data-item-id="<?php echo $item_id; ?>" class="edit-rubric-label-link"><i class="icon-edit"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif;

                                $response_count = 0;
                                foreach ($rubric_line["responses"] as $iresponse_id => $line_response):
                                    $weight_value = Entrada_Utilities::multidimensionalArrayValue($options, null, "rubric_data", "rating_scale_descriptors", $response_count, "weight");
                                    $dark_class = ($weight_value !== null && intval($weight_value) === 0) ? "not-observed-response" : "";
                                    $response_count++;
                                    $this_line_checked_text = ($response_count == $selected_response) ? "checked='checked'" : "";
                                    if (in_array($response_count, $hidden_columns)) {
                                        // This column is set to be hidden
                                        continue;
                                    }
                                    ?>
                                    <td class="rubric-response <?php echo (($is_disabled) && ($response_count == $selected_response) ? "selected-response" : "") ?> <?php echo $dark_class; ?>" <?php echo $rowspan; ?>>
                                        <label for="<?php echo "response-$iresponse_id"; ?>">
                                            <input type="radio"
                                                   class="item-control <?php echo ($is_disabled) ? "hide" : "" ?>"
                                                   value="<?php echo $iresponse_id; ?>"
                                                   data-iresponse-id="<?php echo $iresponse_id; ?>"
                                                   data-item-id="<?php echo $line_response["item_id"]; ?>"
                                                   id="<?php echo "response-$iresponse_id"; ?>"
                                                   name="<?php echo "rubric-item-$rubric_id-{$line_response["item_id"]}"; ?>"
                                                   <?php echo $is_disabled ? "disabled" : ""; ?>
                                                   <?php echo $line_response["flag_response"] ? "data-response-flagged='true'" : ""; ?>
                                                   <?php echo $this_line_checked_text; ?>
                                            />
                                            <?php if ($line_response["flag_response"] && $this->visibility_flags["show_response_flag_icon"]): ?>
                                                <span class="fa fa-exclamation-circle blue-icon pull-right"></span>
                                            <?php endif; ?>
                                            <div class="rubric-response-text">
                                                <?php if ($line_response["text"]): ?>
                                                    <?php echo nl2br(html_decode($line_response["text"])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <?php if ($rubric_line["item"]["render_comment_container"]): ?>
                                <tr id="<?php echo "rubric-item-{$rubric_id}-{$item_id}-comments-block"; ?>" class="item-response-view rubric-comment <?php echo $rubric_line["item"]["comment_container_visible"] ? "" : "hide"; ?>">
                                    <td colspan="<?php echo $width ?>">
                                        <?php
                                        $comment_disabled = "";
                                        if (!$selected_response) {
                                            $comment_disabled = "disabled";
                                        }
                                        if ($is_disabled) {
                                            // If is_disabled is set, we override.
                                            $comment_disabled = "disabled";
                                        }
                                        ?>
                                        <div class="comment-label"><?php echo $translate->_("Comment"); ?></div>
                                        <textarea class="span11 expandable"
                                            <?php echo $placeholder_text ?>
                                                  id="<?php echo "item-$item_id-comments"; ?>"
                                                  name="<?php echo "item-$item_id-comments"; ?>"
                                            <?php echo $comment_disabled; ?>
                                        ><?php echo html_decode($rubric_line["item"]["item_comment_text"]) ?></textarea>
                                        <div class="rubric-comment-hidden-textarea">
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>

                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php if ($element_id): ?>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render an error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render rubric"); ?></strong>
        </div>
        <?php
    }
}
?>
