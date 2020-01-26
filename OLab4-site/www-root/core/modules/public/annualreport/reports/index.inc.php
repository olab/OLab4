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
 * This file is used to add events to the entrada.events table.
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
} elseif(!$ENTRADA_ACL->amIAllowed('annualreport', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

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
	<h1>My Reports</h1>
	
	<h2>Research Reports</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_publications">My Publications</a><br />
			A report that shows publications for a specific date range.
		</li>
	</ol>
	<?php 
	if($ENTRADA_ACL->amIAllowed('mydepartment', 'read', 'DepartmentHead') || $ENTRADA_ACL->amIAllowed('myowndepartment', 'read', 'DepartmentRep')) { ?>
	<h2>Research Reports for My Department</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_publications">Publications</a><br />
			A report that shows publications in your department for a specific date range.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_publications_by_member">Publications By Members</a><br />
			A report that shows publications in your department for a specific date range grouped by faculty member.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_publications_by_member_journal">Peer Reviewed Publications By Members By Journal</a><br />
			A report that shows publications in your department for a specific date range grouped by faculty member and the Journals they published in.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_grants">Research Grants</a><br />
			A report that shows Research Grants in your department for a specific date range.
		</li>
	</ol>
	<h2>Teaching Reports for My Department</h2>
	<ol class="system-reports">
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_ug_teaching">Undergraduate Medical Teaching</a><br />
			A report that shows Undergraduate Medical Teaching (MEdTech) in your department for a specific calendar year.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_ug_nonmedical_teaching">Undergraduate Non-Medical Teaching</a><br />
			A report that shows Undergraduate Non-Medical Teaching in your department for a specific calendar year.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_pg_teaching">Postgraduate Teaching</a><br />
			A report that shows Postgraduate Teaching in your department for a specific calendar year.
		</li>
		<li>
			<a href="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=my_departmental_clinical_teaching">Clincial Teaching</a><br />
			A report that shows Clinical Teaching in your department for a specific date range.
		</li>
	</ol>
	<?php }
}
?>