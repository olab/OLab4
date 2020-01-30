<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = "global_lu_provinces";
    protected $primaryKey = "province_id";

    public $timestamps = false;
}
