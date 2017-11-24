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
 * The default file that is loaded when /admin/assessments/items is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
*/


if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var ORGANISATION = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $SUBMODULE ."/". $SUBMODULE .".js\"></script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.dataTables.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
    ?>
    
    <?php
    $course = Models_Course::get($COURSE_ID);

    if ($course) {

        echo "<h1 id=\"page-top\">" . $course->getFullCourseTitle() . "</h1>";

        courses_subnavigation($course->toArray(), "groups");
        $curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course->getCurriculumTypeID(), $course->getID());

        switch ($STEP) {
            case 2 :

                if (isset($_POST["element_type"]) && $tmp_input = clean_input($_POST["element_type"], array("trim", "striptags"))) {
                    $PROCESSED["element_type"] = $tmp_input;
                }

                if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], "int")) {
                    $PROCESSED["id"] = $tmp_input;
                }

                if (isset($_POST["items"]) && is_array($_POST["items"])) {
                    $PROCESSED["items"] = array_filter($_POST["items"], function ($id) {
                        return (int)$id;
                    });
                } else {
                    add_error($SECTION_TEXT["no_items_selected"]);
                    $STEP = 1;
                }

                break;
        }


        switch ($STEP) {
            case 2 :
                if ($ERROR) {
                    echo display_error();
                }
                if ($SUCCESS) {
                    echo display_success();
                }
                $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "\\'', 5000)";
                break;
            case 1 :
            default :
                if ($ERROR) {
                    echo display_error();
                }
                if ($SUCCESS) {
                    echo display_success();
                }
                $curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course->getCurriculumTypeID(), $course->getID());
                if ($curriculum_periods) {
                    ?>
                    <div id="msgs"></div>
                    <div class="row-fluid">
                        <div class="span12">
                            <div class="span5">
                                <h1 class="muted"><?php echo $translate->_("Course Groups"); ?></h1>
                            </div>
                            <div class="span7 no-printing">
                                <?php
                                if ($curriculum_periods) { ?>
                                    <form class="pull-right form-horizontal no-printing"
                                          style="margin-bottom:0; margin-top:18px">
                                        <div class="control-group">
                                            <label for="cperiod_select" class="control-label muted group-index-label">Period:</label>
                                            <div class="controls group-index-select">
                                                <select style="width:100%" id="cperiod_select" name="cperiod_select">
                                                    <?php
                                                    foreach ($curriculum_periods as $period) { ?>
                                                        <option
                                                            value="<?php echo html_encode($period->getID()); ?>" <?php echo(isset($PREFERENCES["selected_curriculum_period"]) && $PREFERENCES["selected_curriculum_period"] == $period->getID() ? "selected=\"selected\"" : ""); ?>>
                                                            <?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate())) . " to " . date("F jS, Y", html_encode($period->getFinishDate())); ?>
                                                        </option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </form>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="display-notice hide" id="no-curriculum-msg">
                        <strong>Please note</strong> that the highlighted course groups below are not currently assigned
                        to a curriculum period and will continue to be displayed until they are assigned. To assign them
                        to a curriculum period select the row(s) and click the &quot;Assign Period&quot; button.
                        <div class="pull-right space-above">
                            <a href="#" id="assign-period-modal-but" data-toggle="modal"
                               class="period-assign btn btn-warning active"><?php echo $translate->_("Assign Period"); ?></a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div id="assessment-items-container">
                        <form id="form-search" class="form-search"
                              action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?step=2"; ?>" method="POST">
                            <input type="hidden" id="element_type" name="element_type"
                                   value="<?php echo(isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>"/>
                            <input type="hidden" id="id" name="id"
                                   value="<?php echo(isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>"/>
                            <input type="hidden" id="form_id" name="form_id"
                                   value="<?php echo(isset($PROCESSED["form_id"]) ? $PROCESSED["form_id"] : ""); ?>"/>
                            <div id="search-bar" class="search-bar">
                                <div class="row-fluid space-below medium">
                                    <div class="pull-right">
                                        <a href="#delete-groups-modal" data-toggle="modal"
                                           class="btn btn-danger space-right"><i
                                                class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Groups"); ?>
                                        </a>
                                        <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>?section=add&id=<?php echo $COURSE_ID ?>"
                                           class="btn btn-success pull-right"><i
                                                class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Groups") ?>
                                        </a>
                                    </div>
                                    <div id="enrolment-options" class="btn-group">
                                        <a id="download-csv" class="btn" href="#"><i
                                                class="icon-file"></i> <?php echo $translate->_("Download as CSV"); ?>
                                        </a>
                                        <button class="btn dropdown-toggle" data-toggle="dropdown">
                                            <span class="caret"></span>
                                        </button>
                                        <ul id="secondary-enrolment-options" class="dropdown-menu">
                                            <li><a href="#" id="print"><i
                                                        class="icon-print"></i> <?php echo $translate->_("Print"); ?>
                                                </a></li>
                                        </ul>
                                    </div>
                                    <div class="pull-left">
                                        <div class="input-append space-right">
                                            <input type="text" id="group-search"
                                                   placeholder="<?php echo $translate->_("Search the Course Groups"); ?>"
                                                   class="input-large search-icon">
                                            <input type="hidden" id="course-id" value="<?php echo $COURSE_ID; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div id="item-summary"></div>
                            </div>
                            <div id="search-container" class="hide space-below medium"></div>
                            <div id="item-summary"></div>
                            <div id="groups-msgs">
                                <div id="groups-items-loading" class="enrolment-loading hide">
                                    <p><?php echo $translate->_("Loading Groups..."); ?></p>
                                    <img src="<?php echo ENTRADA_URL . "/images/loading.gif" ?>"/>
                                </div>
                            </div>
                            <div id="group-table-container">
                                <table id="groups-table" class="table table-bordered table-striped">
                                    <thead>
                                    <th width="5%"><input type="checkbox" id="checkAll" name="checkAll"></th>
                                    <th width="76%" class="general">Group Name<i class="fa fa-sort group-sort"
                                                                                 aria-hidden="true" data-name="code"
                                                                                 data-order=""></i></th>
                                    <th width="19%" class="title"># of members</th>
                                    </thead>
                                    <tbody>
                                    <tr id="no-items">
                                        <td colspan="5"><?php echo $translate->_("No groups to display"); ?></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="item-detail-container" class="hide"></div>
                        </form>
                        <div id="delete-groups-modal" class="modal hide fade">
                            <form id="delete-groups-modal-item" class="form-horizontal"
                                  action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-groups"; ?>"
                                  method="POST" style="margin:0px;">
                                <input type="hidden" name="step" value="2"/>
                                <div class="modal-header"><h1><?php echo $translate->_("Delete Groups"); ?></h1></div>
                                <div class="modal-body">
                                    <div id="no-groups-selected" class="hide">
                                        <p><?php echo $translate->_("No Group Selected to delete."); ?></p>
                                    </div>
                                    <div id="groups-selected" class="hide">
                                        <p><?php echo $translate->_("Please confirm that you would like to proceed with the selected Group(s)?"); ?></p>
                                        <div id="delete-groups-container"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="row-fluid">
                                        <a href="#" class="btn btn-default pull-left"
                                           data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                        <input id="delete-groups-modal-delete" type="submit" class="btn btn-danger"
                                               value="<?php echo $translate->_("Delete"); ?>"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="assign-period-modal" class="modal hide fade">
                            <form id="assign-period-modal-item" class="form-horizontal"
                                  action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-groups"; ?>"
                                  method="POST" style="margin:0px;">
                                <input type="hidden" name="step" value="2"/>
                                <div class="modal-header">
                                    <h1><?php echo $translate->_("Assign Curriculum Period"); ?></h1></div>
                                <div class="modal-body">
                                    <div id="error-groups-selected" class="hide">
                                        <p><?php echo $translate->_("Please select only Group(s) without curriculum period assigned."); ?></p>
                                    </div>
                                    <div id="assign-groups-selected" class="hide">
                                        <p><?php echo $translate->_("Please confirm that you would like to assign the curriculum period to the selected Group(s)?"); ?></p>
                                        <div id="assign-groups-container"></div>
                                        <div class="control-group">
                                            <label for="cperiod_select"
                                                   class="control-label group-assign-label">Period:</label>
                                            <div class="controls group-assign-select">
                                                <select id="cperiod_assign_select" name="cperiod_assign_select"
                                                        class="span10">
                                                    <?php
                                                    foreach ($curriculum_periods as $period) { ?>
                                                        <option
                                                            value="<?php echo html_encode($period->getID()); ?>" <?php echo(isset($PREFERENCES["selected_curriculum_period"]) && $PREFERENCES["selected_curriculum_period"] == $period->getID() ? "selected=\"selected\"" : ""); ?>>
                                                            <?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate())) . " to " . date("F jS, Y", html_encode($period->getFinishDate())); ?>
                                                        </option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="row-fluid">
                                        <a href="#" class="btn btn-default pull-left"
                                           data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                        <input id="assign-groups-modal-assign" type="submit" class="btn btn-info"
                                               value="<?php echo $translate->_("Assign"); ?>"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="row-fluid">
                            <a id="load-groups"
                               class="btn btn-block"><?php echo $translate->_("Load More Groups"); ?></a>
                        </div>
                    </div>
                    <?php
                } else {
                    add_notice($translate->_("This course currently has no curriculum periods associated with it. This is because there are no Active Periods setup in the Course Setup section."));
                    echo display_notice();
                    application_log("notice", $translate->_("No curriculum periods found for a course when attempting to edit course groups"));
                }
                break;
        }
    } else {
        add_error("In order to edit a course group you must provide a valid course identifier. The provided ID does not exist in this system.");
        echo display_error();
    }
}