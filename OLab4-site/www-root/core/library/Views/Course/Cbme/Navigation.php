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
 * View class for rendering the course CBME sub navigation.
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Navigation extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id", "active_tab"))) {
            return false;
        }
        return true;
    }

    /**
     * Render the Course CBME navigation
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="btn-group space-below">
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?id=" . html_encode($options["course_id"]) ?>" class="btn <?php echo ($options["active_tab"] == "getting_started" ? "active" : "") ?>"><?php echo $translate->_("Getting Started") ?></a>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=import-cbme-data&id=" . html_encode($options["course_id"]) ?>" class="btn <?php echo ($options["active_tab"] == "import_cbme_data" ? "active" : "") ?>"><?php echo $translate->_("Import CBME Data") ?></a>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=map-curriculumtags&id=" . html_encode($options["course_id"]) ?>" class="btn <?php echo ($options["active_tab"] == "map_curriculum_tags" ? "active" : "") ?>"><?php echo $translate->_("Map Curriculum Tags") ?></a>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=manage-contextual-variable-responses&id=" . html_encode($options["course_id"]) ?>" class="btn <?php echo ($options["active_tab"] == "contextual_variable_responses" ? "active" : "") ?>"><?php echo $translate->_("CV Responses") ?></a>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme/plans?id=" . html_encode($options["course_id"]) ?>" class="btn <?php echo ($options["active_tab"] == "assessment_plans" ? "active" : "") ?>"><?php echo $translate->_("Assessment Plans") ?></a>
        </div>
        <?php
    }
}