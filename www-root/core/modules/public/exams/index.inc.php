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
 * This is the default section that is loaded when the exam module is
 * accessed without a defined section.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $MODULE_TEXT    = $translate->_($MODULE);
    $SUBMODULE_TEXT = $MODULE_TEXT["exams"]["my_exams"];
    $PREFERENCES    = preferences_load($MODULE);
    $card_selected  = preference_module_set("card_selected", "", "un_complete");
    preferences_update($MODULE);

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => $SUBMODULE_TEXT["title"]);

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_RELATIVE . "/css/exams/exams-public-index.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_RELATIVE . "/css/font-awesome/css/font-awesome.min.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"" . ENTRADA_RELATIVE . "/" . $MODULE . "?section=api-exams\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_RELATIVE = \"" . ENTRADA_RELATIVE . "\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_RELATIVE . "/javascript/" . $MODULE . "/" . $MODULE . "-public-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    ?>
    <h1><?php echo $SUBMODULE_TEXT["title"];?></h1>
    <?php
    $posts = Models_Exam_Post::fetchAllEventExamsByProxyID($ENTRADA_USER->getID(), true, true);
    $all_exams          = array();
    $submitted_exams    = array();
    $un_submitted_exams = array();
    if (isset($posts) && is_array($posts) && !empty($posts)) {
        foreach ($posts as $post) {
            if (isset($post) && is_object($post)) {
                $start_valid        = $post->isAfterUserStartTime($ENTRADA_USER);
                $end_valid          = $post->isBeforeUserEndTime($ENTRADA_USER);
                $submission_valid   = $post->isSubmitAttemptAllowedByUser($ENTRADA_USER);
                $exam_id            = (int)$post->getExamID();
                $post_id            = (int)$post->getID();
                $proxy_id           = (int)$ENTRADA_USER->getID();
                $title              = $post->getTitle();
                $progress           = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post_id, $proxy_id, "submitted");

                if ($progress && is_array($progress) && !empty($progress)) {
                    $submitted_exams[] = $post;
                } else {
                    $progress = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post_id, $proxy_id, "inprogress");
                    if ($progress && is_array($progress) && !empty($progress)) {
                        if ($submission_valid) {
                            $exam_exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyIdExcluded($post->getID(), $ENTRADA_USER->getID());
                            if (!$exam_exception) {
                                $un_submitted_exams[] = $post;
                            }
                        } else {
                            // this is where a missed exam would show up if we want to count them
                        }
                    } else {
                        if ($start_valid && $end_valid && $submission_valid) {
                            $exam_exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyIdExcluded($post->getID(), $ENTRADA_USER->getID());
                            if (!$exam_exception) {
                                $un_submitted_exams[] = $post;
                            }
                        } else {
                            // this is where a missed exam would show up if we want to count them
                        }
                    }
                }
            }
        }
    }
    ?>
    <div class="clearfix space-above space-below" id="my_exams_switcher" >
        <div class="span2"></div>
        <div id="exam-un-complete-card" class="span4 exam-card">
            <div class="exam-card-count pending"><?php echo count($un_submitted_exams); ?></div>
            <p class="exam-card-description pending"><?php echo sprintf($SUBMODULE_TEXT["message_not_submitted"], count($un_submitted_exams)); ?></p>
            <a class="exam-card-status-btn<?php echo ($card_selected === "un_complete" ? " active" : "");?>" id="un_complete_button" data-status="un_complete">
                <?php echo $SUBMODULE_TEXT["text_not_submitted"]; ?> <span class="down-arrow"></span>
            </a>
        </div>
        <div id="exam-complete-card" class="span4 exam-card">
            <div class="exam-card-count complete"><?php echo count($submitted_exams); ?></div>
            <p class="exam-card-description complete"><?php echo sprintf($SUBMODULE_TEXT["message_submitted"], count($submitted_exams)); ?></p>
            <a class="exam-card-status-btn<?php echo ($card_selected === "complete" ? " active" : "");?>" data-status="complete">
                <?php echo $SUBMODULE_TEXT["text_submitted"]; ?> <span class="down-arrow"></span>
            </a>
        </div>
        <div class="span2"></div>
    </div>
    <div class="grading-table-wrapper" id="grading-un_complete-table-wrapper"<?php echo ($card_selected === "complete" ? " style=\"display:none\"" : "");?>>
        <div id="search_div"></div>
        <h2><?php echo $SUBMODULE_TEXT["text_not_submitted_exams"]; ?></h2>
        <table class="table table-striped table-bordered grading-table" id="table_un_complete">
            <thead>
                <tr>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["name"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["name"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["new"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["new"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["resume"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["resume"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_review"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["score_review"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["feedback_review"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["feedback_review"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["mandatory"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["mandatory"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["start_date"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["start_date"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["end_date"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["end_date"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["submission_deadline"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["submission_deadline"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_start_date"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["score_start_date"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_end_date"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["score_end_date"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["course_code"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["course_code"]; ?>
                        </a>
                    </th>
                    <th>
                        <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["course_name"];?>" class="settings_tooltip">
                            <?php echo $SUBMODULE_TEXT["table_headers"]["course_name"]; ?>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($un_submitted_exams && is_array($un_submitted_exams) && !empty($un_submitted_exams)) {
                foreach ($un_submitted_exams as $post_id => $post) {
                    if ($post && is_object($post)) {
                        $post_view = new Views_Exam_Post($post);
                        echo $post_view->renderPostRow();
                    }
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="grading-table-wrapper" id="grading-complete-table-wrapper"<?php echo ($card_selected === "un_complete" ? " style=\"display:none\"" : "");?>>
        <div id="search_div"></div>
        <h2><?php echo $SUBMODULE_TEXT["text_submitted_exams"]; ?></h2>
        <table class="table table-striped table-bordered grading-table" id="table_complete">
            <thead>
            <tr>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["name"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["name"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["new"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["new"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["resume"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["resume"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_review"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["score_review"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["feedback_review"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["feedback_review"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["mandatory"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["mandatory"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["start_date"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["start_date"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["end_date"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["end_date"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["submission_deadline"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["submission_deadline"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_start_date"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["score_start_date"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["score_end_date"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["score_end_date"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["course_code"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["course_code"]; ?>
                    </a>
                </th>
                <th>
                    <a href="#" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["tool_tips"]["course_name"];?>" class="settings_tooltip">
                        <?php echo $SUBMODULE_TEXT["table_headers"]["course_name"]; ?>
                    </a>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($submitted_exams && is_array($submitted_exams) && !empty($submitted_exams)) {
                foreach ($submitted_exams as $post_id => $post) {
                    if ($post && is_object($post)) {
                        $post_view = new Views_Exam_Post($post);
                        echo $post_view->renderPostRow();
                    }
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <div id="post-modal"  class="modal hide fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo $SUBMODULE_TEXT["text_exam_activity"]; ?></h4>
                </div>
                <div class="modal-body" >
                    <div id="missing-responses" class="alert alert-notice">
                        <p><i class="fa fa-spinner fa-pulse"></i> <?php echo $SUBMODULE_TEXT["text_loading_exam_activity"]; ?></p>
                    </div>
                    <div id="exam-activity-content">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SUBMODULE_TEXT["buttons"]["btn_close"];?></button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <?php
}