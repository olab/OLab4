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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */
if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: " . ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

	echo display_error();

	application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $PROCESSED = array();

	$BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/settings?section=edit&amp;org=" . $ORGANISATION['organisation_id'], "title" => "Edit Organisation");

	switch ($STEP) {
		case 2 :
			$PROCESSED["organisation_active"] = 1;

			if (isset($_POST["organisation_title"]) && ($tmp_input = clean_input($_POST["organisation_title"], array("trim", "notags")))) {
				$PROCESSED["organisation_title"] = $tmp_input;
			} else {
				add_error("You must provide a name for this organisation.");
			}

			if ((isset($_POST["countries_id"])) && ($tmp_input = clean_input($_POST["countries_id"], "int"))) {
				$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = " . $db->qstr($tmp_input);
				$result = $db->GetRow($query);
				if ($result) {
					$PROCESSED["countries_id"] = $tmp_input;
					$PROCESSED["organisation_country"] = $result["country"];
                    $PROCESSED["province_id"] = 0;
                    $PROCESSED["organisation_province"] = "";

                    if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
						if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
							$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = " . $db->qstr($tmp_input) . " AND `country_id` = " . $db->qstr($PROCESSED["countries_id"]);
							$result = $db->GetRow($query);
							if ($result) {
								$PROCESSED["province_id"] = $tmp_input;
								$PROCESSED["organisation_province"] = $result["abbreviation"];
							} else {
								add_error("The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.");
							}
						} else {
							$PROCESSED["organisation_province"] = $tmp_input;
						}
					}
				} else {
					add_error("The selected country does not exist in our countries database. Please select a valid country.");
					application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
				}
			} else {
				add_error("The selected country does not exist in our countries database. Please select a valid country.");

				application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
			}

			if (isset($_POST["organisation_city"]) && ($tmp_input = clean_input($_POST["organisation_city"], array("trim", "notags")))) {
				$PROCESSED["organisation_city"] = $tmp_input;
			} else {
				add_error("You must provide a city for this organisation.");
			}

			if (isset($_POST["organisation_postcode"]) && ($tmp_input = clean_input($_POST["organisation_postcode"], array("trim", "notags")))) {
				$PROCESSED["organisation_postcode"] = $tmp_input;
			} else {
				add_error("You must provide a postal code for this organisation.");
			}

			if (isset($_POST["organisation_address1"]) && ($tmp_input = clean_input($_POST["organisation_address1"], array("trim", "notags")))) {
				$PROCESSED["organisation_address1"] = $tmp_input;
			} else {
				add_error("You must provide an address for this organisation.");
			}

			if (isset($_POST["organisation_address2"]) && ($tmp_input = clean_input($_POST["organisation_address2"], array("trim", "notags")))) {
				$PROCESSED["organisation_address2"] = $tmp_input;
			}

			if (isset($_POST["organisation_telephone"]) && ($tmp_input = clean_input($_POST["organisation_telephone"], array("trim", "notags")))) {
				$PROCESSED["organisation_telephone"] = $tmp_input;
			} else {
				add_error("You must provide a telephone number for this organisation.");
			}

			if (isset($_POST["organisation_fax"]) && ($tmp_input = clean_input($_POST["organisation_fax"], array("trim", "notags")))) {
				$PROCESSED["organisation_fax"] = $tmp_input;
			}

			if (isset($_POST["organisation_email"]) && ($tmp_input = clean_input($_POST["organisation_email"], array("trim", "notags")))) {
				$PROCESSED["organisation_email"] = $tmp_input;
			}

			if (isset($_POST["organisation_url"]) && ($tmp_input = clean_input($_POST["organisation_url"], array("trim", "notags")))) {
				$PROCESSED["organisation_url"] = $tmp_input;
			} else {
				$PROCESSED["organisation_url"] = "";
			}

			if (isset($_POST["organisation_twitter"]) && ($tmp_input = clean_input($_POST["organisation_twitter"], array("trim", "notags")))) {
				$PROCESSED["organisation_twitter"] = $tmp_input;
			} else {
				$PROCESSED["organisation_twitter"] = "";
			}

			if (isset($_POST["organisation_hashtags"])) {
				$PROCESSED["organisation_hashtags"] = array();
				foreach ($_POST["organisation_hashtags"] as $index => $tmp_input) {
					if ($organisation_hashtags = clean_input($tmp_input, array("trim", "notags"))) {
						$PROCESSED["organisation_hashtags"][] = $organisation_hashtags;
					}
				}
				$PROCESSED["organisation_hashtags"] = implode(" ", $PROCESSED["organisation_hashtags"]);
			} else {
				$PROCESSED["organisation_hashtags"] = "";
			}

			if (isset($_POST["organisation_desc"]) && ($tmp_input = clean_input($_POST["organisation_desc"], array("trim","notags")))) {
				$PROCESSED["organisation_desc"] = $tmp_input;
			} else {
				$PROCESSED["organisation_desc"] = "";
			}

			if (isset($_POST["template"]) && ($tmp_input = clean_input($_POST["template"], array("trim", "notags")))) {
				$PROCESSED["template"] = $tmp_input;
			} else {
				//Default to the default template if no template selected.
				if ($ENTRADA_TEMPLATE->defaultTemplate()) {
					$PROCESSED["template"] = $ENTRADA_TEMPLATE->defaultTemplate();
				} else {
					add_error("You must select an interface template.");
				}
			}

			if (isset($_POST["aamc_institution_id"]) && ($tmp_input = clean_input($_POST["aamc_institution_id"], array("trim", "notags")))) {
				$PROCESSED["aamc_institution_id"] = $tmp_input;
			} else {
				$PROCESSED["aamc_institution_id"] = NULL;
			}

			if (isset($_POST["aamc_institution_name"]) && ($tmp_input = clean_input($_POST["aamc_institution_name"], array("trim", "notags")))) {
				$PROCESSED["aamc_institution_name"] = $tmp_input;
			} else {
				$PROCESSED["aamc_institution_name"] = NULL;
			}

			if (isset($_POST["aamc_program_id"]) && ($tmp_input = clean_input($_POST["aamc_program_id"], array("trim", "notags")))) {
				$PROCESSED["aamc_program_id"] = $tmp_input;
			} else {
				$PROCESSED["aamc_program_id"] = NULL;
			}

			if (isset($_POST["aamc_program_name"]) && ($tmp_input = clean_input($_POST["aamc_program_name"], array("trim", "notags")))) {
				$PROCESSED["aamc_program_name"] = $tmp_input;
			} else {
				$PROCESSED["aamc_program_name"] = NULL;
			}

			if (!$ERROR) {
				$PROCESSED["updated_last"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute(AUTH_DATABASE . ".organisations", $PROCESSED, "UPDATE", "`organisation_id`=" . $db->qstr($ORGANISATION_ID))) {
					add_success("You have successfully updated <strong>" . html_encode($PROCESSED["organisation_title"]) . "</strong> in the system.<br /><br />You will now be redirected to the organisations index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage?org=" . $ORGANISATION_ID . "\" style=\"font-weight: bold\">click here</a> to continue.");

					$ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/settings/manage?org=" . $ORGANISATION_ID . "\\'', 5000)";

					application_log("success", "Updated information for the organisation [" . $PROCESSED["organisation_title"] . "] in the system.");
				} else {
					add_error("We were unable to update this organisation in the system at this time.<br /><br />The system administrator has been notified of this issue, please try again later.");

					application_log("error", "Failed to update the organisation in the database. Database said: " . $db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
            /*
             * Set the default $PROCESSED array to $ORGANISATION.
             */
            $PROCESSED = $ORGANISATION;

			$query = "SELECT * FROM `global_lu_countries` WHERE `country` = " . $db->qstr($PROCESSED["organisation_country"]);
			$result = $db->GetRow($query);
			if ($result) {
				$PROCESSED["countries_id"] = $result["countries_id"];
			}

			$query = "SELECT * FROM `global_lu_provinces` WHERE `abbreviation` = " . $db->qstr($PROCESSED["organisation_province"]);
			$result = $db->GetRow($query);
			if ($result) {
				$PROCESSED["province_id"] = $result["province_id"];
			}
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
			if ($ENTRADA_ACL->amIAllowed(new ConfigurationResource($ORGANISATION_ID), "update")) {
				$PROCESSED["prov_state"] = ((isset($PROCESSED["province_id"]) && $PROCESSED["province_id"]) ? (int) $PROCESSED["province_id"] : ((isset($PROCESSED["organisation_province"]) && $PROCESSED["organisation_province"]) ? $PROCESSED["organisation_province"] : ""));
				$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/chosen.jquery.min.js\"></script>\n";
				$HEAD[]	= "<link rel=\"stylesheet\" type=\"text/css\"  href=\"".ENTRADA_RELATIVE."/css/jquery/chosen.css\" />\n";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/Twitter.js\"></script>";

				$ONLOAD[] = "provStateFunction(\$F($('editOrganisationForm')['countries_id']))";

				if ($ERROR) {
					echo display_error();
				}
				?>
				<script type="text/javascript">
				function provStateFunction(countries_id) {
					var url = '<?php echo webservice_url("province"); ?>';
					url = url + '?countries_id=' + countries_id + '&prov_state=<?php echo rawurlencode((isset($_POST["prov_state"]) ? clean_input($_POST["prov_state"], array("notags", "trim")) : $PROCESSED["prov_state"])); ?>';
					new Ajax.Updater($('prov_state_div'), url,
					{
						method:'get',
						onComplete: function () {
							generateAutocomplete();

							if ($('prov_state').type == 'select-one') {
								$('prov_state').observe('change', updateAptData);

								$('prov_state_label').removeClassName('form-nrequired');
								$('prov_state_label').addClassName('form-required');
							} else {
								$('prov_state').observe('blur', updateAptData);

								$('prov_state_label').removeClassName('form-required');
								$('prov_state_label').addClassName('form-nrequired');
							}
						}
					});
				}

				function generateAutocomplete() {
					if (updater != null) {
						updater.url = '<?php echo ENTRADA_URL; ?>/api/cities-by-country.api.php?countries_id=' + $F('countries_id');
					} else {
						updater = new Ajax.Autocompleter('city', 'city_auto_complete', '<?php echo ENTRADA_URL; ?>/api/cities-by-country.api.php?countries_id=' + $F('countries_id'), {
							frequency: 0.2,
							minChars: 2,
							afterUpdateElement : getRegionId
						});
					}
				}

				function getRegionId(text, li) {
					if (li.id) {
						$('region_id').setValue(li.id);
					}
				}

				function addPointToMap(lat, lng) {
					if (googleMap && lat != '' && lng != '' && GBrowserIsCompatible()) {
						point = new GLatLng(lat, lng);

						addMarker(point, lat, lng);
					}
				}

				function addAddressToMap(response) {
					if (googleMap && GBrowserIsCompatible()) {
						if (!response || response.Status.code != 200) {
							//						alert("Sorry, we were unable to geocode that address");
						} else {
							place = response.Placemark[0];
							point = new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]);

							addMarker(point, place.Point.coordinates[1], place.Point.coordinates[0]);
						}
					}
				}

				function addMarker(point, lat, lng) {
					if (googleMap && point && lat && lng) {
						if (!$('mapContainer').visible()) {
							$('mapContainer').show();
						}

						googleMap = new GMap2($('mapData'));
						googleMap.setUIToDefault();
						googleMap.setCenter(point, 15);
						googleMap.clearOverlays();

						var icon = new GIcon();
						icon.image = '<?php echo ENTRADA_URL; ?>/images/icon-apartment.gif';
						icon.shadow = '<?php echo ENTRADA_URL; ?>/images/icon-apartment-shadow.png';
						icon.iconSize = new GSize(25, 34);
						icon.shadowSize = new GSize(35, 34);
						icon.iconAnchor = new GPoint(25, 34);
						icon.infoWindowAnchor = new GPoint(15, 5);

						var marker = new GMarker(point, icon);
						googleMap.addOverlay(marker);

						$('apartment_latitude').setValue(lat);
						$('apartment_longitude').setValue(lng);
					}
				}

				function updateAptData() {
					var address = ($('apartment_address') ? $F('apartment_address') : false);
					var country = ($F('countries_id') ? $('countries_id')[$('countries_id').selectedIndex].text : false);
					var city = ($F('city') ? $F('city') : false);

					if ($('prov_state').type == 'select-one' && ($F('prov_state') > 0)) {
						var province = $('prov_state')[$F('prov_state')].text;
					} else if ($('prov_state').type == 'text' && $F('prov_state') != '') {
						var province = $F('prov_state');
					} else {
						var province = false;
					}

					if (googleMap && address && city && country && GBrowserIsCompatible()) {
						var geocoder = new GClientGeocoder();

						var search = [address, city];
						if (province) {
							search.push(province);
						}
						search.push(country);

						searchFor = search.join(', ');

						geocoder.getLocations(searchFor, addAddressToMap);
					}

					if ((address) && ($F('apartment_title') == '')) {
						$('apartment_title').setValue(($F('apartment_number').length > 0 ? $F('apartment_number') + ' - ' : '') + address);
					}

					return false;
				}
				</script>

				<h1>Edit <?php echo html_encode($ORGANISATION["organisation_title"]); ?></h1>

				<form id="editOrganisationForm" action="<?php echo ENTRADA_URL; ?>/admin/settings/manage?section=edit&amp;org=<?php echo $ORGANISATION_ID; ?>&amp;step=2" method="post" class="form-horizontal">
				<div class="control-group">
					<label for="organisation_title" class="control-label form-required">Organisation Name:</label>
					<div class="controls">
						<input type="text" id="organisation_title" name="organisation_title" value="<?php echo html_encode($PROCESSED["organisation_title"]); ?>"/>
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_desc" class="control-label form-nrequired">Description:</label>
					<div class="controls">
						<textarea id="organisation_desc" name="organisation_desc"><?php echo html_encode($PROCESSED["organisation_desc"]); ?></textarea>
					</div>
				</div>
				<div class="control-group">
					<label for="countries_id" class="control-label form-required">Country</label>
					<div class="controls">
						<?php

									$countries = fetch_countries();
									if ((is_array($countries)) && (count($countries))) {
										echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 256px\" onchange=\"provStateFunction(this.value); updateAptData();\">\n";
										foreach ($countries as $country) {
											echo "<option value=\"" . (int) $country["countries_id"] . "\"" . (($PROCESSED["countries_id"] == $country["countries_id"]) ? " selected=\"selected\"" : (!isset($PROCESSED["countries_id"]) && $country["countries_id"] == DEFAULT_COUNTRY_ID) ? " selected=\"selected\"" : "") . ">" . html_encode($country["country"]) . "</option>\n";
										}
										echo "</select>\n";
									} else {
										echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
										echo "Country information not currently available.\n";
									}
									?>
					</div>
				</div>
				<div class="control-group">
					<label for="province_id" class="control-label form-nrequired">Province / State:</label>
					<div class="controls">
						<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_city" class="control-label form-required">City:</label>
					<div class="controls">
						<input type="text" id="organisation_city" name="organisation_city" value="<?php echo html_encode($PROCESSED["organisation_city"]); ?>" />
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_postcode" class="control-label form-required">Postal Code:</label>
					<div class="controls">
						<input type="text" id="organisation_postcode" name="organisation_postcode" value="<?php echo html_encode($PROCESSED["organisation_postcode"]); ?>" maxlength="16"/>
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_address1" class="control-label form-required">Address 1:</label>
					<div class="controls">
						<input type="text" id="organisation_address1" name="organisation_address1" value="<?php echo html_encode($PROCESSED["organisation_address1"]); ?>"  />
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_address2" class="control-label">Address 2:</label>
					<div class="controls">
						<input type="text" id="organisation_address2" name="organisation_address2" value="<?php echo html_encode($PROCESSED["organisation_address2"]); ?>" />
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_telephone" class="form-required control-label">Telephone:</label>
					<div class="controls">
						<input type="text" id="organisation_telephone" name="organisation_telephone" value="<?php echo html_encode($PROCESSED["organisation_telephone"]); ?>" maxlength="32" />
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_fax" class="control-label">Fax:</label>
					<div class="controls">
						<input type="text" id="organisation_fax" name="organisation_fax" value="<?php echo html_encode($PROCESSED["organisation_fax"]); ?>" maxlength="32"/>
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_email" class="control-label">E-Mail Address:</label>
					<div class="controls">
						<input type="text" id="organisation_email" name="organisation_email" value="<?php echo html_encode($PROCESSED["organisation_email"]); ?>"/>
					</div>
				</div>
				<div class="control-group">
					<label for="organisation_url" class="control-label form-nrequired">Website:</label>
					<div class="controls">
						<input type="text" id="organisation_url" name="organisation_url" value="<?php echo html_encode($PROCESSED["organisation_url"]); ?>"/>
					</div>
				</div>
				<?php if ( Entrada_Twitter::widgetIsActive() ) { ?>
					<div class="control-group">
						<label for="organisation_twitter" class="control-label form-nrequired">Twitter Handle:</label>
						<div class="controls">
							<input type="text" id="organisation_twitter" name="organisation_twitter" value="<?php echo html_encode($PROCESSED["organisation_twittertwitter"]); ?>"/>
						</div>
					</div>
					<div class="control-group">
						<label for="organisation_hashtags" class="control-label form-nrequired">Twitter Hashtags:</label>
						<div class="controls">
							<select class="chosen-select" multiple id="twitter_hashtags" name="organisation_hashtags[]">
								<?php
								$select_options_array = explode(" ", $PROCESSED["organisation_hashtags"]);
								foreach ($select_options_array as $select_option) {
									echo "<option selected value=\"" . $select_option . "\">".$select_option."</option>";
								}
								?>
							</select>

						</div>
					</div>
				<?php } ?>
				<div class="control-group">
					<label for="template" class="form-required control-label">Interface Template:</label>
					<div class="controls">
						<?php
									$templates = fetch_templates();
									if ($templates && is_array($templates) && !empty($templates)) {
										?>
										<select id="template" name="template" style="width: 256px">
										<?php
										foreach ($templates as $template) {
											?>
											<option value="<?php echo $template; ?>"<?php echo (($PROCESSED["template"] == $template) ? " selected=\"selected\"" : ""); ?>><?php echo $template; ?></option>
											<?php
										}
										?>
										</select>
										<?php
									} else {
										echo  html_encode($PROCESSED["template"]) . " (not modifiable for this organisation)";
									}
									?>
					</div>
				</div>
				<div class="control-group">
					<label for="aamc_institution_id" class="control-label form-nrequired">AAMC Institution ID:</label>
					<div class="controls">
						<input type="text" id="aamc_institution_id" name="aamc_institution_id" value="<?php echo html_encode($PROCESSED["aamc_institution_id"]); ?>"/>
					</div>
				</div>
				<div class="control-group">
					<label for="aamc_institution_name" class="control-label form-nrequired">AAMC Institution Name:</label>
					<div class="controls">
						<input type="text" id="aamc_institution_name" name="aamc_institution_name" value="<?php echo html_encode($PROCESSED["aamc_institution_name"]); ?>"/>
					</div>
				</div>
				<div class="control-group">
					<label for="aamc_program_id" class="control-label form-nrequired">AAMC Program ID:</label>
					<div class="controls">
						<input type="text" id="aamc_program_id" name="aamc_program_id" value="<?php echo html_encode($PROCESSED["aamc_program_id"]); ?>" />
					</div>
				</div>
				<div class="control-group">
					<label for="aamc_program_name" class="control-label form-nrequired">AAMC Program Name:</label>
					<div class="controls">
						<input type="text" id="aamc_program_name" name="aamc_program_name" value="<?php echo html_encode($PROCESSED["aamc_program_name"]); ?>" />
					</div>
				</div>
				<div class="control-group">
					<div class="pull-right">
						<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage?org=<?php echo $ORGANISATION_ID; ?>'" />
						<input type="submit" class="btn btn-primary" value="Save" />
					</div>
				</div>

				</form>
				<?php
			} else {
				add_notice("You don't appear to have access to change any organisations. If you feel you are seeing this in error, please contact your system administrator.");
				echo display_notice();
			}
		break;
	}
}