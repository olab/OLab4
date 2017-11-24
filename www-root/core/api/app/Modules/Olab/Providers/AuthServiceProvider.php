<?php

namespace App\Modules\Olab\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use App\Policies\ACLPolicy;
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
        Gate::policy(Olab::class, ACLPolicy::class); 
    }
}
