<?php

namespace Entrada\Modules\Locations\Http\Controllers;

use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\SiteOrganisation;

class SitesOrganisationsController extends Controller
{
    public function __construct()
    {
        $this->input_fields = [
            "site_id" => "required|integer",
            "organisation_id" => "required|integer"
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\SiteOrganisation $site_organisation
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response([
            'sites_organisations' => SiteOrganisation::get()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\SiteOrganisation $site_organisation
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response(SiteOrganisation::findOrFail($id));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Entrada\Modules\Locations\Models\Entrada\SiteOrganisation $site_organisation
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SiteOrganisation $site_organisation)
    {
        $this->validate($request, $this->input_fields);
        $new = $site_organisation->create($request->all());

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
     */
    public function update(Request $request, $id)
    {
        $site_organisation = SiteOrganisation::findOrFail($id);

        // Save new data to sandbox model
        $update = $site_organisation->update($request->all());

        if ($update) {
            // Successful update returns a 204
            return response(["success" => true, "data" => $site_organisation->findOrFail($id)], 200);
        }

        return response(["success" => false], 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $site_organisation = SiteOrganisation::findOrFail($id);

        $delete = $site_organisation->delete();

        if ($delete) {
            // Successful delete returns a 204
            return response("", 204);
        }

        return response("", 404);
    }
}
