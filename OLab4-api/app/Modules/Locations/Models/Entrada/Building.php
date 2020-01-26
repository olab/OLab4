<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Auth;
use App;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = "global_lu_buildings";
    protected $primaryKey = "building_id";
    protected $fillable = [
        "site_id",
        "building_code",
        "building_name",
        "building_address1",
        "building_address2",
        "building_city",
        "building_province",
        "building_country",
        "building_province_id",
        "building_country_id",
        "building_postcode"
    ];

    public $timestamps = false;
}
