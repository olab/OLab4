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
 * This file edits a department in the `departments` table.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */
if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: " . ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

	echo display_error();

	application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/settings/manage/departments?" . replace_query(array("section" => "add")) . "&amp;org=" . $ORGANISATION_ID, "title" => "Edit");

	if ((isset($_GET["department_id"])) && ($department_id = clean_input($_GET["department_id"], array("notags", "trim")))) {
		$PROCESSED["department_id"] = $department_id;
	}

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "department_title" / Department Name
			 */
			if (isset($_POST["department_title"]) && ($dept_title = clean_input($_POST["department_title"], array("notags", "trim")))) {
				$PROCESSED["department_title"] = $dept_title;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Department Name</strong> is a required field.";
			}

			if (isset($_POST["entity_id"]) && ($entity_id = clean_input($_POST["entity_id"], array("notags", "int", "trim")))) {
				$PROCESSED["entity_id"] = $entity_id;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Department Type</strong> is a required field.";
			}

			/**
			 * Non-required field "telephone" / Telephone Number.
			 */
			if ((isset($_POST["telephone"])) && ($telephone = clean_input($_POST["telephone"], "trim"))) {
				$PROCESSED["department_telephone"] = $telephone;
			} else {
				$PROCESSED["department_telephone"] = "";
			}

			/**
			 * Non-required field "fax" / Fax Number.
			 */
			if ((isset($_POST["fax"])) && ($fax = clean_input($_POST["fax"], "trim"))) {
				$PROCESSED["department_fax"] = $fax;
			} else {
				$PROCESSED["department_fax"] = "";
			}

			/**
			 * Non-required field "email" / email.
			 */
			if ((isset($_POST["email"])) && ($email = clean_input($_POST["email"], "trim"))) {
				$PROCESSED["department_email"] = $email;
			} else {
				$PROCESSED["department_email"] = "";
			}

			/**
			 * Non-required field "website" / Website (url).
			 */
			if ((isset($_POST["website"])) && ($website = clean_input($_POST["website"], "trim"))) {
				$PROCESSED["department_url"] = $website;
			} else {
				$PROCESSED["department_url"] = "";
			}

			/**
			 * Non-required field "department_address1" / Address Line 1.
			 */
			if ((isset($_POST["department_address1"])) && ($address = clean_input($_POST["department_address1"], array("trim", "ucwords"))) && (strlen($address) >= 6) && (strlen($address) <= 255)) {
				$PROCESSED["department_address1"] = $address;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Address Line 1</strong> is a required field.";
			}

			/**
			 * Non-required field "department_address2" / Address Line 2.
			 */
			if ((isset($_POST["department_address2"])) && ($address = clean_input($_POST["department_address2"], array("trim", "ucwords"))) && (strlen($address) >= 6) && (strlen($address) <= 255)) {
				$PROCESSED["department_address2"] = $address;
			} else {
				$PROCESSED["department_address2"] = "";
			}

			/**
			 * required field "city" / City.
			 */
			if ((isset($_POST["city"])) && ($city = clean_input($_POST["city"], array("trim", "ucwords"))) && (strlen($city) >= 3) && (strlen($city) <= 35)) {
				$PROCESSED["department_city"] = $city;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>City</strong> is a required field.";
			}



			/**
			 * required field "postcode" / Postal Code.
			 */
			if ((isset($_POST["postcode"])) && ($postcode = clean_input($_POST["postcode"], array("trim", "uppercase"))) && (strlen($postcode) >= 5) && (strlen($postcode) <= 12)) {
				$PROCESSED["department_postcode"] = $postcode;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Postal Code</strong> is a required field.";
			}

			/**
			 * Required filed "country_id" / Country
			 */
			if ((isset($_POST["country_id"])) && ($tmp_input = clean_input($_POST["country_id"], "int"))) {
				$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = " . $db->qstr($tmp_input);
				$result = $db->GetRow($query);
				if ($result) {
					$PROCESSED["country_id"] = $tmp_input;
				} else {
					$ERROR++;
					$ERRORSTR[] = "The selected country does not exist in our countries database. Please select a valid country.";

					application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a country.";
			}

			/**
			 * Required field "prov_state" / Province or State
			 */
			if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
				$PROCESSED["province_id"] = 0;

				if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
					if ($PROCESSED["country_id"]) {
						$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = " . $db->qstr($tmp_input) . " AND `country_id` = " . $db->qstr($PROCESSED["country_id"]);
						$result = $db->GetRow($query);
						if (!$result) {
							$ERROR++;
							$ERRORSTR[] = "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.";
						} else {
							$PROCESSED["province_id"] = $tmp_input;
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "Please select a country and then a province/state.";
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "Province or state format error.";
				}				
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a province or state.";
			}

			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute( "`" . AUTH_DATABASE . "`.`departments`", $PROCESSED, "UPDATE", "`department_id`=" . $db->qstr($PROCESSED["department_id"]))) {
					$url = ENTRADA_URL . "/admin/settings/manage/departments?org=" . $ORGANISATION_ID;
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully updated <strong>" . html_encode($PROCESSED["department_title"]) . "</strong> to the system.<br /><br />You will now be redirected to the Departments index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.";
					$ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
					application_log("success", "Department [" . $TOPIC_ID . "] was changed in the system.");
				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem updating this Department into the system. The system administrator was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a Department. Database said: " . $db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
			break;
		case 1 :
		default :

			$query = "SELECT * FROM `" . AUTH_DATABASE . "`.`departments` WHERE `department_id` = " . $db->qstr($PROCESSED["department_id"]);
			$result = $db->GetRow($query);
			if ($result) {
				$PROCESSED["department_title"] = $result["department_title"];
				$PROCESSED["entity_id"] = $result["entity_id"];
				$PROCESSED["department_address1"] = $result["department_address1"];
				$PROCESSED["department_address2"] = $result["department_address2"];
				$PROCESSED["department_city"] = $result["department_city"];
				$PROCESSED["country_id"] = $result["country_id"];
				$PROCESSED["province_id"] = $result["province_id"];				
				$PROCESSED["prov_state"] = $result["province_id"];
				$PROCESSED["department_postcode"] = $result["department_postcode"];
				$PROCESSED["department_telephone"] = $result["department_telephone"];
				$PROCESSED["department_fax"] = $result["department_fax"];
				$PROCESSED["department_email"] = $result["department_email"];
				$PROCESSED["department_url"] = $result["department_url"];
			}

			break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}

			if ($NOTICE) {
				echo display_notice();
			}

			if ($ERROR) {
				echo display_error();
			}
			break;
		case 1 :
		default:
			if ($ERROR) {
				echo display_error();
			}
			$ONLOAD[] = "provStateFunction('".$PROCESSED["country_id"]."', '".$PROCESSED["province_id"]."')";
