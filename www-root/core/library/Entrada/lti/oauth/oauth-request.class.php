<?php
/**
 * OAuth Token
 *
 * This OAuth Token class
 *
 */
class OAuthRequest {
    /**
     * OAuth version
     */
    const VERSION = '1.0';

    /**
     * Nonce
     *
     * @var String
     */
    private $nonce;

    /**
     * Timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * HTTP Method (POST, GET, etc.)
     *
     * @var String
     */
    private $httpMethod;

    /**
     * HTTP URL
     *
     * @var String
     */
    private $httpURL;

    /**
     * Parameters
     *
     * @var array|null
     */
    private $parameters;

    /**
     * Default constructor
     *
     * @param String $httpMethod - http method
     * @param String $httpURL - http URL
     * @param array|null $parameters - parameters
     * @param String|null $nonce - nonce
     * @param int|null $timestamp - timestamp
     */
    public function __construct($httpMethod, $httpURL, $parameters = null, $nonce = null, $timestamp = null) {
        $this->httpMethod = $httpMethod;
        $this->httpURL    = $httpURL;
        $this->parameters = $parameters;
        $this->nonce      = ($nonce == null) ? OAuthRequest::generateNonce() : $nonce;
        $this->timestamp  = ($timestamp == null) ? OAuthRequest::generateTimestamp() : $timestamp;
    }

    /**
     * Sign request
     *
     * @param IOAuthSignatureMethod $signatureMethod - signature method
     * @param OAuthConsumer $consumer - OAuth consumer
     * @param OAuthToken $token - OAuth token
     */
    public function sign($signatureMethod, $consumer, $token) {
        $this->setParameter('oauth_signature_method', $signatureMethod->getName(), false);
        $signature = $this->buildSignature($signatureMethod, $consumer, $token);
        $this->setParameter('oauth_signature', $signature, false);
    }

    /**
     * Build Signature
     *
     * @param IOAuthSignatureMethod $signatureMethod - signature method
     * @param OAuthConsumer $consumer - consumer
     * @param OAuthToken $token - token
     * @return String - Signature
     */
    public function buildSignature($signatureMethod, $consumer, $token) {
        return $signatureMethod->build($this->getSignatureBaseString(), $consumer, $token);
    }

    /**
     * Return signature base string
     *
     * @return String - signature base string
     */
    public function getSignatureBaseString() {
        $parts = array(
            $this->getNormalizedHTTPMethod(),
            $this->getNormalizedURL(),
            $this->getNormalizedParameters()
        );

        $parts = OAuthUtils::urlEncodeRfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * Get normalized HTTP Method
     *
     * @return String - normalized HTTP Method
     */
    private function getNormalizedHTTPMethod() {
        return strtoupper($this->httpMethod);
    }

    /**
     * Get normalized HTTP URL
     *
     * @return String - normalized HTTP URL
     */
    private function getNormalizedURL() {
        $urlParts = parse_url($this->httpURL);

        $port   = @$urlParts['port'];
        $scheme = $urlParts['scheme'];
        $host   = $urlParts['host'];
        $path   = @$urlParts['path'];

        $port or $port = ($scheme == 'https') ? '443' : '80';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Get normalized parameters
     *
     * @return String - Normalized Parameters
     */
    private function getNormalizedParameters() {
        $params = $this->parameters;

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtils::buildHTTPQuery($params);
    }

    /**
     * Set parameter
     *
     * @param String $name - name of parameter
     * @param mixed $value - value of parameter
     * @param bool $allowDuplicates - allow duplicates
     */
    public function setParameter($name, $value, $allowDuplicates = true) {
        if($allowDuplicates && isset($this->parameters[$name])) {
            if(is_scalar($this->parameters[$name])) {
                $this->parameters[$name] = array($this->parameters[$name]);
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Getters
     */
    public function getParameters() { return $this->parameters; }

    /**
     * Create OAuthRequest from Consumer and Token
     *
     * @param OAuthConsumer $consumer - OAuthConsumer
     * @param OAuthToken $token - OAuthToken
     * @param String $httpMethod - http method
     * @param String $httpURL - http URL
     * @param array|null $parameters - parameters
     * @return OAuthRequest
     */
    public static function createFromConsumerAndToken($consumer, $token, $httpMethod, $httpURL, $parameters = null) {
        @$parameters or $parameters = array();

        $nonce     = OAuthRequest::generateNonce();
        $timestamp = OAuthRequest::generateTimestamp();
        $default   = array(
            'oauth_version'      => OAuthRequest::VERSION,
            'oauth_nonce'        => $nonce,
            'oauth_timestamp'    => $timestamp,
            'oauth_consumer_key' => $consumer->getKey()
        );

        if($token) {
            $default['oauth_token'] = $token->getKey();
        }

        $parameters = array_merge($default, $parameters);

        $urlParts = parse_url($httpURL);
        if(isset($urlParts['query']) && $urlParts['query']) {
            $params     = OAuthUtils::parseParameterFromString($urlParts['query']);
            $parameters = array_merge($params, $parameters);
        }

        return new OAuthRequest($httpMethod, $httpURL, $parameters, $nonce, $timestamp);
    }

    /**
     * Generate nonce
     *
     * @return string - nonce
     */
    public static function generateNonce() {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand);
    }

    /**
     * Generate timestamp
     *
     * @return int - timestamp
     */
    public static function generateTimestamp() {
        return time();
    }
}
?>