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
 * Serves as the main Entrada "public" request controller file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

$PROCEED_TO = ((isset($_GET["url"])) ? clean_input($_GET["url"], "trim") : (isset($_SERVER["REQUEST_URI"]) ? clean_input($_SERVER["REQUEST_URI"], "trim") : false));

$PATH_INFO = ((isset($_SERVER["PATH_INFO"])) ? clean_input($_SERVER["PATH_INFO"], array("url", "lowercase")) : "");
$PATH_SEPARATED = explode("/", $PATH_INFO);

$localPOST = $_POST;

$parms = array(
  'postSize' => sizeof($localPOST),
  'requestSize' => sizeof($_REQUEST),
  'ssobypass' => (empty($localPOST["ssobypass"]) ? null : $localPOST["ssobypass"]),
  'username' => (empty($localPOST["username"]) ? null : $localPOST["username"]),
  'password' => (empty($localPOST["password"]) ? null : $localPOST["password"]),
  'isAuthorized' => (empty($_SESSION["isAuthorized"]) ? null : $_SESSION["isAuthorized"]),
  'pathInfo' => $PATH_INFO,
  'module' => (empty($PATH_SEPARATED[1]) ? null : $PATH_SEPARATED[1]),
  'proceedTo' => $PROCEED_TO
);

/**/
if ( empty( $_SESSION['isAuthorized'] ) && 
     ( !empty($PATH_SEPARATED[1]) && $PATH_SEPARATED[1] === "olab" ) &&
     ( !empty($PATH_SEPARATED[2]) && $PATH_SEPARATED[2] === "play" ) ) {
  $localPOST = array();
  $localPOST['action'] = 'login';
  $localPOST['ssobypass'] = '1';
  $localPOST['username'] = empty($config->service_account->username) ? "" : $config->service_account->username; // 'service';
  $localPOST['password'] = empty($config->service_account->password) ? "" : $config->service_account->password; // 'account001';
  $ACTION = "login";
}
/**/

$parms = array(
  'postSize' => sizeof($localPOST),
  'requestSize' => sizeof($_REQUEST),
  'ssobypass' => (empty($localPOST["ssobypass"]) ? null : $localPOST["ssobypass"]),
  'username' => (empty($localPOST["username"]) ? null : $localPOST["username"]),
  'password' => (empty($localPOST["password"]) ? null : $localPOST["password"]),
  'isAuthorized' => (empty($_SESSION["isAuthorized"]) ? null : $_SESSION["isAuthorized"]),
  'pathInfo' => $PATH_INFO,
  'module' => (empty($PATH_SEPARATED[1]) ? null : $PATH_SEPARATED[1]),
  'proceedTo' => $PROCEED_TO,
  'action' => $ACTION
);

/**
 * If we are here because of a submit on the login form, the ssobypass POST variable will be set
 */
$sso_bypass = (!empty($localPOST["ssobypass"]) ? true : false);
$sso = false;

/**
 * if SSO is enabled, get it initialized
 */
if (defined("AUTH_SSO_ENABLED") && (bool) AUTH_SSO_ENABLED && defined("AUTH_SSO_TYPE")) {
    $sso = Entrada_Sso_Sso::getInstance(AUTH_SSO_TYPE);
    if (!$sso) {
        add_error("Unable to initialize SSO type: ".AUTH_SSO_TYPE."<br /><br />Please contact a system administrator for further information.");
        $SSO_ERROR = true;
    }
}

/**
 * If we don't have a valid session yet and SSO is enabled, see if there are SSO tokens present
 */
if ($sso && !$sso_bypass) {
    if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
        /**
         * If the SSO tokens are present, extract the details and try to map to a user in the Entrada database.
         * Once we have that mapping, the normal login process is followed to authorize the user and set up the session
         */
        if ($sso->isSsoAuthenticated()) {
            $result = $sso->validateUser();
            if ($result) {
                $SSO_AUTHENTICATED = true;
                $username = $result["username"];
                $password = $result["password"];
                $USER_ACCESS_ID = $result["access_id"];
            } else {
                add_error("Your login credentials are not recognized.<br /><br />Please contact a system administrator for further information.");
                $SSO_ERROR = true;
            }
            $ACTION = "login";
        }
    }
}

