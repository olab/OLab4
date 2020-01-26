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
 * Modal for getting PIN input.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_PreceptorAccessRequest extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id"))) {
            return false;
        }
        return true;
    }

    /**
     * Render default error
     */
    protected function renderError() {
        global $translate;
        ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render Preceptor Access Request modal"); ?></strong>
        </div>
        <?php
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="modal fade" id="preceptor-access-request-modal" style="display: none">
            <form id="preceptor-access-request-form" class="form-horizontal">
                <input type="hidden" name="course_id" value="<?php echo $options["course_id"] ?>" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Enter Preceptor Information"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="preceptor-msgs"></div>
                    <div id="preceptor-access-loading" class="hide">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                        <p id="preceptor-access-msg"></p>
                    </div>
                    <div>
                        <div class="control-group">
                            <label for="preceptor-firstname" class="control-label form-required"><?php echo $translate->_("First Name") ?></label>
                            <div class="controls">
                                <input id="preceptor-firstname" type="text" name="requested_user_firstname" placeholder="<?php echo $translate->_("Preceptor First Name") ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="preceptor-lastname" class="control-label form-required"><?php echo $translate->_("Last Name") ?></label>
                            <div class="controls">
                                <input id="preceptor-lastname" type="text" name="requested_user_lastname" placeholder="<?php echo $translate->_("Preceptor Last Name") ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="preceptor-email" class="control-label form-required"><?php echo $translate->_("Email Address") ?></label>
                            <div class="controls">
                                <input id="preceptor-email" type="text" name="requested_user_email" placeholder="<?php echo $translate->_("Preceptor Email Address") ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="preceptor-number" class="control-label"><?php echo $translate->_("Staff Number") ?></label>
                            <div class="controls">
                                <input id="preceptor-number" type="text" name="requested_user_number" placeholder="<?php echo $translate->_("Preceptor Staff Number") ?>" maxlength="8" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="additional-comments" class="control-label"><?php echo $translate->_("Additional Comments") ?></label>
                            <div class="controls">
                                <textarea id="additional-comments" name="additional_comments"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="preceptor-access-request-submit" class="btn btn-info pull-right"><?php echo $translate->_("Request Access"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}