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
 *
 * @author Organisation: Univerity of Calgary
 * @author Unit: Cumming School of Medicine
 * @author Developer: Corey Wirun
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_OLAB"))) {

	exit;

} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {

    header("Location: ".ENTRADA_URL);
    exit;

} else {

    $fullPath = "/var/www/html/olab/www-root/core/storage/olab/1/1063/26350/CallahanMetaMotivation2.pdf";

    if ($fd = fopen ($fullPath, "r")) {

        $fsize = filesize($fullPath);
        $path_parts = pathinfo($fullPath);
        $ext = strtolower($path_parts["extension"]);
        switch ($ext) {
            case "pdf":
                header("Content-type: application/pdf");
                header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
                break;
            // add more headers for other content types here
            default;
                header("Content-type: application/octet-stream");
                header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
                break;
        }

        header("Content-length: " . $fsize);
        header("Cache-control: private"); //use this to open files directly
        while(!feof($fd)) {
            $buffer = fread($fd, 2048);
            echo $buffer;
        }
    }

    fclose ($fd);

}
?>



