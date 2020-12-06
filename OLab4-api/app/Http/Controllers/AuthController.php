<?php

namespace Entrada\Http\Controllers;

use Auth;
use Entrada_Auth;
use Entrada\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

        $this->middleware('auth', [
            'except' => [
                'postLogin',
                'postClient',
            ]
        ]);

        $this->middleware('api_auth', [
            'only' => [
                'getUser'
            ]
        ]);
    }

    public function postLogin(Request $request)
    {
        // Validate all request parameters

        $this->validate($request, [
            'auth_app_id' => 'required|integer',
            'auth_username' => 'required',
            'auth_password' => 'required',
            'username' => 'required',
            'password' => 'required',
            'auth_method' => 'required'
        ]);

        // Get app auth credentials

        $app_auth_credentials = [
            'id' => $request->input('auth_app_id'),
            'script_id' => $request->input('auth_username'),
            'script_password' => $request->input('auth_password'),
        ];
        // Authenticate the app auth_username and auth_password

        if (!Auth::guard('registered_apps')->attempt($app_auth_credentials)) {

            // Log unsuccessful request

            Log::info('Unsuccessful login attempt with invalid app credentials',
                ['id' => $request->input('auth_app_id'), 'script_id' => $request->input('auth_username')]);

            // Return 401 response

            return response()->json(['invalid_app_credentials'], 401);
        }

        // Remove potential white-space from the auth_method input.

        $auth_method_input = clean_input($request->input('auth_method'), "nows");

        // auth_method comes in as comma-separated string, so we
        // explode it to become an array of potential auth methods

        $auth_methods = explode(',', $auth_method_input);

        // For each method, attempt logging in via username, password

        foreach ($auth_methods as $auth_method) {
            try {

                $claims = [
                    'auth_method' => $auth_method,
                    'auth_app_id' => $app_auth_credentials["id"]
                ];

                $session_id = session_id();

                if ($session_id) {
                    $claims['session_id'] = $session_id;
                }

                // Add claims to token

                Auth::guard($auth_method)->claims($claims);

                // Attempt creating JWT with current auth_method

                if ($token = Auth::guard($auth_method)->attempt($request->only('username', 'password'))) {

                    Log::info('Successful login, JWT created.', $request->only('username', 'auth_method'));

                    // Attach Entrada_User and Entrada_ACL to PHP session
                    $response = Entrada_Auth::login($token);

                    return response()->json($response);
                }
            } catch (\Exception $e) {
                // For any exceptions that come up, we want it to be handled
                // by the 'invalid_user_credentials' error

                Log::error($e);

                continue;
            }
        }

        Log::info('Unsuccessful login attempt with invalid user credentials.',
            $request->only('username', 'auth_method'));

        return response()->json(['invalid_user_credentials'], 401);
    }

    public function postClient(Request $request)
    {
        // Validate all request parameters

        // Create auth instance

        $auth = new Entrada_Auth(AUTH_PRODUCTION);
        $auth->setAppAuth(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
        $method = AUTH_METHOD;

        if ( $request->isJson()) {

          Log::info('Login initiated via client route (json-based).', $request->only('username'));
          $auth_result = $auth->authenticate($request->json('username'), $request->json('password'), $method);

        } else {

          Log::info('Login initiated via client route.', $request->only('username'));

          // Authenticate all inputs
          $this->validate($request, [
              'username' => 'required',
              'password' => 'required',
          ]);

          $auth_result = $auth->authenticate($request->input('username'), $request->input('password'), $method);
        }

        // Login / create session

        if (!empty($auth_result['response']['status']) && $auth_result['response']['status'] == 'success' && !empty($auth_result['response']['token'])) {
            $auth_result['response'] = Entrada_Auth::login($auth_result['response']['token']);
        }

        return response($auth_result['response'], $auth_result['http_status']);
    }

    public function postLogout()
    {
        Entrada_Auth::logout();

        if (Auth::user()) {
            Log::info('User has logged out.', ['user_id' => Auth::user()->id]);
        }

        return ['logout_successful'];
    }

    public function getUser(Request $request)
    {
        global $ENTRADA_USER;

        if (Auth::user()) {
            Log::info('User has viewed their own profile.', ['user_id' => Auth::user()->id]);
        }

        return [
            'token_payload' => Auth::parseToken()->getPayload(),
            'user' => Auth::user(),
            'token' => Auth::getToken()->get(),
            'session' => $_SESSION,
            'ENTRADA_USER' => $ENTRADA_USER
        ];
    }

    /**
     * This method creates a token with a random number of claims
     * as well as random keys and values, designed purely for testing.
     * Requires username and password as input, and
     * assumes the "local" auth_method
     */
    public function createTokenWithRandomCustomClaims(Request $request)
    {
        $auth_method = 'local';

        $claims = [];

        $number_of_claims = rand(1,5);

        for ($i = 0; $i <= $number_of_claims; $i++) {
            $claims[uniqid()] = rand();
        }

        Auth::guard($auth_method)->claims($claims);

        $token = Auth::guard($auth_method)->attempt($request->only('username', 'password'));

        return response()->json(compact('token'));
    }

    public function parseToken(Request $request)
    {
        return ['payload' => $this->jwt->parseToken()->getPayload()];
    }
}
