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
 * View class for rendering numeric form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_Numeric extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        $numeric_value = Entrada_Assessments_Forms::getItemComment($this->element, $this->progress); ?>
        <div class="item-container" data-item-id="<?php echo $this->item["item_id"]; ?>">
            <table class="item-table <?php echo str_replace("_", "-", $this->item["shortname"]) ?>">

                <?php $this->buildItemHeader(); ?>

                <tr class="item-response-view">
                  <td class="item-type-control">
                      <input
                            <?php echo ($this->disabled) ? "disabled" : "" ?>
                            id="<?php echo "item-{$this->item["item_id"]}"; ?>"
                            name="<?php echo "item-{$this->item["item_id"]}"; ?>"
                            type="text"
                            class="form-control item-control input-small"
                            value="<?php echo html_encode($numeric_value); ?>"
                      />
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
            <strong><?php echo $translate->_("Unable to render numeric field form item"); ?></strong>
        </div>
        <?php
    }
}