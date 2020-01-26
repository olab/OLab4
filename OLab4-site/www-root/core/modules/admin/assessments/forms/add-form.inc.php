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
    
    define("ADD_FORM", true);
    $REQUEST_MODE = "add";
    $METHOD = "insert";
    $render_standard_form = false;
    $render_blueprint_form = false;
    $render_cbme_form = false;
    $posted_form_title = null;
    $posted_form_type_id = null;

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    if (empty($_POST)) {
        // Nothing was posted, so render the standard "Add Form" form
        $render_standard_form = true;

    } else {
        // Something was posted, so make sure we have something to work with.
        if (isset($_POST["form_type_id"]) && $tmp_input = clean_input($_POST["form_type_id"], array("trim", "int"))) {
            $posted_form_type_id = $tmp_input;
        } else {
            add_error($translate->_("A form type ID must be specified."));
        }

        if (isset($_POST["form_title"]) && $tmp_input = clean_input($_POST["form_title"], array("trim", "striptags"))) {
            $posted_form_title = $tmp_input;
        } else {
            add_error($translate->_("A form title must be specified."));
        }

        if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
            $course_id = $tmp_input;
        } else {
            add_error($translate->_("A program must be specified."));
        }
    }

    if ($ERROR) {
        // By default, render the standard form and render any errors.
        $render_standard_form = true;

    } else {
        // Check if the form creation request is for a blueprint or for a form.
        $form_category = $forms_api->getFormCategory($posted_form_type_id);
        if ($form_category == "blueprint") {
            $render_blueprint_form = true;

        } elseif ($form_category == "form") {
            $render_standard_form = true;

        } elseif ($form_category == "cbme_form") {
            $render_cbme_form = true;

        } else {
            $render_standard_form = true;
            add_error($translate->_("Error: unknown form type."));
        }
    }

    if ($render_standard_form) {

        // Create empty form object
        $form = new Models_Assessments_Form();
        $PROCESSED = $form->toArray();

        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/forms?section=add-form", "title" => $translate->_("Create New Form"));
        echo "<h1>{$translate->_("Create New Form")}</h1>";

        // Fall through to the standard form editor, in "insert"/"add" mode
        require_once("form.inc.php");

    } else if ($render_cbme_form) {
        $PROCESSED["attributes"] = array();

        if ($course_id) {
            $PROCESSED["attributes"]["course_id"] = $course_id;
        }

        $form = new Models_Assessments_Form();

        // Create empty CBME form object
        $added = $forms_api->createEmptyCBMEForm($posted_form_title, $posted_form_type_id, $PROCESSED["attributes"]);

        if (!$added) {
            foreach ($forms_api->getErrorMessages() as $error_message) {
                add_error($error_message);
            }
        }

        if (!$ERROR) {
            // Save the POSTed blueprint record, and redirect to the blueprint editor page.
            $cbme_form_editor_url = ENTRADA_URL . "/admin/assessments/cbmeforms/?section=edit-form&cbme_form_id={$forms_api->getCBMEFormID()}";
            header("Location: $cbme_form_editor_url");

        } else {
            add_error($translate->_("Unable to save new form blueprint."));
            echo display_error();

        }


    } else if ($render_blueprint_form) {

        // Rendering a blueprint, so first save the record, then redirect to the blueprint editing page.
        $added = $forms_api->saveFormBlueprint(
            array(
                "form_type_id" => $posted_form_type_id,
                "title" => $posted_form_title,
                "active" => 0,
                "published" => 0,
                "course_id" => $course_id
            )
        );

        if (!$added) {
            foreach ($forms_api->getErrorMessages() as $error_message) {
                add_error($error_message);
            }
        }

        if (!$ERROR) {
            // Save the POSTed blueprint record, and redirect to the blueprint editor page.
            $blueprint_form_editor_url = ENTRADA_URL . "/admin/assessments/blueprints/?section=edit-blueprint&form_blueprint_id={$forms_api->getFormBlueprintID()}";
            header("Location: $blueprint_form_editor_url");

        } else {
            add_error($translate->_("Unable to save new form blueprint."));
            echo display_error();

        }
    }
}