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

class Views_Exam_Grader_FnbQuestion extends Views_Exam_Grader_Question {
    public function render() {
        global $translate;

        // Begin form
        $html = "<form method=\"post\" action=\"".ENTRADA_URL."/admin/exams/grade?".replace_query(array("step" => 2))."\" onsubmit=\"return false;\">\n";
        // Begin wrapper
        $html .= "<div class=\"grading-wrapper".($this->response->getGradedBy() !== null ? " graded" : "")."\">\n";
        
        // Show question text with answers filled in
        $question_text = $this->question->getQuestionText();
        foreach ($this->response_answers as $answer) {
            $needle = "_?_";
            $all_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getAnswerElementID());
            $fnb_correct_text = array_map(function($blank) { return $blank->getText(); }, $all_fnb_text);
            $response_value = $answer->getResponseValue();
            $is_correct = in_array($response_value, $fnb_correct_text);
            $replacement = "<div class=\"input-append\">";
            $replacement .= "<input type=\"text\" value=\"".html_encode($response_value)."\" readonly />";
            $replacement .= "<div class=\"btn-group\">";
            $replacement .= "<button class=\"btn dropdown-toggle\" data-toggle=\"dropdown\" id=\"grading-mark-toggle-".$answer->getID()."\" tabindex=\"-1\">";
            if ($is_correct) {
                $replacement .= "<i style=\"color: #50bc00\" class=\"fa fa-check\"></i>";
            } else {
                $replacement .= "<i style=\"color: #d9534f\" class=\"fa fa-close\"></i>";
            }
            $replacement .= "</button>";
            $replacement .= "<ul class=\"dropdown-menu\">";
            $replacement .= "<li><a href=\"#\" class=\"grading-mark-correct\" data-id=\"".$answer->getID()."\">Mark as correct</a></li>";
            $replacement .= "<li><a href=\"#\" class=\"grading-mark-incorrect\" data-id=\"".$answer->getID()."\">Mark as incorrect</a></li>";
            $replacement .= "</ul>";
            $replacement .= "</div>";
            $replacement .= "</div>";
            $question_text = substr_replace($question_text, $replacement, strpos($question_text, $needle), strlen($needle));
            // Output a hidden field with the current grading status (correct or incorrect) of the input
            $payload = array("response_id" => $this->response->getID(), "epr_answer_id" => $answer->getID(), "correct" => $is_correct);
            $html .= "<input type=\"hidden\" class=\"correct-".$this->response->getID()."\" id=\"correct-".$answer->getID()."\" value=\"".html_encode(json_encode($payload))."\" />";
        }
        $html .= "<div class=\"grading-question\">".$this->response->getQuestionCount().". ".$question_text."</div>\n";
        
        // Show correct answer and student response
        $html .= "<div class=\"grading-answer\">\n";
        $html .= "<p><strong>Correct Answer(s):</strong>";
        foreach ($this->response_answers as $i => $answer) {
            $all_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getAnswerElementID());
            if (0 < count($all_fnb_text)) {
                $correct_answers = implode(" <em>or</em> ", array_map(function($blank) { return html_encode($blank->getText()); }, $all_fnb_text));
            } else {
                $correct_answers = "<em>none</em>";
            }
            $qanswer_id = $answer->getAnswerElementID();
            $html .= "<br /><strong>Blank ".($i + 1).":</strong> <span id=\"correct_answers_".$qanswer_id."\">".$correct_answers."</span>\n";
        }
        $html .= "</p>\n";
        $html .= "</div>\n";
        
        // Show grader comments box and score input
        $response_id    = $this->response->getID();
        $comments_id    = "comments_".$response_id;
        $comments_name  = "comments[".$response_id."]";
        $score_id       = "scores_".$response_id;
        $score_name     = "scores[".$response_id."]";
        $score_text     = (float)$this->response->getScore();

        $html .= "<div class=\"grading-feedback\">\n";
        $html .= "<div><label for=\"$comments_id\"><strong>Feedback (optional):</strong></label><br />\n";
        $html .= "<textarea name=\"$comments_name\" id=\"$comments_id\" rows=\"5\">".html_encode($this->response->getGraderComments())."</textarea></div>\n";
        $html .= "<div>\n";
        $html .= "<label for=\"$score_id\"><strong>Score:</strong></label><br />\n";
        $html .= "<input type=\"text\" name=\"$score_name\" id=\"$score_id\" value=\"$score_text\" readonly /><span>&nbsp;/ ".(int)$this->element->getAdjustedPoints()."</span>\n";
        $html .= "<input class=\"btn btn-primary pull-right grading-save-btn\" data-id=\"$response_id\" type=\"submit\" value=\"".$translate->_("Save")."\" />\n";
        $html .= "<span class=\"grading-submit-spinner\" id=\"spinner_$response_id\" style=\"display: none;\"><i class=\"fa fa-lg fa-spinner fa-spin\"></i></span>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
        
        // End wrapper
        $html .= "</div>\n";
        // End form
        $html .= "</form>\n";
        return $html;
    }
}