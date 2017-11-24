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

    if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], "int")) {
        $PROCESSED["proxy_id"] = $tmp_input;
        $user = Models_User::fetchRowByID($PROCESSED["proxy_id"]);
    }

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    if (isset($PROCESSED["proxy_id"]) && $user) {

        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&proxy_id=" . $PROCESSED["proxy_id"], "title" => $user->getFullname(false));

        $tracked_leave = array();

        switch ($STEP) {
            case 3 :
                if (isset($request["delete_btn"])) {
                    if (isset($request["delete"]) && is_array($request["delete"])) {
                        $deleted = 0;
                        foreach ($request["delete"] as $leave_id => $delete) {
                            $leave_id = clean_input($leave_id, "int");
                            if ($leave_id) {
                                $tmp_leave = Models_Leave_Tracking::fetchRowByID($leave_id);
                                if ($tmp_leave) {
                                    $tmp_leave->fromArray(array(
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getActiveID(),
                                        "deleted_date" => time()
                                    ));
                                    if ($tmp_leave->update()) {
                                        $deleted++;
                                    }
                                }
                            }
                        }

                        $total_leave_days = Models_Leave_Tracking::fetchLeaveDayTotalByProxyID($PROCESSED["proxy_id"]);

                        if ($deleted > 0 && $total_leave_days > 0) {
                            add_success(sprintf($translate->_("Successfully deleted %s leave entries."), $deleted));
                            Header("Location: ". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&proxy_id=" . $PROCESSED["proxy_id"]);
                        } else {
                            Header("Location: ". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE);
                        }

                        $STEP = 1;
                    } else {
                        add_error($translate->_("No leaves selected for deletion"));
                        $tracked_leave = Models_Leave_Tracking::fetchAllByProxyID($PROCESSED["proxy_id"]);
                        $STEP = 1;
                    }
                }

            break;
            case 2 :
                if (isset($request["delete_btn"])) {
                    if (isset($request["delete"]) && is_array($request["delete"])) {
                        foreach ($request["delete"] as $leave_id => $delete) {
                            $leave_id = clean_input($leave_id, "int");
                            if ($leave_id) {
                                $tracked_leave[] = Models_Leave_Tracking::fetchRowByID($leave_id);
                            }
                        }
                        add_notice($translate->_("You have selected the following leave entries to delete. Please confirm using the button below."));
                    } else {
                        $tracked_leave = Models_Leave_Tracking::fetchAllByProxyID($PROCESSED["proxy_id"]);
                        $STEP = 1;
                    }
                }
            break;
            case 1 :
            default:
                $tracked_leave = Models_Leave_Tracking::fetchAllByProxyID($PROCESSED["proxy_id"]);
            break;
        }

        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery-ui.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/".$MODULE."/leave.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/rotationschedule/rotationschedule.css\" />";
        $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $JAVASCRIPT_TRANSLATIONS[] = "var leave_error = {};";
        $JAVASCRIPT_TRANSLATIONS[] = "leave_error.Display = '" . $translate->_("No leaves selected for deletion.") . "';";
        $HEAD[] = "<script type=\"text/javascript\">
                        var ENTRADA_URL = '". ENTRADA_URL ."';
                        var MODULE = \"".$MODULE."\";
                        var SUBMODULE = \"".$SUBMODULE."\";
                        var SECTION = \"".$SECTION."\";
                    </script>";

        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        $curriculum_periods = false;
        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                $periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                if ($periods) {
                    foreach ($periods as $period) {
                        $curriculum_periods[] = $period;
                    }
                }
            }
        }

        $assessments_base = new Entrada_Utilities_Assessments_Base();
        $assessments_base->getAssessmentPreferences("rotationschedule");
        $cperiod_id_preference = (isset($_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"]["leave"]["cperiod_id"]) ? $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"]["leave"]["cperiod_id"] : false);
        ?>
        <h1><?php echo $translate->_("Leave Tracking for") . " " . $user->getFullname(false) ; ?></h1>
        <?php Views_Schedule_UserInterfaces::renderScheduleNavTabs($SECTION); ?>
        <?php
            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_notice();
            }
            if ($SUCCESS) {
                echo display_success();
            }
        ?>
        <div id="no-selection-error"></div>
        <form class="new-leave-form" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&proxy_id=" . $PROCESSED["proxy_id"]; ?>" method="POST">
            <input type="hidden" name="step" value="<?php echo $STEP + 1; ?>" />
            <?php if ($STEP != 2) { ?>
                <div class="row-fluid">
                    <input type="text" id="current-leave-search" placeholder="<?php echo $translate->_("Search"); ?>" class="input-large search-icon">
                    <a href="#new-leave" data-toggle="modal" class="btn btn-success space-left new-leave-btn"><i class="icon-plus-sign icon-white"></i><?php echo $translate->_(" New Leave"); ?></a>
                    <div class="control-group pull-right">
                        <label for="learner-curriculum-period-select"><?php echo $translate->_("Curriculum Period: "); ?></label>
                        <select id="learner-curriculum-period-select">
                            <option value="0"><?php echo $translate->_("All"); ?></option>
                            <?php foreach ($curriculum_periods as $curriculum_period): ?>
                                <option value="<?php echo $curriculum_period->getCperiodID(); ?>" <?php echo ($cperiod_id_preference == $curriculum_period->getCperiodID() ? "selected=\"selected\"" : ""); ?> data-start-date="<?php echo date("Y-m-d", $curriculum_period->getStartDate())?>" data-end-date="<?php echo date("Y-m-d", $curriculum_period->getFinishDate())?>">
                                    <?php echo date("Y-m-d", $curriculum_period->getStartDate()) . " - " . date("Y-m-d", $curriculum_period->getFinishDate()) . ($curriculum_period->getCurriculumPeriodTitle() ? " " . $curriculum_period->getCurriculumPeriodTitle() : ""); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php } ?>
            <table class="table table-bordered table-striped leave-tracking-table" id="current-leave-table" data-colspan="6">
                <thead>
                    <tr>
                        <th width="25%"><?php echo $translate->_("Leave Type"); ?></th>
                        <th width="13%"><?php echo $translate->_("Days Used"); ?></th>
                        <th width="13%"><?php echo $translate->_("Start Date"); ?></th>
                        <th width="12%"><?php echo $translate->_("End Date"); ?></th>
                        <th width="32%"><?php echo $translate->_("Comments"); ?></th>
                        <th width="5%"><i class="icon-trash"></i></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($tracked_leave) {
                    foreach ($tracked_leave as $leave) {
                        $leave_type = Models_Leave_Type::fetchRowByID($leave->getTypeID());
                    ?>
                    <tr id="leave-id-<?php echo $leave->getID(); ?>" data-start-dates="<?php echo date("Y-m-d", $leave->getStartDate()); ?>" data-end-dates="<?php echo date("Y-m-d", $leave->getEndDate()); ?>">
                        <td><a href="#new-leave" class="edit-leave leave-type" data-leave-id="<?php echo html_encode($leave->getID()); ?>"><?php echo ($leave_type->getTypeValue() ? html_encode(ucwords($leave_type->getTypeValue())) : ""); ?></a></td>
                        <td><a href="#new-leave" class="edit-leave days-used" data-leave-id="<?php echo html_encode($leave->getID()); ?>"><?php echo $leave->getDaysUsed() ? $leave->getDaysUsed() : $translate->_("Please update."); ?></a></td>
                        <td><a href="#new-leave" class="edit-leave start-date" data-leave-id="<?php echo html_encode($leave->getID()); ?>"><?php echo date("Y-m-d", $leave->getStartDate()); ?></a></td>
                        <td><a href="#new-leave" class="edit-leave end-date" data-leave-id="<?php echo html_encode($leave->getID()); ?>"><?php echo date("Y-m-d", $leave->getEndDate()); ?></a></td>
                        <td><a href="#new-leave" class="edit-leave comments" data-leave-id="<?php echo html_encode($leave->getID()); ?>"><?php echo (strlen($leave->getComments()) < 30) ? $leave->getComments() : substr($leave->getComments(), 0, 27) . "..." ?></a></td>
                        <td><input type="checkbox" name="delete[<?php echo $leave->getID(); ?>]" value="<?php echo $leave->getID(); ?>" <?php echo $STEP == 2 ? "checked=\"checked\"" : ""; ?> /></td>
                    </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr class="empty">
                        <td colspan="6"><?php echo $translate->_("You have not entered any leave for this user."); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <div class="row-fluid">
                <?php if ($STEP == 2){ ?>
                    <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&proxy_id=" . $PROCESSED["proxy_id"]; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                <?php } else{ ?>
                    <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                <?php } ?>
                <input type="submit" value="<?php echo $translate->_("Delete"); ?>" class="btn btn-danger pull-right" name="delete_btn" id="delete_leave_btn"/>
            </div>
        </form>
        <?php
        Views_Schedule_UserInterfaces::renderLeaveBookingModal($PROCESSED["proxy_id"]);
    } else {
        echo display_error(sprintf($translate->_("An invalid user ID was provided. Please <a href=\"%s\">click here</a> return to the leave tracking index and try again."), ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE));
    }
}