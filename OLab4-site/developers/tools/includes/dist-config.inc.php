<?php
/**
 * Entrada Tools
 * Tools: Tools Config File
 *
 * This is the distribution's configuration file for the Entrada developer
 * tools. I would recommend that you simply copy your
 * entrada/www-root/includes/config.inc.php file to this directory as these
 * tools require much of the same data.
 *
 * The reason this is a separate file and not just a symlink is that this allows
 * you to test with different databases in your production environment before
 * actually running the tools.
 *
 * No matter which tool you are using, we highly recommend that you backup your
 * database and perhaps even your filesystem before running anything.
 *
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * $Id: dist-config.inc.php 1183 2010-05-05 13:51:59Z hbrundage $
 */

/**
 * DEVELOPMENT_MODE - Whether or not you want to run in development mode.
 * When in development mode only IP's that exist in the $DEVELOPER_IPS
 * array will be allowed to access the application. Others are directed to
 * the maintenance.html file.
 *
 */
define("DEVELOPMENT_MODE",		true);

/**
 * AUTH_DEVELOPMENT - If you would like to specify an alternative authetication
 * web-service URL for use during development you can do so here. If you leave
 * this blank it will use the AUTH_PRODUCTION URL you specify below.
 *
 * WARNING: Do not leave your development URL in here when you put this
 * into production.
 *
 */
define("AUTH_DEVELOPMENT",		"");

$DEVELOPER_IPS					= array();

define("ENTRADA_URL",			((isset($_SERVER["HTTPS"])) ? "https" : "http")."://localhost/projects/entrada/www-root");	// Full URL to application's index file without a trailing slash.
define("ENTRADA_RELATIVE",		"/projects/entrada/www-root");							// Absolute Path from the document_root to application's index file without a trailing slash.
define("ENTRADA_ABSOLUTE",		"/Users/username/Sites/projects/entrada/www-root");		// Full Directory Path to application's index file without a trailing slash.

define("COMMUNITY_URL",			ENTRADA_URL."/community");						// Full URL to the community directory without a trailing slash.
define("COMMUNITY_ABSOLUTE",	ENTRADA_ABSOLUTE."/community");					// Full Directory Path to the community directory without a trailing slash.
define("COMMUNITY_RELATIVE",	ENTRADA_RELATIVE."/community");					// Absolute Path from the document_root to the community without a trailing slash.

define("DATABASE_TYPE",			"mysql");										// Database Connection Type
define("DATABASE_HOST",			"localhost");									// The hostname or IP of the database server you want to connnect to.
define("DATABASE_NAME",			"entrada");										// The name of the database to connect to.
define("DATABASE_USER",			"entrada");										// A username that can access this database.
define("DATABASE_PASS",			"");											// The password for the username to connect to the database.

define("DATABASE_SESSIONS",		true);
define("SESSION_DATABASE_TYPE",	DATABASE_TYPE);									// Database Connection Type
define("SESSION_DATABASE_HOST",	DATABASE_HOST);									// The hostname or IP of the database server you want to connnect to.
define("SESSION_DATABASE_NAME",	"entrada_sessions");							// The name of the database to connect to.
define("SESSION_DATABASE_USER",	DATABASE_USER);									// A username that can access this database.
define("SESSION_DATABASE_PASS",	DATABASE_PASS);									// The password for the username to connect to the database.

define("CLERKSHIP_DATABASE",	"entrada_clerkship");							// The name of the database that stores the clerkship schedule information.
define("CLERKSHIP_SITE_TYPE", 	1);												// The value this application will use for site types in the clerkship logbook module. This will be removed/replaced by functional logic to decide which site type to use in the future - for now, leave this as 1.
define("CLERKSHIP_EMAIL_NOTIFICATIONS", 1);										// Whether email notifications will be sent out to the Program Coordinator of the Rotation's related course

define("AUTH_PRODUCTION",		ENTRADA_URL."/authentication/authenticate.php");

