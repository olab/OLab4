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
 * This file is used to display annual report reports.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('annualreportadmin', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
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
	<h1><?php echo $MODULES[strtolower($MODULE)]["title"]; ?></h1>
	
	<h2 style="color: #669900">Completion Reports</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=report-status">Report By Faculty Member</a><br />
			A report indicating annual report completion status for a given period for all faculty.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=faculty-search">Search for Specific Faculty Member</a><br />
			A report indicating an individual faculty Member's annual report completion status for a given period.
		</li>
	</ol>
	<h2 style="color: #669900">Education Reports</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=ug-teaching">Undergraduate Teaching</a><br />
			A break down of undergraduate medical teaching per department per faculty member.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=ug-nonmedical-teaching">Undergraduate Non-Medical Teaching</a><br />
			A break down of undergraduate non-medical teaching per department per faculty member.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=pg-teaching">Graduate Teaching</a><br />
			A break down of graduate teaching per department per faculty member.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=ug-supervision">Undergraduate Supervision</a><br />
			A report outlining undergraduate supervision in a department per faculty member.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=pg-supervision">Graduate Supervision</a><br />
			A report outlining graduate supervision in a department per faculty member.
		</li>
	</ol>
	<h2 style="color: #669900">Research Reports</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=research-grants">Research Grants</a><br />
			A report containing research grant amounts and totals for a department.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=peer-reviewed-pubs">Peer Reviewed Articles</a><br />
			A report containing peer reviewed articles and counts for a department.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=clinical-citations">Peer Reviewed Clinical Citations</a><br />
			A report containing peer reviewed article citations for a department.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=research-invited-lectures">Invited Lectures</a><br />
			A report containing invited lectures listed and counted for a department.
		</li>
	</ol>
	<h2 style="color: #669900">Combined Departmental Reports</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=opth-report">Publiatons, Posters, Awards and Grants</a><br />
			A report containing all peer and non peer-reviewed publications, poster presentations / invited lectures, awards and research grants for a department.
		</li>
	</ol>
	<?php
}
?>