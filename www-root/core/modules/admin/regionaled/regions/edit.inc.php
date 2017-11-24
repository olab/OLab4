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
 * Allows administrators to edit regions in the entrada_clerkship.regions table.
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
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/regionaled/regions", "title" => "Edit Region");

	$region_id = 0;

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
		$region_id = $tmp_input;
	}

	if ($region_id) {
		$query = "	SELECT a.*, b.`province`, c.`country`
					FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
					LEFT JOIN `global_lu_provinces` AS b
					ON b.`province_id` = a.`province_id`
					LEFT JOIN `global_lu_countries` AS c
					ON c.`countries_id` = a.`countries_id`
					WHERE a.`region_id` = ".$db->qstr($region_id);
		$region_info = $db->GetRow($query);
		if ($region_info) {
			switch ($STEP) {
				case 2 :
					if ((isset($_POST["countries_id"])) && ($tmp_input = clean_input($_POST["countries_id"], "int"))) {
						$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = ".$db->qstr($tmp_input);
						$result = $db->GetRow($query);
						if ($result) {
							$PROCESSED["countries_id"] = $tmp_input;

							if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
								$PROCESSED["province_id"] = 0;
								$PROCESSED["prov_state"] = "";

								if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
									$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = ".$db->qstr($tmp_input)." AND `country_id` = ".$db->qstr($PROCESSED["countries_id"]);
									$result = $db->GetRow($query);
									if ($result) {
										$PROCESSED["province_id"] = $tmp_input;
									} else {
										$ERROR++;
										$ERRORSTR[] = "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.";
									}
								} else {
									$PROCESSED["prov_state"] = $tmp_input;
								}
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "The selected country does not exist in our countries database. Please select a valid country.";

							application_log("error", "Unknown countries_id [".$tmp_input."] was selected. Database said: ".$db->ErrorMsg());
						}
					} else {
						$ERROR++;
						$ERRORSTR[]	= "You must select the country that this region resides is in.";
					}

					if ((isset($_POST["region_name"])) && ($tmp_input = clean_input($_POST["region_name"], array("trim", "notags")))) {
						$PROCESSED["region_name"] = $tmp_input;
					} else {
						$ERROR++;
						$ERRORSTR[] = "You must enter a city / region in.";
					}

					if ((isset($_POST["manage_apartments"])) && ($_POST["manage_apartments"] == "1")) {
						$PROCESSED["manage_apartments"] = 1;
					} else {
						$PROCESSED["manage_apartments"] = 0;
					}

					if ((isset($_POST["is_core"])) && ($_POST["is_core"] == "1")) {
						$PROCESSED["is_core"] = 1;
					} else {
						$PROCESSED["is_core"] = 0;
					}

					/**
					 * Allow non-active regions to be enabled, but never allow them to disabled from here.
					 */
					if ((isset($_POST["region_active"])) && ($_POST["region_active"] == "1") && ($region_info["region_active"] == 0)) {
						$PROCESSED["region_active"] = 1;
					} else {
						$PROCESSED["region_active"] = $region_info["region_active"];
					}

					if (!$ERROR) {
						$PROCESSED["updated_date"] = time();
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

						if ($db->AutoExecute(CLERKSHIP_DATABASE.".regions", $PROCESSED, "UPDATE", "region_id = ".$db->qstr($region_id))) {
							$SUCCESS++;
							$SUCCESSSTR[] = "You have successfully updated <strong>".html_encode($PROCESSED["region_name"])."</strong>.<br /><br />You will now be redirected to the Manage Regions index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/regionaled/regions\" style=\"font-weight: bold\">click here</a> to continue.";

							$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/regionaled/regions\\'', 5000)";

							application_log("success", "Region_id [".$region_id."] was updated in the system.");
						} else {
							$ERROR++;
							$ERRORSTR[]	= "We were unable to update this region at this time.<br /><br />The system administrator has been notified of this issue, please try again later.";

							application_log("error", "Failed to update region_id [".$region_id."]. Database said: ".$db->ErrorMsg());
						}
					}

					if ($ERROR) {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					$PROCESSED = $region_info;
				break;
			}

			switch ($STEP) {
				case 2 :
					if ($ERROR) {
						echo display_errors();
					}

					if ($NOTICE) {
						echo display_notices();
					}

					if ($SUCCESS) {
						echo display_success();
					}
				break;
				case 1 :
				default :
					$ONLOAD[] = "provStateFunction(\$F($('addRegionForm')['countries_id']))";
					?>
					<h1>Edit Region</h1>
					<?php
					if ($ERROR) {
						echo display_error();
					}
					?>
					<script type="text/javascript">
					function provStateFunction(country_id) {
						var url='<?php echo webservice_url("province"); ?>';
						<?php
                        if ((isset($PROCESSED["province"]) && $PROCESSED["province"]) || (isset($PROCESSED["province_id"]) && $PROCESSED["province_id"])) {
                            $source_arr = $PROCESSED;
                        } elseif (isset($region_info["province_id"]) && $region_info["province_id"]) {
                            $source_arr = $region_info;
                        } else {
                            $source_arr = $_SESSION[APPLICATION_IDENTIFIER][$MODULE];
                        }
                        if (isset($source_arr["province"]) && $source_arr["province"]) {
                            $province = $source_arr["province"];
                        } elseif (isset($source_arr["province_id"]) && $source_arr["province_id"]) {
                            $province_id = $source_arr["province_id"];
                        }
                        $prov_state = (isset($province) && $province ? $province : $province_id);
						?>

						url = url + '?countries_id=' + country_id + '&prov_state=<?php echo $prov_state; ?>';
						new Ajax.Updater($('prov_state_div'), url,
							{
								method:'get',
								onComplete: function (init_run) {

									if ($('prov_state').type == 'select-one') {
										$('prov_state_label').removeClassName('form-nrequired');
										$('prov_state_label').addClassName('form-required');
										if (!init_run)
											$("prov_state").selectedIndex = 0;


									} else {

										$('prov_state_label').removeClassName('form-required');
										$('prov_state_label').addClassName('form-nrequired');
										if (!init_run)
											$("prov_state").clear();


									}
								}.curry(!provStateFunction.initialzed)
							});
						provStateFunction.initialzed = true;

					}
					provStateFunction.initialzed = false;
					</script>

					<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions?section=edit&id=<?php echo $region_id; ?>" method="post" id="addRegionForm">
						<input type="hidden" id="step" name="step" value="2" />
						<table style="width: 100%" cellspacing="0" cellpadding="2" summary="Add Region Form">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 20%" />
								<col style="width: 77%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="3" style="padding-top: 25px; text-align: right">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled/regions'" />
										<input type="submit" class="btn btn-primary" value="Save" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td>&nbsp;</td>
									<td><label for="countries_id" class="form-required">Country</label></td>
									<td>
										<?php
										$countries = fetch_countries();
										if ((is_array($countries)) && (count($countries))) {
											echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 256px\" onchange=\"provStateFunction(this.value);\">\n";
											foreach ($countries as $country) {
												echo "<option value=\"".(int) $country["countries_id"]."\"".(($PROCESSED["countries_id"] == $country["countries_id"]) ? " selected=\"selected\"" : (!isset($PROCESSED["countries_id"]) && $country["countries_id"] == $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["country"]) ? " selected=\"selected\"" : "").">".html_encode($country["country"])."</option>\n";
											}
											echo "</select>\n";
										} else {
											echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
											echo "Country information not currently available.\n";
										}
										?>
									</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td><label id="prov_state_label" for="prov_state_div" class="form-required">Province / State</label></td>
									<td>
										<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
									</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td><label for="region_name" class="form-required">Region Name</label></td>
									<td>
										<input type="text" id="region_name" name="region_name" size="100" autocomplete="off" style="width: 250px; vertical-align: middle" value="<?php echo html_encode($PROCESSED["region_name"]); ?>" />
									</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td><input type="checkbox" id="is_core" name="is_core" value="1"<?php echo ((isset($PROCESSED["is_core"]) && ($PROCESSED["is_core"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td colspan="2"><label for="is_core" class="form-nrequired">Clerkship core rotations can take place in this region.</label></td>
								</tr>
								<tr>
									<td><input type="checkbox" id="manage_apartments" name="manage_apartments" value="1"<?php echo ((isset($PROCESSED["manage_apartments"]) && ($PROCESSED["manage_apartments"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td colspan="2"><label for="manage_apartments" class="form-nrequired">This region contains managed accommodations.</label></td>
								</tr>
								<?php if ($region_info["region_active"] == "0") : ?>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td><input type="checkbox" id="region_active" name="region_active" value="1"<?php echo ((isset($PROCESSED["region_active"]) && ($PROCESSED["region_active"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td colspan="2"><label for="region_active" class="form-nrequired"><strong>Reactivate this region</strong> so it appears in the available regions list.</label></td>
								</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</form>
					<?php
				break;
			}
		} else {
			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/regionaled/regions\\'', 5000)";

			application_log("notice", "Someone attempted to edit a region that does not exist [".$region_id."]. Database said: ".$db->ErrorMsg());

			$ERROR++;
			$ERRORSTR[] = "The region you are trying to manage does not exist in the system.";

			echo display_error();
		}
	} else {
		application_log("notice", "Someone attempted to edit a region without providing a region_id.");

		header("Location: ".ENTRADA_URL."/admin/regionaled/apartments");
		exit;
	}
}