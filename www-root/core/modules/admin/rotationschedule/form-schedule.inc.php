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
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    if (($PROCESSED["schedule_id"] && $SECTION == "edit") || ($SECTION == "add")) {

        if ($STEP == 2) {
            if (isset($_POST["title"]) && $tmp_input = clean_input($_POST["title"], array("trim", "striptags"))) {
                $PROCESSED["title"] = $tmp_input;
            } else {
                add_error("A title is required.");
            }

            if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                $PROCESSED["description"] = $tmp_input;
            }

            if (isset($_POST["code"]) && $tmp_input = clean_input($_POST["code"], array("trim", "underscores", "module"))) {
                $PROCESSED["code"] = $tmp_input;
            }

            if (isset($_POST["start_date"]) && $tmp_input = clean_input(strtotime($_POST["start_date"]), "int")) {
                $PROCESSED["start_date"] = $tmp_input;
            } else {
                add_error("A start date is required.");
            }

            if (isset($_POST["end_date"]) && $tmp_input = clean_input(strtotime($_POST["end_date"] . " 23:59:59"), "int")) {
                $PROCESSED["end_date"] = $tmp_input;
            } else {
                add_error("An end date is required.");
            }

            if (!$ERROR) {
                $method = "update";
                if ($SECTION == "add") {
                    $PROCESSED["schedule_parent_id"] = $PROCESSED["schedule_id"];
                    unset($PROCESSED["schedule_id"]);
                    $PROCESSED["schedule_type"] = "rotation_block";
                    $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                    $PROCESSED["created_date"] = time();
                    $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                    $SCHEDULE = new Models_Schedule($PROCESSED);
                    $method = "insert";
                } else {
                    $SCHEDULE = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
                    $SCHEDULE->fromArray($PROCESSED);
                }

                if ($SCHEDULE->{$method}()) {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully %s <strong>%s</strong>."), $method, $PROCESSED["title"]), $success);
                    $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . ($SECTION == "edit" ? $PROCESSED["schedule_id"] : $PROCESSED["schedule_parent_id"]);
                    header("Location: " . $url);
                    exit;
                }
            }
        }

        $schedule_view = new Views_Schedule_UserInterfaces($SCHEDULE);

        $draft = Models_Schedule_Draft::fetchRowByID($SCHEDULE->getDraftID());
        if ($draft && $draft->getStatus() == "draft") {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts", "title" => "My Drafts");
        }

        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $draft->getID(), "title" => $draft->getTitle());

        $schedule_breadcrumb = $SCHEDULE->getBreadCrumbData();
        if ($schedule_breadcrumb) {
            $schedule_breadcrumb = array_reverse($schedule_breadcrumb);
            foreach ($schedule_breadcrumb as $breadcrumb_data) {
                $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . $breadcrumb_data["schedule_id"], "title" => $breadcrumb_data["title"]);
            }
        }

        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        ?>
        <script type="text/javascript">
            var API_URL = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>";
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            jQuery(function ($) {

                $(".slot-row td a").on("click", function (e) {
                    $("#occupant-search").attr("data-schedule-slot-id", $(this).closest(".slot-row").data("slot-id"));
                });

                $("#slot-form").on("click", ".search-apply-button", function (e) {
                    return false;
                });

                $("#shift-slots").on("click", function (e) {
                    $.ajax({
                        url: $("#shift-slot-form").attr("action"),
                        data: $("#shift-slot-form").serialize(),
                        type: $("#shift-slot-form").attr("method"),
                        success: function (data) {
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse.status == "success") {
                                $.each(jsonResponse.data, function (i, v) {
                                    $(".start-" + v.slot_id).html(v.start_date);
                                    $(".end-" + v.slot_id).html(v.end_date);
                                });
                                $("#shift-blocks").modal("hide");
                            } else {
                                display_error(jsonResponse.data, "#shift-msgs");
                            }
                        }
                    });
                    e.preventDefault();
                });
                $("#shift-blocks").on("hidden", function (e) {
                    $("#shift-msgs").empty();
                    $("#number-of-days").val("1");
                    $("#shift-blocks input[type=radio]").removeProp("checked");
                    $("#shift-direction-future").prop("checked", "checked");
                });

                $("#slot").on("hidden", function(e) {
                    $("#slot-occupants").empty();
                    $("#slot-form input.audience").remove();
                });

                $("#occupant-search").audienceSelector({
                    "filter": "#contact-type",
                    "target": "#slot-occupants",
                    "content_type": "individual",
                    "content_target": "slot-id",
                    "api_url": "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule" ; ?>",
                    "delete_attr": "data-proxy-id",
                    "add_audience": false,
                    "min_chars": 0,
                    "api_params": {
                        "schedule_slot_id": $("#slot-id")
                    }
                });

                $("#slot-table").on("click", ".slot-row a", function () {
                    var slot_id = $(this).closest(".slot-row").data("slot-id");
                    clearSlotForm();
                    getSlotData(slot_id);
                    $("#slot_occupants_controls").show();
                });

                $("#add-slot").on("click", function () {
                    clearSlotForm();
                    $("#slot_occupants_controls").hide();
                });

                $("#save-slot").on("click", function () {
                    $.each($("#slot-occupants li"), function (i, v) {
                        var input = $(document.createElement("input")).attr({
                            "type": "hidden",
                            "name": "audience[]"
                        }).addClass("audience").val($(v).children().data("proxy-id"));
                        $("#slot-form").append(input);
                    });
                    $.ajax({
                        url: API_URL,
                        data: "method=save-slot&" + $("#slot-form").serialize(),
                        type: "POST",
                        success: function (data) {
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse.status = "success") {

                                var delete_col = $(document.createElement("td")).append(
                                    $(document.createElement("input")).attr({
                                        "type": "checkbox",
                                        "name": "delete[]"
                                    }).val(jsonResponse.data.schedule_slot_id)
                                );

                                var name_col = $(document.createElement("td")).append(
                                    $(document.createElement("a")).attr({
                                        "data-toggle": "modal",
                                        "href": "#slot"
                                    }).append(jsonResponse.data.slot_type)
                                );

                                var spaces_col = $(document.createElement("td")).append(
                                    $(document.createElement("a")).attr({
                                        "data-toggle": "modal",
                                        "href": "#slot"
                                    }).append(jsonResponse.data.slot_spaces)
                                );

                                var occupants_col = $(document.createElement("td")).append(
                                    $(document.createElement("a")).attr({
                                        "data-toggle": "modal",
                                        "href": "#slot"
                                    }).append(jsonResponse.data.slot_occupants.length)
                                );

                                var current_row = $("tr[data-slot-id=" + jsonResponse.data.schedule_slot_id + "]");

                                if (current_row.length >= 1) {
                                    current_row.empty().append(delete_col, name_col, spaces_col, occupants_col);
                                } else {
                                    var slot_row = $(document.createElement("tr")).addClass("slot-row").attr("data-slot-id", jsonResponse.data.schedule_slot_id);
                                    slot_row.append(delete_col, name_col, spaces_col, occupants_col);
                                    $("#slot-table tbody").append(slot_row);
                                }
                                $("#slot").modal("hide");
                            }
                        }
                    });
                });

                function clearSlotForm() {
                    $("#slot-occupants").empty();
                    $("#slot-type option").removeProp("selected");
                    $("#slot-spaces, #slot-id").val("");
                }

                function getSlotData(slot_id) {
                    $.ajax({
                        url: API_URL,
                        data: {"method": "get-slot-data", "slot_id": slot_id},
                        type: "GET",
                        success: function (data) {
                            var jsonResponse = JSON.parse(data);
                            if (jsonResponse.status = "success") {
                                $("#slot-id").val(slot_id);
                                $("#slot-type option[value=" + jsonResponse.data.slot_type_id + "]").prop("selected", "selected");
                                $("#slot-spaces").val(jsonResponse.data.slot_spaces);

                                if (jsonResponse.data.slot_type_id == 2) {
                                    if ($("#slot-course-container").hasClass("hide")) {
                                        $("#slot-course-container").removeClass("hide");
                                    }
                                    if (jsonResponse.data.course_id == null || jsonResponse.data.course_id.length > 0) {
                                        $("#slot-course").children("option[value=" + jsonResponse.data.course_id + "]").attr({"selected" : "selected"});
                                    }
                                } else {
                                    if (!$("#slot-course-container").hasClass("hide")) {
                                        $("#slot-course-container").addClass("hide");
                                    }
                                }

                                if (jsonResponse.data.slot_occupants.length >= 1) {
                                    $.each(jsonResponse.data.slot_occupants, function (i, v) {
                                        var occupant_li = $(document.createElement("li")).attr(
                                            {"data-saudience-id": v.id, "data-proxy-id": v.proxy_id}
                                        );
                                        var occupant_remove = $(document.createElement("a"))
                                            .addClass("remove-permission")
                                            .attr("data-proxy-id", v.proxy_id)
                                            .attr("href", "#")
                                            .append($(document.createElement("i")).addClass("icon-remove-circle"))
                                            .on("click", function () {
                                                var link = $(this);
                                                $.ajax({
                                                    url: API_URL,
                                                    data: {
                                                        "method": "remove-occupant",
                                                        "saudience_id": link.parent().data("saudience-id")
                                                    },
                                                    type: "POST",
                                                    success: function (data) {
                                                        link.parent("li").remove();
                                                    }
                                                });
                                            });
                                        occupant_li.append(occupant_remove, " ", v.fullname);
                                        $("#slot-occupants").append(occupant_li);
                                    });
                                }
                            }
                        }
                    });
                }

                $("#delete-slot").on("click", function (e) {
                    var url = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=delete&schedule_id=".$PROCESSED["schedule_id"]; ?>";
                    $("input[name=step]").attr("value", "1");
                    $("#form-slots").attr("action", url).submit();
                });

                $("#slot-type").on("change", function(e) {
                    if ($(this).val() == "2") {
                        $("#slot-course-container").removeClass("hide");
                    } else {
                        $("#slot-course-container").addClass("hide");
                    }
                });

            })
        </script>
        <?php
        $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=" . $SECTION . "&schedule_id=" . $PROCESSED["schedule_id"];
        ?>
        <form action="<?php echo $url; ?>" method="POST" class="form-horizontal" id="form-slots">
            <h2>General Information</h2>
            <?php

            Entrada_Utilities_Flashmessenger::displayMessages();

            echo $schedule_view->renderScheduleInformation();

            if ($SECTION == "edit") {
                $children = $SCHEDULE->getChildren();
                if ($children) {

                    $grouped_children = array();
                    // Group by block length and then sort by the "largest" block length.
                    foreach ($children as $child) {
                        if ($child->getBlockTypeID()) {
                            if (array_key_exists($child->getBlockTypeID(), $grouped_children)) {
                                $grouped_children[$child->getBlockTypeID()][] = $child;
                            } else {
                                $grouped_children[$child->getBlockTypeID()] = array($child);
                            }
                        }
                    }
                    krsort($grouped_children);

                    ?>
                    <div class="row-fluid space-below">
                        <button href="#shift-blocks" class="btn pull-right" data-toggle="modal"><?php echo $translate->_("Shift Blocks"); ?></button>
                    </div>
                    <?php
                    if ($grouped_children) {
                        foreach ($grouped_children as $block_type_id => $length_children) {
                            $block_type = Models_BlockType::fetchRowByID($block_type_id);
                            if ($block_type) {
                                ?>
                                <h2><?php echo $block_type->getName() . $translate->_(" Blocks"); ?></h2>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th width="5%"></th>
                                        <th><?php echo $translate->_("Name"); ?></th>
                                        <th width="8%"><?php echo $translate->_("Slots"); ?></th>
                                        <th width="12%"><?php echo $translate->_("Start"); ?></th>
                                        <th width="12%"><?php echo $translate->_("Finish"); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($length_children as $child) {
                                        $slots = Models_Schedule_Slot::fetchAllByScheduleID($child->getID());
                                        $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . $child->getID();
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="delete[]" value="<?php echo $child->getID(); ?>"/>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url; ?>"><?php echo html_encode($child->getTitle()); ?></a>
                                            </td>
                                            <td><a href="<?php echo $url; ?>"><?php echo count($slots); ?></a></td>
                                            <td>
                                                <a href="<?php echo $url; ?>" class="start-<?php echo $child->getID(); ?>"><?php echo html_encode(date("Y-m-d", $child->getStartDate())); ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url; ?>" class="end-<?php echo $child->getID(); ?>"><?php echo html_encode(date("Y-m-d", $child->getEndDate())); ?></a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            }
                        }
                    }
                } else {
                    ?>
                    <h2><?php echo $translate->_("Slots"); ?></h2>
                    <div class="row-fluid space-below">
                        <input type="submit" value="<?php echo $translate->_("Delete"); ?>"
                               class="btn btn-danger"/>
                        <a href="#slot" id="add-slot" data-toggle="modal" class="btn btn-success pull-right"><?php echo $translate->_("Add Slot"); ?></a>
                    </div>
                    <table class="table table-bordered table-striped" id="slot-table">
                        <thead>
                        <tr>
                            <th width="5%"></th>
                            <th><?php echo $translate->_("Slot Type"); ?></th>
                            <th width="12%" class="text-center"><?php echo $translate->_("Spaces"); ?></th>
                            <th width="5%" class="text-center"><i class="icon-user"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $slots = Models_Schedule_Slot::fetchAllByScheduleID($SCHEDULE->getID());
                        if ($slots) {
                            foreach ($slots as $slot) {
                                $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-slot&id=" . $slot->getID();
                                $slot_type = $slot->getSlotType();
                                ?>
                                <tr class="slot-row" data-slot-id="<?php echo $slot->getID(); ?>">
                                    <td><input type="checkbox" name="delete[]" value="<?php echo $slot->getID(); ?>"/>
                                    </td>
                                    <td><a href="#slot"
                                           data-toggle="modal"><?php echo $slot_type["slot_type_description"]; ?></a>
                                    </td>
                                    <td class="text-center"><a href="#slot"
                                                               data-toggle="modal"><?php echo $slot->getSlotSpaces(); ?></a>
                                    </td>
                                    <td class="text-center"><a href="#slot"
                                                               data-toggle="modal"><?php echo count($slot->getAudience()); ?></a>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            ?>
                            <tr class="no-slots">
                                <td colspan="4"><?php echo $translate->_("There are currently not slots assigned to this"); ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                <?php
                }
                ?>

            <?php
            }
            ?>
            <input type="hidden" name="step" value="2"/>

            <div class="row-fluid space-below">
                <?php
                $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=";
                if ($SCHEDULE->getScheduleParentID()) {
                    $url .= "edit&schedule_id=" . $SCHEDULE->getScheduleParentID();
                } else {
//                    if ($draft && $draft->getStatus() == "live") {
//                        $url .= "index";
//                    } else {
                        $url .= "edit-draft&draft_id=" . $SCHEDULE->getDraftID();
//                    }
                }
                ?>
                <a href="<?php echo $url; ?>"
                   class="btn btn-default"><?php echo $translate->_("Back"); ?></a>

                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>"/>

            </div>
        </form>

        <div id="shift-blocks" class="modal hide fade">
            <div class="modal-header">
                <h1><?php echo $translate->_("Shift Blocks"); ?></h1>
            </div>
            <div class="modal-body">
                <div id="shift-msgs"></div>
                <p></p><?php echo $translate->_("This will move all blocks in the schedule forwards or backwards by the number of days specified."); ?></p>
                <form id="shift-slot-form" class="form-horizontal"
                      action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=api-schedule"
                      method="POST">
                    <input type="hidden" name="schedule_id" value="<?php echo $PROCESSED["schedule_id"]; ?>"/>
                    <input type="hidden" name="method" value="shift-blocks"/>

                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Number of Days"); ?>:</label>

                        <div class="controls">
                            <input type="text" id="number-of-days" name="number_of_days" class="span2" value="1"/>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Shift Direction"); ?>:</label>

                        <div class="controls">
                            <div class="radio">
                                <label><input type="radio" id="shift-direction-future" name="shift_direction"
                                              value="future" checked="checked"/> <?php echo $translate->_("Shift blocks into the future"); ?></label>
                            </div>
                            <div class="radio">
                                <label><input type="radio" id="shift-direction-past" name="shift_direction"
                                              value="past"/> <?php echo $translate->_("Shift blocks into the past"); ?></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-default" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                <a href="#" class="btn btn-primary" id="shift-slots"><?php echo $translate->_("Shift"); ?></a>
            </div>
        </div>
        <div id="slot" class="modal hide fade">
            <div class="modal-header">
                <h1><?php echo $translate->_("Slot Details"); ?></h1>
            </div>
            <div class="modal-body" style="overflow:inherit;">
                <form id="slot-form" action="" class="form-horizontal" method="POST">
                    <input type="hidden" name="slot_id" id="slot-id" value=""/>
                    <input type="hidden" name="schedule_id" value="<?php echo $SCHEDULE->getID(); ?>"/>

                    <div class="control-group">
                        <label class="control-label" for="slot-type"><?php echo $translate->_("Slot Type"); ?></label>

                        <div class="controls">
                            <select name="slot_type" id="slot-type">
                                <?php
                                $slot_types = Models_Schedule_Slot::getSlotTypes();
                                if ($slot_types) {
                                    foreach ($slot_types as $slot_type) {
                                        ?>
                                        <option value="<?php echo $slot_type["slot_type_id"]; ?>"><?php echo $slot_type["slot_type_description"]; ?></option>
                                    <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="slot-spaces"><?php echo $translate->_("Slot Spaces"); ?></label>

                        <div class="controls">
                            <input class="span2" type="text" id="slot-spaces" name="slot_spaces" value=""/>
                        </div>
                    </div>
                    <div class="control-group" id="slot_occupants_controls">
                        <label class="control-label" for="occupant-search"><?php echo $translate->_("Slot Occupants"); ?></label>

                        <div class="controls">
                            <input type="text" id="occupant-search" name="search_value" />
                            <ul class="unstyled" id="slot-occupants"></ul>
                        </div>
                    </div>
                    <?php
                    $courses = Models_Course::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($courses) {
                    ?>
                    <div class="control-group hide" id="slot-course-container">
                        <label class="control-label" for="course"><?php echo $translate->_("Course"); ?></label>
                        <div class="controls">
                            <select id="slot-course" name="slot_course">
                                <option value="0"><?php echo $translate->_("Available to all courses"); ?></option>
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
                </form>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-default"
                   data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                <a href="#" class="btn btn-primary"
                   id="save-slot"><?php echo $translate->_("Save"); ?></a>
            </div>
        </div>
    <?php
    } else {
        application_log("error", "A user attempted to edit a schedule using an invalid ");
        echo display_error($translate->_("An invalid schedule ID was provided."));
    }
}