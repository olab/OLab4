<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\ReaderGroup;
use Illuminate\Http\Request;

use Entrada\Http\Requests;
use Entrada\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReaderGroupController extends Controller
{
    /**
     * Display a listing of ReaderGroups, ordered and indexed by their group_type
     *
     * @return array
     */
    public function index() {

        $this->authorize("view", new ReaderGroup());

        $groups = ReaderGroup::with("readers")->get();

        $ret = [];
        foreach ($groups as $group) {
            $group["readers"] = $group->readers;
            $ret[] = $group;
        }

        return $ret;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function store(Request $request) {

        $this->authorize("create", new ReaderGroup());

        // Pick the one of group or groups that is not empty
        $data = array_filter($request->only(["group", "groups"]));

        // Calling this method with an empty payload will generate an empty ReaderGroup
        if (empty($data)) {
            $model = new ReaderGroup();
            $model->save();

            return response([
                "message" => __("New Group created successfully"),
                "group" => $model
            ], 200);
        }

        foreach ($data as $key => $array) {
            $array = is_array($array) ? $array : json_decode($array, true);
            switch($key) {
                case "group" :
                    $return = [ReaderGroup::saveWithArray($array)];
                    break;
                case "groups" :
                    $return = [];
                    foreach ($array as $single) {
                        $return[] = ReaderGroup::saveWithArray($single);
                    }
                    break;
                default:
                    return response([__("Please provide group or groups data to save")], 400);
            }
        }

        if(empty($return)) {
            Log::debug("No Reader Groups were created");
            return response([__("No Reader Groups were created")], 500);
        } else {
            return response([
                "message" => __(":count Reader Groups were created!", [
                    "count" => count($return)
                ]),
                "groups" => $return
            ], 200);
        }
    }

    /**
     * Display the specified ReaderGroup.
     *
     * @param  int  $id the integer ID of the ReaderGroup request
     * @return ReaderGroup|Response
     */
    public function show($id) {

        $model = ReaderGroup::findOrFail($id);

        $this->authorize("view", $model);

        return $model;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $model = ReaderGroup::findOrFail($id);

        $this->authorize("update", $model);

        $data = $request->all();
        $data["group_id"] = $id;

        $success = ReaderGroup::saveWithArray($data);

        if ($success) {
            return response([
                "message" => __("Group updated successfully"),
                "group" => $model
                ], 200);
        } else {
            Log::debug("Group {$id} failed to update with error: ".$model->errors);
            return response([__("Group :id failed to update", ["id" => $id])], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $model = ReaderGroup::findOrFail($id);

        $this->authorize("delete", $model);

        if ($model->delete()) {
            return response([__("Reader Group :id deleted", ["id" => $id])], 200);
        } else {
            Log::debug("Reader Group {$id} failed to delete with error:" . $model->errors);
            return response([__("Reader Group :id failed to delete", ["id" => $id])], 500);
        }
    }

    /**
     * Fetch ReaderGroups and Applicants, and assign them as equally as possible
     */
    public function assignApplicants() {

        $this->authorize("update", new ReaderGroup());

        $response = ReaderGroup::assignApplicants();

        if ($response) {
            return response([__("Success! :applicants applicants assigned to :groups groups.", [
                "applicants" => $response["applicants"],
                "groups" => $response["groups"]
            ])], 200);
        } else {
            Log::debug("There was an error assigning applicants to groups");
            return response([__("There was an error assigning applicants to groups")], 500);
        }
    }
}
