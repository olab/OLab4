<?php

namespace Entrada\Modules\Locations\Http\Controllers;


use Auth;
use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\Room;
use Entrada\Modules\Locations\Models\Entrada\Building;
use Illuminate\Http\Request;

class BuildingsController extends Controller
{
    protected $input_fields = [];

    public function __construct()
    {
        $this->input_fields = [
            "site_id" => "required|integer",
            "building_code" => "required|string",
            "building_name" => "required|string",
            "building_address1" => "required|string",
            "building_address2" => "nullable|string",
            "building_city" => "required|string",
            "building_province_id" => "nullable|integer",
            "building_country_id" => "required|integer",
            "building_postcode" => "required|string"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\Building $building
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Building $building)
    {

        $this->authorize('view', $building);

        return response([
            'buildings' => $building->paginate()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($id)
    {
        $building = Building::findOrFail($id);

        $this->authorize('view', $building);
        return response($building);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Entrada\Modules\Locations\Models\Entrada\Building $building
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Building $building)
    {
        $this->authorize('create', $building);

        $this->validate($request, $this->input_fields);
        $new = $building->create($request->all());

        if ($new) {
            // Successful create returns a 204
            return response(["success" => true, "data" => $new], 201);
        }

        return response(["success" => false], 404);
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
        $building = Building::findOrFail($id);

        $this->authorize('update', $building);

        $this->validate($request, $this->input_fields);

        $update = $building->update($request->all());

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $building->findOrFail($id)], 200);
        }

        return response(["success" => false], 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  array $ids
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);

        $buildings = [];

        // make sure we are allowed to delete all the buildings provided
        foreach ($ids as $id) {
            $building = Building::findOrFail($id);
            $this->authorize('delete', $building);
            $buildings[] = $building;
        }

        foreach ($buildings as $building) {
            $id = $building->building_id;

            $rooms = Room::where("building_id", "=", $id)->get();

            foreach ($rooms as $room) {
                $delete = $room->delete();
                if (!$delete) {
                    $success = false;
                }
            }

            if($success) {
                $delete = $building->delete();
                if (!$delete) {
                    $success = false;
                }
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
    public function showBySite($id)
    {
        $this->authorize('view', new Building());

        if (!$buildings = Building::where("site_id", $id)->orderBy("building_code")->get()) {
            return response(array("success" => false));
        }

        return response(array("success" => true, "data" => $buildings));
    }
}
