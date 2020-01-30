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
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_AAMC_CI"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ?>
    <div class="row-fluid space-below medium">
        <div class="pull-right">
            <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/reports/aamc/manage?section=edit&amp;id=<?php echo $REPORT_ID;?>" class="btn btn-medium"><i class="icon-edit"></i> Edit Report</a>
        </div>
    </div>
    <?php

	echo "<h1>".html_encode($REPORT["report_title"])."</h1>";

	if ($REPORT["report_description"]) {
		echo "<div class=\"event-description\">\n";
		echo $REPORT["report_description"];
		echo "</div>";
	}
	?>

	<div style="text-align: right; margin-top: 5px;">
		<a class="btn btn-primary" href="<?php echo ENTRADA_RELATIVE."/admin/reports/aamc/manage?id=".$REPORT_ID; ?>&section=generate">Save XML</a>
	</div>
	<?php
}
