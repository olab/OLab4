<?php

namespace App\Models\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
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
    protected $table = 'user_data';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The field to record when the record was created.
     *
     * @var string
     */
    const CREATED_AT = 'created_date';

    /**
     * The field to record when the record was updated.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'salt',
        'organisation_id',
        'department',
        'province',
        'country',
        'notes',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
