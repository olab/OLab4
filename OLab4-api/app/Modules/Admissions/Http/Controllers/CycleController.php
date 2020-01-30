<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CycleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
        $this->authorize("view_list", new Cycle());

        $user = CycleRole::admissionsUserFromUser(Auth::user());

        return $user->cycles();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $this->authorize("create", new Cycle());

        $this->validate($request, [
            "name" => "string|required|max:64",
            "description" => "string",
            "organization_id" => "int"
        ], [
            "name.string" => __("Cycle name should be a string"),
            "name.required" => __("Cycle name is required"),
            "name.max" => __("Cycle name should be fewer than 65 characters"),
            "description.string" => __("Cycle description should be a string"),
            "organization_id.int" => __("Organization ID should be an integer")
        ]);

        $cycle = new Cycle();
        $cycle->fill($request->all());

        if (empty($cycle->organization_id)) {
            $cycle->organization_id = Auth::user()->organization_id;
        }

        if ($cycle->save()) {
            return response([
                "message" => __("New Cycle created"),
                "cycle" => $cycle->refresh()
            ]);
        } else {
            Log::debug("Cycle failed to create with error: ".$cycle->error);
            return response([
                __("Failed to create new Cycle"),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
        $cycle = Cycle::findOrFail($id);
        $this->authorize("view", $cycle);

        return $cycle;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $cycle = Cycle::findOrFail($id);
        $this->authorize("update", $cycle);

        $this->validate($request, [
            "name" => "string|max:64",
            "description" => "string",
            "organization_id" => "int"
        ], [
            "name.string" => __("Cycle name should be a string"),
            "name.max" => __("Cycle name should be fewer than 65 characters"),
            "description.string" => __("Cycle description should be a string"),
            "organization_id.int" => __("Organization ID should be an integer")
        ]);

        $cycle->fill($request->all());
        if ($cycle->save()) {
            return response([
                "message" => __("Cycle saved successfully"),
                "cycle" => $cycle->refresh()
            ], 200);
        } else {
            Log::debug("Cycle {$id} failed to save with error: ".$cycle->error);
            return response([__("Cycle :id failed to save", ["id" => $id])], 500);
        }
    }

    /**
     * Remove the specified Cycle from storage
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $cycle = Cycle::findOrFail($id);
        $this->authorize("delete", $cycle);

        if ($cycle->delete()) {
            return response([__("Cycle :id deleted successfully", ["id" => $id])], 200);
        } else {
            Log::debug("Cycle {$id} failed to delete with error: ".$cycle->error);
            return response([__("Cycle :id failed to delete", ["id" => $id])], 500);
        }
    }
}
