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
 * The default file that is loaded when /admin/exams is accessed.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION).""."\"></script>";
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE, "title" => $translate->_("Dashboard"));

    $MODULE_TEXT = $translate->_($MODULE);
    $SUBMODULE_TEXT = $MODULE_TEXT["exams"];
    ?>

    <h1><?php echo $translate->_("Exam"); ?> Dashboard</h1>
    <div class="well">
        <?php echo $translate->_("Welcome to the Exams Module. If you have access to grade any exams, they will be shown below. To create new exam questions and to group questions use the <strong>Questions</strong> section. Questions and grouped questions can be assembled into exams in the <strong>Exams</strong> section."); ?>
    </div>
    
    <ul class="nav nav-tabs" id="exam-dashboard-tabs">
        <?php if ($ENTRADA_ACL->amIAllowed("exam", "create", false)) { ?>
            <li><a href="#recent-exams"><?php echo $translate->_("Recent Exams"); ?></a></li>
        <?php } ?>
        <li class="active"><a href="#exams-to-grade"><?php echo $translate->_("Exams to Grade"); ?></a></li>
    </ul>
    
    <div class="tab-content" style="overflow: visible;">
        <div class="tab-pane active" id="exams-to-grade">
            <?php
            $exam_posts = Models_Exam_Grader::fetchGradableExamPosts($ENTRADA_USER->getActiveId());
            if (0 === count($exam_posts)) {
                echo "<div class=\"alert\">".$translate->_("You have no exams to grade.")."</div>\n";
            } else {
                ?>
                <table class="table table-bordered table-striped" id="exams-to-grade-table">
                    <thead>
                        <tr>
                            <th><?php echo $translate->_("Post Title"); ?></th>
                            <th><?php echo $translate->_("Posted To"); ?></th>
                            <th class="grading-circles"><?php echo $translate->_("Progress"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($exam_posts as $exam_post) {
                        if (isset($exam_post) && is_object($exam_post)) {
                            $link = ENTRADA_URL."/admin/exams/grade?post_id=".$exam_post->getPostID();
                            $posted_to = "";
                            if ($exam_post->getTargetType() === "event") {
                                $event = Models_Event::fetchRowByID($exam_post->getTargetID());
                                if ($event) {
                                    $posted_to = "Event: ".$event->getEventTitle();
                                }
                            } else if ($exam_post->getTargetType() === "community") {
                                $community = Models_Community::fetchRowByCommunityID($exam_post->getTargetID());
                                if ($community) {
                                    $posted_to = "Community: ".$community->getCommunityTitle();
                                }
                            }
                            $submissions = Models_Exam_Grader::fetchGradableSubmissionsForPost($ENTRADA_USER->getActiveId(), $exam_post->getID());
                            $gradable_question_num = Models_Exam_Grader::fetchGradableQuestionCount($exam_post->getID());
                            $pending_count = 0;
                            $in_progress_count = 0;
                            $completed_count = 0;
                            foreach ($submissions as $submission) {
                                $graded_question_num = Models_Exam_Grader::fetchGradedQuestionCount($submission->getID());
                                if (0 === $graded_question_num) {
                                    $pending_count++;
                                } else if ($gradable_question_num === $graded_question_num) {
                                    $completed_count++;
                                } else {
                                    $in_progress_count++;
                                }
                            }
                            echo "<tr>\n";
                            echo "<td><a href=\"$link\">".html_encode($exam_post->getTitle())."</a></td>\n";
                            echo "<td><a href=\"$link\">".html_encode($posted_to)."</a></td>\n";
                            echo "<td style=\"text-align: center\">\n";
                            echo "<span class=\"exam-grading-progress-bubble pending\"><a href=\"$link&progress_type=pending\" title=\"".$translate->_("Not Started")."\" data-toggle=\"tooltip\">$pending_count</a></span>\n";
                            echo "<span class=\"exam-grading-progress-bubble in-progress\"><a href=\"$link&progress_type=in-progress\" title=\"".$translate->_("In Progress")."\" data-toggle=\"tooltip\">$in_progress_count</a></span>\n";
                            echo "<span class=\"exam-grading-progress-bubble complete\"><a href=\"$link&progress_type=complete\" title=\"".$translate->_("Complete")."\" data-toggle=\"tooltip\">$completed_count</a></span>\n";
                            echo "</td>\n";
                            echo "</tr>\n";
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div> <!-- End exams to grade tab page -->
        <?php if ($ENTRADA_ACL->amIAllowed("exam", "create", false)) { ?>
        <div class="tab-pane" id="recent-exams" >
            <?php
            $exam_ids = Models_Exam_Exam::fetchAllRecentExamIds(strtotime("-1 month"));
            $exams = array();
            foreach ($exam_ids as $exam_id) {
                if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam_id, true), "update")) {
                    $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                    if ($exam) {
                        $exams[] = $exam;
                    }
                }
            }
            
            if (0 === count($exams)) {
                echo "<div class=\"alert\">You have no recent exams to edit.</div>\n";
            } else {
                ?>
                <table class="table table-bordered table-striped" id="exams-table">
                    <thead>
                        <tr>
                            <th width="65%"><?php echo $SUBMODULE_TEXT["index"]["title_heading"]; ?></th>
                            <th width="15%"><?php echo $SUBMODULE_TEXT["index"]["updated_heading"]; ?></th>
                            <th width="10%"><?php echo $SUBMODULE_TEXT["index"]["questions_heading"]; ?></th>
                            <th width="5%"><?php echo $SUBMODULE_TEXT["index"]["posts_heading"]; ?></th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($exams as $exam) {
                        if ($exam && is_object($exam)) {
                            $url = ENTRADA_URL . "/admin/exams/exams?section=edit-exam&id=" . $exam->getID();
                            $view = new Views_Exam_Exam($exam);
                            if ($view) {
                                $show_select_exam = 0;
                                echo $view->render($show_select_exam);
                            }
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </div> <!-- End recent exams tab page -->
        <?php } // End amIAllowed("exam", "create", false) ?>
    </div>

    <div id="post-info-modal"  class="modal hide fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo $SUBMODULE_TEXT["index"]["post-info"]["title_post"]; ?></h4>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SUBMODULE_TEXT["buttons"]["btn_cancel"];?></button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).ready(function() {
            $("[data-toggle=tooltip]").tooltip({ placement: "bottom" });
        });
        $("#exams-to-grade-table").dataTable({
            sPaginationType: "full_numbers",
            bSortClasses: false,
            bPaginate: false,
            iDisplayLength: 25,
            sDom: '<"top">rt<"bottom"p><"clear">',
            aaSorting: [[ 0, "asc" ]]
        });
        $("#exam-dashboard-tabs a:first").tab("show");
        $("#exam-dashboard-tabs a").click(function() {
            $(this).tab("show");
            return false;
        });

        $("#exams-table").on("click", ".get-post-targets", function (e) {
            var exam_id = $(this).data("id");
            var transmission = {"method" : "courses-from-posts", "exam_id" : exam_id};
            $("#post-info-modal").modal("show");
            $.ajax({
                url: "exams/exams?section=api-exams",
                data: transmission,
                type: "POST",
                success: function(data) {
                    var jsonResponse = JSON.parse(data);
                    var html = jsonResponse.html;
                    if (jsonResponse.status == "success") {
                        $("#post-info-modal .modal-body").html(html);
                    } else if (jsonResponse.status == "warning") {
                        $("#post-info-modal .modal-body").html(jsonResponse.msg);
                    } else {
                        $("#post-info-modal .modal-body").html(jsonResponse.msg);
                    }
                }
            });
        });
    });
    </script>
    <?php
}
