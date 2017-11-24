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
 * The default file that is loaded when /admin/regionaled is accessed.
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
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	$query = "	SELECT `proxy_id`
				FROM `" . CLERKSHIP_DATABASE . "`.`apartment_regionaled_users`
				WHERE `proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
	
	$is_regionaled = $db->getOne($query);
	if ($is_regionaled) {
	?>
	<h1>Regional Education</h1>

	<h2 title="Learners Requiring Accommodations">Learners Requiring Accommodations</h2>

	<div id="learners-requiring-accommodations">
	<?php
	$query	= "	SELECT a.*, c.`region_name`, d.`id` AS `proxy_id`, d.`firstname`, d.`lastname`, e.`rotation_title`
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
				ORDER BY a.`event_start`, a.`category_id`, c.`region_name`, d.`lastname` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<div class="display-generic">
			The following list of learners require accommodations to be assigned to them based on the region of their event.
		</div>
		<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled?section=delete" method="post">
			<table class="tableList" summary="List of students who require apartments assigned to them.">
				<colgroup>
					<col class="modified" />
					<col class="teacher" />
					<col class="actions" />
					<col class="title" />
					<col class="actions" />
					<col class="actions" />
					<col class="actions" />
				</colgroup>
				<thead>
					<tr>
						<td class="modified">&nbsp;</td>
						<td class="teacher">Student</td>
						<td class="actions"><?php echo $translate->_("Event Type"); ?></td>
						<td class="title">Rotation Name</td>
						<td class="actions">Region</td>
						<td class="actions">Start Date</td>
						<td class="actions">Finish Date</td>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td></td>
						<td colspan="6" style="padding-top: 10px">
							<input type="submit" class="btn btn-danger" value="Remove Selected" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<?php
					foreach ($results as $result) {
						$click_url = ENTRADA_URL."/admin/regionaled?section=assign&id=".(int) $result["event_id"];
						echo "<tr>\n";
						echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["event_id"]."\" /></td>\n";
						echo "	<td class=\"teacher\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["lastname"].", ".$result["firstname"] )."</a></td>\n";
						echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".(($result["event_type"] == "elective") ? "Elective (Approved)" : "Core Rotation")."</a></td>\n";
						echo "	<td class=\"title\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["rotation_title"])."</a></td>\n";
						echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["region_name"])."</a></td>\n";
						echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("M d/y", $result["event_start"])."</a></td>\n";
						echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("M d/y", $result["event_finish"])."</a></td>\n";
						echo "</tr>\n";
					}
					?>
				</tbody>
			</table>
		</form>
		<?php
	} else {
		$NOTICE++;
		$NOTICESTR[] = "There are no learners that require accommodations in the system at this time.";

		echo display_notice($NOTICESTR);
	}
	?>
	</div>
	<?php
	} 
	?>
	<h2 title="Unconfirmed Accommodation Assignments">Unconfirmed Accommodation Assignments</h2>	
	<?php
	$query = "	SELECT a.*, b.`apartment_title`, c.`region_name`, d.`id` AS `proxy_id`, d.`firstname`, d.`lastname`, e.`group` AS `learner_type`
				FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartments` AS b
				ON b.`apartment_id` = a.`apartment_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
				ON c.`region_id` = b.`region_id`
				LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
				ON d.`id` = a.`proxy_id`
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS e
				ON e.`user_id` = d.`id`
				JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` f
				ON f.`apartment_id` = a.`apartment_id`
				JOIN `".AUTH_DATABASE."`.`departments` g
				ON g.`department_id` = f.`department_id`
				AND e.`group` IN ('student', 'Clerk')
				AND e.`account_active` = 'true'
				AND e.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				WHERE a.`confirmed` = '0'
				AND a.`inhabiting_finish` > UNIX_TIMESTAMP()
				AND f.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId()) . "
				GROUP BY d.`id`
				ORDER BY a.`inhabiting_start` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<div id="unconfirmed-accommodation-assignments">
			<div class="display-generic">
				The following list of learners have been assigned accommodations but have not yet confirmed the assignment.
			</div>

			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled?section=reminder" method="post">
				<table class="tableList" summary="List of students have been assigned accommodations, but not yet confirmed them.">
					<colgroup>
						<col class="modified" />
						<col class="teacher" />
						<col class="actions" />
						<col class="title" />
						<col class="actions" />
						<col class="actions" />
						<col class="actions" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="teacher">Student</td>
							<td class="actions">Learner Type</td>
							<td class="title">Apartment Title</td>
							<td class="actions">Region</td>
							<td class="actions">Start Date</td>
							<td class="actions">Finish Date</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="6" style="padding-top: 10px">
								<input type="submit" class="btn" value="Send Reminder" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach ($results as $result) {
							$click_url = ENTRADA_URL."/admin/regionaled/apartments/manage?id=".(int) $result["apartment_id"]."&dstamp=".$result["inhabiting_start"];
							echo "<tr>\n";
							echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"remind[]\" value=\"".(int) $result["aschedule_id"]."\" /></td>\n";
							echo "	<td class=\"teacher\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["lastname"].", ".$result["firstname"] )."</a></td>\n";
							echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".$result["learner_type"]."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["apartment_title"])."</a></td>\n";
							echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["region_name"])."</a></td>\n";
							echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("M d/y", $result["inhabiting_start"])."</a></td>\n";
							echo "	<td class=\"actions\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("M d/y", $result["inhabiting_finish"])."</a></td>\n";
							echo "</tr>\n";
						}
						?>
					</tbody>
				</table>
			</form>
		</div>
		<?php
	} else {
		$NOTICE++;
		$NOTICESTR[] = "There are no learners that have not confirmed their accommodations in the system at this time.";

		echo display_notice($NOTICESTR);
	}
}
