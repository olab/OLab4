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
 * Form buttons row on edit form page.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_FormOptionButtons extends Views_Assessments_Forms_Controls_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("form_in_use", "form_id"));
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $form_in_use     = $options["form_in_use"];
        $form_id         = $options["form_id"];
        $element_count   = @$options["element_count"] ? (int)$options["element_count"] : 0;
        $referrer_hash   = @$options["referrer_hash"];
        $items_list_url  = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?", $referrer_hash);
        $rubric_list_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?", $referrer_hash);
        $hide_class      = $element_count ? "" : "hide";
        ?>
        <div class="row-fluid space-below">

            <?php if (!$form_in_use): ?>
                <a href="#delete-form-items-modal" data-toggle="modal" class="btn btn-danger btn-delete visible-when-form-populated <?php echo $hide_class ?>">
                    <i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete"); ?>
                </a>
            <?php endif; ?>

            <div class="pull-right">

                <a href="?section=edit-form&form_id=<?php echo $form_id; ?>&generate-pdf=true"
                   name="generate-pdf"
                   class="btn btn-success space-right always-enabled visible-when-form-populated <?php echo $hide_class ?>">
                    <i class="icon-download-alt icon-white"></i> <?php echo $translate->_("Download PDF"); ?>
                </a>

                <div class="btn-group visible-when-form-populated <?php echo $hide_class ?>">
                    <button id="preview-form" class="btn"><i class="icon-eye-open"></i> <?php echo $translate->_("Preview Form"); ?></button>
                    <a id="copy-form-link" href="#copy-form-modal" data-toggle="modal" class="btn">
                        <i class="icon-share"></i> <?php echo $translate->_("Copy Form"); ?>
                    </a>
                </div>

                <div class="btn-group <?php echo $form_in_use ? "hide" : ""; ?>">
                    <a class="btn btn-success update-form-on-click" href="<?php echo $items_list_url?>">
                        <i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Item(s)"); ?>
                    </a>
                    <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="update-form-on-click" href="<?php echo $rubric_list_url; ?>">
                                <?php echo $translate->_("Add Grouped Item(s)"); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="add-text" data-form-id="<?php echo $form_id; ?>">
                                <?php echo $translate->_("Add Free Text"); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="add-curriculum-set" data-form-id="<?php echo $form_id; ?>">
                                <?php echo $translate->_("Add Curriculum Tag Set"); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

}