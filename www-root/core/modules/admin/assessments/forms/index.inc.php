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
 * The default file that is loaded when /admin/assessments/forms is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_FORMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $PREFERENCES = $assessments_base->getAssessmentPreferences($MODULE);

    $PROCESSED["filters"] = array();
    if (isset($PREFERENCES["forms"]["selected_filters"])) {
        $PROCESSED["filters"] = $PREFERENCES["forms"]["selected_filters"];
    }

    $PROCESSED["rubric_id"] = null;
    if (isset($_GET["rubric_id"]) && $tmp_input = clean_input($_GET["rubric_id"], "int")) {
        $PROCESSED["rubric_id"] = $tmp_input;
    }

    $PROCESSED["item_id"] = null;
    if (isset($_GET["item_id"]) && $tmp_input = clean_input($_GET["item_id"], "int")) {
        $PROCESSED["item_id"] = $tmp_input;
    }

    $assessments_base->updateAssessmentPreferences($MODULE);

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". sprintf($translate->_("%s Filters"), ucwords(str_replace("_", " ", $key))) . "</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li class='remove-single-filter' data-id='$target_id' data-filter='$key'>";
                $sidebar_html .= "<img src='" . ENTRADA_URL . "/images/checkbox-on.gif'/>";
                $sidebar_html .= "<span> ". html_encode($target_label) ."</span>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<input type=\"button\" id=\"clear-all-filters\" class=\"btn\" style=\"width: 100%\" value=\"Clear All Filters\"/>";
        new_sidebar_item($translate->_("Selected Form Bank Filters"), $sidebar_html, "assessment-filters", "open");
    }

    $PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef();
    $PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::getRubricRef();
    $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
    $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);
    $filter_by_single_rubric = false;
    $filter_by_single_item = false;
    $PROCESSED["filter_item_id"] = $PROCESSED["item_id"];
    // Prioritize item filtering over rubric filtering
    if ($PROCESSED["filter_item_id"]) {
        $filter_by_single_item = true;
    } else {
        $PROCESSED["filter_rubric_id"] = null;
        if ($PROCESSED["rubric_id"]) {
            $PROCESSED["filter_rubric_id"] = $PROCESSED["rubric_id"];
            $filter_by_single_rubric = true;
        } else if (!empty($rubric_referrer_data)) {
            $filter_by_single_rubric = true;
            $PROCESSED["filter_rubric_id"] = $rubric_referrer_data["rubric_id"];
        }
    }

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array("active" => "forms"));
    ?>
    <h1><?php echo $translate->_("Forms"); ?></h1>
    <?php
    if ($filter_by_single_rubric) {
        // Draw the rubric we're filtering by
        $rubric_data = $forms_api->fetchRubricData($PROCESSED["rubric_id"]);
        if (!empty($rubric_data)) {
            echo display_notice($translate->_("<strong>Please Note:</strong> You are viewing all forms associated with a single <strong>Grouped Item</strong>. If you wish to modify any of the forms listed below, clicking on its name will redirect you to that form in a new window."));
            $rubric_view = new Views_Assessments_Forms_Rubric(array("mode" => "editor", "rubric_state" => "editor-readonly"));
            $rubric_view->render(
                array(
                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                    "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                    "rubric_id" => $PROCESSED["rubric_id"],
                    "rubric_data" => $rubric_data
                )
            );
        } else {
            // rubric wasn't found, so don't filter by it
            $filter_by_single_rubric = false;
        }
    } else if ($filter_by_single_item) {
        $filter_by_single_item = true;

        $item_data = $forms_api->fetchItemData($PROCESSED["item_id"]);
        if (!empty($item_data)) {
            if (!$item_data["item"]["deleted_date"]) {
                $item_options = $forms_api->buildItemViewOptionsForRender($PROCESSED["filter_item_id"], true);
                if (!empty($item_options)) {
                    echo display_notice($translate->_("<strong>Please Note:</strong> You are viewing all forms associated with a single <strong>Item</strong>. If you wish to modify any of the forms listed below, clicking on its name will redirect you to that form in a new window."));
                    $item_view = new Views_Assessments_Forms_Item(array("mode" => "editor-readonly"));
                    $item_view->render($item_options);
                } else {
                    $filter_by_single_item = false;
                }
            } else {
                $filter_by_single_item = false;
            }
        } else {
            $filter_by_single_item = false;
        }
    }
    ?>
    <script type="text/javascript">
        var assessment_forms_localization = {};
        assessment_forms_localization.message_there_are_no_items_attached = "<?php echo $translate->_("There are currently no elements attached to this form."); ?>";
        assessment_forms_localization.comment_type_optional = "<?php echo $translate->_("optional") ?>";
        assessment_forms_localization.comment_type_mandatory = "<?php echo $translate->_("mandatory") ?>";
        assessment_forms_localization.comment_type_mandatory_flagged = "<?php echo $translate->_("mandatory for flagged responses") ?>";
        assessment_forms_localization.comment_type_disabled = "<?php echo $translate->_("disabled") ?>";

        var rubric_referrer_hash = "<?php echo $PROCESSED["rref"] ?>";
        <?php if ($filter_by_single_item): ?>
        var referrer_item_id = "<?php echo $PROCESSED["filter_item_id"] ? $PROCESSED["filter_item_id"] : 'null' ?>";
        var referrer_rubric_id = null;
        <?php elseif ($filter_by_single_rubric): ?>
        var referrer_rubric_id = "<?php echo $PROCESSED["filter_rubric_id"] ? $PROCESSED["filter_rubric_id"] : 'null' ?>";
        var referrer_item_id = null;
        <?php else: ?>
        var referrer_rubric_id = null;
        var referrer_item_id = null;
        <?php endif; ?>

        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url : "<?php echo ENTRADA_URL . "/admin/assessments/forms?section=api-forms" ; ?>",
                    resource_url: ENTRADA_URL,
                    filters : {
                        curriculum_tag : {
                            label : "<?php echo $translate->_("Curriculum Tags"); ?>",
                            data_source : "get-objectives",
                            secondary_data_source: "get-child-objectives"
                        },
                        author : {
                            label : "<?php echo $translate->_("Form Authors"); ?>",
                            data_source : "get-form-authors"
                        },
                        course : {
                            label : "<?php echo $translate->_("Courses"); ?>",
                            data_source : "get-user-courses"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No Items found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    list_selections: false,
                    results_parent: $("#assessment-forms-container"),
                    width: 400
                }
            );
        });
    </script>
    <script type="text/javascript" src="<?php echo ENTRADA_URL . "/javascript/assessments/forms/assessments-forms-admin.js?release=" . html_encode(APPLICATION_VERSION); ?>"></script>
    <div id="assessment-forms-container">
        <form id="form-table-form" action="<?php echo ENTRADA_URL . "/admin/assessments/forms?section=delete&step=1"; ?>" method="POST">
            <div class="row-fluid space-below">
                <div class="input-append space-right">
                    <input type="text" id="form-search" placeholder="<?php echo $translate->_("Begin typing to search the forms..."); ?>" class="input-large search-icon">
                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                </div>
                <div class="pull-right">
                    <?php if ($PROCESSED["rref"] && !empty($rubric_referrer_data)): ?>
                    <a href="<?php echo $rubric_referrer_data["referrer_url"] ?>" class="btn btn-default space-right"><i class="icon-circle-arrow-left"></i> <?php echo $translate->_("Back to Rubric"); ?></a>
                    <?php endif; ?>
                    <a href="#delete-form-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Form"); ?></a>
                    <a href="#add-form-modal" data-toggle="modal" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Form"); ?></a>
                </div>
            </div>
            <div id="assessment-msgs">
                <div id="assessment-forms-loading" class="hide">
                    <p><?php echo $translate->_("Loading Assessment Forms..."); ?></p>
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                </div>
            </div>
            <table class="table table-bordered table-striped" id="forms-table">
                <thead>
                <tr>
                    <th width="4%"></th>
                    <th width="71%"><?php echo $translate->_("Form Title"); ?></th>
                    <th width="15%"><?php echo $translate->_("Date Created"); ?></th>
                    <th width="10%"><?php echo $translate->_("Items"); ?></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </form>
        <?php
        // Render helpful modals
        $add_form_modal = new Views_Assessments_Forms_Modals_AddForm();
        $add_form_modal->render(array("action_url" => ENTRADA_URL."/admin/assessments/forms/?section=add-form"));

        $delete_form_modal = new Views_Assessments_Forms_Modals_DeleteForm();
        $delete_form_modal->render(array("action_url" => ENTRADA_URL . "/admin/assessments/forms?section=api-forms"));

        // Template for search results
        $search_result_template = new Views_Assessments_Forms_Templates_SearchResultFormRow();
        $search_result_template->render();
        ?>
        <div class="row-fluid">
            <a id="load-forms" class="btn btn-block"><?php echo $translate->_("Load More Forms"); ?></a>
        </div>
    </div>
    <?php if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"])): ?>
        <form id="search-targets-form">
            <?php foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"] as $key => $filter_type): ?>
                <?php foreach ($filter_type as $target_id => $target_label):
                    $target_id = html_encode($target_id);
                    $target_label = html_encode($target_label);
                    ?>
                    <input id="<?php echo "{$key}_{$target_id}"; ?>"
                           class="search-target-control <?php echo "{$key}_search_target_control"; ?>"
                           type="hidden"
                           name="<?php echo "{$key}[]"; ?>"
                           value="<?php echo $target_id; ?>"
                           data-id="<?php echo $target_id; ?>"
                           data-filter="<?php echo $key; ?>"
                           data-label="<?php echo $target_label; ?>"
                    />
                <?php endforeach; ?>
            <?php endforeach; ?>
        </form>
    <?php endif;
}