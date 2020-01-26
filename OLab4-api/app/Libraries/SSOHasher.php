<?php

namespace Entrada\Libraries;

use Illuminate\Contracts\Hashing\Hasher;

class SSOHasher implements Hasher
{

    /**
     * Hash the given value. For SSO, just return the original string, because we are already working with hashed items
     *
     * @param  string $value
     * @param  array $options
     * @return string
     */
    public function make($value, array $options = array())
    {
        return $value;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string $value
     * @param  string $hashedValue
     * @param  array $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = array())
    {
        return $this->make($value) === $hashedValue;
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string $hashedValue
     * @param  array $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = array())
    {
        return false;
    }
}
