<?php

use Tymon\JWTAuth\Providers\JWT\Namshi as JWT;

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
            switch ($status = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                case 200:  # OK
                    $response = json_decode($curl_response, true);
                    break;
                default:
                    $response = array("status" => "failed", "message" => "The username or password you have provided is incorrect.");
            }
        }
        // Close request
        curl_close($curl);

        return $response;
    }

    /**
     * Creates a PHP session to access ENTRADA_ACL and other special features.
     * 
     * @param JWT $token
     * @return array $_SESSION["details"]
     */
    public static function login($token)
    {
        global $ENTRADA_USER, $ENTRADA_ACL, $ENTRADA_CACHE;

        // Get payload from token
        $algo = getenv("JWT_ALGO") ? getenv("JWT_ALGO") : "HS256";
        $jwt = new JWT(ENCRYPTION_KEY, $algo);
        $payload = $jwt->decode($token);

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
                "message" => "Your account is not currently set up for access to this application. Please contact a system administrator if you require further assistance."
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
                "message" => "Your account is not currently set up for access to this application. Please contact a system administrator if you require further assistance."
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
                    "message" => "To log in using guest credentials you must be a member of at least one community."
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
            "username" => $ENTRADA_USER->getUsername(),
            "prefix" => $ENTRADA_USER->getPrefix(),
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

        return [
            "status" => "success",
            "message" => "Login successful",
            "token" => $ENTRADA_USER->getToken(), 
            "id" => $ENTRADA_USER->getID(),
            "access_id" => $user_access->getID(),
            "prefix" => $ENTRADA_USER->getPrefix(),
            "firstname" => $ENTRADA_USER->getUsername(),
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
        ];
    }

    /**
     * Destroys the current PHP session to log the user out
     * 
     * @return void
     */
    public static function logout() {
        $_SESSION = array();
        unset($_SESSION);
        session_destroy();
    }

    public static function isAuthorized($token) {
        global $ENTRADA_USER;

        if ($token) {
            if (isset($_SESSION)) {
                if (array_key_exists('isAuthorized', $_SESSION)) {
                    if (isset($ENTRADA_USER)) {
                        if ($ENTRADA_USER->getToken() == $token) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Extract the session id from the given token
     *
     * @param $token int - the jwt token
     * @return bool|int - session_id or false if it cannot be extracted from the token
     */
    public static function getSession($token) {

        $session_id = false;
        if (!empty($token)) {
            // Get payload from token
            $algo = getenv("JWT_ALGO") ? getenv("JWT_ALGO") : "HS256";
            $jwt = new JWT(ENCRYPTION_KEY, $algo);
            $payload = $jwt->decode($token);
            $session_id = $payload["session_id"];
        }
        return $session_id;
    }
}
