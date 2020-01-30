<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Views_Sandbox_Sidebar extends Views_Base {
    /**
     * Validate: make sure assessment record and distribution exist in
     * order to display the relevant info.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render view specific error, indicating that delivery information is unreadable.
     */
    protected function renderError() {
        global $translate;
        ?>
        <div id="assessment-delivery-info" class="space-below">
            <?php echo $translate->_("Unable to determine task details."); ?>
        </div>
        <?php
    }

    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $sidebar_html  = "<ul>";
        $sidebar_html .= "    <li><a href=\"" . ENTRADA_RELATIVE . "/sandbox\">" . $translate->_("Public Side") . "</a></li>";
        $sidebar_html .= "    <li><a href=\"" . ENTRADA_RELATIVE . "/admin/sandbox\">" . $translate->_("Admin Side") . "</a></li>";
        $sidebar_html .= "</ul>";

        new_sidebar_item("Sidebar", $sidebar_html, "page-sandbox", "open");
    }
}