define("AUTH_APP_ID",			"1");											// Application ID for the MEdTech Authentication System.
define("AUTH_USERNAME",			"30000001");									// Application username to connect to the MEdTech Authentication System.
define("AUTH_PASSWORD",			"apple123");									// Application password to connect to the MEdTech Authentication System.

define("AUTH_METHOD",			"local");										// The method used to authenticate users into the application (local or ldap).
define("AUTH_DATABASE",			"entrada_auth");								// The name of the database that the authentication tables are located in. Must be able to connect to this using DATABASE_HOST, DATABASE_USER and DATABASE_PASS which are specified below.

define("AUTH_MAX_LOGIN_ATTEMPTS",	5);											// The number of login attempts a user can make before they are locked out of the system for the lockout duration
define("AUTH_LOCKOUT_TIMEOUT",		900);											// The amount of time in seconds a locked out user is prevented from logging in

define("PASSWORD_RESET_URL",	ENTRADA_URL."/password-reset.php");				// The URL that users are directed to if they have forgotten their password.
define("PASSWORD_CHANGE_URL",	ENTRADA_URL."/password-change.php");			// The URL that users are directed to if they wish to change their password.

define("AUTH_FORCE_SSL",		true);											// If you want to force all login attempts to use SSL, set this to true, otherwise false.

define("AUTH_ALLOW_CAS",		false);											// Whether or not you wish to allow CAS authorisation.
define("AUTH_CAS_HOSTNAME",		"cas.schoolu.ca");								// Hostname of your CAS server.
define("AUTH_CAS_PORT",			443);											// Port that CAS is running on.
define("AUTH_CAS_URI",			"cas");											// The URI where CAS is located on the CAS host.

define("AUTH_CAS_COOKIE",		"isCasOn");										// The name of the CAS cookie.
define("AUTH_CAS_SESSION",		"phpCAS");										// The session key set by phpCAS.
define("AUTH_CAS_ID",			"peopleid");									// The session key that holds the employee / student number.

define("SESSION_NAME",			"entrada");
define("SESSION_EXPIRES",		3600);

define("DEFAULT_TEMPLATE",		"default");										// This is the system template that will be loaded. System templates include language files, custom images, visual layouts, etc.
define("DEFAULT_LANGUAGE",		"en");											// This is the default language file that will be loaded. Language files must be placed in your DEFAULT_TEMPLATE."/languages directory. (i.e. en.lang.php)
define("DEFAULT_CHARSET",		"ISO-8859-1");									// The character encoding which will be used on the website & in e-mails.
define("DEFAULT_TIMEZONE",		"America/Toronto");								// The default timezone based on PHP's supported timezones http://ca3.php.net/manual/en/timezones.america.php
define("DEFAULT_COUNTRY_ID",	39);											// The default contry id used to determine provinces / states, etc.

define("DEFAULT_DATE_FORMAT",	"D M d/y g:ia");
define("DEFAULT_ROWS_PER_PAGE",	25);

define("ENCRYPTION_KEY",		"UXZF4tTES8RmTHY9qA7DQrvqEde7R5a8");			// Encryption key to encrypt data in the encrypted session ;)

/**
 * Google Analystics Tracking Code
 * Create an account at: http://www.google.com/analytics
 */
define("GOOGLE_ANALYTICS_CODE",	"UA-XXXXX-X");									// If you would like Google Analytics to track your usage (in production), then enter your tracking code.

/**
 * Goole Maps API Key
 * Generate your key from: http://code.google.com/apis/maps/
 */
define("GOOGLE_MAPS_API",		"http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=XXXXXXXXXXX");

/**
 * Used to cap the number of rotations which are allowed in the system.
 */
define("MAX_ROTATION", 10);

/**
 * Defines whether the system should allow communities to have mailing lists created for them,
 * and what type of mailing lists will be used (currently google is the only choice.)
 *
 */
