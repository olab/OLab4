<?php
/**available*/

class Views_Exam_Progress extends Views_Deprecated_Base {
    protected   $progress,
                $post,
                $limit,
                $total_pages,
                $total_questions,
                $total_elements,
                $current_page,
                $page_break_set,
                $grade_book_id;

    public function __construct(Models_Exam_Progress $progress) {
        $this->progress = $progress;
        $this->post = $progress->getExamPost();
        $this->limit = $this->setLimitFromDisplayQuestions();
        $this->current_page = 1;
        $this->total_questions = $this->progress->countExamProgressResponseQuestions();
        $this->total_elements = $this->progress->countExamProgressResponseElements();
        $this->total_pages = (NULL !== $this->limit) ? ceil((int)$this->total_elements / (int)$this->limit) : NULL;
    }

    private function getPageBreakPages($page) {
        if (NULL === $this->page_break_set) {
            $limit = $this->limit;
            $include_page_breaks = true;
            $progress_responses = $this->progress->getExamProgressResponses($limit, $page, $include_page_breaks);
            $progress_set = array();
            $page_set = 1;

            foreach ($progress_responses as $progress_response) {
                $progress_response_element = $progress_response->getElement();
                if ($progress_response_element->getElementType() == "page_break") {
                    $page_set++;
                } else {
                    $progress_set[$page_set][] = $progress_response;
                }
            }

            $this->page_break_set = $progress_set;
            $this->total_pages = count($progress_set);
        } else {
            $progress_set = $this->page_break_set;
        }

        return (NULL !== $page) ? $progress_set[$page] : $progress_set;
    }

    private function setLimitFromDisplayQuestions(){
        $display = $this->post->getExam()->getDisplayQuestions();
        switch ($display) {
            case "all":
                $this->limit = $this->total_elements;
                break;
            case "one":
                $this->limit = 1;
                break;
            case "page_breaks":
                $this->limit = $this->progress->countExamProgressResponsePageBreaks();
                break;
            default:
                $this->limit = $this->total_elements;
                break;
        }
        return $this->limit;
    }

    private function getPage($page = 1, $limit = NULL) {
        if (!isset($limit)) {
            $display_questions = $this->progress->getExamPost()->getExam()->getDisplayQuestions();
            if ($display_questions) {
                switch ($display_questions) {
                    case "all":
                        $this->limit = NULL;
                        $responses = $this->progress->getExamProgressResponses($this->limit, $page);
                        break;
                    case "one":
                        $this->limit = 1;
                        $elements       = (int) $this->total_elements;
                        $limits         = (int) $this->limit;
                        $this->total_pages = (int) ceil($elements / $limits);
                        $responses = $this->progress->getExamProgressResponses($this->limit, $page);
                        break;
                    case "page_breaks":
                        $this->limit = NULL;
                        $responses = $this->getPageBreakPages($page);
                        break;
                    default:
                        $this->limit = NULL;
                        $responses = $this->progress->getExamProgressResponses($this->limit, $page);
                        break;
                }
            } else {
                $this->limit = (int) $limit;
                $responses = $this->progress->getExamProgressResponses($this->limit, $page);
            }
        } else {
            $this->limit = (int) $limit;
            $responses = $this->progress->getExamProgressResponses($this->limit, $page);
        }
        return $responses;
    }

    public function renderExamProgress($page = 1, $limit = NULL) {
        /*
         * Determine how to display questions
         * This is currently read from the exam level settings, if $limit is not set.
         * You can override the value in exam settings by providing a value for $limit
        */
        $this->limit = (NULL !== $limit) ? $limit : $this->limit;
        $responses = $this->getPage($page, $limit);

        $this->current_page = (isset($page)) ? (int) $page : 1;

        $display_questions = $this->progress->getExamPost()->getExam()->getDisplayQuestions();
        $randomize_answers = $this->progress->getExamPost()->getExam()->getRandomAnswers();
        if ($display_questions) {
            switch ($display_questions) {
                case "one":
                    $last_response_current_page = $responses[0];
                    break;
                case "page_breaks":
                    $response_count = count($responses) - 1;
                    $last_response_current_page = $responses[$response_count];
                    break;
                case "all":
                default:
                    $last_response_current_page = NULL;
                    break;
            }
        }

        if (isset($last_response_current_page) && is_object($last_response_current_page)) {
            $allow_view = $this->allowView($last_response_current_page);
        } else {
            $allow_view = 1;
        }

        $html = NULL;
        if (isset($responses) && is_array($responses)) {
            foreach ($responses as $response) {
                if (isset($response) && is_object($response)) {
                    $element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
                    switch ($element->getElementType()) {
                        case "question" :
                            $last_question_type = "question";
                            $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                            $question_view = new Views_Exam_Question($question);
                            $render = $question_view->render(
                                true,
                                NULL,
                                array("element-id" => $response->getExamElementID()),
                                "details",
                                false,
                                $this->progress,
                                $response,
                                NULL,
                                $allow_view,
                                0,
                                array(),
                                $randomize_answers
                            );
                            $html .= $render;
                            break;
                        case "text" :
                            $element_view = new Views_Exam_Exam_Element($element);
                            $html .= $element_view->render(true, "", $response); //render using "exam_mode"
                            $last_question_type = "text";
                            break;
                    }
                    /*
                     * Log that the question has been viewed.
                     * @todo consider making this logging optional.. since it should really only be logged for exam takers
                     */
                    $view_date = $response->getViewDate();

                    if (!isset($view_date)) {
                        $response->setViewDate(time());
                        $response->update();
                    }
                }
            }
        }

        return $html;
    }

