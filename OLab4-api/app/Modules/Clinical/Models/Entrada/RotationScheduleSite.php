<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class RotationScheduleSite extends Model
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
    protected $table = 'cbl_schedule_sites';

    protected $primaryKey = "cssite_id";

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
    protected $fillable = ['schedule_id', 'site_id'];

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

    public function site() {
        return $this->belongsTo('Entrada\Modules\Locations\Models\Entrada\Site', 'site_id');
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
}