?>
			<form action="<?php echo ENTRADA_URL . "/admin/settings/manage/departments" . "?" . replace_query(array("step" => 2)) . "&org=" . $ORGANISATION_ID; ?>" id ="department_edit_form" method="post">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Page">
					<colgroup>
						<col style="width: 30%" />
						<col style="width: 70%" />
					</colgroup>
					<thead>
						<tr>
							<td colspan="2">
								<h1>Edit Department</h1>
								<?php echo departments_nav($department_id, "edit"); ?>
							</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="2" style="padding-top: 15px; text-align: right">
								<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/departments?org=<?php echo $ORGANISATION_ID; ?>'" />
								<input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td><label for="department_title" class="form-required">Department Name:</label></td>
							<td><input type="text" id="department_title" name="department_title" value="<?php echo ((isset($PROCESSED["department_title"])) ? html_encode($PROCESSED["department_title"]) : ""); ?>" maxlength="60" style="width: 300px" /></td>
						</tr>
						<tr>
							<td><label for="entity_id" class="form-required">Department Type:</label></td>
							<td><select type="text" id="entity_id" name="entity_id" style="width: 250px">
									<option value="0">Choose a Deparmental Type</option>
						<?php
						$query = "	SELECT a.* FROM " . AUTH_DATABASE . ". `entity_type` AS a
												ORDER BY a.`entity_title` ASC";

						$results = $db->GetAll($query);

						if ($results) {
							foreach ($results as $result) {
								if ($result["entity_id"] == $PROCESSED["entity_id"]) {
									$selected = "selected=\"selected\"";
								} else {
									$selected = "";
								}
						?>
								<option value="<?php echo $result["entity_id"]; ?>" <?php echo $selected; ?> >
							<?php echo $result["entity_title"] ?>
							</option>
						<?php
							}
						}
						?>
					</select>
				</td>
			</tr>
				<tr>
					<td><label for="department_address1" class="form-required">Address Line 1</label></td>
					<td>
						<input type="text" id="department_address1" name="department_address1" value="<?php echo ((isset($PROCESSED["department_address1"])) ? html_encode($PROCESSED["department_address1"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="255" />
					</td>
				</tr>
				<tr>
					<td><label for="department_address2" class="form-nrequired">Address Line 2</label></td>
					<td>
						<input type="text" id="department_address2" name="department_address2" value="<?php echo ((isset($PROCESSED["department_address2"])) ? html_encode($PROCESSED["department_address2"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="255" />
					</td>
				</tr>
				<tr>
					<td><label for="city" class="form-required">City</label></td>
					<td>
						<input type="text" id="city" name="city" value="<?php echo ((isset($PROCESSED["department_city"])) ? html_encode($PROCESSED["department_city"]) : "Kingston"); ?>" style="width: 250px; vertical-align: middle" maxlength="35" />
					</td>
				</tr>
				<tr>
					<td><label for="country_id" class="form-required">Country</label></td>
					<td>
