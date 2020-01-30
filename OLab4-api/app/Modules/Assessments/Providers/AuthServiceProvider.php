<?php

namespace Entrada\Modules\Assessments\Providers;

use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Entrada\Modules\Admissions\Policies\AdmissionsPolicy;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;
use Entrada\Modules\Admissions\Models\Entrada\Flag;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\Reader;
use Entrada\Modules\Admissions\Models\Entrada\ReaderGroup;
use Entrada\Modules\Admissions\Policies\ApplicantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // Define Policies for Classes
        // Applicant::class => ApplicantPolicy::class,

    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}