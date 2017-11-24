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
 * this file updates the document sharing folder order
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$response = [];

/*
 * @todo This is absolutely not right. We need the community_id passed in, and we check for
 * ourselves whether or not this user has the permissions required to re-order folders.
 */
if ($_POST["user_access"] == "3") {
    $admin = true;
} else {
    $admin = false;
}

$folder_order_array = [];

if (isset($_POST["fieldOrder"]) && $_POST["fieldOrder"]) {
    foreach (explode("&", $_POST["fieldOrder"]) as $pair) {
        list($key, $value) = explode("[]=", $pair);

        $folder_order_array[] = (int) $value;
    }
}

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"] && (bool) $admin) {
    if (isset($folder_order_array) && is_array($folder_order_array)) {
        foreach ($folder_order_array as $key => $folder) {
            if (!$folder == 0) {
                $query = "UPDATE `community_shares`
                            SET `folder_order` = " . $db->qstr((int) $key) . "
                            WHERE `cshare_id` = " . $db->qstr((int) $folder);
                if (!$db->Execute($query)) {
                    $errors[] = [
                        "id" => $folder
                    ];
                }
            }
        }
    }
    
    if ($errors) {
        foreach ($errors as $error) {
            application_log("error", "Error updating order folder id: " . $error["id"]);
        }

        $response["errors"] = $errors;
    }

} else {
    $response["errors"] = "Not Authorized";

    application_log("error", "Error reordering folders - Account not authorized.");
}

header("Content-type: application/json");

echo json_encode($response);
