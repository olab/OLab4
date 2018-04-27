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
 * This file loads details for any exam activity, posts, progress, submissions, etc
 * Tools like regrade, reopen, analytics
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ?>
    <?php
    // JS translation strings.
    Entrada_Utilities::addJavascriptTranslation($translate->_("Yes"), 'yes');
    Entrada_Utilities::addJavascriptTranslation($translate->_("No"), 'no');

    Entrada_Utilities::addJavascriptTranslation($translate->_("Are you sure you want to remove this exception?"), 'clear_exception_message');
    Entrada_Utilities::addJavascriptTranslation($translate->_("There was an error while recovering the exam audience. Please contact the administrator."), 'recovering_audience_error_message');
    Entrada_Utilities::addJavascriptTranslation($translate->_("1The Exam Post has been successfully created."), 'exam_post_created_message');
    Entrada_Utilities::addJavascriptTranslation($translate->_("The exam post has been successfully saved."), 'exam_post_saved_message');
    Entrada_Utilities::addJavascriptTranslation($translate->_("Yes"), 'yes');

    $HEAD[] = "<script type=\"text/javascript\">var org_id = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var DEFAULT_DATETIME_FORMAT = \"". DEFAULT_DATETIME_FORMAT ."\";</script>";

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION).""."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.timepicker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ."\";</script>";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/exam-posts-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.moment.min.js\"></script>\n";

    load_rte("post", array('autogrow' => true));

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.timepicker.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/wizard.css\" />";

    ?>
    <style>
        #add-post {
            margin-right: 10px;
            margin-left: 10px;
            display: none;
        }
    </style>
    <?php

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
        $referrer = "exam";
    } else {
        $mode = "create";
    }
    if (isset($_GET["mode"]) && $tmp_input = clean_input($_GET["mode"], array("trim", "striptags"))) {
        $mode = strtolower($tmp_input);
    } else {
        $mode = "";
    }
    if (isset($_GET["target_type"]) && $tmp_input = clean_input($_GET["target_type"], array("notags", "trim"))) {
        $PROCESSED["target_type"] = $tmp_input;
    }
    if (isset($_GET["target_id"]) && $tmp_input = clean_input($_GET["target_id"], "int")) {
        $PROCESSED["target_id"] = $tmp_input;
        $referrer = "event";
    }
    if (isset($_GET["post_id"]) && $tmp_input = clean_input($_GET["post_id"], "int")) {
        $POST_ID = $tmp_input;
    }

    $redirect_section = 1;

    if (isset($_GET["redirect_section"]) && ($tmp_input = clean_input($_GET["redirect_section"], "int")) && isset($POST_ID)) {
        if ($tmp_input >= 1 && $tmp_input <= 6) {
            $redirect_section = $tmp_input;
        }
    }

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $POST_TEXT = $SUBMODULE_TEXT["posting"];
    $DEFAULT_LABELS = $translate->_("default");

    $POST_REFERRER = false;
    if (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["posts"]["post_editor_referrer"]["url"])) {
        if ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["posts"]["post_editor_referrer"]["from_index"] == false &&
            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["posts"]["post_editor_referrer"]["post_id"] > 0 &&
            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["posts"]["post_editor_referrer"]["post_id"] == $POST_ID) {
            $POST_REFERRER = html_encode($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["posts"]["post_editor_referrer"]["url"]);
        }
    }
    // Fetch the post data (and set the mode appropriately if unset or set improperly).
    // This data is also used to populate the first step of the form.
    $allow_access = false;

    /* Honor Code use and default text */
    $honorcode_text          = $ENTRADA_SETTINGS->fetchByShortname("honorcode_text", $ENTRADA_USER->getOrganisationId());
    $honorcode_use_exam      = $ENTRADA_SETTINGS->fetchByShortname("honorcode_use_exam", $ENTRADA_USER->getOrganisationId());

    if (!$POST_ID) {
        //The post has not been created yet
        $mode = "create";
        $exam = false;
        $event = false;
        $start_date = "";
        $start_time = "";
        $end_date = "";
        $end_time = "";

        if ($PROCESSED["id"] && $PROCESSED["target_id"]) {
            $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
            $event = Models_Event::fetchRowByID($PROCESSED["target_id"]);
            if (!$exam) {
                add_error($POST_TEXT["exam_not_found"]);
            } else {
                $allow_access   = $ENTRADA_ACL->amIAllowed(new EventContentResource($event->getID(), $event->getCourseID(), $event->getCourse()->getOrganisationID()), "update");
                $title          = $exam->getTitle();
                $exam_title     = $exam->getTitle();
                $event_id       = $event->getID();
                $course_id      = $event->getCourseID();
                $start_date     = ($event->getEventStart() && $event->getEventStart() !== "0" && $event->getEventStart() !=="") ? date("Y-m-d", $event->getEventStart()) : "";
                $start_time     = ($event->getEventStart() && $event->getEventStart() !== "0" && $event->getEventStart() !=="") ? date("H:i", $event->getEventStart()) : "";
                $end_date       = ($event->getEventFinish() && $event->getEventFinish() !== "0" && $event->getEventFinish() !=="") ? date("Y-m-d", $event->getEventFinish()) : "";
                $end_time       = ($event->getEventFinish() && $event->getEventFinish() !== "0" && $event->getEventFinish() !=="") ? date("H:i", $event->getEventFinish()) : "";
            }
        } else if ($PROCESSED["target_id"]) {
            $event = Models_Event::fetchRowByID($PROCESSED["target_id"]);
            if (!$event) {
                add_error($POST_TEXT["event_not_found"]);
            } else {
                $allow_access   = $ENTRADA_ACL->amIAllowed(new EventContentResource($event->getID(), $event->getCourseID(), $event->getCourse()->getOrganisationID()), "update");
                $title          = "Event: " . $event->getEventTitle();
                $exam_title     = "";
                $event_id       = $event->getID();
                $course_id      = $event->getCourseID();
                $start_date     = ($event->getEventStart() && $event->getEventStart() !== "0" && $event->getEventStart() !=="") ? date("Y-m-d", $event->getEventStart()) : "";
                $start_time     = ($event->getEventStart() && $event->getEventStart() !== "0" && $event->getEventStart() !=="") ? date("H:i", $event->getEventStart()) : "";
                $end_date       = ($event->getEventFinish() && $event->getEventFinish() !== "0" && $event->getEventFinish() !=="") ? date("Y-m-d", $event->getEventFinish()) : "";
                $end_time       = ($event->getEventFinish() && $event->getEventFinish() !== "0" && $event->getEventFinish() !=="") ? date("H:i", $event->getEventFinish()) : "";
            }
        } else if ($PROCESSED["id"]) {
            $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
            if (!$exam) {
                add_error($POST_TEXT["exam_not_found"]);
            } else {
                $allow_access   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
                $title          = $exam->getTitle();
                $exam_title     = $exam->getTitle();
                $event_id       = "";
            }
        }

        $update_questions_available = array();

        if ($exam) {
            $exam_elements = $exam->getExamElements();
            if ($exam_elements && is_array($exam_elements)) {
                foreach ($exam_elements as $element) {
                    if ($element && is_object($element)) {
                        if ($element->getElementType() === "question") {
                            $question_version = $element->getElementID();
                            $question = Models_Exam_Question_Versions::fetchRowByVersionID($question_version);
                            if ($question && is_object($question)) {
                                $question_id = $question->getQuestionID();
                                $question_current_count = $question->getVersionCount();

                                $other_versions = $question->fetchAllRelatedVersions();
                                if ($other_versions && is_array($other_versions) && !empty($other_versions)) {
                                    foreach ($other_versions as $version) {
                                        $version_count = $version->getVersionCount();

                                        if ($question_current_count < $version_count) {
                                            $temp_array = array(
                                                "question_id"   => $version->getQuestionID(),
                                                "version_id"    => $version->getVersionID(),
                                                "element_id"    => $element->getElementID(),
                                                "count"         => $version_count
                                            );

                                            if ($update_questions_available[$question_id]) {
                                                if ($update_questions_available[$question_id]["count"] < $version_count ) {
                                                    $update_questions_available[$question_id] = $temp_array;
                                                }
                                            } else {
                                                $update_questions_available[$question_id] = $temp_array;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        //The post exists
        $post = Models_Exam_Post::fetchRowByID($POST_ID);
        $secure_post = Models_Secure_RpNow::fetchRowByPostID($post->getID());
        if (!$post) { // Post was not found, so assume create mode
            add_error($POST_TEXT["post_not_found"]);
            $POST_ID = 0;
            $mode = "create";
        } else {
            // The only valid modes are copy and edit.
            if ($mode != "edit" && $mode != "copy") {
                $mode = "edit";
            }
            $exam = $post->getExam();
            $event = $post->getEvent();

            if (!$post->getSecure() && $redirect_section == 6) {
                $redirect_section = 1;
            }

            switch ($referrer) {
                case "exam":
                    $allow_access   = ($exam) ? $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update") : false;
                    $title          = ($exam) ? $exam->getTitle() : "";
                    $exam_title     = $post->getTitle();
                    $event_id       = ($event) ? $event->getID() : "";
                    $course_id      = ($event) ? $event->getCourseID(): "";
                    break;
                case "event":
                    $allow_access   = ($event) ? $ENTRADA_ACL->amIAllowed(new EventContentResource($event->getID(), $event->getCourseID(), $event->getCourse()->getOrganisationID()), "update") : false;
                    $title          = ($event) ? "Event: " . $event->getEventTitle() : "";
                    $exam_title     = $post->getTitle();
                    $event_id       = ($event) ? $event->getID() : "";
                    $course_id      = ($event) ? $event->getCourseID() : "";
                    break;
                default:
                    break;
            }

            $start_date = ($post->getStartDate() !== "" && $post->getStartDate() !== "0" && $post->getUseExamStartDate() !== "") ? date("Y-m-d", $post->getStartDate()) : date("Y-m-d", $event->getEventStart());
            $start_time = ($post->getStartDate() !== "" && $post->getStartDate() !== "0" && $post->getUseExamStartDate() !== "") ? date("H:i", $post->getStartDate()) : date("H:i", $event->getEventStart());
            $end_date = ($post->getEndDate() !== "" && $post->getEndDate() !== "0" && $post->getUseExamEndDate() != "") ? date("Y-m-d", $post->getEndDate()) : date("Y-m-d", $event->getEventFinish());
            $end_time = ($post->getEndDate() !== "" && $post->getEndDate() !== "0" && $post->getUseExamEndDate() != "") ? date("H:i", $post->getEndDate()) : date("H:i", $event->getEventFinish());
        }
    }

    if ($post && $time_frame = $post->getTimeFrame()) {
        if (in_array($time_frame, array("none", "pre", "during", "post"))) {
            $selected = $time_frame;
        } else {
            $time_frame = "none";
        }
    } else {
        $time_frame = "none";
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=post&id=" . $exam->getID(), "title" => $title);
    $BREADCRUMB[] = array("url" => "", "title" => ($mode === "edit") ? $SUBMODULE_TEXT["posts"]["edit_post"] : $SUBMODULE_TEXT["posts"]["add_post"]);
    if (!has_error()) {
        if ($exam) {
            $exam_view = new Views_Exam_Exam($exam);
            echo $exam_view->examNavigationTabs($SECTION);

            if ($update_questions_available && $mode !== "edit") {
                $url = ENTRADA_URL . "/admin/exams/exams/?section=edit-exam&id=" . $exam->getID();
                $message = $SUBMODULE_TEXT["index"]["title_updated_questions_available2"] . "<a href=\"" . $url . "\"><strong>" . $SUBMODULE_TEXT["index"]["title_updated_questions_available3"] . "</strong></a> " . $SUBMODULE_TEXT["index"]["title_updated_questions_available4"];
                add_notice($message);
                echo display_notice();
            }
        }
        if ($allow_access) {
            $step_status = ($mode === "edit") ? " complete" : "";
            ?>
            <h1><?php echo ($mode === "edit") ? $SUBMODULE_TEXT["posts"]["edit_post"] : $SUBMODULE_TEXT["posts"]["add_post"]; ?></h1>
            <h2><?php echo $title; ?></h2>

            <div class="wizard-body" id="post-editor-body">
                <div class="wizard-step-container">
                    <ul class="wizard-steps">
                        <li id="wizard-nav-item-1" class="wizard-nav-item<?php echo $step_status; ?>" data-step="1">
                            <a href="#">
                                <span class="step-number">1</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["1"]; ?></span>
                            </a>
                        </li>
                        <li id="wizard-nav-item-2" class="wizard-nav-item<?php echo $step_status; ?>" data-step="2">
                            <a href="#">
                                <span class="step-number">2</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["2"]; ?></span>
                            </a>
                        </li>
                        <li id="wizard-nav-item-3" class="wizard-nav-item<?php echo $step_status; ?>" data-step="3">
                            <a href="#">
                                <span class="step-number">3</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["3"]; ?></span>
                            </a>
                        </li>
                        <li id="wizard-nav-item-4" class="wizard-nav-item<?php echo $step_status; ?>" data-step="4">
                            <a href="#">
                                <span class="step-number">4</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["4"]; ?></span>
                            </a>
                        </li>
                        <li id="wizard-nav-item-5" class="wizard-nav-item<?php echo $step_status; ?>" data-step="5">
                            <a href="#">
                                <span class="step-number">5</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["5"]; ?></span>
                            </a>
                        </li>
                        <li id="wizard-nav-item-6" class="wizard-nav-item<?php echo ($post && $post->getSecure() === "1") ? $step_status: ""; ?>" <?php echo (!$post || !$post->getSecure()) ? "style=\"display: none;\"" : ""; ?> data-step="6">
                            <a href="#">
                                <span class="step-number">6</span>
                                <span class="step-label"><?php echo $POST_TEXT["steps"]["6"]; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div id="msgs"></div>

                <div id="wizard-loading" class="hide">
                    <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
                    <p id="wizard-loading-msg"></p>
                </div>

                <div id="post-wizard-form">
                    <form class="wizard-data-form form-horizontal" id="search-targets-form" autocomplete="off">
                        <input id="wizard-step-input" type="hidden" name="wizard_step" value="<?php echo $redirect_section ?>"/>
                        <input id="wizard-editor-mode" type="hidden" name="mode" value="<?php echo $mode ?>"/>
                        <div id="wizard-step-1" class="wizard-step hide">
                            <div class="distribution-instruction"></div>
                            <?php if (!$PROCESSED["id"]) { ?>
                                <div class="control-group">
                                    <label for="choose-exam-btn" class="control-label form-required">
                                        <?php echo $POST_TEXT["select_exam"] ?>
                                    </label>
                                    <div class="controls entrada-search-widget">
                                        <button id="choose-exam-btn" class="btn btn-search-filter" type="button">
                                            <?php echo ($exam) ? $exam->getTitle() : $POST_TEXT["browse_exam"]; ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (!$PROCESSED["target_id"]) { ?>
                                <div class="control-group" id="event-search">
                                    <label for="choose-event-btn" class="control-label form-required">
                                        <?php echo $translate->_("Select an Event"); ?>
                                    </label>
                                    <div class="controls" id="event-search-controls">
                                        <div id="event-label">
                                            <?php if ($post) { ?>
                                                <span class="selected-filter-label">
                                                    <?php echo $post->getCourse()->getCourseCode(); ?> - <?php echo $post->getCourse()->getCourseName(); ?>
                                                </span>
                                            <?php } ?>
                                        </div>
                                        <button id="choose-event-btn" class="btn btn-search-filter">
                                            <?php if ($post) { ?>
                                                <span class="selected-label space-right">
                                                    <?php echo date(DEFAULT_DATETIME_FORMAT, $post->getEvent()->getEventStart()); ?> - <?php echo $post->getEvent()->getEventTitle(); ?>
                                                </span>
                                            <?php } else { ?>
                                                <?php echo $translate->_("Browse Events"); ?>
                                            <?php } ?>
                                            <i class="icon-chevron-down btn-icon pull-right"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="control-group">
                                <label for="exam-title" class="control-label form-required">
                                    <?php echo $POST_TEXT["exam_title"]; ?>
                                </label>
                                <div class="controls">
                                    <input id="exam-title" type="text" name="exam_title" value="<?php echo $exam_title; ?>"/>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="exam-description" class="control-label">
                                    <?php echo $POST_TEXT["exam_description"]; ?>
                                </label>
                                <div class="controls">
                                    <textarea id="exam-description" name="exam_description">
                                        <?php echo ($post) ? $post->getDescription() : ""; ?>
                                    </textarea>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="exam-instructions" class="control-label">
                                    <?php echo $POST_TEXT["exam_instructions"]; ?>
                                </label>
                                <div class="controls">
                                    <textarea id="exam-instructions" name="exam_instructions">
                                        <?php echo ($post) ? $post->getInstructions() : ""; ?>
                                    </textarea>
                                </div>
                            </div>
                            <input type="hidden" name="exam" id="exam-id" value="<?php echo ($exam) ? $exam->getID() : ""; ?>"/>
                            <input type="hidden" name="target_type" value="<?php echo $PROCESSED["target_type"]; ?>"/>
                        </div>
                        <div id="wizard-step-2" class="hide wizard-step">
                            <div class="distribution-instruction"></div>
                            <div id="distribution-specific-date-options" class="distribution-options">
                                <div class="control-group">
                                    <label for="use_exam_start_date" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["exam_start_date"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_exam_start_date" class="use_date" type="checkbox"
                                                   name="use_exam_start_date" data-date-name="exam_start_date"
                                                   data-time-name="exam_start_time"
                                                   value="1"<?php echo (!$post || $post->getUseExamStartDate() != "") ? " checked=\"checked\" disabled=\"disabled\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="exam_start_date" type="text" class="input-small datepicker"
                                                   value="<?php echo $start_date; ?>"
                                                   name="exam_start_date"<?php echo ($post && $post->getUseExamStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?>
                                                   data-default-date="<?php echo $start_date; ?>"/>
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="exam_start_time" type="text" class="input-mini timepicker"
                                                   value="<?php echo $start_time; ?>"
                                                   name="exam_start_time"<?php echo ($post && $post->getUseExamStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?>
                                                   data-default-time="<?php echo $start_time; ?>"/>
                                            <span class="add-on pointer">
                                                <i class="icon-time"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label for="use_exam_end_date" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["exam_end_date"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_exam_end_date" class="use_date" type="checkbox"
                                                   name="use_exam_end_date" data-date-name="exam_end_date"
                                                   data-time-name="exam_end_time"
                                                   value="1"<?php echo ($post && $post->getUseExamEndDate() === "1") ? " checked=\"checked\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="exam_end_date" type="text" class="input-small datepicker"
                                                   value="<?php echo (!$post || $post->getUseExamEndDate() !== "1") ? "" : $end_date; ?>"
                                                   name="exam_end_date"<?php echo (!$post || $post->getUseExamEndDate() !== "1") ? " disabled=\"disabled\"" : ""; ?>
                                                   data-default-date="<?php echo $end_date; ?>"/>
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="exam_end_time" type="text" class="input-mini timepicker"
                                                   value="<?php echo (!$post || $post->getUseExamEndDate() !== "1") ? "" : $end_time; ?>"
                                                   name="exam_end_time"<?php echo (!$post || $post->getUseExamEndDate() !== "1") ? " disabled=\"disabled\"" : ""; ?>
                                                   data-default-time="<?php echo $end_time; ?>"/>z
                                            <span class="add-on pointer">
                                                <i class="icon-time"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="use_exam_submission_date" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["exam_submission_date"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_exam_submission_date" class="use_date" type="checkbox"
                                                   name="use_exam_submission_date" data-date-name="exam_submission_date"
                                                   data-time-name="exam_submission_time"
                                                   value="1"<?php echo ($post && $post->getUseSubmissionDate() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="exam_submission_date" type="text" class="input-small datepicker"
                                                   value="<?php echo ($post && $post->getSubmissionDate() != "" && $post->getSubmissionDate() !== "0" && $post->getUseSubmissionDate() == "1") ? date("Y-m-d", $post->getSubmissionDate()) : ""; ?>"
                                                   name="exam_submission_date"<?php echo (!$post || $post->getUseSubmissionDate() != "1") ? " disabled=\"disabled\"" : ""; ?>/>
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="exam_submission_time" type="text" class="input-mini timepicker"
                                                   value="<?php echo ($post && $post->getSubmissionDate() != "" && $post->getSubmissionDate() !== "0" && $post->getUseSubmissionDate() == "1") ? date("H:i", $post->getSubmissionDate()) : ""; ?>"
                                                   name="exam_submission_time"<?php echo (!$post || $post->getUseSubmissionDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                            <span class="add-on pointer">
                                                <i class="icon-time"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="timeframe" class="control-label form-required">
                                        <?php echo $POST_TEXT["timeframe"]; ?>
                                    </label>
                                    <div class="controls">
                                        <select name="timeframe" id="timeframe">
                                            <option value="none" <?php echo ($time_frame == "none") ? " selected=\"selected\"" : ""; ?>>None</option>
                                            <option value="pre" <?php echo ($time_frame == "pre") ? " selected=\"selected\"" : ""; ?>>Pre</option>
                                            <option value="during" <?php echo ($time_frame == "during") ? " selected=\"selected\"" : ""; ?>>During</option>
                                            <option value="post" <?php echo ($time_frame == "post") ? " selected=\"selected\"" : ""; ?>>Post</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="use_time_limit" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["use_time_limit"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_time_limit" type="checkbox"
                                                   name="use_time_limit"<?php echo ($post && $post->getUseTimeLimit() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="time_limit_hours" class="input-small" type="text"
                                                   name="time_limit_hours"<?php echo (!$post || $post->getUseTimeLimit() != "1") ? " disabled=\"disabled\"" : " value=\"" . (int)floor($post->getTimeLimit() / 60) . "\""; ?> />
                                            <span class="add-on">
                                                <?php echo $POST_TEXT["time_hours"]; ?>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="time_limit_mins" class="input-small" type="text"
                                                   name="time_limit_mins"<?php echo (!$post || $post->getUseTimeLimit() != "1") ? " disabled=\"disabled\"" : " value=\"" . (int)$post->getTimeLimit() % 60 . "\""; ?> />
                                            <span class="add-on">
                                                <?php echo $POST_TEXT["time_mins"]; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="auto_submit" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["auto_submit"]; ?>
                                    </label>
                                    <div class="controls">
                                        <label class="checkbox">
                                            <input id="auto_submit" type="checkbox" name="auto_submit"
                                                   value="1"<?php echo ($post && $post->getAutoSubmit() == "1") ? " checked=\"checked\"" : ""; ?> />
                                            <?php echo $POST_TEXT["auto_submit_text"]; ?>
                                        </label>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <label for="hide_exam" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["hide_exam"]; ?>
                                    </label>
                                    <div class="controls">
                                        <label class="checkbox">
                                            <input id="hide_exam" type="checkbox" name="hide_exam"
                                                   value="1"<?php echo ($post && $post->getHideExam() == "1") ? " checked=\"checked\"" : ""; ?> />
                                            <?php echo $POST_TEXT["hide_exam"]; ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="mandatory" class="control-label">
                                    <?php echo $POST_TEXT["mandatory"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="mandatory" type="checkbox" name="mandatory"
                                               value="1"<?php echo ($post && $post->getMandatory() == "1") ? " checked=\"checked\"" : ""; ?> />
                                        <?php echo $POST_TEXT["mandatory_text"]; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="max_attempts"
                                       class="control-label form-required"><?php echo $POST_TEXT["max_attempts"]; ?></label>
                                <div class="controls">
                                    <input id="max_attempts" class="input-mini" type="text" name="max_attempts"
                                           value="<?php echo ($post) ? $post->getMaxAttempts() : "1"; ?>"/>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="backtrack" class="control-label"><?php echo $POST_TEXT["backtrack"]; ?></label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="backtrack" type="checkbox" name="backtrack"
                                               value="1"<?php echo (!$post || $post->getBacktrack() == "1") ? " checked=\"checked\"" : ""; ?> />
                                        <?php echo $POST_TEXT["backtrack_text"]; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label">
                                    <?php echo $POST_TEXT["secure_mode"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="radio">
                                        <input type="radio" name="secure" id="secure_false" value="0"<?php echo (!$post || ($post && $post->getSecure() == "0")) ? " checked=\"checked\"" : ""; ?>>
                                        <?php echo $POST_TEXT["secure_not_required"]; ?>
                                    </label><br/>
                                    <label class="radio">
                                        <input type="radio" name="secure" id="secure_true" value="1"<?php echo ($post && $post->getSecure() == "1") ? " checked=\"checked\"" : ""; ?>>
                                        <?php echo $POST_TEXT["secure_required"]; ?>
                                    </label>
                                </div>
                            </div>
                            <?php
                            if ($honorcode_use_exam && $honorcode_text) {
                            ?>
                            <div class="control-group honor-code-group<?php echo ($post && $post->getSecure() == "1") ? "" : " hide"; ?>">
                                <label for="use_honor_code" class="control-label">
                                    <?php echo $translate->_("Use Honor Code"); ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="use_honor_code" type="checkbox" name="use_honor_code" value="1"<?php echo ($post && $post->getUseHonorCode() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        <?php echo $translate->_("If checked, this will display the honor code before starting a secure exam and require the learner check a box to acknowledge that they agree to the honor code."); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="control-group honor-code-group<?php echo ($post && $post->getSecure() == "1") ? "" : " hide"; ?>">
                                <label for="honor-code" class="control-label">
                                    <?php echo $translate->_("Honor Code"); ?>
                                </label>
                                <div class="controls">
                                    <textarea id="honor-code" name="honor_code">
                                        <?php echo ($post && $post->getHonorCode() != null) ? $post->getHonorCode() : $honorcode_text->getValue(); ?>
                                    </textarea>
                                </div>
                            </div>
                            <?php
                            }
                            ?>

                            <div class="control-group">
                                <label for="mark_faculty_review" class="control-label">
                                    <?php echo $POST_TEXT["fac_feedback_title"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="mark_faculty_review" type="checkbox" name="mark_faculty_review" value="1"<?php echo ($post && $post->getAllowFeedback() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        <?php echo $POST_TEXT["fac_feedback_text"]; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="allow_calculator" class="control-label">
                                    <?php echo $POST_TEXT["calculator_title"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="allow_calculator" type="checkbox" name="allow_calculator" value="1"<?php echo ($post && $post->getUseCalculator() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        <?php echo $POST_TEXT["allow_calculator_text"]; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="use_self_timer" class="control-label">
                                    <?php echo $POST_TEXT["use_self_timer_title"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox">
                                        <input id="use_self_timer" type="checkbox" name="use_self_timer" value="1"<?php echo ($post && $post->getUseSelfTimer() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        <?php echo $POST_TEXT["use_self_timer_text"]; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="wizard-step-3" class="wizard-step hide">

                            <div class="alert alert-info">
                                <?php echo $translate->_("Use this screen to set accommodations or exemptions for any learner eligible to take this exam. Click the pencil icon in the Edit column to begin."); ?>
                            </div>

                            <div class="distribution-instruction"></div>

                            <table class="table table-striped table-bordered grading-table" id="audience-list-table">
                                <thead>
                                <tr>
                                    <th><?php echo $POST_TEXT["learner_name"]; ?></th>
                                    <th><?php echo $POST_TEXT["excluded"]; ?></th>
                                    <th><?php echo $POST_TEXT["exception_start_date"]; ?></th>
                                    <th><?php echo $POST_TEXT["exception_end_date"]; ?></th>
                                    <th><?php echo $POST_TEXT["exception_submission_date"]; ?></th>
                                    <th><?php echo $POST_TEXT["exception_time_factor"]; ?></th>
                                    <th><?php echo $POST_TEXT["max_attempts"]; ?></th>
                                    <th><?php echo $DEFAULT_LABELS["btn_edit"] ?></th>
                                </tr>
                                </thead>
                                <tbody id="audience-list-body">
                                </tbody>
                            </table>

                        </div>
                        <div id="wizard-step-4" class="wizard-step hide">
                            <div class="distribution-instruction"></div>
                            <div class="control-group">
                                <label for="release_score" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["release_score"]; ?>
                                </label>
                                <div class="controls">
                                    <label class="checkbox" for="release_score">
                                        <input id="release_score" type="checkbox" name="release_score"
                                               value="1"<?php echo ($post && $post->getReleaseScore() == "1") ? " checked=\"checked\"" : ""; ?> />
                                        <?php echo $POST_TEXT["score_text"]; ?>
                                    </label>
                                </div>
                            </div>

                            <div id="release_score_group" class="<?php echo ($post && $post->getReleaseScore() == "1") ? "show" : "hide"; ?>">
                                <div class="control-group">
                                    <label for="release_feedback" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["release_feedback"]; ?>
                                    </label>
                                    <div class="controls">
                                        <label class="checkbox" for="release_feedback">
                                            <input id="release_feedback" type="checkbox" name="release_feedback"
                                                   value="1"<?php echo ($post && $post->getReleaseFeedback() == "1") ? " checked=\"checked\"" : ""; ?> />
                                            <?php echo $POST_TEXT["feedback_text"]; ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="control-group <?php echo ($post && $post->getReleaseFeedback() == "1") ? "show" : "hide"; ?>" id="release_feedback_group">
                                    <label for="release_incorrect_responses" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["feedback_level"]; ?>
                                    </label>
                                    <div class="controls">
                                        <label class="radio">
                                            <input type="radio" name="release_incorrect_responses" id="release_incorrect_responses_false" value="0"<?php echo ($post && $post->getReleaseIncorrectResponses() == "0") ? " checked=\"checked\"" : ""; ?>>
                                            <?php echo $POST_TEXT["feedback_level_all"]; ?>
                                        </label><br/>
                                        <label class="radio">
                                            <input type="radio" name="release_incorrect_responses" id="release_incorrect_responses_true" value="1"<?php echo ($post && $post->getReleaseIncorrectResponses() == "1") ? " checked=\"checked\"" : ""; ?>>
                                            <?php echo $POST_TEXT["feedback_level_incorrect"]; ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label for="use_release_start_date" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["release_start_date"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_release_start_date" class="use_date" type="checkbox"
                                                   name="use_release_start_date" data-date-name="release_start_date"
                                                   data-time-name="release_start_time"
                                                   value="1"<?php echo ($post && $post->getUseReleaseStartDate() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="release_start_date" type="text" class="input-small datepicker"
                                                   value="<?php echo ($post && $post->getReleaseStartDate() != 0 && $post->getUseReleaseStartDate() != 0) ? date("Y-m-d", $post->getReleaseStartDate()) : ""; ?>"
                                                   name="release_start_date"<?php echo (!$post || $post->getUseReleaseStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="release_start_time" type="text" class="input-mini timepicker"
                                                   value="<?php echo ($post && $post->getReleaseStartDate() != 0 && $post->getUseReleaseStartDate() != 0) ? date("H:i", $post->getReleaseStartDate()) : ""; ?>"
                                                   name="release_start_time"<?php echo (!$post || $post->getUseReleaseStartDate() != "1") ? " disabled=\"disabled\"" : ""; ?>/>
                                            <span class="add-on pointer">
                                                <i class="icon-time"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label for="use_release_end_date" class="control-label form-nrequired">
                                        <?php echo $POST_TEXT["release_end_date"]; ?>
                                    </label>
                                    <div class="controls">
                                        <div class="input-append space-right">
                                            <input id="use_release_end_date" class="use_date" type="checkbox"
                                                   name="use_release_end_date" data-date-name="release_end_date"
                                                   data-time-name="release_end_time"
                                                   value="1" <?php echo ($post && $post->getUseReleaseEndDate() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                        </div>
                                        <div class="input-append space-right">
                                            <input id="release_end_date" type="text" class="input-small datepicker"
                                                   value="<?php echo ($post && $post->getReleaseEndDate() != 0 && $post->getUseReleaseEndDate() != 0) ? date("Y-m-d", $post->getReleaseEndDate()) : ""; ?>"
                                                   name="release_end_date"<?php echo (!$post || $post->getUseReleaseEndDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                            <span class="add-on pointer">
                                                <i class="icon-calendar"></i>
                                            </span>
                                        </div>
                                        <div class="input-append">
                                            <input id="release_end_time" type="text" class="input-mini timepicker"
                                                   value="<?php echo ($post && $post->getReleaseEndDate() != 0 && $post->getUseReleaseEndDate() != 0) ? date("H:i", $post->getReleaseEndDate()) : ""; ?>"
                                                   name="release_end_time"<?php echo (!$post || $post->getUseReleaseEndDate() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                            <span class="add-on pointer">
                                                <i class="icon-time"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="use_re_attempt_threshold" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["use_re_attempt_threshold"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_re_attempt_threshold" type="checkbox"
                                               name="use_re_attempt_threshold" <?php echo ($post && $post->getUseRAThreshold() == "1") ? " checked=\"checked\"" : ""; ?>/>
                                    </div>
                                    <div class="input-append space-right">
                                        <input id="re_attempt_threshold" class="input-small" type="text"
                                               value="<?php echo ($post) ? $post->getRAThreshold() : ""; ?>"
                                               name="re_attempt_threshold"<?php echo (!$post || $post->getUseRAThreshold() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                        <span class="add-on">
                                            <?php echo $POST_TEXT["label_re_attempt_threshold"]; ?>
                                        </span>
                                    </div>
                                    <div class="input-append">
                                        <input id="re_attempt_threshold_attempts" class="input-small" type="text"
                                               value="<?php echo ($post) ? $post->getRAThresholdAttempts() : ""; ?>"
                                               name="re_attempt_threshold_attempts"<?php echo (!$post || $post->getUseRAThreshold() != "1") ? " disabled=\"disabled\"" : ""; ?> />
                                        <span class="add-on">
                                            <?php echo $POST_TEXT["re_attempt_threshold_attempts"]; ?>
                                        </span>
                                    </div>
                                    <small class="exam-notes help-block">
                                        <?php echo $POST_TEXT["threshold_note"]; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="add_to_grade_book_btn" class="control-label form-required">
                                    <?php echo $translate->_("Attach GradeBook to Post"); ?>
                                </label>
                                <div class="controls entrada-search-widget">
                                    <button id="add_to_grade_book_btn" class="btn btn-search-filter" type="button">
                                        <?php echo ($post && $post->getGradeBookAssessment()) ? $post->getGradeBookAssessment()->getName() : $translate->_("GradeBook"); ?>
                                        <i class="icon-chevron-down btn-icon pull-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="wizard-step-5" class="wizard-step hide">
                            <div class="distribution-instruction"></div>
                            <h4><?php echo $POST_TEXT["exam_post_review"]; ?></h4>
                            <div id="review-exam-details"></div>
                        </div>
                        <div id="wizard-step-6" class="wizard-step hide">
                            <div class="distribution-instruction"></div>
                            <?php if ($post) { ?>
                                <div class="item-container" id="security-settings" data-post="<?php echo ($post) ? $post->getID() : ""; ?>">
                                    <div class="item-header" id="basic-header">
                                        <input type="radio" name="secure_mode" id="secure_mode_basic_password" value="basic"<?php echo (($post && $post->getSecureMode() == "basic") ? " checked=\"checked\"" : ""); ?> />
                                        <label for="secure_mode_basic_password" class="bold">
                                            <?php echo $translate->_("Basic Password"); ?>
                                        </label>
                                    </div>
                                    <div class="item-body hide" id="basic">
                                        <div class="control-group space-above">
                                            <label for="resume_password_basic" class="control-label form-required">
                                                <?php echo $translate->_("Exam Begin Password"); ?>
                                            </label>
                                            <div class="controls">
                                                <div class="input-append space-right" id="password_basic">
                                                    <input id="resume_password_basic" type="text" class="resume_password" name="resume_password_basic" size="25"
                                                           maxlength="20" placeholder="Please enter or generate a password"
                                                           value="<?php echo ($post) ? $post->getResumePassword() : ""; ?>"/>
                                                    <button class="btn generate-resume-password-btn" id="basic" data-name="basic" type="button">
                                                        <?php echo $POST_TEXT["resume_password"]; ?>
                                                    </button>
                                                </div>
                                                <small class="help-block">
                                                    <?php echo $POST_TEXT["use_resume_password_text"]; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-header" id="seb-header">
                                        <input type="radio" name="secure_mode" id="secure_mode_seb_password" value="seb" <?php echo ($post && $post->getSecureMode() == "seb") ? " checked=\"checked\"" : ""; ?>/>
                                        <label for="secure_mode_seb_password" class="bold">
                                            <?php echo $POST_TEXT["secure_exam_config"]; ?>
                                        </label>
                                        <span id="secure-file-header-messages"></span>
                                        <div class="pull-right"></div>
                                    </div>
                                    <div class="item-body hide" id="seb">
                                        <div class="item-section secure-password">
                                            <div class="control-group space-above" id="secure-password-header">
                                                <label class="control-label" for="use_resume_password"><?php echo $POST_TEXT["use_resume_password"]; ?></label>
                                                <div class="controls">
                                                    <div class="input-append space-right">
                                                        <input id="use_resume_password" type="checkbox" name="use_resume_password"<?php echo ($post && $post->getUseResumePassword() == "1") ? " checked=\"checked\"" : ""; ?> />
                                                        <?php echo $POST_TEXT["use_resume_password_text"]; ?>
                                                    </div>
                                                    <div class="input-append space-right">
                                                        <input id="resume_password_seb" type="text" class="resume_password" name="resume_password_seb" size="25"
                                                               maxlength="20" placeholder="Please enter or generate a password"
                                                               value="<?php echo ($post) ? $post->getResumePassword() : ""; ?>"/>
                                                        <button class="btn generate-resume-password-btn" id="seb" type="button">
                                                            <?php echo $POST_TEXT["resume_password"]; ?>
                                                        </button>
                                                    </div>
                                                    <small class="help-block">
                                                        <?php echo $POST_TEXT["use_resume_password_text"]; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="item-section secure-file"
                                             data-post-id="<?php echo ($post) ? $post->getID() : ""; ?>">
                                            <div class="item-section-header">
                                                <?php echo $POST_TEXT["seb_file"]; ?>
                                                <div class="pull-right">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn delete-item disabled"
                                                                id="delete-secure-file" data-post=""
                                                                title="Delete SEB file(s)"><i class="icon-minus-sign"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item-section-body show-less secure-file-list upload" id="">
                                                <div class="secure-file-list-content"></div>
                                            </div>
                                        </div>
                                        <div class="item-section secure-keys">
                                            <div class="item-section-header" id="secure-key-header">
                                                <?php echo $POST_TEXT["secure_keys"]; ?>
                                                <span class="badge badge-success secure-key-badge"></span>
                                                <div class="pull-right">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn delete-item disabled"
                                                                id="delete-secure-keys" data-post=""
                                                                title="Delete Browser Exam Key(s)"><i
                                                                    class="icon-minus-sign"></i></button>
                                                        <button type="button" class="btn add-item" id="add-secure-key"
                                                                data-post="" title="Add Secure Key(s)"><i
                                                                    class="icon-plus-sign"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item-section-body show-less secure-key-list"></div>
                                        </div>
                                    </div>
                                    <div class="item-header" id="rpnow-header">
                                        <input type="radio" name="secure_mode" id="secure_mode_rpnow_password" value="rp_now" <?php echo ($post && $post->getSecureMode() == "rp_now") ? " checked=\"checked\"" : ""; ?>/>
                                        <label for="secure_mode_rpnow_password" class="bold">
                                            <?php echo $translate->_("RP-Now by Software Secure"); ?>
                                        </label>

                                    </div>
                                    <div class="item-body hide" id="rp_now">
                                        <div class="control-group space-above">
                                            <label for="resume_password_rp_now" class="control-label">
                                                <?php echo $translate->_("Exam Password"); ?>
                                            </label>
                                            <div class="controls">
                                                <div class="input-append space-right" id="password_rpnow">
                                                    <input id="resume_password_rp_now" class="resume_password" type="text" name="resume_password_rp_now" size="25"
                                                           maxlength="20" placeholder="Please enter or generate a password"
                                                           value="<?php echo ($post) ? $post->getResumePassword() : ""; ?>"/>
                                                    <button class="btn generate-resume-password-btn" id="rpnow" data-name="rpnow" type="button">
                                                        <?php echo $POST_TEXT["resume_password"]; ?>
                                                    </button>
                                                </div>
                                                <small class="help-block">
                                                    <?php echo $POST_TEXT["use_resume_password_text"]; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label for="exam_url" class="control-label form-required">
                                                <?php echo $POST_TEXT["exam_url"]; ?>
                                            </label>
                                            <div class="controls">
                                                <input id="exam_url" type="text" name="exam_url" value="<?php echo ($secure_post ?  $secure_post->getExamUrl() : ENTRADA_URL . "/exams?section=post&id=" . $post->getID()); ?>"/>
                                            </div>
                                        </div>
                                        <div class="control-group" id="exam-sponsor-search-controls">
                                            <label for="exam_sponsor" class="control-label form-required space-right">
                                                <?php echo $POST_TEXT["exam_sponsor"]; ?>
                                            </label>
                                            <button id="choose-exam-sponsor-btn" class="btn btn-search-filter">
                                                    <span class="selected-label">
                                                        <?php echo ($secure_post && Models_User::fetchRowByID($secure_post->getExamSponsor()) ? Models_User::fetchRowByID($secure_post->getExamSponsor())->getFullname(false) : $translate->_("Browse Director")); ?>
                                                    </span>
                                                <i class="icon-chevron-down btn-icon pull-right"></i>
                                            </button>
                                        </div>
                                        <div class="control-group">
                                            <label for="rpnow_reviewed_exam" class="control-label">
                                                <?php echo $POST_TEXT["rpnow_reviewed_exam"]; ?>
                                            </label>
                                            <div class="controls">
                                                <label class="checkbox">
                                                    <input id="rpnow_reviewed_exam" type="checkbox" name="rpnow_reviewed_exam" value="1"<?php echo (($secure_post && $secure_post->getRpnowReviewedExam() == "1") || !$secure_post) ? " checked=\"checked\"" : ""; ?>/>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="control-group hide" id="reviewer_notes">
                                            <label for="rpnow_reviewer_notes" class="control-label form-required">
                                                <?php echo $POST_TEXT["rpnow_reviewer_notes"]; ?>
                                            </label>
                                            <div class="controls">
                                                <textarea id="rpnow_reviewer_notes" name="rpnow_reviewer_notes">
                                                    <?php echo ($secure_post) ? $secure_post->getRpnowReviewerNotes() : ""; ?>
                                                </textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="wizard-footer">
                            <div class="row-fluid">
                                <button class="wizard-previous-step btn btn-default <?php echo ($redirect_section != 1 ? "" : "hide") ?>">
                                    <?php echo $translate->_("Previous"); ?>
                                </button>
                                <button class="wizard-next-step btn btn-primary">
                                    <?php echo $translate->_("Next"); ?>
                                </button>
                            </div>
                        </div>
                        <!--- @todo hidden_inputs -->

                        <div id="selected_list_container">
                            <input type="hidden" name="referrer" value="<?php echo $referrer; ?>" id="referrer"/>
                            <input type="hidden" name="post_id" value="<?php echo ($post) ? $post->getID() : ""; ?>" id="post_id"/>
                            <input type="hidden" name="exam_exceptions" id="exam_exceptions"/>
                            <?php if ($event_id && $event_id !== "") {
                                if (!$post) {
                                    $event = Models_Event::fetchRowByID($event_id);
                                    if ($event) {
                                        $course_id = $event->getCourseID();
                                    }

                                } else {
                                    $course = $post->getCourse();
                                    if ($course) {
                                        $course_id = $course->getID();
                                    }
                                }
                                ?>
                                <input type="hidden" name="target_id" value="<?php echo $event_id; ?>"
                                       id="course_<?php echo ($post && $post->getCourse()) ? $post->getCourse()->getID() : $course_id . "_" . $event_id; ?>"
                                       data-label="<?php echo ($post) ? $post->getTitle() : ""; ?>"
                                       data-id="<?php echo $event_id; ?>"
                                       data-filter="course"
                                       class="event-id search-target-control course_search_target_control"/>
                            <?php } ?>
                            <?php if ($post) { ?>
                                <?php if ($post->getGradeBookAssessment()) { ?>
                                    <input type="hidden" name="grade_book"
                                           value="<?php echo $post->getGradeBookAssessment()->getID(); ?>"
                                           id="grade_book_<?php echo $post->getGradeBookAssessment()->getID(); ?>"
                                           data-label="<?php echo $post->getGradeBookAssessment()->getName(); ?>"
                                           class="search-target-control grade_book_search_target_control grade-book-selector">
                                <?php } ?>
                            <?php } ?>
                            <?php if ($secure_post) { ?>
                                <?php if ($exam_sponsor = Models_User::fetchRowByID($secure_post->getExamSponsor())) { ?>
                                    <input type="hidden" name="exam_sponsor"
                                           value="<?php echo $exam_sponsor->getID(); ?>"
                                           id="exam_sponsor_<?php echo $exam_sponsor->getID(); ?>"
                                           data-label="<?php echo $exam_sponsor->getFullname(false); ?>"
                                           class="search-target-control exam_sponsor_search_target_control">
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <!-- <div class="dropdown-menu toggle-left hide" id="edit-user-exception"> -->
                        <div class="modal hide fade" id="edit-user-exception">
                            <div class="control-group">
                                <h3 class="title" id="exception-student-name">Student Name</h3>
                            </div>
                            <div class="control-group">
                                <label for="excluded" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["excluded"]; ?>
                                </label>
                                <div class="controls">
                                    <input id="excluded" type="checkbox"/>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="use_exception_start_date" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["exam_start_date"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_exception_start_date" class="use_date" type="checkbox"
                                               name="use_exception_start_date" data-date-name="exception_start_date"
                                               data-time-name="exception_start_time" value="1"/>
                                    </div>
                                    <div class="input-append space-right">
                                        <input id="exception_start_date" type="text" class="input-small datepicker"
                                               value="<?php echo date("Y-m-d", $event_info["event_start"]) ?>"
                                               name="exception_start_date"/>
                                    <span class="add-on pointer">
                                        <i class="icon-calendar"></i>
                                    </span>
                                    </div>
                                    <div class="input-append">
                                        <input id="exception_start_time" type="text" class="input-mini timepicker"
                                               value="<?php echo date("H:i", $event_info["event_start"]) ?>"
                                               name="exception_start_time"/>
                                        <span class="add-on pointer">
                                            <i class="icon-time"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="use_exception_end_date" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["exam_end_date"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_exception_end_date" class="use_date" type="checkbox"
                                               name="use_exception_end_date" data-date-name="exception_end_date"
                                               data-time-name="exception_end_time" value="1"/>
                                    </div>
                                    <div class="input-append space-right">
                                        <input id="exception_end_date" type="text" class="input-small datepicker"
                                               value="<?php echo date("Y-m-d", $event_info["event_finish"]) ?>"
                                               name="exception_end_date"/>
                                        <span class="add-on pointer">
                                            <i class="icon-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="input-append">
                                        <input id="exception_end_time" type="text" class="input-mini timepicker"
                                               value="<?php echo date("H:i", $event_info["event_finish"]) ?>"
                                               name="exception_end_time"/>
                                        <span class="add-on pointer">
                                            <i class="icon-time"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="use_exception_submission_date" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["exam_submission_date"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_exception_submission_date" class="use_date" type="checkbox"
                                               name="use_exception_submission_date"
                                               data-date-name="exception_submission_date"
                                               data-time-name="exception_submission_time" value="1"/>
                                    </div>
                                    <div class="input-append space-right">
                                        <input id="exception_submission_date" type="text" class="input-small datepicker"
                                               value="<?php echo date("Y-m-d", $event_info["event_finish"]) ?>"
                                               name="exception_submission_date"/>
                                        <span class="add-on pointer">
                                            <i class="icon-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="input-append">
                                        <input id="exception_submission_time" type="text" class="input-mini timepicker"
                                               value="<?php echo date("H:i", $event_info["event_finish"]) ?>"
                                               name="exception_submission_time"/>
                                        <span class="add-on pointer">
                                            <i class="icon-time"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="use_exception_time_factor" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["exception_time_factor"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_exception_time_factor" type="checkbox" value="1"/>
                                    </div>
                                    <div class="input-append">
                                        <input id="exception_time_factor" type="text" class="input-mini input-selector" name="exception_time_factor"/>
                                        <span class="add-on">
                                            <?php echo $POST_TEXT["exc_time_factor_more"]; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="control-group">
                                <label for="use_exception_max_attempts" class="control-label form-nrequired">
                                    <?php echo $POST_TEXT["max_attempts"]; ?>
                                </label>
                                <div class="controls">
                                    <div class="input-append space-right">
                                        <input id="use_exception_max_attempts" name="use_exception_max_attempts" type="checkbox" value="1"/>
                                    </div>
                                    <input id="exception_max_attempts" type="text"/>
                                </div>
                            </div>
                            <div class="row-fluid">
                                <button id="cancel-dropdown-exception" class="btn btn-default">
                                    <?php echo $DEFAULT_LABELS["btn_cancel"]; ?>
                                </button>

                                <div class="pull-right">
                                    <button id="clear-dropdown-exception" class="btn btn-warning" disabled>
                                        <?php echo $translate->_("Clear"); ?>
                                    </button>

                                    <button id="update-dropdown-exception" class="btn btn-primary">
                                        <?php echo $DEFAULT_LABELS["btn_update"]; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <script type="text/javascript">
                var security_options = <?php echo $post && $post->getSecure() ? 'true' : 'false'; ?>;

                jQuery(function ($) {
                    /**
                     * Instantiate datepickers
                     */
                    $("#exam_start_date").datepicker("setDate", "<?php echo $start_date; ?>");
                    $("#exam_end_date").datepicker("setDate", "<?php echo (!$post || ($post && $post->getUseExamEndDate() !== "1")) ? "" : $end_date; ?>");
                    $("#exam_submission_date").datepicker("setDate", "<?php echo ($post && $post->getSubmissionDate() != "" && $post->getSubmissionDate() !== "0") ? date("Y-m-d", $post->getSubmissionDate()) : ""; ?>");

                    $("#choose-event-btn").advancedSearch({
                        api_url: API_URL,
                        resource_url: ENTRADA_URL,
                        build_selected_filters: false,
                        filters: {
                            courses: {
                                mode: "radio",
                                label: "Courses",
                                data_source: "get-user-events",
                                secondary_data_source: "get-user-events",
                                selector_control_name: "target_id"
                            }
                        },
                        control_class: "choose-event-selector event-id",
                        no_results_text: "<?php echo $translate->_("No events found"); ?>",
                        parent_form: $("#search-targets-form"),
                        width: 500
                    });

                    $("body").on("click", "#choose-event-btn", function (e) {
                        $("#event-search .entrada-search-widget .filter-list").css("max-height", "300px").css("overflow", "auto");
                        e.preventDefault();
                    });

                    $("body").on("change", "#choose-event-btn", function () {
                        ($("input[name=\"target_id\"]").length > 1 ? $("input[name=\"target_id\"]")[0].remove() : false);
                        var event_id = $("input[name=\"target_id\"]");
                        $.getJSON(ENTRADA_URL + "/admin/events?section=api-events", {
                            method: "get-event-info",
                            event_id: event_id.val()
                        }).done(function (json) {
                            event = json.data;
                            var event_start = new Date(event.event_start * 1000);
                            var event_start_date_str = event_start.getFullYear() + "-" + ("0" + (event_start.getMonth() + 1)).slice(-2) + "-" + ("0" + event_start.getDate()).slice(-2);
                            var event_start_time_str = ("0" + event_start.getHours()).slice(-2) + ":" + ("0" + event_start.getMinutes()).slice(-2);
                            $("input#exam_start_time").data("default-time", event_start_time_str).val(event_start_time_str);
                            $("input#use_exam_start_date").prop({"checked": true, "disabled": true});
                            $("#exam_start_date").data("default-date", event_start_date_str).datepicker("setDate", event_start_date_str);

                            var event_end = new Date(event.event_finish * 1000);
                            var event_end_date_str = event_end.getFullYear() + "-" + ("0" + (event_end.getMonth() + 1)).slice(-2) + "-" + ("0" + event_end.getDate()).slice(-2);
                            var event_end_time_str = ("0" + event_end.getHours()).slice(-2) + ":" + ("0" + event_end.getMinutes()).slice(-2);
                            $("input#exam_end_time").data("default-time", event_end_time_str);
                            $("#exam_end_date").data("default-date", event_end_date_str);
                            var course_str = event.course.course_code + " - " + event.course.course_name;
                            var event_str = $(event_id).attr('data-label');
                            var selected_event_str = " <span class=\"selected-label space-right\">" + event_str + "</span>  <i class=\"icon-chevron-down btn-icon pull-right\"></i>";
                            $("button#choose-event-btn").html(selected_event_str);
                            $("#event-label").html("<span class=\"selected-filter-label\">" + course_str + "</span>");
                        });
                    });

                    if ($("#choose-exam-btn").length) {
                        $("#choose-exam-btn").advancedSearch({
                            api_url: API_URL,
                            resource_url: ENTRADA_URL,
                            filters: {
                                exam: {
                                    label: "<?php echo $translate->_("Exam"); ?>",
                                    data_source: "get-user-exams",
                                    mode: "radio",
                                    selector_control_name: "exam"
                                }
                            },
                            control_class: "exam-selector",
                            no_results_text: "<?php echo $translate->_("No exams found matching the search criteria"); ?>",
                            selected_list_container: $("#selected_list_container"),
                            parent_form: $("#post-wizard-form"),
                            width: 500,
                            modal: false
                        });
                    }

                    $("#exceptions-btn").advancedSearch({
                        api_url: API_URL,
                        resource_url: ENTRADA_URL,
                        filters: {
                            exception_audience: {
                                label: "<?php echo $POST_TEXT["exam_exceptions"];?>",
                                data_source: "get-exception-audience",
                                mode: "checkbox",
                                selector_control_name: "exception_audience",
                                api_params: {
                                    event_id: function () {
                                        var event_id = $("input[name=\"target_id\"]");
                                        return event_id.val();
                                    }
                                }
                            }
                        },
                        build_selected_filters: false,
                        control_class: "exception-selector",
                        no_results_text: "<?php echo $POST_TEXT["no_users_exam"];?>",
                        selected_list_container: $("#selected_list_container"),
                        parent_form: $("#post-wizard-form"),
                        width: 500,
                        modal: false
                    });

                    $("#add_to_grade_book_btn").advancedSearch({
                        api_url: API_URL,
                        resource_url: ENTRADA_URL,
                        filters: {
                            grade_book: {
                                label: "<?php echo $translate->_("GradeBook"); ?>",
                                data_source: "get-exam-grade-books-by-event",
                                mode: "radio",
                                selector_control_name: "grade_book",
                                api_params: {
                                    event_id: function () {
                                        var event_id = $("input[name=\"target_id\"]");
                                        return event_id.val();
                                    }
                                }
                            }
                        },
                        control_class: "grade-book-selector",
                        no_results_text: "<?php echo $translate->_("No Grade Books found to attach."); ?>",
                        selected_list_container: $("#selected_list_container"),
                        results_parent: $("#post-wizard-form"),
                        parent_form: $("#search-targets-form"),
                        width: 300,
                        modal: false
                    });

                    $("#exception_time_factor").inputSelector({
                        rows: 1,
                        columns: 4,
                        data_text: [25, 50, 75, 100],
                        modal: 1,
                        header: "<?php echo $POST_TEXT["exc_time_factor_perc"];?>",
                        form_name: "#search-targets-form",
                        label: "",
                        type: ""
                    });

                    $("#choose-exam-sponsor-btn").advancedSearch({
                        api_url: API_URL,
                        resource_url: ENTRADA_URL,
                        build_selected_filters: false,
                        filters: {
                            exam_sponsor: {
                                label: "<?php echo $translate->_("Sponsors"); ?>",
                                mode: "radio",
                                data_source: "get-users-course",
                                selector_control_name: "exam_sponsor",
                                api_params: {
                                    event_id: function () {
                                        var event_id = $("input[name=\"target_id\"]");
                                        return event_id.val();
                                    }
                                }
                            }
                        },
                        no_results_text: "<?php echo $translate->_("No sponsors found"); ?>",
                        selected_list_container: $("#selected_list_container"),
                        results_parent: $("#post-wizard-form"),
                        parent_form: $("#search-targets-form"),
                        width: 500,
                        modal: false
                    });
                    $("body").on("change", "#choose-exam-sponsor-btn", function () {
                        ($("input[name=\"exam_sponsor\"]").length > 1 ? $("input[name=\"exam_sponsor\"]")[0].remove() : false);
                    });

                });
            </script>

            <?php
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this exam [" . $PROCESSED["id"] . "]");
        }
    } else {
        echo display_error($SUBMODULE_TEXT["posts"]["post_not_found"]);
    }
}
