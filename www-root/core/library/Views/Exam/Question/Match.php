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

class Views_Exam_Question_Match extends Views_Deprecated_Base {
    protected $default_fieldset = array(
        "match_id",
        "version_id",
        "match_text",
        "order",
        "updated_date",
        "updated_by",
        "deleted_date"
    );

    protected $table_name               = "exam_question_match";
    protected $primary_key              = "match_id";
    protected $default_sort_column      = "order";

    protected $match, $question_version, $short_name;

    public function __construct(Models_Exam_Question_Match $match) {
        $this->match = $match;
    }

    public function buildMatchTableHeader(array $control_array = NULL, array $data_attr_array = NULL, $td_count) {
        global $translate;
        $short_name = $this->short_name;

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"];

        if (!$data_attr_array) {
            $data_attr_array = array();
        }
        $data_attr_array["match-id"] = $this->match->getID();
        $data_attr_array["version-id"] = $this->match->getVersionID();
        $data_attr_array["sortable-element-id"] = $this->match->getOrder();

        $html = "<div class=\"exam-question-match question-container\" id=\"match-stem-id-" . $this->match->getID() ."\"";
        if (!empty($data_attr_array)) {
            foreach ($data_attr_array as $key => $data_attr) {
                $html .= " data-" . $key . "=\"" . $data_attr . "\"";
            }
        }

        $html .= ">";
        $html .= "<table class=\"table table-striped table-bordered\">";
        $html .= "  <tr class=\"type\">";
        $html .= "      <td colspan=\"" . $td_count . "\" class=\"match-header\">";
        $html .= "          <span class=\"match-number\"> " . $SUBMODULE_TEXT["match"]["label_row"] . ": " . $this->match->getOrder() . "</span>";
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

    public function renderMatch(array $data_attr_array = NULL, $question_type_short_name = NULL) {
        global $translate;

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"]["answers"];

        $match = $this->match;
        if (isset($match) && is_object($match) && $match->getVersionID() > 0) {
            $this->question_version = Models_Exam_Question_Versions::fetchRowByVersionID($match->getVersionID());
            $qv                 = $this->question_version;
            $this->short_name   = $qv->getQuestionType()->getShortname();
            $short_name         = $this->question_type;
            //built answer for question
            $answers            = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($qv->getVersionID());
            $correct_option     = Models_Exam_Question_Match_Correct::fetchRowByMatchID($match->getID());
        } else {
            $short_name          = $question_type_short_name;
            $this->question_type = $short_name;
            $answers             = NULL;
        }

        $answer_options_array = array();
        if (!isset($correct_option)) {
            $selected = " selected=selected";
        } else {
            $selected = "";
        }

        $answer_options_array[] = "<option disabled=disabled" . $selected . ">Select a correct answer</option>";
        if (isset($answers) && is_array($answers) && !empty($answers)) {
            foreach ($answers as $answer) {
                if (isset($correct_option) && is_object($correct_option) && ((int)$correct_option->getCorrect() === (int) $answer->getOrder())) {
                    $selected = " selected=selected";
                } else {
                    $selected = "";
                }
                $answer_options_array[] = "<option value=\"" . $answer->getOrder() . "\"" . $selected . " data-option-number=\"" . $answer->getOrder() . "\">" . $answer->getOrder() . "</option>";
            }
        }

        $control_array = array(
            array("<select class=\"add-match-correct\" match-order=\"" . $this->match->getOrder() . "\">" . implode("\n", $answer_options_array) . "</select>"),
            array(
                "<span class=\"btn select-match\">
                    <i class=\"icon-select-match fa fa-2x fa-square-o\" data-sortable-element-id=\"" . $this->match->getOrder() . "\"></i>
                </span>"
            ),
            array(
                "<a class=\"btn move\" title=\"Move\" href=\"#\"><i class=\"icon-move\"></i></a>"
            )
        );
        $td_count = 2;

        $html = $this->buildMatchTableHeader($control_array, $data_attr_array, 2);

        $html .= "<tr class=\"stem-row\">";
        $html .= "  <td colspan=\"2\" class=\"stem-row-text\">";
        $html .= "      <textarea class=\"match-input\" id=\"item_stem_" . $this->match->getOrder() . "\" name=\"question_item_stems[" . $this->match->getOrder() . "]\">";
        $html .= $this->match->getMatchText();
        $html .= "      </textarea>";
        $html .= "    </td>";
        $html .= "</tr>";
        $html .= "</table>";
        $html .= "</div>";

        return $html;
    }
}