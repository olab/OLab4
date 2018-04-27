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
 * The form that allows users to add and edit formbank forms.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_FORM") && !defined("EDIT_FORM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte();

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/assessments/assessment-blueprint.css\" />";

    $forms_api->setCBMEFormID($PROCESSED["cbme_form_id"]);
    $form_data = $forms_api->fetchCBMEFormData(); // Guaranteed array (empty for new forms)
    $form_in_use = $forms_api->isCBMEFormInUse();
    $form_editable = $forms_api->isCBMEFormEditable();
    $form_readonly = !$forms_api->isCBMEFormEditable();
    $form_mode = $form_in_use ? "editor-readonly" : "editor";
    $form_referrer_hash = null;
    $form_referrer_url = ENTRADA_URL."/admin/assessments/cbmeforms?section=edit-form&cbme_form_id={$PROCESSED["cbme_form_id"]}";
    if ($PROCESSED["cbme_form_id"]) {
        $form_referrer_hash = Entrada_Utilities_FormStorageSessionHelper::addFormReferrerURL($PROCESSED["cbme_form_id"], $form_referrer_url);
    }
    $form_referrer_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL($form_referrer_url, $form_referrer_hash);

    if (count($form_data["objectives"])) {
        $objective = reset($form_data["objectives"]);
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "course_id" => $objective["course_id"]
        ));
        $epas = $tree_object->fetchTreeNodesAtDepth(2, "o.objective_code");
    } else {
        $epas = array();
    }

    $user_courses = array();
    $user_courses_records = $forms_api->fetchUserCourseList(($ENTRADA_USER->getActiveRole() == "admin"));
    if (empty($user_courses_records)) {
        add_error(sprintf($translate->_("No %s Found."), $translate->_("Course")));
    } else {
        $user_courses = array_map(
            function ($r) {
                return array("course_id" => $r->getID(), "course_name" => $r->getCourseName());
            },
            $user_courses_records
        );
    }

    if (isset($pdf_error) && $pdf_error) {
        echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
        application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
    }

    if (empty($form_data) && $REQUEST_MODE == "edit"): ?>
        <div class="alert alert-danger">
            <ul>
                <li><?php echo $translate->_("Unable to load the requested form."); ?></li>
            </ul>
        </div>
        <?php
    else:
        if ($form_in_use && !$form_editable): ?>
            <div class="alert alert-info">
                <ul>
                    <li><?php echo $translate->_("<strong>This form is in use</strong> as part of an assessment or evaluation. Only <strong>permissions</strong> can be edited when a form is used in tasks that have been delivered. If you wish to make changes, please make a <strong>new copy</strong> of the form."); ?></li>
                </ul>
            </div>
            <?php
        endif;

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

        switch ($STEP) {
            case 2 :
                if (!$form_in_use) {
                    if (isset($_POST["form_title"]) && $tmp_input = clean_input($_POST["form_title"], array("trim", "striptags"))) {
                        $PROCESSED["title"] = $tmp_input;
                    } else {
                        add_error($translate->_("A form title is required."));
                    }

                    if (isset($_POST["form_type_id"]) && $tmp_input = clean_input($_POST["form_type_id"], array("trim", "int"))) {
                        $PROCESSED["form_type_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A form type is required."));
                    }

                    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A course id is required."));
                    }

                    if (isset($_POST["form_description"]) && $tmp_input = clean_input($_POST["form_description"], array("trim", "striptags"))) {
                        $PROCESSED["description"] = $tmp_input;
                    }

                    if (isset($_POST["curriculum_tag_selected"]) && $tmp_input = clean_input($_POST["curriculum_tag_selected"], array("trim", "int"))) {
                        $PROCESSED["curriculum_tag_selected"] = $tmp_input;
                        if (isset($_POST["curriculum_tag"]) && $tmp_input = clean_input($_POST["curriculum_tag"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("You must select a <strong>Curriculum Tag Set</strong> for this form."));
                        }
                    } else {
                        $PROCESSED["objective_id"] = NULL;
                    }

                    // Check for contextual variables
                    if (isset($_POST["contextual_vars_0_1"]) && is_array($_POST["contextual_vars_0_1"])) {
                        $contextual_vars = array_map(function ($cvar) {
                            return clean_input($cvar, array("trim", "int"));
                        }, $_POST["contextual_vars_0_1"]);

                        $PROCESSED["contextual_vars"] = array();
                        foreach ($contextual_vars as $contextual_var) {
                            if(isset($_POST["cvariable_responses_0_1_".$contextual_var]) && is_array($_POST["cvariable_responses_0_1_".$contextual_var])) {
                                $PROCESSED["contextual_vars"][$contextual_var] = array_map(function ($response) {
                                    return clean_input($response, array("trim", "int"));
                                }, $_POST["cvariable_responses_0_1_".$contextual_var]);
                            }
                        }
                    } else {
                        $PROCESSED["contextual_vars"] = array();
                    }

                    if (isset($_POST["feedback"]) && $tmp_input = clean_input($_POST["feedback"], array("trim", "striptags"))) {
                        $PROCESSED["feedback"] = $tmp_input;
                    } else {
                        $PROCESSED["feedback"] = NULL;
                    }

                    if (isset($_POST["concerns"]) && $tmp_input = clean_input($_POST["concerns"], array("trim", "striptags"))) {
                        $PROCESSED["concerns"] = $tmp_input;
                    } else {
                        $PROCESSED["concerns"] = NULL;
                    }

                    if (isset($_POST["entrustment_rating"])) {
                        if (isset($_POST["rating_scale"]) && $tmp_input = clean_input($_POST["rating_scale"], array("trim", "int"))) {
                            $PROCESSED["rating_scale"] = $tmp_input;
                        } else {
                            add_error($translate->_("You must select a scale to use for the Entrustment Rating."));
                            $PROCESSED["rating_scale"] = "";
                        }

                        if (isset($_POST["rating_scale_comments"]) && $tmp_input = clean_input($_POST["rating_scale_comments"], array("trim", "notags"))) {
                            $PROCESSED["rating_scale_comments"] = $tmp_input;
                        } else {
                            $PROCESSED["rating_scale_comments"] = "disabled";
                        }

                        if (isset($_POST["item_text"]) && $tmp_input = clean_input($_POST["item_text"], array("trim", "notags"))) {
                            $PROCESSED["item_text"] = $tmp_input;
                        } else {
                            add_error($translate->_("You must specify the item text for the Entrustment Rating."));
                            $PROCESSED["item_text"] = "";
                        }

                        if (isset($_POST["scale_reponse_flag"]) && is_array($_POST["scale_reponse_flag"])) {
                            $PROCESSED["scale_reponse_flag"] = array_map(function ($flag) {
                                return clean_input($flag, array("trim", "int"));
                            }, $_POST["scale_reponse_flag"]);
                        } else {
                            $PROCESSED["scale_reponse_flag"] = array();
                        }

                        $PROCESSED["entrustment_rating"] = array(
                            "item_text" => $PROCESSED["item_text"],
                            "selected_scale_id" => $PROCESSED["rating_scale"],
                            "comment_type" => $PROCESSED["rating_scale_comments"],
                            "flagged_response_descriptors" => $PROCESSED["scale_reponse_flag"]
                        );

                    } else {
                            $PROCESSED["entrustment_rating"] = array();
                    }
                }

                $PROCESSED["attributes"] = array(
                    "entrustment_rating" => $PROCESSED["entrustment_rating"]
                );

                if (isset($PROCESSED["course_id"])) {
                    $PROCESSED["attributes"]["course_id"] = $PROCESSED["course_id"];
                }

                if (isset($PROCESSED["contextual_vars"])) {
                    $PROCESSED["attributes"]["contextual_vars"] = $PROCESSED["contextual_vars"];
                }

                if (isset($PROCESSED["feedback"]) && $PROCESSED["feedback"] == "true") {
                    $PROCESSED["attributes"]["feedback"] = $PROCESSED["feedback"];
                } else {
                    $PROCESSED["attributes"]["feedback"] = "false";
                }

                if (isset($PROCESSED["concerns"]) && $PROCESSED["concerns"] == "true") {
                    $PROCESSED["attributes"]["concerns"] = $PROCESSED["concerns"];
                } else {
                    $PROCESSED["attributes"]["concerns"] = "false";
                }

                $STEP = 1;
                if (!$ERROR) {
                    if ($forms_api->saveCBMEForm($PROCESSED)) {
                        $action_taken = $METHOD == "insert" ? $translate->_("created") : $translate->_("updated");
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully %s the form."), $action_taken), "success", $MODULE);
                        $url = ENTRADA_URL . "/admin/assessments/cbmeforms?section=edit-form&cbme_form_id={$forms_api->getCBMEFormID()}";

                        header("Location: " . $url);

                    } else {
                        foreach ($forms_api->getErrorMessages() as $error_message) {
                            add_error($error_message);
                        }
                    }
                }

                $PROCESSED["attributes"] = json_encode($PROCESSED);
            break;
        }

        if ($SUCCESS) {
            echo display_success();
        }
        if ($ERROR) {
            echo display_error();
        }

        $all_objectives = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation());
        $objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);

        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">var form_in_use = \"". ($form_in_use ? "true" : "false") ."\"</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/assessment-form.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/items.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/rubrics.css\" />";
        if ($REQUEST_MODE == "edit") {
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"" . ENTRADA_URL . "\";</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.animated-notices.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.animated-notices.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        }
        ?>
        <script type="text/javascript">
            var assessment_forms_localization = {};
            assessment_forms_localization.default_error_message = "<?php echo $translate->_("Unknown Server Error"); ?>";
            assessment_forms_localization.message_there_are_no_items_attached = "<?php echo $translate->_("There are currently no elements attached to this form."); ?>";
            assessment_forms_localization.comment_type_optional = "<?php echo $translate->_("optional") ?>";
            assessment_forms_localization.comment_type_mandatory = "<?php echo $translate->_("mandatory") ?>";
            assessment_forms_localization.comment_type_mandatory_flagged = "<?php echo $translate->_("mandatory for flagged responses") ?>";
            assessment_forms_localization.comment_type_disabled = "<?php echo $translate->_("disabled") ?>";
            assessment_forms_localization.no_results = "<?php echo $translate->_("You currently have no forms to display. To Add a new form click the Add Form button above.") ?>";
        </script>
        <script type="text/javascript">
            var referrer_rubric_id = null;
            var referrer_item_id = null;
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var API_URL = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-forms"; ?>";
            jQuery(document).ready(function ($) {
                $("#curriculum-tag-btn").advancedSearch({
                    api_url: "<?php echo ENTRADA_URL . "/admin/assessments/items?section=api-items"; ?>",
                    resource_url: ENTRADA_URL,
                    filters: {
                        curriculum_tag: {
                            label: "<?php echo $translate->_("Curriculum Tag"); ?>",
                            data_source: "get-fieldnote-objectives",
                            mode: "radio",
                            secondary_data_source: "get-fieldnote-child-objectives",
                            selector_control_name: "curriculum_tag"
                        }
                    },
                    control_class: "field-note-objective-control",
                    no_results_text: "",
                    parent_form: $("#form-elements"),
                    width: 400
                });
            });
        </script>
        <script type="text/javascript" src="<?php echo ENTRADA_URL . "/javascript/assessments/forms/assessments-cbmeforms-admin.js?release=" . html_encode(APPLICATION_VERSION); ?>"></script>

        <div id="form-page-loading-overlay" style="display: none;"></div>
        <form id="form-elements" action="<?php echo ENTRADA_URL . "/admin/assessments/cbmeforms?section=edit-form&cbme_form_id={$PROCESSED["cbme_form_id"]}"; ?>" data-form-id="<?php echo $PROCESSED["cbme_form_id"]; ?>" class="form-horizontal" method="POST">
            <input type="hidden" name="step" value="2"/>

            <h2 title="<?php echo $translate->_("Form Information"); ?>"><?php echo $translate->_("Form Information"); ?></h2>

            <?php
            $form_types = Models_Assessments_Form_Type::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation());
            $attributes = json_decode($PROCESSED["attributes"], true);
            if (is_array($attributes) && array_key_exists("entrustment_rating", $attributes) && count($attributes["entrustment_rating"]) ) {
                if (isset($attributes["entrustment_rating"]["selected_scale_id"]) && intval($attributes["entrustment_rating"]["selected_scale_id"])) {
                    $attributes["entrustment_rating"]["scale_items"] = $forms_api->fetchScaleData($attributes["entrustment_rating"]["selected_scale_id"]);
                }
            }
            if (isset($attributes["course_id"])) {
                Entrada_Utilities_FormStorageSessionHelper::addFormCourseID($PROCESSED["cbme_form_id"], $attributes["course_id"]);
            }

            $contextual_variables = $forms_api->fetchContextualVariables();
            $contextual_vars_desc = $forms_api->fetchContextualVarsDescriptionArray(isset($attributes["course_id"]) ? $attributes["course_id"] : null);

            $scales_list["global_assessment"] = $forms_api->fetchScalesList("global_assessment");

            // Render "Form Information" input boxes
            $form_information_view = new Views_Assessments_Forms_Sections_FormInformation();
            $form_information_view->render(
                array(
                    "form_id" => $PROCESSED["cbme_form_id"],
                    "form_in_use" => $form_in_use,
                    "form_mode" => $REQUEST_MODE, // "add" or "edit"
                    "form_title" => $PROCESSED["title"],
                    "attributes" => $attributes,
                    "form_type_id" => $PROCESSED["form_type_id"],
                    "form_types" => $form_types,
                    "description" => $PROCESSED["description"],
                    "objective" => $objective,
                    "authors" => @$form_data["authors"], // Can be empty for a new form
                    "is_publishable" => $form_data["meta"]["is_publishable"],
                    "form_objectives" => $form_data["objectives"],
                    "epas" => $epas,
                    "contextual_variables" => $contextual_variables,
                    "contextual_vars_desc" => $contextual_vars_desc,
                    "is_published" => count($epas) ? true : false,
                    "courses_list" => $user_courses,
                    "course_related" => true,
                    "scales_list" => $scales_list
                )
            );
            ?>
            <?php if ($REQUEST_MODE == "edit"): ?>
                <h2><?php echo $translate->_("Form Items"); ?></h2>
                <?php
                    // Render delete/download/add items control buttons
                    $form_buttons = new Views_Assessments_Forms_Controls_FormOptionButtons();
                    $form_buttons->render(
                        array(
                            "form_id" => $PROCESSED["cbme_form_id"],
                            "form_in_use" => $form_in_use,
                            "element_count" => @$form_data["meta"]["element_count"],
                            "referrer_hash" => $form_referrer_hash,
                            "pdf_generation_url" => "?section=edit-form&cbme_form_id={$PROCESSED["form_id"]}&generate-pdf=true"
                        )
                    );
                ?>
                <div class="" id="form-items">
                    <?php
                    // Render the form in editor mode
                    $view_options = array(
                        "form_id" => $PROCESSED["cbme_form_id"],
                        "elements" => $form_data["elements"],
                        "rubrics" => $form_data["rubrics"],
                        "disabled" => $form_in_use,
                        "public" => false,
                        "referrer_hash" => $form_referrer_hash,
                        "all_objectives" => $all_objectives
                    );
                    $form_view = new Views_Assessments_Forms_Form(array("mode" => $form_mode));
                    $form_view->render($view_options);
                   ?>
                </div>

                <?php if (isset($PROCESSED["curriculum_tag_selected"])): ?>
                    <input type="hidden" value="1" name="curriculum_tag_selected"/>
                <?php endif; ?>
                <?php if (isset($PROCESSED["objective_id"])): ?>
                    <input type="hidden" value="1" name="curriculum_tag_selected"/>
                    <input type="hidden"
                           name="curriculum_tag"
                           value="<?php echo html_encode($PROCESSED["objective_id"]); ?>"
                           id="curriculum_tag_<?php echo html_encode($PROCESSED["objective_id"]); ?>"
                           data-label="<?php echo $objective ? html_encode($objective->getName()) : ""; ?>"
                           class="search-target-control curriculum_tag_search_target_control field-note-objective-control">
                <?php endif; ?>

            <?php endif; ?>
        </form>
        <?php
        if ($REQUEST_MODE == "edit") { // In edit mode, so render some helpful modals

            // Copy modal
            $copy_modal = new Views_Assessments_Forms_Modals_CopyForm();
            $copy_modal->render(array(
                    "action_url" => ENTRADA_URL . "/admin/assessments/cbmeforms",
                    "form_id" => $PROCESSED["cbme_form_id"],
                    "prepopulate_text" => html_encode($form_data["form"]["title"])
                )
            );

            // Preview window
            // TODO: In the future, change this behaviour to not be a snapshot of the old version of the form, but use AJAX to render the up-to-date version, leveraging the view classes and loadTemplate.
            $preview_form_view = new Views_Assessments_Forms_Form(array("mode" => "assessment-blank")); // Render the form in assessment mode
            $form_html = $preview_form_view->render(array(
                    "form_id" => $PROCESSED["cbme_form_id"],
                    "disabled" => false,
                    "elements" => $form_data["elements"],
                    "progress" => $form_data["progress"],
                    "rubrics" => $form_data["rubrics"],
                    "aprogress_id" => null,
                    "public" => true
                ), false // do not echo
            );
            $preview_dialog = new Views_Assessments_Forms_Sections_PreviewDialog();
            $preview_dialog->render(array("form_html" => $form_html));

            // Delete items confirmation modal
            $delete_items_modal = new Views_Assessments_Forms_Modals_RemoveFormItems();
            $delete_items_modal->render(array("action_url" => ENTRADA_URL . "/admin/assessments/cbmeforms?section=api-forms"));
        }
    endif;
}