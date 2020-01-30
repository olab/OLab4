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
 * @copyright Copyright 2015 Regents of the University of California. All Rights Reserved.
 */

class Views_Exam_Grader_Question extends Views_Deprecated_Base {
    /**
     * @var Models_Exam_Question_Versions
     */
    protected $question;
    
    /**
     *
     * @var Models_Exam_Exam_Element
     */
    protected $element;
    
    /**
     *
     * @var Models_Exam_Progress_Responses
     */
    protected $response;


    /**
     *
     * @var Models_Exam_Progress_Response_Answers
     */
    protected $response_answers;

    /**
     * @param Models_Exam_Progress_Responses $response
     */
    public function __construct(Models_Exam_Progress_Responses $response) {
        $this->response         = $response;
        $this->response_answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($this->response->getID());
        $this->element          = Models_Exam_Exam_Element::fetchRowByID($this->response->getExamElementID());
        if ($this->element) {
            $this->question     = Models_Exam_Question_Versions::fetchRowByVersionID($this->element->getElementID());
        } else {
            $this->question     = NULL;
        }
    }

    public function render() {
        global $translate;
        $question = $this->question;

        if ($question) {
            $answers = $this->response_answers;
            $response_value = NULL;
            if (isset($answers) && is_array($answers)) {
                foreach ($answers as $answer) {
                    if (isset($answer) && is_object($answer)) {
                        $response_value = $answer->getResponseValue();
                        break;
                    }
                }
            }

            // Begin form
            $html = "<form method=\"post\" action=\"".ENTRADA_URL."/admin/exams/grade?".replace_query(array("step" => 2))."\" onsubmit=\"return false;\">\n";
            // Begin wrapper
            $html .= "<div class=\"grading-wrapper".($this->response->getGradedBy() !== null ? " graded" : "")."\">\n";

            // Show original question text
            $html .= "<div class=\"grading-question\">".$this->response->getQuestionCount().". ".$this->question->getQuestionText()."</div>\n";

            // Show correct answer and student response
            $html .= "<div class=\"grading-answer\">\n";
            $html .= "<p><strong>Correct Answer:</strong><br />";
            $html .= html_encode($this->question->getCorrectText())."</p>";
            $html .= "<div><strong>Student Response:</strong><br />\n";
            $html .= "<textarea rows=\"10\" style=\"cursor: text; background: #f7f7f7; color: #333\" readonly>".html_encode($response_value)."</textarea></div>\n";
            $html .= "</div>\n";

            // Show grader comments box and score input
            $response_id    = $this->response->getID();
            $comments_id    = "comments_".$response_id;
            $comments_name  = "comments[".$response_id."]";
            $score_id       = "scores_".$response_id;
            $score_name     = "scores[".$response_id."]";
            $score_text     = $this->response->getGradedBy() === null ? "" : $this->response->getScore();

            $html .= "<div class=\"grading-feedback\">\n";
            $html .= "<div><label for=\"$comments_id\"><strong>Feedback (optional):</strong></label><br />\n";
            $html .= "<textarea name=\"$comments_name\" id=\"$comments_id\" rows=\"5\">".html_encode($this->response->getGraderComments())."</textarea></div>\n";
            $html .= "<div>\n";
            $html .= "<label for=\"$score_id\" class=\"form-required\"><strong>Score:</strong></label><br />\n";
            $html .= "<input type=\"text\" name=\"$score_name\" id=\"$score_id\" value=\"$score_text\" /><span>&nbsp;/ ".(int)$this->element->getAdjustedPoints()."</span>\n";
            $html .= "<input class=\"btn btn-primary pull-right grading-save-btn\" data-id=\"$response_id\" type=\"submit\" value=\"".$translate->_("Save")."\" />\n";
            $html .= "<span class=\"grading-submit-spinner\" id=\"spinner_$response_id\" style=\"display: none;\"><i class=\"fa fa-lg fa-spinner fa-spin\"></i></span>\n";
            $html .= "</div>\n";
            $html .= "</div>\n";

            // End wrapper
            $html .= "</div>\n";
            // End form
            $html .= "</form>\n";
        } else {
            $html = "<div class=\"grading-question\">Question was deleted from the exam after a learner answered it.</div>\n";
        }

        return $html;
    }
}