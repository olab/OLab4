<?php

namespace Entrada\Modules\Admissions\Policies;

use Entrada\Models\Auth\User;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\CycleContact;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Policies\ACLPolicy;

use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\Flag;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\Reader;
use Entrada\Modules\Admissions\Models\Entrada\ReaderGroup;

class AdmissionsPolicy extends ACLPolicy
{


    public function view_list(User $user, $model) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'read', false);

        return $cycle;
    }

    /**
     * Determine whether the user can view the model instance.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function view(User $user, $model)
    {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'read', false);

        return $cycle;
    }

    /**
     * Determine whether the user can create a model instance.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function create(User $user, $model)
    {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'create', false);

        return $cycle;
    }

    /**
     * Determine whether the user can update the model instance.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function update(User $user, $model)
    {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'update', false);

        return $cycle;
    }

    public function mass_update_csv(User $user, $reader) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'update', false);

        $user = CycleRole::admissionsUserFromUser($user);

        // Admin can mass update
        return $user->isAdmin();
    }



    /**
     * Determine whether the user can delete the model instance.
     *
     * @param  User $user
     * @param  $model
     * @return mixed
     */
    public function delete(User $user, $model)
    {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'delete', false);

        return $cycle;
    }

}