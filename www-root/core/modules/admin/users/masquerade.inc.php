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
                $ENTRADA_USER = User::get($PROXY_ID);
                $_SESSION["previous_session"] = $previous_session;
                $_SESSION["first_session_id"] = $first_session_id;

                /**
                 * Swap out the admin's data for the requested user's
                 */
                $_SESSION["isAuthorized"] = true;
                $_SESSION["auth"]["method"] = $previous_session["auth"]["method"];
                $_SESSION["details"] = array();
                $_SESSION["details"]["app_id"] = (int) AUTH_APP_ID;
                $_SESSION["details"]["id"] = $user_data["id"];
                $_SESSION["details"]["access_id"] = $user_access["id"];
                $_SESSION["details"]["username"] = $user_data["username"];
                $_SESSION["details"]["prefix"] = $user_data["prefix"];
                $_SESSION["details"]["firstname"] = $user_data["firstname"];
                $_SESSION["details"]["lastname"] = $user_data["lastname"];
                $_SESSION["details"]["email"] = $user_data["email"];
                $_SESSION["details"]["email_alt"] = $user_data["email_alt"];
                $_SESSION["details"]["email_updated"] = (int) $user_data["email_updated"];
                $_SESSION["details"]["google_id"] = $user_data["google_id"];
                $_SESSION["details"]["telephone"] = $user_data["telephone"];
                $_SESSION["details"]["role"] = $user_access["role"];
                $_SESSION["details"]["group"] = $user_access["group"];
                $_SESSION["details"]["organisation_id"] = $user_access["organization_id"];
                $_SESSION["details"]["expires"] = $user_access["access_expires"];
                $_SESSION["details"]["lastlogin"] = $user_access["last_login"];
                $_SESSION["details"]["privacy_level"] = $user_data["privacy_level"];
                $_SESSION["details"]["notifications"] = $user_data["notifications"];
                $_SESSION["details"]["private_hash"] = $user_access["private_hash"];
                $_SESSION["details"]["allow_podcasting"] = false;

                if (isset($ENTRADA_CACHE) && !DEVELOPMENT_MODE) {
                    if (!($ENTRADA_CACHE->test("acl_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID()))) {
                        $ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
                        $ENTRADA_CACHE->save($ENTRADA_ACL, "acl_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                    } else {
                        $ENTRADA_ACL = $ENTRADA_CACHE->load("acl_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                    }
                } else {
                    $ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
                }

                $extras = unserialize(base64_decode($user_access["extras"]));
                if (isset($extras["allow_podcasting"])) {
                    if ((int) trim($extras["allow_podcasting"])) {
                        $_SESSION["details"]["allow_podcasting"] = (int) trim($extras["allow_podcasting"]);
                    } elseif (trim(strtolower($extras["allow_podcasting"])) == "all") {
                        $_SESSION["details"]["allow_podcasting"] = "all";
                    }
                }
                /**
                 * Save the user login information to the statistics table.
                 */
                add_statistic("users", "login_as", "proxy_id", $_SESSION["details"]["id"], $_SESSION["first_session_id"]);

                /**
                 * Any custom session information that needs to be set on a per-group basis.
                 */
                switch ($ENTRADA_USER->getActiveGroup()) {
                    case "student" :
                        if (!$ENTRADA_USER->getGradYear()) {
                            $_SESSION["details"]["grad_year"] = fetch_first_year();
                        } else {
                            $_SESSION["details"]["grad_year"] = $ENTRADA_USER->getGradYear();
                        }
                    break;
                    case "medtech" :
                        /**
                         * If you're in MEdTech, always assign a graduating year,
                         * because we normally see more than normal users.
                         */
                        $_SESSION["details"]["grad_year"] = fetch_first_year();
                    break;
                    case "staff" :
                    case "faculty" :
                    default :
                        continue;
                    break;
                }

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
