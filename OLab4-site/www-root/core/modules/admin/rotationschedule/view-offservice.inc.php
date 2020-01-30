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

    if (isset($_GET["schedule_id"]) && $tmp_input = clean_input($_GET["schedule_id"], "int")) {
        $PROCESSED["schedule_id"] = $tmp_input;
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=drafts", "title" => "My Drafts");
    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["draft_id"] = $tmp_input;
        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
        if ($draft) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"], "title" => $draft->getTitle());
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=view-offservice&schedule_id=".$PROCESSED["schedule_id"]."&draft_id=" . $PROCESSED["draft_id"], "title" => $translate->_("Off Service Rotation"));
        }
    }

    echo "<h1>" . $translate->_("Off Service Rotation") . "</h1>";

    if ($PROCESSED["schedule_id"]) {

        $schedule = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
        $contacts = Models_Schedule_Draft_Author::fetchAllByDraftID($schedule->getDraftID());

        ?>
        <h2><?php echo $schedule->getTitle(); ?></h2>
        <form class="form-horizontal">
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Shortname"); ?>:</label>
                <div class="controls">
                    <input type="text" disabled="disabled" value="<?php echo $schedule->getCode(); ?>" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Contacts"); ?>:</label>
                <div class="controls">
                    <ul class="menu">
                        <?php
                        if ($contacts) {
                            foreach ($contacts as $contact) {
                                $contact_user = $contact->getUser();
                                if ($contact_user) { ?>
                                    <li class="user"><?php echo $contact_user->getFullname(false); ?></li>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </form>
        <h2><?php echo $translate->_("Rotation Blocks"); ?></h2>
        <?php
        $children = $schedule->getChildren();
        if ($children) {
            $i = 0;
            ?>
            <div class="row-fluid">
            <?php
            foreach ($children as $child) {
                $slots = Models_Schedule_Slot::fetchAllByScheduleID($child->getID());

                if ($i >= 4) {
                    $i = 0;
                    ?>
                    </div>
                    <div class="row-fluid">
                    <?php
                }

                if ($slots) {
                    $s = NULL;
                    if (count($slots) > 1) {
                        foreach ($slots as $slot) {
                            if ($slot->getSlotTypeID() == "2") {
                                $s = $slot;
                            }
                        }
                        if (is_null($s)) {
                            $s = $slots[0];
                        }
                    } else {
                        $s = $slots[0];
                    }

                    ?>
                    <div class="pull-left span3 well well-small <?php echo $s->getSlotTypeID() == "2" ? "" : "muted"; ?>">
                        <div class="row-fluid">
                            <h4 style="margin-top:0px;" class="pull-left <?php echo $s->getSlotTypeID() != "2" ? "muted" : ""; ?>"><?php echo $s->getSlotTypeID() == "1" ? "On Service" : "Off Service"; ?></h4>
                            <?php if ($s->getSlotTypeID() == "2") { ?>
                                <a href="#slot-info" data-slot-id="<?php echo $s->getID(); ?>" data-toggle="modal" class="btn btn-mini btn-default pull-right slot-info"><?php echo $translate->_("Info"); ?></a>
                            <?php } ?>
                        </div>
                        <div class="row-fluid"><div class="span4"><?php echo $translate->_("Start"); ?>:</div> <?php echo date("Y-m-d", $child->getStartDate()); ?></div>
                        <div class="row-fluid"><div class="span4"><?php echo $translate->_("End"); ?>:</div> <?php echo date("Y-m-d", $child->getEndDate()); ?></div>
                        <div class="row-fluid"><div class="span9"><?php echo $translate->_("Block #"); ?>:</div> <?php echo $child->getOrder(); ?></div>
                    </div>
                    <?php
                    $i++;
                }
            }
            ?>
            </div>
            <script type="text/javascript">
                var API_URL = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>";
                jQuery(function($) {
                    $(".slot-info").on("click", function(e) {
                        getSlotData($(this).data("slot-id"));
                        e.preventDefault();
                    });

                    function getSlotData(slot_id) {
                        $.ajax({
                            url: API_URL,
                            data: {"method": "get-slot-data", "slot_id": slot_id},
                            type: "GET",
                            success: function (data) {
                                var jsonResponse = safeParseJson(data, "Unknown Server Error");
                                if (jsonResponse.status == "success") {
                                    $("#slot-info .start-date").val(jsonResponse.data.start_date);
                                    $("#slot-info .end-date").val(jsonResponse.data.end_date);
                                    $(".learner-list").empty();
                                    if (jsonResponse.data.slot_occupants.length > 0) {
                                        $(jsonResponse.data.slot_occupants).each(function(i, v) {
                                            var learner_li = $(document.createElement("li")).addClass("user").html(v.fullname);
                                            $(".learner-list").append(learner_li);
                                        });
                                    } else {
                                        var no_learners = $(document.createElement("li")).html("No learners are attached.");
                                        $(".learner-list").append(no_learners);
                                    }
                                }
                            }
                        });
                    }
                });
            </script>
            <div id="slot-info" class="modal hide fade">
                <div class="modal-header">
                    <h1><?php echo $translate->_("Slot Info"); ?></h1>
                </div>
                <div class="modal-body">
                    <form action="" method="" class="form-horizontal" id="slot-info">
                        <div class="control-group">
                            <label class="control-label"><?php echo $translate->_("Start Date"); ?>:</label>
                            <div class="controls">
                                <input type="text" disabled="disabled" value="" class="start-date" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $translate->_("End Date"); ?>:</label>
                            <div class="controls">
                                <input type="text" disabled="disabled" value="" class="end-date" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $translate->_("Current Learners"); ?>:</label>
                            <div class="controls">
                                <ul class="menu learner-list"></ul>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="#" data-dismiss="modal" class="btn btn-default"><?php echo $translate->_("Close"); ?></a>
                </div>
            </div>
            <?php
        }

    } else {
        echo display_error($translate->_("The schedule ID that has been provided is invalid, please try again later."));
    }
    ?>
    <div class="row-fluid">
        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=". (isset($draft) ? "edit-draft&draft_id=".$draft->getID() : "drafts"); ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
    </div>
    <?php
}