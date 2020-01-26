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
 * Used in observerships.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: index.inc.php 1187 2010-05-06 13:44:57Z finglanj $
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_OBSERVERSHIPS_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("observerships", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	
	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/Observerships.class.php");
	echo "<h1>Pending Observerships</h1>";
	
	$observerships = Observerships::get(array("status" => "pending"));
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/observerships", "title" => "Pending Observerships");
	
	switch ($STEP) {
		case 2 :
			if ($_POST["action"] == "Reject" || $_POST["action"] == "Approve") {
				$set_status = ($_POST["action"] == "Reject" ? "rejected" : "approved");
				if ($_POST["status"] && is_array($_POST["status"])) {
					foreach ($_POST["status"] as $id => $status) {
						if ($status == "on") {
							$id = (int) $id;
							$query = "UPDATE `student_observerships` SET `status` = ".$db->qstr($set_status)." WHERE `id` = ".$db->qstr($id);
							if(!$db->Execute($query)) {
								add_error("Failed to update observership. A system administrator has been informed, please try again later.");
								application_log("error", "Failed to updated observership, DB said: ".$db->ErrorMsg());
							}
						} else {
							add_error("Sorry, but a problem occurred while attempting to update the observership status. An invalid status type was sent to the server.");
							application_log("error", "Attempt to updated observership [".$id."] with invalid status of [".$status."]");
						}
					}
					if (!$ERROR) {
						add_success("Thank you, the observerships have successfully been updated. You will be directed to the observerships page in 5 seconds. Please <a href=\"".ENTRADA_URL."/admin/observerships\">click here</a> if you do not wish to wait.");
					}
				}
			} else {
				add_error("Sorry, an error ocurred while trying to update the selected observership statuses. An administrator has been informed, please try again later.");
			}
		break;
	}
	
	switch ($STEP) {
		case 2 :
			if ($ERROR) {
				echo display_error();
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/observerships\\'', 5000)";
			}
			if ($SUCCESS) {
				echo display_success();
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/observerships\\'', 5000)";
			}
		break;
		case 1 :
			if (count($observerships) > 0) { ?>
				<form action="<?php echo ENTRADA_URL; ?>/admin/observerships" method="post">
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" id="observership-list" cellspacing="0" cellpadding="1" summary="List of Observerships">
						<thead>
							<tr>
								<th></th>
								<th>Student</th>
								<th>Title</th>
								<th>Start</th>
								<th>Finish</th>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($observerships as $observership) {
					$student = User::fetchRowByID($observership->getStudentID());
					echo "<tr>\n";
					echo "<td><input type=\"checkbox\" name=\"status[".$observership->getId()."]\" /></td>\n";
					echo "<td><a href=\"".ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$student->getID()."\">".$student->getFullname(false)."</a></td>\n";
					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
					<div class="row-fluid">
						<input class="btn btn-danger"  type="submit" value="Reject" name="action" />
						<input class="btn btn-primary pull-right" type="submit" value="Approve" name="action" />
					</div>
				</form>
				<?php
			} else {
				echo "<div class=\"display-generic\">There are no pending observerships at this time. Please use the <a href=\"".ENTRADA_URL."/admin/users\">Manage Users</a> section to review individual user observerships.</div>"; 
			}

		break;
	}
}