<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlotType;
use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;
use DB;

class ScheduleRotationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param int $draft_schedule_id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index($draft_schedule_id, Request $request)
    {
        $parameters = $request->all();

        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('view', $draft_rotation_schedule);

        $course_id = $draft_rotation_schedule->course_id;
        $start_date = (isset($parameters["start_date"]) ? $parameters["start_date"]+1 : "");
        $end_date = (isset($parameters["end_date"]) ? $parameters["end_date"]-1 : "");

        $rotations = RotationSchedule::where("draft_id", $draft_schedule_id)
            ->where("schedule_type", "rotation_stream")
            ->with("sites")
            ->get();

        $off_service_id = RotationScheduleSlotType::where("slot_type_code", "=", "OFFSL")
            ->first()
            ->slot_type_id;

        $off_service_blocks = RotationSchedule::join("cbl_schedule_slots", function($join) use($off_service_id, $course_id) {
            $join->on("cbl_schedule_slots.schedule_id", "cbl_schedule.schedule_id")
                ->on("cbl_schedule_slots.slot_type_id", DB::raw($off_service_id))
                ->on(function($query) use ($course_id) {
                    $query->whereNull("cbl_schedule_slots.course_id");
                    $query->orWhere("cbl_schedule_slots.course_id", $course_id);
                });
            })
            ->where("cperiod_id", "=", $draft_rotation_schedule->cperiod_id)
            ->where("draft_id", "!=", $draft_schedule_id)
            ->where(function($query) use($start_date, $end_date) {
                $query->whereBetween("start_date", [$start_date, $end_date]);
                $query->orWhereBetween("end_date", [$start_date, $end_date]);
                $query->orWhere(function($query) use ($start_date, $end_date) {
                    $query->where("start_date", "<=", $start_date);
                    $query->where("end_date", ">=", $end_date);
                });
            })
            ->with("sites")
            ->with("block_type")
            ->with("slots.site")
            ->with("parent.course")
            ->with("parent.sites")
            ->get();

        // these are fetched based on the rotation blocks, but we want to return them based on the rotation stream, so turn it inside out
        $off_service_rotations = [];

        $off_service = $off_service_blocks->toArray();
        foreach ($off_service as $block) {
            if (!array_key_exists($block["schedule_parent_id"], $off_service_rotations)) {
                $off_service_rotations[$block["schedule_parent_id"]] = $block["parent"];
                $off_service_rotations[$block["schedule_parent_id"]]["blocks"] = [];
            }
            unset ($block["parent"]);
            $off_service_rotations[$block["schedule_parent_id"]]["blocks"][] = $block;
        }

        return response(["rotations" => $rotations, "off_service" => array_values($off_service_rotations)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $draft_schedule_id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store($draft_schedule_id, Request $request)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('create', $draft_rotation_schedule);

        return response("not implemented", 404);
    }

    /**
     * Display the specified resource.
     *
     * @param int $draft_schedule_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($draft_schedule_id, $id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('view', $draft_rotation_schedule);

        return response("not implemented", 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $draft_schedule_id
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update($draft_schedule_id, Request $request, $id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('update', $draft_rotation_schedule);

        return response("not implemented", 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $draft_schedule_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($draft_schedule_id, $id)
    {
        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('delete', $draft_rotation_schedule);

        return response("not implemented", 404);
    }

    /**
     * Fetch the rotation blocks for a schedule.
     *
     * @param int $draft_schedule_id
     * @param  int  $rotation_id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function blocks($draft_schedule_id, $rotation_id, Request $request)
    {
        $parameters = $request->all();

        $draft_rotation_schedule = DraftRotationSchedule::findOrFail($draft_schedule_id);
        $this->authorize('view', $draft_rotation_schedule);

        $rotation = RotationSchedule::where("draft_id", $draft_schedule_id)
            ->findOrFail($rotation_id);

        $start_date = (isset($parameters["start_date"]) ? $parameters["start_date"] : "");
        $end_date = (isset($parameters["end_date"]) ? $parameters["end_date"] : "");

        $blocks = RotationSchedule::where("schedule_parent_id", $rotation_id)
            ->where("draft_id", $draft_schedule_id)
            ->where(function($query) use($start_date, $end_date) {
                $query->whereBetween("start_date", [$start_date, $end_date]);
                $query->orWhereBetween("end_date", [$start_date, $end_date]);
                $query->orWhere(function($query) use ($start_date, $end_date) {
                    $query->where("start_date", "<=", $start_date);
                    $query->where("end_date", ">=", $end_date);
                });
            })
            ->with("sites")
            ->with("block_type")
            ->with("slots.site")
            ->get();

        return response(["blocks" => $blocks]);
    }
}
