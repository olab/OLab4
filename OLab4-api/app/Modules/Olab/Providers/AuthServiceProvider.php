<?php

namespace Entrada\Modules\Olab\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use Entrada\Policies\ACLPolicy;
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
        Gate::policy(Maps::class, ACLPolicy::class);
    }
}