    public function allowView(Models_Exam_Progress_Responses $response) {
        global $ENTRADA_USER;
        $post = $this->progress->getExamPost();
        $exam = $this->progress->getExamPost()->getExam();
        $response_answers = Models_Exam_Progress_Response_Answers::fetchRowByID($response->getID());

        $response_answered = false;
        if (isset($response_answers) && is_array($response_answers) && !empty($response_answers)) {
            foreach ($response_answers as $response_answer) {
                if (isset($response_answer) && is_object($response_answer)) {
                    $response_value = $response_answer->getResponseValue();
                    if (isset($response_value) && $response_value != "") {
                        $response_answered = true;
                    }
                }
            }
        }

        $creator = $response->getCreatedBy();

        $exam_delivery_mode = $exam->getDisplayQuestions();
        if (isset($exam_delivery_mode) && $post->getBacktrack() == 0) {
            switch($exam_delivery_mode) {
                case "one" :
                case "page_breaks" :
                    //check next question
                    $next_response          = $response->fetchNextResponse();
                    if (isset($next_response) && is_object($next_response)) {
                        $viewed_next_response = $next_response->getViewDate();
                    } else {
                        $viewed_next_response = NULL;
                    }

                    break;
            }
        }

        if (isset($viewed_next_response) && $viewed_next_response < time() && $response_answered === true && $creator == $ENTRADA_USER->getID()) {
            $allow_view = 0;
        } else {
            $allow_view = 1;
        }

        return $allow_view;
    }

    public function checkPreviousPageResponses($page = 1, $limit = NULL) {
        if ($page === 1) {
            return 1;
        } else {
            $previous_page = $page - 1;
            $responses = $this->getPage($previous_page, $limit);
            $responses_saved = Models_Exam_Progress_Responses::checkResponsePageSaved($responses);
            if ($responses_saved === true) {
                return $page;
            } else {
                //check previous page
                if ($previous_page === 1) {
                    return 1;
                } else {
                    return $this->checkPreviousPageResponses($previous_page, $limit);
                }
            }
        }
    }

    public function renderPageLinks($current_page = 1, $secure = false) {
        $post = $this->progress->getExamPost();
        $backtrack = $post->getBacktrack();
        $entrada_url = ($secure === true) ? ENTRADA_URL . "/secure" : ENTRADA_URL;
        if ($this->total_pages >= 1) {
            $this->current_page = isset($current_page) ? (int) $current_page : 1;
            $next_page = $this->current_page+1;
            $previous_page = $this->current_page-1;
            $html = "";
            if (1 !== $this->current_page && (int)$backtrack === 1) {
                $html .= "<div id=\"exam_previous_page\">";
                $html .= "<a class=\"btn btn-link\" href=\"" . $entrada_url . "/exams?section=attempt&action=resume&continue=true&id=" . $this->progress->getPostID() . "&progress_id=" . $this->progress->getID() . "&page=" . $previous_page . "\">";
                $html .= "<i class=\"fa fa-chevron-left\"></i>";
                $html .= "</a>";
                $html .= "</div>";
            }
            if ($this->current_page < $this->total_pages) {
                $html .= "<div id=\"exam_next_page\">";
                $html .= "<a class=\"btn btn-link\" href=\"" . $entrada_url . "/exams?section=attempt&action=resume&continue=true&id=" . $this->progress->getPostID() . "&progress_id=" . $this->progress->getID() . "&page=" . $next_page . "\">";
                $html .= "<i class=\"fa fa-chevron-right\"></i>";
                $html .= "</a>";
                $html .= "</div>";
            }
        }

        return $html;
    }

    public function renderAdminRow($export = "html", $not_started = 1, $show_edit = 1) {
        $progress = $this->progress;
        $post = $this->progress->getExamPost();

        $student = User::fetchRowByID($progress->getProxyID(), null, null, 1);
        $creator_id = $progress->getCreatedBy();
        $updater_id = $progress->getCreatedBy();

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
            if ($updater) {
                $updater_name = $updater->getName();
            } else {
                $updater_name = NULL;
            }
        } else {
            $updater_name = NULL;
        }

        if ($student) {
            $student_name = $student->getFullname();
            $student_number = $student->getNumber();
        } else {
            $student_name = "Proxy ID: ". $progress->getProxyID();
            $student_number = NULL;
        }

        //generates submission information
        $submission_date = $progress->getSubmissionDate();
        $late = $progress->getLate();

        if ($submission_date) {
            if ($late > 0) {
                $submission_class = "late-submission";
            } else {
                $submission_class = "";
            }
            $submission_date_return = "<span class=\"" . $submission_class . "\">" . date("m-d-Y H:i", $progress->getSubmissionDate()) . "</span>";
        } else {
            $submission_date_return = "N/A";
        }

        if ("submitted" === $progress->getProgressValue()) {
            $gradable_question_count = Models_Exam_Grader::fetchGradableQuestionCount($post->getID());
            $graded_question_count = Models_Exam_Grader::fetchGradedQuestionCount($progress->getID());
            if ($gradable_question_count === $graded_question_count) {
                $status = "Graded";
            } else {
                $status = "Submitted";
            }
        } else {
            $status = "In Progress";
        }

