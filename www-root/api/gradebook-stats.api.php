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
 * Loads the stats for the selected module
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

ob_clear_open_buffers();

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    add_error("It appears as though your session has expired; you will now be taken back to the login page.");
    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    exit;
} else {

    if((isset($_GET["assessment_id"])) && $tmp_input = clean_input($_GET["assessment_id"])) {
        $PROCESSED["assessment_id"] = $tmp_input;
    }

    if($PROCESSED["assessment_id"]) {
        if ($ENTRADA_ACL->amIAllowed(new GradebookResource($PROCESSED["assessment_id"], $result["course_id"], $result["organisation_id"]), "update")) {
            $assessment_statistics = Models_Statistic::getGradebookViews($PROCESSED["assessment_id"]);
            
            if ($assessment_statistics) {
                foreach ($assessment_statistics as $assessment_statistic) {
                    $output[] = array(
                        $assessment_statistic["lastname"] . ", " . $assessment_statistic["firstname"],
                        $assessment_statistic["views"],
                        $assessment_statistic["first_viewed_time"],
                        $assessment_statistic["last_viewed_time"]
                    );
                }
                echo json_encode(array("status" => "success", "data" => $output));
            } else {
                echo json_encode(array("status" => "success", "data" => array("This assessessment has not been viewed")));
            }            
        } else {
            add_error("Your account does not have the permissions required to use this feature of this module. If you believe you are receiving this message in error please contact us for assistance.");

            echo json_encode(array("status" => "error", "data" => $ERRORSTR));

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to the file wizard.");            
        }
    } else {
        add_error("The provided assessment identifier does not exist in this system.");
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));

        application_log("error", "File wizard was accessed without a valid assessment id.");
    }
    
}

exit;