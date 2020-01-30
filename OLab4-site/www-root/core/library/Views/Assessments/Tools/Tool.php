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
 * HTML view for CBME assessment tools.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Tools_Tool extends Views_HTML {

    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("course_epas", "assessment_methods", "module", "mode", "proxy_id", "course_requires_epas", "can_request_preceptor_access", "course_requires_date_of_encounter", "preset_filters"))) {
            return false;
        }
        if (!$this->validateArray($options, array("user_courses"))) {
            return false;
        }
        return true;
    }

    /**
     * Render the curriculum mapping form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $this->addHeadScripts($options["course_epas"], $options["module"], $options["preset_filters"], $options["course_requires_epas"]);
        echo "<h1>" . $translate->_("Assessment Tools") . "</h1>";

        switch ($options["mode"]) {
            case "admin":
                $this->renderAdminView($options);
                break;

            case "faculty":
                $this->renderFacultyView($options);
                break;

            case "assessment-backfill":
                $this->renderAssessmentBackfillView($options);
                break;

            default:
                $this->renderLearnerView($options);
                break;
        }
    }

    /**
     * Render a container with the default localized string to use in the EPA selection advancedSearch button.
     * This method returns the generated button text, as well as renders the container for the button text, but not the button.
     *
     * @return string
     */
    private function renderDefaultEPASelectButtonTextContainer() {
        global $translate;
        $default_button_text = html_encode($translate->_("Click here to select an EPA")) . '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>'; ?>
        <div id="epa-selection-default-button-text" class="hide"><?php echo $default_button_text ?></div>
        <?php
        return $default_button_text;
    }

    /**
     * Render a container with the default localized string to use in the Course selection advancedSearch button.
     * This method returns the generated button text, as well as renders the container for the button text, but not the button.
     *
     * @return string
     */
    private function renderDefaultCourseSelectButtonTextContainer() {
        global $translate;
        $default_button_text = html_encode($translate->_("Click here to select a program")) . '&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i>'; ?>
        <div id="course-selection-default-button-text" class="hide"><?php echo $default_button_text ?></div>
        <?php
        return $default_button_text;
    }

    private function renderAdminView($options) {
        global $translate;
        $user_details = array_key_exists("target_details", $options) ? $options["target_details"] : null;
        $default_course_id = array_key_exists("default_course_id", $options) ? $options["default_course_id"] : null;
        $course_count = count($options["user_courses"]);
        $course_preference_id = false;
        if ($course_count == 1) {
            $only_course = end($options["user_courses"]);
            $only_course_id = $only_course["course_id"];
        } elseif ($default_course_id) {
            $course_preference_id = $default_course_id;
        }
        $default_method = "complete_and_confirm_by_pin";
        $default_epa_select_button_text = $this->renderDefaultEPASelectButtonTextContainer(); ?>
        <form id="assessment-tool-form" class="form-horizontal space-above large">
            <input type="hidden" name="target_record_id" value="<?php echo $options["proxy_id"] ?>"/>
            <?php if ($user_details): ?>
                <div class="control-group">
                    <label class="control-label"><?php echo $translate->_("Target") ?></label>
                    <div class="controls">
                        <div>
                            <img class="media-object people-search-thumb img-circle img-polaroid" src="<?php echo ENTRADA_URL ?>/api/photo.api.php/<?php echo $options["proxy_id"]; ?>/official" alt="<?php echo html_encode("{$user_details["firstname"]} {$user_details["lastname"]}"); ?>">
                        </div>
                        <?php echo html_encode("{$user_details["firstname"]} {$user_details["lastname"]}"); ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="control-group">
                <label for="select-attending-btn" class="control-label"><?php echo html_encode($translate->_("Select Attending")) ?></label>
                <div class="controls">
                    <button id="select-attending-btn" class="btn btn-default"><?php echo html_encode($translate->_("Click here to select an attending")) ?>&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>
                </div>
            </div>
            <div id="disabled-controls">
                <div class="disabled-overlay">
                    <span><?php echo $translate->_("Please select an assessor in order to continue.") ?></span>
                </div>
                <div class="control-group">
                    <label class="control-label"><?php echo $translate->_("Select assessment method") ?></label>
                    <div class="controls">
                        <?php if ($options["assessment_methods"]) : ?>
                            <?php foreach ($options["assessment_methods"] as $key => $assessment_method) : ?>
                                <label for="<?php echo html_encode($assessment_method["shortname"]) ?>" class="radio assessment-method <?php echo ($assessment_method["display"] ? "" : " hide") ?> <?php echo html_encode("assessment-method-" . $assessment_method["assessment_method_id"]) ?>">
                                    <input id="<?php echo html_encode($assessment_method["shortname"]) ?>" type="radio" name="assessment_method" value="<?php echo $assessment_method["assessment_method_id"] ?>" <?php echo($assessment_method["shortname"] == $default_method ? "checked=\"checked\"" : "") ?>>
                                    <span class="assessment-type-title"><?php echo html_encode($assessment_method["title"]) ?></span>
                                    <span class="assessment-type-description muted"><?php echo html_encode($assessment_method["description"]) ?></span>
                                    <span class="pin-warning muted hide"><?php echo html_encode($translate->_("The selected attending has not set their PIN.")) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <label for="no-methods-span">
                                <span id="no-methods-span"><?php echo html_encode($translate->_("No assessment methods defined.")) ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($course_count == 1) : ?>
                    <input id="course-id" type="hidden" name="course_id" value="<?php echo $only_course_id ?>"/>
                <?php else : ?>
                    <div id="course-selector" class="control-group">
                        <label for="course-id" class="control-label"><?php echo $translate->_("Select Program") ?></label>
                        <div class="controls">
                            <select id="course-id" name="course_id">
                                <option value="0"><?php echo $translate->_("-- Select program --") ?></option>
                                <?php foreach ($options["user_courses"] as $user_course) : ?>
                                    <option value="<?php echo $user_course["course_id"] ?>"<?php echo $user_course["course_id"] == $course_preference_id ? " selected" : "" ?>><?php echo html_encode($user_course["course_name"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <?php echo $this->renderDateOfEncounter($options["course_requires_date_of_encounter"]); ?>
                <div class="control-group epa-selector-div">
                    <label for="select-epa-btn" class="control-label"><?php echo $translate->_("Select an EPA") ?></label>
                    <div class="controls">
                        <button id="select-epa-btn" class="btn btn-default"><?php echo $default_epa_select_button_text ?></button>
                        <a class="epa-help space-left" href="<?php echo ENTRADA_URL . "/cbme/encyclopedia"?>" target="_blank" data-toggle="tooltip" data-original-title="<?php echo $translate->_("Click here for more information on EPAs"); ?>" data-placement="bottom"><i class="fa fa-question-circle fa-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="control-group hide space-below" id="assessment-tools">
                    <label for="search-assessment-tools" class="control-label"><?php echo $translate->_("Assessment Tools") ?></label>
                    <div class="controls">
                        <?php $this->renderAssessmentSearch(); ?>
                        <div id="assessment-tool-loading" class="hide">
                            <img src="<?php echo ENTRADA_URL . "/images/loading_small.gif" ?>" alt="<?php echo $translate->_("Loading assessment tools") ?>"/> <?php echo $translate->_("Loading assessment tools") ?>
                        </div>
                        <ul id="assessment-tool-list" class="user-list-card"></ul>
                        <div class="no-results-container"></div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    private function renderFacultyView($options) {
        global $translate;
        $default_course_select_button_text = $this->renderDefaultCourseSelectButtonTextContainer();
        $default_epa_select_button_text = $this->renderDefaultEPASelectButtonTextContainer();
        $assessment_method_id = array_key_exists("assessment_method_id", $options) ? $options["assessment_method_id"] : null;
        if (!$assessment_method_id) {
            $this->renderError();
            return;
        }
        ?>
        <form id="assessment-tool-form" class="form-horizontal space-above large">
            <input type="hidden" name="proxy_id" value="0"/>
            <input type="hidden" name="attending" id="attending" value="<?php echo $options["proxy_id"]; ?>"/>
            <input type="radio" checked="checked" class="hide" name="assessment_method" id="assessment_method" value="<?php echo $assessment_method_id ?>"/>
            <div id="assessment-tool-msgs" class="hide"></div>
            <div class="control-group">
                <label for="select-resident-btn" class="control-label"><?php echo $translate->_("Select Resident") ?></label>
                <div class="controls">
                    <button id="select-resident-btn" class="btn btn-default"><?php echo $translate->_("Click here to select a resident") ?>&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>
                </div>
            </div>
            <div id="disabled-controls">
                <div class="control-group hide course-selector-div">
                    <label for="cbme-course-picker" class="control-label"><?php echo html_encode($translate->_("Select a Program")) ?></label>
                    <div class="controls">
                        <button id="cbme-course-picker" class="btn btn-default"><?php echo $default_course_select_button_text ?></button>
                    </div>
                </div>
                <?php echo $this->renderDateOfEncounter($options["course_requires_date_of_encounter"]); ?>
                <div class="control-group hide epa-selector-div">
                    <label for="select-epa-btn" class="control-label"><?php echo $translate->_("Select an EPA") ?></label>
                    <div class="controls">
                        <button id="select-epa-btn" class="btn btn-default"><?php echo $default_epa_select_button_text ?></button>
                        <a class="epa-help space-left" href="<?php echo ENTRADA_URL . "/cbme/encyclopedia"?>" target="_blank" data-toggle="tooltip" data-original-title="<?php echo $translate->_("Click here for more information on EPAs"); ?>" data-placement="bottom"><i class="fa fa-question-circle fa-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="control-group hide space-below" id="assessment-tools">
                    <label for="search-assessment-tools" class="control-label"><?php echo $translate->_("Assessment Tools") ?></label>
                    <div class="controls">
                        <?php $this->renderAssessmentSearch(); ?>
                        <div id="assessment-tool-loading" class="hide">
                            <img src="<?php echo ENTRADA_URL . "/images/loading_small.gif" ?>" alt="<?php echo $translate->_("Loading assessment tools") ?>"/> <?php echo $translate->_("Loading assessment tools") ?>
                        </div>
                        <ul id="assessment-tool-list" class="user-list-card"></ul>
                        <div class="no-results-container"></div>
                    </div>
                </div>
            </div>
        </form>
        <div id="assessment-tool-form-preview" class="row-fluid space-below inner-content"></div>
        <?php
    }

    private function renderLearnerView($options) {
        global $translate;
        $default_course_id = array_key_exists("default_course_id", $options) ? $options["default_course_id"] : null;
        $course_count = count($options["user_courses"]);
        $course_preference_id = false;
        if ($course_count == 1) {
            $only_course = end($options["user_courses"]);
            $only_course_id = $only_course["course_id"];
            $has_tool_objectives = $only_course["has_tool_objectives"];

        } elseif ($default_course_id) {
            $course_preference_id = $default_course_id;
        }
        $default_method = "complete_and_confirm_by_pin";
        $default_epa_select_button_text = $this->renderDefaultEPASelectButtonTextContainer(); ?>
        <form id="assessment-tool-form" class="form-horizontal space-above large">
            <div id="assessment-tool-msgs" class="hide"></div>
            <input type="hidden" name="target_record_id" value="<?php echo $options["proxy_id"] ?>"/>
            <div class="control-group">
                <label for="select-attending-btn" class="control-label"><?php echo $translate->_("Select Assessor") ?></label>
                <div class="controls">
                    <button id="select-attending-btn" class="btn btn-default"><?php echo $translate->_("Click here to select an assessor") ?>&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>
                    <?php if ($options["can_request_preceptor_access"]) : ?>
                        <a href="#preceptor-access-request-modal" id="request-preceptor-access-btn" data-toggle="modal" class="btn btn-default"><?php echo $translate->_("Request Preceptor Access") ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div id="disabled-controls">
                <div class="disabled-overlay">
                    <span><?php echo $translate->_("Please select an assessor in order to continue.") ?></span>
                </div>
                <?php if ($course_count == 1) : ?>
                    <input id="course-id" type="hidden" name="course_id" value="<?php echo $only_course_id ?>" data-objective-tools="<?php echo ($has_tool_objectives ? "true" : "false") ?>"/>
                <?php else : ?>
                    <div id="course-selector" class="control-group">
                        <label for="course-id" class="control-label"><?php echo $translate->_("Select Program") ?></label>
                        <div class="controls">
                            <select id="course-id" name="course_id">
                                <option value="0"><?php echo $translate->_("-- Select program --") ?></option>
                                <?php foreach ($options["user_courses"] as $user_course) : ?>
                                    <option value="<?php echo $user_course["course_id"] ?>"<?php echo $user_course["course_id"] == $course_preference_id ? " selected" : "" ?> data-objective-tools="<?php echo ($user_course["has_tool_objectives"] ? "true" : "false") ?>"><?php echo html_encode($user_course["course_name"]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="control-group">
                    <label class="control-label"><?php echo $translate->_("Select assessment method") ?></label>
                    <div class="controls">
                        <?php if ($options["assessment_methods"]) : ?>
                            <?php foreach ($options["assessment_methods"] as $key => $assessment_method) : ?>
                                <label for="<?php echo html_encode($assessment_method["shortname"]) ?>" class="radio assessment-method <?php echo ($assessment_method["display"] ? "" : " hide") ?> <?php echo html_encode("assessment-method-" . $assessment_method["assessment_method_id"]) ?>">
                                    <input id="<?php echo html_encode($assessment_method["shortname"]) ?>" type="radio" name="assessment_method" data-shortname="<?php echo html_encode($assessment_method["shortname"]) ?>" value="<?php echo $assessment_method["assessment_method_id"] ?>" <?php echo($assessment_method["shortname"] == $default_method ? "checked=\"checked\"" : "") ?>>
                                    <span class="assessment-type-title"><?php echo html_encode($assessment_method["title"]) ?></span>
                                    <span class="assessment-type-description muted"><?php echo html_encode($assessment_method["description"]) ?></span>
                                    <span class="pin-warning muted hide"><?php echo html_encode($translate->_("The selected attending has not set their PIN.")) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <label for="no-methods-span">
                                <span id="no-methods-span"><?php echo html_encode($translate->_("No assessment methods defined.")) ?></span>
                            </label>

                        <?php endif; ?>
                    </div>
                </div>
                <?php echo $this->renderDateOfEncounter($options["course_requires_date_of_encounter"]); ?>
                <div class="control-group epa-selector-div <?php echo (($course_count == 1 || $course_preference_id) && ($options["course_requires_epas"])) ?  "preload-epas" : "hide"?>">
                    <label for="select-epa-btn" class="control-label"><?php echo $translate->_("Select an EPA") ?></label>
                    <div class="controls">
                        <button id="select-epa-btn" class="btn btn-default"><?php echo $default_epa_select_button_text ?></button>
                        <a class="epa-help space-left" href="<?php echo ENTRADA_URL . "/cbme/encyclopedia"?>" target="_blank" data-toggle="tooltip" data-original-title="<?php echo $translate->_("Click here for more information on EPAs"); ?>" data-placement="bottom"><i class="fa fa-question-circle fa-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="control-group hide space-below" id="assessment-tools">
                    <label for="search-assessment-tools" class="control-label"><?php echo $translate->_("Assessment Tools") ?></label>
                    <div class="controls">
                        <?php $this->renderAssessmentSearch(); ?>
                        <div id="assessment-tool-loading" class="hide">
                            <img src="<?php echo ENTRADA_URL . "/images/loading_small.gif" ?>" alt="<?php echo $translate->_("Loading assessment tools") ?>"/> <?php echo $translate->_("Loading assessment tools") ?>
                        </div>
                        <ul id="assessment-tool-list" class="user-list-card"></ul>
                        <div class="no-results-container"></div>
                    </div>
                </div>
            </div>
        </form>
        <div id="assessment-tool-form-preview" class="row-fluid space-below inner-content"></div>
            <?php $this->renderAssessmentCueModal(); ?>
        <?php
    }

    private function renderAssessmentBackfillView($options) {
        global $translate;
        $default_course_select_button_text = $this->renderDefaultCourseSelectButtonTextContainer();
        $default_epa_select_button_text = $this->renderDefaultEPASelectButtonTextContainer();
        $assessment_method_id = array_key_exists("assessment_method_id", $options) ? $options["assessment_method_id"] : null;
        if (!$assessment_method_id) {
            $this->renderError();
            return;
        }
        ?>
        <form id="assessment-tool-form" class="form-horizontal space-above large">
            <input type="hidden" name="referrer" value="backfill-assessment"/>
            <input type="hidden" name="proxy_id" value="0"/>
            <input type="radio" checked="checked" class="hide" name="assessment_method" id="assessment_method" value="<?php echo $assessment_method_id ?>"/>
            <div id="assessment-tool-msgs" class="hide"></div>
            <div class="control-group">
                <label for="select-my-learners-btn" class="control-label"><?php echo $translate->_("Select Resident") ?></label>
                <div class="controls">
                    <button id="select-my-learners-btn" class="btn btn-default"><?php echo $translate->_("Click here to select a resident") ?>&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>
                </div>
            </div>
            <div class="control-group">
                <label for="select-attending-btn" class="control-label"><?php echo $translate->_("Select Assessor") ?></label>
                <div class="controls">
                    <button id="select-attending-btn" class="btn btn-default"><?php echo $translate->_("Click here to select an assessor") ?>&nbsp;<i class="icon-chevron-down pull-right btn-icon"></i></button>
                    <?php if ($options["can_request_preceptor_access"]) : ?>
                        <a href="#preceptor-access-request-modal" id="request-preceptor-access-btn" data-toggle="modal" class="btn btn-default"><?php echo $translate->_("Request Preceptor Access") ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div id="disabled-controls">
                <div class="control-group hide course-selector-div">
                    <label for="cbme-course-picker" class="control-label"><?php echo html_encode($translate->_("Select a Program")) ?></label>
                    <div class="controls">
                        <button id="cbme-course-picker" class="btn btn-default"><?php echo $default_course_select_button_text ?></button>
                    </div>
                </div>
                <?php echo $this->renderDateOfEncounter($options["course_requires_date_of_encounter"]); ?>
                <div class="control-group hide epa-selector-div">
                    <label for="select-epa-btn" class="control-label"><?php echo $translate->_("Select an EPA") ?></label>
                    <div class="controls">
                        <button id="select-epa-btn" class="btn btn-default"><?php echo $default_epa_select_button_text ?></button>
                        <a class="epa-help space-left" href="<?php echo ENTRADA_URL . "/cbme/encyclopedia"?>" target="_blank" data-toggle="tooltip" data-original-title="<?php echo $translate->_("Click here for more information on EPAs"); ?>" data-placement="bottom"><i class="fa fa-question-circle fa-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="control-group hide space-below" id="assessment-tools">
                    <label for="search-assessment-tools" class="control-label"><?php echo $translate->_("Assessment Tools") ?></label>
                    <div class="controls">
                        <?php $this->renderAssessmentSearch(); ?>
                        <div id="assessment-tool-loading" class="hide">
                            <img src="<?php echo ENTRADA_URL . "/images/loading_small.gif" ?>" alt="<?php echo $translate->_("Loading assessment tools") ?>"/> <?php echo $translate->_("Loading assessment tools") ?>
                        </div>
                        <ul id="assessment-tool-list" class="user-list-card"></ul>
                        <div class="no-results-container"></div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    private function renderDateOfEncounter($course_requires_date_of_encounter = true) {
        global $translate;
        ?>
        <div class="control-group">
            <div id="date-of-encounter-container" <?php ($course_requires_date_of_encounter ? "" : "class=\"hide\"") ?>>
                <label for="date-of-encounter" class="control-label"><?php echo html_encode($translate->_("Select Date of Encounter")) ?></label>
                <div class="controls input-append display-block space-below medium">
                    <input id="date-of-encounter" type="text" name="date_of_encounter" class="datepicker" value="<?php echo(isset($options["filters"]["start_date"]) ? date("Y-m-d", $options["filters"]["start_date"]) : "") ?>"/>
                    <span class="add-on pointer">
                        <i class="icon-calendar"></i>
                    </span>
                </div>
            </div>
    <?php
    }

    private function renderAssessmentSearch() {
        global $translate;
        ?>
        <div class="entrada-search-widget space-below">
            <input id="search-assessment-tools" type="text" id="assessment-search" placeholder="<?php echo $translate->_("Search Assessment Tools..."); ?>" value="" class="input-large search-icon" data-append="false">
        </div>
        <?php
    }

    private function renderAssessmentCueModal() {
        global $translate;
        ?>
            <div id="assessment-cue-modal" style="display: none" class="modal fade">
                <div class="modal-header">
                    <h2><?php echo $translate->_("Assessment Cue"); ?></h2>
                </div>
                <form class="form-vertical" id="slot-info">
                    <div class="modal-body form-vertical">
                        <div class="control-group">
                            <label for="assessment-cue-text" class="form-label"><?php echo $translate->_("Assessment Cue (optional):"); ?></label>
                            <div class="form-control">
                                <textarea rows="10" cols="50" name="assessment-cue" id="assessment-cue-text" class="cue-text-area" form="assessment-cue-form"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-dismiss="modal" class="btn btn-default pull-left"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" data-form-id="" data-form-count="" data-epa-objective-id="" data-form-type="" class="all-assessments assessment-tool-btn btn btn-primary" id="send-assessment-button" data-trigger-action="begin"><?php echo $translate->_("Send Assessment") ?></a>
                    </div>
                </form>
            </div>
        <?php
    }

    /**
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     *
     * @param $course_epas
     * @param $module
     */
    protected function addHeadScripts($course_epas, $module, $preset_filters = array(), $course_requires_epas = true) {
        global $translate, $BREADCRUMB, $HEAD, $JAVASCRIPT_TRANSLATIONS;
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $module, "title" => $translate->_("Assessment Tools"));

        if ($preset_filters) {
            $HEAD[] = "<script type=\"text/javascript\">var preset_filters = '" . addslashes(json_encode($preset_filters)) . "';</script>";
        }

        $HEAD[] = "<script type=\"text/javascript\">var course_epas = '" . addslashes(json_encode($course_epas)) . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/assessment-tools.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">var course_requires_epas = ". json_encode($course_requires_epas) .";</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/assessment-tools.css?release=" . html_encode(APPLICATION_VERSION). "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-form.css?release=" . html_encode(APPLICATION_VERSION). "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/items.css?release=" . html_encode(APPLICATION_VERSION). "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/rubrics.css?release=" . html_encode(APPLICATION_VERSION). "\" />";
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();

        $JAVASCRIPT_TRANSLATIONS[] = "var assessment_tools = {};";
        $JAVASCRIPT_TRANSLATIONS[] = "assessment_tools.json_error = '" . addslashes($translate->_("A problem occurred while attempting to create an access request for this user. Please try again later.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "assessment_tools.preceptor_exists = '" . addslashes($translate->_("We found an active account for this preceptor and they have automatically been selected as the assessor.")) . "';";
        $JAVASCRIPT_TRANSLATIONS[] = "assessment_tools.no_results_message = '" . addslashes($translate->_("No assessment tools found")) . "';";
        Entrada_Utilities::addJavascriptTranslation("Curriculum Period", "curriculum_periods");
        Entrada_Utilities::addJavascriptTranslation("Residents", "my_residents");
        Entrada_Utilities::addJavascriptTranslation("No Results Found", "no_results");
    }
}