<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 */

class Views_OLab_Sidebar extends Views_HTML {
    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate, $ENTRADA_ACL;

        $sidebar_html  = "<ul class=\"nav nav-list\">";
        $sidebar_html .= "    <li><a href=\"" . ENTRADA_RELATIVE . "/olab\">" . $translate->_("Public Side") . "</a></li>";

        if ($ENTRADA_ACL->amIAllowed("olab", "create", false)) {
            $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/admin/olab\">" . $translate->_("Admin Side") . "</a></li>";
        }

        $sidebar_html .= "</ul>";

        new_sidebar_item($translate->_("OpenLabyrinth"), $sidebar_html, "page-olab", "open");
    }
}
