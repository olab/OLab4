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
 * this file loads the open folders
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

$js_array = false;

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $community_id = 0;
    $page_id = 0;

    if (isset($_POST["community_id"]) && ($tmp_input = clean_input($_POST["community_id"], "int"))) {
        $community_id = $tmp_input;
    }        

    if (isset($_POST["page_id"]) && ($tmp_input = clean_input($_POST["page_id"], "int"))) {
        $page_id = $tmp_input;
    }        

    if ($community_id && $page_id) {
        $query = "SELECT *
                    FROM `community_shares_open`
                    WHERE `community_id` = " . $db->qstr($community_id) . "
                    AND `page_id` = " . $db->qstr($page_id) . "
                    AND `proxy_id` = " . $db->qstr($ENTRADA_USER->getID());
        $results = $db->GetAll($query);
        if ($results) {
            if (isset($results[0]["shares_open"]) && $results[0]["shares_open"]) {
                $opened_folders = explode(",", $results[0]["shares_open"]);
                if ($opened_folders) {
                    $js_array = json_encode($opened_folders);
                }
            }
        }
    }
}

if (!$js_array) {
    $js_array = json_encode([0 => 0]);
}

echo $js_array;
