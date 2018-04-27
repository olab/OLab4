<?php

use Tymon\JWTAuth\Providers\JWT\Namshi as JWT;
use Illuminate\Support\Facades\Log;

class Entrada_Auth {

    protected $url, $auth_app_id, $auth_app_username, $auth_app_password;

    public function __construct($url) {
        $this->url = $url;
    }

    public function setAppAuth($auth_app_id, $auth_username, $auth_password) 
    {
        $this->auth_app_id = $auth_app_id;
        $this->auth_username = $auth_username;
        $this->auth_password = $auth_password;
    }

    public function authenticate($username, $password, $auth_method)
    {
        global $translate;

        // Get cURL resource
        $curl = curl_init();

        $options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => [
                'auth_app_id'       => $this->auth_app_id,
                'auth_username'     => $this->auth_username,
                'auth_password'     => $this->auth_password,
                'username'          => $username,
                'password'          => $password,
                'auth_method'       => $auth_method,
            ]
        ];

        if (defined('SSL_VERIFY_CERTIFICATE') && !SSL_VERIFY_CERTIFICATE) {
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
            $options[CURLOPT_SSL_VERIFYPEER] = 0;
        }

        // Set cURL options
        curl_setopt_array($curl, $options);

        // Send the request & save response to $resp
        $curl_response = curl_exec($curl);

        // construct the return array, based on the curl status and response
        if (curl_errno($curl)) {
            $response = array("status" => "error", "message" => curl_error($curl));
        } else {
            switch ($http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    $response = json_decode($curl_response, true);
                    break;
                case 400:  # bad request (invalid username or password)
                    $message = json_decode($curl_response, true)[0];
                    if ($message == "validation_error") {
                        $response = array("status" => "failed", "message" => $translate->_("The username or password you have provided is incorrect."));
                    } else {
                        $response = array("status" => "failed", "message" => $translate->_("An internal authentication error has occurred."));
                    }
                    break;
                case 401:  # unauthorized
                    $message = json_decode($curl_response, true)[0];
                    if ($message == "invalid_app_credentials") {
                        $response = array("status" => "failed", "message" => $translate->_("The application credentials are incorrect for this system."));
                    } else {
                        $response = array("status" => "failed", "message" => $translate->_("The username or password you have provided is incorrect."));
                    }
                    break;
                case 500:
                    $response = array("status" => "error", "message" => $translate->_("An internal server error has occurred."));
                    break;
                default:
                    $response = array("status" => "failed", "message" => $translate->_("An internal authentication error has occurred."));
            }
        }
        // Close request
        curl_close($curl);

