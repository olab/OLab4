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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_SCALES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    define("EDIT_SCALE", true);

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $translate->_("Edit Rating Scale"));

    if (isset($_GET["rating_scale_id"]) && $tmp_input = clean_input($_GET["rating_scale_id"], "int")) {
        $PROCESSED["rating_scale_id"] = $tmp_input;
    }
    $scale = Models_Assessments_RatingScale::fetchRowByIDOrganisationID($PROCESSED["rating_scale_id"], $PROCESSED['organisation_id']);

    $PROCESSED['organisation_id'] = $ENTRADA_USER->getActiveOrganisation();

    if ($PROCESSED["rating_scale_id"] && $scale) {
        if ($ENTRADA_ACL->amIAllowed(new AssessmentComponentResource($PROCESSED["rating_scale_id"], "scale", true), "update")) {
            $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
            $forms_api->setScaleID($PROCESSED["rating_scale_id"]);
            $scale_data = $forms_api->fetchScaleData();

            $scale_referrer_data = array();
            $form_referrer_data = array();

            // Fetch the form referrer data, if any.
            if ($PROCESSED["fref"] = Entrada_Utilities_FormStorageSessionHelper::getFormRef()) {
                $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
            }

            $METHOD = "update";
            $PROCESSED = $scale;
            $PROCESSED['title'] = $PROCESSED['rating_scale_title'];
            $PROCESSED['description'] = $PROCESSED['rating_scale_description'];

            // Create a rubric referrer hash for our "create and attach" actions from the rubric edit form.
            $PROCESSED["rref"] = Entrada_Utilities_FormStorageSessionHelper::buildRubricRef($PROCESSED["rating_scale_id"]);
            $this_url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL."/admin/assessments/scales?section=edit-scales&rating_scale_id={$PROCESSED["rating_scale_id"]}", null, $PROCESSED["rref"]);
            ?>
            <h1><?php echo $translate->_("Editing Rating Scale:"); ?></h1>
            <?php
            require_once("form.inc.php");
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this scale.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
            echo display_error();
            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this scale [".$PROCESSED["rating_scale_id"]."]");
        }
    } else {
        ?>
        <h1><?php echo $translate->_("Editing Rating Scale:"); ?></h1>
        <?php
        echo display_error($translate->_("Scale not found"));
    }
}