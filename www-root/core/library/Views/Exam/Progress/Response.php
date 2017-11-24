<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Progress_Response extends Views_Deprecated_Base {
    protected
        $response,
        $response_answers,
        $progress,
        $exam,
        $exam_element,
        $shortName,
        $grading_scheme;

    public function __construct(Models_Exam_Progress_Responses $response) {
        $this->response         = $response;
        $this->response_answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
        $this->exam_element     = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
    }

    public function getExamElement(){
        return $this->exam_element;
    }

    public function renderProgressMenuItem($li = true, $access = true, $page = 1, $current_page = false) {
        $response           = $this->response;
        $response_answers   = $this->response_answers;
        $exam               = $response->getExam();

        $display_questions  = $exam->getDisplayQuestions();
        $flagged            = $response->getFlagQuestion();
        $scratchPad         = $response->getLearnerComments();
        $qt_shortname       = $response->getQuestionType();
        $element_type       = $this->exam_element->getElementType();

        $data = array();
        $data["display-questions"]  = $display_questions;
        $data["response_id"]        = $response->getExamProgressResponseID();
        $data["element-id"]         = $response->getExamElementID();
        $data["allow-access"]       = $access;
        $a = "";
        $render_arrow = 0;

        switch ($display_questions) {
            case 'one':
                $order = $response->getOrder();
                $data["page"] = $order + 1;
                if (isset($current_page) && ($current_page == $page || $current_page == $data["page"])) {
                    $render_arrow = 1;
                }
                break;
            case 'page_breaks':
                $data["page"] = $page;
                break;
            case 'all':
            default:
                break;
        }

        $response_answered = false;
        if (isset($response_answers) && is_array($response_answers) && !empty($response_answers)) {
            foreach ($response_answers as $response_answer) {
                if (isset($response_answer) && is_object($response_answer)) {
                    $response_value = $response_answer->getResponseValue();
                    if (isset($response_value) && $response_value != "" && $response_value != "0") {
                        $response_answered = true;
                    }
                }
            }
        }

        if ($render_arrow) {
            $a .= "<i class=\"arrow-icon fa fa-arrow-right\"></i>";
        }

        if ($response_answered === true) {
            $class = "response_answered";
        } else {
            $class = "response_unanswered";
        }

        if (isset($access) && $access === true) {
            $class .= " access_true";
        } else {
            $class .= " access_false disabled";
        }

        // current

        //html generation for anchor
        $a .= "<a class=\"exam_nav_link " . $class . "\"";

        if (isset($data) && is_array($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                $a .= " data-" . $key . "=\"" . $value . "\"";
            }
        }

        $a .= ">";

        if ($element_type == "question" && $qt_shortname != "text")  {
            $a .= "Q. " . $response->getQuestionCount();
        } else {
            $a .= "Text";
        }

        $a .= "</a>";

        if (isset($flagged) && $flagged == 1 && $element_type == "question") {
            $a .= "<i class=\"icon-flag-question fa fa-flag\"></i>";
        }

        if (isset($scratchPad) && $scratchPad != "" && $element_type == "question") {
            $a .= "<i class=\"scratchPad-icon fa fa-commenting\"></i>";
        }

        //html generation for LI
        if (isset($access) && $access === true) {
            $li_class = "progress_menu_item access_true";
        } else {
            $li_class = "progress_menu_item access_false";
        }

        $html = ($li ? "<li class=\"" . $li_class . ($render_arrow ? " current" : "") . "\">" : "");
        $html .= $a;
        $html .= ($li ? "</li>" : "");

        return $html;
    }

    public function renderAdminRow() {
        $response       = $this->response;
        $exam_element   = $this->exam_element;

        $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($exam_element->getElementID());
        $response_answers = $this->response_answers;

        if ($question_version) {
            $creator_id = $response->getCreatedBy();
            $updater_id = $response->getUpdatedBy();
            $grader_id  = $response->getGradedBy();

        if (isset($creator_id) && $creator_id > 0) {
            $creator = User::fetchRowByID($creator_id, null, null, 1);
            if ($creator) {
                $creator_name = $creator->getName();
            } else {
                $creator_name = NULL;
            }
        } else {
            $creator_name = NULL;
        }

        if (isset($updater_id) && $updater_id > 0) {
            $updater = User::fetchRowByID($updater_id, null, null, 1);
            if ($updater_id) {
                $updater_name = $updater->getName();
            } else {
                $updater_name = NULL;
            }
        } else {
            $updater_name = NULL;
        }

        if (isset($grader_id) && $grader_id > 0) {
            $grader = User::fetchRowByID($grader_id, null, null, 1);
            if ($grader) {
                $grader_name = $grader->getName();
            } else {
                $grader_name = NULL;
            }
        } else {
            $grader_name = NULL;
        }

        if (isset($response_answers) && is_array($response_answers) && !empty($response_answers)) {
            $response_value_array = array();
            $response_letter_array = array();
            $response_value_set = false;
            $response_letter_set = false;

            foreach ($response_answers as $response_answer) {
                if (isset($response_answer) && is_object($response_answer)) {
                    $response_value     = $response_answer->getResponseValue();
                    $response_letter    = $response_answer->getResponseElementLetter();

                    if (isset($response_value) && $response_value != "") {
                        $response_value_array[] = html_encode($response_value);
                        $response_value_set = true;
                    }

                    if (isset($response_letter) && $response_letter != "") {
                        $response_letter_array[] = html_encode($response_letter);
                        $response_letter_set = true;
                    }

                }
            }
        }

        $correct_letter = $question_version->getAdjustedMultipleChoiceCorrectText($exam_element->getID(), $response->getExamID());
        if (!$correct_letter) {
            $correct_letter = "N/A";
        }

        $html = "<tr class=\"response_record\" data-id=\"" . $exam_element->getElementID() . "\">\n";
        $html .= "<td>\n";
        $html .= $response->getQuestionCount();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $question_version->getQuestionID();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $question_version->getQuestionText();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $question_version->getQuestionCode();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $response->getQuestionType();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getLearnerComments() ? html_encode($response->getLearnerComments()) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        //todo check this to work with multiple answer.
        $html .= ($response_value_set ? implode(", ", $response_value_array) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response_letter_set ? implode(", ", $response_letter_array) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $correct_letter;
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getElement()->isScored() ? $response->getScore() : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getElement()->isScored() ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getGraderComments() ? html_encode($response->getGraderComments()) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getRegrade() ? $response->getRegrade() : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getGradedDate() ? date("m-d-Y H:i", $response->getGradedDate()) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $grader_name;
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getCreatedDate() ? date("m-d-Y H:i", $response->getCreatedDate()) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $creator_name;
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($response->getUpdatedDate() ? date("m-d-Y H:i", $response->getUpdatedDate()) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $updater_name;
        $html .= "</td>\n";
        $html .= "<td class=\"edit_menu\">\n";
        $html .= $this->getEditMenu($response);
        $html .= "</td>\n";
        $html .= "</tr>\n";

        return $html;
        }
    }

    public function gradeMC($user_points) {
        $response           = $this->response;
        $exam_element       = $this->exam_element;
        $post               = $this->response->getPost();
        $exam               = $post->getExam();
        $grading_scheme     = $this->grading_scheme;

        $answer_choices     = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($exam_element->getQuestionVersion()->getID());
        //variables for scoring the multi questions
        $possible_correct   = 0;
        $user_correct       = 0;
        $user_incorrect     = 0;
        $user_weight        = 0;

        if (isset($answer_choices) && is_array($answer_choices) && !empty($answer_choices)) {
            foreach ($answer_choices as $choice) {
                if (isset($choice) && is_object($choice)) {
                    $answer_response = Models_Exam_Progress_Response_Answers::fetchRowByAnswerElement($response->getID(), $choice->getID());
                    $choice_weight = $choice->getWeight();
                    //this section counts the number of correct and incorrect options for "Full" and adds up the weight for "Partial" and "Penalty"
                    if ($choice->getAdjustedCorrect($exam_element->getID(), $post->getExamID()) === 1) {
                        $possible_correct++;
                        //if $answer_response exists and is true then they answered the question correctly
                        if (isset($answer_response) && is_object($answer_response) && $answer_response->getResponseValue() == 1) {
                            $user_correct++;
                            if (isset($choice_weight)) {
                                $user_weight += $choice_weight;
                            }
                        } else if (isset($answer_response) && is_object($answer_response) && $answer_response->getResponseValue() == 0) {
                            //correct value unchecked
                            if (isset($grading_scheme) && $grading_scheme == "partial") {
                                //don't give points with partial
                                //only allow if the scheme is partial
                            } else if (isset($grading_scheme) && $grading_scheme == "penalty") {
                                //penalty takes away ...kinda like jeopardy game
                                $user_weight += -1 * abs($choice_weight);
                            }
                        } else {
                            //didn't answer
                            if (isset($grading_scheme) && $grading_scheme == "partial") {
                                //don't give points with partial
                                //only allow if the scheme is partial
                            } else if (isset($grading_scheme) && $grading_scheme == "penalty") {
                                //penalty takes away ...kinda like jeopardy game
                                $user_weight += -1 * abs($choice_weight);
                            }
                        }
                    } else {
                        //this is an incorrect option
                        if (isset($answer_response) && is_object($answer_response) && $answer_response->getResponseValue() == 1) {
                            $user_incorrect++;
                            if (isset($grading_scheme) && $grading_scheme == "partial") {
                                //don't give points with partial
                                //only allow if the scheme is partial
                            } else if (isset($grading_scheme) && $grading_scheme == "penalty") {
                                $points = -1 * abs($choice_weight);
                                //penalty takes away ...kinda like jeopardy game
                                $user_weight += $points;
                            }
                        } else if (isset($answer_response) && is_object($answer_response) && $answer_response->getResponseValue() == 0) {
                            if (isset($grading_scheme) && ($grading_scheme == "partial"|| $grading_scheme == "penalty")) {
                                //a response value of 0 means the item was unchecked
                                //in this case they should get credit
                                //only allow if the scheme is partial
                                $user_weight += $choice_weight;
                            }
                        } else {
                            //no response, give credit
                            if (isset($grading_scheme) && ($grading_scheme == "partial" || $grading_scheme == "penalty")) {
                                $user_weight += $choice_weight;
                            }
                        }
                    }
                }
            }
            // Single select vs multiple select questions are graded differently
            switch ($this->response->getQuestionType()) {
                case "mc_v_m":
                case "mc_h_m":
                    // We only care about the grading scheme for multiple select questions
                    switch($grading_scheme) {
                        case "partial":
                        case "penalty":
                            //use $user_weight total as a percentage of
                            // $exam_element->getPoints()
                            if ($user_weight) {
                                if ($user_weight > 100) {
                                    //don't allow a weight more than 100
                                    $user_weight = 100;
                                } else if ($user_weight < 0) {
                                    $user_weight = 0;
                                }
                                $response_points = $exam_element->getAdjustedPoints() * ($user_weight / 100);
                            } else {
                                $response_points = 0;
                            }

                            $user_points += $response_points;
                            $response->setScore($response_points);
                            break;
                        case "full":
                        default:
                            if ($possible_correct === $user_correct && $user_incorrect === 0) {
                                //full points all correct
                                $user_points += $exam_element->getAdjustedPoints();
                                $response->setScore($exam_element->getAdjustedPoints());
                            } else {
                                $response->setScore(0);
                            }
                            break;
                    }
                    break;
                case "mc_v":
                case "mc_h":
                default:
                    if (0 < $user_correct && $user_incorrect <= 0) {
                        $user_points += $exam_element->getAdjustedPoints();
                        $response->setScore($exam_element->getAdjustedPoints());
                    } else {
                        $response->setScore(0);
                    }
                    break;
            }
        }

        //after setting the score update the response object
        $response->update();

        return $user_points;
    }

    public function gradeFNB($user_points) {
        $response       = $this->response;
        $exam_element   = $this->exam_element;
        $post           = $this->response->getPost();
        $exam           = $post->getExam();

        $answers = $exam_element->getQuestionVersion()->getQuestionAnswers();
        if (isset($answers) && is_array($answers)) {
            $exam_element_parts = count($answers);
            $response_element_points = 0;
            foreach ($answers as $answer) {
                $answer_view        = new Views_Exam_Question_Answer($answer);
                $fnb_correct_text   = $answer_view->compileFnbArray();

                $answer_response    = Models_Exam_Progress_Response_Answers::fetchRowByAnswerElement($response->getID(), $answer->getID());
                if ($answer_response) {
                    $response_value = $answer_response->getResponseValue();
                } else {
                    $response_value = NULL;
                }

                if (isset($fnb_correct_text) && is_array($fnb_correct_text) && !empty($fnb_correct_text)) {
                    if (in_array($response_value, $fnb_correct_text)) {
                        //calculates partial score
                        $response_element_points += ($exam_element->getAdjustedPoints() / $exam_element_parts);
                    }
                }
            }

            $user_points += $response_element_points;
            $response->setScore($response_element_points);
            $response->update();
        }

        return $user_points;
    }

    public function gradeMatch($user_points) {
        $response       = $this->response;
        $exam_element   = $this->exam_element;
        $post           = $this->response->getPost();
        $exam           = $post->getExam();
        $match_stems    = $exam_element->getQuestionVersion()->getMatchStems();

        if (isset($match_stems) && is_array($match_stems)) {
            $stem_count = count($match_stems);
            $response_element_points = 0;
            foreach ($match_stems as $stem) {
                $match_id        = $stem->getID();
                $match_correct   = Models_Exam_Question_Match_Correct::fetchRowByMatchID($match_id);
                $answer_response = Models_Exam_Progress_Response_Answers::fetchRowByResponseIdMatchId($response->getID(), $match_id);

                if (isset($match_correct) && is_object($match_correct)) {
                    $match_correct_value = (int)$match_correct->getCorrect();
                } else {
                    $match_correct_value = NULL;
                }

                if (isset($answer_response) && is_object($answer_response)) {
                    $response_order     = (int)$answer_response->getResponseElementOrder();
                } else {
                    $response_order     = NULL;
                }

                if (isset($match_correct_value) && !empty($match_correct_value)) {
                    if ($match_correct_value === $response_order) {
                        //calculates partial score
                        $response_element_points += ($exam_element->getAdjustedPoints() / $stem_count);
                    }
                }
            }

            $user_points += $response_element_points;
            $response->setScore($response_element_points);
            $response->update();
        }

        return $user_points;
    }

    public function gradeResponse($exam_points, $user_points) {
        $exam_element   = $this->exam_element;
        $post           = $this->response->getPost();
        $exam           = $post->getExam();

        if (isset($exam_element) && is_object($exam_element)) {
            $is_thrown_out = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($exam_element->getID(), $post->getExamID(), "throw_out");
            if ($is_thrown_out) {
                return array("user_points" => $user_points, "exam_points" => $exam_points);
            }
            $is_full_credit = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($exam_element->getID(), $post->getExamID(), "full_credit");
            if ($is_full_credit) {
                $full_points = $exam_element->getAdjustedPoints();
                $this->response->setScore($full_points);
                $this->response->update();
                return array("user_points" => $user_points + $full_points, "exam_points" => $exam_points + $full_points);
            }
            
            //adds the current question points to the over all possible score (unless it is a bonus question)
            $is_bonus = Models_Exam_Adjustment::fetchRowByElementIDExamIDType($exam_element->getID(), $post->getExamID(), "make_bonus");
            if (!$is_bonus) {
                $exam_points    = $exam_points + $exam_element->getAdjustedPoints();
            }
            $shortName      = $exam_element->getQuestionVersion()->getQuestionType()->getShortname();
            $this->grading_scheme = $exam_element->getQuestionVersion()->getGradingScheme();

            switch ($shortName) {
                case "drop_m":
                case "mc_v_m":
                case "mc_h_m":
                case "drop_s":
                case "mc_h":
                case "mc_v":
                    $user_points = $this->gradeMC($user_points);
                    break;
                case "match" :
                    $user_points = $this->gradeMatch($user_points);
                    break;
                case "fnb":
                    $user_points = $this->gradeFNB($user_points);
                    break;
                case "essay":
                case "short":
                    $user_points += (double)$this->response->getScore();
                    break;
            }
        }

        return array("user_points" => $user_points, "exam_points" => $exam_points);
    }

    public function getEditMenu(Models_Exam_Progress_Responses $response) {

    }

    public function render() {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        if ($this->response !== null && $this->exam_element !== null) {
            return $this->renderAdminRow();
        } else {
            echo display_notice($MODULE_TEXT["response"]["text_no_available_posts"]);
        }
    }
}
