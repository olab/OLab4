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
 * View class for rendering the copy form blueprint modal.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_CopyFormBlueprint extends Views_Assessments_Forms_Base {

    /**
     * Ensure required options exist.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("action_url", "form_blueprint_id")) ) {
            return false;
        }
        if (!$this->validateArray($options, array("user_courses"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $action_url         = $options["action_url"];
        $form_blueprint_id  = $options["form_blueprint_id"];
        $user_courses       = $options["user_courses"];
        $prepopulate_text   = array_key_exists("prepopulate_text", $options) ? $options["prepopulate_text"] : "";
        $course_related     = $options["course_related"];
        ?>
        <div id="copy-form-blueprint-modal" class="modal fade" style="display:none">
            <form class="form-horizontal no-margin" action="<?php echo $action_url ?>" method="POST">
                <div class="modal-header"><h1><?php echo $translate->_("Copy Form Template"); ?></h1></div>
                <div class="modal-body">
                    <div id="copy-form-blueprint-msgs"></div>
                    <div class="control-group">
                        <label class="control-label form-required" for="new-form-title"><?php echo $translate->_("New Form Template Title"); ?></label>
                        <div class="controls">
                            <input type="text" name="new-form-blueprint-title" id="new-form-blueprint-title" value="<?php echo html_encode($prepopulate_text); ?>"/>
                        </div>
                    </div>
                    <?php if ($course_related): ?>
                    <div class="control-group hide" id="user-course-controls">
                        <label for="new-course-id" class="control-label form-required"><?php echo $translate->_("Program") ?></label>
                        <div class="controls">
                            <select name="new_course_id" id="new-course-id">
                                <?php foreach ($user_courses as $course): ?>
                                    <option value="<?php echo html_encode($course["course_id"]) ?>"><?php echo html_encode($course["course_name"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="new_course_id" id="new-course-id" value="0" />
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" data-form-blueprint-id="<?php echo $form_blueprint_id; ?>" class="btn btn-primary" id="copy-form-blueprint" value="<?php echo $translate->_("Copy Form Template")?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}