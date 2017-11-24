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
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	define("IN_PROFILE", true);
	
	$VALID_MIME_TYPES			= array("image/pjpeg" => "jpg", "image/jpeg" => "jpg", "image/jpg" => "jpg", "image/gif" => "gif", "image/png" => "png");
	$VALID_MAX_FILESIZE			= MAX_UPLOAD_FILESIZE;
	$VALID_MAX_DIMENSIONS		= array("photo-width" => 216, "photo-height" => 300, "thumb-width" => 75, "thumb-height" => 104);
	$RENDER						= false;

	$MODULE_TEXT = $translate->_($MODULE);
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile", "title" => $MODULE_TEXT["title"]);

	if (($router) && ($router->initRoute())) {
		$module_file = $router->getRoute();
		if ($module_file) {
		
			if (isset($ACTION)) {
				switch(trim(strtolower($ACTION))) {
					case "privacy-update" :
						profile_update_privacy();
					break;
					case "notifications-update" :
						profile_update_notifications();
					break;
					case "google-update" :
						profile_update_google();
					break;
					case "google-password-reset" :
						profile_update_google_password();
					break;
					case "privacy-google-update" :
						profile_update_google_privacy();
					break;
					case "profile-update" :
						profile_update_personal_info();
					break;
					case "assistant-add" :
						profile_add_assistant();
					break;
					case "assistant-remove" :
						profile_remove_assistant();
						break;
					case "privacy-copyright-google-update" :
						profile_copyright_update_google_privacy();
						break;
					case "privacy-copyright-update" :
						profile_copyright_update();
						break;
					case "copyright-google-update" :
						copyright_update_google_privacy();
						break;
					case "copyright-update" :
						copyright_update();
						break;
				}
			}
			add_profile_sidebar();

			require_once($module_file);
			
		}
	} else {
		$url = ENTRADA_URL."/public/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

		header("Location: ".$url);
		exit;
	}

}

/**
 * Creates the profile sidebar to appear on all profile pages. The sidebar content will vary depending on the permissions of the user.
 * 
 */
function add_profile_sidebar () {
	global $ENTRADA_ACL, $ENTRADA_USER;

	$sidebar_html  = "<ul class=\"menu\">";
	$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile\">Personal Information</a></li>\n";
	$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile?section=privacy\">Privacy Preferences</a></li>\n";
	if (((defined("COMMUNITY_NOTIFICATIONS_ACTIVE")) && ((bool) COMMUNITY_NOTIFICATIONS_ACTIVE)) || ((defined("NOTIFICATIONS_ACTIVE")) && ((bool) NOTIFICATIONS_ACTIVE))) {
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile?section=activenotifications\">Notification Preferences</a></li>\n";
	}
	if ($ENTRADA_ACL->isLoggedInAllowed('assistant_support', 'create')) {
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile?section=assistants\">My Admin Assistants</a></li>\n";
	}

	if ($ENTRADA_USER->getActiveGroup() == "student") {
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile?section=mspr\">My MSPR</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile/observerships\">My Observerships</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile/gradebook\">My Gradebooks</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile/gradebook/assignments\">My Assignments</a></li>\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/profile/eportfolio\">My ePortfolio</a></li>\n";
	}
	
	$sidebar_html .= "</ul>";

	new_sidebar_item("Profile", $sidebar_html, "profile-nav", "open");
}

/**
 * Processes the personal info update. source data retrieved from POST. modifies the $PROCESSED variable 
 */
