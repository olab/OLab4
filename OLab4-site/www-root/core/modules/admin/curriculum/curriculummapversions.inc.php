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
 * @author Developer: Matt Simpson <simpson@queensu.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_CURRICULUM_MAP_VERSIONS",	true);
    if ($router && $router->initRoute()) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION["organisation_id"], "title" => $translate->_("Curriculum Map Versions"));

        $PREFERENCES = preferences_load($MODULE);

        /**
         * See if we are working with an existing Grading Scale
         */
        if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("notags", "trim", "int")))) {
            $VERSION_ID = $tmp_input;
        }

        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /**
         * Check if preferences need to be updated on the server at this point.
         */
        preferences_update($MODULE, $PREFERENCES);
    } else {
        $url = ENTRADA_URL."/admin/".$MODULE;
        application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

        header("Location: ".$url);
        exit;
    }
}
