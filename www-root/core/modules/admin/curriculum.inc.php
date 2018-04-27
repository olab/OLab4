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
    if (!$LEARNER_VIEW) {
        $BREADCRUMB[] = array("url" => ENTRADA_RELATIVE . "/admin/curriculum", "title" => $translate->_("Manage Curriculum"));
    }
    
    if ($router && $router->initRoute()) {
        $PREFERENCES = preferences_load($MODULE);

        // @todo This is here temporarily until we clean up the internal modules.
        $ORGANISATION_ID = $ENTRADA_USER->getActiveOrganisation();

        $sidebar_html  = "<ul class=\"menu\">";
        $sidebar_html .= "	<li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum\">" . $translate->_("Dashboard") . "</a></li>\n";
        if ($ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
            $sidebar_html .= "	<li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculumtypes?org=" . $ENTRADA_USER->getActiveOrganisation() . "\">" . $translate->_("Curriculum Layout") . "</a></li>\n";
            $sidebar_html .= "	<li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculumtracks?org=" . $ENTRADA_USER->getActiveOrganisation() . "\">" . $translate->_("Curriculum Tracks") . "</a></li>\n";
            //$sidebar_html .= "	<li class=\"link\"><a href=\"#\">" . $translate->_("Curriculum Maps") . "</a></li>\n";
            $sidebar_html .= "	<li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/curriculummapversions?org=" . $ENTRADA_USER->getActiveOrganisation() . "\">" . $translate->_("Curriculum Map Versions") . "</a></li>\n";
        }
        $sidebar_html .= "	<li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/curriculum/tags\">" . $translate->_("Curriculum Tags") . "</a></li>\n";
        $sidebar_html .= "</ul>";
        new_sidebar_item($translate->_("Manage Curriculum"), $sidebar_html, "manage-curriculum", "open");

        $module_file = $router->getRoute();
        if ($module_file) {
            require_once($module_file);
        }

        preferences_update($MODULE, $PREFERENCES);
    }
}