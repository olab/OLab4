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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_RUBRICS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $navigation_tabs = new Views_Assessments_Forms_Controls_NavigationTabs();
    $navigation_tabs->render(array("active" => "rubrics", "has_access" => $ENTRADA_ACL->amIAllowed("assessments", "update", false), "exclusions" => array("items")));

    define("EDIT_RUBRIC", true);
    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    $PROCESSED["rubric_id"] = null;
    $rubric = null;
    $rubric_data = array();
    $rubric_referrer_data = array();
    $form_referrer_data = array();

    // Fetch the form referrer data, if any.
    if ($PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef()) {
        $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
    }

    if (isset($_GET["rubric_id"]) && $tmp_input = clean_input($_GET["rubric_id"], "int")) {
        $PROCESSED["rubric_id"] = $tmp_input;
    }
    if (isset($_POST["rubric_id"]) && $tmp_input = clean_input($_POST["rubric_id"], "int")) {
        $PROCESSED["rubric_id"] = $tmp_input;
    }

    if ($PROCESSED["rubric_id"]) {

        $forms_api->setRubricID($PROCESSED["rubric_id"]);
        $rubric_data = $forms_api->fetchRubricData();

        // Check if the rubric in question exists, and store it in the PROCESSED array
        if ($rubric = Models_Assessments_Rubric::fetchRowByIDOrganisationID($PROCESSED["rubric_id"], $ENTRADA_USER->getActiveOrganisation())) {
            $PROCESSED = array_merge($rubric->toArray(), $PROCESSED);
        }

        // Create a rubric referrer hash for our "create and attach" actions from the rubric edit form.
        $PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::buildRubricRef($PROCESSED["rubric_id"]);
        $this_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/rubrics?section=edit-rubric&rubric_id={$PROCESSED["rubric_id"]}", null, $PROCESSED["rref"]);

        // Add all related rubric referrer data
        Entrada_Utilities_FormStorageSessionHelper::addRubricReferrerData($PROCESSED["rubric_id"], $rubric_data, $this_url);

        // Fetch a local copy of it for the form to use.
        $rubric_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["rref"]);
    }

    if (!isset($PROCESSED["rubric_id"])) {
        echo display_error($translate->_("No Grouped Item found with the identifier supplied."));

    } else if (!$rubric) {
        // Model failed to fetch it
        echo display_error($translate->_("You do not have access to edit the selected Grouped Item in your active organisation."));

    } else if (!$ENTRADA_ACL->amIAllowed(new AssessmentComponentResource($PROCESSED["rubric_id"], "rubric", true), "update")) {
        //ACL check failed
        add_error(sprintf($translate->_("Your account does not have the permissions required to edit this rubric.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
        echo display_error();
        application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this item [" . $PROCESSED["rubric_id"] . "]");

    } else {

        // Finally, render the page after all checks pass.

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/rubrics/section=edit-rubric", "title" => $translate->_("Edit Grouped Item"));

        $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
        $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["rubrics"]["selected_view"]) ? $PREFERENCES["rubrics"]["selected_view"] : "list") ."\";</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.editable.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/rubrics/rubric-admin.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/forms/view.js\"></script>";

        echo "<h1>{$translate->_("Editing Grouped Item:")}</h1>";

        require_once("form.inc.php");
    }
}