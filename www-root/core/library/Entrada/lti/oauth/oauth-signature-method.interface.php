<?php
/**
 * OAuth Signature Method
 *
 * This OAuth Signature Method interface
 *
 */
interface IOAuthSignatureMethod {
    /**
     * Return name of signature method
     *
     * @return String - name of signature method
     */
    public function getName();

    /**
     * Build signature
     *
     * @param String $baseString - base string
     * @param OAuthConsumer $consumer - consumer
     * @param OAuthToken $token - token
     * @return String
     */
    public function build($baseString, $consumer, $token);
}
?>