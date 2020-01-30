<?php

namespace Entrada\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Entrada_Auth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Global $translate instance
     */
    protected $translate;

    /**
     * Authenticate constructor. Create a new middleware instance.
     * @param \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct(Auth $auth)
    {
        global $translate;

        $this->auth = $auth;
        $this->translate = $translate;
    }

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        global $ENTRADA_USER;

        // Do not allow users who are unauthenticated / unauthorized

        if (!Entrada_Auth::isAuthorized($this->auth->getToken())) {

            throw new TokenInvalidException();
        }

        return $next($request);
    }
}
