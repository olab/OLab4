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
 * Allows administrators to deactivate or expire apartments from entrada_clerkship.apartments table. Apartments are
 * never deleted as we need to keep them for data integrity.
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
	if (isset($_POST["delete"]) && is_array($_POST["delete"]) && count($_POST["delete"])) {
		$apartment_ids = array();
		$region_ids = array();

		foreach ($_POST["delete"] as $apartment_id) {
			if ($tmp_input = clean_input($apartment_id, array("nows", "int"))) {
				$query = "	SELECT * 
							FROM `".CLERKSHIP_DATABASE."`.`apartments` a
							JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` b
							ON b.`apartment_id` = a.`apartment_id`
							WHERE a.`apartment_id` = ".$db->qstr($tmp_input) . "
							AND b.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
				$result = $db->GetRow($query);
				if ($result) {
					$apartment_ids[] = $tmp_input;

					if (!in_array($result["region_id"], $region_ids)) {
						$region_ids[] = $result["region_id"];
					}
				}
			}
		}
		
		if (count($apartment_ids)) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/regionaled/apartments", "title" => "Expire Apartments");

			switch ($STEP) {
				case 2 :
					$url = ENTRADA_URL."/admin/regionaled/apartments";
					$msg = "You will now be redirected back to the " . $APARTMENT_INFO["department_title"] . " Apartment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

					$PROCESSED = array();
					$PROCESSED["available_finish"] = time();
					$PROCESSED["updated_date"] = time();
					$PROCESSED["updated_by"] = $PROXY_ID;

					if (($db->AutoExecute(CLERKSHIP_DATABASE.".apartments", $PROCESSED, "UPDATE", "apartment_id IN (".implode(", ", $apartment_ids).")")) && ($updated = $db->Affected_Rows())) {
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully expired ".$updated." apartment".(($updated != 1) ? "s" : "").".<br /><br />".$msg;

						echo display_success();

						/**
						 * Go through the regions of each apartment that was expired to ensure there are still apartments
						 * available. If there are not, then turn off the manage_apartments flag for the region, and
						 * unset the requires_apartment flag for anyone whose event is in that region.
						 */
						if (count($region_ids)) {
							foreach ($region_ids as $region_id) {
								$query = "	SELECT *
											FROM `".CLERKSHIP_DATABASE."`.`apartments`
											WHERE `region_id` = ".$db->qstr($region_id)."
											AND (`available_finish` = '0' OR `available_finish` > ".$db->qstr(time()).")";
								$result = $db->GetRow($query);
								if (!$result) {
									$query = "UPDATE `".CLERKSHIP_DATABASE."`.`regions` SET `manage_apartments` = '0' WHERE `region_id` = ".$db->qstr($region_id)." AND `manage_apartments` = '1'";
									if (($db->Execute($query)) && ($updated = $db->Affected_Rows())) {
										$query = "UPDATE `".CLERKSHIP_DATABASE."`.`events` SET `requires_apartment` = '0' WHERE `region_id` = ".$db->qstr($region_id)." AND `requires_apartment` = '1'";
										if (!$db->Execute($query)) {
											application_log("error", "Unable to set events.requires_apartment to 0 when expiring the last apartment from a region. Database said: ".$db->ErrorMsg());
										}
									} else {
										application_log("error", "Unable to set regions.manage_apartments to 0 when expiring the last apartment from a region. Database said: ".$db->ErrorMsg());
									}
								}
							}
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to expire the requested apartments at this time. The administrator has been notified of the error, please try again later.<br /><br />".$msg;

						echo display_error();

						application_log("error", "Unable to expire apartment_ids [".implode(", ", $apartment_ids)."]. Database said: ".$db->ErrorMsg());
					}
				break;
				case 1 :
				default :
					$query = "	SELECT a.*, b.*
								FROM `".CLERKSHIP_DATABASE."`.`apartments` AS a
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS b
								ON b.`region_id` = a.`region_id`
								WHERE (a.`available_finish` = 0 OR a.`available_finish` > ".$db->qstr(time()).")
								AND a.`apartment_id` IN (".implode(", ", $apartment_ids).")
								ORDER BY b.`region_name`, a.`apartment_title` ASC";
					$results = $db->GetAll($query);
					if ($results) {
						$total_rows = count($results);
						?>
						<div class="display-notice">
							Please confirm that you wish to expire the following apartment<?php echo (($total_rows != 1) ? "s" : ""); ?>. By expiring an apartment it will no longer be available when assigning new accommodations to learners, although it will remain visible to existing occupants.
						</div>
						<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments" method="post">
							<input type="hidden" name="section" value="delete" />
							<input type="hidden" name="step" value="2" />
							<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of aparments being expired.">
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
											<input type="submit" class="btn btn-danger" value="Confirm Removal" />
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									foreach($results as $result) {
										$url = ENTRADA_URL."/admin/regionaled/apartments/manage?id=".(int) $result["apartment_id"];

										echo "<tr>\n";
										echo "	<td><input type=\"checkbox\" name=\"delete[]\" value=\"".(int) $result["apartment_id"]."\" checked=\"checked\" /></td>\n";
										echo "	<td><a href=\"".$url."\">".html_encode($result["region_name"])."</a></td>\n";
										echo "	<td><a href=\"".$url."\">".html_encode($result["apartment_title"])."</a></td>\n";
										echo "	<td class=\"content-small\">".(($result["available_finish"] > 0) ? date(DEFAULT_DATE_FORMAT, $result["available_finish"]) : "No expiry date")."</td>\n";
										echo "</tr>\n";
									}
									?>
								</tbody>
							</table>
						</form>
						<?php
					} else {
						application_log("notice", "The " . $APARTMENT_INFO["department_title"] . " apartment expiration page was accessed without providing any valid apartment_ids to remove.");

						header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
						exit;
					}
				break;
			}
		} else {
			application_log("notice", "The " . $APARTMENT_INFO["department_title"] . " apartment expiration page was accessed without providing any valid apartment_ids to remove.");

			header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
			exit;
		}
	} else {
		application_log("notice", "The " . $APARTMENT_INFO["department_title"] . " apartment expiration page was accessed without providing any apartment_ids to remove.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
		exit;
	}
}