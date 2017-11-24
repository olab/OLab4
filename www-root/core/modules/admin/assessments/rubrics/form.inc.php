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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $page_request_mode = (defined("EDIT_RUBRIC") && EDIT_RUBRIC === true) ? "edit" : "read-only";
    $warning_message = "";

    // A link to the "what uses this rubric" page
    $rubric_usage_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/forms?rubric_id={$PROCESSED["rubric_id"]}", $PROCESSED["fref"], $PROCESSED["rref"]);

    // Check the editability of this current rubric.
    $rubric_readonly = !$forms_api->isRubricEditable();

    // Render the copy and attach button instead of the copy button? We only change this when there's a form to attach to.
    $copy_and_attach_button = false;

    // Set mode for the view, and set the state of the view.
    $view_mode = ($rubric_readonly) ? "editor-readonly" : "editor";
    $rubric_view_state = "rubric-edit-inuse"; // Default state is to lock down edit functionality

    // Fetch the editability state of the rubric
    $rubric_editability = $forms_api->getRubricEditabilityState(@$form_referrer_data["form_id"]);

    /** Determine editability of the various parts of the rubric. Warn the user about all the possible actions, in context. **/

    switch ($rubric_editability) {

        case "readonly":
            // Rubric has been delivered, no editing allowed.
            $msg  = $translate->_("This <strong>Grouped Item</strong> is in use by a form that has been delivered. Only <strong>permissions</strong> can be edited when a Grouped Item is used in tasks that have been delivered. ");
            $msg .= $translate->_("If you wish to make changes, please make a <strong>new copy</strong> of the Grouped Item. ");
            $msg .= "<br/><br/>";
            $msg .= $translate->_("To view which forms are using this Grouped Item, click here <strong><a href='%s'>click here</a></strong>. ");
            $warning_message = sprintf($msg, $rubric_usage_url);
            $rubric_view_state = "rubric-edit-inuse";
            break;

        case "readonly-attached-editable":
            // Rubric has been delivered, but the form is editable, so we can't edit the rubric, but we can attach it to the form.
            $msg  = $translate->_("This <strong>Grouped Item</strong> is in use by a form that has been delivered. Only <strong>permissions</strong> can be edited when a Grouped Item is used in tasks that have been delivered. ");
            $msg .= $translate->_("Use the <strong>\"Copy & Attach This Grouped Item\"</strong> button to replace this Grouped Item on your form with a new copy.");
            $msg .= "<br/><br/>";
            $msg .= $translate->_("To view which forms are using this Grouped Item, click here <strong><a href='%s'>click here</a></strong>. ");
            $warning_message = sprintf($msg, $rubric_usage_url);
            $rubric_view_state = "rubric-edit-inuse";
            $copy_and_attach_button = true;
            break;

        case "editable": // Not in use anywhere
        case "editable-attached": // In use by only one form, and it was the referrer specified
            $rubric_view_state = "rubric-edit-clean"; // Allow full editability
            break;

        case "editable-attached-multiple":
            // The rubric is in use in multiple forms, but none of them are delivered.
            // We can edit the rubric, but must notify the user that they will be affecting all of them.
            $msg = $translate->_("This <strong>Grouped Item</strong> is attached to one or more forms. Making modifications to it will affect all of the associated forms. To view which forms are using this Grouped Item, <strong><a href='%s'>click here</a></strong>.");
            $warning_message = sprintf($msg, $rubric_usage_url);
            $rubric_view_state = "rubric-edit-modify";
            break;

        case "editable-attached-items-in-use-descriptors-locked":
            // There are rubrics that use some of the items in this current rubric (excluding the current rubric)
            $msg  = $translate->_("Because this rubric contains items that are in use individually on other forms, the response descriptors of the associated items cannot be modified.");
            $msg .= "<br/><br/>";
            $msg .= $translate->_("Use the <strong>\"Copy & Attach This Grouped Item\"</strong> button to replace this Grouped Item on your form with a new copy.");
            $warning_message = $msg;
            $copy_and_attach_button = true;
            $rubric_view_state = "rubric-edit-modify";
            break;

        case "editable-attached-multiple-descriptors-locked":
            $msg  = $translate->_("This rubric is attached to one or more forms. Making modifications to it will affect all of the associated forms. ");
            $msg .= "<br/><br/>";
            $msg .= $translate->_("To view which forms are using this Grouped Item, <strong><a href='%s'>click here</a></strong>. ");
            $warning_message = sprintf($msg, $rubric_usage_url);
            $rubric_view_state = "rubric-edit-modify";
            break;

        case "editable-descriptors-locked":
            // Items of this rubric are in use somewhere in the system, but they haven't been delivered.
            // So we can edit the rubric, but can't change the descriptors.
            $warning_message = $translate->_("Because this <strong>Grouped Item</strong> contains items that are in use individually on other forms, the response descriptors of the associated items cannot be modified.");
            $rubric_view_state = "rubric-edit-modify";
            break;

        case "editable-attached-descriptors-locked":
            // None of the forms this is attached to are delivered, so we allow editing (no descriptor editing), but
            // notify them that they will affect changes across the board.
            $msg  = $translate->_("This <strong>Grouped Item</strong> is attached to one or more forms. Making modifications to it will affect all of the associated forms. ");
            $msg .= $translate->_("To view which forms are using this Grouped Item, <strong><a href='%s'>click here</a></strong>. ");
            $msg .= "<br/><br/>";
            $msg .= $translate->_("Use the <strong>\"Copy & Attach This Grouped Item\"</strong> button to replace this Grouped Item on your form with a new copy.");
            $warning_message = sprintf($msg, $rubric_usage_url);
            $copy_and_attach_button = true;
            $rubric_view_state = "rubric-edit-modify";
            break;
    }


    if ($warning_message): ?>
        <div class="alert alert-info">
            <ul>
                <li><?php echo $warning_message; ?></li>
            </ul>
        </div>
    <?php endif;

    $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
    if ($flash_messages) {
        foreach ($flash_messages as $message_type => $messages) {
            switch ($message_type) {
                case "error" :
                    echo display_error($messages);
                    break;
                case "success" :
                    echo display_success($messages);
                    break;
                case "notice" :
                default :
                    echo display_notice($messages);
                    break;
            }
        }
    }

    $HEAD[] = "<script type=\"text/javascript\">var rubric_in_use = \"". ($rubric_readonly ? "true" : "false") ."\"</script>";
    if ($page_request_mode == "edit") {
        $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"" . ENTRADA_URL . "\";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"" . ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-rubric" . "\";</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        ?>
        <script type="text/javascript">
            var rubric_localizations = {};
            rubric_localizations.error_default = '<?php echo $translate->_("The action could not be completed. Please try again later"); ?>';
            rubric_localizations.error_unable_to_copy = '<?php echo $translate->_("The action could not be completed. Please try again later"); ?>';

            var form_referrer_hash = "<?php echo $PROCESSED["fref"] ?>";
            jQuery(function ($) {
                jQuery("#contact-selector").audienceSelector({
                    "filter": "#contact-type",
                    "target": "#author-list",
                    "content_type": "rubric-author",
                    "content_target": "<?php echo $PROCESSED["rubric_id"]; ?>",
                    "api_url": "<?php echo ENTRADA_URL . "/admin/assessments/rubrics?section=api-rubric"; ?>",
                    "delete_attr": "data-arauthor-id"
                });
            });
        </script>
        <?php

        $render_page = true;
        switch ($STEP) {
            case 2 :

                if (!$rubric_readonly) {
                    if ((isset($_POST["rubric_title"])) && ($tmp_input = clean_input($_POST["rubric_title"], array("trim", "notags")))) {
                        $PROCESSED["rubric_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("Sorry, a title is required"));
                    }

                    if ((isset($_POST["rubric_description"])) && ($tmp_input = clean_input($_POST["rubric_description"], array("trim", "notags")))) {
                        $PROCESSED["rubric_description"] = $tmp_input;
                    } else {
                        $PROCESSED["rubric_description"] = "";
                    }

                    if ((isset($_POST["rubric_item_code"])) && ($tmp_input = clean_input($_POST["rubric_item_code"], array("trim", "notags")))) {
                        $PROCESSED["rubric_item_code"] = $tmp_input;
                    } else {
                        $PROCESSED["rubric_item_code"] = "";
                    }
                }

                if (!$ERROR) {
                    $forms_api->setRubricID($PROCESSED["rubric_id"]);
                    $saved = $forms_api->saveRubric($PROCESSED["rubric_title"], @$PROCESSED["rubric_description"], @$PROCESSED["rubric_item_code"], @$form_referrer_data["form_id"]);
                    if (!$saved) {
                        foreach ($forms_api->getErrorMessages() as $error_message) {
                            add_error($error_message);
                            Entrada_Utilities_Flashmessenger::addMessage($error_message, "error", $MODULE);
                        }
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("There was an error encountered while trying to add this Grouped Item. The system administrator was informed of this error; please try again later."), "error", $MODULE);
                        $STEP = 1;
                    }
                    if ($saved && !$forms_api->isRubricInUse() && $PROCESSED["fref"] && !empty($form_referrer_data)) {
                        // We're creating and attaching a new rubric to a form.
                        if (!$forms_api->attachRubricsToForm($form_referrer_data["form_id"], array($PROCESSED["rubric_id"]))) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                                Entrada_Utilities_Flashmessenger::addMessage($error_message, "error", $MODULE);
                            }
                        }
                    } else {
                        foreach ($forms_api->getErrorMessages() as $error_message) {
                            add_error($error_message);
                            Entrada_Utilities_Flashmessenger::addMessage($error_message, "error", $MODULE);
                        }
                        $STEP = 1;
                    }

                    if (!$ERROR) {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully updated Grouped Item."), "success", $MODULE);
                        $url = ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$PROCESSED["rubric_id"]}";
                        $url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL($url, $PROCESSED["fref"], $PROCESSED["rref"]);
                        header("Location: " . $url);
                    }
                }
            break;
        }

        if ($SUCCESS) {
            echo display_success();
        }
        if ($NOTICE) {
            echo display_notice();
        }
        if ($ERROR) {
            echo display_error();
        }

        if ($render_page) {
            $form_action_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$PROCESSED["rubric_id"]}", $PROCESSED["fref"], $PROCESSED["rref"]);
            $items_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?", $PROCESSED["fref"], $PROCESSED["rref"]);
            $add_attach_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?section=add-item", $PROCESSED["fref"], $PROCESSED["rref"]);
            ?>
            <form id="rubric-form" action="<?php echo $form_action_url; ?>" class="form-horizontal" method="post">

                <div id="msgs" class="row-fluid"></div>
                <input type="hidden" name="step" value="2"/>
                <input type="hidden" name="fref" value="<?php echo $PROCESSED["fref"] ?>"/>
                <?php
                    // Render the information input boxes (title/description/item code/permissions)
                    $information_view = new Views_Assessments_Forms_Sections_RubricInformation(array("mode" => $view_mode));
                    $information_view->render(
                        array(
                            "in_use" => $rubric_readonly,
                            "rubric_title" => $PROCESSED["rubric_title"],
                            "rubric_description" => $PROCESSED["rubric_description"],
                            "rubric_item_code" => $PROCESSED["rubric_item_code"],
                            "authors" => Models_Assessments_Rubric_Author::fetchAllRecords($PROCESSED["rubric_id"]),
                            "form_mode" => $view_mode
                        )
                    );
                ?>
                <div class="row-fluid">
                    <?php
                        $button = new Views_Assessments_Forms_Controls_BackToReferrerButton();
                        $button->render(array("referrer_url" => @$form_referrer_data["referrer_url"] ? $form_referrer_data["referrer_url"] : null, "referrer_type" => "form"));
                    ?>
                    <input id="submit-button" type="submit" class="btn btn-primary pull-right<?php echo($rubric_readonly ? " hide" : "") ?>" value="<?php echo $translate->_("Save"); ?>"/>
                </div>

                <h2><?php echo $translate->_("Attached Items"); ?></h2>


                <div class="row-fluid space-below">
                    <div class="pull-right">
                        <?php if ($rubric_data["meta"]["lines_count"]): ?>
                            <?php if ($copy_and_attach_button):?>
                                <a id="copy-rubric-link" href="#copy-rubric-modal" data-toggle="modal" class="btn"><i class="icon-share"></i> <?php echo $translate->_("Copy & Attach This Grouped Item"); ?></a>
                            <?php else: ?>
                                <a id="copy-rubric-link" href="#copy-rubric-modal" data-toggle="modal" class="btn"><i class="icon-share"></i> <?php echo $translate->_("Copy Grouped Item"); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!$rubric_readonly): ?>
                            <a href="<?php echo $add_attach_url; ?>" class="btn btn-success">
                                <i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Create & Attach a New Item"); ?>
                            </a>
                            <a id="add-element"
                               class="btn btn-success"
                               href="<?php echo $items_url; ?>">
                                <?php echo $translate->_("Attach Existing Item(s)"); ?>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

                <?php if ($rubric_data["meta"]["lines_count"]): // Render the rubric lines (if any) ?>

                    <?php $all_descriptors = Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada"); ?>
                    <div class="row-fluid space-above">
                        <?php
                            $rubric_view = new Views_Assessments_Forms_Rubric(array("mode" => "editor", "rubric_state" => $rubric_view_state));
                            $rubric_view->render(
                                array(
                                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                    "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                    "rubric_id" => $rubric_data["meta"]["rubric_id"],
                                    "rubric_data" => $rubric_data,
                                    "referrer_hash" => $PROCESSED["fref"],
                                    "all_descriptors" => $all_descriptors
                                )
                            );
                        ?>
                    </div>

                <?php else: ?>

                    <?php echo display_notice($translate->_("Use the \"Attach Items\" button to create an item group.")); ?>

                <?php endif; ?>
            </form>
            <?php

            // Render modals
            if ($page_request_mode == "edit") {
                $delete_rubric_modal = new Views_Assessments_Forms_Modals_RemoveRubric();
                $delete_rubric_modal->render(
                    array(
                        "rubric_id" => (int)$PROCESSED["rubric_id"],
                        "action_url" => ENTRADA_URL . "/admin/assessments/rubrics?section=api-rubric",
                    )
                );
            }

            $copy_rubric_modal = new Views_Assessments_Forms_Modals_CopyRubric();
            $copy_rubric_modal->render(
                array(
                    "rubric_id" => (int)$PROCESSED["rubric_id"],
                    "action_url" => ENTRADA_URL . "/admin/assessments/rubrics",
                    "prepopulated_text" => $rubric_data["rubric"]["rubric_title"],
                    "form_id" => @$form_referrer_data["form_id"],
                    "contains_deleted_items" => @$rubric_data["meta"]["contains_deleted_items"],
                    "copy_and_attach" => $copy_and_attach_button
                )
            );
        }
    }
}