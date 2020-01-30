<?php

namespace Entrada\Modules\Locations\Http\Controllers;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\Province;
use Illuminate\Http\Request;

class ProvincesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\Province $province
     * @return \Illuminate\Http\Response
     */
    public function index(Province $province)
    {
        if (!$provinces = $province->orderBy("province")->get()) {
            return response(array("success" => false));
        }

        return response(array("success" => true, "data" => $provinces));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response(Province::findOrFail($id));
    }

    /**
     * Display the specified resource by parameter
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function showByCountry($id)
    {
        if (!$provinces = Province::where("country_id", $id)->orderBy("province")->get()) {
            return response(array("success" => false));
        }

        return response(array("success" => true, "data" => $provinces));
    }
}
