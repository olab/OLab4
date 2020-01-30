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
 * View class for General Comments input in blueprints.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Components_StandardItem extends Views_HTML {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        $view_to_render = new Views_Assessments_Forms_Item(array("mode" => "assessment"));
        $view_to_render->render($options);
        // Currently, we just render a readonly version of the form item.
        // We're wrapping it in this view class because we might include flagging options in the future.
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render Standard Form Item."); ?></strong>
        </div>
        <?php
    }

}