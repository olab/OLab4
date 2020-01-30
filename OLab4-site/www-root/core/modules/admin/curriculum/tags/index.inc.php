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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer:James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_OBJECTIVES"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('curriculum', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"".$ENTRADA_TEMPLATE->relative()."/js/libs/bootstrap-table.js?release=".APPLICATION_VERSION."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/objectives/tags_index.js?release=" . APPLICATION_VERSION . "\"></script>\n";
    $HEAD[] = "<link href=\"" . $ENTRADA_TEMPLATE->relative() . "/css/bootstrap-table.css?release=" . html_encode(APPLICATION_VERSION) . "\"rel=\"stylesheet\" media=\"all\"/>";
    ?>

<?php
    $page_title = $module_title;
    
    switch ($STEP) {

        case 2:
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "\\'', 5000)";
            break;

        case 1:
        default:
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }

            include("templates/filters.inc.php");
            
            // $page_title must be set and is used in these includes ...
            echo "<h1 role=\"header\" aria-level=\"1\">". $translate->_($page_title) . "</h1>";
            include ("templates/index.inc.php");
            include("templates/delete-modal.inc.php");
            
            break;
    }
}
 