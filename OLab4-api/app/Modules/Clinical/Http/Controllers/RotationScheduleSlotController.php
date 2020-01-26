<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlot;
use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;


class RotationScheduleSlotController extends Controller
{
    public function __construct()
    {
        $this->input_fields = [
            'slot_type_id' => "required|integer|not_in:0",
            'slot_min_spaces' => "nullable|integer",
            'slot_spaces' => "nullable|integer",
        ];
    }

    /**
     * Display a listing of the resource.
     * This is currently not implemented
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response("item not found", 404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @param  RotationScheduleSlot $rotationScheduleSlot
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, RotationScheduleSlot $rotationScheduleSlot)
    {
        $data = $request->all();

        $rotation = RotationSchedule::findOrFail($data["schedule_id"]);
        $draft = DraftRotationSchedule::findOrFail($rotation->draft_id);
        $this->authorize('create', $draft);

        $this->validate($request, $this->input_fields);
        $new = $rotationScheduleSlot->create($request->all());

        return response($new, 201);
    }
    /**
     * Display the specified resource.
     * This is currently not implemented
     *
     * @param  int $rotation_schedule_slot_id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function show($rotation_schedule_slot_id, Request $request)
    {
        return response("item not found", 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * Note: PUT and PATCH methods in Laravel require
     * an extra header: "Content-Type: application/x-www-form-urlencoded"
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, $id)
    {
        $slot = RotationScheduleSlot::findOrFail($id);
        $rotation = RotationSchedule::findOrFail($slot->schedule_id);
        $draft = DraftRotationSchedule::findOrFail($rotation->draft_id);

        $this->authorize('update', $draft);

        // Validate request
        $this->validate($request, $this->input_fields);

        // Save new data to Rotation Schedule Slot model
        $update = $slot->update($request->all());

        return response($slot, 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);

        $rotation_schedule_slots = [];
        foreach ($ids as $id) {
            $rotation_schedule_slot = RotationScheduleSlot::findOrFail($id);
            $rotation = RotationSchedule::findOrFail($rotation_schedule_slot->schedule_id);
            $draft = DraftRotationSchedule::findOrFail($rotation->draft_id);

            $this->authorize('delete', $draft);
            $rotation_schedule_slots[] = $rotation_schedule_slot;
        }

        foreach ($rotation_schedule_slots as $rotation_schedule_slot) {
            $delete = $rotation_schedule_slot->delete();
            
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
}
