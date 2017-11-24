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
 * View class for rendering free text on a form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_FreeTextLabel extends Views_Assessments_Forms_Controls_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $afelement_id = @$options["afelement_id"];
        $element_text = @$options["element_text"];
        ?>
        <?php if ($this->mode == "editor" || $this->mode == "editor-readonly"): ?>

            <div class="form-item" data-afelement-id="<?php echo html_encode($afelement_id) ?>">
                <div class="item-container">
                    <table class="item-table">
                        <tr class="type">
                            <td>
                                <span class="item-type"><?php echo $translate->_("Free Text"); ?></span>
                                <div class="pull-right">
                                    <div class="btn-group">
                                        <a href="#" class="btn save-element" data-text-element-id="<?php echo $afelement_id ?>">
                                            <?php echo $translate->_("Save"); ?>
                                        </a>
                                        <span href="#" class="btn"><input type="checkbox" class="delete" name="delete[]" value="<?php echo $afelement_id ?>"/></span>
                                        <a href="#" title="<?php echo $translate->_("Attach to Form"); ?>" class="btn move"><i class="icon-move"></i></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="padding-left padding-right">
                                <div class="row-fluid space-above space-below">
                                    <?php if ($this->mode == "editor-readonly"):?>
                                        <?php echo $element_text; ?>
                                    <?php else: ?>
                                        <textarea id="<?php echo "element-$afelement_id"; ?>" name="text-element[<?php echo $afelement_id ?>]">
                                            <?php echo html_encode($element_text); ?>
                                        </textarea>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        <?php else: ?>

            <div class="form-text-container">
                <p><?php echo $element_text ?></p>
            </div>

        <?php endif; ?>
        <?php
    }

}






