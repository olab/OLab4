<?php

namespace App\Policies;

use App\Models\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ACLPolicy
{
    use HandlesAuthorization;

    public function __construct() {
        global $ENTRADA_ACL;
        $this->acl = $ENTRADA_ACL;
    }

    /**
     * This method runs before all others
     *
     * @param  User  $user
     * @param  string $ability
     * @return false|void
     */
    public function before(User $user, $ability) {
        if (empty($this->acl)) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model instance.
     *
     * @param  User  $user
     * @param  $model
     * @return mixed
     */
    public function view(User $user, $model)
    {
        return $this->acl->amIAllowed($model->getTable(), 'read', false);
    }

    /**
     * Determine whether the user can create a model instance.
     *
     * @param  User  $user
     * @param  $model
     * @return mixed
     */
    public function create(User $user, $model)
    {
        return $this->acl->amIAllowed($model->getTable(), 'create', false);
    }

    /**
     * Determine whether the user can update the model instance.
     *
     * @param  User  $user
     * @param  $model
     * @return mixed
     */
    public function update(User $user, $model)
    {
        return $this->acl->amIAllowed($model->getTable(), 'update', false);
    }

    /**
     * Determine whether the user can delete the model instance.
     *
     * @param  User  $user
     * @param  $model
     * @return mixed
     */
    public function delete(User $user, $model)
    {
        return $this->acl->amIAllowed($model->getTable(), 'delete', false);
    }
}