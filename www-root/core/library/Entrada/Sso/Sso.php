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
 * Entrada_Sso_Sso
 *
 * The Entrada Sso Sso class implements SSO in Entrada via available
 * SSO providers.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Sso_Sso
{
    private static $sso_instance;
    private $sso_method;

    private function __construct(Entrada_Sso_Provider $provider)
    {
        $this->sso_method = $provider;
    }

    /**
     * Constructor - create a single instance of a Sso_Provider class based on the Type
     *
     * @param  type - SSO Provider to provision - 'Cas' or 'Shibboleth'
     * @return object
     */
    public static function getInstance($type)
    {
        if (empty(self::$sso_instance)) {
            $sso_classname = "Entrada_Sso_".$type;
            if (class_exists($sso_classname)) {
                self::$sso_instance = new Entrada_Sso_Sso(new $sso_classname());
            }
        }
        return self::$sso_instance;
    }

    /**
     * Used to force the system to authenticate directly with the SSO provider
     *
     * @param  target - where to redirect after login
     * @return void
     */
    public function login($target="")
    {
        return isset($this->sso_method) ? $this->sso_method->login($target) : null;
    }

    /**
     * Used to indicate that the login is to be forced
     *
     * @return bool
     */
    public function requiresLogin()
    {
        return isset($this->sso_method) ? $this->sso_method->requiresLogin() : null;
    }

    /**
     * Check the presence of SSO Authentication tokens that indicate SSO login was successful
     *
     * @return bool
     */
    public function isSsoAuthenticated()
    {
        return isset($this->sso_method) ? $this->sso_method->isSsoAuthenticated() : null;
    }

    /**
     * Process Authentication tokens from SSO provider and map to local authorization database
     *
     * @return array | null
     */
    public function validateUser()
    {
        return isset($this->sso_method) ? $this->sso_method->validateUser() : null;
    }

    /**
     * Invalidate the SSO Session. This may or may not return to the site depending on the technology
     *
     * @return void
     */
    public function logout()
    {
        return isset($this->sso_method) ? $this->sso_method->logout() : null;
    }
}