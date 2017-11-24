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
 * This is the main dashboard that people see when they log into Entrada
 * and have not requested another page or module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@qmed.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) exit;

if (!$ENTRADA_ACL->amIAllowed("dashboard", "read")) {

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else { 
	if (isset($_GET["notice_id"]) && $tmp_input = clean_input($_GET["notice_id"], array("int"))) {
		$PROCESSED["notice_id"] = $tmp_input;
		$notice = Models_Notice::fetchNotice($PROCESSED["notice_id"]);
	}
	
	$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE."/dashboard/notices?section=view", "title" => ($notice ? date(DEFAULT_DATE_FORMAT, $notice["updated_date"]) : ""));
	
	?>
	<?php 
	if ($notice) {
		echo "<div id=\"notice_box_".(int) $notice["notice_id"]."\" class=\"space-below\">";
		echo	"<strong>".date(DEFAULT_DATE_FORMAT, $notice["updated_date"])."</strong>";
		echo	"<div class=\"space-left\">".trim(clean_input($notice["notice_summary"], "html"))."</div>";
		echo "</div>";
		add_statistic("notices", "read", "notice_id", $notice["notice_id"]);
	} else { ?>
		<div class="alert alert-info">
			<strong>No message found.</strong>
		</div>
	<?php	
	}
	?>
<?php	
}