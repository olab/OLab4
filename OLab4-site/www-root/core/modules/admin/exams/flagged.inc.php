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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "update", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/questions?section=import", "title" => $translate->_(""));

    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("flagged");
    echo $sub_navigation;
    
    if ("unflag" === $ACTION) {
        $question_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
        $version_id = isset($_GET["version_id"]) ? (int)$_GET["version_id"] : 0;
        $question_version = Models_Exam_Question_Versions::fetchRowByQuestionID($question_id, $version_id);
        if ($question_version) {
            $question_version->setExamsoftFlagged(false);
            if ($question_version->update()) {
                add_success(sprintf($translate->_("Successfully unflagged question ID: %d / Ver: %d"), $question_id, $question_version->getVersionCount()));
            } else {
                add_error(sprintf($translate->_("Error unflagging question ID: %d / Ver: %d"), $question_id, $question_version->getVersionCount()));
            }
        }
    }
    ?>
    <h1><?php echo $translate->_("Flagged Questions"); ?></h1>
    <?php
    // Display content
    if (has_success()) {
        echo display_success();
    }
    if (has_notice()) {
        echo display_notice();
    }
    if (has_error()) {
        echo display_error();
    }
    $flagged_questions = Models_Exam_Question_Versions::fetchAllExamsoftFlagged();
    foreach ($flagged_questions as $question_version) {
        $question_view = new Views_Exam_Question($question_version);
        $unflag_button_html = "<a href=\"" . ENTRADA_URL . "/admin/exams/flagged?action=unflag&id=" . $question_version->getQuestionID() . "&version_id=" . $question_version->getVersionID() . "\" title=\"" . $translate->_("Unflag Question") . "\" class=\"flat-btn btn unflag-question\">";
        $unflag_button_html .= "<i class=\"fa fa-flag\"></i>";
        $unflag_button_html .= "</a>";
        $edit_button_html = "<a href=\"".ENTRADA_URL . "/admin/exams/questions?section=edit-question&id=".$question_version->getQuestionID()."&version_id=".$question_version->getVersionID()."\" title=\"".$translate->_("Edit Question")."\" class=\"flat-btn btn edit-question\">";
        $edit_button_html .= "<i class=\"fa fa-pencil\"></i>";
        $edit_button_html .= "</a>";
        $question_view->render(false, array(array($unflag_button_html, $edit_button_html)), NULL, 'details', true);
    }
}