$MAILING_LISTS						= array();
$MAILING_LISTS["active"]			= false;
$MAILING_LISTS["type"]				= "google";

/**
 * Google Hosted Apps Details
 * Signup for Google Apps at: http://www.google.com/apps/
 */
$GOOGLE_APPS						= array();
$GOOGLE_APPS["active"]				= false;
$GOOGLE_APPS["groups"]				= array();
$GOOGLE_APPS["admin_username"]		= "";
$GOOGLE_APPS["admin_password"]		= "";
$GOOGLE_APPS["domain"]				= "";
$GOOGLE_APPS["quota"]				= "7 GB";
$GOOGLE_APPS["new_account_subject"]	= "Activation Required: New %GOOGLE_APPS_DOMAIN% Account";
$GOOGLE_APPS["new_account_msg"]		= <<<GOOGLENOTIFICATION
Dear %FIRSTNAME% %LASTNAME%,

Good news! Your new %GOOGLE_APPS_DOMAIN% account has just been created, now you need to activate it!

Account Activation:
====================

To activate your %GOOGLE_APPS_DOMAIN% account, please follow these instructions:

1. Go to http://webmail.%GOOGLE_APPS_DOMAIN%

2. Enter your %APPLICATION_NAME% username and passowrd:

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
 * Weather Information provided by weather.com's XOAP service.
 * Register at: http://www.weather.com/services/xmloap.html
 *
 * After you register, customize the URL below with your key.
 *
 */
define("DEFAULT_WEATHER_FETCH",	"http://xoap.weather.com/weather/local/%LOCATIONCODE%?cc=*&link=xoap&prod=xoap&unit=m&par=1008727287&key=8e77e355b8662cda");	// The default weather image location that cron will fetch.

$WEATHER_LOCATION_CODES			= array("CAXX0225" => "Kingston, Ontario");		// These are the weather.com weather city / airport weather codes that are fetched and stored for use on the Dashboard.

define("LOG_DIRECTORY",			"/storage/logs");								// Full directory path to the logs directory without a trailing slash.

define("USE_CACHE",				false);											// true | false: Would you like to have the program cache frequently used database results on the public side?
define("CACHE_DIRECTORY",		"/storage/cache");								// Full directory path to the cache directory without a trailing slash.
define("CACHE_TIMEOUT",			30);											// Number of seconds that a general public query should be cached.
define("LONG_CACHE_TIMEOUT",	3600);											// Number of seconds that a less important / larger public query should be cached.
define("AUTH_CACHE_TIMEOUT",	3600);											// Number of seconds to use cache for on queries that query the Authentication Database.
define("RSS_CACHE_DIRECTORY",	CACHE_DIRECTORY);								// Full directory path to the cache directory without a trailing slash (for RSS).
define("RSS_CACHE_TIMEOUT",		300);											// Number of seconds that an RSS file will be cached.

define("COOKIE_TIMEOUT",		((time()) + (3600 * 24 * 365)));				// Number of seconds the cookie will be valid for. (default: ((time())+(3600*24*365)) = 1 year)

define("MAX_PRIVACY_LEVEL",		3);												// Select the max privacy level you accept.

define("MAX_UPLOAD_FILESIZE",	52428800);										// Maximum allowable filesize (in bytes) of a file that can be uploaded (52428800 = 50MB).

define("COMMUNITY_STORAGE_GALLERIES",	"/storage/community-galleries");		// Full directory path where the community gallery images are stored without trailing slash.
define("COMMUNITY_STORAGE_DOCUMENTS",	"/storage/community-shares");			// Full directory path where the community document shares are stored without trailing slash.

define("STORAGE_USER_PHOTOS",		"/storage/user-photos");						// Full directory path where user profile photos are stored without trailing slash.
define("FILE_STORAGE_PATH",			"/storage/event-files");						// Full directory path where off-line files are stored without trailing slash.

define("SENDMAIL_PATH",				"/usr/lib/sendmail -t -i");						// PRODUCTION: Full path and parametres to sendmail.

