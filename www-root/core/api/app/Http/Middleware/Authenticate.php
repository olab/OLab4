<?php

namespace App\Http\Middleware;

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
     * Global $ENTRADA_USER instance
     */
    protected $entrada_user;

    /**
     * Authenticate constructor. Create a new middleware instance.
     * @param \Illuminate\Contracts\Auth\Factory $auth
     */
    public function __construct(Auth $auth)
    {
        global $translate, $ENTRADA_USER;

        $this->auth = $auth;
        $this->translate = $translate;
        $this->entrada_user = $ENTRADA_USER;
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
        // Do not allow users who are unauthenticated / unauthorized

        if (!Entrada_Auth::isAuthorized($this->auth->getToken())) {

            throw new TokenInvalidException("session expired");
        }
        else if (!$this->auth->guard($guard)->guest()) {

            // Get payload as array

            $payload = $this->auth->parseToken()->getPayload()->toArray();

            // Set default auth driver if token present

            if ($token_auth_method = $this->auth->parseToken()->getClaim('auth_method')) {
                $this->auth->shouldUse($token_auth_method);

                // If session does not match, generate new session

                if ($this->auth->parseToken()->getClaim('session_id') != session_id()) {

                    // Generate a new session
                    Entrada_Auth::login($this->auth->getToken()->get());

                    // Create new payload with new session_id
                    $newpayload = $this->auth->factory()->customClaims(['session_id' => session_id()])->make();

                    // Create new token from payload
                    $newtoken = $this->auth->manager()->encode($newpayload);
            
                    // Send the new token back to the client
                    $response = $next($request);
                    $response->headers->set('Authorization', 'Bearer '.$newtoken);

                    // Add new token to session
                    $this->entrada_user->setToken($newtoken);

                    return $response;
                }
            }
        }

        return $next($request);
    }
}
