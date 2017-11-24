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
 * The file that is loaded when the regional education office wants to remove someone from the list of students
 * who require accommodations.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONALED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "delete", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["delete"]) && is_array($_POST["delete"]) && count($_POST["delete"])) {
		$event_ids = array();

		foreach ($_POST["delete"] as $event_id) {
			if ($tmp_input = clean_input($event_id, array("nows", "int"))) {
				$event_ids[] = $tmp_input;
			}
		}

		if (count($event_ids)) {
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled", "title" => "Delete Accommodation Requirements");

			switch ($STEP) {
				case 2 :
					$url = ENTRADA_URL."/admin/regionaled";
					$msg = "You will now be redirected back to the Regional Education dashboard; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

					if (($db->AutoExecute(CLERKSHIP_DATABASE.".events", array("requires_apartment" => 0), "UPDATE", "event_id IN (".implode(", ", $event_ids).")")) && ($updated = $db->Affected_Rows())) {
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully removed ".$updated." record".(($updated != 1) ? "s" : "")." from the Regional Education dashboard.<br /><br />".$msg;

						echo display_success();
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to remove the requested learners from the Regional Education dashboard at this time. The administrator has been notified of the error, please try again later.<br /><br />".$msg;

						echo display_error();

						application_log("error", "Unable to set requires_apartment to 0 for the following event_ids [".implode(", ", $event_ids)."]. Database said: ".$db->ErrorMsg());
					}
				break;
				case 1 :
				default :
					$query = "	SELECT a.`event_id`, a.`rotation_id`, a.`event_title`, a.`event_start`, a.`event_finish`, c.`region_name`, d.`id` AS `proxy_id`, d.`firstname`, d.`lastname`, e.`rotation_title`
								FROM `".CLERKSHIP_DATABASE."`.`events` AS a
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
								ON b.`event_id` = a.`event_id`
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
								ON c.`region_id` = a.`region_id`
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
								ON d.`id` = b.`etype_id`
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS e
								ON e.`rotation_id` = a.`rotation_id`
								WHERE a.`event_finish` > ".$db->qstr(time())."
								AND a.`event_status` = 'published'
								AND a.`requires_apartment` = '1'
								AND c.`manage_apartments` = '1'
								AND a.`event_id` IN (".implode(", ", $event_ids).")
								ORDER BY a.`event_start`, a.`category_id`, c.`region_name`, d.`lastname` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						$total_rows = count($results);
						?>
						<div class="display-notice">
							Please confirm that you wish to <strong>permanently</strong> remove the following <?php echo (($total_rows != 1) ? "entries" : "entry"); ?> from the Regional Education dashboard.
						</div>
						<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled" method="post">
							<input type="hidden" name="section" value="delete" />
							<input type="hidden" name="step" value="2" />
							<table class="tableList" cellspacing="0" cellpadding="1" summary="List of students who require apartments assigned to them.">
								<colgroup>
									<col class="modified" />
									<col class="teacher" />
									<col class="type" />
									<col class="title" />
									<col class="region" />
									<col class="date-smallest" />
									<col class="date-smallest" />
								</colgroup>
								<thead>
									<tr>
										<td class="modified">&nbsp;</td>
										<td class="teacher">Student</td>
										<td class="type"><?php echo $translate->_("Event Type"); ?></td>
										<td class="title">Rotation Name</td>
										<td class="region">Region</td>
										<td class="date-smallest">Start Date</td>
										<td class="date-smallest">Finish Date</td>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td></td>
										<td colspan="6" style="padding-top: 10px">
											<input type="submit" class="btn btn-danger" value="Confirm Removal" />
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									foreach ($results as $result) {
										$click_url = ENTRADA_URL."/admin/regionaled/apartments/schedules?section=assign&id=".(int) $result["event_id"];
										echo "<tr>\n";
										echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["event_id"]."\" checked=\"checked\" /></td>\n";
										echo "	<td class=\"teacher\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["lastname"].", ".$result["firstname"] )."</a></td>\n";
										echo "	<td class=\"type\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".(($result["event_type"] == "elective") ? "Elective (Approved)" : "Core Rotation")."</a></td>\n";
										echo "	<td class=\"title\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["rotation_title"])."</a></td>\n";
										echo "	<td class=\"region\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["region_name"])."</a></td>\n";
										echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_start"])."</a></td>\n";
										echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["event_finish"])."</a></td>\n";
										echo "</tr>\n";
									}
									?>
								</tbody>
							</table>
						</form>
						<?php
					} else {
						application_log("notice", "The regional education accommodation request removal page was accessed without providing any valid event_ids to remove.");

						header("Location: ".ENTRADA_URL."/admin/regionaled");
						exit;
					}
				break;
			}
		} else {
			application_log("notice", "The regional education accommodation request removal page was accessed without providing any valid event_ids to remove.");

			header("Location: ".ENTRADA_URL."/admin/regionaled");
			exit;
		}
	} else {
		application_log("notice", "The regional education accommodation request removal page was accessed without providing any event_ids to remove.");

		header("Location: ".ENTRADA_URL."/admin/regionaled");
		exit;
	}
}
