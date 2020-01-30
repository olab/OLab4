<?php

namespace Entrada\Modules\Clinical\Models\entrada;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models_Curriculum_Period;

class LeaveTracking extends Model
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
    protected $table = 'cbl_leave_tracking';

    protected $primaryKey = "leave_id";

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
    protected $fillable = ['proxy_id', 'type_id', 'start_date', 'end_date', 'days_used', 'weekdays_used', 'weekend_days_used', 'comments'];

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
            }
        });
    }

    public static function fetchByUser($user_id, $search = "", $cperiod_id = null)
    {
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);
        $query =  DB::table('cbl_leave_tracking as a')
           ->selectRaw('a.*, b.type_value')
           ->join('cbl_lu_leave_tracking_types as b', 'b.type_id', '=', 'a.type_id')
           ->whereNull("a.deleted_date")
           ->whereRaw("b.type_value LIKE ?", '%'.$search.'%')
           ->where('a.proxy_id', $user_id);

        if ($curriculum_period) {
            $query->whereBetween('a.start_date', [$curriculum_period->getStartDate(), $curriculum_period->getFinishDate()])
                ->whereBetween('a.end_date', [$curriculum_period->getStartDate(), $curriculum_period->getFinishDate()]);
        }

        return $query->get();
    }
}
