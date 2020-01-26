<?php

namespace Entrada\Modules\Clinical\Policies;

use Entrada\Models\Auth\User;
use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Policies\ACLPolicy;

class RotationSchedulePolicy extends ACLPolicy
{

    /**
     * Determine whether the user can view a specific location.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function view(User $user, $model)
    {
        $id = $this->getModelId($model);
        if ($id) {
            return $this->acl->amIAllowed(new \RotationScheduleResource($id), 'read');
        } else {
            return $this->acl->amIAllowed('rotationschedule', 'read', false);
        }
    }

    /**
     * Determine whether the user can create a location model.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function create(User $user, $model)
    {
        $id = $this->getModelId($model);
        if ($id) {
            return $this->acl->amIAllowed(new \RotationScheduleResource($id), 'create');
        } else {
            return $this->acl->amIAllowed('rotationschedule', 'create', false);
        }
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
        $id = $this->getModelId($model);
        if ($id) {
            return $this->acl->amIAllowed(new \RotationScheduleResource($id), 'update');
        } else {
            return $this->acl->amIAllowed('rotationschedule', 'update', false);
        }
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
        $id = $this->getModelId($model);
        if ($id) {
            return $this->acl->amIAllowed(new \RotationScheduleResource($id), 'delete');
        } else {
            return $this->acl->amIAllowed('rotationschedule', 'delete', false);
        }
    }


    private function getModelId($model)
    {
        $id = false;
        if ($model instanceof DraftRotationSchedule && !empty($model->getKey())) {
            $id = $model->getKey();
        }

        return $id;
    }
}