function profile_update_personal_info() {
	global $PROCESSED, $PROFILE_NAME_PREFIX, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR, $NOTICE, $NOTICESTR, $PROCESSED_PHOTO, $PROCESSED_PHOTO_STATUS, $PROCESSED_NOTIFICATIONS, $VALID_MIME_TYPES, $ENTRADA_USER;
	
	if (isset($_POST["custom"]) && $_POST["custom"]) {
		/*
		* Fetch the custom fields
		*/
		$dep_fields = Models_Profile_Custom_Fields::getAllByByOrganisationIDOrdered($ENTRADA_USER->getActiveOrganisation());
		if ($dep_fields) {
			foreach ($dep_fields as $field_id => $field) {
				switch (strtolower($field["type"])) {
					case "checkbox" :
						if (isset($_POST["custom"][$field["department_id"]][$field_id])) {
							$PROCESSED["custom"][$field_id] = "1";
						} else {
							$PROCESSED["custom"][$field_id] = "0";
						}
					break;
					default :
						if (!empty($_POST["custom"][$field["department_id"]][$field_id])) {
							if ($field["length"] != NULL && strlen($_POST["custom"][$field["department_id"]][$field_id]) > $field["length"]) {
								add_error("<strong>".$field["title"]."</strong> has a character limit of <strong>".$field["length"]."</strong> and you have entered <strong>".strlen($_POST["custom"][$field["department_id"]][$field_id])."</strong> characters. Please edit your response and re-save your profile.");
							} else {
								$PROCESSED["custom"][$field_id] = clean_input($_POST["custom"][$field["department_id"]][$field_id], array("trim", strtolower($field["type"]) == "richtext" ? "html" : (strtolower($field["type"]) == "twitter" ? "alphanumeric" : "striptags")));
							}
						} else {
							if ($field["required"] == "1") {
								add_error("<strong>".$field["title"]."</strong> is a required field, please enter a response and re-save your profile.");
							}
						}
					break;
				}
			}
		}
	}
	
	if (isset($_POST["publications"]) && $_POST["publications"]) {
		foreach ($_POST["publications"] as $pub_type => $ppublications) {
			foreach ($ppublications as $department_id => $publications) {
				foreach ($publications as $publication_id => $status) {
					$PROCESSED["publications"][$pub_type][$department_id][] = clean_input($publication_id, "numeric");
				}
			}
		}
	}

    if (isset($PROFILE_NAME_PREFIX) && is_array($PROFILE_NAME_PREFIX) && isset($_POST["prefix"]) && in_array($_POST["prefix"], $PROFILE_NAME_PREFIX)) {
        /*
         * To prevent students from providing a prefix when they shouldn't be setting
         * one I need to know if they already have one or not.
         */
        if ($ENTRADA_USER->getGroup() == "student") {
			$user = Models_User::fetchRowByID($ENTRADA_USER->GetProxyId());
            $prefix = $user->getPrefix();
        } else {
            $prefix = false;
        }

        if (($ENTRADA_USER->getGroup() != "student") || $prefix) {
            /*
             * Doing this safe because we are checking that the value of $_POST["prefix"] is set in the $PROFILE_NAME_PREFIX array above.
             */
            $PROCESSED["prefix"] = $_POST["prefix"];
        }
	} else {
		$PROCESSED["prefix"] = "";
	}

	if ((isset($_POST["office_hours"])) && ($office_hours = clean_input($_POST["office_hours"], array("notags","encode", "trim"))) && ($_SESSION["details"]["group"] != "student")) {
		$PROCESSED["office_hours"] = ((strlen($office_hours) > 100) ? substr($office_hours, 0, 97)."..." : $office_hours);
	} else {
		$PROCESSED["office_hours"] = "";
	}
		
	if($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] == "faculty") {
		if ((isset($_POST["email"])) && ($email = clean_input($_POST["email"], "trim", "lower"))) {
			if (valid_address($email)) {
				$PROCESSED["email"] = $email;
			} else {
				add_error("The primary e-mail address you have provided is invalid. Please make sure that you provide a properly formatted e-mail address.");
			}
		} else { 
			add_error("The primary e-mail address is a required field.");
		}
	}
	
	if ((isset($_POST["email_alt"])) && ($_POST["email_alt"] != "")) {
		if (valid_address(trim($_POST["email_alt"]))) {
			$PROCESSED["email_alt"] = strtolower(trim($_POST["email_alt"]));
		} else {
			add_error("The secondary e-mail address you have provided is invalid. Please make sure that you provide a properly formatted e-mail address or leave this field empty if you do not wish to display one.");
		}
	} else {
		$PROCESSED["email_alt"] = "";
	}

	if ((isset($_POST["telephone"])) && (strlen(trim($_POST["telephone"])) >= 10) && (strlen(trim($_POST["telephone"])) <= 25)) {
		$PROCESSED["telephone"] = strtolower(trim($_POST["telephone"]));
	} else {
		$PROCESSED["telephone"] = "";
	}

	if ((isset($_POST["fax"])) && (strlen(trim($_POST["fax"])) >= 10) && (strlen(trim($_POST["fax"])) <= 25)) {
		$PROCESSED["fax"] = strtolower(trim($_POST["fax"]));
	} else {
		$PROCESSED["fax"] = "";
	}

	if ((isset($_POST["address"])) && (strlen(trim($_POST["address"])) >= 6) && (strlen(trim($_POST["address"])) <= 255)) {
		$PROCESSED["address"] = ucwords(strtolower(trim($_POST["address"])));
	} else {
		$PROCESSED["address"] = "";
	}

	if ((isset($_POST["city"])) && (strlen(trim($_POST["city"])) >= 3) && (strlen(trim($_POST["city"])) <= 35)) {
		$PROCESSED["city"] = ucwords(strtolower(trim($_POST["city"])));
	} else {
		$PROCESSED["city"] = "";
	}

	if ((isset($_POST["postcode"])) && (strlen(trim($_POST["postcode"])) >= 5) && (strlen(trim($_POST["postcode"])) <= 12)) {
		$PROCESSED["postcode"] = strtoupper(trim($_POST["postcode"]));
	} else {
		$PROCESSED["postcode"] = "";
	}
	
	if ((isset($_POST["country_id"])) && ($tmp_input = clean_input($_POST["country_id"], "int"))) {
		$result = Models_Country::fetchRowByID($tmp_input);
		if ($result) {
			$PROCESSED["country_id"] = $tmp_input;
		} else {
			add_error("The selected country does not exist in our countries database. Please select a valid country.");

			application_log("error", "Unknown countries_id [".$tmp_input."] was selected.");
		}
	} else {
		add_error("You must select a country.");
	}

	if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
		$PROCESSED["province_id"] = 0;
		$PROCESSED["province"] = "";

		if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
			if ($PROCESSED["country_id"]) {
				$result = Models_Province::fetchRowByIDCountryID($tmp_input, $PROCESSED["country_id"]);
				if (!$result) {
					add_error("The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.");
				}
			}

			$PROCESSED["province_id"] = $tmp_input;
		} else {
			$PROCESSED["province"] = $tmp_input;
		}

		$PROCESSED["prov_state"] = ($PROCESSED["province_id"] ? $PROCESSED["province_id"] : ($PROCESSED["province"] ? $PROCESSED["province"] : ""));
	}
	if (!$ERROR) {
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && $user->fromArray($PROCESSED)->update()) {
			add_success("Your account profile has been successfully updated.");

			application_log("success", "User successfully updated their profile.");

			if (isset($PROCESSED["custom"])) {
				foreach ($PROCESSED["custom"] as $field_id => $value) {
					Models_Profile_Custom_Responses::deleteByFieldIDProxyID($field_id, $ENTRADA_USER->getID());
					$custom_response = new Models_Profile_Custom_Responses();
					$custom_response->fromArray(array("field_id" => $field_id, "proxy_id" => $ENTRADA_USER->getID(), "value" => $value))->insert();
				}
			}
			
		} else {
			add_error("We were unfortunately unable to update your profile at this time. The system administrator has been informed of the problem, please try again later.");

			application_log("error", "Unable to update user profile. ");
		}
	}
}

