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
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=drafts", "title" => "My Drafts");

    $drafts = array();
    $is_admin = Entrada_Utilities::isCurrentUserSuperAdmin(array(array("resource" => "assessmentreportadmin")));
    if ($is_admin) {
        $all_drafts = Models_Schedule_Draft::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation(), "draft", "draft_title");
        if (is_array($all_drafts)) {
            foreach ($all_drafts as $schedule_draft_record) {
                $drafts[$schedule_draft_record->getID()] = $schedule_draft_record;
            }
        }
    } else {
        $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
        if ($courses) {
            foreach ($courses as $course) {
                if ($schedule_draft = Models_Schedule_Draft::fetchAllByProxyIDCourseID($ENTRADA_USER->getActiveID(), $course->getID(), "draft")) {
                    foreach ($schedule_draft as $schedule_draft_record) {
                        $drafts[$schedule_draft_record->getID()] = $schedule_draft_record;
                    }
                }
            }
        }
    }
    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["current_draft_id"] = $tmp_input;
    }

    if (isset($_POST["delete"])) {
        $method = "delete";
    } else if (isset($_POST["publish"])){
        $method = "publish";
    }

    if (isset($_POST["draft_ids"]) && is_array($_POST["draft_ids"]) && !empty($_POST["draft_ids"])) {
        foreach ($_POST["draft_ids"] as $draft_id) {
            $tmp_input = clean_input($draft_id);
            if ($tmp_input) {
                $PROCESSED["draft_ids"][] = $tmp_input;
            }
        }
    } else {
        if (isset($method) && empty($_POST["draft_ids"])) {
            add_error(sprintf($translate->_("Please check off the drafts you wish to %s."), $method));
            unset($method);
            $STEP = 1;
        }
    }

    switch ($STEP) {
        case 3 :
            if (isset($PROCESSED["draft_ids"]) && !empty($PROCESSED["draft_ids"])) {
                switch ($method) {
                    case "delete" :
                        foreach ($PROCESSED["draft_ids"] as $draft_id) {
                            $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
                            if ($draft && !$draft->fromArray(array("deleted_date" => time()))->update()) {
                                $ERROR++;
                            }
                        }
                        break;
                    case "publish" :
                        foreach ($PROCESSED["draft_ids"] as $draft_id) {
                            $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
                            if ($draft && !$draft->fromArray(array("status" => "live"))->update()) {
                                $ERROR++;
                            }
                        }
                        break;
                    default :
                        break;
                }
                if (!$ERROR) {
                    add_success(sprintf($translate->_("Successfully %s <strong>%s</strong> rotation schedule."), ($method == "delete" ? "deleted" : $method."ed"), count($PROCESSED["draft_ids"])));
                    $STEP = 1;
                    $drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID());
                    unset($method);
                }
            }
        break;
        case 2 :
            if (isset($PROCESSED["draft_ids"]) && !empty($PROCESSED["draft_ids"])) {
                $drafts = array();
                foreach ($PROCESSED["draft_ids"] as $draft_id) {
                    $drafts[] = Models_Schedule_Draft::fetchRowByID($draft_id);
                }
                add_notice(sprintf($translate->_("You have chosen the following drafts to be %sed. Please confirm by clicking on the <strong>%s</strong> button below."), $method, ucwords($method)));
            }

        break;
    }

    $SECTION_TEXT = $MODULE_TEXT[$SECTION];
    define("IN_DRAFT", true);

    ?>
    <h1><?php echo $translate->_("My Draft Rotation Schedules"); ?></h1>
    <?php
        Views_Schedule_UserInterfaces::renderScheduleNavTabs($SECTION);

        if ($NOTICE) {
            echo display_notice();
        }
        if ($SUCCESS) {
            echo display_success();
        }
        if ($ERROR) {
            echo display_error();
        }
    ?>
    <?php if (!isset($method)) { ?>
    <div class="row-fluid space-below">
        <a href="#new-draft-modal" data-toggle="modal" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("New Draft"); ?></a>
    </div>
    <?php } ?>
    <form class="form-horizontal" id="my-drafts" method="POST" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>">
        <input type="hidden" name="step" value="<?php echo $STEP + 1; ?>" />
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="40%"><?php echo $translate->_("Draft"); ?></th>
                    <th width="35%"><?php echo $translate->_("Course"); ?></th>
                    <th width="20%"><?php echo $translate->_("Created Date"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($drafts) {
                    foreach ($drafts as $draft) {
                        $url = ENTRADA_URL."/admin/".$MODULE."?section=edit-draft&draft_id=".$draft->getID();
                        $course = Models_Course::fetchRowByID($draft->getCourseID());
                        ?>
                        <tr>
                            <td><input type="checkbox" name="draft_ids[]" value="<?php echo $draft->getID(); ?>" <?php echo isset($method) && ($method == "delete" || $method == "publish") ? "checked=\"checked\"" : ""; ?> /></td>
                            <td><a href="<?php echo $url; ?>"><?php echo $draft->getTitle(); ?></a></td>
                            <td><a href="<?php echo $url; ?>"><?php echo $course ? $course->getCourseCode() . " - " . $course->getCourseName() : ""; ?></a></td>
                            <td><a href="<?php echo $url; ?>"><?php echo date("Y-m-d", $draft->getCreatedDate()); ?></a></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4"><?php echo $translate->_("You have no unpublished drafts in the system."); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <div class="row-fluid">
            <?php
                if (isset($method)) {
                    $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts";
                    if (isset($PROCESSED["current_draft_id"])) {
                        $url = ENTRADA_URL . "/admin/".$MODULE."?section=edit-draft&draft_id=" . $PROCESSED["current_draft_id"];
                    }
                ?>
                <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                <?php
                }
            ?>
            <?php if (!isset($method) || $method == "delete") { ?>
            <input type="submit" name="delete" class="btn btn-danger <?php echo isset($method) && $method == "delete" ? "pull-right" : ""; ?>" value="<?php echo $translate->_("Delete"); ?>"/>
            <?php } ?>
            <?php if (!isset($method) || $method == "publish") { ?>
            <input type="submit" name="publish" class="btn btn-primary pull-right" value="<?php echo $translate->_("Publish"); ?>" />
            <?php } ?>
        </div>
    </form>
    <script type="text/javascript">
        jQuery(function($) {
            $("#new-draft-btn").on("click", function(e) {
                $("#new-draft-form").submit();
            });
            $("#new-draft-form").on("submit", function(e) {
                $("#new-draft-modal .msgs").empty();
                $.ajax({
                    url: $("#new-draft-modal form").attr("action"),
                    type: "POST",
                    data: "method=new-draft&draft_title=" + $("#draft-title").val() + ($("#course").length >= 1 ? "&course_id=" + $("#course").val() : "") + ($("#curriculum_period").length >= 1 ? "&cperiod_id=" + $("#curriculum_period").val() : ""),
                    success : function(data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status == "success") {
                            window.location = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id="; ?>" + jsonResponse.data;
                        } else {
                            display_error(jsonResponse.data, $("#new-draft-modal .msgs"));
                        }
                    }
                });
                e.preventDefault();
            });
        })
    </script>
    <div id="new-draft-modal" class="modal fade hide">
        <div class="modal-header">
            <h1><?php echo $translate->_("New Draft"); ?></h1>
        </div>
        <div class="modal-body">
            <div class="msgs"></div>
            <form method="POST" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>" class="form-horizontal" id="new-draft-form">
                <div class="control-group">
                    <label class="control-label form-required" for="draft-title"><?php echo $translate->_("Draft Title"); ?></label>
                    <div class="controls">
                        <input type="text" id="draft-title" name="draft_title" value=""/>
                    </div>
                </div>
                <?php
                $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
                if ($courses) {
                    ?>
                    <div class="control-group">
                        <label class="control-label form-required" for="course"><?php echo $translate->_("Course"); ?></label>
                        <div class="controls">
                            <select id="course" name="course_id">
                                <option><?php echo $translate->_("Please select a course"); ?></option>
                                <?php
                                foreach ($courses as $course) {
                                    ?>
                                    <option value="<?php echo $course->getID(); ?>"><?php echo $course->getCourseCode() . " - " . $course->getCourseName(); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                <?php
                }
                ?>
                <?php
                $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                if ($curriculum_types) {
                    ?>
                    <div class="control-group">
                        <label class="control-label form-required" for="curriculum_period"><?php echo $translate->_("Curriculum Period"); ?></label>
                        <div class="controls">
                            <select id="curriculum_period" name="cperiod_id">
                                <option><?php echo $translate->_("Please select a curriculum period"); ?></option>
                                <?php
                                foreach ($curriculum_types as $curriculum_type) {
                                    $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                                    if ($curriculum_periods) {
                                        echo "<optgroup label=\"" . html_encode($curriculum_type->getCurriculumTypeName()) . "\">";
                                        foreach ($curriculum_periods as $curriculum_period) {
                                            ?>
                                            <option value="<?php echo $curriculum_period->getCperiodID(); ?>"><?php echo ($curriculum_period->getCurriculumPeriodTitle() ? $curriculum_period->getCurriculumPeriodTitle() : ""); ?> - <?php echo date("F jS, Y", $curriculum_period->getStartDate()) . " to " . date("F jS, Y", $curriculum_period->getFinishDate()); ?></option>
                                        <?php
                                        }
                                        echo "</optgroup>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </form>
        </div>
        <div class="modal-footer">
            <a href="#" class="btn btn-default" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
            <a href="#" id="new-draft-btn" class="btn btn-primary"><?php echo $translate->_("Add"); ?></a>
        </div>
    </div>
    <?php

}