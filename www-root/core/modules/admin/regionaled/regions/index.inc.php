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
 * The default file that is loaded when /admin/regionaled/regions is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONS")) {
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
	/**
	 * Show a list of countries that have regions in them.
	 */
	$query = "SELECT * FROM `global_lu_countries` ORDER BY `country` ASC";
	$countries = $db->GetAll($query);
	if ($countries) {
		echo "<div style=\"float: right\">\n";
		echo "	<form id=\"changeCountry\">\n";
		echo "		<label for=\"change_countries_id\" class=\"form-nrequired\">Select Country</label>\n";
		echo "		<select id=\"change_countries_id\" style=\"margin-left: 2px; width: 250px\" onchange=\"window.location = '".ENTRADA_URL."/admin/regionaled/regions?country=' + \$F('change_countries_id')\">\n";
		foreach ($countries as $country) {
			echo "		<option value=\"".(int) $country["countries_id"]."\"".(($country["countries_id"] == $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"]) ? " selected=\"selected\"" : "").">".html_encode($country["country"])."</option>\n";
		}
		echo "		</select>\n";
		echo "	</form>\n";
		echo "</div>\n";
		echo "<div class=\"clear\"></div>\n";
	}
	?>
	<h1>Manage Regions</h1>
	<?php
	if ($ENTRADA_ACL->amIAllowed("regionaled", "create", false)) {
		?>
		<div style="float: right">
			<ul class="page-action">
				<li><a href="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions?section=add" class="strong-green">Add New Region</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
		<?php
	}
	
	$query = "	SELECT a.*, b.`province`, c.`country`
				FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
				LEFT JOIN `global_lu_provinces` AS b
				ON b.`province_id` = a.`province_id`
				LEFT JOIN `global_lu_countries` AS c
				ON c.`countries_id` = a.`countries_id`
				WHERE a.`countries_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"])."
				AND a.`region_active` = '1'
				ORDER BY c.`country`, b.`province`, a.`prov_state`, a.`region_name` ASC, a.`manage_apartments` DESC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<h2 title="Active Regions Section">Active Regions in <?php echo html_encode($results[0]["country"]); ?></h2>
		<div id="active-regions-section">
			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions?section=delete" method="post">
				<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of active regions in <?php echo html_encode($results[0]["country"]); ?>">
					<colgroup>
						<col class="modified" />
						<col class="general" />
						<col class="title" />
						<col class="title" />
						<col class="general" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="general">Country</td>
							<td class="title">Province</td>
							<td class="title">City</td>
							<td class="general">Managed Region</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="4" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Disable Selected" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$click_url = ENTRADA_URL."/admin/regionaled/regions?section=edit&id=".(int) $result["region_id"];
							
							echo "<tr>\n";
							echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["region_id"]."\" /></td>\n";
							echo "	<td class=\"general\"><a href=\"".$click_url."\">".html_encode($result["country"])."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\">".html_encode(($result["province"] ? $result["province"] : $result["prov_state"]))."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\">".html_encode($result["region_name"])."</a></td>\n";
							echo "	<td class=\"general\">".(($result["manage_apartments"] == "1") ? "Yes (Managed)" : "")."</td>\n";
							echo "</tr>\n";
						}
						?>
					</tbody>
				</table>
			</form>
		</div>
		<?php
	} else {
		echo display_notice("There do not appear to be any active regions available. To add a new region click <strong>Add New Region</strong>.");
	}

	$query = "	SELECT a.*, b.`province`, c.`country`
				FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
				LEFT JOIN `global_lu_provinces` AS b
				ON b.`province_id` = a.`province_id`
				LEFT JOIN `global_lu_countries` AS c
				ON c.`countries_id` = a.`countries_id`
				WHERE a.`countries_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"])."
				AND a.`region_active` = '0'
				ORDER BY c.`country`, b.`province`, a.`prov_state`, a.`region_name` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<h2 title="Disabled Regions Section" class="collapsed">Disabled Regions in <?php echo html_encode($results[0]["country"]); ?></h2>
		<div id="disabled-regions-section">
			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions?section=activate" method="post">
				<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of disabled regions in <?php echo html_encode($results[0]["country"]); ?>">
					<colgroup>
						<col class="modified" />
						<col class="general" />
						<col class="title" />
						<col class="title" />
						<col class="general" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="general">Country</td>
							<td class="title">Province</td>
							<td class="title">City</td>
							<td class="general">Managed Region</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="4" style="padding-top: 10px">
								<input type="submit" class="btn btn-primary" value="Activate Selected" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$click_url = ENTRADA_URL."/admin/regionaled/regions?section=edit&id=".(int) $result["region_id"];

							echo "<tr>\n";
							echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["region_id"]."\" /></td>\n";
							echo "	<td class=\"general\"><a href=\"".$click_url."\" class=\"content-small\">".html_encode($result["country"])."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\" class=\"content-small\">".html_encode($result["province"])."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\" class=\"content-small\">".html_encode($result["region_name"])."</a></td>\n";
							echo "	<td class=\"general content-small\">".(($result["manage_apartments"] == "1") ? "Yes (Managed)" : "")."</td>\n";
							echo "</tr>\n";
						}
						?>
					</tbody>
				</table>
			</form>
		</div>
		<?php
	}
}