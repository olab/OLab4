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

class Views_Assessments_Forms_Sections_FormInformation extends Views_Assessments_Forms_Sections_Base {

    protected function renderView($options = array()) {
        global $translate;

        $form_id = @$options["form_id"];
        $form_in_use = @$options["form_in_use"];
        $form_mode = @$options["form_mode"];
        $form_title = @$options["form_title"];
        $form_description = @$options["description"];
        $authors = @$options["authors"] ? $options["authors"] : array();
        $objective = @$options["objective"];
        ?>
        <div id="form-information-error-msg"></div>
        <div id="form-information">
            <div class="control-group">
                <label class="control-label<?php echo $form_in_use ? "" : " form-required"; ?>" for="form-title">
                    <?php echo $translate->_("Form Title"); ?>
                </label>
                <div class="controls">
                    <input type="text" name="form_title" id="form-title" class="span11" value="<?php echo html_encode($form_title); ?>"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="form-description">
                    <?php echo $translate->_("Form Description"); ?>
                </label>
                <div class="controls">
                    <textarea class="span11 expandable" name="form_description" id="form-description"><?php echo html_encode($form_description); ?></textarea>
                </div>
            </div>

            <script type="text/javascript">
                jQuery(function ($) {
                    if ($("#contact-selector").audienceSelector != typeof "undefined") {
                        $("#contact-selector").audienceSelector({
                            "filter": "#contact-type",
                            "target": "#author-list",
                            "content_target": "<?php echo $form_id; ?>",
                            "api_url": "<?php echo ENTRADA_URL . "/admin/assessments/forms?section=api-forms"; ?>"
                        });
                    }
                });
            </script>

            <?php if ($form_mode == "edit"):

                $audience_selector = new Views_Assessments_Forms_Controls_AudienceSelector(array("mode" => $form_mode));
                $audience_selector->render(array(
                        "authors" => $authors,
                        "related-data-key" => "data-afauthor-id"
                    )
                );
                ?>

                <div id="curriculum-tag-container" class="control-group hide">
                    <label class="control-label form-required" for="curriculum-tag-btn"><?php echo $translate->_("Curriculum Tag Set"); ?></label>
                    <div class="controls">
                        <button id="curriculum-tag-btn" class="btn"><?php echo $objective ? $objective->getName() : $translate->_("Select A Curriculum Tag Set"); ?></button>
                    </div>
                </div>
                <div class="row-fluid <?php echo $form_in_use ? "hide" : ""; ?>">
                    <input id="submit-button" type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>"/>
                </div>

            <?php elseif ($form_mode == "add"): ?>

                <div class="row-fluid">
                    <input id="submit-button" type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>"/>
                </div>

            <?php endif; ?>
        </div>
    <?php
    }
}