function profile_update_privacy() {
	/**
	 * This actually changes the privacy settings in their profile.
	 * Note: The sessions variable ($_SESSION["details"]["privacy_level"]) is actually being
	 * changed in index.php on line 268, so that the proper tabs are displayed.
	 */
	global $ENTRADA_USER;
	
	if ((isset($_POST["privacy_level"])) && ($privacy_level = (int) trim($_POST["privacy_level"]))) {
		if ($privacy_level > MAX_PRIVACY_LEVEL) {
			$privacy_level = MAX_PRIVACY_LEVEL;
		}
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && $user->fromArray(array("privacy_level" => $privacy_level))->update()) {
			if ((isset($_POST["redirect"])) && (trim($_POST["redirect"]) != "")) {
				header("Location: ".((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input(rawurldecode($_POST["redirect"]), array("nows", "url")));
				exit;
			} else {
				header("Location: ".ENTRADA_URL);
				exit;
			}
		} else {
			add_error("We were unfortunately unable to update your privacy settings at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update privacy setting. ");
		}

	}
}

function profile_update_google_privacy() {
	global $GOOGLE_APPS, $ENTRADA_USER;
	
	if ((bool) $GOOGLE_APPS["active"]) {
		/**
		 * This actually creates a Google Hosted Apps account associated with their profile.
		 * Note: The sessions variable ($_SESSION["details"]["google_id"]) is being
		 * changed in index.php on line 242 to opt-in, which is merely used in the logic
		 * of the first-login page, but only if the user has no google id and hasn't opted out.
		 */
		if (isset($_POST["google_account"])) {
			if ((int) trim($_POST["google_account"])) {
				if (google_create_id()) {
					add_success("<strong>Your new ".$GOOGLE_APPS["domain"]."</strong> account has been created!</strong><br /><br />An e-mail will be sent to ".$_SESSION["details"]["email"]." shortly, containing further instructions regarding account activation.");
				}
			} else {
				$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
				if ($user) {
					$user->fromArray(array("google_id" => "opt-out"))->update();
				}
			}
		}
	}

	/**
	 * This actually changes the privacy settings in their profile.
	 * Note: The sessions variable ($_SESSION["details"]["privacy_level"]) is actually being
	 * changed in index.php on line 268, so that the proper tabs are displayed.
	 */
	if ((isset($_POST["privacy_level"])) && ($privacy_level = (int) trim($_POST["privacy_level"]))) {
		if ($privacy_level > MAX_PRIVACY_LEVEL) {
			$privacy_level = MAX_PRIVACY_LEVEL;
		}

		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && !$user->fromArray(array("privacy_level" => $privacy_level))->update()){
			add_error("We were unfortunately unable to update your privacy settings at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update privacy setting. ");
		}
	}
}

