<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Entrada_Sso_Cas
 *
 * The Entrada Sso Cas class extends the Sso Provider class to support
 * CAS SSO.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Sso_Cas implements Entrada_Sso_Provider
{
    public function __construct()
    {
        phpCAS::client(CAS_VERSION_2_0, AUTH_CAS_HOSTNAME, AUTH_CAS_PORT, AUTH_CAS_URI, false);

        if (defined("AUTH_CAS_SERVER_CA_CERT") && AUTH_CAS_SERVER_CA_CERT) {
            phpCAS::setCasServerCACert(AUTH_CAS_SERVER_CA_CERT);
        } else {
            phpCAS::setNoCasServerValidation();    
        }

        if (defined("AUTH_CAS_SERVICE_VALIDATOR") && AUTH_CAS_SERVICE_VALIDATOR) {
            phpCAS::setServerServiceValidateURL("https://" . AUTH_CAS_HOSTNAME . "/" . AUTH_CAS_URI . "/" . AUTH_CAS_SERVICE_VALIDATOR);
        }
   }
    

    /**
     * Used to force the system to authenticate directly with the SSO provider
     *
     * @param  target - where to redirect after login
     * @return void
     */
    public function login($target)
    {
        phpCAS::forceAuthentication();
    }

    /**
     * Used to indicate that the login is to be forced
     *
     * @return bool
     */
    public function requiresLogin() {
        return isset($_COOKIE[AUTH_CAS_COOKIE]);
    }

    /**
     * Check the presence of SSO Authentication tokens that indicate SSO login was successful
     *
     * @return bool
     */
    public function isSsoAuthenticated()
    {
        return phpCAS::isSessionAuthenticated();
    }

    /**
     * Process Authentication tokens from SSO provider and map to local authorization database
     *
     * @return array | null
     */
    public function validateUser()
    {
        if (isset($_SESSION[AUTH_CAS_SESSION]["attributes"][AUTH_CAS_ID])) {
            $number = (int) $_SESSION[AUTH_CAS_SESSION]["attributes"][AUTH_CAS_ID];
            if ($number) {
                $user_id = User::fetchProxyBySuppliedField($number, AUTH_SSO_LOCAL_USER_QUERY_FIELD);
                $user_details = ($user_id ? User::fetchRowById($user_id) : null);
                if ($user_id && $user_details) {
                    $result = array();
                    $result["username"] = $user_details->getUserName();
                    $result["password"] = $user_details->getPassword();
                    $result["access_id"] = $user_details->getAccessId();

                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Invalidate the SSO Session. This may or may not return to the site depending on the technology
     *
     * @return void
     */
    public function logout()
    {
       phpCAS::logoutWithRedirectService(ENTRADA_URL);
    }
}
