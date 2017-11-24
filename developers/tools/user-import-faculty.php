<?php
/**
 * Faculty Sync
 *
 * Description:
 * This script will import faculty from the data source who do not currently exist in the entrada.user_data table.
 * More would could be done in this sync script to accommodate changes such as name and department changes; however,
 * at this time it only imports users once.
 *
 * Usage:
 * php user-import-faculty.php
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

set_time_limit(0);

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("config/settings.inc.php");

require_once("functions.inc.php");
require_once("dbconnection.inc.php");

echo "\n";

/* This is an example of an Oracle database connection as your data source.
$oracle = NewADOConnection("oci8");
$oracle->Connect("hostname", "entrada", "password", "view");
$oracle->SetFetchMode(ADODB_FETCH_ASSOC);

$query = "SELECT number, firstname, lastname, email, username, department, role, group FROM entrada_faculty_import";
$faculty = $oracle->GetAll($query);
*/

/* CSV data as your data source. */
$faculty = file_get_contents("./data/import-faculty.csv");

if ($faculty) {
    foreach ($faculty as $member) {
        $user_exists = Models_User::fetchRowByNumber((int) $member["number"]);
        if (!$user_exists) {
            $salt = hash("sha256", (uniqid(rand(), 1) . time()));
            $plain_password = generate_password(10);
            $password = sha1($plain_password . $salt);

            $uuid = $db->GetOne("SELECT UUID()");

            $gender = 0;

            $department = Models_Department::fetchRowByName($member["department"], 1);
            if ($department) {
                $department_id = $department["department_id"];
            } else {
                $record = array(
                    "organisation_id" => 1,
                    "entity_id" => 3, // Department from entrada_auth.entity_type.
                    "parent_id" => 0,
                    "department_title" => $member["department"],
                    "department_address1" => "",
                    "department_address2" => "",
                    "department_city" => DEFAULT_CITY,
                    "department_province" => "",
                    "province_id" => DEFAULT_PROVINCE_ID,
                    "department_country" => "",
                    "country_id" => DEFAULT_COUNTRY_ID,
                    "department_postcode" => "",
                    "department_telephone" => "",
                    "department_fax" => "",
                    "department_email" => "",
                    "department_url" => "",
                    "department_active" => 1,
                    "updated_date" => time(),
                    "updated_by" => 1
                );

                $department = new Models_Department($record);
                $result = $department->insert();
                if ($result) {
                    $department_id = $result->getDepartmentID();
                } else {
                    echo "\n[ERROR] Unable to locate the new department_id for " . $member["department"] . ".\n";
                    exit;
                }
            }

            if ($member["email"]) {
                $account = array(
                    "number" => (int) $member["number"],
                    "username" => $member["email"],
                    "password" => $password,
                    "salt" => $salt,
                    "organisation_id" => 0, // Legacy field, but still required.
                    "prefix" => "",
                    "firstname" => $member["firstname"],
                    "lastname" => $member["lastname"],
                    "email" => $member["email"],
                    "email_alt" => "",
                    "telephone" => "",
                    "fax" => "",
                    "address" => "",
                    "city" => "",
                    "province" => "",
                    "postcode" => "",
                    "country" => "",
                    "country_id" => DEFAULT_COUNTRY_ID,
                    "province_id" => DEFAULT_PROVINCE_ID,
                    "notes" => "",
                    "copyright" => 0,
                    "notifications" => 1,
                    "gender" => $gender,
                    "clinical" => 0,
                    "uuid" => $uuid,
                    "created_date" => time(),
                    "created_by" => 1,
                    "updated_date" => time(),
                    "updated_by" => 1,
                );

                $user_data = new Models_User($account);
                $user = $user_data->insert();
                if ($user) {
                    $user_id = $user->getID();
                    if ($user_id) {
                        $access = array(
                            "user_id" => $user_id,
                            "app_id" => 1,
                            "organisation_id" => 1, // Undergraduate Medicine
                            "account_active" => true,
                            "access_starts" => time(),
                            "access_expires" => 0,
                            "last_login" => 0,
                            "last_ip" => "",
                            "role" => strtolower($member["role"]),
                            "group" => strtolower($member["group"]),
                            "extras" => "",
                            "private_hash" => generate_hash(),
                            "notes" => ""
                        );

                        $user_access = new Models_User_Access($access);
                        if ($user_access->insert()) {
                            echo "\n[SUCCESS] Person ID: " . $member["number"] . " has an Entrada account: " . $member["email"] . " / " . $plain_password;

                            if ($department_id) {
                                $record = array(
                                    "user_id" => $user_id,
                                    "dep_id" => $department_id,
                                    "dep_title" => "",
                                    "entrada_only" => 0
                                );

                                $user_department = new Models_User_Department($record);
                                if ($user_department->insert()) {
                                    echo "\n[SUCCESS] Person ID: " . $member["number"] . " was inserted into " . $member["department"];
                                }
                            }
                        } else {
                            echo "\n[ERROR] Unable to insert Entrada user_access record for person ID: " . $member["number"];
                        }
                    } else {
                        echo "\n[ERROR] Unable to find the new Entrada proxy_id for person ID: " . $member["number"];
                    }
                } else {
                    echo "\n[ERROR] Unable to insert new user record for person ID: " . $member["number"];
                }
            } else {
                echo "\n[SKIPPED] Person ID: " . $member["number"] . " does not have an email address.";
            }
        } else {
            echo "\n[SKIPPED] Person ID: " . $member["number"] . " already exists in Entrada.";
        }
    }
} else {
    echo "\n[ERROR] No faculty records found in data source.";
}

echo "\n\n";
