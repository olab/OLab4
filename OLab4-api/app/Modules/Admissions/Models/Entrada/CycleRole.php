<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Entrada\Modules\Admissions\Models\Entrada\UserRoles\AdminUser;
use Entrada\Modules\Admissions\Models\Entrada\UserRoles\ReaderUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class CycleRole extends Model
{
    use SoftDeletes;

    const ROLE_ADMIN = 'admin';
    const ROLE_READER = 'reader';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    const DELETED_AT = 'deleted_date';
    protected $dateFormat = 'U';

    protected $connection = "entrada_database";
    protected $table = "admissions_cycle_roles";
    protected $primaryKey = "role_id";

    public function contacts() {
        return $this->hasMany(CycleContact::class, "role_id", "role_id");
    }

    /**
     * Fetches the role of the User object represented by user_id, for the cycle_id
     *
     * @param int $user_id The id of the user for which we are fetching the role
     * @param bool $return_object if true, returns ContactRole object; just the shortname if false
     * @param null|int $cycle_id the id of the Cycle for which to find the role. Current cycle if null
     * @return CycleRole|string|null CycleRole if $return object is true, shortname if not. Returns null if CycleContact or CycleRole are not found
     */
    public static function roleForUser($user_id, $return_object = false, $cycle_id = null) {

        $cycle_id = is_null($cycle_id) ? Cycle::cycleFromRequest() : $cycle_id;

        $contact = CycleContact::where([
           "proxy_id" => $user_id,
            "cycle_id" => $cycle_id
        ])->first();

        return empty($contact->role)
            ? null
            : ($return_object ? $contact->role : $contact->role->shortname);
    }

    /**
     * Converts an Auth\User into an Admissions User (or child object)
     *
     * @param $user \Entrada\Models\Auth\User the Auth\User to convert
     * @return User the Admissions User (or child)
     */
    public static function admissionsUserFromUser($user) {
        if (empty($user))
           return null; // This should probably throw an error

        $role = self::roleForUser($user->id);

        switch($role) {
            case self::ROLE_ADMIN:
                return AdminUser::find($user->id);
                break;
            case self::ROLE_READER:
                return ReaderUser::find($user->id);
                break;
            default:
                return User::find($user->id);
                break;
        }
    }
}
