<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Adjustment extends Views_Deprecated_Base {
    protected $exam_element;
    protected $exam;
    protected $throw_out_adj;
    protected $full_credit_adj;

    public function __construct(Models_Exam_Exam_Element $exam_element, Models_Exam_Exam $exam) {
        $this->exam_element             = $exam_element;
        $this->exam                     = $exam;

        if ($this->exam_element && $this->exam_element->getElementType() === "question") {
            $this->question_version     = $this->exam_element->getQuestionVersion();
        } else {
            $this->question_version     = NULL;
        }
        if ($this->question_version) {
            $this->question_type        = $this->question_version->getQuestionType()->getShortname();
            $this->progress_responses   = array();
            $posts = Models_Exam_Post::fetchAllByExamID($this->exam->getID());
            if ($posts && is_array($posts)) {
                foreach ($posts as $post) {
                    if ($post && is_object($post)) {
                        $responses = Models_Exam_Progress_Responses::fetchAllByExamElementIDPostID($this->exam_element->getID(), $post->getID());
                        if ($responses && is_array($responses)) {
                            $this->progress_responses = array_merge($this->progress_responses, $responses);
                        }
                    }
                }
            }
        } else {
            $this->question_type        = NULL;
        }

        $this->throw_out_adj   = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($this->exam_element->getID(), $this->exam->getID(), "throw_out");
        $this->make_bonus_adj  = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($this->exam_element->getID(), $this->exam->getID(), "make_bonus");
        $this->full_credit_adj = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($this->exam_element->getID(), $this->exam->getID(), "full_credit");
    }

    public function render() {
        if (isset($this->exam_element)) {
            $element        = $this->exam_element;
            $element_id     = $element->getID();
            $element_order  = ($element->getOrder()) + 1;
        }

        if (isset($this->question_version)) {
            $question = $this->question_version;
        }

        $responses = $this->progress_responses;
        if (isset($responses) && is_array($responses) && !empty($responses)) {
            $comments_array = array();
            foreach ($responses as $response) {
                if (isset($response) && is_object($response)) {
                    if ((int)$response->getMarkFacultyReview() === 1) {
                        $l_comments = $response->getLearnerComments();
                        $comments_array[$response->getProxyID()] = $l_comments;
                    }
                }
            }
        }


        $html = "";
        $count = 3;
        if ($question) {
            if ("fnb" === $this->question_type) {
                $question_text = str_replace("_?_", "<input type=\"text\" />", $this->question_version->getQuestionText());
            } else {
                $question_text = $this->question_version->getQuestionText();
            }

            switch ($this->question_type) {
                case "mc_v":
                    $div_class = "exam-vertical-choice-question";
                    break;
                case "mc_h":
                    $div_class = "exam-horizontal-choice-question";
                    break;
                case "short":
                    $div_class = "exam-short-question";
                    break;
                case "essay":
                    $div_class = "exam-essay-question";
                    break;
                case "text":
                    $div_class = "exam-text-question";
                    break;
                case "fnb":
                    $div_class = "exam-fnb-question";
                    break;
                case "match":
                    $div_class = "exam-match-question";
                    break;
                default:
                    $div_class = "";
                    break;
            }

            $html .= "<form method=\"post\">\n";
            $html .= "  <input type=\"hidden\" name=\"exam_element_id\" value=\"" . $this->exam_element->getID() . "\" />\n";
            $html .= "    <div class=\"exam-question $div_class\">\n";
            $html .= "        <table class=\"question-table table table-bordered admin-table mc-v\">\n";
            $html .= "            <tr class=\"type\">";
            $html .= "                <td colspan=\"" . $count . "\">\n";
            if (isset($element_id)) {
                $html .= "                <span class=\"question-number\" data-element-id=\"" . $element_id . "\">" . $element_order . ".</span>\n";
            }
            $html .= "                    <span class=\"question-type\">ID: " . $question->getQuestionID() . " / Ver: " . $question->getVersionCount() . "</span>\n";

            $html .= "                </td>\n";
            $html .= "            </tr>\n";
            $html .= "            <tr class=\"heading\">\n";
            $html .= "                <td colspan=\"" . $count . "\">\n";
            $html .= "                    <div id=\"question_stem\"><div class=\"question_text\">\n";
            $html .= "                      " . $question_text . "\n";
            $html .= "                    </div></div>\n";
            $html .= "                </td>\n";
            $html .= "            </tr>\n";

            switch ($this->question_version->getQuestionType()->getShortname()) {
                case "mc_v_m":
                case "mc_h_m":
                    $multiple_answers = true;
                case "mc_v":
                case "mc_h":
                    $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->question_version->getID());
                    $stripe = true;
                    if ($answers && is_array($answers)) {
                        foreach ($answers as $order => $answer) {
                            if ($answer && is_object($answer)) {
                                $stripe = !$stripe;
                                $letter = chr(ord('A') + $order);
                                $checked = $answer->getAdjustedCorrect($this->exam_element->getID(), $this->exam->getID());
                                $html .= "<tr class=\"" . ($stripe ? "row-stripe " : "") . "question-answer-view\">\n";
                                if ($checked) {
                                    $name = "mark_incorrect[" . $answer->getID() . "]";
                                    $html .= "    <td class=\"edit-mc-answers\">\n";
                                    $html .= "        <input type=\"submit\" class=\"btn btn-danger\" name=\"$name\" value=\"Mark Incorrect\" />\n";
                                    $html .= "    </td>\n";
                                } else {
                                    $name = "mark_correct[" . $answer->getID() . "]";
                                    $html .= "    <td class=\"edit-mc-answers\">\n";
                                    $html .= "        <input type=\"submit\" class=\"btn btn-success\" name=\"$name\" value=\"Mark Correct\" />\n";
                                    $html .= "    </td>\n";
                                }
                                $html .= "    <td class=\"vertical-answer-input\">\n";
                                $html .= "        <span class=\"question-letter\">\n";
                                $html .=            $letter . ".";
                                $html .= "        </span>\n";
                                $html .= "        <input type=\"" . ($multiple_answers ? "checkbox" : "radio") . "\" class=\"question-control\" disabled" . ($checked ? " checked" : "") . " />\n";
                                $html .= "    </td>\n";
                                $html .= "    <td class=\"vertical-answer-label\">\n";
                                $html .= "        <label>\n";
                                $html .=             $answer->getAnswerText();
                                $html .= "        </label>\n";
                                $html .= "    </td>\n";
                                $html .= "</tr>\n";
                            }
                        }
                    }
                    break;
                case "short":
                    $html .= "<tr class=\"question-answer-view\">\n";
                    $html .= "    <td class=\"question-type-control\">\n";
                    $html .= "        <input class=\"question-control\" type=\"text\" />\n";
                    $html .= "    </td>\n";
                    $html .= "</tr>\n";
                    break;
                case "essay":
                    $html .= "<tr class=\"question-answer-view\">\n";
                    $html .= "    <td class=\"question-type-control\">\n";
                    $html .= "        <textarea class=\"expandable question-control\"></textarea>\n";
                    $html .= "    </td>\n";
                    $html .= "</tr>\n";
                    break;
                case "fnb":
                    $answers = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($this->question_version->getID());
                    if ($answers && is_array($answers)) {
                        foreach ($answers as $i => $answer) {
                            if ($answer && is_object($answer)) {
                                $fnb_text_all_obj = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($answer->getID());
                                $fnb_text_all = array_map(function ($blank) {
                                    return html_encode($blank->getText());
                                }, $fnb_text_all_obj);
                                $order = $i + 1;
                                $html .= "<tr>\n";
                                $html .= "    <td colspan=\"100\" class=\"extra-info\">\n";
                                $html .= "        <strong>Answer(s) for Blank $order:</strong> " . implode(" <em>or</em> ", $fnb_text_all) . "\n";
                                $html .= "    </td>\n";
                                $html .= "</tr>\n";
                            }
                        }
                    }
                    break;
                case "match":
                    $question       = $this->question_version;
                    $match_stems    = $question->getMatchStems();
                    $match_choices  = $question->getQuestionAnswers();

                    if ($match_stems && is_array($match_stems) && !empty($match_stems)) {
                        foreach ($match_stems as $i => $stem) {
                            if ($stem && is_object($stem)) {
                                $order          = $i + 1;

                                $match_text = $stem->getMatchText();
                                $paragraph_count = substr_count($match_text, "<p>");
                                if ($paragraph_count === 1) {
                                    // strip out p tag
                                    $match_text = str_replace("<p>", "", $match_text);
                                    $match_text = str_replace("</p>", "", $match_text);
                                }

                                $correct_choice = Models_Exam_Question_Match_Correct::fetchRowByMatchID($stem->getID());
                                if ($correct_choice && is_object($correct_choice)) {
                                    $correct_answer = $correct_choice->getAnswer();
                                }
                                $html .= "<tr class=\"question-answer-view\">\n";
                                $html .= "    <td class=\"vertical-answer-input\">\n";
                                $html .= "        <div>\n";
                                $html .= "          ". $order . ". " . $match_text . "\n";
                                $html .= "        </div>\n";
                                $html .= "        <div>\n";
                                $html .= "            <select class=\"form-control question-control\">\n";
                                $html .= "                <option>-- select an option --</option>\n";
                                if ($match_choices && is_array($match_choices) && !empty($match_choices)) {
                                    foreach ($match_choices as $j => $choice) {
                                        if ($choice && is_object($choice)) {
                                            $letter = chr(ord('A') + $j);
                                            $selected = ($correct_answer->getID() == $choice->getID() ? 1 : 0);
                                            $html .= "          <option" . ($selected ? " selected" : "") . ">" . $letter . ". " . $choice->getAnswerText() . "</option>\n";
                                        }
                                    }
                                }
                                $html .= "            </select>\n";
                                $html .= "        </div>\n";
                                $html .= "    </td>\n";
                                $html .= "</tr>\n";
                            }
                        }
                    }

                    break;
                case "text":
                default:
                    break;
            }
            if ($this->question_version->getQuestionDescription()) {
                $html .= "<tr>\n";
                $html .= "    <td colspan=\"100\" class=\"extra-info\">\n";
                $html .= "        <strong>Description:</strong>\n";
                $html .= "          " . html_encode($this->question_version->getQuestionDescription()) . "\n";
                $html .= "    </td>\n";
                $html .= "</tr>\n";
            }
            if ($this->question_version->getRationale()) {
                $html .= "<tr><td colspan=\"100\" class=\"extra-info\"><strong>Rationale:</strong> " . $this->question_version->getRationale() . "</td></tr>\n";
            }
            if ($this->question_version->getCorrectText()) {
                $html .= "<tr><td colspan=\"100\" class=\"extra-info\"><strong>Correct Text:</strong> " . html_encode($this->question_version->getCorrectText()) . "</td></tr>\n";
            }



            if (isset($comments_array) && is_array($comments_array) && !empty($comments_array)) {
                $count = 0;
                $html .= "<tr>\n";
                $html .= "    <td colspan=\"3\">\n";
                $html .= "        <div class=\"learner_comments lc_review\">\n";
                $html .= "            <strong class=\"learner_comments_title\">\n";
                $html .= "            Learner Response for Faculty Review:\n";
                $html .= "            </strong>\n";

                foreach ($comments_array as $proxy_id => $comments) {
                    $count++;
                    $html .= "            <textarea readonly=\"\" class=\"" . ($count == 1 ? "lc_review_comments active" : "lc_review_comments") . " span12\" data-proxy-id=\"" . $proxy_id . "\" data-element-id=\"" . $element_id . "\" data-response-count=\"" . $count . "\">\n";
                    $html .=               html_encode($comments);
                    $html .= "            </textarea>\n";
                }

                $html .= "             <div class=\"btn-group pull-right\">\n";
                $html .= "                <a class=\"btn lcr_backwards" . ($count >= 0 ? " disabled": "") . "\"  data-element-id=\"" . $element_id . "\" >\n";
                $html .= "                    <i class=\"fa fa-chevron-left\"></i>\n";
                $html .= "                    Previous\n";
                $html .= "                </a>\n";
                $html .= "                <a class=\"btn lcr_advance" . ($count <= 1 ? " disabled": "") . "\" data-element-id=\"" . $element_id . "\" >\n";
                $html .= "                    Next\n";
                $html .= "                    <i class=\"fa fa-chevron-right\"></i>\n";
                $html .= "                </a>\n";
                $html .= "             </div>\n";
                $html .= "             <p class=\"pull-left\"><small><span class=\"learner_comments_title response_number\" data-element-id=\"" . $element_id . "\">1 of " . count($comments_array) . "</span></small></p>\n";
                $html .= "        </div>\n";
                $html .= "    </td>\n";
                $html .= "</tr>\n";
            }

            // Show button for editing question points
            $html .= "<tr>\n";
            $html .= "    <td colspan=\"100\" class=\"extra-info\">\n";
            $html .= "        <strong>Points:</strong>\n";
            $html .= "        <div class=\"input-append update-points\">\n";
            $html .= "            <input class=\"span2\" type=\"text\" name=\"adjusted_points\" value=\"" . html_encode($this->exam_element->getAdjustedPoints()) . "\" />\n";
            $html .= "            <input type=\"submit\" class=\"btn btn-default\" name=\"update_points\" value=\"Update Question Points\" />\n";
            $html .= "        </div>\n";
            $html .= "    </td>\n";
            $html .= "</tr>\n";

            $throw_out_name         = $this->throw_out_adj ? "undo_throw_out" : "throw_out";
            $throw_out_btn_text     = $this->throw_out_adj ? "Undo Throw Out Question" : "Throw Out Question";
            $throw_out_disabled     = $this->full_credit_adj || $this->make_bonus_adj ? " disabled " : "";
            $make_bonus_name        = $this->make_bonus_adj ? "undo_make_bonus" : "make_bonus";
            $make_bonus_btn_text    = $this->make_bonus_adj ? "Undo Make Bonus" : "Make Bonus";
            $make_bonus_disabled    = $this->throw_out_adj || $this->full_credit_adj ? " disabled " : "";
            $full_credit_name       = $this->full_credit_adj ? "undo_full_credit" : "full_credit";
            $full_credit_btn_text   = $this->full_credit_adj ? "Undo Give Full Credit" : "Give Full Credit";
            $full_credit_disabled   = $this->throw_out_adj || $this->make_bonus_adj ? " disabled " : "";

            // Show buttons for throwing out questions and giving full credit
            $html .= "<tr>\n";
            $html .= "    <td colspan=\"100\" class=\"extra-info action-buttons\">\n";
            $html .= "        <input type=\"submit\" class=\"btn btn-danger\" name=\"" . $throw_out_name . "\" value=\"" . $throw_out_btn_text . "\" " . $throw_out_disabled . "/>\n";
            $html .= "        <input type=\"submit\" class=\"btn btn-default\" name=\"" . $make_bonus_name . "\" value=\"" . $make_bonus_btn_text . "\" " . $make_bonus_disabled . "/>\n";
            $html .= "        <input type=\"submit\" class=\"btn btn-default\" name=\"" . $full_credit_name . "\" value=\"" . $full_credit_btn_text . "\" " . $full_credit_disabled . "/>\n";
            $html .= "    </td>\n";
            $html .= "</tr>\n";
            $html .= "</table>\n";
            // Close outer tags
            $html .= "</div>\n";
            $html .= "</form>\n";
        }
        return $html;
    }
}
