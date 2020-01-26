<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Modules\Admissions\Models\Entrada\ReaderType;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReaderTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $this->authorize("view_list", new ReaderType());

        $user = CycleRole::admissionsUserFromUser(Auth::user());

        return $user->readerTypes();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $this->authorize("create", new ReaderType());

        $data = $request->all();
        if (!empty($data["reader_type_id"])) {
            return response([__("Please use POST to update a ReaderType or remove reader_type_id to create a new one")], 400);
        }

        $readerType = ReaderType::firstOrNew([
            "cycle_id" => empty($data["cycle_id"]) ? Cycle::currentCycleID() : $data["cycle_id"],
            "shortname" => empty($data["shortname"]) ? md5(date("Y-m-d G:i:s")) : $data["shortname"]
        ]);

        if (!empty($readerType->reader_type_id)) {
            return response([__("You cannot create another ReaderType with the shortname ':shortname'. Update it in POST, or choose a different name.", [
                "shortname" => $readerType->shortname
            ])], 400);
        }

        if (empty($data["shortname"]) && empty($data["name"])) {
            $data["shortname"] = "---";
        } else {
            $data["shortname"] = empty($data["shortname"]) ? ReaderType::newSlug($data["name"]) : $data["shortname"];
        }

        $readerType->fill($data);

        if ($readerType->save()) {
            return response([
                "message" => __("ReaderType created successfully"),
                "reader_type" => $readerType
            ], 200);
        } else {
            return response([
                __("ReaderType creation failed")
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return ReaderType
     */
    public function show($id) {
        $readerType = ReaderType::findOrFail($id);
        $this->authorize("view", $readerType);

        return $readerType;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {

        $readerType = ReaderType::findOrFail($id);
        $this->authorize("update", $readerType);
        $data = $request->all();

        if (isset($data["shortname"])) {
            $checkReader = ReaderType::where([
                "cycle_id" => empty($data["cycle_id"]) ? Cycle::currentCycleID() : $data["cycle_id"],
                "shortname" => $data["shortname"]
            ])->first();

            if (!empty($checkReader) && $checkReader->reader_type_id !== $readerType->reader_type_id) {
                return response([__("Another ReaderType with the shortname ':shortname' already exists. Please choose a different shortname.", [
                    "shortname" => $readerType->shortname
                ])], 400);
            }
        }

        $readerType->fill($data);

        if ($readerType->save()) {
            return response([
                __("ReaderType :id saved successfully",  [
                    "id" => $id
                ])
            ], 200);
        } else {
            Log::debug("ReaderType {$id} failed to save with error: ".$readerType->errors);
            return response([
                __("ReaderType :id failed to save", [
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
        //
        $readerType = ReaderType::findOrFail($id);

        if ($readerType->delete()) {
            return response([__("ReaderType :id deleted successfully", ["id" => $id])], 200);
        } else {
            Log::debug("ReaderType {$id} failed to deleted with error: " . $readerType->error);
            return response([__("ReaderType :id failed to deleted", ["id" => $id])], 200);
        }
    }




}
