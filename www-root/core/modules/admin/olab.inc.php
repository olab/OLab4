<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("olab", "update", false)) {
    add_error("Your account does not have the permissions required to use this module.");

    echo display_error();

    application_log("error", "Group [" . $ENTRADA_USER->getActiveGroup() . "] and role [" . $ENTRADA_USER->getActiveRole() . "] do not have access to this module [" . $MODULE . "]");
} else {
    /*
     * Adds a breadcrumb to the breadcrumb trail.
     */
    $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/admin/olab", "title" => $translate->_("Sandbox Admin"));

    /*
     * More information on our global namespace is documented http://docs.entrada-project.org/developer/namespace/
     * Some commonly used ones include $HEAD[] and $ONLOAD[].
     */

    /*
     * Renders the sandbox sidebar View Helper.
     */
    $sidebar = new Views_OLab_Sidebar();
    $sidebar->render();

    if ($router && $router->initRoute()) {
        /*
         * Loads user specific preferences for this module that are persistent between logins. This information
         * is stored in the entrada_auth.user_preferences table. Any preferences that are changed by the user are
         * updated below by the preferences_update() function.
         */
        $PREFERENCES = preferences_load($MODULE);

        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        /*
         * This checks to see if any preferences have been changed, and updates them within the
         * entrada_auth.user_preferences table as needed.
         */
        preferences_update($MODULE, $PREFERENCES);
    }
}
