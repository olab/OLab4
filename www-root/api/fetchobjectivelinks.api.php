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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    /**
     * Clears all open buffers so we can return a simple REST response.
     */
    ob_clear_open_buffers();
    
    $objective_id   = (int) $_GET["objective_id"];
    $org_id         = (int) (isset($_GET["org_id"]) ? $_GET["org_id"] : (isset($ENTRADA_USER) && $ENTRADA_USER->getActiveOrganisation() ? $ENTRADA_USER->getActiveOrganisation() : false));
    $cmapversion_id = (int) isset($_GET["cmapversion_id"]) ? $_GET["cmapversion_id"] : 0;

    $PREFERENCES = preferences_load("settings");
    $_SESSION[APPLICATION_IDENTIFIER]["settings"]["selected_curriculum_map_version"] = $cmapversion_id;
    preferences_update("settings", $PREFERENCES);
    $linked_objectives = Models_Objective::fetchObjectivesMappedTo($objective_id, $cmapversion_id);

    if ($linked_objectives) {
        $obj_array = array();
        foreach($linked_objectives as $objective){
            $fields = array('objective_id'=>$objective["objective_id"],
                            'objective_code'=>$objective["objective_code"],
                            'objective_name'=>$objective["objective_name"],
                            'objective_description'=>$objective["objective_description"]
            );
            
            $parent = Entrada_Curriculum_Explorer::fetch_objective_parents($objective["objective_id"]);
            if ($parent) {
                $fields["parent_objective"] = $parent["parent"]["objective_name"];
            }
            $obj_array[] = $fields;
        }
        echo json_encode(array("status" => "success", "data" => $obj_array));
    } else {
        echo json_encode(array("status" => "empty", "data" => "This curriculum tag is not currently linked to any other tags."));
    }
    
    exit;
}