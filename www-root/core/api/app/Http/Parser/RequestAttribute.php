<?php

namespace App\Http\Parser;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Contracts\Http\Parser as ParserContract;

class RequestAttribute implements ParserContract
{
    /**
     * The input source key.
     *
     * @var string
     */
    protected $key = 'token';

    /**
     * Try to parse the token from the request attribute.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return null|string
     */
    public function parse(Request $request)
    {
        return $request->attributes->get($this->key);
    }

    /**
     * Set the input source key.
     *
     * @param  string  $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the input source key.
     *
     * @return string $key
     */
    public function getKey()
    {
        return $this->key;
    }
}
