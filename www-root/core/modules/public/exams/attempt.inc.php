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
 * This section is loaded when an individual wants to attempt a quiz.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_GET["id"])) {
    $post_id = $_GET["id"];
}

if (isset($_GET["progress_id"])) {
    $progress_id = (int)$_GET["progress_id"];
}
if (isset($_GET["page"])) {
    $page = (int)$_GET["page"];
} else {
    $page = 1;
}
if (isset($_GET["continue"]) && $tmp_input = clean_input($_GET["continue"], "bool")) {
    $PROCESSED["continue"] = $tmp_input;
} else {
    $PROCESSED["continue"] = false;
}
if (isset($_POST["resume_password"]) && $tmp_input = clean_input($_POST["resume_password"], "string")) {
    $PROCESSED["resume_password"] = $tmp_input;
} else {
    $PROCESSED["resume_password"] = false;
}

if (isset($_GET["action"]) && $tmp_input = clean_input($_GET["action"], "string")) {
    $show_instructions = false;
    $attempt_in_progress = false;
    $PROCESSED["action"] = $tmp_input;
    switch ($PROCESSED["action"]) {
        case "instructions":
            $action = "instructions";
            break;
        case "resume" :
            $action = "resume";
            break;
        case "start" :
            $action = "start";
            break;
        default :
            $action = "";
            break;
    }
} else {
    $action = "";
}
$secure_mode = (isset($EXAM_MODE) && $EXAM_MODE === "secure") ? true : false;

$allow_resume_secure = true;
if (isset($post_id)) {
    $post = Models_Exam_Post::fetchRowByID($post_id);
    if ($post->getResumePassword() != null && $post->getSecureMode() != "seb") {
        if (!isset($_SESSION["ExamAuth"][$post_id]) || (!$_SESSION["ExamAuth"][$post_id])) {
            $allow_resume_secure = false;
        }
    }
}

$MODULE_TEXT = $translate->_($MODULE);
$SECTION_TEXT = $MODULE_TEXT[$SECTION];

$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/summernote/summernote.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/javascript/CalcSS3/CalcSS3.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release= ". html_encode(APPLICATION_VERSION) . "\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/summernote/summernote.js?release= ". html_encode(APPLICATION_VERSION) . "\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/summernote/plugins/disableEditing.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

$HEAD[] = "<script type=\"text/javascript\">var SECTION_TEXT = " . json_encode($SECTION_TEXT) . "</script>";
$HEAD[] = "<script type=\"text/javascript\">var EXAM_STORAGE_PATH = " . json_encode(EXAM_STORAGE_PATH) . "</script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE . "-public.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

// Error checking
switch ($STEP) {
    case 2 :
        if (has_error()) {
            $STEP = 1;
        }
        break;
    case 1 :
    default :
        continue;
        break;
}

