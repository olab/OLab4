<?php

namespace Entrada\Modules\Admissions\Policies;

use Entrada\Models\Auth\User;
use Entrada\Modules\Admissions\Models\Entrada\User as AdmissionsUser;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\CycleRole;
use Entrada\Policies\ACLPolicy;

class ApplicantPolicy extends ACLPolicy
{

    public function view_list(User $user, $applicant) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'read', false);

        return true;
    }

    public function view(User $user, $applicant) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'read', false);

        $user = CycleRole::admissionsUserFromUser($user);

        return self::isMyApplicant($user, $applicant);
    }

    public function create(User $user, $applicant) {
        return false;
    }

    /**
     * Determine if the given post can be updated by the user.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function update(User $user, $applicant)
    {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'read', false);

        return $cycle;
    }

    public function mass_update(User $user, $applicant) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'update', false);

        $user = CycleRole::admissionsUserFromUser($user);

        return $user->isAdmin();
    }



    public function mass_update_csv(User $user, $applicant) {
        $cycle = $this->acl->amIAllowed("admissions_cycle", 'update', false);

        $user = CycleRole::admissionsUserFromUser($user);

        return self::isMyApplicant($user, $applicant);
    }


    private function isMyApplicant(AdmissionsUser $user, Applicant $applicant) {
        $applicants = $user->applicants();

        foreach($applicants as $userApplicant) {
            if ($userApplicant->applicant_id == $applicant->applicant_id) {
                return true;
            }
        }

        return false;
    }
}