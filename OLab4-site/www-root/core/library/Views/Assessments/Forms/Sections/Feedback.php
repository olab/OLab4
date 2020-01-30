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
 * View class for rendering feedback capture elements on a form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_Feedback extends Views_Assessments_Forms_Sections_Base {

    /**
     * Validate required view options.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("actor_id", "actor_type", "feedback_actor_is_target", "assessment_complete", "assessor_id", "assessor_type"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $actor_id                   = $options["actor_id"];
        $actor_type                 = $options["actor_type"];
        $assessor_id                = $options["assessor_id"];
        $assessor_type              = $options["assessor_type"];
        $assessment_complete        = $options["assessment_complete"];
        $feedback_actor_is_target   = $options["feedback_actor_is_target"];

        $edit_state                 = array_key_exists("edit_state", $options) ? $options["edit_state"] : null; // boolean; null if ignore this value.
        $include_preceptor_label    = array_key_exists("include_preceptor_label", $options) ? $options["include_preceptor_label"] : false;
        $is_pdf                     = array_key_exists("is_pdf", $options) ? $options["is_pdf"] : false;

        $assessor_feedback_value    = array_key_exists("assessor_feedback", $options) ? $options["assessor_feedback"] : null;
        $target_feedback_value      = array_key_exists("target_feedback", $options) ? $options["target_feedback"] : null;
        $hide_target_comments       = array_key_exists("hide_target_comments", $options) ? $options["hide_target_comments"] : false;
        $target_feedback_comments   = array_key_exists("comments", $options) ? $options["comments"] : null;
        $target_feedback_progress   = array_key_exists("target_progress_value", $options) ? $options["target_progress_value"] : null;

        $disabled_text = "disabled='disabled'";
        $target_disabled_text = "";
        $assessor_disabled_text = "";

        $display_target_feedback = false;

        // If the assessment is complete, then we display no target feedback, only the assessor feedback window
        if ($assessment_complete) {
            $assessor_disabled_text = $disabled_text;
            if ($target_feedback_progress == "complete") {
                $display_target_feedback = true;
                $target_disabled_text = $disabled_text;
            } else if ($feedback_actor_is_target) {
                $display_target_feedback = true;
            }
        }
        if ($edit_state) {
            // If a particular state was specified, we determine what it was to set editability.
            // This forced state applies to both assessor and target feedback fields.
            if ($edit_state == "editable" || $edit_state == "edit") {
                $target_disabled_text = $assessor_disabled_text = "";
            } else if ($edit_state == "readonly") {
                $target_disabled_text = $assessor_disabled_text = $disabled_text;
            }
        }
        ?>
        <h3 class="assessment-feedback-heading"><?php echo $translate->_("Assessment Feedback") ?></h3>
        <div class="form-item">
            <div class="item-container">
                <table class="item-table">
                    <tbody>
                    <tr class="heading">
                        <td colspan="2">
                            <?php if ($feedback_actor_is_target || $include_preceptor_label): // The assessed/target ?>
                                <h3 id="assessor-feedback-question-text" data-feedback-question-text=""><?php echo $translate->_("<strong>Preceptor response: </strong>Did you meet with this trainee to discuss their performance?") ?></h3>
                            <?php else: // The assessor ?>
                                <h3 id="assessor-feedback-question-text" data-feedback-question-text="<?php echo html_encode($translate->_("Did you meet with this trainee to discuss their performance?")) ?>"><?php echo $translate->_("Did you meet with this trainee to discuss their performance?") ?></h3>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="vertical-choice-row item-response-view">
                        <td class="vertical-response-input <?php echo $assessor_disabled_text && $assessor_feedback_value == "1" ? "selected-response" : "" ?>" width="5%">
                            <input  id="assessor_feedback_yes"
                                    type="radio"
                                    name="assessor_feedback_response"
                                    value="yes"
                                    <?php echo $assessor_disabled_text ? "class=\"hide\"" : "" ?>
                                    <?php echo $assessor_feedback_value == "1" ? "checked='checked'" : "" ?>
                                    <?php echo $assessor_disabled_text ?>
                            />
                        </td>
                        <td class="vertical-response-label" width="95%">
                            <label for="assessor_feedback_yes">
                                <?php echo $translate->_("Yes") ?>
                            </label>
                        </td>
                    </tr>
                    <tr class="vertical-choice-row  item-response-view">
                        <td class="vertical-response-input <?php echo $assessor_disabled_text && $assessor_feedback_value == "0" ? "selected-response" : "" ?>" width="5%">
                            <input  id="assessor_feedback_no"
                                    type="radio"
                                    name="assessor_feedback_response"
                                    value="no"
                                    <?php echo $assessor_disabled_text ? "class=\"hide\"" : "" ?>
                                    <?php echo $assessor_feedback_value == "0" ? "checked='checked'" : "" ?>
                                    <?php echo $assessor_disabled_text ?>
                            />
                        </td>
                        <td class="vertical-response-label" width="95%">
                            <label for="assessor_feedback_no">
                                <?php echo $translate->_("No") ?>
                            </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($display_target_feedback && !$hide_target_comments): // Only display learner feedback if the feedback is complete. It should be disabled for everyone but the learner themselves ?>

            <div class="form-item">
                <div class="item-container">
                    <table class="item-table">
                        <tbody>
                        <tr class="heading">
                            <td colspan="2">
                                <h3 id="target-feedback-question-text" data-feedback-question-text="<?php echo html_encode($translate->_("Did you meet with your preceptor to discuss your performance?")) ?>"><?php echo $translate->_("Did you meet with your preceptor to discuss your performance?") ?></h3>
                            </td>
                        </tr>
                        <tr class="vertical-choice-row item-response-view">
                            <td class="vertical-response-input <?php echo $target_disabled_text &&  $target_feedback_value == "1" ? "selected-response" : "" ?>" width="5%">
                                <input  id="target_feedback_yes"
                                        type="radio"
                                        name="target_feedback_response"
                                        value="yes"
                                        <?php echo $target_disabled_text ? "class=\"hide\"" : "" ?>
                                        <?php echo $target_feedback_value == "1" ? "checked='checked'" : "" ?>
                                        <?php echo $target_disabled_text ?>
                                />
                            </td>
                            <td class="vertical-response-label" width="95%">
                                <label for="target_feedback_yes">
                                    <?php echo $translate->_("Yes") ?>
                                </label>
                            </td>
                        </tr>
                        <tr class="vertical-choice-row item-response-view">
                            <td class="vertical-response-input <?php echo $target_disabled_text &&  $target_feedback_value == "0" ? "selected-response" : "" ?>" width="5%">
                                <input  id="target_feedback_no"
                                        type="radio"
                                        name="target_feedback_response"
                                        value="no"
                                        <?php echo $target_disabled_text ? "class=\"hide\"" : "" ?>
                                        <?php echo $target_feedback_value == "0" ? "checked='checked'" : "" ?>
                                        <?php echo $target_disabled_text ?>
                                />
                            </td>
                            <td class="vertical-response-label" width="95%">
                                <label for="target_feedback_no">
                                    <?php echo $translate->_("No") ?>
                                </label>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="form-item">
                <div class="item-container">
                    <table class="item-table">
                        <tbody>
                        <tr class="heading">
                            <td colspan="2">
                                <h3><?php echo $translate->_("Comments (optional)") ?></h3>
                            </td>
                        </tr>
                        <tr class="item-response-view">
                            <td class="item-type-control">
                                <?php if ($is_pdf): ?>
                                    <p class="text-left"><?php echo $target_feedback_comments ? nl2br($target_feedback_comments) : ""; ?></p>
                                <?php else: ?>
                                    <textarea class="expandable" title="<?php echo $translate->_("Comments") ?>" name="feedback_meeting_comments" <?php echo $target_disabled_text ?>><?php echo $target_feedback_comments ? html_encode($target_feedback_comments) : "" ?></textarea>
                                <?php endif; ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

        <input type="hidden" name="feedback_actor_id" value="<?php echo $actor_id ?>" />
        <input type="hidden" name="feedback_actor_type" value="<?php echo $actor_type ?>" />
        <input type="hidden" name="feedback_assessor_id" value="<?php echo $assessor_id ?>" />
        <input type="hidden" name="feedback_assessor_type" value="<?php echo $assessor_type ?>" />
        <?php
    }
}