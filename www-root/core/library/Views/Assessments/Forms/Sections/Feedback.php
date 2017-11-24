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
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $target_record_id     = $options["target_record_id"];
        $distribution         = $options["distribution"];
        $hide_from_approver   = $options["hide_from_approver"];
        $actor_proxy_id       = $options["actor_proxy_id"];
        $progress_record      = $options["progress_record"];
        $assessment_record    = $options["assessment_record"];
        $feedback_record      = $options["feedback_record"];
        $is_pdf               = isset($options["is_pdf"]) ? $options["is_pdf"] : false;
        $use_disable_override = false;
        $disable_override     = false;
        if (array_key_exists("disabled", $options)) {
            $use_disable_override = true;
            $disable_override = $options["disabled"];
        }

        // Only attempt to render progress for logged in users.
        // Distributions do not allow external feedback, but you can still add an external as an additional task to a distribution that does allow feedback.
        ?>
        <?php if ($actor_proxy_id): ?>

            <?php if ($assessment_record && !$hide_from_approver): ?>

                <?php if ($assessment_record->getAssessorValue() == $actor_proxy_id): // The actor is the assessor ?>

                    <?php
                        $disabled_text = "";
                        if (($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate())) {
                            $disabled_text = "disabled";
                        }
                        if ($use_disable_override) {
                            $disabled_text = ($disable_override) ? "disabled" : "";
                        }
                    ?>
                    <!-- // Note that the submit buttons for this case will be rendered as part of the overall assessment form, as it is the preceptor completing feedback on a target. -->
                    <h3 class="assessment-feedback-heading"><?php echo $translate->_("Assessment Feedback") ?></h3>
                    <div class="form-item">
                        <div class="item-container">
                            <table class="item-table">
                                <tbody>
                                <tr class="heading">
                                    <td colspan="2">
                                        <h3><?php echo $translate->_("Did you meet with this trainee to discuss their performance?") ?></h3>
                                    </td>
                                </tr>
                                <tr class="vertical-choice-row">
                                    <?php $checked_yes_text = ($feedback_record && $feedback_record->getAssessorFeedback() == "1") ? 'checked="checked"' : "";?>
                                    <td class="vertical-response-input" width="5%">
                                        <input id="assessor_feedback_yes"
                                               type="radio"
                                               name="assessor_feedback_response"
                                               value="yes"
                                            <?php echo $checked_yes_text ?>
                                            <?php echo $disabled_text ?>
                                        />
                                    </td>
                                    <td class="vertical-response-label" width="95%">
                                        <label for="feedback_yes">
                                            <?php echo $translate->_("Yes") ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="vertical-choice-row">
                                    <?php $checked_no_text = $feedback_record && $feedback_record->getAssessorFeedback() == "0" ? 'checked="checked"' : ""; ?>
                                    <td class="vertical-response-input" width="5%">
                                        <input id="assessor_feedback_no"
                                               type="radio"
                                               name="assessor_feedback_response"
                                               value="no"
                                            <?php echo $checked_no_text ?>
                                            <?php echo $disabled_text ?>
                                        />
                                    </td>
                                    <td class="vertical-response-label" width="95%">
                                        <label for="feedback_no">
                                            <?php echo $translate->_("No") ?>
                                        </label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php else: // The actor is not the assessor ?>
                    <!-- Display all feedback for non-preceptors (learner, PAs, distribution authors, etc.) -->

                    <?php if (!$progress_record || $progress_record->getAssessorType() == "internal"): ?>

                        <?php
                            //$disabled_text = ($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate()) ? "disabled" : "";
                            $disabled_text = $use_disable_override ? ($disable_override ? "disabled" : "") : "disabled";
                        ?>
                        <h3 class="assessment-feedback-heading"><?php echo $translate->_("Assessment Feedback") ?></h3>

                        <div class="form-item">
                            <div class="item-container">
                                <table class="item-table">
                                    <tbody>
                                    <tr class="heading">
                                        <td colspan="2">
                                            <h3><?php echo $translate->_("<strong>Preceptor response: </strong>Did you meet with this trainee to discuss their performance?") ?></h3>
                                        </td>
                                    </tr>
                                    <tr class="vertical-choice-row">
                                        <td class="vertical-response-input" width="5%">
                                            <?php $checked_yes_text = ($feedback_record && $feedback_record->getAssessorFeedback() == "1") ? 'checked="checked"' : "";?>
                                            <input id="assessor_feedback_yes"
                                                   type="radio"
                                                   name="assessor_feedback_response"
                                                   value="yes"
                                                   <?php echo $checked_yes_text ?>
                                                   <?php echo $disabled_text ?> />
                                        </td>
                                        <td class="vertical-response-label" width="95%">
                                            <label for="assessor_feedback_yes">
                                                <?php echo $translate->_("Yes") ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr class="vertical-choice-row">
                                        <td class="vertical-response-input" width="5%">
                                            <?php $checked_no_text = ($feedback_record && $feedback_record->getAssessorFeedback() == "0") ? 'checked="checked"' : ""; ?>
                                            <input id="assessor_feedback_no"
                                                   type="radio"
                                                   name="assessor_feedback_response"
                                                   value="no"
                                                   <?php echo $checked_no_text ?>
                                                   <?php echo $disabled_text ?> />
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

                        <!-- // Only display learner feedback if the feedback is complete. It should be disabled for everyone but the learner themselves -->
                        <?php if ($progress_record && $progress_record->getProgressValue() == "complete") : ?>
                            <?php
                            $disabled_text = "";
                            if (($feedback_record && $feedback_record->getTargetProgressValue() == "complete") || ($distribution->getDeletedDate() || $target_record_id != $actor_proxy_id)) {
                                $disabled_text = "disabled";
                            }
                            if ($use_disable_override) {
                                $disabled_text = ($disable_override) ? "disabled" : "";
                            }
                            ?>
                            <div class="form-item">
                                <div class="item-container">
                                    <table class="item-table">
                                        <tbody>
                                        <tr class="heading">
                                            <td colspan="2">
                                                <h3><?php echo $translate->_("Did you meet with your preceptor to discuss your performance?") ?></h3>
                                            </td>
                                        </tr>
                                        <tr class="vertical-choice-row">
                                            <?php $checked_yes_text = $feedback_record && $feedback_record->getTargetFeedback() == "1" ? 'checked="checked"' : "";?>
                                            <td class="vertical-response-input" width="5%">
                                                <input  id="feedback_yes"
                                                        type="radio"
                                                        name="feedback_response"
                                                        value="yes"
                                                        <?php echo $checked_yes_text ?>
                                                        <?php echo $disabled_text ?>
                                                />
                                            </td>
                                            <td class="vertical-response-label" width="95%">
                                                <label for="feedback_yes">
                                                    <?php echo $translate->_("Yes") ?>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr class="vertical-choice-row">
                                            <?php $checked_no_text = $feedback_record && $feedback_record->getTargetFeedback() == "0" ? 'checked="checked"' : ""; ?>
                                            <td class="vertical-response-input" width="5%">
                                                <input  id="feedback_no"
                                                        type="radio"
                                                        name="feedback_response"
                                                        value="no"
                                                        <?php echo $checked_no_text ?>
                                                        <?php echo $disabled_text ?>
                                                />
                                            </td>
                                            <td class="vertical-response-label" width="95%">
                                                <label for="feedback_no">
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
                                                <?php if (!$is_pdf) { ?>
                                                    <textarea class="expandable" name="feedback_meeting_comments" <?php echo $disabled_text ?>><?php if ($feedback_record && $feedback_record->getComments()): ?><?php echo html_encode($feedback_record->getComments()) ?><?php endif; ?></textarea>
                                                <?php } else { ?>
                                                    <p style='text-align:left'><?php echo ($feedback_record && $feedback_record->getComments() ? nl2br($feedback_record->getComments()) : ""); ?></p>
                                                 <?php } ?>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- // Display submission buttons for target learners when the feedback has not been completed. -->
                            <?php if (!$is_pdf && $target_record_id == $actor_proxy_id && (!$feedback_record || ($feedback_record && $feedback_record->getTargetProgressValue() != "complete"))): ?>
                                <div class="row-fluid">
                                    <div class="pull-right">
                                        <input type="submit" id="save-form" class="btn btn-warning" name="save_form_progress" value="<?php echo $translate->_("Save as Draft")?>" />
                                        <span class="or"><?php echo $translate->_("or") ?></span>
                                        <input class="btn btn-primary" type="submit" id="submit_form" name="submit_form" value="<?php echo $translate->_("Submit") ?>" />
                                    </div>
                                </div>

                            <?php endif; ?>

                        <?php endif; ?>

                    <?php endif; ?>

                <?php endif; ?>

            <?php endif; ?>
            <input type="hidden" name="feedback_proxy_id" value="<?php echo html_encode($actor_proxy_id) ?>" />
        <?php endif; ?>
        <?php
    }

}