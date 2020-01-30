<?php

namespace Entrada\Models\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class RegisteredApp extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'auth_database';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'registered_apps';

    /**
     * Disables the need to store created_at / updated_at
     *
     * @var string
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'script_id',
        'script_password',
        'server_ip',
        'server_url',
        'employee_rep',
        'notes',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'script_password',
        'server_ip',
        'server_url',
    ];

    /**
     * Get the password field for an app
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->script_password;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
