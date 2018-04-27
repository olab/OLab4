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
 * Entrada Authenticator - Server
 *
 * This server portion of the Entrada Authenticatior.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
 * Changes:
 * =============================================================================
 * 1.4.1 - July 9th, 2012
 * [+]	Added access_id variable to list of available variables.
 *
 * 1.4.0 - August 10th, 2011
 * [+]	Added enc_method variable to specify encryption method (default = low security, no requirements | blowfish = medium security, requires mCrypt | rijndael 256 = highest security, requires mcrypt).
 *
 * 1.3.0 - August 24th, 2010
 * [*]	Added ability for fallback auth methods (auth_method = "local, ldap").
 *
 * 1.2.0 - October 24th, 2008
 * [*]  Ported to PHP5 code.
 * [*]  Major changes to the structure of the code.
 * [+]  Added auth_method variable.
 * [+]  Added ability to authenticate against LDAP servers.
 *
 * 1.1.2 - July 11th, 2008
 * [-]	Removed the checkslashes function.
 * [+]	Added clean_input() function.
 * [*]	Used $db->qstr to prevent SQL injection.
 *
 * 1.1.1 - November 30th, 2004
 * [+]	Added magic_quotes detection to provided variables.
 * [*]	Moved configuration options to seperate config file.
 *
 * 1.1.0 - September 16th, 2004
 * [+]	Added organisation and department returns.
 * [*]	Updated documentation
 *
 * 1.0.0 - April 1st, 2004
 * [+]	First release of this application.
 *
 * Available Variables:
 * =============================================================================
 * $_POST["auth_app_id"]		- REQ	- int(12)
 * $_POST["auth_username"]		- REQ	- varchar(32)	- plain text.
 * $_POST["auth_password"]		- REQ	- varchar(32)	- md5 encrypted.
 * $_POST["auth_method"]		- OPT   - varchar(32)   - plain text. Options: "local" or "ldap", or both "local, ldap" for chained methods.
 *
 * $_POST["action"]				- REQ   - varchar(32)   - plain text.
 *
 * $_POST["username"]			- REQ	- varchar(32)	- plain text.
 * $_POST["password"]			- REQ	- varchar(32)	- md5 encrypted.
 * $_POST["requested_info"]		- OPT	- serialized array() of what information you would like returned.
 *
 * $_SERVER["REMOTE_ADDR"]
 * $_SERVER["HTTP_REFERER"]
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    realpath(dirname(__FILE__) . "/includes"),
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

header("Content-type: text/xml");

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("functions.inc.php");
require_once("settings.inc.php");

$db = NewADOConnection(DATABASE_TYPE);
$db->Connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);

$ERROR = 0;
$tokens_returned = 0;

$auth_method_chain = array();
$auth_action = ((isset($_POST["action"])) ? $_POST["action"] : "");

$user_data = array();
$user_access = array();
$application = array();

$auth_app_id = "";
$auth_username = "";
$auth_password = "";

$user_username = "";
$user_password = "";

$authenticated_by_method = "";

echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
echo "<authenticate xmlns:authenticate=\"".AUTH_URL."/authenticate.dtd\">\n";
echo "\t<result>\n";

/**
 * Validate the auth_method request.
 */
if (isset($_POST["auth_method"]) && ($tmp_input = clean_input($_POST["auth_method"], "trim"))) {
	$auth_method_pieces = explode(",", $tmp_input);

	if (is_array($auth_method_pieces) && !empty($auth_method_pieces)) {
		foreach ($auth_method_pieces as $auth_method_piece) {
			$auth_method_piece = clean_input($auth_method_piece, array("alphanumeric", "lowercase"));

			if ($auth_method_piece && is_array($ALLOWED_AUTH_METHODS) && isset($ALLOWED_AUTH_METHODS[$auth_method_piece]) && (bool) $ALLOWED_AUTH_METHODS[$auth_method_piece]) {
				$auth_method_chain[] = $auth_method_piece;
			} else {
				application_log("auth_error", "The provided application auth_method string [".$tmp_input."] has a bad method included [".$auth_method_piece."].");
			}
		}
	}

	if (empty($auth_method_chain)) {
		$auth_method_chain = array("local");
	}
}

