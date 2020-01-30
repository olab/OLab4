<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class PoolFilter extends Model
{

    protected $connection = "entrada_database";
    protected $table = "admissions_pool_filters";
    protected $primaryKey = "filter_id";

    public $timestamps = false;

    protected $fillable = [
        "pool_id",
        "subpool",
        "gpa_total",
        "gpa_last_2_years",
        "mcat_total",
        "bbfl",
        "psbb",
        "cpbs",
        "cars",
        "has_reference_letters",
        "has_sketch_review"
    ];

    public function pool() {
        return $this->hasOne(Pool::class, "pool_id", "pool_id");
    }
}
