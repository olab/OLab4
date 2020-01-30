<?php

namespace Entrada\Modules\Locations\Providers;

use Entrada\Modules\Locations\Models\Entrada\Building;
use Entrada\Modules\Locations\Models\Entrada\Room;
use Entrada\Modules\Locations\Models\Entrada\Site;
use Entrada\Modules\Locations\Policies\LocationsPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the Locations module.
     *
     * @var array
     */
    protected $policies = [
        // Define Policies for Classes
        Site::class => LocationsPolicy::class,
        Building::class => LocationsPolicy::class,
        Room::class => LocationsPolicy::class,
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
