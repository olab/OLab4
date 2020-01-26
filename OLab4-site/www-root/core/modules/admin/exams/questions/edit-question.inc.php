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
 * The file that loads the add / edit question when /admin/exams/questions?section=add-question is accessed.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUESTIONS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    define("EDIT_QUESTION", true);

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["breadcrumb"]["title"]);

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["question_id"] = $tmp_input;
    }

    if (isset($_POST["version_id"]) && $tmp_input = clean_input($_POST["version_id"], "int")) {
        $PROCESSED["version_id"] = $tmp_input;
    } else if (isset($_GET["version_id"]) && $version_id = clean_input($_GET["version_id"], "int")) {
        $PROCESSED["version_id"] = $version_id;
    } else {
        $version_id = Models_Exam_Question_Versions::getLatestVersionByQuestionID($PROCESSED["question_id"]);
        $PROCESSED["version_id"] = $version_id;
    }

    $question = Models_Exam_Questions::fetchRowByID($PROCESSED["question_id"]);
    if ($question) {
        if ($ENTRADA_ACL->amIAllowed(new ExamQuestionResource($PROCESSED["version_id"], true), "update")) {
            $METHOD = "update";
            $question_version = Models_Exam_Question_Versions::fetchRowByQuestionID($PROCESSED["question_id"], $PROCESSED["version_id"]);
            if ($question_version && is_object($question_version)) {
                $PROCESSED = $question_version->toArray();
                $PROCESSED["folder_id"] = $question_version->getFolderID();
            }
            ?>
            <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
            <?php
            require_once("form.inc.php");
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this question.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this question [".$PROCESSED["question_id"]."]");
        }
    } else {
        ?>
        <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["form_not_found"]);
    }
}
