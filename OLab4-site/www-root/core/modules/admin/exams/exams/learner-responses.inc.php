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
 * Aggregates learner responses for questions on this exam.
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
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if ($exam) {
        $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
        $allowed_post_ids = array_map(function($i) { return $i->getID(); }, $posts);
        if (isset($_POST["download_csv"]) && isset($_POST["post_ids"])) {
            ob_clear_open_buffers();
            header("Content-type: text/csv");
            header("Content-disposition: attachment; filename=\"report.csv\"");
            $out = fopen("php://output", "w");
            // Collect all exam elements and submissions
            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($exam->getID(), "question");
            $submissions = array();
            foreach ($_POST["post_ids"] as $post_id) {
                if (in_array($post_id, $allowed_post_ids)) {
                    $submissions = array_merge($submissions, Models_Exam_Progress::fetchAllByPostIDProgressValue($post_id, "submitted"));
                }
            }
            // Get headings. 2 for each exam element, one for response value and one for correctness
            $headings = array("Student Name", "Student ID");
            foreach ($exam_elements as $elem) {
                $n = (int)$elem->getOrder() + 1;
                $headings[] = "Q$n Response";
                $headings[] = "Q$n Points";
            }
            fputcsv($out, $headings);
            // Output all submissions
            foreach ($submissions as $sub) {
                $row = array();
                $student = User::fetchRowByID($sub->getProxyID());
                $row[] = $student->getFullName();
                $row[] = $student->getNumber();
                foreach ($exam_elements as $elem) {
                    $question_type = $elem->getQuestionVersion()->getQuestionType()->getShortName();
                    $response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($sub->getID(), $elem->getID());
                    $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
                    $response_value = "";
                    switch ($question_type) {
                        case "mc_v":
                        case "mc_h":
                        case "mc_v_m":
                        case "mc_h_m":
                            $letters = array();
                            foreach ($answers as $answer) {
                                $letters[] = $answer->getResponseElementLetter();
                            }
                            $response_value = implode(", ", $letters);
                            break;
                        case "essay":
                        case "short":
                        case "fnb":
                        case "match":
                            $values = array();
                            foreach ($answers as $answer) {
                                $values[] = $answer->getResponseValue();
                            }
                            $response_value = implode(", ", $values);
                            break;
                        case "text":
                        default:
                            // No response
                            break;
                    }
                    $row[] = $response_value;
                    $row[] = $response->getScore();
                }
                fputcsv($out, $row);
            }
            fclose($out);
            exit;
        } // END OUTPUT CSV
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=learner-responses&id=".$exam->getID(), "title" => "Learner Responses");
        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            ?>
            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
            <?php
            $exam_view = new Views_Exam_Exam($exam);
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2>Learner Responses Report</h2>
            <form action="<?php echo ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."/?section=learner-responses&id=".$exam->getID(); ?>" method="post"
                  onsubmit="return 0 !== jQuery('.post_checkbox:checked').length;">
                <?php
                foreach ($posts as $post) {
                    $checked = !isset($_POSTS["post_ids"]) || in_array($post->getID(), $_POST["post_ids"]) ? "checked" : "";
                    echo "<label>\n";
                    echo "<input type=\"checkbox\" class=\"post_checkbox\" name=\"post_ids[]\" value=\"".$post->getID()."\" $checked />\n";
                    echo html_encode($post->getTitle())." (".date("m/d/y", $post->getUpdatedDate()).")";
                    echo "</label><br />\n";
                }
                ?>
                <input type="submit" class="btn btn-primary" name="download_csv" value="Download CSV" />
            </form>
            <?php
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to view this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["exams"]["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["exam_not_found"]);
    }
}