<?php

namespace Entrada\Providers;

use Auth;
use Entrada\Models\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Entrada\Auth\RegisteredAppUserProvider;
use Entrada\Auth\LocalUserProvider;
use Entrada\Auth\SsoUserProvider;
use Entrada\Auth\LdapUserProvider;
use Entrada\Auth\AdUserProvider;
use Entrada\Policies\ACLPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        // Register new user providers when functionality cannot 
        // be handled entirely by Eloquent or Database user providers

        Auth::provider('registered_apps', function ($app, $config) {
            return new RegisteredAppUserProvider($this->createHasher($config['hasher']), $config['model']);
        });

        Auth::provider('local', function ($app, $config) {
            return new LocalUserProvider($this->createHasher($config['hasher']), $config['model']);
        });

        Auth::provider('sso', function ($app, $config) {
            return new SsoUserProvider($this->createHasher($config['hasher']), $config['model']);
        });

        Auth::provider('ldap', function ($app, $config) {
            return new LdapUserProvider($this->createHasher($config['hasher']), $config['model']);
        });

        Auth::provider('ad', function ($app, $config) {
            return new AdUserProvider($this->createHasher($config['hasher']), $config['model']);
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {

        // Register the ACL Policy for the model. 
        // Do this for all models that require ACL access.

        // Example:
        // Gate::policy(YourModel::class, ACLPolicy::class);
    }

    /**
     * Create a new instance of the hasher.
     *
     * @return \Illuminate\Contracts\Hashing\Hasher
     */
    protected function createHasher($hasher)
    {
        $class = '\\' . ltrim($hasher, '\\');

        return new $class;
    }
}
