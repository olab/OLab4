<?php
/**
 * Entrada [ https://entrada.org ]
 *
 * @author Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool)$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("sandbox", "update")) {
    add_error($translate->_("Your account does not have the permissions required to use this module."));

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PAGE_META["title"] = "Admin Side: Sandbox";
    $PAGE_META["description"] = "";
    $PAGE_META["keywords"] = "";

    $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/admin/sandbox", "title" => "Admin Side: Sandbox");

    $sidebar = new Views_Sandbox_Sidebar();
    $sidebar->render();
    ?>

    <!-- EntradaJS Entry Point -->
    <div id="app-root" data-route="sandbox.index"></div>

    <?php
}
