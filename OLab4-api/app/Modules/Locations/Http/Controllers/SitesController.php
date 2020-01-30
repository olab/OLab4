<?php

namespace Entrada\Modules\Locations\Http\Controllers;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\Room;
use Entrada\Modules\Locations\Models\Entrada\Building;
use Entrada\Modules\Locations\Models\Entrada\Site;
use Entrada\Modules\Locations\Models\Entrada\SiteOrganisation;
use Illuminate\Http\Request;

class SitesController extends Controller
{
    protected $input_fields = [];

    public function __construct()
    {
        $this->input_fields = [
            "site_code" => "required|string",
            "site_name" => "required|string",
            "site_address1" => "required|string",
            "site_address2" => "nullable|string",
            "site_city" => "required|string",
            "site_province_id" => "nullable|integer",
            "site_country_id" => "required|integer",
            "site_postcode" => "required|string"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\Site $site
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Site $site)
    {
        global $ENTRADA_USER;

       // $this->authorize('view', $site);

        $org = $ENTRADA_USER->getActiveOrganisation();

        return response([
            'sites' => Site::whereHas('organisations', function ($query) use ($org) {
                    $query->where("organisation_id", $org);
                })
                ->get()
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

        $site = Site::findOrFail($id);

        $this->authorize('view', $site);

        return response($site);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Entrada\Modules\Locations\Models\Entrada\Site $site
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request, Site $site)
    {
        $this->authorize('create', $site);

        $this->validate($request, $this->input_fields);
        $new = $site->create($request->all());

        if ($new) {
            if (SiteOrganisation::create(["site_id" => $new->site_id, "organisation_id" => $request->organisation_id])) {
                // Successful create returns a 204
                return response(["success" => true, "data" => $new], 201);
            } else {
                return response(["success" => false], 404);
            }
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
        $site = Site::findOrFail($id);

        $this->authorize('update', $site);

        $this->validate($request, $this->input_fields);

        $update = $site->update($request->all());

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $site->findOrFail($id)], 200);
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
        $sites = [];

        // make sure we are allowed to delete all the sites provided
        foreach ($ids as $id) {
            $site = Site::findOrFail($id);
            $this->authorize('delete', $site);
            $sites[] = $site;
        }

        foreach ($sites as $site) {
            $id = $site->site_id;

            $buildings = Building::where("site_id", "=", $id)->get();

            foreach ($buildings as $building) {

                $rooms = Room::where("building_id", "=", $building->building_id)->get();

                foreach ($rooms as $room) {
                    $delete = $room->delete();
                    if (!$delete) {
                        $success = false;
                    }
                }

                if ($success) {
                    $delete = $building->delete();

                    if (!$delete) {
                        $success = false;
                    }
                }
            }

            $site_organisation = SiteOrganisation::findOrFail($id);
            $delete = $site_organisation->delete();

            if (!$delete) {
                $success = false;
            }

            if ($success) {
                $delete = $site->delete();

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
    public function showByOrganisation($id)
    {
        $this->authorize('view', new Site());

        $sites = Site::whereHas('organisations', function ($query) use ($id) {
            $query->where("organisation_id", $id);
        })->get();

        return response(array("success" => true, "sites" => $sites));
    }
}
