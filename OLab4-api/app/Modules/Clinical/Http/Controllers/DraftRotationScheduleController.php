<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\DraftRotationScheduleAuthor;
use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;
use Models_Course;
use Entrada_Utilities;

class DraftRotationScheduleController extends Controller
{
    protected $input_fields = [];

    public function __construct()
    {
        $this->input_fields = [
            'draft_title' => 'required|string',
            'course_id' => 'required|integer|not_in:0',
            'cperiod_id' => 'required|integer|not_in:0',
        ];

    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        global $ENTRADA_USER;

        $this->authorize('view', new DraftRotationSchedule());

        $paraments = $request->all();
        $cperiod = (isset($paraments["cperiod"]) && $paraments["cperiod"] != "0" ? $paraments["cperiod"] : null);
        $search = (isset($paraments["search"]) && $paraments["search"] != "" ? $paraments["search"] : null);
        $status = (isset($paraments["status"]) && $paraments["status"] != "" ? $paraments["status"] : "draft");

        $rotation_schedules = array();
        $is_admin = Entrada_Utilities::isCurrentUserSuperAdmin();

        if ($is_admin) {
            $rotation_schedules = DraftRotationSchedule::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation(), $status, $cperiod, $search);
        } else {
            $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
            if ($courses) {
                foreach ($courses as $course) {
                    $rotation_schedules = DraftRotationSchedule::fetchAllByProxyIDCourseID($ENTRADA_USER->getActiveID(), $course->getID(), $status, $cperiod, $search);
                }
            }
        }

        $user_drafts = DraftRotationSchedule::fetchAllByProxyID($ENTRADA_USER->getActiveID(), $status, $cperiod, $search);
        if (!$user_drafts->isEmpty()) {
            $rotation_schedules = $rotation_schedules->merge($user_drafts);
            $rotation_schedules = $rotation_schedules->unique();
        }

        return response($rotation_schedules, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        global $ENTRADA_USER;
        // Authorizes the creation of DraftRotationSchedule
        $draft_rotation_schedule = new DraftRotationSchedule();
        $this->authorize('create', $draft_rotation_schedule);

        $this->validate($request, $this->input_fields);

        $draft_rotation_schedule = DraftRotationSchedule::create($request->all());

        $draft_author_course = DraftRotationScheduleAuthor::create(
            ["cbl_schedule_draft_id" => $draft_rotation_schedule->cbl_schedule_draft_id,
                "author_value" => $draft_rotation_schedule->course_id,
                "author_type" => "course_id"]
        );

        $draft_authors_proxyid = DraftRotationScheduleAuthor::create(
            ["cbl_schedule_draft_id" => $draft_rotation_schedule->cbl_schedule_draft_id,
                "author_value" => $ENTRADA_USER->getActiveID(),
                "author_type" => "proxy_id"]
        );

        return response($draft_rotation_schedule, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $draft_id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($draft_id)
    {
        $show = DraftRotationSchedule::with("course")
            ->with("authors.user")
            ->with(["authors" => function ($query) {
                $query->where("author_type", "proxy_id");
            }])
            ->findOrFail($draft_id);

        $this->authorize('view', $show);

        return response($show, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * Note: PUT and PATCH methods in Laravel require
     * an extra header: "Content-Type: application/x-www-form-urlencoded"
     *
     * @param  Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update($id, Request $request)
    {
        $this->validate($request, ['draft_title' => 'required|string']);
        $data = $request->all();
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);

        $this->authorize('update', $draft_rotation_schedule);

        // Save new data to DraftRotationSchedule model
        $update = $draft_rotation_schedule->update(["draft_title" => $data["draft_title"]]);

        $new_authors = [];
        if (isset($data["authors"]) && !empty($data["authors"])) {
            foreach ($data["authors"] as $author) {
                $new_authors[] = $author["id"];
                DraftRotationScheduleAuthor::firstOrCreate(
                    ["cbl_schedule_draft_id" => $draft_rotation_schedule->cbl_schedule_draft_id,
                        "author_value" => $author["id"],
                        "author_type" => "proxy_id"]);
            }
        }

        DraftRotationScheduleAuthor::where("cbl_schedule_draft_id", $draft_rotation_schedule->cbl_schedule_draft_id)
            ->whereNotIn("author_value", $new_authors)->delete();

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $draft_rotation_schedule->findOrFail($id)], 200);
        }

        return response(["success" => false], 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);
        $draft_rotation_schedules = [];

        // you must have access to delete every one of the schedules. Otherwise nothing is deleted
        foreach ($ids as $id) {
            $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
            $this->authorize('delete', $draft_rotation_schedule);
            $draft_rotation_schedules[] = $draft_rotation_schedule;
        }

        foreach ($draft_rotation_schedules as $draft_rotation_schedule) {
            $id = $draft_rotation_schedule->cbl_schedule_draft_id;

            $delete = $draft_rotation_schedule->delete();

            DraftRotationScheduleAuthor::where("cbl_schedule_draft_id", $id)->delete();

            if (!$delete) {
                $success = false;
            }
        }

        if ($success) {
            // Successful delete returns a 204
            return response([], 200);
        }

        return response([], 404);
    }

    public function copyExistingRotation(Request $request)
    {
        $data = $request->all();

        if (isset($data["copy_draft_id"])) {
            if (isset($data["draft_id"])) {
                return DraftRotationSchedule::copyExistingRotation($data["copy_draft_id"], $data["draft_id"]);
            } else {
                return response(["success" => false, "data" => "You are missing some required parameters"]);
            }
        } else {
            return response(["success" => false, "data" => "You are missing some required parameters"]);
        }
    }

    public function export(Request $request)
    {
        $data = $request->all();

        if (isset($data["draft_id"])) {
            if (isset($data["block_type_id"])) {
                return DraftRotationSchedule::export($data["draft_id"], $data["block_type_id"]);
            } else {
                return response(["success" => false, "data" => "You are missing some required parameters"]);
            }
        } else {
            return response(["success" => false, "data" => "You are missing some required parameters"]);
        }
    }

    public function changeStatus($ids, Request $request)
    {
        $success = true;
        $this->validate($request, ['status' => 'required|string']);
        $ids = explode(",", $ids);

        // you must have access to delete every one of the schedules. Otherwise nothing is updated
        foreach ($ids as $id) {
            $draft_rotation_schedule = DraftRotationSchedule::findOrFail($id);
            $this->authorize('update', $draft_rotation_schedule);
            $draft_rotation_schedules[] = $draft_rotation_schedule;
        }

        foreach ($draft_rotation_schedules as $draft_rotation_schedule) {

            $update = $draft_rotation_schedule->update(["status" => $request->status]);

            if (!$update) {
                $success = false;
            }
        }
        if ($success) {
            // Successful delete returns a 204
            return response([], 200);
        }

        return response([], 404);

    }
}
