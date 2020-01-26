<?php

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Entrada\Modules\Admissions\Models\Entrada\QUEXROOData;
use Entrada\Modules\Admissions\Models\Entrada\Reader;
use Entrada\Modules\Admissions\Models\Entrada\ReaderType;
use Entrada\Modules\Admissions\Models\Entrada\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Entrada\Http\Controllers\Controller;

class ReaderController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index(Request $request) {
        $this->authorize("view", new Reader());


        $user = CycleRole::admissionsUserFromUser(Auth::user());

        //
        return $user->admissionsReaders();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $this->authorize("create", new Reader());

        // Readers can technically be created empty, so none of these fields are required
        $this->validate($request, [
            "proxy_id" => "integer",
            "name" => "string",
            "email" => "email",
            "reader_type_id" => "integer",
            "cycle_id" => "integer",
            "pool_id" => "integer",
            "group_id" => "integer"
        ], [
            "proxy_id.integer" => __("User ID should be an integer"),
            "name.string" => __("Reader Name should be a string"),
            "email.email" => __("Reader Email should be a valid email address"),
            "reader_type_id.integer" => __("Reader Type ID should be an integer"),
            "cycle_id.integer" => __("Cycle ID Should be an integer"),
            "pool_id.integer" => __("Pool ID should be an integer"),
            "group_id.integer" => __("Group ID should be an integer")
        ]);

        $data = $request->all();

        if (!empty($data["reader_id"])) {
            return response([__("Please use POST to update a Reader, or remove reader_id to create a new one.")], 400);
        }

        $reader = new Reader();
        $reader->fill($data);
        if (empty($reader->cycle_id)) {
            $reader->cycle_id = Cycle::currentCycleID();
        }

        // If reader_type_id is set directly, update that way. Otherwise, check if reader_type was specified by shortname
        if (empty($data["reader_type_id"])
            && !empty($data["reader_type"])
            && $readerType = ReaderType::where(["shortname" => $data["reader_type"]])->first()) {

            $reader->reader_type_id = $readerType->reader_type_id;
        }

