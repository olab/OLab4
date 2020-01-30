<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleAudience;
use Entrada\Modules\Clinical\Models\Entrada\User;

use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;
use Models_Course;
use Models_Course_Group;
use Models_User_Photo;
use Models_User_LearnerLevel;
use Entrada_CBME_Visualization;

class ScheduleAudienceController extends Controller
{
    protected $input_fields = [];

    public function __construct()
    {
        $this->input_fields = [
            'schedule_id' => 'required|integer|not_in:0',
            'schedule_slot_id' => 'required|integer|not_in:0',
            'audience_type' => 'required|string',
            'audience_value' => 'required|integer|not_in:0',
            'custom_start_date' => 'nullable|integer',
            'custom_end_date' => 'nullable|integer'
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @param int $schedule_id
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     */
    public function index($schedule_id, Request $request)
    {
        return response("index not implemented", 404);
    }

    /**
     * Store a newly created audience record.
     *
     * @param int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store($id, Request $request)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
        $this->authorize('create', $draft_rotation_schedule);

        $this->validate($request, $this->input_fields);

        $audience = RotationScheduleAudience::create($request->all());

        return response($audience, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @param  int $audience_id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($id, $audience_id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
        $this->authorize('update', $draft_rotation_schedule);

        $audience = RotationScheduleAudience::findOrFail($audience_id);
        return response($audience, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id Draft Schedule ID
     * @param  \Illuminate\Http\Request $request
     * @param  int $audience_id Audience to update
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update($id, Request $request, $audience_id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
        $this->authorize('update', $draft_rotation_schedule);

        $this->validate($request, $this->input_fields);

        $audience = RotationScheduleAudience::findOrFail($audience_id);
        $audience->update($request->all());

        return response($audience, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @param  int $audience_id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy($id, $audience_id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
        $this->authorize('delete', $draft_rotation_schedule);

        $audience = RotationScheduleAudience::findOrFail($audience_id);
        $audience->delete();

        return response([], 200);
    }

    /**
     * Fetch the template set of blocks for the schedule.
     *
     * @param  int $id Schedule ID
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function template($id)
    {
        $schedule = DraftRotationSchedule::findOrFail($id);
        $this->authorize('view', $schedule);

        $template = RotationSchedule::fetchLargestTemplateByCPeriodID($schedule->cperiod_id);
        
        return response($template[0]);
    }

    /**
     * Display the learners for a schedule with their assigned audience records.
     *
     * @param int $schedule_id
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function learners($schedule_id, Request $request)
    {
        global $ENTRADA_USER;

        $parameters = $request->all();
        $audience_type = !empty($parameters["audience_type"]) ? $parameters["audience_type"] : "proxy_id";
        $audience_value = !empty($parameters["audience_value"]) ? $parameters["audience_value"] : 0;

        $schedule = DraftRotationSchedule::findOrFail($schedule_id);
        $this->authorize('update', $schedule);

        $course_id = $schedule->course_id;
        $cperiod_id = $schedule->cperiod_id;
        $course = Models_Course::fetchRowByID($course_id);

        if ($audience_type == "proxy_id") {
            if (!$audience_value) {
                $learner_ids = $course->getStudentIDs($cperiod_id);
            } else {
                $learner_ids = [$audience_value];
            }
            $learners = User::fetchAllByLearnerIds($learner_ids);

            // add additional detail for each learner
            foreach ($learners as $learner) {
                $audience = RotationScheduleAudience::fetchScheduleForLearnerIdDraftIdCourseIdCPeriodId($learner->id, $schedule->cbl_schedule_draft_id, $course_id, $cperiod_id);
                if ($audience) {
                    // mark audience records as read only if they are from a different course and not off service
                    foreach ($audience as $record) {
                        $off_service = false;
                        if ($record["slot"]["slot_type"]["slot_type_code"] == "OFFSL" &&
                            (empty($record["slot"]["course_id"]) || $record["slot"]["course_id"] == $course_id )) {
                            $off_service = true;
                        }
                        if (!$off_service && $record["rotation_schedule"]["course_id"] != $course_id) {
                            $record["read_only"] = 1;
                        }
                    }
                    $learner["audience"] = $audience;
                }
                $photo_object = Models_User_Photo::get($learner["id"], Models_User_Photo::UPLOADED);
                if ($photo_object) {
                    $uploaded_photo = $photo_object->toArray();
                    $learner["photo"] = webservice_url("photo", array($learner["id"], isset($uploaded_photo) && $uploaded_photo ? "upload" : "official"));
                }
                $learner["photo"] = webservice_url("photo", array($learner["id"], isset($uploaded_photo) && $uploaded_photo ? "upload" : "official"));

                $learner_level_model = new Models_User_LearnerLevel();
                $learner["level"] = [];
                $learner_level = $learner_level_model->fetchActiveLevelInfoByProxyIDOrganisationID($learner["id"], $ENTRADA_USER->getActiveOrganisation());
                if ($learner_level) {
                    $learner_level["stage_code"] = "";
                    $learner_level["stage_name"] = "";
                    /**
                     * Instantiate the CBME visualization abstraction layer
                     */
                    $cbme_progress_api = new Entrada_CBME_Visualization(array(
                        "actor_proxy_id" => $learner["id"],
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                        "datasource_type" => "progress",
                    ));

                    $learner_stage = $cbme_progress_api->getLearnerStage($learner["id"], $course_id);
                    if ($learner_stage) {
                        $learner_level["stage_code"] = $learner_stage["objective_code"];
                        $learner_level["stage_name"] = $learner_stage["objective_name"];
                    }
                    $learner["level"] = $learner_level;
                }
            }
        } else {
            $learners = [];

            if (!$audience_value) {
                $groups = Models_Course_Group::fetchAllByCourseIDCperiodID($course_id, $cperiod_id);
            } else {
                $groups = [Models_Course_Group::fetchRowByID($audience_value)];
            }
            foreach ($groups as $group) {
                $group_details = $group->toArray();

                $audience = RotationScheduleAudience::fetchByGRoupIdCourseIdCPeriodId($group->getId(), $schedule_id, $course_id, $cperiod_id);
                if ($audience) {
                    $group_details["audience"] = $audience;
                }
                $learners[] = $group_details;
            }
        }

        return response(["audience_type" => $audience_type, "learners" => $learners]);
    }

    /**
     * Display the streams/blocks/slots for a schedule for a date range.
     *
     * @param int $schedule_id
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function slots($schedule_id, Request $request)
    {
        $parameters = $request->all();

        $schedule = DraftRotationSchedule::findOrFail($schedule_id);
        $this->authorize('update', $schedule);

        $start_date = (isset($parameters["start_date"]) ? $parameters["start_date"] : "");
        $end_date = (isset($parameters["end_date"]) ? $parameters["end_date"] : "");

        $query = RotationSchedule::where("draft_id", $schedule_id)
            ->where("schedule_type", "rotation_stream")
            ->with(["children" => function($query) use ($start_date, $end_date)
            {
                if (!empty($start_date) & !empty($end_date)) {
                    $query->whereBetween("start_date", [$start_date, $end_date]);
                    $query->orWhereBetween("end_date", [$start_date, $end_date]);
                    $query->orWhere(function($query) use ($start_date, $end_date) {
                        $query->where("start_date", "<=", $start_date);
                        $query->where("end_date", ">=", $end_date);
                    });
                }
                $query->with("slots");
                $query->with("block_type");
            }]);

        $streams = $query->get();

        return response($streams);
    }
}
