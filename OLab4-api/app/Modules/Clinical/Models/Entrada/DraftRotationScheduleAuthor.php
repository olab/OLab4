<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Carbon\Carbon;

class DraftRotationScheduleAuthor extends Model
{

    const CREATED_AT = 'created_date';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cbl_schedule_draft_authors';

    protected $primaryKey = "cbl_schedule_draft_author_id";

    public $timestamps = false;

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
    protected $fillable = ['author_value', 'author_type', 'cbl_schedule_draft_id'];

    /**
     * The user who created this leaveTracking
     */
    public function created_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'created_by');
    }

    public function draft() {
        return $this->belongsTo('Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule', 'cbl_schedule_draft_id');
    }

    public function user() {
        return $this->belongsTo('Entrada\Models\Auth\User', 'author_value')->select("id", "firstname", "lastname");
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
            $model->created_date = Carbon::now()->getTimestamp();
        });
    }
}
