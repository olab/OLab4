<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Flag;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileFlagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $applicant_id = null, $file_id = null)
    {
        //
        return Flag::where(["entity_type" => "file"])
            ->get()
            ->makeVisible(["file_id"]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $flag = new Flag();
        $this->authorize("create", $flag);

        $this->validate($request, [
            "file_id" => "required|int",
            "reason" => "required|string"
        ], [
            "file_id.int" => __("File ID should be an integer"),
            "file_id.required" => __("File ID is required"),
            "reason.string" => __("Reason should be a string"),
            "reason.required" => __("A Reason is required")
        ]);


        $flag->fill($request->all());
        $flag->flagged_by = Auth::user()->id;

        if ($request->has("file_id")) {
            $flag->entity_type = "file";
            $flag->entity_id = $request->get("file_id");
        }

        if ($flag->save()) {
            return response([
                "message" => __("Flag created successfully"),
                "flag" => $flag->refresh()->makeVisible(["file_id"])
            ]);
        } else {
            Log::debug("Flag failed to create with error: ".$flag->error);
            return response([
                __("Flag failed to create")
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
        $flag = Flag::findOrFail($id);
        $this->authorize("view", $flag);

        $flag->makeVisible(["file"]);

        return $flag;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $file_id = null) {
        $flag = Flag::findOrFail($id);
        $this->authorize("update", $flag);

        $this->validate($request, [
            "file_id" => "int",
            "reason" => "string"
        ], [
            "file_id.int" => __("File ID should be an integer"),
            "reason.string" => __("Reason should be a string"),
        ]);

        $flag->fill($request->all());

        if ($request->has("file_id")) {
            $flag->entity_type = "file";
            $flag->entity_id = $request->get("file_id");
        }

        if ($flag->save()) {
            return response([
                "message" => __("Flag updated successfully"),
                "flag" => $flag->refresh()->makeVisible(["file_id"])
            ]);
        } else {
            Log::debug("Flag {$id} failed to update with error: ".$flag->error);
            return response([
                __("Flag :id failed to update", [
                    "id" => $id
                ])
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $flag = Flag::findOrFail($id);
        $this->authorize("delete", $flag);

        $flag->deleted_by = Auth::user()->id;

        if ($flag->save() && $flag->delete()) {

            return response([
                __("Flag $id deleted successfully", [
                    "id" => $id
                ])
            ]);
        } else {
            Log::debug("Flag {$id} failed to delete with error: ".$flag->error);
            return response([
                __("Flag :id failed to delete", [
                    "id" => $id
                ])
            ], 500);
        }
    }
}
