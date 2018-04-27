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
 * This file lists the logged hours of a clerkship course
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

//← change to 0 to turn off
ini_set("display_errors", "0");
error_reporting(E_ERROR);

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

    $course_id = (isset($_GET["id"])) ? $_GET["id"] : $_POST["course_id"];
    $cperiod_id = (isset($_GET["cperiod_id"])) ? $_GET["cperiod_id"] : $_POST["cperiod_id"];

    if (isset($course_id) && isset($cperiod_id)) {
        $rotation = Models_Course::fetchRowByID($course_id);
        if (isset($rotation)) {
            $HEAD[] = (
                "<script type=\"text/javascript\" src=\"" .
                ENTRADA_RELATIVE . "/javascript/jquery/jquery.moment.min.js?release=" .
                html_encode(APPLICATION_VERSION) . "\"></script>\n"
            );
            $HEAD[] = (
                "<script type=\"text/javascript\" src=\"" .
                ENTRADA_RELATIVE . "/javascript/jquery/jquery.fullcalendar.min.js?release=" .
                html_encode(APPLICATION_VERSION) . "\"></script>\n"
            );
            $HEAD[] = (
                "<script type=\"text/javascript\" src=\"" .
                ENTRADA_RELATIVE . "/javascript/calendar/config/xc2_default.js?release=" .
                html_encode(APPLICATION_VERSION) . "\"></script>\n"
            );
            $HEAD[] = (
                "<script type=\"text/javascript\" src=\"" .
                ENTRADA_RELATIVE . "/javascript/calendar/script/xc2_inpage.js?release=" .
                html_encode(APPLICATION_VERSION) . "\"></script>\n"
            );
            $HEAD[] = (
                "<link href=\"" . ENTRADA_RELATIVE .
                "/css/jquery/jquery.fullcalendar.min.css?release=" .
                html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n"
            );
            $HEAD[] = (
                "<link href=\"" . ENTRADA_RELATIVE .
                "/css/jquery/jquery.fullcalendar.print.css?release=" .
                html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"print\" />\n"
            );
            $HEAD[] = (
                "<link href=\"" . ENTRADA_RELATIVE .
                "/javascript/calendar/css/xc2_default.css?release=" .
                html_encode(APPLICATION_VERSION) . "\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n"
            );
            $HEAD[] = (
                "<script type=\"text/javascript\" src=\"" . ENTRADA_URL .
                "/javascript/jquery/jquery.dataTables.min.js\"></script>"
            );
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
            $HEAD[] = "<script type=\"text/javascript\">var USER_ID = '" . $ENTRADA_USER->getID() . "';</script>";
            $HEAD[] = "<script type=\"text/javascript\">var COURSE_ID = '" . $course_id . "';</script>";
            $HEAD[] = "<script type=\"text/javascript\">var CPERIOD_ID = '" . $cperiod_id . "';</script>";
            $HEAD[] = (
                "<script src=\"" . ENTRADA_URL .
                "/javascript/dutyhours/dutyhours.js\" type=\"text/javascript\"></script>"
            );

            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/clerkship/dutyhours/rotations?id=" . $rotation->getID(),
                "title" => $rotation->getCourseName());


            $log_entries = Models_Duty_Hours::fetchRecordsByCourse(
                $ENTRADA_USER->getID(), $rotation->getID(), $cperiod_id
            );
            $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);
            $can_edit = ($cperiod->getStartDate() <= time() && $cperiod->getFinishDate() >= time());

            // logged hours for this rotation
            $view_data = [
                "ENTRADA_USER" => $ENTRADA_USER,
                "course_id" => $course_id,
                "cperiod_id" => $cperiod_id,
                "can_edit" => $can_edit,
                "entries" => $log_entries
            ];

            $cal_view = new Zend_View();
            $cal_view->data = $view_data;
            $cal_view->setScriptPath(dirname(__FILE__));
            echo $cal_view->render("views/calendar.view.php");

            $view = new Zend_View();
            $view->data = $view_data;
            $view->setScriptPath(dirname(__FILE__));
            echo $view->render("views/rotation_log.view.php");
        }
    }
}

