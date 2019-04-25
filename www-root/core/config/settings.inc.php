<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This is the Entrada settings file which reads from the configuration file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

/*
 * Push user to setup if the config file doesn't exist, and the
 * setup file does.
 */
if (!@file_exists("core/config/config.inc.php") && @file_exists("setup/index.php")) {
    header("Location: setup/index.php");
    exit;
}

$config = new Zend_Config(require "config/config.inc.php");

/**
 * The default timezone based on PHP's supported timezones:
 * http://php.net/manual/en/timezones.php
 */
define("DEFAULT_TIMEZONE", "America/Edmonton");

date_default_timezone_set(DEFAULT_TIMEZONE);

/**
 * DEVELOPMENT_MODE - Whether or not you want to run in development mode.
 * When in development mode only IP's that exist in the $DEVELOPER_IPS
 * array will be allowed to access the application. Others are directed to
 * the notavailable.html file.
 *
 */
define("DEVELOPMENT_MODE", false);

/**
 * AUTH_DEVELOPMENT - If you would like to specify an alternative authetication
 * web-service URL for use during development you can do so here. If you leave
 * this blank it will use the AUTH_PRODUCTION URL you specify below.
 *
 * WARNING: Do not leave your development URL in here when you put this
 * into production.
 *
 */
define("AUTH_DEVELOPMENT", "");

$DEVELOPER_IPS = array();

define("ENTRADA_URL", $config->entrada_url);									// Full URL to application's index file without a trailing slash.
define("ENTRADA_RELATIVE", $config->entrada_relative);							// Absolute Path from the document_root to application's index file without a trailing slash.
define("ENTRADA_ABSOLUTE", $config->entrada_absolute);							// Full Directory Path to application's index file without a trailing slash.
define("ENTRADA_API_ABSOLUTE", $config->entrada_api_absolute);			        // Full Directory Path to api root directory without a trailing slash.
define("ENTRADA_CORE", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."core");			// Full Directory Path to the Entrada core directory.
define("API_BASE_PATH", "api/v2");

/**
 * DEMO_MODE - Whether or not you want to run in demo mode.
 * When in demo mode upload functionality is limited or replaced
 * with place holder files (DEMO_FILENAME) to reduce the posibility of any malicious
 * actions taking place through the Entrada demo site.
 *
 */
