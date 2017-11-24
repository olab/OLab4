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
 * View class for rendering horizontal multiple choice (single-select) form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_HorizontalMultipleChoiceSingle extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="assessment-horizontal-choice-item item-container" data-item-id="<?php echo $this->item["item_id"] ?>" data-comment-type="<?php echo html_encode($this->item["comment_type"]) ?>">
            <table class="item-table <?php echo str_replace("_", "-", $this->item["shortname"]) ?>">

                <?php $this->buildItemHeader(); ?>

                <?php if ($this->responses): $column_width = (100 / $this->response_count); ?>
                    <tr class="horizontal-response-input item-response-view">
                        <?php foreach ($this->responses as $iresponse_id => $response): ?>
                            <td width="<?php echo $column_width ?>%">
                                <label for="<?php echo "item-{$this->item["item_id"]}-response-{$iresponse_id}"; ?>">
                                    <input type="radio"
                                           class="item-control"
                                           data-item-id="<?php echo $this->item["item_id"]; ?>"
                                           data-iresponse-id="<?php echo $iresponse_id; ?>"
                                           id="<?php echo "item-{$this->item["item_id"]}-response-{$iresponse_id}"; ?>"
                                           name="<?php echo "item-{$this->item["item_id"]}"; ?>"
                                           value="<?php echo $iresponse_id; ?>"
                                           <?php echo ($response["flag_response"])? "data-response-flagged='true'" : ""; ?>
                                           <?php echo ($response["is_selected"]) ? "checked='checked'" : ""; ?>
                                           <?php echo ($this->disabled) ? "disabled" : ""; ?>
                                    />
                                </label>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr class="horizontal-response-label item-response-view">
                        <?php foreach ($this->responses as $iresponse => $response): ?>
                            <td width="<?php echo $column_width ?>%">
                                <label for="<?php echo "item-{$this->item["item_id"]}-response-{$iresponse}" ?>">
                                       <?php echo html_encode($response["response_descriptor_text"]) ?>
                                </label>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <?php if ($this->areControlsVisible()): ?>
                        <?php $this->buildItemDetails(); ?>
                    <?php endif; ?>

                    <?php $this->buildItemComments(); ?>

                <?php else: ?>

                    <tr>
                        <td colspan="<?php echo $this->response_count ?>">
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
            <strong><?php echo $translate->_("Unable to render horizontal multiple choice (single response) form item"); ?></strong>
        </div>
        <?php
    }
}