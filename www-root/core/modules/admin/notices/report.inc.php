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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_NOTICES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('notice', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["notice_id"]) && $NOTICE_ID = (int)$_GET["notice_id"]) {
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/notices?".replace_query(array("section" => "edit","id"=>$NOTICE_ID)), "title" => "Editing Notice");
		$BREADCRUMB[] = array("url" => "", "title" => "Notice Statistics");

		$read_users = array(
			"proxy_id" => array(),
			"timestamp" => array()
		);

		$query = "SELECT *, MIN(`timestamp`) AS `timestamp` FROM `statistics` 
            WHERE `module` = 'notices' 
            AND `action` = 'read' 
            AND `action_field` = 'notice_id' 
            AND `action_value` = ".$db->qstr($NOTICE_ID)."
            GROUP BY `proxy_id`";
		$reads = $db->GetAll($query);

		if ($reads) {
			foreach ($reads as $read) {
				$read_users["proxy_id"][] = $read["proxy_id"];
				$read_users["timestamp"][] = $read["timestamp"];
			}
		}

		$audience = array();
		$query = "SELECT * FROM `notice_audience` WHERE `notice_id` = ".$db->qstr($NOTICE_ID);
		$audience_members = $db->GetAll($query);
		if ($audience_members) {
			foreach ($audience_members as $member) {
				switch ($member["audience_type"]) {
					case "cohort" :
					case "course_list" :
						$query = "SELECT a.*, CONCAT_WS(', ',b.`lastname`,b.`firstname`) as `fullname` FROM `group_members` AS a JOIN `".AUTH_DATABASE."`.`user_data` AS b ON a.`proxy_id` = b.`id` WHERE `group_id` = ".$db->qstr($member["audience_value"]);
						$group_mmbrs = $db->GetAll($query);
						if ($group_mmbrs) {
							foreach ($group_mmbrs as $gmember) {
								$audience[$gmember["proxy_id"]] = $gmember["fullname"];
							}
						}
					break;
					case "staff" :
					case "faculty" :
					case "student" :
						$query = "SELECT CONCAT_WS(', ',`lastname`,`firstname`) as `fullname` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($member["audience_value"]);
						$fullname = $db->GetOne($query);
						if ($fullname) {
							$audience[$member["audience_value"]] = $fullname;
						}
					break;
					case "all:faculty" :
						$query = "SELECT CONCAT_WS(', ',a.`lastname`,a.`firstname`) AS `fullname`,a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a JOIN `".AUTH_DATABASE."`.`user_access` AS b ON a.`id` = b.`user_id` WHERE b.`group` = 'faculty' AND (b.`access_expires` = 0 OR b.`access_expires` > ".time().") AND b.`app_id` = ".$db->qstr(AUTH_APP_ID);
						$users = $db->GetAll($query);
						if ($users) {
							foreach ($users as $user) {
								$audience[$user["proxy_id"]] = $user["fullname"];
							}
						}
					break;
					case "all:staff" :
						$query = "SELECT CONCAT_WS(', ',a.`lastname`,a.`firstname`) AS `fullname`,a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a JOIN `".AUTH_DATABASE."`.`user_access` AS b ON a.`id` = b.`user_id` WHERE b.`group` IN ('medtech','staff') AND (b.`access_expires` = 0 OR b.`access_expires` > ".time().") AND b.`app_id` = ".$db->qstr(AUTH_APP_ID);
						$users = $db->GetAll($query);
						if ($users) {
							foreach ($users as $user) {
								$audience[$user["proxy_id"]] = $user["fullname"];
							}
						}
					break;
					case "all:students" :
						$query = "SELECT CONCAT_WS(', ',a.`lastname`,a.`firstname`) AS `fullname`,a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a JOIN `".AUTH_DATABASE."`.`user_access` AS b ON a.`id` = b.`user_id` WHERE b.`group` = 'student' AND (b.`access_expires` = 0 OR b.`access_expires` > ".time().") AND b.`app_id` = ".$db->qstr(AUTH_APP_ID);
						$users = $db->GetAll($query);
						if ($users) {
							foreach ($users as $user) {
								$audience[$user["proxy_id"]] = $user["fullname"];
							}
						}
					break;
					case "all:users" :
						$query = "SELECT CONCAT_WS(', ',a.`lastname`,a.`firstname`) AS `fullname`,a.`id` AS `proxy_id` FROM `".AUTH_DATABASE."`.`user_data` AS a JOIN `".AUTH_DATABASE."`.`user_access` AS b ON a.`id` = b.`user_id` WHERE (b.`access_expires` = 0 OR b.`access_expires` > ".time().") AND b.`app_id` = ".$db->qstr(AUTH_APP_ID);
						$users = $db->GetAll($query);
						if ($users) {
							foreach ($users as $user) {
								$audience[$user["proxy_id"]] = $user["fullname"];
							}
						}
					break;
					default :
						continue;
					break;
				}
			}
		}
		?>
		<h1>Notice Statistics</h1>

		<h2 title="Read Notice Section">People who have read this notice</h2>
		<div id="read-notice-section">
			<?php
			$display_read = false;
			if (isset($read_users["proxy_id"]) && !empty($read_users["proxy_id"])) {
				foreach ($read_users["proxy_id"] as $key => $proxy_id) {
					if (array_key_exists($proxy_id, $audience)) {
						$display_read = true;
					}
				}
			}

			if ($display_read) {
				?>
				<table class="tableList" cellspacing="0" summary="List of users who have marked this notice as read.">
					<colgroup>
						<col class="modified" />
						<col class="title" />
						<col class="date" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="title">Full Name</td>
							<td class="date">Timestamp</td>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($read_users["proxy_id"] as $key => $proxy_id) {
							if (array_key_exists($proxy_id, $audience)) {
								echo "<tr id=\"notice-".$result["notice_id"]."\" class=\"notice".(($expired) ? " na" : "")."\">\n";
								echo "	<td class=\"modified\">&nbsp;</td>\n";
								echo "	<td class=\"title\">".$audience[$proxy_id]."</td>\n";
								echo "	<td class=\"date\">".date("F jS, Y g:i a",$read_users["timestamp"][$key])."</td>\n";
								echo "</tr>\n";
							}
						}
						?>
					</tbody>
				</table>
				<?php
			} else {
				?>
				<div class="display-generic">
					No one as currently marked this message as read.
					<br /><br />
					<strong>Please Note:</strong> that it is possible that users have read the notice but not checked off the &quot;Mark As Read&quot;.
				</div>
				<?php
			}
			?>
		</div>

		<h2 title="Unread Notice Section">People who have not read this notice</h2>
		<div id="unread-notice-section">
			<?php
			if ($audience) {
				$proxy_ids = array_keys($audience);
				$missing = false;

				foreach ($proxy_ids as $id) {
					if (!in_array($id, $read_users["proxy_id"])) {
						$missing = true;
						break;
					}
				}

				if ($missing && $audience) {
					?>
					<table class="tableList" cellspacing="0" summary="List of users who have not marked this notice as read.">
						<colgroup>
							<col class="modified" />
							<col class="title" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title">Full Name</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($audience as $proxy_id => $user) {
								if (!in_array($proxy_id, $read_users["proxy_id"])) {
									echo "<tr id=\"notice-".$result["notice_id"]."\" class=\"notice".(($expired) ? " na" : "")."\">\n";
									echo "	<td class=\"modified\">&nbsp;</td>\n";
									echo "	<td class=\"title\">".$user."</td>\n";
									echo "</tr>\n";
								}
							}
							?>
						</tbody>
					</table>
					<?php
				} else {
					add_success("All selected notice recipients have marked this message as read.");

					echo display_success();
				}
			} else {
				add_notice("There were no notice recipients found for this notice.");

				echo display_notice();
			}
			?>
		</div>
		<?php
	}
}