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
 * The file that loads the add / edit form... form... when /admin/assessments/forms?section=add-form is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ITEMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    
    define("EDIT_ITEM", true);
    $METHOD = "update";
    $REQUEST_MODE = "edit";

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/items/section=edit-item", "title" => $translate->_("Edit Item"));

    $navigation_tabs = new Views_Assessments_Forms_Controls_NavigationTabs();
    $navigation_tabs->render(array("active" => "items", "has_access" => $ENTRADA_ACL->amIAllowed("assessments", "update", false), "exclusions" => array("rubrics")));

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $form_referrer_data = $rubric_referrer_data = null;
    if ($PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef()) {
        $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
    }
    if ($PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::getRubricRef()) {
        $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);
    }

    if (isset($_GET["item_id"]) && $tmp_input = clean_input($_GET["item_id"], "int")) {
        $PROCESSED["item_id"] = $tmp_input;
    }

    if (isset($_POST["item_id"]) && $tmp_input = clean_input($_POST["item_id"], "int")) {
        $PROCESSED["item_id"] = $tmp_input;
    }

    if (isset($_POST["rating_scale_id"]) && $tmp_input = clean_input($_POST["rating_scale_id"], "int")) {
        $PROCESSED["rating_scale_id"] = $tmp_input;
    }

    if (!isset($PROCESSED["item_id"])) {
        // No posted ID to edit with
        echo display_error($translate->_("Please specify a item ID."));

    } else {
        $item = Models_Assessments_Item::fetchRowByIDOrganisationID($PROCESSED["item_id"], $ENTRADA_USER->getActiveOrganisation());
        if ($item) {
            if ($ENTRADA_ACL->amIAllowed(new AssessmentComponentResource($PROCESSED["item_id"], "item", true), "update")) {
                $PROCESSED = array_merge($item->toArray(), $PROCESSED);

                echo "<h1>{$translate->_("Edit Item")}</h1>";
                require_once("form.inc.php");

            } else {

                add_error(sprintf($translate->_("Your account does not have the permissions required to edit this item.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
                echo display_error();
                application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this item [" . $PROCESSED["item_id"] . "]");

            }
        } else {

            echo "<h1>{$translate->_("Edit Item")}</h1>";
            echo display_error($translate->_("You do not have access to edit the selected Item in your active organisation."));

        }
    }
}