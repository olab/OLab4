<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CycleContact extends Model
{
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_cycle_contacts";
    protected $primaryKey = "cycle_id";

    public function cycle() {
        return $this->hasOne(Cycle::class, "cycle_id", "cycle_id");
    }

    public function user() {
        return $this->hasOne(User::class, "id", "proxy_id");
    }

    public function role() {
        return $this->hasOne(CycleRole::class, "role_id", "role_id");
    }

    public static function findByUserID($user_id) {
        return self::where(["proxy_id" => $user_id])->first();
    }
}
