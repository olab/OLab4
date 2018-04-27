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
 * This API file returns Assessor records in the format:
 *
 * $assessments = array(
 * 	0 => array(
 * 		"id" 		     => 16,
 * 		"title" 	     => "Winter 2014 Assessment",
 * 		"link"           => "http://www.onefortyfiveapp.com/login?assessmentid=16",
 * 		"startDate"      => 1361517660,
 * 		"endDate"        => 1393053660,
 * 		"gracePeriodEnd" => 1395472860,
 * 		"program"        => array(
 * 			"id"   => 122,
 * 			"name" => "MEDS244 - Clinical & Communication Skills 3"
 * 		),
 * 		"status"         => PRECEPTOR_ASSESSEVAL_STATUS_CLOSED,
 * 		"dataSource"     => PRECEPTOR_ASSESSEVAL_DATASOURCE_ONEFORTYFIVE,
 * 		"targets"         => array(
 * 			array(
 * 				"id"   => 153,
 * 				"name" => "David Erikson"
 * 			),
 * 			array(
 * 				"id"   => 525,
 * 				"name" => "Arthur Hardy"
 * 			),
 * 			array(
 * 			    "id"   => 15,
 * 			    "name" => "Andrea D. McMillan"
 * 			)
 * 		)
 * 	)
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
if(!defined("IN_ASSESSMENTS") && !defined("IN_EVENTS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    $status = array();
    if (isset(${$request_var}["status"])) {
        $tmp_input = ${$request_var}["status"];
        if (is_array($tmp_input)) {
            foreach($tmp_input as $tmp) {
                $status[] = clean_input($tmp, array("trim", "notags"));
            }
        }
    }

    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
        $search_term = $tmp_input;
    } else {
        $search_term = false;
    }

    if (isset(${$request_var}["assessment_index_view_preference"]) && $tmp_input = clean_input(${$request_var}["assessment_index_view_preference"], array("trim", "striptags"))) {
        $assessment_index_view_preference = $tmp_input;
    }

    if (isset($assessment_index_view_preference)) {
        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_index_view_preference"] = $assessment_index_view_preference;
    }

    switch ($request) {
        case "POST" :
            switch ($method) {
                case "trigger-feedback-assessment-for-learning-event":
                    if (isset(${$request_var}["form_id"]) && $tmp_input = clean_input(${$request_var}["form_id"], array("trim", "int"))) {
                        $form_id = $tmp_input;
                    } else {
                        add_error($translate->_("No form identifier provided."));
                    }

                    if (isset(${$request_var}["event_id"]) && $tmp_input = clean_input(${$request_var}["event_id"], array("trim", "int"))) {
                        $event_id = $tmp_input;
                        $event = Models_Event::fetchRowByID($event_id);
                        if (!$event) {
                            add_error($translate->_("Invalid event specified."));
                        }
                    } else {
                        add_error($translate->_("No event identifier provided."));
                    }

                    if (!$ERROR) {
                        $assessment_api = new Entrada_Assessments_Assessment(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
                        $status = $assessment_api->createAssessment(
                            array(
                                "form_id" => $form_id,
                                "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "course_id" => $event->getCourseID(),
                                "assessor_type" => "internal",
                                "assessor_value" => $ENTRADA_USER->getActiveId(),
                                "associated_record_id" => $event_id,
                                "associated_record_type" => "event_id",
                                "created_by" => $ENTRADA_USER->getActiveId()
                            ),
                            array(
                                0 => array(
                                    "target_type" => "event_id",
                                    "target_value" => $event_id,
                                    "task_type" => "evaluation"
                                )
                            )
                        );

                        if ($status) {
                            $url = $assessment_api->getAssessmentURL($event_id, "event_id", false);
                            echo json_encode(array("status" => "success", "data" => array("url" => $url)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $assessment_api->getErrorMessages()));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
                case "trigger-assessment" :
                    if (isset(${$request_var}["form_id"]) && $tmp_input = clean_input(${$request_var}["form_id"], array("trim", "int"))) {
                        $form_id = $tmp_input;
                    } else {
                        add_error($translate->_("No form identifier provided."));
                    }

                    if (isset(${$request_var}["assessor_value"]) && $tmp_input = clean_input(${$request_var}["assessor_value"], array("trim", "int"))) {
                        $assessor_value = $tmp_input;
                    } else {
                        add_error($translate->_("Please select an <strong>attending</strong>."));
                    }

                    if (isset(${$request_var}["assessment_method_id"]) && $tmp_input = clean_input(${$request_var}["assessment_method_id"], array("trim", "int"))) {
                        $assessment_method_id = $tmp_input;
                        $assessment_method_model = new Models_Assessments_Method();
                        $assessment_method = $assessment_method_model->fetchRowByID($assessment_method_id);
                        if (!$assessment_method) {
                            add_error($translate->_("No assessment method found"));
                        }
                    } else {
                        add_error($translate->_("Please select an <strong>assessment method</strong>."));
                    }

                    if (isset(${$request_var}["target_record_id"]) && $tmp_input = clean_input(${$request_var}["target_record_id"], array("trim", "int"))) {
                        $target_record_id = $tmp_input;
                    } else {
                        add_error($translate->_("No target provided."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No course identifier provided."));
                    }

                    if (isset(${$request_var}["assessment_cue"]) && $tmp_input = clean_input(${$request_var}["assessment_cue"], array("trim", "striptags"))) {
                        $assessment_cue = $tmp_input;
                    } else {
                        $assessment_cue = NULL;
                    }
                    if (isset(${$request_var}["referrer"]) && $tmp_input = clean_input(${$request_var}["referrer"], array("trim", "striptags"))) {
                        $referrer = $tmp_input;
                    } else {
                        $referrer = NULL;
                    }

                    /**
                     * Check course settings to see if this course requires a date of encounter when triggering assessments.
                     * Defaults to true.
                     */
                    $PROCESSED["course_requires_date_of_encounter"] = true;
                    if (isset($course_id)) {
                        /**
                         * Instantiate the visualization API
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));

                        $PROCESSED["course_requires_date_of_encounter"] = $cbme_progress_api->courseRequiresDateOfEncounter($course_id);
                    }

                    $encounter_date = null;
                    if ($PROCESSED["course_requires_date_of_encounter"]) {
                        if ((isset(${$request_var}["encounter_date"]) && ${$request_var}["encounter_date"]) && ($tmp_input = clean_input(${$request_var}["encounter_date"] . " 00:00:00", array("striptags")))) {
                            $dt = DateTime::createFromFormat("Y-m-d H:i:s", $tmp_input);
                            if ($dt === false || array_sum($dt->getLastErrors())) {
                                add_error($translate->_("An invalid date was provided."));
                            } else {
                                $encounter_date = $dt->getTimestamp();
                            }
                        } else {
                            add_error($translate->_("No date of encounter provided."));
                        }
                    }

                    $notify_id = false; // The default is to not notify; notifications should be sent out when appropriate, on assessment completion (via assessment method hook).

                    $assessment_type_id = Models_Assessments_Type::fetchAssessmentTypeIDByShortname("cbme");
                    if (!$assessment_type_id) {
                        add_error($translate->_("Invalid assessment type ID."));
                    }

                    $assessment_method = new Models_Assessments_Method();
                    if (!$assessment_method = $assessment_method->fetchRowByID($assessment_method_id)) {
                        add_error($translate->_("Invalid assessment method ID"));
                    }

                    if (!$ERROR) {

                        $assessment_api = new Entrada_Assessments_Assessment(
                            array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "limit_dataset" => array("targets", "assessment_method")
                            )
                        );
                        $add_assessment_option = true;
                        switch ($assessment_method->getShortname()) {
                            case "default":
                            case "send_blank_form" :
                                // Create one single form, not linked to anything, with the attending as assessor.
                                $status = $assessment_api->createAssessment(
                                    array(
                                        "form_id" => $form_id,
                                        "course_id" => $course_id,
                                        "assessment_type_id" => $assessment_type_id,
                                        "assessment_method_id" => $assessment_method_id,
                                        "assessment_method_data" => json_encode(array("assessor_group" => "faculty")),
                                        "assessor_value" => $assessor_value, // defaults to assessor type internal
                                        "published" => 1,
                                        "encounter_date" => $encounter_date
                                    ),
                                    array(
                                        array("target_value" => $target_record_id)
                                    )
                                );

                                if ($assessment_cue) {
                                    $assessment_api->createAssessmentOptions(
                                        "individual_json_options",
                                        array(
                                            "assessment_cue" => array(
                                                "aprogress_id" => null,
                                                "cue" => $assessment_cue
                                            )
                                        )
                                    );
                                }
                                if ($ENTRADA_USER->getActiveId() != $assessor_value) {
                                    $notify_id = $assessor_value; // Notify this assessor
                                }
                                break;

                            case "faculty_triggered_assessment":
                                if ($referrer == "backfill-assessment") {
                                    $assessment_method_data = json_encode(array("assessor_group" => "faculty", "referrer" => $referrer));
                                } else {
                                    $assessment_method_data = json_encode(array("assessor_group" => "faculty"));
                                }
                                $status = $assessment_api->createAssessment(
                                    array(
                                        "form_id" => $form_id,
                                        "course_id" => $course_id,
                                        "assessment_type_id" => $assessment_type_id,
                                        "assessment_method_id" => $assessment_method_id,
                                        "assessment_method_data" => $assessment_method_data,
                                        "assessor_value" => $assessor_value, // defaults to assessor type internal
                                        "published" => 1,
                                        "encounter_date" => $encounter_date
                                    ),
                                    array(
                                        array("target_value" => $target_record_id)
                                    )
                                );
                                if ($ENTRADA_USER->getActiveId() != $assessor_value) {
                                    $notify_id = $assessor_value; // Notify this assessor
                                }
                                break;

                            case "complete_and_confirm_by_pin" :
                                // Create one single form with resident as assessor (self).
                                // On submit w/pin, copy the assessment (set attending as assessor), linking to original, set progress-completed.
                                $status = $assessment_api->createAssessment(
                                    array(
                                        "form_id" => $form_id,
                                        "course_id" => $course_id,
                                        "assessment_type_id" => $assessment_type_id,
                                        "assessment_method_id" => $assessment_method_id,
                                        "assessment_method_data" => json_encode(array(
                                            "assessor_value" => $assessor_value,
                                            "assessor_type" => "internal",
                                            "assessor_group" => "student"
                                        )),
                                        "assessor_value" => $target_record_id, // defaults to assessor type internal
                                        "published" => 0,
                                        "encounter_date" => $encounter_date
                                    ),
                                    array(
                                        array("target_value" => $target_record_id)
                                    )
                                );
                                // We let the assessment method hook handle adding the option, since the form needs all items to  be
                                // visible for the attending to fill it out the complete form, despite that it's technically in the resident's contenxt.
                                $add_assessment_option = false;
                                break;

                            case "double_blind_assessment":
                                // Create one single form for the resident as assessor.
                                // On submission, create a blank one for the attending, linked to the original.
                                if ($assessment_cue) {
                                    $temp = $assessment_api->createAssessmentOptions(
                                        "individual_json_options",
                                        array(
                                            "assessment_cue" => array(
                                                "aprogress_id" => null,
                                                "cue" => $assessment_cue
                                            )
                                        )
                                    );
                                }

                            case "complete_and_confirm_by_email" :

                                // Create one single form with resident as assessor (self).
                                // On submit, make a copy of the progress (set status in-progress) with the attending as assessor.
                                $status = $assessment_api->createAssessment(
                                    array(
                                        "form_id" => $form_id,
                                        "course_id" => $course_id,
                                        "assessment_type_id" => $assessment_type_id,
                                        "assessment_method_id" => $assessment_method_id,
                                        "assessment_method_data" => json_encode(array(
                                            "assessor_value" => $assessor_value,
                                            "assessor_type" => "internal",
                                            "assessor_group" => "student"
                                        )),
                                        "assessor_value" => $target_record_id, // defaults to assessor type internal
                                        "published" => 0,
                                        "encounter_date" => $encounter_date
                                    ),
                                    array(
                                        array("target_value" => $target_record_id)
                                    )
                                );
                                break;
                        }
                        if ($status && $add_assessment_option) {
                            $assessment_api->createAssessmentOptions(
                                "individual_json_options",
                                array(
                                    "items_invisible_to" => array(
                                        array(
                                            "type" => "proxy_id",
                                            "value" => $target_record_id
                                        )
                                    )
                                )
                            );
                        }
                        if (!$status) {
                            foreach ($assessment_api->getErrorMessages() as $error) {
                                add_error($error);
                            }
                        }
                    }
                    if (!$ERROR && $status && $notify_id) {
                        // Created an assessment that we must notify for.
                        $assessment_api->queueAssessorNotifications(
                            $assessment_api->getAssessmentRecord(),
                            $notify_id,
                            NULL,
                            1,
                            false,
                            false,
                            false,
                            false
                        );
                    }
                    if (!$ERROR) {
                        switch ($assessment_method->getShortname()) {
                            case "send_blank_form" :
                                $url = ENTRADA_URL . "/assessments?section=tools&success=true";
                                break;
                            case "complete_and_confirm_by_email" :
                            case "complete_and_confirm_by_pin" :
                            case "double_blind_assessment" :
                            case "faculty_triggered_assessment" :
                            case "default":
                                $url = $assessment_api->getAssessmentURL($ENTRADA_USER->getActiveId(), "proxy_id", false);
                                break;
                        }
                        echo json_encode(array("status" => "success", "data" => array("url" => $url)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "set-epa-view-preference" :
                    if (isset(${$request_var}["preference"]) && $tmp_input = clean_input(${$request_var}["preference"], array("trim", "striptags"))) {
                        $preference = $tmp_input;
                    } else {
                        add_error($translate->_("No preference provided."));
                    }

                    if (isset(${$request_var}["stage"]) && $tmp_input = clean_input(${$request_var}["stage"], array("trim", "striptags"))) {
                        $stage = $tmp_input;
                    } else {
                        add_error($translate->_("No stage provided."));
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["epa_assessments_view_preference"][$stage] = $preference;
                        preferences_update("dashboard", $PREFERENCES);
                        echo json_encode(array("status" => "success"));
                    }
                    break;
                case "set-assessment-filter-view-preference" :
                    if (isset(${$request_var}["preference"]) && $tmp_input = clean_input(${$request_var}["preference"], array("trim", "striptags"))) {
                        $preference = $tmp_input;
                    } else {
                        add_error($translate->_("No preference provided."));
                    }

                    if (isset(${$request_var}["filter_type"]) && $tmp_input = clean_input(${$request_var}["filter_type"], array("trim", "striptags"))) {
                        $filter_type = $tmp_input;
                    } else {
                        add_error($translate->_("No filter type provided."));
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["assessment_filter_view_preference"][$filter_type] = $preference;
                        preferences_update("cbme_assessments", $PREFERENCES);
                        echo json_encode(array("status" => "success"));
                    }
                    break;
                case "set-learner-preference" :
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "striptags"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No proxy id provided."));
                    }
                    $user = Models_User::fetchRowByID($proxy_id);

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["learner_preference"] = null;
                        $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["learner_preference"][$proxy_id] = $user->getFirstname()." ".$user->getLastname();
                        preferences_update("dashboard", $PREFERENCES);
                        echo json_encode(array("status" => "success"));
                    }
                    break;
                case "verify-pin":
                    if (isset(${$request_var}["assessor_pin"]) && $tmp_input = clean_input(${$request_var}["assessor_pin"], array("trim"))) {
                        $assessor_pin = $tmp_input;
                    } else {
                        add_error($translate->_("No PIN provided."));
                    }
                    if (isset(${$request_var}["dassessment_id"]) && $tmp_input = clean_input(${$request_var}["dassessment_id"], array("trim", "int"))) {
                        $dassessment_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment ID provided."));
                    }
                    if (isset(${$request_var}["aprogress_id"]) && $tmp_input = clean_input(${$request_var}["aprogress_id"], array("trim", "int"))) {
                        $aprogress_id = $tmp_input;
                    } else {
                        add_error($translate->_("No progress ID provided."));
                    }
                    if (isset(${$request_var}["assessor_id"]) && $tmp_input = clean_input(${$request_var}["assessor_id"], array("trim", "int"))) {
                        $assessor_id = $tmp_input;
                    } else {
                        add_error($translate->_("Unknown assessor"));
                    }
                    if (!has_error()) {
                        $assessment_api = new Entrada_Assessments_Assessment();
                        $pin_nonce = $assessment_api->generateAssessorPinNonce($dassessment_id, $aprogress_id, $assessor_id, $assessor_pin);
                        if ($pin_nonce) {
                            $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_nonce"][$aprogress_id] = $pin_nonce;
                        } else {
                            // if pin_nonce is false, then it failed validation (or other error)
                            foreach ($assessment_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }
                    if (!has_error()) {
                        echo json_encode(array("status" => "success", "data" => array("success" => true)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "set-course-preference" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No course identifier provided."));
                    }
                    if ($course_record = Models_Course::fetchRowByID($course_id)) {
                        $course_code = $course_record->getCourseCode();
                        if ($course_code) {
                            $course_name = "{$course_code}: {$course_record->getCourseName()}";
                        } else {
                            $course_name = $course_record->getCourseName();
                        }
                    } else {
                        add_error(sprintf($translate->_("Invalid %s ID"), $translate->_("course")));
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        $_SESSION[APPLICATION_IDENTIFIER]["dashboard"]["course_preference"] = array("course_id" => $course_id, "course_name" => $course_name);
                        $_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["course_preference"] = array("course_id" => $course_id, "course_name" => $course_name);
                        preferences_update("dashboard", $PREFERENCES);
                        preferences_update("cbme_assessments", $PREFERENCES);
                        echo json_encode(array("status" => "success"));
                    }
                    break;
                case "update-objective-completion-status":
                    if (isset(${$request_var}["action"]) && $tmp_input = clean_input(${$request_var}["action"], array("trim", "striptags"))) {
                        $action = $tmp_input;
                    } else {
                        add_error($translate->_("No action provided."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No course id provided."));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No proxy id provided."));
                    }


                    if ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($proxy_id), "read")) {
                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $proxy_id
                        );
                    }

                    if (isset(${$request_var}["objective_id"]) && $tmp_input = clean_input(${$request_var}["objective_id"], array("int"))) {
                        $objective_id = $tmp_input;
                    } else {
                        add_error($translate->_("No objective id provided."));
                    }

                    if (isset(${$request_var}["objective_set"]) && $tmp_input = clean_input(${$request_var}["objective_set"], array("trim", "striptags"))) {
                        $objective_set = $tmp_input;
                    } else {
                        $objective_set = $translate->_("EPA");
                    }

                    if (isset(${$request_var}["reason"]) && $tmp_input = clean_input(${$request_var}["reason"], array("trim", "striptags"))) {
                        $reason = $tmp_input;
                    } else {
                        $reason = "";
                    }

                    if (!has_error()) {
                        switch ($action) {
                            case "incomplete":
                                if ($reason == "" ) {
                                    echo json_encode(array("status" => "error", "data" =>$translate->_("A reason is required to set the objective as incomplete")));
                                    exit();
                                }

                                if (!Models_Objective_Completion::setObjectiveAsInComplete($objective_id, $course_id, $proxy_id, $ENTRADA_USER->getActiveID(), $reason)) {
                                    echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update objective completion status")));
                                    exit();
                                }

                                echo json_encode(array("status" => "success", "data" => $translate->_($objective_set . " status updated"), "action" => "incomplete"));
                                exit();

                            case "complete":
                                if (!Models_Objective_Completion::setObjectiveAsCompleted($objective_id, $course_id, $proxy_id, $ENTRADA_USER->getActiveID(), $reason)) {
                                    json_encode(array("status" => "error", "data" => $translate->_("Failed to update objective completion status")));
                                    exit();
                                }

                                echo json_encode(array("status" => "success", "data" => $translate->_($objective_set . " status updated"), "action" => "complete"));
                                exit();

                            default:
                                echo json_encode(array("status" => "error", "data" => $translate->_("Invalid action specified")));
                                exit();
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "pin" :
                    if (isset(${$request_var}["dassessment_id"]) && $tmp_input = clean_input(${$request_var}["dassessment_id"], array("int"))) {
                        $dassessment_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment id provided."));
                    }

                    if (isset(${$request_var}["aprogress_id"]) && $tmp_input = clean_input(${$request_var}["aprogress_id"], array("int"))) {
                        $aprogress_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment progress id provided."));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No user id provided."));
                    }

                    if (isset(${$request_var}["pin_type"]) && $tmp_input = clean_input(${$request_var}["pin_type"], array("trim", "striptags"))) {
                        $pin_type = $tmp_input;
                    } else {
                        add_error($translate->_("No pin type provided."));
                    }

                    if (isset(${$request_var}["pin_value"]) && $tmp_input = clean_input(${$request_var}["pin_value"], array("int"))) {
                        $pin_value = $tmp_input;
                    } else {
                        add_error($translate->_("No pin value provided."));
                    }

                    if (!$ERROR) {
                        $pinned_model = new Models_Assessments_Pins();
                        $pinned_assessment = $pinned_model->fetchRowByTypeAndIDAndAProgressID($pin_value, $pin_type, $aprogress_id, false);
                        $pin_id = 0;
                        if (!$pinned_assessment) {
                            $pinned_model->setCreatedBy($ENTRADA_USER->getActiveID());
                            $pinned_model->setCreatedDate(time());
                            $pinned_model->setPinType($pin_type);
                            $pinned_model->setProxyId($proxy_id);
                            $pinned_model->setDassessmentId($dassessment_id);
                            $pinned_model->setPinValue($pin_value);
                            $pinned_model->setAprogressId($aprogress_id);
                            if (!$pinned_model->insert()) {
                                error_log("Failed to pin an assessment for pin_id ". $pin_id . ". DB said:" . $db->ErrorMsg());
                                add_error($translate->_("Failed to add a new assessment pin"));
                            } else {
                                $pin_id = $pinned_model->getID();
                            }
                        } else {
                            $pinned_assessment->setDeletedDate(NULL);
                            $pinned_assessment->setDeletedBy(NULL);
                            $pinned_assessment->setUpdatedDate(time());
                            $pinned_assessment->setUpdatedBy($proxy_id);
                            if(!$pinned_assessment->update()) {
                                error_log("Failed to pin an assessment for pin_id ". $pin_id . ". DB said:" . $db->ErrorMsg());
                                add_error($translate->_("Failed to update a new assessment pin"));
                            } else {
                                $pin_id = $pinned_assessment->getID();
                            }
                        }

                        $message = "";
                        switch ($pin_type) {
                            case "assessment" :
                                $message = $translate->_("Pinned assessment");
                                break;
                            case "item" :
                                $message = $translate->_("Pinned item");
                                break;
                            case "comment" :
                                $message = $translate->_("Pinned comment");
                                break;
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("message" => $message, "pin_id" => $pin_id)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "unpin" :
                    if (isset(${$request_var}["pin_id"]) && $tmp_input = clean_input(${$request_var}["pin_id"], array("int"))) {
                        $pin_id = $tmp_input;
                    } else {
                        add_error($translate->_("No identifier provided."));
                    }

                    if (!$ERROR) {
                        $pinned_model = new Models_Assessments_Pins();
                        $pinned_assessment = $pinned_model->fetchRowByID($pin_id);
                        if ($pinned_assessment) {
                            $pinned_assessment->setDeletedBy($ENTRADA_USER->getActiveID());
                            $pinned_assessment->setDeletedDate(time());
                            if (!$pinned_assessment->update()) {
                                error_log("Failed to unpin an assessment for pin_id ". $pin_id . ". DB said:" . $db->ErrorMsg());
                            } else {
                                $message = "";
                                switch ($pinned_assessment->getPinType()) {
                                    case "assessment" :
                                        $message = $translate->_("Unpinned assessment");
                                        break;
                                    case "item" :
                                        $message = $translate->_("Unpinned item");
                                        break;
                                    case "comment" :
                                        $message = $translate->_("Unpinned comment");
                                        break;
                                }

                                echo json_encode(array("status" => "success", "data" => array("message" => $message)));
                            }
                        } else {
                            add_error($translate->_("No pin found."));
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "pinned-tab-preference" :
                    if (isset(${$request_var}["pin_type"]) && $tmp_input = clean_input(${$request_var}["pin_type"], array("trim", "striptags"))) {
                        $pin_type = $tmp_input;
                    } else {
                        add_error($translate->_("No pin type provided."));
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["pinned_tab_view_preference"] = $pin_type;
                        preferences_update("cbme_assessments", $PREFERENCES);
                        echo json_encode(array("status" => "success"));
                    }
                break;
                case "preceptor-access-request" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No <strong>course identifier</strong> was provided."));
                    }

                    if (isset(${$request_var}["requested_user_firstname"]) && $tmp_input = clean_input(${$request_var}["requested_user_firstname"], array("trim", "striptags"))) {
                        $PROCESSED["requested_user_firstname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>First Name</strong>."));
                    }

                    if (isset(${$request_var}["requested_user_lastname"]) && $tmp_input = clean_input(${$request_var}["requested_user_lastname"], array("trim", "striptags"))) {
                        $PROCESSED["requested_user_lastname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Last Name</strong>."));
                    }

                    if (isset(${$request_var}["requested_user_email"]) && $tmp_input = clean_input(${$request_var}["requested_user_email"], array("trim", "striptags"))) {
                        if (filter_var($tmp_input, FILTER_VALIDATE_EMAIL)) {
                            $PROCESSED["requested_user_email"] = $tmp_input;
                        } else {
                            add_error($translate->_("Please provide a <strong>valid Email Address</strong>"));
                        }
                    } else {
                        add_error($translate->_("Please provide an <strong>Email Address</strong>."));
                    }

                    if (isset(${$request_var}["requested_user_number"]) && ${$request_var}["requested_user_number"]) {
                        if (($tmp_input = clean_input(${$request_var}["requested_user_number"], array("trim", "numeric"))) && (strlen(${$request_var}["requested_user_number"])) <= 8) {
                            $PROCESSED["requested_user_number"] = $tmp_input;
                        } else {
                            add_error($translate->_("Please ensure the provided <strong>Staff Number is numeric and is no more than 8 numbers</strong>"));
                        }
                    } else {
                        $PROCESSED["requested_user_number"] = 0;
                    }

                    if (isset(${$request_var}["additional_comments"]) && $tmp_input = clean_input(${$request_var}["additional_comments"], array("trim", "striptags"))) {
                        $PROCESSED["additional_comments"] = $tmp_input;
                    }

                    if (!$ERROR) {
                        $PROCESSED["requested_group"] = "faculty";
                        $PROCESSED["requested_role"] = "lecturer";
                        $PROCESSED["created_by"] = $ENTRADA_USER->getActiveId();
                        $PROCESSED["created_date"] = time();


                        $assessment_users_api = new Entrada_Assessments_Users();
                        $result = $assessment_users_api->handlePreceptorAccessRequest($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED);
                        if ($result) {
                            $advanced_search_data = array("target_id" => $result["proxy_id"], "target_label" => $result["name"]);
                            echo json_encode(array("status" => "success", "data" => $advanced_search_data));
                        } else {
                            echo json_encode(array("status" => "success", "data" => array($translate->_("You have successfully sent an access request for this preceptor. Once the preceptor information has been confirmed, they will appear in the attending list."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "unread" :
                    if (isset(${$request_var}["read_id"]) && $tmp_input = clean_input(${$request_var}["read_id"], array("int"))) {
                        $read_id = $tmp_input;
                    } else {
                        add_error($translate->_("No identifier provided."));
                    }

                    if (!$ERROR) {
                        $read_model = new Models_Assessments_Read();
                        $read_assessment = $read_model->fetchRowByID($read_id);
                        if ($read_assessment) {
                            $read_assessment->setDeletedBy($ENTRADA_USER->getActiveID());
                            $read_assessment->setDeletedDate(time());
                            if (!$read_assessment->update()) {
                                error_log(sprintf("Failed to mark as unread for read_id %s. DB said: %s", $read_id, $db->ErrorMsg()));
                            } else {
                                echo json_encode(array("status" => "success", "data" => array("message" => $translate->_("Marked as Unread"))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("message", $translate->_("Unable to find the specified element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "read" :
                    if (isset(${$request_var}["item_id"]) && $tmp_input = clean_input(${$request_var}["item_id"], array("int"))) {
                        $item_id = $tmp_input;
                    } else {
                        $item_id = NULL;
                    }

                    if (isset(${$request_var}["aprogress_id"]) && $tmp_input = clean_input(${$request_var}["aprogress_id"], array("int"))) {
                        $aprogress_id = $tmp_input;
                    } else {
                        add_error($translate->_("No progress id provided."));
                    }

                    if (isset(${$request_var}["dassessment_id"]) && $tmp_input = clean_input(${$request_var}["dassessment_id"], array("int"))) {
                        $dassessment_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment id provided."));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No user id provided."));
                    }

                    if (isset(${$request_var}["read_type"]) && $tmp_input = clean_input(${$request_var}["read_type"], array("striptags"))) {
                        $read_type = $tmp_input;
                    } else {
                        add_error($translate->_("No user read type provided."));
                    }
                    $read_model = new Models_Assessments_Read();

                    if (!$ERROR) {
                        switch($read_type) {
                            case "assessment" :
                                $read_assessment = $read_model->fetchRowByTypeAndIDAndAProgressID($dassessment_id , "assessment", $aprogress_id, $ENTRADA_USER->getActiveID(), false);
                                $read_id = 0;
                                if (!$read_assessment) {
                                    $read_model->setCreatedBy($ENTRADA_USER->getActiveID());
                                    $read_model->setCreatedDate(time());
                                    $read_model->setReadType("assessment");
                                    $read_model->setDassessmentId($dassessment_id);
                                    $read_model->setReadValue($dassessment_id);
                                    $read_model->setProxyId($proxy_id);
                                    $read_model->setAprogressId($aprogress_id);
                                    if (!$read_model->insert()) {
                                        error_log(sprintf($translate->_("Failed to mark an assessment as read for assessment_id %s. DB said: %s"), $dassessment_id, $db->ErrorMsg()));
                                        add_error(sprintf($translate->_("Failed to pin an item %s"), $db->ErrorMsg()));
                                    } else {
                                        $read_id = $read_model->getID();
                                        $success_message = $translate->_("Marked Assessment as Read");
                                    }
                                } else {
                                    $read_assessment->setDeletedDate(NULL);
                                    $read_assessment->setDeletedBy(NULL);
                                    $read_assessment->setUpdatedDate(time());
                                    $read_assessment->setUpdatedBy($proxy_id);
                                    if (!$read_assessment->update()) {
                                        error_log(sprintf($translate->_("Failed to mark an assessment as read for item_id %s. DB said: %s"), $dassessment_id, $db->ErrorMsg()));
                                        add_error($translate->_("Failed to mark an assessment as read"));
                                    } else {
                                        $read_id = $read_assessment->getID();
                                        $success_message = $translate->_("Marked Assessment as Read");
                                    }
                                }
                                if (!$ERROR) {
                                    echo json_encode(array("status" => "success", "data" => array("message" => $success_message, "read_id" => $read_id)));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            break;
                            case "item" :
                                $read_item = $read_model->fetchRowByTypeAndIDAndAProgressID($item_id , "item", $aprogress_id, $ENTRADA_USER->getActiveID(), false);
                                $read_id = 0;
                                if (!$read_item) {
                                    $read_model->setCreatedBy($ENTRADA_USER->getActiveID());
                                    $read_model->setCreatedDate(time());
                                    $read_model->setReadType("item");
                                    $read_model->setDassessmentId($dassessment_id);
                                    $read_model->setReadValue($item_id);
                                    $read_model->setProxyId($proxy_id);
                                    $read_model->setAprogressId($aprogress_id);
                                    if (!$read_model->insert()) {
                                        error_log(sprintf($translate->_("Failed to mark an item as read for item_id %s. DB said: %s")), $dassessment_id, $db->ErrorMsg());
                                        add_error(sprintf($translate->_("Failed to mark an item as read %s"), $db->ErrorMsg()));
                                    } else {
                                        $read_id = $read_model->getID();
                                        $success_message = $translate->_("Marked Item as Read");
                                    }
                                } else {
                                    $read_item->setDeletedDate(NULL);
                                    $read_item->setDeletedBy(NULL);
                                    $read_item->setUpdatedDate(time());
                                    $read_item->setUpdatedBy($proxy_id);
                                    if (!$read_item->update()) {
                                        error_log(sprintf("Failed to mark an item as read for item_id %s. DB said: %s", $dassessment_id, $db->ErrorMsg()));
                                        add_error($translate->_("Failed to mark an item as read"));
                                    } else {
                                        $read_id = $read_item->getID();
                                        $success_message = $translate->_("Marked Item as Read");
                                    }
                                }
                                if (!$ERROR) {
                                    echo json_encode(array("status" => "success", "data" => array("message" => $success_message, "read_id" => $read_id)));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            break;
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "mark-all-as-read":
                    /**
                     * Fetches the provided users unread Assessments and marks them as read.
                     */
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No user id provided."));
                    }

                    if (isset(${$request_var}["secondary_proxy_id"]) && $tmp_input = clean_input(${$request_var}["secondary_proxy_id"], array("int"))) {
                        $PROCESSED["secondary_proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No secondary user id provided."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course id provided."));
                    }

                    if (!$ERROR) {

                        /**
                         * Load module preferences
                         */
                        $PREFERENCES = preferences_load("cbme_assessments");

                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"]
                        );

                        $visualization_options = array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "actor_course_id" => $PROCESSED["course_id"],
                            "datasource_type" => "progress",
                            "limit_dataset" => array("all_unread_assessments"),
                            "query_limit" => false,
                            "query_offset" => 0,
                            "courses" => $courses,
                            "secondary_proxy_id" => $PROCESSED["secondary_proxy_id"]
                        );

                        $cbme_progress_api = new Entrada_CBME_Visualization($visualization_options);

                        /**
                         * Fetch the dataset that will be used by the view
                         */
                        $dataset = $cbme_progress_api->fetchData();

                        if ($dataset["assessments"]) {
                            foreach ($dataset["assessments"] as $assessment) {
                                $read_model = new Models_Assessments_Read();
                                $read_assessment = $read_model->fetchRowByTypeAndIDAndAProgressID($assessment["dassessment_id"], "assessment", $assessment["aprogress_id"], $PROCESSED["secondary_proxy_id"], false);
                                $read_id = 0;
                                if (!$read_assessment) {
                                    $read_model->setCreatedBy($ENTRADA_USER->getActiveID());
                                    $read_model->setCreatedDate(time());
                                    $read_model->setReadType("assessment");
                                    $read_model->setDassessmentId($assessment["dassessment_id"]);
                                    $read_model->setReadValue($assessment["dassessment_id"]);
                                    $read_model->setProxyId($PROCESSED["proxy_id"]);
                                    $read_model->setAprogressId($assessment["aprogress_id"]);
                                    if (!$read_model->insert()) {
                                        error_log(sprintf($translate->_("Failed to mark an assessment as read for assessment_id %s. DB said: %s"), $assessment["dassessment_id"], $db->ErrorMsg()));
                                        add_error(sprintf($translate->_("Failed to pin an assessment %s"), $db->ErrorMsg()));
                                    } else {
                                        $read_id = $read_model->getID();
                                        $success_message = $translate->_("Marked Assessment as Read");
                                    }
                                } else {
                                    $read_assessment->setDeletedDate(NULL);
                                    $read_assessment->setDeletedBy(NULL);
                                    $read_assessment->setUpdatedDate(time());
                                    $read_assessment->setUpdatedBy($PROCESSED["proxy_id"]);
                                    if (!$read_assessment->update()) {
                                        error_log(sprintf($translate->_("Failed to mark an assessment as read for assessment id %s. DB said: %s"), $assessment["dassessment_id"], $db->ErrorMsg()));
                                        add_error($translate->_("Failed to mark an assessment as read"));
                                    } else {
                                        $read_id = $read_assessment->getID();
                                        $success_message = $translate->_("Marked Assessment as Read");
                                    }
                                }
                            }
                            echo json_encode(array("status" => "success", "data" => $translate->_("Marked All Assessments as Read")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("There were no assessments found")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "like":
                    if (isset(${$request_var}["aprogress_id"]) && $tmp_input = clean_input(${$request_var}["aprogress_id"], array("int"))) {
                        $aprogress_id = $tmp_input;
                    } else {
                        add_error($translate->_("No progress id provided."));
                    }

                    if (isset(${$request_var}["dassessment_id"]) && $tmp_input = clean_input(${$request_var}["dassessment_id"], array("int"))) {
                        $dassessment_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment id provided."));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No user id provided."));
                    }

                    if (isset(${$request_var}["like_type"]) && $tmp_input = clean_input(${$request_var}["like_type"], array("striptags"))) {
                        $like_type = $tmp_input;
                    } else {
                        add_error($translate->_("No user like type provided."));
                    }

                    $like_model = new Models_Assessments_Likes();
                    $like_id = 0;
                    $success_message = "";
                    if (!$ERROR) {
                        switch($like_type) {
                            case "assessment":
                                $like_record = $like_model->fetchRowByTypeAndIDAndAProgressID($dassessment_id, $like_type, $aprogress_id, $ENTRADA_USER->getActiveID());
                                if (!$like_record) {
                                    $like_model->setCreatedBy($ENTRADA_USER->getActiveID());
                                    $like_model->setCreatedDate(time());
                                    $like_model->setAprogressID($aprogress_id);
                                    $like_model->setDassessmentID($dassessment_id);
                                    $like_model->setLikeValue($dassessment_id);
                                    $like_model->setLikeType($like_type);
                                    $like_model->setProxyID($proxy_id);
                                    if (!$like_model->insert()) {
                                        error_log(sprintf($translate->_("Failed to like an assessment for assessment_id %s. DB said: %s"), $dassessment_id, $db->ErrorMsg()));
                                        add_error($translate->_("Failed to like an assessment"));
                                    } else {
                                        $like_id = $like_model->getID();
                                        $success_message = $translate->_("You liked this assessment");
                                    }
                                } else {
                                    $like_record->setDeletedDate(NULL);
                                    $like_record->setDeletedBy(NULL);
                                    $like_record->setUpdatedDate(time());
                                    $like_record->setUpdatedBy($proxy_id);
                                    if (!$like_record->update()) {
                                        error_log(sprintf($translate->_("Failed to like an assessment for assessment id %s. DB said: %s"), $dassessment_id, $db->ErrorMsg()));
                                        add_error($translate->_("Failed to like an assessment"));
                                    } else {
                                        $like_id = $like_record->getID();
                                        $success_message = $translate->_("You liked this assessment");
                                    }
                                }
                            break;
                        }
                        echo json_encode(array("status" => "success", "data" => array("message" => $success_message, "like_id" => $like_id)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("message" => $translate->_("Unable to like the assessment"))));
                    }
                break;
                case "unlike":
                    if (isset(${$request_var}["like_id"]) && $tmp_input = clean_input(${$request_var}["like_id"], array("int"))) {
                        $like_id = $tmp_input;
                    } else {
                        add_error($translate->_("No identifier provided."));
                    }

                    if (!$ERROR) {
                        $like_model = new Models_Assessments_Likes();
                        $like_assessment = $like_model->fetchRowByID($like_id);
                        if ($like_assessment) {
                            $like_assessment->setDeletedBy($ENTRADA_USER->getActiveID());
                            $like_assessment->setDeletedDate(time());
                            if (!$like_assessment->update()) {
                                error_log(sprintf("Failed to unlike assessment for like_id %s. DB said: %s", $like_id, $db->ErrorMsg()));
                            } else {
                                echo json_encode(array("status" => "success", "data" =>  array("message" => $translate->_("You unliked this assessment"))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("message" => $translate->_("Unable to find the specified element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("message" => $ERRORSTR)));
                    }
                break;
                case "add-assessment-comment":
                    if (isset(${$request_var}["aprogress_id"]) && $tmp_input = clean_input(${$request_var}["aprogress_id"], array("int"))) {
                        $aprogress_id = $tmp_input;
                    } else {
                        add_error($translate->_("No progress id provided."));
                    }

                    if (isset(${$request_var}["dassessment_id"]) && $tmp_input = clean_input(${$request_var}["dassessment_id"], array("int"))) {
                        $dassessment_id = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment id provided."));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("No user id provided."));
                    }

                    if (isset(${$request_var}["like_type"]) && $tmp_input = clean_input(${$request_var}["like_type"], array("striptags"))) {
                        $like_type = $tmp_input;
                    } else {
                        add_error($translate->_("No user like type provided."));
                    }

                    if (isset(${$request_var}["like_id"]) && $tmp_input = clean_input(${$request_var}["like_id"], array("int"))) {
                        $like_id = $tmp_input;
                    } else {
                        add_error($translate->_("No identifier provided."));
                    }

                    if (isset(${$request_var}["comment"]) && $tmp_input = clean_input(${$request_var}["comment"], array("striptags"))) {
                        $comment = $tmp_input;
                    } else {
                        $comment = "";
                    }

                    if (!$ERROR) {
                        $like_model = new Models_Assessments_Likes();
                        $like_assessment = $like_model->fetchRowByID($like_id);
                        if ($like_assessment) {
                            $like_assessment->setComment($comment);
                            $like_assessment->setUpdatedDate(time());
                            if (!$like_assessment->update()) {
                                error_log(sprintf("Failed to add a comment to the assessment for like_id %s. DB said: %s", $like_id, $db->ErrorMsg()));
                            } else {
                                echo json_encode(array("status" => "success", "data" =>  array("message" => $translate->_("Successfully added a comment to this assessment"))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("message" => $translate->_("Unable to find the specified element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("message" => $ERRORSTR)));
                    }
                break;
            }
            break;
        case "GET" :
            switch ($method) {
                case "list-assessments":
                    $assessments = array();
                    $count = 0;
                    $assessors = Models_Assessments_Distribution_Assessor::fetchAllByProxyIDSearch($ENTRADA_USER->getActiveID(), $search_term);

                    if ($assessors) {
                        $target_text = "N/A";
                        /* @var $assessor Models_Assessments_Distribution_Assessor */
                        foreach ($assessors as $assessor) {
                            $targets = fetchAssessmentTargets($assessor["adistribution_id"]);

                            $form_type = fetchFormTypeTitle($assessor["adistribution_id"]);
                            $target_text = $form_type["title"];

                            $schedule = Models_Schedule::fetchRowByID($assessor["schedule_id"]);
                            $schedule_children = $schedule->getChildren();

                            $progress_value["name"] = "Awaiting Completion";

                            $assessor_user = Models_Assessments_Distribution_Assessor::fetchRowByID($assessor["adassessor_id"]);

                            if ($schedule_children) {
                                foreach($schedule_children as $schedule_child) {
                                    $progress = false;
                                    if ($targets && count($targets) == 1) {
                                        $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor["adistribution_id"], "internal", $ENTRADA_USER->getActiveId());
                                    }

                                    $progress_value = fetchTargetStatus($targets, $assessor_user, $schedule_child);

                                    if (in_array($progress_value["shortname"], $status)) {
                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$assessor["adistribution_id"]."&schedule_id=".$assessor["schedule_id"]."&form_id=".$assessor["form_id"].($progress ? "&aprogress_id=".$progress->getID() : "");

                                        $row = array();
                                        $row["id"] = $assessor["adistribution_id"];
                                        $row["title"] = $assessor["rotation_name"];
                                        $row["link"] = $url;
                                        $row["startDate"] = $assessor["rotation_start_date"];
                                        $row["endDate"] = $assessor["rotation_end_date"];
                                        $row["gracePeriodEnd"] = $assessor["rotation_end_date"];
                                        $row["program"] = array(
                                            "id"   => $assessor["course_id"],
                                            "name" => $assessor["course_name"]);
                                        $row["status"] = $progress_value["name"];
                                        $row["dataSource"] = "";
                                        $row["targets"] = array(array(
                                            "id"   => "",
                                            "name" => $target_text));
                                        if ($assessor["target_type"] == "proxy_id") {
                                            $image_src = webservice_url("photo", array($assessor["target_id"], "official"));
                                        } else {
                                            $image_src = $ENTRADA_TEMPLATE->url()."/images/icon-checklist.gif";
                                        }
                                        $row["img_src"] = $image_src;
                                        $assessments[] = $row;
                                        $count++;
                                    }
                                }
                            } else {
                                $progress = false;
                                if ($targets && count($targets) == 1) {
                                    $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValue($assessor["adistribution_id"], "internal", $ENTRADA_USER->getActiveId());
                                }

                                $progress_value = fetchTargetStatus($targets, $assessor_user, $schedule);

                                if (in_array($progress_value["shortname"], $status)) {
                                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=".$assessor["adistribution_id"]."&schedule_id=".$assessor["schedule_id"]."&form_id=".$assessor["form_id"].($progress ? "&aprogress_id=".$progress->getID() : "");

                                    $row = array();
                                    $row["id"] = $assessor["adistribution_id"];
                                    $row["title"] = $assessor["rotation_name"];
                                    $row["link"] = $url;
                                    $row["startDate"] = $assessor["rotation_start_date"];
                                    $row["endDate"] = $assessor["rotation_end_date"];
                                    $row["gracePeriodEnd"] = $assessor["rotation_end_date"];
                                    $row["program"] = array(
                                        "id"   => $assessor["course_id"],
                                        "name" => $assessor["course_name"]);
                                    $row["status"] = $progress_value["name"];
                                    $row["dataSource"] = "";
                                    $row["targets"] = array(array(
                                        "id"   => "",
                                        "name" => $target_text));
                                    if ($assessor["target_type"] == "proxy_id") {
                                        $image_src = webservice_url("photo", array($assessor["target_id"], "official"));
                                    } else {
                                        $image_src = $ENTRADA_TEMPLATE->url()."/images/icon-checklist.gif";
                                    }
                                    $row["img_src"] = $image_src;
                                    $assessments[] = $row;
                                    $count++;
                                }
                            }
                        }

                        echo json_encode(array("status" => "success", "data" => array("assessments" => $assessments)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("assessments" => array())));
                    }
                    break;
                case "list-assessment-learners":
                    $learners = array();
                    $count = 0;
                    $course_groups = Models_Course_Group::fetchAllGroupsByTutorProxyIDOrganisationID($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
                        $search_term = $tmp_input;
                    } else {
                        $search_term = false;
                    }

                    if ($course_groups) {
                        foreach ($course_groups as $course_group) {
                            $course = Models_Course::fetchRowByID($course_group->getCourseID());
                            $tmp_learners = Models_User::fetchAllByCGroupIDSearchTerm($course_group->getID(), $search_term);
                            if ($course && $tmp_learners) {
                                foreach ($tmp_learners as $learner) {

                                    $duplicate = false;
                                    // Ensure this is not a duplicate.
                                    foreach ($learners as $previous_learner) {
                                        if ($previous_learner["id"] == $learner->getID()) {
                                            $duplicate = true;
                                        }
                                    }

                                    if (!$duplicate) {
                                        $url = ENTRADA_URL . "/assessments/learner?proxy_id=" . $learner->getID();
                                        $tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssessmentProgressOnUser($learner->getID(), $ENTRADA_USER->getActiveOrganisation(), "learner");
                                        $completed_tasks = $tasks["complete"];

                                        $row = array();
                                        $row["id"] = $learner->getID();
                                        $row["name"] = $learner->getLastname() . ", " . $learner->getFirstname();
                                        $row["email"] = $learner->getEmail();
                                        $row["link"] = $url;
                                        $row["img_src"] = webservice_url("photo", array($learner->getID(), "official"));
                                        $row["course_title"] = $course->getCourseName();
                                        $row["completed_assessments"] = ($completed_tasks && @count($completed_tasks) ? @count($completed_tasks) : 0);
                                        $learners[] = $row;
                                        $count++;
                                    }
                                }
                            }
                        }
                        // Sort learners by name.
                        usort($learners,  function ($a, $b) {
                            return strcmp($a["name"], $b["name"]);
                        });

                        echo json_encode(array("status" => "success", "data" => array("learners" => $learners)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("learners" => array())));
                    }
                    break;
                case "list-assessment-faculty":
                    $faculty = array();
                    $count = 0;

                    $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if (isset(${$request_var}["search_term"]) && $tmp_input = clean_input(${$request_var}["search_term"], array("trim", "striptags"))) {
                        $search_term = $tmp_input;
                    } else {
                        $search_term = false;
                    }

                    if ($courses) {
                        $tmp_faculty = array();
                        foreach ($courses as $course) {
                            if (CourseOwnerAssertion::_checkCourseOwner($ENTRADA_USER->getActiveID(), $course->getID())) {

                                // Add course directors.
                                $tmp_directors = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "director", $search_term);
                                if ($tmp_directors) {
                                    foreach ($tmp_directors as $director) {
                                        $tmp_faculty[] = $director;
                                    }
                                }

                                // Add course associated_faculty.
                                $tmp_associated_faculty = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course->getID(), "associated_faculty", $search_term);
                                if ($tmp_associated_faculty) {
                                    foreach ($tmp_associated_faculty as $associated_faculty) {
                                        $tmp_faculty[] = $associated_faculty;
                                    }
                                }

                                if ($tmp_faculty) {
                                    foreach ($tmp_faculty as $tmp) {

                                        $person = Models_User::fetchRowByID($tmp["proxy_id"]);
                                        if ($person) {

                                            $duplicate = false;
                                            // Ensure this is not a duplicate.
                                            foreach ($faculty as $previous_faculty) {
                                                if ($previous_faculty["id"] == $person->getID()) {
                                                    $duplicate = true;
                                                }
                                            }

                                            if (!$duplicate) {
                                                $url = ENTRADA_URL . "/assessments/faculty?proxy_id=" . $person->getID();
                                                $current_assessment_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasks($person->getID(), "faculty");

                                                $row = array();
                                                $row["id"] = $person->getID();
                                                $row["name"] = $person->getLastname() . ", " . $person->getFirstname();
                                                $row["email"] = $person->getEmail();
                                                $row["link"] = $url;
                                                $row["img_src"] = webservice_url("photo", array($person->getID(), "official"));
                                                $row["course_title"] = $course->getCourseName();
                                                $faculty[] = $row;
                                                $count++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Sort faculty by name.
                        usort($faculty,  function ($a, $b) {
                            return strcmp($a["name"], $b["name"]);
                        });

                        echo json_encode(array("status" => "success", "data" => array("faculty" => $faculty)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("faculty" => array())));
                    }
                    break;
                case "list-assessments-by-user":
                    /**
                     * Find all completed progress records that have the current user as the target.
                     */
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error("A user is required");
                    }

                    if (!$ERROR) {
                        $assessments = array();
                        $progress_records = Models_Assessments_Progress::fetchAllByProxyIDSearch($proxy_id, $search_term);
                        if ($progress_records) {
                            foreach ($progress_records as $progress) {
                                $distribution = Models_Assessments_Distribution::fetchRowByID($progress->getAdistributionID());
                                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($progress->getAdistributionID());
                                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                $rotation_name = "N/A";
                                if ($schedule->getScheduleParentID()) {
                                    $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                                    $rotation_name = $schedule_parent->getTitle();
                                } else {
                                    $rotation_name = $schedule->getTitle();
                                }
                                $course = Models_Course::fetchRowByID($schedule->getCourseID());

                                $url = ENTRADA_URL . "/assessments/viewassessment?&target_record_id=".$proxy_id."&adistribution_id=".$distribution->getID()."&schedule_id=".$distribution_schedule->getScheduleID()."&form_id=".$distribution->getFormID()."&aprogress_id=".$progress->getID();

                                $row = array();
                                $row["id"] = $progress->getAdistributionID();
                                $row["title"] = $rotation_name;
                                $row["program"] = array(
                                    "id"   => $course->getID(),
                                    "name" => $course->getCourseName());
                                $row["link"] = $url;
                                $row["startDate"] = $schedule->getStartDate();
                                $row["endDate"] = $schedule->getEndDate();
                                $row["assessor"] = User::fetchRowByID($progress->getCreatedBy())->getFullname(false);
                                $image_src = webservice_url("photo", array($progress->getProxyID(), "official"));

                                $row["img_src"] = $image_src;

                                $assessments[] = $row;
                                $count++;
                            }

                            echo json_encode(array("status" => "success", "data" => array("assessments" => $assessments)));
                        } else {
                            echo json_encode(array("status" => "success", "data" => array("assessments" => array())));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
                case "get-faculty" :
                    if (isset(${$request_var}["search_value"]) && $tmp_input = clean_input(strtolower(${$request_var}["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(strtolower(${$request_var}["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(strtolower(${$request_var}["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    $users = User::fetchUsersByGroups($PROCESSED["search_value"], array("faculty"), $ENTRADA_USER->getActiveOrganisation(), AUTH_APP_ID, 0, $PROCESSED["limit"], $PROCESSED["offset"]);

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
                case "get-residents" :
                    if (isset(${$request_var}["search_value"]) && $tmp_input = clean_input(strtolower(${$request_var}["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(strtolower(${$request_var}["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = null;
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(strtolower(${$request_var}["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = null;
                    }

                    $limit_to_course = null;

                    $current_cperiods = Models_Curriculum_Period::fetchAllCurrentIDs();
                    $users = User::fetchAllResidentsByCPeriodIDs($ENTRADA_USER->getActiveOrganisation(), $current_cperiods, $PROCESSED["search_value"], 0, $PROCESSED["limit"], $PROCESSED["offset"], $limit_to_course);

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
                case "get-residents-and-faculty" :
                    if (isset(${$request_var}["search_value"]) && $tmp_input = clean_input(strtolower(${$request_var}["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(strtolower(${$request_var}["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = null;
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(strtolower(${$request_var}["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = null;
                    }

                    if (isset($_GET["proxy_id"]) && $tmp_input = clean_input(strtolower($_GET["proxy_id"]), array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = null;
                    }

                    $current_cperiods = Models_Curriculum_Period::fetchAllCurrentIDs();
                    $users = User::fetchAllResidentsAndFacultyByCPeriodIDs($ENTRADA_USER->getActiveOrganisation(), $current_cperiods, $PROCESSED["search_value"], 0, $PROCESSED["limit"], $PROCESSED["offset"], array(),  array("faculty"),  $PROCESSED["proxy_id"]);

                    $data = array();
                    if ($users) {
                        foreach ($users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_(ucfirst($user["role"])), "email" => $user["email"], "group" => $user["group"]);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;
                case "get-assessment-tools" :
                    if (isset(${$request_var}["node_id"]) && $tmp_input = clean_input(${$request_var}["node_id"], array("trim", "int"))) {
                        $node_id = $tmp_input;
                    } else {
                        add_error($translate->_("Please select an <strong>EPA</strong>."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No Course selected."));
                    }

                    if (isset(${$request_var}["subject_id"]) && $tmp_input = clean_input(${$request_var}["subject_id"], array("trim", "int"))) {
                        $subject_id = $tmp_input;
                    } else {
                        $subject_id = null;
                    }

                    if (!$ERROR) {
                        $forms_api = new Entrada_Assessments_Forms(array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                            )
                        );
                        $forms_tagged = $forms_api->fetchFormsTaggedToTreeBranch($node_id, $course_id, $subject_id);
                        if (!$forms_tagged) {
                            add_error($translate->_("No Assessment Tools found."));
                        }

                        foreach ($forms_tagged as &$form) {
                            // Format time 
                            if ($form["average_time"]) {
                                $form["average_time"] = ($form["average_time"] > 3599) ? gmdate("h:i:s", $form["average_time"]) : gmdate("i:s", $form["average_time"]);
                            } else {
                                $form["average_time"] = $translate->_("N/A");
                            }
                        }
                        
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $forms_tagged));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-all-assessment-tools-by-date":
                    if (isset(${$request_var}["subject_id"]) && $tmp_input = clean_input(${$request_var}["subject_id"], array("trim", "int"))) {
                        $subject_id = $tmp_input;
                    } else {
                        add_error($translate->_("No proxy id provided"));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No course id provided"));
                    }

                    if (isset(${$request_var}["start_date"]) && $tmp_input = clean_input(${$request_var}["start_date"], array("trim", "striptags"))) {
                        $start_date = strtotime($tmp_input);
                    } else {
                        add_error($translate->_("No start date"));
                    }

                    if (isset(${$request_var}["finish_date"]) && $tmp_input = clean_input(${$request_var}["finish_date"], array("trim", "striptags"))) {
                        $finish_date = strtotime($tmp_input);
                    } else {
                        add_error($translate->_("No end date"));
                    }

                    if (!$ERROR) {
                        $forms_api = new Entrada_Assessments_Forms(array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                            )
                        );
                        $progress_model = new Models_Assessments_Progress();
                        $forms_tagged = array();
                        $forms = $forms_api->fetchFormsTaggedToMilestones($course_id, $subject_id, $ENTRADA_USER->getActiveOrganisation());
                        $form_objective = new Models_Assessments_Form_Objective();

                        if ($forms) {
                            foreach ($forms as $form) {
                                $form_objectives = $form_objective->fetchAllByFormID($form["form_id"], true);
                                $progress = $progress_model->getAssessmentsByFormIDTarget($form["form_id"], $subject_id, $start_date, $finish_date);
                                if ($progress) {
                                    $form["created_date"] = date("Y-m-d", $form["created_date"]);
                                    $form["objectives"] = $form_objectives;
                                    $forms_tagged[] = $form;
                                }
                            }
                        }

                        if (!$forms_tagged) {
                            add_error($translate->_("No assessment tools found"));
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $forms_tagged));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                break;
                case "get-form-preview":
                    if (isset(${$request_var}["form_id"]) && $tmp_input = clean_input(${$request_var}["form_id"], array("trim", "int"))) {
                        $form_id = $tmp_input;
                    } else {
                        add_error($translate->_("No form identifier provided."));
                    }

                    $form_preview = "";

                    if (!$ERROR) {
                        $forms_api = new Entrada_Assessments_Forms(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        ));

                        $forms_api->setFormID($form_id);
                        $form_data = $forms_api->fetchFormData();

                        if ($form_data && is_array($form_data)) {
                            $preview_form_view = new Views_Assessments_Forms_Form(array("mode" => "assessment-blank"));
                            $view_data = array(
                                "form_id" => $form_id,
                                "disabled" => false,
                                "elements" => $form_data["elements"],
                                "progress" => $form_data["progress"],
                                "rubrics" => $form_data["rubrics"],
                                "aprogress_id" => null,
                                "public" => true
                            );

                            $form_preview = $preview_form_view->render($view_data, false);
                        }
                    }

                    if (!$form_preview) {
                        add_error($translate->_("An error occurred while attempting to render the form preview."));
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => base64_encode($form_preview)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-course-assessment-methods":
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No Course selected."));
                    }

                    if (!$ERROR) {
                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));
                        $assessment_methods = $cbme_progress_api->fetchCourseAssessmentMethods($course_id, $ENTRADA_USER->getActiveGroup());
                        if ($assessment_methods) {
                            echo json_encode(array("status" => "success", "data" => $assessment_methods));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("No assessment methods found.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-assessment-procedure-tools":

                    // A form ID of "one" of the procedures. We use this to find its siblings, if any, and determine all of the available procedures.
                    if (isset(${$request_var}["form_id"]) && $tmp_input = clean_input(${$request_var}["form_id"], array("trim", "int"))) {
                        $form_id = $tmp_input;
                    } else {
                        add_error($translate->_("No Form selected."));
                    }
                    // The Objective ID of the EPA (global_lu_objectives record)
                    if (isset(${$request_var}["objective_id"]) && $tmp_input = clean_input(${$request_var}["objective_id"], array("trim", "int"))) {
                        $objective_id = $tmp_input;
                    } else {
                        add_error($translate->_("No EPA selected."));
                    }
                    $procedures = array();
                    if (!has_error()) {
                        $form_record = Models_Assessments_Form::fetchRowByID($form_id);
                        if (!$form_record) {
                            add_error($translate->_("Unable to find specified procedure tool."));
                        }
                    }
                    if (!has_error()) {
                        if ($form_record->getOriginType() !== "blueprint" || !$form_record->getOriginatingID()) {
                            add_error($translate->_("Unable to determine procedures list."));
                        }
                    }
                    if (!has_error()) {
                        // Fetch the parent blueprint of this form
                        $forms_api = new Entrada_Assessments_Forms(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "form_blueprint_id" => $form_record->getOriginatingID()
                        ));
                        $procedures = $forms_api->fetchFormBlueprintProcedureList($objective_id);
                        if (empty($procedures)) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => $procedures));
                    }
                    break;
                case "get-course-tools" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error($translate->_("No Course selected."));
                    }

                    if (!$ERROR) {
                        /**
                         * Instantiate the forms api abstraction
                         */
                        $forms_api = new Entrada_Assessments_Forms(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        ));

                        /**
                         * Check course settings to see if this course requires a date of encounter when triggering assessments.
                         * Defaults to true.
                         */
                        $PROCESSED["course_requires_date_of_encounter"] = true;

                        /**
                         * Instantiate the visualization API
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));

                        $PROCESSED["course_requires_date_of_encounter"] = $cbme_progress_api->courseRequiresDateOfEncounter($course_id);


                        $assessment_tools = array();

                        $course_settings_model = new Models_Course_Setting();
                        $course_tool_settings = $course_settings_model->fetchRowByCourseIDShortname($course_id, "assessment_tools");
                        if ($course_tool_settings) {
                            $course_tool_settings = $course_tool_settings->toArray();
                            $course_tools = @json_decode($course_tool_settings["value"], true);
                            if ($course_tools) {
                                foreach ($course_tools as $key => $tools) {
                                    switch ($key) {
                                        case "objectives" :
                                            $assessment_tools = $forms_api->FetchFormsTaggedToObjectives($tools, $course_id);
                                            break;
                                    }
                                }
                            }
                        }

                        if (!$assessment_tools) {
                            add_error($translate->_("No Assessment Tools found."));
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("assessment_tools" => $assessment_tools, "course_requires_date_of_encounter" => $PROCESSED["course_requires_date_of_encounter"])));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-user-pin" :
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("Please select an <strong>attending</strong>."));
                    }

                    if (!$ERROR) {
                        $has_pin = true;
                        $user = Models_User::fetchRowByID($proxy_id);
                        if ($user) {
                            if (!$user->getPin()) {
                                $has_pin = false;
                            }
                        } else {
                            add_error("No user found.");
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => array("has_pin" => $has_pin)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-user-course":
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error($translate->_("Please select an <strong>attending</strong>."));
                    }
                    if (isset(${$request_var}["advanced_search"]) && $tmp_input = clean_input(${$request_var}["advanced_search"], array("trim", "int"))) {
                        $results_as_advanced_search_datasource = $tmp_input ? true : false;
                    } else {
                        $results_as_advanced_search_datasource = false;
                    }

                    if (isset(${$request_var}["assessment_tool"]) && $tmp_input = clean_input(${$request_var}["assessment_tool"], array("trim", "int"))) {
                        $assessment_tool = $tmp_input ? true : false;
                    } else {
                        $assessment_tool = false;
                    }

                    $organisation_id = $ENTRADA_USER->getActiveOrganisation();

                    if (!$ERROR) {
                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));

                        if ($assessment_tool) {
                            $courses = Models_Course::getCoursesByContacts($proxy_id, $organisation_id);
                            if (!$courses) {
                                add_error($translate->_("Unknown"));
                            }
                        } else {
                            $cperiod_model = new Models_Curriculum_Period();
                            $cperiod_ids = $cperiod_model->fetchAllCurrentIDsByOrganisation($organisation_id);
                            $courses = Models_Course::getCoursesByProxyIDOrganisationID($proxy_id, $organisation_id, $cperiod_ids, true);
                            if (!$courses) {
                                add_error($translate->_("No active course found for resident"));
                            }
                        }
                        if ($results_as_advanced_search_datasource) {
                            $courses = Entrada_Utilities_AdvancedSearchHelper::buildSearchSource($courses, "course_id", "course_name");
                            if ($courses) {
                                foreach ($courses as &$course) {
                                    $course_tool_settings = $cbme_progress_api->fetchCourseSettingsByShortname($course["target_id"], "assessment_tools");
                                    if ($course_tool_settings) {
                                        $course["objective_tools"] = true;
                                    } else {
                                        $course["objective_tools"] = false;
                                    }
                                }
                            }
                        } else {
                            $courses = array("courses" => $courses);
                            if ($courses) {
                                foreach ($courses as $key => $user_courses) {
                                    foreach ($user_courses as &$course) {
                                        $course_tool_settings = $cbme_progress_api->fetchCourseSettingsByShortname($course["course_id"], "assessment_tools");
                                        if ($course_tool_settings) {
                                            $course["objective_tools"] = true;
                                        } else {
                                            $course["objective_tools"] = false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!$ERROR) {
                        if ($results_as_advanced_search_datasource) {
                            echo json_encode(array("status" => "success", "data" => $courses));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-course-epas":
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $course_id = $tmp_input;
                    } else {
                        add_error(sprintf($translate->_("Please select a <strong>%s</strong>."), $translate->_("course")));
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $proxy_id = $tmp_input;
                    } else {
                        add_error(sprintf($translate->_("No learner was provided.")));
                    }

                    if (!$ERROR) {
                        $forms_api = new Entrada_Assessments_Forms(array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                            )
                        );

                        /**
                         * Check course settings to see if this course requires a date of encounter when triggering assessments.
                         * Defaults to true.
                         */
                        $PROCESSED["course_requires_date_of_encounter"] = true;

                        /**
                         * Instantiate the visualization API
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));

                        $filter_presets = $cbme_progress_api->getLearnerEpaFilterPresets($translate->_("Current Stage EPAs"), $proxy_id, $course_id);

                        $PROCESSED["course_requires_date_of_encounter"] = $cbme_progress_api->courseRequiresDateOfEncounter($course_id);

                        $epa_advanced_search_data = array();
                        $epas_tagged_to_forms = $forms_api->fetchEPANodesTaggedToForms($course_id);
                        if ($epas_tagged_to_forms) {
                            foreach ($epas_tagged_to_forms as $epa) {
                                $epa_advanced_search_data[] = array(
                                    "target_id" => $epa["cbme_objective_tree_id"],
                                    "target_label" => $epa["objective_code"] . ": " . substr($epa["objective_name"], 0, 65) . "...",
                                    "target_title" => $epa["objective_code"] . " " . $epa["objective_name"]
                                );
                            }
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => array("epas" => $epa_advanced_search_data, "course_requires_date_of_encounter" => $PROCESSED["course_requires_date_of_encounter"], "filter_presets" => $filter_presets)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-faculty-staff" :
                    if (isset(${$request_var}["search_value"]) && $tmp_input = clean_input(strtolower(${$request_var}["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(strtolower(${$request_var}["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(strtolower(${$request_var}["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    $users = User::fetchUsersByGroups($PROCESSED["search_value"], array("staff", "faculty", "medtech"), $ENTRADA_USER->getActiveOrganisation(), AUTH_APP_ID, 0, $PROCESSED["limit"], $PROCESSED["offset"]);
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
                case "get-assessments" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        add_error($translate->_("No limit provided."));
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveId();
                    }

                    if (isset(${$request_var}["secondary_proxy_id"]) && $tmp_input = clean_input(${$request_var}["secondary_proxy_id"], array("trim", "int"))) {
                        $PROCESSED["secondary_proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["secondary_proxy_id"] = NULL;
                    }

                    if (isset(${$request_var}["pinned_only"]) && $tmp_input = clean_input(${$request_var}["pinned_only"], array("trim", "int"))) {
                        $PROCESSED["pinned_only"] = true;
                    } else {
                        $PROCESSED["pinned_only"] = false;
                    }

                    if (isset(${$request_var}["assessment_type"]) && $tmp_input = clean_input(${$request_var}["assessment_type"], array("trim", "striptags"))) {
                        $PROCESSED["assessment_type"] = $tmp_input;
                    } else {
                        $PROCESSED["assessment_type"] = "complete";
                    }

                    if (!$ERROR) {
                        $assessment_data = array();
                        $filters = array();
                        $data = array();
                        parse_str(${$request_var}["filters"], $filters);

                        /**
                         * Load module preferences
                         */
                        $PREFERENCES = preferences_load("cbme_assessments");

                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"]
                        );

                        switch ($PROCESSED["assessment_type"]) {
                            case "completed" :
                                $limit_dataset = "assessments";
                            break;
                            case "inprogress" :
                                $limit_dataset = "inprogress_assessments";
                            break;
                            case "pending" :
                                $limit_dataset = "pending_assessments";
                            break;
                            case "deleted" :
                                $limit_dataset = "deleted_assessments";
                            break;
                        }

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $visualization_options = array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "actor_course_id" => $PROCESSED["course_id"],
                            "datasource_type" => "progress",
                            "limit_dataset" => array($limit_dataset),
                            "filters" => $filters,
                            "query_limit" => $PROCESSED["limit"],
                            "query_offset" => $PROCESSED["offset"],
                            "courses" => $courses,
                            "secondary_proxy_id" => $PROCESSED["secondary_proxy_id"]
                        );

                        if ($PROCESSED["pinned_only"]) {
                            $visualization_options["limit_dataset"] = array("assessment_pins");
                        }

                        $cbme_progress_api = new Entrada_CBME_Visualization($visualization_options);

                        /**
                         * Fetch the dataset that will be used by the view
                         */
                        $dataset = $cbme_progress_api->fetchData();

                        if ($PROCESSED["pinned_only"]) {
                            $data = $dataset["assessment_pins"];
                        } else {
                            $data = $dataset["assessments"];
                        }

                        if ($dataset["assessments"]) {
                            foreach ($dataset["assessments"] as $key => $assessment) {
                                $read_model = new Models_Assessments_Read();
                                $dataset["assessments"][$key]["deleted_by"] = sprintf($translate->_("Deleted by %s |"), $assessment["deleted_by"]);
                                if ($PROCESSED["secondary_proxy_id"] != NULL) {
                                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($assessment["dassessment_id"], "assessment", $assessment["aprogress_id"], $PROCESSED["secondary_proxy_id"], true);
                                    if (!$read_record) {
                                        $dataset["assessments"][$key]["read_id"] = NULL;
                                    } else {
                                        $dataset["assessments"][$key]["read_id"] = $read_record->getID();
                                    }
                                } else {
                                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($assessment["dassessment_id"], "assessment", $assessment["aprogress_id"], $PROCESSED["proxy_id"], true);
                                    if (!$read_record) {
                                        $dataset["assessments"][$key]["read_id"] = NULL;
                                    } else {
                                        $dataset["assessments"][$key]["read_id"] = $read_record->getID();
                                    }
                                }
                            }
                        }

                        if ($PROCESSED["pinned_only"]) {
                            $data = $dataset["assessment_pins"];
                        } else {
                            $data = $dataset["assessments"];
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No assessments found."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "get-assessment-comments" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        add_error($translate->_("No limit provided."));
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveId();
                    }

                    if (isset(${$request_var}["pinned_only"]) && $tmp_input = clean_input(${$request_var}["pinned_only"], array("trim", "int"))) {
                        $PROCESSED["pinned_only"] = true;
                    } else {
                        $PROCESSED["pinned_only"] = false;
                    }

                    if (!$ERROR) {
                        $assessment_data = array();
                        $filters = array();
                        parse_str(${$request_var}["filters"], $filters);

                        /**
                         * Load module preferences
                         */
                        $PREFERENCES = preferences_load("cbme_assessments");

                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"]
                        );

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $visualization_options = array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "actor_course_id" => $PROCESSED["course_id"],
                            "datasource_type" => "progress",
                            "filters" => $filters,
                            "limit_dataset" => array("assessment_comments"),
                            "query_limit" => $PROCESSED["limit"],
                            "query_offset" => $PROCESSED["offset"],
                            "courses" => $courses,
                        );

                        if ($PROCESSED["pinned_only"]) {
                            $visualization_options["limit_dataset"] = array("assessment_comment_pins");
                        }

                        $cbme_progress_api = new Entrada_CBME_Visualization($visualization_options);

                        /**
                         * Fetch the dataset that will be used by the view
                         */
                        $dataset = $cbme_progress_api->fetchData();

                        $data = array();
                        if ($PROCESSED["pinned_only"]) {
                            $data = $dataset["assessment_comment_pins"];
                        } else {
                            $data = $dataset["assessment_comments"];
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No assessments found."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-items" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        add_error($translate->_("No limit provided."));
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveId();
                    }

                    if (isset(${$request_var}["secondary_proxy_id"]) && $tmp_input = clean_input(${$request_var}["secondary_proxy_id"], array("trim", "int"))) {
                        $PROCESSED["secondary_proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["secondary_proxy_id"] = NULL;
                    }

                    if (isset(${$request_var}["pinned_only"]) && $tmp_input = clean_input(${$request_var}["pinned_only"], array("trim", "int"))) {
                        $PROCESSED["pinned_only"] = true;
                    } else {
                        $PROCESSED["pinned_only"] = false;
                    }

                    if (!$ERROR) {
                        $item_data = array();
                        $filters = array();
                        parse_str(${$request_var}["filters"], $filters);

                        /**
                         * Load module preferences
                         */
                        $PREFERENCES = preferences_load("cbme_assessments");

                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"]
                        );

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $visualization_options = array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress",
                            "filters" => $filters,
                            "limit_dataset" => array("items"),
                            "query_limit" => $PROCESSED["limit"],
                            "query_offset" => $PROCESSED["offset"],
                            "courses" => $courses,
                        );

                        if ($PROCESSED["pinned_only"]) {
                            $visualization_options["limit_dataset"] = array("item_pins");
                        }

                        $cbme_progress_api = new Entrada_CBME_Visualization($visualization_options);

                        /**
                         * Fetch the dataset that will be used by the view
                         */
                        $dataset = $cbme_progress_api->fetchData();
                        if ($dataset["items"]) {
                            foreach ($dataset["items"] as $key => $item) {
                                $read_model = new Models_Assessments_Read();
                                if ($PROCESSED["secondary_proxy_id"] != NULL) {
                                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($item["item_id"], "item", $item["aprogress_id"], $PROCESSED["secondary_proxy_id"], true);
                                    if (!$read_record) {
                                        $dataset["items"][$key]["read_id"] = NULL;
                                    } else {
                                        $dataset["items"][$key]["read_id"] = $read_record->getID();
                                    }
                                } else {
                                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($item["item_id"], "item", $item["aprogress_id"], $PROCESSED["proxy_id"], true);
                                    if (!$read_record) {
                                        $dataset["items"][$key]["read_id"] = NULL;
                                    } else {
                                        $dataset["items"][$key]["read_id"] = $read_record->getID();
                                    }
                                }
                            }
                        }
                        $data = array();
                        if ($PROCESSED["pinned_only"]) {
                            $data = $dataset["item_pins"];
                        } else {
                            $data = $dataset["items"];
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No items found."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-contextual-variables":
                    /**
                     * Instantiate the forms api
                     */
                    $forms_api = new Entrada_Assessments_Forms(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                    ));

                    /**
                     * Fetch the standard contextual variables and store them in a format that the advanced search widget can consume
                     */
                    $order_by = "b.`objective_name` ASC";
                    $contextual_variables = $forms_api->fetchContextualVariables($order_by);
                    if ($contextual_variables) {
                        $advanced_search_cv_data = array();
                        foreach ($contextual_variables as $contextual_variable) {
                            $advanced_search_cv_data[] = array("target_id" => html_encode($contextual_variable["objective_id"]), "target_label" => html_encode($contextual_variable["objective_name"]));
                        }
                    }

                    if ($advanced_search_cv_data) {
                        echo json_encode(array("status" => "success", "data" => $advanced_search_cv_data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No contextual variables found."))));
                    }
                    break;
                case "get-contextual-variable-responses":
                    if (isset(${$request_var}["objective_id"]) && $tmp_input = clean_input(${$request_var}["objective_id"], array("trim", "int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No contextual variable provided."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (!$ERROR) {
                        /**
                         * Instantiate the forms api
                         */
                        $forms_api = new Entrada_Assessments_Forms(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        ));

                        $active = 1;
                        $order_by = "a.`objective_name` ASC";

                        $contextual_variable_responses = Models_Objective::fetchAllByParentIDCBMECourseObjective($PROCESSED["objective_id"], $PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation(), $active, $order_by);
                        if ($contextual_variable_responses) {
                            $advanced_search_cv_response_data = array();
                            foreach ($contextual_variable_responses as $contextual_variable_response) {
                                $advanced_search_cv_response_data[] = array("target_id" => html_encode($contextual_variable_response->getID()), "target_label" => $contextual_variable_response->getName());
                            }
                            echo json_encode(array("status" => "success", "data" => $advanced_search_cv_response_data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No contextual variable responses found.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-stage-milestones" :
                    if (isset(${$request_var}["cbme_objective_tree_id"]) && $tmp_input = clean_input(${$request_var}["cbme_objective_tree_id"], array("trim", "int"))) {
                        $PROCESSED["cbme_objective_tree_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No tree identifier provided."));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course identifier provided."));
                    }

                    if (!$ERROR) {
                        // Initialize an objective tree object
                        $tree_object = new Entrada_CBME_ObjectiveTree(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "course_id" => $PROCESSED["course_id"]
                        ));

                        $branch = $tree_object->fetchBranch($PROCESSED["cbme_objective_tree_id"], 6);
                        if ($branch) {
                            $advanced_search_branch_data = array();
                            foreach ($branch as $node) {
                                $advanced_search_branch_data[] = array(
                                    "target_id" => $node["objective_id"],
                                    "target_label" => $node["objective_code"] . ": " . substr($node["objective_name"], 0, 25) . "...",
                                    "target_title" => $node["objective_code"] . " " . $node["objective_name"]
                                );
                            }
                        }

                        $course_utility = new Models_CBME_Course();

                        // For now this API call assumes that if they've made it this far, the learner they are managing is one of their own.
                        // At some point this should be changed to receive a proxy_id like the other methods and operate as normal.
                        if ($ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
                            $courses = array();
                            $course = Models_Course::fetchRowByID($PROCESSED["course_id"]);
                            if ($course) {
                                $courses = array(
                                    "course_id"     => $course->getID(),
                                    "course_name"   => $course->getCourseName(),
                                    "courses"       => $course->toArray()
                                );
                            }
                        } else {
                            $course_utility = new Models_CBME_Course();
                            $courses = $course_utility->getActorCourses(
                                $ENTRADA_USER->getActiveGroup(),
                                $ENTRADA_USER->getActiveRole(),
                                $ENTRADA_USER->getActiveOrganisation(),
                                $ENTRADA_USER->getActiveId()
                            );
                        }

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress",
                            "courses" => $courses
                        ));

                        /**
                         * Sort the advacnedSearch milestone data alphabetically by the target_label key
                         */
                        usort($advanced_search_branch_data, $cbme_progress_api->sortMilestones('target_label'));

                        /**
                         * Remove duplicate milestones from the advancedSearch data
                         */
                        $advanced_search_branch_data = array_unique($advanced_search_branch_data, SORT_REGULAR);

                        if ($advanced_search_branch_data) {
                            echo json_encode(array("status" => "success", "data" => $advanced_search_branch_data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No milestones found.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "my-learners":
                    if (isset(${$request_var}["parent_id"]) && $tmp_input = clean_input(${$request_var}["parent_id"], array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = "";
                        add_error($translate->_("No curriculum period provided."));
                    }
                    $data = array();
                    if ($PROCESSED["parent_id"] == 0) {
                        $cperiod = new Models_Curriculum_Period();
                        echo $cperiod->fetchCurriculumPeriodsAdvancedSearch();
                        break;
                    } else {
                        $parent_object = Models_Curriculum_Period::fetchRowByID($PROCESSED["parent_id"]);

                        $assessment_user = new Entrada_Utilities_AssessmentUser();
                        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                        $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null, $PROCESSED["parent_id"]);
                        $assessment_user->cacheUserCardPhotos($learners);
                        $data = array();
                        if ($learners) {
                            foreach ($learners as $learner) {
                                $cperiod_found = true;
                                if (isset($learner["cperiod_ids"][0])) {
                                    $cperiod_found = false;
                                    foreach ($learner["cperiod_ids"] as $cperiod_id) {
                                        if ($cperiod_id == $PROCESSED["parent_id"]) {
                                            $cperiod_found = true;
                                        }
                                    }

                                }
                                $data[] = array("target_id" => $learner["id"], "target_label" => $learner["firstname"] . " " . $learner["lastname"]);
                            }
                        }
                    }
                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => $parent_object ? date("Y-m-d", $parent_object->getStartDate())." - ".date("Y-m-d", $parent_object->getFinishDate()) : "0" ));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "There were no learners found"));
                    }
                    break;
                case "get-learner-picker-data":
                    if (isset(${$request_var}["parent_id"]) && $tmp_input = clean_input(${$request_var}["parent_id"], array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = "";
                        add_error($translate->_("No curriculum period provided."));
                    }
                    $data = array();
                    if ($PROCESSED["parent_id"] == 0) {
                        $cperiod = new Models_Curriculum_Period();
                        echo $cperiod->fetchCurriculumPeriodsAdvancedSearch();
                        break;
                    } else {
                        $parent_object = Models_Curriculum_Period::fetchRowByID($PROCESSED["parent_id"]);

                        $assessment_user = new Entrada_Utilities_AssessmentUser();
                        $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                        $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null, $PROCESSED["parent_id"]);
                        $assessment_user->cacheUserCardPhotos($learners);
                        $data = array();
                        if ($learners) {
                            foreach ($learners as $learner) {
                                $cperiod_found = true;
                                if (isset($learner["cperiod_ids"][0])) {
                                    $cperiod_found = false;
                                    foreach ($learner["cperiod_ids"] as $cperiod_id) {
                                        if ($cperiod_id == $PROCESSED["parent_id"]) {
                                            $cperiod_found = true;
                                        }
                                    }

                                }
                                $data[] = array("target_id" => $learner["id"], "target_label" => $learner["firstname"] . " " . $learner["lastname"]);
                            }
                        }
                    }
                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => $parent_object ? date("Y-m-d", $parent_object->getStartDate())." - ".date("Y-m-d", $parent_object->getFinishDate()) : "0" ));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "There were no learners found"));
                    }

                break;

                case "get-residents-faculty-courses":
                    if (isset(${$request_var}["advanced_search"]) && $tmp_input = clean_input(${$request_var}["advanced_search"], array("trim", "int"))) {
                        $results_as_advanced_search_datasource = $tmp_input ? true : false;
                    } else {
                        $results_as_advanced_search_datasource = false;
                    }

                    if (isset(${$request_var}["search_value"]) && $tmp_input = clean_input(strtolower(${$request_var}["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(strtolower(${$request_var}["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = null;
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(strtolower(${$request_var}["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = null;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(strtolower(${$request_var}["proxy_id"]), array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = null;
                    }

                    $current_cperiods = Models_Curriculum_Period::fetchAllCurrentIDs();
                    if ($current_cperiods) {
                        $users = User::fetchAllResidentsAndFacultyByCPeriodIDs($ENTRADA_USER->getActiveOrganisation(), $current_cperiods, $PROCESSED["search_value"], 0, $PROCESSED["limit"], $PROCESSED["offset"], null, array("faculty"), $PROCESSED["proxy_id"]);
                        $organisation_id = $ENTRADA_USER->getActiveOrganisation();
                        $target_user = User::fetchRowByID($PROCESSED["proxy_id"]);

                        if (!$ERROR) {
                            if ($target_user) {
                                if ($target_user->getGroup() == "staff" || $target_user->getGroup() == "faculty") {
                                    $courses = Models_Course::getCoursesByContacts($PROCESSED["proxy_id"], $organisation_id);
                                    if (!$courses) {
                                        add_error($translate->_("No course contact found for target"));
                                    }
                                } else {
                                    $cperiod_model = new Models_Curriculum_Period();
                                    $cperiod_ids = $cperiod_model->fetchAllCurrentIDsByOrganisation($organisation_id);
                                    $courses = Models_Course::getCoursesByProxyIDOrganisationID($PROCESSED["proxy_id"], $organisation_id, $cperiod_ids, true);
                                    if (!$courses) {
                                        add_error($translate->_("No active course found for resident"));
                                    }
                                }
                                if ($results_as_advanced_search_datasource) {
                                    $courses = Entrada_Utilities_AdvancedSearchHelper::buildSearchSource($courses, "course_id", "course_name");
                                } else {
                                    $courses = array("courses" => $courses);
                                }
                            }
                        }

                        $data = array();
                        if ($users) {
                            foreach ($users as $user) {
                                $user["stage_code"] = "";
                                $user["stage_name"] = "";
                                /**
                                 * Instantiate the CBME visualization abstraction layer
                                 */
                                $cbme_progress_api = new Entrada_CBME_Visualization(array(
                                    "actor_proxy_id" => $user["proxy_id"],
                                    "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                    "datasource_type" => "progress",
                                ));

                                /**
                                 * Get the learner stage
                                 */
                                $learner_stage = $cbme_progress_api->getLearnerStage();
                                if ($learner_stage) {
                                    $user["stage_code"] = $learner_stage["objective_code"];
                                    $user["stage_name"] = $learner_stage["objective_name"];
                                }

                                /**
                                 * Get learner level data
                                 */
                                $learner_cbme_flag = false;
                                $learner_level_title = "";
                                $learner_level = $cbme_progress_api->getLearnerLevel();
                                if ($learner_level) {
                                    $learner_cbme_flag = $learner_level["cbme_flag"];
                                    $learner_level_title = $learner_level["learner_level"];
                                }

                                $photo_url = ENTRADA_URL . "/api/photo.api.php/" . $PROCESSED["proxy_id"];
                                if ((@file_exists(STORAGE_USER_PHOTOS . "/" . $PROCESSED["proxy_id"] . "-official")) && (@is_readable(STORAGE_USER_PHOTOS . "/" . $PROCESSED["proxy_id"] . "-official"))) {
                                    $photo_url = ENTRADA_URL . "/api/photo.api.php/" . $PROCESSED["proxy_id"] . "/official";
                                } elseif ((@file_exists(STORAGE_USER_PHOTOS . "/" . $PROCESSED["proxy_id"] . "-upload")) && (@is_readable(STORAGE_USER_PHOTOS . "/" . $PROCESSED["proxy_id"] . "-upload"))) {
                                    $photo_url = ENTRADA_URL . "/api/photo.api.php/" . $PROCESSED["proxy_id"] . "/upload";
                                }

                                $data[] = array(
                                    "target_id" => $user["proxy_id"],
                                    "target_label" => $user["firstname"] . " " . $user["lastname"],
                                    "lastname" => $user["lastname"],
                                    "role" => $translate->_(ucfirst($user["role"])),
                                    "email" => $user["email"],
                                    "group" => $user["group"],
                                    "courses" => $courses,
                                    "stage" => $user["stage_code"],
                                    "stage_name" => $user["stage_name"],
                                    "cbme_flag" => $learner_cbme_flag,
                                    "learner_level" => $learner_level_title,
                                    "photo_url" => $photo_url);
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                break;
                case "get-course-setting-tools" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course identifier provided."));
                    }

                    if (!$ERROR) {
                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress"
                        ));

                        $course_tool_settings = $cbme_progress_api->fetchCourseSettingsByShortname($PROCESSED["course_id"], "assessment_tools");
                        if ($course_tool_settings) {
                            echo json_encode(array("status" => "success", "data" => array("course_id" => $PROCESSED["course_id"])));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No tools found")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "get-assessment-stage-data":
                    if (isset(${$request_var}["objective_id"]) && $tmp_input = clean_input(strtolower(${$request_var}["objective_id"]), array("trim", "striptags"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("You must provide an objective id"));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 100;
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No proxy id provided"));
                    }

                    if (!$ERROR) {
                        $assessment_data = array();
                        $filters = array();

                        /**
                         * Load module preferences
                         */
                        $PREFERENCES = preferences_load("cbme_assessments");

                        $course_utility = new Models_CBME_Course();
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"]
                        );

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "actor_course_id" => $PROCESSED["course_id"],
                            "datasource_type" => "progress",
                            "limit_dataset" => array("assessments"),
                            "filters" => $filters,
                            "query_limit" => $PROCESSED["limit"],
                            "query_offset" => $PROCESSED["offset"],
                            "courses" => $courses,
                        ));

                        /**
                         * Fetch the dataset that will be used by the modal
                         */
                        $dataset = $cbme_progress_api->fetchData();
                        $form_objectives = null;
                        $form_objective_model = new Models_Assessments_Form_Objective();
                        $valid_assessments = array();
                        $supervisor_forms = array();
                        $ppa_forms = array();
                        $rubric_forms = array();
                        $fieldnote_forms = array();
                        $procedure_forms = array();
                        $multisource_forms = array();
                        if ($dataset["assessments"]) {
                            foreach ($dataset["assessments"] as &$assessment) {
                                $form_objectives = $form_objective_model->fetchAllByFormID($assessment["form_id"], true);
                                $rating_scale = new Models_Assessments_RatingScale();
                                $scale = $rating_scale->fetchRowByID($assessment["rating_scale_responses"][0]["rating_scale_id"]);
                                if ($scale) {
                                    $assessment["rating_scale_title"] = $scale->getRatingScaleTitle();
                                } else {
                                    $assessment["rating_scale_title"] = $translate->_("N/A");
                                }

                                foreach ($form_objectives as $objective) {
                                    if ($objective["objective_id"] === $PROCESSED["objective_id"]) {
                                        switch ($assessment["form_shortname"]) {
                                            case "cbme_supervisor":
                                                array_push($supervisor_forms, $assessment);
                                                break;
                                            case "cbme_ppa_form":
                                                array_push($ppa_forms, $assessment);
                                                break;
                                            case "cbme_fieldnote":
                                                array_push($fieldnote_forms, $assessment);
                                                break;
                                            case "cbme_multisource_feedback":
                                                array_push($multisource_forms, $assessment);
                                                break;
                                            case "cbme_procedure":
                                                array_push($procedure_forms, $assessment);
                                                break;
                                            case "cbme_rubric":
                                                array_push($rubric_forms, $assessment);
                                                break;
                                        }
                                    }
                                }
                            }
                            if ($supervisor_forms) {
                                array_push($valid_assessments, $supervisor_forms);
                            }
                            if ($ppa_forms) {
                                array_push($valid_assessments, $ppa_forms);
                            }
                            if ($fieldnote_forms) {
                                array_push($valid_assessments, $fieldnote_forms);
                            }
                            if ($multisource_forms) {
                                array_push($valid_assessments, $multisource_forms);
                            }
                            if ($procedure_forms) {
                                array_push($valid_assessments, $procedure_forms);
                            }
                            if ($rubric_forms) {
                                array_push($valid_assessments, $rubric_forms);
                            }
                        }

                        if ($valid_assessments) {
                            echo json_encode(array("status" => "success", "data" => $valid_assessments));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No assessments found."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "get-assessment-breakdown-data":
                    if (isset(${$request_var}["objective_id"]) && $tmp_input = clean_input(strtolower(${$request_var}["objective_id"]), array("trim", "striptags"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("You must provide an objective id"));
                    }

                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = NULL;
                    }

                    if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No proxy id provided"));
                    }

                    if (!$ERROR) {
                        $assessment_data = array();
                        $filters = array();

                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "actor_course_id" => $PROCESSED["course_id"],
                            "filters" => $filters,
                            "limit_dataset" => array("assessments"),
                            "query_limit" => $PROCESSED["limit"],
                            "query_offset" => $PROCESSED["offset"],
                        ));

                        /**
                         * Fetch the dataset that will be used in the assessment breakdown drawer
                         */
                        $dataset = $cbme_progress_api->fetchData();
                        $form_objectives = null;
                        $form_objective_model = new Models_Assessments_Form_Objective();
                        $valid_assessments = array();
                        $supervisor_forms = array();
                        $ppa_forms = array();
                        $rubric_forms = array();
                        $fieldnote_forms = array();
                        $procedure_forms = array();
                        $multisource_forms = array();
                        if ($dataset["assessments"]) {
                            foreach ($dataset["assessments"] as &$assessment) {
                                $form_objectives = $form_objective_model->fetchAllByFormID($assessment["form_id"], true);
                                $rating_scale = new Models_Assessments_RatingScale();
                                $scale = $rating_scale->fetchRowByID($assessment["rating_scale_responses"][0]["rating_scale_id"]);
                                if ($scale) {
                                    $assessment["rating_scale_title"] = $scale->getRatingScaleTitle();
                                    $assessment["rating_scale_id"] = $scale->getID();
                                } else {
                                    $assessment["rating_scale_title"] = $translate->_("N/A");
                                    $assessment["rating_scale_id"] = 0;
                                }

                                foreach ($form_objectives as $objective) {
                                    if ($objective["objective_id"] === $PROCESSED["objective_id"]) {
                                        switch ($assessment["form_shortname"]) {
                                            case "cbme_supervisor":
                                                array_push($supervisor_forms, $assessment);
                                                break;
                                            case "cbme_ppa_form":
                                                array_push($ppa_forms, $assessment);
                                                break;
                                            case "cbme_fieldnote":
                                                array_push($fieldnote_forms, $assessment);
                                                break;
                                            case "cbme_multisource_feedback":
                                                array_push($multisource_forms, $assessment);
                                                break;
                                            case "cbme_procedure":
                                                array_push($procedure_forms, $assessment);
                                                break;
                                            case "cbme_rubric":
                                                array_push($rubric_forms, $assessment);
                                                break;
                                        }
                                    }
                                }
                            }
                            if ($supervisor_forms) {
                                array_push($valid_assessments, $supervisor_forms);
                            }
                            if ($ppa_forms) {
                                array_push($valid_assessments, $ppa_forms);
                            }
                            if ($fieldnote_forms) {
                                array_push($valid_assessments, $fieldnote_forms);
                            }
                            if ($multisource_forms) {
                                array_push($valid_assessments, $multisource_forms);
                            }
                            if ($procedure_forms) {
                                array_push($valid_assessments, $procedure_forms);
                            }
                            if ($rubric_forms) {
                                array_push($valid_assessments, $rubric_forms);
                            }
                        }

                        if ($valid_assessments) {
                            $assessments_data = array();
                            foreach ($valid_assessments as $assessment_array) {
                                $tallied_responses = array();
                                $selected_response = array();
                                $progress = array();
                                $form_ids = array();
                                $rating_scale_ids = array();
                                $number_of_assessments = 0;

                                usort($assessment_array, function($a, $b) {
                                    return strcmp($a['title'], $b['title']);
                                });

                                foreach ($assessment_array as $index => $assessment) {
                                    $responses = array();
                                    $progress[$assessment["aprogress_id"]] = $assessment["aprogress_id"];
                                    $form_ids[$assessment["form_id"]] = $assessment["form_id"];
                                    $rating_scale_ids[$assessment["rating_scale_id"]] = $assessment["rating_scale_id"];
                                    $rating_scale_length = sizeof($assessment["rating_scale_responses"]);
                                    array_push($selected_response, $assessment["selected_iresponse_order"]);
                                    $number_of_assessments ++;

                                    if ($assessment["rating_scale_responses"]) {
                                        foreach ($assessment["rating_scale_responses"] as $key => $scale_response) {
                                            if (!isset($tallied_responses[$key])) {
                                                $tallied_responses[$key]["value"] = 0;
                                                $tallied_responses[$key]["text"] = $scale_response["text"];
                                            }
                                            if ((int)$assessment["selected_iresponse_order"]-1 === $key) {
                                                $tallied_responses[$key]["value"] ++;
                                            }
                                            $tallied_responses[$key]["iresponse_ids"][$scale_response["iresponse_id"]] = $scale_response["iresponse_id"];
                                        }
                                    }

                                    if (isset($assessment_array[$index+1])) {
                                        if ($assessment["title"] === $assessment_array[$index+1]["title"]) {
                                            if ($assessment["rating_scale_title"] != "N/A") {
                                                $selected_response[$index] = $assessment["selected_iresponse_order"];
                                            }
                                        } else {
                                            array_push($assessment_data,
                                                array(
                                                    "rating_scale_length" => $rating_scale_length,
                                                    "selected_response" => $selected_response,
                                                    "title" => $assessment["title"],
                                                    "form_ids" => $form_ids,
                                                    "number_of_assessments" => $number_of_assessments,
                                                    "rating_scale_title" => $assessment["rating_scale_title"],
                                                    "rating_scale_ids" => $rating_scale_ids,
                                                    "tallied_responses" => $tallied_responses,
                                                    "progress" => $progress
                                                )
                                            );
                                            $number_of_assessments = 0;
                                            $selected_response = array();
                                            $tallied_responses = array();
                                            $progress = array();
                                            $form_ids = array();
                                            $rating_scale_ids = array();
                                        }
                                    } else {
                                        array_push($assessment_data,
                                            array(
                                                "rating_scale_length" => $rating_scale_length,
                                                "selected_response" => $selected_response,
                                                "title" => $assessment["title"],
                                                "form_ids" => $form_ids,
                                                "number_of_assessments" => $number_of_assessments,
                                                "rating_scale_title" => $assessment["rating_scale_title"],
                                                "rating_scale_ids" => $rating_scale_ids,
                                                "tallied_responses" => $tallied_responses, "progress" => $progress
                                            )
                                        );
                                        $number_of_assessments = 0;
                                        $selected_response = array();
                                        $tallied_responses = array();
                                        $progress = array();
                                        $form_ids = array();
                                        $rating_scale_ids = array();
                                    }
                                }
                            }
                        }


                        if ($assessment_data) {
                            echo json_encode(array("status" => "success", "data" => $assessment_data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No assessments found."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "get-experience-data":
                    if (!$ERROR) {
                        $cperiod = new Models_Curriculum_Period();
                        echo $cperiod->fetchCurriculumPeriodsAdvancedSearch();
                        break;
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
                case "get-rotations" :
                    if (isset(${$request_var}["parent_id"]) && $tmp_input = clean_input(${$request_var}["parent_id"], array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        $PROCESSED["proxy_id"] = "";
                        add_error($translate->_("No proxy id provided."));
                    }

                    $data = array();
                    if (!$ERROR) {
                        if ($PROCESSED["parent_id"] == 0) {
                            $cperiod = new Models_Curriculum_Period();
                            echo $cperiod->fetchCurriculumPeriodsAdvancedSearch();
                            break;
                        } else {
                            $schedule_model = new Entrada_CBME_RotationSchedule();
                            $schedule_filters = array();
                            $parent_object = Models_Curriculum_Period::fetchRowByID($PROCESSED["parent_id"]);
                            $rotation_schedule_audience_membership = Models_Schedule_Audience::fetchAllByProxyID($PROCESSED["proxy_id"], true);
                            if ($rotation_schedule_audience_membership) {
                                foreach ($rotation_schedule_audience_membership as $audience) {
                                    $schedules = $schedule_model->fetchRotations($audience["schedule_parent_id"], null, $PROCESSED["proxy_id"], $PROCESSED["parent_id"]);
                                    if ($schedules) {
                                        $parent_schedule = Models_Schedule::fetchRowByID($schedules[0]["schedule_parent_id"]);
                                        if (count($schedules) > 1) {
                                            foreach ($schedules as $key => $schedule) {
                                                if ($schedules[0]["schedule_slot_id"] == $schedules[1]["schedule_slot_id"] - 1) {
                                                    //Consecutive
                                                    $start_date = $schedules[0]["start_date"];
                                                    $end_date = $schedules[1]["end_date"];
                                                    $data[] = array("target_id" => $schedule["schedule_id"], "target_label" => $parent_schedule->getTitle() . " (" . date( "Y-m-d", $schedule["start_date"]) . " - " . date("Y-m-d", $schedule["end_date"]) . ")", "start_date" => $schedule["start_date"], "end_date" => $schedule["end_date"]);
                                                    break;
                                                } else {
                                                    $start_date = $schedule["start_date"];
                                                    $end_date = $schedule["end_date"];
                                                    $data[] = array("target_id" => $schedule["schedule_id"], "target_label" => $parent_schedule->getTitle() . " (" . date( "Y-m-d", $schedule["start_date"]) . " - " . date( "Y-m-d", $schedule["end_date"]) . ")", "start_date" => $schedule["start_date"], "end_date" => $schedule["end_date"]);
                                                }
                                            }
                                        } else {
                                            $data[] = array("target_id" => $schedules[0]["schedule_id"], "target_label" => $parent_schedule->getTitle() . " (" . date( "Y-m-d", $schedules[0]["start_date"]) . " - " . date( "Y-m-d", $schedules[0]["end_date"]) . ")", "start_date" => $schedules[0]["start_date"], "end_date" => $schedules[0]["end_date"]);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => $parent_object ? date("Y-m-d", $parent_object->getStartDate())." - ".date("Y-m-d", $parent_object->getFinishDate()) : "0" ));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "There were no rotations found"));
                    }
                break;
                default:
                    echo json_encode(array("status" => "error", "data" => array("No Assessments Available.")));
                    break;
            }
    }

    exit;
}