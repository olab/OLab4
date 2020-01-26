<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models_Curriculum_Period;
use Models_Curriculum_Type;
use Models_Schedule;
use Models_Schedule_Slot;
use Models_Schedule_Draft;

class DraftRotationSchedule extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cbl_schedule_drafts';

    protected $primaryKey = "cbl_schedule_draft_id";

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['draft_title', 'status', 'course_id', 'cperiod_id'];

    /**
     * The user who created this leaveTracking
     */
    public function created_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'created_by');
    }

    /**
     * The user who updated this leaveTracking
     */
    public function updated_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'updated_by');
    }

    public function course() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\Courses', 'course_id')->select('course_id','course_name', 'course_code');
    }

    public function authors() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\DraftRotationScheduleAuthor', 'cbl_schedule_draft_id');
    }

    public function rotation_schedules() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\RotationSchedule', 'draft_id');
    }

    public static function boot()
    {
        parent::boot();

        /**
         * Set fields on creating event
         */
        static::creating(function ($model) {
            $user = Auth::user();
            $model->created_by = $user->id;
            $model->updated_by = $user->id;
        });

        /**
         * Set fields on updating leaveTracking
         */
        static::updating(function ($model) {
            $user = Auth::user();
            $model->updated_by = $user->id;
        });

        /**
         * Set fields on deleting event if not force deleting
         */
        static::deleting(function ($model) {
            $user = Auth::user();

            if (!$model->isForceDeleting()) {
                $model->updated_by = $user->id;
                $model->save();

                foreach ($model->rotation_schedules as $rotation_schedule) {
                    $rotation_schedule->delete();
                }
            }
        });
    }

    public static function fetchAllByOrg($org, $status = "draft", $cperiod_id = null, $search = "")
    {
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);

        $query =  DB::table('cbl_schedule_drafts as a')
            ->selectRaw('a.cbl_schedule_draft_id as id, a.draft_title, a.cperiod_id, b.course_name, b.course_code, b.course_id')
            ->join('courses as b', 'b.course_id', '=', 'a.course_id')
            ->where('a.status', $status)
            ->whereNull("a.deleted_date")
            ->whereRaw("a.draft_title LIKE ?", '%'.$search.'%')
            ->where('b.organisation_id', $org);

        if ($curriculum_period) {
            $query->where('a.cperiod_id', $curriculum_period->getID());
        }

        return $query->get();
    }

    public static function fetchAllByProxyID ($proxy_id, $status = "draft", $cperiod_id = null, $search = "")
    {
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);

        $query =  DB::table('cbl_schedule_drafts as a')
            ->selectRaw('a.cbl_schedule_draft_id as id, a.draft_title, a.cperiod_id, b.course_name, b.course_code, b.course_id')
            ->join('courses as b', 'b.course_id', '=', 'a.course_id')
            ->join('cbl_schedule_draft_authors as c', 'c.cbl_schedule_draft_id', '=', 'a.cbl_schedule_draft_id')
            ->where('a.status', $status)
            ->whereNull("a.deleted_date")
            ->whereRaw("a.draft_title LIKE ?", '%'.$search.'%')
            ->where("c.author_value", $proxy_id)
            ->where("c.author_type", 'proxy_id')
            ->groupBy("c.cbl_schedule_draft_id");

        if ($curriculum_period) {
            $query->where('a.cperiod_id', $curriculum_period->getID());
        }

        return $query->get();

    }

    public static function fetchAllByProxyIDCourseID ($proxy_id, $course_id, $status = "draft", $cperiod_id = null, $search = "")
    {
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);

        $query =  DB::table('cbl_schedule_drafts as a')
            ->selectRaw('a.cbl_schedule_draft_id as id, a.draft_title, a.cperiod_id, b.course_name, b.course_code, b.course_id')
            ->join('courses as b', 'b.course_id', '=', 'a.course_id')
            ->join('cbl_schedule_draft_authors as c', 'c.cbl_schedule_draft_id', '=', 'a.cbl_schedule_draft_id')
            ->where('a.status', $status)
            ->whereNull("a.deleted_date")
            ->whereRaw("a.draft_title LIKE ?", '%'.$search.'%')
            ->whereRaw("((c.`author_value` = ? AND c.`author_type` = 'proxy_id')
                                OR (c.`author_value` = ? AND c.`author_type` = 'course_id'))", [$proxy_id, $course_id])
            ->groupBy("c.cbl_schedule_draft_id");

        if ($curriculum_period) {
            $query->where('a.cperiod_id', $curriculum_period->getID());
        }

        return $query->get();

    }

    public static function copyExistingRotation($copy_draft_id, $draft_id) {
        global $ENTRADA_USER;
        $previous_draft = self::findOrFail($copy_draft_id);
        $current_draft = self::findOrFail($draft_id);

        if ($current_draft) {
            if ($previous_draft) {

                // Fetch all templates associated with the current draft's enrolment period.
                $curriculum_type = Models_Curriculum_Type::fetchRowByCPeriodID($current_draft->cperiod_id);
                if ($curriculum_type) {
                    $curriculum_period = Models_Curriculum_Period::fetchRowByID($current_draft->cperiod_id);
                    if ($curriculum_period) {
                        $schedules = Models_Schedule::fetchAllTemplatesByCPeriodID($curriculum_period->getCperiodID());
                        if ($schedules) {
                            $template_rotation_children = array();
                            foreach($schedules as $template_rotation) {
                                $template_rotation_children[$template_rotation->getID()] = $template_rotation->getChildren();
                            }
                            // For each rotation in the draft that is being copied from, we need to create a new rotation with the same title in the current draft.
                            $rotations = Models_Schedule::fetchAllByDraftID($previous_draft->cbl_schedule_draft_id, "rotation_stream");
                            if ($rotations) {
                                $unique_parents = array();
                                $child_block_list = array();
                                $slot_data_list = array();
                                foreach ($rotations as $rotation) {
                                    $slot_spaces = false;
                                    $slot_type_id = false;
                                    $slot_course_id = false;
                                    $block = Models_Schedule::fetchRowByParentID($rotation->getID());
                                    if ($block) {
                                        // Attempt to fetch an example of slots from the children of the rotation we are copying from.
                                        $slot = Models_Schedule_Slot::fetchRowByScheduleID($block->getID());
                                        if (isset($slot) && $slot) {
                                            $slot_spaces = $slot->getSlotSpaces();
                                            $slot_type_id = $slot->getSlotTypeID();
                                            $slot_course_id = $slot->getCourseID();
                                        }
                                    }
                                    $schedule_type_map = array(
                                        "stream" => "rotation_stream",
                                        "block" => "rotation_block"
                                    );
                                    // Create rotation stream
                                    $schedule_data = $schedules[0]->toArray();
                                    unset($schedule_data["schedule_id"]);
                                    unset($schedule_data["schedule_parent_id"]);
                                    unset($schedule_data["block_type_id"]);
                                    $schedule_data["schedule_parent_id"] = 0;
                                    $schedule_data["draft_id"] = $current_draft->cbl_schedule_draft_id;
                                    $schedule_data["course_id"] = $current_draft->course_id;
                                    $schedule_data["title"] = $rotation->getTitle();
                                    $schedule_data["code"] = $rotation->getCode();
                                    $schedule_data["schedule_type"] = $schedule_type_map[$schedule_data["schedule_type"]];
                                    // Write to copied from to maintain a history of rotations that are cloned.
                                    $schedule_data["copied_from"] = $rotation->getID();
                                    $schedule_data["created_date"] = time();
                                    $schedule_data["created_by"] = $ENTRADA_USER->getActiveId();
                                    $new_schedule = new Models_Schedule($schedule_data);
                                    $result = $new_schedule->insert();
                                    if ($result) {
                                        // Create child schedules for the new rotation stream based on each template.
                                        foreach ($schedules as $schedule) {
                                            $children = $template_rotation_children[$schedule->getID()];
                                            if ($children) {
                                                $new_parent_id = $result->getID();
                                                $i = 1;
                                                foreach ($children as $child_block) {
                                                    $child_block_data = $child_block->toArray();
                                                    unset($child_block_data["schedule_id"]);
                                                    unset($child_block_data["schedule_parent_id"]);
                                                    $child_block_data["cperiod_id"] = $schedule_data["cperiod_id"];
                                                    $child_block_data["draft_id"] = $current_draft->cbl_schedule_draft_id;
                                                    $child_block_data["course_id"] = $current_draft->course_id;
                                                    $child_block_data["created_date"] = time();
                                                    $child_block_data["created_by"] = $ENTRADA_USER->getActiveID();
                                                    $child_block_data["schedule_parent_id"] = $new_parent_id;
                                                    $child_block_data["schedule_type"] = $schedule_type_map[$child_block_data["schedule_type"]];
                                                    $child_block_data["schedule_order"] = $i++;
                                                    $new_child = new Models_Schedule($child_block_data);

                                                    $unique_parents[$new_parent_id] = array(
                                                        "schedule_parent_id" => $new_parent_id,
                                                        "slot_type_id" => ($slot_type_id ? $slot_type_id : 1),
                                                        "slot_spaces" => ($slot_spaces ? $slot_spaces : 2),
                                                        "course_id" => ($slot_course_id ? $slot_course_id : NULL)
                                                    );

                                                    $child_block_list[] = $new_child->createValueString();
                                                }
                                            }
                                        }
                                    } else {
                                        return response(["success" => false, "data" => "An error occurred when attempting attempting to add new rotation schedule."]);
                                    }
                                }

                                $max_sql_line_limit = 500;
                                for($i = 0; $i < ceil(count($child_block_list) / $max_sql_line_limit); $i++) {
                                    $sliced_array = array_slice($child_block_list, $i * $max_sql_line_limit, $max_sql_line_limit);
                                    Models_Schedule::addAllSchedules(implode($sliced_array, ","));
                                }

                                if ($unique_parents) {
                                    foreach ($unique_parents as $unique_parent) {
                                        $created_blocks = Models_Schedule::fetchAllByParentID($unique_parent["schedule_parent_id"]);
                                        if ($created_blocks) {
                                            foreach ($created_blocks as $created_block) {
                                                $slot_data = array(
                                                    "schedule_id" => $created_block->getID(),
                                                    "slot_type_id" => $unique_parent["slot_type_id"],
                                                    "slot_spaces" => $unique_parent["slot_spaces"],
                                                    "course_id" => $unique_parent["course_id"],
                                                    "created_date" => time(),
                                                    "created_by" => $ENTRADA_USER->getActiveID(),
                                                    "updated_date" => time(),
                                                    "updated_by" => $ENTRADA_USER->getActiveID()
                                                );
                                                $new_slot = new Models_Schedule_Slot($slot_data);
                                                $slot_data_list[] = $new_slot->createValueString();
                                            }
                                        }
                                    }
                                }

                                for ($i = 0; $i < ceil(count($slot_data_list) / $max_sql_line_limit); $i++) {
                                    $sliced_array = array_slice($slot_data_list, $i * $max_sql_line_limit, $max_sql_line_limit);
                                    Models_Schedule_Slot::addAllSlots(implode($sliced_array, ","));
                                }

                                return response(["success" => true, "data" => "Successfully copied rotation schedules from " . $previous_draft->draft_title . "."]);
                            } else {
                                return response(["success" => false, "data" => "No rotations found within the selected schedule."]);
                            }
                        } else {
                            return response(["success" => false, "data" => "No templates found to import for the current draft schedule's curriculum period."]);
                        }
                    } else {
                        return response(["success" => false, "data" => "No curriculum period found for draft schedule."]);
                    }
                } else {
                    return response(["success" => false, "data" => "No curriculum type found for draft schedule."]);
                }
            } else {
                return response(["success" => false, "data" => "Unable to fetch schedule that is being copied from."]);
            }
        } else {
            return response(["success" => false, "data" => "Unable to fetch schedule that is being edited."]);
        }
    }

    public static function export ($draft_id, $block_type_id, $include_off_service = false) {
        if (isset($draft_id) && isset($block_type_id)) {
            $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
            $csv_data = $draft->getScheduleTable();

            if (!empty($csv_data)) {
                $output[0][] = "Name";
                $i = 1;
                foreach ($csv_data["blocks"][$block_type_id] as $block) {
                    $output[0][] = "Block" . $i++ . ": " . date("Y-m-d", $block[0]["start_date"]) . " to " . date("Y-m-d", $block[0]["end_date"]);
                }

                if (!isset($include_off_service) || $include_off_service !== true) {
                    $members = array_merge($csv_data["on_service_audience"], $csv_data["unscheduled_on_service_audience"]);
                } else {
                    $members = array_merge($csv_data["on_service_audience"], $csv_data["unscheduled_on_service_audience"], $csv_data["off_service_audience"]);
                }

                $i = 1;
                foreach ($members as $audience_member) {
                    $output[$i][] = $audience_member["name"] . " (" . $audience_member["number"] . ")";
                    foreach ($csv_data["blocks"][$block_type_id] as $block_key => $block) {
                        $output[$i][] = (isset($audience_member["slots"][$block_type_id][$block_key][0]["code"]) && $audience_member["slots"][$block_type_id][$block_key][0]["code"] ? $audience_member["slots"][$block_type_id][$block_key][0]["code"] : " ");
                    }
                    $i++;
                }
            }

            if (!empty($output)) {
                ob_clear_open_buffers();

                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: text/csv");
                header("Content-Disposition: attachment; filename=\"csv-".date("Y-m-d").".csv\"");
                header("Content-Transfer-Encoding: binary");

                $fp = fopen("php://output", "w");

                foreach ($output as $row) {
                    fputcsv($fp, $row);
                }

                fclose($fp);

                exit;
            }
        }
    }
}
