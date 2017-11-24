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
 * This file loads details for any exam post activity ie students progress
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
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE ."/" . $SUBMODULE . "/". $SECTION . ".js\"></script>";
    ?>
    <script>
        var progress_id;
        var progress_table;
    </script>
    <style>
        .ColVis {
            margin: 0px 5px;
        }

        span.late-submission {
            color: #CC0000;
            font-weight: 800;
        }
    </style>
    <?php
    
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $post = Models_Exam_Post::fetchRowByExamIDNoPreview($PROCESSED["id"]);

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION]["progress"];

    if (isset($post) && is_object($post)) {
        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=post&id=" . $exam->getID() , "title" => $exam->getTitle());
        $BREADCRUMB[] = array("title" => "Activity");
        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update")) {
            ?>
            <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
            <?php
            if ($exam) {
                $exam_view = new Views_Exam_Exam($exam);
                echo $exam_view->examNavigationTabs($SECTION);
            }
            ?>
            <h2><?php echo $exam->getTitle(); ?></h2>
            <h3><?php echo $post->getTitle(); ?></h3>
            <?php
            $progress = Models_Exam_Progress::fetchAllByPostID($post->getID());

            if (isset($progress) && is_array($progress) && !empty($progress)) {
                // Now we need to get all the students who are in the audience
                // but have not yet begun the exam.
                $audience_full = array();
                if ($post->getTargetType() === "event") {
                    $event = Models_Event::fetchRowByID($post->getTargetID());
                    $event_start = $event->getEventStart();
                    if (!$event) {
                        return array();
                    }
                    $event_audiences = $event->getEventAudience();
                    foreach ($event_audiences as $event_audience) {
                        $a = $event_audience->getAudience($event_start);
                        $audience_full = array_merge($audience_full, array_keys($a->getAudienceMembers()));
                    }
                } else if ($post->getTargetType() === "community") {
                    $community_members = Models_Community_Member::fetchAllByCommunityID($post->getTargetID());
                    $audience_full = array_map(function($a) { return (int)$a->getProxyId(); }, $community_members);
                }
                $audience_not_started = array_values(array_diff($audience_full, array_map(function($a) { return $a->getProxyId(); }, $progress)));

                ?>
                <div id="show_columns">
                    <input type="button" class="btn pull-right" value="Download CSV" id="download-csv"/>
                </div>
                <table class="table table-bordered table-striped" id="progress-table" style="background: #fff">
                    <thead>
                    <tr>
                        <th><?php echo $SECTION_TEXT["student"]; ?></th>
                        <th><?php echo $SECTION_TEXT["number"]; ?></th>
                        <th><?php echo $SECTION_TEXT["progress_value"]; ?></th>
                        <th><?php echo $SECTION_TEXT["submission_date"]; ?></th>
                        <th><?php echo $SECTION_TEXT["late"]; ?></th>
                        <th><?php echo $SECTION_TEXT["exam_points"]; ?></th>
                        <th><?php echo $SECTION_TEXT["exam_value"]; ?></th>
                        <th><?php echo $SECTION_TEXT["exam_score"]; ?></th>
                        <th><?php echo $SECTION_TEXT["created"]; ?></th>
                        <th><?php echo $SECTION_TEXT["createdBy"]; ?></th>
                        <th><?php echo $SECTION_TEXT["started"]; ?></th>
                        <th><?php echo $SECTION_TEXT["update"]; ?></th>
                        <th><?php echo $SECTION_TEXT["updatedBy"]; ?></th>
                        <th class="edit_menu"><?php echo $SECTION_TEXT["edit"]; ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($progress as $student_attempt) {
                        if (is_object($student_attempt)) {
                            $progress_view = new Views_Exam_Progress($student_attempt);
                            echo $progress_view->render();
                        }
                    }

                    $num_of_columns = 10;
                    foreach ($audience_not_started as $proxy_id) {
                        $student = Models_User::fetchRowByID($proxy_id, null, null, 1);
                        if ($student) {
                            echo "<tr>\n";
                            echo "<td>".$student->getFullname()."</td>\n";
                            echo "<td>".$student->getNumber()."</td>\n";
                            echo "<td>Not Started</td>\n";
                            echo "<td>N/A</td>\n";
                            for ($i = 0; $i < $num_of_columns; $i++) {
                                echo "<td></td>\n";
                            }
                            echo "</tr>\n";
                        }
                    }
                    ?>
                    </tbody>
                </table>

                <div id="reopen-modal"  class="modal hide fade">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title"><?php echo $SECTION_TEXT["title_reopen"]; ?></h4>
                            </div>
                            <div class="modal-body">
                                <?php echo $SECTION_TEXT["reopen_instructions"];?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_cancel"];?></button>
                                <button type="button" class="btn btn-primary reopen" data-id="<?php $PROCESSED["id"];?>"><?php echo $SECTION_TEXT["buttons"]["btn_reopen"]?></button>
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->

                <div id="delete-modal"  class="modal hide fade">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title"><?php echo $SECTION_TEXT["title_delete"]; ?></h4>
                            </div>
                            <div class="modal-body">
                                <?php echo $SECTION_TEXT["delete_instructions"];?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SECTION_TEXT["buttons"]["btn_cancel"];?></button>
                                <button type="button" class="btn btn-primary delete-progress" data-id="<?php $PROCESSED["id"];?>"><?php echo $SECTION_TEXT["buttons"]["btn_delete_progress"]?></button>
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->

                <form enctype="multipart/form-data" method="post" action="<?php echo ENTRADA_URL."/admin/exams/exams?".replace_query(array("section" => "activity-csv"));?>" id="csv-form">
                    <input type="hidden" name="csv" id="csv-hidden-field" />
                </form>

                <?php
            } else {
                echo display_error($SECTION_TEXT["no_progress_records_found"]);
            }

        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . "exams?section=post&id=" . $PROCESSED["id"] , "title" => "Post");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["posts"]["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["post_not_found"]);
    }
}