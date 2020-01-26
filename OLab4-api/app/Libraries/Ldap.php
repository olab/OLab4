<?php
/**
 * Created by PhpStorm.
 * User: eah5
 * Date: 2017-05-24
 * Time: 11:10 AM
 */

namespace Entrada\Libraries;

use Illuminate\Support\Facades\Log;

class Ldap extends \Zend_Ldap
{

    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    public function connect($host = null, $port = null, $useSsl = null, $useStartTls = null)
    {
        try {
            parent::connect($host, $port, $useSsl, $useStartTls);
        } catch (\Zend_Ldap_Exception $zle) {
            Log::info("LDAP connection failed with error: " . $zle->getCode() . " - " . $zle->getMessage());
            return false;
        }
        return true;
    }

    public function bind($username = null, $password = null)
    {
        try {
            parent::bind($username, $password);
        } catch (\Zend_Ldap_Exception $zle) {
            Log::info("LDAP authentication failed with error: " . $zle->getCode() . " - " . $zle->getMessage());
            return false;
        }
        return true;
    }
}