        if ($reader->save()) {
            return response([
                "message" => __("Reader created successfully"),
                "reader" => $reader->refresh()
            ], 200);
        } else {
            Log::debug("Reader failed to save with error: ".json_encode($reader->errors));
            return response([
                __("Reader failed to save")
            ], 500);
        }
    }

    /**
     * Endpoint to create a new Reader by providing a ReaderType ID, Name or Shortname
     * Include optional payload array to set Reader values, or do that later.
     *
     * @param Request $request
     * @param $type int|string an identifier for the type to use
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function createWithType(Request $request, $type) {

        // Fetch the reader. Check ID first, then shortname as they are more unique identifiers. Finally check name
        $readerType = ReaderType::where(["reader_type_id" => $type])
            ->orWhere(["shortname" => $type])
            ->orWhere(["name" => $type])
            ->first();

        // We should get at least one
        if (empty($readerType)) {
            return response("ReaderType ($type) not found", 404);
        }

        $this->validate($request, [
            "proxy_id" => "integer",
            "name" => "string",
            "email" => "email",
            "cycle_id" => "integer",
            "pool_id" => "integer",
            "group_id" => "integer"
        ], [
            "proxy_id.integer" => __("User ID should be an integer"),
            "name.string" => __("Reader Name should be a string"),
            "email.email" => __("Reader Email should be a valid email address"),
            "cycle_id.integer" => __("Cycle ID Should be an integer"),
            "pool_id.integer" => __("Pool ID should be an integer"),
            "group_id.integer" => __("Group ID should be an integer")
        ]);

        $data = $request->all();

        $reader = new Reader();
        $reader->fill($data);
        if (empty($reader->cycle_id)) {
            $reader->cycle_id = Cycle::currentCycleID();
        }

        $reader->reader_type_id = $readerType->reader_type_id;

        if ($reader->save()) {
            return response([
                "message" => __("Reader created successfully with type: $type"),
                "reader" => $reader
            ], 200);
        } else {
            Log::debug("Reader failed to create with error: ".$reader->error);
            return response([
               __("Failed to create Reader")
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Reader
     */
    public function show(Request $request, Reader $reader) {
        $this->authorize("view", $reader);

        $with = [];
        if ($request->get("type")) {
            $with[] = "type";
        }

        return $reader->load($with);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {

        $reader = Reader::findOrFail($id);
        $this->authorize("update", $reader);

        $this->validate($request, [
            "proxy_id" => "integer",
            "name" => "string",
            "email" => "email",
            "reader_type_id" => "integer",
            "cycle_id" => "integer",
            "pool_id" => "integer",
            "group_id" => "integer"
        ], [
            "proxy_id.integer" => __("User ID should be an integer"),
            "name.string" => __("Reader Name should be a string"),
            "email.email" => __("Reader Email should be a valid email address"),
            "reader_type_id.integer" => __("Reader Type ID should be an integer"),
            "cycle_id.integer" => __("Cycle ID Should be an integer"),
            "pool_id.integer" => __("Pool ID should be an integer"),
            "group_id.integer" => __("Group ID should be an integer")
        ]);


        $data = $request->all();
        $reader->fill($data);

        // If reader_type_id is set directly, update that way. Otherwise, check if reader_type was specified by shortname
        if (empty($data["reader_type_id"])
            && !empty($data["reader_type"])
            && $readerType = ReaderType::where(["shortname" => $data["reader_type"]])->first()) {

            $reader->reader_type_id = $readerType->reader_type_id;
        }

        if ($reader->save()) {
            return response([
                "message" => __("Reader saved successfully"),
                "reader" => $reader->refresh()
            ], 200);
        } else {
            Log::debug("Reader {$id} failed to save with error: ".$reader->errors);
            return response([
                __("Reader :id failed to save", [
                    "id" => $id
                ])
            ], 500);
        }
    }

    /**
     * Receives a CSV files named 'readers' and attempts to update or create Readers from the array
     *
     * The format of the CSV is as follows:
     * [
     *  "reader_id"
     *  "proxy_id"
     *  "name"
     *  "email"
     *  "reader_type"
     *  "cycle_id"
     *  "group_id"
     * ]
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function createWithCSV(Request $request) {
        $this->authorize("mass_update_csv", new Reader());

        $readerTypes = ReaderType::all()->keyBy("shortname");


        $csv = $request->file("readers");

        $mimes = [
            "text/csv",
            "text/plain",
            "application/csv",
            "text/comma-separated-values",
            "application/excel",
            "application/vnd.ms-excel",
            "application/vnd.msexcel"
        ];

        if (empty($csv) || !in_array($csv->getMimeType(), $mimes)) {
            return response("Please provide a 'readers' CSV file", 400);
        }

        $invalid = [];
        $row = 0;
        $created = 0;
        if (($handle = fopen($csv->path(), "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                if (empty($readerTypes[strtolower($data[4])])) {
                    $invalid[] = "Line {$row} failed. '{$data[4]}' did not match an existing Reader Type. Please create one, or confirm the name.";
                    continue;
                } else {
                    $data[4] = $readerTypes[strtolower($data[4])]->reader_type_id;
                }

                $success = Reader::createFromCSVLine($data, function($line_array) {
                    return [
                        "reader_id" => empty($line_array[0]) ? null : $line_array[0],
                        "proxy_id" => empty($line_array[1]) ? null : $line_array[1],
                        "name" => $line_array[2],
                        "email" => $line_array[3],
                        "reader_type" => $line_array[4],
                        "cycle_id" => empty($line_array[5]) ? Cycle::currentCycleID() : $line_array[5],
                        "group_id" => empty($line_array[6]) ? Cycle::currentCycleID() : $line_array[6],
                    ];
                });

                if ($success) {
                    $created ++;
                }
                $row ++;
            }
            fclose($handle);
        }

        return response([
            "message" => __("CSV uploaded successfully. :rows lines processed. :created readers created.", [
                "rows" => $row,
                "created" => $created
            ]),
            "errors" => $invalid
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $reader = Reader::findOrFail($id);
        $this->authorize("delete", $reader);

        if ($reader->delete()) {
            return response([__("Reader :id deleted", ["id" => $id])], 200);
        } else {
            Log::debug("Reader {$id} failed to delete with error:" . $reader->errors);
            return response([__("Reader :id failed to delete", ["id" => $id])], 500);
        }
    }
}