<?php
						$countries = fetch_countries();
						if ((is_array($countries)) && (count($countries))) {
							echo "<select id=\"country_id\" name=\"country_id\" style=\"width: 256px\" onchange=\"provStateFunction();\">\n";
							echo "<option value=\"0\">-- Select Country --</option>\n";
							foreach ($countries as $country) {
								echo "<option value=\"" . (int) $country["countries_id"] . "\"" . (((!isset($PROCESSED["country_id"]) && ($country["countries_id"] == DEFAULT_COUNTRY_ID)) || ($PROCESSED["country_id"] == $country["countries_id"])) ? " selected=\"selected\"" : "") . ">" . html_encode($country["country"]) . "</option>\n";
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
					<td><label id="prov_state_label" for="prov_state_div" class="form-required">Province / State</label></td>
					<td>
						<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
					</td>
				</tr>
				<tr>
					<td><label for="postcode" class="form-required">Postal Code</label></td>
					<td>
						<input type="text" id="postcode" name="postcode" value="<?php echo ((isset($PROCESSED["department_postcode"])) ? html_encode($PROCESSED["department_postcode"]) : "K7L 3N6"); ?>" style="width: 250px; vertical-align: middle" maxlength="7" />
						<span class="content-small">(<strong>Example:</strong> K7L 3N6)</span>
					</td>
				</tr>
				<tr>
					<td><label for="telephone" class="form-nrequired">Telephone Number</label></td>
					<td>
						<input type="text" id="telephone" name="telephone" value="<?php echo ((isset($PROCESSED["department_telephone"])) ? html_encode($PROCESSED["department_telephone"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="25" />
						<span class="content-small">(<strong>Example:</strong> 613-533-6000 x74918)</span>
					</td>
				</tr>
				<tr>
					<td><label for="fax" class="form-nrequired">Fax Number</label></td>
					<td>
						<input type="text" id="fax" name="fax" value="<?php echo ((isset($PROCESSED["department_fax"])) ? html_encode($PROCESSED["department_fax"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="25" />
						<span class="content-small">(<strong>Example:</strong> 613-533-3204)</span>
					</td>
				</tr>
				<tr>
					<td><label for="email" class="form-nrequired">Departmental Email Address</label></td>
					<td>
						<input type="text" id="email" name="email" value="<?php echo ((isset($PROCESSED["department_email"])) ? html_encode($PROCESSED["department_email"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="25" />
					</td>
				</tr>
				<tr>
					<td><label for="website" class="form-nrequired">Website</label></td>
					<td>
						<input type="text" id="website" name="website" value="<?php echo ((isset($PROCESSED["department_url"])) ? html_encode($PROCESSED["department_url"]) : ""); ?>" style="width: 250px; vertical-align: middle" maxlength="255" />
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	<script type="text/javascript">

		function provStateFunction(country_id, province_id) {
			var url_country_id = '<?php echo ((!isset($PROCESSED["country_id"]) && defined("DEFAULT_COUNTRY_ID") && DEFAULT_COUNTRY_ID) ? DEFAULT_COUNTRY_ID : 0); ?>';
			var url_province_id = '<?php echo ((!isset($PROCESSED["province_id"]) && defined("DEFAULT_PROVINCE_ID") && DEFAULT_PROVINCE_ID) ? DEFAULT_PROVINCE_ID : 0); ?>';

			if (country_id != undefined) {
				url_country_id = country_id;
			} else if ($('country_id')) {
				url_country_id = $('country_id').getValue();
			}

			if (province_id != undefined) {
				url_province_id = province_id;
			} else if ($('province_id')) {
				url_province_id = <?php echo $PROCESSED["province_id"]; ?>;
			}

			var url = '<?php echo webservice_url("province"); ?>?countries_id=' + url_country_id + '&prov_state=' + url_province_id;

			new Ajax.Updater($('prov_state_div'), url, {
				method:'get',
				onComplete: function (init_run) {

					if ($('prov_state').type == 'select-one') {
						$('prov_state_label').removeClassName('form-nrequired');
						$('prov_state_label').addClassName('form-required');
						if (!init_run) {
							$("prov_state").selectedIndex = 0;
						}
					} else {
						$('prov_state_label').removeClassName('form-required');
						$('prov_state_label').addClassName('form-nrequired');
						if (!init_run) {
							$("prov_state").clear();
						}
					}
				}
			});
		}
	</script>
<?php
						break;
				}
			}


