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
 * A class to manage the Image Uploading.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Image extends Entrada_Base {

    public static function uploadImage($source, $dimensions, $id, $resource_type, $sizes = array("upload" => array("width" => 192, "height" => 250), "upload-thumbnail" => array("width" => 75, "height" => 98))) {
        $dimensions = explode(",", $dimensions);

        if ($id) {
            $image_details = getimagesize($source);

            $image = false;

            switch($image_details["mime"]) {
                case "image/jpeg" :
                    $image = imagecreatefromjpeg($source);
                    break;
                case "image/gif" :
                    $image = imagecreatefromgif($source);
                    break;
                case "image/png" :
                    $image = imagecreatefrompng($source);
                    break;
                default:
                    application_log("error", "An unsupported mimetype [" . $image_details["mime"] . "] was encountered when trying to upload an image.");
                    break;
            }

            if ($image) {
                if (defined("STORAGE_RESOURCE_IMAGES") && is_dir(STORAGE_RESOURCE_IMAGES) && is_writable(STORAGE_RESOURCE_IMAGES)) {
                    copy($source, STORAGE_RESOURCE_IMAGES . "/" . $id . "-" . $resource_type . "-original");

                    foreach ($sizes as $size_name => $size) {
                        $resized_image = imagecreatetruecolor($size["width"], $size["height"]);
                        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $size["width"], $size["height"], $dimensions[0], $dimensions[1]);
                        $scaled_image = CACHE_DIRECTORY . "/resource-img-" . $id . "-" . $size["width"] . "x" . $size["height"] . ".png";
                        imagepng($resized_image, $scaled_image);
                        if (copy($scaled_image, STORAGE_RESOURCE_IMAGES . "/" . $id . "-" . $resource_type . "-" . $size_name)) {
                            unlink($scaled_image);
                        } else {
                            application_log("error", "Unable to copy [" . $scaled_image . "] to [" . STORAGE_RESOURCE_IMAGES . "] directory.");
                        }
                    }

                    if (is_file(STORAGE_RESOURCE_IMAGES . "/" . $id . "-" . $resource_type . "-large")) {
                        return filesize(STORAGE_RESOURCE_IMAGES . "/" . $id . "-" . $resource_type . "-large");
                    }
                } else {
                    application_log("error", "The STORAGE_RESOURCE_IMAGES directory is either not defined, does not exist, or is not writable by PHP.");
                }
            }
        }

        return false;
    }
}
