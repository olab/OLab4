<?php

namespace Entrada\Modules\Locations\Http\Controllers;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Locations\Models\Entrada\Country;
use Illuminate\Http\Request;

class CountriesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param  \Entrada\Modules\Locations\Models\Entrada\Country $country
     * @return \Illuminate\Http\Response
     */
    public function index(Country $country)
    {
        if (!$countries = $country->orderBy("country")->get()) {
            return response(array("success" => false));
        }

        return response(array("success" => true, "data" => $countries));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response(Country::findOrFail($id));
    }
}
