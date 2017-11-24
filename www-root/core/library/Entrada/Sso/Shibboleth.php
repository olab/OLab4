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
 * Entrada_Sso_Shibboleth
 *
 * The Entrada Sso Shibboleth class extends the Sso Provider class to support
 * Shibboleth SSO.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Sso_Shibboleth implements Entrada_Sso_Provider
{
    /**
     * Used to force the system to authenticate directly with the SSO provider
     *
     * @param  target - where to redirect after login
     * @return void
     */
    public function login($target)
    {
        header("Location: " . AUTH_SHIB_URL . AUTH_SHIB_LOGIN_URI . "?target=" . $target);
        exit;
    }

    /**
     * Used to indicate that the login is to be forced
     * This does not apply to Shibboleth, so always return false
     *
     * @return bool
     */
    public function requiresLogin()
    {
        return false;
    }

    /**
     * Check the presence of SSO Authentication tokens that indicate SSO login was successful
     *
     * @return bool
     */
    public function isSsoAuthenticated()
    {
        return !empty($_SERVER[AUTH_SHIB_SESSION]) && !empty($_SERVER[AUTH_SHIB_ID]);
    }

    /**
     * Process Authentication tokens from SSO provider and map to local authorization database
     *
     * @return array | null
     */
    public function validateUser()
    {
        $result = false;
        if (!empty($_SERVER[AUTH_SHIB_SESSION]) && !empty($_SERVER[AUTH_SHIB_ID])) {
            $user_id = User::fetchProxyBySuppliedField($_SERVER[AUTH_SHIB_ID], AUTH_SSO_LOCAL_USER_QUERY_FIELD);
            $user_details = (!empty($user_id) ? User::fetchRowById($user_id) : false);

            if (!empty($user_id) && !empty($user_details)) {
                $result["username"] = $user_details->getUserName();
                $result["password"] = $user_details->getPassword();
                $result["access_id"] = $user_details->getAccessId();
            } else {
                // the user record was not found. Log the details
                application_log("error", "SSO login: Unable to find user with identifier: [".$_SERVER[AUTH_SHIB_ID]."] in the database column: [".AUTH_SSO_LOCAL_USER_QUERY_FIELD."].");
            }
        }
        return $result;
    }

    /**
     * Invalidate the SSO Session. This may or may not return to the site depending on the technology
     *
     * @return void
     */
    public function logout()
    {
        if (!empty($_SERVER[AUTH_SHIB_SESSION]) && !empty($_SERVER[AUTH_SHIB_ID])) {
            header("Location: ".AUTH_SHIB_URL.AUTH_SHIB_LOGOUT_URI);
            exit;
        }
    }
}