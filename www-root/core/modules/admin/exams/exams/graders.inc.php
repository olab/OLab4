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
 * Provides an interface for editing graders on an exam.
 *
 * @author Organization: Queens University.
 * @author Developer: Steve Yang <sy49@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
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
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    
    $post = null;
    if (isset($_GET["post_id"]) && $post_id = (int)clean_input($_GET["post_id"], "int")) {
        $post = Models_Exam_Post::fetchRowByExamIDNoPreview($post_id);
        $course = $post->getCourse();
    }
    if (!isset($post) || !$post) {
        add_error($translate->_("You must supply a valid exam post ID."));
        echo display_error();
    } else if (!$ENTRADA_ACL->amIAllowed(new ExamResource($post->getExamID(), true), "update")) {
        add_error($translate->_("You do not have permission to edit the graders for this exam post."));
        echo display_error();
    } else if (!isset($course) || !$course) {
        add_error($translate->_("Could not find course groups for this exam post.."));
        echo display_error();
    } else {
        ?>
        <h1><?php echo sprintf($SECTION_TEXT["title"], $post->getTitle()); ?></h1>
        <?php
        $event = $post->getEvent();
       
        if ($event !== null) {
            $audience = Models_Event_Attendance::fetchAllByEventID($event->getID(), $event->getEventStart());
        } else { // $event is null,  we assume the $post then must be attached to an assessment if lieu of an attached event.
            $assessment_details = $post->getGradeBookAssessment()->toArray();
        }

        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
        if ($exam) {
            $exam_view = new Views_Exam_Exam($exam);
            echo $exam_view->examNavigationTabs($SECTION);
        }

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=post&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=graders&post_id=".$post_id, "title" => "Edit Graders");

        switch ($STEP) {
            case 2 :
                $url = ENTRADA_URL . "/admin/exams/exams?" . replace_query(array("step" => false, "section" => "post", "id" => $exam->getID()));
                $msg =  "You will now be redirected to the <strong>Exam Posts</strong> page for exam\"<strong>" . $exam->getTitle() . "</strong>\"; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.";
                add_success($msg);
                $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                echo display_success();
            break;
            case 1 :
            default :

                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery-shuffle.js\"></script>\n";
                $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/exams/graders-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/exams/graders-admin.css?release=".html_encode(APPLICATION_VERSION)."\" />";

                $PROCESSED["graders"] = Models_Exam_Grader::fetchGradersIdsbyPostId($post_id);
                if ($PROCESSED["graders"] && is_array($PROCESSED["graders"])) {
                     foreach ($PROCESSED["graders"] as $grader) {
                        $PROCESSED["post_".$grader->getID()] = Models_Exam_Grader::fetchAssignedCourseGroups($post_id, $grader->getID());
                     }
                }
                ?>
                <script type="text/javascript">
                    var COURSE_ID = '<?php echo $course->getID(); ?>';
                    var EVENT_ID = '<?php echo $event->getID(); ?>';
                    var POST_ID = '<?php echo $post_id; ?>';
                    var ASSIGN_TO_GRADER_TEXT = '<?php echo $translate->_("Assign to Grader"); ?>';
                </script>
                
                <div class="row-fluid" style="padding-bottom: 2px;">
                    <div class="span6">
                        <h3>Graders</h3>
                        <div class="row-fluid">
                            <div class="span10">
                                <input class="search-icon input-large" type="text" id="grader_name" name="fullname" size="30" autocomplete="off" style="width: 97%; vertical-align: middle" onkeyup="checkItem('grader')" />
                                <div class="ui-autocomplete" id="grader_name_auto_complete"></div>
                                <input type="hidden" id="associated_grader" name="associated_grader" />
                            </div>
                            <div class="pull-right">
                                <input type="button" class="btn pull-right" onclick="addGrader('grader');" value="Add" style="vertical-align: middle" />
                                <input type="hidden" id="grader_ref" name="grader_ref" value="" />
                                <input type="hidden" id="grader_id" name="grader_id" value="" />
                            </div>
                        </div>

                        <div id="graders-assignments-container" class="hide">
                            <?php
                            if (isset($PROCESSED["graders"]) && is_array($PROCESSED["graders"])) {
                                foreach ($PROCESSED["graders"] as $grader) {
                                    if (isset($PROCESSED["post_".$grader->getID()]) && is_array($PROCESSED["post_".$grader->getID()])) {
                                        foreach ($PROCESSED["post_".$grader->getID()] as $group) {
                                            ?>
                                            <input type="hidden" name="post_<?php echo $grader->getID(); ?>[]" value="<?php echo $group->getGroupName(); ?>" />
                                            <?php
                                        }
                                    }
                                }
                            }
                            ?>
                        </div>

                        <div class="clearfix" style="margin-bottom: 20px;"></div>

                        <!-- Graders to groups table -->
                        <div style="max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                            <table id="table-graders-to-groups" class="table table-bordered table-striped" style="margin-bottom: 0;">
                                <thead>
                                <tr>
                                    <th>Grader</th>
                                    <th>Assigned Course Groups</th>
                                </tr>
                                </thead>
                                <tbody>
                                
                                <?php
                                if (isset($PROCESSED["graders"]) && is_array($PROCESSED["graders"])) {
                                    foreach ($PROCESSED["graders"] as $grader) {
                                        ?>
                                        <tr>
                                            <td>
                                                <label for="grader_<?php echo $grader->getID(); ?>"><input id="grader_<?php echo $grader->getID(); ?>" name="chk_graders[]" value="<?php echo $grader->getID(); ?>" type="checkbox" data-name="<?php echo $grader->getFullname(); ?>"><?php echo $grader->getFullName(); ?></label>
                                                <input type="hidden" name="graders[]" value="<?php echo $grader->getID(); ?>">
                                            </td>
                                            <td id="td-graders-to-group-<?php echo $grader->getID(); ?>">
                                                <?php
                                                if (isset($PROCESSED["post_".$grader->getID()]) && is_array($PROCESSED["post_".$grader->getID()])) {
                                                    foreach ($PROCESSED["post_".$grader->getID()] as $group) {
                                                    ?>
                                                    <div style="margin-bottom: 10px;" data-id="<?php echo $group->getCgroupID(); ?>" data-name="<?php echo $group->getGroupName();?>">
                                                        <?php echo $group->getGroupName();?><img id="remove-group-<?php echo $group->getCgroupID(); ?>" src="/images/action-delete.gif" class="remove-group pull-right" style="cursor: pointer;" data-id="<?php echo $group->getCgroupID(); ?>" data-grader="<?php echo $grader->getID(); ?>">
                                                    </div>
                                                    <?php
                                                    }
                                                } else { 
                                                    echo "<i>No groups assigned</i>"; 
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Remove grader button -->
                        <a id="btn-remove-graders" href="#modal-remove-grader" class="btn"><span class="icon-minus-sign"></span> Remove Selected Graders</a>

                        <div class="clearfix" style="margin-bottom: 20px;"></div>
                    </div>
                    <div class="span6">
                        <h3>Groups</h3>
                        <div class="row-fluid" style="padding-bottom: 10px;">
                            <button id="randomly-distribute-groups" class="btn btn-primary"><span class="icon-random icon-white"></span> Randomly Distribute Groups to Graders</button>
                        </div>
                        <div class="clearfix" style="margin-bottom: 20px;"></div>

                        <!-- Groups table -->
                        <div id="group-learner-table" class="learner-table" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;">
                            <table id="table-groups-no-grader" class="table table-bordered table-striped" style="margin-bottom: 0;">
                                <thead>
                                <tr>
                                    <th><input id="all-groups" value="1" type="checkbox">Course Groups</th>
                                    <th>Assign to Grade </th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <a id="btn-assign-group" href="#assign-grader-modal" class="btn"><span class="icon-plus"></span> Assign Selected to Grader</a>
                        <div class="clearfix" style="margin-bottom: 20px;"></div>
                    </div>
                </div>
                <a href="#" id="btn-save-grader-settings" role="button" class="btn btn-primary pull-right">Save Grader Settings</a>
                <?php

                $modal_body = "<div class=\"alert alert-block hide\"></div>
                        <table id=\"table-assign-grader-modal\" class=\"table table-bordered table-striped\">
                            <thead>
                            <tr>
                                <th>Graders</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>";

                $modal_attach_assessment = new Views_Gradebook_Assignments_Modal(array(
                    "id" => "assign-grader-modal",
                    "title" => $translate->_("Assign Selected Groups to Grader"),
                    "body" => $modal_body,
                    "dismiss_button" => array(
                        "text" => $translate->_("Close"),
                        "class" => "pull-left close-assign-grader-modal"
                    ),
                    "success_button" => array(
                        "text" => $translate->_("Assign Groups"),
                        "class" => "pull-right btn-primary btn-modal-assign-groups"
                    )
                ));

                $modal_attach_assessment->render();

                $modal_body = "<div class=\"alert alert-block hide\"></div>
                        <table id=\"table-modal-remove-grader\" class=\"table table-bordered table-striped\">
                            <thead>
                            <tr>
                                <th>Graders</th>
                                <th>Assigned Groups</th>
                            </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>";

                $modal_remove_grader = new Views_Gradebook_Assignments_Modal(array(
                    "id" => "modal-remove-grader",
                    "title" => $translate->_("Remove Grader"),
                    "body" => $modal_body,
                    "dismiss_button" => array(
                        "text" => $translate->_("Cancel"),
                        "class" => "pull-left close-modal-remove-grader"
                    ),
                    "success_button" => array(
                        "text" => $translate->_("Remove Grader"),
                        "class" => "pull-right btn-primary btn-modal-remove-grader"
                    )
                ));

                $modal_remove_grader->render();

                $modal_remove_all_graders_from_post = new Views_Gradebook_Assignments_Modal(array(
                    "id" => "modal_remove_all_graders_from_post",
                    "title" => $translate->_("No Grader Assigned"),
                    "body" => "<p></p>",
                    "dismiss_button" => array(
                        "text" => $translate->_("Cancel"),
                        "class" => "pull-left close-modal-remove-all-graders-from-post"
                    ),
                    "success_button" => array(
                        "text" => $translate->_("Confirm"),
                        "class" => "pull-right btn-primary btn-modal-remove-all-graders-from-post"
                    )
                ));

                $modal_remove_all_graders_from_post->render();

            break;
        }   
    }
}