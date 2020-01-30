<?php

namespace Entrada\Modules\Admissions\Providers;

use Entrada\Modules\Admissions\Models\Entrada\ReaderType;
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
        Applicant::class => ApplicantPolicy::class,
            // Generally, we'll check against Admissions/ACL for these
        ApplicantFile::class => AdmissionsPolicy::class,
        Cycle::class => AdmissionsPolicy::class,
        Flag::class => AdmissionsPolicy::class,
        Pool::class => AdmissionsPolicy::class,
        Reader::class => AdmissionsPolicy::class,
        ReaderGroup::class => AdmissionsPolicy::class,
        ReaderType::class => AdmissionsPolicy::class
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