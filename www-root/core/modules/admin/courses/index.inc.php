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
 * The default file that is loaded when /admin/courses is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $MODULE .".js\"></script>";
    /* $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.dataTables.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>"; */
    $HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";
    ?>
    
    <h1><?php echo $translate->_($MODULE); ?></h1>
    <?php

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."\\'', 5000)";
        break;
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            } ?>
            <div id="msgs"></div>
            <div id="courses-container">
                <form id="form-search" class="form-search" action="<?php echo ENTRADA_URL."/admin/" . $MODULE  . "?step=2"; ?>" method="POST">
                    <div id="search-bar" class="search-bar">
                        <div class="row-fluid space-below medium">
                            <div class="pull-right">
                                <?php if ($ENTRADA_ACL->amIAllowed('course', 'delete', false)) { ?>
                                    <a href="#delete-courses-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Courses"); ?></a>
                                <?php } if ($ENTRADA_ACL->amIAllowed(new CourseResource(null, $ENTRADA_USER->getActiveOrganisation()), "create")) { ?>
                                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add A New Course"); ?></a>
                                <?php } ?>
                            </div>
                            <div class="pull-left">
                                <input type="text" id="course-search" placeholder="<?php echo $translate->_("Search Courses..."); ?>" class="input-large search-icon">
                            </div>
                        </div>
                        <div id="item-summary"></div>
                    </div>
                    <div id="search-container" class="hide space-below medium"></div>    
                    <div id="item-summary"></div>
                    <div id="courses-msgs">
                        <div id="courses-loading" class="hide">
                            <p><?php echo $translate->_("Loading Courses..."); ?></p>
                            <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                        </div>
                        <div id="courses-no-results" class="hide">
                            <p><?php echo $translate->_("No Courses Found"); ?></p>
                        </div>
                    </div>
                    <div id="course-table-container">
                        <table id="courses-table" class="table table-bordered table-striped">
                            <thead>
                                <th width="5%"></th>
                                <th width="20%" class="general">Layout<i class="fa fa-sort course-sort" aria-hidden="true" data-name="type" data-order=""></i></th>
                                <th width="25%" class="general">Code<i class="fa fa-sort-asc course-sort" aria-hidden="true" data-name="code" data-order="asc"></i></th>
                                <th width="43%" class="title">Name<i class="fa fa-sort course-sort" aria-hidden="true" data-name="name" data-order=""></i></th>
                                <th width="7%"></th>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div id="item-detail-container" class="hide"></div>
                </form>
                <div id="delete-courses-modal" class="modal hide fade">
                    <form id="delete-courses-modal-item" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-courses"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h1><?php echo $translate->_("Delete Courses"); ?></h1></div>
                        <div class="modal-body">
                            <div id="no-courses-selected" class="hide">
                                <p><?php echo $translate->_("No Courses Selected to delete"); ?></p>
                            </div>
                            <div id="courses-selected" class="hide">
                                <p><?php echo $translate->_("Please confirm you would like to delete the selected Courses(s)?"); ?></p>
                                <div id="delete-courses-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                <input id="delete-courses-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row-fluid">
                    <a id="load-more-courses" class="btn btn-block"><?php echo $translate->_("Load More Courses"); ?></a>
                </div>
            </div>
            <?php 
        break;
    }
}