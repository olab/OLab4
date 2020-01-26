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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 Regents of the University of California. All Rights Reserved.
 */

class Views_Exam_Grader_FnbByQuestion extends Views_Deprecated_Base {
    public function __construct(Models_Exam_Exam_Element $exam_element) {
        $this->exam_element = $exam_element;
        $this->question = $exam_element->getQuestionVersion();
    }
    public function render() {
        global $translate;

        // Begin wrapper
        $html = "<div class=\"grading-wrapper\">\n";
        
        // Show question text with answers filled in
        $question_text = str_replace("_?_", "<input type=\"text\" readonly />", $this->question->getQuestionText());
        $html .= "<div class=\"grading-question\">".($this->exam_element->getOrder() + 1).". ".$question_text."</div>\n";
        
        // Show correct answers
        $html .= "<form method=\"post\" action=\"".ENTRADA_URL."/admin/exams/grade?".replace_query(array("step" => 2))."\">\n";
        $html .= "<div class=\"grading-answer\">\n";
        $html .= "<p><strong>Correct Answer(s):</strong></p>\n";
        $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->question->getID());
        foreach ($answers as $i => $answer) {
            $html .= "<p><strong>Blank ".($i + 1).":</strong>";
            $correct_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
            if (0 === count($correct_fnb_text)) {
                $html .= "<br /><em>None</em>";
            } else {
                foreach ($correct_fnb_text as $correct_blank) {
                    $checkbox_name = "mark_incorrect[".$correct_blank->getID()."]";
                    $checkbox_value = html_encode($correct_blank->getText());
                    $html .= "<br /><label><input name=\"$checkbox_name\" value=\"$checkbox_value\" type=\"checkbox\"> $checkbox_value</label>";
                }
            }
            $html .= "</p>\n";
        }
        $html .= "<input class=\"btn btn-primary\" type=\"submit\" value=\"Mark Incorrect\" />\n";
        $html .= "</div>\n";
        $html .= "</form>\n";
        
        // Show incorrect answers
        $html .= "<form method=\"post\" action=\"".ENTRADA_URL."/admin/exams/grade?".replace_query(array("step" => 2))."\">\n";
        $html .= "<div class=\"grading-answer\">\n";
        $html .= "<p><strong>Incorrect Answer(s):</strong></p>\n";
        foreach ($answers as $i => $answer) {
            $html .= "<p><strong>Blank ".($i + 1).":</strong>";
            // Get correct answers
            $correct_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
            $correct_answers = array_map(function($blank) { return $blank->getText(); }, $correct_fnb_text);
            // Get all answers
            $submissions = Models_Exam_Progress_Responses::fetchAllByExamElementID($this->exam_element->getID());
            $submitted_answers = array();
            foreach ($submissions as $submission) {
                $submitted_answers = array_merge($submitted_answers, Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($submission->getID()));
            }
            $submitted_answers = array_filter($submitted_answers, function($submitted_answer) use ($answer) { return $submitted_answer->getAnswerElementID() == $answer->getID(); });
            $all_answers = array_unique(array_map(function($answer) { return $answer->getResponseValue(); }, $submitted_answers));
            // Get the incorrect answers
            $incorrect_answers = array_diff($all_answers, $correct_answers);
            if (0 === count($incorrect_answers)) {
                $html .= "<br /><em>None</em>";
            } else {
                foreach ($incorrect_answers as $incorrect_answer) {
                    $checkbox_name = "mark_correct[".$answer->getID()."][]";
                    $checkbox_value = html_encode($incorrect_answer);
                    $html .= "<br /><label><input name=\"$checkbox_name\" value=\"$checkbox_value\" type=\"checkbox\"> ".html_encode($incorrect_answer)."</label>";
                }
            }
            $html .= "</p>\n";
        }
        $html .= "<input class=\"btn btn-primary\" type=\"submit\" value=\"Mark Correct\" />\n";
        $html .= "</div>\n";
        $html .= "</form>\n";
        
        // End wrapper
        $html .= "</div>\n";
        return $html;
    }
}