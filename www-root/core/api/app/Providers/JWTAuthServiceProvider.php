<?php

namespace App\Providers;

use Tymon\JWTAuth\Providers\LaravelServiceProvider;
use App\Http\Parser\RequestAttribute;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tymon\JWTAuth\Http\Parser\InputSource;
use Tymon\JWTAuth\Http\Parser\QueryString;
use Tymon\JWTAuth\Http\Parser\LumenRouteParams;

class JWTAuthServiceProvider extends LaravelServiceProvider
{
    /**
     * Boot the JWTAuth services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Boot parent instructions

        parent::boot();

        // Register new parser chain to include RequestAttribute

        $this->app['tymon.jwt.parser']->setChain([
            new RequestAttribute,
            new AuthHeaders,
            new QueryString,
            new InputSource,
            new LumenRouteParams,
        ]);
    }
}
