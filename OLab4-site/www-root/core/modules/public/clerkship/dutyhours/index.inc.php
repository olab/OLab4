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
 * This file displays the edit entry interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

global $translate;

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("dutyhours", "read")) {
    $ONLOAD[] = "setTimeout(\"window.location=\"" . ENTRADA_URL . "/admin/" . $MODULE . "\", 15000)";

    $ERROR++;
    $ERRORSTR[] = ("Your account does not have the permissions required to use this feature of this module.<br />" .
        "<br />If you believe you are receiving this message in error please contact <a href=\"mailto:" .
        html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" .
        html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    $log_message = (
        "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] .
        "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] .
        "] does not have access to this module [" . $MODULE . "]"
    );

    application_log(
        "error",
        $log_message
    );

} else {

    echo "<h1>" . $translate->_("Duty Hours") . "</h1>";

    $rotations = Models_Duty_Hours::getUserClerkshipCourses();
    if ($rotations) {
        $rotations_view_data = array();
        foreach ($rotations as $rotation) {
            $r_view_data["id"] = $rotation["course_id"];
            $r_view_data["cperiod_id"] = $rotation["cperiod_id"];
            $r_view_data["name"] = $rotation["course_name"];
            $r_view_data["logged_hours"] = Models_Duty_Hours::getDutyHoursForCourse(
                $ENTRADA_USER->getID(), $rotation["course_id"], $rotation["cperiod_id"]
            );
            $r_view_data["hours_per_week"] = Models_Duty_Hours::getAverageDutyHoursPerWeek(
                $ENTRADA_USER->getID(), $rotation["course_id"], $rotation["cperiod_id"]
            );
            $r_view_data["logged_hours_this_week"] = Models_Duty_Hours::getLoggedHoursThisWeek(
                $ENTRADA_USER->getID(), $rotation["course_id"], $rotation["cperiod_id"]
            );
            $cperiod = Models_Curriculum_Period::fetchRowByID($rotation["cperiod_id"]);
            $r_view_data["can_edit"] = ($cperiod->getStartDate() <= time() && $cperiod->getFinishDate() >= time());
            $rotations_view_data[] = $r_view_data;
        }
        $view_data = [
            "rotations" => $rotations_view_data
        ];
        $view = new Zend_View();
        $view->data = $view_data;
        $view->setScriptPath(dirname(__FILE__));
        echo $view->render("views/rotations.view.php");
    } else {
        $ONLOAD[] = "setTimeout(\"window.location=\"" . ENTRADA_URL . "\", 15000)";
        $ERROR++;
        $ERRORSTR[] = "You have not enrolled in any clerkship courses.";

        echo display_error();
    }
}
