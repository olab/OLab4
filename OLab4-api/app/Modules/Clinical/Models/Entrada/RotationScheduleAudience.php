<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RotationScheduleAudience extends Model
{
    use SoftDeletes;

    const DELETED_AT = 'deleted_date';

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cbl_schedule_audience';

    protected $primaryKey = "saudience_id";

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['custom_start_date', 'custom_end_date', 'deleted_date'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['schedule_id', 'schedule_slot_id', 'audience_type', 'audience_value', 'custom_start_date', 'custom_end_date',];

    public function rotation_schedule() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationSchedule', 'schedule_id');
    }

    public function user() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\User', 'audience_value');
    }

    public function slot() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlot', 'schedule_slot_id');
    }
    /**
     * @param array $learners
     * @param int $course_id
     * @param int $cperiod_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchAllByLearnerIdCourseIdCPeriodId($learners, $draft_id, $course_id, $cperiod_id)
    {
        if (!is_array($learners)) {
            $learners = [ $learners ];
        }

        $query = self::whereHas('rotation_schedule', function($query) use($draft_id, $course_id, $cperiod_id)
        {
            $query->where("draft_id", $draft_id);
            $query->where("cperiod_id", $cperiod_id);
            $query->where("course_id", $course_id);
        })
        ->where(function($query) use($learners) {
            $query->where("audience_type", "proxy_id");
            $query->whereIn("audience_value", $learners);
        })->orderBy('audience_value')->with('user');

        return $query->get();
    }

    /**
     * @param array $proxy_id
     * @param int $draft_id
     * @param int $course_id
     * @param int $cperiod_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchByLearnerIdCourseIdCPeriodId($proxy_id, $draft_id, $course_id, $cperiod_id)
    {
        $query = self::where("audience_type", "proxy_id")
            ->where("audience_value", $proxy_id)
            ->whereHas('rotation_schedule', function($query) use($draft_id, $course_id, $cperiod_id)
            {
                $query->where("draft_id", $draft_id);
                $query->where("cperiod_id", $cperiod_id);
                $query->where("course_id", $course_id);
            })
            ->with("slot.slot_type")
            ->with("rotation_schedule.parent");

        return $query->get();
    }

    /**
     * @param array $proxy_id
     * @param int $draft_id
     * @param int $course_id
     * @param int $cperiod_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchScheduleForLearnerIdDraftIdCourseIdCPeriodId($proxy_id, $draft_id, $course_id, $cperiod_id)
    {
        $query = self::where("audience_type", "proxy_id")
            ->where("audience_value", $proxy_id)
            ->whereHas("rotation_schedule", function($query) use($draft_id, $course_id, $cperiod_id)
            {
                $query->whereHas("draft", function($query) use($draft_id, $course_id) {
                    $query->where("cbl_schedule_draft_id", $draft_id);
                    $query->orWhere(function($query) use($draft_id, $course_id) {
                        $query->where("cbl_schedule_draft_id", "!=", $draft_id);
                        $query->where("course_id", "!=", $course_id);
                        $query->where("status", "live");
                    });
                });
                $query->where("cperiod_id", $cperiod_id);
            })
            ->with("slot.slot_type")
            ->with("rotation_schedule.parent.course");

        return $query->get();
    }

    /**
     * @param array $group_id
     * @param int $course_id
     * @param int $cperiod_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchByGroupIdCourseIdCPeriodId($group_id, $draft_id, $course_id, $cperiod_id)
    {
        $query = self::whereHas("rotation_schedule", function($query) use($draft_id, $course_id, $cperiod_id)
        {
            $query->where("draft_id", $draft_id);
            $query->where("cperiod_id", $cperiod_id);
            $query->where("course_id", $course_id);
        })
            ->where("audience_type", "cgroup_id")
            ->where("audience_value", $group_id)
            ->with("rotation_schedule.parent");

        return $query->get();
    }
}