        if ($export === "html") {
            $export_data = "<tr class=\"progress_record\" data-id=\"" . $progress->getID() . "\">\n";
            $export_data .= "<td>\n";
            $export_data .= $student_name;
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $student_number;
            $export_data .= "</td>\n";
            $export_data .= "<td class=\"progress_value\" data-id=\"" . $progress->getID() . "\">\n";
            $export_data .= $status;
            $export_data .= "</td>\n";
            $export_data .= "<td class=\"submission_date\" data-id=\"" . $progress->getID() . "\">\n";
            $export_data .= $submission_date_return;
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $late;
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $progress->getExamPoints();
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $progress->getExamValue();
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            if ("submitted" === $progress->getProgressValue()) {
                $export_data .= $progress->getExamScore()."%";
            }
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= ($progress->getCreatedDate() ? date("m-d-Y H:i", $progress->getCreatedDate()) : "N/A");
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $creator_name;
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= ($progress->getStartedDate() > 0 ? date("m-d-Y H:i", $progress->getStartedDate()) : "N/A");
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= ($progress->getUpdatedDate() ? date("m-d-Y H:i", $progress->getUpdatedDate()) : "N/A");
            $export_data .= "</td>\n";
            $export_data .= "<td>\n";
            $export_data .= $updater_name;
            $export_data .= "</td>\n";

            if ($show_edit) {
                $export_data .= "<td class=\"edit_menu\">\n";
                $export_data .= $this->getEditMenu();
                $export_data .= "</td>\n";
            }

            $export_data .= "</tr>\n";
        } else {
            $export_data = array(
                $student_name,
                $student_number,
                ($not_started ? "Not Started" : $status),
                ($not_started ? "N/A" : $submission_date_return),
                ($not_started ? "" : $late),
                ($not_started ? "" : $progress->getExamPoints()),
                ($not_started ? "" : $progress->getExamValue()),
                ($not_started ? "" : ($progress->getProgressValue() === "submitted" ? $progress->getExamScore()."%" : "")),
                ($not_started ? "" : ($progress->getCreatedDate() ? date("m-d-Y H:i", $progress->getCreatedDate()) : "N/A")),
                ($not_started ? "" : $creator_name),
                ($not_started ? "" : ($progress->getStartedDate() > 0  ? date("m-d-Y H:i", $progress->getStartedDate()) : "N/A")),
                ($not_started ? "" : ($progress->getUpdatedDate() ? date("m-d-Y H:i", $progress->getUpdatedDate()) : "N/A")),
                ($not_started ? "" : $updater_name),
                ($not_started && $show_edit ? "" : $this->getEditMenu())
            );
        }

