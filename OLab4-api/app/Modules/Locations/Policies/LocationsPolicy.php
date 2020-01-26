<?php

namespace Entrada\Modules\Locations\Policies;

use Entrada\Models\Auth\User;
use Entrada\Policies\ACLPolicy;

class LocationsPolicy extends ACLPolicy
{

    /**
     * Determine whether the user can view a specific location.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function view(User $user, $model) {
        global $ENTRADA_USER;

        return $this->acl->amIAllowed(new \ConfigurationResource($ENTRADA_USER->getActiveOrganisation()), 'read');
    }

    /**
     * Determine whether the user can create a location model.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function create(User $user, $model) {
        global $ENTRADA_USER;

        return $this->acl->amIAllowed(new \ConfigurationResource($ENTRADA_USER->getActiveOrganisation()), 'create');
    }

    /**
     * Determine whether the user can update the location model.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function update(User $user, $model)
    {
        global $ENTRADA_USER;

        return $this->acl->amIAllowed(new \ConfigurationResource($ENTRADA_USER->getActiveOrganisation()), 'update');
    }

    /**
     * Determine whether the user can delete the location model.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function delete(User $user, $model)
    {
        global $ENTRADA_USER;

        return $this->acl->amIAllowed(new \ConfigurationResource($ENTRADA_USER->getActiveOrganisation()), 'delete');
    }
}