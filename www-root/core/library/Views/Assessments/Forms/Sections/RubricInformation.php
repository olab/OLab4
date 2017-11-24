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
 * View class for assessment form form information edit contorls.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_RubricInformation extends Views_Assessments_Forms_Sections_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("in_use", "rubric_description", "rubric_title", "rubric_item_code"));
    }

    protected function renderView($options = array()) {
        global $translate;

        $rubric_in_use = $options["in_use"];
        $rubric_title = $options["rubric_title"];
        $rubric_description = $options["rubric_description"];
        $rubric_item_code = $options["rubric_item_code"];
        $authors = @$options["authors"] ? $options["authors"] : array();
        $disabled_text = "";
        if ($rubric_in_use) {
            $disabled_text = "disabled";
        }
        ?>
        <div class="control-group">
            <label class="control-label<?php echo ($rubric_in_use ? "" : " form-required") ?>" for="rubric-title"><?php echo $translate->_("Title"); ?></label>

            <div class="controls">
                <input type="text" name="rubric_title" id="rubric-title" class="span11" value="<?php echo html_encode($rubric_title); ?>" <?php echo $disabled_text; ?>/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rubric-description"><?php echo $translate->_("Description"); ?></label>

            <div class="controls">
                <textarea name="rubric_description" id="rubric-description" class="span11 expandable" <?php echo $disabled_text; ?>><?php echo html_encode($rubric_description); ?></textarea>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rubric-item-code"><?php echo $translate->_("Grouped Item Code"); ?></label>

            <div class="controls">
                <input type="text" name="rubric_item_code" id="rubric-item-code" class="span11" value="<?php echo html_encode($rubric_item_code); ?>" <?php echo $disabled_text; ?>/>
            </div>
        </div>
        <?php
            $audience_selector = new Views_Assessments_Forms_Controls_AudienceSelector(array("mode" => "edit"));
            $audience_selector->render(array(
                    "authors" => $authors,
                    "related-data-key" => "data-arauthor-id"
                )
            );
    }
}
