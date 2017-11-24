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
 * Serve learning object files to users.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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

if((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {

    ob_clear_open_buffers();
    
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }
    
    if (isset($_GET["thumbnail"]) && $tmp_input = clean_input($_GET["thumbnail"], "int")) {
        $PROCESSED["thumbnail"] = 1;
    }
    
    if ($PROCESSED["id"]) {
        
        $lo_file = Models_LearningObject::fetchRowByID($PROCESSED["id"]);
        
        if ($lo_file) {
            $file_realpath = LOR_STORAGE_PATH . "/" . $lo_file->getProxyID() . "/" . $PROCESSED["id"];
            if ($PROCESSED["thumbnail"]) {
                if (!is_dir(LOR_STORAGE_PATH . "/" . $lo_file->getProxyID() . "/thumbnails/")) {
                    mkdir(LOR_STORAGE_PATH . "/" . $lo_file->getProxyID() . "/thumbnails/");
                }
                $thumbnail_realpath = LOR_STORAGE_PATH . "/" . $lo_file->getProxyID() . "/thumbnails/" . $PROCESSED["id"];   
            }

            if (file_exists($file_realpath)) {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file_realpath);
                finfo_close($finfo);

                $image_types = array("image/jpeg", "image/png", "image/gif");
                $file_extensions = array("ppt", "pptx", "doc", "docx", "xls", "xlsx");
                $file_extension = substr($lo_file->getFilename(), strripos($lo_file->getFilename(), ".") + 1, strlen($lo_file->getFilename()));

                if (isset($PROCESSED["thumbnail"]) && in_array($mime_type, $image_types)) {
                    if (file_exists($thumbnail_realpath)) {
                        $file_realpath = $thumbnail_realpath;
                        $mime_type = "image/png";
                    } else {
                        if (Models_LearningObject::generateLearningObjectThumbnail($file_realpath, $mime_type)) {
                            $file_realpath = $thumbnail_realpath;
                            $mime_type = "image/png";
                        }
                    }
                } else if (isset($PROCESSED["thumbnail"]) && in_array($file_extension, $file_extensions)) {
                    switch ($file_extension) {
                        case "ppt" :
                        case "pptx" :
                            $file_realpath = ENTRADA_URL . "/images/ppt-thumb-150x150.png";
                        break;
                        case "doc" :
                        case "docx" :
                            $file_realpath = ENTRADA_URL . "/images/word-thumb-150x150.png";
                        break;
                        case "xls" :
                        case "xlsx" :
                            $file_realpath = ENTRADA_URL . "/images/excel-thumb-150x150.png";
                        break;
                        default :
                            $file_realpath = ENTRADA_URL . "/images/unknown-thumb-150x150.png";
                        break;
                    }
                    $mime_type = "image/png";
                } else if (isset($PROCESSED["thumbnail"])) {
                    $file_realpath = ENTRADA_URL . "/images/unknown-thumb-150x150.png";
                    $mime_type = "image/png";
                }

                switch ($mime_type) {
                    case "image/jpeg":
                        header('Content-Type: image/jpeg');
                        $image = imagecreatefromjpeg($file_realpath);
                        imagejpeg($image);
                    break;
                    case "image/png":
                        header('Content-Type: image/png');

                        $image      = imagecreatefrompng($file_realpath);
                        $background = imagecolorallocate($image, 0, 0, 0);

                        imagecolortransparent($image, $background);
                        imagealphablending($image, false);
                        imagesavealpha($image, true);

                        imagepng($image);
                    break;
                    case "image/gif":
                        header('Content-Type: image/gif');

                        $image      = imagecreatefromgif($file_realpath);
                        $background = imagecolorallocate($image, 0, 0, 0);

                        imagecolortransparent($image, $background);

                        imagegif($image);
                    break;
                    default:
                        header('Content-Description: File Transfer');
                        header('Content-Type: '.$mime_type);
                        header('Content-Disposition: attachment; filename='.basename(str_replace(" ", "_", $lo_file->getFilename())));
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file_realpath));

                        ob_clear_open_buffers();

                        readfile($file_realpath);

                        exit;
                    break;
                }

                if (isset($image)) {
                    imagedestroy($image);
                }

            }
        
        }
        
    }
    
    exit;
    
}