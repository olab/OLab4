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
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/exams/exams.css?release=".html_encode(APPLICATION_VERSION)."'>";

    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ."\";</script>";
    $HEAD[] = "<script src=\"" . ENTRADA_RELATIVE . "/javascript/exams/exams/history.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if ($exam) {
        $exam_view = new Views_Exam_Exam($exam);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=history&id=".$exam->getID(), "title" => "History");

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            ?>
            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>

            <?php
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2>Exam Creation History</h2>
            <?php
            $history_records = Models_Exam_Creation_History::fetchAllByExamId($exam->getID());
            if (isset($history_records) && is_array($history_records)) {
                ?>
                <script>
                    jQuery(document).ready(function ($) {
                        var can_delete = <?php echo $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete") ? "true" : "false"; ?>;
                        var history_records = $("#history-table").DataTable({
                            sPaginationType: 'full_numbers',
                            bSortClasses: false,
                            oSearch: {bSmart: false},
                            aaSorting: [[3, 'desc']],
                            "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]]
                        });
                    });

                </script>
                <table class="table table-bordered table-striped" id="history-table">
                    <thead>
                    <tr>
                        <th><?php echo $SECTION_TEXT["header"]["user"]; ?></th>
                        <th><?php echo "Primary </br> " . $SECTION_TEXT["header"]["action"]; ?></th>
                        <th><?php echo $SECTION_TEXT["header"]["message"]; ?></th>
                        <th><?php echo $SECTION_TEXT["header"]["timestamp"]; ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($history_records as $history) {
                        if (is_object($history)) {

                            $user = Models_User::fetchRowByID($history->getProxyID());
                            if ($user && is_object($user)) {
                                $user_name = $user->getFullname();
                            }

                            $action_type = "";
                            switch ($history->getAction()) {
                                case "exam_add":
                                    $action_type = "Added Exam";
                                    break;
                                case "exam_edit":
                                    $action_type = "Edited Exam";
                                    break;
                                case "exam_delete":
                                    $action_type = "Deleted Exam";
                                    break;
                                case "exam_copy":
                                    $action_type = "Copied Exam";
                                    break;
                                case "exam_move":
                                    $action_type = "Moved Exam";
                                    break;
                                case "exam_settings_edit":
                                    $action_type = "Edited Settings";
                                    Break;
                                case "exam_element_add":
                                    $action_type = "Added Exam Element";
                                    break;
                                case "exam_element_edit":
                                    $action_type = "Edited Exam Element";
                                    break;
                                case "exam_element_delete":
                                    $action_type = "Removed Exam Element";
                                    break;
                                case "exam_element_group_add":
                                    $action_type = "Added Grouped Question";
                                    break;
                                case "exam_element_group_edit":
                                    $action_type = "Edited Group Question";
                                    break;
                                case "exam_element_group_delete":
                                    $action_type = "Removed Group Question";
                                    break;
                                case "exam_element_order":
                                    $action_type = "Updated Exam Order";
                                    break;
                                case "exam_element_points":
                                    $action_type = "Updated Element Points";
                                    break;
                                case "post_exam_add":
                                    $action_type = "Added Exam Post";
                                    break;
                                case "post_exam_edit":
                                    $action_type = "Edited Exam Post";
                                    break;
                                case "post_exam_delete":
                                    $action_type = "Deleted Exam Post";
                                    break;
                                case "adjust_score":
                                    $action_type = "Adjusted Score";
                                    break;
                                case "delete_adjust_score":
                                    $action_type = "Delete Adjusted Score";
                                    break;
                                case "reopen_progress":
                                    $action_type = "Reopen Progress";
                                    break;
                                case "delete_progress":
                                    $action_type = "Delete Progress";
                                    break;
                                case "report_add":
                                    $action_type = "Added Report";
                                    break;
                                case "report_edit":
                                    $action_type = "Edited Report";
                                    break;
                                case "report_delete":
                                    $action_type = "Deleted Report";
                                    break;
                            }

                            $message = "";
                            switch ($history->getAction()) {
                                case "exam_add":
                                case "exam_edit":
                                case "exam_delete":
                                case "exam_copy":
                                case "exam_settings_edit":

                                    break;

                                case "exam_move":
                                    $folder = Models_Exam_Bank_Folders::fetchRowByID($history->getActionResourceID());
                                    if ($folder && is_object($folder)) {

                                        // $folder
                                        $folder_view  = new Views_Exam_Bank_Folder($folder);
                                        $url        = $folder_view->getFolderURL();
                                        $title      = $folder->getFolderTitle();
                                        $message    = "Moved to folder: <a href=\"" . $url . "\"><strong>" . str_replace("'", "\'", $title) . "</strong></a>";
                                    }
                                    break;
                                case "exam_element_add":
                                case "exam_element_edit":
                                case "exam_element_delete":

                                    if ($history->getSecondaryAction() != "" || $history->getSecondaryAction() != NULL) {
                                        $version_id = $history->getSecondaryActionResourceId();
                                        if ($version_id) {
                                            $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                            if ($version && is_object($version)) {
                                                $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                $message = "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                            }
                                        }
                                    } else {
                                        $exam_element_id = $history->getActionResourceID();
                                        $element = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                        if ($element && is_object($element)) {
                                            if ($element->getElementType() == "question") {
                                                $version_id = $element->getElementID();
                                                if ($version_id) {
                                                    $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                    if ($version && is_object($version)) {
                                                        $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                        $message = "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                    }
                                                }
                                            } elseif ($element->getElementType() == "text") {
                                                //@todo show the text
                                            }
                                        }
                                    }
                                    break;
                                case "exam_element_group_add":
                                case "exam_element_group_edit":
                                case "exam_element_group_delete":

                                    $secondary_action   = $history->getSecondaryAction();
                                    $primary_id         = $history->getActionResourceID();
                                    $secondary_id       = $history->getSecondaryActionResourceId();

                                    if ($secondary_action == "group") {
                                        $group = Models_Exam_Group::fetchRowByID($secondary_id);
                                        if ($group && is_object($group)) {
                                            $title = "ID. " . $group->getGroupID() . " Title. " . $group->getGroupTitle();
                                            $message = "<a class=\"group_preview\" data-group-id=\"" . $group->getID() . "\" data-type=\"group\">" . $title . "</a>";
                                        }
                                    }

                                    break;
                                case "exam_element_order":
                                    break;
                                case "exam_element_points":

                                    $secondary_action   = $history->getSecondaryAction();
                                    $primary_id         = $history->getActionResourceID();
                                    $secondary_id       = $history->getSecondaryActionResourceId();

                                    $exam_element_id = $primary_id;
                                    $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                    if ($element && is_object($element)) {
                                        if ($element->getElementType() == "question") {
                                            $version_id = $element->getElementID();
                                            if ($version_id) {
                                                $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                if ($version && is_object($version)) {
                                                    $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();

                                                    if ($secondary_action == "scoring") {
                                                        $message = "Updated scoring to <strong>" . ($secondary_id == 1 ? "on": "off") . "</strong> for question: ";
                                                    } else {
                                                        $message = "Updated points to <strong>" . $secondary_id . "</strong>  for question: ";
                                                    }

                                                    $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                }
                                            }
                                        }
                                    }

                                    break;
                                case "post_exam_add":
                                case "post_exam_edit":
                                case "post_exam_delete":
                                    $post = Models_Exam_Post::fetchRowByID($history->getActionResourceID());
                                    if ($post && is_object($post)) {
                                        $post_view  = new Views_Exam_Post($post);
                                        $url        = $post_view->getTargetPublicURL(true);
                                        $title      = $post_view->getTargetTitle();
                                        $message    = "Posted to <a href=\"" . $url . "\"><strong>" . str_replace("'", "\'", $title) . "</strong></a>";
                                    }
                                    break;
                                case "adjust_score":
                                    $secondary_action   = $history->getSecondaryAction();
                                    $primary_id         = $history->getActionResourceID();
                                    $secondary_id       = $history->getSecondaryActionResourceId();

                                    switch ($secondary_action) {
                                        case "throw_out":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Thrown out question: ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "correct":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                            $choice         = $secondary_id;
                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Marked answer choice correct: ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-adjustment-correct=\"" . $choice . "\" data-type=\"adjustment-correct\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "incorrect":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                            $choice         = $secondary_id;
                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Marked answer choice incorrect: ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-adjustment-incorrect=\"" . $choice . "\" data-type=\"adjustment-incorrect\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "update_points":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Updated points to " . $secondary_id . " for question: ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "make_bonus":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Made bonus question: ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        default:
                                            break;
                                    }


                                    break;
                                case "delete_adjust_score":
                                    $secondary_action   = $history->getSecondaryAction();
                                    $primary_id         = $history->getActionResourceID();
                                    $secondary_id       = $history->getSecondaryActionResourceId();

                                    switch ($secondary_action) {
                                        case "throw_out":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Deleted Adjustment - Thrown out question: <br />";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "correct":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                            $choice         = $secondary_id;
                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Deleted Adjustment - Marked answer choice correct: <br /> ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-adjustment-correct=\"" . $choice . "\" data-type=\"adjustment-correct\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "incorrect":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                            $choice         = $secondary_id;
                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Deleted Adjustment - Marked answer choice incorrect: <br /> ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-adjustment-incorrect=\"" . $choice . "\" data-type=\"adjustment-incorrect\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "update_points":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Deleted Adjustment - Updated points to " . $secondary_id . " for question: <br /r> ";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        case "make_bonus":
                                            $exam_element_id = $primary_id;
                                            $element        = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);

                                            if ($element && is_object($element)) {
                                                if ($element->getElementType() == "question") {
                                                    $version_id = $element->getElementID();
                                                    if ($version_id) {
                                                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($version_id);
                                                        if ($version && is_object($version)) {
                                                            $title = "ID. " . $version->getQuestionID() . " Ver. " . $version->getVersionCount();
                                                            $message = "Deleted Adjustment - Made bonus question: <br />";
                                                            $message .= "<a class=\"question_preview\" data-version-id=\"" . $version_id . "\" data-type=\"exam-element\">" . $title . "</a>";
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        default:
                                            break;
                                    }
                                    break;
                                case "reopen_progress":
                                case "delete_progress":
                                    break;
                                case "report_add":
                                case "report_edit":
                                case "report_delete":

                                    break;
                            }

                            echo "<tr>";
                            echo "<td>";
                            echo $user_name;
                            echo "</td>";
                            echo "<td>";
                            echo $action_type;
                            echo "</td>";
                            echo "<td>";
                            echo ($message ? $message : $history->getHistoryMessage());
                            echo "</td>";
                            echo "<td>";
                            echo date("n/j/Y G:i", $history->getTimestamp());
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
            <div id="preview-question-modal" class="modal hide fade">
                <form id="preview-question" class="exam-horizontal" style="margin:0px;">
                    <div class="modal-body">
                        <h3></h3>
                        <div class="modal-sub-body"></div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-fluid">
                            <a href="#" class="btn btn-default btn-primary" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_close"]; ?></a>
                        </div>
                    </div>
                </form>
            </div>
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