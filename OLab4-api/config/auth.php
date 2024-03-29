<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'registered_apps' => [
            'driver' => 'jwt',
            'provider' => 'registered_apps'
        ],

        'local' => [
            'driver' => 'jwt',
            'provider' => 'local'
        ],

        'sso' => [
            'driver' => 'jwt',
            'provider' => 'sso'
        ],

        'ldap' => [
            'driver' => 'jwt',
            'provider' => 'ldap'
        ],

        'ad' => [
            'driver' => 'jwt',
            'provider' => 'ad'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'registered_apps' => [
            'driver' => 'registered_apps',
            'model' => Entrada\Models\Auth\RegisteredApp::class,
            'hasher' => Entrada\Libraries\MD5Hasher::class,
        ],

        'local' => [
            'driver' => 'local',
            'model' => Entrada\Models\Auth\User::class,
            'hasher' => Entrada\Libraries\SHAHasher::class,
        ],

        'sso' => [
            'driver' => 'sso',
            'model' => Entrada\Models\Auth\User::class,
            'hasher' => Entrada\Libraries\SSOHasher::class,
        ],

        'ldap' => [
            'driver' => 'ldap',
            'model' => Entrada\Models\Auth\User::class,
            'hasher' => Entrada\Libraries\SSOHasher::class,
        ],

        'ad' => [
            'driver' => 'ad',
            'model' => Entrada\Models\Auth\User::class,
            'hasher' => Entrada\Libraries\SSOHasher::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Here you may set the options for resetting passwords including the view
    | that is your password reset e-mail. You may also set the name of the
    | table that maintains all of the reset tokens for your application.
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        //
    ],

];