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
 * View class for rendering date selector form element.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Items_Date extends Views_Assessments_Forms_Items_Base {

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        $date_value = Entrada_Assessments_Forms::getItemComment($this->element, $this->progress);
        $dt = DateTime::createFromFormat("U", $date_value);
        if ($dt === false || array_sum($dt->getLastErrors())) {
            $value = "";
        } else {
            $value = $dt->format("Y-m-d");
        }
        ?>
        <div class="item-container" data-item-id="<?php echo $this->item["item_id"]; ?>">

            <?php $this->buildItemDisabledOverlay(); ?>

            <table class="item-table <?php echo str_replace("_", "-", $this->item["shortname"]) ?>">

                <?php $this->buildItemHeader(); ?>

                <tr class="response-label item-response-view">
                    <td class="item-type-control">
                        <div class="input-append full-width">
                            <input type="text"
                                   name="<?php echo "item-{$this->item["item_id"]}"; ?>"
                                   class="item-element-date-picker datepicker item-control input-large"
                                   <?php echo ($this->disabled) ? "disabled" : ""; ?>
                                   value="<?php echo ($value) ? $value : ""; ?>"
                            />
                            <span class="add-on datepicker-icon"><i class="icon-calendar"></i></span>
                        </div>
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
            <strong><?php echo $translate->_("Unable to render date selection form item"); ?></strong>
        </div>
        <?php
    }
}