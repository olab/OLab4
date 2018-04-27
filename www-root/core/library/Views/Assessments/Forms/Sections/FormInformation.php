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

    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("form_types"))) {
            return false;
        }
        return true;
    }

    protected function renderJavascriptTranslations() {
        global $translate;

        Entrada_Utilities::addJavascriptTranslation($translate->_("Item Code: "), "item_code_label");
        Entrada_Utilities::addJavascriptTranslation($translate->_("Item Tagged with "), "item_tag_label");
        Entrada_Utilities::addJavascriptTranslation($translate->_("Item Details"), "item_details_label");
        Entrada_Utilities::addJavascriptTranslation(
                sprintf("<span>%s</span> %s ",$translate->_("Comments"),$translate->_("are")),
                "comments_are_label_prefix",
                "javascript_translations",
                false);
        Entrada_Utilities::addJavascriptTranslation($translate->_(" for this item"), "comments_are_label_postfix");

        Entrada_Utilities::addJavascriptTranslation(
                sprintf("<span>%s</span>: ",$translate->_("Item created on")),
                "item_created_label",
                "javascript_translations",
                false);


    }

    protected function renderView($options = array()) {
        global $translate;

        $form_types             = $options["form_types"];
        $selected_form_type     = Entrada_Utilities::arrayValueOrDefault($options, "form_type_id");
        $form_id                = Entrada_Utilities::arrayValueOrDefault($options, "form_id");
        $attributes             = Entrada_Utilities::arrayValueOrDefault($options, "attributes");
        $form_in_use            = Entrada_Utilities::arrayValueOrDefault($options, "form_in_use");
        $form_mode              = Entrada_Utilities::arrayValueOrDefault($options, "form_mode");
        $form_title             = Entrada_Utilities::arrayValueOrDefault($options, "form_title");
        $form_description       = Entrada_Utilities::arrayValueOrDefault($options, "description");
        $authors                = Entrada_Utilities::arrayValueOrDefault($options, "authors", array());
        $objective              = Entrada_Utilities::arrayValueOrDefault($options, "objective");
        $is_publishable         = Entrada_Utilities::arrayValueOrDefault($options, "is_publishable");
        $form_objectives        = Entrada_Utilities::arrayValueOrDefault($options, "form_objectives", array());
        $epas                   = Entrada_Utilities::arrayValueOrDefault($options, "epas", array());
        $contextual_variables   = Entrada_Utilities::arrayValueOrDefault($options, "contextual_variables");
        $contextual_vars_desc   = Entrada_Utilities::arrayValueOrDefault($options, "contextual_vars_desc");
        $course_related         = Entrada_Utilities::arrayValueOrDefault($options, "course_related");
        $courses_list           = Entrada_Utilities::arrayValueOrDefault($options, "courses_list");
        $is_published           = Entrada_Utilities::arrayValueOrDefault($options, "is_published");
        $scales_list            = Entrada_Utilities::arrayValueOrDefault($options, "scales_list");

        $course_id = null;
        $cvars_init_data = array();
        $entrustment_init_data = array();
        $has_scale = false;
        if ($attributes && is_array($attributes)) {
            if (array_key_exists("course_id", $attributes)) {
                $course_id = $attributes["course_id"];
            }

            if (array_key_exists("contextual_vars", $attributes)) {
                $cvars_init_data = $attributes["contextual_vars"];
            }

            if (array_key_exists("entrustment_rating", $attributes)) {
                $entrustment_init_data = $attributes["entrustment_rating"];
                if (is_array($entrustment_init_data) && count($entrustment_init_data)) {
                    $has_scale = true;
                }
            }
        }

        // Just need the objective codes, the details will be fetch from the epas
        $form_objectives = array_filter(
            array_map(
                function ($form_objective) {
                    if (isset($form_objective["objective_code"])) {
                        return $form_objective["objective_code"];
                    } else {
                        return null;
                    }
                },
                $form_objectives
            )
        );

        $this->renderJavascriptTranslations();
        ?>
        <div id="form-information-error-msg"></div>
        <input type="hidden" id="form-id" value="<?php echo $form_id; ?>" />
        <?php if ($course_id): ?>
            <input type="hidden" id="course-id" name="course_id" value="<?php echo $course_id; ?>" />
        <?php endif; ?>
        <?php if ($epas && is_array($epas)): ?>
            <div id="mapped-epas-div" class="alert alert-block alert-info<?php echo (count($form_objectives)) ? "" : " hide"; ?>">
                <p><?php echo $translate->_("This form is published and mapped to the following EPAs:"); ?></p>
                <ul id="mapped-epas-list">
                    <?php foreach ($epas as $epa): ?>
                        <?php if (in_array($epa["objective_code"], $form_objectives)): ?>
                            <li>
                                <strong><?php echo $epa["objective_code"]; ?></strong>&nbsp;-&nbsp;<?php echo $epa["objective_name"]; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
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
            <div class="control-group space-above">
                <label class="control-label"><?php echo $translate->_("Form Type"); ?></label>
                <div class="controls">
                    <?php if($selected_form_type): ?>
                        <input type="hidden" name="form_type_id" value="<?php echo $selected_form_type; ?>">
                        <input type="text" value="<?php echo is_object(@$form_types[$selected_form_type]) ? $form_types[$selected_form_type]->getTitle() : ""; ?>" disabled class="disabled">
                    <?php else: ?>
                        <select name="form_type_id" id="form-type">
                            <?php foreach($form_types as $form_type):
                                $selected_form_type_text = "";
                                if ($form_type->getID() == $selected_form_type) {
                                    $selected_form_type_text = "selected";
                                }
                                ?>
                                <option value="<?php echo $form_type->getID() ?>" <?php echo $selected_form_type_text; ?>><?php echo $form_type->getTitle(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($course_related): ?>
                <input type="hidden" name="original_course_id" id="original-course-id" value="<?php echo $course_id; ?>" />
                <div class="control-group">
                    <label class="control-label" for="course-id"><?php echo $translate->_("Course"); ?></label>
                    <div class="controls">
                        <?php if ($is_published): ?>
                            <input type="hidden" name="course_id" id="course-id" class="hide" value="<?php echo $course_id ?>"/>
                            <?php foreach ($courses_list as $course_record):
                                if ($course_record["course_id"] == $course_id): ?>
                                    <input type="text" value="<?php echo html_encode($course_record["course_name"]); ?>" disabled class="disabled">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <select name="course_id" id="course-id">
                                <?php if (count($courses_list) > 1): ?>
                                    <option value="0"><?php echo sprintf($translate->_("Select a %s"), strtolower($translate->_("Course"))); ?></option>
                                <?php endif; ?>
                                <?php foreach ($courses_list as $course_record): ?>
                                    <option value="<?php echo html_encode($course_record["course_id"]); ?>" <?php echo ($course_record["course_id"] == $course_id) ? "selected" : "" ?>><?php echo html_encode($course_record["course_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <script type="text/javascript">
                jQuery(function ($) {
                    if ($("#contact-selector").length) {
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

                <?php if ($contextual_variables): ?>
                <div class="control-group">
                    <label class="control-label" for="form-description">
                        <?php echo $translate->_("Contextual Variables"); ?>
                    </label>
                    <div class="controls">
                        <?php
                        $cvars_view = new Views_Assessments_FormBlueprints_Components_ContextualVariableList();
                        $cvars_view->render(
                            array(
                                "epas" => array(),
                                "render_types" => array("markup", "template"),
                                "display_section_header" => false,
                                "contextual_variables" => $contextual_variables,
                                "epas_desc" => array(),
                                "contextual_vars_desc" => $contextual_vars_desc,
                                "disabled" => false,
                                "visible" => true,
                                "component_type" => "contextual_variable_list",
                                "form_blueprint_id" => $form_id,
                                "component_id" => 0,
                                "init_data" => $cvars_init_data,
                                "settings" => array(
                                    "mode" => "standalone",
                                    "required_types" => array(),
                                    "display_heading" => false
                                )
                            )
                        );
                        $modal_cvars_responses = new Views_Assessments_Modals_ContextualVariableResponses();
                        $modal_cvars_responses->render();
                        ?>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="rating-scale-required">
                        <input type="checkbox"  id="rating-scale-required" name="entrustment_rating" value="1" <?php echo $has_scale ? "checked" : ""; ?> />
                        <?php echo $translate->_("Entrustment Rating"); ?>
                    </label>
                    <div class="controls">
                        <?php
                        if ($settingsObj = Models_Assessments_Form_Blueprint_ComponentSettings::fetchRowByFormTypeComponentOrder($selected_form_type, 1)) {
                            $settings = json_decode($settingsObj->getSettings(), true);
                        } else {
                            $settings = array();
                        }

                        $scale_view = new Views_Assessments_FormBlueprints_Components_ScaleSelector();
                        $scale_view->render(
                            array(
                                "component_id" => 1,
                                "form_blueprint_id" => $form_id,
                                "render_types" => array("markup", "template"),
                                "init_data" => $entrustment_init_data,
                                "display_section_header" => true,
                                "all_scale_types" => @$scales_list["global_assessment"], // all of the scales of the given type for this blueprint_component
                                "scale_type" => "entrustment_scale",
                                "visible" => $has_scale,
                                "disabled"  => false,
                                "settings" => $settings
                            )
                        );
                        $modal_cvars_responses = new Views_Assessments_Modals_ContextualVariableResponses();
                        $modal_cvars_responses->render();
                        ?>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <?php
                        if ($form_types[$selected_form_type]->getShortname() == "cbme_ppa_form" || $form_types[$selected_form_type]->getShortname() == "cbme_rubric") {
                            echo display_notice(array($translate->_("Feedback and Concerns will be added when the form is published")));
                            $feedback_concerns = new Views_Assessments_FormBlueprints_Components_FeedbackConcernsOptions();
                            $feedback_concerns->render($attributes);
                        }
                        ?>
                    </div>
                </div>

                <div id="cvars-selection-inconsistent-with-published-data" class="alert alert-warning hide">
                    <?php echo $translate->_("The changes to the contextual variables will be reflected below once the form is published."); ?>
                </div>
                <div id="scale-selection-inconsistent-with-published-data" class="alert alert-warning hide">
                    <?php echo $translate->_("The changes to the entrustment rating will be reflected below once the form is published."); ?>
                </div>
            <?php endif; ?>
                <div class="row-fluid <?php echo $form_in_use ? "hide" : ""; ?>">
                    <input id="submit-button" type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>"/>
                    <input id="publish-button" type="submit" class="btn btn-primary pull-right<?php echo (!$is_publishable) ? " hide" : ""; ?>" style="margin-right: 10px;" value="<?php echo $translate->_("Publish"); ?>" />
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
