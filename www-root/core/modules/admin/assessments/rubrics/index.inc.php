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
 * The Rubrics index page.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_RUBRICS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $form_referrer_data = null;
    $referrer_form_id = null;
    $referrer_url = null;

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    if ($PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef()) {
        $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
        if (!empty($form_referrer_data)) {
            $referrer_url = $form_referrer_data["referrer_url"];
            $referrer_form_id = $form_referrer_data["form_id"];
        }
    }

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array("active" => "items"));

    // Draw navigation tabs, context sensitive
    $exclude_tabs = array();
    if (!empty($form_referrer_data)) {
        $exclude_tabs= array("items");
    }

    $navigation_tabs = new Views_Assessments_Forms_Controls_NavigationTabs();
    $navigation_tabs->render(array("active" => "rubrics", "has_access" => $ENTRADA_ACL->amIAllowed("assessments", "update", false), "exclusions" => $exclude_tabs));

    // Useful?
    if (isset($_GET["itemtype_id"]) && $tmp_input = clean_input(strtolower($_GET["itemtype_id"]), array("trim", "int"))) {
        $PROCESSED["itemtype_id"] = $tmp_input;
    } else {
        $PROCESSED["itemtype_id"] = 0;
    }

    if (isset($_GET["itemtype_id"]) && $tmp_input = clean_input($_GET["itemtype_id"], array("trim", "striptags"))) {
        $PROCESSED["itemtype_id"] = $tmp_input;
        $_SESSION["itemtype_id"] = $PROCESSED["itemtype_id"];
    } elseif (isset($_SESSION["itemtype_id"])) {
        $PROCESSED["itemtype_id"] = $_SESSION["itemtype_id"];
    }

    if (isset($_GET["item_id"]) && $tmp_input = clean_input(strtolower($_GET["item_id"]), array("trim", "int"))) {
        $PROCESSED["item_id"] = $tmp_input;
    } else {
        $PROCESSED["item_id"] = 0;
    }

    if (isset($_GET["element_type"]) && $tmp_input = clean_input($_GET["element_type"], array("trim", "striptags"))) {
        $PROCESSED["element_type"] = $tmp_input;
    }

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $PROCESSED["rubric_id"] = null;
    if ($form_referrer_data) {
        $PROCESSED["rubric_id"] = isset($form_referrer_data["rubric_id"]) ? $form_referrer_data["rubric_id"] : null;
    }
    if (!isset($PROCESSED["rubric_id"]) || !$PROCESSED["rubric_id"]) {
        if (isset($_POST["rubric_id"]) && $tmp_input = clean_input($_POST["rubric_id"], "int")) {
            $PROCESSED["rubric_id"] = $tmp_input;
        } else if (isset($_GET["rubric_id"]) && $tmp_input = clean_input($_GET["rubric_id"], "int")) {
            $PROCESSED["rubric_id"] = $tmp_input;
        }
    }

    if (!$referrer_url && $PROCESSED["rubric_id"]) {
        $referrer_url = ENTRADA_URL."/admin/assessments/rubrics?section=edit-rubric&rubric_id={$PROCESSED["rubric_id"]}";
    }

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["rubrics"]["selected_view"]) ? $PREFERENCES["rubrics"]["selected_view"] : "list") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var itemtype_id = '{$PROCESSED["itemtype_id"]}';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/rubrics/rubric-admin.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    ?>
    <script type="text/javascript">
        var rubric_localizations = {};
        rubric_localizations.error_default = '<?php echo $translate->_("The action could not be completed. Please try again later"); ?>';
        rubric_localizations.error_unable_to_copy = '<?php echo $translate->_("The action could not be completed. Please try again later"); ?>';
    </script>
    <?php
    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $PREFERENCES = $assessments_base->getAssessmentPreferences($MODULE);

    $PROCESSED["filters"] = array();
    if (isset($PREFERENCES["rubrics"]["selected_filters"])) {
        $PROCESSED["filters"] = $PREFERENCES["rubrics"]["selected_filters"];
    }
    $assessments_base->updateAssessmentPreferences($MODULE);
    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". ucwords(str_replace("_", " ", $key)) . " Filters</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li class='remove-single-filter' data-id='$target_id' data-filter='$key'>";
                $sidebar_html .= "<img src='" . ENTRADA_URL . "/images/checkbox-on.gif'/>";
                $sidebar_html .= "<span> ". html_encode($target_label) ."</span>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<input type=\"button\" id=\"clear-all-filters\" class=\"btn\" style=\"width: 100%\" value=\"{$translate->_("Clear All Filters")}\"/>";
        new_sidebar_item("Selected Grouped Item Filters", $sidebar_html, "assessment-filters", "open");
    }
    ?>

    <h1><?php echo $translate->_("Grouped Items"); ?></h1>

    <?php
    if ($flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE)) {
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

            $success = false;
            $PROCESSED["rubrics"] = array();
            if (isset($_POST["rubrics"]) && is_array($_POST["rubrics"])) {
                $PROCESSED["rubrics"] = array_filter($_POST["rubrics"], function ($id) {
                    return (int) $id;
                });
            }

            if ($referrer_form_id) {
                if (empty($PROCESSED["rubrics"])) {
                    add_error($translate->_("Please select one or more Grouped Items to add."));
                } else {
                    $success = $forms_api->attachRubricsToForm($referrer_form_id, $PROCESSED["rubrics"]);
                    if ($success != count($PROCESSED["rubrics"])) { // Some weren't added, notify the user.
                        foreach ($forms_api->getErrorMessages() as $error_msg) {
                            add_error($error_msg);
                        }
                    }
                    if ($success) {
                        add_success(sprintf($translate->_("Successfully added %s Grouped Items."), $success));
                    }
                }
            }

            if (!$ERROR) {
                $success_message = $translate->_("Successfully added the Grouped Item(s).");
                Entrada_Utilities_Flashmessenger::addMessage($success_message, "success", $MODULE);
                header("Location: " . $referrer_url);
            } else {
                $STEP = 1;
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            //$default_url = ENTRADA_URL."/admin/assessments/rubrics";
            //$ONLOAD[] = "setTimeout('window.location=\\'$default_url\\'', 5000)";
            break;
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if (isset($PROCESSED["item_id"]) && $PROCESSED["item_id"]) {
                add_notice($translate->_("<strong>Please Note</strong>: You are viewing all rubrics associated with a single item. If you would like to edit the item for a specific rubric, click on the rubric from the list and you will be directed to editing the item (shown directly below) for the selected rubric."));
            }
            if ($NOTICE) {
                echo display_notice();
            }
            if (isset($PROCESSED["item_id"]) && $PROCESSED["item_id"]): ?>
                <h2><?php echo $translate->_("Item to Edit in Grouped Items"); ?></h2>
                <div id="item-detail-container"></div>
            <?php endif; ?>
            <script type="text/javascript">
                jQuery(function($) {
                    <?php if (isset($PROCESSED["item_id"]) && $PROCESSED["item_id"]):  ?>
                        var items = jQuery.ajax({
                            url: ENTRADA_URL + "/admin/assessments/items?section=api-items",
                            data: "method=get-items&item_id=<?php echo $PROCESSED["item_id"]; ?>",
                            type: 'GET'
                        });
                        jQuery.when(items).done(function (data) {
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse.results > 0) {
                                jQuery.each(jsonResponse.data.items, function (key, item) {
                                    build_item_details(item);
                                });
                            }
                        });
                    <?php endif; ?>
                    $("#advanced-search").advancedSearch(
                        {
                            api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-rubric".(isset($PROCESSED["item_id"]) && ((int)$PROCESSED["item_id"]) ? "&item_id=".((int)$PROCESSED["item_id"]) : "") ; ?>",
                            resource_url: ENTRADA_URL,
                            filters: {
                                curriculum_tag: {
                                    label: "<?php echo $translate->_("Curriculum Tag"); ?>",
                                    data_source: "get-objectives",
                                    secondary_data_source: "get-child-objectives"
                                },
                                author: {
                                    label: "<?php echo $translate->_("Grouped Item Authors"); ?>",
                                    data_source: "get-rubric-authors"
                                },
                                course: {
                                    label: "<?php echo $translate->_("Courses"); ?>",
                                    data_source: "get-user-courses"
                                }
                            },
                            no_results_text: "<?php echo $translate->_("No Items found matching the search criteria"); ?>",
                            reload_page_flag: true,
                            list_selections: false,
                            results_parent: $("#assessment-rubrics-container"),
                            width: 400
                        }
                    );
                });
            </script>
            <div id="assessment-rubrics-container">
                <?php $rubric_table_form_action_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?step=2", $PROCESSED["fref"]); ?>
                <form id="rubric-table-form" action="<?php echo $rubric_table_form_action_url; ?>" method="POST">
                    <?php if ($PROCESSED["item_id"]): ?>
                    <input type="hidden" id="item-id" value="<?php echo $PROCESSED["item_id"]?>">
                    <?php endif; ?>
                    <div class="row-fluid space-below">
                        <div class="full-width">
                            <div class="input-append float-left">
                                <input type="text" id="rubric-search" placeholder="<?php echo $translate->_("Begin typing to search..."); ?>" class="input-large search-icon pull-left">
                                <a href="#" id="advanced-search" class="btn pull-left" type="button"><i class="icon-chevron-down"></i></a>
                            </div>
                            <div class="pull-right">
                                <?php
                                if ($PROCESSED["fref"]) {
                                    $back_button = new Views_Assessments_Forms_Controls_BackToReferrerButton();
                                    $back_button->render(array("referrer_url" => $form_referrer_data["referrer_url"], "referrer_type" => "form", "css_classes" => "pull-right"));
                                }
                                ?>
                            </div>
                        </div>
                        <div class="pull-right full-width">
                            <?php if (!empty($form_referrer_data)): ?>

                                <a href="#delete-rubric-modal" data-toggle="modal" class="btn btn-danger pull-left" id="delete-rubrics"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Grouped Item(s)"); ?></a>
                                <input id="attach-selected-grouped-items-btn" type="submit" class="btn btn-success pull-right" value="<?php echo $translate->_("Attach Selected Grouped Item(s)"); ?>" />
                                <a id="create-attach-rubric-link" href="#create-attach-rubric-modal" class="btn btn-success pull-right space-right" data-toggle="modal"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Create & Attach a New Grouped Item"); ?></a>

                            <?php else: ?>

                                <a href="#delete-rubric-modal" data-toggle="modal" class="btn btn-danger pull-left" id="delete-rubrics"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Grouped Item(s)"); ?></a>
                                <a href="#add-rubric-modal" data-toggle="modal" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add A New Grouped Item"); ?></a>

                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="assessment-msgs">
                        <div id="assessment-rubrics-loading" class="hide">
                            <p><?php echo $translate->_("Loading Assessment Rubrics..."); ?></p>
                            <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                        </div>
                    </div>
                    <div id="msgs"></div>
                    <div id="rubric-table-container">
                        <table class="table table-bordered table-striped" id="rubrics-table" summary="<?php $translate->_("List of Grouped Items") ?>">
                            <colgroup>
                                <col class="modified" />
                                <col class="title" />
                                <col class="actions" />
                            </colgroup>
                            <thead>
                            <tr>
                                <th width="5%"></th>
                                <th width="80%"><?php echo $translate->_("Title"); ?></th>
                                <th width="15%"><?php echo $translate->_("Date Created"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </form>
                <div class="row-fluid">
                    <a id="load-rubrics" class="btn btn-block"><?php echo $translate->_("Load More Grouped Items"); ?></a>
                </div>
            </div>
        <?php
            $rubric_form_modal_action_url = ENTRADA_URL."/admin/assessments/rubrics?section=api-rubric";
            $rubric_form_modal_action_url .= (isset($PROCESSED["item_id"]) && ((int)$PROCESSED["item_id"]) ? "&item_id=".((int)$PROCESSED["item_id"]) : "");
            $create_attach_rubric_action_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?", $PROCESSED["fref"]);

            $add_rubric_modal = new Views_Assessments_Forms_Modals_AddRubric();
            $add_rubric_modal->render(array("action_url" => $rubric_form_modal_action_url));

            $delete_rubric_modal = new Views_Assessments_Forms_Modals_DeleteRubric();
            $delete_rubric_modal->render(array("action_url" => $rubric_form_modal_action_url));

            $create_attach_modal = new Views_Assessments_Forms_Modals_CreateAttachRubric();
            $create_attach_modal->render(array("action_url" => $create_attach_rubric_action_url, "form_id" => $referrer_form_id, "fref" => $PROCESSED["fref"]));

            if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"])) {
            echo "<form id=\"search-targets-form\">";
            foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"] as $key => $filter_type) {
                foreach ($filter_type as $target_id => $target_label) {
                    echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
                }
            }
            echo "</form>";
        }
        break;
    }
}