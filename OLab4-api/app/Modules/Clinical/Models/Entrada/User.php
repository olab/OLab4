<?php

namespace Entrada\Modules\Clinical\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class User extends Model
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

    /**
     * @param array $learners
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function fetchAllByLearnerIds($learners)
    {
        if (!is_array($learners)) {
            $learners = [ $learners ];
        }

        $query = self::whereIn('id', $learners)->select(['id', 'number', 'username', 'firstname', 'lastname', 'email']);
        return $query->get();
    }
}
