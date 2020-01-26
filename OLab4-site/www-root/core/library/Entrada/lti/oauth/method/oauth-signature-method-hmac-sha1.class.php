<?php
/**
 * OAuth Signature Method
 *
 * This OAuth Signature Method HMAC SHA1
 *
 */
class OAuthSignatureMethodHMACSHA1 implements IOAuthSignatureMethod {
    /**
     * Return name of signature method
     *
     * @return String - name of signature method
     */
    public function getName() {
        return 'HMAC-SHA1';
    }

    /**
     * Build signature
     *
     * @param String $baseString - base string
     * @param OAuthConsumer $consumer - consumer
     * @param OAuthToken $token - token
     * @return String
     */
    public function build($baseString, $consumer, $token) {
        $keyParts = array(
            $consumer->getSecret(),
            ($token) ? $token->getSecret() : ''
        );

        $keyParts = OAuthUtils::urlEncodeRfc3986($keyParts);
        $key      = implode('&', $keyParts);

        return base64_encode(hash_hmac('sha1', $baseString, $key, true));
    }
}
?>