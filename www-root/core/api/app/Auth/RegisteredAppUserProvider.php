<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class RegisteredAppUserProvider extends EloquentUserProvider
{
    /**
     * RegisteredAppUserProvider constructor.
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param string $model
     */
    public function __construct(HasherContract $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }

    /**
     * Validate a registered app against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['script_password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}
