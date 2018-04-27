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
 * This file displays the duty hours form (to add hours)
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

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

    if (isset($_GET["course_id"]) && isset($_GET["cperiod_id"])) {
        if (Models_Duty_Hours::isUserInCourseAudience(null, $_GET["course_id"])) {
            $rotation = Models_Course::fetchRowByID($_GET["course_id"]);
            if (isset($rotation) && $rotation != false) {
                $HEAD[] = (
                    "<link href=\"" . ENTRADA_URL .
                    "/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />"
                );
                $HEAD[] = (
                    "<link href=\"" . ENTRADA_URL .
                    "/css/dutyhours/style.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\"></script>"
                );
                $HEAD[] = (
                    "<script type=\"text/javascript\" src=\"" . ENTRADA_URL .
                    "/javascript/calendar/config/xc2_default.js\"></script>\n"
                );
                $HEAD[] = (
                    "<script type=\"text/javascript\" src=\"" . ENTRADA_URL .
                    "/javascript/calendar/script/xc2_inpage.js\"></script>\n"
                );
                $HEAD[] = (
                    "<script type=\"text/javascript\" src=\"" . ENTRADA_URL .
                    "/javascript/picklist.js\"></script>\n"
                );
                $HEAD[] = (
                    "<script type=\"text/javascript\" src=\"" . ENTRADA_URL .
                    "/javascript/jquery/jquery.timepicker.js?release=" .
                    html_encode(APPLICATION_VERSION) . "\"></script>"
                );
                $HEAD[] = (
                    "<script type=\"text/javascript\" src=\"" . ENTRADA_RELATIVE .
                    "/javascript/jquery/jquery.moment.min.js?release=" .
                    html_encode(APPLICATION_VERSION) . "\"></script>\n"
                );
                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var USER_ID = '" . $ENTRADA_USER->getID() . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var COURSE_ID = '" . $rotation->getID() . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var CPERIOD_ID = '" . $_GET['cperiod_id'] . "';</script>";
                $HEAD[] = (
                    "<script src=\"" . ENTRADA_URL .
                    "/javascript/dutyhours/dutyhours.js\" type=\"text/javascript\"></script>"
                );

                $BREADCRUMB[] = array(
                    "url" => (
                        ENTRADA_URL .
                        "/clerkship/dutyhours/rotation?id=" .
                        $rotation->getID() .
                        "&cperiod_id=" .
                        $_GET["cperiod_id"]
                    ),
                    "title" => $rotation->getCourseName()
                );

                $BREADCRUMB[] = array("title" => "Add Log Entry");

                echo "<h2>Add Duty Hours for " . $rotation->getCourseName() . "</h2>";

                $view_data = [
                    "ENTRADA_USER" => $ENTRADA_USER,
                    "course_id" => $rotation->getID(),
                    "cperiod_id" => $_GET["cperiod_id"]
                ];

                $view = new Zend_View();
                $view->data = $view_data;
                $view->setScriptPath(dirname(__FILE__));

                echo $view->render("views/form.view.php");

            } else {
                $ONLOAD[] = "setTimeout(\"window.location=\"" . ENTRADA_URL . "\", 15000)";
                $ERROR++;
                $ERRORSTR[] = "Course " . $_GET["course_id"] . " does't exists.";
                echo display_error();
            }
        } else {
            $ERROR++;
            $ERRORSTR[] = (
                "Your account does not have the permissions required to use this feature of this module.<br />" .
                "<br />If you believe you are receiving this message in error please contact <a href=\"mailto:" .
                html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" .
                html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance."
            );
            echo display_error();
        }
    }else{
        $ERROR++;
        $ERRORSTR[] = (
            "Your account does not have the permissions required to use this feature of this module.<br />" .
            "<br />If you believe you are receiving this message in error please contact <a href=\"mailto:" .
            html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" .
            html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance."
        );
        echo display_error();
    }
}