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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {

    /**
     * Validate that the given distribution and delegation IDs correspond. Return the corresponding
     * delegation and distribution objects via the $delegation and $distribution parameters respectively.
     *
     * @param int $distribution_id
     * @param object $distribution
     * @param int $delegation_id
     * @param object $delegation
     * @return bool
     */
    function validateDistributionAndDelegation ($distribution_id, &$distribution, $delegation_id, &$delegation) {
        global $translate;
        $status = false;
        if (!$distribution_id) {
            add_error($translate->_("Invalid distribution ID."));
        } else if (!$delegation_id) {
            add_error($translate->_("Invalid delegation ID."));
        } else {
            $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_id);
            if (!$distribution) {
                add_error($translate->_("Unable to fetch distribution record."));
            } else {
                $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($delegation_id);
                if (!$delegation) {
                    add_error($translate->_("Unable to fetch delegation record."));
                } else {
                    if ($delegation->getDistributionID() != $distribution_id) {
                        add_error($translate->_("Delegation and distribution records do not match."));
                    } else {
                        $status = true; // Successfully validated
                    }
                }
            }
        }
        return $status;
    }

    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    $current_id = (int)$ENTRADA_USER->getActiveId();

    $distribution = null;
    $delegation = null;
    $distribution_id = isset($request["adistribution_id"]) ? clean_input($request["adistribution_id"], array("int")) : null;
    $delegation_id = isset($request["addelegation_id"]) ? clean_input($request["addelegation_id"], array("int")) : null;

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "add-additional-assessors":
                    $additional_assessors = @$request["selected_additional_list"];
                    $checked_assessors = @$request["selected_available_list"];
                    $new_assessors = array();
                    $selected_assessors = array();
                    $course_contact_model = new Models_Assessments_Distribution_CourseContact();

                    // parse input, add new assessors to distribution assessor list
                    validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation); // adds errors if failed validation
                    if (!$ERROR) {
                        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_id);
                        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));
                        $possible_assessors = $distribution_delegation->getPossibleAssessors();

                        if (empty($possible_assessors)) {
                            add_error($translate->_("No possible assessors found"));
                        }

                        if (!is_array($additional_assessors) || empty($additional_assessors)) {
                            add_error($translate->_("No additional assessors selected."));
                        } else {
                            foreach ($additional_assessors as $additional_assessor) {
                                $assessors_info = explode("_", $additional_assessor);
                                $assessor_type = $assessors_info[0];
                                $assessor_value = $assessors_info[1];
                                $new_assessors[$assessor_value] = array("assessor_type" => ($assessor_type == "external") ? "external_hash" : "proxy_id", "assessor_value" => $assessor_value, "original_type" => $assessor_type);
                                foreach ($possible_assessors as $pa) {
                                    if ($pa["assessor_value"] == $assessor_value && $pa["assessor_type"] == $assessor_type) {
                                        add_error(sprintf($translate->_("\"%s\" is already in the list of assessors."), $pa["name"]));
                                    }
                                }
                            }
                            if (!$ERROR && !empty($new_assessors)) {
                                // no new errors, add those to the distribution record and repost
                                foreach ($new_assessors as $new_assessor) {
                                    $new_assessment_assessor = new Models_Assessments_Distribution_Assessor(array(
                                            "adistribution_id"=> $distribution_id,
                                            "assessor_type" => $new_assessor["assessor_type"],
                                            "assessor_role" => "any",
                                            "assessor_scope" => "self",
                                            "assessor_value" => $new_assessor["assessor_value"]
                                        )
                                    );
                                    if (!$new_assessment_assessor->insert()) {
                                        add_error($translate->_("Error creating assessor. Please try again later."));
                                    }
                                    // Add them to the "selected assessor" list, so that when the page is reloaded, it auto populates the newly created checkbox(es)
                                    $selected_assessors[] = "assessor-{$new_assessor["original_type"]}-{$new_assessor["assessor_value"]}";

                                    if (!$ERROR && $new_assessor["assessor_value"]) {
                                        $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $new_assessor["assessor_value"], $new_assessor["original_type"]);
                                    }
                                }
                            }
                            if (is_array($checked_assessors) && !empty($checked_assessors)) {
                                // Format the list of selected assessor (checked checkboxes) so that they can be reposted and easily consumed on page reload.
                                foreach ($checked_assessors as $checked_assessor) {
                                    $checked_info = explode("_", $checked_assessor);
                                    $checked_type = $checked_info[0];
                                    $checked_value = $checked_info[1];
                                    $selected_assessors[] = "assessor-$checked_type-$checked_value";
                                }
                            }
                        }
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("additional_assessors" => $new_assessors, "checked_assessors" => $selected_assessors)));
                    }
                break;
                case "query-assessor-selections":
                    $duplicates = array();
                    $duplicates_error = false;
                    $allow_duplicates = (isset($request["allow_duplicates"]))?(int)$request["allow_duplicates"] : 0;

                    $assessors = $request["assessor_list"];
                    $targets = $request["target_list"];

                    validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation); // adds errors if failed validation
                    if (!$ERROR) {
                        if (empty($assessors) || empty($targets)) {
                            add_error($translate->_("No assessors or targets selected."));
                        } else {

                            // If the distribution is repeat based, duplicates are allowed.
                            if ($distribution) {
                                $schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                                if ($schedule && $schedule->getScheduleType() == "repeat") {
                                    $allow_duplicates = true;
                                }
                            }

                            if (!$allow_duplicates) {
                                $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));
                                $duplicates = $distribution_delegation->findDuplicateDelegatedAssessments($targets, $assessors);

                                if (!empty($duplicates)) {
                                    $duplicates_error = true;
                                }
                                if ($duplicates_error) {
                                    add_error($translate->_("There are one or more duplicates."));
                                }
                            }
                        }
                    }
                    if ($ERROR) {
                        if ($duplicates_error) {
                            echo json_encode(array("status" => "duplicates_error", "data" => $ERRORSTR, "duplicates" => $duplicates));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "success", "data" => array()));
                    }
                break;
                case "create-assessments":
                    $assessors = $request["assessor_list"];
                    $targets = $request["target_list"];
                    $status = false;
                    $course_contact_model = new Models_Assessments_Distribution_CourseContact();

                    if (isset($request["auto_mark_complete"]) && $tmp_input = clean_input($request["auto_mark_complete"], array("int"))) {
                        $PROCESSED["auto_mark_complete"] = $tmp_input;
                    } else {
                        $PROCESSED["auto_mark_complete"] = null;
                    }

                    if (validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation)) {
                        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_id);
                        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id"=>$delegation->getID()));
                        $status = $distribution_delegation->createDelegatedAssessments($current_id, $targets, $assessors);
                        if (!is_null($PROCESSED["auto_mark_complete"])) {
                            $distribution_delegation->completeDelegation($current_id, $translate->_("Delegation automatically marked complete."));
                        }

                        foreach ($assessors as $assessor) {
                            if (!$ERROR && $assessor["assessor_value"]) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor["assessor_value"], $assessor["assessor_type"]);
                            }
                        }
                    }
                    if ($status) {
                        // Process distribution options and apply them as assessment options as necessary.
                        $assessments_base = new Entrada_Assessments_Base();
                        $assessments_base->processDistributionAssessmentOptions($distribution_id);

                        echo json_encode(array("status" => "success", "data" => array()));
                    } else {
                        add_error($translate->_("Unable to add delegated assessments."));
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "remove-assessor":
                    $target_type = clean_input($request["target_type"], array("notags", "trim"));
                    $assessor_type = clean_input($request["assessor_type"], array("notags", "trim"));
                    $removal_reason = clean_input($request["removal_reason"], array("notags", "trim"));
                    $target_id = clean_input($request["target_id"], array("int"));
                    $assessor_id = clean_input($request["assessor_id"], array("int"));
                    $assessment_id = clean_input($request["assessment_id"], array("int"));
                    $addassignment_id = clean_input($request["addassignment_id"], array("int"));
                    $removal_reason_id = clean_input($request["removal_reason_id"], array("int"));
                    $status = false;

                    if (!$removal_reason) {
                        add_error($translate->_("Please enter a reason for removal."));
                    } else {
                        if (validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation)) {
                            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));
                            $status = $distribution_delegation->removeDelegatedAssessor($current_id, $addassignment_id, $assessment_id, $target_type, $target_id, $removal_reason_id, $removal_reason);
                            if (!$status) {
                                add_error($translate->_("Unable to remove distribution assessor."));
                            }
                        } else {
                            add_error($translate->_("Unable to remove delegated assessor."));
                        }
                    }

                    if ($status) {
                        echo json_encode(array("status" => "success", "data" => array()));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "complete-delegation":
                    $status = false;
                    $completed_reason = isset($request["completed_reason"]) ? clean_input($request["completed_reason"], array("notags", "trim")) : NULL;

                    if (validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation)) {
                        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id"=>$delegation->getID()));
                        $status = $distribution_delegation->completeDelegation($current_id, $completed_reason);
                    }
                    if ($status) {
                        echo json_encode(array("status" => "success", "data" => array()));
                    } else {
                        add_error($translate->_("Unable to mark the delegation as completed."));
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "add-external-assessor" :
                    $course_contact_model = new Models_Assessments_Distribution_CourseContact();

                    if (isset($request["firstname"]) && $tmp_input = clean_input($request["firstname"], array("trim", "striptags"))) {
                        $PROCESSED["firstname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Firstname</strong> for this assessor."));
                    }

                    if (isset($request["lastname"]) && $tmp_input = clean_input($request["lastname"], array("trim", "striptags"))) {
                        $PROCESSED["lastname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Lastname</strong> for this assessor."));
                    }

                    if (isset($request["email"]) && $tmp_input = clean_input($request["email"], array("trim", "striptags"))) {
                        $PROCESSED["email"] = $tmp_input;
                        if (!valid_address($PROCESSED["email"])) {
                            add_error($translate->_("Please provide a <strong>valid E-Mail Address</strong> for this assessor."));
                        }
                    } else {
                        add_error($translate->_("Please provide a <strong>E-Mail Address</strong> for this assessor."));
                    }

                    validateDistributionAndDelegation($distribution_id, $distribution, $delegation_id, $delegation);
                    if (!$ERROR) {
                        $is_internal_user = Models_Assessments_Distribution_ExternalAssessor::internalUserExists($PROCESSED["email"]);
                        $is_external_user = Models_Assessments_Distribution_ExternalAssessor::externalUserExists($PROCESSED["email"]);
                        $distribution =  Models_Assessments_Distribution::fetchRowByID($distribution_id);

                        if (!$is_internal_user && !$is_external_user) {
                            $external_assessor = new Models_Assessments_Distribution_ExternalAssessor(
                                array(
                                    "firstname"     => $PROCESSED["firstname"],
                                    "lastname"      => $PROCESSED["lastname"],
                                    "email"         => $PROCESSED["email"],
                                    "created_date"  => time(),
                                    "created_by"    => $ENTRADA_USER->getActiveID(),
                                    "updated_date"  => time(),
                                    "updated_by"    => $ENTRADA_USER->getActiveID()
                                )
                            );

                            if ($external_assessor->insert()) {
                                if (!$ERROR && $external_assessor->getID()) {
                                    $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $external_assessor->getID(), "external");
                                    echo json_encode(array("status" => "success", "data" => array("id" => $external_assessor->getID(), "firstname" => $PROCESSED["firstname"], "lastname" => $PROCESSED["lastname"], "email" => $PROCESSED["email"])));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to save this assessor. Please try again later"))));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to save this assessor. Please try again later"))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array(sprintf($translate->_("There is already a %s user with E-Mail address <strong>%s</strong>"), APPLICATION_NAME, $PROCESSED["email"]))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
            }
            break;

        case "GET" :
            switch ($request["method"]) {
                case "get-organisation-users" :
                    if (isset($request["term"]) && $tmp_input = clean_input(strtolower($request["term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $internal_users = Models_Organisation::fetchOrganisationUsers($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation());
                    $data = array();
                    if ($internal_users) {
                        foreach ($internal_users as $user) {
                            $data[] = array("value" => $user["proxy_id"], "label" => html_encode("{$user["firstname"]} {$user["lastname"]}"), "lastname" => html_encode($user["lastname"]), "role" => html_encode($translate->_("Internal")), "email" => html_encode($user["email"]));
                        }
                    }

                    $external_users = Models_Assessments_Distribution_ExternalAssessor::fetchAllBySearchValue($PROCESSED["search_value"], null);
                    if ($external_users) {
                        foreach ($external_users as $user) {
                            $data[] = array("value" => $user["eassessor_id"], "label" => html_encode("{$user["firstname"]} {$user["lastname"]}"), "lastname" => html_encode($user["lastname"]), "role" => html_encode($translate->_("External")), "email" => html_encode($user["email"]));
                        }
                    }

                    if ($data) {
                        echo json_encode($data);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;
                default:
                    break;
            }
            break;
    }
    exit;
}