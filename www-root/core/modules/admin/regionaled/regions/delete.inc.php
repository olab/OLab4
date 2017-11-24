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
		$region_ids = array();

		foreach ($_POST["delete"] as $region_id) {
			if ($region_id = clean_input($region_id, array("nows", "int"))) {
				$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`regions` WHERE `region_id` = ".$db->qstr($region_id)." AND `region_active` = '1'";
				$result = $db->GetRow($query);
				if ($result) {
					$region_ids[] = $region_id;
				}
			}
		}

		if (count($region_ids)) {
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled/regions", "title" => "Disable Regions");

			switch ($STEP) {
				case 2 :
					$url = ENTRADA_URL."/admin/regionaled/regions";
					$msg = "You will now be redirected back to the Manage Regions index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

					if (($db->AutoExecute(CLERKSHIP_DATABASE.".regions", array("region_active" => 0, "updated_date" => time(), "updated_by" => $PROXY_ID), "UPDATE", "region_id IN (".implode(", ", $region_ids).")")) && ($updated = $db->Affected_Rows())) {
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully disabled ".$updated." region".(($updated != 1) ? "s" : "").".<br /><br />".$msg;

						echo display_success();
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to disable the requested regions at this time. The administrator has been notified of the error, please try again later.<br /><br />".$msg;

						echo display_error();

						application_log("error", "Unable to set region_active to 0 for the following region_ids [".implode(", ", $region_ids)."]. Database said: ".$db->ErrorMsg());
					}
				break;
				case 1 :
				default :
					$query = "	SELECT a.*, b.`province`, c.`country`
								FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
								LEFT JOIN `global_lu_provinces` AS b
								ON b.`province_id` = a.`province_id`
								LEFT JOIN `global_lu_countries` AS c
								ON c.`countries_id` = a.`countries_id`
								WHERE a.`region_id` IN (".implode(", ", $region_ids).")
								ORDER BY c.`country`, b.`province`, a.`prov_state`, a.`region_name` ASC, a.`manage_apartments` DESC";
					$results = $db->GetAll($query);
					if ($results) {
						?>
						<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions?section=delete" method="post">
							<input type="hidden" name="step" value="2" />
							<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of regions being removed.">
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
											<input type="submit" class="btn btn-danger" value="Confirm Removal" />
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									foreach($results as $result) {
										$click_url = ENTRADA_URL."/admin/regionaled/regions?section=edit&id=".(int) $result["region_id"];

										echo "<tr>\n";
										echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["region_id"]."\" checked=\"checked\" /></td>\n";
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
						<?php
					} else {
						application_log("notice", "The region removeal page was accessed without providing any valid event_ids to remove.");

						header("Location: ".ENTRADA_URL."/admin/regionaled/regions");
						exit;
					}
				break;
			}
		} else {
			application_log("notice", "TThe region removeal page was accessed without providing any valid event_ids to remove.");

			header("Location: ".ENTRADA_URL."/admin/regionaled/regions");
			exit;
		}
	} else {
		application_log("notice", "The region removeal page was accessed without providing any event_ids to remove.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/regions");
		exit;
	}
}