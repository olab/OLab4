#!/usr/bin/php
<?php
/**
 * Entrada Tools [ http://www.entrada-project.org ]
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
 * User Import Utilitiy
 *
 * This is a script that you can use to import your users into the
 * entrada_auth.user_data table. It also gives them the correct permissions
 * in the entrada_auth.user_access table.
 *
 * Instructions:
 * 0. Backup the databases *always* before importing new users.
 *
 * 1. Run "./user-import.php -validate path/to/file.csv" to import all of
 *    the data in the rows of your CSV file.
 *
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    realpath(dirname(__FILE__) . "/includes"),
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

@ini_set("auto_detect_line_endings", 1);
@ini_set("magic_quotes_runtime", 0);
set_time_limit(0);

if ((!isset($_SERVER["argv"])) || (@count($_SERVER["argv"]) < 1)) {
	echo "<html>\n";
	echo "<head>\n";
	echo "	<title>Processing Error</title>\n";
	echo "</head>\n";
	echo "<body>\n";
	echo "This file should be run by command line only.";
	echo "</body>\n";
	echo "</html>\n";
	exit;
}

$ERROR = 0;
$ERRORSTR = array();
$NOTICE = 0;
$NOTICESTR = array();
$SUCCESS = 0;
$SUCCESSSTR = array();

require_once("autoload.php");

require_once("config.inc.php");
require_once("dbconnection.inc.php");
require_once("functions.inc.php");

ini_set("sendmail_from", $AGENT_CONTACTS["administrator"]["email"]);

$ACTION = ((isset($_SERVER["argv"][1])) ? trim($_SERVER["argv"][1]) : "-usage");
$CSV_FILE = (((isset($_SERVER["argv"][2])) && (trim($_SERVER["argv"][2]) != "")) ? trim($_SERVER["argv"][2]) : false);

$SKIP_EMAIL_NOTIFICATION = true;
$SEND_ADMIN_NOTIFICATION = false;

switch($ACTION) {
	case "-validate" :
		$handle = fopen($CSV_FILE, "r");
		if ($handle) {
			$row_count = 0;

			while (($row = fgetcsv($handle)) !== false) {
				$row_count++;
				$valid_group = false;

				/**
				 * We do not want the first row to be imported because it should
				 * be the CSV heading titles.
				 */
				if ($row_count > 1) {
					$user = array();
					$user["number"] = clean_input($row[0], array("nows", "int"));
					$user["firstname"] = clean_input($row[1], array("trim", "ucwords"));
					$user["lastname"] = clean_input($row[2], array("trim", "ucwords"));
					$user["email"] = clean_input($row[3], array("nows", "lowercase"));
					$user["role"] = clean_input($row[4], array("nows", "lowercase"));
					$user["group"] = clean_input($row[5], array("nows", "lowercase"));
					$user["organisation_id"] = isset($row[6]) ? clean_input($row[6], array("nows", "int")) : 1;
					$user["entry_year"] = isset($row[7]) ? clean_input($row[7], array("nows", "int")) : "";
					$user["grad_year"] = isset($row[8]) ? clean_input($row[8], array("nows", "int")) : "";

					if (($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
						$user["username"] = trim($pieces[0]);
					}

					if (isset($row[9]) && ($tmp_input = clean_input($row[9], array("nows"))) && in_array($tmp_input, array("M", "F"))) {
						$user["gender"] = (($tmp_input == "F") ? 1 : 2);
					} else {
						$user["gender"] = 0;
					}

					if (!$user["number"]) {
						output_notice("[Row ".$row_count."]\tThis user does not have a staff / student number in the CSV file.");
					}

					if (!$user["firstname"]) {
						output_error("[Row ".$row_count."]\tThis user does not have a firstname in the CSV file.");
					}

					if (!$user["lastname"]) {
						output_error("[Row ".$row_count."]\tThis user does not have a lastname in the CSV file.");
					}

					if (!$user["email"]) {
						output_error("[Row ".$row_count."]\tThis user does not have a lastname in the CSV file.");
					}

					if ($user["group"]) {
						if (array_key_exists($user["group"], $SYSTEM_GROUPS)) {
							$valid_group = true;
						} else {
							output_error("[Row ".$row_count."]\tThis group [".$user["group"]."] does not exist in your config.inc.php file.");
						}
					} else {
						output_error("[Row ".$row_count."]\tThis user does not have a group in the CSV file.");
					}

					if ($user["role"]) {
						if ($valid_group) {
							if (!in_array($user["role"], $SYSTEM_GROUPS[$user["group"]])) {
								output_error("[Row ".$row_count."]\tThis role [".$user["role"]."] does not exist within the specified group [".$user["group"]."] in your config.inc.php file.");
							}
						} else {
							output_notice("[Row ".$row_count."]\tUnable to validate the role because of an invalid group.");
						}
					} else {
						output_error("[Row ".$row_count."]\tThis user does not have a role in the CSV file.");
					}

					if (!$user["username"]) {
						output_error("[Row ".$row_count."]\tThe username could not be generated from the e-mail address for this user.");
					}
				}
			}

			if (!$ERROR) {
				output_notice("You do not appear to have any problems in your CSV file [".$CSV_FILE."].");
			}

			fclose($handle);
		} else {
			output_error("Unable to open the provided CSV file [".$CSV_FILE."].");
		}
	break;
	case "-emailskipimport" :
		$SKIP_EMAIL_NOTIFICATION	= true;
	case "-emailadminimport" :
		$SEND_ADMIN_NOTIFICATION	= true;
	case "-import" :
		$handle = fopen($CSV_FILE, "r");
		if ($handle) {
			$row_count = 0;

			while (($row = fgetcsv($handle)) !== false) {
				$row_count++;

				/**
				 * We do not want the first row to be imported because it should
				 * be the CSV heading titles.
				 */
				if ($row_count > 1) {
					$user = array();
					$user["number"] = clean_input($row[0], array("nows", "int"));
					$user["firstname"] = clean_input($row[1], array("trim", "ucwords"));
					$user["lastname"] = clean_input($row[2], array("trim", "ucwords"));
					$user["email"] = clean_input($row[3], array("nows", "lowercase"));
					$user["role"] = clean_input($row[4], array("nows", "lowercase"));
					$user["group"] = clean_input($row[5], array("nows", "lowercase"));
					$user["organisation_id"] = isset($row[6]) ? clean_input($row[6], array("nows", "int")) : 1;
					$user["entry_year"] = isset($row[7]) ? clean_input($row[7], array("nows", "int")) : "";
					$user["grad_year"] = isset($row[8]) ? clean_input($row[8], array("nows", "int")) : "";

					if (($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
						$user["username"] = trim($pieces[0]);
					}

					if (isset($row[9]) && ($tmp_input = clean_input($row[9], array("nows"))) && in_array($tmp_input, array("M", "F"))) {
						$user["gender"] = (($tmp_input == "F") ? 1 : 2);
					} else {
						$user["gender"] = 0;
					}

					if (($user["email"] != "") && ($pieces = explode("@", $user["email"])) && (is_array($pieces))) {
                        $salt = hash("sha256", (uniqid(rand(), 1) . time() . $user["number"]));

						$user["password_plain"]	= generate_password();
						$user["password"] = sha1($user["password_plain"].$salt);
						$user["salt"] = $salt;

						$query	= "SELECT * FROM `".AUTH_DATABASE."`.`user_data` WHERE `number` = ".$db->qstr($user["number"])." OR `username` = ".$db->qstr($user["username"]);
						$result	= $db->GetRow($query);
						if ($result) {
							output_notice("[Row ".$row_count."]\tSkipping staff / student number [".$user["number"]."] because they already exists in the database under proxy_id [".$result["id"]."].");
						} else {
							if (($db->AutoExecute(AUTH_DATABASE.".user_data", $user, "INSERT")) && ($proxy_id = $db->Insert_Id())) {
								$access						= array();
								$access["user_id"]			= $proxy_id;
								$access["app_id"]			= AUTH_APP_ID;
								$access["organisation_id"]	= $user["organisation_id"];
								$access["account_active"]	= "true";
								$access["access_starts"]	= time();
								$access["access_expires"]	= 0;
								$access["last_login"]		= 0;
								$access["last_ip"]			= "";
								$access["role"]				= $user["role"];
								$access["group"]			= $user["group"];
                                $access["private_hash"]     = md5(hash("sha256", (uniqid(rand(), 1) . time() . $proxy_id)));

								if ($db->AutoExecute(AUTH_DATABASE.".user_access", $access, "INSERT")) {
									if ($SKIP_EMAIL_NOTIFICATION) {
										output_success("[Row ".$row_count."]\tSuccessfully added username [".$user["username"]."] and skipped e-mail notification.");
									} else {
										do {
											$hash = generate_hash();
										} while($db->GetRow("SELECT `id` FROM `".AUTH_DATABASE."`.`password_reset` WHERE `hash` = ".$db->qstr($hash)));

										if ($db->AutoExecute(AUTH_DATABASE.".password_reset", array("ip" => "127.0.0.1", "date" => time(), "user_id" => $proxy_id, "hash" => $hash, "complete" => 0), "INSERT")) {
											$notification_search	= array("%firstname%", "%lastname%", "%username%", "%password_reset_url%", "%application_url%", "%application_name%");
											$notification_replace	= array(stripslashes($user["firstname"]), stripslashes($user["lastname"]), stripslashes($user["username"]), PASSWORD_RESET_URL."?hash=".rawurlencode($proxy_id.":".$hash), ENTRADA_URL, APPLICATION_NAME);

											$message = str_ireplace($notification_search, $notification_replace, $DEFAULT_NEW_USER_NOTIFICATION);

											if ($SEND_ADMIN_NOTIFICATION) {
												$user["email"] = $AGENT_CONTACTS["administrator"]["email"];
											}

											if (@mail($user["email"], "Welcome To ".APPLICATION_NAME, $message, "From: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">\nReply-To: \"".$AGENT_CONTACTS["administrator"]["name"]."\" <".$AGENT_CONTACTS["administrator"]["email"].">")) {
												output_success("[Row ".$row_count."]\tSuccessfully added username [".$user["username"]."] and sent e-mail notification to [".$user["email"]."].");
											} else {
												output_error("[Row ".$row_count."]\tAdded username [".$user["username"]."] to the database, but could not send e-mail notification to [".$user["email"]."].");
											}
										} else {
											output_error("[Row ".$row_count."]\tAdded username [".$user["username"]."] to the database, but could not insert password reset entry into password_reset table. Database said: ".$db->ErrorMsg());
										}
									}
								} else {
									output_error("[Row ".$row_count."]\tUnable to insert user_access record for proxy_id [".$proxy_id."] and application_id [".AUTH_APP_ID."]. Database said: ".$db->ErrorMsg());
								}
							} else {
								output_error("[Row ".$row_count."]\tUnable to insert user_data record for staff / student number [".$user["number"]."]. Database said: ".$db->ErrorMsg());
							}
						}
					} else {
						output_notice("[Row ".$row_count."]\tStaff / student number [".$user["number"]."] does not have a valid e-mail address.");
					}
				}
			}

			fclose($handle);
		} else {
			output_error("Unable to open the provided CSV file [".$CSV_FILE."].");
		}
	break;
	case "-usage" :
	default :
		echo "\nUsage: user-import.php [options] /path/to/import-file.csv";
		echo "\n   -usage               Brings up this help screen.";
		echo "\n   -emailadminimport    Proceeds with import to database, but e-mails notifications are sent to admin vs. user.";
		echo "\n   -emailskipimport     Proceeds with import to database, but e-mails notifications are skipped.";
		echo "\n   -validate            Goes through the import file and validates the data.";
		echo "\n   -import              Proceeds with import to database and sends e-mail.";
	break;
}
echo "\n\n";
?>