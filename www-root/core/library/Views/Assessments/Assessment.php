<?php

class Views_Assessments_Assessment extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateArrayNotEmpty(
                $options,
                array(
                    "assessment_data",
                    "form_data",
                    "current_target"
                )
            )
        ) {
            return false;
        }
        if (!$this->validateIsSet(
                $options,
                array(
                    "dassessment_id",
                    "actor_proxy_id",
                    "actor_type",
                    "actor_scope",
                    "actor_organisation_id"
                )
            )
        ) {
            return false;
        }
        if (!$this->validateIsSet(
                $options["form_data"],
                array(
                    "elements",
                    "progress",
                    "rubrics",
                    "meta"
                )
            )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Render an assessment form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $actor_proxy_id                 = $options["actor_proxy_id"];
        $actor_organisation_id          = $options["actor_organisation_id"];
        $actor_type                     = $options["actor_type"];
        $actor_scope                    = $options["actor_scope"];
        $actor_group                    = $options["actor_group"];
        $dassessment_id                 = $options["dassessment_id"];
        $assessment_data                = $options["assessment_data"];
        $form_data                      = $options["form_data"];
        $current_progress               = $options["current_progress"];
        $current_target                 = $options["current_target"];
        $atarget_id                     = Entrada_Utilities::arrayValueOrDefault($current_target, "atarget_id");
        $target_name                    = Entrada_Utilities::arrayValueOrDefault($current_target, "target_name");
        $target_record_id               = Entrada_Utilities::arrayValueOrDefault($current_target, "target_record_id");
        $target_type                    = Entrada_Utilities::arrayValueOrDefault($current_target, "target_type");
        $target_scope                   = Entrada_Utilities::arrayValueOrDefault($current_target, "target_scope");
        $aprogress_id                   = Entrada_Utilities::arrayValueOrDefault($options, "aprogress_id");
        $disabled                       = Entrada_Utilities::arrayValueOrDefault($options, "disabled", false);
        $external_hash                  = Entrada_Utilities::arrayValueOrDefault($options, "external_hash", "");
        $action_url                     = Entrada_Utilities::arrayValueOrDefault($options, "action_url", ENTRADA_URL);
        $assessment_mode                = Entrada_Utilities::arrayValueOrDefault($options, "assessment_mode", "internal"); // internal or external
        $assessment_render_mode         = Entrada_Utilities::arrayValueOrDefault($options, "assessment_render_mode", "assessment"); // e.g., pdf, assessment, assessment-completed
        $assessment_error_messages      = Entrada_Utilities::arrayValueOrDefault($options, "assessment_error_messages", array());
        $deletion_reasons               = Entrada_Utilities::arrayValueOrDefault($options, "deletion_reasons", array());
        $objectives                     = Entrada_Utilities::arrayValueOrDefault($options, "objectives", array());
        $assessment_target_list         = Entrada_Utilities::arrayValueOrDefault($options, "assessment_target_list", array());
        $approval_data                  = Entrada_Utilities::arrayValueOrDefault($options, "approval_data", array());
        $approval_pending               = Entrada_Utilities::arrayValueOrDefault($options, "approval_pending", false);
        $approval_required              = Entrada_Utilities::arrayValueOrDefault($options, "approval_required",false);
        $approver_fullname              = Entrada_Utilities::arrayValueOrDefault($options, "approver_fullname", "");
        $approval_time                  = Entrada_Utilities::arrayValueOrDefault($options, "approval_time", time());
        $feedback_required              = Entrada_Utilities::arrayValueOrDefault($options, "feedback_required", false);
        $feedback_options               = Entrada_Utilities::arrayValueOrDefault($options, "feedback_options", array());
        $pin_assessor_id                = Entrada_Utilities::arrayValueOrDefault($options, "pin_assessor_id", false);
        $pin_is_required                = Entrada_Utilities::arrayValueOrDefault($options, "pin_is_required", false);
        $is_distribution_deleted        = Entrada_Utilities::arrayValueOrDefault($options, "is_distribution_deleted", false);
        $assessment_completed           = Entrada_Utilities::arrayValueOrDefault($options, "assessment_completed", false);
        $cannot_complete                = Entrada_Utilities::arrayValueOrDefault($options, "cannot_complete", false);
        $submit_on_behalf               = Entrada_Utilities::arrayValueOrDefault($options, "submit_on_behalf", false);
        $can_download                   = Entrada_Utilities::arrayValueOrDefault($options, "can_download", true);
        $can_manage                     = Entrada_Utilities::arrayValueOrDefault($options, "can_manage", false);
        $can_forward                    = Entrada_Utilities::arrayValueOrDefault($options, "can_forward", false);
        $can_delete                     = Entrada_Utilities::arrayValueOrDefault($options, "can_delete", false);
        $actor_is_assessor              = Entrada_Utilities::arrayValueOrDefault($options, "actor_is_assessor", false);
        $actor_is_approver              = Entrada_Utilities::arrayValueOrDefault($options, "actor_is_approver", false);
        $actor_can_complete_assessment  = Entrada_Utilities::arrayValueOrDefault($options, "actor_can_complete_assessment", false);
        $show_forms_to_complete_message = Entrada_Utilities::arrayValueOrDefault($options, "show_forms_to_complete_message", false);
        $render_form                    = Entrada_Utilities::arrayValueOrDefault($options, "render_form", true);
        $render_submission_buttons      = Entrada_Utilities::arrayValueOrDefault($options, "render_submission_buttons", true);
        $submit_button_id               = Entrada_Utilities::arrayValueOrDefault($options, "submit_button_id", "submit_form");
        $submit_button_text             = Entrada_Utilities::arrayValueOrDefault($options, "submit_button_text", $translate->_("Submit"));
        $prerendered_subheader_html     = Entrada_Utilities::arrayValueOrDefault($options, "subheader_html", "");
        $form_mutators                  = Entrada_Utilities::arrayValueOrDefault($options, "form_mutators", array());
        if (is_string($assessment_error_messages)) {
            $assessment_error_messages = array($assessment_error_messages);
        }
        $this->addHeadScripts($assessment_mode);

        if ($assessment_render_mode == "error"): ?>

            <div class="alert alert-block alert-error">
                <ul>
                    <?php foreach ($assessment_error_messages as $error_message): ?>
                        <li><?php echo $error_message; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($render_form): ?>
            <div class="clearfix"></div>
            <h1 id="form-heading"><?php echo $form_data["form"]["title"]; ?></h1>
            <div id="msgs"></div>

            <div id="assessment-controls-button-row" class="space-below medium">
                <?php if ($can_download): ?>
                    <input type="checkbox" class="generate-pdf hide" style="display:none" value="1" checked="checked" data-aprogress-id="<?php echo $aprogress_id; ?>" data-dassessment-id="<?php echo $dassessment_id; ?>">
                <?php endif; ?>
                <?php if (!$assessment_data["meta"]["is_external"] && !$assessment_completed && $can_delete && $atarget_id): ?>
                    <a id="delete-task" href="#remove-tasks-modal" data-toggle="modal" data-atarget-id="<?php echo $atarget_id; ?>" class="btn btn-danger pull-right space-left medium"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task"); ?></a>
                <?php endif; ?>
                <?php if ($can_download): ?>
                    <a href="#"
                       name="generate-pdf"
                       class="btn btn-success generate-pdf-btn"><i class="icon-download-alt icon-white"></i> <?php echo $translate->_("Download PDF"); ?>
                    </a>
                <?php endif; ?>
                <?php if ($can_forward && !$assessment_completed && !$assessment_data["meta"]["is_external"]): ?>
                    <a id="forward-task" class="btn" data-toggle="modal" href="#forward-task-modal"><i class="icon-share-alt"></i> <?php echo $translate->_("Forward Task"); ?></a>
                <?php endif;
                if ($can_manage && $aprogress_id):
                    if ($assessment_completed) : ?>
                        <a id="reopen-task" href="#reopen-task-modal" data-toggle="modal" class="btn btn-warning"><i class="icon-refresh icon-white"></i> <?php echo $translate->_("Reopen Task"); ?></a>
                    <?php else: ?>
                        <a id="clear-task-progress" href="#clear-task-progress-modal" data-toggle="modal" class="btn btn-warning"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Clear Task Progress"); ?></a>
                    <?php endif;
                endif; ?>
            </div>

            <?php if ($assessment_completed && $approval_required):
                if (!$approval_pending): ?>
                    <div class="alert alert-info text-center">
                        <strong>
                            <?php if ($actor_is_approver && $approval_data["approver_proxy_id"] == $actor_proxy_id): ?>
                                <?php echo sprintf($translate->_("You reviewed this task on %s."), date("Y-m-d", $approval_time)); ?>
                            <?php else: ?>
                                <?php echo sprintf($translate->_("This task was reviewed by %s on %s."), html_encode($approver_fullname), date("Y-m-d", $approval_time)); ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                <?php elseif (!$actor_is_approver && $approval_pending): ?>
                    <div class="alert alert-info text-center">
                        <strong>
                            <?php echo $translate->_("This task is awaiting review. It will only be accessible by the target if it is approved."); ?>
                        </strong>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div id="msgs"></div>
            <?php
            if (isset($assessment_data["assessment_options"])) {
                $options_view = new Views_Assessments_Assessment_Option();
                $options_view->render(array("assessment_options" => $assessment_data["assessment_options"]));
            }
            if (!$assessment_completed) {
                if (!empty($assessment_data["assessment_method_meta"])) {
                    // Render instructions, if any.
                    $assessment_instruction_view = new Views_Assessments_Assessment_MethodInstruction();
                    $assessment_instruction_view->render(array("instruction_text" => $assessment_data["assessment_method_meta"]["instructions"]));
                }
            }
            if ($show_forms_to_complete_message):
                if (count($assessment_data["targets"]) > 1 && $actor_can_complete_assessment):
                    // Show a blurb indicating how many forms are available to complete overall
                    $max_overall_attempts = $assessment_data["assessment"]["max_submittable"] * count($assessment_target_list);
                    $overall_attempts_completed = 0;
                    foreach ($assessment_target_list as $target_meta) {
                        $overall_attempts_completed += $target_meta["counts"]["complete"];
                    }
                    ?>
                    <p id="targets_remaining" class="muted">
                        <?php echo sprintf($translate->_("You have <strong>%s</strong> assessments to complete with this form."), ($max_overall_attempts - $overall_attempts_completed)); ?>
                    </p>
                <?php endif;
            endif; ?>
            <form id="assessment-form" action="<?php echo $action_url; ?>" method="post">
                <input type="hidden" id="aprogress_id" name="aprogress_id" value="<?php echo $aprogress_id; ?>"/>
                <input type="hidden" id="external_hash" name="external_hash" value="<?php echo $external_hash; ?>"/>
                <input type="hidden" id="dassessment_id" name="dassessment_id" value="<?php echo $dassessment_id ?>"/>
                <input type="hidden" id="atarget_id" name="atarget_id" value="<?php echo $atarget_id; ?>"/>
                <input type="hidden" id="target_record_id" name="target_record_id" value="<?php echo $target_record_id; ?>"/>
                <input type="hidden" id="target_type" name="target_type" value="<?php echo html_encode($target_type); ?>"/>
                <input type="hidden" id="target_scope" name="target_scope" value="<?php echo html_encode($target_scope); ?>"/>
                <div class="row-fluid">
                    <?php if ($is_distribution_deleted && $actor_can_complete_assessment && !$assessment_completed): ?>
                        <div class="alert alert-warning">
                            <?php echo $translate->_("This assessment task cannot be submitted because its <strong>Distribution</strong> has been deleted.") ?>
                        </div>
                    <?php elseif (!$assessment_completed): ?>
                        <?php if ($cannot_complete): ?>
                            <div class="alert alert-warning">
                                <?php echo $translate->_("You cannot complete this assessment because you are not this task's <strong>Assessor</strong>."); ?>
                            </div>
                        <?php elseif ($submit_on_behalf): ?>
                            <div class="alert alert-warning">
                                <?php
                                if ($assessment_data["assessor"]["full_name"]) {
                                    $name = sprintf(
                                        "<a class=\"user-email\" href=\"mailto:{$assessment_data["assessor"]["email"]}\" target =\"_top\">{$assessment_data["assessor"]["full_name"]}%s</a>",
                                        $assessment_data["assessor"]["email"] ? " ({$assessment_data["assessor"]["email"]})" : ""
                                    );
                                } else {
                                    $name = $translate->_("the assessor");
                                }
                                echo sprintf($translate->_("You are submitting this assessment <strong>on behalf of</strong> %s."), $name);
                                ?>
                            </div>
                        <?php endif;
                    endif;

                    $assessment_form_meta = new Views_Assessments_Assessment_MetaData();
                    $meta_data_html = $assessment_form_meta->render($form_data, false);

                    $form_view = new Views_Assessments_Forms_Form(array("mode" => $assessment_render_mode));
                    $form_view_options = array(
                        "form_id" => $form_data["meta"]["form_id"],
                        "disabled" => $disabled,
                        "elements" => $form_data["elements"],
                        "progress" => $form_data["progress"],
                        "rubrics" => $form_data["rubrics"],
                        "aprogress_id" => $aprogress_id,
                        "public" => true,
                        "objectives" => $objectives,
                        "form_mutators" => $form_mutators // e.g. ["invisibility"] <- this would trigger the invisibility mutator, hiding any items that are hideable/invisible
                    );
                    $rendered_assessment = $form_view->render($form_view_options, false);
                    $assessment_form_html = $meta_data_html . $rendered_assessment;

                    if ($feedback_required) {
                        $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                        $assessment_form_html .= $feedback_view->render($feedback_options, false);
                    }

                    // Make sure selected objectives are populated on page load
                    if (!empty($objectives)) {
                        foreach ($objectives as $afelement_id => $afelement_objectives) {
                            foreach ($afelement_objectives as $objective): ?>
                                <input type="hidden" name="afelement_objectives[<?php echo $afelement_id ?>][]" value="<?php echo $objective ?>" class="afelement-objective-<?php echo $afelement_id ?>"/>
                            <?php endforeach;
                        }
                    }

                    /**
                     * Output the pre-rendered subheader, if any.
                     **/

                    echo $prerendered_subheader_html;

                    /**
                     * Output the assessment form
                     **/

                    echo $assessment_form_html;

                    /**
                     * Render submission buttons if appropriate (displayed in context below)
                     **/

                    $submission_button_html = "";

                    if ($render_submission_buttons) {
                        $assessment_button_view = new Views_Assessments_Assessment_Controls_Buttons(array("id" => $submit_button_id));
                        $button_construction = array(); // default, no special options
                        if ($submit_button_text) {
                            $button_construction = array("button_text" => $submit_button_text);
                        }
                        if ($pin_is_required) {
                            $button_construction["button_classes"] = "assessment-show-pin-modal";
                        }
                        $button_construction["has_selections"] = Entrada_Assessments_Forms::hasSelectionBeenMade($assessment_data["progress"], $form_data["elements"]);
                        $method_meta = Models_Assessments_Method_Meta::fetchRowByAssessmentMethodIDGroup(
                            $assessment_data["assessment"]["assessment_method_id"],
                            $actor_group
                        );
                        $button_construction["is_learner"] = 0;
                        if ($method_meta) {
                            $button_construction["is_learner"] = $method_meta->getAssessmentCue() ? $method_meta->getAssessmentCue() : 0;
                        }
                        $submission_button_html = $assessment_button_view->render($button_construction, false);
                    }

                    /**
                     * Render the approval buttons, if required
                     **/

                    if ($approval_pending && $actor_is_approver && $assessment_completed): ?>

                        <div class="row-fluid">
                            <div class="pull-left">
                                <a href="<?php echo ENTRADA_URL; ?>/assessments" id="return_to_my_assessments" class="btn" name="return_to_my_assessments"><?php echo $translate->_("Return"); ?></a>
                            </div>
                            <div class="pull-right padding-left">
                                <input type="button" id="hide_form" class="btn btn-warning" name="hide_form" value="<?php echo $translate->_("Hide Form"); ?>"/>
                                <span class="or"><?php echo $translate->_("or") ?></span>
                                <input type="button" id="release_form" class="btn btn-primary" name="release_form" value="<?php echo $translate->_("Approve Form"); ?>"/>
                            </div>
                            <?php echo $submission_button_html; ?>
                        </div>

                    <?php else:

                        // Display the standard submission buttons (if appropriate)
                        echo $submission_button_html;

                    endif; ?>
                </div>
                <?php $this->renderAssessmentCueModal(); ?>
            </form>
            <?php

            /**
             * Render modals where appropriate
             **/

            if ($pin_is_required) {
                $enter_pin_modal = new Views_Assessments_Modals_EnterPIN();
                $enter_pin_modal->render(array("pin_assessor_id" => $pin_assessor_id));
            }
            if ($actor_is_approver) {
                $hide_assessment_task_modal = new Views_Assessments_Modals_HideAssessmentTask();
                $hide_assessment_task_modal->render();
            }
            if ($can_manage && $aprogress_id) {
                $reopen_task_modal = new Views_Assessments_Modals_ReopenTask();
                $reopen_task_modal->render(array("aprogress_id" => $aprogress_id));
                $clear_task_progress_modal = new Views_Assessments_Modals_ClearTaskProgress();
                $clear_task_progress_modal->render(array("aprogress_id" => $aprogress_id));
            }
            if ($can_forward && !$assessment_completed) {
                $forward_task_modal = new Views_Assessments_Modals_ForwardTask();
                $forward_task_modal->render(
                    array(
                        "dassessment_id" => $dassessment_id,
                        "target_type" => $current_target["target_type"],
                        "target_record_id" => $current_target["target_record_id"],
                        "viewer_is_assessor" => $actor_is_assessor
                    )
                );
            }
            if ($can_delete && $atarget_id) {
                $remove_task_modal = new Views_Assessments_Modals_RemoveTask();
                $remove_task_modal->render(array("deletion_reasons" => $deletion_reasons));
            }
            if ($can_download) {
                $generate_pdf_modal = new Views_Assessments_Modals_GeneratePDF();
                $generate_pdf_modal->render(
                    array(
                        "action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment",
                        "label" => $translate->_("Download this assessment in PDF format:"),
                        "error_url" => ENTRADA_URL . $_SERVER["REQUEST_URI"] . "&pdf-error=true",
                        "download_as_one_file" => false,
                        "download_button_label" => $translate->_("Download PDF")
                    )
                );
            }
        endif;
    }

    private function renderAssessmentCueModal() {
        global $translate;
        ?>
        <div id="assessment-cue-modal" style="display: none;" class="modal fade">
            <div class="modal-header">
                <h2><?php echo $translate->_("Assessment Cue"); ?></h2>
            </div>
            <div class="modal-body form-vertical">
                <div class="control-group">
                    <label for="assessment-cue-text" class="form-label"><?php echo $translate->_("Assessment Cue (optional):"); ?></label>
                    <div class="form-control">
                        <textarea rows="10" cols="50" name="assessment_cue" id="assessment-cue-text" class="cue-text-area" form="assessment-form"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" data-dismiss="modal" class="btn btn-default pull-left"><?php echo $translate->_("Close"); ?></a>
                <input class="btn btn-primary"  type="submit" id="submit_form" name="submit_form" value="<?php echo $translate->_("Submit Assessment"); ?>" />
            </div>
        </div>
        <?php
    }

    /**
     * Build the appropriate header entries required to allow an assessment to function correctly.
     *
     * @param string $mode
     */
    public function addHeadScripts($mode = "internal") {
        global $HEAD, $JQUERY, $translate;
        $head_contents = array();
        $jquery_contents = array();
        ob_start();
        ?><script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/assessments/assessment<?php echo $mode == "external" ? "-external" : ""; ?>.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script><?php
        $jquery_contents[] = ob_get_clean();
        ob_start();
        ?><script type="text/javascript">var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";</script><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery.advancedsearch.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/jquery/jquery.advancedsearch.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/rubrics.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/items.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessments.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?><link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL; ?>/css/assessments/assessment-form.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>"/><?php
        $head_contents[] = ob_get_clean();
        ob_start();
        ?>
        <script type="text/javascript">
            jQuery(function ($) {
                sidebarBegone();
                if ($("#choose-forward-assessor-btn").length) {
                    $("#choose-forward-assessor-btn").advancedSearch({
                        api_url: ENTRADA_URL + "/assessments?section=api-assessments",
                        resource_url: ENTRADA_URL,
                        filters: {
                            assessor_faculty: {
                                label: "<?php echo $translate->_("Assessor"); ?>",
                                data_source: "get-faculty-staff", // For now, only faculty/staff will be able to forward to other faculty.
                                selector_control_name: "forward_assessor_id",
                                mode: "radio"
                            }
                        },
                        list_data: {
                            selector: "#selected-forward-assessor-container",
                            background_value: "url(../../images/user-circle-small.png) no-repeat scroll 0 0 transparent"
                        },
                        lazyload: true,
                        control_class: "target-audience-selector",
                        no_results_text: "<?php echo $translate->_("No assessors found matching the search criteria"); ?>",
                        parent_form: $("#forward-task-modal-form"),
                        width: 300,
                        modal: true
                    });
                }
            });
        </script>
        <?php
        $jquery_contents[] = ob_get_clean();

        foreach ($head_contents as $head_item) {
            if (!in_array(trim($head_item), $HEAD)) {
                $HEAD[] = trim($head_item);
            }
        }
        foreach ($jquery_contents as $jquery_item) {
            if (!in_array(trim($jquery_item), $JQUERY)) {
                $JQUERY[] = trim($jquery_item);
            }
        }
        // Add common translations via Tasks object
        Entrada_Assessments_Tasks::addCommonJavascriptTranslations();
    }

}