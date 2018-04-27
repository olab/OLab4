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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_BLUEPRINTS"))) {
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
    $user_courses = array();
    $user_courses_records = $forms_api->fetchUserCourseList(($ENTRADA_USER->getActiveRole() == "admin"));
    $user_courses = array_map(
        function ($r) {
            return array("course_id" => $r->getID(), "course_name" => $r->getCourseName());
        },
        $user_courses_records
    );

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"] as $key => $filter_type) {
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
        new_sidebar_item($translate->_("Selected Form Template Filters"), $sidebar_html, "assessment-filters", "open");
    }

    $assessment_evaluation_tabs = new Views_Assessments_Dashboard_NavigationTabs();
    $assessment_evaluation_tabs->render(array(
        "active" => "blueprints",
        "group" => $ENTRADA_USER->getActiveGroup(),
        "role" => $ENTRADA_USER->getActiveRole()
    ));
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url : "<?php echo ENTRADA_URL . "/admin/assessments/blueprints?section=api-blueprints" ; ?>",
                    resource_url: ENTRADA_URL,
                    filters : {
                        epas : {
                            label : "<?php echo $translate->_("EPAs"); ?>",
                            data_source: "get-user-courses-filter",
                            secondary_data_source : "get-epas"
                        },
                        milestones : {
                            label : "<?php echo $translate->_("Milestones"); ?>",
                            data_source: "get-user-courses-filter",
                            secondary_data_source : "get-milestones"
                        },
                        contextual_variables : {
                            label : "<?php echo $translate->_("Contextual Variables"); ?>",
                            data_source: "get-contextual-variables"
                        },
                        author : {
                            label : "<?php echo $translate->_("Form Authors"); ?>",
                            data_source : "get-form-authors"
                        },
                        course : {
                            label : "<?php echo $translate->_("Courses"); ?>",
                            data_source : "get-user-courses"
                        },
                        form_types : {
                            label : "<?php echo $translate->_("Form Type"); ?>",
                            data_source : "get-form-types"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No Items found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    list_selections: false,
                    results_parent: $("#assessment-forms-blueprints-container"),
                    width: 400
                }
            );
        });
    </script>
    <script type="text/javascript" src="<?php echo ENTRADA_URL . "/javascript/assessments/forms/assessments-blueprints-admin.js?release=" . html_encode(APPLICATION_VERSION); ?>"></script>
    <div id="assessment-forms-blueprints-container">
        <form id="form-table-form" action="<?php echo ENTRADA_URL . "/admin/assessments/blueprints?section=delete&step=1"; ?>" method="POST">
            <div class="row-fluid space-below">
                <div class="input-append space-right">
                    <input type="text" id="blueprint-search" placeholder="<?php echo $translate->_("Begin typing to search the templates..."); ?>" class="input-large search-icon">
                    <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                </div>
                <div class="pull-right">
                    <?php if (isset($PROCESSED["rref"]) && !empty($rubric_referrer_data)): ?>
                        <a href="<?php echo $rubric_referrer_data["referrer_url"] ?>" class="btn btn-default space-right"><i class="icon-circle-arrow-left"></i> <?php echo $translate->_("Back to Rubric"); ?></a>
                    <?php endif; ?>
                    <a href="#delete-form-blueprint-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Form Template"); ?></a>
                    <a href="#add-form-modal" data-toggle="modal" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Form Template"); ?></a>
                </div>
            </div>
            <div id="assessment-msgs">
                <div id="assessment-forms-loading" class="hide">
                    <p><?php echo $translate->_("Loading Assessment Forms Templates..."); ?></p>
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                </div>
            </div>
            <table class="table table-bordered table-striped" id="blueprints-table">
                <thead>
                <tr>
                    <th width="4%"></th>
                    <th width="56%"><?php echo $translate->_("Template Title"); ?></th>
                    <th width="15%"><?php echo $translate->_("Date Created"); ?></th>
                    <th width="15%"><?php echo $translate->_("Type"); ?></th>
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
        $add_form_modal->render(
            array (
                "action_url" => ENTRADA_URL."/admin/assessments/forms/?section=add-form",
                "form_types" => Models_Assessments_Form_Type::fetchAllByOrganisationIDCategory($ENTRADA_USER->getActiveOrganisation(), "blueprint"),
                "user_courses" => $user_courses,
                "medtech_admin" => ($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech")
            )
        );

        $delete_form_modal = new Views_Assessments_Forms_Modals_DeleteFormBlueprint();
        $delete_form_modal->render(array("action_url" => ENTRADA_URL . "/admin/assessments/blueprints?section=api-blueprints"));

        // Template for search results
        $search_result_template = new Views_Assessments_FormBlueprints_Templates_SearchResultFormBlueprintRow();
        $search_result_template->render();
        ?>
        <div class="row-fluid">
            <a id="load-blueprints" class="btn btn-block"><?php echo $translate->_("Load More Templates"); ?></a>
        </div>
    </div>
    <?php if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"])): ?>
        <form id="search-targets-form">
            <?php foreach ($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["blueprints"]["selected_filters"] as $key => $filter_type): ?>
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