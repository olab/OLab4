<?php

namespace Entrada\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class SsoUserProvider extends EloquentUserProvider
{
    /**
     * SsoUserProvider constructor.
     * @param \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param string $model
     */
    public function __construct(HasherContract $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }
}
