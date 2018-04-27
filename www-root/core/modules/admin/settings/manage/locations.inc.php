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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2014 David Geffen School of Medicine at UCLA. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool)$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read")) {
    add_error($translate->_("Your account does not have the permissions required to use this module."));

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PAGE_META["title"] = $translate->_("Location Management");
    $PAGE_META["description"] = "";
    $PAGE_META["keywords"] = "";

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/locations?org=".$ORGANISATION['organisation_id'], "title" => $translate->_("Location Management"));
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/locations?org=".$ORGANISATION['organisation_id'], "title" => "Sites");

    $JAVASCRIPT_TRANSLATIONS[] = "let organisation_id = " . $ORGANISATION['organisation_id'] . ";";
    // These are temporary translations for the Locations module until the proper entradajs way is ready
    $JAVASCRIPT_TRANSLATIONS[] = "let global_translations = {};";
    $JAVASCRIPT_TRANSLATIONS[] = "global_translations.Province = '" . html_encode($translate->_("Province / State")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "global_translations.PostalCode = '" . html_encode($translate->_("Postal Code")) . "';";
    ?>

    <!-- EntradaJS Entry Point -->
    <div id="app-root" data-route="locations.index" data-layout="NoComponentsLayout"></div>

    <?php
}