function profile_update_google() {
	global $GOOGLE_APPS, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR, $ENTRADA_USER;
		
	if ((bool) $GOOGLE_APPS["active"]) {
		/**
		 * This actually creates a Google Hosted Apps account associated with their profile.
		 * Note: The sessions variable ($_SESSION["details"]["google_id"]) is being
		 * changed in index.php on line 242 to opt-in, which is merely used in the logic
		 * of the first-login page, but only if the user has no google id and hasn't opted out.
		 */
		if (isset($_POST["google_account"])) {
			if ((int) trim($_POST["google_account"])) {
				if (google_create_id()) {
					add_success("<strong>Your new ".$GOOGLE_APPS["domain"]."</strong> account has been created!</strong><br /><br />An e-mail will be sent to ".$_SESSION["details"]["email"]." shortly, containing further instructions regarding account activation.");

					if ((isset($_POST["ajax"])) && ($_POST["ajax"] == "1")) {
						// Clear any open buffers and push through only the success message.
						ob_clear_open_buffers();
						echo display_success($SUCCESSSTR);
                        $google_address = html_encode($_SESSION["details"]["google_id"]."@".$GOOGLE_APPS["domain"]);
                        ?>
                        <span class="input-large uneditable-input"><?php echo $google_address; ?></span>
                        <?php
                        if ($google_address) {
                            ?>
                            <div style="margin-top: 10px">
                                <a href="#reset-google-password-box" id="reset-google-password" class="btn" data-toggle="modal">Change My <strong><?php echo ucwords($GOOGLE_APPS["domain"]); ?></strong> Password</a>
                            </div>
                        <?php
                        }
						exit;
					}
				} else {
					if ((isset($_POST["ajax"])) && ($_POST["ajax"] == "1")) {
						// $ERRORSTR is set by the google_create_id() function.
						// Clear any open buffers and push through only the error message.
						ob_clear_open_buffers();
                        echo "Your ".$GOOGLE_APPS["domain"]." account is <strong>not active</strong>. <br /> ( <a href=\"javascript: create_google_account()\" class=\"action\">create my account</a>)";
						echo display_error($ERRORSTR);
						exit;
					}
				}
			} else {
				$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
				if ($user) {
					$user->fromArray(array("google_id" => "opt-out"))->update();
				}
			}
		}
	}
}

