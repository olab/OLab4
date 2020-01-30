<?php

namespace Entrada\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
               'Nodes'   => 'Entrada\Modules\Olab\Models\MapNodes',
               'Maps'    => 'Entrada\Modules\Olab\Models\Maps',
               'Servers' => 'Entrada\Modules\Olab\Models\Servers',
               'Scenarios' => 'Entrada\Modules\Olab\Models\Scenarios',
               'Courses' => 'Entrada\Modules\Olab\Models\Courses',
               'Globals' => 'Entrada\Modules\Olab\Models\Globals'
        ]);

        Validator::extend('timestamp', function ($attribute, $value, $parameters, $validator) {
            return ((string) (int) $value === $value)
                && ($value <= PHP_INT_MAX)
                && ($value >= ~PHP_INT_MAX);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
