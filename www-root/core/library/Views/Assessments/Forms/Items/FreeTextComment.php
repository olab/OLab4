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
 * along with Entrada. If not, see <http://www.gnu.org/licenses/>.
 *
 * View class for rendering free text comment form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_FreeTextComment extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $value = html_decode(Entrada_Assessments_Forms::getItemComment($this->element, $this->progress));
        $is_disabled = @$options["disabled"] ? true : false;
        ?>
        <div class="assessment-horizontal-choice-item item-container" data-item-id="<?php echo $this->item["item_id"]; ?>" data-comment-type="<?php echo html_encode($this->item["comment_type"]); ?>">

            <?php $this->buildItemDisabledOverlay(); ?>

            <table class="item-table <?php echo str_replace("_", "-", $this->item["shortname"]) ?>">

                <?php $this->buildItemHeader(); ?>

                <tr class="response-label item-response-view">
                    <td class="item-type-control">
                        <?php if ($this->getMode() == "pdf"): ?>
                            <p class="text-left"><?php echo nl2br($value); ?></p>
                        <?php else: ?>
                            <textarea title="<?php echo $translate->_("Comment"); ?>"
                                      class="expandable"
                                      id="<?php echo "item-{$this->item["item_id"]}"; ?>"
                                      name="<?php echo "item-{$this->item["item_id"]}"; ?>"
                                      data-afelement-id="<?php echo $this->element["afelement_id"]?>"
                                      <?php echo $is_disabled ? "disabled" : ""; ?>><?php echo $value; ?></textarea>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ($this->areControlsVisible()): ?>
                    <?php $this->buildItemDetails(); ?>
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
            <strong><?php echo $translate->_("Unable to render free-text comment form item"); ?></strong>
        </div>
        <?php
    }
}