define("DEBUG_MODE",				true);											// Some places have extra debug code to show sample output. Set this to true if you want to see it.
define("SHOW_LOAD_STATS",			true);											// Do you want to see the time it takes to load each page?

define("APPLICATION_NAME",			"Entrada");									// The name of this application in your school (i.e. MedCentral, Osler, etc.)
define("APPLICATION_VERSION",		"0.8.5");									// The current version of this application.
define("APPLICATION_IDENTIFIER",	"app-".AUTH_APP_ID);						// PHP does not allow session key's to be integers (sometimes), so we have to make it a string.

$DEFAULT_META["title"]				= "Entrada: An eLearning Community";
$DEFAULT_META["keywords"]			= "course notes, schedule, schedules, community, communities, powerpoints, download, access, discussions, document sharing, announcements";
$DEFAULT_META["description"]		= "A place to gain access to your schedule, download related learning event content, and create communities within your school.";

define("COPYRIGHT_STRING",			"Copyright ".date("Y", time())." Entrada Project. All Rights Reserved.");

define("NOTIFY_ADMIN_ON_ERROR",		true);

define("ENABLE_NOTICES",			true);											// Do you want the dashboard notices to display or not?

$APPLICATION_PATH = array();
$APPLICATION_PATH["htmldoc"]		= "/usr/bin/htmldoc";

$AGENT_CONTACTS = array();
$AGENT_CONTACTS["administrator"]					= array("name" => "System Administrator", "email" => "support@yourschool.ca");
$AGENT_CONTACTS["general-contact"]					= array("name" => "Undergraduate Education", "email" => "support@yourschool.ca");
$AGENT_CONTACTS["agent-feedback"]					= array("name" => "System Administrator", "email" => "support@yourschool.ca");
$AGENT_CONTACTS["agent-notifications"]				= array("name" => "Undergraduate Education", "email" => "undergrad@yourschool.ca");
$AGENT_CONTACTS["agent-clerkship"]					= array("name" => "Clerkship Administrator", "email" => "undergrad@yourschool.ca", "director_ids" => array(0));
$AGENT_CONTACTS["agent-clerkship-international"]	= array("name" => "International Clerkship Administrator", "email" => "intlundergrad@yourschool.ca");
$AGENT_CONTACTS["agent-apartment"]		= array("name" => "Apartment Administrator", "email" => "apartments@yourschool.ca");

define("ADMINISTRATOR_NAME",			$AGENT_CONTACTS["administrator"]["name"]);		// Historical, should be cleaned up.
define("ADMINISTRATOR_EMAIL",			$AGENT_CONTACTS["administrator"]["email"]);		// Historical, should be cleaned up.
define("FEEDBACK_NAME",					$AGENT_CONTACTS["agent-feedback"]["name"]);		// Historical, should be cleaned up.
define("FEEDBACK_EMAIL",				$AGENT_CONTACTS["agent-feedback"]["email"]);	// Historical, should be cleaned up.

$COMMUNITY_RESERVED_PAGES				= array();										// Reserved names of pages (in lower case) so a user cannot create pages with these names.
$COMMUNITY_RESERVED_PAGES[]				= "home";
$COMMUNITY_RESERVED_PAGES[]				= "members";
$COMMUNITY_RESERVED_PAGES[]				= "pages";
$COMMUNITY_RESERVED_PAGES[]				= "search";
$COMMUNITY_RESERVED_PAGES[]				= "ics";
$COMMUNITY_RESERVED_PAGES[]				= "rss";

