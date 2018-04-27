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
 *  This file displays a duty hour entry
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
    $entry_id = (isset($_GET["id"])) ? $_GET["id"] : $_POST["dhentry_id"];
    if (isset($entry_id)) {
        $entry = Models_Duty_Hours::fetchRowByID($entry_id);
        if (isset($entry) && $entry != false) {
            $rotation = Models_Course::fetchRowByID($entry->getCourseID());
            $BREADCRUMB[] = array(
                "url" => (
                    ENTRADA_URL . "/clerkship/dutyhours/rotation?id=" . $rotation->getID() .
                    "&cperiod_id=" . $entry->getCurriculumPeriodID()
                ),
                "title" => $rotation->getCourseName()
            );
            $BREADCRUMB[] = array("title" => "View Log Entry");
            echo "<h2>View Duty Hours for " . $rotation->getCourseName() . "</h2>";
            $view_data = [
                "ENTRADA_USER" => $ENTRADA_USER,
                "entry" => $entry
            ];
            $view = new Zend_View();
            $view->data = $view_data;
            $view->setScriptPath(dirname(__FILE__));
            echo $view->render("views/entry.view.php");

        }
    }
}