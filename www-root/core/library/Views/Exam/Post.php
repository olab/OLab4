<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Post extends Views_Deprecated_Base {
    protected $post;
    protected $event;
    protected $community;

    public function __construct(Models_Exam_Post $post, Models_Event $event = NULL, Models_Community $community = NULL) {
        $this->post = $post;
        $this->event = $event;
        $this->community = $community;
    }

    /**
     *
     * @param bool $include_description Call Models_Exam_Post->getExamPostDescription() to include a description of the exam post
     * @param bool $include_progress Includes a description of the the user's current exam progress
     * @return string
     */
    public function renderEventPost ($include_description = false, $include_progress = false, $included_course_code = false) {
        global $ENTRADA_USER, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $post = $this->post;
        $show_row = false;

        if ($post->getSecure() === "1") {
            if ($post->isResumeAttemptAllowedByUser($ENTRADA_USER)) {
                $access_time = true;
                $progress_results = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                if (!empty($progress_results)) {
                    //use current progress and orders
                    $progress = false;
                    foreach ($progress_results as $progress_result){
                        if ($progress_result->getProgressValue() === "inprogress") {
                            $progress = $progress_result;
                            $show_row = true;
                            break;
                        }
                    }
                    if (!$progress) {
                        if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                            $access_time = true;
                            $new_start = true;
                            $show_row = true;
                        } else {
                            $access_time = false;
                            $new_start = false;
                        }
                    }
                } else {
                    if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                        $access_time = true;
                        $new_start = true;
                        $show_row = true;
                    } else {
                        $access_time = false;
                        $new_start = false;
                    }
                }
            } elseif ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                $access_time = true;
                $new_start = true;
                $show_row = true;
            }
        } else {
            $show_row = true;
        }


        if ($show_row === true) {
            $course = $post->getCourse();
            $post_progress = $post->fetchExamProgress($ENTRADA_USER->getID());

            $html = "<tr id=\"exam-post-id-" . $post->getPostID() . "\">\n";
            /*
             * Course code
             */
            $html .= "<td class=\"modified\" style=\"vertical-align: top\">";
            if ($included_course_code === true) {
                $course = $post->getCourse();
                if ($course && is_object($course)) {
                    $course_code = $course->getCourseCode();
                    $html .= $course_code;
                }
            }

            $html .= "</td>\n";
            $html .= "<td class=\"title\" style=\"vertical-align: top; white-space: normal; overflow: visible\">\n";

            if ($ENTRADA_USER->getActiveGroup() != "student" && $post->getHideExam() == 1) {
                $anchor_class = "start-exam hidden_shares";
            } else {
                $anchor_class = "start-exam";
            }

            if ($post->isAfterUserStartTime($ENTRADA_USER) || $ENTRADA_USER->getActiveGroup() != "student") {
                $secure_exam_url = ENTRADA_URL."/file-secure-access.php?id=" . $post->getID();

                if ($post->getSecure() !== "1") {
                    $html .= "<a class=\"" . $anchor_class . "\" href=\"" . ENTRADA_URL . "/exams?section=post&id=" . $post->getID() . "\">";
                } else {
                    if (!$post_progress) {
                        $html .= "<a class=\"" . $anchor_class . "\" data-href=\"" . $secure_exam_url . "\" data-resource-id=\"" . $post->getID() . "\">";
                    }
                }
                $html .= "<strong>" . html_encode($post->getTitle()) . ($post->getSecure() == 1 ? " (<i class=\"icon-lock\"></i> Secure mode required)" : "") . "</strong>";
                if (!$post_progress) {
                    $html .= "</a>";
                }
            } else {
                $html .= "<strong class=\"muted small\">";
                $html .= html_encode($post->getTitle());
                $html .= ($post->getSecure() == 1) ? " (<i class=\"icon-lock\"></i> Secure mode required)" : '';
                $html .= "</strong>";
            }

            if ($include_description) {
                $html .= "<br />";
                $html .= $post->getExamPostDescription(1);
            }

            if ($include_progress) {
                if ($post_progress) {
                    $html .= "<ul class=\"menu\">";
                    foreach ($post_progress as $entry) {
                        switch ($entry->getProgressValue()) {
                            case "complete" :
                                if ($post->isScoreStartValid()) {
                                    $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                                } else {
                                    if ($post->getReleaseScore() === 1) {
                                        if ($post->getUseReleaseStartDate()) {
                                            if (NULL !== $post->getReleaseStartDate() && $post->getReleaseEndDate() <= time()) {
                                                $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " <strong>Score:</strong> To Be Released " . date(DEFAULT_DATETIME_FORMAT, $post->getReleaseStartDate()) . "</li>";
                                            } else {
                                                $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                                            }
                                        } else {
                                            $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                                        }
                                    }
                                }
                                break;
                            case "expired" :
                                $html .= "<li class=\"incorrect\">" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " <strong>Expired Attempt</strong>: not completed.</li>";
                                break;
                            case "inprogress" :
                                $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " <strong>Attempt In Progress</strong> ( <a class=\"start-exam\" data-href=\"" . $secure_exam_url . "\" data-resource-id=\"".$post->getID()."\">continue exam</a> )</li>";

                                break;
                            default :
                                break;
                        }
                    }
                }
                $html .= "</ul>";
            }
            $html .= "</td>\n";
            $html .= "<td class=\"date\" style=\"vertical-align: top\">";
            $html .= (((int) $post->getEndDate()) ? date(DEFAULT_DATETIME_FORMAT, $post->getEndDate()) : $MODULE_TEXT["posts"]["text_no_expiration"]);
            $html .= "</td>\n";
            $html .= "</tr>\n";

            return $html;
        }
    }

    public function renderEventResource($edit = false) {
        global $ENTRADA_USER, $ENTRADA_ACL, $translate;
        $MODULE_TEXT        = $translate->_("exams");
        $SECTION_TEXT       = $MODULE_TEXT["posts"];
        $post               = $this->post;
        $title              = "";
        $description        = "";

        $messages           = $this->generateTimeMessages();
        $available          = $messages["available"];
        $attempts_allowed   = $messages["attempts_allowed"];
        $time_limit         = $messages["time_limit_display"];

        if ($post->getDescription() && $post->getDescription() != "") {
            $description = "<p class=\"muted resource-description\">" . html_encode($post->getDescription()) . "</p>";
        }

        $title .= "<p class=\"resource-title\">";
        if ($edit === true && $ENTRADA_ACL->amIAllowed("exam", "create", false)) {
            if ($post && is_object($post)) {
                $exam = $post->getExam();
                if ($exam && is_object($exam)) {
                    $url =  ENTRADA_URL . "/admin/exams/exams?section=form-post&id=" . $exam->getID() . "&post_id=" . $post->getID() . "&target_type=" . $post->getTargetType();

                    $title .= "<strong>";
                    $title .= "<a class=\"resource-link edit-post\" href=\"" . $url . "\">";
                    $title .= html_encode($post->getTitle());
                    $title .= "</a>";
                    $title .= "</strong>";
                }
            }
        } else {
            if ($post->isAfterUserStartTime($ENTRADA_USER) || $ENTRADA_USER->getActiveGroup() != "student") {
                $secure_exam_url = ENTRADA_URL."/file-secure-access.php?id=" . $post->getID();

                if ($post->getSecure() !== "1") {
                    $title .= "<a class=\"resource-link start-exam\" href=\"" . ENTRADA_URL . "/exams?section=post&id=" . $post->getID() . "\">";
                } else {
                    $title .= "<a class=\"resource-link start-exam\" data-href=\"" . $secure_exam_url . "\" data-resource-id=\"" . $post->getID() . "\">";
                }
                $title .= "<strong>" . html_encode($post->getTitle()) . ($post->getSecure() == 1 ? " (<i class=\"icon-lock\"></i> Secure mode required)" : "") . "</strong>";
                $title .= "</a>";
            } else {
                $title .= "<strong class=\"muted small\">";
                $title .= html_encode($post->getTitle());
                $title .= ($post->getSecure() == 1) ? " (<i class=\"icon-lock\"></i> Secure mode required)" : '';
                $title .= "</strong>";
            }
        }

        $title .= "</p>";

        return array("title" => $title, "description" => $description, "available" => $available, "attempts_allowed" => $attempts_allowed, "time_limit" => $time_limit);
    }

    private function generateTimeMessages() {
        global $ENTRADA_USER, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $SECTION_TEXT = $MODULE_TEXT["posts"];
        $post = $this->post;

        $start_date     = $this->post->getStartDate();
        $end_date       = $this->post->getEndDate();
        $sub_date       = $this->post->getSubmissionDate();
        $attempts_allowed = $this->post->getMaxAttempts();
        $time_limit     = $this->post->getTimeLimit();
        $use_time_limit = $this->post->getUseTimeLimit();
        $use_start_date = $this->post->getUseExamStartDate();
        $use_end_date   = $this->post->getUseExamEndDate();
        $use_sub_date   = $this->post->getUseSubmissionDate();
        $post_date      = $this->post->getCreatedDate();

        $exam_exceptions = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($this->post->getID(), $ENTRADA_USER->getID());

        if (isset($exam_exceptions) && is_object($exam_exceptions)) {
            $excp_start_date     = $exam_exceptions->getStartDate();
            $excp_end_date       = $exam_exceptions->getEndDate();
            $excp_sub_date       = $exam_exceptions->getSubmissionDate();
            $excp_use_start_date = $exam_exceptions->getUseStartDate();
            $excp_use_end_date   = $exam_exceptions->getUseEndDate();
            $excp_use_sub_date   = $exam_exceptions->getUseSubmissionDate();
            $excp_attempts_allowed = $exam_exceptions->getAttempts();
            $excp_use_time_limit = $exam_exceptions->getUseExceptionTimeFactor();
            $excp_time_limit     = $exam_exceptions->getExceptionTimeFactor();

            if ($excp_use_start_date) {
                $start_date = $excp_start_date;
            }

            if ($excp_use_end_date) {
                $end_date   = $excp_end_date;
            }

            if ($excp_use_sub_date) {
                $sub_date   = $excp_sub_date;
            }

            if ($excp_attempts_allowed) {
                $attempts_allowed = $excp_attempts_allowed;
            }

            if ($excp_use_time_limit) {
                $time_limit = $time_limit * (($excp_time_limit + 100) / 100);
            }
        }

        if ($use_start_date == 1) {
            if ($start_date && $start_date != 0) {
                $available = date("M j Y g:i a", $start_date);
            } else {
                $available = date("M j Y g:i a", $post_date);
            }
        } else {
            $available = date("M j Y g:i a", $post_date);
        }

        if ($start_date && $end_date) {
            $available .= " - ";
        }

        if ($use_end_date == 1) {
            if ($end_date && $end_date != 0) {
                $available .= date("M j Y g:i a", $end_date);
            } else if ($end_date == 0) {
                $available .= $SECTION_TEXT["text_no_end_date"];
            }
        } else {
            $available .= $SECTION_TEXT["text_no_end_date"];
        }

        if ($use_sub_date == 1) {
            if ($sub_date && $sub_date != 0) {
                $sub_date = date("M j Y g:i a", $sub_date);
            } else if ($sub_date == 0) {
                $sub_date =  $SECTION_TEXT["text_no_sub_date"];
            }
        } else {
            $sub_date =  $SECTION_TEXT["text_no_sub_date"];
        }

        if ($use_time_limit) {
            //convert TL to hours/ minuetss
            $time_limit_hours   = (int)floor($time_limit / 60);
            $time_limit_mins    = (int)$time_limit % 60;
            $time_limit_display = $time_limit_hours . " Hours" . ($time_limit_mins ? " & " . $time_limit_mins . " Minutes" : "");

            $auto_submit = $this->post->getAutoSubmit();
        } else {
            $time_limit_display = NULL;
            $auto_submit = NULL;
        }

        return array("available" => $available, "attempts_allowed" => $attempts_allowed, "time_limit" => $time_limit_display, "sub_date" => $sub_date, "auto_submit" => $auto_submit);
    }

    /**
     * @param bool $include_description Call Models_Exam_Post->getExamPostDescription() to include a description of the exam post
     * @param bool $include_progress Includes a description of the the user's current exam progress
     * @return string
     */
    public function renderCommunityPost ($include_description = false, $include_progress = false) {
        global $ENTRADA_USER, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $post = $this->post;

        if (!(int) $post->getStartDate() || ($post->getStartDate() <= time() && (time() <= $post->getEndDate() || !(int) $post->getEndDate()))) {
            $allow_attempt = true;
        } else {
            $allow_attempt = false;
        }

        $html = "<tr id=\"exam-post-id-" . $post->getPostID() . "\">\n";
        $html .= "<td class=\"modified\" style=\"vertical-align: top\">";
        $html .= "</td>\n";
        $html .= "<td class=\"title\" style=\"vertical-align: top; white-space: normal; overflow: visible\">\n";

        if ($allow_attempt) {
            $secure_exam_url = ENTRADA_URL."/file-secure-access.php?id=" . $post->getID();
            if($post->getSecure() !== "1") {
                $html .= "<a href=\"" . ENTRADA_URL . "/exams?section=post&id=" . $post->getID() . "\">";
            } else {
                $html .= "<a class=\"start-exam\" data-href=\"" . $secure_exam_url . "\" data-resource-id=\"".$post->getID()."\">";
            }
            $html .= "<strong>" . html_encode($post->getTitle()) . ($post->getSecure() == 1 ? " (<i class=\"icon-lock\"></i> Secure mode required)" : "") . "</strong>";
            $html .= "</a>";
        } else {
            $html .= "<strong class=\"muted small\">";
            $html .= html_encode($post->getTitle());
            $html .= ($post->getSecure() == 1) ? " (<i class=\"icon-lock\"></i> Secure mode required)" : '';
            $html .= "</strong>";
        }
        if ($include_description){
            $html .= "<br />";
            $html .= $post->getExamPostDescription(1);
        }
        if ($include_progress){
            $post_progress = $post->fetchExamProgress($ENTRADA_USER->getID());
            if ($post_progress) {
                $html .= "<strong>Your Attempts</strong>";
                $html .= "<ul class=\"menu\">";
                foreach ($post_progress as $entry) {
                    switch ($entry->getProgressValue()) {
                        case "complete" :
                            if ($post->isScoreStartValid()) {
                                $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                            } else {
                                if ($post->getReleaseScore() === 1) {
                                    if ($post->getUseReleaseStartDate()) {
                                        if (NULL !== $post->getReleaseStartDate() && $post->getReleaseEndDate() <= time()) {
                                            $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " <strong>Score:</strong> To Be Released " . date(DEFAULT_DATETIME_FORMAT, $post->getReleaseStartDate()) . "</li>";
                                        } else {
                                            $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                                        }
                                    } else {
                                        $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " - <strong>Completed</strong></li>";
                                    }
                                }
                            }
                            break;
                        case "inprogress" :
                            $html .= "<li>" . date(DEFAULT_DATETIME_FORMAT, $entry->getUpdatedDate()) . " <strong>Attempt In Progress</strong> ( <a class=\"start-exam\" data-href=\"" . $secure_exam_url . "\" data-resource-id=\"".$post->getID()."\">continue quiz</a> )</li>";
                            break;
                        default :
                            break;
                    }
                }
            }
            $html .= "</ul>";
        }
        $html .= "</td>\n";
        $html .= "<td class=\"date\" style=\"vertical-align: top\">";
        $html .= (((int) $post->getEndDate()) ? date(DEFAULT_DATETIME_FORMAT, $post->getEndDate()) : $MODULE_TEXT["posts"]["text_no_expiration"]);
        $html .= "</td>\n";
        $html .= "</tr>\n";

        return $html;
    }



    /**
     * @param Models_Exam_Post $post
     * @return string
     */
    public function renderPreviewPost () {
        global $ENTRADA_USER, $translate, $db;
        $MODULE_TEXT = $translate->_("exams");
        $post = $this->post;
        $exam = $post->fetchExam();
        $html = "";
        $allow_attempt = true;

        $secure_exam_url = ENTRADA_URL."/file-secure-access.php?id=" . $post->getID();
        if ($post->getSecure() !== "1") {
            $html .= "<button class=\"btn btn-success start-exam\" data-href=\"" . ENTRADA_URL . "/exams?section=post&id=" . $post->getID() . "\" data-resource-id=\"" . $post->getID() . "\">";
        } else {
            $html .= "<button class=\"btn btn-success start-exam\" data-href=\"" .$secure_exam_url . "\" data-resource-id=\"" . $post->getID() . "\">";
        }
        $html .= "<strong>Exam Preview - " . $exam->getTitle() . ($post->getSecure() == 1 ? " (<i class=\"icon-lock\"></i> Secure mode required)" : "") . "</strong>";
        $html .= "</button>";

        return $html;
    }


    /**
     * @return string
     */
    public function renderEventPostAdminRow() {
        $exam_post = $this->post;

        $progress_attempts = Models_Exam_Progress::fetchAllByPostID($exam_post->getID());
        $finish_count = 0;

        if (isset($progress_attempts) && is_array($progress_attempts)) {
            foreach ($progress_attempts as $progress_attempt) {
                $submissionDate = $progress_attempt->getSubmissionDate();
                if (isset($submissionDate) && $submissionDate >= 0) {
                    $finish_count++;
                }
            }
        }

        $html = "<tr data-post-id=\"" . $exam_post->getID() . "\" class=\"exam-posting\" >\n";
            $html .= "<td class=\"modified\" style=\"width: 50px; white-space: nowrap\">\n";
                $html .= "<input class=\"delete_exam_post\" type=\"checkbox\" name=\"delete[]\" value=\"" . $exam_post->getID() . "\" data-post-id=\"" . $exam_post->getID() . "\" data-post-title=\"" . html_encode($exam_post->getTitle()) . "\" style=\"vertical-align: middle\" />\n";
            if ($finish_count > 0) {
                $html .= "<a href=\"" .  ENTRADA_URL . "/admin/exams/exams?section=activity&amp;id=" . $exam_post->getID() . "\">\n";
                    $html .= "<img src=\"" . ENTRADA_URL . "/images/view-stats.gif\" width=\"16\" height=\"16\" alt=\"View results of " . html_encode($exam_post->getTitle()) . "\" title=\"View results of " . html_encode($exam_post->getTitle()) . "\" style=\"vertical-align: middle\" border=\"0\" />\n";
                $html .= "</a>\n";
            } else {
                $html .= "<img src=\"" . ENTRADA_URL . "/images/view-stats-disabled.gif\" width=\"16\" height=\"16\" alt=\"No completed exams at this time.\" title=\"No completed exams at this time.\" style=\"vertical-align: middle\" border=\"0\" />\n";
            }
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($exam_post->getMandatory() ? "Yes" : "No");
            $html .= "</td>\n";
                $html .= "<td class=\"title\" style=\"white-space: normal; overflow: visible\">\n";
            $html .= "<a data-id=\"" . $exam_post->getID() . "\" class=\"exam-post-listing\" title=\"Click to edit " . html_encode($exam_post->getTitle()) . "\" href=\"" .  ENTRADA_URL . "/admin/exams/exams?section=form-post&post_id=" . $exam_post->getID() . "&target_type=event&target_id=" . $exam_post->getEvent()->getID() . "\">\n";
                $html .= "<strong>" . html_encode($exam_post->getTitle()) . "</strong>\n";
                $html .= ($exam_post->getSecure() == 1) ? " <i class=\"icon-lock\"></i>" : "";
            $html .= "</a>\n";
                $html .= "</td>\n";
            $html .= "<td class=\"date-small\">\n";
                $html .= "<span class=\"content-date\">\n";
                    $html .= (((int) $exam_post->getStartDate()) ? date(DEFAULT_DATETIME_FORMAT, $exam_post->getStartDate()) : "No Restrictions");
                $html .= "</span>\n";
            $html .= "</td>\n";
            $html .= "<td class=\"date-small\">\n";
                $html .= "<span class=\"content-date\">\n";
                    $html .= (((int) $exam_post->getEndDate()) ? date(DEFAULT_DATETIME_FORMAT, $exam_post->getEndDate()) : "No Restrictions");
                $html .= "</span>\n";
            $html .= "</td>\n";
            $html .= "<td class=\"accesses\" style=\"text-align: center\">\n";
                $html .= $finish_count . "\n";
            $html .= "</td>\n";
        $html .= "</tr>\n";

        return $html;
    }

    /**
     * @return string
     */
    public function renderPostRow() {
        global $ENTRADA_USER;
        $post   = $this->post;
        $course = $post->getCourse();
        $score_review           = $post->getReleaseScore();
        $feedback_review        = $post->getReleaseFeedback();
        $score_start_valid      = $post->isScoreStartValid();
        $score_end_valid        = $post->isScoreEndValid();
        $feedback_start_valid   = $post->isFeedbackStartValid();
        $feedback_end_valid     = $post->isFeedbackEndValid();
        $resume_allowed         = $post->isResumeAttemptAllowedByUser($ENTRADA_USER);
        $new_allowed            = $post->isNewAttemptAllowedByUser($ENTRADA_USER);
        
        $url                    = "<a href=\"" . ENTRADA_URL . "/exams?section=post&id=" . $post->getID() . "\">";

        $progress               = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post->getID(), $ENTRADA_USER->getID(), "submitted");
        $exam_exception         = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());

        $use_exam_start_date    = $post->getUseExamStartDate();
        $use_exam_end_date      = $post->getUseExamEndDate();
        $use_exam_sub_date      = $post->getUseSubmissionDate();
        $use_release_start_date = $post->getUseReleaseStartDate();
        $use_release_end_date   = $post->getUseReleaseEndDate();

        $exam_start_date        = $post->getStartDate();
        $exam_end_date          = $post->getEndDate();
        $exam_sub_date          = $post->getSubmissionDate();
        $release_start_date     = $post->getReleaseStartDate();
        $release_end_date       = $post->getReleaseEndDate();

        /**
         * Feedback Report and Score Report will always be NO if an attempt is not submitted
         *
         * Resume is currently Yes, whether or not there is an exam to resume.
         */
        if ($progress && is_array($progress) && !empty($progress)) {
            foreach ($progress as $progress_obj) {
                if ($progress_obj->getProgressValue() == "submitted") {
                    if ($feedback_review && $feedback_start_valid && $feedback_end_valid) {
                        $feedback_report = 1;
                    }
                    if ($score_review && $score_start_valid && $score_end_valid) {
                        $score_report = 1;
                    }
                }
            }
        }

        if ($exam_exception && is_object($exam_exception)) {
            if ($exam_exception->getExcluded() !== 1) {
                if ($exam_exception->getUseStartDate() === 1) {
                    $exam_start_date      = $exam_exception->getStartDate();
                    $use_exam_start_date  = 1;
                }

                if ($exam_exception->getUseEndDate() === 1) {
                    $exam_end_date      = $exam_exception->getEndDate();
                    $use_exam_end_date  = 1;
                }

                if ($exam_exception->getUseSubmissionDate() === 1) {
                    $exam_sub_date      = $exam_exception->getSubmissionDate();
                    $use_exam_sub_date  = 1;
                }
            }
        }

        $html = "<tr>\n";
        $html .= "<td>\n";
        $html .= $url;
        $html .= "<strong>" . $post->getTitle() . "</strong>\n";
        $html .= "</a>\n";
        $html .= "<i class=\"attempt-info fa fa-bar-chart\" data-post-id=\"" . $post->getID() . "\"></i>\n";
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($new_allowed ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($resume_allowed ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($score_report ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($feedback_report ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($this->post->getMandatory() ? "Yes" : "No");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($use_exam_start_date && $exam_start_date ? date("m-d-Y g:i", $exam_start_date) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($use_exam_end_date && $exam_end_date ? date("m-d-Y g:i", $exam_end_date) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($use_exam_sub_date && $exam_sub_date ? date("m-d-Y g:i", $exam_sub_date) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($use_release_start_date && $release_start_date ? date("m-d-Y g:i", $release_start_date) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= ($use_release_end_date && $release_end_date ? date("m-d-Y g:i", $release_end_date) : "N/A");
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $course->getCourseCode();
        $html .= "</td>\n";
        $html .= "<td>\n";
        $html .= $course->getCourseName();
        $html .= "</td>\n";

        return $html;
    }

    /**
     * @return string
     */
    public function renderAdminRow() {
        $post = $this->post;

        $creator_id = $post->getCreatedBy();
        $updater_id = $post->getUpdatedBy();
        $course = ($post->getCourse()? $post->getCourse() : null);

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

        $progress_attempts = Models_Exam_Progress::fetchAllByPostID($post->getID());
        $start_count = 0;
        $finish_count = 0;

        if (isset($progress_attempts) && is_array($progress_attempts)) {
            $start_count = count($progress_attempts);
            foreach ($progress_attempts as $progress_attempt) {
                $submissionDate = $progress_attempt->getSubmissionDate();
                if (isset($submissionDate) && $submissionDate >= 0) {
                    $finish_count++;
                }
            }
        }

        $html = "<tr>\n";
            $html .= "<td>\n";
                $html .= $post->getTitle();
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= "<a href=\"" . $this->getTargetPublicURL() . "\">";
                    $html .= "<strong>" . $this->getTargetTitle() . "</strong>";
                $html .= "</a>";
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($course ? $course->getCourseName() : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($course ? $course->getCourseCode() : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= $start_count;
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= $finish_count;
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= $post->getMaxAttempts();
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getUseExamStartDate() && $post->getStartDate() ? date("m-d-Y g:i", $post->getStartDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getUseExamEndDate() && $post->getEndDate() ? date("m-d-Y g:i", $post->getEndDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getUseSubmissionDate() && $post->getSubmissionDate() ? date("m-d-Y g:i", $post->getSubmissionDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getReleaseScore() ? "Yes" : "No");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getReleaseStartDate() ? date("m-d-Y g:i", $post->getReleaseStartDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getReleaseEndDate() ? date("m-d-Y g:i", $post->getReleaseEndDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getReleaseFeedback() ? "Yes" : "No");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= $creator_name;
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getCreatedDate() ? date("m-d-Y g:i", $post->getCreatedDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= ($post->getUpdatedDate() ? date("m-d-Y g:i", $post->getUpdatedDate()) : "N/A");
            $html .= "</td>\n";
            $html .= "<td>\n";
                $html .= $updater_name;
            $html .= "</td>\n";
            $html .= "<td class=\"nowrap\">\n";
                $html .= $this->getPostEditMenu();
            $html .= "</td>\n";
        $html .= "</tr>\n";

        return $html;
    }

    /**
     * @return string
     */
    public function getPostEditMenu() {
        global $ENTRADA_ACL, $ENTRADA_USER, $translate;
        $MODULE_TEXT = $translate->_("exams");
        $MENU_TEXT = $MODULE_TEXT["exams"]["posts"]["edit_menu"];
        $exam = $this->post->getExam();

        $can_update = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
        $can_view   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read");
        $can_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete");
        $can_grade  = Models_Exam_Grader::isExamPostGradableBy($this->post->getID(), $ENTRADA_USER->getProxyId());
        $can_rp_now = ($this->post->getSecureMode() == "rp_now" ? true : false);

        $html = "<div class=\"btn-group\">\n";
        if ($can_rp_now) {
            $html .= "<div class=\"btn-group\">\n";
            $html .= "<button class=\"btn btn-mini email_rpnow\" data-post=". $this->post->getID() . ">\n";
            $html .= "<i class=\"fa fa-envelope\"></i>\n";
            $html .= "</button>\n";
        }
            $html .= "<button class=\"btn btn-mini dropdown-toggle\" data-toggle=\"dropdown\">\n";
                $html .= "<i class=\"fa fa-cog\"></i>\n";
            $html .= "</button>\n";
            $html .= "<ul class=\"dropdown-menu toggle-left\">\n";


            if ($can_view) {
                $html .= "<li><a href=\"" . ENTRADA_URL ."/admin/exams/exams?section=activity&id=" . $this->post->getID() . "\">" . $MENU_TEXT["view"] . "</a></li>\n";
            }
            if ($can_grade) {
                $html .= "<li><a href=\"" . ENTRADA_URL ."/admin/exams/grade?post_id=" . $this->post->getID() . "\" >" . $MENU_TEXT["grade"] . "</a></li>\n";
            }
            if ($can_update) {
                $html .= "<li><a href=\"".ENTRADA_URL."/admin/exams/exams?section=graders&post_id=".$this->post->getID()."\">".$MENU_TEXT["edit_graders"]."</a></li>\n";
                $html .= "<li><a href=\"".ENTRADA_URL."/admin/exams/exams?section=form-post&id=".$exam->getID()."&post_id=".$this->post->getID()."&target_type=event\" data-id=\"" . $this->post->getID()."\">".$MENU_TEXT["edit"]."</a></li>\n";
                /*
                $html .= "<li><a href=\"#\" >" . $MENU_TEXT["repost"] . "</a></li>\n";
                */
            }

            if ($can_delete) {
                /*
                $html .= "<li><a href=\"#\" >" . $MENU_TEXT["delete"] . "</a></li>\n";
                */
            }

            $html .= "</ul>\n";

        $html .= "</div>\n";
        return $html;
    }

    public function getTargetTitle() {
        $target_id = $this->post->getTargetID();
        switch ($this->post->getTargetType()) {
            case "event" :
                $event = Models_Event::fetchRowByID($target_id);
                if (isset($event) && is_object($event)) {
                    $title = "Event: " . $event->getEventTitle();
                }
                break;
            case "community" :
                $community = Models_Community::fetchRowByCommunityID($target_id);
                if (isset($community) && is_object($community)) {
                    //todo need to check this is working
                    $title = "Community: " . $community->getCommunityTitle();
                }
                break;
        }

        return $title;
    }

    public function getTargetPublicURL($admin = false) {
        $export = "";
        $target_id = $this->post->getTargetID();
        switch ($this->post->getTargetType()) {
            case "event" :
                $event = Models_Event::fetchRowByID($target_id);
                if (isset($event) && is_object($event)) {
                    if ($admin) {
                        $url = ENTRADA_RELATIVE . "/admin/events?id=" . $event->getID() . "&section=content#event-resources-exams";
                    } else {
                        $url = ENTRADA_RELATIVE . "/events?id=" . $event->getID() . "#event-resources-exams";
                    }
                }
                break;
            case "community" :
                $community = Models_Community::fetchRowByCommunityID($target_id);
                if (isset($community) && is_object($community)) {
                    //todo need to write this
                }
                break;
        }

        return $url;
    }

    public function renderHonorCode() {
        global $translate;

        $html = "<hr />";
        $html .= "<div class=\"well\">";
        $html .= "    <div class=\"row-fluid\" id=\"honor-code-instructions\">";
        $html .= "        <div class=\"span3\"><strong>" . $translate->_("Honor Code") . "</strong></div>";
        $html .= "        <div class=\"span9\">" .  $this->post->getHonorCode() . "</div>";
        $html .= "    </div>";
        $html .= "    <div class=\"row-fluid\">";
        $html .= "        <div class=\"span9\">";
        $html .= "            <input type=\"checkbox\" id=\"honor-code-ok\" name=\"honor-code-ok\"/>";
        $html .= "            <label for=\"honor-code-ok\">";
        $html .=  $translate->_("I acknowledge the above Honor Code.");
        $html .= "            </label>";
        $html .= "        </div>";
        $html .= "    </div>";
        $html .= "</div>";
        $html .= "<hr />";

        return $html;
    }

    public function renderPublicPostSettings() {
        global $translate;

        $MODULE_TEXT = $translate->_("exams");
        $SECTION_TEXT = $MODULE_TEXT["posts"];

        $exam_display_questions = $this->post->getExam()->getDisplayQuestions();


        switch ($exam_display_questions) {
            case "one":
                $qpp = $SECTION_TEXT["text_opp"];
                break;
            case "page_breaks":
                $qpp = $SECTION_TEXT["text_vpp"];
                break;
            case "all":
            default:
                $qpp = $SECTION_TEXT["text_aop"];
                break;
        }

        $messages           = $this->generateTimeMessages();
        $available          = $messages["available"];
        $attempts_allowed   = $messages["attempts_allowed"];
        $sub_date           = $messages["sub_date"];
        $time_limit_display = $messages["time_limit"];
        $auto_submit        = $messages["auto_submit"];

        $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($this->post->getExamID(), "question");

        if (isset($exam_elements) && is_array($exam_elements) && !empty($exam_elements)) {
            $question_count = count($exam_elements);
        }

        $html = "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong>" . $SECTION_TEXT["table_headers"]["instructions"] . "</strong></div>";
            $html .= "<div class=\"span9\">" .  $this->post->getInstructions() . "</div>";
        $html .= "</div>";
        $html .= "<hr />";

        $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["available"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["available"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . $available . "</div>";
        $html .= "</div>";

        $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["submission_deadline"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["submission_deadline"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . $sub_date . "</div>";
        $html .= "</div>";

        $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["time_limit"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["time_limit"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . ($time_limit_display ? $time_limit_display : "No limit set") . "</div>";
        $html .= "</div>";

        if ($time_limit_display) {
            $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["auto_submit"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["auto_submit"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . ($auto_submit ? "Yes" : "No") . "</div>";
            $html .= "</div>";
        }

        $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["mandatory"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["mandatory"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . ($this->post->getMandatory() ? "Yes" : "No") . "</div>";
        $html .= "</div>";

        $html .= "<div class=\"row-fluid\">";
            $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["attempts"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["attempts"] . "</a></strong></div>";
            $html .= "<div class=\"span9\">" . ($attempts_allowed ? $attempts_allowed : "Unlimited") . "</div>";
        $html .= "</div>";

        $html .= "<div class=\"row-fluid\">";
        $html .= "<div class=\"span3\"><strong><a href=\"#\" data-toggle=\"tooltip\" title=\"" . $SECTION_TEXT["tool_tips"]["total_questions"] . "\" class=\"settings_tooltip\">" . $SECTION_TEXT["table_headers"]["total_questions"] . "</a></strong></div>";
        $html .= "<div class=\"span9\">" . $question_count . "</div>";
        $html .= "</div>";

        return $html;
    }

    public function render($admin_row = false) {
        global $translate;
        $MODULE_TEXT = $translate->_("exams");
        if ($this->post !== null) {
            if ($admin_row == false) {
                return $this->renderPostRow();
            } else {
                return $this->renderAdminRow();
            }
        } else {
            echo display_notice($MODULE_TEXT["posts"]["text_no_available_posts"]);
        }
    }
}