function profile_update_google_password() {
	global $GOOGLE_APPS;

	ob_clear_open_buffers();

	if ((bool) $GOOGLE_APPS["active"]) {
		if (isset($_POST["password"]) && ($tmp_input = clean_input($_POST["password"], "trim"))) {
			if (google_reset_password($tmp_input)) {
				echo 1;
				exit;
			}
		}
	}

	echo 0;
	exit;
}

function profile_add_assistant() {
	global $PROCESSED, $ERROR, $ENTRADA_ACL, $ENTRADA_USER;
	
	if ($ENTRADA_ACL->isLoggedInAllowed('assistant_support', 'create')) {
		$access_timeframe = Entrada_Utilities::validate_calendars("valid", true, true);

		if (!$ERROR) {
			if ((isset($access_timeframe["start"])) && ((int) $access_timeframe["start"])) {
				$PROCESSED["valid_from"] = (int) $access_timeframe["start"];
			}

			if ((isset($access_timeframe["finish"])) && ((int) $access_timeframe["finish"])) {
				$PROCESSED["valid_until"] = (int) $access_timeframe["finish"];
			}

			if ((isset($_POST["assistant_id"])) && ($proxy_id = (int) trim($_POST["assistant_id"]))) {
				if ($proxy_id != $ENTRADA_USER->getID()) {

					$result = Models_User::getUserByIDAndGroup($proxy_id, "student");
					if ($result) {
						$PROCESSED["assigned_by"]	= $ENTRADA_USER->getID();
						$PROCESSED["assigned_to"]	= $result["proxy_id"];
						$fullname					= $result["fullname"];

						$permission_object = Models_Permissions::fetchOneByAssignedByAssignedTo($PROCESSED["assigned_by"],$PROCESSED["assigned_to"]);
						if ($permission_object) {
							if ($permission_object->fromArray($PROCESSED)->update()) {
								add_success("You have successfully updated <strong>".html_encode($fullname)."'s</strong> access permissions to your account.");

								application_log("success", "Updated permissions for proxy_id [".$PROCESSED["assigned_by"]."] who is allowing [".$PROCESSED["assigned_by"]."] accecss to their account from ".date(DEFAULT_DATE_FORMAT, $PROCESSED["valid_from"])." until ".date(DEFAULT_DATE_FORMAT, $PROCESSED["valid_until"]));
							} else {
								add_error("We were unable to update <strong>".html_encode($fullname)."'s</strong> access permissions to your account at this time. The system administrator has been informed of this, please try again later.");

								application_log("error", "Unable to update permissions for proxy_id [".$PROCESSED["assigned_by"]."] who is allowing [".$PROCESSED["assigned_by"]."] accecss to their account.");
							}
						} else {
							$permission_object = new Models_Permissions();
							if ($permission_object->fromArray($PROCESSED)->insert()) {
								add_success("You successfully gave <strong>".html_encode($fullname)."</strong> access permissions to your account.");

								application_log("success", "Added permissions for proxy_id [".$PROCESSED["assigned_by"]."] who is allowing [".$PROCESSED["assigned_by"]."] accecss to their account from ".date(DEFAULT_DATE_FORMAT, $PROCESSED["valid_from"])." until ".date(DEFAULT_DATE_FORMAT, $PROCESSED["valid_until"]));
							} else {
								add_error("We were unable to give <strong>".html_encode($fullname)."</strong> access permissions to your account at this time. The system administrator has been informed of this, please try again later.");

								application_log("error", "Unable to insert permissions for proxy_id [".$PROCESSED["assigned_by"]."] who is allowing [".$PROCESSED["assigned_by"]."] accecss to their account. ");
							}
						}
					} else {
						add_error("The person that have selected to add as an assistant either does not exist in this system, or their account is not currently active.<br /><br />Please contact Denise Jones in the Undergrad office (613-533-6000 x77804) to get an account for the requested individual.");
					}
				} else {
					add_error("You cannot add yourself as your own assistant, there is no need to do so.");
				}
			} else {
				add_error("You must enter, then select the name of the person you wish to give access to your account permissions.");
			}
		} else {
            add_error("Please enter valid start and end dates for the period when the assistant will have access to your account.");
        }
	} else {
		add_error("Your account does not have the required access levels to add assistants to your profile.");

		application_log("error", "User tried to add assistants to profile without an acceptable group & role.");
	}
}

