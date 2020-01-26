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
            courses_subnavigation($course->toArray(), "cbme");

            switch ($STEP) {
                case 2 :
                    /**
                     * Validate objective sets and save all objective branches
                     */
                    if (isset($_POST["objective_sets"])) {
                        $branches = array();
                        $objective_model = new Models_Objective();

                        // Initialize an objective tree object
                        $tree_object = new Entrada_CBME_ObjectiveTree(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "course_id" => $COURSE_ID
                        ));

                        if (!$tree_object->getRootNodeID()) {
                            // Initialize a new tree. By default, this will create a primary tree if no other trees exist.
                            $tree_object->createNewTree();
                        }


                        if (isset($_POST["stages"]) && $tmp_input = clean_input($_POST["stages"], array("trim", "int"))) {
                            $stage = $tmp_input;
                        } else {
                            add_error($translate->_("No stage provided"));
                        }

                        if (isset($_POST["epas"]) && $tmp_input = clean_input($_POST["epas"], array("trim", "int"))) {
                            $epa = $tmp_input;
                        } else {
                            add_error($translate->_("No epa provided"));
                        }

                        if (isset($_POST["objective_sets"]["roles"])) {
                            foreach ($_POST["objective_sets"]["roles"] as $role_objective_id => $role_objective_code) {
                                $role_objective_id = clean_input($role_objective_id, array("trim", "int"));
                                $role_objective_code = clean_input($role_objective_code, array("trim", "striptags"));
                                if ($course->getCBMEMilestones()) {
                                    if (isset($_POST["objective_sets"]["milestones"])) {
                                        foreach ($_POST["objective_sets"]["milestones"] as $milestone_objective_id => $milestone_objective_code) {
                                            $milestone_objective_code = clean_input($milestone_objective_code, array("trim", "striptags"));
                                            $milestone_objective_id = clean_input($milestone_objective_id, array("trim", "int"));
                                            if (strpos($milestone_objective_code, $role_objective_code) !== false) {
                                                $code_pieces = explode(".", substr($milestone_objective_code, 2));
                                                $kc_code = $code_pieces[0];
                                                $ec_code = $code_pieces[0] . "." . $code_pieces[1];

                                                $settings = new Entrada_Settings();
                                                $course_settings = new Entrada_Course_Settings($COURSE_ID);

                                                /*
                                                 * If either the system setting or course setting for cbme_standard_kc_ec_objectives is true then use it.
                                                 */
                                                if ((int) $settings->read("cbme_standard_kc_ec_objectives") || (int) $course_settings->read("cbme_standard_kc_ec_objectives")) {
                                                    $cbme_standard_kc_ec_objectives = true;
                                                } else {
                                                    $cbme_standard_kc_ec_objectives = false;
                                                }

                                                /**
                                                 * Set the key and enabling competency codes and fetch their corresponding objectives
                                                 */
                                                if ($cbme_standard_kc_ec_objectives) {
                                                    $key_competency = $objective_model->fetchRowByShortnameCode("kc", $kc_code);
                                                    $enabling_competency = $objective_model->fetchRowByShortnameCode("ec", $ec_code);
                                                } else {
                                                    $key_competency = $objective_model->fetchRowByObjectiveCodeCourseID("kc", $kc_code, $COURSE_ID);
                                                    $enabling_competency = $objective_model->fetchRowByObjectiveCodeCourseID("ec", $ec_code, $COURSE_ID);
                                                }

                                                if ($key_competency) {
                                                    $kc_id = (int)$key_competency["objective_id"];
                                                } else {
                                                    add_error($translate->_("No key competency found"));
                                                }

                                                if ($enabling_competency) {
                                                    $ec_id = (int)$enabling_competency["objective_id"];
                                                } else {
                                                    add_error($translate->_("No enabling competency found"));
                                                }

                                                if (!$ERROR) {
                                                    $branches[] = array($stage, $epa, $role_objective_id, $kc_id, $ec_id, $milestone_objective_id);
                                                }
                                            }
                                        }
                                    } else {
                                        add_error($translate->_("No milestones provided"));
                                    }
                                } else {
                                    if (isset($_POST["objective_sets"]["enabling-competencies"])) {
                                        foreach ($_POST["objective_sets"]["enabling-competencies"] as $ec_objective_id => $ec_objective_code) {
                                            $ec_objective_code = clean_input($ec_objective_code, array("trim", "striptags"));
                                            $ec_objective_id = clean_input($ec_objective_id, array("trim", "int"));
                                            if (strpos($ec_objective_code, $role_objective_code) !== false) {
                                                $code_pieces = explode(".", substr($ec_objective_code, 0, 3));
                                                $kc_code = $code_pieces[0];

                                                $key_competency = $objective_model->fetchRowByShortnameCode("kc", $kc_code);
                                                if ($key_competency) {
                                                    $kc_id = (int)$key_competency["objective_id"];
                                                } else {
                                                    add_error($translate->_("No key competency found"));
                                                }

                                                $enabling_competency = $objective_model->fetchRowByShortnameCode("ec", $ec_objective_code);
                                                if ($enabling_competency) {
                                                    $ec_id = (int)$enabling_competency["objective_id"];
                                                } else {
                                                    add_error($translate->_("No enabling competency found"));
                                                }

                                                if (!$ERROR) {
                                                    $branches[] = array($stage, $epa, $role_objective_id, $kc_id, $ec_id);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            add_error($translate->_("No roles provided"));
                        }

                        if (!$ERROR) {
                            if ($branches) {
                                $branch_error = false;
                                foreach ($branches as $branch) {
                                    if (!$tree_object->addBranch($tree_object->getRootNodeID(), $branch)) {
                                        $branch_error = true;
                                    }
                                }
                            }
                        }

                        if ($branch_error) {
                            add_error($translate->_("A problem occurred while attempting to save an objective branch. Please try again later"));
                        }
                    } else {
                        add_error($translate->_("No objective sets provided"));
                    }

                    if (!$ERROR) {
                        $url = ENTRADA_URL . "/admin/courses/cbme?section=map-curriculumtags&id=" . $COURSE_ID;
                        $success_msg = sprintf($translate->_("All branches have been successfully saved. You will be redirected back to the Map Curriculum Tags interface. Please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                        add_success($success_msg);
                        $ONLOAD[] = "setTimeout(\"window.location='$url'\", 5000);";
                        echo display_success();
                    } else {
                        $STEP = 1;
                    }
                    break;
            }

            /**
             * Display any necessary messaging
             */
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_notice();
            }

            switch ($STEP) {
                case 1 :
                    // Initialize an objective tree object
                    $tree_object = new Entrada_CBME_ObjectiveTree(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                        "course_id" => $COURSE_ID
                    ));

                    if (!$tree_object->getRootNodeID()) {
                        // Initialize a new tree. By default, this will create a primary tree if no other trees exist.
                        $tree_object->createNewTree();
                    }

                    /**
                     * Get the json representation of this course's tree
                     */
                    $tree_json = $tree_object->fetchEpaBranchesParentChild();

                    /**
                     * Instantiate an objective model to fetch various objective "buckets"
                     */
                    $objective_model = new Models_Objective();

                    /**
                     * Get standard Stages
                     */
                    $stages = $objective_model->fetchChildrenByObjectiveSetShortname("stage", $ENTRADA_USER->getActiveOrganisation());

                    /**
                     * Get standard Roles
                     */
                    $standard_roles = $objective_model->fetchChildrenByObjectiveSetShortname("role", $ENTRADA_USER->getActiveOrganisation());

                    /**
                     * Get course specific Milestones
                     */
                    $course_milestones = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("milestone", $COURSE_ID);

                    /**
                     * Get standard Enabling Competencies
                     */
                    $enabling_competencies = $objective_model->fetchChildrenByObjectiveSetShortname("ec", $ENTRADA_USER->getActiveOrganisation());

                    /**
                     * Instantiate and render the curriculum mapping page view
                     */
                    $page_view = new Views_Course_Cbme_MapCurriculumTags_Page();
                    $page_view->render(
                        array(
                            "entrada_url" => ENTRADA_URL,
                            "course_id" => $COURSE_ID,
                            "module" => $MODULE,
                            "stages" => $stages,
                            "standard_roles" => $standard_roles,
                            "course_milestones" => $course_milestones,
                            "enabling_competencies" => $enabling_competencies,
                            "tree_json" => $tree_json,
                            "cbme_milestones" => $course->getCBMEMilestones()
                        )
                    );
                    break;
            }
        }
    } else {
        add_error($translate->_("You do not have the required permissions to edit this course resource."));

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifier when attempting to edit a course.");
    }
}