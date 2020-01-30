<?php

namespace Entrada\Models\Auth;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserAccess extends Model
{
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
    protected $table = 'user_access';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'access_starts',
        'access_expires',
        'last_login',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Scope a query to only a specific user id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    /**
     * Scope a query to only a specific app id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApp($query, $app_id)
    {
        return $query->where('app_id', $app_id);
    }
}
