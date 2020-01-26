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
 * View class for rendering verticle multiple choice (multi-select) form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_VerticalMultipleChoiceMultiple extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="assessment-vertical-choice-item item-container" data-item-id="<?php echo $this->item["item_id"] ?>" data-comment-type="<?php echo html_encode($this->item["comment_type"]); ?>">

            <?php $this->buildItemDisabledOverlay(); ?>

            <table class="item-table vertical-multiple-choice-multiple">

                <?php $this->buildItemHeader(); ?>

                <?php
                if ($this->item["allow_default"] && $this->item["default_response"]) {
                    $has_response_selected = false;
                    foreach ($this->responses as $response) {
                        if ($response["is_selected"]) {
                            $has_response_selected = true;
                        }
                    }
                } else {
                    $has_response_selected = true;
                }
                ?>

                <?php if ($this->responses): ?>

                    <?php $count = 0; foreach ($this->responses as $iresponse_id => $response): $count++; ?>
                        <tr class="item-response-view <?php echo ($count % 2 == 0) ? "row-stripe" : ""?>">
                            <td width="5%" class="vertical-response-input <?php echo (($this->disabled) && ($response["is_selected"] || (!$has_response_selected && $this->item["default_response"] == $count))) ? "selected-response" : ""; ?>">
                                <input type="checkbox"
                                       class="item-control <?php echo ($this->disabled) ? "hide" : ""; ?>"
                                       data-item-id="<?php echo $this->item["item_id"] ?>"
                                       data-iresponse-id="<?php echo $iresponse_id ?>"
                                       id="<?php echo "item-{$this->item["item_id"]}-response-{$iresponse_id}" ?>"
                                       name="<?php echo "item-{$this->item["item_id"]}[]" ?>"
                                       value="<?php echo $iresponse_id ?>"
                                       <?php echo ($response["flag_response"]) ? "data-response-flagged='true'" : ""; ?>
                                        <?php echo ($response["is_selected"] || (!$has_response_selected && $this->item["default_response"] == $count)) ? "checked='checked'" : ""; ?>
                                        <?php echo ($this->disabled) ? "disabled" : ""; ?>
                                />
                            </td>
                            <td width="95%" class="vertical-response-label">
                                <label for="<?php echo "item-{$this->item["item_id"]}-response-{$iresponse_id}"; ?>">
                                    <?php if (Entrada_Assessments_Forms::usesDescriptorInsteadOfResponseText($this->item["shortname"])): ?>
                                        <?php echo html_decode($response["response_descriptor_text"]) ?>
                                    <?php else: ?>
                                        <?php echo html_decode($response["text"]) ?>
                                    <?php endif; ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($this->areControlsVisible()): ?>
                        <?php $this->buildItemDetails(); ?>
                    <?php endif; ?>

                    <?php $this->buildItemComments($options); ?>

                <?php else: ?>

                    <tr>
                        <td colspan="2">
                            <p class="padding-top padding-left"><strong><?php echo $translate->_("There are no responses for this item."); ?></strong></p>
                        </td>
                    </tr>

                <?php endif; ?>

            </table>
        </div>        
    <?php
    }

    /**
     * Render an error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render vertical multiple choice (multiple response) form item"); ?></strong>
        </div>
        <?php
    }

}