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
 * View class for rendering selectbox (multi select) form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_SelectboxMultiple extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="item-container" data-item-id="<?php echo $this->item["item_id"] ?>" data-comment-type="<?php echo html_encode($this->item["comment_type"]) ?>">
            <table class="item-table <?php echo str_replace("_", "-", $this->item["shortname"]) ?>">

                <?php $this->buildItemHeader(); ?>

                <?php if ($this->responses): ?>

                    <tr class="item-response-view">
                        <td class="item-type-control">
                            <select id="<?php echo "item-{$this->item["item_id"]}"; ?>"
                                    data-item-id="<?php echo $this->item["item_id"]; ?>"
                                    name="<?php echo "item-{$this->item["item_id"]}[]"; ?>"
                                    class="form-control item-control"
                                    multiple
                                    size="10"
                                    <?php echo $this->disabled ? "disabled" : "" ?>>

                                <?php foreach ($this->responses as $iresponse_id => $response): ?>
                                    <option value="<?php echo $response["iresponse_id"]; ?>"
                                        <?php echo $response["is_selected"] ? "selected" : "" ?>
                                        <?php echo $response["flag_response"] ? " data-response-flagged='true'" : ""; ?>>
                                        <?php echo html_encode($response["response_text"]); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </td>
                    </tr>

                    <?php if ($this->areControlsVisible()): ?>
                        <?php $this->buildItemDetails(); ?>
                    <?php endif; ?>

                    <?php $this->buildItemComments(); ?>

                <?php else: ?>

                    <tr>
                        <td>
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
            <strong><?php echo $translate->_("Unable to render multiple choice select box form item"); ?></strong>
        </div>
        <?php
    }

}