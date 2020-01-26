<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Entrada\Modules\Locations\Models\Entrada\SiteOrganisation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Site extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';

    protected $table = "global_lu_sites";

    protected $primaryKey = "site_id";

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        "site_code",
        "site_name",
        "site_address1",
        "site_address2",
        "site_city",
        "site_province_id",
        "site_country_id",
        "site_postcode"
    ];

    public function organisations() {
        return $this->hasMany('Entrada\Modules\Locations\Models\Entrada\SiteOrganisation', 'site_id');
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
