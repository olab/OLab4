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
 * this file loads the views for the event sorted different way
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
if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {

    if (isset($_POST["action"]) && $_POST["action"] != "") {
        $action = $_POST["action"];
    }
    
    if (isset($_POST["action_field"]) && $_POST["action_field"] != "") {
        $action_field = $_POST["action_field"];
    }
    
    if (isset($_POST["action_value"]) && $_POST["action_value"] != "") {
        $action_value = $_POST["action_value"];
    }
    
    if (isset($_POST["module"]) && $_POST["module"] != "") {
        $module = $_POST["module"];
    }
    
    $html = "";
        
    switch ($action) {
        case "link_view":
            $views = Models_Statistic::getCommunityLinkViews($module, $action_value);
            break;
        case "folder_view":
            $views = Models_Statistic::getCommunityFolderViews($module, $action_value);
            break;
        case "file_download":
        default:
            $views = Models_Statistic::getCommunityFileDownloads($module, $action_value);
            break;
        
    }
    
    if ($views) {
        foreach ($views as $view) {
            $output[] = array(
                $view["lastname"] . ", " . $view["firstname"],
                $view["views"],
                date("Y-m-d H:i", $view["last_viewed_time"])

            );
        }
        $export = array("status" => "success", "data" => $output);
    } else {
        $export = array("status" => "success", "data" => array("This item has not been viewed"));
    }
     
    header("Content-type: application/json");
    echo json_encode($export);
}
?>