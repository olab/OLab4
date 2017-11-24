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
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    } else if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    if (isset($_GET["schedule_parent_id"]) && $tmp_input = clean_input($_GET["schedule_parent_id"], "int")) {
        $PROCESSED["schedule_parent_id"] = $tmp_input;
    }

    $cperiod = false;
    if (isset($_GET["cperiod_id"]) && $tmp_input = clean_input($_GET["cperiod_id"], "int")) {
        $CPERIOD_ID = $tmp_input;
        $cperiod = Models_Curriculum_Period::fetchRowByID($CPERIOD_ID);
        $ctype = Models_Curriculum_Type::fetchRowByID($cperiod->getCurriculumTypeID());
        if ($ctype) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=edit&org=" . $ORGANISATION['organisation_id'] . "&type_id=" . $ctype->getID(), "title" => $ctype->getCurriculumTypeName());
        }
    }

    $error_elements = array();

    switch ($STEP) {
        case 2 :

            $schedule_type = "stream";
            if (isset($PROCESSED["schedule_parent_id"]) && $PROCESSED["schedule_parent_id"]) {
                $schedule_type = "block";
            }

            $schedule_data = array_merge($_POST, !isset($PROCESSED["id"]) ? array("created_date" => time(), "created_by" => $ENTRADA_USER->getActiveID()) : array(), array("schedule_type" => $schedule_type));

            $schedule_controller = new Controllers_Schedule($schedule_data);

            $results = $schedule_controller->save();

            if ($results instanceof Models_Schedule) {
                $PROCESSED["id"] = $results->getID();
                $PROCESSED["schedule_parent_id"] = $results->getScheduleParentID();
                Entrada_Utilities_Flashmessenger::addMessage("Successfully updated the schedule.", "success", $MODULE);
                $SECTION = "edit-schedule";

                $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=" . $SECTION . "&org=" . $ORGANISATION_ID . (isset($PROCESSED["id"]) ? "&id=" . $PROCESSED["id"] : "") . (isset($PROCESSED["schedule_parent_id"]) ? "&schedule_parent_id=" . $PROCESSED["schedule_parent_id"] : "")."&cperiod_id=".$CPERIOD_ID;
                header("Location: ".$url);
                exit;
            } else {
                if (is_array($results)) {
                    foreach ($results as $message_type => $messages) {
                        foreach ($messages as $message) {
                            foreach ($messages as $message_element => $message) {
                                Entrada_Utilities_Flashmessenger::addMessage($SCHEDULE_TEXT["edit-schedule"]["errors"][$message_element], "error", $MODULE);
                                $error_elements[$message_element] = $message;
                            }
                        }
                    }
                }
                $STEP = 1;
            }

        break;
    }

    switch ($STEP) {
        case 1 :
        default:

            $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.moment.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.fullcalendar.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $JQUERY[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.fullcalendar.min.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
            $JQUERY[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.fullcalendar.print.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"print\" />\n";

            $schedule = new Models_Schedule();
            $children = false;
            if (isset($PROCESSED["id"]) && $PROCESSED["id"]) {
                $schedule = Models_Schedule::fetchRowByID($PROCESSED["id"]);
                $children = Models_Schedule::fetchAllByOrgByTypeByParent($ORGANISATION_ID, $PROCESSED["id"]);
                $PROCESSED["schedule_parent_id"] = $schedule->getScheduleParentID();
            }

            if (isset($PROCESSED["schedule_parent_id"])) {
                $schedule_parent = Models_Schedule::fetchRowByID($PROCESSED["schedule_parent_id"]);
                if (!isset($PROCESSED["id"])) {
                    $schedule->fromArray(array("schedule_type" => $schedule_parent->getChildScheduleType(), "schedule_parent_id" => $PROCESSED["schedule_parent_id"]));
                }
            } else {
                $schedule->fromArray(array("start_date" => $cperiod->getStartDate(), "end_date" => $cperiod->getFinishDate()));
            }

            $schedule->fromArray(array("cperiod_id" => $CPERIOD_ID));

            $schedule_view = new Views_Schedule_UserInterfaces($schedule, (count($children) <= 0 || $children === false ? false : true));


            $breadcrumb_data = $schedule->getBreadCrumbData();
            if ($breadcrumb_data) {
                $breadcrumb_data = array_reverse($breadcrumb_data);
                foreach ($breadcrumb_data as $breadcrumb_element) {
                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."/schedule?section=edit-schedule&org=".$ORGANISATION['organisation_id']."&id=".$breadcrumb_element["schedule_id"], "title" => $breadcrumb_element["title"]);
                }
            }

            ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $("#auto-generate").on("click", function(e) {
                        $("input[name=generate_blocks]").val("1");
                        $("form.schedule-form").submit();
                    });

                    $(".datepicker").datepicker();

                    $('#schedules').fullCalendar({
                        header: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'month,agendaWeek,agendaDay'
                        },
                        timezone: 'America/New_York',
                        defaultDate: '<?php echo date("Y-m-d", $schedule->getStartDate()); ?>',
                        editable: false,
                        events: '<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/schedule?section=api-schedule&method=get-calendar-json&id=<?php echo (isset($PROCESSED["id"]) && $PROCESSED["id"] ? $PROCESSED["id"] : ""); ?>&org=<?php echo $ORGANISATION_ID; ?>'
                    });

                    $(".view-picker a").on("click", function(e) {
                        $($(this).attr("href")).removeClass("hide");
                        $($(this).siblings().attr("href")).addClass("hide");
                        if ($(this).attr("href") == "#schedules") {
                            $("#schedules").fullCalendar("render");
                        }
                        e.preventDefault();
                    })

                    $("#delete-btn").on("click", function(e) {
                        $("input[name=step]").val("1");
                        $(".schedule-form").attr("action", "<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/schedule?section=delete&org=<?php echo $ORGANISATION_ID; ?><?php echo isset($PROCESSED["id"]) ? "&id=" . $PROCESSED["id"] : ""; ?>");
                    });
                })
            </script>
            <form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/curriculumtypes?section=<?php echo $SECTION; ?>&org=<?php echo $ORGANISATION_ID; ?><?php echo isset($PROCESSED["id"]) ? "&id=" . $PROCESSED["id"] : ""; ?><?php echo isset($PROCESSED["schedule_parent_id"]) ? "&schedule_parent_id=" . $PROCESSED["schedule_parent_id"] : ""; ?>&cperiod_id=<?php echo $CPERIOD_ID; ?>" method="POST" class="form-horizontal schedule-form">

                <?php if (isset($PROCESSED["id"]) && !empty($PROCESSED["id"])) { ?>
                <input type="hidden" name="schedule_id" value="<?php echo $PROCESSED["id"]; ?>" />
                <?php } ?>
                <?php if (isset($PROCESSED["schedule_parent_id"]) && $schedule_parent) {
                    ?>
                    <input type="hidden" name="schedule_parent_id" value="<?php echo $PROCESSED["schedule_parent_id"]; ?>" />
                    <input type="hidden" name="schedule_type" value="<?php echo $schedule_parent->getChildScheduleType(); ?>" />
                <?php } else {
                    ?>
                    <input type="hidden" name="schedule_parent_id" value="<?php echo ($schedule->getScheduleParentID() ? $schedule->getScheduleParentID() : 0); ?>" />
                    <input type="hidden" name="schedule_type" value="<?php echo $schedule->getScheduleType() ? $schedule->getScheduleType() : "organisation"; ?>" />
                <?php } ?>
                <input type="hidden" name="cperiod_id" value="<?php echo $schedule->getCperiodID(); ?>" />
                <input type="hidden" name="organisation_id" value="<?php echo $ORGANISATION_ID; ?>" />
                <input type="hidden" name="step" value="2" />
                <input type="hidden" name="generate_blocks" value="0" />
                <h2><?php echo $SCHEDULE_TEXT["edit-schedule"]["schedule_information"]; ?></h2>
                <?php
                Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
                echo $schedule_view->renderScheduleInformation(array_keys($error_elements));
                ?>
                <div class="row-fluid">
                    <?php if (isset($PROCESSED["schedule_parent_id"]) && $schedule_parent && empty($children)) { ?>
                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/curriculumtypes?section=edit-schedule&org=<?php echo $ORGANISATION_ID; ?>&id=<?php echo $PROCESSED["schedule_parent_id"]; ?>&cperiod_id=<?php echo $CPERIOD_ID; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                    <?php } ?>
                    <div class="btn-group pull-right">
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Save"); ?>" />
                        <?php if (count($children) <= 0 && $schedule->getScheduleType() == "stream") { ?>
                            <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" id="auto-generate">Auto-generate Blocks</a></li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
                <?php
                if (EDIT_SCHEDULE === true) {
                    switch ($schedule->getScheduleType()) {
                        case "organisation" :
                        case "academic_year" :
                        case "stream" :
                            ?>
                            <h2><?php echo $SCHEDULE_TEXT["edit-schedule"]["children_" . $schedule->getScheduleType() . "_title"]; ?></h2>
                            <div class="row-fluid space-below">
                                <input type="submit" class="btn btn-danger" id="delete-btn" value="<?php echo $translate->_("Delete"); ?>" />
                                <div class="view-picker btn-group pull-right" data-toggle="buttons-radio">
                                    <a href="#schedule-table" class="btn active"><i class="icon-list"></i></a>
                                    <a href="#schedules" class="btn"><i class="icon-calendar"></i></a>
                                </div>
                            </div>
                            <table id="schedule-table" class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th>Name</th>
                                    <th width="15%">Start</th>
                                    <th width="15%">End</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                if ($children) {
                                    foreach ($children as $child) {
                                        $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=edit-schedule&org=" . $ORGANISATION_ID . "&id=" . $child->getID() . "&cperiod_id=" . $CPERIOD_ID;
                                        ?>
                                        <tr>
                                            <td><input type="checkbox" name="delete[]" value="<?php echo $child->getID(); ?>" /></td>
                                            <td>
                                                <a href="<?php echo $url; ?>"><?php echo $child->getTitle(); ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url; ?>"><?php echo date("Y-m-d", $child->getStartDate()); ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url; ?>"><?php echo date("Y-m-d", $child->getEndDate()); ?></a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="4"><?php echo $SCHEDULE_TEXT["edit-schedule"]["no_children"]; ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                            <div id="schedules" class="hide"></div>
                            <div class="row-fluid">
                                <?php
                                    $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=edit-schedule&org=" . $ORGANISATION_ID . "&id=" . $schedule->getScheduleParentID() . "&cperiod_id=" . $CPERIOD_ID   ;
                                    if (!$schedule->getScheduleParentID()) {
                                        $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=edit&org=" . $ORGANISATION_ID . "&type_id=" . $cperiod->getCurriculumTypeID();
                                    }
                                ?>
                                <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                                <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/curriculumtypes?section=add-schedule&org=<?php echo $ORGANISATION_ID; ?>&schedule_parent_id=<?php echo (isset($PROCESSED["id"]) && $PROCESSED["id"] ? $PROCESSED["id"] : 0); ?>&cperiod_id=<?php echo $CPERIOD_ID; ?>" class="btn btn-primary pull-right"><?php echo $translate->_("Add"); ?></a>
                            </div>
                            <?php
                        break;
                    }
                }
                ?>
            </form>
            <?php
        break;
    }

}