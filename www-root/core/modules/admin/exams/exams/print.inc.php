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
 * This file shows a print-friendly view of an exam.
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
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/exams/exams/print.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/exams/print.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_GET["id"])) {
        $exam_id = (int)$_GET["id"];
    }
    $exam = Models_Exam_Exam::fetchRowByID($exam_id);
    if (!$exam) {
        add_error($SECTION_TEXT["errors"]["01"]);
        echo display_error();
    } else {
        $exam_view = new Views_Exam_Exam($exam);
        echo "<h1>" . $exam->getTitle() . "</h1>";
        echo $exam_view->examNavigationTabs($SECTION);
        $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
        echo "<h2 class=\"hidden-print\">" .  $SECTION_TEXT["print_view"] . "</h2>";
        // Add up total exam points
        $total_exam_points = 0;
        if ($exam_elements && is_array($exam_elements)) {
            foreach ($exam_elements as $elem) {
                $total_exam_points += $elem->getAdjustedPoints();
            }
        }

        // Create sidebar item
        $font_options = implode("\n", array_map(function($i) {
                            return "<option value=\"$i\">" . (int)($i * 100) . "%</option>";
                        }, range(1, 2, 0.25)));

        $sidebar_html = "<div>
                            <select id=\"update_fonts\">
                                " . $font_options . "
                            </select>
                             " . $SECTION_TEXT["font_size"] . "
                        </div>";

        if ($SECTION_TEXT["options"] && is_array($SECTION_TEXT["options"])) {
            foreach ($SECTION_TEXT["options"] as $hide_class => $option) {
                $sidebar_html .= "<div>\n";
                $sidebar_html .= "    <label>\n";
                $sidebar_html .= "        <input id=\"" . $hide_class. "\" type=\"checkbox\" class=\"hide_sections\" checked data-type=\"" . $hide_class ."\" />\n";
                $sidebar_html .= $option;
                $sidebar_html .= "    </label>\n";
                $sidebar_html .= "</div>\n";
            }
        }
        $sidebar_html .= "<div>\n";
        $sidebar_html .= "    <label>\n";
        $sidebar_html .= "        <input id=\"one_per_page\" type=\"checkbox\" class=\"\" data-type=\"one_per_page\" />\n";
        $sidebar_html .= "One Per Page";
        $sidebar_html .= "    </label>\n";
        $sidebar_html .= "</div>\n";

        $sidebar_html .= "<div>\n";
        $sidebar_html .= "    <label>\n";
        $sidebar_html .= "        <input id=\"repeat_question_stem\" type=\"checkbox\" class=\"\" data-type=\"repeat_question_stem\" />\n";
        $sidebar_html .= "Repeat Question Stem";
        $sidebar_html .= "    </label>\n";
        $sidebar_html .= "</div>\n";

        $print_button = "<a class=\"no-printing pull-right btn btn-primary print_button\" href=\"#\">
                            " . $SECTION_TEXT["print"] . "
                        </a>\n";
        $sidebar_html .= $print_button;
        new_sidebar_item($SECTION_TEXT["print_options"], $sidebar_html, "", "open", 2);
        // Output printer friendly view
        ?>
        <div class="print-friendly">
            <!-- Exam information -->
            <div class="exam_id">
                <strong>
                    <?php echo $SECTION_TEXT["options"]["exam_id"];?>:
                </strong>
                <?php echo $exam->getID();?>
            </div>
            <div class="exam_created_date">
                <strong>
                    <?php echo $SECTION_TEXT["options"]["exam_created_date"];?>:
                </strong>
                <?php echo date("D, M j, Y @ H:i:s", $exam->getCreatedDate());?>
            </div>
            <div class="num_questions">
                <strong>
                    <?php echo $SECTION_TEXT["options"]["num_questions"];?>:
                </strong>
                <?php echo count($exam_elements);?>
            </div>
            <div class="total_exam_points">
                <strong>
                    <?php echo $SECTION_TEXT["options"]["total_exam_points"];?>:
                </strong>
                <?php echo $total_exam_points;?>
            </div>
            <!-- Question information -->
            <?php
            foreach ($exam_elements as $element) {
                if ($element && is_object($element)) {
                ?>
                    <div class="print-question">
                        <div class="question_stem">
                            <div>
                                <strong>
                                    <?php echo $SECTION_TEXT["question_number"];?>
                                </strong>
                                <?php echo ($element->getOrder() + 1);?>
                            </div>
                            <?php
                            switch ($element->getElementType()) {
                                case "text":
                                    echo $element->getElementText();
                                    break;
                                case "question":
                                    $question_version = $element->getQuestionVersion();
                                    if ($question_version) {
                                        $folder = Models_Exam_Question_Bank_Folders::fetchRowByID($question_version->getFolderID());
                                        $short_name = $question_version->getQuestionType()->getShortname();
                                        switch ($short_name) {
                                            case "mc_h":
                                            case "mc_h_m":
                                            case "mc_v":
                                            case "mc_v_m":
                                                echo "<div>";
                                                echo $question_version->getQuestionText();
                                                echo "</div>";
                                                echo "<div>";
                                                echo "    <table>";
                                                $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
                                                foreach ($answers as $answer) {
                                                    $letter = chr(ord("A") + $answer->getOrder() - 1);
                                                    ?>
                                                    <tr>
                                                        <td class="correct">
                                                            <?php echo ($answer->getCorrect() ? "&#x2713;" : "");?>
                                                        </td>
                                                        <td>
                                                            <?php echo $letter . ". " . $answer->getAnswerText();?>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                echo "    </table>";
                                                echo "</div>";
                                            break;
                                            case "match":
                                                ?>
                                                <div>
                                                    <?php echo $question_version->getQuestionText();?>
                                                </div>
                                                <div>
                                                    <br />
                                                    <table>
                                                        <tr>
                                                           <th>
                                                              Order
                                                           </th>
                                                           <th>
                                                              Stem
                                                           </th>
                                                           <th>
                                                              Correct
                                                           </th>
                                                        </tr>
                                                        <?php
                                                        $matching_stems = Models_Exam_Question_Match::fetchAllRecordsByVersionID($question_version->getVersionID());
                                                        if ($matching_stems && is_array($matching_stems)) {
                                                            foreach ($matching_stems as $stem) {
                                                                if ($stem && is_object($stem)) {
                                                                    $matching_correct = Models_Exam_Question_Match_Correct::fetchRowByMatchID($stem->getID());
                                                                    if ($matching_correct && is_object($matching_correct)) {
                                                                        $answer = $matching_correct->getAnswer();
                                                                        if ($answer && is_object($answer)) {
                                                                            $answer_text = $answer->getAnswerText();
                                                                        }
                                                                    }
                                                                    ?>
                                                                          <tr>
                                                                               <td>
                                                                                  <?php echo $stem->getOrder();?>
                                                                               </td>
                                                                               <td>
                                                                                  <?php echo $stem->getMatchText();?>
                                                                               </td>
                                                                               <td>
                                                                                  <?php echo $answer_text;?>
                                                                               </td>
                                                                          </tr>
                                                                  <?php
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    </table>
                                                    <br />
                                                </div>
                                                <?php
                                                break;
                                            case "fnb":
                                                $question_text      = $question_version->getQuestionText();
                                                $fnb_count = substr_count($question_version->getQuestionText(), "_?_");
                                                $question_stem = "";
                                                $question_text_array = explode("_?_", $question_text);
                                                if (isset($question_text_array) && is_array($question_text_array)) {
                                                    $part_counter = 1;
                                                    foreach ($question_text_array as $key => $question_part) {
                                                        if ($question_part != "") {
                                                            $question_stem .= $question_part;
                                                            if ($part_counter <= $fnb_count) {
                                                                // get all correct answers for fnb item
                                                                $answer = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $part_counter);
                                                                if ($answer && is_object($answer)) {
                                                                    $correct_options = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
                                                                    $correct = array();
                                                                    foreach ($correct_options as $option) {
                                                                        if ($option && is_object($option)) {
                                                                            $correct[] = $option->getText();
                                                                        }
                                                                    }
                                                                    if ($correct && is_array($correct)) {
                                                                        $question_stem .= "<span class=\"print_fnb_text\">" . implode("/", $correct) . "</span>";
                                                                    }
                                                                }
                                                                $part_counter++;
                                                            }
                                                        }
                                                    }
                                                }
                                                echo "<div>";
                                                echo $question_stem;
                                                echo "</div>";
                                                break;
                                            default:
                                                echo "<div>";
                                                echo $question_version->getQuestionText();
                                                echo "</div>";
                                                if ($question_version->getCorrectText()) {
                                                ?>
                                                    <div class="correct">
                                                        <strong>
                                                            <?php echo $SECTION_TEXT["options"]["correct"];?>:
                                                        </strong>
                                                        <?php echo $question_version->getCorrectText();?>
                                                    </div>
                                                <?php
                                                }
                                                break;
                                        }
                                        ?>
                                    </div>
                                <?php
                                if ($question_version->getRationale()) {
                                ?>
                                <div class="rationale">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["rationale"];?>:
                                    </strong>
                                    <?php echo $question_version->getRationale();?>
                                </div>
                                <?php
                                }
                                ?>
                                <div class="entrada_id">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["entrada_id"];?>:
                                    </strong>
                                    <?php echo $question_version->getQuestionID();?>/<?php echo $question_version->getVersionCount();?>
                                </div>
                                <div class="examsoft_id">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["examsoft_id"];?>:
                                    </strong>
                                    <?php echo ($question_version->getExamsoftID() ? $question_version->getExamsoftID() : "N/A");?>
                                </div>
                                <?php
                                if ($question_version->getQuestionDescription()) {
                                ?>
                                <div class="description">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["description"];?>:
                                    </strong>
                                    <?php echo $question_version->getQuestionDescription();?>
                                </div>
                                <?php
                                }
                                ?>
                                <div class="weight">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["weight"];?>:
                                    </strong>
                                    <?php echo $element->getPoints();?>
                                </div>
                                <div class="question_folder">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["question_folder"];?>:
                                    </strong>
                                    <?php echo $folder->getCompleteFolderTitle();?>
                                </div>
                                <div class="curriculum_tags">
                                    <strong>
                                        <?php echo $SECTION_TEXT["options"]["curriculum_tags"];?>:
                                    </strong>
                                    <?php
                                    $objectives = Views_Exam_Question_Objective::renderObjectives($question_version->getQuestionID(), true);
                                    if ($objectives) {
                                        echo $objectives;
                                    }
                                    ?>
                                </div>

                                <?php
                                break;
                            }

                    }
                    ?>
                    </div>
                <?php
                }
            }
        echo "<div>"; // End print-friendly div
        echo $print_button;
    }
}