        return [
            "http_status" => $http_status,
            "response" => $response
        ];
    }

    /**
     * Creates a PHP session to access ENTRADA_ACL and other special features.
     * 
     * @param JWT $token
     * @return array $_SESSION["details"]
     */
    public static function login($token)
    {
        global $ENTRADA_USER, $ENTRADA_ACL, $ENTRADA_CACHE, $translate;

        // Get payload from token
        $payload = self::getPayload($token);

        /**
         * If $ENTRADA_USER was previously initialized in init.inc.php
         * before the session was authorized, it is set to false and needs to be re-initialized.
         * The app_id that was used in authenticating is also stored in the token, so fetch it as well
         */
        $ENTRADA_USER = User::get($payload["sub"]);
        $auth_app_id = $payload["auth_app_id"];

        /**
         * Validate this user's access to the application, before we start mucking about with session variables
         */
        $access_records = Models_User_Access::fetchAllActiveByProxyIDAppID($ENTRADA_USER->getID(), $auth_app_id);
        if (!$access_records) {
            application_log("auth_notice", "Username [".$ENTRADA_USER->getID()."] attempted to log into application_id [".$auth_app_id."], and their account has not yet been provisioned.");
            return [
                "status" => "error",
                "message" => $translate->_("Your account is not currently set up for access to this application. Please contact a system administrator if you require further assistance.")
                ];
        }

        /**
         * Set the active organisation profile for the user. This will give us the right user_access id to use
         */
        load_active_organisation();

        $user_access = Models_User_Access::fetchRowByID($ENTRADA_USER->getAccessId());
        if (!$user_access) {
            application_log("auth_notice", "Username [".$ENTRADA_USER->getID()."] attempted to log into application_id [".$auth_app_id."], but no user_access record could be found.");
            return [
                "status" => "error",
                "message" => $translate->_("Your account is not currently set up for access to this application. Please contact a system administrator if you require further assistance.")
            ];
        }

        /**
         * For guest accounts, make sure they have somewhere to go. Otherwise, no go.
         */
        if ($user_access->getGroup() == "guest") {
            $community_members = Models_Community_Member::fetchAllByProxyID($ENTRADA_USER->getID(), 1);
            if (!is_array($community_members) || count($community_members) == 0) {
                application_log("auth_notice", "Guest user[".$ENTRADA_USER->getUsername()."] tried to log in and isn't a member of any communities.");
                return [
                    "status" => "error",
                    "message" => $translate->_("To log in using guest credentials you must be a member of at least one community.")
                ];
            }
        }

        /**
         * Checks completed. This user is granded access. Set session variables
         */
        $_SESSION["isAuthorized"] = true;
        $_SESSION["auth"]["method"] = (isset($payload["auth_method"]) ? $payload["auth_method"] : "local");
        $_SESSION["details"] = [
            "app_id" => (int) $auth_app_id,
            "id" => $ENTRADA_USER->getID(),
            "access_id" => $user_access->getID(),
            "prefix" => $ENTRADA_USER->getPrefix(),
            "number" => $ENTRADA_USER->getNumber(),
            "username" => $ENTRADA_USER->getUsername(),
            "firstname" => $ENTRADA_USER->getFirstname(),
            "lastname" => $ENTRADA_USER->getLastname(),
            "email" => $ENTRADA_USER->getEmail(),
            "email_alt" => $ENTRADA_USER->getEmailAlt(),
            "email_updated" => (int) $ENTRADA_USER->getEmailUpdated(),
            "google_id" => $ENTRADA_USER->getGoogleId(),
            "telephone" => $ENTRADA_USER->getTelephone(),
            "role" => $user_access->getRole(),
            "group" => $user_access->getGroup(),
            "organisation_id" => $user_access->getOrganisationID(),
            "expires" => $user_access->getAccessExpires(),
            "lastlogin" => $user_access->getLastLogin(),
            "privacy_level" => $ENTRADA_USER->getPrivacyLevel(),
            "copyright" => $ENTRADA_USER->getCopyright(),
            "notifications" => $ENTRADA_USER->getNotifications(),
            "private_hash" => $user_access->getPrivateHash(),
            "allow_podcasting" => false,
        ];

        $_SESSION["permissions"][$user_access->getID()]["group"] = $user_access->getGroup();
        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"] = $user_access->getID();

        // Attach token to $ENTRADA_USER
        $ENTRADA_USER->setToken($token);

        /**
         * Cache the credentials
         */
        if (isset($ENTRADA_CACHE) && !DEVELOPMENT_MODE) {
            if (!($ENTRADA_CACHE->test("acl_"  . $auth_app_id . "_" . $ENTRADA_USER->getID()))) {
                $ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
                $ENTRADA_CACHE->save($ENTRADA_ACL, "acl_" . $auth_app_id . "_" . $ENTRADA_USER->getID());
            } else {
                $ENTRADA_ACL = $ENTRADA_CACHE->load("acl_" . $auth_app_id . "_" . $ENTRADA_USER->getID());
            }
        } else {
            $ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
        }

        $extras = unserialize(base64_decode($user_access->getExtras()));
        if (isset($extras["allow_podcasting"])) {
            if ((int) trim($extras["allow_podcasting"])) {
                $_SESSION["details"]["allow_podcasting"] = (int) trim($extras["allow_podcasting"]);
            } elseif (trim(strtolower($extras["allow_podcasting"])) == "all") {
                $_SESSION["details"]["allow_podcasting"] = "all";
            }
        }

        /**
         * Any custom session information that needs to be set on a per-group basis.
         */
        switch ($ENTRADA_USER->getActiveGroup()) {
            case "student" :
                if (!$ENTRADA_USER->getGradYear()) {
                    $_SESSION["details"]["grad_year"] = fetch_first_year();
                } else {
                    $_SESSION["details"]["grad_year"] = $ENTRADA_USER->getGradYear();
                }
                break;
            case "medtech" :
                /**
                 * If you're in MedTech, always assign a graduating year,
                 * because we normally see more than normal users.
                 */
                $_SESSION["details"]["grad_year"] = fetch_first_year();
                break;
            case "staff" :
            case "faculty" :
            default :
                continue;
                break;
        }

        /**
         * If the users e-mail address hasn't been verified in the last 365 days,
         * set a flag that indicates this should be done.
         */
        if (!$_SESSION["details"]["email_updated"] || (($_SESSION["details"]["email_updated"] - time()) / 86400 >= 365)) {
            $_SESSION["details"]["email_updated"] = false;
        } else {
            $_SESSION["details"]["email_updated"] = true;
        }

        add_statistic("index", "login", "access_id", $ENTRADA_USER->getAccessId(), $ENTRADA_USER->getID());
        application_log("access", "User [".$ENTRADA_USER->getUsername()."] successfully logged in.");

        return array_merge([
            "status" => "success",
            "message" => "Login successful",
            "token" => $ENTRADA_USER->getToken(),
        ], self::getUserProfile($user_access));
    }

    public static function getUserProfile($user_access = null)
    {
        global $ENTRADA_USER;

        /**
         * Get user access if not already set
         */

        $user_access = $user_access ? $user_access : Models_User_Access::fetchRowByID($ENTRADA_USER->getAccessId());

        /**
         * Get user photo
         */

        $photo_object = Models_User_Photo::get($ENTRADA_USER->getID(), Models_User_Photo::UPLOADED);

        $uploaded_photo = $photo_object ? $photo_object->toArray() : null;

        $avatar_url = webservice_url("photo", array($ENTRADA_USER->getID(), $uploaded_photo ? "upload" : "official"))."/".time();

        /**
         * Return user profile information
         */

        return [
            "id" => $ENTRADA_USER->getID(),
            "access_id" => $user_access->getID(),
            "prefix" => $ENTRADA_USER->getPrefix(),
            "number" => $ENTRADA_USER->getNumber(),
            "username" => $ENTRADA_USER->getUsername(),
            "firstname" => $ENTRADA_USER->getFirstname(),
            "lastname" => $ENTRADA_USER->getLastname(),
            "email" => $ENTRADA_USER->getEmail(),
            "email_alt" => $ENTRADA_USER->getEmailAlt(),
            "email_updated" => (int) $ENTRADA_USER->getEmailUpdated(),
            "google_id" => $ENTRADA_USER->getGoogleId(),
            "telephone" => $ENTRADA_USER->getTelephone(),
            "role" => $user_access->getRole(),
            "group" => $user_access->getGroup(),
            "organisation_id" => $user_access->getOrganisationID(),
            "access_starts" => $user_access->getAccessStarts(),
            "access_expires" => $user_access->getAccessExpires(),
            "last_login" => $user_access->getLastLogin(),
            "privacy_level" => $ENTRADA_USER->getPrivacyLevel(),
            "copyright" => $ENTRADA_USER->getCopyright(),
            "notifications" => $ENTRADA_USER->getNotifications(),
            "private_hash" => $user_access->getPrivateHash(),
            "private-allow_podcasting"  => $_SESSION["details"]["allow_podcasting"],
            "acl" => $user_access->getOrganisationID(),
            "avatar_url" => $avatar_url,
        ];
    }

    /**
     * Destroys the current PHP session to log the user out
     * 
     * @return void
     */
    public static function logout()
    {
        $_SESSION = array();
        unset($_SESSION);
        session_destroy();
    }

    /**
     * Check if the user represented in the token is logged in
     *
     * @param $token
     * @return bool
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    public static function isAuthorized($token)
    {
        if ($token) {

            self::loadSession($token);

            if (isset($_SESSION)) {
                if (array_key_exists('isAuthorized', $_SESSION)) {
                    if ($_SESSION['isAuthorized'] === true) {
                        if (isset($_SESSION['details'])) {
                            /**
                             * 2018/03/15 EAH:
                             * Took out this check that the session references the token. When there are multiple API
                             * requests coming in, it is possible that one request will refresh the token but not return
                             * the new token until after more API requests have started with the previous token.
                             * The token blacklist grace period allows for the older tokens to still be valid for a short time
                             * to prevent asynchronous API calls from failing. We have to assume that more than one valid token
                             * can contain the same session_id
                             */
                            //if ($_SESSION['details']['token'] == $token) {

                                // now that the token has been verified as the one in session, re-initiate $ENTRADA_USER
                                self::login($token); 

                                return true;
                            //}
                        }
                    }

                }
            }
        }

        return false;
    }

    /**
     * Extract the payload from the given token
     * 
     * @param $token - the jwt token
     * @return array|bool - the payload
     *
     * @throws Tymon\JWTAuth\Exceptions\JWTException
     */
    public static function getPayload($token)
    {
        $algo = getenv("JWT_ALGO") ? getenv("JWT_ALGO") : "HS256";
        $jwt = new JWT(ENCRYPTION_KEY, $algo);
        $payload = $jwt->decode($token);

        return $payload;
    }

    /**
     * Extract the session id from the given token
     *
     * @param $token int - the jwt token
     * @return bool|int - session_id or false if it cannot be extracted from the token
     *
     * @throws Tymon\JWTAuth\Exceptions\JWTException
     */
    public static function getSession($token)
    {
        $session_id = false;
        if (!empty($token)) {
            // Get payload from token
            $payload = self::getPayload($token);
            $session_id = $payload["session_id"];
        }
        return $session_id;
    }

    /**
     * Extract the session id from the given token
     *
     * @param $token int - the jwt token
     * @return bool|int - session_id or false if it cannot be extracted from the token
     *
     * @throws Tymon\JWTAuth\Exceptions\JWTException
     */
    public static function loadSession($token)
    {
        $payload = self::getPayload($token);

        // Get session ID from token
        $session_id = $payload['session_id'];

        if ($session_id) {

            if ($session_id != session_id()) {
                // End the existing non-attached session
                session_write_close();

                // Set the proper session ID
                session_id($session_id);

                // restart existing session based on ID
                session_start();

                Log::info('Session reloaded for user.', ['user_id' => $payload['sub']]);
            } else {
                Log::info('Session has not changed for user.', ['user_id' => $payload['sub']]);
            }

            return $session_id;
        }

        return false;
    }
}
