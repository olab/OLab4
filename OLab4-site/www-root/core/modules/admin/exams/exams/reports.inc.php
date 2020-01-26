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
 * This file shows a list of available reports to run on the given exam.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if ($exam) {
        $exam_view = new Views_Exam_Exam($exam);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            ?>
            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>

            <?php
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2>Faculty Reports and Tools</h2>
            
            <ul>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=category&id=<?php echo $exam->getID(); ?>">Curriculum Tags Report</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=print-word&id=<?php echo $exam->getID(); ?>">Export Word</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=analysis&id=<?php echo $exam->getID(); ?>">Item Analysis</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=learner-comments&id=<?php echo $exam->getID(); ?>">Learner Comments</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=report-faculty-feedback&id=<?php echo $exam->getID(); ?>">Learner Feedback Report</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=learner-responses&id=<?php echo $exam->getID(); ?>">Learner Responses</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=print&id=<?php echo $exam->getID(); ?>">Print View</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=score&id=<?php echo $exam->getID(); ?>">Score Report</a></li>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=summary&id=<?php echo $exam->getID(); ?>">Summary Report</a></li>
            </ul>

            <h2>Release Learner Reports</h2>
            <ul>
                <li><a href="<?php echo ENTRADA_URL; ?>/admin/exams/exams?section=category-learner-setup&id=<?php echo $exam->getID(); ?>">Learner Curriculum Tags Report</a></li>
            </ul>
            <?php
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["exams"]["title"]; ?></h1>
        <?php
        echo display_error($SUBMODULE_TEXT["exams"]["exam_not_found"]);
    }
}