define("COMMUNITY_NOTIFY_TIMEOUT",		3600);                                      // Lock file expirary time
define("COMMUNITY_MAIL_LIST_MEMBERS_TIMEOUT",		1800);                                      // Lock file expirary time
define("COMMUNITY_NOTIFY_LOCK",         CACHE_DIRECTORY."/notify_mail.lck");    // Full directory path to the cache directory without a trailing slash (for RSS).
define("COMMUNITY_MAIL_LIST_MEMBERS_LOCK",         CACHE_DIRECTORY."/mail_list_members.lck");    // Full directory path to the cache directory without a trailing slash (for RSS).
define("COMMUNITY_NOTIFY_LIMIT",		100);                                       // Per batch email mailout limit
define("COMMUNITY_MAIL_LIST_MEMBERS_LIMIT",		100);                                       // Per batch google requests limit

define("COMMUNITY_NOTIFICATIONS_ACTIVE",     false);

/**
 * Array containing valid Podcast mime types as required by Apple.
 */
$VALID_PODCASTS						= array();
$VALID_PODCASTS[]					= "audio/mp3";
$VALID_PODCASTS[]					= "audio/mpeg";
$VALID_PODCASTS[]					= "audio/mpg";
$VALID_PODCASTS[]					= "audio/x-m4a";
$VALID_PODCASTS[]					= "video/mp4";
$VALID_PODCASTS[]					= "video/x-m4v";
$VALID_PODCASTS[]					= "video/quicktime";
$VALID_PODCASTS[]					= "application/pdf";

/**
 * Array containing valid name prefix's.
 */
$PROFILE_NAME_PREFIX				= array();
$PROFILE_NAME_PREFIX[]				= "Dr.";
$PROFILE_NAME_PREFIX[]				= "Mr.";
$PROFILE_NAME_PREFIX[]				= "Mrs.";
$PROFILE_NAME_PREFIX[]				= "Ms.";

/**
 * Would you like to add the ability to web-proxy links? If not you can leave
 * these blank and the proxy ability will not be used.
 */
$PROXY_SUBNETS						= array();
$PROXY_SUBNETS["library"]			= array("start" => "130.15.0.0", "end" => "130.15.255.255", "exceptions" => array("130.15.126.81"));

$PROXY_URLS							= array();
$PROXY_URLS["library"]["active"]	= "http://proxy.yourschool.ca/login?url=http://library.yourschool.ca";
$PROXY_URLS["library"]["inactive"]	= "http://library.yourschool.ca";

/**
 * What type of file are you adding?
 */
$RESOURCE_CATEGORIES							= array();
$RESOURCE_CATEGORIES["event"]["lecture_notes"]	= "Lecture Notes";
$RESOURCE_CATEGORIES["event"]["lecture_slides"]	= "Lecture Slides";
$RESOURCE_CATEGORIES["event"]["podcast"]		= "Podcast";
// @todo $RESOURCE_CATEGORIES["event"]["scorm"]	= "SCORM Learning Object";
$RESOURCE_CATEGORIES["event"]["other"]			= "Other / General File";

$RESOURCE_CATEGORIES["course"]["group"]			= "Group Information";
$RESOURCE_CATEGORIES["course"]["podcast"]		= "Podcast";
// @todo $RESOURCE_CATEGORIES["course"]["scorm"]	= "SCORM Learning Object";
$RESOURCE_CATEGORIES["course"]["other"]			= "Other / General File";

/**
 * This is currently selectable by the teacher; however, not displayed to the
 * student quite yet. It's purpose is the student knows when the resource
 * should actually be viewed / completed.
 */
$RESOURCE_TIMEFRAMES							= array();
$RESOURCE_TIMEFRAMES["event"]["pre"]			= "Prior To The Event";
$RESOURCE_TIMEFRAMES["event"]["during"]			= "During The Event";
$RESOURCE_TIMEFRAMES["event"]["post"]			= "After The Event";
$RESOURCE_TIMEFRAMES["event"]["none"]			= "Not Applicable";

$RESOURCE_TIMEFRAMES["course"]["pre"]			= "Prior To The Course";
$RESOURCE_TIMEFRAMES["course"]["during"]		= "During The Course";
$RESOURCE_TIMEFRAMES["course"]["post"]			= "After The Course";
$RESOURCE_TIMEFRAMES["course"]["none"]			= "Not Applicable";

