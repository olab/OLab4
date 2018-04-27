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

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        $tracked_vacation = Models_Leave_Tracking::fetchAllGroupedByProxyID($ENTRADA_USER->getID());
    } else {
        $tracked_vacation = Models_Leave_Tracking::fetchAllByMyCourses($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());
    }

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\">
                    var ENTRADA_URL = '". ENTRADA_URL ."';
                    var MODULE = \"".$MODULE."\";
                    var SUBMODULE = \"".$SUBMODULE."\";
                    var SECTION = \"".$SECTION."\";
                </script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery-ui.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/".$MODULE."/leave.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/rotationschedule/rotationschedule.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
    $curriculum_periods = array();
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

    <h1><?php echo $translate->_("Leave Tracking"); ?></h1>
    <?php Views_Schedule_UserInterfaces::renderScheduleNavTabs($SECTION); ?>
    <div class="row-fluid">
        <input type="text" id="leave-search" placeholder="<?php echo $translate->_("Search"); ?>" class="input-large search-icon">
        <a href="#new-leave" data-toggle="modal" class="btn btn-success space-left new-leave-btn"><i class="icon-plus-sign icon-white"></i>&nbsp;<?php echo $translate->_("New Leave"); ?></a>
        <div class="control-group pull-right">
            <label for="learner-curriculum-period-select"><?php echo $translate->_("Curriculum Period"); ?>: </label>
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
    <table class="table table-bordered table-striped leave-tracking-table" id="leave-table" data-colspan="3">
        <thead>
            <tr>
                <th><?php echo $translate->_("First name"); ?></th>
                <th><?php echo $translate->_("Last name"); ?></th>
                <th width="18%"><?php echo $translate->_("Total Leave Days"); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($tracked_vacation) {
            foreach ($tracked_vacation as $vacation) {
                $user = Models_User::fetchRowByID($vacation->getProxyID());
                if ($user) {
                    $tracked_leave = Models_Leave_Tracking::fetchAllByProxyID($vacation->getProxyID());
                    $selected_cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id_preference);
                    $user_start_dates = array();
                    $user_end_dates = array();
                    $total_days_array = array();
                    $total_days_array[] = Models_Leave_Tracking::fetchLeaveDayTotalByProxyID($vacation->getProxyID());
                    $total_days = 0;

                    foreach ($tracked_leave as $leave) {
                        $user_start_dates[] = date("Y-m-d", $leave->getStartDate());
                        $user_end_dates[] = date("Y-m-d", $leave->getEndDate());
                    }

                    foreach($curriculum_periods as $cperiod) {
                        $total_days = Models_Leave_Tracking::fetchLeaveDayTotalByProxyIDDateRange($vacation->getProxyID(), $cperiod->getStartDate(), $cperiod->getFinishDate());
                        if (!$total_days) {
                            $total_days = 0;
                        }
                        array_push($total_days_array, $total_days);
                    }

                    $url = ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=user&proxy_id=" . $vacation->getProxyID();
                    ?>
                    <tr data-start-dates="<?php echo implode(",", $user_start_dates); ?>" data-end-dates="<?php echo implode(",", $user_end_dates); ?>" data-total-days="<?php echo implode(",", $total_days_array); ?>">
                        <td><a href="<?php echo $url; ?>"><?php echo html_encode($user->getFirstname()); ?></a></td>
                        <td><a href="<?php echo $url; ?>"><?php echo html_encode($user->getLastname()); ?></a></td>
                        <td><a class="total-days-count" href="<?php echo $url; ?>"><?php echo $total_days ? $total_days : $translate->_("Please update."); ?></a></td>
                    </tr>
                    <?php
                }
            }
        }
        ?>
        </tbody>
    </table>
    <div class="row-fluid">
        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
    </div>
    <?php
    Views_Schedule_UserInterfaces::renderLeaveBookingModal(NULL, "index");
}