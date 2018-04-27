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
 * View for modal for adding a new form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_AddForm extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("action_url", "medtech_admin"))) {
            return false;
        }
        if (!$this->validateArray($options, array("form_types"))) {
            return false;
        }
        if (!$this->validateArray($options, array("user_courses"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"];
        $form_types = $options["form_types"];
        $user_courses = $options["user_courses"];
        $medtech_admin = $options["medtech_admin"];
        ?>
        <div id="add-form-modal" class="modal hide fade">
            <form class="form-horizontal no-margin" action="<?php echo $action_url ?>" method="POST">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $translate->_("Add Form"); ?></h1></div>
                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label form-required" for="form-title"><?php echo $translate->_("Form Name"); ?></label>
                        <div class="controls">
                            <input type="text" name="form_title" id="form-title" />
                        </div>
                    </div>

                    <div class="control-group space-above">
                        <label for="form-type" class="control-label"><?php echo $translate->_("Form Type"); ?></label>
                        <div class="controls">
                            <select name="form_type_id" id="form-type">
                                <?php foreach($form_types as $form_type): ?>
                                    <?php if ($form_type->getShortname() != "cbme_multisource_feedback" || $medtech_admin): ?>
                                        <option data-course-related="<?php echo html_encode($form_type->getCourseRelated()); ?>" value="<?php echo html_encode($form_type->getID()) ?>"><?php echo html_encode($form_type->getTitle()) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (count($user_courses) > 1): ?>
                    <div class="control-group hide" id="user-course-controls">
                        <label for="course-id" class="control-label form-required"><?php echo $translate->_("Program") ?></label>
                        <div class="controls">
                            <select name="course_id" id="course-id">
                                <?php foreach($user_courses as $course): ?>
                                    <option value="<?php echo html_encode($course["course_id"]) ?>"><?php echo html_encode($course["course_name"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php else : ?>
                        <input type="hidden" name="course_id" value="<?php echo html_encode($user_courses[0]["course_id"]); ?>" />
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Add Form"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
