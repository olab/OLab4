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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define(("IN_TRACKS"),	true);

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID, "title" => "Curriculum Tracks");

    if (($router) && ($router->initRoute())) {
        $PREFERENCES = preferences_load($MODULE);

        if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
            $RECORD_ID = $tmp_input;
        } else {
            $RECORD_ID = 0;
        }

        $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."';</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/image/image-upload.css\" />\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.imgareaselect.min.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/imgareaselect-default.css\" />\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/image/image-upload.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">var RESOURCE_ID = ".$RECORD_ID.";</script>";


        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update($MODULE, $PREFERENCES);
    }

}