<?php

namespace Entrada\Modules\Clinical\Http\Controllers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationSchedule;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSite;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlot;
use Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlotType;
use Illuminate\Http\Request;

use Entrada\Http\Controllers\Controller;
use Models_Curriculum_Period;
use Entrada_Settings;

class RotationScheduleController extends Controller
{
    public function __construct()
    {
        $this->input_fields = [
            'title' => 'required|string',
            'code' => 'required|string',
            'blocks' => 'required',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $parameters = $request->all();
        $id = $parameters["draft_id"];
        $type = $parameters["type"];

        if (!empty($id)) {
            $draft = DraftRotationSchedule::findOrFail($id);
            $this->authorize('view', $draft);
        } else {
            $this->authorize('view', new DraftRotationSchedule());
        }

        $cbme_enabled = (bool) (new Entrada_Settings)->read("cbme_enabled");


        $off_service = RotationScheduleSlot::with("rotation_schedule.parent.course")
            ->whereHas("rotation_schedule", function($query) use($draft)
            {
                $query->where("cperiod_id", $draft->cperiod_id);
                $query->where("draft_id", "!=", $draft->cbl_schedule_draft_id);
            })
            ->where(function($query) use($draft) {
                $query->whereNull("course_id");
                $query->orWhere("course_id", $draft->course_id);
            })
            ->where("slot_type_id", RotationScheduleSlotType::where("slot_type_code", "OFFSL")->first()->slot_type_id )
            ->with("site")
            ->get();

        return response(["rotation_schedules" => RotationSchedule::fetchAllByDraftID($id, $type), "off_service_slots" => $off_service , "cbme_enabled" => $cbme_enabled], 200);
    }

    /**
     * Fetch rotation templates for a given curriculum period
     *
     * @param  int $cperiod_id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function templates($cperiod_id)
    {
        $this->authorize('view', new DraftRotationSchedule());

        return response(RotationSchedule::fetchAllTemplatesByCPeriodID($cperiod_id), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        global $ENTRADA_USER;

        $this->validate($request, $this->input_fields);
        $data = $request->all();

        $org = $ENTRADA_USER->getActiveOrganisation();

        $schedules = RotationSchedule::whereIn("schedule_id", $data["blocks"])
            ->with(["children" => function($query)
            {
                $query->orderBy("block_type_id", "desc");
                $query->orderBy("schedule_order");
            }])->get();

        if ($schedules) {

            $draft = DraftRotationSchedule::findOrFail($data["draft_id"]);
            $curriculum_period = Models_Curriculum_Period::fetchRowByID($draft->cperiod_id);

            $this->authorize('create', $draft);

            //create rotation stream
            $new_schedule = RotationSchedule::firstOrCreate(["title" => $data["title"],
                "code" => $data["code"],
                "description" => ($request->has("description") ? $data["description"] : ""),
                "schedule_type" => "rotation_stream",
                "course_id" => $draft->course_id,
                "schedule_parent_id" => 0,
                "organisation_id" => $org,
                "cperiod_id" => $draft->cperiod_id,
                "draft_id" => $draft->cbl_schedule_draft_id,
                "start_date" => $curriculum_period->getStartDate(),
                "end_date" => $curriculum_period->getFinishDate(),
            ]);


            $default_slot_spaces = 2;

            if ($new_schedule) {
                $new_parent_id = $new_schedule->schedule_id;

                //create sites relationship
                if (isset($data["selected_sites"]) && !empty($data["selected_sites"])) {
                    foreach ($data["selected_sites"] as $site) {
                        RotationScheduleSite::firstOrCreate(
                            ["schedule_id" => $new_parent_id,
                                "site_id" => $site["site_id"]]);
                    }
                }

                // Create child schedules for the new rotation stream based on each template.
                foreach ($schedules as $schedule) {
                    $i = 1;
                    foreach ($schedule->children as $child_block) {
                       $new_child = RotationSchedule::firstOrCreate(["title" => $child_block->title,
                           "code" => $child_block->code,
                           "description" => $child_block->description,
                           "schedule_type" => "rotation_block",
                           "schedule_parent_id" => $new_parent_id,
                           "organisation_id" => $org,
                           "course_id" => $draft->course_id,
                           "cperiod_id" => $draft->cperiod_id,
                           "start_date" => $child_block->start_date,
                           "end_date" => $child_block->end_date,
                           "block_type_id" =>$child_block->block_type_id,
                           "draft_id" => $draft->cbl_schedule_draft_id,
                           "schedule_order" => $i++
                       ]);

                       if ($new_child) {
                           RotationScheduleSlot::create([ "schedule_id" => $new_child->schedule_id,
                               "slot_type_id" => "1",
                               "slot_spaces" => $default_slot_spaces,
                           ]);
                       }
                   }
                }
            }
        }

        return response([], 201);
    }
    /**
     * Display the specified resource.
     *
     * @param  int $rotation_id
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($rotation_id, Request $request)
    {
        $rotation_schedule = RotationSchedule::with("course", "slots.slot_type")
            ->with("sites.site")
            ->with("slots.site")
            ->with("children.block_type")
            ->with("children.slots")
            ->with(["children" => function($query)
            {
                $query->orderBy("block_type_id", "desc");
                $query->orderBy("schedule_order");
            }])
            ->with("parent.sites.site")
            ->findOrFail($rotation_id);

        $draft = DraftRotationSchedule::findOrFail($rotation_schedule->draft_id);
        $this->authorize('view', $draft);

        return response($rotation_schedule);
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
        $rotation_schedule = RotationSchedule::findOrFail($id);

        if ($rotation_schedule->schedule_type === "rotation_stream") {
            $this->validate($request, ['title' => 'required|string', 'code' => 'required|string']);
        } else {
            $this->validate($request, ['title' => 'required|string', 'start_date' => 'required', 'end_date' => 'required|timestamp_greater_than:start_date'],
                ['timestamp_greater_than' => "The end date must be after the start date"]);
        }

        $data = $request->all();
        $draft = DraftRotationSchedule::findOrFail($rotation_schedule->draft_id);

        $this->authorize('update', $draft);

        // Validate request
        //$this->validate($request, $this->input_fields);

        // Save new data to rotationSchedule model
        $update = $rotation_schedule->update((
            $rotation_schedule->schedule_type === "rotation_stream" ? $request->only('title', 'code', 'description') :
                $request->only('title', 'code', 'description', 'start_date', 'end_date')));

        $new_sites_id = [];
        if (isset($data["selected_sites"]) && !empty($data["selected_sites"])) {
            foreach ($data["selected_sites"] as $site) {
                $new_sites_id[] = $site["site_id"];
                RotationScheduleSite::firstOrCreate(
                    ["schedule_id" => $rotation_schedule->schedule_id,
                        "site_id" => $site["site_id"]]);
            }
        }
        RotationScheduleSite::where("schedule_id" , $rotation_schedule->schedule_id)
            ->whereNotIn( "site_id", $new_sites_id)->delete();

        return response($rotation_schedule->findOrFail($id), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($ids)
    {
        $success = true;
        $ids = explode(",", $ids);

        $rotation_schedules = [];
        foreach ($ids as $id) {
            $rotation_schedule = RotationSchedule::findOrFail($id);
            $draft = DraftRotationSchedule::findOrFail($rotation_schedule->draft_id);
            $this->authorize('delete', $draft);
            $rotation_schedules[] = $rotation_schedule;
        }

        foreach ($rotation_schedules as $rotation_schedule) {
            $delete = $rotation_schedule->delete();
            if (!$delete) {
                $success = false;
            }
        }

        if ($success) {
            // Successful delete returns a 204
            return response([], 200);
        }

        return response([], 404);
    }

    /**
     * Change dates of blocks within a rotation.
     *
     * @param  int $schedule_id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function shiftBlocks($schedule_id, Request $request)
    {
        $data = $request->all();

        $rotation_schedule = RotationSchedule::with("children")->findOrFail($schedule_id);
        $draft = DraftRotationSchedule::findOrFail($rotation_schedule->draft_id);

        $this->authorize('update', $draft);

        $this->validate($request, [
            'days' => 'required|integer|min:1',
            'shift_direction' => 'required',
        ]);

        foreach ($rotation_schedule->children as $child_block) {
            $start_date = ($data["shift_direction"] === "future" ? $child_block->start_date->addDays($data["days"]) : $child_block->start_date->subDays($data["days"]));
            $end_date = ($data["shift_direction"] === "future" ? $child_block->end_date->addDays($data["days"]) : $child_block->end_date->subDays($data["days"]));

            $child_block->update(["start_date" => $start_date, "end_date" => $end_date]);
        }

        return response([], 200);
    }

    public function mappingUrl ($schedule_id) {

        $mapping_url = ENTRADA_URL . "/admin/clinicalobjectives?schedule_id=" . $schedule_id;

        return response(["src" => $mapping_url], 200);
    }

    public function ImportRotationStructure(Request $request)
    {
        $this->validate($request, [
            "draft_id" => "required",
            "file" => "required|max:10000|mimes:csv,txt",
        ]);

        ini_set('auto_detect_line_endings', true);
        $fp = false;

        if ($request->hasFile("file")) {
            $fp = fopen($request->file("file"), "r");
        }

        $draft = DraftRotationSchedule::findOrFail($request->get("draft_id"));

        if ($fp) {
            $row_count = 0;

            while (($row = fgetcsv($fp, 1000, ",")) !== FALSE) {
                if (!$row_count++) {  // Skip header
                    continue;
                }

                if ($row[0] !== "" && $row[1] !== "") {
                    $rotationRequest = new Request();
                    $rotationRequest->setMethod('POST');
                    $rotationRequest->request->add(['code' => $row[0]]);
                    $rotationRequest->request->add(['title' => $row[1]]);
                    $rotationRequest->request->add(['draft_id' => $draft->cbl_schedule_draft_id]);
                    $rotationRequest->request->add(['blocks' => json_decode( $request->get("blocks"))]);
                    $this->store($rotationRequest);
                }
            }
        }

        return response([], 201);
    }
}
