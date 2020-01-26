<?php

namespace Entrada\Modules\Notices\Providers;

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
        $this->loadTranslationsFrom(__DIR__.'/../Resources/Lang', 'notices');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'notices');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations', 'notices');
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
