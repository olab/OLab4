<?php

/*
 * Function that builds Display Style sidebar widget from language file.
 *
 * @return void
 */
function add_display_style_sidebar($admin_url, $learner_url, $selected)
{
    global $translate, $ENTRADA_ACL, $ENTRADA_USER;
    
    /**
     * Check for groups which have access to the administrative side of this module
     * and add the appropriate toggle sidebar item.
     */
    if ($ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
        switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
            case "admin":
                $admin_wording = "Administrator View";
                break;
            case "pcoordinator":
                $admin_wording = "Coordinator View";
                break;
            case "director":
                $admin_wording = "Director View";
                break;
            default:
                $admin_wording = "";
                break;
        }
        
        $sidebar_html = "<ul class=\"menu\">\n";
        $sidebar_html .= "	<li class=".($selected == "learner" ? "on" : "off")."><a href=\"" . $learner_url . "\"><img src=\"".ENTRADA_RELATIVE."/images/checkbox-";
        $sidebar_html .= ($selected == "learner" ? "on" : "off") . ".gif\" alt=\"\" /> <span>Learner View</span></a></li>\n";
        if ($admin_wording) {
            $sidebar_html .= "<li class=" .($selected == "admin" ? "on" : "off"). "><a href=\"" . $admin_url . "\"><img src=\"".ENTRADA_RELATIVE."/images/checkbox-";
            $sidebar_html.= ($selected == "admin" ? "on" : "off") . ".gif\" alt=\"\" /> <span>".html_encode($admin_wording)."</span></a></li>\n";
        }
        $sidebar_html .= "</ul>\n";
        
        new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
    }
}