function profile_remove_assistant () {
	global $PROCESSED, $ENTRADA_ACL, $ENTRADA_USER;
	
	if ($ENTRADA_ACL->isLoggedInAllowed('assistant_support', 'delete')) {
		if ((isset($_POST["remove"])) && (@is_array($_POST["remove"])) && (@count($_POST["remove"]))) {
			foreach ($_POST["remove"] as $assigned_to => $permission_id) {
				$permission_id = (int) trim($permission_id);
				if ($permission_id) {
					if (Models_User::deleteAsistants($permission_id, $ENTRADA_USER->getID())) {

						add_success("You have successfully removed ".get_account_data("fullname", (int) $assigned_to)." from to accessing your permission levels.");

						application_log("success", "Removed assigned_to [".$assigned_to."] permissions from proxy_id [".$ENTRADA_USER->getID()."] account.");
					} else {
						add_error("Unable to remove ".get_account_data("fullname", (int) $assigned_to)." from to accessing your permission levels. The system administrator has been informed of this error; however, if this is urgent, please contact us be telephone at: 613-533-6000 x74918.");

						application_log("error", "Failed to remove assigned_to [".$assigned_to."] permissions from proxy_id [".$ENTRADA_USER->getID()."] account.");
					}
				}
			}
		} else {

		}
	} else {
		add_error("Your account does not have the required access levels to remove assistants from your profile.");

		application_log("error", "User tried to remove assistants from profile without an acceptable group & role.");
	}
}

function profile_update_notifications() {
	global $PROCESSED, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR, $ENTRADA_ACL, $ENTRADA_USER;

	$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
	if ($_POST["enable-notifications"] == 1) {
		if ($user && ((int)$user->getNotifications()) != 1) {
			if (!$user->fromArray(array("notifications" => 1))->update()) {
				$ERROR++;
				application_log("error", "Notification settings for the Proxy ID [".$ENTRADA_USER->getID()."] could not be activated");
			}
		}
	} else {
		if ($user && ((int)$user->getNotifications()) != 0) {
			if (!$user->fromArray(array("notifications" => 0))->update()) {
				$ERROR++;
				application_log("error", "Notification settings for the Proxy ID [".$ENTRADA_USER->getID()."] could not be deactivated");
			}
		}
	}
}

function profile_copyright_update_google_privacy() {
	global $GOOGLE_APPS, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR, $ENTRADA_USER;

	if ((bool) $GOOGLE_APPS["active"]) {
		/**
		 * This actually creates a Google Hosted Apps account associated with their profile.
		 * Note: The sessions variable ($_SESSION["details"]["google_id"]) is being
		 * changed in index.php on line 242 to opt-in, which is merely used in the logic
		 * of the first-login page, but only if the user has no google id and hasn't opted out.
		 */
		if (isset($_POST["google_account"])) {
			if ((int) trim($_POST["google_account"])) {
				if (google_create_id()) {
					add_success("<strong>Your new ".$GOOGLE_APPS["domain"]."</strong> account has been created!</strong><br /><br />An e-mail will be sent to ".$_SESSION["details"]["email"]." shortly, containing further instructions regarding account activation.");
				}
			} else {
				$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
				if ($user) {
					$user->fromArray(array("google_id" => "opt-out"))->update();
				}
			}
		}
	}

	if (isset($_POST["copyright"])) {
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());

		if ($user && !$user->fromArray(array("copyright" => time()))->update()) {
			add_error("We were unfortunately unable to update your copyright setting at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update copyright setting. ");
		}
	}

	/**
	 * This actually changes the privacy settings in their profile.
	 * Note: The sessions variable ($_SESSION["details"]["privacy_level"]) is actually being
	 * changed in index.php on line 268, so that the proper tabs are displayed.
	 */
	if ((isset($_POST["privacy_level"])) && ($privacy_level = (int) trim($_POST["privacy_level"]))) {
		if ($privacy_level > MAX_PRIVACY_LEVEL) {
			$privacy_level = MAX_PRIVACY_LEVEL;
		}
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && !$user->fromArray(array("privacy_level" => $privacy_level))->update()){
			add_error("We were unfortunately unable to update your privacy settings at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update privacy setting. ");
		}
	}
}

