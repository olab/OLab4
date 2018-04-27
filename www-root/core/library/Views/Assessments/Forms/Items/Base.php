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
 * Base view class for all form items. This base class isf or the input items.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_Base extends Views_Assessments_Forms_Base {

    protected $disabled       = false;      // Form disabled status (should the elements be manipulatable?)
    protected $element        = array();    // The form element (the instance of an item on a form)
    protected $item           = array();    // The specific form item
    protected $responses      = array();    // Responses for the item
    protected $response_count = 1;          // Count of the responses
    protected $progress       = array();    // Progress record information for the responses (includes related progress_response data)
    protected $tags           = array();    // Any associated tags for this item
    protected $header_html    = null;       // Optional header HTML for the item (not always visible)
    protected $draw_overlay   = false;      // Draw an optional gray overlay that renders the entire item as disabled

    protected function validateOptions($options = array()) {
        $this->setFormElementData($options);
        return parent::validateOptions($options);
    }

    /**
     * Given an options array, set object properties if they are found within.
     *
     * @param array $options
     */
    protected function setFormElementData($options = array()) {
        foreach ($options as $type => $option) {
            switch ($type) {
                case "item":
                    $this->item = $option;
                    break;
                case "responses":
                    $this->responses = $option;
                    if (!empty($this->responses)) {
                        $this->response_count = count($option);
                    }
                    break;
                case "progress":
                    $this->progress = $option;
                    break;
                case "tags":
                    $this->tags = $option;
                    break;
                case "disabled":
                    $this->disabled = $option;
                    break;
                case "header_html":
                    $this->header_html = $option;
                    break;
                case "element":
                    $this->element = $option;
                    break;
                case "draw_overlay":
                    $this->draw_overlay = $option;
                    break;
            }
        }
    }

    /**
     * Determine whether or not to show the element editor controls (the header bar).
     *
     * @return bool
     */
    public function areControlsVisible() {
        switch ($this->mode) {
            case "editor":
            case "editor-readonly":
                return true;
            default:
                return false;
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render form element"); ?></strong>
        </div>
        <?php
    }

    /**
     * Generate a header row based on the item and form element mode.
     */
    protected function buildItemHeader() {
        global $translate;
        $url = ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id={$this->item["item_id"]}";
        ?>
        <?php if ($this->areControlsVisible()): ?>
            <tr class="type">
                <td colspan="<?php echo $this->response_count ?>">
                    <span class="item-type"><?php echo html_encode($this->item["item_type_name"]); ?></span>
                    <div class="pull-right">
                        <?php if (is_null($this->header_html)): ?>
                            <span class="btn select-item">
                                <input title="<?php echo $translate->_("Select") ?>" type="checkbox" class="item-selector" name="items[]" value="<?php echo $this->item["item_id"] ?>"/>
                            </span>
                            <div class="btn-group">
                                <a href="<?php echo $url ?>" title="<?php echo $translate->_("Edit Item"); ?>" class="btn edit-item"><i class="icon-pencil"></i></a>
                                <a href="#" title="<?php echo $translate->_("View Item Details"); ?>" class="btn item-details"><i class="icon-eye-open"></i></a>
                                <a class="btn always-enabled" id="move-form-item" title="<?php echo $translate->_("Move"); ?>"><i class="icon-move"></i></a>
                            </div>
                        <?php else: ?>
                            <div class="btn-group">
                                <?php echo $this->header_html ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        <tr class="heading">
            <td colspan="<?php echo $this->response_count ?>">
                <h3><?php echo html_decode($this->item["item_text"]) ?></h3>
            </td>
        </tr>
        <?php
    }

    /**
     * Build the comments window, based on the element it is associated with.
     */
    protected function buildItemComments($options = array()) {
        global $translate;

        $disabled_text = "disabled='true'";
        $placeholder_text = sprintf("placeholder=\"%s\"", html_encode($translate->_("Please select a response before adding a comment.")));
        $default_response = null;
        if ($this->item["allow_default"] && $this->item["default_response"]) {
            $default_response = $this->item["default_response"]; // default_response is the response order (i.e., the nth item is the default response)
        }
        $selected_response = $default_response; // null or the default order
        $response_number = 0;
        foreach ($this->responses as $response) {
            $response_number++;
            if ($response["is_selected"]) {
                $selected_response = $response_number;
                $placeholder_text = "";
            }
        }
        if ($selected_response) {
            // There's a selected response, be it a default or an actual selection, so allow the comments to be editable
            $disabled_text = "";
        }
        if ($this->disabled) {
            // Always show as disabled if flag is set, regardless of other logic.
            $disabled_text = "disabled='true'";
            $placeholder_text = "";
        }
        if ($this->mode == "editor" || $this->mode == "editor-readonly") {
            $placeholder_text = ""; // no placeholder when in editor mode
        }
        if ($selected_response) {
            $placeholder_text = ""; // no need for a placeholder when a response is selected.
        }
        if (!empty($this->item) && $this->item["render_comment_container"]) {
            $data_afelement_id_text = $this->item["comment_related_afelement_id"] ?
                "data-afelement-id=\"{$this->item["comment_related_afelement_id"]}\"" :
                "";
            ?>
            <tr class="heading item-comment <?php echo $this->item["comment_container_visible"] ? "" : "hide"; ?>" id="<?php echo "item-{$this->item["item_id"]}-comments-header"; ?>">
                <td colspan="<?php echo $this->response_count; ?>">
                    <?php
                    switch ($this->item["comment_type"]) {
                        case "optional" :
                            $comment_type_label = $translate->_("(optional)");
                            break;
                        case "mandatory" :
                        case "flagged" :
                            $comment_type_label = $translate->_("(mandatory)");
                            break;
                        case "disabled" :
                        default :
                            $comment_type_label = "";
                            break;
                    }
                    ?>
                    <h3><?php echo $translate->_("Comments"); ?> <?php echo $comment_type_label ?></h3>
                </td>
            </tr>
            <tr id="<?php echo "item-{$this->item["item_id"]}-comments-block" ?>" class="item-response-view item-comment <?php echo $this->item["comment_container_visible"] ? "" : "hide" ?>">
                <td class="item-type-control" colspan="<?php echo $this->response_count ?>">
                    <?php if ($this->getMode() == "pdf"): ?>
                        <p class="text-left"><?php echo nl2br(html_decode($this->item["item_comment_text"])); ?></p>
                    <?php else: ?>
                        <textarea title="<?php echo $translate->_("Comment") ?>"
                                  class="expandable <?php echo $this->disabled ? "disabled" : ""; ?>"
                                  id="<?php echo "item-{$this->item["item_id"]}-comments" ?>"
                                  name="<?php echo "item-{$this->item["item_id"]}-comments" ?>"
                                  <?php echo $placeholder_text ?>
                                  <?php echo $data_afelement_id_text ?>
                                  <?php echo $disabled_text ?>><?php echo html_decode($this->item["item_comment_text"]) ?></textarea>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Build a table row containing item details for a given form item.
     */
    protected function buildItemDetails() {
        global $translate; ?>
        <tr class="item-detail-view hide">
            <td colspan="<?php echo $this->response_count ?>">
                <div class="item-details-container">
                    <h5><?php echo $translate->_("Item Code:"); ?> <span><?php echo $this->item["item_code"] ? $this->item["item_code"] : $translate->_("N/A"); ?></span></h5>
                    <h5><?php echo $translate->_("Item Tagged With"); ?></h5>
                    <div class="item-tags">
                        <?php if ($this->tags): ?>
                            <?php foreach ($this->tags as $tag): ?>
                                <span>
                                    <a href="#"><?php echo html_decode($tag["tag"]); ?></a>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php echo $translate->_("This item has no tags."); ?></p>
                        <?php endif; ?>
                    </div>
                    <ul>
                        <li class="pull-left">
                            <?php
                                switch ($this->item["comment_type"]) {
                                    case "optional" :
                                        $comment_type_text = $translate->_("optional");
                                        break;
                                    case "mandatory" :
                                        $comment_type_text = $translate->_("mandatory");
                                        break;
                                    case "flagged" :
                                        $comment_type_text = $translate->_("mandatory for flagged responses");
                                        break;
                                    case "disabled" :
                                    default :
                                        $comment_type_text = $translate->_("disabled");
                                        break;
                                }
                            ?>
                            <?php echo sprintf($translate->_("<span>Comments</span> are %s for this Item"), $comment_type_text); ?>
                        </li>
                        <li class="pull-right">
                            <?php echo sprintf($translate->_("<span>Item was created on</span>: %s"), date("Y-m-d", $this->item["created_date"])); ?>
                        </li>
                    </ul>
                </div>
            </td>
        </tr>
        <?php
    }

    protected function buildItemDisabledOverlay() {
        if ($this->draw_overlay) : ?>
        <div class="assessment-item-disabled-overlay"></div>
        <?php endif;
    }
}