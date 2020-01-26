<?php

namespace Entrada\Modules\Olab\Providers;

use Caffeinated\Modules\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/Lang', 'olab');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'olab');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations', 'olab');

        // uncomment if SQL statement logging is desired
        //\DB::listen(function($query) {
        //    Log::Debug("SQL:" . $query->sql,
        //        $query->bindings,
        //        $query->time);
        //});

    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        // CMW: added
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
