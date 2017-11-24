<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Log;
use App\Http\Parser\RequestAttribute;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class RefreshToken
{
    /**
     * Handle an incoming request by refreshing token if expired
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        global $ENTRADA_USER;

        try {

            // Attempt parsing payload of token. This will trigger an exception
            // if the token is not current.

            $payload = Auth::parseToken()->getPayload();

        } catch (TokenExpiredException $e) {
            
            // If valid token is expired, renew it

            $new_token = Auth::parseToken()->refresh();

            // Attach new token to Auth instance

            Auth::setToken($new_token);

            // Attach new token to session

            $ENTRADA_USER->setToken($new_token);

            Log::info('Expired token has been refreshed via middleware.', [
                'user_id' => Auth::manager()->decode(Auth::getToken())->get('sub'),
            ]);

            // Check for use of RequestAttribute in parser chain

            $request_attribute_key = $this->getRequestAttributeKey();

            if ($request_attribute_key) {
                
                // Save new token in request attributes, so it can persist 
                // to next step in request pipeline

                $request->attributes->set($request_attribute_key, $new_token);
            }

            // set up next request

            $response = $next($request);
        
            // send the refreshed token back to the client

            $response->headers->set('Authorization', 'Bearer '.$new_token);

            // Onto next step in request pipeline

            return $response;

        } catch (JWTException $e) {
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage(), $e, $e->getCode());
        }

        // Token is still valid, so we move on to next item in request pipeline

        return $next($request);
    }

    /**
     * Get the stored key from the instance of RequestAttribute
     * if it is being used in the parser chain
     * 
     * @return string $key || false
     */
    protected function getRequestAttributeKey() 
    {
        $chain = Auth::parser()->getChain();

        if (is_array($chain)) {
            foreach($chain as $parser) {
                if ($parser instanceof RequestAttribute) {
                    return $parser->getKey();
                }
            }
        }

        return false;
    }
}