if ($ACTION == "login") {
    /**
     * Only check for locked out users if they are not using SSO
     */
	if (!$SSO_AUTHENTICATED) {
		$username = clean_input($localPOST["username"], "credentials");
		$password = clean_input($localPOST["password"], "trim");

		// Check for locked-out-edness before doing anything else
		$lockout_query = "SELECT a.`id`, a.`login_attempts`, a.`locked_out_until`
							FROM `".AUTH_DATABASE."`.`user_access` as a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` as b
							ON b.`id` = a.`user_id`
							WHERE b.`username` = ".$db->qstr($username)."
							AND a.`app_id` = ".$db->qstr(AUTH_APP_ID);
		$lockout_result = $db->GetRow($lockout_query);
		if ($lockout_result) {
			$USER_ACCESS_ID = $lockout_result["id"];
			$LOGIN_ATTEMPTS = (isset($lockout_result["login_attempts"]) ? $lockout_result["login_attempts"] : 0);
			if (isset($lockout_result["locked_out_until"])) {
				// User has been locked out, is it still valid?
				if ($lockout_result["locked_out_until"] < time()) {
					// User's lockout has expired, remove it
					if (!$db->Execute("UPDATE `".AUTH_DATABASE."`.`user_access` SET `locked_out_until` = NULL, `login_attempts` = NULL WHERE `id` = ".$lockout_result["id"])) {
						application_log("error", "The system was unable to reset the lockout time for user [".$username."] after it expired.");
					}
				} else {
					add_error("Your access to this system has been locked due to too many failed login attempts. You may try again at " . date("g:iA ", $lockout_result["locked_out_until"]));

					application_log("error", "User[".$username."] tried to access account after being locked out.");
				}
			}
		}

		// Check for SESSION lockout also
		if (isset($_SESSION["auth"]["locked_out_until"])) {
			if ($_SESSION["auth"]["locked_out_until"] < time()) {
				unset($_SESSION["auth"]["locked_out_until"]);
			} else {
				add_error("Your access to this system has been locked due to too many failed login attempts. You may try again at " . date("g:iA ", $_SESSION["auth"]["locked_out_until"]));

				application_log("error", "User[".$username."] tried to access account after being SESSION locked out.");
			}
		}

		if (isset($_SESSION["auth"]["login_attempts"]) && ($_SESSION["auth"]["login_attempts"] > $LOGIN_ATTEMPTS)) {
			$LOGIN_ATTEMPTS = $_SESSION["auth"]["login_attempts"];
		}
	}

    /**
     * If there is no lockout error, validate the username and password. If successful, set up the session
     */
    if ($ERROR === 0) {
        $auth = new Entrada_Auth(AUTH_PRODUCTION);
        $auth->setAppAuth(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);

        $method = ($SSO_AUTHENTICATED) ? "sso" : AUTH_METHOD;

        $auth_result = $auth->authenticate($username, $password, $method);
        if (!empty($auth_result["response"]["status"]) && $auth_result["response"]["status"] == "success" && !empty($auth_result["response"]["token"])) {
            $login_result = Entrada_Auth::login($auth_result["response"]["token"]);
        }
    }

    if (($ERROR === 0) && !empty($login_result["status"]) && ($login_result["status"] == "success")) {
        /**
         * Reset the login attempts counter on successful login
         */
		if (!empty($USER_ACCESS_ID) || !empty($login_result["access_id"])) {
			if (!$db->Execute("UPDATE `".AUTH_DATABASE."`.`user_access` SET `login_attempts` = NULL, `last_login` = ".$db->qstr(time()).", `last_ip` = ".$db->qstr((isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : 0))." WHERE `id` = ".(!empty($USER_ACCESS_ID) ? (int) $USER_ACCESS_ID : (int) $login_result["access_id"])." AND `app_id` = ".$db->qstr(AUTH_APP_ID))) {
				application_log("error", "Unable to reset the login attempt counter for user [".$username."]. Database said ".$db->ErrorMsg());
			}
		}

		/**
		 * Test for Copyright compliance and if Copyright notice has been updated
		 */
		$COPYRIGHT = false;
		if ((array_count_values($copyright_settings = (array) $translate->_("copyright")) > 1) && strlen($copyright_settings["copyright-version"])) {
			if (!(($lastupdated = strtotime($copyright_settings["copyright-version"])) === false)) {
				if ((!(int) $_SESSION["details"]["copyright"]) || ($lastupdated > (int) $_SESSION["details"]["copyright"])) {
					$COPYRIGHT = true;
				}
			}
		}

		if ((!(int)$_SESSION["details"]["privacy_level"] && $PATH_SEPARATED[1] != "secure") || $COPYRIGHT ||  (((bool) $GOOGLE_APPS["active"]) && (in_array($_SESSION["details"]["group"], $GOOGLE_APPS["groups"])) && (!$_SESSION["details"]["google_id"]))) {
			/**
			 * They need to be re-directed to the firstlogin module.
			 */
			$PATH_SEPARATED[1] = "firstlogin";
			$MODULE = "firstlogin";

			if (((bool) $GOOGLE_APPS["active"]) && (in_array($_SESSION["details"]["group"], $GOOGLE_APPS["groups"])) && (!$_SESSION["details"]["google_id"])) {
				$_SESSION["details"]["google_id"] = "opt-in";
			}
		} elseif ($PROCEED_TO) {
			header("Location: ".((isset($_SERVER["HTTPS"])) ? "https" : "http")."://".$_SERVER["HTTP_HOST"].clean_input(rawurldecode($PROCEED_TO), array("nows", "url")));
			exit;
		}
	} elseif (($ERROR === 0) && (isset($LOGIN_ATTEMPTS)) && ($LOGIN_ATTEMPTS >= AUTH_MAX_LOGIN_ATTEMPTS)) {
		$locked_out_until = time() + AUTH_LOCKOUT_TIMEOUT;

		if (isset($USER_ACCESS_ID)) {
			// Lock this user out
			if (!$db->Execute("UPDATE `".AUTH_DATABASE."`.`user_access` SET `locked_out_until` = ?, `login_attempts` = NULL  WHERE `id` = ?", array($locked_out_until, $USER_ACCESS_ID))) {
				application_log("error", "Unable to set `locked_out_until` for user [".$username."]. Database said ".$db->ErrorMsg());
			}
		} else {
			if (isset($_SESSION["auth"]["login_attempts"])) {
				$_SESSION["auth"]["login_attempts"] = 0;
				$_SESSION["auth"]["locked_out_until"] = $locked_out_until;
			}
		}

		add_error("Your access to this system has been locked due to too many failed login attempts. You may try again at " . date("g:iA ", $locked_out_until));
	} else {
		/**
		 * There can only be auth errors if not already locked out, so only fandangle this stuff
		 * if no errors have been encountered before trying to authenticate.
		 */
		if ($ERROR === 0) {
			$remaining_attempts = (AUTH_MAX_LOGIN_ATTEMPTS - (isset($LOGIN_ATTEMPTS) && ((int)$LOGIN_ATTEMPTS) ? $LOGIN_ATTEMPTS : 0));

			$error_message = $auth_result["response"]["message"];

			if ($remaining_attempts > 1 && $remaining_attempts <= (AUTH_MAX_LOGIN_ATTEMPTS - 1)) {
				$error_message .= "<br /><br />You have <strong>".$remaining_attempts." attempts</strong> remaining before your account is locked for ".round((AUTH_LOCKOUT_TIMEOUT / 60))." minutes.";
			} elseif ($remaining_attempts == 1) {
				$error_message .= "<br /><br />This is your <strong>last login attempt</strong> before your account is locked for ".round((AUTH_LOCKOUT_TIMEOUT / 60))." minutes.";
			}

			add_error($error_message);

			application_log("access", $error_message);

			if (isset($USER_ACCESS_ID)) {
				if (!$db->Execute("UPDATE `".AUTH_DATABASE."`.`user_access` SET `login_attempts` = ? WHERE `id`= ?", array(($LOGIN_ATTEMPTS + 1), $USER_ACCESS_ID))) {
					application_log("error", "Unable to increment the login attempt counter for user [".$username."]. Database said ".$db->ErrorMsg());
				}
			} else {
				if (isset($_SESSION["auth"]["login_attempts"])) {
					$_SESSION["auth"]["login_attempts"]++;
				} else {
					$_SESSION["auth"]["login_attempts"] = 1;
				}
			}
		}
	}

	unset($auth_result, $login_result, $username, $password);
} elseif ($ACTION == "logout") {
	add_statistic("index", "logout", "access_id", $_SESSION["details"]["access_id"], $_SESSION["details"]["id"]);

	users_online("logout");

    /**
     * If the user is in masquerade mode, save the session data from before
     * the masquerade began before it is destroyed with the rest of the session
     */
    if (isset($_SESSION["previous_session"]) && $_SESSION["previous_session"]) {
        $previous_session = $_SESSION["previous_session"];
    }

    /**
     * We are logging out so remove this user from the cache if present
     */
    if (isset($ENTRADA_CACHE) && $ENTRADA_CACHE && isset($ENTRADA_USER) && $ENTRADA_USER) {
        $ENTRADA_CACHE->remove("user_".AUTH_APP_ID."_".$ENTRADA_USER->getID());
    }

    $_SESSION = array();
    unset($_SESSION);
    session_destroy();

    /**
     * If logging out of the masquerade, log back in as admin and redirect
     */
    if (isset($previous_session) && $previous_session) {
        session_start();
        $_SESSION = $previous_session;

        header("Location: ".ENTRADA_URL."/admin/users");
        exit;
    }

    /**
     * SSO Logout delayed until the very end, in case you are using masquerade, in which case the session is re-established
     * for the original user (which is the one that SSO may have authenticated in the first place)
     */
    if ($sso) {
        $sso->logout();
    }

    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"] && isset($ENTRADA_USER)) {
	if (($cached_user = $ENTRADA_CACHE->load("user_".AUTH_APP_ID."_".$ENTRADA_USER->getID())) && $cached_user != $ENTRADA_USER) {
		$ENTRADA_CACHE->save($ENTRADA_USER, "user_".AUTH_APP_ID."_".$ENTRADA_USER->getID(), array("auth"), 300);
	}
}

if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	if (isset($PATH_SEPARATED[1])) {
		switch ($PATH_SEPARATED[1]) {
			case "confirm_observership" :
			case "password_reset" :
			case "privacy_policy" :
			case "help" :
				$MODULE = $PATH_SEPARATED[1];
			break;
            case "login_secure" :
            case "secure" :
                $MODULE = "secure";
            break;
			case "assessment" :
				$MODULE = "assessment";
			break;
			case "api-assessment-external.inc.php" :
				$MODULE = "api-assessment-external";
			break;
			case "api-assessment-external" :
				$MODULE = "api-assessment-external";
			break;
			default :
				$MODULE = "login";
			break;
		}
	}
} else {
	if (($_SESSION["details"]["expires"] && ($_SESSION["details"]["expires"] <= time())) || !isset($_SESSION["details"]["app_id"]) || ($_SESSION["details"]["app_id"] != AUTH_APP_ID)) {
		header("Location: ".ENTRADA_URL."/?action=logout");
		exit;
	}

	/**
	 * This function controls setting the permission masking feature.
	 */
	permissions_mask();

	/**
	 * This function updates the users_online table.
	 */
	users_online();

	/**
	 * This section of code sets the $MODULE variable.
	 */
	if (isset($PATH_SEPARATED[1]) && (trim($PATH_SEPARATED[1]) != "")) {
		$MODULE = $PATH_SEPARATED[1]; // This is sanitized when $PATH_SEPARATED is created.
	} else {
		$MODULE = "dashboard"; // This is the default file that will be launched upon successful login.
	}

	/**
	 * This section of code sets the $SUBMODULE variable.
	 */
	if (isset($PATH_SEPARATED[2]) && (trim($PATH_SEPARATED[2]) != "")) {
		$SUBMODULE = $PATH_SEPARATED[2]; // This is sanitized when $PATH_SEPARATED is created.
	} else {
		$SUBMODULE = false; // This is the default file that will be launched upon successful login.
	}

	/**
	 * This is a simple re-direct to catch admin without slash on the end.
	 */
	if ($MODULE == "admin") {
		header("Location: ".ENTRADA_URL."/admin/");
		exit;
	}

	/**
	 * This sends guests on their way to their communities and prevents them from seeing any other part of the site.
	 */
	if ((($MODULE != "communities") || (!isset($_GET["section"]) || ($_GET["section"] != "leave"))) && ($_SESSION["details"]["group"] == "guest") && ($_SESSION["details"]["role"] == "communityinvite")) {
		$query	= "	SELECT a.`community_id`, b.`community_url`
					FROM `community_members`AS a
					LEFT JOIN `communities` AS b
					ON a.`community_id` = b.`community_id`
					WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
					AND a.`member_active` = 1
					ORDER BY a.`member_joined`";
		$result	= $db->GetRow($query);
		if ($result) {
			/**
			 * This guest belongs to at least one community
			 */
			header("Location: ".ENTRADA_URL."/community".$result["community_url"]);
			exit;
		} elseif (isset($_SESSION["isAuthorized"]) && $_SESSION["isAuthorized"] == true) {
			header("Location: ".ENTRADA_URL."/?action=logout");
			exit;
		}
	}

	/**
	 * This section of code is only activated if the user is changing their privacy_level.
	 * The real work is actually done in modules/public/profile.inc.php; however, I need the
	 * session data to be properly set so the page tabs display the correct information.
	 */
	if (isset($localPOST["privacy_level"]) && ($privacy_level = (int) trim($localPOST["privacy_level"]))) {
		if ($privacy_level > MAX_PRIVACY_LEVEL) {
			$privacy_level = MAX_PRIVACY_LEVEL;
		}

		$_SESSION["details"]["privacy_level"] = $privacy_level;
	}
}

/**
 * SSO redirection
 *
 * If SSO is enabled, we might have to redirect to the external authentication source if not authenticated yet.
 * Redirect to the SSO provider for the following situations:
 * - SSO Login button selected on the login screen (&action="ssologin") or provided as a GET parameter
 * - The SSO provider requires it (based on the SSO implementation)
 * - The only available AUTH_METHOD is "sso". In which case, there is no local login possible
 *
 * Redirection is not done if:
 * - there was a previous SSO error or SSO was bypassed on the login page
 * - the page does not require authentication
 */
if ((!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) && $sso && !$sso_bypass && !$SSO_ERROR && in_array($MODULE, ["login","secure","login_secure"])) {
    if (($ACTION == "ssologin") || $sso->requiresLogin() || (defined("AUTH_METHOD") && AUTH_METHOD == "sso")) {
        $sso->login(rawurlencode((isset($_SERVER["HTTPS"]) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . ($PROCEED_TO ? $PROCEED_TO : "")));
    }
}

/**
 * Make sure that the login page is accessed via SSL if either the AUTH_FORCE_SSL is not defined in
 * the settings.inc.php file or it's set to true.
 */
if (($MODULE == "login") && !isset($_SERVER["HTTPS"]) && (!defined("AUTH_FORCE_SSL") || AUTH_FORCE_SSL)) {
	header("Location: ".str_replace("http://", "https://", strtolower(ENTRADA_URL)."/?url=".rawurlencode($PROCEED_TO)));
	exit;
}

define("PARENT_INCLUDED", true);

/*
 * Header
 */
switch ($MODULE) {
    case "confirm_observership" :
    case "password_reset" :
    case "privacy_policy" :
    case "help" :
        require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header.tpl.php");
        break;
	case "exams":
		if ($SECTION === "attempt") {
			require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header_exam.tpl.php");
		} else if (isset($PATH_SEPARATED[2]) &&  $PATH_SEPARATED[2] === "reports") {
			require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header_no_prototype.tpl.php");
		} else {
			require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header.tpl.php");
		}
		break;
    case "login" :
    case "secure":
    case "login_secure" :
    
        if (isset($PATH_SEPARATED[1]) && $PATH_SEPARATED[1] === "secure") {
            require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header_secure.tpl.php");
        } else {
            require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header.tpl.php");
        }
        break;
    
    default:
        require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/header.tpl.php");
        break;
}

/*
 * Body
 */
switch ($MODULE) {
	case "confirm_observership" :
	case "password_reset" :
	case "privacy_policy" :
	case "help" :
	case "login_secure" :
    case "login" :
        if (isset($PATH_SEPARATED[1]) && $PATH_SEPARATED[1]=== "secure") {
            require_once(ENTRADA_ABSOLUTE . DIRECTORY_SEPARATOR . "default-pages" . DIRECTORY_SEPARATOR . "login_secure.inc.php");
        } else {
            require_once(ENTRADA_ABSOLUTE . DIRECTORY_SEPARATOR . "default-pages" . DIRECTORY_SEPARATOR . $MODULE . ".inc.php");
        }
        break;
	case "assessment" :
	case "api-assessment-external":
		define("IN_EXTERNAL_ASSESSMENT", "IN_EXTERNAL_ASSESSMENT");
		require_once(ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."default-pages".DIRECTORY_SEPARATOR.$MODULE.".inc.php");
    case "filebrowser" :
		require_once(ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."default-pages".DIRECTORY_SEPARATOR.$MODULE.".inc.php");
	break;
    case "secure":
        
        /**
         * Initialize Entrada_Router so it can load the requested modules.
         */
        $router = new Entrada_Router();
        $router->setBasePath(ENTRADA_CORE . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "public");
        $router->setSection($SECTION);

        if ($router && ($route = $router->initRoute($MODULE))) {
            $module_file = $router->getRoute();
            if ($module_file) {
                require_once($module_file);
            }
        } else {
            application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [" . ENTRADA_URL . "].");

            header("Location: " . ENTRADA_URL);
            exit;
        }
        break;
	default :

		/**
		 * Initialize Entrada_Router so it can load the requested modules.
		 */
		$router = new Entrada_Router();
		$router->setBasePath(ENTRADA_CORE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."public");
		$router->setSection($SECTION);

		if ($router && ($route = $router->initRoute($MODULE))) {
            if (isset($_SESSION["isAuthorized"]) && $_SESSION["isAuthorized"]) {
            /**
             * Responsible for displaying the permission masks sidebar item
             * if they have more than their own permission set available.
             */
            if (isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && (count($_SESSION["permissions"]) > 1)) {
                $sidebar_html  = "<form id=\"masquerade-form\" action=\"".ENTRADA_URL."\" method=\"get\">\n";
                $sidebar_html .= "<label for=\"permission-mask\">Available permission masks:</label><br />";
                $sidebar_html .= "<select id=\"permission-mask\" name=\"mask\" style=\"width: 100%\" onchange=\"window.location='".ENTRADA_URL."/".$MODULE."/?".str_replace("&#039;", "'", replace_query(array("mask" => "'+this.options[this.selectedIndex].value")))."\">\n";
                $display_masks = false;
                $added_users = array();
                foreach ($_SESSION["permissions"] as $access_id => $result) {
                    if ($result["organisation_id"] == $ENTRADA_USER->getActiveOrganisation() && is_int($access_id) && ((isset($result["mask"]) && $result["mask"]) || $access_id == $ENTRADA_USER->getDefaultAccessId() || ($result["id"] == $ENTRADA_USER->getID() && $ENTRADA_USER->getDefaultAccessId() != $access_id)) && array_search($result["id"], $added_users) === false) {
                        if (isset($result["mask"]) && $result["mask"]) {
                            $display_masks = true;
                        }
                        $added_users[] = $result["id"];
                        $sidebar_html .= "<option value=\"".(($access_id == $ENTRADA_USER->getDefaultAccessId()) || !isset($result["permission_id"]) ? "close" : $result["permission_id"])."\"".(($result["id"] == $ENTRADA_USER->getActiveId()) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"]) . "</option>\n";
                    }
                }
                $sidebar_html .= "</select>\n";
                $sidebar_html .= "</form>\n";
                if ($display_masks) {
                    new_sidebar_item("Permission Masks", $sidebar_html, "permission-masks", "open");
                }
            }
            }

			$module_file = $router->getRoute();
			if ($module_file) {
				require_once($module_file);
			}
		} else {
			application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".ENTRADA_URL."].");

			header("Location: ".ENTRADA_URL);
			exit;
		}
	break;
}

/*
 * Footer
 */
switch ($MODULE) {
    case "confirm_observership" :
    case "password_reset" :
    case "privacy_policy" :
    case "help" :
    case "login" :
    case "news" :
    case "news_stream":
	case "exams":
		if (isset($PATH_SEPARATED[1]) && $PATH_SEPARATED[1] === "secure") {
			require_once(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/footer_secure.tpl.php");
		} else {
            require_once(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/footer.tpl.php");
		}
        break;
	case "secure" :
    case "login_secure" :
        require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/footer_secure.tpl.php");
        break;
    default:
        require_once(ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/public/footer.tpl.php");
        break;
}


/**
 * Add the Feedback Sidebar Window.
 */
if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $settings = new Entrada_Settings();
    if ($settings->read("bookmarks_display_sidebar")) {
        Models_Bookmarks::showSidebar();
    }
	add_feedback_sidebar($ENTRADA_USER->getActiveGroup());
	Entrada_Utilities::myEntradaSidebar();
    Entrada_Utilities::orgSelectorSidebar();
}
