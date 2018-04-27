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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    search_subnavigation("reports");
    ?>
    <style type="text/css">
        ol.system-reports li {
            width:			70%;
            color:			#666666;
            font-size:		12px;
            padding:		0px 15px 15px 0px;
            margin-left:	5px;
        }

        ol.system-reports li a {
            font-size:		13px;
            font-weight:	bold;
        }
    </style>
    <h1><?php echo $translate->_("Curriculum Reports"); ?></h1>

    <ol class="system-reports">
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/reports?section=curriculum">Curriculum Tag Mapping Report</a><br />
            A report showing the relationship between tags in the different tag sets in either up or down mapping direction.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/curriculum/reports/minutes">Curriculum Tag Minutes and Mapping Report</a><br />
            A report showing the total Learning Event minutes and mappings grouped by Tags within selected Tag Sets.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/reports?section=curriculum-review">Curriculum Review Report</a><br />
            A report containing a summary of objectives, and presentations for each event.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/reports?section=course-summary">Course Summary Report</a><br />
            A report containing a summary of objectives, presentations, and hot topics for each learning event.
        </li>
        <li>
            <a href="<?php echo ENTRADA_URL; ?>/admin/reports?section=event-types-by-course"><?php echo $translate->_("Learning Event Types"); ?> by Course</a><br />
            A detailed report containing a <?php echo $translate->_("Learning Event Type"); ?> breakdown by Course.
        </li>
    </ol>

    <?php
}

