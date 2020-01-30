<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleBlockType;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlotType;
use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;
use Models_Curriculum_Type;
use Models_Curriculum_Period;
use Entrada_Utilities_AssessmentUser;
use Models_Logbook_Entry;
use Models_Course;
use Entrada_Utilities;
use Models_Schedule_Draft;
use Models_User_Photo;
use Entrada_Settings;
use User;

class ClinicalController extends Controller
{
    /**
     * Returns the curriculum period list
     *
     * @return \Illuminate\Http\Response
     */
    public function curriculumPeriod() {
        global $ENTRADA_USER;

        $curriculum_periods = [];
        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());

        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                $periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                if ($periods) {
                    foreach ($periods as $period) {
                        $period_arr = $period->toArray();
                        $title = (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate()));
                        $period_arr["display_title"] = $title;
                        $curriculum_periods[$curriculum_type->getCurriculumTypeName()][] = $period_arr;
                    }
                }
            }
        }
        return response(["cperiods" => $curriculum_periods , "organisation" => $ENTRADA_USER->getActiveOrganisation()] , 200);
    }

    /**
     * Returns the courses
     *
     * @return \Illuminate\Http\Response
     */
    public function courses() {
        global $ENTRADA_USER;

        $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
        $courses_arr = [];

        foreach ($courses as $course) {
            $courses_arr[] = ["id" => $course->getID(), "title" => $course->getCourseCode() . " - " . $course->getCourseName()];
        }

        return response($courses_arr, 200);
    }

    /**
     * Returns the RotationScheduleSlotType
     *
     * @return \Illuminate\Http\Response
     */
    public function slotTypes() {

        return response(RotationScheduleSlotType::whereNull("deleted_date")->get(), 200);
    }

    /**
     * Returns the RotationScheduleSlotType
     *
     * @return \Illuminate\Http\Response
     */
    public function blockTypes() {

        return response(RotationScheduleBlockType::whereNull("deleted_date")->get(), 200);
    }

    /**
     * Returns the path for the given course and cperiod
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function RotationSchedulePath(Request $request) {
        $data = $request->all();

        $cperiod = Models_Curriculum_Period::fetchRowByID($data["cperiod_id"]);
        $curriculum_path = curriculum_hierarchy($data["course_id"], true, false);

        if (is_array($curriculum_path) && !empty($curriculum_path)) {
            $curriculum_path = implode(" &gt; ", $curriculum_path);
        }
        $path = $curriculum_path . " &gt; " .  date("F jS, Y", html_encode($cperiod->getStartDate()))." to ".date("F jS, Y", html_encode($cperiod->getFinishDate()));

        return response(["path" => $path], 201);
    }

    /**
     * Returns the countries list
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myLearners(Request $request) {
        global $ENTRADA_USER, $ENTRADA_URL;

        $data = [];
        $settings = [];
        $paraments = $request->all();
        $cperiod = (isset($paraments["cperiod"]) && $paraments["cperiod"] != "0" ? $paraments["cperiod"] : null);

        $assessment_user = new Entrada_Utilities_AssessmentUser();
        $is_admin = Entrada_Utilities::isCurrentUserSuperAdmin(array(array("resource" => "assessmentreportadmin")));
        $cbme_enabled = (bool) (new Entrada_Settings)->read("cbme_enabled");
        $settings["cbme_enabled"] = $cbme_enabled;

        $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $is_admin, $request->input("search_term"), $cperiod);
        if ($learners) {
            foreach ($learners as $learner) {
                $photo_object = Models_User_Photo::get($learner["id"], Models_User_Photo::UPLOADED);
                if ($photo_object) {
                    $uploaded_photo = $photo_object->toArray();
                }
                $learner["photo"] = webservice_url("photo", array($learner["id"], isset($uploaded_photo) && $uploaded_photo ? "upload" : "official"));

                $learner["logbook"] = count(Models_Logbook_Entry::fetchAll($learner["id"])) > 0;
                if ($learner["logbook"]) {
                    $learner["logbook_link"] = $ENTRADA_URL . "/logbook?proxy_id=" . $learner["id"];
                }

                if ($cbme_enabled) {
                    $learner["cbme_link"] = $ENTRADA_URL . "/assessments/learner/cbme?proxy_id=" . $learner["id"];
                }

                $learner["assessment"] = $ENTRADA_URL. "/assessments/learner?proxy_id=" . $learner["id"];
                $data[] = $learner;
            }
        }

        return response(["learners" => $data, "settings" => $settings] , 200);
    }

    /**
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function publishedSchedules() {
        global $ENTRADA_USER, $ENTRADA_URL;

        $this->authorize('view', new DraftRotationSchedule());

        $drafts = array();
        $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
        if ($courses) {
            foreach ($courses as $course) {
                $schedule_draft = Models_Schedule_Draft::fetchAllByProxyIDCourseID($ENTRADA_USER->getActiveID(), $course->getID(), "live");
                foreach ($schedule_draft as $schedule_draft_record) {
                    $schedule = $schedule_draft_record->toArray();
                    $schedule["course_name"] = $course->getCourseCode() . " - " . $course->getCourseName();
                    $schedule["created_date_draft"] = date("Y-m-d", $schedule["created_date"]);
                    $schedule["edit_url"] = $ENTRADA_URL . "/admin/rotationschedule?section=edit-draft&draft_id=" . $schedule["cbl_schedule_draft_id"];
                    $drafts[] = $schedule;
                }
            }
        }

        return response($drafts , 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getUsersByGroup(Request $request)
    {
        global $ENTRADA_USER;

        $this->authorize('view', new DraftRotationSchedule());

        $data = $request->all();
        $search = (isset($data["search"]) ? $data["search"] : "");
        $group = (isset($data["group"]) ? $data["group"] : "staff");

        $output = [];
        $users = User::fetchUsersByGroups($search, $group, $ENTRADA_USER->getActiveOrganisation(), AUTH_APP_ID);
        if ($users) {
            foreach ($users as $user) {
                $output[] = array("fullname" => $user["lastname"] . ", " . $user["firstname"], "id" => $user["proxy_id"], "email" => $user["email"]);
            }
        }

        return response($output, 200);
    }
}
