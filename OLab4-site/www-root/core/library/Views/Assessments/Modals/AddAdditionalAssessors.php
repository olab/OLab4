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
 * View class for modal window to display and confirm the addition of
 * assessors
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Modals_AddAdditionalAssessors extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>

        <div id="additional-assessors-modal" class="modal delegation-modal fade hide">
        <form id="assessor-selections-form" class="hide"></form>

        <div class="modal-header text-center">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h2><?php echo $translate->_("Add Additional Assessor"); ?></h2>
        </div>

        <div id="assessor-msgs" class="text-center"></div>

        <div class="modal-body text-center space-above">
            <p><?php echo $translate->_("Select an assessor to add to the list:"); ?></p>
            <div id="select-additional-assessors" class="assessor-type-selector">
                <div class="control-group">
                    <div id="add-assessors-autocomplete-container" class="controls clearfix">
                        <div class="assessor-autocomplete-sizer">
                            <div class="input-append">
                                <input id="additional-assessors-search" type="text" class="form-control search" name="additional_assessors_search" placeholder="<?php echo $translate->_("Type to search for assessors.."); ?>"/>
                                <span id="assessor-autocomplete-clear-field" class="add-on">×</span>
                            </div>
                            <div id="autocomplete">
                                <div id="additional-assessors-autocomplete-list" class="text-left"></div>
                                <a id="default-add-external-assessor-btn" class="hide" href="#"><i class="icon-plus-sign"></i><?php echo $translate->_("Add New External Assessor");?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="selected-assessors-list">
                <h3 id="selected-assessors-list-heading" class="hide"><?php echo $translate->_("Selected Assessors"); ?></h3>
                <div id="selected-assessors-list-container" class="text-left">
                    <ul id="selected-assessors-list-ul" class="menu"></ul>
                </div>
            </div>

            <div id="external-assessors-controls" class="space-below medium hide">
                <div class="form-inline">
                    <input id="external-assessor-firstname" name="external_assessor_firstname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("First Name"); ?>"/>
                    <input id="external-assessor-lastname" name="external_assessor_lastname" class="form-control input-small" type="text" placeholder="<?php echo $translate->_("Last Name"); ?>"/>
                    <input id="external-assessor-email" name="external_assessor_email" class="form-control input-medium" type="text" placeholder="<?php echo $translate->_("Email Address"); ?>"/>
                    <a id="save-external-assessor-btn" href="#" class="btn btn-mini btn-success"><?php echo $translate->_("Add Assessor"); ?></a>
                    <a id="cancel-assessor-btn" href="#" class="btn btn-mini"><?php echo $translate->_("Cancel"); ?></a>
                </div>
            </div>

            <div class="hide">
                <?php // Empty default auto complete assessor list item ?>
                <li id="autocomplete-default-item" class="ui-menu-item" role="menuitem">
                    <a class="ui-corner-all" tabindex="-1">
                        <div class="additional-assessor-details text-left">
                            <div class="additional-assessor-photo-container">
                                <img src="<?php echo ENTRADA_URL . "/images/headshot-male-small.gif"; ?>" class="additional-assessor-photo">
                            </div>
                            <span class="additional-assessor-name"></span>
                            <span class="additional-assessor-secondary-details">
                                <span class="additional-assessor-type pull-right"></span>
                            </span>
                            <span class="additional-assessor-email"></span>
                        </div>
                    </a>
                </li>

                <?php // Empty default assessor added to the selected assessor list ?>
                <li id="default-selected-assessor" class="community selected-assessor-list-item">
                    <span class="selected-assessor-name"></span>
                    <span class="pull-right selected-assessor-container">
                        <span class="selected-assessor-label"></span>
                        <span class="remove-selected-assessor">&times;</span>
                    </span>
                </li>
            </div>

            <div class="modal-footer text-center">
                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                <a href="#" class="btn btn-primary pull-right" id="additional-assessor-selections-submit"><?php echo $translate->_("Continue"); ?></a>
            </div>
        </div>

        <?php
    }
}