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
 * API to handle interaction with Images.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("course", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }

    if (!$ERROR) { 
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-image" :
                        $image_types = array ("course", "track");
                        $size_types = array ("large", "medium", "small");

                        if (isset($request["resource_id"]) && $tmp_input = clean_input(strtolower($request["resource_id"]), array("trim", "int"))) {
                            $PROCESSED["resource_id"] = $tmp_input;
                        } else {
                            $PROCESSED["resource_id"] = 0;
                        }

                        if (isset($request["resource_type"]) && $tmp_input = clean_input(strtolower($request["resource_type"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$image_types)) {
                                $PROCESSED["resource_type"] = $tmp_input;
                            } else {
                                $PROCESSED["resource_type"] = "course";
                            }
                        } else {
                            $PROCESSED["resource_type"] = "course";
                        }

                        if (isset($request["size"]) && $tmp_input = clean_input(strtolower($request["size"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$size_types)) {
                                $PROCESSED["size"] = $tmp_input;
                            } else {
                                $PROCESSED["size"] = "large";
                            }
                        } else {
                            $PROCESSED["size"] = "large";
                        }

                        if ($PROCESSED["resource_id"] && $PROCESSED["resource_type"]) {
                            $image = Models_Resource_Image::fetchRowByResourceIDResourceType($PROCESSED["resource_id"], $PROCESSED["resource_type"]);

                            if ($image) {

                                if ((@file_exists(STORAGE_RESOURCE_IMAGES."/". $PROCESSED["resource_id"]."-".$PROCESSED["resource_type"]."-".$PROCESSED["size"])) && (@is_readable(STORAGE_RESOURCE_IMAGES."/". $PROCESSED["resource_id"]."-".$PROCESSED["resource_type"]."-".$PROCESSED["size"]))) {

                                    $display_file = STORAGE_RESOURCE_IMAGES."/". $PROCESSED["resource_id"]."-".$PROCESSED["resource_type"]."-".$PROCESSED["size"];

                                    header("Cache-Control: max-age=2592000");
                                    header("Content-Type: ".($image->getImageMimetype() ? $image->getImageMimetype() : "image/jpeg"));
                                    header("Content-Length: ".@filesize($display_file));
                                    header("Content-Disposition: inline; filename=\"".$PROCESSED["resource_id"]."-".$PROCESSED["resource_type"]."-".$PROCESSED["size"].".jpg\"");
                                    header("Content-Transfer-Encoding: binary\n");

                                    echo @file_get_contents($display_file, FILE_BINARY);
                                    exit;
                                } else {
                                    header("Cache-Control: max-age=2592000\n");
                                    header("Content-type: image/jpeg\n");
                                    header("Content-Disposition: filename=course-default.jpg\n");
                                    header("Content-Transfer-Encoding: binary\n");

                                    echo @file_get_contents(ENTRADA_ABSOLUTE."/images/course_default.jpg", FILE_BINARY);
                                    exit;
                                }
                            } else {
                                header("Cache-Control: max-age=2592000\n");
                                header("Content-type: image/jpeg\n");
                                header("Content-Disposition: filename=course-default.jpg\n");
                                header("Content-Transfer-Encoding: binary\n");

                                echo @file_get_contents(ENTRADA_ABSOLUTE."/images/course_default.jpg", FILE_BINARY);
                                exit;
                            }
                        } else {
                            header("Cache-Control: max-age=2592000\n");
                            header("Content-type: image/jpeg\n");
                            header("Content-Disposition: filename=course-default.jpg\n");
                            header("Content-Transfer-Encoding: binary\n");

                            echo @file_get_contents(ENTRADA_ABSOLUTE."/images/course_default.jpg", FILE_BINARY);
                            exit;
                        }
                    break;
                }
            break;
            case "POST" :
                switch ($method) {
                    case "upload-image" :
                        $image_types = array ("course", "track");
                        $size_types = array ("large", "medium", "small");

                        if (isset($request["resource_id"]) && $tmp_input = clean_input(strtolower($request["resource_id"]), array("trim", "int"))) {
                            $PROCESSED["resource_id"] = $tmp_input;
                        } else {
                            $PROCESSED["resource_id"] = 0;
                        }

                        if (isset($request["resource_type"]) && $tmp_input = clean_input(strtolower($request["resource_type"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$image_types)) {
                                $PROCESSED["resource_type"] = $tmp_input;
                            } else {
                                $PROCESSED["resource_type"] = "course";
                            }
                        } else {
                            $PROCESSED["resource_type"] = "course";
                        }

                        if (isset($_POST["dimensions"]) && $_POST["dimensions"]) {
                            $dimensions = explode(",", $_POST["dimensions"]);
                            
                            foreach($dimensions as $dimension) {
                                $tmp_dimensions[] = clean_input($dimension, "int");
                            }

                            $PROCESSED["dimensions"] = implode(",", $tmp_dimensions);
                        }

                        if ($PROCESSED["resource_id"] && $PROCESSED["resource_type"] && $PROCESSED["dimensions"]) {
                            $sizes = array (1200, 600, 100);

                            $percentage = array();

                            foreach ($sizes as $size) {
                                $percentage[] = round($size / $dimensions[0],2);
                            }

                            $sizes = array("large" => array("width" => 1200,
                                "height" => round($dimensions[1] * $percentage[0])),
                                "medium" => array("width" => 600, "height" => round($dimensions[1] * $percentage[1])),
                                "small" => array("width" => 100, "height" => round($dimensions[1] * $percentage[2])),
                            );

                            $filesize = Entrada_Utilities_Image::uploadImage($_FILES["image"]["tmp_name"], $PROCESSED["dimensions"], $PROCESSED["resource_id"] , $PROCESSED["resource_type"], $sizes);

                            if ($filesize) {
                                $PROCESSED_PHOTO["resource_id"]			= $PROCESSED["resource_id"];
                                $PROCESSED_PHOTO["resource_type"]		=  $PROCESSED["resource_type"];
                                $PROCESSED_PHOTO["image_active"]		= 1;
                                $PROCESSED_PHOTO["image_filesize"]		= $filesize;
                                $PROCESSED_PHOTO["updated_date"]		= time();

                                $resource_image = Models_Resource_Image::fetchRowByResourceIDResourceType($PROCESSED_PHOTO["resource_id"], $PROCESSED_PHOTO["resource_type"]);

                                if ($resource_image) {
                                    if (!$resource_image->fromArray($PROCESSED_PHOTO)->update()) {
                                        add_error("An error occurred while inserting image data in DB, please try again later.");
                                        echo json_encode(array("status" => "error"));
                                    } else {
                                        $display_file = ENTRADA_URL . "/admin/courses?section=api-image&method=get-image&resource_type=" . $PROCESSED["resource_type"] . "&resource_id=" . $PROCESSED_PHOTO["resource_id"];
                                        echo json_encode(array("status" => "success", "data" => $display_file));
                                    }
                                } else {
                                    $resource_image = new Models_Resource_Image();
                                    if (!$resource_image->fromArray($PROCESSED_PHOTO)->insert()) {
                                        add_error("An error occurred while inserting image data in DB, please try again later.");
                                        echo json_encode(array("status" => "error"));
                                    } else {
                                        $display_file = ENTRADA_URL . "/admin/courses?section=api-image&method=get-image&resource_type=" . $PROCESSED["resource_type"] . "&resource_id=" . $PROCESSED_PHOTO["resource_id"];
                                        echo json_encode(array("status" => "success", "data" => $display_file));
                                    }
                                }
                            } else {
                                add_error("An error ocurred while moving your image in the system, please try again later.");
                                echo json_encode(array("status" => "error"));
                            }
                        }
                    break;
                }
            break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}
