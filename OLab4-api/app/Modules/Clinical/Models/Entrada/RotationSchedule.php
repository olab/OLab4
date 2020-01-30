<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models_Curriculum_Period;

class RotationSchedule extends Model
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
    protected $table = 'cbl_schedule';

    protected $primaryKey = "schedule_id";

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
    protected $dates = ['start_date', 'end_date', 'created_date', 'updated_date', 'deleted_date'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'code', 'description', 'schedule_type', 'schedule_parent_id', 'organisation_id', 'course_id', 'region_id', 'facility_id', 'cperiod_id', 'start_date', 'end_date', 'block_type_id', 'draft_id', 'schedule_order', 'copied_from'];

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

    public function children() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\RotationSchedule', 'schedule_parent_id');
    }

    public function slots() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlot', 'schedule_id');
    }

    public function block_type() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleBlockType', 'block_type_id');
    }

    public function sites() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSite', 'schedule_id')->select('schedule_id','site_id');
    }

    public function parent() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationSchedule', 'schedule_parent_id');
    }

    public function draft() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule', 'draft_id');
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

                $model->sites()->delete();

                foreach ($model->children as $children) {
                    $children->delete();
                }

                foreach ($model->slots as $slot) {
                    $slot->delete();
                }
            }
        });
    }

    public static function fetchAllByDraftID($draft_id, $type = "", $search = "")
    {
        $query =  self::where('draft_id', $draft_id);

        if ($type !== "") {
            $query->where('schedule_type', $type);
        }

        return $query->with("course")->get();
    }

    public static function fetchAllTemplatesByCPeriodID($cperiod)
    {
        $query = self::where('cperiod_id', $cperiod)
            ->where('schedule_type', "stream")
            ->where('schedule_parent_id', "0")
            ->with("children")
            ->with("block_type");

        return $query->get();
    }

    public static function fetchLargestTemplateByCPeriodID($cperiod)
    {
        /**
         * find the largest block in the curriculum period's templates
         */
        $stream = self::where('cperiod_id', $cperiod)
            ->where('schedule_type', "stream")
            ->where('schedule_parent_id', "0")
            ->join('cbl_schedule_lu_block_types', 'cbl_schedule_lu_block_types.block_type_id', '=', 'cbl_schedule.block_type_id')
            ->orderBy('cbl_schedule_lu_block_types.number_of_blocks', 'asc')
            ->first();

        /**
         * get all the blocks associated with this stream
         */
        $stream_blocks = self::where('schedule_id', $stream->schedule_id)
            ->with("block_type")
            ->with('children')
            ->get();

        return $stream_blocks;
    }

}
