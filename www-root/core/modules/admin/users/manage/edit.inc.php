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
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_USERS")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br><br>If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte();
	$BREADCRUMB[] = array("url" => "", "title" => "Edit Profile");

	$PROCESSED_ACCESS = array();
	$PROCESSED_DEPARTMENTS = array();

	/*
	 * Get the user departments and the custom fields for the departments.
	 */
    $get_initial_permissions = false;

    if ($STEP == 1) {
        $get_initial_permissions = true;

        $user_departments = get_user_departments($PROXY_ID, false);

        if ($user_departments) {
            foreach ($user_departments as $department) {
                $PROCESSED_DEPARTMENTS[$department["department_id"]] = $department["department_title"];
            }
        }

        ksort($PROCESSED_DEPARTMENTS);

        $custom_fields = fetch_department_fields($PROXY_ID);
    }
    ?>

    <script>var step = "<?php echo $STEP; ?>";</script>

    <?php
    // Error Checking
	switch ($STEP) {
		case 2 :
			$permissions = json_decode($_POST["permissions"], true);
			$organisation_order = json_decode(json_decode($_POST["organisation_order"]));

            /**
             * Non-required (although highly recommended) field for staff / student number.
             */
            if (isset($_POST["number"]) && $number = clean_input($_POST["number"], array("trim", "int"))) {
                if ($number != $user_record["number"]) {
                    $user = Models_User::fetchRowByNumber($number);

                    if ($user) {
                        add_error("A user with the <strong>Staff / Student Number</strong> you have provided already exists in the system under the Username <strong>".$user->getUsername()."</strong>.");
                    } else {
                        $PROCESSED["number"] = $number;
                    }
                } else {
                    $PROCESSED["number"] = $user_record["number"];
                }
            } else {
                add_notice("There was no faculty, staff or student number attached to this profile. If this user is a affiliated with the University, please make sure you add this information.");

                $PROCESSED["number"] = 0;
            }

            /**
             * Required field "username" / Username.
             */
            if (isset($_POST["username"]) && $username = clean_input($_POST["username"], array("credentials", "min:3", "max:24"))) {
                if ($username != $user_record["username"]) {
                    $user = Models_User::fetchRowByUsername($username);

                    if ($user) {
                        add_error("A user with the <strong>username</strong> you have provided already exists in the system under the Staff / Student Number <strong>".$user->getNumber()."</strong>.");
                    } else {
                        $PROCESSED["username"] = $username;
                    }
                } else {
                    $PROCESSED["username"] = $user_record["username"];
                }
            } else {
                add_error("<strong>Username</strong> is a required field and must be 3 to 24 characters in length. We suggest that you use their University NetID if at all possible.");
            }

            /**
             * Non-Required field "password" / Password.
             *
             * This is not required in the edit screen because the password is only changed
             * if there is an entry made here.
             */
            if (isset($_POST["password"]) && $_POST["password"]) {
                $password = clean_input($_POST["password"], array("min:6", "max:48"));

                if ($password) {
                    $PROCESSED["salt"] = hash("sha256", (uniqid(rand(), 1) . time() . $PROXY_ID));
                    $PROCESSED["password"] = $password;
                } else {
                    add_error("<strong>Password</strong> must be 6 to 48 characters in length. Leave this field empty if you do not want to change the user's current password.");
                }
            }

            /*
			 * Required field "account_active" / Account Status.
			 */
            if (isset($_POST["account_active"]) && $_POST["account_active"] == "true") {
                $PROCESSED_ACCESS["account_active"] = "true";
            } else {
                $PROCESSED_ACCESS["account_active"] = "false";
            }

            /**
             * Required field "access_starts" / Access Start (validated through validate_calendars function).
             *
             * Non-required field "access_finish" / Access Finish (validated through validate_calendars function).
             */
            $access_date = Entrada_Utilities::validate_calendars("access", true, false);
            if (isset($access_date["start"]) && (int) $access_date["start"]) {
                $PROCESSED_ACCESS["access_starts"] = (int) $access_date["start"];
            }

            if (isset($access_date["finish"]) && (int) $access_date["finish"]) {
                $PROCESSED_ACCESS["access_expires"] = (int) $access_date["finish"];
            } else {
                $PROCESSED_ACCESS["access_expires"] = 0;
            }

            /**
             * Non-required field "prefix" / Prefix.
             */
            if (isset($_POST["prefix"]) && @in_array($prefix = clean_input($_POST["prefix"], "trim"), $PROFILE_NAME_PREFIX)) {
                $PROCESSED["prefix"] = $prefix;
            } else {
                $PROCESSED["prefix"] = "";
            }

            /**
             * Required field "firstname" / Firstname.
             */
            if (isset($_POST["firstname"]) && $firstname = clean_input($_POST["firstname"], "trim")) {
                $PROCESSED["firstname"] = $firstname;
            } else {
                add_error("<strong>First Name</strong> is a required field.");
            }

            /**
             * Required field "lastname" / Lastname.
             */
            if (isset($_POST["lastname"]) && $lastname = clean_input($_POST["lastname"], "trim")) {
                $PROCESSED["lastname"] = $lastname;
            } else {
                add_error("<strong>Last Name</strong> is a required field.");
            }

            /**
             * Non-Required field "gender" / Gender.
             */
            if (isset($_POST["gender"]) && in_array((int) $_POST["gender"], array(1, 2))) {
                $PROCESSED["gender"] = (int) $_POST["gender"];
            } else {
                $PROCESSED["gender"] = 0;
            }

            /**
             * Required field "email" / Primary E-Mail.
             */
            if (isset($_POST["email"]) && $PROCESSED["email"] = clean_input($_POST["email"], "trim", "lower")) {
                if (valid_address($PROCESSED["email"])) {
                    $user = Models_User::fetchRowByEmail($PROCESSED["email"]);

                    if ($user && $user->getID() != $user_record["id"]) {
                        add_error("The e-mail address <strong>".html_encode($PROCESSED["email"])."</strong> already exists in the system under the Username <strong>".html_encode($user->getUsername())."</strong>. Please provide a unique <strong>Primary E-Mail</strong> for this user.");
                    }
                } else {
                    add_error("The <strong>Primary E-Mail</strong> address you have provided is invalid. Please make sure that you provide a properly formatted e-mail address.");
                }
            } else {
                add_error("<strong>Primary E-Mail</strong> address is a required field.");
            }

            /**
             * Non-required field "email_alt" / Alternative E-Mail.
             */
            if ((isset($_POST["email_alt"])) && ($email_alt = clean_input($_POST["email_alt"], "trim", "lower"))) {
                if (@valid_address($email_alt)) {
                    $PROCESSED["email_alt"] = $email_alt;
                } else {
                    add_error("The <strong>Alternative E-Mail</strong> address you have provided is invalid. Please make sure that you provide a properly formatted e-mail address or leave this field empty.");
                }
            } else {
                $PROCESSED["email_alt"] = "";
            }

            /**
             * Non-required field "telephone" / Telephone Number.
             */
            if (isset($_POST["telephone"]) && $_POST["telephone"]) {
                $telephone = clean_input($_POST["telephone"], array("trim", "min:10", "max:25"));

                if ($telephone) {
                    $PROCESSED["telephone"] = $telephone;
                } else {
                    add_error("<strong>Telephone Number</strong> must be 10 to 25 characters in length. Please make sure that you fix it or leave this field empty.");
                }
            } else {
                $PROCESSED["telephone"] = "";
            }

            /**
             * Non-required field "fax" / Fax Number.
             */
            if (isset($_POST["fax"]) && $_POST["fax"]) {
                $fax = clean_input($_POST["fax"], array("trim", "min:10", "max:25"));

                if ($fax) {
                    $PROCESSED["fax"] = $fax;
                } else {
                    add_error("<strong>Fax Number</strong> must be 10 to 25 characters in length. Please make sure that you fix it or leave this field empty.");
                }
            } else {
                $PROCESSED["fax"] = "";
            }

            /**
             * Required field "country_id" / Country.
             */
            if (isset($_POST["country_id"]) && $country_id = clean_input($_POST["country_id"], "int")) {
                $country = Models_Country::fetchRowByID($country_id);

                if ($country) {
                    $PROCESSED["country_id"] = $country_id;
                } else {
                    add_error("The selected <strong>Country</strong> does not exist in our countries database. Please select a valid country.");

                    application_log("error", "Unknown countries_id [".$country_id."] was selected. Database said: ".$db->ErrorMsg());
                }
            } else {
                add_error("<strong>Country</strong> is a required field.");
            }

            /**
             * Non-required field "prov_state" / Province / State.
             */
            $PROCESSED["province_id"] = 0;
            $PROCESSED["province"] = "";

            if (isset($_POST["prov_state"]) && $tmp_input = clean_input($_POST["prov_state"], array("trim", "notags"))) {
                if (ctype_digit($tmp_input) && $tmp_input = (int) $tmp_input) {
                    if ($PROCESSED["country_id"]) {
                        $province = Models_Province::fetchRowByID($tmp_input);

                        if (!$province) {
                            add_error("The selected <strong>Province / State</strong> does not exist in our provinces database. Please selected a valid Province / State.");
                        } else {
                            $PROCESSED["province_id"] = $tmp_input;
                        }
                    }
                } else {
                    $PROCESSED["province"] = $tmp_input;
                }
            }

            /**
             * Non-required field "city" / City.
             */
            if (isset($_POST["city"]) && $_POST["city"]) {
                $city = clean_input($_POST["city"], array("trim", "ucwords", "min:3", "max:35"));

                if ($city) {
                    $PROCESSED["city"] = $city;
                } else {
                    add_error("<strong>City</strong> must be 3 to 35 characters in length. Please make sure that you fix it or leave this field empty.");
                }
            } else {
                $PROCESSED["city"] = "";
            }

            /**
             * Non-required field "address" / Address.
             */
            if (isset($_POST["address"]) && $_POST["address"]) {
                $address = clean_input($_POST["address"], array("trim", "ucwords", "min:6", "max:255"));

                if ($address) {
                    $PROCESSED["address"] = $address;
                } else {
                    add_error("<strong>Address</strong> must be 6 to 255 characters in length. Please make sure that you fix it or leave this field empty.");
                }
            } else {
                $PROCESSED["address"] = "";
            }

            /**
             * Non-required field "postcode" / Post / Zip Code.
             */
            if (isset($_POST["postcode"]) && $_POST["postcode"]) {
                $postcode = clean_input($_POST["postcode"], array("trim", "uppercase", "min:5", "max:12"));

                if ($postcode) {
                    $PROCESSED["postcode"] = $postcode;
                } else {
                    add_error("<strong>Post / Zip Code</strong> must be 5 to 12 characters in length. Please make sure that you fix it or leave this field empty.");
                }
            } else {
                $PROCESSED["postcode"] = "";
            }

            /**
             * Non-required field "office_hours" / Office Hours.
             */
            if (isset($_POST["office_hours"]) && $office_hours = clean_input($_POST["office_hours"], array("notags", "encode", "trim"))) {
                $PROCESSED["office_hours"] = strlen($office_hours) > 100 ? substr($office_hours, 0, 97)."..." : $office_hours;
            } else {
                $PROCESSED["office_hours"] = "";
            }

            /**
             * Non-required field "notes" / General Comments.
             */
            if (isset($_POST["notes"]) && $notes = clean_input($_POST["notes"], array("trim", "notags"))) {
                $PROCESSED["notes"] = $notes;
            } else {
                $PROCESSED["notes"] = "";
            }

            /**
             * Required field "organisation_id" / Organisation.
             */
            if (isset($_POST["organisation_id"]) && $organisation_id = clean_input($_POST["organisation_id"], array("trim", "int"))) {
                $PROCESSED["organisation_id"] = $organisation_id;
            } else {
                add_error("At least one <strong>Permission</strong> is required.");
            }

            /*
             * Non-Required field "clinical" / Clinical.
             */
            $PROCESSED["clinical"] = 0;

            if ($organisation_order) {
                foreach ($organisation_order as $organisation_id) {
                    $organisation_permissions = json_decode($permissions[$organisation_id], true);

                    foreach ($organisation_permissions as $permission) {
                        // As long as one permission has clinical checked, the user is clinical
                        if ($permission["clinical"] == 1) {
                            $PROCESSED["clinical"] = 1;

                            break 2;
                        }
                    }
                }
            }

            /*
			 * Non-Required field "entry_year" / Program Entry Year.
             *
             * Non-Required field "grad_year" / Expected Graduation Year.
			 */
            if ($organisation_order) {
                foreach ($organisation_order as $organisation_id) {
                    $organisation_permissions = json_decode($permissions[$organisation_id], true);

                    foreach ($organisation_permissions as $permission) {
                        if ($permission["group_text"] == "Student") {
                            $min_year = 1995;
                            $max_year = fetch_first_year() + 1;

                            $entry_year = clean_input($permission["entry_year"], array("trim", "int", "min:" . $min_year, "max:" . $max_year));
                            $grad_year = clean_input($permission["grad_year"], array("trim", "int", "min:" . $entry_year, "max:" . $max_year));

                            if ($entry_year && $grad_year) {
                                $PROCESSED["entry_year"] = $entry_year;
                                $PROCESSED["grad_year"] = $grad_year;

                                break 2;
                            } else {
                                add_error("Please enter a valid <strong>Program Entry Year</strong> and <strong>Expected Graduation Year</strong> for the student in " . $permission["org_text"]);
                            }
                        }
                    }
                }
            }

			if (isset($_POST["custom"]) && $_POST["custom"]) {
				/*
				* Fetch the custom fields
				*/
				$query = "SELECT * FROM `profile_custom_fields` WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())." AND `active` = '1' ORDER BY `organisation_id`, `department_id`, `id`";
				$dep_fields = $db->GetAssoc($query);
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
								if ($_POST["custom"][$field["department_id"]][$field_id]) {
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

            if (isset($_POST["user_departments"]) && $_POST["user_departments"]) {
                $user_departments = json_decode(json_decode($_POST["user_departments"]));
                $PROCESSED["department_ids"] = array_unique($user_departments);
            }

			if (!$ERROR) {
                if ($PROCESSED["password"]) {
                    $PROCESSED["password"] = sha1($PROCESSED["password"].$PROCESSED["salt"]);
                }

                $PROCESSED["email_updated"] = time();
                $PROCESSED["updated_by"] = (int) $ENTRADA_USER->getID();
                $PROCESSED["updated_date"] = time();

				if ($db->AutoExecute("`".AUTH_DATABASE."`.`user_data`", $PROCESSED, "UPDATE", "id = ".$db->qstr($PROXY_ID))) {
                    $current_user_permissions = Models_User_Access::fetchAllByUserIDAppID($PROXY_ID);

					if ($current_user_permissions) {
						foreach ($current_user_permissions as $user_permission) {
							$private_hashes[$user_permission->getAppID()][$user_permission->getOrganisationID()][$user_permission->getGroup()][$user_permission->getRole()] = $user_permission->getPrivateHash();
						}
					}

					// Loads current user preferences to check if the roles being changed is currently selected.
					// If it's selected we'll change it to the first role listed for the user
					$current_user_preferences = preferences_load_user("organisation_switcher", $PROXY_ID);

                    if ($current_user_preferences["access_id"]) {
						$current_access_id = $current_user_preferences["access_id"];
					}

					// Gets current role/groups
					$current_group_role = load_org_group_role($PROXY_ID, $current_access_id);
					$current_role = $current_group_role[$current_access_id]["role"];
					$current_group = $current_group_role[$current_access_id]["group"];

					if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech" && strtolower($ENTRADA_USER->getActiveRole()) == "admin") {
						$query = " SELECT DISTINCT o.`organisation_id`, o.`organisation_title`
								   FROM `" . AUTH_DATABASE . "`.`organisations` o";
					} else {
						$query = " SELECT DISTINCT o.`organisation_id`, o.`organisation_title`
								   FROM `".AUTH_DATABASE."`.`user_access` ua
								   JOIN `" . AUTH_DATABASE . "`.`organisations` o
								   ON ua.`organisation_id` = o.`organisation_id`
								   WHERE ua.`user_id` = " . $db->qstr($ENTRADA_USER->getId()). "
								   AND ua.`app_id` = " . $db->qstr(AUTH_APP_ID);
					}

					$temp_all_orgs = $db->GetAll($query);
					$org_ids = "";

					foreach ($temp_all_orgs as $temp_org) {
						if ($ENTRADA_ACL->amIAllowed(new UserResource(null, $temp_org["organisation_id"]), "update")) {
							$org_ids .= ($org_ids ? ", " : "").$db->qstr($temp_org["organisation_id"]);
						}
					}

					$query = "DELETE FROM `".AUTH_DATABASE."`.`user_access`
							  WHERE `user_id` = ".$db->qstr($PROXY_ID) . "
							  AND `app_id` = " . $db->qstr(AUTH_APP_ID)."
							  AND `organisation_id` IN (".$org_ids.")";

					if ($db->Execute($query)) {
						$ENTRADA_CACHE->remove("user_".AUTH_APP_ID."_".$PROXY_ID);
						$ENTRADA_CACHE->remove("acl_"  . AUTH_APP_ID . "_" . $PROXY_ID);

                        $PROCESSED_ACCESS["user_id"] = $PROXY_ID;

                        /**
                        * Bookmarks
                        * Check to see if default bookmarks need to be added for the user according to their permissions
                        */
                        // Get all the default bookmarks from the database
                        $query = "SELECT * FROM `bookmarks_default`";
                        $default_bookmarks = $db->GetAll($query);

                        // Build each bookmarks permissions
                        if ($default_bookmarks) {

                            foreach ($default_bookmarks as $key => $new_bookmark) {
                                if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "organisation") {
                                    // Organisation
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["organisation"] = $new_bookmark["entity_value"];
                                    $default_bookmarks[$key]["group"] = NULL;
                                    $default_bookmarks[$key]["role"] = NULL;


                                } else if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "group") {
                                    //Group
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["group"] = $new_bookmark["entity_value"];
                                    $default_bookmarks[$key]["organisation"] = NULL;
                                    $default_bookmarks[$key]["role"] = NULL;
                                } else if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "role") {
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["role"] = $new_bookmark["entity_value"];
                                    $default_bookmarks[$key]["group"] = NULL;
                                    $default_bookmarks[$key]["organisation"] = NULL;

                                } else if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "group:role") {
                                    // Group:Role
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }

                                    $entity_vals = explode(":", $new_bookmark["entity_value"]);

                                    if (!isset($entity_vals[1])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] needs to have both a group AND a role seperated by a colon. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["group"] = $entity_vals[0];
                                    $default_bookmarks[$key]["role"] = $entity_vals[1];
                                    $default_bookmarks[$key]["organisation"] = NULL;

                                } else if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "organisation:group") {

                                    // Organisation:group
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }

                                    $entity_vals = explode(":", $new_bookmark["entity_value"]);

                                    if (!isset($entity_vals[1])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] needs to have both an organisation AND a group seperated by a colon. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["organisation"] = $entity_vals[0];
                                    $default_bookmarks[$key]["group"] = $entity_vals[1];
                                    $default_bookmarks[$key]["role"] = NULL;

                                } else if (isset($new_bookmark["entity_type"]) && $new_bookmark["entity_type"] == "organisation:group:role") {

                                    // Organisation:group:role
                                    if (!isset($new_bookmark["entity_value"])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
                                        continue;
                                    }

                                    $entity_vals = explode(":", $new_bookmark["entity_value"]);

                                    if (!isset($entity_vals[1]) || !isset($entity_vals[2])) {
                                        application_log("error", "Default Bookmark [".$new_bookmark["permission_id"]."] needs to have both a group, role AND organisation seperated by a colon. Please fix this in the database.");
                                        continue;
                                    }
                                    $default_bookmarks[$key]["organisation"] = $entity_vals[0];
                                    $default_bookmarks[$key]["group"] = $entity_vals[1];
                                    $default_bookmarks[$key]["role"] = $entity_vals[2];

                                }
                            }
                        }

                        foreach ($organisation_order as $organisation_id) {
                            if (!$ENTRADA_ACL->amIAllowed(new UserResource(null, $organisation_id), "update")) {
                                add_error("You do not have permission to give the user permissions within the selected organisation. Please try again with a different organisation.");

                                application_log("error", "Unable to update new user account because this user didn't have permissions to update with the selected organisation ID. This should only happen if the request is tampered with.");

                                continue;
                            }

                            $organisation_permissions = json_decode($permissions[$organisation_id], true);

                            foreach ($organisation_permissions as $permission) {
                                $PROCESSED_ACCESS["group"] = strtolower($permission["group_text"]);
                                $PROCESSED_ACCESS["role"] = strtolower($permission["role_text"]);
                                $PROCESSED_ACCESS["organisation_id"] = $organisation_id;
                                $PROCESSED_ACCESS["app_id"] = AUTH_APP_ID;

                                $result = $private_hashes[AUTH_APP_ID][$organisation_id][$PROCESSED_ACCESS["group"]][$PROCESSED_ACCESS["role"]];

                                if ($result) {
                                    $PROCESSED_ACCESS["private_hash"] = $result;
                                } else {
                                    $PROCESSED_ACCESS["private_hash"] = generate_hash(32);
                                }

                                $user_group_role = $ENTRADA_USER->getActiveGroup() . ":" . $ENTRADA_USER->getActiveRole();

                                if ($user_group_role != "medtech:admin" && $PROCESSED_ACCESS["group"] == "medtech" && $PROCESSED_ACCESS["role"] == "admin") {
                                    add_error("You don't have permission to give " . $PROCESSED_ACCESS["group"] . "/" . $PROCESSED_ACCESS["role"] . " privileges to this user.");

                                    application_log("error", "The user id [".$ENTRADA_USER->getID()."] tried to give " . $PROCESSED_ACCESS["group"]."/".$PROCESSED_ACCESS["role"] . " privileges to user id [".$PROCESSED_ACCESS["user_id"]."]");
                                } else if ($db->AutoExecute(AUTH_DATABASE.".user_access", $PROCESSED_ACCESS, "INSERT")) {
                                    // See if there is a match for each of the user's entries in user_access
                                    foreach ($default_bookmarks as $new_bookmark) {
                                        $add_bookmark = false;

                                        switch ($new_bookmark["entity_type"]) {
                                            case NULL:
                                                $add_bookmark = true;

                                                break;
                                            case "organisation":
                                                $check_perm = $PROCESSED_ACCESS["organisation_id"];
                                                if ($PROCESSED_ACCESS["organisation_id"] == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }

                                                break;
                                            case "group":
                                                $check_perm = $PROCESSED_ACCESS["group"];
                                                if ($PROCESSED_ACCESS["group"] == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }

                                                break;
                                            case "role":
                                                $check_perm = $PROCESSED_ACCESS["role"];
                                                if ($PROCESSED_ACCESS["role"] == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }

                                                break;
                                            case "organisation:group":
                                                $check_perm = $PROCESSED_ACCESS["organisation_id"].":".$PROCESSED_ACCESS["group"];

                                                if ($check_perm == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }
                                                break;
                                            case "organisation:role":
                                                $check_perm = $PROCESSED_ACCESS["organisation_id"].":".$PROCESSED_ACCESS["role"];


                                                break;
                                            case "group:role";
                                                $check_perm = $PROCESSED_ACCESS["group"].":".$PROCESSED_ACCESS["role"];

                                                if ($check_perm == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }
                                                break;
                                            case "organisation:group:role":
                                                $check_perm = $PROCESSED_ACCESS["organisation_id"].":".$PROCESSED_ACCESS["group"].":".$PROCESSED_ACCESS["role"];

                                                if ($check_perm == $new_bookmark["entity_value"]) {
                                                    $add_bookmark = true;
                                                }
                                                break;
                                        }

                                        if ($add_bookmark === true) {

                                            //check if the user already has the bookmark in their bookmarks
                                            $query = "  SELECT
                                                            EXISTS(SELECT 1 FROM `bookmarks` WHERE `uri` = " . $db->qstr($new_bookmark['uri']) . " AND `proxy_id` = " . $db->qstr($PROCESSED_ACCESS["user_id"]) . ")";
                                            $results = $db->GetOne($query);

                                            //If the bookmark doesn't exist, add it
                                            if (!$results) {

                                                $Bookmark = new Models_Bookmarks($new_bookmark);
                                                $Bookmark->setId(NULL); //Set the ID to null since one will be assigned after insert
                                                $Bookmark->setProxyId($PROCESSED_ACCESS["user_id"]);
                                                $Bookmark->setOrder(0);
                                                $Bookmark->setUpdatedDate(time());

                                                if (!$Bookmark->insert()) {
                                                    application_log("error", "Unable to add default bookmark for proxy id[\"".$PROCESSED_ACCESS["user_id"]."\"]. Database said: ".$db->ErrorMsg());
                                                }

                                            }
                                            $add_bookmark = false;
                                        }
                                    }
                                    //END Bookmarks
                                    if ($PROCESSED_ACCESS["group"] == $current_group && ($PROCESSED_ACCESS["role"] == $current_role)) {
                                        $new_access_id = $db->Insert_Id();

                                        // Update the user preference to the new ID
                                        $current_user_preferences["access_id"] = $new_access_id;
                                    } else {
                                        unset($current_user_preferences["access_id"]);
                                    }

                                    if ($PROCESSED_ACCESS["group"] == "medtech" || $PROCESSED_ACCESS["role"] == "admin") {
                                        application_log("error", "USER NOTICE: (".$PROCESSED["firstname"]." ".$PROCESSED["lastname"].") was updated in ".APPLICATION_NAME." as ".$PROCESSED_ACCESS["group"]." > ".$PROCESSED_ACCESS["role"].".");
                                    }
                                } else {
                                    application_log("error", "Unable to insert proxy_id [".$PROCESSED_ACCESS["user_id"]."] into the user_access table. Database said: ".$db->ErrorMsg());
                                }
                            }
                        }
					}

					// Updates user preferences for organisation_switcher if needed
					$old_user_preferences = preferences_load_user("organisation_switcher", $PROXY_ID);
					preferences_update_user("organisation_switcher", $PROXY_ID, $old_user_preferences, $current_user_preferences);

                    $query = "DELETE FROM `".AUTH_DATABASE."`.`user_departments` WHERE `user_id` = ".$db->qstr($PROXY_ID)." AND `entrada_only` = '1'";
                    $db->Execute($query);

                    if (isset($PROCESSED["department_ids"]) && is_array($PROCESSED["department_ids"])) {

                        foreach ($PROCESSED["department_ids"] as $department_id) {
                            $department_id = clean_input($department_id, "int");

                            if ($department_id) {
                                $PROCESSED_DEPARTMENTS[] = $department_id;
                            }
                        }

                        if (isset($PROCESSED_DEPARTMENTS) && $PROCESSED_DEPARTMENTS) {
                            foreach ($PROCESSED_DEPARTMENTS as $department_id) {
                                if (!$db->AutoExecute(AUTH_DATABASE . ".user_departments", array("user_id" => $PROXY_ID, "dep_id" => $department_id, "entrada_only" => "1"), "INSERT")) {
                                    application_log("error", "Unable to insert proxy_id [" . $PROCESSED_ACCESS["user_id"] . "] into department [" . $department_id . "]. Database said: " . $db->ErrorMsg());
                                }
                            }
                            ?>
                            <script>sessionStorage.removeItem("user_departments_list");</script>
                            <?php
                        }
                    }

					if (isset($PROCESSED["custom"])) {
						foreach ($PROCESSED["custom"] as $field_id => $value) {
							$query = "DELETE FROM `profile_custom_responses` WHERE `field_id` = ".$db->qstr($field_id)." AND `proxy_id` = ".$db->qstr($PROXY_ID);
							$db->Execute($query);

							$query = "INSERT INTO `profile_custom_responses` (`field_id`, `proxy_id`, `value`) VALUES (".$db->qstr($field_id).", ".$db->qstr($PROXY_ID).", ".$db->qstr($value).")";
							$db->Execute($query);
						}
					}

					if (isset($PROCESSED["publications"])) {
						$query = "DELETE FROM `profile_publications` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
						if ($db->Execute($query)) {
							foreach ($PROCESSED["publications"] as $pub_type => $ppublications) {
								foreach ($ppublications as $dep_id => $publications) {
									foreach ($publications as $publication) {
										$query = "INSERT INTO `profile_publications` (`pub_type`, `pub_id`, `dep_id`, `proxy_id`) VALUES (".$db->qstr($pub_type).", ".$db->qstr($publication).", ".$db->qstr($dep_id).", ".$db->qstr($ENTRADA_USER->getID()).")";
										$db->Execute($query);
									}
								}
							}
						}
					}
				} else {
					add_error("Unable to update this user account at this time. The system administrator has been informed of this error, please try again later.");

					application_log("error", "Unable to update user account [".$PROXY_ID."]. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			} else {
                $url = ENTRADA_URL."/admin/users/manage?id=".$PROXY_ID;
                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                add_success("You have successfully updated the account for <strong>".html_encode($PROCESSED["firstname"]." ".$PROCESSED["lastname"])."</strong> in the authentication system.<br><br>You will now be redirected to the users profile page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                application_log("success", "Proxy ID [".$ENTRADA_USER->getID()."] successfully updated the proxy id [".$PROXY_ID."] user profile.");

                if (isset($_POST["send_notification"]) && (int) $_POST["send_notification"] == 1) {
                    $PROXY_ID = $PROCESSED_ACCESS["user_id"];

                    do {
                        $HASH = generate_hash();
                    } while ($db->GetRow("SELECT `id` FROM `".AUTH_DATABASE."`.`password_reset` WHERE `hash` = ".$db->qstr($HASH)));

                    if ($db->AutoExecute(AUTH_DATABASE.".password_reset", array("ip" => $_SERVER["REMOTE_ADDR"], "date" => time(), "user_id" => $PROXY_ID, "hash" => $HASH, "complete" => 0), "INSERT")) {
                        // Send welcome & password reset e-mail.
                        $notification_search	= array("%firstname%", "%lastname%", "%username%", "%password_reset_url%", "%application_url%", "%application_name%");
                        $notification_replace	= array($PROCESSED["firstname"], $PROCESSED["lastname"], $PROCESSED["username"], PASSWORD_RESET_URL."?hash=".rawurlencode($PROXY_ID.":".$HASH), ENTRADA_URL, APPLICATION_NAME);

                        $message = str_ireplace($notification_search, $notification_replace, ((isset($_POST["notification_message"])) ? html_encode($_POST["notification_message"]) : $DEFAULT_EDIT_USER_NOTIFICATION));

                        if (!@mail($PROCESSED["email"], "Updated User Account: ".APPLICATION_NAME, $message, "From: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">\nReply-To: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">")) {
                            add_notice("The user was successfully added; however, we could not send them a new account e-mail notice. The MEdTech Unit has been informed of this problem, please send this new user a password reset notice manually.<br><br>You will now be redirected back to the user index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                            application_log("error", "New user [".$PROCESSED["username"]."] was given access to OCR but the e-mail notice failed to send.");
                        }
                    } else {
                        add_notice("The user was successfully added; however, we could not send them a new account e-mail notice. The MEdTech Unit has been informed of this problem, please send this new user a password reset notice manually.<br><br>You will now be redirected back to the user index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                        application_log("error", "New user [".$PROCESSED["username"]."] was given access to OCR but the e-mail notice failed to send. Database said: ".$db->ErrorMsg());
                    }
                }
            }
		break;
		case 1 :
		default :
			$PROCESSED = $user_record;

			$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($PROXY_ID)." AND `app_id` = ".$db->qstr(AUTH_APP_ID);
			$PROCESSED_ACCESS = $db->GetRow($query);
		break;
	}

	// Display Page.
	switch ($STEP) {
		case 2 :
			if ($NOTICE) {
				echo display_notice();
			}

			if ($SUCCESS) {
				echo display_success();
			}
		break;
		case 1 :
		default :
//					$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>\n";
            $HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
			$HEAD[] = "<style type=\"text/css\"> .dynamic-tab-pane-control .tab-page {height:auto;}</style>\n";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/selectchained.js\"></script>\n";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>\n";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />\n";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/manage-users.css\" />\n";

			$i = count($HEAD);
			$HEAD[$i]  = "<script type=\"text/javascript\">\n";
			$HEAD[$i] .= "addListGroup('account_type', 'cs-top');\n";
			if (is_array($SYSTEM_GROUPS)) {
				$item = 1;
				foreach ($SYSTEM_GROUPS as $group => $roles) {
					$HEAD[$i] .= "addList('cs-top', '".ucwords($group)."', '".$group."', 'cs-sub-".$item."', ".(((isset($PROCESSED_ACCESS["group"])) && ($PROCESSED_ACCESS["group"] == $group)) ? "1" : "0").");\n";
					if (is_array($roles)) {
						foreach ($roles as $role) {
							$HEAD[$i] .= "addOption('cs-sub-".$item."', '".ucwords($role)."', '".$role."', ".(((isset($PROCESSED_ACCESS["role"])) && ($PROCESSED_ACCESS["role"] == $role)) ? "1" : "0").");\n";
						}
					}
					$item++;
				}
			}
			$HEAD[$i] .= "</script>\n";

			$ONLOAD[] = "setMaxLength()";

			$DEPARTMENT_LIST = array();
			$query = "	SELECT a.`department_id`, a.`department_title`, a.`organisation_id`, b.`entity_title`
						FROM `".AUTH_DATABASE."`.`departments` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
						ON a.`entity_id` = b.`entity_id`
						ORDER BY a.`department_title`";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $key => $result) {
					$DEPARTMENT_LIST[$result["organisation_id"]][] = array("department_id" => $result["department_id"], "department_title" => $result["department_title"], "entity_title" => $result["entity_title"]);
				}
			}

			$ONLOAD[] = "toggle_visibility_checkbox('#send_notification', '#send_notification_msg')";

			display_status_messages();

			?>

			<h1>Edit Profile for <strong><?php echo html_encode($user_record["firstname"]." ".$user_record["lastname"]); ?></strong></h1>
			<form id="user-edit" class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/users/manage?section=edit&id=<?php echo $PROXY_ID; ?>&amp;step=2" method="post">
				<h2>Account Details</h2>
				<div class="control-group">
					<label class="form-nrequired control-label" for="number">Staff / Student Number:</label>
					<div class="controls">
						<input type="text" id="number" name="number" value="<?php echo (isset($PROCESSED["number"]) ? html_encode($PROCESSED["number"]) : ""); ?>" maxlength="25" />
						<span class="help-inline"><strong>Important:</strong> Required when ever possible.</span>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-required" for="username">Username:</label>
					<div class="controls">
						<input type="text" id="username" name="username" value="<?php echo (isset($PROCESSED["username"]) ? html_encode($PROCESSED["username"]) : ""); ?>" maxlength="25" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label" for="password">Password:</label>
					<div class="controls">
						<input type="password" id="password" name="password" class="input-medium" value="" maxlength="48" />
						<span class="help-inline"><strong>Important:</strong> This will change the current password.</span>
					</div>
				</div>
				<!--- End control-group ---->

				<hr>

				<h2>Account Options</h2>

				<div class="control-group">
					<label class="control-label form-required" for="account_active">Account Status:</label>
					<div class="controls">
						<select id="account_active" name="account_active">
							<option value="true"<?php echo (((!isset($PROCESSED_ACCESS["account_active"])) || ($PROCESSED_ACCESS["account_active"] == "true")) ? " selected=\"selected\"" : ""); ?>>Active</option>
							<option value="false"<?php echo (($PROCESSED_ACCESS["account_active"] == "false") ? " selected=\"selected\"" : ""); ?>>Disabled</option>
						</select>
					</div>
				</div>
				<!--- End control-group ---->

				<?php echo Entrada_Utilities::generate_calendars("access", "Access", true, true, ((isset($PROCESSED_ACCESS["access_starts"])) ? $PROCESSED_ACCESS["access_starts"] : time()), true, false, ((isset($PROCESSED_ACCESS["access_expires"])) ? $PROCESSED_ACCESS["access_expires"] : 0)); ?>

				<hr>

				<h2>Personal Information</h2>

				<div class="control-group">
					<label class="control-label form-nrequired" for="prefix">Prefix:</label>
					<div class="controls">
						<select id="prefix" name="prefix" class="input-small">
						<option value=""<?php echo ((!isset($result["prefix"])) ? " selected=\"selected\"" : ""); ?>></option>
						<?php
						if ((@is_array($PROFILE_NAME_PREFIX)) && (@count($PROFILE_NAME_PREFIX))) {
							foreach ($PROFILE_NAME_PREFIX as $key => $prefix) {
								echo "<option value=\"".html_encode($prefix)."\"".(((isset($PROCESSED["prefix"])) && ($PROCESSED["prefix"] == $prefix)) ? " selected=\"selected\"" : "").">".html_encode($prefix)."</option>\n";
							}
						}
						?>
						</select>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-required" for="firstname">Firstname:</label>
					<div class="controls">
						<input type="text" id="firstname" name="firstname" value="<?php echo ((isset($PROCESSED["firstname"])) ? html_encode($PROCESSED["firstname"]) : ""); ?>" maxlength="35" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-required" for="lastname">Lastname:</label>
					<div class="controls">
						<input type="text" id="lastname" name="lastname" value="<?php echo ((isset($PROCESSED["lastname"])) ? html_encode($PROCESSED["lastname"]) : ""); ?>" maxlength="35" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-required" for="gender">Gender:</label>
					<div class="controls">
						<select name="gender" id="gender">
							<option value="0"<?php echo (!isset($PROCESSED["gender"]) || $PROCESSED["gender"] == 0 ? " selected=\"selected\"" : ""); ?>>Not Specified</option>
							<option value="1"<?php echo (isset($PROCESSED["gender"]) && $PROCESSED["gender"] == 1 ? " selected=\"selected\"" : ""); ?>>Female</option>
							<option value="2"<?php echo (isset($PROCESSED["gender"]) && $PROCESSED["gender"] == 2 ? " selected=\"selected\"" : ""); ?>>Male</option>
						</select>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="email" class="control-label form-required">Primary E-Mail:</label>
					<div class="controls">
						<input type="text" id="email" name="email" value="<?php echo ((isset($PROCESSED["email"])) ? html_encode($PROCESSED["email"]) : ""); ?>" maxlength="128" />
						<span class="help-inline"><strong>Important:</strong> Official e-mail accounts only.</span>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-nrequired" for="email_alt">Alternative E-Mail:</label>
					<div class="controls">
						<input type="text" id="email_alt" name="email_alt" value="<?php echo ((isset($PROCESSED["email_alt"])) ? html_encode($PROCESSED["email_alt"]) : ""); ?>" maxlength="128" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-nrequired" for="telephone">Telephone Number:</label>
					<div class="controls">
						<input type="text" id="telephone" name="telephone" value="<?php echo ((isset($PROCESSED["telephone"])) ? html_encode($PROCESSED["telephone"]) : ""); ?>" maxlength="25" />
						<span class="content-small">(<strong>Example:</strong> 613-533-6000 x74918)</span>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-nrequired" for="fax">Fax Number:</label>
					<div class="controls">
						<input type="text" id="fax" name="fax" value="<?php echo ((isset($PROCESSED["fax"])) ? html_encode($PROCESSED["fax"]) : ""); ?>" maxlength="25" />
						<span class="content-small">(<strong>Example:</strong> 613-533-3204)</span>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label class="control-label form-required" for="country_id">Country:</label>
					<div class="controls">
						<?php
						$countries = fetch_countries();
						if (is_array($countries) && count($countries)) {
							echo "<select id=\"country_id\" name=\"country_id\">\n";
							echo "<option value=\"0\"".((!$PROCESSED["country_id"]) ? " selected=\"selected\"" : "").">-- Select Country --</option>\n";
							foreach ($countries as $country) {
								echo "<option value=\"".(int) $country["countries_id"]."\"".(($PROCESSED["country_id"] == $country["countries_id"]) ? " selected=\"selected\"" : "").">".html_encode($country["country"])."</option>\n";
							}
							echo "</select>\n";
						} else {
							echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
							echo "Country information not currently available.\n";
						}
						?>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
                    <label id="prov_state_label" for="prov_state_div" class="control-label">Province / State:</label>
					<div class="controls">
						<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="city" class="control-label form-nrequired">City:</label>
					<div class="controls">
						<input type="text" id="city" name="city" value="<?php echo ((isset($PROCESSED["city"])) ? html_encode($PROCESSED["city"]) : ""); ?>" maxlength="35" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="address" class="control-label form-nrequired">Address:</label>
					<div class="controls">
						<input type="text" id="address" name="address" value="<?php echo ((isset($PROCESSED["address"])) ? html_encode($PROCESSED["address"]) : ""); ?>" maxlength="255" />
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="postcode" class="control-label form-nrequired">Post / Zip Code:</label>
					<div class="controls">
						<input type="text" id="postcode" name="postcode" value="<?php echo ((isset($PROCESSED["postcode"])) ? html_encode($PROCESSED["postcode"]) : ""); ?>" maxlength="7" />
						<span class="content-small">(<strong>Example:</strong> K7L 3N6)</span>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="office_hours" class="control-label form-nrequired">Office Hours:</label>
					<div class="controls">
						<textarea id="office_hours" class="expandable" name="office_hours"><?php echo (isset($PROCESSED["office_hours"]) && $PROCESSED["office_hours"] ? html_encode($PROCESSED["office_hours"]) : ""); ?></textarea>
					</div>
				</div>
				<!--- End control-group ---->

				<div class="control-group">
					<label for="notes" class="control-label form-nrequired">General Comments:</label>
					<div class="controls">
						<textarea id="notes" class="expandable" name="notes"><?php echo ((isset($PROCESSED["notes"]) && $PROCESSED["notes"]) ? html_encode($PROCESSED["notes"]) : ""); ?></textarea>
					</div>
				</div>
				<!--- End control-group ---->

				<hr>

				<h2>Permissions</h2>
				<?php
				if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech" && strtolower($ENTRADA_USER->getActiveRole()) == "admin") {
					$query = "	SELECT DISTINCT o.`organisation_id`, o.`organisation_title`
                                FROM `" . AUTH_DATABASE . "`.`organisations` o
                                ORDER BY o.`organisation_title`";
				} else {
					$query = "	SELECT DISTINCT o.`organisation_id`, o.`organisation_title`
                                FROM `".AUTH_DATABASE."`.`user_access` ua
                                JOIN `" . AUTH_DATABASE . "`.`organisations` o
                                ON ua.`organisation_id` = o.`organisation_id`
                                WHERE ua.`user_id` = " . $db->qstr($ENTRADA_USER->getId()). "
                                AND ua.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                                ORDER BY o.`organisation_title`";
				}

				$temp_all_orgs = $db->GetAll($query);
                $organisations = array();

				foreach ($temp_all_orgs as $temp_org) {
					if ($ENTRADA_ACL->amIAllowed(new UserResource(null, $temp_org["organisation_id"]), "update")) {
                        $organisations[] = $temp_org;
					}
				}

				if ($organisations) {
					?>
                    <div id="adding-permission-errors"></div>

                    <div id="adding-permission-container" class="row-fluid">
                        <div class="span6">
                            <label for="organisations"><strong>Organisation</strong></label><br>
                            <select id="organisations" name="organisations" class="span12">
                                <option value="0">Select an Organisation</option>
                                <?php
                                foreach ($organisations as $organisation) {
                                    echo build_option($organisation["organisation_id"], ucfirst($organisation["organisation_title"]), $selected);
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <br>

                    <div class="row-fluid">
                        <input id="add_permissions" name="add_permissions" type="button" value="Add Permission" class="btn btn-success pull-right" />
                    </div>

                    <br>

                    <div id="added-permissions-container">

					<?php
                    if ($get_initial_permissions) {
                        $initial_permissions = array();

                        foreach ($organisations as $organisation) {
                            $query = "	SELECT ua.*, o.`organisation_title`, ud.`clinical`, ud.`entry_year`, ud.`grad_year`
                                        FROM `" . AUTH_DATABASE . "`.`user_access` ua
                                        JOIN `" . AUTH_DATABASE . "`.`organisations` o
                                        ON ua.`organisation_id` = o.`organisation_id`
                                        JOIN `" . AUTH_DATABASE . "`.`user_data` ud
                                        ON ua.`user_id` = ud.`id`
                                        AND ua.`organisation_id` = " . $db->qstr($organisation["organisation_id"]) . "
                                        WHERE ua.`user_id` = " . $db->qstr($PROXY_ID) . "
                                        AND ua.`app_id` = " . $db->qstr(AUTH_APP_ID);

                            $results = $db->GetAll($query);

                            if ($results) { ?>
                                <div id="organisation-<?php echo $organisation["organisation_id"]; ?>-permissions" class="panel panel-info organisation-container">
                                    <div class="panel-heading organisation-label"><?php echo $organisation["organisation_title"]; ?></div>

                                    <ul class="list-group permissions">
                                        <?php
                                        foreach ($results as $result) {
                                            $query = "	SELECT g.`id`
                                                        FROM `" . AUTH_DATABASE . "`.`system_groups` g,
                                                             `" . AUTH_DATABASE . "`.`organisations` o,
                                                             `" . AUTH_DATABASE . "`.`system_group_organisation` gho
                                                        WHERE g.`visible`
                                                        AND o.`organisation_id` = gho.`organisation_id`
                                                        AND gho.`groups_id` = g.`id`
                                                        AND o.`organisation_id` = " . $db->qstr($organisation["organisation_id"]) . "
                                                        AND g.`group_name` = " . $db->qstr($result["group"]);
                                            $group_id = (int) $db->GetOne($query) ?: 0;

                                            $query = "SELECT sr.`id`
                                                      FROM " . AUTH_DATABASE . ".`system_roles` sr
                                                      WHERE sr.`role_name` = " . $db->qstr($result["role"]) . "
                                                      AND sr.`groups_id` = " . $db->qstr($group_id);
                                            $role_id = (int) $db->GetOne($query) ?: 0;

                                                $current_role_results = array(
                                                "org_text" => $result["organisation_title"],
                                                "group_id" => $group_id,
                                                "group_text" => ucfirst($result["group"]),
                                                "role_id" => $role_id,
                                                "role_text" => ucfirst($result["role"]),
                                                "clinical" => $result["clinical"],
                                                "entry_year" => $result["entry_year"],
                                                "grad_year" => $result["grad_year"],
                                            );

                                            // this only allows for one group per role
                                            if (array_key_exists($result["organisation_id"], $initial_permissions)) {
                                                $initial_permissions[$result["organisation_id"]][$group_id] = $current_role_results;
                                            } else {
                                                $initial_permissions[$result["organisation_id"]] = array();
                                                $initial_permissions[$result["organisation_id"]][$group_id] = $current_role_results;
                                            }

                                            $group_role = ucfirst($result["group"]) . " / " . ucfirst($result["role"]);
                                            ?>

                                            <li class="list-group-item<?php echo $result["group"] == "student" ? " student-li" : ""; ?>">
                                                <i class="fa fa-minus-circle fa-lg remove-permission"
                                                   data-org="<?php echo $result["organisation_id"]; ?>"
                                                   data-group="<?php echo $group_id; ?>"></i>&nbsp;&nbsp;
                                                <span class="group-role"><?php echo $group_role; ?></span>

                                                <?php
                                                switch ($result["group"]) {
                                                    case "faculty" :
                                                        $input_id = "clinical_" . $result["organisation_id"];
                                                        $checked = $result["clinical"] ? "checked" : "";

                                                        $options = "<span class=\"pull-right\">";
                                                        $options .= "<input id=\"" . $input_id . "\" class=\"clinical-checkbox\" name=\"clinical_" . $result["organisation_id"] . "\" type=\"checkbox\" " . $checked . ">&nbsp;";
                                                        $options .= "<label for=\"" . $input_id . "\" class=\"clinical-label\"><strong>Clinical</strong> faculty member</label>";
                                                        $options .= "</span>";
                                                        break;
                                                    case "student" :
                                                        $entry_year_id = "organisation-" . $result["organisation_id"] . "-entry-year";
                                                        $grad_year_id = "organisation-" . $result["organisation_id"] . "-grad-year";

                                                        $options = "<div class=\"pull-right student-years\">";
                                                        $options .= "<label class=\"entry-year-label\" for=\"" . $entry_year_id . "\">Program Entry Year</label>&nbsp;";
                                                        $options .= "<select id=\"" . $entry_year_id . "\" class=\"entry-year space-right\">";
                                                        for ($i = fetch_first_year(); $i >= 1995; $i--) {
                                                            $selected = $result["entry_year"] == $i;
                                                            $options .= build_option($i, $i, $selected);
                                                        }
                                                        $options .= "</select>";
                                                        $options .= "<label class=\"grad-year-label\" for=\"" . $grad_year_id . "\">Expected Graduation Year</label>&nbsp;";
                                                        $options .= "<select id=\"" . $grad_year_id . "\" class=\"grad-year\">";
                                                        for ($i = (fetch_first_year() + 1); $i >= 1995; $i--) {
                                                            $selected = $result["grad_year"] == $i;
                                                            $options .= build_option($i, $i, $selected);
                                                        }
                                                        $options .= "</select>";
                                                        $options .= "</div>";
                                                        break;
                                                    default :
                                                        $options = "";
                                                }

                                                echo $options;
                                                ?>
                                            </li>

                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>

                                <?php
                            }
                        }
                    }
                    ?>
                    </div>
                    <hr>

                    <input id="permissions" name="permissions" type="hidden" value="">
                    <input id="organisation-order" name="organisation_order" type="hidden" value="">
                    <input id="organisation-id" name="organisation_id" type="hidden" value="">
					<?php
				}
				?>

                <div id="departmental-affiliation-container">
                    <h2>Departmental Affiliation</h2>
                    
                    <div id="advanced-search-container">
                        <button type="button" id="departments-advanced-search" class="btn btn-search-filter departments-advanced-search">
                            <?php echo $translate->_("Browse Departments"); ?>
                            <i class="icon-chevron-down pull-right btn-icon"></i>
                        </button>
                    </div>

                    <?php
                    if (isset($PROCESSED["department_ids"]) && is_array($PROCESSED["department_ids"])) {
                        foreach ($PROCESSED["department_ids"] as $department_id) {
                            $organisation_title = Models_Department::fetchOrganisationTitleByDepartmentID($department_id);
                            $organisation_filter_name = str_replace(" ", "_", $organisation_title);
                            $department = Models_Department::fetchRowByID($department_id);
                            $input = "<input type=\"hidden\" name=\"" . $organisation_filter_name . "[]\" value=\"" . $department_id . "\" id=\"" . $organisation_filter_name . "_" . $department_id . "\" data-id=\"" . $department_id . "\" data-label=\"" . $department->getDepartmentTitle() . "\" data-filter=\"" . $organisation_filter_name . "\" class=\"search-target-control " . $organisation_filter_name . "_search_target_control\">";

                            echo $input;
                        }
                    } else if ($PROCESSED_DEPARTMENTS) {
                        foreach ($PROCESSED_DEPARTMENTS as $department_id => $department_title) {
                            $organisation_title = Models_Department::fetchOrganisationTitleByDepartmentID($department_id);
                            $organisation_filter_name = str_replace(" ", "_", $organisation_title);

                            $input = "<input type=\"hidden\" name=\"" . $organisation_filter_name . "[]\" value=\"" . $department_id . "\" id=\"" . $organisation_filter_name . "_" . $department_id . "\" data-id=\"" . $department_id . "\" data-label=\"" . $department_title . "\" data-filter=\"" . $organisation_filter_name . "\" class=\"search-target-control " . $organisation_filter_name . "_search_target_control\">";

                            echo $input;
                        }
                    }
                    ?>

                    <ul id="departments-list" class="list-group departments">
                        <?php
                        if ($PROCESSED_DEPARTMENTS) {
                            foreach ($PROCESSED_DEPARTMENTS as $department_id => $department_title) {
                                $organisation_title = Models_Department::fetchOrganisationTitleByDepartmentID($department_id);
                                ?>
                                <li class="list-group-item" id="<?php echo "department-" . $department_id; ?>">
                                    <i class="fa fa-minus-circle fa-lg remove-department" data-dept="<?php echo $department_id; ?>"></i>

                                    &nbsp;

                                    <span class="department-name"><?php echo $department_title . " &#45; " . $organisation_title; ?></span>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>

                    <input id="departments" name="user_departments" type="hidden">

                    <hr>
                </div>                 
                
				<h2>Notification Options</h2>

				<label class="checkbox">
					<input type="checkbox" id="send_notification" name="send_notification" value="1"<?php echo (isset($_POST["send_notification"]) && (int) $_POST["send_notification"] ? " checked=\"checked\"" : ""); ?> onclick="toggle_visibility_checkbox(this, '#send_notification_msg')" /> Send this user the following password reset e-mail after saving.
				</label>
				<div id="send_notification_msg" class="space-below" style="display: block">
					<div class="content-small"><strong>Available Variables:</strong> %firstname%, %lastname%, %username%, %password_reset_url%, %application_url%, %application_name%</div>
					<textarea id="notification_message" class="expandable" name="notification_message" style="width: 98%; min-height: 250px"><?php echo ((isset($_POST["notification_message"])) ? html_encode($_POST["notification_message"]) : $DEFAULT_EDIT_USER_NOTIFICATION); ?></textarea>
				</div>

				<?php
				if ($custom_fields) {
					$pub_types = array (
						"ar_poster_reports"				=> array("id_field" => "poster_reports_id", "title" => "title"),
						"ar_peer_reviewed_papers"		=> array("id_field" => "peer_reviewed_papers_id", "title" => "title"),
						"ar_non_peer_reviewed_papers"	=> array("id_field" => "non_peer_reviewed_papers_id", "title" => "title"),
						"ar_book_chapter_mono"			=> array("id_field" => "book_chapter_mono_id", "title" => "title"),
						"ar_conference_papers"			=> array("id_field" => "conference_papers_id", "title" => "lectures_papers_list")
					);
					echo "<h2>Department Specific Information</h2>";
					$NOTICESTR = array();
					add_notice("The information below has been requested by departments the user is a member of. This information is considered public and may be published on department websites.");
					echo display_notice();
					echo "<div class=\"tabbable departments\">";
					echo "<ul class=\"nav nav-tabs\">";
					$i = 0;
					foreach ($PROCESSED_DEPARTMENTS as $department_id => $department) {
						if (count($custom_fields[$department_id]) >= 1) {
							?>
							<li class="<?php echo $i == 0 ? "active" : ""; ?>"><a data-toggle="tab" href="#dep-<?php echo $department_id; ?>"><?php echo strlen($department) > 15 ? substr($department, 0, 15)."..." : $department; ?></a></li>
							<?php
							$i++;
						}
					}
					echo "</ul>";

					echo "<div class=\"tab-content\">";
					$i = 0;
					foreach ($PROCESSED_DEPARTMENTS as $department_id => $department) {
						if (count($custom_fields[$department_id]) >= 1) {
						echo "<div class=\"tab-pane ".($i == 0 ? "active" : "")."\" id=\"dep-".$department_id."\">";
						echo "<h3>".$department."</h3>";
						foreach ($custom_fields[$department_id] as $field) { ?>
							<div class="control-group">
								<label class="control-label <?php echo $field["required"] == "1" ? " form-required" : ""; ?>" for="<?php echo $field["name"]; ?>"><?php echo $field["title"]; ?></label>
								<div class="controls">
									<?php
										$field["type"] = strtolower($field["type"]);
										switch ($field["type"]) {
											case "textarea" :
												?>
												<textarea id="<?php echo $field["name"]; ?>" class="input-xlarge expandable expanded" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>"><?php echo $field["value"]; ?></textarea>
												<?php
											break;
											case "textinput" :
											case "twitter" :
											case "link" :
												?>
												<input type="text" id="<?php echo $field["name"]; ?>" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>" value="<?php echo $field["value"]; ?>" />
												<?php
											break;
											case "richtext" :
												?>
												<textarea id="<?php echo $field["name"]; ?>" class="input-xlarge" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" maxlength="<?php echo $field["length"]; ?>"><?php echo $field["value"]; ?></textarea>
												<?php
											break;
											case "checkbox" :
												?>
												<label class="checkbox"><input type="checkbox" id="<?php echo $field["name"]; ?>" name="custom[<?php echo $department_id; ?>][<?php echo $field["id"]; ?>]" value="<?php echo $field["value"]; ?>" <?php echo $field["value"] == "1" ? " checked=\"checked\"" : ""; ?> />
												<?php echo $field["helptext"] ? $field["helptext"] : ""; ?></label>
												<?php
											break;
										}
									?>

								</div>
							</div>
						<?php }

						echo "<h3>Publications on ".$department." Website</h3>";

						foreach ($pub_types as $type_table => $data) {
							$query = "	SELECT a.`".$data["id_field"]."` AS `id`, a.`".$data["title"]."` AS `title`, a.`year_reported`, b.`id` AS `dep_pub_id`
										FROM `".$type_table."` AS a
										LEFT JOIN `profile_publications` AS b
										ON a.`proxy_id` = b.`proxy_id`
										AND b.`pub_id` = a.`".$data["id_field"]."`
										AND (b.`dep_id` = ".$db->qstr($department_id). " || b.`dep_id` IS NULL)
										WHERE a.`proxy_id` = ".$db->qstr($user_record["id"]);
							$pubs = $db->GetAll($query);
							if ($pubs) { ?>
								<h4><?php echo ucwords(str_replace("ar ", "", str_replace("_", " ", $type_table))); ?></h4>
								<table width="100%" cellpadding="0" cellspacing="0" border="0" class="table table-striped table-hover table-bordered table-nowrap">
									<thead>
										<tr>
											<th>Title</th>
											<th width="10%">Date</th>
											<th width="8%">Visible</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($pubs as $publication) { ?>
										<tr data-id="<?php echo $publication["id"]; ?>">
											<td><?php echo $publication["title"]; ?></td>
											<td><?php echo $publication["year_reported"]; ?></td>
											<td><input type="checkbox" name="publications[<?php echo str_replace("ar_", "", $type_table); ?>][<?php echo $department_id; ?>][<?php echo $publication["id"]; ?>]" <?php echo ($publication["dep_pub_id"] != NULL ? "checked=\"checked\"" : ""); ?> /></td>
										</tr>
									<?php } ?>
									</tbody>
								</table>
								<?php
							}
						}

						echo "</div>";
						$i++;
						}
					}
					echo "</div>";
					echo "</div>";
				}
				?>

				<div class="pull-left"><input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_RELATIVE; ?>/admin/users/manage?id=<?php echo $PROXY_ID; ?>'" /></div>
				<div class="pull-right"><input type="submit" class="btn btn-primary" value="Save" /></div>
			</form>
            <script>
                jQuery(document).ready(function($) {
                    var glob_type = null;
                    var permissions = {};
                    var organisation_order = [];
                    var departments = [];

                    provStateFunction(<?php echo (isset($PROCESSED["country_id"]) ? (int) $PROCESSED["country_id"] : 0); ?>, <?php echo (isset($PROCESSED["province_id"]) ? (int) $PROCESSED["province_id"] : 0); ?>);

                    <?php
                    if ($initial_permissions && is_array($initial_permissions)) {
                        foreach ($initial_permissions as $org_id => $group_ids) {
                            if ($group_ids && is_array($group_ids)) {
                                foreach ($group_ids as $permission) {
                                ?>
                                    var org_id = "<?php echo $org_id; ?>";

                                    if (typeof permissions[org_id] === "undefined") {
                                        permissions[org_id] = [];

                                        organisation_order.push(org_id);
                                    }

                                    permissions[org_id].push({
                                        "org_text": "<?php echo $permission["org_text"]; ?>",
                                        "group_id": "<?php echo $permission["group_id"]; ?>",
                                        "group_text": "<?php echo $permission["group_text"]; ?>",
                                        "role_id": "<?php echo $permission["role_id"]; ?>",
                                        "role_text": "<?php echo $permission["role_text"]; ?>",
                                        "clinical": "<?php echo (int) $permission["clinical"]; ?>",
                                        "entry_year": "<?php echo $permission["entry_year"]; ?>",
                                        "grad_year": "<?php echo $permission["grad_year"]; ?>"
                                    });
                                    <?php
                                }
                            }
                        }
                    }

                    if ($PROCESSED_DEPARTMENTS) {
                        $department_ids = array_keys($PROCESSED_DEPARTMENTS);

                        foreach ($department_ids as $department_id) { ?>
                            departments.push("<?php echo $department_id; ?>");
                            <?php
                        }
                    }
                    ?>

                    if (!$.isEmptyObject(permissions)) {
                        refreshAdvancedSearch();
                    }

                    if (sessionStorage.getItem("user_departments_list")) {
                        if (step > 1) {
                            var departments_list = JSON.parse(sessionStorage.getItem("user_departments_list"));

                            rebuildSelectedDepartments(JSON.parse(departments_list));
                        } else {
                            sessionStorage.removeItem("user_departments_list");
                        }
                    }

                    if (sessionStorage.getItem("users_permissions")) {
                        if (step > 1) {
                            permissions = JSON.parse(sessionStorage.getItem("users_permissions"));
                            organisation_order = JSON.parse(JSON.parse(sessionStorage.getItem("organisation_order")));

                            rebuildSelectedPermissions();

                            if (organisation_order.length) {
                                refreshAdvancedSearch();
                            }
                        } else {
                            sessionStorage.removeItem("users_permissions");
                            sessionStorage.removeItem("organisation_order");
                        }
                    }

                    if (!organisation_order.length) {
                        $("#departmental-affiliation-container").hide();
                    }

                    $("#user-edit").on("click", "#display-notice a.add-another-user", function (e) {
                        $("#display-notice").remove();

                        disableInputs(false, "");
                    });

                    $("#country_id").on("change", function () {
                        provStateFunction();
                    });

                    $("select[name=organisations]").on("change", function (e) {
                        $("#groups-container").remove();
                        $("#roles-container").remove();

                        var org_id = $(this).val();

                        if (org_id > 0) {
                            showLoadingIcon("Loading Groups...");

                            var url = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-organisations"; ?>";

                            $.getJSON(url, { method: "get-organisation-groups", organisation_id: org_id })
                                .done(function (data) {
                                    var groups_container = $(document.createElement("div")).attr("id", "groups-container").addClass("span3");
                                    var label = $(document.createElement("label")).attr("for", "groups");
                                    var strong = $(document.createElement("strong")).html("Group");
                                    var select = $(document.createElement("select")).attr("id", "groups").attr("name", "groups").addClass("span12");
                                    var option = $(document.createElement("option")).attr("value", "0").html("Select a Group");

                                    $(label).append(strong);
                                    $(select).append(option);

                                    $.each(data, function (index, group) {
                                        var option = document.createElement("option");

                                        option.value = group.id;
                                        option.innerHTML = group.group_name;

                                        $(select).append(option);
                                    });

                                    $("#permissions-loading").remove();

                                    $(groups_container).append(label).append(select);
                                    $("#adding-permission-container").append(groups_container);

                                    filterGroups();
                                });
                        }
                    });

                    $("#adding-permission-container").on("change", "select[name=groups]", function (e) {
                        $("#roles-container").remove();

                        showLoadingIcon("Loading Roles...");

                        var url = "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-organisations"; ?>";

                        $.getJSON(url, { method: "get-organisation-roles", organisation_id: $("#organisations option:selected").val(), group_id: $(this).val() })
                            .done(function (data) {
                                var roles_container = $(document.createElement("div")).attr("id", "roles-container").addClass("span3");
                                var label = $(document.createElement("label")).attr("for", "roles");
                                var strong = $(document.createElement("strong")).html("Role");
                                var select = $(document.createElement("select")).attr("id", "roles").attr("name", "roles").addClass("span12");
                                var option = $(document.createElement("option")).attr("value", "0").html("Select a Role");

                                $(label).append(strong);
                                $(select).append(option);

                                $.each(data, function (index, role) {
                                    var option = document.createElement("option");

                                    option.value = role.id;
                                    option.innerHTML = role.role_name;

                                    $(select).append(option);
                                });

                                $("#permissions-loading").remove();

                                $(roles_container).append(label).append(select);
                                $("#adding-permission-container").append(roles_container);
                            });
                    });

                    $("#add_permissions").on("click", function () {
                        var org_id = $("#organisations").val();
                        var group_id = $("#groups").val();
                        var role_id = $("#roles").val();

                        var error = validateAddingPermission(org_id, group_id, role_id);

                        if (error) {
                            display_error([error], "#adding-permission-errors");
                        } else {
                            var org_text = $("#organisations option:selected").text();
                            var group_text = $("#groups option:selected").text();
                            var role_text = $("#roles option:selected").text();

                            $("#organisations option:first").attr("selected", true);
                            $("#groups-container").remove();
                            $("#roles-container").remove();

                            addNewPermission(org_id, group_id, role_id, org_text, group_text, role_text);

                            $("#adding-permission-errors").html("");
                        }
                    });

                    $("#user-edit").on("change", ".search-target-input-control", function () {
                        var dept_id = $(this).val();

                        if ($(this).is(":checked")) {
                            var department_li = $(document.createElement("li")).attr("id", "department-" + dept_id).addClass("list-group-item");
                            var icon = $(document.createElement("i")).attr("data-dept", dept_id).addClass("fa fa-minus-circle fa-lg remove-department");
                            var span = $(document.createElement("span")).addClass("department-name");
                            var organisation_title = "";
                            var self = this;

                            $.getJSON("<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-departments"; ?>", { method: "get-organisation-title-by-dept-id", dept_id: dept_id }).done(function (data) {
                                organisation_title = data.organisation_title;

                                if (organisation_title) {
                                    organisation_title = " &#45; " + organisation_title;
                                }

                                $(span).html($(self).parent().parent().find(".search-target-label label").text() + organisation_title);

                                $(department_li).append(icon).append("&nbsp;&nbsp;").append(span);

                                $("#departments-list").append(department_li);

                                departments.push(dept_id);
                            });
                        } else {
                            $("#departments-list").find("li#department-" + dept_id).remove();

                            removeDepartment(dept_id);
                        }
                    });

                    $("#user-edit").on("click", ".remove-target-toggle", function () {
                        var dept_id = $(this).attr("data-id");

                        removeDepartment(dept_id);

                        $("#departments-list").find("li#department-" + dept_id).remove();
                    });

                    $("#user-edit").on("click", ".remove-department", function (e) {
                        var dept_id = $(this).attr("data-dept");

                        removeDepartment(dept_id);

                        $(this).closest("li").remove();

                        $("#user-edit").find("input[value=" + dept_id + "]").remove();
                    });

                    $("#added-permissions-container").on("click", ".remove-permission", function (e) {
                        var org_id = $(this).attr("data-org");
                        var group_id = $(this).attr("data-group");

                        removePermission(org_id, group_id);

                        // The user was in the process of adding another permission within the same organisation,
                        // so lets put the permission they just removed back into the group list just in case.
                        if ($("#groups").length && $("#organisations").val() == org_id) {
                            var group_text = $(this).parent().find(".group-role").text().split(" / ")[0];

                            var option = $(document.createElement("option")).attr("value", group_id).text(group_text);

                            $("#groups").append(option);

                            var options = $("#groups option:not(:first)");
                            options.remove();

                            options = sortOptions(options);

                            $("#groups").append(options);
                        }

                        var self = $(this);
                        var ul = self.closest("ul");

                        self.closest("li").remove();

                        // If no permissions are left for this organisation, we can remove its container 
                        // and remove any selected departments that belong to this organisation.
                        if (ul.find("li").length === 0) {
                            ul.closest(".organisation-container").remove();
                            
                            refreshAdvancedSearch();
                            checkForSelectedDepartments(org_id);
                        }
                    });

                    $("#user-edit").on("submit", function (e) {
                        $("input[name=permissions]").val(JSON.stringify(permissions));
                        $("input[name=organisation_order]").val(JSON.stringify(organisation_order));
                        $("input[name=organisation_id]").val(organisation_order[0]);
                        $("input[name=user_departments]").val(JSON.stringify(departments));

                        sessionStorage.setItem("users_permissions", JSON.stringify(permissions));
                        sessionStorage.setItem("organisation_order", JSON.stringify(organisation_order));
                        sessionStorage.setItem("user_departments_list", JSON.stringify(departments));
                    });

                    $("#added-permissions-container").on("change", "input[id^=clinical_]", function (e) {
                        var org_id = $(this).attr("id").split("_")[1];

                        if (typeof permissions[org_id] === "string") {
                            permissions[org_id] = JSON.parse(permissions[org_id]);
                        }

                        for (var i = 0; i < permissions[org_id].length; i++) {
                            if (permissions[org_id][i].group_text == "Faculty") {
                                permissions[org_id][i].clinical = Number($(this).is(":checked"));

                                break;
                            }
                        }
                    });

                    $("#added-permissions-container").on("change", "select.entry-year", function (e) {
                        var org_id = $(this).attr("id").split("-")[1];

                        if (typeof permissions[org_id] === "string") {
                            permissions[org_id] = JSON.parse(permissions[org_id]);
                        }

                        for (var i = 0; i < permissions[org_id].length; i++) {
                            if (permissions[org_id][i].group_text == "Student") {
                                permissions[org_id][i].entry_year = $(this).val();

                                break;
                            }
                        }
                    });

                    $("#added-permissions-container").on("change", "select.grad-year", function (e) {
                        var org_id = $(this).attr("id").split("-")[1];

                        if (typeof permissions[org_id] === "string") {
                            permissions[org_id] = JSON.parse(permissions[org_id]);
                        }

                        for (var i = 0; i < permissions[org_id].length; i++) {
                            if (permissions[org_id][i].group_text == "Student") {
                                permissions[org_id][i].grad_year = $(this).val();

                                break;
                            }
                        }
                    });

                    function addNewPermission(org_id, group_id, role_id, org_text, group_text, role_text) {
                        var clinical = 0;
                        var entry_year = 0;
                        var grad_year = 0;

                        var permission_li = $(document.createElement("li")).addClass("list-group-item");
                        var icon = $(document.createElement("icon")).attr("data-org", org_id).attr("data-group", group_id).addClass("fa fa-minus-circle fa-lg remove-permission");
                        var span = $(document.createElement("span")).addClass("group-role").html(group_text + " / " + role_text);

                        $(permission_li).append(icon).append("&nbsp;&nbsp;").append(span);

                        if (group_text == "Faculty") {
                            clinical = 1;

                            var clinical_span = $(document.createElement("span")).addClass("pull-right");
                            var input = $(document.createElement("input")).attr({ id: "clinical_" + org_id, name: "clinical_" + org_id, type: "checkbox", checked: true }).addClass("clinical-checkbox");
                            var clinical_label = $(document.createElement("label")).attr("for", "clinical_" + org_id).addClass("clinical-label");
                            var strong = $(document.createElement("strong")).html("Clinical");

                            $(clinical_label).append("&nbsp;").append(strong).append(" faculty member");
                            $(clinical_span).append(input).append(clinical_label);
                            $(permission_li).append(clinical_span);
                        } else if (group_text == "Student") {
                            var entry_year_options = '<?php
                                                      $selected_year = (isset($PROCESSED["entry_year"])) ? $PROCESSED["entry_year"] : (date("Y", time()) - ((date("m", time()) < 7) ?  1 : 0));
                                                      for ($i = fetch_first_year(); $i >= 1995; $i--) {
                                                          $selected = $selected_year == $i;
                                                          echo build_option($i, $i, $selected);
                                                      }
                                                      ?>';
                            var grad_year_options = '<?php
                                                     for ($i = (fetch_first_year() + 1); $i >= 1995; $i--) {
                                                         $selected = (isset($PROCESSED["grad_year"]) && $PROCESSED["grad_year"] == $i);
                                                         echo build_option($i, $i, $selected);
                                                     }
                                                     ?>';
                            
                            var select_entry_year_id = "organisation-" + org_id + "-entry-year";
                            var select_grad_year_id = "organisation-" + org_id + "-grad-year";

                            var student_years_container = $(document.createElement("div")).addClass("pull-right student-years");
                            var entry_year_label = $(document.createElement("label")).attr("for", select_entry_year_id).addClass("entry-year-label").html("Program Entry Year&nbsp;");
                            var entry_year_select = $(document.createElement("select")).attr("id", select_entry_year_id).addClass("entry-year space-right").html(entry_year_options);
                            var grad_year_label = $(document.createElement("label")).attr("for", select_grad_year_id).addClass("grad-year-label").html("Expected Graduation Year&nbsp;");
                            var grad_year_select = $(document.createElement("select")).attr("id", select_grad_year_id).addClass("grad-year").html(grad_year_options);

                            entry_year = $(entry_year_select).val();
                            grad_year = $(grad_year_select).val();

                            $(student_years_container).append(entry_year_label).append(entry_year_select).append(grad_year_label).append(grad_year_select);

                            $(permission_li).addClass("student-li").append(student_years_container);
                        }

                        if ($("#organisation-" + org_id + "-permissions").length) {
                            $("#organisation-" + org_id + "-permissions .permissions").append(permission_li);
                        } else {
                            var organisation_container = $(document.createElement("div")).attr("id", "organisation-" + org_id + "-permissions").addClass("panel panel-info organisation-container");
                            var organisation_label = $(document.createElement("div")).addClass("panel-heading organisation-label").html(org_text);
                            var ul = $(document.createElement("ul")).addClass("list-group permissions");

                            $(ul).append(permission_li);
                            $(organisation_container).append(organisation_label).append(ul);

                            $("#added-permissions-container").append(organisation_container);
                        }

                        var refreshDepartments = false;

                        if (typeof permissions[org_id] === "undefined") {
                            permissions[org_id] = [];

                            organisation_order.push(org_id);
                            
                            refreshDepartments = true;
                        } else if (typeof permissions[org_id] === "string") {
                            permissions[org_id] = JSON.parse(permissions[org_id]);
                        }

                        permissions[org_id].push({
                            "org_text": org_text,
                            "group_id": group_id,
                            "group_text": group_text,
                            "role_id": role_id,
                            "role_text": role_text,
                            "clinical": clinical,
                            "entry_year": entry_year,
                            "grad_year": grad_year
                        });

                        if (refreshDepartments) {
                            refreshAdvancedSearch();
                        }
                    }

                    function buildOrganisationPermissions(org_id, organisation_permissions) {
                        for (var i = 0; i < organisation_permissions.length; i++) {
                            displayPermission(org_id, organisation_permissions[i]);
                        }
                    }

                    function checkForSelectedDepartments(org_id) {
                        var filter_name = "";

                        $.getJSON("<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-departments"; ?>", { method: "get-organisation-filter-name", org_id: org_id }).done(function (data) {
                            filter_name = data.organisation_filter_name;

                            $.each($("." + filter_name + "_search_target_control"), function (key, input) {
                                var dept_id = $(input).val();

                                $("#department-" + dept_id).remove();
                                $(input).remove();
                            });
                        });
                    }

                    function disableInputs(value, data) {
                        $("#number").prop("disabled", value).val(data.number);
                        $("#username").prop("disabled", value).val(data.username);
                        $("#password").prop("disabled", value).val(data ? "********" : "<?php echo generate_password(8); ?>");

                        $("#account_active").prop("disabled", value).val("account_active");
                        $("#access_start_date").prop("disabled", value);
                        $("#access_start_date").parent().find("a").removeAttr("href");
                        $("#access_start_hour").prop("disabled", value);
                        $("#access_start_min").prop("disabled", value);
                        $("#access_finish").prop("disabled", value);
                        $("#access_finish_date").prop("disabled", value);
                        $("#access_finish_date").parent().find("a").removeAttr("href");
                        $("#access_finish_hour").prop("disabled", value);
                        $("#access_finish_min").prop("disabled", value);

                        $("#prefix").prop("disabled", value).val(data.prefix);
                        $("#firstname").prop("disabled", value).val(data.firstname);
                        $("#lastname").prop("disabled", value).val(data.lastname);
                        $("#gender").prop("disabled", value).val(data.gender);
                        $("#email").prop("disabled", value).val(data.email);
                        $("#email_alt").prop("disabled", value).val(data.email_alt);
                        $("#telephone").prop("disabled", value).val(data.telephone);
                        $("#fax").prop("disabled", value).val(data.fax);

                        if ($("#country").length) {
                            $("#country").prop("disabled", value).val(data.country);
                        } else if ($("#country_id").length) {
                            $("#country_id").prop("disabled", value).val(data.country_id);
                        }

                        $("#prov_state").prop("disabled", value).val(data.province_id);
                        $("#city").prop("disabled", value).val(data.city);
                        $("#address").prop("disabled", value).val(data.address);
                        $("#postcode").prop("disabled", value).val(data.postcode);
                        $("#office_hours").prop("disabled", value).val(data.office_hours);
                        $("#notes").prop("disabled", value).val(data.notes);

                        $("#organisations").prop("disabled", value);
                        $("#groups").prop("disabled", value);
                        $("#roles").prop("disabled", value);
                        $("#add_permissions").prop("disabled", value);

                        $("#departments-advanced-search").prop("disabled", value);

                        $("#send_notification_msg").hide();
                        $("#send_notification").prop("disabled", value).prop("checked", false);

                        $("#add_user").prop("disabled", value);
                    }

                    function displayPermission(org_id, permission) {
                        var permission_li = $(document.createElement("li")).addClass("list-group-item");
                        var icon = $(document.createElement("icon")).attr("data-org", org_id).attr("data-group", permission.group_id).addClass("fa fa-minus-circle fa-lg remove-permission");
                        var span = $(document.createElement("span")).addClass("group-role").html(permission.group_text + " / " + permission.role_text);

                        $(permission_li).append(icon).append("&nbsp;&nbsp;").append(span);

                        if (permission.group_text == "Faculty") {
                            var clinical_span = $(document.createElement("span")).addClass("pull-right");
                            var input = $(document.createElement("input")).attr({ id: "clinical_" + org_id, name: "clinical_" + org_id, type: "checkbox", checked: Boolean(permission.clinical) }).addClass("clinical-checkbox");
                            var clinical_label = $(document.createElement("label")).attr("for", "clinical_" + org_id).addClass("clinical-label");
                            var strong = $(document.createElement("strong")).html("Clinical");

                            $(clinical_label).append("&nbsp;").append(strong).append(" faculty member");
                            $(clinical_span).append(input).append(clinical_label);
                            $(permission_li).append(clinical_span);
                        } else if (permission.group_text == "Student") {
                            var entry_year_options = '<?php
                                                      $selected_year = (isset($PROCESSED["entry_year"])) ? $PROCESSED["entry_year"] : (date("Y", time()) - ((date("m", time()) < 7) ?  1 : 0));
                                                      for ($i = fetch_first_year(); $i >= 1995; $i--) {
                                                          $selected = $selected_year == $i;
                                                          echo build_option($i, $i, $selected);
                                                      }
                                                      ?>';
                            var grad_year_options = '<?php
                                                     for ($i = (fetch_first_year() + 1); $i >= 1995; $i--) {
                                                         $selected = (isset($PROCESSED["grad_year"]) && $PROCESSED["grad_year"] == $i);
                                                         echo build_option($i, $i, $selected);
                                                     }
                                                     ?>';

                            var select_entry_year_id = "organisation-" + org_id + "-entry-year";
                            var select_grad_year_id = "organisation-" + org_id + "-grad-year";

                            var student_years_container = $(document.createElement("div")).addClass("pull-right student-years");
                            var entry_year_label = $(document.createElement("label")).attr("for", select_entry_year_id).addClass("entry-year-label").html("Program Entry Year&nbsp;");
                            var entry_year_select = $(document.createElement("select")).attr("id", select_entry_year_id).addClass("entry-year space-right").html(entry_year_options);
                            var grad_year_label = $(document.createElement("label")).attr("for", select_grad_year_id).addClass("grad-year-label").html("Expected Graduation Year&nbsp;");
                            var grad_year_select = $(document.createElement("select")).attr("id", select_grad_year_id).addClass("grad-year").html(grad_year_options);

                            $(entry_year_select).val(permission.entry_year);
                            $(grad_year_select).val(permission.grad_year);

                            $(student_years_container).append(entry_year_label).append(entry_year_select).append(grad_year_label).append(grad_year_select);

                            $(permission_li).addClass("student-li").append(student_years_container);
                        }

                        if ($("#organisation-" + org_id + "-permissions").length) {
                            $("#organisation-" + org_id + "-permissions .permissions").append(permission_li);
                        } else {
                            var organisation_container = $(document.createElement("div")).attr("id", "organisation-" + org_id + "-permissions").addClass("panel panel-info organisation-container");
                            var organisation_label = $(document.createElement("div")).addClass("panel-heading organisation-label").html(permission.org_text);
                            var ul = $(document.createElement("ul")).addClass("list-group permissions");

                            $(ul).append(permission_li);
                            $(organisation_container).append(organisation_label).append(ul);

                            $("#added-permissions-container").append(organisation_container);
                        }
                    }

                    function filterGroups() {
                        if ($("#groups option").length > 0) {
                            var org_id = $("#organisations").val();

                            if (typeof permissions[org_id] !== "undefined") {
                                if (typeof permissions[org_id] === "string") {
                                    permissions[org_id] = JSON.parse(permissions[org_id]);
                                }

                                for (var i = 0; i < permissions[org_id].length; i++) {
                                    $("#groups").find("option[value=" + permissions[org_id][i].group_id + "]").remove();
                                }
                            }
                        }
                    }

                    function getResponse(data) {
                        if ($("#" + glob_type + "-default")) {
                            $("#" + glob_type + "-default").show();
                        }

                        if ($("#" + glob_type + "-searching")) {
                            $("#" + glob_type + "-searching").hide();
                        }

                        if (data) {
                            disableInputs(true, data);

                            $("#display-error-box").remove();
                            $("#display-notice-box").remove();

                            var notice = document.createElement("div");
                            notice.id = "display-notice";
                            notice.addClassName("display-notice");
                            notice.innerHTML = data.message;

                            $("#user-edit").prepend(notice);
                        }
                    }

                    function provStateFunction(country_id, province_id) {
                        var url_country_id = "<?php echo (isset($PROCESSED["country_id"]) ? (int) $PROCESSED["country_id"] : (defined("DEFAULT_COUNTRY_ID") ? DEFAULT_COUNTRY_ID : 0)); ?>";
                        var url_province_id = "<?php echo (isset($PROCESSED["province_id"]) ? (int) $PROCESSED["province_id"] : (defined("DEFAULT_PROVINCE_ID") ? DEFAULT_PROVINCE_ID : 0)); ?>";

                        if (country_id != undefined) {
                            url_country_id = country_id;
                        } else if ($("#country_id").val()) {
                            url_country_id = $("#country_id").val();
                        }

                        if (province_id != undefined) {
                            url_province_id = province_id;
                        } else if ($("#province_id").val()) {
                            url_province_id = $("#province_id").val();
                        }

                        var url = "<?php echo webservice_url("province"); ?>?countries_id=" + url_country_id + "&prov_state=" + url_province_id;

                        if ($("#prov_state_div")) {
                            var request = $.ajax({
                                url: url,
                                method: "GET",
                                dataType: "html"
                            });

                            request.done(function(html) {
                                $("#prov_state_div").html(html);

                                if ($("#prov_state").type == "select-one") {
                                    $("#prov_state_label").removeClass("form-nrequired");
                                    $("#prov_state_label").addClass("form-required");
                                } else {
                                    $("#prov_state_label").removeClass("form-required");
                                    $("#prov_state_label").addClass("form-nrequired");
                                }
                            });
                        }
                    }

                    function rebuildSelectedDepartments(selected_departments) {
                        $(selected_departments).each(function (key, dept_id) {
                            var department_li = $(document.createElement("li")).attr("id", "department-" + dept_id).addClass("list-group-item");
                            var icon = $(document.createElement("i")).attr("data-dept", dept_id).addClass("fa fa-minus-circle fa-lg remove-department");
                            var span = $(document.createElement("span")).addClass("department-name");
                            var organisation_title = "";
                            var department_name = "";

                            $.getJSON("<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-departments"; ?>", { method: "get-organisation-title-by-dept-id", dept_id: dept_id }).done(function (data) {
                                organisation_title = data.organisation_title;
                                department_name = data.department_name;

                                if (organisation_title) {
                                    organisation_title = " &#45; " + organisation_title;
                                }

                                $(span).html(department_name + organisation_title);
                                $(department_li).append(icon).append("&nbsp;&nbsp;").append(span);

                                $("#departments-list").append(department_li);
                                departments.push(dept_id);
                            });
                        });
                    }

                    function rebuildSelectedPermissions() {
                        for (var i = 0; i < organisation_order.length; i++) {
                            buildOrganisationPermissions(organisation_order[i], JSON.parse(permissions[organisation_order[i]]));
                        }
                    }

                    function refreshAdvancedSearch() {
                        // Remove all event handlers from the previous instantiation of the plugin
                        $("#departments-advanced-search").parent().unbind();

                        $("#advanced-search-container").html("");

                        var filters = {};

                        for (var i = 0; i < organisation_order.length; i++) {
                            if (typeof permissions[organisation_order[i]] === "string") {
                                permissions[organisation_order[i]] = JSON.parse(permissions[organisation_order[i]]);
                            }

                            var filter_name = permissions[organisation_order[i]][0].org_text.split(" ").join("_");

                            filters[filter_name] = {
                                data_source: "get-organisation-departments",
                                label: permissions[organisation_order[i]][0].org_text,
                                api_params: {
                                    organisation_id: organisation_order[i]
                                }
                            };
                        }

                        if ($.isEmptyObject(filters)) {
                            $("#departmental-affiliation-container").hide();

                            return;
                        }

                        $("#departmental-affiliation-container").show();

                        var departments_advanced_search = $(document.createElement("button")).attr({ id: "departments-advanced-search", type: "button" }).addClass("btn btn-search-filter departments-advanced-search");
                        var chevron_down = $(document.createElement("i")).addClass("icon-chevron-down pull-right btn-icon");

                        $(departments_advanced_search).append("<?php echo $translate->_("Browse Departments"); ?>").append(chevron_down);
                        $("#advanced-search-container").append(departments_advanced_search);

                        $(departments_advanced_search).advancedSearch({
                            api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-departments"; ?>",
                            async: true,
                            resource_url: ENTRADA_URL,
                            filters: filters,
                            filter_component_label: "Departments",
                            parent_form: $("#user-edit"),
                            list_selections: false,
                            width: 500
                        });
                    }

                    function removeDepartment(dept_id) {
                        for (var i = 0; i < departments.length; i++) {
                            if (departments[i] == dept_id ) {
                                departments.splice(i, 1);

                                break;
                            }
                        }
                    }

                    function removePermission(org_id, group_id) {
                        if (typeof permissions[org_id] == "string") {
                            permissions[org_id] = JSON.parse(permissions[org_id]);
                        }

                        permissions[org_id] = permissions[org_id].filter(function (permission) {
                            return permission.group_id != group_id;
                        });

                        if (!permissions[org_id].length) {
                            delete permissions[org_id];

                            organisation_order = organisation_order.filter(function (value) {
                                return value != org_id;
                            });
                        }
                    }

                    function showLoadingIcon(text) {
                        var permissions_loading = $(document.createElement("div")).attr("id", "permissions-loading").addClass("span3");
                        var p = $(document.createElement("p")).html(text);
                        var img = $(document.createElement("img")).attr("src", ENTRADA_URL + "/images/loading_small.gif");

                        $(permissions_loading).append(p).append(img);
                        $("#adding-permission-container").append(permissions_loading);
                    }

                    function sortOptions(options) {
                        options.sort(function(a, b) {
                            if (a.text > b.text) {
                                return 1;
                            } else if (a.text < b.text) {
                                return -1;
                            } else {
                                return 0;
                            }
                        });

                        return options;
                    }

                    function validateAddingPermission(org_id, group_id, role_id) {
                        var error = "";

                        if (org_id == null || org_id == 0) {
                            error += "Please select an <strong>Organisation</strong>"
                        }

                        if (group_id == null || group_id == 0) {
                            if (error) {
                                error += ", then a <strong>Group</strong>";
                            } else {
                                error += "Please select a <strong>Group</strong>";
                            }
                        }

                        if (role_id == null || role_id == 0) {
                            if (error) {
                                error += ", and then a <strong>Role</strong>.";
                            } else {
                                error += "Please select a <strong>Role</strong>.";
                            }
                        }

                        return error;
                    }
                });
            </script>
			<?php
		break;
	} //end display switch
}
