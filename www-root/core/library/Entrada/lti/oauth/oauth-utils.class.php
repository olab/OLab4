<?php
/**
 * OAuth Utils
 *
 * This OAuth Utils class
 *
 */
class OAuthUtils {
    /**
     * Encode input to RFC3986 format
     *
     * @param mixed $input - input values to encoding
     * @return string - encoding string
     */
    public static function urlEncodeRfc3986($input) {
        $result = '';

        if(is_array($input)) {
            $result = array_map(array('OAuthUtils', 'urlEncodeRfc3986'), $input);
        } else if(is_scalar($input)) {
            $result = str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
        }

        return $result;
    }

    /**
     * Parse parameters from string and encode to RCF3986 format
     *
     * @param String $inputStringParams - input string params
     * @return array - encoded params
     */
    public static function parseParameterFromString($inputStringParams) {
        $result = array();
        if(!isset($inputStringParams) || !$inputStringParams) return $result;

        $pairs = explode('&', $inputStringParams);
        foreach($pairs as $pair) {
            $split     = explode('=', $pair, 2);
            $parameter = OAuthUtils::urlEncodeRfc3986($split[0]);
            $value     = isset($split[1]) ? OAuthUtils::urlEncodeRfc3986($split[1]) : '';

            if(isset($result[$parameter])) {
                if(is_scalar($result[$parameter])) {
                    $result[$parameter] = array($result[$parameter]);
                }

                $result[$parameter][] = $value;
            } else {
                $result[$parameter] = $value;
            }
        }

        return $result;
    }

    /**
     * Build normalized HTTP query
     *
     * @param array $input - HTTP query parameters
     * @return String - normalized query string
     */
    public static function buildHTTPQuery($input) {
        if(!$input) return '';

        $keys   = OAuthUtils::urlEncodeRfc3986(array_keys($input));
        $values = OAuthUtils::urlEncodeRfc3986(array_values($input));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = array();
        foreach($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                natsort($value);

                foreach ($value as $duplicateValue) {
                    $pairs[] = $parameter . '=' . $duplicateValue;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }

        return implode('&', $pairs);
    }
}
?>