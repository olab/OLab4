<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = "global_lu_countries";
    protected $primaryKey = "countries_id";

    public $timestamps = false;
}
