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
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $rubric_id = $options["rubric_id"];
        $rubric_data = $options["rubric_data"];
        $rubric_is_deleted = @$rubric_data["rubric"]["deleted_date"];

        $referrer_hash = @$options["referrer_hash"];
        $element_id = @$options["afelement_id"];
        $width = $rubric_data["meta"]["width"] + 1; // Add one for the left-most column

        $rubric_edit_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?section=edit-rubric&rubric_id={$rubric_id}", $referrer_hash);
        $is_disabled = $this->visibility_flags["disable_inputs"];
        $all_response_descriptors = @$options["all_descriptors"] ? $options["all_descriptors"] : array();
        ?>
        <?php if ($element_id): // If this rubric is being rendered on a form, then wrap it in a form-item tag. ?>
            <div class="form-item" data-afelement-id="<?php echo $element_id ?>">
        <?php endif; ?>
        <div id="rubric-error-msg"></div>
        <div data-item-id="<?php echo $rubric_id; ?>" class="assessment-horizontal-choice-rubric rubric-container">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rubric-table ui-sortable">
                    <thead>
                        <?php if ($this->visibility_flags["show_header_control_bar"]): ?>
                            <tr class="rubric-controls">
                                <th colspan="<?php echo $width; ?>">
                                    <div class="pull-right">
                                        <?php if ($this->visibility_flags["show_header_deleted_rubric_notice"] && $rubric_is_deleted):?>
                                            <span class="rubric-header-warning-text">
                                                <?php echo $translate->_("This rubric has been deleted and can not be edited."); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($this->visibility_flags["show_header_checkbox"]): ?>
                                            <span class="btn">
                                                <input type="checkbox" class="delete" name="delete[]" value="<?php echo $element_id; ?>">
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$rubric_is_deleted):?>
                                            <?php if ($this->visibility_flags["show_header_pencil"]): ?>
                                                <a href="<?php echo $rubric_edit_url; ?>" title="<?php $translate->_("Edit Item"); ?>" class="btn edit-item"><i class="icon-pencil"></i></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($this->visibility_flags["show_header_arrows"]): ?>
                                            <a href="#" title="<?php echo $translate->_("Move"); ?>" class="btn move"><i class="icon-move"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </th>
                            </tr>
                        <?php endif; ?>

                        <?php if ($this->visibility_flags["show_header_title"]): ?>
                            <tr class="rubric-title">
                                <th colspan="<?php echo $width; ?>">
                                    <div class="pull-left">
                                        <h2><?php echo html_encode($rubric_data["rubric"]["rubric_title"]); ?></h2>
                                    </div>
                                </th>
                            </tr>
                        <?php endif; ?>

                        <?php if ($this->visibility_flags["show_header_description"] && $rubric_data["rubric"]["rubric_description"]): ?>
                            <tr class="rubric-description">
                                <th colspan="<?php echo $width; ?>">
                                    <div class="pull-left">
                                        <p><?php echo html_encode($rubric_data["rubric"]["rubric_description"]); ?></p>
                                    </div>
                                </th>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($rubric_data["descriptors"])): ?>
                            <tr>
                                <th></th>
                                <?php $position = 0; foreach($rubric_data["descriptors"] as $response_descriptors): $position++;?>
                                    <th class="label-cell <?php echo $this->visibility_flags["show_descriptor_edit_controls"] ? "category-editable" : ""; ?>">
                                        <h3 data-column-number="<?php echo $position?>" <?php echo $response_descriptors["ardescriptor_id"] ? "data-ardescriptor-id='{$response_descriptors["ardescriptor_id"]}' id='response-descriptor-{$response_descriptors["ardescriptor_id"]}'" : ""; ?>>
                                            <?php echo html_encode($response_descriptors["response_descriptor_text"]); ?>
                                        </h3>

                                        <?php if ($this->visibility_flags["show_descriptor_edit_controls"]): ?>
                                            <?php if ($all_response_descriptors): ?>
                                                <select class="descriptor-select">
                                                    <?php foreach ($all_response_descriptors as $descriptor): ?>
                                                    <option value="<?php echo $descriptor->getID(); ?>" <?php echo ($response_descriptors["ardescriptor_id"] == $descriptor->getID()) ? "selected='selected'" : ""; ?>>
                                                        <?php echo html_encode($descriptor->getDescriptor()); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                            <br/>
                                            <a class="btn btn-mini btn-category-remove hide"><?php echo $translate->_("Cancel"); ?></a>
                                            <a class="btn btn-mini btn-category-ok hide" data-descriptor-id="<?php echo @$response_descriptors["ardescriptor_id"] ?>" data-rubric-id="<?php echo $rubric_id ?>"><?php echo $translate->_("Save"); ?></a>
                                            <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>" class="category-loading hide">
                                            <i class="icon-pencil icon-category-pencil hide"></i>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    </thead>

                    <?php foreach ($rubric_data["lines"] as $rubric_line):
                        $item_id = $rubric_line["item"]["item_id"];
                        $edit_rubric_line_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?section=edit-item&item_id={$rubric_line["item"]["item_id"]}", $referrer_hash, null, null, $rubric_id);
                        ?>
                        <tbody data-aritem-id="<?php echo $rubric_line["rubric_item_record"]["aritem_id"]; ?>" data-comment-type="<?php echo $rubric_line["item"]["comment_type"]; ?>" class="sortable-rubric-item ui-draggable">

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
                                                <a class="btn move-rubric-item" href="#"><i class="icon-move"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr data-aritem-id="<?php echo $rubric_line["rubric_item_record"]["aritem_id"]; ?>" class="rubric-response-input item-response-view">
                                <td width="30%">
                                    <div class="rubric-item-text"><?php echo html_encode($rubric_line["item"]["item_text"]); ?>
                                        <?php if ($this->visibility_flags["show_label_edit_controls"]): ?>
                                            <br/>
                                            <span data-item-id="<?php echo $item_id; ?>" id="" class="edit-rubric-label" title="<?php echo $translate->_("Click to add description"); ?>">
                                                <?php if (isset($rubric_data["labels"][$item_id]["label"])): ?>
                                                    <?php echo html_encode($rubric_data["labels"][$item_id]["label"]); ?>
                                                <?php endif; ?>
                                            </span>
                                            <a data-item-id="<?php echo $item_id; ?>" class="edit-rubric-label-link"><i class="icon-edit"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php foreach($rubric_line["responses"] as $iresponse_id => $line_response): ?>
                                    <td class="rubric-response">
                                        <label for="<?php echo "response-$iresponse_id"; ?>">
                                            <input type="radio"
                                                   class="item-control"
                                                   value="<?php echo $iresponse_id; ?>"
                                                   data-iresponse-id="<?php echo $iresponse_id; ?>"
                                                   data-item-id="<?php echo $line_response["item_id"]; ?>"
                                                   id="<?php echo "response-$iresponse_id"; ?>"
                                                   name="<?php echo "rubric-item-$rubric_id-{$line_response["item_id"]}"; ?>"
                                                   <?php echo $is_disabled ? "disabled" : ""; ?>
                                                   <?php echo $line_response["flag_response"] ? "data-response-flagged='true'" : ""; ?>
                                                   <?php echo $line_response["is_selected"] ? "checked" : ""; ?>
                                            />
                                            <?php if ($line_response["flag_response"] && $this->visibility_flags["show_response_flag_icon"]): ?>
                                                <i class="icon-flag pull-right"></i>
                                            <?php endif; ?>
                                            <div class="rubric-response-text">
                                                <?php if ($line_response["response_text"]): ?>
                                                    <?php echo html_encode($line_response["response_text"]); ?>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <?php if ($rubric_line["item"]["render_comment_container"]): ?>
                                <tr id="<?php echo "rubric-item-{$rubric_id}-{$item_id}-comments-block"; ?>" class="item-response-view rubric-comment <?php echo $rubric_line["item"]["comment_container_visible"] ? "" : "hide"; ?>">
                                    <td></td>
                                    <td colspan="<?php echo $width; ?>">
                                        <div><?php echo $translate->_("COMMENT"); ?></div>
                                        <textarea class="span11 expandable"
                                                  id="<?php echo "item-$item_id-comments"; ?>"
                                                  name="<?php echo "item-$item_id-comments"; ?>"
                                                  <?php echo $is_disabled ? "disabled" : ""; ?>><?php echo html_encode($rubric_line["item"]["item_comment_text"]) ?></textarea>
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
