<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Models_User;
use Models_Leave_Tracking;
use Models_Leave_Type;
use Entrada\Modules\Clinical\Models\entrada\LeaveTracking;

class LeaveTrackingController extends Controller
{
    private $input_fields;

    public function __construct()
    {
        $this->input_fields = [
            'proxy_id' => 'required|integer',
            'type_id' => 'required|integer|not_in:0',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_used' => 'required|integer|min:1',
            'weekdays_used' => 'nullable|integer',
            'weekend_days_used' => 'nullable|integer',
            'comments' => 'nullable|string',
        ];
    }

    public function index(Request $request) {

    }

    public function leaveTypes () {
        $leave_types = [];

        foreach(Models_Leave_Type::fetchAllRecords() as $leave_type) {
            $leave_types[] = [ "id" => $leave_type->getID(),
                "type" => $leave_type->getTypeValue()];
        }

        return response($leave_types , 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Entrada\Modules\Clinical\Models\Entrada\LeaveTracking $leaveTracking
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, LeaveTracking $leave_tracking)
    {
        $data = $request->all();
        $this->validate($request, $this->input_fields);

        $new_leave_tracking = $leave_tracking->create(["proxy_id" => $data["proxy_id"],
            "type_id" => $data["type_id"],
            "start_date" => strtotime($data["start_date"] . (isset($data["start_time"]) ? " " . $data["start_time"] : "")),
            "end_date" => strtotime($data["end_date"] . (isset($data["end_time"]) ? " " . $data["end_time"] : "")),
            "days_used" => $data["days_used"],
            "weekdays_used" => $data["weekdays_used"],
            "weekend_days_used" => $data["weekend_days_used"],
            "comments" => $data["comments"]]);

        return response($new_leave_tracking, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Entrada\Modules\Clinical\Models\Entrada\LeaveTracking $leaveTracking
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($user_id, Request $request)
    {
        $paraments = $request->all();
        $cperiod = (isset($paraments["cperiod"]) && $paraments["cperiod"] != "0" ? $paraments["cperiod"] : null);
        $search = (isset($paraments["search_user"]) && $paraments["search_user"] != "" ? $paraments["search_user"] : null);

        $user = Models_User::fetchRowByID($user_id);
        return response(["user" => $user->getFullname(false),
            "leave_trackings" => LeaveTracking::fetchByUser($user_id, $search, $cperiod)]);
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
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $this->validate($request, $this->input_fields);

        $leave_tracking = LeaveTracking::findOrFail($id);

        // Save new data to leavetracking model
        $update = $leave_tracking->update(["proxy_id" => $data["proxy_id"],
            "type_id" => $data["type_id"],
            "start_date" => strtotime($data["start_date"] . (isset($data["start_time"]) ? " " . $data["start_time"] : "")),
            "end_date" => strtotime($data["end_date"] . (isset($data["end_time"]) ? " " . $data["end_time"] : "")),
            "days_used" => $data["days_used"],
            "weekdays_used" => $data["weekdays_used"],
            "weekend_days_used" => $data["weekend_days_used"],
            "comments" => $data["comments"]]);

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $leave_tracking->findOrFail($id)], 200);
        }

        return response(["success" => false], 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);
        foreach ($ids as $id) {
            $leave_tracking = LeaveTracking::findOrFail($id);

            $delete = $leave_tracking->delete();
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
