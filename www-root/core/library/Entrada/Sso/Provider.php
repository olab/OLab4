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
 * Entrada_Sso_Provider
 *
 * The Entrada Sso Provider defines the interface for any supported
 * SSO providers used to support federated login to the site.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

interface Entrada_Sso_Provider
{
    /**
     * Used to force the system to authenticate directly with the SSO provider
     *
     * @param  target - where to redirect after login
     * @return void
     */
    public function login($target);

    /**
     * Used to indicate that the login is to be forced
     *
     * @return bool
     */
    public function requiresLogin();

    /**
     * Check the presence of SSO Authentication tokens that indicate SSO login was successful
     *
     * @return bool
     */
    public function isSsoAuthenticated();

    /**
     * Process Authentication tokens from SSO provider and map to local authorization database
     *
     * @return array | null
     */
    public function validateUser();

    /**
     * Invalidate the SSO Session. This may or may not return to the site depending on the technology
     *
     * @return void
     */
    public function logout();
}