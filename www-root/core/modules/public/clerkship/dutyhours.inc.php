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
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_CLERKSHIP")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("dutyhours", "read")) {

    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br />" .
        "<br />If you believe you are receiving this message in error please contact <a href=\"mailto:" .
        html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" .
        html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";

    echo display_error();

    application_log(
        "error",
        "Group [" . $GROUP . "] and role [" . $ROLE . "] do not have access to this module [" . $MODULE . "]"
    );

} else {
    $USER_ID = (isset($PROXY_ID) && (int)$PROXY_ID ? $PROXY_ID : 0);

    $BREADCRUMB[] = array(
        "url" => ENTRADA_URL . "/clerkship/dutyhours" . ($USER_ID ? "?id=" . $USER_ID : ""),
        "title" => "Duty Hours"
    );
    if (($router) && ($router->initRoute())) {
        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }
    } else {
        $url = ENTRADA_URL . "/" . $MODULE;
        application_log(
            "error",
            "The Entrada_Router failed to load a request. The user was redirected to [" . $url . "]."
        );

        header("Location: " . $url);
        exit;
    }
}