<?php
/**
 * LTIConsumer
 *
 * This LTIConsumer class
 *
 */
class LTIConsumer {
    /**
     * Sign LTI Parameters
     *
     * @param array $parameters - parameters
     * @param String $endPoint - HTTP URL end point
     * @param String $method - HTTP method
     * @param String $key - LTI Key
     * @param String $secret - LTI Secret
     * @return array - signed parameters
     */
    public static function sign($parameters, $endPoint, $method, $key, $secret) {
        $params = $parameters;
        if(!isset($params["lti_version"])) $params['lti_version'] = 'LTI-1p0';
        if(!isset($params["lti_message_type"])) $params['lti_message_type'] = 'basic-lti-launch-request';
        if(!isset($params["oauth_callback"])) $params['oauth_callback'] = 'about:blank';

        $oauthConsumer = new OAuthConsumer($key, $secret);

        $oauthRequest  = OAuthRequest::createFromConsumerAndToken($oauthConsumer, null, $method, $endPoint, $params);
        $oauthRequest->sign(new OAuthSignatureMethodHMACSHA1(), $oauthConsumer, null);

        $newParams = $oauthRequest->getParameters();
        foreach($newParams as $k => $v ) {
            if (strpos($k, "oauth_") === 0 ) {
                $params[$k] = $v;
            }
        }

        return $params;
    }
}
?>