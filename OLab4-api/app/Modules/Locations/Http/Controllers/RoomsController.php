<?php

namespace Entrada\Modules\Locations\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\Room;

class RoomsController extends Controller
{
    protected $input_fields = [];

    public function __construct()
    {
        $this->input_fields = [
            "building_id" => "required|integer",
            "room_number" => "required|string",
            "room_name" => "string",
            "room_description" => "string",
            "room_max_occupancy" => "required|integer"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\Room $room
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Room $room)
    {
        $this->authorize('view', $room);

        return response([
            'rooms' => Room::get()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($id)
    {

        $room = Room::findOrFail($id);

        $this->authorize('view', $room);

        return response([
            "success" => true,
            "data" => $room
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Entrada\Modules\Locations\Models\Entrada\Room $room
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Room $room)
    {
        $this->authorize('create', $room);

        $this->validate($request, $this->input_fields);
        $new = $room->create($request->all());

        if ($new) {
            // Successful create returns a 204
            return response(["success" => true, "data" => $new], 201);
        }

        return response(["success" => false], 404);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $this->authorize('update', $room);

        $this->validate($request, $this->input_fields);
        $update = $room->update($request->all());

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $room->findOrFail($id)], 200);
        }

        return response(["success" => false], 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  array  $ids
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);
        $rooms = [];

        foreach ($ids as $id) {
            $room = Room::findOrFail($id);
            $this->authorize('delete', $room);
            $rooms[] = $room;
        }

        foreach ($rooms as $room) {
            $delete = $room->delete();
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

    /**
     * Display the specified resource by parameter
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showByBuilding($id)
    {
        $this->authorize('view', new Room());

        if (!$rooms = Room::where("building_id", $id)->orderBy("room_number")->get()) {
            return response(array("success" => false));
        }

        return response(array("success" => true, "data" => $rooms));
    }
}
