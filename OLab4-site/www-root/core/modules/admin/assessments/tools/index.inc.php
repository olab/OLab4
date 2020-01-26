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
 * The default file that is loaded when /admin/assessments/distributions is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT_TOOLS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["target_id"]) && $tmp_input = clean_input($_GET["target_id"], array("trim", "striptags"))) {
        $PROCESSED["target_id"] = $tmp_input;
    } else {
        add_error($translate->_("No target specified"));
    }
    if (!has_error()) {
        $user_details = Models_User::fetchRowByID($PROCESSED["target_id"]);
        if (empty($user_details)) {
            add_error($translate->_("Invalid target."));
        }
    }

    if (!has_error()) {
        /**
         * Fetch the current user's programs.
         */
        $user_courses = Models_Course::getCoursesByProxyIDOrganisationID($PROCESSED["target_id"], $ENTRADA_USER->getActiveOrganisation(), null, true);
        if (empty($user_courses)) {
            add_error(sprintf($translate->_("The specified target has no associated %s records."), strtolower($translate->_("course"))));
        }
    }

    if (!has_error()) {

        /**
         * When there is only one course, fetch course specific EPAs and then populate an array for the EPA instance of advancedSearch.
         */
        $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
        $objective_model = new Models_Objective();

        if (count($user_courses) == 1) {
            // Get the specific course info.
            $the_only_course = end($user_courses);
            $course_epas = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("epa", $the_only_course["course_id"]);
            $epas_tagged_to_forms = $forms_api->fetchEPANodesTaggedToForms($the_only_course["course_id"]);
            if (empty($epas_tagged_to_forms)) {
                add_error($translate->_("No EPAs have been defined."));
            } else {
                $epa_advanced_search_data = array();
                foreach ($epas_tagged_to_forms as $epa) {
                    $epa_advanced_search_data[] = array(
                        "target_id" => $epa["cbme_objective_tree_id"],
                        "target_label" => $epa["objective_code"] . ": " . substr($epa["objective_name"], 0, 65) . "...",
                        "target_title" => $epa["objective_code"] . " " . $epa["objective_name"]
                    );
                }
            }
        } else {
            // More than one course, leave it blank until they pick a relevant course.
            $course_epas = array();
            $epas_tagged_to_forms = array();
            $epa_advanced_search_data = array();
        }
    }
    if (!has_error()) {

        /**
         * Get assessment types
         */
        $assessment_method_model = new Models_Assessments_Method();
        $assessment_methods = $assessment_method_model->fetchAllByGroupOrganisationID(
            $ENTRADA_USER->getActiveGroup(),
            $ENTRADA_USER->getActiveOrganisation(),
            true
        );

        /**
         * Render the assessment tool interface
         */
        $assessment_tools_view = new Views_Assessments_Tools_Tool();
        $assessment_tools_view->render(
            array(
                "proxy_id" => $PROCESSED["target_id"],
                "course_epas" => $epa_advanced_search_data,
                "assessment_methods" => $assessment_methods,
                "user_courses" => $user_courses,
                "module" => $MODULE,
                "mode" => "admin",
                "target_details" => $user_details->toArray()
            )
        );

        /**
         * Render the assessment tool template interface
         */
        $assessment_tool_template = new Views_Assessments_Tools_Templates_ToolCard();
        $assessment_tool_template->render(
            array(
                "administrator_options" => array(
                    "show_form_preview" => false,
                )
            )
        );

    } else {
        echo display_error();
    }
}