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
 * View class for rendering the assessment plans interface
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Plans_Plan extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_id", "objective_id", "form_data", "assessment_plan_container_id", "cbme_objective_tree_id"))) {
            return false;
        }

        if (!$this->validateArrayNotEmpty($options, array("objective", "assessment_tools"))) {
            return false;
        }
        return true;
    }

    /**
     * Render the EPA editor
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->renderHead($options["assessment_tools"], $options["course_id"], $options["assessment_plan_container_id"]); ?>
        <h1 class="assessment-plan-heading">
            <?php echo sprintf($translate->_("%s Requirements"), html_encode($options["objective"]["objective_code"])) ?>
        </h1>

        <form id="assessment-plan-form" method="post" action="<?php echo ENTRADA_URL ?>/admin/courses/cbme/plans?section=plan&id=<?php echo $options["course_id"] ?>&objective_id=<?php echo $options["objective_id"] ?>&cbme_objective_tree_id=<?php echo $options["cbme_objective_tree_id"] ?>&assessment_plan_container_id=<?php echo $options["assessment_plan_container_id"] ?>&step=2">
            <input id="objective-id" type="hidden" name="objective_id" value="<?php echo $options["objective_id"] ?>" />
            <input id="course-id" type="hidden" name="course_id" value="<?php echo $options["course_id"] ?>" />
            <?php /*
            <div class="assessment-plan-buttons space-below medium pull-right">
                <a href="#" class="btn"><i class="fa fa-files-o" aria-hidden="true"></i> <?php echo $translate->_("Export Assessment Plan") ?></a>
            </div>
            */ ?>

            <?php if (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1) : ?>
                <div class="clearfix"></div>
                <div class="alert alert-info">
                    <?php echo $translate->_("<strong>Please Note</strong>: Since this Assessment Plan has been published it can no longer be edited. If you wish to edit this Assessment Plan, you must first mark it as a draft using the Mark as Draft button below.") ?>
                </div>
            <?php endif; ?>

            <div class="control-group">
                <label for="plan-title" class="control-label form-required"><?php echo $translate->_("Title") ?></label>
                <div class="controls">
                    <input type="text" id="plan-title" name="title" value="<?php echo (isset($options["form_data"]["title"]) ? $options["form_data"]["title"] : "") ?>" class="input-block-level <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?> />
                </div>
            </div>

            <div class="control-group">
                <label for="plan-description" class="control-label"><?php echo $translate->_("Description") ?></label>
                <div class="controls">
                    <textarea id="plan-description" name="description" class="input-block-level" rows="3" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?>><?php echo (isset($options["form_data"]["description"]) ? $options["form_data"]["description"] : "") ?></textarea>
                </div>
            </div>

            <?php /*
            <div class="control-group">
                <label for="minimum-assessments" class="control-label form-required"><?php echo $translate->_("Minimum Number of Assessments"); ?></label>
                <div class="controls">
                    <input type="text" id="minimum-assessments" name="minimum_assessments" value="<?php echo (isset($options["form_data"]["minimum_assessments"]) ? $options["form_data"]["minimum_assessments"] : "") ?>" class="input-small" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?> />
                </div>
            </div>
            */ ?>

            <div class="control-group space-below medium">
                <div id="assessment-tool-msgs" class="alert alert-error hide"></div>

                <label for="assessment_plan_tools" class="control-label"><?php echo $translate->_("Tools"); ?></label>
                <div class="controls">
                    <button id="assessment_plan_tools" class="btn btn-success" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>><?php echo $translate->_("Assessment Tools <span class=\"fa fa-chevron-down\"></span>") ?></button>
                </div>
            </div>

            <div id="assessment-tools">
            <?php if (isset($options["form_data"]["assessment_forms"])) : ?>
                <?php foreach ($options["form_data"]["assessment_forms"] as $assessment_form) : ?>
                    <?php $minimum_assessments_key = "form_" . $assessment_form["form_id"] . "_minimum"; ?>
                    <?php $minimum_assessors_key = "form_" . $assessment_form["form_id"] . "_minimum_assessors"; ?>
                    <?php $rating_scale_response_key = "form_" . $assessment_form["form_id"] . "_rating_scale_response"; ?>
                    <div class="assessment-plan-container space-below medium clearfix" id="form-<?php echo $assessment_form["form_id"] ?>-container" data-id="<?php echo $assessment_form["form_id"] ?>">
                        <h3><?php echo html_encode($assessment_form["title"]) ?></h3>

                        <div class="control-group clearfix">
                            <div class="inline-control-wrapper">
                                <label class="control-label form-required" for="form-<?php echo $assessment_form["form_id"] ?>-minimum"><?php echo $translate->_("Minimum number of assessments") ?></label>
                                <div class="controls">
                                    <input type="text" class="input-small <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>" id="form-<?php echo $assessment_form["form_id"] ?>-minimum" name="form_<?php echo $assessment_form["form_id"] ?>_minimum" value="<?php echo (isset($options["form_data"][$minimum_assessments_key]) ? html_encode($options["form_data"][$minimum_assessments_key]) : "") ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?> />
                                </div>
                            </div>

                            <div class="inline-control-wrapper">
                                <label for="form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response" class="control-label form-required"><?php echo $translate->_("With a global assessment rating equal to or higher than") ?></label>
                                <div class="controls">
                                    <button id="form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response" class="btn btn-success rating-scale-response-btn" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>><?php echo isset($options["form_data"][$rating_scale_response_key]) ? html_encode($options["form_data"][$rating_scale_response_key]["text"]) . " <span class=\"fa fa-chevron-down\"></span>" : $translate->_("Rating scale responses <span class=\"fa fa-chevron-down\"></span>") ?></button>
                                </div>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label form-required" for="form-<?php echo $assessment_form["form_id"] ?>-minimum-assessors"><?php echo $translate->_("Minimum number of assessors") ?></label>
                            <div class="controls">
                                <input type="text" class="input-small <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>" id="form-<?php echo $assessment_form["form_id"] ?>-minimum-assessors" name="form_<?php echo $assessment_form["form_id"] ?>_minimum_assessors" value="<?php echo (isset($options["form_data"][$minimum_assessors_key]) ? html_encode($options["form_data"][$minimum_assessors_key]) : "") ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?> />
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="form_<?php echo $assessment_form["form_id"] ?>_contextual_variables" class="control-label form-required"><?php echo $translate->_("Contextual Variables") ?></label>
                            <div class="controls">
                                <button class="btn btn-success contextual-variable-widget" id="form_<?php echo $assessment_form["form_id"] ?>_contextual_variables" data-form-id="<?php echo $assessment_form["form_id"] ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>><?php echo $translate->_("Contextual Variables") ?> <span class="fa fa-chevron-down"></span></button>
                            </div>
                        </div>

                        <?php $contextual_variable_key = "form_" . $assessment_form["form_id"] . "_contextual_variables"; ?>
                        <?php if (array_key_exists($contextual_variable_key, $options["form_data"]) && is_array($options["form_data"][$contextual_variable_key])) : ?>
                            <?php foreach ($options["form_data"][$contextual_variable_key] as $contextual_variable) : ?>
                                <div id="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-<?php echo $contextual_variable["objective_id"] ?>-container" class="assessment-plan-container space-below medium clearfix" data-id="<?php echo $contextual_variable["objective_id"] ?>" data-form-id="<?php echo $assessment_form["form_id"] ?>">
                                    <h3><?php echo html_encode($contextual_variable["objective_name"]) ?></h3>
                                    <div class="control-group">
                                        <label for="form_<?php echo $assessment_form["form_id"] ?>_cv_<?php echo $contextual_variable["objective_id"] ?>_responses" class="control-label form-required"><?php echo $translate->_("Contextual Variable Responses") ?></label>
                                        <div class="controls">
                                            <button class="btn btn-success contextual-variable-response-widget" id="form_<?php echo $assessment_form["form_id"] ?>_cv_<?php echo $contextual_variable["objective_id"] ?>_responses" data-form-id="<?php echo $assessment_form["form_id"] ?>" data-objective-id="<?php echo $contextual_variable["objective_id"] ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>><?php echo $translate->_("Responses") ?> <span class="fa fa-chevron-down"></span></button>
                                        </div>
                                    </div>

                                    <?php $contextual_variable_responses_key = "form_". $assessment_form["form_id"] ."_cv_" . $contextual_variable["objective_id"] . "_responses"; ?>
                                    <?php if (array_key_exists($contextual_variable_responses_key, $options["form_data"]) && is_array($options["form_data"][$contextual_variable_responses_key])) : ?>
                                        <ul id="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-<?php echo $contextual_variable["objective_id"] ?>-response-list" class="list-set space-below medium contextual-variable-response-list">
                                            <?php foreach ($options["form_data"][$contextual_variable_responses_key] as $contextual_variable_response) : ?>
                                                <li id="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-response-<?php echo $contextual_variable_response["objective_id"] ?>-item" class="list-set-item">
                                                    <div class="list-set-item-cell full-width">
                                                        <label for="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-response-<?php echo $contextual_variable_response["objective_id"] ?>" class="control-label"><?php echo html_encode($contextual_variable_response["objective_name"]) ?></label>
                                                    </div>

                                                    <div class="list-set-item-cell">
                                                        <label for="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-response-<?php echo $contextual_variable_response["objective_id"] ?>" class="control-label form-required nowrap"><?php echo $translate->_("Minimum number of responses") ?></label>
                                                    </div>

                                                    <div class="list-set-item-cell">
                                                        <div class="controls">
                                                            <input type="text"
                                                                   class="input-small <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "disabled" : "") ?>"
                                                                   name="form_<?php echo $assessment_form["form_id"] ?>_contextual_variable_response_<?php echo $contextual_variable_response["objective_id"] ?>"
                                                                   id="form-<?php echo $assessment_form["form_id"] ?>-contextual-variable-response-<?php echo $contextual_variable_response["objective_id"] ?>"
                                                                   value="<?php echo (isset($options["form_data"]["form_". $assessment_form["form_id"] ."_contextual_variable_response_". $contextual_variable_response["objective_id"]])
                                                                       ? $options["form_data"]["form_". $assessment_form["form_id"] ."_contextual_variable_response_". $contextual_variable_response["objective_id"]] : "") ?>" <?php echo (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1 ? "readonly" : "") ?> />
                                                        </div>
                                                    </div>

                                                    <?php if (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 0) : ?>
                                                        <div class="list-set-item-cell remove-contextual-variable-response-container">
                                                            <a href="#" class="remove-contextual-variable-response-btn" data-form-id="<?php echo $assessment_form["form_id"] ?>" data-contextual-variable-id="<?php echo $contextual_variable["objective_id"] ?>" data-contextual-variable-response-id="<?php echo $contextual_variable_response["objective_id"] ?>">
                                                                <i class="fa fa-times" aria-hidden="true"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php if (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 0) : ?>
                                        <div class="pull-right remove-contextual-variable-container">
                                            <a href="#" class="btn btn-danger remove-contextual-variable-btn" data-form-id="<?php echo $assessment_form["form_id"] ?>" data-contextual-variable-id="<?php echo $contextual_variable["objective_id"] ?>"><i class="fa fa-times" aria-hidden="true"></i> <?php echo $translate->_("Remove Contextual Variable"); ?></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 0) : ?>
                            <div class="pull-right remove-form-container">
                                <a href="#" class="btn btn-danger remove-form-btn" data-form-id="<?php echo $assessment_form["form_id"] ?>"><i class="fa fa-times" aria-hidden="true"></i> <?php echo $translate->_("Remove Tool"); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>

            <div class="row-fluid">
                <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme/plans?section=container&assessment_plan_container_id=" . $options["assessment_plan_container_id"] . "&id=" . $options["course_id"]; ?>" class="btn"><?php echo $translate->_("Cancel"); ?></a>
                <div class="pull-right">
                    <?php if (isset($options["form_data"]) && !empty($options["form_data"])) : ?>
                        <a href="#delete-plan-modal" data-toggle="modal" class="btn btn-danger"><?php echo $translate->_("Remove Plan"); ?></a>
                    <?php endif; ?>
                    <?php if (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 1) : ?>
                        <input type="submit" name="save_as_draft" value="<?php echo $translate->_("Mark as Draft"); ?>" class="btn btn-warning" />
                    <?php endif; ?>
                    <?php if ((!isset($options["form_data"]["published"])) || (isset($options["form_data"]["published"]) && $options["form_data"]["published"] == 0)) : ?>
                        <input type="submit" name="save_as_draft" value="<?php echo $translate->_("Save as Draft"); ?>" class="btn btn-warning" />
                        <input type="submit" name="publish" value="<?php echo $translate->_("Publish Plan"); ?>" class="btn btn-success" />
                    <?php endif; ?>
                </div>
            </div>
            <?php if (isset($options["form_data"]["assessment_forms"]) && is_array($options["form_data"]["assessment_forms"])) : ?>
                <?php foreach ($options["form_data"]["assessment_forms"] as $assessment_form) : ?>
                    <input type="hidden"
                           value="<?php echo $assessment_form["form_id"] ?>"
                           id="assessment_forms_<?php echo $assessment_form["form_id"] ?>"
                           data-label="<?php echo html_encode($assessment_form["title"]) ?>"
                           class="search-target-control assessment_forms_search_target_control assessment-plan-control" name="assessment_forms[]  " />
                    <?php if (array_key_exists("form_". $assessment_form["form_id"] ."_rating_scale_response", $options["form_data"])) : ?>
                        <input type="hidden"
                               name="form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response"
                               value="<?php echo html_encode($options["form_data"]["form_". $assessment_form["form_id"] ."_rating_scale_response"]["iresponse_id"]) ?>"
                               id="form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response_<?php echo $options["form_data"]["form_". $assessment_form["form_id"] ."_rating_scale_response"]["iresponse_id"] ?>"
                               data-label="<?php echo $options["form_data"]["form_". $assessment_form["form_id"] ."_rating_scale_response"]["text"] ?>"
                               class="search-target-control form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response_search_target_control form_<?php echo $assessment_form["form_id"] ?>_rating_scale_response_selector assessment-plan-control" />
                    <?php endif; ?>
                    <?php if (array_key_exists("form_". $assessment_form["form_id"] ."_contextual_variables", $options["form_data"]) && is_array($options["form_data"]["form_". $assessment_form["form_id"] ."_contextual_variables"])) : ?>
                        <?php foreach ($options["form_data"]["form_". $assessment_form["form_id"] ."_contextual_variables"] as $contextual_variable_objective) : ?>
                            <input type="hidden"
                                   value="<?php echo $contextual_variable_objective["objective_id"] ?>"
                                   id="form_<?php echo $assessment_form["form_id"] ?>_contextual_variables_<?php echo $contextual_variable_objective["objective_id"] ?>"
                                   data-label="<?php echo html_encode($contextual_variable_objective["objective_name"]) ?>"
                                   class="search-target-control form_<?php echo $assessment_form["form_id"] ?>_contextual_variables_search_target_control assessment-plan-control contextual-variable-assessment-plan-control"
                                   name="form_<?php echo $assessment_form["form_id"] ?>_contextual_variables[]"
                                   data-form-id="<?php echo $assessment_form["form_id"] ?>" />
                            <?php if (array_key_exists("form_". $assessment_form["form_id"] ."_cv_". $contextual_variable_objective["objective_id"] ."_responses", $options["form_data"]) && is_array($options["form_data"]["form_". $assessment_form["form_id"] ."_cv_". $contextual_variable_objective["objective_id"] ."_responses"])) : ?>
                                <?php foreach ($options["form_data"]["form_". $assessment_form["form_id"] ."_cv_". $contextual_variable_objective["objective_id"] ."_responses"] as $contextual_variable_response_objective) : ?>
                                    <input type="hidden"
                                           value="<?php echo $contextual_variable_response_objective["objective_id"] ?>"
                                           id="form_<?php echo $assessment_form["form_id"] ?>_cv_<?php echo $contextual_variable_objective["objective_id"] ?>_responses_<?php echo $contextual_variable_response_objective["objective_id"] ?>"
                                           data-label="<?php echo html_encode($contextual_variable_response_objective["objective_name"]) ?>"
                                           class="search-target-control form_<?php echo $assessment_form["form_id"] ?>_cv_<?php echo $contextual_variable_objective["objective_id"] ?>_responses_search_target_control assessment-plan-control contextual-variable-assessment-plan-control contextual-variable-response-assessment-plan-control"
                                           name="form_<?php echo $assessment_form["form_id"] ?>_cv_<?php echo $contextual_variable_objective["objective_id"] ?>_responses[]"
                                           data-objective-id="<?php echo $contextual_variable_objective["objective_id"] ?>" />
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </form>
        <?php
    }

    /**
     * * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     * @param $assessment_tools
     * @param int $course_id
     * @param int $assessment_plan_container_id
     */
    protected function renderHead($assessment_tools, $course_id = 0, $assessment_plan_container_id = 0) {
        global $translate, $BREADCRUMB, $HEAD;

        /**
         * Add required stylesheets and scripts
         */
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/assessment-plans.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/cbme/assessment-plan.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";

        /**
         * Add all JavaScript translations
         */
        Entrada_Utilities::addJavascriptTranslation("Assessment Tools", "assessment_tools_label");
        Entrada_Utilities::addJavascriptTranslation("No Assessment Tools Found", "no_assessment_tools_label");
        Entrada_Utilities::addJavascriptTranslation("Contextual Variables", "assessment_tools_cv_label");
        Entrada_Utilities::addJavascriptTranslation("No Contextual Variables Found", "no_assessment_form_cv_label");

        /**
         * Add assessment tools as a global JS variable to be used as the datasource for the assessment tools advancedSearch instance.
         */
        $HEAD[] = "<script type=\"text/javascript\">var assessment_tools = '" . addslashes(json_encode($assessment_tools)) . "';</script>";
        $BREADCRUMB[] = array("url" => ENTRADA_URL ."/admin/courses/cbme/plans?".replace_query(array("section" => "container", "id" => $course_id, "assessment_plan_container_id" => $assessment_plan_container_id)), "title" => $translate->_("Plan"));
        $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Requirements"));
    }

}