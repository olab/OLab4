<?php

namespace Entrada\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class LocalUserProvider extends EloquentUserProvider
{
    /**
     * LocalUserProvider constructor.
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param string $model
     */
    public function __construct(HasherContract $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }

    /**
     * Validate a user against the given credentials.
     * Alters Eloquent driver by concatenating plaintext password and user salt.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain . $user->salt, $user->getAuthPassword());
    }
}
