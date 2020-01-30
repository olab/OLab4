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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_DATASOURCES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    define("EDIT_DATASOURCE", true);

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["breadcrumb"]["title"]);

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $datasource = Models_Assessments_Data_Source::fetchRowByID($PROCESSED["id"]);
    if ($datasource) {
        $METHOD = "update";
        $PROCESSED = $datasource->toArray();
        ?>
        <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
        <?php
        require_once("form.inc.php");
    } else {
        ?>
        <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["form_not_found"]);
    }
}