/**
 * Validate the provided application credentials.
 */
if (isset($_POST["auth_app_id"]) && isset($_POST["auth_username"]) && isset($_POST["auth_password"])) {
	$auth_app_id = clean_input($_POST["auth_app_id"], array("trim", "int"));
	$auth_username = clean_input($_POST["auth_username"], "credentials");
	$auth_password = clean_input($_POST["auth_password"], "trim");

	$query = "SELECT * FROM `registered_apps` WHERE `script_id` = ".$db->qstr($auth_username)." AND `script_password` = ".$db->qstr($auth_password);
	$result = $db->GetRow($query);
	if (($result) && ($result["id"] == $auth_app_id)) {
		if ((($result["server_ip"] == "%") || ($result["server_ip"] == $_SERVER["REMOTE_ADDR"])) && (($result["server_url"] == "%") || ($result["server_url"] == $_SERVER["HTTP_REFERER"]))) {
			/**
			 * The provided application credentials are considered valid.
			 */
			$application = $result;
		} else {
			$ERROR++;

			echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
			echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

			application_log("auth_error", "The IP address [".$_SERVER["REMOTE_ADDR"]."] or the server URL [".$_SERVER["HTTP_REFERER"]."] that was provided does not match the values specified for this application ID.");
		}
	} else {
		$ERROR++;

		echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
		echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

		application_log("auth_error", "There was a problem with the application login information (i.e. auth_app_id, auth_username or auth_password) that was provided.");
	}
} else {
	$ERROR++;

	echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
	echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

	application_log("auth_error", "The application login information (i.e. auth_app_id, auth_username or auth_password) was missing from the request.");
}

if (isset($_POST["enc_method"]) && ($tmp_input = clean_input($_POST["enc_method"], "alphanumeric"))) {
	if (is_array($ALLOWED_ENCRYPTION_METHODS) && isset($ALLOWED_ENCRYPTION_METHODS[$tmp_input]) && (bool) $ALLOWED_ENCRYPTION_METHODS[$tmp_input]) {
		$encryption_method = $tmp_input;
	}
}

if (!isset($encryption_method)) {
	$encryption_method = "default";
}

/**
 * Validate the provided user credentials.
 */
if (!$ERROR) {
	if (isset($_POST["username"]) && isset($_POST["password"])) {
		$user_username = clean_input($_POST["username"], "credentials");
		$user_password = clean_input($_POST["password"], "trim");

		if (empty($user_username) || empty($user_password)) {
			$ERROR++;

			echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
			echo "\t\t<message>".encrypt("Either the username or password you have provided is empty.", $auth_password)."</message>\n";
		}
	} else {
		$ERROR++;

		echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
		echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

		application_log("auth_error", "The user login information (i.e. username or password) was missing from the request.");
	}
}


/**
 * Validate the provided user credentials against the requested source.
 */
