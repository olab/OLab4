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
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE ."/" . $SUBMODULE . "/". $SECTION . ".js?release=". html_encode(APPLICATION_VERSION) ."\"></script>"
    ?>
    <style>
        #add-post {
            margin-right: 10px;
            margin-left: 10px;
        }
    </style>
    <?php

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }
    $template = simplexml_load_file($ENTRADA_TEMPLATE->absolute() . "/email/notification-rpnow-users.xml");
    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if (isset($exam) && is_object($exam)) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $exam->getTitle());
        echo "<h1>" . $exam->getTitle() . "</h1>";
        $exam_view = new Views_Exam_Exam($exam);
        echo $exam_view->examNavigationTabs($SECTION);

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update")) {
            ?>
            <h2><?php echo $SUBMODULE_TEXT["posts"]["title_plural"]; ?></h2>
            <div id="msgs"></div>
            <div class="text-right clearfix space-below">
                <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=preview&id=" . $exam->getID(); ?>" class="btn btn-primary" id="preview-post">Preview <?php echo $SUBMODULE_TEXT["posts"]["title_singular"]; ?></a>
                <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=form-post&id=" . $exam->getID(); ?>&target_type=event" id="add-post" class="btn btn-success"><i class="fa fa-plus-circle"></i> Add New <?php echo $SUBMODULE_TEXT["posts"]["title_singular"]; ?></a>
            </div>
            <?php
            $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
            if (isset($posts) && is_array($posts)) {
                ?>
                <script>
                    jQuery(document).ready(function($) {
                        var can_delete = <?php echo $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete") ? "true" : "false"; ?>;
                        var posts_table = $("#posts-table").DataTable({
                            sPaginationType: 'full_numbers',
                            bSortClasses: false,
                            oSearch: { bSmart: false },
                            aoColumnDefs: [{
                                aTargets: (can_delete ? [0, -1] : [-1]),
                                bSortable: false
                            }],
                            aaSorting: [[(can_delete ? 3 : 2), 'asc']],
                            "lengthMenu": [[-1, 10, 50, 100], ["All", 10, 50, 100]],
                            "columns": [
                                { "visible": false },
                                null,
                                null,
                                { "visible": false },
                                null,
                                null,
                                { "visible": false },
                                null,
                                null,
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                { "visible": false },
                                null
                            ]
                        });
                        //adds the Show/Hide columns button
                        var colvis = new jQuery.fn.dataTable.ColVis( posts_table );
                        jQuery('#preview-post').before(colvis.button());

                        $(".ColVis").show();
                        $(".ColVis_Button").addClass("btn").removeClass("ColVis_Button");
                    });

                </script>
                <table class="table table-bordered table-striped" id="posts-table">
                    <thead>
                    <tr>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["title_heading"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["target"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["course_name"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["course_code"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["downloads"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["submissions"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["max_attempts"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["start_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["end_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["sub_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["release_score"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["release_start_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["release_end_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["release_feedback"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["created_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["created_by"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["updated_date"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["updated_by"]; ?></th>
                        <th><?php echo $SUBMODULE_TEXT["posts"]["actions"]; ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($posts as $post) {
                        if (is_object($post)) {
                            $post_view = new Views_Exam_Post($post);
                            echo $post_view->render(true);
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <div id="email-rpnow-view-modal" class="modal fade hide">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                <h4 id="email-rpnow-view-modal-heading" class="modal-title"> Rp-Now Email Notification </h4>
                            </div>
                            <div class="modal-body">
                                <div>
                                    <input type="hidden" id="post_id" name="post_id" value="">
                                    <div id="email-rpnow-msgs"></div>
                                    <label class="checkbox" for="email_directors">
                                        <input type="checkbox" id="email_directors" name="email_directors" checked="checked" value="1" /> <?php echo $translate->_("Send list of student name and exam code to course director."); ?>
                                    </label>
                                    <label class="checkbox" for="email_students">
                                        <input type="checkbox" id="email_students" name="email_students" onclick="toggle_visibility_checkbox(this, '#send_notification_msg');toggle_visibility_checkbox(this, '#send_notification_subject')" checked="checked" value="1"/> <?php echo $translate->_("Send email for each student with their exam code."); ?>
                                    </label>
                                    <div class="control-group" id="send_notification_subject">
                                        <label class="control-label" for="subject">Subject</label>
                                        <div class="controls">
                                            <input type="text" id="subject" name="subject" value="<?php echo $template->template->subject; ?>"/>
                                        </div>
                                    </div>
                                    <div id="send_notification_msg" style="display: block">
                                        <label class="control-label" for="message">Message</label>
                                        <p class="content-small space-below clearfix"><small><strong class="span4">Available Variables:</strong><span class="span12 muted">%FIRSTNAME%, %LASTNAME%, %EXAM_CODE%, %RP_NOW_URL%, %CREATOR_FIRSTNAME%, %CREATOR_LASTNAME%, %CREATOR_EMAIL%</span></small></p>
                                        <div class="controls">
                                            <textarea id="message" class="expandable" name="message" style="width: 98%; height: 300px"><?php echo $template->template->body; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                                <button id="rpnow-email-notification" type="button" class="btn btn-primary">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                        jQuery(document).ready(function($) {

                            $(".email_rpnow").on("click", function (event) {
                                $("#email-rpnow-msgs").empty();
                                $("#post_id").val($(this).attr("data-post"));
                                $("#email-rpnow-view-modal").modal("show");
                            });

                            $("#rpnow-email-notification").on("click", function (event) {
                                var data_object = {
                                    "method": "email-rpnow-code",
                                    "post_id": $("#post_id").val(),
                                    "subject": $("#subject").val(),
                                    "message": $("#message").val(),
                                    "email_students" : $("#email_students:checked").val(),
                                    "email_directors" : $("#email_directors:checked").val()
                                };
                                $.ajax({
                                    type: "POST",
                                    url: ENTRADA_URL + "/api/api-rpnow.api.php",
                                    data: data_object,
                                    success: function (data) {
                                        var jsonResponse = JSON.parse(data);
                                        if (jsonResponse.status === "success") {
                                            $("#email-rpnow-view-modal").modal("hide");
                                            display_success([jsonResponse.data], "#msgs", "append");
                                        }
                                        if (jsonResponse.status === "error") {
                                            display_error(jsonResponse.data, "#email-rpnow-msgs");
                                        }
                                    }
                                });
                            });
                        });
                </script>

            <?php
            }

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