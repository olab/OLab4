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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"%s\">%s</a> for assistance."), "mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $course = Models_Course::get($COURSE_ID);
    if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
        include("cbme-setup.inc.php");
        if ($course && $cbme_checked) {
            /**
             * Generate the course sub navigation
             */
            courses_subnavigation($course->toArray(), "cbme");

            /**
             * Initialize the dataset options array
             */
            $dataset_options = array();

            /**
             * Initialize an objective tree object
             */
            $tree_object = new Entrada_CBME_ObjectiveTree(array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "course_id" => $course->getID()
            ));

            /**
             * Initialize a new tree if there isn't one. By default, this will create a primary tree if no other trees exist.
             */
            if (!$tree_object->getRootNodeID()) {
                $tree_object->createNewTree();
            }

            /**
             * If EPA objectives are present in the course objective tree fetch those for editing, otherwise
             * nothing has been mapped in the tree so fetch the course EPAs based on the cbme_course_objectives table.
             */
            $tree_epas = $tree_object->fetchTreeNodesByObjectiveSetShortname("epa");
            if ($tree_epas) {
                $dataset_options[] = "course_epas";
            } else {
                $dataset_options[] = "unmapped_course_epas";
            }

            /**
             * Fetch course specific Milestones
             */
            $objective_model = new Models_Objective();
            $course_milestones = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("milestone", $COURSE_ID);


            /**
             * If Enabling Competency objectives are present in the course objective tree fetch those for editing, otherwise
             * nothing has been mapped in the tree so fetch the course EPAs based on the cbme_course_objectives table.
             */
            $tree_ecs = $tree_object->fetchTreeNodesByObjectiveSetShortname("ec");
            if ($tree_ecs) {
                $dataset_options[] = "course_enabling_competencies";
            } else {
                $dataset_options[] = "unmapped_course_enabling_competencies";
            }

            /**
             * If Key Competency objectives are present in the course objective tree fetch those for editing, otherwise
             * nothing has been mapped in the tree so fetch the course EPAs based on the cbme_course_objectives table.
             */
            $tree_kcs = $tree_object->fetchTreeNodesByObjectiveSetShortname("kc");
            if ($tree_kcs) {
                $dataset_options[] = "course_key_competencies";
            } else {
                $dataset_options[] = "unmapped_course_key_competencies";
            }

            /**
             * Fetch course specific Contextual Variable Responses
             */
            $contextual_variable_responses = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("contextual_variable_responses", $COURSE_ID);

            /**
             * Instantiate the CBME visualization abstraction layer
             */
            $cbme_progress_api = new Entrada_CBME_Visualization(array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "actor_course_id" => $course->getID(),
                "datasource_type" => "progress",
                "limit_dataset" => $dataset_options
            ));

            $dataset = $cbme_progress_api->fetchData();

            /**
             * Get the system setting for standard Key and Enabling Competencies
             */
            $settings = new Entrada_Settings();
            $course_settings = new Entrada_Course_Settings($course->getID());

            if ((int) $settings->read("cbme_standard_kc_ec_objectives")) {
                $cbme_standard_kc_ec_objectives = true;
            } else {
                $cbme_standard_kc_ec_objectives = false;
            }

            if ((int) $course_settings->read("cbme_standard_kc_ec_objectives")) {
                $course_cbme_standard_kc_ec_objectives = true;
            } else {
                $course_cbme_standard_kc_ec_objectives = false;
            }

            /**
             * Instantiate the view for CBME data import
             */
            $import_tag_view = new Views_Course_Cbme_ImportData_Page();
            $import_tag_view->render(array(
                "entrada_url" => ENTRADA_URL,
                "course_id" => $COURSE_ID,
                "module" => $MODULE,
                "course_epas" => (!empty($dataset["course_epas"]) ? $dataset["course_epas"] : $dataset["unmapped_course_epas"]),
                "course_milestones" => $course_milestones,
                "course_enabling_competencies" => (!empty($dataset["course_enabling_competencies"]) ? $dataset["course_enabling_competencies"] : $dataset["unmapped_course_enabling_competencies"]),
                "course_key_competencies" => (!empty($dataset["course_key_competencies"]) ? $dataset["course_key_competencies"] : $dataset["unmapped_course_key_competencies"]),
                "contextual_variable_responses" => $contextual_variable_responses,
                "cbme_milestones" => $course->getCBMEMilestones(),
                "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "cbme_standard_kc_ec_objectives" => $cbme_standard_kc_ec_objectives,
                "course_cbme_standard_kc_ec_objectives" => $course_cbme_standard_kc_ec_objectives,
            ));
        }
    } else {
        add_error($translate->_("You do not have the required permissions to edit this course"));

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifer when attempting to edit a course.");
    }
}