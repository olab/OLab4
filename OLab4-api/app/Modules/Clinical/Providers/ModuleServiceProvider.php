<?php

namespace Entrada\Modules\Clinical\Providers;

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
        $this->loadTranslationsFrom(__DIR__.'/../Resources/Lang', 'clinical');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'clinical');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations', 'clinical');
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(ValidationServiceProvider::class);
    }
}