define("DEMO_MODE", false);
define("DEMO_FILE", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_file.jpg");
define("DEMO_PODCAST", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_podcast.aif");
define("DEMO_NOTES", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_notes.rtf");
define("DEMO_SLIDES", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_slides.pptx");
define("DEMO_PHOTO", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_photo.gif");
define("DEMO_ASSIGNMENT", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_assignment.rtf");
define("DEMO_SCHEDULE", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_schedule.csv");
define("DEMO_GRADEBOOK", ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."demo".DIRECTORY_SEPARATOR."demo_grades.csv");

define("COMMUNITY_URL", ENTRADA_URL."/community");								// Full URL to the community directory without a trailing slash.
define("COMMUNITY_ABSOLUTE", ENTRADA_ABSOLUTE."/community");					// Full Directory Path to the community directory without a trailing slash.
define("COMMUNITY_RELATIVE", ENTRADA_RELATIVE."/community");					// Absolute Path from the document_root to the community without a trailing slash.

define("DATABASE_TYPE", $config->database->adapter);												// Database Connection Type
define("DATABASE_HOST", $config->database->host);								// The hostname or IP of the database server you want to connnect to.
define("DATABASE_NAME", $config->database->entrada_database);					// The name of the database to connect to.
define("DATABASE_USER", $config->database->username);							// A username that can access this database.
define("DATABASE_PASS", $config->database->password);							// The password for the username to connect to the database.

// CMW: added
define("OLAB_DATABASE",        $config->database->openlabyrinth_database);     // The name of the database to connect to.

if (!defined('ADODB_DIR')) {    define("ADODB_DIR", ENTRADA_ABSOLUTE."/../vendor/adodb/adodb-php"); }

define("ACADEMIC_YEAR_START_DATE", "September 1");								// The start month and day of your academic year.

define("CLERKSHIP_DATABASE", $config->database->clerkship_database);			// The name of the database that stores the clerkship schedule information.
define("CLERKSHIP_SITE_TYPE", 1);												// The value this application will use for site types in the clerkship logbook module. This will be removed/replaced by functional logic to decide which site type to use in the future - for now, leave this as 1.
define("CLERKSHIP_EMAIL_NOTIFICATIONS", true);									// Whether email notifications will be sent out to the Program Coordinator of the Rotation's related course
define("CLERKSHIP_FIRST_CLASS", 2012);
define("ONE_WEEK", 604800);
define("CLERKSHIP_SIX_WEEKS_PAST", 4);
define("CLERKSHIP_ROTATION_ENDED", 3);
define("CLERKSHIP_ONE_WEEK_PRIOR", 2);
define("CLERKSHIP_ROTATION_PERIOD", 1);
define("CLERKSHIP_EVALUATION_TIMEOUT", ONE_WEEK);
define("CLERKSHIP_EVALUATION_LOCKOUT", 0);
define("CLERKSHIP_SETTINGS_REQUIREMENTS", false);


define("EVALUATION_LOCKOUT", ONE_WEEK);

$CLERKSHIP_REQUIRED_WEEKS = 14;
$CLERKSHIP_CATEGORY_TYPE_ID = 13;
$CLERKSHIP_EVALUATION_FORM = "http://url_of_your_schools_precptor_evaluation_of_clerk_form.pdf";
$CLERKSHIP_INTERNATIONAL_LINK = "http://url_of_your_schools_international_activities_procedures";
$CLERKSHIP_FIELD_STATUS = array();
$CLERKSHIP_FIELD_STATUS["published"] = array("name" => "Published", "visible" => true);
$CLERKSHIP_FIELD_STATUS["draft"] = array("name" => "Draft", "visible" => true);
$CLERKSHIP_FIELD_STATUS["approval"] = array("name" => "Awaiting Approval", "visible" => false);
$CLERKSHIP_FIELD_STATUS["trash"] = array("name" => "Trash", "visible" => false);
$CLERKSHIP_FIELD_STATUS["cancelled"] = array("name" => "Cancelled", "visible" => false);

/**
 * The Course Report tab requires the event type ids that are to be reported on.
 * Patient Contact Session is eventtype_id 4.
 */
$COURSE_REPORT_EVENT_TYPES = array(4);


define("CURRICULAR_OBJECTIVES_PARENT_ID", 1);

define("AUTH_PRODUCTION", ENTRADA_URL."/" . API_BASE_PATH . "/auth/login");		// Full URL to your production Entrada authentication server.
define("AUTH_ENCRYPTION_METHOD", "default");									// Encryption method the authentication client will use to decrypt information from authentication server. default = low security, but no requirements | blowfish = medium security, requires mCrypt | rijndael 256 = highest security, requires mcrypt.
define("AUTH_APP_ID", "1");														// Application ID for the Authentication System.
define("AUTH_APP_IDS_STRING", "1");												// Application ID's to query for users in.
define("AUTH_USERNAME", $config->auth_username);								// Application username to connect to the Authentication System.
define("AUTH_PASSWORD", $config->auth_password);								// Application password to connect to the Authentication System.
define("AUTH_METHOD", "local");													// The method used to authenticate users into the application (local, ldap, or sso).
define("AUTH_DATABASE",	$config->database->auth_database);						// The name of the database that the authentication tables are located in. Must be able to connect to this using DATABASE_HOST, DATABASE_USER and DATABASE_PASS which are specified below.
define("AUTH_MAX_LOGIN_ATTEMPTS", 5);											// The number of login attempts a user can make before they are locked out of the system for the lockout duration
define("AUTH_LOCKOUT_TIMEOUT", 900);											// The amount of time in seconds a locked out user is prevented from logging in

define("AUTH_FORCE_SSL", false);												// If you want to force all login attempts to use SSL, set this to true, otherwise false.
define("SSL_VERIFY_CERTIFICATE", false);                                        // Determines whether cURL verifies or ignores host and peer SSL certificate validation.

define("LDAP_HOST", "ldap.yourschool.ca");										// The hostname of your LDAP server.
define("LDAP_PEOPLE_BASE_DN", "ou=people,o=main,dc=yourschool,dc=ca");			// The BaseDN of your LDAP server.
define("LDAP_GROUPS_BASE_DN", "ou=groups,o=main,dc=yourschool,dc=ca");			// The BaseDN of your LDAP server.
define("LDAP_SEARCH_DN", "uid=readonly,ou=people,dc=yourschool,dc=ca");			// The LDAP username that is used to search LDAP tree for the member attribute.
define("LDAP_SEARCH_DN_PASS", "");												// The LDAP password for the SearchDN above. These fields are optional.
define("LDAP_MEMBER_ATTR", "UniUid");											// The member attribute used to identify the users unique LDAP ID.
define("LDAP_USER_QUERY_FIELD", "UniCaPKey");									// The attribute used to identify the users staff / student number. Only used if LDAP_LOCAL_USER_QUERY_FIELD is set to "number".
define("LDAP_CGROUP_BASE_DN", "ou=cgroups,ou=groups,o=main,dc=yourschool,dc=ca");
define("LDAP_USER_IDENTIFIER", "youruniquemember");
define("LDAP_LOCAL_USER_QUERY_FIELD", "number");								// username | number : This field allows you to specify which local user_data field is used to search for a valid username.

/**
 * SSO related constants.
 */
define("AUTH_SSO_ENABLED", false);                                              // Enable SSO in addition to local or ldap login
define("AUTH_SSO_TYPE", "Shibboleth");                                          // SSO Implementation used. One of "Cas" or "Shibboleth"
define("AUTH_SSO_LOCAL_USER_QUERY_FIELD", "number");                            // The field name from the user_data table of the Authentication database used to match against the identifier supplied by SSO

define("AUTH_ALLOW_CAS", false);						// DEPRECIATED: Whether or not you wish to allow CAS authorisation.
define("AUTH_CAS_HOSTNAME", "cas.schoolu.ca");					// Hostname of your CAS server.
define("AUTH_CAS_PORT", 443);							// Port that CAS is running on.
define("AUTH_CAS_URI", "cas");							// The URI where CAS is located on the CAS host.
define("AUTH_CAS_COOKIE", "isCasOn");						// The name of the CAS cookie.
define("AUTH_CAS_SESSION", "phpCAS");						// The session key set by phpCAS.
define("AUTH_CAS_ID", "peopleid");						    // The session key that holds the employee / student number.
define("AUTH_CAS_SERVICE_VALIDATOR", "serviceValidate");			// CAS validator suffix request required if customized by your institution.
define("AUTH_CAS_SERVER_CA_CERT","");                                           // Path to CAS Server public CA certificate chain bundle file. Needed to secure CAS with Apache server

define("AUTH_SHIB_URL", "https://shibsp.dev");                                  // URL of the Shibboleth Service Provider (SP) service
define("AUTH_SHIB_LOGIN_URI", "/Shibboleth.sso/Login");                         // The URI to request Shibboleth SP to authenticate the user
define("AUTH_SHIB_LOGOUT_URI", "/Shibboleth.sso/Logout");                       // The URI to request Shibboleth SP to invalidate the user's session(s)
define("AUTH_SHIB_SESSION", "Shib-Session-ID");                                 // The variable in $_SERVER containing the SP session if authentication succeeds
define("AUTH_SHIB_ID", "shib-studentid");                                       // The variable in $_SERVER set by SP that holds the identity key provided by Shibboleth
                                                                                //      This value will be compared against user_data.AUTH_SSO_LOCAL_USER_QUERY_FIELD column in auth database

define("PASSWORD_RESET_URL", ENTRADA_URL."/password_reset");		    		// The URL that users are directed to if they have forgotten their password.
define("PASSWORD_CHANGE_URL", "");	                                			// DEPRECATED: The URL that users are directed to if they wish to change their password.

define("DATABASE_SESSIONS", false);
define("SESSION_DATABASE_TYPE",	DATABASE_TYPE);									// Database Connection Type
define("SESSION_DATABASE_HOST",	DATABASE_HOST);									// The hostname or IP of the database server you want to connnect to.
define("SESSION_DATABASE_NAME",	AUTH_DATABASE);									// The name of the database to connect to.
define("SESSION_DATABASE_USER",	DATABASE_USER);									// A username that can access this database.
define("SESSION_DATABASE_PASS",	DATABASE_PASS);									// The password for the username to connect to the database.

define("SESSION_NAME", "entrada-me");
define("SESSION_EXPIRES", 3600);

define("DEFAULT_TEMPLATE", "default");											// This is the system template that will be loaded. System templates include language files, custom images, visual layouts, etc.
define("DEFAULT_LANGUAGE", "en");												// This is the default language file that will be loaded. Language files must be placed in your DEFAULT_TEMPLATE."/languages directory. (i.e. en.lang.php)
define("DEFAULT_CHARSET", "UTF-8");												// The character encoding which will be used on the website & in e-mails.
define("DEFAULT_COUNTRY_ID", 39);												// The default country_id used to determine provinces / states, etc.
define("DEFAULT_PROVINCE_ID", 9);												// The default province_id that is selected (use 0 for none).

define("DEFAULT_CITY", "City");
define("DEFAULT_POSTALCODE", "0123456");
define("DEFAULT_ROWS_PER_PAGE", 25);

define("ENCRYPTION_KEY", "UXZF4tTES8RmTHY9qA7DQrvqEde7R5a8");					// Encryption key to encrypt data in the encrypted session ;)

/**
 * Default date & time formats for displaying date times to users
 * See http://php.net/manual/en/function.date.php for date & time formatting options.
 *
 * PLEASE NOTE: As of July 2017 DEFAULT_DATE_FORMAT no longer contains the time.
 * Keep your existing value of DEFAULT_DATE_FORMAT until you are sure that all
 * of occurrences of this constant have been refactored to DEFAULT_DATETIME_FORMAT
 * or they do not need to show the time.
 */
define("DEFAULT_FULLDATE_FORMAT", "D M d/y");
define("DEFAULT_DATE_FORMAT", "Y-m-d");
define("DEFAULT_TIME_FORMAT", "g:ia");
define("DEFAULT_DATETIME_FORMAT", "D M d/y g:ia");

/**
 * Kiosk Mode Card Parsing
 * define a javascript expression to extract id number from card input
 */
define("KIOSK_MODE_CARD_PARSER", "data.substring(0,data.length-2)");

/**
 * Google Analystics Tracking Code
 * Create an account at: http://www.google.com/analytics
 */
define("GOOGLE_ANALYTICS_CODE",	"");											// If you would like Google Analytics to track your usage (in production), then enter your tracking code.

/**
 * Goole Maps API Key
 * Generate your key from: http://code.google.com/apis/maps/
 */
define("GOOGLE_MAPS_API", "http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=XXXXXXXXXXX");

/**
 * Safe iframe regex
 * iframes matching this pattern will not have the src attribute stripped out by HTMLPurifier
 */
define("VIDEO_EMBED_REGEX", "www.youtube.com/embed/|player.vimeo.com/video/");

/**
 * Defines whether the system should allow communities to have mailing lists created for them,
 * and what type of mailing lists will be used (currently google is the only choice.)
 *
 */
$MAILING_LISTS = array();
$MAILING_LISTS["active"] = false;
$MAILING_LISTS["type"] = "google";

/* RP NOW SECURE BROWSER API */
define("RP_NOW_API", "https://exams.remoteproctor.io/");
define("RP_NOW_ACCESS_KEY_ID", "XXXXXXXXXXXXXXXXXXXX");
define("RP_NOW_SSI_SECRET_KEY", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
define("RP_NOW_CRYPTO_KEY", "XXXXXXXXXXXXXXXXXXXXXXXX");
define("RP_NOW_ORGANIZATION", "zentrada");
define("RP_NOW_DOWNLOAD_URL", "http://zentrada.remoteproctor.com/RPInstallCEF/sep28install/InstallV2.html?orgName=zentrada&orgType=true");

/**
 * Google Hosted Apps Details
 * Signup for Google Apps at: http://www.google.com/apps/
 */
$GOOGLE_APPS = array();
$GOOGLE_APPS["active"] = false;
$GOOGLE_APPS["groups"] = array();
$GOOGLE_APPS["admin_username"] = "";
$GOOGLE_APPS["admin_password"] = "";
$GOOGLE_APPS["domain"] = "";
$GOOGLE_APPS["quota"] = "25 GB";
$GOOGLE_APPS["new_account_subject"]	= "Activation Required: New %GOOGLE_APPS_DOMAIN% Account";
$GOOGLE_APPS["new_account_msg"] = <<<GOOGLENOTIFICATION
Dear %FIRSTNAME% %LASTNAME%,

Good news! Your new %GOOGLE_APPS_DOMAIN% account has just been created, now you need to activate it!

Account Activation:
====================

To activate your %GOOGLE_APPS_DOMAIN% account, please follow these instructions:

1. Go to http://webmail.%GOOGLE_APPS_DOMAIN%

2. Enter your %APPLICATION_NAME% username and password:

   Username: %GOOGLE_ID%
   Password: - Enter Your %APPLICATION_NAME% Password -

3. Once you have accepted Google's Terms of Service, your account is active.

What Is This?
====================

Your %GOOGLE_APPS_DOMAIN% account gives you access to:

- http://webmail.%GOOGLE_APPS_DOMAIN% (E-Mail Service)
Your own %GOOGLE_ID%@%GOOGLE_APPS_DOMAIN% e-mail account with %GOOGLE_APPS_QUOTA% of space, which will remain active even after you graduate.

- http://calendar.%GOOGLE_APPS_DOMAIN% (Calendar Service)
Your own online calendar that allows you to create both personal and shared calendars, as well as subscribe to your school schedule.

- http://documents.%GOOGLE_APPS_DOMAIN% (Document Service)
Your own online office suite with personal document storage.

If you require any assistance, please do not hesitate to contact us.

--
Sincerely,

%ADMINISTRATOR_NAME%
%ADMINISTRATOR_EMAIL%
GOOGLENOTIFICATION;

/**
 * Google Client Libraries / Google v3 REST API Credentials
 *
 * Gives us "Service Account" level access to Google's family of REST API's.
 * Entrada is considered the Service Account and we use these creds to
 * authenticate our app with the API via OAuth 2.0 and receive an access token which
 * is needed to access any auth-required API resources. The Client ID, the Service
 * Account Name and the private key are all generated in the Developer Console
 * (console.developers.google.com).
 *
 * client_id 				ID of our Service Account.
 * service_account_name 	Email address associated with our Service Account.
 * key_file_location 		Location of the PKCS #12-formatted private key file. *** DO NOT STORE IN THE WEBROOT ***
 */

$GOOGLE_V3_REST_API = array();
$GOOGLE_V3_REST_API["client_id"] 		    = "";
$GOOGLE_V3_REST_API["service_account_name"] = "";
$GOOGLE_V3_REST_API["key_file_location"]    = "";

/**
 * Weather Information provided by Yahoo Weather API
 * https://developer.yahoo.com/weather/documentation.html
 *
 * To find your WOEID, browse or search for your city from the Weather home page. The WOEID is in the URL for the
 * forecast page for that city. You can also get the WOEID by entering your zip code on the home page. For example, if
 * you search for Los Angeles on the Weather home page, the forecast page for that city is
 * http://weather.yahoo.com/united-states/california/los-angeles-2442047/. The WOEID is 2442047.
 *
 * The weather widget shows up on the Entrada Dashboard for users, when the cron/weather.php file is run in cron.
 */
$WEATHER_TEMP_UNIT = "c";                                                       // Set this to c for celsius, f for fahrenheits
define("DEFAULT_WEATHER_FETCH", "https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%3D%LOCATIONCODE%%20and%20u%3D'" . $WEATHER_TEMP_UNIT . "'");

$WEATHER_LOCATION_CODES = array("4145" => "Kingston, Ontario");					// Add each WOEID you would like to fetch the weather as a key in this array.

define("LOG_DIRECTORY", $config->entrada_storage . "/logs");					// Full directory path to the logs directory without a trailing slash.

define("USE_CACHE", false);														// true | false: Would you like to have the program cache frequently used database results on the public side?
define("CACHE_DIRECTORY", $config->entrada_storage . "/cache");					// Full directory path to the cache directory without a trailing slash.
define("CACHE_TIMEOUT", 30);													// Number of seconds that a general public query should be cached.
define("LONG_CACHE_TIMEOUT", 3600);												// Number of seconds that a less important / larger public query should be cached.
define("AUTH_CACHE_TIMEOUT", 3600);												// Number of seconds to use cache for on queries that query the Authentication Database.
define("RSS_CACHE_DIRECTORY", CACHE_DIRECTORY);									// Full directory path to the cache directory without a trailing slash (for RSS).
define("RSS_CACHE_TIMEOUT", 300);												// Number of seconds that an RSS file will be cached.

define("COOKIE_TIMEOUT", ((time()) + (3600 * 24 * 365)));						// Number of seconds the cookie will be valid for. (default: ((time())+(3600*24*365)) = 1 year)

define("MAX_NAV_TABS", 8);														//The maxium number of navigation tabs shown to users on every page. Extras will go into a "More" dropdown tab.
define("MAX_PRIVACY_LEVEL", 3);													// Select the max privacy level you accept.
define("MAX_UPLOAD_FILESIZE", 52428800);										// Maximum allowable filesize (in bytes) of a file that can be uploaded (52428800 = 50MB).

define("COMMUNITY_STORAGE_GALLERIES", $config->entrada_storage . "/community-galleries");	// Full directory path where the community gallery images are stored without trailing slash.
define("COMMUNITY_STORAGE_DOCUMENTS", $config->entrada_storage . "/community-shares");		// Full directory path where the community document shares are stored without trailing slash.
define("COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION", $config->entrada_storage . "/community-discussions");		// Full directory path where the community document discussion board files are stored without trailing slash.
$COMMUNITY_ORGANISATIONS = array();															// Array of integer organisation IDs or specifying which organisations are eligble for registration in communities, circumventing APP_ID restrictions. An empty array means all organisations are eligible.

define("ANNUALREPORT_STORAGE", $config->entrada_storage."/annualreports");		// Full directory path where the annual reports are stored without trailing slash.

define("STORAGE_USER_PHOTOS", $config->entrada_storage . "/user-photos");		// Full directory path where user profile photos are stored without trailing slash.

define("STORAGE_RESOURCE_IMAGES", $config->entrada_storage . "/resource-images");		// Full directory path where course/track images are stored without trailing slash.
define("FILE_STORAGE_PATH", $config->entrada_storage . "/event-files");			// Full directory path where off-line files are stored without trailing slash.
define("SECURE_QUIZ_STORAGE_PATH", $config->entrada_storage . "/secure-quiz");	// Full directory path where secure quiz access files are stored without trailing slash.
define("SECURE_ACCESS_STORAGE_PATH", $config->entrada_storage . "/secure-access");	// Full directory path where secure access files are stored without trailing slash.
define("MSPR_STORAGE",$config->entrada_storage . "/msprs");						//Full directory path where student Medical School Performance Reports should be sotred
define("SEARCH_INDEX_PATH",$config->entrada_storage . "/search-indexes");		//Full directory path where student Medical School Performance Reports should be sotred
define("SYLLABUS_STORAGE", $config->entrada_storage . "/syllabi");			// Full directory path where syllabi are stored without trailing slash.
define("EPORTFOLIO_STORAGE_PATH", $config->entrada_storage . "/eportfolio");	// Full directory path where eportfolio files should be sotred
define("LOR_STORAGE_PATH", $config->entrada_storage . "/lor");                  // Full directory path where learning object repository files are stored without trailing slash.
define("EXAM_STORAGE_PATH", $config->entrada_storage . "/exam-files");          // Full directory path where learning object repository files are stored without trailing slash.
define("CBME_UPLOAD_STORAGE_PATH", $config->entrada_storage . "/cbme-uploads");          // Full directory path where CBME files are stored without trailing slash.

define("STORAGE_LOR", "lor");                                                   // This typically will not need to be changed. It's the directory with $filesystem (flysystem) that stores learning objects.

define("SENDMAIL_PATH", "/usr/sbin/sendmail -t -i");							// Full path and parametres to sendmail.

define("DEBUG_MODE", true);														// Some places have extra debug code to show sample output. Set this to true if you want to see it.
define("SHOW_LOAD_STATS", false);												// Do you want to see the time it takes to load each page?

define("SEARCH_FILE_CONTENTS", true);											// Add file contents search to curriculum search

define("APPLICATION_NAME", "OLab 4 (dev)");											// The name of this application in your school (i.e. MedCentral, Osler, etc.)
//define("APPLICATION_NAME", "Entrada ME");											// The name of this application in your school (i.e. MedCentral, Osler, etc.)
define("APPLICATION_VERSION", "1.12.0"); 										// The current filesystem version of Entrada.
define("APPLICATION_IDENTIFIER", "app-".AUTH_APP_ID);							// PHP does not allow session key's to be integers (sometimes), so we have to make it a string.

$DEFAULT_META["title"] = "OLab 4: Scenario-Based Training ";
//$DEFAULT_META["title"] = "Entrada ME: An eLearning Ecosystem";
$DEFAULT_META["keywords"] = "";
$DEFAULT_META["description"] = "";

define("COPYRIGHT_STRING", "Copyright ".date("Y", time())." Entrada Project. All Rights Reserved.");

define("NOTIFY_ADMIN_ON_ERROR", false);											// Do you want to notify the administrator when an error is logged? Please Note: This can be a high volume of e-mail.

define("ENABLE_NOTICES", true);													// Do you want the dashboard notices to display or not?

/**
 * Do you want the dashboard event resource links to display or just the plain resource counts?
 * Default is true.
*/
define("ENABLE_DASHBOARD_EVENT_RESOURCE_LINKS", true);

/**
 * A list of external command-line applications that Entrada uses.
 */
$APPLICATION_PATH = array();
$APPLICATION_PATH["htmldoc"] = "/usr/bin/htmldoc";
$APPLICATION_PATH["wkhtmltopdf"] = "/usr/bin/wkhtmltopdf";

/**
 * Application contact name's and e-mail addresses.
 */
$AGENT_CONTACTS = array();
$AGENT_CONTACTS["administrator"] = array("name" => $config->admin->firstname." ".$config->admin->lastname, "email" => $config->admin->email);
$AGENT_CONTACTS["general-contact"] = array("name" => "Undergraduate Education", "email" => $config->admin->email);
$AGENT_CONTACTS["agent-feedback"] = array("name" => "System Administrator", "email" => $config->admin->email);
$AGENT_CONTACTS["agent-notifications"] = array("name" => "Undergraduate Education", "email" => $config->admin->email);
$AGENT_CONTACTS["agent-clerkship"] = array("name" => "Clerkship Administrator", "email" => $config->admin->email, "director_ids" => array(0));
$AGENT_CONTACTS["agent-clerkship-international"] = array("name" => "International Clerkship Administrator", "email" => $config->admin->email);
$AGENT_CONTACTS["agent-regionaled"] = array("name" => "Apartment Administrator", "email" => $config->admin->email);
$AGENT_CONTACTS["community-notifications"] = array("name" => "Communities Administrator", "email" => $config->admin->email);
$AGENT_CONTACTS["observership"] = array("name" => "Undergraduate Education", "email" => $config->admin->email);

/**
 * A list of reserved names of community pages (in lower case). If a new community page matches
 * one on this list, the user will need to change their Menu Title in order to create the new page.
 */
$COMMUNITY_RESERVED_PAGES = array();
$COMMUNITY_RESERVED_PAGES[] = "home";
$COMMUNITY_RESERVED_PAGES[] = "members";
$COMMUNITY_RESERVED_PAGES[] = "pages";
$COMMUNITY_RESERVED_PAGES[] = "search";
$COMMUNITY_RESERVED_PAGES[] = "ics";
$COMMUNITY_RESERVED_PAGES[] = "rss";

define("COMMUNITY_NOTIFY_TIMEOUT", 3600);										// Lock file expirary time
define("COMMUNITY_MAIL_LIST_MEMBERS_TIMEOUT", 1800);							// Lock file expirary time
define("COMMUNITY_MAIL_LIST_CLEANUP_TIMEOUT", 1800);							// Lock file expirary time
define("COMMUNITY_NOTIFY_LOCK", CACHE_DIRECTORY."/notify_mail.lck");			// Full directory path to the cache directory without a trailing slash (for RSS).
define("COMMUNITY_MAIL_LIST_MEMBERS_LOCK", CACHE_DIRECTORY."/mail_list_members.lck"); // Full directory path to the cache directory without a trailing slash (for RSS).
define("COMMUNITY_MAIL_LIST_CLEANUP_LOCK", CACHE_DIRECTORY."/mail_list_cleanup.lck"); // Full directory path to the cache directory without a trailing slash (for RSS).
define("COMMUNITY_NOTIFY_LIMIT", 100);											// Per batch email mailout limit
define("COMMUNITY_MAIL_LIST_MEMBERS_LIMIT", 100);								// Per batch google requests limit

define("COMMUNITY_NOTIFICATIONS_ACTIVE", true);
define("COMMUNITY_DISCUSSIONS_ANON", true);
define("NOTIFICATIONS_ACTIVE", true);
define("DISCUSSIONS_ANON", true);

/**
 * Array containing valid Podcast mime types as required by Apple.
 */
$VALID_PODCASTS = array();
$VALID_PODCASTS[] = "audio/mp3";
$VALID_PODCASTS[] = "audio/mpg";
$VALID_PODCASTS[] = "audio/mpeg";
$VALID_PODCASTS[] = "audio/x-m4a";
$VALID_PODCASTS[] = "video/mp4";
$VALID_PODCASTS[] = "video/x-m4v";
$VALID_PODCASTS[] = "video/quicktime";
$VALID_PODCASTS[] = "application/pdf";
$VALID_PODCASTS[] = "document/x-epub";

/**
 * Array containing valid name prefixes.
 */
$PROFILE_NAME_PREFIX = array();
$PROFILE_NAME_PREFIX[] = "Dr.";
$PROFILE_NAME_PREFIX[] = "Mr.";
$PROFILE_NAME_PREFIX[] = "Mrs.";
$PROFILE_NAME_PREFIX[] = "Ms.";
$PROFILE_NAME_PREFIX[] = "Prof.";
$PROFILE_NAME_PREFIX[] = "Assoc. Prof.";
$PROFILE_NAME_PREFIX[] = "Asst. Prof.";

/**
 * Array containing valid name generational suffixes.
 */
$PROFILE_NAME_SUFFIX_GEN = array();
$PROFILE_NAME_SUFFIX_GEN[] = "Jr.";
$PROFILE_NAME_SUFFIX_GEN[] = "Sr.";
$PROFILE_NAME_SUFFIX_GEN[] = "I";
$PROFILE_NAME_SUFFIX_GEN[] = "II";
$PROFILE_NAME_SUFFIX_GEN[] = "III";
$PROFILE_NAME_SUFFIX_GEN[] = "IV";
$PROFILE_NAME_SUFFIX_GEN[] = "V";
$PROFILE_NAME_SUFFIX_GEN[] = "VI";

/**
 * Array containing valid name post nominal suffixes.
 */
$PROFILE_NAME_SUFFIX_POST_NOMINAL = array();
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "DDS";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "DNP";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "EdD";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "MBA";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "MD";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "MPH";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "MSc";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "PhD";
$PROFILE_NAME_SUFFIX_POST_NOMINAL[] = "RN";

/**
 * Would you like to add the ability to web-proxy links? If not you can leave
 * these blank and the proxy ability will not be used.
 */
$PROXY_SUBNETS = array();
$PROXY_SUBNETS["default"] = array("start" => "130.15.0.0", "end" => "130.15.255.255", "exceptions" => array());
$PROXY_SUBNETS["library"] = array("start" => "130.15.0.0", "end" => "130.15.255.255", "exceptions" => array());

$PROXY_URLS = array();
$PROXY_URLS["default"]["active"] = "http://proxy.yourschool.ca/login?url=";
$PROXY_URLS["default"]["inactive"] = "";
$PROXY_URLS["library"]["active"] = "http://proxy.yourschool.ca/login?url=http://library.yourschool.ca";
$PROXY_URLS["library"]["inactive"] = "http://library.yourschool.ca";

/**
 * What type of file are you adding?
 */
$RESOURCE_CATEGORIES = array();
$RESOURCE_CATEGORIES["event"]["lecture_notes"] = "Lecture Notes";
$RESOURCE_CATEGORIES["event"]["lecture_slides"]	= "Lecture Slides";
$RESOURCE_CATEGORIES["event"]["podcast"] = "Podcast";
// @todo $RESOURCE_CATEGORIES["event"]["scorm"] = "SCORM Learning Object";
$RESOURCE_CATEGORIES["event"]["other"] = "Other / General File";

$RESOURCE_CATEGORIES["course"]["group"] = "Group Information";
$RESOURCE_CATEGORIES["course"]["podcast"] = "Podcast";
// @todo $RESOURCE_CATEGORIES["course"]["scorm"] = "SCORM Learning Object";
$RESOURCE_CATEGORIES["course"]["other"] = "Other / General File";

/**
 * This is currently selectable by the teacher; however, not displayed to the
 * student quite yet. It's purpose is the student knows when the resource
 * should actually be viewed / completed.
 */
$RESOURCE_TIMEFRAMES = array();
$RESOURCE_TIMEFRAMES["event"]["pre"] = "Prior To The Event";
$RESOURCE_TIMEFRAMES["event"]["during"] = "During The Event";
$RESOURCE_TIMEFRAMES["event"]["post"] = "After The Event";
$RESOURCE_TIMEFRAMES["event"]["none"] = "Not Applicable";
$RESOURCE_TIMEFRAMES["course"]["pre"] = "Prior To The Course";
$RESOURCE_TIMEFRAMES["course"]["during"] = "During The Course";
$RESOURCE_TIMEFRAMES["course"]["post"] = "After The Course";
$RESOURCE_TIMEFRAMES["course"]["none"] = "Not Applicable";

/**
 * This is the default notification message that is used in the Manage Users
 * module when someone is adding a new user to the system. It can be changed
 * by the admin that is adding the user via a textarea when the new user
 * is created.
 */
$DEFAULT_NEW_USER_NOTIFICATION = <<<USERNOTIFICATION
Dear %firstname% %lastname%,

A new account has just been created for you in %application_name%, our web-based integrated teaching and learning system.

Before logging in for the first time you will need to create a password for your account. You can do this by clicking the following link:

%password_reset_url%

Once your password has been set you can log into %application_name% by visiting the following link:

%application_url%

Username: %username%

If you require any assistance with this system, please do not hesitate to contact us.

Sincerely,

%application_name% Team
USERNOTIFICATION;

/**
 * This is the default notification message that is used in the Manage Users
 * module when someone is updating a user in the system. It can be changed
 * by the admin that is adding the user via a textarea when the new user
 * is created.
 */
$DEFAULT_EDIT_USER_NOTIFICATION = <<<USERNOTIFICATION
Dear %firstname% %lastname%,

Your account has been updated in %application_name% our web-based integrated teaching and learning system.

Before logging in, you may need to reset your password. You can do this by clicking the following link:

%password_reset_url%

You can log into %application_name% by visiting the following link:

%application_url%

Username: %username%

If you require any assistance with this system, please do not hesitate to contact us.

Sincerely,

%application_name% Team
USERNOTIFICATION;

/**
 * This is the default notification message that is sent to a new community guest user when the are imported
 * using the import-community-guests.php tool.
 */
$DEFAULT_NEW_GUEST_NOTIFICATION = <<<USERNOTIFICATION
Dear %firstname% %lastname%,

A new guest account has just been created for you in %application_name%, which gives you access to the %community_name% community.

Before logging in for the first time you will need to create a password for your account. You can do this by clicking the following link:

%password_reset_url%

Once your password has been set you can log into the %community_name% community by visiting the following link:

%community_url%

Username: %username%

If you require any assistance with this system, please do not hesitate to contact us.

Sincerely,

%application_name% Team
USERNOTIFICATION;

/**
 * These are nicer names for the modules, instead of the single word. This needs
 * to be made into XML and put each modules' directory.
 *
 * Also note, these are the names of the admin modules only at this time, not
 * the public ones, which needs to be changed.
 */
$MODULES = array();
$MODULES["annualreport"] = array("title" => "Annual Reports", "resource" => "annualreportadmin", "permission" => "read");
$MODULES["assessments"] = array("title" => "Assessment & Evaluation", "resource" => "assessments", "permission" => "update");
$MODULES["clinical"] = array("title" => "Clinical Experience", "resource" => "rotationschedule", "permission" => "update");
$MODULES["awards"] = array("title" => "Manage Awards", "resource" => "awards", "permission" => "update");
$MODULES["clerkship"] = array("title" => "Manage Clerkship", "resource" => "clerkship", "permission" => "update");
$MODULES["groups"] = array("title" => "Manage Cohorts", "resource" => "group", "permission" => "update");
$MODULES["communities"] = array("title" => "Manage Communities", "resource" => "communityadmin", "permission" => "read");
$MODULES["courses"] = array("title" => "Manage Courses", "resource"=> "coursecontent", "permission" => "update");
$MODULES["curriculum"] = array("title" => "Manage Curriculum", "resource"=> "curriculum", "permission" => "update");
$MODULES["eportfolio"] = array("title" => "Manage ePortfolios", "resource" => "eportfolio", "permission" => "update");
$MODULES["events"] = array("title" => "Manage Events", "resource" => "eventcontent", "permission" => "update");
$MODULES["exams"] = array("title" => "Manage Exams", "resource" => "examdashboard", "permission" => "read");
$MODULES["gradebook"] = array("title" => "Manage Gradebook", "resource" => "gradebook", "permission" => "update");
$MODULES["lor"] = array("title" => "Manage Learning Objects", "resource" => "lor", "permission" => "update");
$MODULES["mspr"] = array("title" => "Manage MSPRs", "resource" => "mspr", "permission" => "create");
$MODULES["notices"] = array("title" => "Manage Notices", "resource" => "notice", "permission" => "update");
$MODULES["observerships"] = array("title" => "Manage Observerships", "resource" => "observerships", "permission" => "update");
$MODULES["polls"] = array("title" => "Manage Polls", "resource" => "poll", "permission" => "update");
$MODULES["quizzes"] = array("title" => "Manage Quizzes", "resource" => "quiz", "permission" => "update");
$MODULES["users"] = array("title" => "Manage Users", "resource" => "user", "permission" => "update");
$MODULES["weeks"] = array("title" => "Manage Weeks", "resource" => "weekcontent", "permission" => "update", "enabled" => "curriculum_weeks_enabled");
$MODULES["regionaled"] = array("title" => "Regional Education", "resource" => "regionaled", "permission" => "update");
$MODULES["reports"] = array("title" => "System Reports", "resource" => "reportindex", "permission" => "read");
$MODULES["settings"] = array("title" => "System Settings", "resource" => "configuration", "permission" => "update");

/**
 * Registered Groups, Roles and Start Files for Administrative modules.
 *
 * Example usage:
 * $ADMINISTRATION[GROUP][ROLE] = array(
 *     "start_file" => "module",
 *     "registered" => array("courses", "weeks", "events", "users")
 * );
 */
$ADMINISTRATION = array();

$ADMINISTRATION["medtech"]["admin"]	= array(
    "start_file" => "notices",
    "registered" => array("courses", "weeks", "events", "notices", "clerkship", "quizzes", "reports", "users"),
    "assistant_support"	=> true
);

$ADMINISTRATION["faculty"]["director"] = array(
    "start_file" => "events",
    "registered" => array("courses", "weeks", "events", "notices", "quizzes"),
    "assistant_support" => true
);

$ADMINISTRATION["faculty"]["clerkship"] = array(
    "start_file" => "notices",
    "registered" => array("courses", "weeks", "events", "notices", "clerkship", "quizzes"),
    "assistant_support" => true
);

$ADMINISTRATION["faculty"]["admin"] = array(
    "start_file" => "notices",
    "registered" => array("courses", "weeks", "events", "notices", "quizzes", "reports"),
    "assistant_support" => true
);

$ADMINISTRATION["faculty"]["lecturer"] = array(
    "start_file" => "events",
    "registered" => array("events", "quizzes"),
    "assistant_support" => true
);

$ADMINISTRATION["resident"]["lecturer"]	= array(
    "start_file" => "events",
    "registered" => array("events", "quizzes"),
    "assistant_support"	=> false
);

$ADMINISTRATION["staff"]["admin"] = array(
    "start_file" => "notices",
    "registered" => array("courses", "weeks", "events", "notices", "clerkship", "quizzes", "reports", "users"),
    "assistant_support"	=> true
);

$ADMINISTRATION["staff"]["pcoordinator"] = array(
    "start_file" => "notices",
    "registered" => array("courses", "weeks", "events", "notices", "quizzes"),
    "assistant_support"	=> true
);

$ADMINISTRATION["staff"]["staff"] = array(
    "start_file" => "dashboard",
    "registered" => array("dashboard", "quizzes"),
    "assistant_support"	=> false
);


/**
 * Breadcrumb separator
 */
$BREADCRUMB_SEPARATOR = ">";

/**
 * These are the avialable character sets in both PHP and their cooresponding MySQL names and collation.
 */
$ENTRADA_CHARSETS = array();
$ENTRADA_CHARSETS["ISO-8859-1"] = array("description" => "Western European, Latin-1", "mysql_names" => "latin1", "mysql_collate" => "latin1_general_ci");
$ENTRADA_CHARSETS["UTF-8"] = array("description" => "ASCII compatible multi-byte 8-bit Unicode.", "mysql_names" => "utf8", "mysql_collate" => "utf8_general_ci");
$ENTRADA_CHARSETS["cp866"] = array("description" => "DOS-specific Cyrillic charset.", "mysql_names" => "cp866", "mysql_collate" => "cp866_general_ci");
$ENTRADA_CHARSETS["cp1251"] = array("description" => "Windows-specific Cyrillic charset.", "mysql_names" => "cp1251", "mysql_collate" => "cp1251_general_ci");
$ENTRADA_CHARSETS["cp1252"] = array("description" => "Windows specific charset for Western European.", "mysql_names" => "latin1", "mysql_collate" => "latin1_general_ci");
$ENTRADA_CHARSETS["KOI8-R"] = array("description" => "Russian.", "mysql_names" => "koi8r", "mysql_collate" => "koi8r_general_ci");
$ENTRADA_CHARSETS["BIG5"] = array("description" => "Traditional Chinese, mainly used in Taiwan.", "mysql_names" => "big5", "mysql_collate" => "big5_chinese_ci");
$ENTRADA_CHARSETS["GB2312"] = array("description" => "Simplified Chinese, national standard character set.", "mysql_names" => "gb2312", "mysql_collate" => "gb2312_chinese_ci");
$ENTRADA_CHARSETS["BIG5-HKSCS"] = array("description" => "Big5 with Hong Kong extensions, Traditional Chinese.", "mysql_names" => "big5", "mysql_collate" => "big5_chinese_ci");
$ENTRADA_CHARSETS["Shift_JIS"] = array("description" => "Japanese.", "mysql_names" => "sjis", "mysql_collate" => "sjis_japanese_ci");
$ENTRADA_CHARSETS["EUC-JP"] = array("description" => "Japanese.", "mysql_names" => "ujis", "mysql_collate" => "ujis_japanese_ci");

/**
 * Define the current reporting year for use withing the Annual Reporting Module - If the current month is between January and April then the current reporting
 * year is last year otherwise it is this year. This is because the due date for annual reports are due in February and March and often times faculty complete
 * them after the due date.
 *
 * Define other default "years" required by the Annual Reporting Module.
 */
$AR_CUR_YEAR = (date("Y") - ((date("n") < 5) ? 1 : 0));
$AR_NEXT_YEAR = (int) $AR_CUR_YEAR + 1;
$AR_PAST_YEARS = 1985;
$AR_FUTURE_YEARS = $AR_CUR_YEAR + 10;

/*
 * Reporting settings
 */
define("REPORTS_CALC_HALF_DAYS", false); // Used by teaching-report-by-department.inc.php, default is true

/**
 * Values used in the Clerkship lottery and schedule generation process
 */
define("CLERKSHIP_LOTTERY_RELEASE", strtotime("08:00:00 March 17th, 2014"));
define("CLERKSHIP_LOTTERY_MAX", 6);
define("CLERKSHIP_LOTTERY_START", strtotime("08:00:00 March 3rd, 2014"));
define("CLERKSHIP_LOTTERY_FINISH", strtotime("21:59:59 March 9th, 2014"));
define("CLERKSHIP_SCHEDULE_RELEASE", strtotime("15:00:00 April 4th, 2014"));
define("CLERKSHIP_SWAP_START", strtotime("March 30th, 2012"));
define("CLERKSHIP_SWAP_DEADLINE", strtotime("July 16th, 2012"));

/**
 * Used to cap the number of Clerkship rotations which are allowed in the system.
 */
define("MAX_ROTATION", 10);

/**
 * Defines for MSPR
 */
define("INTERNAL_AWARD_AWARDING_BODY","My University");
define("CLERKSHIP_COMPLETED_CUTOFF", "October 26");

define("MSPR_REJECTION_REASON_REQUIRED", true);         // defines whether a reason is required when rejecting a submission
define("MSPR_REJECTION_SEND_EMAIL", true);              // defines whether an email should be send on rejection of a student submission to their mspr

define("MSPR_CLERKSHIP_MERGE_NEAR", true);              // defines whether or not clerkship rotation with the same title should be merged if they are near in time.
define("MSPR_CLERKSHIP_MERGE_DISTANCE", "+1 week");     // defines how close together clerkship rotations with the SAME title need to be in order to be merged on the mspr display

define("AUTO_APPROVE_ADMIN_MSPR_EDITS", true);          // if true, the comment will be cleared, and the entry approved.
define("AUTO_APPROVE_ADMIN_MSPR_SUBMISSIONS", true);    // when adding to student submissions, admin contributions in these areas are automatically approved, if true.

define("PDF_PASSWORD", "MyPassword");                   // Used to set the owner password of the some PDF files.

/**
 * Gradebook Settings
 */
define("GRADEBOOK_DISPLAY_WEIGHTED_TOTAL", 1);          // Used to determine whether or not to include final grade calculations in Grade Export.
define("GRADEBOOK_DISPLAY_MEAN_GRADE", 1);              // Used to determine whether or not to include mean (average) grade calculations in student gradebook.
define("GRADEBOOK_DISPLAY_MEDIAN_GRADE", 1);            // Used to determine whether or not to include median grade calculations in student gradebook.

/**
 * File access timeout
 */
define("FILE_PUBLIC_ACCESS_TIMEOUT", 30);				// Used to determine the length of time a file can be accessible in the context of requiring temporary public access to that file.

/**
 * Learning Events Validation Constant
 */
define("LEARNING_EVENT_MIN_DURATION", 30);              // Used to determine the minimum event duration for validation when adding and editing learning events.
define("LEARNING_EVENT_DEFAULT_DURATION", 60);          // Used to determine the default event duration when adding event types to a learning event.

/**
 * Bookmark related constants
 */
$BOOKMARK_REMOVE_URL_TOKENS = array();                  // Defines a list of tokens to be stripped out of URLs when saving bookmarks.

/*
 * Curriculum mapping configuration settings
 */
define("EVENT_OBJECTIVES_SHOW_LINKS", 0);
define("WEEK_OBJECTIVES_SHOW_LINKS", 0);
define("COURSE_OBJECTIVES_SHOW_LINKS", 0);
define("OBJECTIVE_LINKS_VIEW_EXCLUDE", "");             // Defines the links between tag sets to get to course objective tag set
define("OBJECTIVE_LINKS_SEARCH_EXCLUDE", "");           // List the names of the tag sets in the string, separated by commas.
define("EVENT_ADMIN_TAG_QUICK_SEARCH", 0);
define("COURSE_ADMIN_TAG_QUICK_SEARCH", 0);

/**
 * Valid CBME objective code pattern settings
 */
define("CBME_EPA_PATTERN", "/^[A-Z]{1}[0-9]+$/");
define("CBME_KEY_COMPETENCY_PATTERN", "/^[A-Z]{2}[0-9]*$/");
define("CBME_ENABLING_COMPETENCY_PATTERN", "/^[A-Z]{2}[0-9]*[\\.][0-9]*$/");
define("CBME_MILESTONE_PATTERN", "/^[A-Z]{1}\\s[A-Z]{2}[0-9]*[\\.][0-9]*[\\.][0-9]*$/");
