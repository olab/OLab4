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
 * API for uploading user photos.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false) && !$ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    global $translate, $db;
    ob_clear_open_buffers();

    // Process posted values.
    if (isset($_POST["coordinates"]) && $tmp_input = clean_input($_POST["coordinates"], array("trim"))) {
        $PROCESSED["coordinates"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid coordinates."));
    }
    if (isset($_POST["dimensions"]) && $tmp_input = clean_input($_POST["dimensions"], array("trim"))) {
        $PROCESSED["dimensions"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid dimensions."));
    }
    if (isset($_POST["proxy_id"]) && $tmp_input = clean_input($_POST["proxy_id"], array("trim", "int"))) {
        $PROCESSED["proxy_id"] = $tmp_input;
    } else {
        add_error($translate->_("Invalid proxy id."));
    }

    if (has_error()) {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    } else {

        $sizes = array("upload" => array("width" => 250, "height" => 250), "upload-thumbnail" => array("width" => 98, "height" => 98));

        $filesize = moveImage($_FILES["image"]["tmp_name"], $PROCESSED["proxy_id"], $PROCESSED["coordinates"], $PROCESSED["dimensions"], "user", $sizes);

        if ($filesize) {
            $PROCESSED_PHOTO = array(
                "proxy_id" => $PROCESSED["proxy_id"],
                "photo_mimetype" => "image/jpeg",
                "photo_active" => 1,
                "photo_type" => 1,
                "updated_date" => time(),
                "photo_filesize" => $filesize
            );

            $existing_photo = Models_User_Photo::get($PROCESSED["proxy_id"], "UPLOADED");

            if ($existing_photo) {
                $PROCESSED_PHOTO["photo_id"] = $existing_photo->getID();
                if ($existing_photo->fromArray($PROCESSED_PHOTO)->update()) {
                    echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($PROCESSED["proxy_id"], "upload")) . "/" . time()));
                } else {
                    echo json_encode(array("status" => "error"));
                }
            } else {
                $new_photo = new Models_User_Photo($PROCESSED_PHOTO);
                if ($new_photo->insert()) {
                    echo json_encode(array("status" => "success", "data" => webservice_url("photo", array($PROCESSED["proxy_id"], "upload")) . "/" . time()));
                } else {
                    echo json_encode(array("status" => "error"));
                }
            }
        }
    }

    exit;
}

