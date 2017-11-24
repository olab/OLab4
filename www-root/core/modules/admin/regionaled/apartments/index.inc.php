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
 * The default file that is loaded when /admin/regionaled/apartments is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_APARTMENTS")) {
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
	?>
	<h1>Manage Apartments</h1>
	<?php
	if ($ENTRADA_ACL->amIAllowed("regionaled", "create", false)) {
		?>
		<div style="float: right">
			<ul class="page-action">
				<li><a href="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments?section=add" class="strong-green">Add New Apartment</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
		<?php
	}

	$query		= "	SELECT a.*, b.*
					FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
					JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
					ON b.`region_id` = a.`region_id`
					JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` c
					ON c.`apartment_id` = a.`apartment_id`
					WHERE (a.`available_finish` = 0 OR a.`available_finish` > ".$db->qstr(time()).")
					AND c.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId()) . "
					ORDER BY b.`region_name`, a.`apartment_title` ASC";
	$results	= $db->GetAll($query);
	if ($results) {
		?>
		<h2 title="Active Apartments Section">Active Apartments</h2>
		<div id="active-apartments-section">
			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments?section=delete" method="post">
				<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of Aparments">
					<colgroup>
						<col class="modified" />
						<col class="general" />
						<col class="title" />
						<col class="date" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="general">City / Region</td>
							<td class="title">Apartment Title</td>
							<td class="date">Available Until</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="3" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Expire Selected" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$url = ENTRADA_URL."/admin/regionaled/apartments/manage?id=".(int) $result["apartment_id"];

							echo "<tr>\n";
							echo "	<td><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["apartment_id"]."\" /></td>\n";
							echo "	<td><a href=\"".$url."\">".html_encode($result["region_name"])."</a></td>\n";
							echo "	<td><a href=\"".$url."\">".html_encode($result["apartment_title"])."</a></td>\n";
							echo "	<td class=\"content-small\">".(($result["available_finish"] > 0) ? date(DEFAULT_DATE_FORMAT, $result["available_finish"]) : "No expiry date")."</td>\n";
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
		$NOTICESTR[] = "There are no apartments in the system to manage.<br /><br />Please click the &quot;Add Apartment&quot; to begin.";

		echo display_notice($NOTICESTR);
	}

	$query		= "	SELECT a.*, b.*
					FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
					ON b.`region_id` = a.`region_id`
					JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` c
					ON c.`apartment_id` = a.`apartment_id`
					WHERE (a.`available_finish` > 0 AND a.`available_finish` <= ".$db->qstr(time()).")
					AND c.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId()) . "
					ORDER BY b.`region_name`, a.`apartment_title` ASC";
	$results	= $db->GetAll($query);
	if ($results) {
		?>
		<h2 title="Expired Apartments Section" class="collapsed">Expired Apartments</h2>
		<div id="expired-apartments-section">
			<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of Aparments">
			<colgroup>
				<col class="modified" />
				<col class="general" />
				<col class="title" />
				<col class="date" />
			</colgroup>
			<thead>
				<tr>
					<td class="modified">&nbsp;</td>
					<td class="general">City / Region</td>
					<td class="title">Apartment Title</td>
					<td class="date">Expired</td>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($results as $result) {
					$url = ENTRADA_URL."/admin/regionaled/apartments/manage?id=".(int) $result["apartment_id"];

					echo "<tr>\n";
					echo "	<td>&nbsp;</td>\n";
					echo "	<td><a href=\"".$url."\" class=\"content-small\">".html_encode($result["region_name"])."</a></td>\n";
					echo "	<td><a href=\"".$url."\" class=\"content-small\">".html_encode($result["apartment_title"])."</a></td>\n";
					echo "	<td class=\"content-small\">".date(DEFAULT_DATE_FORMAT, $result["available_finish"])."</td>\n";
					echo "</tr>\n";
				}
				?>
			</tbody>
			</table>
		</div>
		<?php
	}
}