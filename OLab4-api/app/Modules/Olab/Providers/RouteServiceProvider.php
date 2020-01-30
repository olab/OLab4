<?php

namespace Entrada\Modules\Olab\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'Entrada\Modules\Olab\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the module.
     *
     * @return void
     */
    public function map()
    {
        // CMW: not needed
        // $this->mapWebRoutes();

        $this->mapApiRoutes();

        //
    }

    /**
     * Define the "web" routes for the module.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    // CMW: not needed
    /*
    protected function mapWebRoutes()
    {
        Route::group([
            'middleware' => 'web',
            'namespace'  => $this->namespace,
        ], function ($router) {
            require module_path('olab', 'Routes/web.php');
        });
    }
    */

    /**
     * Define the "api" routes for the module.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::group([
            // CMW: changed
            //'middleware' => 'api',
            'middleware' => ['api', 'api_auth'],
            'namespace'  => $this->namespace
            //'prefix'     => 'api',
        ], function ($router) {
            require module_path('olab', 'Routes/api.php');
        });
    }
}
