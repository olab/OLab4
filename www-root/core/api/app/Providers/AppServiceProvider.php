<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
        //
        Relation::morphMap([
               'Nodes'   => 'App\Modules\Olab\Models\MapNodes',
               'Maps'    => 'App\Modules\Olab\Models\Maps',
               'Servers' => 'App\Modules\Olab\Models\Servers',
               'Scenarios' => 'App\Modules\Olab\Models\Scenarios'
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // this was added to aog all the database queries to the log file
        Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
            // filter oauth ones
            if (!str_contains($query->sql, 'oauth')) {
                Log::debug($query->sql . ' - ' . serialize($query->bindings));
            }
        });
    }

}
