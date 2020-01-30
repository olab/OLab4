<?php

namespace Entrada\Modules\Admissions\Providers;

use Entrada\Modules\Admissions\Providers\AuthServiceProvider;
use Caffeinated\Modules\Support\ServiceProvider;
use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Illuminate\Database\Eloquent\Relations\Relation;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/Lang', 'admissions');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'admissions');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations', 'admissions');

        // This is used for the Flag MorphTo mapping
        Relation::morphMap([
            'file' => ApplicantFile::class
        ]);
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
    }
}
