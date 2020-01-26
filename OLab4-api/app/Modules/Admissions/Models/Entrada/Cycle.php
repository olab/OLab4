<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Cycle extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';

    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_cycles";
    protected $primaryKey = "cycle_id";

    protected $fillable = [
        "name", "description", "organization_id"
    ];

    protected $visible = [
        "cycle_id", "name", "description"
    ];

    private static $current_cycle_id = null;

    public function pools() {
        return $this->hasMany(Pool::class, "cycle_id", "cycle_id")->with("filters");
    }

    public function poolFilters() {
        return $this->hasManyThrough(PoolFilter::class, Pool::class, "pool_id", "pool_id", "cycle_id");
    }

    public function contacts() {
        return $this->hasMany(CycleContact::class, "cycle_id", "cycle_id");
    }

    public function applicants() {
        return $this->hasMany(Applicant::class, "cycle_id", "cycle_id");
    }

    /**
     * Fetches the Cycle from the Request params if set, otherwise uses current cycle
     *
     * @return int|mixed
     */
    public static function cycleFromRequest() {
        // Check if the cycle_id parameter is set in the scope
        $request = app(Request::class);
        $paramCycleSet = $request->has("cycle_id");

        // If yes, use that cycle_id
        if (!empty($paramCycleSet)) {
            $cycle = app(Request::class)->input("cycle_id");
        } else {
            //otherwise, use the default system Cycle
            $cycle = Cycle::currentCycleID();
        }

        return $cycle;
    }


    /**
     * Returns the current Cycle object based on the database setting "admissions_cycle"
     *
     * @return Model the current Cycle
     */
    public static function currentCycle() {
        return Cycle::where(['cycle_id' => self::currentCycleID()])->first();
    }

    /**
     * Returns the current Cycle ID based on the database setting "admissions_cycle"
     *
     *
     * @return int|mixed
     */
    public static function currentCycleID() {
        return is_null(self::$current_cycle_id) ? Setting::fetch('admissions_cycle') : self::$current_cycle_id;
    }

}