/**
 * This is not currently used, but the theory is that we _may_ want to restrict
 * what types of files people can upload, depending on which resource_category
 * the teacher chooses to upload.
 */
$CATEGORY_FILETYPES								= array();
$CATEGORY_FILETYPES["other"]					= array("pdf", "gif", "jpg", "png", "zip", "exe", "html", "mpg", "swf", "mov", "mp3", "aac", "m4a", "txt", "rtf", "pps", "ppt", "doc", "xls", "docx", "xlsx", "pptx", "ppsx");
$CATEGORY_FILETYPES["lecture_notes"]			= array("pdf", "gif", "jpg", "png", "zip", "exe", "html", "mpg", "swf", "mov", "mp3", "aac", "m4a", "txt", "rtf", "pps", "ppt", "doc", "xls", "docx", "xlsx", "pptx", "ppsx");
$CATEGORY_FILETYPES["lecture_slides"]			= array("pdf", "gif", "jpg", "png", "zip", "exe", "html", "mpg", "swf", "mov", "mp3", "aac", "m4a", "txt", "rtf", "pps", "ppt", "doc", "xls", "docx", "xlsx", "pptx", "ppsx");
$CATEGORY_FILETYPES["podcast"]					= array("mp3", "aac", "m4a");
$CATEGORY_FILETYPES["learning_object"]			= array("zip");

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

If you require any assistance with this system, please do not hesitate to contact us:

Central Education Office
E-Mail: undergrad@yourschool.ca
Telephone: +1 (613) 533-6000 x2494

Sincerely,

Central Education Office
undergrad@yourschool.ca
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

If you require any assistance with this system, please do not hesitate to contact us:

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
$MODULES				= array();
$MODULES["notices"]		= array('title' => "Manage Notices", 'resource' => 'notice', 'permission' => 'update');
$MODULES["polls"]		= array('title' => "Manage Polls", 'resource' => 'poll', 'permission' => 'update');
$MODULES["courses"]		= array('title' => "Manage Courses", 'resource'=> 'coursecontent', 'permission' => 'update');
$MODULES["events"]		= array('title' => "Manage Events", 'resource' => 'eventcontent', 'permission' => 'update');
$MODULES["clerkship"]	= array('title' => "Manage Clerkship", 'resource' => 'clerkship', 'permission' => 'update');
$MODULES["reports"]		= array('title' => "System Reports", 'resource' => 'reportindex', 'permission'=>'read');
$MODULES["users"]		= array('title' => "Manage Users", 'resource' => 'user', 'permission' => 'update');
$MODULES["quizzes"]		= array('title' => "Manage Quizzes", 'resource' => 'quiz', 'permission' => 'update');
$MODULES["regionaled"]	= array('title' => "Regional Education", 'resource' => 'regionaled', 'permission' => 'update');

define("MAX_NAV_TABS", 10); //The maxium number of navigation tabs shown to users on every page. Extras will go into a "More" dropdown tab.

/**
 * System groups define which system groups & role combinations are allowed to
 * access this system. Note the student and alumni groups have many roles.
 */
$SYSTEM_GROUPS				= array();
for($i = (date("Y", time()) + ((date("m", time()) < 7) ?  3 : 4)); $i >= 2004; $i--) {
	$SYSTEM_GROUPS["student"][] = $i;
}
for($i = (date("Y", time()) + ((date("m", time()) < 7) ?  3 : 4)); $i >= 1997; $i--) {
	$SYSTEM_GROUPS["alumni"][] = $i;
}
$SYSTEM_GROUPS["faculty"]	= array("faculty", "lecturer", "director", "admin");
$SYSTEM_GROUPS["resident"]	= array("resident", "lecturer");
$SYSTEM_GROUPS["staff"]		= array("staff", "admin", "pcoordinator");
$SYSTEM_GROUPS["medtech"]	= array("staff", "admin");
$SYSTEM_GROUPS["guest"]		= array("communityinvite");

