<?php
/**
 * Student Sync
 *
 * Description:
 * This script will import students from the data source who do not currently exist in the entrada.user_data table.
 * More would could be done in this sync script to accommodate changes such as grad year, name changes, etc; however,
 * at this time it only imports users once.
 *
 * Usage:
 * php user-import-students.php
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

/* This is an example of an Oracle database connection as your data source.
$oracle = NewADOConnection("oci8");
$oracle->Connect("hostname", "entrada", "password", "view");
$oracle->SetFetchMode(ADODB_FETCH_ASSOC);

// Oracle database query for students.
$query = "SELECT id, firstname, lastname, email, username, entry_year, grad_year, gender, number FROM entrada_student_import";
$students = $oracle->GetAll($query);
*/

/* CSV data as your data source. */
$students = file_get_contents("./data/import-students.csv");

if ($students) {
    foreach ($students as $student) {
        $user_exists = Models_User::fetchRowByNumber((int) $student["id"]);
        if (!$user_exists) {
            $number = (trim($student["number"]) ? trim($student["number"]) : NULL);

            $salt = hash("sha256", (uniqid(rand(), 1) . time()));
            $plain_password = generate_password(10);
            $password = sha1($plain_password . $salt);

            $uuid = $db->GetOne("SELECT UUID()");

            if ($student["gender"] == "F") {
                $gender = 2;
            } else if ($student["gender"] == "M") {
                $gender = 1;
            } else {
                $gender = 0;
            }

            $cohort = "Class of " . $student["grad_year"];

            $group = Models_Group::fetchRowByName($cohort, 1);
            if ($group) {
                $group_id = $group["group_id"];
            } else {
                echo "\n[ERROR] Unable to find a group (cohort) for [" . $cohort . "] to insert this student into.\n";
                exit;
            }

            $account = array(
                "number" => (int) $student["id"],
                "number" => $number,
                "username" => $student["email"],
                "password" => $password,
                "salt" => $salt,
                "organisation_id" => 0, // Legacy field, but still required.
                "prefix" => "",
                "firstname" => $student["firstname"],
                "lastname" => $student["lastname"],
                "email" => $student["email"],
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
                "entry_year" => $student["entry_year"],
                "grad_year" => $student["grad_year"],
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
                        "role" => $student["grad_year"],
                        "group" => "student",
                        "extras" => "",
                        "private_hash" => generate_hash(),
                        "notes" => ""
                    );

                    $user_access = new Models_User_Access($access);
                    if ($user_access->insert()) {
                        echo "\n[SUCCESS] Student ID: " . $student["id"] . " has an Entrada account: " . $student["email"] . " / " . $plain_password;

                        if ($group_id) {
                            $member = array(
                                "group_id" => $group_id,
                                "proxy_id" => $user_id,
                                "start_date" => 0,
                                "finish_date" => 0,
                                "member_active" => 1,
                                "entrada_only" => 0,
                                "created_date" => time(),
                                "created_by" => 1,
                                "updated_date" => time(),
                                "updated_by" => 1
                            );

                            $group_member = new Models_Group_Member($member);
                            if ($group_member->insert()) {
                                echo "\n[SUCCESS] Student ID: " . $student["id"] . " was inserted into " . $cohort;
                            }
                        }
                    } else {
                        echo "\n[ERROR] Unable to insert Entrada user_access record for student ID: " . $student["id"];
                    }
                } else {
                    echo "\n[ERROR] Unable to find the new Entrada proxy_id for student ID: " . $student["id"];
                }
            } else {
                echo "\n[ERROR] Unable to insert new user record for student ID: " . $student["id"];
            }
        } else {
            echo "\n[SKIPPED] Student ID: " . $student["id"] . " already exists in Entrada.";
        }
    }
} else {
    echo "\n[ERROR] No students records found in data source.";
}

echo "\n\n";
