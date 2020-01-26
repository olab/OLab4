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
 * Allows administrators to edit apartment information in the entrada_clerkship.apartments table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_MANAGE")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: " . ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[] = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";

	echo display_error();

	application_log("error", "Group [" . $GROUP . "] and role [" . $ROLE . "] does not have access to this module [" . $MODULE . "]");
} else 
{
	$BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/regionaled/apartments/manage?id=" . $APARTMENT_ID, "title" => "Edit Apartment");
	$PROCESSED["associated_proxy_ids"] = array();

	switch ($STEP) {
		case 2 :
			if ((isset($_POST["countries_id"])) && ($tmp_input = clean_input($_POST["countries_id"], "int"))) {
				$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = " . $db->qstr($tmp_input);
				$result = $db->GetRow($query);
				if ($result) {
					$PROCESSED["countries_id"] = $tmp_input;

					if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
						$PROCESSED["province_id"] = 0;
						$PROCESSED["apartment_province"] = "";

						if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
							$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = " . $db->qstr($tmp_input) . " AND `country_id` = " . $db->qstr($PROCESSED["countries_id"]);
							$result = $db->GetRow($query);
							if ($result) {
								$PROCESSED["province_id"] = $tmp_input;
							} else {
								$ERROR++;
								$ERRORSTR[] = "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.";
							}
						} else {
							$PROCESSED["apartment_province"] = $tmp_input;
						}

						if ((isset($_POST["city"])) && ($tmp_input = clean_input($_POST["city"], array("trim", "notags")))) {
							$PROCESSED["city"] = $tmp_input;

							$query = "	SELECT *
										FROM `" . CLERKSHIP_DATABASE . "`.`regions`
										WHERE UPPER(`region_name`) = " . $db->qstr(strtoupper($PROCESSED["city"])) .
									((int) $PROCESSED["province_id"] ? " AND `province_id` = " . $db->qstr($PROCESSED["province_id"]) : ($PROCESSED["apartment_province"] ? " AND `prov_state` = " . $db->qstr($PROCESSED["apartment_province"]) : "")) . "
										AND `countries_id` = " . $db->qstr($PROCESSED["countries_id"]) . "
										AND `region_active` = '1'";
							$result = $db->GetRow($query);
							if ($result) {
								if ($result["manage_apartments"] != 1) {

									$region = array();
									$region["manage_apartments"] = 1;
									$region["updated_date"] = time();
									$region["updated_by"] = $PROXY_ID;

									if (!$db->AutoExecute(CLERKSHIP_DATABASE . ".regions", $region, "UPDATE", "region_id = " . $db->qstr($result["region_id"]))) {
										application_log("error", "Unable to update region_id [" . $result["region_id"] . "] and set manage_apartments to 1. Database said: " . $db->ErrorMsg());
									}
								}

								$PROCESSED["region_id"] = (int) $result["region_id"];
							} else {
								$region = array();
								$region["region_name"] = $PROCESSED["city"];
								$region["province_id"] = $PROCESSED["province_id"];
								$region["countries_id"] = $PROCESSED["countries_id"];
								$region["prov_state"] = $PROCESSED["apartment_province"];
								$region["manage_apartments"] = 1;
								$region["is_core"] = 0;
								$region["region_active"] = 1;
								$region["updated_date"] = time();
								$region["updated_by"] = $PROXY_ID;

								if (($db->AutoExecute(CLERKSHIP_DATABASE . ".regions", $region, "INSERT")) && ($region_id = (int) $db->Insert_Id())) {
									$PROCESSED["region_id"] = $region_id;
								} else {
									$ERROR++;
									$ERRORSTR[] = "Unable to create a new city / region at this time. Please try again later.";

									application_log("error", "Unable to create a new region when adding an apartment. Database said: " . $db->ErrorMsg());
								}
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must enter a city / region that this apartment resides in.";
						}
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "The selected country does not exist in our countries database. Please select a valid country.";

					application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select the country that this apartment is in.";
			}

			if (isset($_POST["apartment_title"]) && ($tmp_input = clean_input($_POST["apartment_title"], array("trim", "notags")))) {
				$PROCESSED["apartment_title"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a title of this apartment. It can simply be the address or a custom title.";
			}

			if (isset($_POST["apartment_number"]) && ($tmp_input = clean_input($_POST["apartment_number"], array("trim", "notags")))) {
				$PROCESSED["apartment_number"] = $tmp_input;
			} else {
				$PROCESSED["apartment_number"] = "";
			}

			if (isset($_POST["apartment_address"]) && ($tmp_input = clean_input($_POST["apartment_address"], array("trim", "notags")))) {
				$PROCESSED["apartment_address"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide the physical address of this apartment.";
			}

			if (isset($_POST["apartment_postcode"]) && ($tmp_input = clean_input($_POST["apartment_postcode"], array("trim", "notags")))) {
				$PROCESSED["apartment_postcode"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide the postal / zip code of this apartment.";
			}

			if (isset($_POST["apartment_phone"]) && ($tmp_input = clean_input($_POST["apartment_phone"], array("trim", "notags")))) {
				$PROCESSED["apartment_phone"] = $tmp_input;
			} else {
				$PROCESSED["apartment_phone"] = "";
			}

			if (isset($_POST["apartment_email"]) && ($tmp_input = clean_input($_POST["apartment_email"], array("trim", "notags")))) {
				if (valid_address($tmp_input)) {
					$PROCESSED["apartment_email"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "The e-mail address you've provided is not valid, please check the format of the e-mail address.";
				}
			} else {
				$PROCESSED["apartment_email"] = "";
			}

			if (isset($_POST["max_occupants"]) && ($tmp_input = clean_input($_POST["max_occupants"], array("trim", "int")))) {
				$PROCESSED["max_occupants"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "Please select the maximum number of occupants in your apartment.";
			}

			if (isset($_POST["apartment_latitude"]) && ($tmp_input = clean_input($_POST["apartment_latitude"], array("trim", "notags")))) {
				$PROCESSED["apartment_latitude"] = $tmp_input;
			} else {
				$PROCESSED["apartment_latitude"] = "";
			}

			if (isset($_POST["apartment_longitude"]) && ($tmp_input = clean_input($_POST["apartment_longitude"], array("trim", "notags")))) {
				$PROCESSED["apartment_longitude"] = $tmp_input;
			} else {
				$PROCESSED["apartment_longitude"] = "";
			}

			if (isset($_POST["apartment_information"]) && ($tmp_input = clean_input($_POST["apartment_information"], array("trim", "allowedtags")))) {
				$PROCESSED["apartment_information"] = $tmp_input;
			} else {
				$PROCESSED["apartment_information"] = "";
			}

			if (isset($_POST["super_firstname"]) && ($tmp_input = clean_input($_POST["super_firstname"], array("trim", "notags")))) {
				$PROCESSED["super_firstname"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide the firstname of the apartment's superintendent.";
			}

			if (isset($_POST["super_lastname"]) && ($tmp_input = clean_input($_POST["super_lastname"], array("trim", "notags")))) {
				$PROCESSED["super_lastname"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide the lastname of the apartment's superintendent.";
			}

			if (isset($_POST["super_phone"]) && ($tmp_input = clean_input($_POST["super_phone"], array("trim", "notags")))) {
				$PROCESSED["super_phone"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide the telephone number of the apartment's superintendent.";
			}

			if (isset($_POST["super_email"]) && ($tmp_input = clean_input($_POST["super_email"], array("trim", "notags"))) && valid_address($tmp_input)) {
				$PROCESSED["super_email"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a valid e-mail address for the apartment's superintendent.";
			}


			if (isset($_POST['keys_from_super'])) {
				$PROCESSED['keys_firstname'] = $PROCESSED['super_firstname'];
				$PROCESSED['keys_lastname'] = $PROCESSED['super_lastname'];
				$PROCESSED['keys_phone'] = $PROCESSED['super_phone'];
				$PROCESSED['keys_email'] = $PROCESSED['super_email'];
				$super_for_keys = true;
			} else {
				$super_for_keys = false;
				if (isset($_POST["keys_firstname"]) && ($tmp_input = clean_input($_POST["keys_firstname"], array("trim", "notags")))) {
					$PROCESSED["keys_firstname"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must provide the firstname of the key contact.";
				}

				if (isset($_POST["keys_lastname"]) && ($tmp_input = clean_input($_POST["keys_lastname"], array("trim", "notags")))) {
					$PROCESSED["keys_lastname"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must provide the lastname of the key contact.";
				}

				if (isset($_POST["keys_phone"]) && ($tmp_input = clean_input($_POST["keys_phone"], array("trim", "notags")))) {
					$PROCESSED["keys_phone"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must provide the telephone number of the key contact.";
				}

				if (isset($_POST["keys_email"]) && ($tmp_input = clean_input($_POST["keys_email"], array("trim", "notags"))) && valid_address($tmp_input)) {
					$PROCESSED["keys_email"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must provide a valid e-mail address for the key contact.";
				}
			}

			/**
			 * Required field "release_date" / Available Start (validated through validate_calendars function).
			 * Non-required field "release_until" / Available Finish (validated through validate_calendars function).
			 */
			$available_date = validate_calendars("available", true, false, false);
			if ((isset($available_date["start"])) && ((int) $available_date["start"])) {
				$PROCESSED["available_start"] = (int) $available_date["start"];
			} else {
				$PROCESSED["available_start"] = 0;
			}

			if ((isset($available_date["finish"])) && ((int) $available_date["finish"])) {
				$PROCESSED["available_finish"] = (int) $available_date["finish"];
			} else {
				$PROCESSED["available_finish"] = 0;
			}

			$query = "	SELECT `dep_id`
						FROM `" . AUTH_DATABASE . "`.`user_departments`
						WHERE `user_id` = " . $db->qstr($ENTRADA_USER->getId());
			$department_id = $db->getOne($query);
			if ($department_id) {
				$PROCESSED["department_id"] = $department_id;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You are not associated with a department in the " . APPLICATION_NAME . " System.  Please email the system administration to be added to a department: " . $AGENT_CONTACTS["administrator"]["email"];
				application_log("error", "Proxy id: " . $ENTRADA_USER->getId() . " is not associated with a department and therefore cannot use the Regional Education module.");
			}

			/**
			* Required field "associated_proxy_ids" / Apartment Contacts (array of proxy ids).
			* This is actually accomplished after the apartment is inserted below.
			*/
			if((isset($_POST["associated_proxy_ids"]))) {
				$associated_proxy_ids = explode(",", $_POST["associated_proxy_ids"]);
				foreach($associated_proxy_ids as $contact_order => $proxy_id) {
					if($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_proxy_ids"][(int) $contact_order] = $proxy_id;
					}
				}
			}

			/**
			* The current apartment must be in the apartmetn contact list.
			*/
			if (!in_array($ENTRADA_USER->getActiveId(), $PROCESSED["associated_proxy_ids"])) {
				array_unshift($PROCESSED["associated_proxy_ids"], $ENTRADA_USER->getActiveId());
				$ERROR++;
				$ERRORSTR[] = "You cannot remove yourself as an <strong>Apartment Contact</strong>.";
			}
			
			if (!$ERROR) {
				$PROCESSED["updated_last"] = time();
				$PROCESSED["updated_date"] = time();
				$PROCESSED["proxy_id"] = $ENTRADA_USER->getId();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute(CLERKSHIP_DATABASE . ".apartments", $PROCESSED, "UPDATE", "apartment_id = " . $db->qstr($APARTMENT_ID))) {
					$PROCESSED["apartment_id"] = $APARTMENT_ID;
					/**
					* Add the apartment contacts to the apartment_contacts table.
					*/
					if ((is_array($PROCESSED["associated_proxy_ids"])) && (count($PROCESSED["associated_proxy_ids"]))) {						
						/**
						* Delete existing apartment contacts, so we can re-add them.
						*/
						$query = "DELETE FROM `" . CLERKSHIP_DATABASE . "`.`apartment_contacts` WHERE `apartment_id` = ".$db->qstr($APARTMENT_ID);
						$db->Execute($query);
						foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
							$PROCESSED["proxy_id"] = $proxy_id;
							if (!$db->AutoExecute(CLERKSHIP_DATABASE.".apartment_contacts", $PROCESSED, "INSERT")) {
								$ERROR++;
								$ERRORSTR[] = "There was an error while trying to attach an <strong>Apartment Contact</strong> to this Apartment.<br /><br />The system administrator was informed of this error; please try again later.";

								application_log("error", "Unable to insert a new apartment_contact record while adding a new apartment. Database said: ".$db->ErrorMsg());
							}
						}
					}
					if (!$ERROR) {
						$SUCCESS++;
						$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["apartment_title"])."</strong> to the system.<br /><br />You will now be redirected to the apartment index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/regionaled/apartments\" style=\"font-weight: bold\">click here</a> to continue.";

						$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/regionaled/apartments\\'', 5000)";

						application_log("success", "New apartment [".$apartment_id."] added to the system.");						
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "We were unable to updated this apartment at this time.<br /><br />The system administrator has been notified of this issue, please try again later.";
					application_log("error", "Failed to update apartment_id [" . $APARTMENT_ID . "]. Database said: " . $db->ErrorMsg());
				}
			}
			if ($ERROR) {
				$STEP = 1;
			}
			break;
		case 1 :
		default :

			if (!isset($APARTMENT_INFO['keys_firstname']) || $APARTMENT_INFO['keys_firstname'] == '') {
				$APARTMENT_INFO['keys_firstname'] = $APARTMENT_INFO['super_firstname'];
				$APARTMENT_INFO['keys_lastname'] = $APARTMENT_INFO['super_lastname'];
				$APARTMENT_INFO['keys_phone'] = $APARTMENT_INFO['super_phone'];
				$APARTMENT_INFO['keys_email'] = $APARTMENT_INFO['super_email'];
			}
			$super_for_keys = ($APARTMENT_INFO['keys_firstname']==$APARTMENT_INFO['super_firstname'])&&
								($APARTMENT_INFO['keys_lastname']==$APARTMENT_INFO['super_lastname'])&&
									($APARTMENT_INFO['keys_phone']==$APARTMENT_INFO['super_phone']) &&
										($APARTMENT_INFO['keys_email']==$APARTMENT_INFO['super_email'])?'true':'false';
			$PROCESSED = $APARTMENT_INFO;
			if (isset($APARTMENT_INFO["region_id"])) {
				$PROCESSED["city"] = get_region_name($APARTMENT_INFO["region_id"]);
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
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$PROCESSED["prov_state"] = ((isset($PROCESSED["province_id"]) && $PROCESSED["province_id"]) ? (int) $PROCESSED["province_id"] : ((isset($PROCESSED["apartment_province"]) && $PROCESSED["apartment_province"]) ? $PROCESSED["apartment_province"] : ""));
			$ONLOAD[] = "provStateFunction(\$F($('editApartmentForm')['countries_id']))";
			$ONLOAD[] = "display_key_contact(".$super_for_keys.")";
			
			$query = "	SELECT `proxy_id` 
						FROM `" . CLERKSHIP_DATABASE . "`.`apartment_contacts` 
						WHERE `apartment_id` = ".$db->qstr($APARTMENT_ID);
			$results = $db->GetAll($query);			
			if ($results) {
				$PROCESSED["associated_proxy_ids"] = array();
				foreach ($results as $result) {
					if (!in_array($result["proxy_id"], $PROCESSED["associated_proxy_ids"])) {
						$PROCESSED["associated_proxy_ids"][] = $result["proxy_id"];
					}
				}
			}
			
			/**
			 * Determine whether the Google Maps can be shown.
			 */
			if ((defined("GOOGLE_MAPS_API")) && (GOOGLE_MAPS_API != "")) {
				$HEAD[] = "<script type=\"text/javascript\" src=\"" . GOOGLE_MAPS_API . "\"></script>";
				$ONLOAD[] = "initialize()";
				if (isset($PROCESSED["apartment_latitude"]) && $PROCESSED["apartment_latitude"] && isset($PROCESSED["apartment_longitude"]) && $PROCESSED["apartment_longitude"]) {
					$ONLOAD[] = "addPointToMap('" . $PROCESSED["apartment_latitude"] . "', '" . $PROCESSED["apartment_longitude"] . "')";
				}
			}

			load_rte("advanced");

			if ($ERROR) {
				echo display_error();
			}
			?>
			<script type="text/javascript">
				var googleMap = null;
				var updater = null;
				
				function display_key_contact(display) {
					if (display) {
						$('keys_from_super').checked = true;
						Effect.Fade($('keys_division'));
					} else {
						$('keys_from_super').checked = false;
						Effect.Appear($('keys_division'));
					}
				}
				
				
				function initialize() {
					googleMap = new GMap2($('mapData'));
				}
				function provStateFunction(countries_id) {
					var url='<?php echo webservice_url("province"); ?>';
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
						updater = new Ajax.Autocompleter('city', 'city_auto_complete',
						'<?php echo ENTRADA_URL; ?>/api/cities-by-country.api.php?countries_id=' + $F('countries_id'),
						{
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
			<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?id=<?php echo $APARTMENT_ID; ?>&section=edit" method="post" id="editApartmentForm">
				<input type="hidden" id="step" name="step" value="2" />
				<input type="hidden" id="region_id" name="region_id" value="<?php echo (isset($PROCESSED["region_id"]) ? (int) $PROCESSED["region_id"] : 0); ?>" />
				<input type="hidden" id="apartment_latitude" name="apartment_latitude" value="<?php echo (isset($PROCESSED["apartment_latitude"]) ? html_encode($PROCESSED["apartment_latitude"]) : 0); ?>" />
				<input type="hidden" id="apartment_longitude" name="apartment_longitude" value="<?php echo (isset($PROCESSED["apartment_longitude"]) ? html_encode($PROCESSED["apartment_longitude"]) : 0); ?>" />
				<table style="width: 100%" cellspacing="0" border="0" cellpadding="2" summary="Edit Apartment Form">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 24%" />
						<col style="width: 32%" />
						<col style="width: 41%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="4" style="padding-top: 25px; text-align: right">
								<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments'" />
								<input type="submit" class="btn btn-primary" value="Save" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td colspan="4"><h2>Apartment Information</h2></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="countries_id" class="form-required">Country</label></td>
							<td>
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
							</td>
							<td rowspan="10">
								<div id="mapContainer" style="display: none;">
									<div id="mapData" style="width: 275px; height: 237px; border: 1px #CCCCCC solid"></div>
								</div>
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
							<td><label for="city" class="form-required">City</label></td>
							<td>
								<input type="text" id="city" name="city" size="100" autocomplete="off" style="width: 250px; vertical-align: middle" value="<?php echo html_encode($PROCESSED["city"]); ?>" onblur="updateAptData()" />
								<script type="text/javascript">
									$('city').observe('keypress', function(event){
										if (event.keyCode == Event.KEY_RETURN) {
											Event.stop(event);
										}
									});
								</script>
								<div class="autocomplete" id="city_auto_complete"></div>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="apartment_number" class="form-nrequired">Apt Number</label> / <label for="apartment_address" class="form-required">Address</label></td>
							<td>
								<input type="text" id="apartment_number" name="apartment_number" value="<?php echo html_encode($PROCESSED["apartment_number"]); ?>" maxlength="4" style="width: 39px; margin-right: 5px" onblur="updateAptData()" /><input type="text" id="apartment_address" name="apartment_address" value="<?php echo html_encode($PROCESSED["apartment_address"]); ?>" maxlength="250" style="width: 200px" onblur="updateAptData()" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="apartment_postcode" class="form-required">Postal / Zip Code</label></td>
							<td>
								<input type="text" id="apartment_postcode" name="apartment_postcode" value="<?php echo html_encode($PROCESSED["apartment_postcode"]); ?>" maxlength="20" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="apartment_phone" class="form-nrequired">Telephone Number</label></td>
							<td>
								<input type="text" id="apartment_phone" name="apartment_phone" value="<?php echo html_encode($PROCESSED["apartment_phone"]); ?>" maxlength="25" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="apartment_email" class="form-required">E-Mail Address</label></td>
							<td>
								<input type="text" id="apartment_email" name="apartment_email" value="<?php echo html_encode($PROCESSED["apartment_email"]); ?>" maxlength="150" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="max_occupants" class="form-required">Maximum Occupants</label></td>
							<td>
								<select id="max_occupants" name="max_occupants" style="width: 257px">
									<option value="1"<?php echo (((int) $PROCESSED["max_occupants"] == 1) ? " selected=\"selected\"" : "") ?>>1 occupant</option>
									<option value="2"<?php echo (((int) $PROCESSED["max_occupants"] == 2) ? " selected=\"selected\"" : "") ?>>2 occupants</option>
									<option value="3"<?php echo (((int) $PROCESSED["max_occupants"] == 3) ? " selected=\"selected\"" : "") ?>>3 occupants</option>
									<option value="4"<?php echo (((int) $PROCESSED["max_occupants"] == 4) ? " selected=\"selected\"" : "") ?>>4 occupants</option>
									<option value="5"<?php echo (((int) $PROCESSED["max_occupants"] == 5) ? " selected=\"selected\"" : "") ?>>5 occupants</option>
									<option value="6"<?php echo (((int) $PROCESSED["max_occupants"] == 6) ? " selected=\"selected\"" : "") ?>>6 occupants</option>
									<option value="7"<?php echo (((int) $PROCESSED["max_occupants"] == 7) ? " selected=\"selected\"" : "") ?>>7 occupants</option>
									<option value="8"<?php echo (((int) $PROCESSED["max_occupants"] == 8) ? " selected=\"selected\"" : "") ?>>8 occupants</option>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="apartment_title" class="form-required">Apartment Title</label></td>
							<td colspan="2">
								<input type="text" id="apartment_title" name="apartment_title" value="<?php echo html_encode($PROCESSED["apartment_title"]); ?>" maxlength="86" style="width: 99%" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td style="vertical-align: top"><label for="apartment_information" class="form-nrequired">General Information</label></td>
							<td colspan="2">
								<textarea id="apartment_information" name="apartment_information" style="width: 100%; height: 200px" cols="70" rows="10"><?php echo html_encode($PROCESSED["apartment_information"]); ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="4"><h2>Superintendent Information</h2></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="super_firstname" class="form-required">Firstname</label></td>
							<td colspan="2">
								<input type="text" id="super_firstname" name="super_firstname" value="<?php echo html_encode($PROCESSED["super_firstname"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="super_lastname" class="form-required">Lastname</label></td>
							<td colspan="2">
								<input type="text" id="super_lastname" name="super_lastname" value="<?php echo html_encode($PROCESSED["super_lastname"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="super_phone" class="form-required">Telephone Number</label></td>
							<td colspan="2">
								<input type="text" id="super_phone" name="super_phone" value="<?php echo html_encode($PROCESSED["super_phone"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label for="super_email" class="form-required">E-Mail Address</label></td>
							<td colspan="2">
								<input type="text" id="super_email" name="super_email" value="<?php echo html_encode($PROCESSED["super_email"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
						<tr>
							<td><input type="checkbox"name ="keys_from_super" id="keys_from_super" value="true" onclick="display_key_contact(this.checked)"/></td>
							<td colspan="2"><label for="keys_from_super" class="form-nrequired">The superintendent is also the <strong>key contact</strong> for this apartment.</label></td>
						</tr>
						</tbody>
						<tbody id="keys_division" style="display: none">
							<tr>
								<td colspan="4"><h2>Contact for Keys</h2></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td><label for="keys_firstname" class="form-required">Firstname</label></td>
								<td colspan="2">
									<input type="text" id="keys_firstname" name="keys_firstname" value="<?php echo html_encode($PROCESSED["keys_firstname"]); ?>" maxlength="32" style="width: 250px" />
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td><label for="keys_lastname" class="form-required">Lastname</label></td>
								<td colspan="2">
									<input type="text" id="keys_lastname" name="keys_lastname" value="<?php echo html_encode($PROCESSED["keys_lastname"]); ?>" maxlength="32" style="width: 250px" />
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td><label for="keys_phone" class="form-required">Telephone Number</label></td>
								<td colspan="2">
									<input type="text" id="keys_phone" name="keys_phone" value="<?php echo html_encode($PROCESSED["keys_phone"]); ?>" maxlength="32" style="width: 250px" />
								</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td><label for="keys_email" class="form-required">E-Mail Address</label></td>
								<td colspan="2">
									<input type="text" id="keys_email" name="keys_email" value="<?php echo html_encode($PROCESSED["keys_email"]); ?>" maxlength="32" style="width: 250px" />
								</td>
							</tr>
						</tbody>
						<tbody>
							<tr>
								<td colspan="4"><h2>Apartment Availability</h2></td>
							</tr>
							<tr>
								<td colspan="3"></td>
								<td rowspan="3"></td>
							</tr>
							<?php
								$available_start = ((isset($PROCESSED["available_start"])) ? (int) $PROCESSED["available_start"] : time());
								$available_finish = ((isset($PROCESSED["available_finish"])) ? (int) $PROCESSED["available_finish"] : 0);
								echo generate_calendars("available", "", true, true, $available_start, true, false, $available_finish, false);
							?>
						</tbody>
						<tbody>
						<tr>
							<td colspan="4">
								<label for="contact_name" class="control-label form-nrequired"><h2>Apartment Contacts:</h2>
								<div class="content-small" style="margin-top: 15px">
									<strong>Tip:</strong> Select any other individuals you would like to give access to this apartment.
								</div>
								</label>
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<input type="text" id="contact_name" name="fullname" size="30" autocomplete="off" style="width: 203px" />
								<?php
								$ONLOAD[] = "contact_list = new AutoCompleteList({ type: 'contact', url: '" . ENTRADA_RELATIVE . "/api/personnel.api.php?type=facultyorstaff', remove_image: '" . ENTRADA_RELATIVE . "/images/action-delete.gif'})";
								?>
								<div class="autocomplete" id="contact_name_auto_complete"></div>
								<input type="hidden" id="associated_contact" name="associated_proxy_ids" value="" />
								<input type="button" class="btn" id="add_associated_contact" value="Add" />
								<span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"] . ", " . $_SESSION["details"]["firstname"]); ?>)</span>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<ul id="contact_list" class="menu" style="margin-top: 15px">
									<?php
									if (is_array($PROCESSED["associated_proxy_ids"]) && !empty($PROCESSED["associated_proxy_ids"])) {
										$selected_contacts = array();

										$query = "	SELECT `id` AS `proxy_id`, CONCAT_WS(', ', `lastname`, `firstname`) AS `fullname`, `organisation_id`
													FROM `" . AUTH_DATABASE . "`.`user_data`
													WHERE `id` IN (" . implode(", ", $PROCESSED["associated_proxy_ids"]) . ")
													ORDER BY `lastname` ASC, `firstname` ASC";
										$results = $db->GetAll($query);
										if ($results) {
											foreach ($results as $result) {
												$selected_contacts[$result["proxy_id"]] = $result;
											}
											unset($results);
										}
										foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
											if ($proxy_id = (int) $proxy_id) {
												if (array_key_exists($proxy_id, $selected_contacts)) {
													?>
													<li class="community" id="contact_<?php echo $proxy_id; ?>" style="cursor: move;">
														<?php echo $selected_contacts[$proxy_id]["fullname"]; ?>
														<img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="contact_list.removeItem('<?php echo $proxy_id; ?>');" class="list-cancel-image" />
													</li>
													<?php
												}
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="contact_ref" name="contact_ref" value="" />
								<input type="hidden" id="contact_id" name="contact_id" value="" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		<?php	
		break;
	}
}