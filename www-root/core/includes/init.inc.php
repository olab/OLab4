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
 * The default init file that includes all common Entrada includes.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@ini_set("filter.default_flags", FILTER_FLAG_NO_ENCODE_QUOTES);

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("config/settings.inc.php");

header("X-Frame-Options: SAMEORIGIN");

require_once("functions.inc.php");
require_once("dbconnection.inc.php");
require_once("cache.inc.php");
require_once("Classes/users/User.class.php");

$ENTRADA_LOGGER = new Models_Logger();

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
	$ENTRADA_USER = User::get($_SESSION["details"]["id"]);
} else {
	$ENTRADA_USER = false;
}

require_once("acl.inc.php");

/**
 * If Entrada is in development mode and the user is not a developer send them to the
 * notavailable.html file.
 */
if (defined("DEVELOPMENT_MODE") && (bool) DEVELOPMENT_MODE) {
	if (!is_array($DEVELOPER_IPS) || !in_array($_SERVER["REMOTE_ADDR"], $DEVELOPER_IPS)) {
		header("Location: ".ENTRADA_URL."/maintenance.html");
		exit;
	}
}

/**
 * Initialize the interface template.
 */
$ENTRADA_TEMPLATE = new Entrada_Template();

/**
 * Initialize the settings class.
 */
$ENTRADA_SETTINGS = new Entrada_Settings();


if ($ENTRADA_USER) {
    /**
     * Check whether we are using the organisation_id + ua_id from the URL, from
     * user preferences, or the default.
     */
    if (isset($_GET["organisation_id"]) && clean_input($_GET["organisation_id"], "int") && isset($_GET["ua_id"]) && clean_input($_GET["ua_id"], "int")) {
        $organisation_id = clean_input($_GET["organisation_id"], "int");
        $user_access_id = clean_input($_GET["ua_id"], "int");
        application_log("success", "User [".$ENTRADA_USER->getId()."] loaded organisation [".$organisation_id."] and access_id [".$user_access_id."] successfully.");
    } else {
        $organisation_id = 0;
        $user_access_id = 0;
    }

    /**
     * Set the active organisation profile for the user.
     */
    load_active_organisation($organisation_id, $user_access_id);
}

/**
 * Setup Zend_Translate for language file support.
 */
if ($ENTRADA_CACHE) {
    Entrada_Translate::setCache($ENTRADA_CACHE);
}

$translate = new Entrada_Translate(
    array (
        "adapter" => "array",
		"disableNotices" => (DEVELOPMENT_MODE ? false : true)
    )
);

/**
 * The auto detection of translations only works when using the addTranslation() method;
 */
$translate->addTranslation(
	array(
		'adapter' => 'array',
		'content' => ENTRADA_ABSOLUTE . "/templates/" . $ENTRADA_TEMPLATE->activeTemplate() . "/languages",
		'locale'  => 'auto',
		"scan" => Entrada_Translate::LOCALE_FILENAME
	)
);
$DEFAULT_TEXT_LABELS = $translate->_("default");

$ADODB_CACHE_DIR = CACHE_DIRECTORY;
$time_start = getmicrotime();

$ERROR = 0;
$ERRORSTR = array();

$NOTICE = 0;
$NOTICESTR = array();

$SUCCESS = 0;
$SUCCESSSTR = array();

$GENERIC = 0;
$GENERICSTR = array();

$BREADCRUMB = array();
$HEAD = array();
$ONLOAD = array();
$JAVASCRIPT_TRANSLATIONS = array();
$ONUNLOAD = array();
$JQUERY = array();
$SIDEBAR = array();
$PAGE_META = array();

//declare some global variables meant to be always available to javascript.
if ($ENTRADA_USER) {
    $HEAD[] = "<script type=\"text/javascript\">
                    var default_org_id = '".$ENTRADA_USER->getActiveOrganisation()."';
                </script>";
}

$SSO_AUTHENTICATED = false;
$SSO_ERROR = false;

$MODULE = "login";
$SECTION = "index";
$ACTION = "";
$STEP = 1;
$PROCESSED = array();

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
	$PROXY_ID = $ENTRADA_USER->getID();
	$GROUP = $ENTRADA_USER->getActiveGroup();
	$ROLE = $ENTRADA_USER->getActiveRole();
} else {
	$PROXY_ID = 0;
	$GROUP = "";
	$ROLE = "";
}

/**
 * Allows you to specify via get or post, which component of the particular
 * module you would like to load (i.e. index, add, edit, delete, etc).
 */
if (isset($_GET["section"]) && ($tmp_input = clean_input($_GET["section"], array("nows", "url")))) {
	$SECTION = $tmp_input;
} elseif (isset($_POST["section"]) && ($tmp_input = clean_input($_POST["section"], array("nows", "url")))) {
	$SECTION = $tmp_input;
}

/**
 * Additional variable which allows allows you to specify via get or post,
 * which action within a particular module component you would like to run
 * (i.e. http:// ... /admin/events?section=add&action=faculty)
 */
if (isset($_GET["action"]) && ($tmp_input = clean_input($_GET["action"], array("nows", "url")))) {
	$ACTION = $tmp_input;
} elseif (isset($_POST["action"]) && ($tmp_input = clean_input($_POST["action"], array("nows", "url")))) {
	$ACTION = $tmp_input;
}

/**
 * Allows you to specify which step you are on within a particular module
 * component (i.e. http:// ... /admin/events?section=add&step=2).
 */
if (isset($_GET["step"]) && ($tmp_input = clean_input($_GET["step"], "int"))) {
	$STEP = $tmp_input;
} elseif (isset($_POST["step"]) && ($tmp_input = clean_input($_POST["step"], "int"))) {
	$STEP = $tmp_input;
}