if (!$ERROR) {
	foreach ($auth_method_chain as $order => $auth_method) {
		switch ($auth_method) {
			case "local" :
				$query	= "SELECT * FROM `user_data` WHERE `username` = ".$db->qstr($user_username)." AND ((`salt` IS NULL AND `password` = MD5(".$db->qstr($user_password).")) OR (`salt` IS NOT NULL AND `password` = SHA1(CONCAT(".$db->qstr($user_password).", `salt`))))";
				$result	= $db->GetRow($query);
				if ($result) {
                    /**
                     * Check to see if password requires some updating.
                     */
                    if (!$result["salt"]) {
                        $salt = hash("sha256", (uniqid(rand(), 1) . time() . $result["id"]));
                        $query = "UPDATE `user_data` SET `password` = ".$db->qstr(sha1($user_password.$salt)).", `salt` = ".$db->qstr($salt)." WHERE `id` = ".$db->qstr($result["id"]);
                        if ($db->Execute($query)) {
                            application_log("auth_success", "Successfully updated password salt for user [".$result["id"]."] via local auth method.");
                        } else {
                            application_log("auth_error", "Failed to update password salt for user [".$result["id"]."] via local auth method. Database said: ".$db->ErrorMsg());
                        }
                    }

					/**
					 * The provided user credentials are considered valid.
					 */
					$user_data = $result;
				}
			break;
            case "sso":
                $query	= "SELECT * FROM `user_data` WHERE `username` = ".$db->qstr($user_username)." AND `password` = ".$db->qstr($user_password);
                $result	= $db->GetRow($query);
                if ($result) {
                    /**
                     * Check to see if password requires some updating.
                     */
                    if (!$result["salt"]) {
                        $salt = hash("sha256", (uniqid(rand(), 1) . time() . $result["id"]));
                        $query = "UPDATE `user_data` SET `password` = " . $db->qstr(sha1($user_password . $salt)) . ", `salt` = " . $db->qstr($salt) . " WHERE `id` = " . $db->qstr($result["id"]);
                        if ($db->Execute($query)) {
                            application_log("auth_success", "Successfully updated password salt for user [" . $result["id"] . "] via local auth method.");
                        } else {
                            application_log("auth_error", "Failed to update password salt for user [" . $result["id"] . "] via local auth method. Database said: " . $db->ErrorMsg());
                        }
                    }

                    /**
                     * The provided user credentials are considered valid.
                     */
                    $user_data = $result;
                }
            break;
			case "ldap" :
			case "ldap3" :
				$LDAP_CONNECT_OPTIONS = array(
					array ("OPTION_NAME" => LDAP_OPT_PROTOCOL_VERSION, "OPTION_VALUE" => 3)
				);

				$ldap = NewADOConnection("ldap");
				$ldap->SetFetchMode(ADODB_FETCH_ASSOC);
				$ldap->debug = false;

				if ($ldap->Connect(LDAP_HOST, LDAP_SEARCH_DN, LDAP_SEARCH_DN_PASS, LDAP_BASE_DN)) {
					if (($result = $ldap->GetRow(LDAP_QUERYMEMBER_ATTR."=".$user_username)) && (is_array($result)) && (isset($result[LDAP_MEMBER_ATTR]))) {
						$ldap->Close();

						$user_dn = LDAP_MEMBER_ATTR."=".$result[LDAP_MEMBER_ATTR].",".LDAP_BASE_DN;

						if ($ldap->Connect(LDAP_HOST, $user_dn, $user_password, LDAP_BASE_DN)) {
							$ldap_result = $ldap->GetRow(LDAP_QUERYMEMBER_ATTR."=".$user_username);

							if ($ldap_result) {
								$ldap->Close();

								if (isset($ldap_result[LDAP_USER_QUERY_FIELD])) {
									if (LDAP_LOCAL_USER_QUERY_FIELD == "number") {
										$user_query_field_value = clean_input($ldap_result[LDAP_USER_QUERY_FIELD], "numeric");
									} else {
										$user_query_field_value = clean_input($ldap_result[LDAP_USER_QUERY_FIELD], "credentials");
									}
									if ($user_query_field_value) {
										$query = "SELECT * FROM `user_data` WHERE `".LDAP_LOCAL_USER_QUERY_FIELD."` = ".$db->qstr($user_query_field_value);
										$local_result = $db->GetRow($query);
										if ($local_result) {
                                            /**
                                             * Check to see if password requires re-hashing.
                                             */
                                            if (!$local_result["salt"]) {
                                                $salt = hash("sha256", (uniqid(rand(), 1) . time() . $local_result["id"]));

                                                /**
                                                 * If their local password is their LDAP password, continue to use it otherwise generate them a new local password.
                                                 */
                                                $query = "UPDATE `user_data` SET `password` = ".$db->qstr(sha1(($local_result["password"] != md5($user_password) ? uniqid(rand(), 1) : $user_password) . $salt)).", `salt` = ".$db->qstr($salt)." WHERE `id` = ".$db->qstr($local_result["id"]);
                                                if ($db->Execute($query)) {
                                                    application_log("auth_success", "Successfully updated password salt for user [".$local_result["id"]."] via LDAP auth method.");
                                                } else {
                                                    application_log("auth_error", "Failed to update password salt for user [".$local_result["id"]."] via LDAP auth method. Database said: ".$db->ErrorMsg());
                                                }
                                            }

											/**
											 * The provided user credentials are considered valid.
											 */
											$user_data = $local_result;
										} else {
											application_log("auth_error", "Username [".$user_username."] attempted to log into application_id [".$auth_app_id."] via LDAP, but they do not have a record in the user_data table that corresponds with the value returned. Query ran: ".$query);
										}
									} else {
										application_log("auth_error", "The user ldap user query field defined in settings [".LDAP_USER_QUERY_FIELD."] returned an empty response [".$ldap_result[LDAP_USER_QUERY_FIELD]." / ".$user_query_field_value."] for username [".$user_username."] from the LDAP server [".LDAP_HOST."]. LDAP Said: ".$ldap->ErrorMsg());
									}
								} else {
									application_log("auth_error", "The user ldap user query field defined in settings [".LDAP_USER_QUERY_FIELD."] does not exist in the result set for username [".$user_username."] returned from the LDAP server [".LDAP_HOST."]. LDAP Said: ".$ldap->ErrorMsg());
								}
							} else {
								application_log("auth_error", "The user supplied an invalid username or password according to the LDAP server [".LDAP_HOST."]. LDAP Said: ".$ldap->ErrorMsg());
							}
						} else {
							application_log("auth_error", "The user supplied an invalid username or password according to the LDAP server [".LDAP_HOST."]. LDAP Said: ".$ldap->ErrorMsg());
						}
					} else {
						application_log("auth_error", "The user supplied an username which could not be found in the LDAP server [".LDAP_HOST."].");
					}
				} else {
					$ERROR++;

					echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
					echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

					application_log("auth_error", "Unable to establish a connection to the LDAP server [".LDAP_HOST."] to authenticate username [".$user_username."].");
				}
			break;
			default :
				$ERROR++;

				echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
				echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

				application_log("auth_error", "The requested authentication method [".$auth_method."] was invalid.");
			break;
		}

		/**
		 * We have found a successful user record in the system, break the chain here, saving the successful method
		 */
		if (!empty($user_data)) {
            $authenticated_by_method = $auth_method;
			break;
		}
	}

	if (empty($user_data)) {
		/**
		 * If an error message already hasn't been thrown from above, use a generic one.
		 */
		if (!$ERROR) {
			$ERROR++;

			echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
			echo "\t\t<message>".encrypt("The username or password you have provided is incorrect.", $auth_password)."</message>\n";
		}
	}
}