        return $export_data;
    }

    public function getEditMenu() {
        global $ENTRADA_ACL, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $MENU_TEXT = $MODULE_TEXT["exams"]["activity"]["progress"]["menu"];

        $exam = Models_Exam_Exam::fetchRowByID($this->progress->getExamID());

        $html = "<div class=\"btn-group\">\n";
        $html .= "<button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
        $html .= "<i class=\"icon-pencil\"></i>\n";
        $html .= "</button>\n";
        $html .= "<ul class=\"dropdown-menu toggle-left\">\n";

        $can_update = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
        $can_view   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read");
        $can_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete");

        if ($can_view) {
            $html .= "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=progress&id=" . $this->progress->getID() . "\">" . $MENU_TEXT["view"] . "</a></li>\n";
        }
        if ($can_update) {
            $html .= "<li><a class=\"progress_menu\" href=\"#\" data-toggle=\"modal\" data-target=\"#reopen-modal\" data-id=\"" . $this->progress->getID() . "\">" . $MENU_TEXT["reopen"] . "</a></li>\n";
        }
        if ($can_delete) {
            $html .= "<li><a class=\"progress_menu\" href=\"#\" data-toggle=\"modal\" data-target=\"#delete-modal\" data-id=\"" . $this->progress->getID() . "\">" . $MENU_TEXT["delete"] . "</a></li>\n";
        }
        if ($can_view) {
            $html .= "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=report-faculty-feedback&id=" . $this->progress->getExamID() . "&progress_id=" . $this->progress->getID() . "\">" . $MENU_TEXT["feedback"] . "</a></li>\n";
        }

        $html .= "</ul>\n";
        $html .= "</div>\n";
        return $html;
    }

    public function renderPublicRow() {
        global $translate, $ENTRADA_USER;
        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["posts"];
        $score_report = 0;
        $feedback_btn = 0;
        $incorrect_btn = 0;

        $post                   = $this->progress->getExamPost();
        $resume_allowed         = $post->isResumeAttemptAllowedByUser($ENTRADA_USER);
        $release_score          = $post->getReleaseScore();
        $release_feedback       = $post->getReleaseFeedback();
        $score_start_valid      = $post->isScoreStartValid();
        $score_end_valid        = $post->isScoreEndValid();
        $incorrect_btn          = $post->getReleaseIncorrectResponses();

        if (($release_score || $release_feedback) && $this->progress->getProgressValue() == "submitted") {
            if ($score_start_valid && $score_end_valid) {
                $score_report = 1;

                if ($release_feedback) {
                    $feedback_btn = 1;
                }
            }
        }

        $html = "<tr class=\"progress_record\" data-id=\"" . $this->progress->getID() . "\">\n";
            $html .= "<td class=\"progress_value\">\n";
                $progress_value = $this->progress->getProgressValue();
                switch ($progress_value) {
                    case "submitted":
                        $html .= $SUBMODULE_TEXT["text_submitted"];
                    break;
                    case "inprogress":
                        $html .= $SUBMODULE_TEXT["text_started"];
                    break;
                    default:
                        $html .= $progress_value;
                    break;
                }
            $html .= "</td>\n";
            $html .= "<td class=\"submission_date\" data-id=\"" . $this->progress->getID() . "\">\n";
                $html .= ($this->progress->getSubmissionDate() ? date("M j Y h:i a", $this->progress->getSubmissionDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($score_report ? $this->progress->getExamPoints() : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($score_report == 1 ? $this->progress->getExamValue() : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($score_report ? $this->progress->getExamScore()."%" : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
            $category_report = Models_Exam_Category::fetchRowByPostID($this->progress->getPostID());
            if ($this->progress->getProgressValue() != "submitted") {
                if ($resume_allowed) {
                    $html .= "<button class=\"btn resume\" data-id=\"" . $this->progress->getID() . "\">" . $SUBMODULE_TEXT["btn_resume"] . "</button>\n";
                } else {
                    $html .= "<button class=\"btn resume\" data-id=\"" . $this->progress->getID() . "\" disabled=disabled>" . $SUBMODULE_TEXT["btn_resume"] . "</button>\n";
                }
            } else if ($feedback_btn || ($category_report && $category_report->isReportReleased() && $category_report->isUserInAudience($ENTRADA_USER))) {
                $html .= "<div class=\"btn-group\">";
                if ($feedback_btn) {
                    $html .= "<a href=\"". ENTRADA_RELATIVE . "/exams?section=feedback&progress_id=". $this->progress->getID() . "\" class=\"btn\" title=\"". $SUBMODULE_TEXT["btn_feedback"]. "\"'>". $SUBMODULE_TEXT["btn_feedback"] . "</a>\n";
                }

                if ($category_report && $category_report->isReportReleased() && $category_report->isUserInAudience($ENTRADA_USER)){
                    $html .= "<a href=\"". ENTRADA_RELATIVE . "/exams/reports?section=category&progress_id=". $this->progress->getID() . "\" class=\"btn\">" . $SUBMODULE_TEXT["btn_category"] . "</button>\n";
                }
                $html .= "</div>";
            } else {
                $html .= $SUBMODULE_TEXT["feedback_not_aval"];
            }
            $html .= "</td>\n";
        $html .= "</tr>\n";

        return $html;
    }

    public function renderExamProgressBar() {
        $response_saved = $this->progress->countProgressResponses();
        $questions = $this->progress->countExamProgressResponseQuestions();
        $percentage = ((int) $response_saved / (int)$questions) * 100;

        if (isset($response_saved) && $response_saved <= 0) {
            $class = "bar progress-bar-not-saved";
        } else {
            $class = "bar progress-bar-saved";
        }

        $html = "<div class=\"progress progress-striped progress-success\">";
        $html .= "<div class=\"progress-text\"><strong>" . $response_saved . " / " . $questions . "</strong></div>";
        $html .= "<div style=\"width: " . $percentage . "%\" class=\"" . $class . "\"></div>";
        $html .= "</div>";

        return $html;
    }

    public function renderExamFileSwitcher() {
        $exam_id = $this->progress->getExamID();
        if ($exam_id) {
            $files = Models_Exam_Exam_File::fetchAllByExamId($exam_id);
            if ($files && is_array($files) && !empty($files)) {
                $html = "<div class=\"dropup pull-right\">\n";
                $html .= "<button href=\"#\" title=\"Select Attached Files\" class=\"flat-btn btn dropdown-toggle exam-files\" data-toggle=\"dropdown\">\n";
                $html .= "<i class=\"exam-file-icon fa fa-paperclip\"></i>\n";
                $html .= "</button>\n";

                $html .= "<ul class=\"dropdown-menu dropdown-menu-right exam_file_menu\">\n";
                $html .= "<li class=\"disabled\"><a tabindex=\"-1\" href=\"#\">Attached Files</a></li>\n";

                foreach ($files as $file) {
                    if ($file) {
                        $html .= "<li class=\"exam_file_link\" data-file-id=\"" . $file->getID() . "\">\n";
                        $html .= "    <a tabindex=\"-1\" href=\"#\">" . ($file->getFileTitle() ? $file->getFileTitle() : $file->getFileName()) . "</a>\n";
                        $html .= "</li>\n";
                    }
                }
                $html .= "</div>\n";
            }
        }
        return $html;
    }

    public function get_cookie($label, $defined_name = "") {
        global $translate;
        $MODULE_TEXT    = $translate->_("exams");
        $SECTION_TEXT   = $MODULE_TEXT["attempt"];

        if ($defined_name == "") {
            $text           = $SECTION_TEXT["text"][$label];
            $text           = str_replace(" ", "_", $text);
            $id             = $this->progress->getID();
            $name           = "exam_header_" . $text . "_" . $id;
        } else {
            $name           = $defined_name;
        }
        $cookie         = $_COOKIE[$name];
        if (isset($cookie)) {
            return (int)$cookie;
        } else {
            return 1;
        }
    }

    public function renderClock($seconds = 0, $timer = 0) {
        global $ENTRADA_USER;
        $post           = $this->progress->getExamPost();
        $use_time_limit = $post->getUseTimeLimit();
        $time_limit     = $post->getTimeLimit();
        $start_time     = $this->progress->getStartedDate();
        $use_sub_time   = $post->getUseSubmissionDate();
        $sub_time       = $post->getSubmissionDate();

        if ($timer == 0) {
            $exam_exceptions = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
            if (isset($exam_exceptions) && is_object($exam_exceptions)) {
                $excp_use_time_limit = $exam_exceptions->getUseExceptionTimeFactor();
                $excp_time_limit     = $exam_exceptions->getExceptionTimeFactor();
                if ($excp_use_time_limit) {
                    $time_limit = $time_limit * (($excp_time_limit + 100) / 100);
                }
            }

            $time_limit_unix = $time_limit * 60;
            $time_left       = ($start_time + $time_limit_unix) - time();
            if ($use_sub_time) {
                //check if the time limit will be before the sub time
                if ($start_time + $time_left >= $sub_time) {
                    $time_left = $sub_time - time();
                }
            }
        } else {
            $time_limit = $this->progress->getSelfTimerLength();
            $start_time = $this->progress->getSelfTimerStart();

            $time_limit_unix = $time_limit * 60;
            $time_left       = ($start_time + $time_limit_unix) - time();
        }

        if ($time_left <= 0) {
            $time_left = 0;
        } else {
            $time_left = $time_left / 60;
        }

        $time_limit_hours   = (int)floor($time_left / 60);
        $time_limit_mins    = (int)$time_left % 60;
        $time_limit_sec     = (int)floor(($time_left * 60) % (60));

        if ($time_limit_mins < 10) {
            $time_limit_mins_display = "0" .  $time_limit_mins;
        } else {
            $time_limit_mins_display = $time_limit_mins;
        }

        if ($time_limit_sec < 10) {
            $time_limit_sec_display = "0" . $time_limit_sec;
        } else {
            $time_limit_sec_display = $time_limit_sec;
        }

        if ($time_limit_hours < 1 && $time_limit_mins < 6 && $time_limit_sec_display < 1 || $time_limit_hours < 1 && $time_limit_mins < 5) {
            $display_clock = $time_limit_hours . " : " . $time_limit_mins_display . " : " . $time_limit_sec_display;
        } else {
            if ($seconds === 1) {
                $display_clock = $time_limit_hours . " : " . $time_limit_mins_display . " : " . $time_limit_sec_display;
            } else {
                $display_clock = $time_limit_hours . " : " . $time_limit_mins_display;
            }
        }

        if ($use_time_limit || $timer == 1) {
            $html = $display_clock;
        }

        return $html;
    }

    public function renderSideBarHeader() {
        global $translate;
        $MODULE_TEXT    = $translate->_("exams");
        $SECTION_TEXT   = $MODULE_TEXT["attempt"];
        $visibility     = $this->get_cookie("label_navigation");
        $class          = ($visibility == 0 ? "dim" : "");
        $icon           = ($visibility == 0 ? "<i class=\"fa fa-plus side-bar-icon\"></i>" : "<i class=\"fa fa-minus side-bar-icon\"></i>");;
        $html           = "<span class=\"nav-list-header\"><h4 class=\"" . $class . "\">" . $icon .  " " . $SECTION_TEXT["text"]["label_navigation"] . "</h4></span>";
        return $html;
    }

    public function renderSideBar($current_page = 1) {
        $total_pages    = $this->total_pages;
        $post           = $this->progress->getExamPost();
        $backtrack      = $post->getBacktrack();
        $display_questions = $this->progress->getExamPost()->getExam()->getDisplayQuestions();
        $visibility     = $this->get_cookie("label_navigation");
        $class          = ($visibility == 0 ? "nav-list-item hide" : "nav-list-item");
        $html = "<ul class=\"" . $class . "\">";
        switch ($display_questions) {
            case "one":
                $this->current_page = isset($current_page) ? (int) $current_page : 1;
                $last_page = $total_pages;
                $last_page_saved = $this->checkPreviousPageResponses($last_page);

                for ($i = 1; $i <= $this->total_pages; $i++) {
                    if ($backtrack != 1) {
                        $page_saved = $this->checkPreviousPageResponses($i);
                        if ($page_saved != $i || $last_page_saved > $i) {
                            $allow_access = false;
                        } else {
                            $allow_access = true;
                        }
                    } else {
                        $allow_access = true;
                    }

                    $responses = $this->getPage($i);
                    if (isset($responses) && is_array($responses)) {
                        foreach ($responses as $response) {
                            if (isset($response) && is_object($response)) {
                                $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
                                $response_view = new Views_Exam_Progress_Response($response, $exam_element);
                                $html .= $response_view->renderProgressMenuItem(true, $allow_access, $i, $current_page);
                            }
                        }
                    }
                }

                break;
            case "page_breaks":
                $this->current_page = isset($current_page) ? (int) $current_page : 1;
                $last_page = $total_pages;
                $last_page_saved = $this->checkPreviousPageResponses($last_page);

                $array_responses = array();
                for ($page = 1; $page <= $this->total_pages; $page++) {
                    if ($backtrack != 1) {
                        $page_saved = $this->checkPreviousPageResponses($page);
                        if ($page_saved != $page || $last_page_saved > $page) {
                            $allow_access = false;
                        } else {
                            $allow_access = true;
                        }
                    } else {
                        $allow_access = true;
                    }

                    $responses = $this->getPage($page, NULL);
                    $array_responses[$page] = $responses;
                    $html .= "<li class=\"page_break\">Page " . $page . "</li>";

                    if (isset($responses) && is_array($responses)) {
                        foreach ($responses as $response) {
                            if (isset($response) && is_object($response)) {
                                $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
                                $response_view = new Views_Exam_Progress_Response($response, $exam_element);
                                $html .= $response_view->renderProgressMenuItem(true, $allow_access, $page);
                            }
                        }
                    }
                }

                break;
            case "all":
            default:
                //all on one page
                $responses = Models_Exam_Progress_Responses::fetchAllByProgressID($this->progress->getID());
                if (isset($responses) && is_array($responses)) {
                    foreach ($responses as $response) {
                        if (isset($response) && is_object($response)) {
                            $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
                            $response_view = new Views_Exam_Progress_Response($response, $exam_element);
                            $html .= $response_view->renderProgressMenuItem(true, true);
                        }
                    }
                }
                break;
        }

        $html .= "</ul>";

        return $html;
    }

    public function renderSelfTimerHeader() {
        global $translate;
        $MODULE_TEXT    = $translate->_("exams");
        $SECTION_TEXT   = $MODULE_TEXT["attempt"];
        $visibility     = $this->get_cookie("label_self-timer");
        $class          = ($visibility == 0 ? "dim" : "");
        $icon           = ($visibility == 0 ? "<i class=\"fa fa-plus side-bar-icon\"></i>" : "<i class=\"fa fa-minus side-bar-icon\"></i>");;
        $html           = "<span class=\"nav-list-header\" data-type=\"self-timer\">\n";
        $html           .= "<h4 class=\"" . $class . "\">\n";
        $html           .= $icon .  " " . $SECTION_TEXT["text"]["label_self-timer"] . "\n";
        $html           .= "</h4>\n";
        $html           .= "</span>\n";
        return $html;
    }

    public function renderSelfTimer() {
        $html = "";
        $visibility     = $this->get_cookie("label_self-timer");
        $class          = ($visibility == 0 ? "nav-list-item hide" : "nav-list-item");

        $visibility_timer  = $_COOKIE["exam_timer_" . $this->progress->getID()];

        if ($visibility_timer == 1) {
            $count_down_class = "hide";
            $count_down_sec_class = "show";
        } else if ($visibility_timer == 0) {
            $count_down_class = "show";
            $count_down_sec_class = "hide";
        }

        $html .= "<div id=\"label_self-timer\" class=\"" . $class . "\">\n";
        $html .= "<div id=\"timer-controls\">\n";
        $html .= "<span id=\"count-down-sec-timer\" class=\"" . $count_down_sec_class . "\">" . $this->renderClock(1, 1) . "</span>\n";
        $html .= "<span id=\"count-down-timer\" class=\"" . $count_down_class . "\">" . $this->renderClock(0, 1) . "</span>\n";
        $html .= "<i id=\"toggle-timer-controls\" class=\"fa fa-2x fa-gear closed\"></i>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
        return $html;
    }

    public function renderSelfTimerMenu() {
        $html = "<div id=\"self-timer-container1\">\n";
        $html .= "    <div id=\"self-timer-container2\">\n";
        $html .= "        <div class=\"cornerarrow\"></div>\n";
        $html .= "        <div class=\"panel-head self-timer-title\">\n";
        $html .= "            <h3>Add Timer</h3>\n";
        $html .= "        </div>\n";
        $html .= "        <input id=\"self_timer_input\" />\n";
        $html .= "    </div>\n";
        $html .= "</div>\n";
        return $html;
    }

    public function renderCalculatorHeader() {
        global $translate;
        $MODULE_TEXT    = $translate->_("exams");
        $SECTION_TEXT   = $MODULE_TEXT["attempt"];
        $visibility     = $this->get_cookie("label_calculator");
        $class          = ($visibility == 0 ? "dim" : "");
        $icon           = ($visibility == 0 ? "<i class=\"fa fa-plus side-bar-icon\"></i>" : "<i class=\"fa fa-minus side-bar-icon\"></i>");;
        $html           = "<span class=\"nav-list-header\" data-type=\"calculator\">\n";
        $html           .= "<h4 class=\"" . $class . "\">\n";
        $html           .= $icon .  " " . $SECTION_TEXT["text"]["label_calculator"] . "\n";
        $html           .= "</h4>\n";
        $html           .= "</span>\n";
        return $html;
    }

    public function renderCalculator() {
        $html = "";
        $visibility     = $this->get_cookie("label_calculator");
        $class          = ($visibility == 0 ? "nav-list-item hide" : "nav-list-item");

        $html .= "<div id=\"calculator\" class=\"" . $class . "\">\n";
        $html .= "<div class=\"calc-main calc-small\">
                        <div class=\"calc-display\">
                            <span>0</span>
                            <div class=\"calc-rad\">Rad</div>
                            <div class=\"calc-hold\"></div>
                            <div class=\"calc-buttons\">
                                <div class=\"calc-info\">?</div>
                                <div class=\"calc-smaller\">&gt;</div>
                                <div class=\"calc-ln\">.</div>
                            </div>
                        </div>\n";

        $html .= "      <div class=\"calc-left\">\n";
        /*
                            <div><div>2nd</div></div>
                            <div><div>(</div></div>
                            <div><div>)</div></div>
                            <div><div>%</div></div>
                            <div><div>1/x</div></div>
                            <div><div>x<sup>2</sup></div></div>
                            <div><div>x<sup>3</sup></div></div>
                            <div><div>y<sup>x</sup></div></div>
                            <div><div>x!</div></div>
                            <div><div>&radic;</div></div>
                            <div><div class=\"calc-radxy\">
                            <sup>x</sup><em>&radic;</em><span>y</span>
                            </div></div>
                            <div><div>log</div></div>
                            <div><div>sin</div></div>
                            <div><div>cos</div></div>
                            <div><div>tan</div></div>
                            <div><div>ln</div></div>
                            <div><div>sinh</div></div>
                            <div><div>cosh</div></div>
                            <div><div>tanh</div></div>
                            <div><div>e<sup>x</sup></div></div>
                            <div><div>Deg</div></div>
                            <div><div>&pi;</div></div>
                            <div><div>EE</div></div>
                            <div><div>Rand</div></div>\n";
        */
        $html .= "      </div>\n";

        $html .= "      <div class=\"calc-right\">
                            <div><div>mc</div></div>
                            <div><div>m+</div></div>
                            <div><div>m-</div></div>
                            <div><div>mr</div></div>
                            <div class=\"calc-brown\"><div >AC</div></div>
                            <div class=\"calc-brown\"><div>+/&#8211;</div></div>
                            <div class=\"calc-brown calc-f19\"><div>&divide;</div></div>
                            <div class=\"calc-brown calc-f21\"><div>&times;</div></div>
                            <div class=\"calc-black\"><div>7</div></div>
                            <div class=\"calc-black\"><div>8</div></div>
                            <div class=\"calc-black\"><div>9</div></div>
                            <div class=\"calc-brown calc-f18\"><div>&#8211;</div></div>
                            <div class=\"calc-black\"><div>4</div></div>
                            <div class=\"calc-black\"><div >5</div></div>
                            <div class=\"calc-black\"><div>6</div></div>
                            <div class=\"calc-brown calc-f18\"><div>+</div></div>
                            <div class=\"calc-black\"><div>1</div></div>
                            <div class=\"calc-black\"><div>2</div></div>
                            <div class=\"calc-black\"><div>3</div></div>
                            <div id=\"calc_spacer\" class=\"calc-blank\"><textarea></textarea></div>
                            <div class=\"calc-orange calc-eq calc-f17\"><div>
                            <div class=\"calc-down\">=</div>
                            </div></div>
                            <div class=\"calc-black calc-zero\"><div>
                            <span>0</span>
                            </div></div>
                            <div class=\"calc-black calc-f21\"><div>.</div></div>
                        </div>
                    </div>\n";
        $html .= "</div>";
        return $html;
    }

    public function gradeExam($regrade = false) {
        global $ENTRADA_USER, $db;
        $progress       = $this->progress;
        $post           = $this->post;
        $submitted      = false;
        $exam_points    = 0;
        $user_points    = 0;
        $late           = 0;
        $use_end_date   = (int)$post->getUseExamEndDate();
        $end_date       = $post->getEndDate();
        $use_time_limit = (int)$post->getUseTimeLimit();
        $time_limit     = $post->getTimeLimit();
        $threshold      = (int)$post->getRAThreshold();
        $threshold_att  = (int)$post->getRAThresholdAttempts();
        $grade_book_id  = (int)$post->getGradeBook();
        $exception      = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
        $responses      = Models_Exam_Progress_Responses::fetchAllByProgressIDNoText($progress->getID());
        
        if (isset($responses) && is_array($responses) && !empty($responses)) {
            foreach ($responses as $response) {
                if (isset($response) && is_object($response)) {
                    $response_view = new Views_Exam_Progress_Response($response);
                    $exam_element = $response_view->getExamElement();
                    //grade response
                    if ($exam_element && $exam_element->isScored()) {
                        $points = $response_view->gradeResponse($exam_points, $user_points);
                        if (isset($points) && is_array($points)) {
                            $exam_points = $points["exam_points"];
                            $user_points = $points["user_points"];
                        }
                    }
                }
            }
        }

        if (0 === $exam_points) {
            $exam_score = 0;
        } else {
            $exam_score = round($user_points / $exam_points, 2) * 100;
        }

        if (isset($exception) && is_object($exception)) {
            $use_exception_end_date     = (int)$exception->getUseEndDate();
            $use_exception_time_factor  = (int)$exception->getUseExceptionTimeFactor();

            if (isset($use_exception_end_date) && $use_exception_end_date === 1) {
                $exception_end_date = $exception->getEndDate();
            }

            if (isset($use_exception_time_factor) && $use_exception_time_factor === 1) {
                $exception_time_factor = $exception->getExceptionTimeFactor();
                if ($exception_time_factor) {
                    $exception_time_limit = $time_limit * (($exception_time_factor + 100) / 100);
                }
            }
        }

        if (isset($use_end_date) && $use_end_date === 1) {
            if (time() > $end_date) {
                $late = (time() - $end_date) / 60;
            }
        }

        if (isset($use_exception_end_date) && $use_exception_end_date === 1) {
            $late = 0;
            if (time() > $exception_end_date) {
                $late = (time() - $exception_end_date) / 60;
            }
        }

        if (isset($use_time_limit) && $use_time_limit === 1) {
            $time_elapsed       = time() - $progress->getStartedDate();
            $time_limit_unix    = $time_limit * 60;

            if (isset($use_exception_time_factor) && $use_exception_time_factor > 0) {
                $time_limit_unix    = $exception_time_limit * 60;
            }

            if ($time_elapsed > $time_limit_unix) {
                $late = ($time_elapsed - $time_limit_unix) / 60;
            }
        }

        if ($late < 0) {
            $late = 0;
        }

        $progress->setExamPoints($user_points);
        $progress->setExamValue($exam_points);
        $progress->setUpdatedDate(time());
        $progress->setUpdateBy($ENTRADA_USER->getID());

        if ($regrade === false) {
            $progress->setLate($late);
            $progress->setProgressValue("submitted");
            $progress->setSubmissionDate(time());
        }

        if ($progress->update()) {
            $submitted = true;
            if (isset($grade_book_id) && $grade_book_id > 0) {
                //attach scores to gradebook assessment.
                $assessment = $post->getGradeBookAssessment();
                if (isset($assessment) && is_object($assessment)) {
                    $this->updateGradeBook();
                }
            }

            if (isset($threshold) && $threshold > 0 && $exam_score < $threshold) {
                // Exam score is under the threshold so allow another attempt if the user hasn't exceed the allowable amount
                // Count current attempts
                $attempts = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                if (isset($attempts) && is_array($attempts)) {
                    $attempt_count = count($attempts);
                } else {
                    $attempt_count = 0;
                }

                if ($attempt_count < $threshold_att) {
                    $attempt_count++;
                    // Allow another attempt if they haven't met the limit of attempts
                    if (isset($exception) && is_object($exception)) {
                        $exception->setUseExceptionMaxAttempts(1);
                        $exception->setAttempts($attempt_count);
                        if (!$exception->update()) {
                            application_log("error", "Error updating Exam Post Exception DB said: " . $db->ErrorMsg());
                        }
                    } else {
                        // No Exception created yet so create it.
                        $exception = new Models_Exam_Post_Exception(array(
                            "post_id"       => $post->getID(),
                            "proxy_id"      => $ENTRADA_USER->getID(),
                            "use_exception_max_attempts" => 1,
                            "max_attempts"  => $attempt_count,
                            "excluded"      => 0,
                            "use_exception_start_date"      => 0,
                            "use_exception_end_date"        => 0,
                            "use_exception_submission_date" => 0,
                            "use_exception_time_factor"     => 0,
                            "created_date"  => time(),
                            "created_by"    => $ENTRADA_USER->getID(),
                            "updated_date"  => time(),
                            "updated_by"    => $ENTRADA_USER->getID(),
                        ));
                        if (!$exception->insert()) {
                            application_log("error", "Error inserting Exam Post Exception DB said: " . $db->ErrorMsg());
                        }
                    }
                }
            }
        }

        return $submitted;
    }

    public function updateGradeBook() {
        global $db;
        $progress       = $this->progress;
        $post           = $this->post;
        $assessment     = $post->getGradeBookAssessment();
        $user_id        = $progress->getCreatedBy();
        $score          = 0;

        if (isset($assessment) && is_object($assessment)) {
            // Get the grading method for the assessment
            $scoring_method     = Models_Gradebook_Assessment_LuMeta_Scoring::fetchRowByID($assessment->getScoringMethod());

            // This gets the proxy ids as an array using Models_Gradebook_Assessment
            $proxy_ids = Models_Gradebook_Assessment::fetchProxyIDsByAssessmentID($assessment);

            if ($proxy_ids && is_array($proxy_ids)) {
                if (in_array($user_id, $proxy_ids)) {
                    // The user is a valid member of the grade book assessment and we can update it.

                    /*
                     * Get the scoring method
                     */
                    if (isset($scoring_method) && is_object($scoring_method)) {
                        $submitted_attempts = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post->getID(), $user_id, "submitted");
                        $submission_count   = count($submitted_attempts);
                        $scores             = array();
                        $update_score       = 0;

                        $marking_scheme = Models_Gradebook_Assessment_Marking_Scheme::fetchRowByID($assessment->getAssessmentMarkingSchemesID());
                        $marking_scheme_handler = $marking_scheme->getHandler();

                        switch ($scoring_method->getShortName()) {
                            case "highest":
                                // Get all the progress scores for this user that are completed
                                if (isset($submitted_attempts) && is_array($submitted_attempts)) {
                                    foreach ($submitted_attempts as $key => $attempt) {
                                        if (isset($attempt) && is_object($attempt)) {
                                            $scores[$key] = $attempt->getExamScore();
                                        }
                                    }
                                    arsort($scores);
                                    $current_key = key($scores);
                                    $current_progress = $submitted_attempts[$current_key];


                                    $score = $current_progress->getExamScore();
                                    if ($score >= $progress->getExamScore()) {
                                        $update_score = 1;
                                    }
                                }
                                break;
                            case "first":
                                // if the count of the progress attempts is 1 than use the current one to update grade book
                                if ($submission_count && $submission_count === 1) {
                                    $current_progress   = $progress;
                                } else {
                                    if (isset($submitted_attempts) && is_array($submitted_attempts)) {
                                        $current_progress =  $submitted_attempts[0];
                                    }
                                }

                                $update_score       = 1;
                                $score              = $current_progress->getExamScore();

                                break;
                            case "average":
                                // Get all the progress scores and average the score
                                if ($submission_count && $submission_count >= 1) {
                                    if (isset($submitted_attempts) && is_array($submitted_attempts)) {
                                        foreach ($submitted_attempts as $attempt) {
                                            if (isset($attempt) && is_object($attempt)) {
                                                $scores[] = $attempt->getExamScore();
                                            }
                                        }

                                        $score = array_sum($scores) / count($scores);
                                        $update_score = 1;
                                    }
                                }

                                break;
                            case "latest":
                                // Get all the progress score and choose the one with the highest progress ID
                                if ($submission_count && $submission_count >= 1) {
                                    $current_key        = $submission_count - 1;
                                    $current_progress   = $submitted_attempts[$current_key];
                                    $update_score       = 1;

                                    $score              = $current_progress->getExamScore();
                                }

                                break;
                        }

                        if ($update_score === 1) {
                            $grade_obj = new Models_Assessment_Grade(array(
                                "assessment_id" => $assessment->getID(),
                                "proxy_id" => $user_id
                            ));

                            $grade = $grade_obj->fetchRowByAssessmentIDProxyID();
                            if ($grade) {
                                $grade->setValue($score);
                                if (!$grade->update()) {
                                    // Error updating
                                    application_log("error", "Error updating Grade Book DB said: " . $db->ErrorMsg());
                                }
                            } else {
                                $grade = new Models_Assessment_Grade(array(
                                    "assessment_id"         => $assessment->getID(),
                                    "proxy_id"              => $user_id,
                                    "value"                 => $score,
                                    "threshold_notified"    => 0
                                ));
                                if (!$grade->insert()) {
                                    // Error updating
                                    application_log("error", "Error inserting Grade Book DB said: " . $db->ErrorMsg());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function getScoreDisplay() {
        global $translate;
        $progress = $this->progress;
        $text = $translate->_("exams");
        $sub_module = $text["exams"]["feedback"]["text"];

        $points = $progress->getExamPoints();
        $value  = $progress->getExamValue();
        $score  = $progress->getExamScore();
        $html = "<p><strong>" . $sub_module["score"] . ":</strong> $points/$value " . $sub_module["points"] . " ($score%)\n";

        $gradable_questions = Models_Exam_Grader::fetchGradableQuestionCount($progress->getPostID());
        $graded_questions   = Models_Exam_Grader::fetchGradedQuestionCount($progress->getID());
        if ($gradable_questions !== $graded_questions) {
            $html .= "<br /><em>" . $sub_module["not_graded"] . "</em></p>\n";
        }

        $html .= "</p>\n";
        return $html;
    }

    public static function getAllowedAttemptsCount(Models_Exam_Post_Exception $exam_exceptions, Models_Exam_Post $post) {
        if (isset($exam_exceptions) && is_object($exam_exceptions)) {
            $use_exception_max = $exam_exceptions->getUseExceptionMaxAttempts();
            if ($use_exception_max && $use_exception_max == 1) {
                $exception_attempts = $exam_exceptions->getAttempts();
            }
        }

        if ($exception_attempts) {
            $allowed_count = $exception_attempts;
        } else {
            $allowed_count = $post->getMaxAttempts();
        }
        return $allowed_count;
    }

    public function render($show_edit = 1) {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        if ($this->progress !== null) {
            return $this->renderAdminRow("html", 1, $show_edit);
        } else {
            echo display_notice($translate->_("No Exams to Display"));
        }
    }
}
