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
 * this file updates the parent folder fields to move folders
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

/**
 * This file needs some love. I just cleaned it up a bit the front end JS side needs to be
 * updated as well.
 */

$response = [];
$errors = [];

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $is_community_admin = false;

    $community_id = 0;
    $moved_folders = [];
    $moved_files = [];

    if (isset($_POST["community_id"]) && ($tmp_input = clean_input($_POST["community_id"], "int"))) {
        $community_id = $tmp_input;

        $membership = Models_Community_Member::fetchRowByProxyIDCommunityID($ENTRADA_USER->getID(), $community_id);
        if ($membership && ($membership->getMemberACL() == 1)) {
            $is_community_admin = true;
        }
    }

    if (isset($_POST["movedFolders"]) && is_array($_POST["movedFolders"]) && $_POST["movedFolders"]) {
        $moved_folders = $_POST["movedFolders"];
    }

    if (isset($_POST["movedFiles"]) && is_array($_POST["movedFiles"]) && $_POST["movedFiles"]) {
        $moved_files = $_POST["movedFiles"];
    }

    if ($community_id && $is_community_admin && ($moved_folders || $moved_files)) {
        if ($moved_folders) {
            foreach ($moved_folders as $folder) {
                $query = "UPDATE `community_shares`
                            SET `parent_folder_id` = " . $db->qstr((int) $folder["destinationFolder"]) . "
                            WHERE `community_id` = " . $db->qstr($community_id) . "
                            AND `cshare_id` = " . $db->qstr((int) $folder["folderMoved"]);
                if (!$db->Execute($query)) {
                    $errors[] = [
                        "type" => "folder",
                        "community_id" => $community_id,
                        "id" => $folder["folderMoved"]
                    ];
                }
            }
        }   

        if ($moved_files) {
            foreach ($moved_files as $file) {
                if ($file["type"] == "file") {
                    $query = "UPDATE `community_share_files`
                                SET `cshare_id` = " . $db->qstr((int) $file["destinationFolder"]) . "
                                WHERE `community_id` = " . $db->qstr($community_id) . "
                                AND `csfile_id` = " . $db->qstr((int) $file["id_moved"]);

                    $query_file_versions = "UPDATE `community_share_file_versions`
                                            SET `cshare_id` = " . $db->qstr((int) $file["destinationFolder"]) . "
                                            WHERE  `community_id` = " . $db->qstr((int) $community_id) . "
                                            AND `csfile_id` = " . $db->qstr((int) $file["id_moved"]);
                } else if ($file["type"] == "link") {
                    $query = "UPDATE `community_share_links`
                                SET `cshare_id` = " . $db->qstr((int) $file["destinationFolder"]) . "
                                WHERE `community_id` = " . $db->qstr((int) $community_id) . "
                                AND `cslink_id` = " . $db->qstr((int) $file["id_moved"]);
                } else if ($file["type"] == "html") {
                    $query = "UPDATE `community_share_html`
                                SET `cshare_id` = " . $db->qstr((int) $file["destinationFolder"]) . "
                                WHERE  `community_id` = " . $db->qstr((int) $community_id) . "
                                AND `cshtml_id` = " . $db->qstr((int) $file["id_moved"]);
                }

                if (!$db->Execute($query)) {
                    $errors[] = [
                        "type" => $file["type"],
                        "community_id" => $community_id,
                        "id" => $file["id_moved"]
                    ];
                }

                if ($query_file_versions) {
                    if (!$db->Execute($query_file_versions)) {
                        $errors[] = [
                            "type" => $file["type"],
                            "community_id" => $community_id,
                            "id" => $file["id_moved"]
                        ];
                    }
                }
            }
        }

        if ($errors) {
            foreach ($errors as $error) {
                switch ($error["type"]) {
                    case "folder":
                        application_log("error", "Error moving folder id: " . $folder["folderMoved"] . " to folder: " . $folder["destinationFolder"]);
                        break;
                    case "file":
                        application_log("error", "Error moving file id: " . $file["id_moved"] . " to folder: " . $file["destinationFolder"]);
                        break;
                    case "link":
                        application_log("error", "Error moving link id: " . $file["id_moved"] . " to folder: " . $file["destinationFolder"]);
                        break;                        
                    case "html":
                        application_log("error", "Error moving html id: " . $file["id_moved"] . " to folder: " . $file["destinationFolder"]);
                        break;
                }
            }

            $response["errors"] = $errors;
        }
    }
} else {
    $response["errors"] = "Not Authorized";

    application_log("error", "Error moving folders - Account not authorized");
}

header("Content-type: application/json");
echo json_encode($response);
