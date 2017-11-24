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
 * The default file that is loaded when /admin/assessments/items is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ITEMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
	echo display_error();
	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef();
    $PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::getRubricRef();
    $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
    $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);

    if (isset($_GET["rubric_id"]) && $tmp_input = clean_input($_GET["rubric_id"], "int")) {
        $PROCESSED["rubric_id"] = $tmp_input;
    } elseif (isset($_POST["rubric_id"]) && $tmp_input = clean_input($_POST["rubric_id"], "int")) {
        $PROCESSED["rubric_id"] = $tmp_input;
    }

    if (!empty($rubric_referrer_data)) {
        // A referrer link was given, it describes the width and item types allowed for this item listing
        $PROCESSED["rubric_width"] = $rubric_referrer_data["width"];
        $PROCESSED["itemtypes"] = $rubric_referrer_data["types"]; // array
        $PROCESSED["rubric_id"] = $rubric_referrer_data["rubric_id"];
    }

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array("active" => "items"));

    $exclude_tabs = array();
    if (!empty($form_referrer_data)) {
        $exclude_tabs= array("rubrics");
    }

    $navigation_tabs = new Views_Assessments_Forms_Controls_NavigationTabs();
    $navigation_tabs->render(array("active" => "items", "has_access" => $ENTRADA_ACL->amIAllowed("assessments", "update", false), "exclusions" => $exclude_tabs));

    $PROCESSED["items"] = array();

    $HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script>var VIEW_PREFERENCE = \"". (isset($PREFERENCES["items"]["selected_view"]) ? $PREFERENCES["items"]["selected_view"] : "detail") ."\";</script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/assessments/items/items.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.dataTables.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/assessments/forms/view.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $PREFERENCES = $assessments_base->getAssessmentPreferences($MODULE);

    $PROCESSED["filters"] = array();
    if (isset($PREFERENCES["items"]["selected_filters"])) {
        $PROCESSED["filters"] = $PREFERENCES["items"]["selected_filters"];
    }
    $assessments_base->updateAssessmentPreferences($MODULE);

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"] as $key => $filter_type) {
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
        $sidebar_html .= "<input type=\"button\" id=\"clear-all-filters\" class=\"btn full-width\" value=\"{$translate->_("Clear All Filters")}\"/>";
        new_sidebar_item($translate->_("Selected Item Bank Filters"), $sidebar_html, "assessment-filters", "open");
    }
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url : "<?php echo ENTRADA_URL . "/admin/assessments/items?section=api-items" ; ?>",
                    resource_url: ENTRADA_URL,
                    filters : {
                        curriculum_tag : {
                            label : "<?php echo $translate->_("Curriculum Tag"); ?>",
                            data_source : "get-objectives",
                            secondary_data_source : "get-child-objectives"
                        },
                        author : {
                            label : "<?php echo $translate->_("Item Authors"); ?>",
                            data_source : "get-item-authors"
                        },
                        course : {
                            label : "<?php echo $translate->_("Courses"); ?>",
                            data_source : "get-user-courses"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No Items found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    list_selections: false,
                    results_parent: $("#assessment-items-container"),
                    width: 400
                }
            );
        });
    </script>
    <h1><?php echo $translate->_("Items"); ?></h1>
    <?php

    // Some ids were posted, so let's attach them to the given context.
    switch ($STEP) {
        case 2 :

            if (isset($_POST["items"]) && is_array($_POST["items"])) {
                $PROCESSED["items"] = array_map(function($i) { return clean_input($i, array("trim", "int")); }, $_POST["items"]);
                $PROCESSED["items"] = array_unique($PROCESSED["items"]);
            } else {
                add_error($translate->_("No items selected to attach."));
                $STEP = 1;
            }

            $form_referrer_url = $rubric_referrer_url = null;

            if ($PROCESSED["rref"] && !empty($rubric_referrer_data) && $PROCESSED["fref"] && !empty($form_referrer_data)) {
                // Both rubric and form must be updated; use the rubric ID to save the form element

                $rubric_referrer_url = $rubric_referrer_data["referrer_url"];
                $success = $forms_api->attachItemsToRubric($rubric_referrer_data["rubric_id"], $PROCESSED["items"]);
                if ($success != count($PROCESSED["items"])) { // Some weren't added, notify the user.
                    foreach ($forms_api->getErrorMessages() as $error_msg){
                        if (!in_array($error_msg, $ERRORSTR)) {
                            add_error($error_msg);
                        }
                    }
                    $ERROR++;
                }

                $form_referrer_url = $form_referrer_data["referrer_url"];
                $success = $forms_api->attachItemsToForm($form_referrer_data["form_id"], $PROCESSED["items"], $rubric_referrer_data["rubric_id"]);
                if ($success != count($PROCESSED["items"])) { // Some weren't added, notify the user.
                    foreach ($forms_api->getErrorMessages() as $error_msg){
                        if (!in_array($error_msg, $ERRORSTR)) {
                            add_error($error_msg);
                        }
                    }
                    $ERROR++;
                }

            } else {
                // Add or or the other individually.

                if ($PROCESSED["rref"] && !empty($rubric_referrer_data)) {
                    $rubric_referrer_url = $rubric_referrer_data["referrer_url"];
                    $success = $forms_api->attachItemsToRubric($rubric_referrer_data["rubric_id"], $PROCESSED["items"]);
                    if ($success != count($PROCESSED["items"])) { // Some weren't added, notify the user.
                        foreach ($forms_api->getErrorMessages() as $error_msg){
                            if (!in_array($error_msg, $ERRORSTR)) {
                                add_error($error_msg);
                            }
                        }
                        $ERROR++;
                    }

                }
                if ($PROCESSED["fref"] && !empty($form_referrer_data)) {
                    $form_referrer_url = $form_referrer_data["referrer_url"];
                    $success = $forms_api->attachItemsToForm($form_referrer_data["form_id"], $PROCESSED["items"]);
                    if ($success != count($PROCESSED["items"])) { // Some weren't added, notify the user.
                        foreach ($forms_api->getErrorMessages() as $error_msg){
                            if (!in_array($error_msg, $ERRORSTR)) {
                                add_error($error_msg);
                            }
                        }
                        $ERROR++;
                    }
                }
            }

            if (!$ERROR) {
                $referrer_url = ENTRADA_URL ."/assessments/items";
                if (!empty($rubric_referrer_data["referrer_url"])) {
                    $referrer_url = $rubric_referrer_data["referrer_url"];
                } else if (!empty($form_referrer_data["referrer_url"])) {
                    $referrer_url = $form_referrer_data["referrer_url"];
                }
                $referrer_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL($referrer_url, $PROCESSED["fref"], $PROCESSED["rref"]);
                if ($PROCESSED["rref"]) {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added <strong>%d</strong> items to the rubric."), $success), "success", $MODULE);
                } else if ($PROCESSED["fref"]) {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added <strong>%d</strong> items to the form."), $success), "success", $MODULE);
                }

                header("Location: ". $referrer_url);
            } else {
                $STEP = 1;
            }

            
        break;
    }

    if ($ERROR) {
        echo display_error();
    }
    if ($SUCCESS) {
        echo display_success();
    }

    switch ($STEP) {
        case 2 :
            if (!$redirect_url = Entrada_Utilities_FormStorageSessionHelper::determineReferrerURI($PROCESSED["fref"], $PROCESSED["rref"])) {
                $redirect_url = ENTRADA_URL . "/admin/assessments/items";
            }

            $set_timeout_js ="setTimeout(\"window.location='$redirect_url'\", 5000);";
            $ONLOAD[] = $set_timeout_js;
            break;
        case 1 :
        default :
        ?>
            <div id="msgs"></div>
            <div id="assessment-items-container">
                <?php $forms_search_form_action_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?step=2", $PROCESSED["fref"], $PROCESSED["rref"]); ?>
                <form id="form-search" class="form-search" action="<?php echo $forms_search_form_action_url; ?>" method="POST">
                    <input type="hidden" id="fref" name="fref" value="<?php echo (isset($PROCESSED["fref"]) ? $PROCESSED["fref"] : ""); ?>" />
                    <input type="hidden" id="rref" name="rref" value="<?php echo (isset($PROCESSED["rref"]) ? $PROCESSED["rref"] : ""); ?>" />
                    <input type="hidden" id="rubric_width" name="rubric_width" value="<?php echo(isset($PROCESSED["rubric_width"]) ? $PROCESSED["rubric_width"] : ""); ?>"/>
                    <input type="hidden" id="rubric_id" name="rubric_width" value="<?php echo(isset($rubric_referrer_data["rubric_id"]) ? $rubric_referrer_data["rubric_id"] : ""); ?>"/>

                    <div id="search-bar">
                        <div class="row-fluid space-below medium">
                            <div class="pull-left full-width">
                                <div class="input-append space-right">
                                    <input type="text" id="item-search" placeholder="<?php echo $translate->_("Begin typing to search the items..."); ?>" class="input-large search-icon">
                                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                                </div>
                                <div id="item-view-controls" class="btn-group">
                                    <a href="#" data-view="list" id="list-view" class="btn view-toggle" title="<?php echo $translate->_("Toggle Item List View"); ?>"><i class="icon-align-justify"></i></a>
                                    <a href="#" data-view="detail" id="detail-view" class="btn view-toggle" title="<?php echo $translate->_("Toggle Item Detail View"); ?>"><i class="icon-th-large"></i></a>
                                </div>
                                <?php
                                    $back_button = new Views_Assessments_Forms_Controls_BackToReferrerButton();
                                    $back_button->render(
                                        array(
                                            "referrer_url" => Entrada_Utilities_FormStorageSessionHelper::determineReferrerURI($PROCESSED["fref"], $PROCESSED["rref"]),
                                            "referrer_type" => Entrada_Utilities_FormStorageSessionHelper::determineReferrerType($PROCESSED["fref"], $PROCESSED["rref"]),
                                            "css_classes" => "pull-right"
                                        )
                                    );
                                ?>
                            </div>

                            <div class="space-above large clear_both">
                                <a href="#delete-item-modal" data-toggle="modal" class="btn btn-danger space-right pull-left"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Items"); ?></a>
                                <?php if (!empty($form_referrer_data) || !empty($rubric_referrer_data)): ?>
                                    <input id="attach-selected-item-btn" type="submit" class="btn btn-success space-left pull-right" value="<?php echo $translate->_("Attach Selected"); ?>" />
                                    <?php $attach_items_link = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/items?section=add-item", $PROCESSED["fref"], $PROCESSED["rref"]); ?>
                                    <a class="btn btn-success space-left pull-right" href="<?php echo $attach_items_link; ?>"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Create & Attach a New Item"); ?></a>
                                <?php else: ?>
                                    <a href="<?php echo ENTRADA_URL; ?>/admin/assessments/items?section=add-item" class="btn btn-success space-left pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add A New Item"); ?></a>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div id="item-summary" class="space-below"></div>
                    </div>
                    <div id="search-container" class="hide space-below medium"></div>
                    <div id="item-summary"></div>
                    <div id="assessment-msgs">
                        <div id="assessment-items-loading" class="hide">
                            <p><?php echo $translate->_("Loading Assessment Items..."); ?></p>
                            <img src="<?php echo ENTRADA_URL."/images/loading.gif" ?>" />
                        </div>
                    </div>
                    <div id="item-table-container">
                        <table id="items-table" class="table table-bordered table-striped hide">
                            <thead>
                            <th width="5%"></th>
                            <th width="40%"><?php echo $translate->_("Item Code"); ?></th>
                            <th width="48%"><?php echo $translate->_("Item Type"); ?></th>
                            <th width="7%"><i class="icon-th-list"></i></th>
                            </thead>
                            <tbody>
                            <tr id="no-items">
                                <td colspan="4"><?php echo $translate->_("No Items to display"); ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="item-detail-container" class="hide"></div>
                </form>
                <div id="delete-item-modal" class="modal hide fade">
                    <form id="delete-item-modal-item" class="form-horizontal no-margin" action="<?php echo ENTRADA_URL . "/admin/assessments/items?section=api-items"; ?>" method="POST">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h1><?php echo $translate->_("Delete Items");?></h1></div>
                        <div class="modal-body">
                            <div id="no-items-selected" class="hide"><p><?php echo $translate->_("No items selected to delete.");?></p>
                            </div>
                            <div id="items-selected" class="hide">
                                <p><?php echo $translate->_("Please confirm you would like to delete the selected Items(s)?"); ?></p>
                                <div id="delete-items-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                <input id="delete-items-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row-fluid">
                    <a id="load-items" class="btn btn-block"><?php echo $translate->_("Load More Items"); ?> <span class="bleh"></span></a>
                </div>
            </div>
            <?php
            if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"])) {
                echo "<form id=\"search-targets-form\">";
                foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["items"]["selected_filters"] as $key => $filter_type) {
                    foreach ($filter_type as $target_id => $target_label) {
                        echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
                    }
                }
                echo "</form>";
            }
            break;
    }
}