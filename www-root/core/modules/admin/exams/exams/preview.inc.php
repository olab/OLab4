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
 * This file loads controls for posting an exam preview.
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
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }
    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
    $inserted = 0;
    if ($exam && is_object($exam)) {
        $exam_view = new Views_Exam_Exam($exam);
        echo "<h1>" . $exam->getTitle() . "</h1>";
        echo $exam_view->examNavigationTabs($SECTION);

        $post = Models_Exam_Post::fetchRowByExamIDType($exam->getID(), "preview");
        $can_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete") ? "true" : "false";

        if ($post && is_object($post)) {
            $PROCESSED = $post->toArray();
        } else {
            // create the preview post if one is not posted
            $PROCESSED["exam_id"]                   = $exam->getID();
            $PROCESSED["title"]                     = "Preview - " . $exam->getTitle();
            $PROCESSED["backtrack"]                 = 1;
            $PROCESSED["secure"]                    = 0;
            $PROCESSED["release_score"]             = 1;
            $PROCESSED["release_feedback"]          = 1;
            $PROCESSED["release_incorrect_responses"] = 0;
            $PROCESSED["max_attempts"]              = 100;
            $PROCESSED["target_type"]               = "preview";
            $PROCESSED["mandatory"]                 = 0;
            $PROCESSED["mark_faculty_review"]       = 0;
            $PROCESSED["hide_exam"]                 = 0;
            $PROCESSED["auto_save"]                 = 30;
            $PROCESSED["auto_submit"]               = 0;
            $PROCESSED["use_time_limit"]            = 0;
            $PROCESSED["use_exam_start_date"]       = 0;
            $PROCESSED["use_exam_end_date"]         = 0;
            $PROCESSED["use_exam_submission_date"]  = 0;
            $PROCESSED["use_release_start_date"]    = 0;
            $PROCESSED["use_release_end_date"]      = 0;
            $PROCESSED["use_re_attempt_threshold"]  = 0;
            $PROCESSED["use_resume_password"]       = 0;
            $PROCESSED["start_date"]                = time();
            $PROCESSED["end_date"]                  = NULL;
            $PROCESSED["release_start_date"]        = time();
            $PROCESSED["release_end_date"]          = NULL;
            $PROCESSED["timeframe"]                 = "none";
            $PROCESSED["created_date"]              = time();
            $PROCESSED["updated_date"]              = time();
            $PROCESSED["created_by"]                = $ENTRADA_USER->getID();
            $PROCESSED["updated_by"]                = $ENTRADA_USER->getID();

            if ($exam && is_object($exam)) {
                $PROCESSED["target_id"] = $exam->getID() . "_" . time();

                $exam_post = new Models_Exam_Post($PROCESSED);

                if ($exam_post->insert()) {
                    $inserted = 1;
                    $post = Models_Exam_Post::fetchRowByExamIDType($exam->getID(), "preview");
                }
            }
        }
    }

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"" . ENTRADA_URL . "\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"" . ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var exam_id = " . (int)$PROCESSED["id"] . ";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var can_delete = " . $can_delete . ";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE ."/" . $SUBMODULE . "/". $SECTION . ".js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css?release=" . html_encode(APPLICATION_VERSION) . " />";


    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];


    if (isset($exam) && is_object($exam)) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $exam->getTitle());

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update")) {
            ?>
            <h2><?php echo $SUBMODULE_TEXT["preview"]["title"]; ?></h2>

            <?php
            if ($post && is_object($post)) {
                ?>
                </br>
                <?php
                $post_view = new Views_Exam_Post($post);
                echo $post_view->renderPreviewPost(false, false);
                ?>
<!--                <div class="pull-right clearfix space-below">-->
<!--                    <button id="edit-post" class="btn btn-primary">Edit --><?php //echo $SUBMODULE_TEXT["preview"]["title"]; ?><!--</button>-->
<!--                </div>-->
                <?php
            }

            ?>
            <div id="preview-exam-modal" class="modal hide fade" data-href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION ;?>">
                <form id="preview-exam-modal-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>" method="POST" style="margin:0px;">
                    <input type="hidden" name="step" value="2" />
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["preview"]["title_modal_preview_exams"]; ?></h1></div>
                    <div class="modal-body">
                        <div>
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <td>Setting Name</td>
                                    <td>Setting Control</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-fluid">
                            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                            <input id="preview-exams-modal-button" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_add"]; ?>" />
                        </div>
                    </div>
                </form>
            </div>
            <?php

        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["posts"]["title"]; ?></h1>
        <?php
        echo display_error($SUBMODULE_TEXT["posts"]["post_not_found"]);
    }
}