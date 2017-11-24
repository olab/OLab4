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
 * API to handle interaction with assessment distributions
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_DISTRIBUTIONS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request = ${"_" . $request_method};

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "distribution-wizard" :
                    if (isset($request["step"]) && $tmp_input = clean_input($request["step"], array("trim", "int"))) {
                        $PROCESSED["step"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Step provided."));
                    }

                    if (isset($request["next_step"]) && $tmp_input = clean_input($request["next_step"], array("trim", "int"))) {
                        $PROCESSED["next_step"] = $tmp_input;
                    }

                    if (isset($request["mode"]) && $tmp_input = clean_input($request["mode"], array("trim", "striptags"))) {
                        $PROCESSED["mode"] = strtolower($tmp_input);
                    }

                    if (isset($PROCESSED["step"])) {
                        switch ($PROCESSED["step"]) {
                            case 1 :
                                $distribution_controller = new Controllers_Assessment_Distribution($request, false, true, 1);
                                $PROCESSED = $distribution_controller->getValidatedData();

                                $next_step = 2;
                            break;
                            case 2 :

                                $distribution_controller = new Controllers_Assessment_Distribution($request, false, true, array(1, 2));
                                $PROCESSED = $distribution_controller->getValidatedData();

                                $next_step = 3;
                            break;
                            case 3 :
                                $distribution_controller = new Controllers_Assessment_Distribution($request, false, true, array(1, 2, 3));
                                $PROCESSED = $distribution_controller->getValidatedData();

                                $next_step = 4;
                            break;
                            case 4 :
                                $distribution_controller = new Controllers_Assessment_Distribution($request, false, true, array(1, 2, 3, 4));
                                $PROCESSED = $distribution_controller->getValidatedData();

                                $next_step = 5;
                                break;
                            case 5 :
                                if (isset($PROCESSED["mode"]) && $PROCESSED["mode"] == "copy") {
                                    unset($request["adistribution_id"]); // remove distribution id, so that a new record will be created.
                                }
                                $distribution_controller = new Controllers_Assessment_Distribution($request, false, true, array(1, 2, 3, 4, 5));
                                if (!has_error()) {
                                    $distribution_controller->save();
                                }

                                $next_step = 6;
                            break;
                            default :
                                add_error($translate->_("Invalid step provided"));
                            break;
                        }

                        if (!$ERROR) {

                            if (isset($PROCESSED["next_step"])) {
                                $step = $PROCESSED["next_step"];
                            } else {
                                $step = $next_step;
                            }

                            //echo $step; exit;

                            $data = array("step" => $step);

                            if (isset($previous_step)) {
                                $data["previous_step"] = $previous_step;
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                break;
                case "remove-filter" :
                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter type provided."));
                    }

                    if (isset($request["filter_target"]) && $tmp_input = clean_input($request["filter_target"], array("trim", "int"))) {
                        $PROCESSED["filter_target"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter target provided."));
                    }

                    $assessments_base = new Entrada_Utilities_Assessments_Base();

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"]);
                            }
                        }

                        $assessments_base->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                break;
                case "remove-all-filters" :
                    $assessments_base = new Entrada_Utilities_Assessments_Base();
                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"]);
                    $assessments_base->updateAssessmentPreferences("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                break;
                case "add-external-assessor" :
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

                    if (!$ERROR) {
                        $is_internal_user = Models_Assessments_Distribution_ExternalAssessor::internalUserExists($PROCESSED["email"]);
                        $is_external_user = Models_Assessments_Distribution_ExternalAssessor::externalUserExists($PROCESSED["email"]);

                        if (!$is_internal_user && !$is_external_user) {
                            $external_assessor_model = new Models_Assessments_Distribution_ExternalAssessor();
                            //$course_contact_model = new Models_Assessments_Distribution_CourseContact();

                            $assessor_value = $external_assessor_model->insertExternalAssessorRecord($PROCESSED["firstname"], $PROCESSED["lastname"], $PROCESSED["email"]);

                            /*if (!$ERROR && $assessor_value) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor_value, "external");
                            }*/

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "data" => array("id" => $assessor_value, "firstname" => $PROCESSED["firstname"], "lastname" => $PROCESSED["lastname"], "email" => $PROCESSED["email"])));
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
                case "generate-pdf":
                    if (isset($_POST["task_data"])) {
                        //The string is being encoded twice (once by the post and another by the stringfy), so it needs to be decoded twice.
                        $json = json_decode($_POST["task_data"], true);
                        $json = json_decode($json, true);

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        foreach ($json as $i => $task_data_array) {
                            if (isset($task_data_array["target_id"]) && $tmp_input = clean_input($task_data_array["target_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["target_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target id provided."));
                            }

                            if (isset($task_data_array["dassessment_id"]) && $tmp_input = clean_input($task_data_array["dassessment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["dassessment_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessment id provided."));
                            }

                            if (isset($task_data_array["assessor_value"]) && $tmp_input = clean_input($task_data_array["assessor_value"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["assessor_value"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor value provided."));
                            }

                            if (isset($task_data_array["assessor_name"]) && $tmp_input = clean_input($task_data_array["assessor_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["assessor_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor name provided."));
                            }

                            if (isset($task_data_array["target_name"]) && $tmp_input = clean_input($task_data_array["target_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["target_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target name provided."));
                            }

                            if (isset($task_data_array["aprogress_id"]) && $tmp_input = clean_input($task_data_array["aprogress_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = $tmp_input;
                            } else {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = null;
                            }

                            if (isset($task_data_array["adistribution_id"]) && $tmp_input = clean_input($task_data_array["adistribution_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["adistribution_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("No distribution id was provided."));
                            }
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                    }
                    
                    if (!$ERROR) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadMultiple($PROCESSED);
                    }
                    break;
                case "generate-pdf-for-tasks":
                    if (isset($_POST["task_data"])) {
                        //The string is being encoded twice (once by the post and another by the stringfy), so it needs to be decoded twice.
                        $json = json_decode($_POST["task_data"], true);
                        $json = json_decode($json, true);

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        foreach ($json as $i => $task_data_array) {
                            if (isset($task_data_array["target_id"]) && $tmp_input = clean_input($task_data_array["target_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["target_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target id provided."));
                            }

                            if (isset($task_data_array["dassessment_id"]) && $tmp_input = clean_input($task_data_array["dassessment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["dassessment_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessment id provided."));
                            }

                            if (isset($task_data_array["assessor_value"]) && $tmp_input = clean_input($task_data_array["assessor_value"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["assessor_value"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor value provided."));
                            }

                            if (isset($task_data_array["assessor_name"]) && $tmp_input = clean_input($task_data_array["assessor_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["assessor_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor name provided."));
                            }

                            if (isset($task_data_array["target_name"]) && $tmp_input = clean_input($task_data_array["target_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["target_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target name provided."));
                            }

                            if (isset($task_data_array["adistribution_id"]) && $tmp_input = clean_input($task_data_array["adistribution_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["adistribution_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("No distribution id was provided."));
                            }

                            if (isset($task_data_array["aprogress_id"]) && $tmp_input = clean_input($task_data_array["aprogress_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = $tmp_input;
                            } else {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = null;
                            }
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                    }

                    if (!$ERROR) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadSingle($PROCESSED);
                    } else {
                        display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
                    }
                    break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-distributions" :
                    if (isset($request["search_value"]) && $request["search_value"] != "" && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    if (isset($request["distribution_editor_referrer"]) && $tmp_input = clean_input(strtolower($request["distribution_editor_referrer"]), array("trim", "striptags"))) {
                        $PROCESSED["distribution_editor_referrer"] = $tmp_input;
                    } else {
                        $PROCESSED["distribution_editor_referrer"] = "";
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }
                    
                    $filters = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"])) {
                        $filters = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["distributions"]["selected_filters"];
                    }

                    $distributions = Models_Assessments_Distribution::fetchFilteredDistributions($PROCESSED["search_value"], $filters, $PROCESSED["offset"]);
                    if ($distributions) {
                        $data = array();
                        $data["total_records"] = Models_Assessments_Distribution::countAllDistributions($PROCESSED["search_value"], $filters);
                        foreach ($distributions as $distribution) {
                            $data["distributions"][] = array("adistribution_id" => $distribution["adistribution_id"], "title" => $distribution["title"], "course_name" => $distribution["course_name"], "updated_date" => (is_null($distribution["updated_date"]) ? "N/A" : date("Y-m-d", $distribution["updated_date"])), "start_date" => date("Y-m-d", $distribution["start_date"]), "finish_date" => date("Y-m-d", $distribution["finish_date"]), "curriculum_period_title" => (($distribution["curriculum_period_title"] == null) ? "" : $distribution["curriculum_period_title"]));
                        }
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No distributions were found.")));
                    }
                break;
                case "get-filtered-audience" :
                    if (isset($request["adistribution_id"]) && $tmp_input = clean_input(strtolower($request["adistribution_id"]), array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    } else {
                        $PROCESSED["adistribution_id"] = false;
                    }

                    if (isset($request["term"]) && $tmp_input = clean_input(strtolower($request["term"]), array("trim", "striptags"))) {
                        $PROCESSED["term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["term"] = "";
                    }

                    if (isset($request["author_type"]) && $tmp_input = clean_input($request["author_type"], array("trim", "striptags"))) {
                        $PROCESSED["author_type"] = $tmp_input;
                    }

                    $results = Models_Assessments_Distribution_Author::fetchAvailableAuthors($PROCESSED["author_type"], 0, $PROCESSED["term"]);
                    $data = array();

                    if ($results) {
                        foreach ($results as $user) {
                            $data[] = array("value" => $user["proxy_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "group" => $user["group"],  "role" => $user["role"], "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode($data);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No audience found.")));
                    }
                    break;
                case "get-current-user-data" :
                    if ($ENTRADA_USER->getID()) {
                        echo json_encode(array("status" => "success", "current_id" => $ENTRADA_USER->getID(), "name" => $ENTRADA_USER->getFullname(false)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No current user found.")));
                    }
                    break;
                case "get-distribution-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $authors = Models_Assessments_Distribution_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                            $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                        }
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No authors were found.")));
                    }
                break;
                case "get-user-courses" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($user_courses) {
                        $data = array();
                        foreach ($user_courses as $course) {
                            $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                break;    
                case "get-user-organisations" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user = new Models_User(array("id" => $ENTRADA_USER->getActiveId()));
                    $user_organisations = $user->getOrganisations($PROCESSED["search_value"]);

                    if ($user_organisations) {
                        $data = array();
                        foreach ($user_organisations as $organisation) {
                            $data[] = array("target_id" => $organisation["organisation_id"], "target_label" => $organisation["organisation_title"]);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No organisations were found.")));
                    }
                break;
                case "get-user-cperiod" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($user_curriculum_types) {
                        $data = array();
                        foreach ($user_curriculum_types as $curriculum_type) {
                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeSearchTerm($curriculum_type->getID(), $PROCESSED["search_value"]);
                            if ($curriculum_periods) {
                                foreach ($curriculum_periods as $curriculum_period) {
                                    $data[] = array("target_id" => $curriculum_period->getCperiodID(), "target_label" => ($curriculum_period->getCurriculumPeriodTitle() ? $curriculum_period->getCurriculumPeriodTitle() : date("Y-m-d", $curriculum_period->getStartDate()) . " to " . date("Y-m-d", $curriculum_period->getFinishDate())));
                                }
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No curriculum periods were found.")));
                    }
                break;
                case "get-user-forms" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }
                    
                    $forms = Models_Assessments_Form::fetchAllByOwner($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["limit"], $PROCESSED["offset"]);

                    if ($forms) {
                        $data = array();
                        foreach ($forms as $form) {
                            $data[] = array("target_id" => $form->getID(), "target_label" => $form->getTitle());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No forms found.")));

                    }
                break;
                case "get-schedules" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Course selected"));
                    }

                    if (isset($request["cperiod_id"]) && $tmp_input = clean_input(strtolower($request["cperiod_id"]), array("trim", "int"))) {
                        $PROCESSED["cperiod_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Curriculum Period selected"));
                    }

                    if (!$ERROR) {
                        $schedules = Models_Schedule::fetchAllByCourseIDScheduleTypeCperiod($PROCESSED["course_id"], $PROCESSED["search_value"], "rotation_stream", $PROCESSED["cperiod_id"]);
                        if ($schedules) {
                            $data = array();
                            foreach ($schedules as $schedule) {
                                $data[] = array("target_id" => $schedule->getID(), "target_parent" => $schedule->getScheduleParentID(), "target_label" => $schedule->getTitle(), "target_children" => Models_Schedule::countScheduleChildren($schedule->getID()));
                            }

                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => 0, "parent_name" => 0, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Schedules found")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    
                break;
                case "get-schedule-children" :
                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    $parent_schedule = Models_Schedule::getRow($PROCESSED["parent_id"]);
                    $child_schedules = Models_Schedule::fetchAllByParentID($PROCESSED["parent_id"]);

                    if ($child_schedules) {
                        $data = array();
                        foreach ($child_schedules as $schedule) {
                            $data[] = array("target_id" => $schedule->getID(), "target_parent" => $schedule->getScheduleParentID(), "target_label" => $schedule->getTitle(), "target_children" => Models_Schedule::countScheduleChildren($schedule->getID()));
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_schedule ? $parent_schedule->getScheduleParentID() : "0"), "parent_name" => ($parent_schedule ? $parent_schedule->getTitle() : "0"), "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                    }
                break;
                case "get-delegators" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    $delegators = Models_Organisation::fetchOrganisationUsers($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation(), array("student", "faculty", "staff", "medtech"), $PROCESSED["limit"], $PROCESSED["offset"]);
                    if ($delegators) {
                        foreach ($delegators as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"]);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Delegators found")));
                    }
                break;
                case "get-cohorts" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    $cohorts = Models_Group::fetchAllByOrganisation($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation());
                    
                    if ($cohorts) {
                        foreach ($cohorts as $cohort) {
                            $data[] = array("target_id" => $cohort->getID(), "target_label" => $cohort->getGroupName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Cohorts found")));
                    }
                break;
                case "get-courses" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if ($user_courses) {
                        foreach ($user_courses as $user_course) {
                            $data[] = array("target_id" => $user_course->getID(), "target_label" => $user_course->getCourseName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Courses found")));
                    }
                break;
                case "get-course-learners" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }


                    if (isset($request["schedule_id"]) && $tmp_input = clean_input($request["schedule_id"], array("trim", "int"))) {
                        $schedule = Models_Schedule::fetchRowByID($tmp_input);
                        if ($schedule) {
                            $PROCESSED["course_id"] = $schedule->getCourseID();
                            $PROCESSED["start_date"] = $schedule->getStartDate();
                            $PROCESSED["end_date"] = $schedule->getEndDate();
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Course, Rotation, or Block provided")));
                        }
                    } elseif (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Course, Rotation, or Block provided")));
                    }

                    if (isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
                        if (isset($request["start_date"]) && $tmp_input = clean_input(strtolower($request["start_date"]), array("trim", "int"))) {
                            $PROCESSED["start_date"] = $tmp_input;
                        }

                        if (isset($request["end_date"]) && $tmp_input = clean_input(strtolower($request["end_date"]), array("trim", "int"))) {
                            $PROCESSED["end_date"] = $tmp_input;
                        }

                        $data = array();
                        $course_learners = User::fetchAllAudienceByCourse($PROCESSED["search_value"], $PROCESSED["course_id"], (isset($PROCESSED["start_date"]) && $PROCESSED["start_date"] ? $PROCESSED["start_date"] : time()), (isset($PROCESSED["end_date"]) && $PROCESSED["end_date"] ? $PROCESSED["end_date"] : false));
                        if ($course_learners) {
                            foreach ($course_learners as $user) {
                                $data[] = array("target_id" => $user->getID(), "target_label" => $user->getFullname(), "lastname" => $user->getLastname(), "role" => $translate->_("Learner"), "email" => $user->getEmail());
                            }
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                        }
                    }
                break;
                case "get-organisation-learners" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    $internal_users = Models_Organisation::fetchOrganisationUsers($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation(), "student", $PROCESSED["limit"], $PROCESSED["offset"]);

                    $data = array();

                    if ($internal_users) {
                        foreach ($internal_users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("Learner"), "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-faculty-staff" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }
                    $users = User::fetchUsersByGroups($PROCESSED["search_value"], array("staff", "faculty", "medtech"), null, AUTH_APP_ID, 0, $PROCESSED["limit"], $PROCESSED["offset"]);

                    $data = array();

                    if ($users) {
                        foreach ($users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_(ucfirst($user["role"])), "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-course-faculty" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    if (isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
                        $internal_users = Models_Course::fetchCourseContactUsersByGroup($PROCESSED["course_id"], $PROCESSED["search_value"], "faculty", $PROCESSED["limit"], $PROCESSED["offset"]);
                    } else {
                        $internal_users = User::fetchUsersByGroups($PROCESSED["search_value"], "faculty", null, AUTH_APP_ID, 0, $PROCESSED["limit"], $PROCESSED["offset"]);
                    }

                    $data = array();

                    if ($internal_users) {
                        foreach ($internal_users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("Faculty"), "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-associated-faculty" :
                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    $data = array();
                    $associated_faculty = array();

                    if (isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
                        $associated_faculty = Models_Course_Contact::fetchAllByCourseIDContactType($PROCESSED["course_id"], "associated_faculty");
                    }

                    if (is_array($associated_faculty) && !empty($associated_faculty)) {
                        foreach ($associated_faculty as $faculty) {
                            $user = Models_User::fetchRowByID($faculty->getProxyID());
                            if ($user) {
                                $data[] = array("target_id" => $faculty->getProxyID(), "target_label" => $user->getFullname(false));
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No associated faculty found")));
                    }
                break;
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
                            $data[] = array("value" => $user["proxy_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("Internal"), "email" => $user["email"]);
                        }
                    }

                    $external_users = Models_Assessments_Distribution_ExternalAssessor::fetchAllBySearchValue($PROCESSED["search_value"], null);

                    if ($external_users) {
                        foreach ($external_users as $user) {
                            $data[] = array("value" => $user["eassessor_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("External"), "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode($data);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-organisation-targets" :
                    if (isset($request["term"]) && $tmp_input = clean_input(strtolower($request["term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $internal_users = Models_Organisation::fetchOrganisationUsers($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation());

                    $data = array();

                    if ($internal_users) {
                        foreach ($internal_users as $user) {
                            $data[] = array("value" => $user["proxy_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "group" => $user["group"],  "role" => $user["role"], "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode($data);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-distribution-data" :

                    if (isset($request["adistribution-id"]) && $tmp_input = clean_input(strtolower($request["adistribution-id"]), array("trim", "striptags"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("The provided distribution ID was invalid. Please try again."));
                    }

                    if ($PROCESSED["adistribution_id"]) {
                        $distribution_data = Models_Assessments_Distribution::fetchDistributionData($PROCESSED["adistribution_id"]);
                        if ($distribution_data) {
                            $controller = new Controllers_Assessment_Distribution();

                            $distribution_data["release_date"] = (!is_null($distribution_data["release_date"]) ? date("Y-m-d", $distribution_data["release_date"]) : null);
                            $distribution_data["delivery_date"] = (!is_null($distribution_data["delivery_date"]) ? date("Y-m-d", $distribution_data["delivery_date"]) : null);

                            $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_data["assessors"] = array();
                            if ($assessors) {
                                foreach ($assessors as $assessor) {
                                    if (!isset($distribution_data["assessor_type"])) {
                                        $distribution_data["assessor_type"] = $assessor->getAssessorType();
                                        $distribution_data["assessor_role"] = $assessor->getAssessorRole();
                                        $distribution_data["assessor_scope"] = $assessor->getAssessorScope();
                                    }
                                    if ($assessor->getAssessorType() == "schedule_id") {
                                        $distribution_data["all_learner_assessor_mode"] = true;
                                    }
                                    $tmp_assessor = $assessor->toArray();
                                    if ($tmp_assessor["assessor_type"] == "proxy_id") {
                                        $tmp_assessor["assessor_name"] = get_account_data("wholename", $tmp_assessor["assessor_value"]);
                                    } elseif ($tmp_assessor["assessor_type"] == "external_hash") {
                                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor->getAssessorValue());
                                        $tmp_assessor["assessor_name"] = html_encode($external_assessor->getFirstname() . " " . $external_assessor->getLastname());
                                    } elseif ($tmp_assessor["assessor_type"] == "course_id") {
                                        $course = Models_Course::fetchRowByID($assessor->getAssessorValue());
                                        if ($course) {
                                            $tmp_assessor["assessor_name"] = $course->getCourseName();
                                        }
                                    } elseif ($tmp_assessor["assessor_type"] == "group_id") {
                                        $cohort = Models_Group::fetchRowByID($assessor->getAssessorValue());
                                        if ($cohort) {
                                            $tmp_assessor["assessor_name"] = $cohort->getGroupName();
                                        }
                                    } elseif ($tmp_assessor["assessor_type"] == "organisation_id") {
                                        $organisation = Models_Organisation::fetchRowByID($assessor->getAssessorValue());
                                        if ($organisation) {
                                            $tmp_assessor["assessor_name"] = $organisation->getOrganisationTitle();
                                        }
                                    }
                                    $distribution_data["assessors"][] = $tmp_assessor;
                                }
                            }

                            $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_data["targets"] = array();
                            if ($targets) {
                                foreach ($targets as $target) {
                                    if (!isset($distribution_data["target_type"])) {
                                        $distribution_data["target_type"] = $target->getTargetType();
                                        $distribution_data["target_role"] = $target->getTargetRole();
                                        $distribution_data["target_scope"] = $target->getTargetScope();
                                    }
                                    if ((!isset($distribution_data["all_learner_target_mode"]) || !$distribution_data["all_learner_target_mode"]) && $target->getTargetType() == "schedule_id") {
                                        $distribution_data["all_learner_target_mode"] = true;
                                    }
                                    $target_array = $target->toArray();
                                    if ($target->getTargetType() == "proxy_id") {
                                        $user = User::fetchRowByID($target->getTargetId());
                                        $target_array["target_name"] = $user->getFullname();
                                        $target_array["group"] = ucfirst($user->getGroup());
                                        $target_array["role"] = ucfirst($user->getRole());
                                    } elseif ($target->getTargetType() == "course_id") {
                                        $course = Models_Course::fetchRowByID($target->getTargetId());
                                        if ($course) {
                                            $target_array["target_name"] = $course->getCourseName();
                                        }
                                    } elseif ($target->getTargetType() == "group_id") {
                                        $cohort = Models_Group::fetchRowByID($target->getTargetID());
                                        if ($cohort) {
                                            $target_array["target_name"] = $cohort->getGroupName();
                                        }
                                    } elseif ($target->getTargetType() == "organisation_id") {
                                        $organisation = Models_Organisation::fetchRowByID($target->getTargetID());
                                        if ($organisation) {
                                            $target_array["target_name"] = $organisation->getOrganisationTitle();
                                        }
                                    }
                                    $distribution_data["targets"][] = $target_array;
                                }
                            }

                            $authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_data["authors"] = array();
                            if ($authors) {
                                foreach ($authors as $author) {
                                    $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                                    $data = $author->toArray();
                                    $data["author_name"] = $author_name;
                                    $distribution_data["authors"][] = $data;
                                }
                            }

                            $distribution_approvers = new Models_Assessments_Distribution_Approver();
                            $approvers = $distribution_approvers->fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_data["distribution_approvers"] = array();
                            if ($approvers) {
                                foreach ($approvers as $approver) {
                                    $data = $approver->toArray();
                                    $data["approver_name"] = $approver->getApproverName();
                                    $distribution_data["distribution_approvers"][] = $data;
                                }
                            }

                            $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                            if (isset($delegator) && $delegator) {
                                $distribution_data["delegator"] = $delegator->toArray();
                            }
                            $schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_data["adistribution_id"]);
                            if (isset($schedule) && $schedule) {
                                $distribution_data["delivery_period"] = $schedule->getDeliveryPeriod();
                                $distribution_data["frequency"] = $schedule->getFrequency();
                                $distribution_data["period_offset_days"] = round(($schedule->getPeriodOffset() / 86400));
                                $schedule = Models_Schedule::fetchRowByID($schedule->getScheduleID());
                                $distribution_data["schedule_id"] = $schedule->getID();
                                $distribution_data["schedule_label"] = $schedule->getTitle();
                            }

                            $eventtype_model = new Models_Assessments_Distribution_Eventtype();
                            $eventtypes = $eventtype_model->fetchEventTypes($distribution_data["adistribution_id"]);
                            if ($eventtypes) {
                                $distribution_data["eventtypes"] = array();
                                foreach ($eventtypes as $eventtype) {
                                    $distribution_data["eventtypes"][] = array("target_id" => $eventtype["eventtype_id"], "target_name" => $eventtype["eventtype_title"]);
                                }
                            }

                            $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_data["distribution_results_user"] = array();
                            if ($reviewers) {
                                foreach ($reviewers as $reviewer) {
                                    $reviewer_array = $reviewer->toArray();
                                    $reviewer_array["reviewer_name"] = $reviewer->getReviewerName();
                                    $distribution_data["distribution_results_user"][] = $reviewer_array;
                                }
                            }

                            if (!empty($distribution_data)) {
                                $distribution_data = $controller->loadRecordAsValidatedData($distribution_data);
                                echo json_encode(array("status" => "success", "data" => $distribution_data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("There was a problem trying to fetch the distribution data. Please try again."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("There was a problem trying to fetch the distribution with the ID provided. Please try again."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                break;
                case "get-eventtypes" :
                    $data = array();
                    $event_types = Models_EventType::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation());
                    if ($event_types) {
                        foreach ($event_types as $event_type) {
                            $data[] = array("target_id" => $event_type->getID(), "target_label" => $event_type->getEventTypeTitle());
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No Event Types found"))));
                    }
                break;
                case "get-distribution-approvers" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Course id provided."));
                    }
                    
                    if (!$ERROR) {
                        $approvers = Models_Course_Contact::fetchApproversByCourseIDSearchTermContactType($PROCESSED["course_id"], $PROCESSED["search_value"]) ;//only certain contact types //add search term
                        if ($approvers) {
                            $data = array();
                            foreach ($approvers as $approver) {
                                $data[] = array("target_id" => $approver["proxy_id"], "target_label" => $approver["firstname"] . " " .$approver["lastname"]);
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No approvers were found.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
            }
        break;
    }
    exit;
}