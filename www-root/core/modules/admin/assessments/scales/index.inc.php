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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_SCALES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Scales [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["scales"]["selected_view"]) ? $PREFERENCES["scales"]["selected_view"] : "list") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/scales/scales-admin.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". ucwords(str_replace("_", " ", $key)) . " {$translate->_("Filters")}</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li>";
                $sidebar_html .= "<a href=\"#\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data-filter=\"". html_encode($key) ."\">";
                $sidebar_html .= "<img src=\"". ENTRADA_URL ."/images/checkbox-on.gif\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data_filter=\"". html_encode($key) ."\" />";
                $sidebar_html .= "<span> ". html_encode($target_label) ."</span>";
                $sidebar_html .= "</a>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<a href=\"#\" class=\"clear-filters\">{$translate->_("Clear All Filters")}</a>";
        new_sidebar_item($translate->_("Selected Rating Scales Filters"), $sidebar_html, "assessment-filters", "open");
    }

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array(
        "active" => "scales",
        "group" => $ENTRADA_USER->getActiveGroup(),
        "role" => $ENTRADA_USER->getActiveRole()
    ));
    ?>
    <h1><?php echo $translate->_("Rating Scales"); ?></h1>
    <?php
    $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
    if (is_array($flash_messages)) {
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
    ?>
    <script type="text/javascript">
        var scale_localization = {};
        scale_localization.load_more_template = "<?php echo $translate->_("Showing %%current_results%% of %%total_scales%% total rating scales") ?>";
        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url: "<?php echo ENTRADA_URL . "/admin/assessments/scales?section=api-scales"; ?>",
                    resource_url: ENTRADA_URL,
                    filters: {
                        scale_type: {
                            label: "<?php echo $translate->_("Rating Scale Type"); ?>",
                            data_source: "get-scale-types"
                        },
                        author: {
                            label: "<?php echo $translate->_("Rating Scale Authors"); ?>",
                            data_source: "get-scale-authors"
                        },
                        course: {
                            label: "<?php echo $translate->_("Courses"); ?>",
                            data_source: "get-user-courses"
                        },
                        organisation: {
                            label: "<?php echo $translate->_("Organisations"); ?>",
                            data_source: "get-user-organisations"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No Rating Scales found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    results_parent: $("#assessment-scales-container"),
                    width: 400,
                    list_selections: false
                }
            );
        });
    </script>
    <div id="assessment-scales-container">
        <form id="scale-table-form" action="<?php echo ENTRADA_URL."/admin/assessments/scales?step=2"; ?>" method="POST">
            <div class="row-fluid space-below">
                <div class="input-append space-right">
                    <input type="text" id="scale-search" placeholder="<?php echo $translate->_("Begin typing to search..."); ?>" class="input-large search-icon">
                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                </div>
                <div class="pull-right">
                    <a href="#delete-scale-modal" data-toggle="modal" class="btn btn-danger space-right" id="delete-scales"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Rating Scale"); ?></a>
                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>?section=add-scale" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Rating Scale"); ?></a>
                </div>
            </div>
            <div id="assessment-msgs">
                <div id="assessment-items-loading" class="hide">
                    <p><?php echo $translate->_("Loading Rating Scales..."); ?></p>
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                </div>
            </div>
            <div id="msgs"></div>
            <div id="scale-table-container">
                <table class="table table-bordered table-striped" id="scales-table" summary="<?php $translate->_("List of Rating Scales") ?>">
                    <colgroup>
                        <col class="modified" />
                        <col class="title" />
                        <col class="actions" />
                    </colgroup>
                    <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="40%"><?php echo $translate->_("Title"); ?></th>
                        <th width="35%"><?php echo $translate->_("Type"); ?></th>
                        <th width="20%"><?php echo $translate->_("Date Created"); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </form>
        <?php // TODO: Move this to a view ?>
        <div id="delete-scale-modal" class="modal hide fade">
            <form id="delete-scale-form-modal" class="form-horizontal no-margin" action="<?php echo ENTRADA_URL."/admin/assessments/scales?section=api-scales"; ?>" method="POST">
                <div class="modal-header"><h1><?php echo $translate->_("Delete Rating Scale"); ?></h1></div>
                <div class="modal-body">
                    <div id="no-scales-selected" class="hide">
                        <p><?php echo $translate->_("No Rating Scales selected to delete."); ?></p>
                    </div>
                    <div id="scales-selected" class="hide">
                        <p><?php echo $translate->_("Delete Rating Scale"); ?></p>
                        <div id="delete-scales-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="delete-scale-cancel" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" id="delete-scales-modal-delete" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div class="row-fluid">
            <a id="load-scales" class="btn btn-block"><?php echo $translate->_("Load More Rating Scales"); ?></a>
        </div>
    </div>
    <?php
    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"])) {
        echo "<form id=\"search-targets-form\">";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"] as $key => $filter_type) {
            foreach ($filter_type as $target_id => $target_label) {
                echo "<input id=\"" . html_encode($key) . "_" . html_encode($target_id) . "\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"" . html_encode($key) . "[]\" value=\"" . html_encode($target_id) . "\" data-id=\"" . html_encode($target_id) . "\" data-filter=\"" . html_encode($key) . "\" data-label=\"" . html_encode($target_label) . "\"/>";
            }
        }
        echo "</form>";
    }
}