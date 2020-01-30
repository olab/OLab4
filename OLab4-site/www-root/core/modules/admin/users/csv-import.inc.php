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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
*/
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

require_once("init.inc.php");

ini_set("auto_detect_line_endings", true);

/**
 * Validates a CSV file in order to be imported.
 *
 * @param $fh A file handler with the contents of the CSV file.
 * @param $PROCESSED Processed data from the request.
 * @param $SYSTEM_GROUPS The system groups.
 */
function validate_csv($fh, $PROCESSED, $SYSTEM_GROUPS, $translate) {
    $row_count = 0;

    while (($row = fgetcsv($fh, 1000, ",")) !== FALSE) {
        if (!$row_count++) {  // Skip header
            continue;
        }
        $user = array();
        foreach ($PROCESSED["col_map"] as $index => $heading) {
            $user[$heading] = trim($row[$index]);
        }

        /*
         * @todo Why are we generating a username? We shouldn't do this unless that column wasn't specified.
         */
        if (($user["username"] == "") && ($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
            $user["username"] = trim($pieces[0]);
        }

        if (!$user["firstname"]) {
            add_error(sprintf($translate->_("Row [%d] This user does not have a first name in the CSV file."), $row_count));
        }

        if (!$user["lastname"]) {
            add_error(sprintf($translate->_("Row [%d] This user does not have a last name in the CSV file."), $row_count));
        }

        if (!$user["email"]) {
            add_error(sprintf($translate->_("Row [%d] This user does not have a email in the CSV file."), $row_count));
        } else if (!valid_address(trim($user["email"]))) {
            add_error(sprintf($translate->_("Row [%d] This user does not have an valid email in the CSV file."), $row_count));
        }

        if (!$user["organisation"]) {
            add_error(sprintf($translate->_("Row [%d] This user does not have a organisation in the CSV file."), $row_count));
        }

        if ($user["group"] && $tmp_input = clean_input($user["group"], ["nows", "lowercase"])) {
            $user["group"] = $tmp_input;
            if (array_key_exists($user["group"], $SYSTEM_GROUPS)) {
                $valid_group = true;
            } else {
                add_error(sprintf($translate->_("Row [%d] This group [%s] does not exist in your database."), $row_count, $user["group"]));
            }
        } else {
            add_error(sprintf($translate->_("Row [%d] This user does not have a group in the CSV file."), $row_count));
        }

        if ($user["role"] && $tmp_input = clean_input($user["role"], ["nows", "lowercase"])) {
            $user["role"] = $tmp_input;
            if ($valid_group) {
                if (!in_array($user["role"], $SYSTEM_GROUPS[$user["group"]])) {
                    add_error(sprintf($translate->_("Row [%d] This role [%s] does not exist within the specified group [%s]."), $row_count, $user["role"], $user["group"]));
                }
            } else {
                add_error(sprintf($translate->_("Row [%d] Unable to validate the role because of an invalid group."), $row_count));
            }
        } else {
            add_error(sprintf($translate->_("Row [%d] This user does not have a role in the CSV file."), $row_count));
        }

        /**
         * Account Status is optional. It will be validated only if it's present in the CSV.
         */
        if (isset($user["account_status"])) {
            $status = strtolower($user["account_status"]);

            if (!in_array($status, ["active", "disabled"])) {
                add_error(sprintf($translate->_("Row [%d] This user does not have a valid status (Active/Disabled)."), $row_count));
            }
        }

        /**
         * Validates for Access Start.
         */
        if (isset($user["access_start"])) {
            if (ctype_digit($user["access_start"])) {
                if ((int) $user["access_start"] <= 0) {
                    add_error(sprintf($translate->_("Row [%d] This user does not have a valid start date [%s]."), $row_count, $user["access_start"]));
                }
                // Passes!
            } else if ($user["access_start"] != "null" && ! strtotime($user["access_start"])) {
                add_error(sprintf($translate->_("Row [%d] This user does not have a valid start date [%s]."), $row_count, $user["access_start"]));
            }
        }

        /**
         * Validates for Access Finish.
         */
        if (isset($user["access_finish"])) {
            if (ctype_digit($user["access_finish"])) {
                if ((int) $user["access_finish"] <= 0) {
                    add_error(sprintf($translate->_("Row [%d] This user does not have a valid finish date [%s]."), $row_count, $user["access_finish"]));
                }
                // Passes!
            } else if ($user["access_finish"] != "null" && ! strtotime($user["access_finish"])) {
                add_error(sprintf($translate->_("Row [%d] This user does not have a valid finish date [%s]."), $row_count, $user["access_finish"]));
            }
        }

        /**
         * Validates the department field.
         */
        if (isset($user["department"])) {
            // We do this because department can be either a single integer, or several separated by commas and surrounded by quotes.
            $dep_ids = explode(",", $user["department"]);

            foreach ($dep_ids as $dep_id) {
                if (!ctype_digit($dep_id)) {
                    add_error(sprintf($translate->_("Row [%d] The value of the department field is invalid. Please make sure you're separating your department ids with commas, and that the value is surrounded by quotes."), $row_count));
                    break;
                }
            }
        }

        if (!$user["username"]) {
            add_error(sprintf($translate->_("Row [%d] The username could not be generated from the e-mail address for this user."), $row_count));
        }
    }
}

/**
 * Builds a string containing HTML for all error messages for CSV imports.
 *
 * @param $flash_messages
 * @param $translate
 * @return string The error messages.
 */
function build_message_text($flash_messages, $translate) {
    $message_text = "";

    foreach ($flash_messages as $message_type => $messages) {
        switch ($message_type) {
            case "error" :
                $message_text .= "<p>" . $translate->_("Errors") . "</p><ul>";
                foreach ($messages as $msg) {
                    $message_text .= "<li>" . $msg ."</li>";
                }
                $message_text .= "</ul></p>";
                break;
            case "success" :
                $message_text .= "<p>" . $translate->_("Success") . "</p><ul>";
                foreach ($messages as $msg) {
                    $message_text .= "<li>" . $msg ."</li>";
                }
                $message_text .= "</ul></p>";
                break;
            case "notice" :
            default :
                $message_text .= "<p>" . $translate->_("Notices") . "</p><ul>";
                foreach ($messages as $msg) {
                    $message_text .= "<li>" . $msg ."</li>";
                }
                $message_text .= "</ul></p>";
                break;
        }
    }

    return $message_text;
}

if (!defined("PARENT_INCLUDED") || !defined("IN_USERS")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "create", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br><br>If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users?".replace_query(array("section" => "add")), "title" => "Import Users");

    echo "<h1>". $translate->_("Import Users") . "</h1>";
    echo '<div id="import-tool">';
    echo "<div id=\"import-msgs\"></div>";

    $csv_headings = array(
        "institution_number"    => array("title" => "Institution Number",   "required" => false),
        "username"              => array("title" => "Username",             "required" => true),
        "password"              => array("title" => "Password",             "required" => false),
        "salt"                  => array("title" => "Salt",                 "required" => false),
        "organisation"          => array("title" => "Organisation",         "required" => true),
        "prefix"                => array("title" => "Prefix",               "required" => false),
        "firstname"             => array("title" => "First Name",           "required" => true),
        "lastname"              => array("title" => "Last Name",            "required" => true),
        "gender"                => array("title" => "Gender",               "required" => true),
        "email"                 => array("title" => "Email",                "required" => true),
        "email_alt"             => array("title" => "Alt Email",            "required" => false),
        "telephone"             => array("title" => "Telephone",            "required" => false),
        "fax"                   => array("title" => "Fax",                  "required" => false),
        "country"               => array("title" => "Country",              "required" => false),
        "province"              => array("title" => "Province",             "required" => false),
        "city"                  => array("title" => "City",                 "required" => false),
        "address"               => array("title" => "Address",              "required" => false),
        "postcode"              => array("title" => "Postal Code",          "required" => false),
        "notes"                 => array("title" => "Notes",                "required" => false),
        "group"                 => array("title" => "Group",                "required" => true),
        "role"                  => array("title" => "Role",                 "required" => true),
        "entry_year"            => array("title" => "Entry Year",           "required" => false),
        "grad_year"             => array("title" => "Grad Year",            "required" => false),
        "account_status"        => array("title" => "Account Status",       "required" => false),
        "access_start"          => array("title" => "Access Start",         "required" => false),
        "access_finish"         => array("title" => "Access Finish",        "required" => false),
        "department"            => array("title" => "Department",           "required" => false),
    );
    
    switch ($STEP) {
        case 3 :
        case "demo" :
            ob_clear_open_buffers();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: text/csv");
            header("Content-Disposition: attachment; filename=user-template.csv");
            header("Content-Transfer-Encoding: binary");
            $fp = fopen("php://output", "w");

            $row = array(
                "institution_number" => $translate->_("Institution Number"),
                "firstname" => $translate->_("Firstname"),
                "lastname" => $translate->_("Lastname"),
                "username" => $translate->_("Username"),
                "email" => $translate->_("Email"),
                "role" => $translate->_("Role"),
                "group" => $translate->_("Group"),
                "organisation" => $translate->_("Organisation"),
                "entry_year" => $translate->_("Entry Year"),
                "grad_year" => $translate->_("Grad Year"),
                "gender" => $translate->_("Gender"),
                "country" => $translate->_("Country"),
                "province" => $translate->_("Province"),
                "notes" => $translate->_("Notes"),
                "account_status" => $translate->_("Account Status"),
                "access_start" => $translate->_("Access Start"),
                "access_finish" => $translate->_("Access Finish"),
                "department" => $translate->_("Department"),
            );

            fputcsv($fp, $row);
            fputcsv($fp, array("123456789", "Kathy", "Morin", "kathymorin", "kathymorin@example.com", 2018, "student", 1, 2015, 2019, "M", "Canada", "ON", "This is a fake account used to demo how to setup the csv", "disabled", 1512086400, "2019-05-01 12:00:00", "19,7,3,20,18,1"));
            fputcsv($fp, array("456789123", "Alfred", "Vieira", "alfredvieira", "alfredvieira@example.com", 2018, "student", 1, 2015, 2019, "M", "Canada", "ON", "This is another fake account used to demo how to setup the csv", "active", "2017-12-01 12:35:00", "null", 1));

            fclose($fp);
            exit();
            break;

        case 4: // Get duplicates.
            ob_clear_open_buffers();
            if (isset($_POST["csv"]) && $tmp_input = clean_input($_POST["csv"], "alphanumeric")) {
                $PROCESSED["csv_filename"] = $tmp_input;
            }

            if (isset($_POST["mapped_headings"]) && is_array($_POST["mapped_headings"])) {
                foreach ($_POST["mapped_headings"] as $col => $heading) {
                    $PROCESSED["col_map"][(int)$col] = clean_input($heading, array("trim", "striptags"));
                }
            }

            foreach ($csv_headings as $name => $field) {
                if ($field["required"]) {
                    if (!in_array($name, $PROCESSED["col_map"])) {
                        add_error($field["title"] . " is required to be mapped to a column.");
                    }
                }
            }

            if (isset($_POST["send_notification"]) && $tmp_input = clean_input($_POST["send_notification"], ["int", "trim"])) {
                $PROCESSED["send_notification"] = $tmp_input;
                if (!isset($_POST["notification_message"]) || empty($_POST["notification_message"])) {
                    add_error("Email message is required.");
                } else {
                    $PROCESSED["notification_message"] = $_POST["notification_message"];
                }
            } else {
                $PROCESSED["send_notification"] = 0;
            }

            /**
             * Validation. Only a clean file will be processed
             */
            $fh = fopen(CACHE_DIRECTORY . "/" . $PROCESSED["csv_filename"], "r");
            validate_csv($fh, $PROCESSED, $SYSTEM_GROUPS, $translate);

            /**
             * If validation succeeds, process the entries
             */
            if (!has_error()) {
                $fh = fopen(CACHE_DIRECTORY . "/" . $PROCESSED["csv_filename"], "r");
                $row_count = 0;
                ob_start();

                $duplicate_users = [];

                while (($row = fgetcsv($fh, 1000, ",")) !== FALSE) {
                    if (!$row_count++) {  // Skip header
                        continue;
                    }
                    $user = array();
                    foreach ($PROCESSED["col_map"] as $index => $heading) {
                        $user[$heading] = trim($row[$index]);
                    }

                    $user["firstname"] = clean_input($user["firstname"], ["trim", "striptags", "ucwords"]);
                    $user["lastname"] = clean_input($user["lastname"], ["trim", "striptags", "ucwords"]);
                    $user["email"] = clean_input($user["email"], ["nows", "striptags", "lowercase"]);
                    $user["role"] = clean_input($user["role"], ["nows", "striptags", "lowercase"]);
                    $user["group"] = clean_input($user["group"], ["nows", "striptags", "lowercase"]);
                    $user["entry_year"] = (isset($user["entry_year"]) ? clean_input($user["entry_year"], ["nows", "int"]) : "");
                    $user["grad_year"] = (isset($user["grad_year"]) ? clean_input($user["grad_year"], ["nows", "int"]) : "");


                    if ((!isset($user["username"]) || !$user["username"]) && $user["email"] && ($pieces = explode("@", $user["email"])) && is_array($pieces)) {
                        $user["username"] = trim($pieces[0]);
                    }

                    if (isset($user["institution_number"]) && $user["institution_number"] && ($tmp_input = clean_input($user["institution_number"], ["striptags", "nows"]))) {
                        $user["number"] = $tmp_input;
                    } else {
                        $user["number"] = 0;
                    }

                    if (isset($user["gender"]) && ($tmp_input = clean_input($user["gender"], ["nows", "uppercase"])) && in_array($tmp_input, ["M", "F"])) {
                        $user["gender"] = (($tmp_input == "F") ? 1 : 2);
                    } else {
                        $user["gender"] = 0;
                    }

                    $user["organisation_id"] = 0;

                    if (isset($user["organisation"])) {
                        if (is_numeric($user["organisation"]) && ($tmp_input = clean_input($user["organisation"], ["nows", "int"]))) {
                            $organisation = Models_Organisation::fetchRowByID($tmp_input);
                            if ($organisation) {
                                $user["organisation_id"] = $tmp_input;
                            } else {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find the provided organisation."), $row_count), "error", $MODULE);
                                continue;
                            }
                        } else {
                            $organisation = Models_Organisation::fetchRowByOrganisationTitle(clean_input($user["organisation"], ["trim", "ucwords"]));
                            if ($organisation) {
                                $user["organisation_id"] = $organisation->getID();
                            } else {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find the provided organisation."), $row_count), "error", $MODULE);
                                continue;
                            }
                        }

                        if ($user["organisation_id"] && !$ENTRADA_ACL->amIAllowed(new UserResource(null, $user["organisation_id"]), "create")) {
                            unset($user["organisation_id"]);
                            application_log("error", "Attempt made to import a user into an organisation_id [" . $user["organisation_id"] . "] that the administrator didn't have access to.");
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] You are not able to create a user in this organisation."), $row_count), "error", $MODULE);
                            continue;
                        }
                    }

                    if (!$user["organisation_id"]) {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] This user does not have a organisation in the CSV file"), $row_count), "error", $MODULE);
                        continue;
                    }

                    if (isset($user["country"])) {
                        $country = false;
                        if (is_numeric($user["country"]) && $tmp_input = clean_input($user["country"], ["nows", "int"])) {
                            $country = Models_Country::fetchRowByID($tmp_input);
                        } elseif (is_string($user["country"]) && $tmp_input = clean_input($user["country"], ["nows", "uppercase"])) {
                            if (strlen($user["country"]) == 3) {
                                $country = Models_Country::fetchRowByAbbreviation($tmp_input);
                            } elseif (strlen($user["country"]) == 2) {
                                $country = Models_Country::fetchRowByIso2($tmp_input);
                            } else {
                                $country = Models_Country::fetchRowByCountry($user["country"]);
                            }
                        }
                        if ($country) {
                            $user["country_id"] = $country->getID();
                            if (isset($user["province"])) {
                                $province = false;
                                if (is_numeric($user["province"]) && $tmp_input = clean_input($user["province"], ["nows", "int"])) {
                                    $province = Models_Province::fetchRowByID($tmp_input);
                                } elseif (is_string($user["province"])) {
                                    if (strlen($user["province"]) == 2) {
                                        $province = Models_Province::fetchRowByAbbreviation($country->getID(), clean_input($user["province"], ["nows", "uppercase"]));
                                    } else {
                                        $province = Models_Province::fetchRowByProvinceName(clean_input($user["province"], ["nows", "ucwords"]));
                                    }
                                }
                                if ($province) {
                                    $user["province_id"] = $province->getID();
                                }
                                unset($user["province"]);
                            }
                        }
                        unset($user["country"]);
                    }

                    /**
                     * Check if the account is enabled or not, and use the appropriate boolean.
                     */
                    if (isset($user["account_status"]) && (strtolower($user["account_status"]) == "disabled")) {
                        $user["account_status"] = "false";
                    } else {
                        $user["account_status"] = "true";
                    }

                    /**
                     * Verify if the access starting date is passed. It can either be a unix timestamp or a datetime.
                     */
                    if (isset($user["access_start"]) && !is_numeric($user["access_start"]) && (strtolower($user["access_start"]) != "null")) {
                        $user["access_start"] = strtotime($user["access_start"]);
                    }

                    if (!isset($user["access_start"]) || !(int) $user["access_start"]) {
                        $user["access_start"] = time();
                    }

                    /**
                     * Verify if the access end date is passed. It can either be a unix timestamp or a datetime.
                     */
                    if (isset($user["access_finish"]) && !is_numeric($user["access_finish"]) && strtolower($user["access_finish"]) != "null") {
                        $user["access_finish"] = strtotime($user["access_finish"]);
                    }

                    if (!isset($user["access_finish"]) || !(int) $user["access_finish"]) {
                        // Defaults to never expires.
                        $user["access_finish"] = 0;
                    }

                    /**
                     * Processes the department field.
                     * @todo This should also allow strings. If it encounters a string it should search for the dep_id.
                     */
                    if (isset($user["department"])) {
                        $dep_ids = explode(",", $user["department"]);

                        $user["department"] = [];

                        // Filter out the invalid department ids.
                        foreach ($dep_ids as $dep_id) {
                            $department = Models_Department::fetchRowByID($dep_id);
                            if ($department) {
                                $user["department"][] = $dep_id;
                            } else {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find the provided department: [%d]."), $row_count, $dep_id), "error", $MODULE);
                            }
                        }
                    }

                    /**
                     * Checks if the cohort exists if this is a student.
                     */
                    $group_id = 0;
                    
                    if ($user["group"] == "student") {
                        $cohort = $translate->_("Class of ") . $user["grad_year"];

                        $group = Models_Group::fetchRowByName($cohort, $ENTRADA_USER->getActiveOrganisation());
                        if ($group) {
                            $group_id = $group["group_id"];
                        } else {
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find cohort [%s]."), $row_count, $cohort), "error", $MODULE);
                        }
                    }

                    if ($user["email"] && ($pieces = explode("@", $user["email"])) && is_array($pieces)) {
                        $user_group_role = $ENTRADA_USER->getActiveGroup() . ":" . $ENTRADA_USER->getActiveRole();
                        
                        $result_access = false;
                        $existing_user = false;
                        $method = "";

                        if ($user["number"] && $user["number"] != 0) {
                            $existing_user = Models_User::fetchRowByNumber($user["number"]);
                            $method = "number";
                        }

                        if (! $existing_user) {
                            $existing_user = Models_User::fetchRowByUsername($user["username"]);
                            $method = "username";
                        }

                        if (! $existing_user) {
                            $existing_user = Models_User::fetchRowByEmail($user["email"]);
                            $method = "email";
                        }

                        if ($existing_user) {
                            $user_data = [];

                            $user_data["proxy_id"] = $existing_user->getProxyId();
                            $user_data["old"] = $existing_user->toArray();
                            $user_data["new"] = $user;
                            $user_data["method"] = $method;

                            $user_access = Models_User_Access::fetchAllByUserIDAppID($existing_user->getProxyId(), $user['organisation']);

                            $group_roles = [];

                            if ($user_access) {
                                foreach ($user_access as $access_row) {
                                    $group_roles[] = [
                                        "group" => $access_row->getGroup(),
                                        "role" => $access_row->getRole(),
                                        "account_active" => $access_row->getAccountActive(),
                                        "access_start" => $access_row->getAccessStarts(),
                                        "access_expires" => $access_row->getAccessExpires(),
                                    ];
                                }
                                $user_data["old"]["group_roles"] = $group_roles;
                            }

                            // The fields that will be checked.
                            // The indexes are CSV fields, the values are object properties.
                            $fields = array(
                                'institution_number' => 'number',
                                'firstname' => 'firstname',
                                'lastname' => 'lastname',
                                'username' => 'username',
                                'email' => 'email',
                                'gender' => 'gender',
                                'password' => 'password',
                                'salt' => 'salt',
                                'prefix' => 'prefix',
                                'email_alt' => 'email_alt',
                                'telephone' => 'telephone',
                                'fax' => 'fax',
                                'country' => 'country_id',
                                'province' => 'province_id',
                                'city' => 'city',
                                'address' => 'address',
                                'postcode' => 'postcode',
                                'notes' => 'notes',
                                'entry_year' => 'entry_year',
                                'grad_year' => 'grad_year'
                            );

                            // CSV headings on the left, object attributes on the right.
                            $access_fields = array(
                                'role' => 'role',
                                'group' => 'group',
                                'account_status' => 'account_active',
                                'access_start' => 'access_start',
                                'access_finish' => 'access_expires',
                            );

                            // Check departments:
                            $diffs = array();

                            // Do the comparison, add fields that are different from user_data;
                            foreach ($PROCESSED['col_map'] as $header) {
                                if (isset($fields[$header])) {
                                    if ($user_data["new"][$fields[$header]] != $user_data["old"][$fields[$header]]) {
                                        $diffs[] = $fields[$header];
                                    }
                                }
                            }

                            // If the user is in a group.
                            $in_a_group = false;

                            // Compare user_acess records with the one in the csv file:
                            foreach ($group_roles as $access_record) {
                                if ($access_record['group'] == $user['group']) {
                                    $in_a_group = true;

                                    // Same Group
                                    if ($access_record['role'] != $user['role']) {
                                        // Different role, will update the role.
                                        if (! in_array('role', $diffs)) {
                                            $diffs[] = 'role';
                                        }
                                    }

                                    if (in_array('account_status', $PROCESSED['col_map']) && $access_record['account_active'] != $user['account_status']) {
                                        // Different access_start, will update it.
                                        if (! in_array('account_status', $diffs)) {
                                            $diffs[] = 'account_status';
                                        }
                                    }

                                    // Same Group, will always compare and update access_start, access_finish and account_active.
                                    if (in_array('access_start', $PROCESSED['col_map']) && $access_record['access_start'] != $user['access_start']) {
                                        // Different access_start, will update it.
                                        if (! in_array('access_start', $diffs)) {
                                            $diffs[] = 'access_start';
                                        }
                                    }

                                    if (in_array('access_finish', $PROCESSED['col_map']) && $access_record['access_expires'] != $user['access_finish']) {
                                        // Different access_start, will update it.
                                        if (! in_array('access_finish', $diffs)) {
                                            $diffs[] = 'access_finish';
                                        }
                                    }
                                }
                            }

                            if (! $in_a_group) {
                                if (! in_array('group', $diffs)) {
                                    $diffs[] = 'group';
                                }
                            }

                            if (count($diffs) > 0) {
                                $user_data['diffs'] = $diffs;
                                $duplicate_users[] = $user_data;
                            }
                        }
                    }
                }
                ob_end_clean();

                $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE, null, false);
                if ($flash_messages) {
                    // The error messages are in a flash message.
                    add_error("flash_messages");

                    echo json_encode(array(
                        "status" => "error",
                        "dataError" => $ERRORSTR
                    ));
                } else {
                    if (count($duplicate_users) > 0) {
                        echo json_encode(array(
                            "status" => "success",
                            "duplicates" => true,
                            "data" => $duplicate_users,
                            "col_map" => $PROCESSED["col_map"]
                        ));
                    } else {
                        echo json_encode(array(
                            "status" => "success",
                            "duplicates" => false
                        ));
                    }
                }
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "dataError" => $ERRORSTR
                ));
            }

            exit();
            break;
        case 2:
            ob_clear_open_buffers();
            if (isset($_POST["csv"]) && $tmp_input = clean_input($_POST["csv"], "alphanumeric")) {
                $PROCESSED["csv_filename"] = $tmp_input;
            }

            if (isset($_POST["mapped_headings"]) && is_array($_POST["mapped_headings"])) {
                foreach ($_POST["mapped_headings"] as $col => $heading) {
                    $PROCESSED["col_map"][(int)$col] = clean_input($heading, array("trim", "striptags"));
                }
            }

            foreach ($csv_headings as $name => $field) {
                if ($field["required"]) {
                    if (!in_array($name, $PROCESSED["col_map"])) {
                        add_error($field["title"] . " is required to be mapped to a column.");
                    }
                }
            }

            if (isset($_POST["send_notification"]) && $tmp_input = clean_input($_POST["send_notification"], ["int", "trim"])) {
                $PROCESSED["send_notification"] = $tmp_input;
                if (!isset($_POST["notification_message"]) || empty($_POST["notification_message"])) {
                    add_error("Email message is required.");
                } else {
                    $PROCESSED["notification_message"] = $_POST["notification_message"];
                }
            } else {
                $PROCESSED["send_notification"] = 0;
            }

            /**
             * Gets the list of duplicate records that will be replaced with the ones in the CSV file.
             */
            if (isset($_POST["replace_records"]) && $tmp_input = $_POST["replace_records"]) {
                $PROCESSED["replace_records"] = json_decode($_POST["replace_records"]);
            } else {
                $PROCESSED["replace_records"] = [];
            }
            

            /**
             * Validation. Only a clean file will be processed
             */
            $fh = fopen(CACHE_DIRECTORY . "/" . $PROCESSED["csv_filename"], "r");
            validate_csv($fh, $PROCESSED, $SYSTEM_GROUPS, $translate);

            $user_updated = false;

            /**
             * If validation succeeds, process the entries
             */
            if (!has_error()) {
                $fh = fopen(CACHE_DIRECTORY . "/" . $PROCESSED["csv_filename"], "r");
                $row_count = 0;
                ob_start();
                while (($row = fgetcsv($fh, 1000, ",")) !== FALSE) {
                    if (!$row_count++) {  // Skip header
                        continue;
                    }
                    $user = array();
                    foreach ($PROCESSED["col_map"] as $index => $heading) {
                        $user[$heading] = trim($row[$index]);
                    }

                    $user["firstname"] = clean_input($user["firstname"], ["trim", "ucwords"]);
                    $user["lastname"] = clean_input($user["lastname"], ["trim", "ucwords"]);
                    $user["email"] = clean_input($user["email"], ["nows", "lowercase"]);
                    $user["role"] = clean_input($user["role"], ["nows", "lowercase"]);
                    $user["group"] = clean_input($user["group"], ["nows", "lowercase"]);
                    $user["entry_year"] = (isset($user["entry_year"]) ? clean_input($user["entry_year"], ["nows", "int"]) : "");
                    $user["grad_year"] = (isset($user["grad_year"]) ? clean_input($user["grad_year"], ["nows", "int"]) : "");


                    if (($user["username"] == "") && ($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
                        $user["username"] = trim($pieces[0]);
                    }

                    if (isset($user["institution_number"]) && ($user["institution_number"] != "") && $tmp_input = clean_input($user["institution_number"], ["nows", "int"])) {
                        $user["number"] = $tmp_input;
                    } else {
                        $user["number"] = 0;
                    }

                    if (isset($user["gender"]) && ($tmp_input = clean_input($user["gender"], array("nows"))) && in_array($tmp_input, array("M", "F"))) {
                        $user["gender"] = (($tmp_input == "F") ? 1 : 2);
                    } else {
                        $user["gender"] = 0;
                    }

                    if (isset($user["organisation"])) {
                        if (is_numeric($user["organisation"]) && $tmp_input = clean_input($user["organisation"], ["nows", "int"])) {
                            $user["organisation_id"] = $tmp_input;
                        } else {
                            $organisation = Models_Organisation::fetchRowByOrganisationTitle(clean_input($user["organisation"], ["trim", "ucwords"]));
                            if ($organisation) {
                                $user["organisation_id"] = $organisation->getID();
                            } else {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find the provided organisation."), $row_count), "error", $MODULE);
                                continue;
                            }
                        }
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] This user does not have a organisation in the CSV file"), $row_count), "error", $MODULE);
                        continue;
                    }

                    if (isset($user["country"])) {
                        $country = false;
                        if (is_numeric($user["country"]) && $tmp_input = clean_input($user["country"], ["nows", "int"])) {
                            $country = Models_Country::fetchRowByID($tmp_input);
                        } elseif (is_string($user["country"]) && $tmp_input = clean_input($user["country"], ["nows", "uppercase"])) {
                            if (strlen($user["country"]) == 3) {
                                $country = Models_Country::fetchRowByAbbreviation($tmp_input);
                            } elseif (strlen($user["country"]) == 2) {
                                $country = Models_Country::fetchRowByIso2($tmp_input);
                            } else {
                                $country = Models_Country::fetchRowByCountry($user["country"]);
                            }
                        }
                        if ($country) {
                            $user["country_id"] = $country->getID();
                            if (isset($user["province"])) {
                                $province = false;
                                if (is_numeric($user["province"]) && $tmp_input = clean_input($user["province"], ["nows", "int"])) {
                                    $province = Models_Province::fetchRowByID($tmp_input);
                                } elseif (is_string($user["province"])) {
                                    if (strlen($user["province"]) == 2) {
                                        $province = Models_Province::fetchRowByAbbreviation($country->getID(), clean_input($user["province"], ["nows", "uppercase"]));
                                    } else {
                                        $province = Models_Province::fetchRowByProvinceName(clean_input($user["province"], ["nows", "ucwords"]));
                                    }
                                }
                                if ($province) {
                                    $user["province_id"] = $province->getID();
                                }
                                unset($user["province"]);
                            }
                        }
                        unset($user["country"]);
                    }

                    /**
                     * Check if the account is enabled or not, and use the appropriate boolean.
                     */
                    if (isset($user["account_status"]) && strtolower($user["account_status"]) == "disabled") {
                        $user["account_status"] = "false";
                    } else {
                        $user["account_status"] = "true";
                    }

                    /**
                     * Verify if the access starting date is passed. It can either be a unix timestamp or a datetime.
                     */
                    if (isset($user["access_start"])) {
                        if (! is_numeric($user["access_start"]) || ! (int) $user["access_start"] > 0) {
                            $user["access_start"] = strtotime($user["access_start"]);
                        } else if ($user["access_start"] == "null") {
                            $user["access_start"] = time();
                        }
                    } else {
                        // Defaults to now.
                        $user["access_start"] = time();
                    }

                    /**
                     * Verify if the access end date is passed. It can either be a unix timestamp or a datetime.
                     */
                    if (isset($user["access_finish"])) {
                        if (! is_numeric($user["access_finish"]) || ! (int) $user["access_finish"] > 0) {
                            $user["access_finish"] = strtotime($user["access_finish"]);
                        } else if ($user["access_finish"] == "null") {
                            $user["access_finish"] = 0;
                        }
                    } else {
                        // Defaults to never.
                        $user["access_finish"] = 0;
                    }

                    /**
                     * Processes the department field.
                     */
                    $departments = [];
                    if (isset($user["department"])) {
                        $dep_ids = explode(",", $user["department"]);

                        // Filter out the invalid department ids.
                        foreach ($dep_ids as $dep_id) {
                            $department = Models_Department::fetchRowByID($dep_id);

                            if (! $department) {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find the provided department: [%d]."), $row_count, $dep_id), "error", $MODULE);
                            } else {
                                array_push($departments, $dep_id);
                            }
                        }
                        unset($user["department"]);
                    }

                    if (($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
                        $user_group_role = $ENTRADA_USER->getActiveGroup() . ":" . $ENTRADA_USER->getActiveRole();

                        $result_access = false;
                        $existing_user = false;
                        $method = "";

                        if ($user["number"] && $user["number"] != 0) {
                            $existing_user = Models_User::fetchRowByNumber($user["number"]);
                            $method = "number";
                        }

                        if (! $existing_user) {
                            $existing_user = Models_User::fetchRowByUsername($user["username"]);
                            $method = "username";
                        }

                        if (! $existing_user) {
                            $existing_user = Models_User::fetchRowByEmail($user["email"]);
                            $method = "email";
                        }

                        if (!$existing_user) {
                            $salt = hash("sha256", (uniqid(rand(), 1) . time()));
                            if (!(isset($user["password"]) && $user["password"] != "")) {
                                $user["password_plain"] = generate_password();
                                $user["password"] = sha1($user["password_plain"] . $salt);
                            }
                            $user["salt"] = ((isset($user["salt"]) && $user["salt"] != "") ? $user["salt"] : $salt);
                            $user["updated_date"] = time();
                            $user["updated_by"] = $ENTRADA_USER->getID();

                            if ($uuid = $db->GetOne("SELECT UUID()")) {
                                $user["uuid"] = $uuid;
                            } else {
                                application_log("error", "An error was encountered while attempting to set the UUID for a newly created user. DB said: " . $db->ErrorMsg());
                            }

                            $new_user = new Models_User($user);
                            if ($new_user->insert()) {
                                $proxy_id = $new_user->getID();
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] User successfully created. [%s]:"), $row_count, $user["username"]), "success", $MODULE);
                                $user_updated = true;
                            } else {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to insert user_data record for staff / student: [%s]."), $row_count, $user["username"]), "error", $MODULE);
                                application_log("Unable to insert user_data record for staff / student: [" . $user["username"] . "]. Database said: " . $db->ErrorMsg());
                                continue;
                            }
                        } else {
                            $proxy_id = $existing_user->getProxyId();

                            // If this user was selected to be updated.
                            if (in_array($proxy_id, $PROCESSED["replace_records"])) {
                                // Updates existing fields with data from the CSV file.
                                if ($existing_user->fromArray($user)->update()) {
                                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] User successfully updated. [%s]:"), $row_count, $user["username"]), "success", $MODULE);
                                    $user_updated = true;
                                } else {
                                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to update user_data record for staff / student: [%s]."), $row_count, $user["username"]), "error", $MODULE);
                                    application_log("Unable to update user_data record for staff / student: [" . $user["username"] . "]. Database said: " . $db->ErrorMsg());
                                }
                            }

                            $result_access = Models_User_Access::fetchRowByAppIdUserIDOrganisationIDGroup(AUTH_APP_ID, $proxy_id, $user["organisation_id"], $user["group"]);
                        }

                        if (isset($proxy_id) && $proxy_id) {

                            $access = array();
                            $access["user_id"] = $proxy_id;
                            $access["app_id"] = AUTH_APP_ID;
                            $access["organisation_id"] = $user["organisation_id"];
                            $access["account_active"] = $user["account_status"];
                            $access["access_starts"] = $user["access_start"];
                            $access["access_expires"] = $user["access_finish"];
                            $access["last_login"] = 0;
                            $access["last_ip"] = "";
                            $access["role"] = $user["role"];
                            $access["group"] = $user["group"];
                            $access["extras"] = "";
                            $access["notes"] = "";

                            if ($existing_user && $result_access) {
                                // Update user_access data.
                                $updates = [];

                                // If this user was selected to be updated.
                                if (in_array($proxy_id, $PROCESSED["replace_records"])) {

                                    // Verify if the columns were mapped to csv data and if not, just ignore them.
                                    if(in_array('account_status', $PROCESSED["col_map"])) {
                                        $updates["account_active"] = $user["account_status"];
                                    }
                                    if(in_array('access_start', $PROCESSED["col_map"])) {
                                        $updates["access_starts"] = $user["access_start"];
                                    }
                                    if(in_array('access_finish', $PROCESSED["col_map"])) {
                                        $updates["access_expires"] = $user["access_finish"];
                                    }

                                    // Verify if the role needs to be updated for the current group.
                                    if ($result_access->getRole() != $user["role"]) {
                                        $updates["role"] = $user["role"];
                                    }

                                    if (count($updates) > 0) {
                                        if($result_access->fromArray($updates)->update()) {
                                            $user_updated = true;
                                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Updated permissions for user [%s]: " . implode(', ', array_keys($updates)) . ". "), $row_count, $user["username"]), "success", $MODULE);
                                        } else {
                                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Error: Couldn't update user_access record for user [%s]. "), $row_count, $user["username"]), "error", $MODULE);
                                            application_log("Unable to update user_access record for staff / student: [" . $user["username"] . "]. Database said: " . $db->ErrorMsg());
                                        }
                                    }
                                }

                            } else {
                                $access["private_hash"] = md5(hash("sha256", (uniqid(rand(), 1) . time() . $proxy_id)));
                            }
                            $user_access = new Models_User_Access($access);

                            if ($user_group_role != "medtech:admin" && $access["group"] == "medtech" && $access["role"] == "admin") {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] You don't have permission to give [%s] / [%s] privileges to this user."), $row_count, $access["group"], $access["role"]), "error", $MODULE);
                                application_log("error", "The user id [".$ENTRADA_USER->getID()."] tried to give " . $access["group"]."/".$access["role"] . " privileges to user id [".$proxy_id."]");
                                continue;
                            } else if (!$result_access && $user_access->insert()) {
                                $user_updated = true;
                                if (!$PROCESSED["send_notification"] || $existing_user) {
                                    if ($existing_user) {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Successfully added user_access record " . $user["group"] . ":" . $user["role"] . " for [%s]"), $row_count, $user["username"]), "success", $MODULE);
                                    } else {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Successfully created user_access record " . $user["group"] . ":" . $user["role"] . " for [%s]"), $row_count, $user["username"]), "success", $MODULE);
                                    }
                                } else {
                                    do {
                                        $hash = generate_hash();
                                    } while ($db->GetRow("SELECT `id` FROM `" . AUTH_DATABASE . "`.`password_reset` WHERE `hash` = " . $db->qstr($hash)));
                                    if ($db->AutoExecute("`" . AUTH_DATABASE . "`.`password_reset`", array("ip" => "127.0.0.1", "date" => time(), "user_id" => $proxy_id, "hash" => $hash, "complete" => 0), "INSERT")) {
                                        $notification_search = array("%firstname%", "%lastname%", "%username%", "%password_reset_url%", "%application_url%", "%application_name%");
                                        $notification_replace = array(stripslashes($user["firstname"]), stripslashes($user["lastname"]), stripslashes($user["username"]), PASSWORD_RESET_URL . "?hash=" . rawurlencode($proxy_id . ":" . $hash), ENTRADA_URL, APPLICATION_NAME);
                                        $message = str_ireplace($notification_search, $notification_replace, $PROCESSED["notification_message"]);
                                        $mail = new Zend_Mail();
                                        $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                                        $mail->addTo($user["email"], $user["firstname"]);
                                        $mail->setSubject(sprintf($translate->_("Welcome To %s"), APPLICATION_NAME));
                                        $mail->setBodyText($message);
                                        if ($mail->send()) {
                                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Successfully added username [%s] and sent e-mail notification to [%s]."), $row_count, $user["username"], $user["email"]), "success", $MODULE);
                                            application_log("reminder", "SUCCESS: Sent account creation (via batch import) email to " . $user["email"]);
                                        } else {
                                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Added username [%s] to the database, but could not send e-mail notification to [%s]."), $row_count, $user["username"], $user["email"]), "error", $MODULE);
                                            application_log("reminder", "FAILURE: Unable to send account creation (via batch import) email to " . $user["email"]);
                                        }
                                    } else {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Added username [%s] to the database, but could not insert password reset entry into password_reset table."), $row_count, $user["username"]), "error", $MODULE);

                                        application_log("[Row " . $row_count . "]\tAdded username [" . $user["username"] . "] to the database, but could not insert password reset entry into password_reset table. Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }

                            // Add data to group member table for cohort creation.
                            if ($user["group"] == "student") {
                                $cohort = "Class of " . $user["grad_year"];

                                $group = Models_Group::fetchRowByName($cohort, 1);
                                if ($group) {
                                    $group_id = $group["group_id"];
                                } else {
                                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to find a group (cohort) for [%s] to insert this student into."), $row_count, $cohort), "error", $MODULE);
                                    continue;
                                }
                                if ($group_id) {
                                    $query = "SELECT * FROM `group_members` WHERE `group_id`=" . $db->qstr($group_id) . " AND `proxy_id`=" . $db->qstr($proxy_id);
                                    $result = $db->GetRow($query);
                                    if ($result) {
                                        /**
                                         * Update existing group_member record.
                                         */
                                        $gmember = array(
                                            "member_active" => 1,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID()
                                        );
                                        $db->AutoExecute("group_members", $gmember, "UPDATE", "gmember_id = " . $result["gmember_id"]);
                                    } else {
                                        /**
                                         * Create new group_member record.
                                         */
                                        $gmember = array(
                                            "group_id" => $group_id,
                                            "proxy_id" => $proxy_id,
                                            "start_date" => 0,
                                            "finish_date" => 0,
                                            "member_active" => 1,
                                            "entrada_only" => 1,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID()
                                        );
                                        $db->AutoExecute("group_members", $gmember, "INSERT");
                                    }
                                }
                            }

                            // Attach existing departments to the user.
                            foreach ($departments as $dep_id) {
                                $user_dep = Models_User_Department::fetchRowByUserIdDepartmentId($proxy_id, $dep_id);

                                if (! $user_dep) {
                                    // User-Department relationship not found, let's add it.
                                    $user_department = array(
                                        "user_id" => $proxy_id,
                                        "dep_id" => $dep_id,
                                        "entrada_only" => 1, // Set to 1 because the user is being imported through the UI.
                                    );

                                    $udp = new Models_User_Department($user_department);

                                    if (! $udp->insert()) {
                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Row [%d] Unable to insert user_departments record for department number [%d]."), $row_count, $dep_id), "error", $MODULE);
                                        application_log("Unable to insert user_departments record for user id: [" . $proxy_id . "]. Department id: [" . $dep_id . "]. Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }
                        }
                        unset($proxy_id);
                    }
                }
                $template = simplexml_load_file($ENTRADA_TEMPLATE->absolute() . "/email/users-import.xml");
                $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE, null, false);
                if ($flash_messages) {
                    $message_text = build_message_text($flash_messages, $translate);

                    $mail = new Zend_Mail();
                    $mail->setFrom($AGENT_CONTACTS["administrator"]["email"], $AGENT_CONTACTS["administrator"]["name"]);
                    $mail->addTo($ENTRADA_USER->getEmail(), $ENTRADA_USER->getFirstname());
                    $mail->setSubject($template->template->subject);

                    $message_search = array("%TARGET_FIRSTNAME%", "%TARGET_LASTNAME%", "%LOG_MSGS%", "%APPLICATION_NAME%");
                    $message_replace = array($ENTRADA_USER->getFirstname(), $ENTRADA_USER->getLastname(), $message_text, APPLICATION_NAME);
                    $final_message = str_ireplace($message_search, $message_replace, $template->template->body);
                    $mail->setBodyHtml($final_message);

                    if ($mail->send()) {
                        application_log("reminder", "SUCCESS: Sent email with log " . $ENTRADA_USER->getEmail());
                    } else {
                        application_log("reminder","FAILURE: Unable to send email with log to " . $ENTRADA_USER->getEmail());
                    }
                }

                if (! $user_updated) {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("No users were updated/created because your .CSV file is up to date with the data currently in the system.")), "success", $MODULE);
                }

                ob_end_clean();
                echo json_encode(array("status" => "success"));
            } else {
                echo json_encode(array("status" => "error", "dataError" => $ERRORSTR));
            }

            exit();

        case 1 :
        default :
            $unmapped_fields = $csv_headings;
            if (isset($_FILES["csv_file"]) && $_FILES["csv_file"]["tmp_name"]) {
                switch ($_FILES["csv_file"]["error"]) {
                    case 1 :
                    case 2 :
                    case 3 :
                        add_error("The file that uploaded did not complete the upload process or was interupted. Please <a href=\"" . ENTRADA_RELATIVE . "/admin/users\">click here</a> and try your CSV again.");
                        break;
                    case 4 :
                        add_error("You did not select a file on your computer to upload. Please <a href=\"" . ENTRADA_RELATIVE . "/admin/users\">click here</a> and try your CSV import again.");
                        break;
                    case 6 :
                    case 7 :
                        add_error("Unable to store the new file on the server, please <a href=\"" . ENTRADA_RELATIVE . "/admin/users\">click here</a> and try again.");
                        break;
                    default :
                        continue;
                        break;
                }

                if (!in_array(mime_content_type($_FILES["csv_file"]["tmp_name"]), array("text/csv", "text/plain", "application/vnd.ms-excel", "text/comma-separated-values", "application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel", "application/octet-stream"))) {
                    add_error($translate->_("Invalid <strong>file type</strong> uploaded. Must be a CSV file in the proper format, please <a href=\"" . ENTRADA_RELATIVE . "/admin/users\">click here</a> and try again."));
                }
            } else {
                add_error($translate->_("You must select a CSV file to upload from your computer. Please try again."));
            }

            if (!has_error()) {
                ?>
                <style type="text/css">
                    #unmapped-fields {
                        margin-left: 0px;
                        padding-bottom: 14px;
                    }

                    ul.nostyle {
                        list-style: none;
                        margin: 0px;
                        padding: 0px;
                    }

                    .drop-target {
                        background: #D9EDF7 !important;
                        border-top: 1px dashed #C8DCE6 !important;
                        border-right: 1px dashed #C8DCE6 !important;
                        border-bottom: 1px dashed #C8DCE6 !important;
                        border-left: 1px dashed #C8DCE6 !important;
                    }

                    .draggable-title {
                        cursor: pointer;
                        margin-right: 4px;
                        margin-bottom: 5px;
                    }
                </style>
                <p><?php echo $translate->_("Please use this interface to map the users columns to the appropriate CSV columns. We will try to automatically map the headings to the correct columns via the titles in the first row, but if there are no titles this will need to be done manually."); ?></p>

                <h2><?php echo $translate->_("Field Mapping") ?></h2>
                <?php
                echo "<h4>". $translate->_("Available Fields") . "</h4>";
                if (($handle = fopen($_FILES["csv_file"]["tmp_name"], "r")) !== FALSE) {
                    $tmp_name = explode("/", $_FILES["csv_file"]["tmp_name"]);
                    $new_filename = md5(end($tmp_name));

                    copy($_FILES["csv_file"]["tmp_name"], CACHE_DIRECTORY . "/" . $new_filename);

                    // we just want the headings
                    $i = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if ($i === 0) {
                            $j = 0;
                            foreach ($data as $d) {
                                $mapped = false;
                                $title = "";
                                $key = strtolower(str_replace(" ", "_", clean_input($d, "boolops")));
                                if (isset($csv_headings[$key])) {
                                    $mapped = true;
                                    $title = $csv_headings[$key]["title"];
                                    unset($unmapped_fields[$key]);
                                }
                                if (trim($key) !== "") {
                                    $output[] = "<tr class=\"" . ($mapped === true && $csv_headings[$key]["required"] === true ? "success" : "") . "\">\n";
                                    $output[] = "<td style=\"text-align:center!important;\">" . ($mapped === true ? "<a href=\"#\" class=\"remove\"><i class=\"icon-remove-sign\"></i></a>" : "") . "</td>\n";
                                    $output[] = "<td class=\"" . ($mapped === false ? "droppable-title-container" : "") . "\">" . $title . "<input type=\"hidden\" name=\"mapped_headings[" . $j . "]\" value=\"" . $key . "\" /></td>\n";
                                    $output[] = "<td><strong>" . $d . "</strong></td>\n";
                                    $output[] = "</tr>\n";
                                }

                                $j++;
                            }
                        }
                        $output_data = array();
                        foreach ($data as $key => $field) {
                            if (($i > 0) || ($i === 0 && trim($field) !== "")) {
                                $clean_field = str_replace("'", "&#39;", $field);
                                $output_data[$key] = $clean_field;
                            }
                        }
                        $json_rows[] = $output_data;
                        $i++;
                    }

                    fclose($handle);

                    if (!empty($unmapped_fields)) {
                        echo "<div class=\"space-below row well\" id=\"unmapped-fields\">";
                        foreach ($unmapped_fields as $field_name => $field) {
                            echo "<span data-field-name=\"" . $field_name . "\" class=\"draggable-title label pull-left " . ($field["required"] === true ? "label-important" : "") . "\"><i class=\"icon-move icon-white\"></i> <span class=\"label-text\">" . $field["title"] . "</span></span>";
                        }
                        echo "</div>";
                    }

                    ?>

                    <form class="form" id="import-form"
                          action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=csv-import"
                          method="POST">
                        <input type="hidden" name="csv" value="<?php echo $new_filename; ?>"/>
                        <input type="hidden" name="step" value="4">
                        <input type="hidden" name="replace_records" value="[]" id="replace-records">

                        <table class="table table-bordered csv-map">
                            <thead>
                            <tr>
                                <th width="6%"></th>
                                <th width="47%">Mapped Field</th>
                                <th width="47%">My CSV
                                    <div class="pull-right"><label for="line" class="muted space-right"> Row </label>
                                        <input class="input-mini" type="number" id="line" min="1" name="line" value="1">
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php echo implode("", $output); ?>
                            </tbody>
                        </table>

                        <h2>User Notification</h2>
                        <label class="checkbox">
                            <input type="checkbox" id="send_notification" name="send_notification" value="1" onclick="toggle_visibility_checkbox(this, '#send_notification_msg')" />
                            <?php echo $translate->_("Send each new user that is imported a welcome e-mail with password reset instructions."); ?>
                        </label>
                        <div id="send_notification_msg" class="hide">
                            <div class="content-small"><strong>Available Variables:</strong> %firstname%, %lastname%, %username%, %password_reset_url%, %application_url%, %application_name%</div>
                            <textarea id="notification_message" name="notification_message" style="width: 98%; height: 350px"><?php echo $DEFAULT_NEW_USER_NOTIFICATION; ?></textarea>
                        </div>
                    </form>
                    <div id="loading" class="alert hide">
                        <p class="text-center"><i class="fa fa-spinner fa-spin fa-fw"></i><?php echo $translate->_("Importing users..."); ?></p>
                    </div>
                    </div>

                    <div id="duplicates-tool" hidden>
                        <p>It seems like your CSV file has some records that are already in the system. You can use this tool to select what you want to do with them. The top row shows the data currently saved and the bottom row shows what was found in the CSV file.</p>

                        <div class="row-fluid space-above space-below">
                            <label class="radio inline">
                                <input type="radio" name="overwrite-all" value="true" id="keep-all" checked="checked"> Do not update existing records.
                            </label>
                            <label class="radio inline">
                                <input type="radio" name="overwrite-all" value="false" id="keep-none"> Update all records.
                            </label>
                        </div>

                        <table class="table table-striped table-bordered table-condensed" id="duplicates-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th></th>
                                <th>Number</th>
                                <th>Firstname</th>
                                <th>Lastname</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Group:Role</th>
                            </tr>
                            </thead>
                            <tbody id="duplicates-table-body"></tbody>
                        </table>
                    </div>

                    <div class="row-fluid space-above">
                        <a href="<?php echo ENTRADA_URL; ?>/admin/users" class="btn">Back</a>
                        <input class="btn btn-primary pull-right" id="import-users-btn" type="Submit" value=""/>
                    </div>

                    <script>
                        var json_rows = <?php echo json_encode($json_rows); ?>;
                        var replace_records = [];

                        jQuery(function ($) {
                            var SITE_URL = "<?php echo ENTRADA_URL; ?>";
                            var max_row = (json_rows.length - 1);

                            $("#import-users-btn").attr({"value": "Import " + max_row + (max_row > 1 ? " users" : " user")});
                            $("#line").attr({"max": max_row + 1});


                            $("#line").on("change keyup", function (e) {
                                var val = $(this).val() - 1;
                                updateRows(json_rows[val]);
                            });

                            function updateRows(jsonData) {
                                for (var i = 0; i < jsonData.length; i++) {
                                    $(".csv-map tbody tr").eq(i).children("td").eq(2).html("<strong>" + jsonData[i] + "</strong>");
                                }
                            }

                            $(".draggable-title").draggable({
                                start: function (event, ui) {
                                    $(".droppable-title-container").addClass("drop-target");
                                },
                                stop: function (event, ui) {
                                    $(".droppable-title-container").removeClass("drop-target");
                                },
                                revert: true
                            });

                            $(".droppable-title-container").droppable({
                                drop: function (event, ui) {
                                    var drop_target = $(this);
                                    handleDrop(drop_target, event, ui);
                                }
                            });

                            $(".csv-map").on("click", "a.remove", function (e) {
                                var parent = $(this).closest("tr");
                                var draggable_title = $(document.createElement("span"));
                                var field_name = parent.children("td").eq(1).children("input[type=hidden]").val();
                                parent.children("td").eq(1).children("input[type=hidden]").remove();
                                draggable_title.addClass("label draggable-title pull-left " + (parent.hasClass("success") ? "label-important" : "")).attr("data-field-name", field_name).html("<i class=\"icon-move icon-white\"></i> <span class=\"label-text\">" + parent.children("td").eq(1).html() + "</span>").draggable({
                                    start: function (event, ui) {
                                        $(".droppable-title-container").addClass("drop-target");
                                    },
                                    stop: function (event, ui) {
                                        $(".droppable-title-container").removeClass("drop-target");
                                    },
                                    revert: true
                                });

                                $("#unmapped-fields").append(draggable_title);
                                parent.removeClass("success");
                                parent.children("td").eq(0).html("");
                                parent.children("td").eq(1).html("").addClass("droppable-title-container").droppable({
                                    drop: function (event, ui) {
                                        var drop_target = $(this);
                                        handleDrop(drop_target, event, ui);
                                    }
                                });
                                e.preventDefault();
                            });

                            function handleDrop(drop_target, event, ui) {
                                $(".droppable-title-container").removeClass("drop-target");
                                drop_target.html(ui.draggable.children(".label-text").html()).removeClass("droppable-title-container").droppable("destroy");

                                var input = $(document.createElement("input"));
                                input.attr({
                                    type: 'hidden',
                                    value: ui.draggable.data("field-name"),
                                    name: 'mapped_headings[' + drop_target.closest("tbody tr").index() + ']'
                                });

                                drop_target.append(input);

                                if (ui.draggable.hasClass("label-important")) {
                                    drop_target.closest("tr").addClass("success");
                                }

                                var remove_link = $(document.createElement("a"));
                                remove_link.addClass("remove").attr("href", "#");
                                var remove_icon = $(document.createElement("i"));
                                remove_icon.addClass("icon-remove-sign").wrap($(document.createElement("a")));
                                remove_link.append(remove_icon);
                                drop_target.closest("tr").children("td").eq(0).append(remove_link);

                                ui.draggable.remove();
                            }

                            /**
                             * Uploads CSV and handles import and validation.
                             */
                            $("#import-users-btn").on("click", function (e) {
                                e.preventDefault();

                                $("#import-msgs").empty();
                                $("#msgs").empty();

                                var form_data = $("#import-form").serialize();

                                $("#import-users-btn").attr("disabled", "disabled");
                                $("#loading").show();

                                if ($("input[name=step]").val() == 2) {
                                    // After the data is validated, well call the API again to import everything.
                                    post_import(form_data);
                                } else {
                                    // First, we'll call the API to validate everything and get the duplicates list.s
                                    $.ajax({
                                        type: "POST",
                                        url: $("#import-form").attr("action"),
                                        data: form_data,
                                        success: function (data) {
                                            var jsonResponse = JSON.parse(data);
                                            $("#import-users-btn").removeAttr("disabled");
                                            $("#loading").hide();

                                            console.log(jsonResponse);

                                            if (jsonResponse.status === "success") {
                                                // All done, the next step is saving the data.
                                                $("input[name=step]").val('2');
                                                form_data = $("#import-form").serialize();

                                                if (jsonResponse.duplicates) {
                                                    render_duplicates_table(jsonResponse.data);

                                                    $("#import-tool").fadeOut(400, function () {
                                                        $("#duplicates-tool").fadeIn();
                                                    });
                                                } else {
                                                    post_import(form_data); // Now, we call the API the to save everything.
                                                }
                                            } else if (jsonResponse.status === "error") {
                                                if (jsonResponse.dataError == "flash_messages") {
                                                    window.location = SITE_URL + "/admin/users";
                                                } else {
                                                    display_error(jsonResponse.dataError, "#import-msgs");
                                                    $("html, body").animate({scrollTop: 0}, 0);
                                                }
                                            }
                                        }
                                    });
                                }
                            });

                            /**
                             * Radio controls.
                             */
                            $('body').on('change', '.element-old', function(){
                                var radio = $(this);
                                if(radio.is(':checked')) {
                                    $('#keep-none').prop('checked', false);
                                    // Delete from the replace_records array.
                                    var index = replace_records.indexOf(radio.data('id'));
                                    replace_records.splice(index, 1);
                                    $('#replace-records').val(JSON.stringify(replace_records));
                                }
                            });

                            $('body').on('change', '.element-new', function(){
                                var radio = $(this);
                                if(radio.is(':checked')) {
                                    // Add id to the replace_records array.
                                    replace_records.push(radio.data('id'));
                                    $('#keep-all').prop('checked', false);
                                    $('#replace-records').val(JSON.stringify(replace_records));
                                }
                            });

                            $('#keep-all').on('click', function(){
                                $('.element-old').trigger('click');

                            });

                            $('#keep-none').on('click', function(){
                                $('.element-new').trigger('click');
                            });

                            /**
                             * Performs a post request to execute the import.
                             * @param form_data
                             */
                            function post_import(form_data) {
                                $.ajax({
                                    type: "POST",
                                    url: $("#import-form").attr("action"),
                                    data: form_data,
                                    success: function (data) {
                                        var jsonResponse = JSON.parse(data);

                                        $("#import-users-btn").removeAttr("disabled");
                                        $("#loading").hide();
                                        if (jsonResponse.status === "success") {
                                            window.location = SITE_URL + "/admin/users";
                                        } else if (jsonResponse.status === "error") {
                                            display_error(jsonResponse.dataError, "#import-msgs");
                                            $("html, body").animate({scrollTop: 0}, 0);
                                        }
                                    }
                                });
                            }

                            /**
                             * Parse gender into a readable format (M/F/-).
                             * @param gender
                             */
                            function parse_gender(gender) {
                                if (gender == 1) {
                                    return 'F';
                                } else if (gender == 2) {
                                    return 'M';
                                }
                                return '-';
                            }

                            /**
                             * Renders duplicates table.
                             * @param data
                             */
                            function render_duplicates_table(data) {
                                var table_body = '';

                                jQuery(data).each(function(index, element) {

                                    var group_roles = '';

                                    jQuery(element.old.group_roles).each(function (i, group_role) {
                                        group_roles += group_role.group + ':' + group_role.role + '<br>';
                                    });

                                    var new_group_roles = '';
                                    var replaced = false;

                                    jQuery(element.old.group_roles).each(function (i, group_role) {
                                        if (group_role.group == element.new.group) {
                                            new_group_roles += group_role.group + ':' + element.new.role + '<br>';
                                            replaced = true;
                                        } else {
                                            new_group_roles += group_role.group + ':' + group_role.role + '<br>';
                                        }
                                    });

                                    if (! replaced) {
                                        new_group_roles += element.new.group + ':' + element.new.role + '<br>';
                                    }

                                    var diffs = '';

                                    // Displays the list of fields being updated.
                                    // jQuery(element.diffs).each(function (i, diff_field) {
                                    //     diffs += diff_field + '<br>';
                                    // });

                                    table_body += '<tr>' +
                                        '<td rowspan="2">' + element.proxy_id + '<br>' + diffs + '</td>' +
                                        '<td><input type="radio" name="' + element.proxy_id + '" data-id="' + element.proxy_id + '" value="c" checked class="element-old"></td>' +
                                        '<td>' + element.old.number + '</td>' +
                                        '<td>' + element.old.firstname + '</td>' +
                                        '<td>' + element.old.lastname + '</td>' +
                                        '<td>' + element.old.username + '</td>' +
                                        '<td>' + element.old.email + '</td>' +
                                        '<td>' + group_roles + '</td>' +
                                        '</tr>' +

                                        '<tr>' +
                                        '<td><input type="radio" name="' + element.proxy_id + '" data-id="' + element.proxy_id + '" value="n" class="element-new"></td>' +
                                        '<td>' + element.new.number + '</td>' +
                                        '<td>' + element.new.firstname + '</td>' +
                                        '<td>' + element.new.lastname + '</td>' +
                                        '<td>' + element.new.username + '</td>' +
                                        '<td>' + element.new.email + '</td>' +
                                        '<td>' + new_group_roles + '</td>' +
                                        '</tr>';
                                });

                                $("#duplicates-table-body").empty();
                                $("#duplicates-table-body").append(table_body);
                            }
                        });
                    </script>
                    <?php
                }
            } else {
                echo display_error();
                $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/users\\'', 5000)";
            }
            break;
    }
}