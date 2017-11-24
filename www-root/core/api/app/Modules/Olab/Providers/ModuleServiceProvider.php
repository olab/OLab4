<?php

namespace App\Modules\Olab\Providers;

use Caffeinated\Modules\Support\ServiceProvider;

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
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
