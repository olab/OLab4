<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use DB;

class RotationScheduleSlot extends Model
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
    protected $table = 'cbl_schedule_slots';

    protected $primaryKey = "schedule_slot_id";

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
    protected $fillable = ['schedule_id', 'slot_type_id', 'slot_min_spaces', 'slot_spaces', 'strict_spaces', 'course_id', 'site_id'];

    /**
     * The user who created this Rotation Schedule Slot
     */
    public function created_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'created_by');
    }

    /**
     * The user who updated this Rotation Schedule Slot
     */
    public function updated_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'updated_by');
    }

    public function slot_type() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleSlotType', 'slot_type_id');
    }

    public function audience() {
        return $this->hasMany('Entrada\Modules\Clinical\Models\Entrada\RotationScheduleAudience', 'schedule_slot_id');
    }

    public function site() {
        return $this->belongsTo('Entrada\Modules\Locations\Models\Entrada\Site', 'site_id');
    }

    public function rotation_schedule() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\RotationSchedule', 'schedule_id');
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

                $model->audience()->delete();
            }
        });
    }
}
