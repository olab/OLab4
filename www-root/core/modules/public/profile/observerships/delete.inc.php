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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_OBSERVERSHIPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/observerships?section=delete", "title" => "Delete Observerships");
	
	require_once("Classes/mspr/Observership.class.php");
	require_once("Classes/mspr/Observerships.class.php");
	?>
	<h1>My Observerships</h1>
	<?php
	if ($_POST["delete"]) {
		if (is_array($_POST["delete"])) {
			foreach ($_POST["delete"] as $observership_id) {
				$observership = Observership::get($observership_id);
				if ($observership->getStatus() == "pending" || $observership->getStatus() == "approved" || $observership->getStatus() == "rejected") {
					$valid_observerships[] = $observership;
				}
			}
		}

		switch ($STEP) {
			case 2 :
				foreach ($valid_observerships as $observership) {
					$title = $observership->getTitle();
					if ($observership->getStatus() == "pending" || $observership->getStatus() == "approved" || $observership->getStatus() == "rejected") {
						if ($observership->delete()) {
							$deleted[] = $title;
						} else {
							add_error("An error ocurred while attempting to delete the observership <strong>".$title."</strong>. An administrator has been informed, please try again later.
										<br />You will be automatically redirected to the My Observerships page in 5 seconds, or you can <a href=\"".ENTRADA_URL."/profile/observerships\">click here</a>.");
						}
					} else {
						add_error("Unable to delete the observership <strong>".$title."</strong>, the status is ".$observership->getStatus().".");
					}
				}
				if ($deleted) {
					$message = "<pre>";
					foreach ($deleted as $title) {
						$message .= "  ".$title."\n";
					}
					$message .= "</pre>";
					add_success("Successfully deleted the following observerships:<br />" . $message ."You will be automatically redirected to the My Observerships page in 5 seconds, or you can <a href=\"".ENTRADA_URL."/profile/observerships\">click here</a>.");
				}
			break;
		}

		switch ($STEP) {
			case 2 :	
				if ($SUCCESS) {
					echo display_success();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
				}
				if ($ERROR) {
					echo display_error();
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
				}
			break;
			case 1 :
			default :
				if ($valid_observerships) { ?>
					<div class="display-generic">Please review the list of observerships below to confirm you wish to <strong>permanently delete</strong> them.</div>
					<form action="<?php echo ENTRADA_URL; ?>/profile/observerships?section=delete" method="post">
						<input type="hidden" name="step" value="2" />
						<table class="table table-striped" id="observership-list" cellspacing="0" cellpadding="1" summary="List of Observerships">
							<thead>
								<tr>
									<th width="5%">&nbsp;</th>
									<th>Title</th>
									<th>Site</th>
									<th>Start</th>
									<th>Finish</th>
								</tr>
							</thead>
							<tbody>
					<?php
					foreach ($valid_observerships as $observership) {
						echo "<tr>\n";
						echo "<td><input type=\"checkbox\" name=\"delete[]\" value=\"".$observership->getId()."\" checked=\"checked\" /></td>\n";
						echo "<td>".$observership->getTitle()."</td>\n";
						echo "<td>".$observership->getSite()."</td>\n";
						echo "<td>".date("Y-m-d", $observership->getStart())."</td>\n";
						echo "<td>".date("Y-m-d", $observership->getEnd())."</td>\n";
						echo "</tr>\n";
					} ?>
							</tbody>
						</table>
						<div class="row-fluid">
							<input class="btn btn-primary pull-right" type="submit" value="Delete" />
						</div>
					</form>
					<?php
				} else {
					add_error("Sorry, an error ocurred, none of the selected observerships are able to be deleted. If you believe this is in error please use the feedback system to contact a system administrator.<br /><br />Please <a href=\"".ENTRADA_URL."/profile/observerships\"><strong>click here</strong></a> to return to your Observership section.");
					echo display_error();
				}
			break;
		}
	} else {
		add_error("Sorry, but no Observerships were selected to be deleted. You will now be returned to the Observerships page where you can select the Observerships you would like to delete.");
		echo display_error();
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/profile/observerships\\'', 5000)";
	}
}