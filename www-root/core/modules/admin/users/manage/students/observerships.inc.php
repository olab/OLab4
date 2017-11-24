<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Observership management page for administrators.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
if (!defined("IN_MANAGE_USER_STUDENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", true)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {

	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/Observerships.class.php");
	
	$PROXY_ID	= $user_record["id"];
	$user		= User::fetchRowByID($PROXY_ID);
	$pending_observerships = Observerships::get(array("student_id" => $user->getID(), "status" => "pending"));
	$approved_observerships = Observerships::get(array("student_id" => $user->getID(), "status" => "approved"));
	$rejected_observerships = Observerships::get(array("student_id" => $user->getID(), "status" => "rejected"));
	$confirmed_observerships = Observerships::get(array("student_id" => $user->getID(), "status" => "confirmed"));
	$denied_observerships = Observerships::get(array("student_id" => $user->getID(), "status" => "denied"));
	
	echo "<h1>Observerships for ".$user->getFullname(false)."</h1>";
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$user->getID(), "title" => "Observerships");
	
	switch ($STEP) {
		case 2 : 
			if ($_POST["status"] && is_array($_POST["status"])) {
				foreach ($_POST["status"] as $observership_id => $status) {
					$observership = Observership::get($observership_id);
					if ($observership->getStudentID() == $PROXY_ID && ($status == "rejected" || $status == "approved")) {
						$query = "UPDATE `student_observerships` SET `status` = ".$db->qstr($status)." WHERE `id` = ".$observership_id;
						if (!$db->Execute($query)) {
							application_log("error", "Error while updating `student_observerships`, DB said: ".$db->ErrorMsg());
							add_error("An error ocurred while trying to update observership status. An administrator has been informed, please try again later.");
						}
					} else {
						application_log("error", "Attempt to update observership with id [".$observership_id."] for proxy id [".$PROXY_ID."] where status was invalid or different student_id.");
						add_error("An error ocurred while trying to update observership status. An administrator has been informed, please try again later.");
					}
					unset($observership);
				}
			}
		break;
	}
	
	switch ($STEP) {
		case 2 :
			if ($ERORR) {
				echo display_error();
			} else {
				echo display_success("Successfully updated observerships for ".$user->getFullname(false).". You will be redirected to the students observership managment page in 5 seconds. Please <a href=\"".ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$PROXY_ID."\">click here</a> if you do not wish to wait.");
				if (count($pending_observerships) > 0) {
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/users/manage/students?section=observerships&id=".$PROXY_ID."\\'', 5000)";
				} else {
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/users/manage?id=".$PROXY_ID."\\'', 5000)";
				}
			}
		break;
		case 1 :
		default:
			if (clerkship_fetch_schedule($user->getID()) == false || ($ENTRADA_USER->getGroup() == "staff" || $ENTRADA_USER->getGroup() == "medtech")) {
				?>
<div class="row-fluid">
	<a id="add_observership" href="<?php echo ENTRADA_URL; ?>/admin/observerships?section=add&id=<?php echo $PROXY_ID; ?>" class="btn btn-small btn-success pull-right"><i class="icon-plus-sign icon-white"></i> Add Observership</a></li>
</div>
				<?php
			}
			if (count($pending_observerships) > 0) { ?>
				<h2>Pending Observerships</h2>
				<form action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=observerships&id=<?php echo $PROXY_ID; ?>" method="post">
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" summary="List of Observerships" width="674">
						<thead>
							<tr>
								<td width="300">Title</td>
								<td width="184">Site</td>
								<td width="60">Start</td>
								<td width="60">Finish</td>
								<td width="120">Status</td>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($pending_observerships as $observership) {
					echo "<tr>\n";

					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".$observership->getSite()."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "<td><select name=\"status[".$observership->getId()."]\" style=\"width:100px;\">";
					echo "<option value=\"rejected\" ".($observership->getStatus() == "rejected" ? " selected=\"selected\" " : "").">Rejected</option>";
					echo "<option value=\"pending\" ".($observership->getStatus() == "pending" ? " selected=\"selected\" " : "").">Pending</option>";
					echo "<option value=\"approved\" ".($observership->getStatus() == "approved" ? " selected=\"selected\" " : "").">Approved</option>";
					echo "</status></td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
					
					<div class="row">
						<div class="pull-right"><input class="btn btn-primary" type="submit" value="Save" /></div>
					</div>
				</form>
				<?php
			} else {
				?>
				<div class="display-generic">There are no pending observerships at this time.</div>
				<?php
			}
			
			if (count($approved_observerships) > 0) { ?>
				<h2>Approved Observerships</h2>
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" summary="List of Observerships">
						<thead>
							<tr>
								<td width="300">Title</td>
								<td width="184">Site</td>
								<td width="60">Start</td>
								<td width="60">Finish</td>
								<td width="120">Reflection</td>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($approved_observerships as $observership) {
					echo "<tr>\n";
					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".$observership->getSite()."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "<td>".($observership->getReflection() ? "<a href=\"" . ENTRADA_URL . "/admin/observerships?section=reflection&id=".$observership->getReflection()."\">Review Reflection</a>" : "")."</td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
				<?php
			}
			
			if (count($rejected_observerships) > 0) { ?>
				<h2>Rejected Observerships</h2>
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" summary="List of Observerships">
						<thead>
							<tr>
								<td width="300">Title</td>
								<td width="204">Site</td>
								<td width="60">Start</td>
								<td width="160">Finish</td>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($rejected_observerships as $observership) {
					echo "<tr>\n";
					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".$observership->getSite()."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
				<?php
			}
			
			if (count($confirmed_observerships) > 0) { ?>
				<h2>Confirmed Observerships</h2>
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" summary="List of Observerships">
						<thead>
							<tr>
								<td width="300">Title</td>
								<td width="204">Site</td>
								<td width="60">Start</td>
								<td width="60">Finish</td>
								<td width="100">Reflection</td>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($confirmed_observerships as $observership) {
					echo "<tr>\n";
					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".$observership->getSite()."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "<td>".($observership->getReflection() ? "<a href=\"" . ENTRADA_URL . "/admin/observerships?section=reflection&id=".$observership->getReflection()."\">Review Reflection</a>" : "")."</td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
				<?php
			}
			
			if (count($denied_observerships) > 0) { ?>
				<h2>Confirmed Observerships</h2>
					<input type="hidden" name="step" value="2" />
					<table class="table table-striped table-bordered" summary="List of Observerships">
						<thead>
							<tr>
								<td width="200">Title</td>
								<td width="150">Site</td>
								<td width="60">Start</td>
								<td width="160">Finish</td>
							</tr>
						</thead>
						<tbody>
				<?php
				foreach ($denied_observerships as $observership) {
					echo "<tr>\n";
					echo "<td><a href=\"" . ENTRADA_URL . "/admin/observerships?section=review&id=".$observership->getId()."\">".$observership->getTitle()."</a></td>\n";
					echo "<td>".$observership->getSite()."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
					echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
					echo "</tr>\n";
				} ?>
						</tbody>
					</table>
				<?php
			}
		break;
	}
}