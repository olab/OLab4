<?php

namespace Entrada\Modules\Clinical\Providers;

use Entrada\Modules\Clinical\Models\Entrada\DraftRotationSchedule;
use Entrada\Modules\Clinical\Policies\RotationSchedulePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the Clinical module.
     * Currently all the authorization is tied to the rotation schedule, so that is the only acceptable model class
     *
     * @var array
     */
    protected $policies = [
        // Define Policies for Classes
        DraftRotationSchedule::class => RotationSchedulePolicy::class
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
