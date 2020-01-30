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
 * The form that allows users to add and edit formbank forms.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_FORM") && !defined("EDIT_FORM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	load_rte();

	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css\" />";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/assessment-form.css\" />";

	// TODO: Use the non-deprecated form rendering method
	$form_html = Views_Deprecated_Form::renderFormElements($form->getID(), true, false, true, array(), true, false, true);
	$form_html = implode("\n", $form_html);

	if ($form_html) {
		$form_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
		if ($form_pdf->configure()) {
			$html = $form_pdf->generateAssessmentFormHTML($form_html, $form);
			$filename = $form_pdf->buildFilename($form->getTitle(), ".pdf");
			if (!$form_pdf->send($filename, $html)) {
				// Unable to send, so redirect away from this page and show an error.
				ob_clear_open_buffers();
				$error_url = $form_pdf->buildURI("/admin/assessments/forms", $_SERVER["REQUEST_URI"]);
				$error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
				Header("Location: $error_url");
				die();
			}

		} else {

			echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
			application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");

			require_once("form.inc.php");
		}
	} else {
		echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
		application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");

		require_once("form.inc.php");
	}
}