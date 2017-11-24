<?php

namespace App\Modules\Sandbox\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use App\Policies\ACLPolicy;
use App\Modules\Sandbox\Models\Entrada\Sandbox;
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
