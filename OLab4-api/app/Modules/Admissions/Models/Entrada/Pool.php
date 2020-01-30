<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Modules\Admissions\Scopes\CycleScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pool extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_pools";
    protected $primaryKey = "pool_id";

    protected $fillable = [
        "cycle_id", "name"
    ];

    public function cycle() {
        return $this->hasOne(Cycle::class, "cycle_id", "cycle_id");
    }

    public function applicants() {
        return $this->hasMany(Applicant::class, "pool_id", "pool_id");
    }

    public function filters() {
        return $this->hasMany(PoolFilter::class, 'pool_id', 'pool_id');
    }

    public function getFiltersAttribute() {

        return $this->getRelationValue('filters')->keyBy('subpool');
    }

    /**
     * Removes non letter-characters from string and adds Pool suffix
     *
     * @param $name string the readable name
     * @return string a Pool identifier
     */
    public static function classNamify($name) {
        return preg_replace("/[^A-Za-z]/", '', ucwords($name))."Pool";
    }

    protected static function boot() {
        parent::boot();

        static::addGlobalScope(new CycleScope());
    }

}
