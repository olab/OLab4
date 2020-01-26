<?php

namespace Entrada\Modules\Admissions\Models\Entrada\UserRoles;

use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\CycleContact;
use Entrada\Modules\Admissions\Models\Entrada\Flag;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Entrada\Modules\Admissions\Models\Entrada\Reader;
use Entrada\Modules\Admissions\Models\Entrada\User;
use Illuminate\Http\Request;

class AdminUser extends User {

    /**
     * @inheritdoc
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function cycles() {
        return Cycle::all();
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function allApplicants() {
        return Applicant::byPool()->get();

    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function applicants() {
        return $this->allApplicants();
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    public function admissionsReaders() {
        return Reader::byPool($this)->get();
    }

    public function pools() {

        return Pool::with("filters")->get();
    }

    public function filters() {
        return PoolFilter::all();
    }

    public function flags() {
        return Flag::all();
    }

    public function isAdmin() {
        return true;
    }
}

?>