function profile_copyright_update() {
	/**
	 * This actually changes the privacy settings in their profile.
	 * Note: The sessions variable ($_SESSION["details"]["privacy_level"]) is actually being
	 * changed in index.php on line 268, so that the proper tabs are displayed.
	 */
	global $ERROR, $ERRORSTR, $ENTRADA_USER;

	if (isset($_POST["copyright"])) {
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && !$user->fromArray(array("copyright" => time()))->update()) {
			add_error("We were unfortunately unable to update your copyright setting at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update copyright setting. ");
		}
	}

	if ((isset($_POST["privacy_level"])) && ($privacy_level = (int) trim($_POST["privacy_level"]))) {
		if ($privacy_level > MAX_PRIVACY_LEVEL) {
			$privacy_level = MAX_PRIVACY_LEVEL;
		}
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
		if ($user && $user->fromArray(array("privacy_level" => $privacy_level))->update()) {
			if ((isset($_POST["redirect"])) && (trim($_POST["redirect"]) != "")) {
				header("Location: ".((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input(rawurldecode($_POST["redirect"]), array("nows", "url")));
				exit;
			} else {
				header("Location: ".ENTRADA_URL);
				exit;
			}
		} else {
			add_error("We were unfortunately unable to update your privacy settings at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update privacy setting.");
		}

	}
}

function copyright_update_google_privacy() {
	/**
	 * This actually changes the copyright setting.
	 */
	global $GOOGLE_APPS, $ERROR, $ERRORSTR, $SUCCESS, $SUCCESSSTR, $ENTRADA_USER;

	if ((bool) $GOOGLE_APPS["active"]) {
		/**
		 * This actually creates a Google Hosted Apps account associated with their profile.
		 * Note: The sessions variable ($_SESSION["details"]["google_id"]) is being
		 * changed in index.php on line 242 to opt-in, which is merely used in the logic
		 * of the first-login page, but only if the user has no google id and hasn't opted out.
		 */
		if (isset($_POST["google_account"])) {
			if ((int) trim($_POST["google_account"])) {
				if (google_create_id()) {
					add_success("<strong>Your new ".$GOOGLE_APPS["domain"]."</strong> account has been created!</strong><br /><br />An e-mail will be sent to ".$_SESSION["details"]["email"]." shortly, containing further instructions regarding account activation.");
				}
			} else {
				$user = Models_User::fetchRowByID($ENTRADA_USER->getID());
				if ($user) {
					$user->fromArray(array("google_id" => "opt-out"))->update();
				}
			}
		}
	}

	if (isset($_POST["copyright"])) {
		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());

		if ($user && $user->fromArray(array("copyright" => time()))->update()) {
			if ((isset($_POST["redirect"])) && (trim($_POST["redirect"]) != "")) {
				header("Location: ".((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input(rawurldecode($_POST["redirect"]), array("nows", "url")));
				exit;
			} else {
				header("Location: ".ENTRADA_URL);
				exit;
			}
		} else {
			add_error("We were unfortunately unable to update your copyright setting at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update copyright setting.");
		}

	}
}

function copyright_update() {
	/**
	 * This actually changes the copyright setting in their profile.
	 */
	global $ENTRADA_USER;

	if (isset($_POST["copyright"])) {

		$user = Models_User::fetchRowByID($ENTRADA_USER->getID());

		if ($user && $user->fromArray(array("copyright" => time()))->update()) {
			if ((isset($_POST["redirect"])) && (trim($_POST["redirect"]) != "")) {
				header("Location: ".((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input(rawurldecode($_POST["redirect"]), array("nows", "url")));
				exit;
			} else {
				header("Location: ".ENTRADA_URL);
				exit;
			}
		} else {
			add_error("We were unfortunately unable to update your copyright setting at this time. The system administrator has been informed of the error, please try again later.");

			application_log("error", "Unable to update copyright setting.");
		}

	}
}
?>