<?php

namespace Entrada\Modules\Assessments\Http\Controllers;

use Entrada\Modules\Assessments\Models\Assessment;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AssessmentsController extends Controller
{
    private static $my_assessments = null;
    private $_assessment_api = null;

    public function index(Request $request) {



    }

    /**
     * @return array
     */
    public function tasks() {
        $generate = self::my_assessments();

        return ["tasks" => self::$my_assessments];
    }

    /**
     * @param $task_id
     * @param $target_id
     */
    public function singleAssessment(Request $request, $task_id) {
        // TODO Get a single assessment (for pending assessments) (dassessment_id=28711&atarget_id=351534)
        $data = $request->all();
        $progressID = empty($data['progress_id']) ? false : $data['progress_id'];

        $api = Assessment::api();
        $assessment = $api->fetchAssessmentData($task_id);

        if (empty($assessment)) {
            return response(["Assessment not found"], 404);
        }

        return $assessment;
        // TODO ?aprogress_id=:progressID
    }

    /**
     * @param $task_id
     */
    public function deleteTask(Request $request, $task_id) {

        $api = Assessment::api();
        $api->deleteAssessment($task_id);

        return response([
            "Task removed successfully"
        ]);
    }

    /**
     * @param $task_id
     */
    public function targetsForTask(Request $request, $task_id) {

        $api = Assessment::api();
        $targets = $api->getAssessmentTargetList($task_id);

        return $targets;
    }

    /**
     * @param $task_id
     * @param $target_id
     */
    public function singleTarget(Request $request, $task_id, $target_id) {
        global $ENTRADA_USER;

        $data = $request->all();
        $progressID = empty($data['progress_id']) ? false : $data['progress_id'];

        $data = array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "dassessment_id" => $task_id,
            "atarget_id" => $target_id
        );

        if ($progressID) {
            $data['aprogress_id'] = $progressID;
        }

        $api = new \Entrada_Assessments_Assessment($data);
        $assessment_data = $api->fetchAssessmentData($task_id);
        foreach($assessment_data['targets'] as $potentialTarget) {
            if ($potentialTarget['atarget_id'] == $target_id) {
                $target = $potentialTarget;
            }
        }

        if (empty($target)) {
            return response([$this->translate("Unable to locate Target in Assessmsent")], 404);
        }

        $forms_api = new \Entrada_Assessments_Forms(
            array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "form_id" => $assessment_data["meta"]["form_id"],
                "aprogress_id" => $progressID
            )
        );

        $form_data = $forms_api->fetchFormData();

        $assessment = $api->renderAssessment(
            [
                "form_dataset" => $form_data,
                "assessment_mode" => "external",
                "render_html" => false, // in the case where we get an error, but want to render the form anyway (e.g., validation failure).
                "render_as_json" => true
            ],
            false,
            false,
            $target['target_value'],
            $target['target_type'],
            $task_id,
            $progressID
        );

        $assessment = json_decode($assessment, true);
        return $assessment;
    }

    /**
     * @param $task_id
     */
    public function removeTarget(Request $request, $task_id, $target_id) {

        global $ENTRADA_USER;

        $assessment_api = new \Entrada_Assessments_Assessment(
            array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "dassessment_id" => $task_id,
                "limit_dataset" => array("targets")
            )
        );

        if (!$assessment_api->deleteAssessmentByTarget($target_id)) {
            foreach ($assessment_api->getErrorMessages() as $error_message) {
                return response(["status" => "error", "data" => [$error_message]], 500);
            }
        }

        return response(["status" => "success", "data" => ["Target removed"]]);
    }


    /**
     * @param $task_id
     * @param $target_id
     * @param $progress_id
     */
    public function saveTask(Request $request, $task_id, $target_id, $progress_id) {
        /**
         * TODO Save progress of an assessment.
         * Need to figure out exactly what payload needs to be sent.
         * Do we need to pass in progress ID?
         */

    }

    // =====================
    // === Triggering Assessments
    // =====================

    public function getCourses(Request $request) {
        global $ENTRADA_USER;
        $course_utility = new \Models_CBME_Course();
        $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());

        $courses = $course_utility->getActorCourses(
            "student",
            $ENTRADA_USER->getActiveRole(),
            $ENTRADA_USER->getActiveOrganisation(),
            $ENTRADA_USER->getActiveId(),
            null,
            $cperiods
        );

        return $courses;
    }

    public function getUserCourse(Request $request, $user_id = null) {
        global $ENTRADA_USER;
        $this->validate($request, [
            "proxy_id" => empty($user_id) ? "required|int" : "int",
            "advanced_search" => "boolean",
            "assessment_tool" => "boolean"
        ], [
            "proxy_id.required" => $this->translate("Please select an <strong>attending</strong>."),
            "proxy_id.int" => $this->translate("proxy_id should be an integer"),
            "advanced_search.boolean" => $this->translate("advanced_search should be a boolean"),
            "assessment_tool.boolean" => $this->translate("assessment_tool should be a boolean")
        ]);

        $proxy_id = empty($user_id) ? $request->get("proxy_id") : $user_id;
        $results_as_advanced_search_datasource = $request->has("advanced_search") ? $request->get("advanced_search") : false;
        $assessment_tool = $request->has("assessment_tool") ? $request->get("assessment_tool") : false;
        $organisation_id = $ENTRADA_USER->getActiveOrganisation();


        /*
         * Instantiate the CBME visualization abstraction layer
         */
        $cbme_progress_api = new \Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress"
        ));

        if ($assessment_tool) {
            $courses = \Models_Course::getCoursesByContacts($proxy_id, $organisation_id);
            if (empty($courses)) {
                return response([$this->translate("Unknown")], 404);
            }
        } else {
            $cperiod_model = new \Models_Curriculum_Period();
            $cperiod_ids = $cperiod_model->fetchAllCurrentIDsByOrganisation($organisation_id);
            $courses = \Models_Course::getCoursesByProxyIDOrganisationID($proxy_id, $organisation_id, $cperiod_ids, true);
            if (empty($courses)) {
                return response([$this->translate("No active course found for resident")], 404);
            }
        }
        if ($results_as_advanced_search_datasource) {
            $courses = \Entrada_Utilities_AdvancedSearchHelper::buildSearchSource($courses, "course_id", "course_name");
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

        return response(["status" => "success", "data" => $courses]);
    }

    public function getCourseEPAs(Request $request, $course_id = null ) {
        global $ENTRADA_USER;

        $this->validate($request, [
            "course_id" => empty($course_id) ? "int|required" : "int",
            "proxy_id" => "required|int"
        ], [
            "course_id.required" => $this->translate("Please select a <strong>%s</strong>.", ["%s" => "course"]),
            "course_id.int" => $this->translate("Course ID should be an integer"),
            "proxy_id.required" => $this->translate("No learner was provided."),
            "proxy_id.int" => $this->translate("Proxy ID should be an integer"),
        ]);

        $course_id = empty($course_id) ? $request->get("course_id") : $course_id;
        $proxy_id = $request->get("proxy_id");


        $forms_api = new \Entrada_Assessments_Forms(array(
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
        $cbme_progress_api = new \Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress"
        ));

        $filter_presets = $cbme_progress_api->getLearnerEpaFilterPresets($this->translate("Current Stage EPAs"), $proxy_id, $course_id);

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

        return response([
            "status" => "success",
            "data" => [
                "epas" => $epa_advanced_search_data,
                "course_requires_date_of_encounter" => $PROCESSED["course_requires_date_of_encounter"],
                "filter_presets" => $filter_presets
            ]
        ]);
    }

    public function getAssessmentMethods(Request $request) {
        global $ENTRADA_USER;

        $this->validate($request, [
            "course_id" => "required|int"
        ], [
            "course_id.required" => $this->translate("No Course selected."),
            "course_id.int" => $this->translate("Course ID should be an integer"),
        ]);

        $course_id = $request->get("course_id");

        /**
         * Instantiate the CBME visualization abstraction layer
         */
        $cbme_progress_api = new \Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "datasource_type" => "progress"
        ));
        $assessment_methods = $cbme_progress_api->fetchCourseAssessmentMethods($course_id, $ENTRADA_USER->getActiveGroup());
        if ($assessment_methods) {
            return response([
                "status" => "success",
                "data" => $assessment_methods
            ]);
        } else {
            return response([
                "status" => "error",
                "data" => [$this->translate("No assessment methods found.")]
            ], 404);
        }
    }

    public function getUserPIN(Request $request, $user_id = null) {
        global $ENTRADA_USER;

        $this->validate($request, [
            "proxy_id" => empty($user_id) ? "required|int" : "int"
        ], [
            "proxy_id.required" => $this->translate("Please select an <strong>attending</strong>."),
            "proxy_id.int" => $this->translate("Proxy ID should be an integer")
        ]);

        $proxy_id = empty($user_id) ? $request->get("proxy_id") : $user_id;

        $has_pin = true;
        $user = \Models_User::fetchRowByID($proxy_id);
        if (!empty($user)) {
            if (!$user->getPin()) {
                $has_pin = false;
            }
        } else {
            return response(["status" => "failure", "data" => [$this->translate("No user found")]], 404);
        }

        return response(["status" => "success", "data" => ["has_pin" => $has_pin]]);
    }

    public function getAssessmentTools(Request $request)
    {
        global $ENTRADA_USER;

        $this->validate($request, [
            "node_id" => "required|int",
            "course_id" => "required|int",
            "subject_id" => "int"
        ], [
            "node_id.required" => $this->translate("Please select an <strong>EPA</strong>."),
            "node_id.int" => "Node ID should be an integer",
            "course_id.required" => $this->translate("No Course selected."),
            "course_id.int" => "Course ID should be an integer",
            "subject_id.int" => "Subject ID should be an integer",

        ]);

        /**
         * @var $node_id
         * @var $course_id
         * @var $subject_id
         */
        extract($request->all());

        $forms_api = new \Entrada_Assessments_Forms(array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
            )
        );

        $forms_tagged = $forms_api->fetchFormsTaggedToTreeBranch($node_id, $course_id, $subject_id);
        if (empty($forms_tagged)) {
            return response([$this->translate("No Assessment Tools found.")]);
        }
        // Format time
        foreach ($forms_tagged as &$form) {
            if ($form["average_time"]) {
                $form["average_time"] = ($form["average_time"] > 3599) ? gmdate("h:i:s", $form["average_time"]) : gmdate("i:s", $form["average_time"]);
            } else {
                $form["average_time"] = $this->translate("N/A");
            }
        }

        return response(["status" => "success", "data" => $forms_tagged]);
    }

    public function triggerAssessment(Request $request) {
        global $ENTRADA_USER;
        
        $this->validate($request, [
            "form_id" => "int|required",
            "assessor_value" => "int|required",
            "assessment_method_id" => "int|required",
            "target_record_id" => "int|required",
            "course_id" => "int|required",
            "assessment_cue" => "string",
            "encounter_date" => "required|string"
        ], [
            "form_id|required" => $this->translate("No form identifier provided."),
            "assessor_value|required" => $this->translate("Please select an <strong>attending</strong>."),
            "assessment_method_id|required" => $this->translate("Please select an <strong>assessment method</strong>."),
            "target_record_id|required" => $this->translate("No target provided."),
            "course_id|required" => $this->translate("No course identifier provided."),
            "assessment_cue|string" => "",
            "encounter_date|string" => "",
            "encounter_date|required" => ""
        ]);
        
        /**
         * @var $form_id
         * @var $assessor_value
         * @var $assessment_method_id
         * @var $target_record_id
         * @var $course_id
         * @var $assessment_cue
         * @var $encounter_date
        */
        // Pull variables from the Request array into the symbol table, eg "user_id" => 5 becomes $user_id = 5
        extract($request->all());
        
        $assessment_method_model = new \Models_Assessments_Method();
        $assessment_method = $assessment_method_model->fetchRowByID($assessment_method_id);
        if (!$assessment_method) {
            return response([$this->translate("No assessment method found")], 404);
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
            $cbme_progress_api = new \Entrada_CBME_Visualization(array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "datasource_type" => "progress"
            ));

            $PROCESSED["course_requires_date_of_encounter"] = $cbme_progress_api->courseRequiresDateOfEncounter($course_id);
        }

        if ($PROCESSED["course_requires_date_of_encounter"]) {
            $this->validate($request, [
                "encounter_date" => "required"
            ], [
                "encounter_date|required" => "No date of encounter provided."
            ]);

            $dt = \DateTime::createFromFormat("Y-m-d H:i:s", $encounter_date . " 00:00:00");
            if ($dt === false || array_sum($dt->getLastErrors())) {
                return response([$this->translate("An invalid date was provided.")], 400);
            } else {
                $encounter_date = $dt->getTimestamp();
            }
        }

        $notify_id = false; // The default is to not notify; notifications should be sent out when appropriate, on assessment completion (via assessment method hook).

        $assessment_type_id = \Models_Assessments_Type::fetchAssessmentTypeIDByShortname("cbme");
        if (!$assessment_type_id) {
            return response([$this->translate("Invalid assessment type ID.")], 404);
        }

        $assessment_method = new \Models_Assessments_Method();
        if (!$assessment_method = $assessment_method->fetchRowByID($assessment_method_id)) {
            return response([$this->translate("Invalid assessment method ID")], 404);
        }

        $assessment_api = Assessment::api([
            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            "limit_dataset" => array("targets", "assessment_method")
            ], false);

        $add_assessment_option = true;
        switch ($assessment_method->getShortname()) {
            case "default":
            case "send_blank_form" :
                // Create one single form, not linked to anything, with the attending as assessor.
                $status = $assessment_api->createAssessment([
                        "form_id" => $form_id,
                        "course_id" => $course_id,
                        "assessment_type_id" => $assessment_type_id,
                        "assessment_method_id" => $assessment_method_id,
                        "assessment_method_data" => json_encode(["assessor_group" => "faculty"]),
                        "assessor_value" => $assessor_value, // defaults to assessor type internal
                        "published" => 1,
                        "encounter_date" => $encounter_date
                    ],
                    [
                        ["target_value" => $target_record_id]
                    ]
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
            // TODO Should there be a break here or does this case merge into the following case
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
        if (isset($status) && $add_assessment_option) {
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
        if (empty($status)) {
            $errors = [];
            foreach ($assessment_api->getErrorMessages() as $error) {
                $errors[] = $error;
            }

            return response($errors, 500);
        }


        if (empty($ERROR) && $status && $notify_id) {
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
        if (empty($ERROR)) {
            switch ($assessment_method->getShortname()) {
                case "send_blank_form" :
                    $url = ENTRADA_URL . "/assessments?section=tools&success=true";
                    break;
                case "complete_and_confirm_by_email" :
                case "complete_and_confirm_by_pin" :
                case "double_blind_assessment" :
                case "faculty_triggered_assessment" :
                case "default":
                default:
                    $url = $assessment_api->getAssessmentURL($ENTRADA_USER->getActiveId(), "proxy_id", false);
                    break;
            }
           return response([
                "status" => "success",
                "data" => array("url" => $url)
            ]);
        } else {
            return response([
                "status" => "error",
                // TODO I don't really know how the Entrada Error system normally works so I've been returning errors all along
                "data" => $ERROR //$this->translate("There was an error")
            ]);
        }
    }

    public function saveWithPost(Request $request, $task_id, $target_id) {
        $this->validate($request, [

        ], [

        ]);

        $data = $request->all();
        $data['dassessment_id'] = $task_id;
        $data['atarget_id'] = $target_id;

        $submit_form = $request->has("submit_form") ? !empty($request->get("submit_form")) : false;

        $api = new \Entrada_Assessments_Assessment($data);

        $assessment_data = $api->fetchAssessmentData($task_id);
        foreach($assessment_data['targets'] as $potentialTarget) {
            if ($potentialTarget['atarget_id'] == $target_id) {
                $target = $potentialTarget;
            }
        }

        if (empty($target)) {
            return response([$this->translate("Unable to locate Target in Assessmsent")], 404);
        }

        $data['target_value'] = $target['target_value'];
        $data['target_record_id'] = $target_id;



        if ($api->saveAssessmentByPost($data, $submit_form)){
            return response(["status" => "success", "data"=> [$this->translate("Assessment saved successfully")]]);
        } else {
            return response(["status" => "error", "data"=> [$this->translate("There was an error")]], 500);
        }
    }
    
    public function saveResponses(Request $request) {
        global $ENTRADA_USER;

        $this->validate($request, [
            "dassessment_id" => "int|required",
            "aprogress_id" => "int",
            "target_record_id" => "int|required",
            "target_type" => "string|required"
        ],[
            "dassessment_id.required" => $this->translate("Assessment ID is required"),
            "target_record_id.required" => $this->translate("Target Record ID is required"),
            "target_type.required" => $this->translate("Target Type is required"),
            "dassessment_id.int" => $this->translate("Assessment ID should be an integer"),
            "aprogress_id.int" => $this->translate("Progress ID should be an integer"),
            "target_record_id.int" => $this->translate("Target Record ID should be an integer"),
            "target_type.string" => $this->translate("Target Type should be a string")
        ]);

        $progress_id = null;

        $data = $request->all();


        $assessment_api = new \Entrada_Assessments_Assessment(
            array (
                "dassessment_id" => $data["dassessment_id"],
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                "limit_dataset" => array("assessor", "course_owners", "targets") // limit dataset to only that which is required to determine if the user can complete the assessment
            )
        );
        if ($data["aprogress_id"]) {
            // We have a progress ID already, so use it.
            $progress_id = $data["aprogress_id"];
        }
        $assessment_api->setAprogressID($progress_id);
        if ($assessment_api->canUserCompleteAssessment(($ENTRADA_USER->getActiveRole() == "admin"), false, false, $data["target_type"], $data["target_record_id"])) {
            // We pass the posted data directly to updateProgressResponses; it will perform the required validation.
            if (!$assessment_api->updateProgressResponses($_POST, $data["target_record_id"], $data["target_type"])) {
                foreach ($assessment_api->getErrorMessages() as $error_message) {
                    return response(["status" => "error", "message" => $error_message], 500);
                }
            }
            $progress_id = $assessment_api->getAprogressID();
        } else {
            return response([$this->translate("You do not have permission to update this assessment.")], 400);
        }

        return response(["status" => "success", "data" => ["saved" => date("g:i:sa", time()), "aprogress_id" => $progress_id]]);
    }

    public function verifyPin(Request $request) {
        global $ENTRADA_USER;

        $this->validate($request, [
            "assessor_pin" => "required|string",
            "dassessment_id" => "required|int",
            "aprogress_id" => "required|int",
            "assessor_id" => "required|int",
        ], [
            "assessor_pin.required" => $this->translate("No PIN provided."),
            "dassessment_id.required" => $this->translate("No assessment ID provided."),
            "aprogress_id.required" => $this->translate("No progress ID provided."),
            "assessor_id.required" => $this->translate("Unknown assessor"),
        ]);

        /**
         * @var $dassessment_id
         * @var $aprogress_id
         * @var $assessor_id
         * @var $assessor_pin
         */
        extract($request->only(["dassessment_id", "aprogress_id", "assessor_id", "assessor_pin"]));

        $assessment_api = new \Entrada_Assessments_Assessment();
        $pin_nonce = $assessment_api->generateAssessorPinNonce($dassessment_id, $aprogress_id, $assessor_id, $assessor_pin);
        if ($pin_nonce) {
            $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_nonce"][$aprogress_id] = $pin_nonce;
        } else {
            // if pin_nonce is false, then it failed validation (or other error)
            foreach ($assessment_api->getErrorMessages() as $error_message) {
                return response(["status" => "error", "data" => [$error_message]], 500);
            }
        }

        return response(["status" => "success", "data" => ["success" => true]]);
    }

    private function translate($str, $args = []) {
        /*global $TRANSLATE;

        return $this->translate($str);*/


        return __($str, []);
    }
        /**
     * Display assessments summary
     *
     * @return \Illuminate\Http\Response|array
     */
    public static function appendToUserSummary() {
        return [
            'pending' => count(self::my_assessments("pending"))
        ];
    }




    private static function my_assessments($type = "pending") {

        global $ENTRADA_USER;

        if (is_null(self::$my_assessments)) {

            $entrada_actor = array(
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
            );

            // dd($entrada_actor);

            $assessment_tasks = new \Entrada_Assessments_Tasks($entrada_actor);

            $user_filters = $assessment_tasks->getFilterValuesFromSession("assessments");

            $pending_limit = 60; // Limit to 60 for pending assessments
            $pending_offset = 0;

            $pending_tasks_lists = array("assessor-pending");
            $pending_tasks_filters = array(
                "limit" => $pending_limit,
                "offset" => $pending_offset,
                "sort_order" => "asc",
                "sort_column" => 28 // Column #28 is delivery date
            );
            $assessment_tasks->setFilters(array_merge($pending_tasks_filters, $user_filters));
            $assessments = $assessment_tasks->fetchAssessmentTaskList($pending_tasks_lists, $entrada_actor["actor_proxy_id"]);


            self::$my_assessments = $assessments;
        }

        return self::$my_assessments;
    }
}
