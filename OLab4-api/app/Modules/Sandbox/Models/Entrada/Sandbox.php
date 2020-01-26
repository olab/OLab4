<?php

namespace Entrada\Modules\Sandbox\Models\Entrada;

use Auth;
use App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sandbox extends Model
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
    protected $table = 'sandbox';

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
    protected $dates = ['created_date', 'updated_date', 'deleted_date'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'description'];

    /**
     * The users that belong to a sandbox
     */
    public function users()
    {
        $db_config = app()->make('config')->get('database');
        $database = $db_config['connections']['entrada_database']['database'];

        return $this->belongsToMany('Entrada\Models\Auth\User', "$database.sandbox_contacts", 'sandbox_id', 'proxy_id');
    }

    /**
     * The user who created this sandbox
     */
    public function created_by()
    {
        return $this->belongsTo('Entrada\Models\Auth\User', 'created_by');
    }

    /**
     * The user who updated this sandbox
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
         * Set owner of sandbox upon creation
         */
        static::created(function ($model) {
            $user = Auth::user();
            $model->users()->attach($user->id);
        });

        /**
         * Set fields on updating event
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
                $model->deleted_by = $user->id;
                $model->save();
            }
        });
    }
}
