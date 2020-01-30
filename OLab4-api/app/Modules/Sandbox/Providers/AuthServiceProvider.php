<?php

namespace Entrada\Modules\Sandbox\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use Entrada\Policies\ACLPolicy;
use Entrada\Modules\Sandbox\Models\Entrada\Sandbox;
use Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap auth services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::policy(Sandbox::class, ACLPolicy::class);
    }
}