/*	Registered Groups, Roles and Start Files for Administrative modules.
	Example usage:
	$ADMINISTRATION[GROUP][ROLE]	=	array(
									"start_file" => "module",
									"registered"	=> array("courses", "events", "users")
									);
*/
$ADMINISTRATION							= array();
$ADMINISTRATION["medtech"]["admin"]		= array(
											"start_file"		=> "notices",
											"registered"		=> array("courses", "events", "notices", "clerkship", "quizzes", "reports", "users"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["faculty"]["director"]	= array(
											"start_file"		=> "events",
											"registered"		=> array("courses", "events", "notices", "quizzes"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["faculty"]["clerkship"]	= array(
											"start_file"		=> "notices",
											"registered"		=> array("courses", "events", "notices", "clerkship", "quizzes"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["faculty"]["admin"]		= array(
											"start_file"		=> "notices",
											"registered"		=> array("courses", "events", "notices", "quizzes", "reports"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["faculty"]["lecturer"]	= array(
											"start_file"		=> "events",
											"registered"		=> array("events", "quizzes"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["resident"]["lecturer"]	= array(
											"start_file"		=> "events",
											"registered"		=> array("events", "quizzes"),
											"assistant_support"	=> false
										);

$ADMINISTRATION["staff"]["admin"]		=	array(
											"start_file"		=> "notices",
											"registered"		=> array("courses", "events", "notices", "clerkship", "quizzes", "reports", "users"),
											"assistant_support"	=> true
										);

$ADMINISTRATION["staff"]["pcoordinator"] =	array(
											"start_file"		=> "notices",
											"registered"		=> array("courses", "events", "notices", "quizzes"),
											"assistant_support"	=> true
										);

$CLERKSHIP_REQUIRED_WEEKS 		= 14;
$CLERKSHIP_CATEGORY_TYPE_ID		= 13;
$CLERKSHIP_EVALUATION_FORM 		= "http://url_of_your_schools_precptor_evaluation_of_clerk_form.pdf";
$CLERKSHIP_INTERNATIONAL_LINK	= "http://url_of_your_schools_international_activities_procedures";

/**
 * This is the HTML that is used to render external pages that contain errors,
 * notices, etc.
 */
$EXTERNAL_HTML  = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
$EXTERNAL_HTML .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
$EXTERNAL_HTML .= "<head>\n";
$EXTERNAL_HTML .= "	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".DEFAULT_CHARSET."\" />\n";
$EXTERNAL_HTML .= "	<title>%TITLE%</title>\n";
$EXTERNAL_HTML .= "	<link href=\"".ENTRADA_URL."/css/common.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
$EXTERNAL_HTML .= "	<link href=\"".ENTRADA_URL."/images/favicon.ico\" rel=\"shortcut icon\" type=\"image/x-icon\" />\n";
$EXTERNAL_HTML .= "</head>\n";
$EXTERNAL_HTML .= "<body>\n";
$EXTERNAL_HTML .= "<div style=\"width: 600px; margin: 50px;\">\n";
$EXTERNAL_HTML .= "%BODY%\n";
$EXTERNAL_HTML .= "</div>\n";
$EXTERNAL_HTML .= "</body>\n";
$EXTERNAL_HTML .= "</html>\n";

$CLERKSHIP_FIELD_STATUS						= array();
$CLERKSHIP_FIELD_STATUS["published"]		= array("name" => "Published", "visible" => true);
$CLERKSHIP_FIELD_STATUS["draft"]			= array("name" => "Draft", "visible" => true);
$CLERKSHIP_FIELD_STATUS["approval"]			= array("name" => "Awaiting Approval", "visible" => false);
$CLERKSHIP_FIELD_STATUS["trash"]			= array("name" => "Trash", "visible" => false);
$CLERKSHIP_FIELD_STATUS["cancelled"]		= array("name" => "Cancelled", "visible" => false);