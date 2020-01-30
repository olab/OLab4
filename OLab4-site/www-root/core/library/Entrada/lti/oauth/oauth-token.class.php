<?php
/**
 * OAuth Token
 *
 * This OAuth Token class
 *
 */
class OAuthToken {
    /**
     * Token Key
     *
     * @var String
     */
    private $key;

    /**
     * Token Secret
     *
     * @var String
     */
    private $secret;

    /**
     * Default constructor
     *
     * @param String $key - Token Key
     * @param String $secret - Token Secret
     */
    public function __construct($key, $secret) {
        $this->key    = $key;
        $this->secret = $secret;
    }

    /**
     * Convert token to string
     *
     * @return string - string equivalent of OAuthToken
     */
    public function toString() {
        return 'oauth_token='         . OAuthUtils::urlEncodeRfc3986($this->key) .
               '&oauth_token_secret=' . OAuthUtils::urlEncodeRfc3986($this->secret);
    }

    /**
     * Getters
     */
    public function getKey()    { return $this->key;    }
    public function getSecret() { return $this->secret; }
}
?>