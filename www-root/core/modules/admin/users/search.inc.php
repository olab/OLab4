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
 * Used by the add / edit users sections to search for a number / username
 * that exists in the database already. This file outputs JSON data only, and
 * does not use any templates.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Clears all open buffers so we can return a simple JSON response.
	 */
	ob_clear_open_buffers();
    
    $field = $value = false;

	if (isset($_GET["number"])) {
		$field = "number";
        $value = clean_input($_GET["number"], array("int"));
	} elseif (isset($_GET["username"])) {
        $field = "username";
        $value = clean_input($_GET["username"], array("credentials"));
    } elseif (isset($_GET["id"])) {
        $field = "id";
        $value = clean_input($_GET["id"], array("int"));
    }

    if ($field && $value) {
        $query = "	SELECT a.*, b.`account_active`
					FROM `".AUTH_DATABASE."`.`user_data` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
					ON a.`id` = b.`user_id`
					AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					WHERE `".$field."` = ".$db->qstr($value);

        $result = $db->GetRow($query);

        if ($result) {
            $result["message"] = "<ul><li>A user with that " . $field . " has been found in the system. Please click <strong><a class=\"add-another-user\">here</a></strong> to add another user or <strong><a href=\"" . ENTRADA_URL . "/admin/users/manage?section=edit&id=" . $result["id"] . "\">here</a></strong> to edit this user.</li></ul>";

            header("Content-type: application/json");
            echo json_encode($result);
        }
    }

	exit;
}