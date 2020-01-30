<?php
/**
 * OAuth Consumer
 *
 * This OAuth Consumer class represent consumer with data
 *
 */
class OAuthConsumer {
    /**
     * OAuth Consumer Key
     *
     * @var String
     */
    private $key;

    /**
     * OAuth Consumer Secret
     *
     * @var String
     */
    private $secret;

    /**
     * OAuth Consumer callback URL
     *
     * @var String
     */
    private $callbackURL;

    /**
     * Default constructor
     *
     * @param String $key - OAuth Consumer Key
     * @param String $secret - OAuth Consumer Secret
     * @param String|null $callbackURL - OAuth Consumer callback URL
     */
    public function __construct($key, $secret, $callbackURL = null) {
        $this->key         = $key;
        $this->secret      = $secret;
        $this->callbackURL = $callbackURL;
    }

    /**
     * Getters
     */
    public function getKey()         { return $this->key;         }
    public function getSecret()      { return $this->secret;      }
    public function getCallbackURL() { return $this->callbackURL; }
}
?>