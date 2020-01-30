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
 * The file linked from /admin/users when the user clicks "login as".
 * The masquerade feature fully logs the admin in as the user of their choice.
 * When they log out of the user's account, they will be logged back in as 
 * their original admin account.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2013 David Geffen School of Medicine at UCLA. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("masquerade", "read")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($PROXY_ID && $PROXY_ID != $_SESSION["details"]["id"]) {
		
		$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($PROXY_ID);
		$user_data = $db->GetRow($query);
        if ($user_data) {
            $query = "SELECT *
                        FROM `".AUTH_DATABASE."`.`user_access`
                        WHERE `user_id` = ".$db->qstr($PROXY_ID)."
                        AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
                        AND `account_active` = 'true'
                        AND (`access_starts` = 0 OR `access_starts` <= ".time().")
                        AND (`access_expires` = 0 OR `access_expires` > ".time().")";
            $user_access = $db->GetRow($query);
		    if ($user_access) {
                /**
                 * Store the old session data for when the current is logged out
                 */
                $previous_session = $_SESSION;

                $first_session_id = (isset($_SESSION["first_session_id"]) ? $_SESSION["first_session_id"] : $_SESSION["details"]["id"]);

                $_SESSION = array();
                unset($_SESSION);
                session_destroy();

                session_start();

                /**
                 * We need to use the full Entrada login process to get a token for this user from the API. Otherwise API calls will fail later
                 */
                $auth = new Entrada_Auth(AUTH_PRODUCTION);
                $auth->setAppAuth(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);

                /**
                 * We use the SSO authentication method, because it allows us to log in any user
                 */
                $method = "sso";

                $auth_result = $auth->authenticate($user_data["username"], $user_data["password"], $method);
                if (!empty($auth_result["response"]["status"]) && $auth_result["response"]["status"] == "success" && !empty($auth_result["response"]["token"])) {
                    $auth_result = Entrada_Auth::login($auth_result["response"]["token"]);
                }

                if (empty($auth_result["status"]) || ($auth_result["status"] != "success")) {
                    header("Location: ".ENTRADA_URL."/admin/users");
                    exit;
                }

                $_SESSION["previous_session"] = $previous_session;
                $_SESSION["first_session_id"] = $first_session_id;
                $_SESSION["auth"]["method"] = $previous_session["auth"]["method"];

                /**
                 * Save the user login information to the statistics table.
                 */
                add_statistic("users", "login_as", "proxy_id", $_SESSION["details"]["id"], $_SESSION["first_session_id"]);

                /**
                 * Set the active organisation profile for the user.
                 */
                load_active_organisation();

                header("Location: ".ENTRADA_URL);
                exit;
            } else {
                header("Location: ".ENTRADA_URL."/admin/users");
                exit;
            }
		} else {
            header("Location: ".ENTRADA_URL."/admin/users");
            exit;
		}
	} else {
		header("Location: ".ENTRADA_URL."/admin/users");
		exit;
	}
}
