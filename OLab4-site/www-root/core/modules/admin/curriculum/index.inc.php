<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("curriculum", "read", false)) {
    add_error("Your account does not have the permissions required to use this module.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    /*
     * Updates the <title></title> of the page.
     */
    $PAGE_META["title"] = $translate->_("Dashboard");

    /*
     * Adds a breadcrumb to the breadcrumb trail.
     */
    $BREADCRUMB[] = array("title" => "Dashboard");
    ?>

    <h1><?php echo $translate->_("Dashboard"); ?></h1>

    <div class="alert alert-info">
        This Curriculum Dashboard will be replaced with the information before the end of this cycle.
    </div>
    <?php
}