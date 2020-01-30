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
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Question_Answer extends Views_Deprecated_Base {
    protected $default_fieldset = array(
        "qanswer_id", "question_id", "version_id", "answer_text", "answer_rationale", "correct", "weight", "order", "updated_date", "updated_by", "deleted_date"
        );

    protected $table_name               = "exam_question_answers";
    protected $primary_key              = "qanswer_id";
    protected $default_sort_column      = "`exam_question_answers`.`order`";

    protected $answer, $short_name;

    public function __construct(Models_Exam_Question_Answers $answer) {
        $this->answer = $answer;
    }

    public function buildAnswerTableHeader(array $control_array = NULL, array $data_attr_array = NULL, $td_count) {
        global $translate;
        $short_name = $this->short_name;

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"];

        $html = "<div class=\"exam-question-answer question-container\" id=\"" . $this->answer->getID() ."\" data-question-id=\"" . $this->answer->getQuestionID() . "\" data-version-id=\"" . $this->answer->getVersionID() . "\" data-sortable-element-id=\"" . $this->answer->getOrder() . "\"";
        if (!empty($data_attr_array)) {
            foreach ($data_attr_array as $key => $data_attr) {
                $html .= " data-" . $key . "=\"" . $data_attr . "\"";
            }
        }
        $html .= ">";

        switch ($short_name) {
            case "fnb":
                $title =  $SUBMODULE_TEXT["answers"]["label_row_fnb"];
                break;
            case "match":
                $title =  $SUBMODULE_TEXT["answers"]["label_row_match"];
                break;
            default :
                $title =  $SUBMODULE_TEXT["answers"]["label_row"];
                break;
        }

        $html .= "<table class=\"table table-striped table-bordered\">";
        $html .= "  <tr class=\"type\">";
        $html .= "      <td colspan=\"" . $td_count . "\" class=\"answer-header\">";
        $html .= "          <span class=\"answer-number\"> " . $title . ": " . $this->answer->getOrder() . "</span>";

        $html .= "      <div class=\"pull-right\">";
        if (is_null($control_array)) {

        } else {
            if (is_array($control_array) && !empty($control_array)) {
                foreach ($control_array as $control_group) {
                    $html .= "<div class=\"btn-group\">";
                    foreach ($control_group as $control) {
                        $html .= $control;
                    }
                    $html .= "</div>";
                }
            }
        }
        $html .= "        </div>";
        $html .= "      </td>";
        $html .= "  </tr>";

        return $html;

    }

    public function renderAnswer(array $data_attr_array = NULL, $question_type_short_name = NULL) {
        global $translate;
        $answer = $this->answer;
        if (isset($answer) && is_object($answer) && $answer->getVersionID() > 0) {
            $this->short_name = Models_Exam_Question_Versions::fetchRowByVersionID($answer->getVersionID())->getQuestionType()->getShortname();
            $short_name = $this->short_name;
        } else {
            $short_name = $question_type_short_name;
            $this->question_type = $short_name;
        }

        switch ($short_name) {
            case "mc_h_m":
            case "mc_v_m":
                $custom_grading_allowed = true;
                break;
            default :
                $custom_grading_allowed = false;
                break;
        }

        switch ($short_name) {
            case "fnb":
            case "match":
                $lock_icon = ($this->answer->getLocked() == 1 ? "fa-lock" : "fa-unlock-alt");
                $locked = ($this->answer->getLocked() == 1 ? "locked" : "unlocked");
                $lock_text = ($this->answer->getLocked() == 1 ? "Unlock from this position" : "Lock at this position");
                $control_array = array(
                    array(
                        "<span class=\"btn select-answer\">
                            <i class=\"icon-select-answer fa fa-2x fa-square-o\" data-sortable-element-id=\"" . $this->answer->getOrder() . "\"></i>
                        </span>"
                    ),
                    array(
                        //"<a class=\"btn answer-details\" title=\"View Question Details\" href=\"#\"><i class=\"icon-eye-open\"></i></a>",
                        "<a class=\"btn move\" title=\"Move\" href=\"#\"><i class=\"icon-move\"></i></a>",
                        "<a class=\"btn lock\" title=\"" . $lock_text . "\" href=\"#\"><i class=\"fa fa-lg " . $lock_icon . " $locked\" data-order-id=\"" . $this->answer->getOrder() . "\"></i></a>"
                    )
                );
                break;
            default :
                $correct = ($this->answer->getCorrect() == 1 ? "correct" : "incorrect");
                $lock_icon = ($this->answer->getLocked() == 1 ? "fa-lock" : "fa-unlock-alt");
                $locked = ($this->answer->getLocked() == 1 ? "locked" : "unlocked");
                $lock_text = ($this->answer->getLocked() == 1 ? "Unlock from this position" : "Lock at this position");
                $control_array = array(
                    array(
                        "<span class=\"btn select-answer\">
                            <i class=\"icon-select-answer fa fa-2x fa-square-o\" data-sortable-element-id=\"" . $this->answer->getOrder() . "\"></i>
                        </span>"
                    ),
                    array(
                        "<span class=\"btn answer-correct\" title=\"Set as correct answer\" ><i class=\"icon-select-correct fa fa-lg fa-check-circle " . $correct . "\" data-correct=\"" . $this->answer->getCorrect() . "\" data-sortable-element-id=\"" . $this->answer->getOrder() . "\"></i></i></span>",
                        //"<a class=\"btn answer-details\" title=\"View Question Details\" href=\"#\"><i class=\"icon-eye-open\"></i></a>",
                        "<a class=\"btn move\" title=\"Move\" href=\"#\"><i class=\"icon-move\"></i></a>",
                        "<a class=\"btn lock\" title=\"" . $lock_text . "\" href=\"#\"><i class=\"icon-lock-answer fa fa-lg " . $lock_icon . " $locked\" data-order-id=\"" . $this->answer->getOrder() . "\"></i></a>"
                    )
                );
                break;
        }

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"]["answers"];

        if ($this->answer->getCorrect() == 1) {
            $correct = "checked=\"checked\"";
        } else {
            $correct = "";
        }

        $td_count = ($custom_grading_allowed ? 1 : 2);

        $html = $this->buildAnswerTableHeader($control_array, $data_attr_array, 2);

        $html .= "<tr class=\"answer-row\">";
        $html .= "  <td colspan=\"2\" class=\"answer-row-text\">";

        switch ($short_name) {
            case "fnb":
                $html .= "<div class=\"row-fluid\">";
                $html .= "<div class=\"span2\">" . $SUBMODULE_TEXT["fnb"]["correct_answers"] . ": </div>";
                $html .= "<div class=\"span8\" class=\"fnb-correct-answers\">";
                $correct = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($this->answer->getID());
                if (isset($correct) && is_array($correct)) {
                    foreach ($correct as $correct_item) {
                        $correct_item_view = new Views_Exam_Question_Fnb_Text($correct_item);
                        if (isset($correct_item_view) && is_object($correct_item_view)) {
                            $html .= $correct_item_view->renderSpan();
                        }
                    }
                }

                $html .= "</div>";
                $html .= "<div class=\"span2\"><button class=\"btn btn-success fnb-correct-add\"> " . $SUBMODULE_TEXT["fnb"]["add_correct"] . "</button></div>";
                $html .= "</div>";
                break;
            default:
                $html .= "      <textarea class=\"answer-input\" id=\"question_answer_" . $this->answer->getOrder() . "\" name=\"question_answers[" . $this->answer->getOrder() . "]\">";
                $html .= $this->answer->getAnswerText();
                $html .= "      </textarea>";
                break;
        }

        $html .= "    </td>";
        $html .= "</tr>";
        $html .= "<tr class=\"answer-details-row hide\">";
        $html .= "  <td colspan=\"" . $td_count . "\" class=\"rationale\" >
                        <label for=\"question_answer_rationale_" . $this->answer->getOrder() . "\" class=\"question-answer-label\">Rationale:</label>
                        <textarea class=\"answer-input expandable\" id=\"question_answer_rationale_" . $this->answer->getOrder() . "\" name=\"question_answer_rationale[" . $this->answer->getOrder() . "]\">" . $this->answer->getRationale() . "</textarea>
                    </td>";
        $html .= "<td class=\"" . ($custom_grading_allowed ? "grading_weight" : "grading_weight hide" ) . "\">
                        <label for=\"question_answer_weight_" . $this->answer->getOrder() . "\" class=\"question-answer-label\">Weight %</label>
                        <input type=\"text\" class=\"answer-input expandable\" id=\"question_answer_weight_" . $this->answer->getOrder() . "\" name=\"question_answer_weight[" . $this->answer->getOrder() . "]\" value=\"" . $this->answer->getWeight() . "\" />
                    </td>";
        $html .= "</tr>";

        $html .= "</table>";
        $html .= "</div>";

        return $html;
    }

    public function compileFnbArray() {
        $answer = $this->answer;
        $correct_answers = $answer->getFnbText();

        $answer_array = array();
        foreach ($correct_answers as $correct_answer) {
            $answer_array[] = $correct_answer->getText();
        }
        return $answer_array;
    }
}