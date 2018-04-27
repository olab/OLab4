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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif(!(Entrada_Settings::read("curriculum_weeks_enabled") && $ENTRADA_ACL->amIAllowed("weekcontent", "update", false))) {
    add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_WEEKS", true);
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
    $HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\" />\n";

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE, "title" => $translate->_("Weeks"));

    if (($router) && ($router->initRoute())) {
        $PREFERENCES = preferences_load($MODULE);

        if ((isset($_GET["id"])) && ($tmp_input = clean_input($_GET["id"], array("nows", "int")))) {
            $WEEK_ID = $tmp_input;
        } else if ((isset($_POST["id"])) && ($tmp_input = clean_input($_POST["id"], array("nows", "int")))) {
            $WEEK_ID = $tmp_input;
        } else {
            $WEEK_ID = 0;
        }

        if(isset($_GET["org_id"])){
            $ORGANISATION_ID = $_GET["org_id"];
        }
        else{
            $ORAGNISATION_ID = false;
        }

        /**
         * Check for groups which have access to the administrative side of this module
         * and add the appropriate toggle sidebar item.
         */
        if ($ENTRADA_ACL->amIAllowed("weekcontent", "update", false)) {
            switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
            case "admin" :
                $admin_wording = "Administrator View";
                break;
            case "pcoordinator" :
                $admin_wording = "Coordinator View";
                break;
            case "director" :
                $admin_wording = "Director View";
                break;
            default :
                $admin_wording = "";
                break;
            }

            $sidebar_html  = "<ul class=\"menu\">\n";
            $sidebar_html .= "	<li class=\"off\"><a href=\"".ENTRADA_URL."/".$MODULE.(($WEEK_ID) ? "?".replace_query(array("id" => $WEEK_ID, "section" => false)) : "")."\">Learner View</a></li>\n";
            if($admin_wording) {
                $sidebar_html .= "<li class=\"on\"><a href=\"".ENTRADA_URL."/admin/".$MODULE.(($WEEK_ID) ? "?".replace_query(array("id" => $WEEK_ID, "section" => "edit")) : "")."\">".html_encode($admin_wording)."</a></li>\n";
            }
            $sidebar_html .= "</ul>\n";

            new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
        }
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
/* vim: set expandtab: */
