<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;



class Setting extends Model
{

    protected $connection = "entrada_database";
    protected $table = "settings";
    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'shortname', 'organisation_id', 'value'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * Returns the value of the Setting identified by the $key
     *
     * @param $key
     * @return bool|mixed the value of the setting currently, false otherwise. This default should be taken into consideration when requesting boolean or falsy values
     */
    public static function fetch($key) {
        $setting = self::where(["shortname" => $key])->first();

        if (empty($setting)) {
            return false;
        }

        return $setting->value;
    }
}
