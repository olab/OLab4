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
} elseif (!$ENTRADA_ACL->amIAllowed("curriculum", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte("minimal");

    $HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/ckeditor/adapters/jquery.js\"></script>";
    $HEAD[] = "<script src=\"".$ENTRADA_TEMPLATE->relative()."/js/libs/bootstrap-table.js?release=".APPLICATION_VERSION."\"></script>";
    $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/objectives/obj_index.js?release=" . APPLICATION_VERSION . "\"></script>";
    $HEAD[] = "<link href=\"" . $ENTRADA_TEMPLATE->relative() . "/css/bootstrap-table.css?release=" . APPLICATION_VERSION . "\" rel=\"stylesheet\" media=\"all\"/>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . APPLICATION_VERSION . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css?release=" . APPLICATION_VERSION . "\" />";

    if ($objective_set = Models_ObjectiveSet::fetchRowByID($objective_set_id)) {
        $page_title = $objective_set->GetTitle();
        $empty_tag_sets = empty(Models_Objective::fetchAllChildrenByObjectiveSetID($objective_set_id, $ENTRADA_USER->getActiveOrganisation()));
    } else {
        $page_title = $translate->_($module_title);
    }
    
    switch ($STEP) {
        case 2:
            $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "\\'', 5000)";

            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            break;
        case 1:
        default:
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }

            ?>
            <script>
                let display_button_add = <?php echo ($ENTRADA_ACL->amIAllowed("objective", "create", false) ? "true" : "false"); ?>;
                let display_button_edit = <?php echo ($ENTRADA_ACL->amIAllowed("objective", "update", false) ? "true" : "false"); ?>;
                let display_button_delete = <?php echo ($ENTRADA_ACL->amIAllowed("objective", "delete", false) ? "true" : "false"); ?>;
            </script>
            <?php

            include("templates/filters.inc.php");

            echo "<input type=\"hidden\" value=\"$objective_set_id\" id=\"objective_set_id\" name=\"objective_set_id\">";
            // $page_title must be set and is used in these includes ...
            include (ENTRADA_ABSOLUTE . "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/layouts/global/pageIntro.tpl.php");

            include ("templates/index.inc.php");

            include("templates/delete-modal.inc.php");
            
            break;
    }
}
 