/**
 * Validate the users' application level access.
 */
if (!$ERROR) {
	$query	= " SELECT * FROM `user_access` 
                WHERE `user_id` = ".$db->qstr($user_data["id"])." 
                AND `app_id` = ".$db->qstr($application["id"]) . " 
                AND `account_active` = 'true' 
                AND (`access_starts` = '0' OR `access_starts` < ". $db->qstr(time()) .") 
                AND (`access_expires` = '0' OR `access_expires` > ". $db->qstr(time()) .")";
    
	$result	= $db->GetRow($query);
	if ($result) {
        /**
         * The users application access is considered valid.
         */
        $user_access = $result;
	} else {
		$ERROR++;

		echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
		echo "\t\t<message>".encrypt("Your account is not currently set up for access to this application. Please contact a system administrator if you require further assistance.", $auth_password)."</message>\n";

		application_log("auth_notice", "Username [".$user_username."] attempted to log into application_id [".$auth_app_id."], and their account has not yet been provisioned.");
	}
}

/**
 * Proceed with the requested system action.
 */
if (!$ERROR) {
	switch ($auth_action) {
		case "Authenticate" :
			/**
			 * Output a successfully authenticated message.
			 */
			echo "\t\t<status>".encrypt("success", $auth_password)."</status>\n";
			echo "\t\t<message>".encrypt("You were successfully authenticated into this application.", $auth_password)."</message>\n";
            echo "\t\t<method>".encrypt($authenticated_by_method, $auth_password)."</method>\n";

			if ((isset($_POST["requested_info"])) && ($REQUESTED_INFO = @unserialize(base64_decode($_POST["requested_info"]))) && (is_array($REQUESTED_INFO)) && (count($REQUESTED_INFO) > 0)) {
				$APPLICATION_SPECIFIC	= unserialize(base64_decode($user_access["extras"]));
				$tokens_returned		= count($REQUESTED_INFO);

				foreach($REQUESTED_INFO as $value) {
					$type = explode("-", $value);
					switch ($type[0]) {
						case "id" :
							echo "\t\t<".$value.">".encrypt($user_data["id"], $auth_password)."</".$value.">\n";
						break;
						case "number" :
							echo "\t\t<".$value.">".encrypt($user_data["number"], $auth_password)."</".$value.">\n";
						break;
						case "prefix" :
							echo "\t\t<".$value.">".encrypt($user_data["prefix"], $auth_password)."</".$value.">\n";
						break;
						case "firstname" :
							echo "\t\t<".$value.">".encrypt($user_data["firstname"], $auth_password)."</".$value.">\n";
						break;
						case "lastname" :
							echo "\t\t<".$value.">".encrypt($user_data["lastname"], $auth_password)."</".$value.">\n";
						break;
						case "organisation_id":
							echo "\t\t<".$value.">".encrypt($user_data["organisation_id"], $auth_password)."</".$value.">\n";
						break;
						case "acl" :
							// @todo: insert ACL generation code here.
							echo "\t\t<".$value.">".encrypt($user_data["organisation_id"], $auth_password)."</".$value.">\n";
						break;
						case "department" :
							echo "\t\t<".$value.">".encrypt($user_data["department"], $auth_password)."</".$value.">\n";
						break;
						case "email" :
							echo "\t\t<".$value.">".encrypt($user_data["email"], $auth_password)."</".$value.">\n";
						break;
						case "email_alt" :
							echo "\t\t<".$value.">".encrypt($user_data["email_alt"], $auth_password)."</".$value.">\n";
						break;
						case "email_updated" :
							echo "\t\t<".$value.">".encrypt($user_data["email_updated"], $auth_password)."</".$value.">\n";
						break;
						case "google_id" :
							echo "\t\t<".$value.">".encrypt($user_data["google_id"], $auth_password)."</".$value.">\n";
						break;
						case "telephone" :
							echo "\t\t<".$value.">".encrypt($user_data["telephone"], $auth_password)."</".$value.">\n";
						break;
						case "fax" :
							echo "\t\t<".$value.">".encrypt($user_data["fax"], $auth_password)."</".$value.">\n";
						break;
						case "address" :
							echo "\t\t<".$value.">".encrypt($user_data["address"], $auth_password)."</".$value.">\n";
						break;
						case "city" :
							echo "\t\t<".$value.">".encrypt($user_data["city"], $auth_password)."</".$value.">\n";
						break;
						case "province" :
							echo "\t\t<".$value.">".encrypt($user_data["province"], $auth_password)."</".$value.">\n";
						break;
						case "postcode" :
							echo "\t\t<".$value.">".encrypt($user_data["postcode"], $auth_password)."</".$value.">\n";
						break;
						case "country" :
							echo "\t\t<".$value.">".encrypt($user_data["country"], $auth_password)."</".$value.">\n";
						break;
						case "privacy_level" :
							echo "\t\t<".$value.">".encrypt($user_data["privacy_level"], $auth_password)."</".$value.">\n";
						break;
						case "copyright" :
							echo "\t\t<".$value.">".encrypt($user_data["copyright"], $auth_password)."</".$value.">\n";
						break;
						case "notifications" :
							echo "\t\t<".$value.">".encrypt($user_data["notifications"], $auth_password)."</".$value.">\n";
						break;
						case "access_id" :
							echo "\t\t<".$value.">".encrypt($user_access["id"], $auth_password)."</".$value.">\n";
						break;
						case "access_starts" :
							echo "\t\t<".$value.">".encrypt($user_access["access_starts"], $auth_password)."</".$value.">\n";
						break;
						case "access_expires" :
							echo "\t\t<".$value.">".encrypt($user_access["access_expires"], $auth_password)."</".$value.">\n";
						break;
						case "last_login" :
							echo "\t\t<".$value.">".encrypt($user_access["last_login"], $auth_password)."</".$value.">\n";
						break;
						case "last_ip" :
							echo "\t\t<".$value.">".encrypt($user_access["last_ip"], $auth_password)."</".$value.">\n";
						break;
						case "role" :
							echo "\t\t<".$value.">".encrypt($user_access["role"], $auth_password)."</".$value.">\n";
						break;
						case "group" :
							echo "\t\t<".$value.">".encrypt($user_access["group"], $auth_password)."</".$value.">\n";
						break;
						case "grad_year" :
							echo "\t\t<".$value.">".encrypt($user_data["grad_year"], $auth_password)."</".$value.">\n";
						break;
						case "private_hash" :
							echo "\t\t<".$value.">".encrypt($user_access["private_hash"], $auth_password)."</".$value.">\n";
						break;
						case "private" :
							if ($type[1]) {
								echo "\t\t<private-".$type[1].">".(($APPLICATION_SPECIFIC[$type[1]]) ? encrypt($APPLICATION_SPECIFIC[$type[1]], $auth_password) : "")."</private-".$type[1].">\n";
							}
						break;
						case "pin" :
							echo "\t\t<".$value.">".encrypt($user_data["pin"], $auth_password)."</".$value.">\n";
							break;
						default :
							continue;
						break;
					}
				}

				application_log("auth_success", "Username [".$user_username."] was successfully authenticated into application_id [".$auth_app_id."]. ".$tokens_returned." token".(($tokens_returned != 1) ? "s were" : "was")." returned.");
			}
		break;
		case "updateLastLogin" :
			if ((isset($_POST["last_login"])) && ($_POST["last_login"] != "")) {
				$LAST_LOGIN = (int) $_POST["last_login"];
			} else {
				$LAST_LOGIN = time();
			}

			if ((isset($_POST["last_ip"])) && ($_POST["last_ip"] != "") && (preg_match("^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}^", $_POST["last_ip"]))) {
				$LAST_IP	= $_POST["last_ip"];
			} else {
				$LAST_IP	= 0;
			}

			if (!@$db->Execute("UPDATE `user_access` SET `last_login` = ".$db->qstr($LAST_LOGIN).", `last_ip` = ".$db->qstr($LAST_IP)." WHERE `user_id` = ".$db->qstr($user_data["id"])." AND `app_id` = ".$db->qstr($application["id"]))) {
				application_log("auth_error", "Unabled to update the user_access table for the last login information action. Database said: ".$db->ErrorMsg());
			}
		break;
		default :
			$ERROR++;

			echo "\t\t<status>".encrypt("failed", $auth_password)."</status>\n";
			echo "\t\t<message>".encrypt("A problem occurred during the authentication process and we were unable to complete the request. A system administrator has been notified of the error, please try again later.", $auth_password)."</message>\n";

			application_log("auth_error", "An unrecognized authentication action [".$auth_action."] was used against the authentication system.");
		break;
	}
}

echo "\t</result>\n";
echo "</authenticate>\n";
