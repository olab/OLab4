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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("unitcontent", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var ORGANISATION = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $SUBMODULE ."/". $SUBMODULE .".js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $SUBMODULE .".css\" />";
    $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDCourseID($COURSE->getCurriculumTypeID(), $COURSE->getID());
    $view = function () use ($translate, $curriculum_periods, $MODULE, $SUBMODULE, $PREFERENCES, $COURSE_ID) {
        ?>
        <div id="msgs"></div>
        <div class="row-fluid">
            <div class="span12">
                <div class="span5">
                    <h1><?php echo $translate->_("Course Units");?></h1>
                </div>
                <div class="span7 no-printing">
                    <?php if ($curriculum_periods): ?>
                        <form class="pull-right form-horizontal no-printing" style="margin-bottom:0; margin-top:18px">
                            <div class="control-group">
                                <label for="cperiod_select" class="control-label muted unit-index-label"><?php echo $translate->_("Period"); ?>:</label>
                                <div class="controls unit-index-select">
                                    <select style="width:100%" id="cperiod_select" name="cperiod_select">
                                        <?php foreach ($curriculum_periods as $period): ?>
                                            <option value="<?php echo html_encode($period->getID());?>" <?php echo (isset($PREFERENCES["selected_curriculum_period"]) && $PREFERENCES["selected_curriculum_period"] == $period->getID() ? "selected=\"selected\"" : ""); ?>>
                                                <?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate())); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="display-notice hide" id="no-curriculum-msg">
            <strong>Please note</strong> that the highlighted <?php echo $translate->_("Course Units"); ?> below are not currently assigned to a curriculum period and will continue to be displayed until they are assigned. To assign them to a curriculum period select the row(s) and click the &quot;Assign Period&quot; button.
            <div class="pull-right space-above">
                <a href="#" id="assign-period-modal-but" data-toggle="modal" class="period-assign btn btn-warning active"><?php echo $translate->_("Assign Period"); ?></a>
            </div>
            <div class="clearfix"></div>
        </div>
        <div id="unit-items-container">
            <input type="hidden" id="course-id" value="<?php echo $COURSE_ID; ?>">
            <div id="search-bar" class="search-bar">
                <div class="row-fluid space-below medium">
                    <div class="pull-right">
                        <a href="#delete-units-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Units"); ?></a>
                        <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>?section=add&id=<?php echo $COURSE_ID ?>" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Units") ?></a>
                    </div>
                </div>
            </div>
            <div id="units-msgs">
                <div id="units-items-loading" class="enrolment-loading hide">
                    <p><?php echo $translate->_("Loading Units..."); ?></p>
                    <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>" />
                </div>
            </div>
            <div id="unit-table-container">
                <table id="units-table" class="table table-bordered table-striped">
                    <thead>
                        <th width="5%"><input type="checkbox" id="checkAll" name="checkAll"></th>
                        <th width="95%" class="general"><?php echo $translate->_("Unit"); ?><i class="fa fa-sort unit-sort" aria-hidden="true" data-name="title" data-order=""></i></th>
                    </thead>
                    <tbody>
                        <tr id="no-items">
                            <td colspan="5"><?php echo $translate->_("No Units to display"); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="item-detail-container" class="hide"></div>
        </div>
        <div id="delete-units-modal" class="modal hide fade">
            <form id="delete-units-modal-item" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-units"; ?>" method="POST" style="margin:0px;">
                <div class="modal-header"><h1><?php echo $translate->_("Delete Units"); ?></h1></div>
                <div class="modal-body">
                    <div id="no-units-selected" class="hide">
                        <p><?php echo $translate->_("No Unit Selected to delete."); ?></p>
                    </div>
                    <div id="units-selected" class="hide">
                        <p><?php echo $translate->_("Please confirm that you would like to proceed with the selected Unit(s)?"); ?></p>
                        <div id="delete-units-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input id="delete-units-modal-delete" type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div id="assign-period-modal" class="modal hide fade">
            <form id="assign-period-modal-item" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-units"; ?>" method="POST" style="margin:0px;">
                <div class="modal-header"><h1><?php echo $translate->_("Assign Curriculum Period"); ?></h1></div>
                <div class="modal-body">
                    <div id="error-units-selected" class="hide">
                        <p><?php echo $translate->_("Please select only Unit(s) without curriculum period assigned."); ?></p>
                    </div>
                    <div id="assign-units-selected" class="hide">
                        <p><?php echo $translate->_("Please confirm that you would like to assign the curriculum period to the selected Unit(s)?"); ?></p>
                        <div id="assign-units-container"></div>
                        <div class="control-group">
                            <label for="cperiod_select" class="control-label unit-assign-label"><?php echo $translate->_("Period"); ?>:</label>
                            <div class="controls unit-assign-select">
                                <select id="cperiod_assign_select" name="cperiod_assign_select" class="span10">
                                    <?php foreach ($curriculum_periods as $period): ?>
                                        <option value="<?php echo html_encode($period->getID());?>" <?php echo (isset($PREFERENCES["selected_curriculum_period"]) && $PREFERENCES["selected_curriculum_period"] == $period->getID() ? "selected=\"selected\"" : ""); ?>>
                                            <?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate())); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input id="assign-units-modal-assign" type="submit" class="btn btn-info" value="<?php echo $translate->_("Assign"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div class="row-fluid">
            <a id="load-units" class="btn btn-block"><?php echo $translate->_("Load More Units"); ?> <span class="bleh"></span></a>
        </div>
        <?php
    };
    $view();
}
/* vim: set expandtab: */