switch ($STEP) {
    case 2 :
        if (has_success()) {
            echo display_success();
        }
        break;
    case 1:

    default :
        if (isset($post_id)) {

            $access_audience = false;
            $access_time     = false;
            $new_start       = false;
            $post = Models_Exam_Post::fetchRowByID($post_id);
            if (isset($post) && is_object($post)) {
                if ($post->getSecure() === "1" && $post->getSecureMode() === "rp_now" && strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "rpnow") !== false) {
                    $grant_access = true;
                }
                if ($post->getSecure() !== "1" || ($post->getSecure() === "1" && $grant_access === true) || ($post->getSecure() === "1" && $post->getSecureMode() == "basic")) {
                    $HEAD[] = "<script type=\"text/javascript\">var AUTO_SAVE_TIMER = " . (int)$post->getAutoSave() . "</script>";

                    echo "<h1>" . html_encode($post->getTitle()) . "</h1>";
                    echo "<div id=\"display-error-box\" class=\"clear\"></div>";
                    $post_type = $post->getTargetType();

                    if ($post_type == "event") {
                        $access_audience = $post->check_event_audience($ENTRADA_USER);
                    } else if ($post_type == "community") {
                        //get community audience
                        $community_id = $post->getTargetID();
                    } else if ($post_type == "preview") {
                        $access_audience = $post->canEditExam();
                    }

                    if ($access_audience === true) {
                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
                        if (isset($exam) && is_object($exam)) {

                            //this section resets the exam
                            $new_start = false;
                            switch ($action) {
                                case "start":
                                    if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                                        $access_time = true;
                                        $new_start = true;
                                    }
                                    break;
                                case "resume":
                                    if ($post->isResumeAttemptAllowedByUser($ENTRADA_USER)) {
                                        $access_time = true;
                                        $progress = Models_Exam_Progress::fetchRowByID($progress_id);
                                    }
                                    break;
                                default:
                                    if ($post->isResumeAttemptAllowedByUser($ENTRADA_USER)) {
                                        $access_time = true;
                                        $progress_results = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                                        if (!empty($progress_results)) {
                                            //use current progress and orders
                                            $progress = false;
                                            foreach ($progress_results as $progress_result) {
                                                if ($progress_result->getProgressValue() === "inprogress") {
                                                    $progress = $progress_result;
                                                    break;
                                                }
                                            }
                                            if (!$progress) {
                                                if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                                                    $access_time = true;
                                                    $new_start = true;
                                                } else {
                                                    $access_time = false;
                                                    $new_start = false;
                                                }
                                            }
                                        } else {
                                            if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                                                $access_time = true;
                                                $new_start = true;
                                            } else {
                                                $access_time = false;
                                                $new_start = false;
                                            }
                                        }
                                    } elseif ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                                        $access_time = true;
                                        $new_start = true;
                                    }
                                    break;
                            }

                            if ($access_time === true) {
                                /**
                                 * Determine if the user must enter a password to resume their exam attempt
                                 */
                                //Validate the resume password
                                $allow_resume = false;

                                if ($progress) {
                                    if ($post->getUseResumePassword() !== "1" || $post->getSecure() !== "1" || $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["attempt_in_progress"] === true) {
                                        $allow_resume = true;
                                    } elseif ($post->getUseResumePassword() === "1" && $post->getSecure() === "1" && $post->getSecureMode() !== "seb") {
                                        $allow_resume = true;
                                    } elseif ($post->getUseResumePassword() === "1" && $post->getSecure() === "1" && $post->getSecureMode() === "seb") {
                                        if ($PROCESSED["resume_password"] && $PROCESSED["resume_password"] != "") {
                                            if ($PROCESSED["resume_password"] === $post->getResumePassword()) {
                                                $allow_resume = true;
                                                $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["resume_password_valid"] = true;
                                                $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["attempt_in_progress"] = true;
                                            } else {
                                                add_error($translate->_($SECTION_TEXT["text"]["bad_resume_password"]));

                                                echo display_error();
                                            }
                                        } elseif ($new_start === true) {
                                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["resume_password_valid"] = true;
                                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["attempt_in_progress"] = true;
                                        }
                                    }
                                }

                                /**
                                 * Create the progress record if the user is starting a new attempt
                                 */
                                if ($new_start === true) {
                                    $progress = new Models_Exam_Progress(
                                        array(
                                            "post_id" => $post->getID(),
                                            "exam_id" => $post->getExamID(),
                                            "proxy_id" => $ENTRADA_USER->getID(),
                                            "progress_value" => "inprogress",
                                            "menu_open" => 1,
                                            "created_date" => time(),
                                            "created_by" => $ENTRADA_USER->getID(),
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID(),
                                            "started_date" => 0
                                        )
                                    );

                                    if ($secure_mode !== true){
                                        $progress->setStartedDate(time());
                                    }

                                    if ($progress->insert()){
                                        $progress->setID($db->Insert_ID());
                                    } else {
                                        add_error($translate->_("An error occurred while attempting to create your exam attempt in the database. Please try again. If the problem persists, please notify an administrator."));
                                        application_log("error", "An error occurred while attempting to insert an Exam Progress record for User ".$ENTRADA_USER->getID()." for Post ".$post->getID());
                                        echo display_error();
                                        exit;
                                    }
                                }

                                //generates the exam question order if the order has not already been saved
                                if (!$responses = $progress->getExamProgressResponses()) {
                                    $reload = false;
                                    // $post->getQuestionsPerPage() == 0)
                                    $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
                                    if (isset($exam_elements) && is_array($exam_elements) && !empty($exam_elements)) {
                                        $order_count = 0;
                                        $question_count = 0;
                                        if ($exam->getRandom() == 1) {
                                            $exam_view = new Views_Exam_Exam($exam);
                                            $exam_elements = $exam_view->randomizeExam($exam_elements);
                                        }
                                        $insert_collection = array();

                                        foreach ($exam_elements as $exam_element) {
                                            switch ($exam_element->getElementType()) {
                                                case "question" :
                                                    $question = Models_Exam_Question_Versions::fetchRowByVersionID($exam_element->getElementID());
                                                    $question_lu_type = $question->getQuestionType();

                                                    $shortname = $question_lu_type->getShortname();
                                                    ($shortname != "text" ? $question_count++ : "");

                                                    $new_response = array(
                                                        "exam_progress_id" => $progress->getID(),
                                                        "exam_id" => $progress->getExamID(),
                                                        "post_id" => $progress->getPostID(),
                                                        "proxy_id" => $progress->getProxyID(),
                                                        "exam_element_id" => $exam_element->getID(),
                                                        "epr_order" => $order_count,
                                                        "question_count" => ($shortname != "text" ? $question_count : NULL),
                                                        "question_type" => $db->qstr($shortname),
                                                        "created_date" => time(),
                                                        "created_by" => $ENTRADA_USER->getID()
                                                    );

                                                    $order_count++;
                                                    break;
                                                case "text" :
                                                    $new_response = array(
                                                        "exam_progress_id" => $progress->getID(),
                                                        "exam_id" => $progress->getExamID(),
                                                        "post_id" => $progress->getPostID(),
                                                        "proxy_id" => $progress->getProxyID(),
                                                        "exam_element_id" => $exam_element->getID(),
                                                        "epr_order" => $order_count,
                                                        "question_count" => NULL,
                                                        "question_type" => NULL,
                                                        "created_date" => time(),
                                                        "created_by" => $ENTRADA_USER->getID()
                                                    );
                                                    $order_count++;
                                                    break;
                                                case "page_break":
                                                    $new_response = array(
                                                        "exam_progress_id" => $progress->getID(),
                                                        "exam_id" => $progress->getExamID(),
                                                        "post_id" => $progress->getPostID(),
                                                        "proxy_id" => $progress->getProxyID(),
                                                        "exam_element_id" => $exam_element->getID(),
                                                        "epr_order" => $order_count,
                                                        "question_count" => NULL,
                                                        "question_type" => NULL,
                                                        "created_date" => time(),
                                                        "created_by" => $ENTRADA_USER->getID()
                                                    );
                                                    $order_count++;
                                                    break;
                                            }
                                            /**
                                             * Generate insert query for response
                                             */
                                            $new_response_list = implode(",", $new_response);
                                            $insert_collection[] = "(" . $new_response_list . ")";
                                        }

                                        /**
                                         * Build query and insert exam progress responses
                                         */
                                        if (!empty($insert_collection)) {
                                            $insert_sql = "INSERT INTO " . Models_Exam_Progress_Responses::TABLE_NAME . " (`exam_progress_id`, `exam_id`, `post_id`, `proxy_id`, `exam_element_id`, `epr_order`, `question_count`, `question_type`, `created_date`, `created_by`) VALUES " . implode(", ", $insert_collection);

                                            // Adds NULLs to the insert where missing.
                                            do {
                                                $insert_sql = str_replace(",,", ",NULL,", $insert_sql);
                                            } while (strpos($insert_sql, ",,") != false);

                                            if (!$db->Execute($insert_sql)) {
                                                add_error("Error, could not insert the exam progress responses");
                                                echo display_error();
                                            } else {
                                                $reload = true;
                                            }
                                        }
                                    }

                                    /**
                                     * Deprecated reloading of the exam after the initial order has been saved
                                     * This only seemed to make it more difficult in SEB to properly display the correct message to the user
                                     * on whether they were resuming a previous attempt or just starting a new one
                                     */
                                    switch ($EXAM_MODE) {
                                        case "secure":
                                            $entrada_url = ENTRADA_RELATIVE . "/secure";
                                            break;
                                        default:
                                            $entrada_url = ENTRADA_RELATIVE;
                                            break;
                                    }
                                    if ($reload == true) {
                                        $redirect_url = $entrada_url . "/exams?section=attempt&action=instructions&continue=true&id=" . $post_id . "&progress_id=" . $progress->getID();
                                        header("Location: " . $redirect_url);
                                    }
                                }
                                //only allow to continue if the exam is still in progress
                                $attempt_in_progress = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()][$progress->getID()]["attempt_in_progress"];
                                if ($progress->getStartedDate() === NULL || $progress->getStartedDate() <= 0){
                                    $show_instructions = true;
                                }
                                if (($allow_resume === true || $new_start === true || $attempt_in_progress === true) && ($show_instructions !== true || $secure_mode !== true) && $allow_resume_secure === true) {
                                    $responses = Models_Exam_Progress_Responses::fetchAllByProgressID($progress->getID());
                                    //shows the exam on the page
                                    if (isset($responses) && is_array($responses) && !empty($responses)) {
                                        $time_limit = $post->getTimeLimit();

                                        $exam_exceptions = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());
                                        if (isset($exam_exceptions) && is_object($exam_exceptions)) {
                                            $excp_use_time_limit = $exam_exceptions->getUseExceptionTimeFactor();
                                            $excp_time_limit     = $exam_exceptions->getExceptionTimeFactor();
                                            
                                            if ($excp_use_time_limit) {
                                                $time_limit = $time_limit * (($excp_time_limit + 100) / 100);
                                            }
                                        }
                                        ?>
                                        <script>
                                            post_id             = <?php echo $post->getID();?>;
                                            exam_id             = <?php echo $post->getExamID();?>;
                                            exam_progress_id    = <?php echo $progress->getID();?>;
                                            proxy_id            = <?php echo $ENTRADA_USER->getID();?>;
                                            menu_open           = <?php echo $progress->getMenuOpen();?>;
                                            current_page        = <?php echo $page;?>;
                                            use_time_limit      = <?php echo (int)$post->getUseTimeLimit($ENTRADA_USER);?>;
                                            time_limit          = <?php echo (int)$time_limit;?>;
                                            use_self_timer      = "<?php echo $post->getUseSelfTimer();?>";
                                            create_time         = <?php echo $progress->getCreatedDate();?>;
                                            start_time          = <?php echo $progress->getStartedDate();?>;
                                            self_timer_start    = "<?php echo $progress->getSelfTimerStart();?>";
                                            self_timer          = "<?php echo $progress->getSelfTimerLength();?>";
                                            auto_submit         = <?php echo (int)$post->getAutoSubmit();?>;
                                            use_submission_date = <?php echo (int)$post->getUseSubmissionDateException($ENTRADA_USER);?>;
                                            allow_feedback      = <?php echo (int)$post->getAllowFeedback();?>;
                                            submission_date     = <?php echo $post->getSubmissionDateException($ENTRADA_USER) * 1000;?>;
                                            text_save           = "<?php echo $SECTION_TEXT["buttons"]["btn_save"];?>";
                                            text_saved          = "<?php echo $SECTION_TEXT["buttons"]["btn_saved"];?>";
                                            auto_submit_msgs = [];
                                            auto_submit_msgs["0"] = "<?php echo $SECTION_TEXT["text"]["auto_submit_msgs_0"];?>";
                                            auto_submit_msgs["1"] = "<?php echo $SECTION_TEXT["text"]["auto_submit_msgs_1"];?>";
                                            auto_submit_msgs["2"] = "<?php echo $SECTION_TEXT["text"]["auto_submit_msgs_2"];?>";
                                            auto_submit_msgs["3"] = "<?php echo $SECTION_TEXT["text"]["auto_submit_msgs_3"];?>";
                                        </script>
                                        <?php
                                        //Insert a prompt for the user to supply a resume password

                                        // set a cookie to expire when the browser is closed
                                        $session_started = "exam_start_attempt_" . $progress->getID();
                                        setcookie($session_started, 1);
                                        $session_started_cookie = $_COOKIE[$session_started];
                                        $cookie_name = "exam_dismiss_attempt_message_" . $progress->getID();
                                        $displayed = $_COOKIE[$cookie_name];

                                        if ($new_start === false && $PROCESSED["continue"] === false && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["exams"]["posts"][$post->getPostID()]["instructions_viewed"] === false && $displayed != 1 && !isset($session_started_cookie)) { ?>
                                            <div class="alert alert-info">
                                                <a id="dismiss_attempt_message" class="close" data-dismiss="alert" href="#" data-progress-id="<?php echo $progress->getID() ?>"><i class="fa fa-times"></i></a>
                                                <h3>Your previous attempt has been loaded.</h3>
                                                <p>We've found your previous exam attempt and have loaded it for you to
                                                    complete. Please complete the exam and click
                                                    <strong><?php echo $SECTION_TEXT["buttons"]["btn_submit"]; ?></strong>
                                                </p>
                                            </div>
                                        <?php }

                                        /*
                                         * If backtracking is turned off we're checking if the previous pages
                                         * have been answered or not. If they haven't then we display a link to the
                                         * page they should be on and don't display the page elements.
                                         */
                                        $progress_view = new Views_Exam_Progress($progress);
                                        $progress_value = $progress->getProgressValue();
                                        if ($post->getBacktrack() != 1) {
                                            $progress_page = $progress_view->checkPreviousPageResponses($page);
                                            if ($page != $progress_page) {
                                                //user isn't on the correct page and should be directed back to that page.
                                                $url = " <a href=\"" . ENTRADA_URL . "/" . $MODULE . "?section=" . $SECTION . "&action=" . $_GET["action"] . "&id=" . $_GET["id"] . "&progress_id=" . $_GET["progress_id"] . "&page=" . $progress_page . "\"><strong>" . $SECTION_TEXT["text"]["page"] . "</strong></a>";
                                                add_error($SECTION_TEXT["text"]["backtrack_error_01"] . $url . ".");

                                                echo display_error();
                                            } else {
                                                if ($progress_value === "inprogress") {
                                                    //user is on the correct page render progress
                                                    $progress_view = new Views_Exam_Progress($progress);
                                                    echo $progress_view->renderExamProgress($page);
                                                } else if ($progress_value === "submitted") {
                                                    $url = " <a href=\"" . ENTRADA_URL . "/" . $MODULE . "?section=post&id=" . $progress->getPostID() . "\"><strong>" . $SECTION_TEXT["text"]["review_link_02"] . "</strong></a>";
                                                    add_error($SECTION_TEXT["text"]["submit_already"] . $SECTION_TEXT["text"]["submit_already2"] . strtolower($SECTION_TEXT["text"]["review_link_01"]) . $url . ".");

                                                    echo display_error();
                                                }
                                            }
                                        } else {
                                            if ($progress_value === "inprogress") {
                                                echo $progress_view->renderExamProgress($page);
                                            } else if ($progress_value === "submitted") {
                                                $url = " <a href=\"" . ENTRADA_URL . "/" . $MODULE . "?section=post&id=" . $progress->getPostID() . "\"><strong>" . $SECTION_TEXT["text"]["review_link_02"] . "</strong></a>";
                                                add_error($SECTION_TEXT["text"]["submit_already"] . $SECTION_TEXT["text"]["submit_already2"] . strtolower($SECTION_TEXT["text"]["review_link_01"]) . $url . ".");

                                                echo display_error();
                                            }
                                        }
                                        ?>
                                        <div id="pdf_viewer_fixed">
                                            <div id="pdf_viewer_container">
                                                <div id="pdf_viewer" class="hide">
                                                    <div class="row-fluid">
                                                        <div id="pdf_close" class="pull-left"><i class="fa fa-close"></i></div>
                                                        <div id="pdf_handle" class="pull-right"><i class="fa fa-arrows"></i></div>
                                                    </div>
                                                    <iframe id="iframe_path" src=""></iframe>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="confirmation-modal" class="modal hide fade">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo $SECTION_TEXT["title_exam_submission"]; ?></h4>
                                                    </div>
                                                    <div class="modal-body" >
                                                        <div id="missing-responses" class="alert alert-notice">
                                                            <p><i class="fa fa-spinner fa-pulse"></i> <?php echo $SECTION_TEXT["text"]["answer_check"]; ?></p>
                                                        </div>
                                                        <p><?php echo $SECTION_TEXT["text"]["submit_confirmation"]; ?></p>
                                                        <p><?php echo $SECTION_TEXT["text"]["return_to_exam"]; ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_cancel"];?></button>
                                                        <button type="button" class="btn btn-primary submit-exam"><?php echo $SECTION_TEXT["buttons"]["btn_submit"]?></button>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div><!-- /.modal -->

                                        <div id="time-limit-modal" class="modal hide fade">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo $SECTION_TEXT["title_exam_time_limit"]; ?></h4>
                                                    </div>
                                                    <div class="modal-body" >
                                                        <div id="missing-responses" class="alert alert-notice">
                                                            <p><?php echo $SECTION_TEXT["text"]["time_limit_error_01"]; ?></p>
                                                        </div>
                                                        <p><?php echo $SECTION_TEXT["text"]["return_to_exam_close"]; ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_close"];?></button>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div><!-- /.modal -->

                                        <div id="self-timer-limit-modal" class="modal hide fade">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo $SECTION_TEXT["title_exam_self_timer"]; ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <p><strong>End of Self Timer Reached.</strong></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_close"];?></button>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div><!-- /.modal -->

                                        <div id="self-timer-modal" class="modal hide fade">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo $SECTION_TEXT["text"]["self_timer_title"]; ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div id="self-timer-inputs">
                                                            <div class="control-group">
                                                                <label for="use_self_timer" class="control-label form-nrequired">
                                                                    <?php echo $SECTION_TEXT["text"]["self_timer_text"];; ?>
                                                                </label>
                                                                <div class="controls">
                                                                    <div class="input-append space-right">
                                                                        <input id="use_self_timer" type="checkbox"
                                                                               name="use_self_timer"<?php echo ($progress && (int)$progress->getUseSelfTimer() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                                                    </div>
                                                                    <div class="input-append space-right">
                                                                        <input id="time_limit_hours" class="input-small" type="text"
                                                                               name="time_limit_hours"<?php echo (!$progress || $progress->getUseSelfTimer() != "1") ? " disabled=\"disabled\"" : " value=\"" . (int)floor($progress->getSelfTimerLength() / 60) . "\""; ?> />
                                                                        <span class="add-on">
                                                                            <?php echo $SECTION_TEXT["text"]["time_hours"]; ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="input-append">
                                                                        <input id="time_limit_mins" class="input-small" type="text"
                                                                               name="time_limit_mins"<?php echo (!$progress || $progress->getUseSelfTimer() != "1") ? " disabled=\"disabled\"" : " value=\"" . (int)$progress->getSelfTimerLength() % 60 . "\""; ?> />
                                                                        <span class="add-on">
                                                                            <?php echo $SECTION_TEXT["text"]["time_mins"]; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_close"];?></button>
                                                        <button id="update_self_timer" type="button" class="btn btn-primary"><?php echo $SECTION_TEXT["buttons"]["btn_save"]?></button>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div><!-- /.modal -->

                                        <div id="control-bar">
                                            <?php
                                            if ($progress->getProgressValue() === "inprogress") {
                                                ?>
                                                <div class="row-fluid container">
                                                    <div class="span2" id="control-bar-clock">
                                                        <div id="clock-container" class="pull-left">
                                                            <?php
                                                            if (isset($progress_view)) {
                                                                if ($post->getUseTimeLimit()) {
                                                                    $name = "exam_clock_" . $progress->getID();
                                                                    $visibility = $progress_view->get_cookie("", $name);
                                                                    if ($visibility === 1) {
                                                                        $count_down_class = "clock-toggle show";
                                                                        $no_count_down_class = "clock-toggle hide";
                                                                        $count_down_sec_class = "clock-toggle hide";
                                                                    } else if ($visibility === 0) {
                                                                        $count_down_class = "clock-toggle hide";
                                                                        $no_count_down_class = "clock-toggle show";
                                                                        $count_down_sec_class = "clock-toggle hide";
                                                                    } else if ($visibility === 2) {
                                                                        $count_down_class = "clock-toggle hide";
                                                                        $no_count_down_class = "clock-toggle hide";
                                                                        $count_down_sec_class = "clock-toggle show";
                                                                    }
                                                                    ?>
                                                                    <i class="fa fa-clock-o clock-toggle"></i>
                                                                    <div id="count-down-clock" class="<?php echo $count_down_class; ?>">
                                                                        <p><?php echo $progress_view->renderClock(); ?></p>
                                                                    </div>
                                                                    <div id="count-down-sec-clock" class="<?php echo $count_down_sec_class; ?>">
                                                                        <p><?php echo $progress_view->renderClock(1); ?></p>
                                                                    </div>
                                                                    <div id="no-count-down-clock" class="<?php echo $no_count_down_class; ?>">
                                                                        <p>-- : --</p>
                                                                    </div>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="span6" id="control-bar-pagination">
                                                        <?php echo $progress_view->renderPageLinks($page, $secure_mode); ?>
                                                    </div>
                                                    <div class="span4 pull-right" id="control-bar-buttons">
                                                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#confirmation-modal"><?php echo $SECTION_TEXT["buttons"]["btn_submit"]; ?></button>
                                                        <button class="btn btn-success pull-right save-exam" disabled><?php echo $SECTION_TEXT["buttons"]["btn_saved"]; ?></button>
                                                        <button class="btn btn pull-right" id="menu-toggle-exam"><i class="icon-list"></i> Menu</button>
                                                        <?php echo $progress_view->renderExamFileSwitcher(); ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <?php
                                        if (isset($progress_view)) {
                                            $visibility     = $progress_view->get_cookie("label_calculator");
                                            $use_calculator = $post->getUseCalculator();
                                            $use_self_timer = $post->getUseSelfTimer();
                                        }
                                        ?>
                                        <div id="exam-menu" class="hide<?php echo ($visibility ? " expanded": "");?>" >
                                            <div id="exam-progress-menu">
                                                <?php
                                                if (isset($progress_view)) {
                                                    if ($use_self_timer) {
                                                        echo $progress_view->renderSelfTimerHeader();
                                                        echo $progress_view->renderSelfTimer();
                                                    }
                                                    if ($use_calculator) {
                                                        echo $progress_view->renderCalculatorHeader();
                                                        echo $progress_view->renderCalculator();
                                                    }
                                                    echo $progress_view->renderSideBarHeader();
                                                    echo $progress_view->renderSideBar($page);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <ul id="contextMenuExam" class="dropdown-menu pull-left" role="menu"></ul>
                                        <div id="link_modal_window" class="modal hide fade">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo $SECTION_TEXT["text"]["open_link_title"]; ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>
                                                            <?php echo $SECTION_TEXT["text"]["open_link"]; ?>
                                                        </p>
                                                        <p><strong class="open_link_msg"></strong></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div><!-- /.modal -->
                                        <?php
                                        if (isset($progress_view) && $use_calculator) {
                                            echo "<script src=\"" .  ENTRADA_URL . "/javascript/CalcSS3/CalcSS3.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                                        }
                                    } else {
                                        echo "<script> post_id = " . $post->getID() . ";</script>";
                                    }
                                } elseif ($show_instructions === true) { ?>
                                    <script>
                                        post_id = <?php echo $post->getID();?>;
                                        exam_id = <?php echo $post->getExamID();?>;
                                        exam_progress_id = <?php echo $progress->getID();?>;
                                        proxy_id = <?php echo $ENTRADA_USER->getID();?>;
                                    </script>
                                    <form class="form-horizontal" id="exam-attempt-instructions" method="post" action="<?php echo ENTRADA_URL; ?>/secure/exams?section=attempt&id=<?php echo $post->getPostID(); ?>">
                                        <div class="well" id="exam-settings">
                                            <h4><?php echo $SECTION_TEXT["text_exam_information"]; ?></h4>
                                            <?php
                                            $post_view = new Views_Exam_Post($post);
                                            echo $post_view->renderPublicPostSettings();
                                            ?>
                                            <input id="instructions_viewed" name="instructions_viewed" type="hidden" value="true">
                                            <input type="submit" id="instructions-start-exam" name="start-exam" value="Start Exam" class="btn btn-primary" />
                                        </div>
                                    </form>
                                    <?php
                                } else {
                                    if ($post->getSecureMode() == "basic" || $post->getSecureMode() == "rpnow") { ?>
                                        <div class="control-group pull-right">
                                            <label class="control-label" for="exam-password">Exam Password:</label>
                                            <div class="controls">
                                                <input class="input-large space-right" name="exam-password" id="exam-password" type="password" value=""/>
                                                <div class="pull-right">
                                                    <button id="start_exam" class="btn btn-primary">Resume Exam</button>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            jQuery(document).ready(function($) {
                                                $("#start_exam").on("click", function() {
                                                    var password = $("#exam-password").val();
                                                    var data = {"method" : "verify-exam-password", "post_id" : <?php echo $post->getID();?>, "password" : password};
                                                    $.ajax({
                                                        url: ENTRADA_RELATIVE +  "/exams?section=api-exams",
                                                        data: data,
                                                        type: "POST",
                                                        success: function(data) {
                                                            var jsonResponse = JSON.parse(data);
                                                            if (jsonResponse.status == "success") {
                                                                window.location.reload();
                                                            } else {
                                                                $("#display-error-box").empty();
                                                                display_error(jsonResponse.data, "#display-error-box", "append");
                                                            }
                                                        }
                                                    });
                                                });
                                            });
                                        </script>
                                    <?php }
                                    else { ?>
                                        <form class="form-horizontal" method="post" action="<?php echo ENTRADA_URL; ?>/secure/exams?section=attempt&id=<?php echo $post->getPostID(); ?>">
                                            <div class="row-fluid">
                                                <div class="control-group span8 offset2">
                                                    <div class="alert alert-info"><h4>A password is required to resume your exam attempt</h4><p>Please ask the exam proctor for the password.</p></div>
                                                    <label class="control-label" for="resume_password"> Password</label>
                                                    <div class="controls">
                                                        <div class="input-append">
                                                            <input class="span10" id="resume_password" name="resume_password" type="password" value="">
                                                            <button class="btn" id="btn-resume" type="submit">Resume Exam</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    <?php }
                                }
                            } else {
                                //access time incorrect
                                switch ($action) {
                                    case "start":
                                        if (!$post->isAfterUserStartTime($ENTRADA_USER)) {

                                            add_error($SECTION_TEXT["text"]["access_time_future"]);

                                            echo display_error();

                                            application_log("error", $SECTION_TEXT["text"]["access_time_future"]);
                                        } else if (!$post->isBeforeUserEndTime($ENTRADA_USER)) {

                                            add_error($translate->_($SECTION_TEXT["text"]["access_time_end_past"]));

                                            echo display_error();

                                            application_log("error", $SECTION_TEXT["text"]["access_time_end_past"]);
                                        } else if (!$post->isSubmitAttemptAllowedByUser($ENTRADA_USER)) {
                                            add_error($translate->_($SECTION_TEXT["text"]["access_time_sub_past"]));

                                            echo display_error();

                                            application_log("error", $SECTION_TEXT["text"]["access_time_sub_past"]);
                                        }

                                        break;
                                    case "resume":

                                        add_error($SECTION_TEXT["text"]["access_time_sub_past"]);

                                        echo display_error();

                                        application_log("error", $SECTION_TEXT["text"]["access_time_sub_past"]);
                                        break;
                                    default :
                                        add_error($SECTION_TEXT["text"]["access_time_unknown"]);

                                        echo display_error();

                                        application_log("error", $SECTION_TEXT["text"]["access_time_unknown"]);
                                }
                            }
                        } else {
                            add_error($SECTION_TEXT["text"]["no_exam_id_error"]);

                            echo display_error();

                            application_log("error", $SECTION_TEXT["text"]["no_exam_id_error"]);
                        }
                    } else {
                        add_error($SECTION_TEXT["text"]["no_access_audience"]);

                        echo display_error();

                        application_log("error", $SECTION_TEXT["text"]["no_access_audience"]);
                    }
                } else {
                    switch ($post->getSecureMode()) {
                        case "rp_now" :
                            add_error($SECTION_TEXT["text"]["secure_mode_required_rpnow"]);
                            application_log("error", $SECTION_TEXT["text"]["secure_mode_required"]);
                            break;
                        case "seb" :
                            default :
                            add_error($SECTION_TEXT["text"]["secure_mode_required_rpnow"]);
                            application_log("error", $SECTION_TEXT["text"]["secure_mode_required"]);
                            break;
                    }
                    echo display_error();
                }
            } else {
                add_error($SECTION_TEXT["text"]["id_invalid"]);

                echo display_error();

                application_log("error", $SECTION_TEXT["text"]["id_invalid"]);
            }
        } else {
            add_error($SECTION_TEXT["text"]["no_id_error"]);

            echo display_error();

            application_log("error", $SECTION_TEXT["text"]["no_post_id_error"]);
        }
    break;
}
