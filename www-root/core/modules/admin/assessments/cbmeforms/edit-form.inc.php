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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_FORMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();

    define("EDIT_FORM", true);
    $REQUEST_MODE = "edit";
    $METHOD = "update";

	$generate_pdf	= false;
	$pdf_error		= false;

    $PROCESSED["cbme_form_id"] = null;
    if (isset($_GET["cbme_form_id"]) && $tmp_input = clean_input($_GET["cbme_form_id"], "int")) {
        $PROCESSED["cbme_form_id"] = $tmp_input;
    }
    if (isset($_POST["cbme_form_id"]) && $tmp_input = clean_input($_POST["cbme_form_id"], "int")) {
        $PROCESSED["cbme_form_id"] = $tmp_input;
    }

    if (isset($_GET["generate-pdf"]) && ($tmp_input = clean_input($_GET["generate-pdf"], array("trim", "lower", "notags")))) {
		if ($tmp_input == "1" || $tmp_input == "true") {
			$generate_pdf = true;
		}
	}
	if (isset($_GET["pdf-error"]) && ($tmp_input = clean_input($_GET["pdf-error"], array("trim", "lower", "notags")))) {
		if ($tmp_input == "1" || $tmp_input == "true") {
			$pdf_error = true;
		}
	}

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    if ($form = Models_Assessments_Form::fetchRowByIDOrganisationID($PROCESSED["cbme_form_id"], $ENTRADA_USER->getActiveOrganisation())) {

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/cbmeforms?section=edit-form", "title" => $form->getTitle());

        $form_category = $forms_api->getFormCategory($form->getFormTypeID());
        if ($form_category != "cbme_form") {
            add_error($translate->_("Unable render that form category."));

            echo display_error();

            application_log("error", "Trying to edit a form of category {$form_category} in the publishable forms admin form.");
        } else {
            if ($ENTRADA_ACL->amIAllowed(new AssessmentComponentResource($PROCESSED["cbme_form_id"], "form", true), "update")) {
                $PROCESSED = array_merge($PROCESSED, $form->toArray());

                echo "<h1>{$translate->_("Editing Form")}</h1>";

                // Instantiate form view here, instead of including a separate file
                if ($generate_pdf) {
                    require_once("form-pdf.inc.php");
                } else {
                    require_once("form.inc.php");
                }

            } else {
                add_error(sprintf($translate->_("Your account does not have the permissions required to edit this form.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

                echo display_error();

                application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this form [" . $PROCESSED["cbme_form_id"] . "]");
            }
        }

    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/assessments/forms?section=edit-form", "title" => $translate->_("Edit Form"));
        echo display_error($translate->_("You do not have access to edit the selected Form in your active organisation."));
    }
}