<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * The web-based Entrada setup utility.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <jellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * Originally:
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Register the Composer autoloader.
 */
require_once("autoload.php");

require_once("includes/functions.inc.php");
require_once("includes/constants.inc.php");

$ERROR = 0;
$TOTAL_ERRORS = 0;
$NOTICE = 0;
$SUCCESS = 0;

$ERRORSTR = array();
$NOTICESTR = array();
$SUCCESSSTR = array();

if ((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
	$STEP = (int) trim($_GET["step"]);
} elseif ((isset($_POST["step"])) && ((int) trim($_POST["step"]))) {
	$STEP = (int) trim($_POST["step"]);
} else {
	$STEP = 1;
}

/**
 * A list of valid database adapters that Entrada can utilize.
 */
$DATABASE_ADAPTERS = array(
	"mysqli" => "MySQL Improved",
	"pdo_mysql" => "PDO MySQL"
);

/**
 * Just used for reference.
 */
$PROCESSED = array(
	"auth_username" => "",
	"auth_password" => "",
	"entrada_url" => "",
	"entrada_relative" => "",
	"entrada_absolute" => "",
	"entrada_storage" => "",
	"database_adapter" => "",
	"database_host" => "",
	"database_username" => "",
	"database_password" => "",
	"entrada_database" => "",
	"auth_database" => "",
	"openlabyrinth_database" => "",
	"clerkship_database" => "",
	"admin_username" => "",
	"admin_password_hash" => "",
    "admin_password_salt" => "",
	"admin_firstname" => "",
	"admin_lastname" => "",
	"admin_email" => ""
);

$PROCESSED = array();

/**
 * Error Checking: Content Validation
 */
switch ($STEP) {
	case 6 :
	case 5 :
		if (isset($_POST["admin_firstname"]) && ($admin_firstname = clean_input($_POST["admin_firstname"], "trim"))) {
			$PROCESSED["admin_firstname"] = $admin_firstname;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The first name of the administrator for your install of Entrada must be entered before continuing.";
		}

		if (isset($_POST["admin_lastname"]) && ($admin_lastname = clean_input($_POST["admin_lastname"], "trim"))) {
			$PROCESSED["admin_lastname"] = $admin_lastname;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The last name of the administrator for your install of Entrada must be entered before continuing.";
		}

		if (isset($_POST["admin_email"]) && ($admin_email = clean_input($_POST["admin_email"], array("trim", "lower"))) && @valid_address($admin_email)) {
			$PROCESSED["admin_email"] = $admin_email;
		} else {
			$ERROR++;
			$ERRORSTR[] = "A valid E-mail for the administrator of your install of Entrada must be entered before continuing.";
		}

		if (isset($_POST["admin_username"]) && ($admin_username = clean_input($_POST["admin_username"], "credentials"))) {
			$PROCESSED["admin_username"] = $admin_username;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The username of the administrator for your install of Entrada must be entered before continuing.";
		}

		if (isset($_POST["admin_password"]) && ($admin_password = $_POST["admin_password"])) {
			if (isset($_POST["re_admin_password"]) && ($re_admin_password = $_POST["re_admin_password"]) && $re_admin_password == $admin_password) {
			    $PROCESSED["admin_password_salt"] = hash("sha256", (uniqid(rand(), 1) . time()));
				$PROCESSED["admin_password_hash"] = sha1($admin_password . $PROCESSED["admin_password_salt"]);
			} else {
				$ERROR++;
				$ERRORSTR[] = "The two passwords you have entered for the administrator of your install of Entrada must match before continuing, please re-enter them now.";
			}
		} elseif (isset($_POST["admin_password_hash"]) && ($admin_password_hash = clean_input($_POST["admin_password_hash"], "alphanumeric")) &&
                  isset($_POST["admin_password_salt"]) && ($admin_password_salt = clean_input($_POST["admin_password_salt"], "alphanumeric"))) {
            $PROCESSED["admin_password_hash"] = $admin_password_hash;
            $PROCESSED["admin_password_salt"] = $admin_password_salt;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The password of the administrator for your install of Entrada must be entered before continuing.";
		}

		if ($ERROR && ($ERROR > $TOTAL_ERRORS)) {
			$TOTAL_ERRORS = $ERROR;

			$STEP = 4;
		}
	case 4 :
		if (isset($_POST["database_adapter"]) && ($database_adapter = clean_input($_POST["database_adapter"], "credentials")) && array_key_exists($database_adapter, $DATABASE_ADAPTERS)) {
			$PROCESSED["database_adapter"] = $database_adapter;
		} else {
			$ERROR++;
			$ERRORSTR[] = "A valid database type must be selected before being able to continue.";
		}

		if (isset($_POST["database_host"]) && ($database_host = clean_input($_POST["database_host"], "url"))) {
			$PROCESSED["database_host"] = $database_host;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The host where the entrada databases will be accessed from must be entered before continuing.";
		}

		if (isset($_POST["database_username"]) && ($database_username = clean_input($_POST["database_username"], "credentials"))) {
			$PROCESSED["database_username"] = $database_username;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The username to connect to the Entrada databases must be entered before continuing.";
		}

		if (isset($_POST["database_password"]) && ($database_password = $_POST["database_password"])) {
			$PROCESSED["database_password"] = $database_password;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The password to connect to the Entrada databases must be entered before continuing.";
		}

		if (isset($_POST["entrada_database"]) && ($entrada_database = clean_input($_POST["entrada_database"], "credentials"))) {
			$PROCESSED["entrada_database"] = $entrada_database;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The name of the primary Entrada database must be entered before continuing.";
		}

		if (isset($_POST["auth_database"]) && ($auth_database = clean_input($_POST["auth_database"], "credentials"))) {
			$PROCESSED["auth_database"] = $auth_database;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The name of the Entrada Authentication database must be entered before continuing.";
		}

		if (isset($_POST["openlabyrinth_database"]) && ($openlabyrinth_database = clean_input($_POST["openlabyrinth_database"], "credentials"))) {
			$PROCESSED["openlabyrinth_database"] = $openlabyrinth_database;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The name of the primary OLab4 database must be entered before continuing.";
		}

		if (isset($_POST["clerkship_database"]) && ($clerkship_database = clean_input($_POST["clerkship_database"], "credentials"))) {
			$PROCESSED["clerkship_database"] = $clerkship_database;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The name of the Entrada Clerkship database must be edntered before continuing.";
		}

		
		if ($ERROR && ($ERROR > $TOTAL_ERRORS)) {
			$TOTAL_ERRORS = $ERROR;

			$STEP = 3;
		}
	case 3 :
		if (isset($_POST["entrada_url"]) && ($url_parts = parse_url($_POST["entrada_url"])) && ($url_scheme = $url_parts["scheme"]) && ($tmp_url = str_replace($url_scheme . "://", "", $_POST["entrada_url"])) && ($url = ($url_scheme ? $url_scheme : "http") . "://" . clean_input($tmp_url, "url"))) {
			$PROCESSED["entrada_url"] = $url;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The URL where this instance of Entrada will be accessed must be entered before continuing.";
		}

		if (isset($_POST["entrada_relative"]) && ($entrada_relative = clean_input($_POST["entrada_relative"], "url"))) {
			$PROCESSED["entrada_relative"] = $entrada_relative;
		} else {
			$PROCESSED["entrada_relative"] = "";
		}

		if (isset($_POST["entrada_absolute"]) && ($entrada_absolute = clean_input($_POST["entrada_absolute"], "dir"))) {
			$PROCESSED["entrada_absolute"] = $entrada_absolute;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The absolute directory path on the server where Entrada will be installed must be entered before continuing.";
		}

		if (isset($_POST["entrada_storage"]) && ($entrada_storage = clean_input($_POST["entrada_storage"], "dir")) && (@is_dir($entrada_storage))) {
			$PROCESSED["entrada_storage"] = $entrada_storage;

			if (!@is_writable($entrada_storage."/annualreports") ||
                !@is_writable($entrada_storage."/app")  ||
                !@is_writable($entrada_storage."/app/public")  ||
				!@is_writable($entrada_storage."/cache") ||
                !@is_writable($entrada_storage."/cbme-uploads") ||
				!@is_writable($entrada_storage."/community-discussions") ||
				!@is_writable($entrada_storage."/community-galleries") ||
				!@is_writable($entrada_storage."/community-shares") ||
                !@is_writable($entrada_storage."/eportfolio") ||
				!@is_writable($entrada_storage."/event-files") ||
				!@is_writable($entrada_storage."/exam-files") ||
                !@is_writable($entrada_storage."/framework")  ||
                !@is_writable($entrada_storage."/framework/cache") ||
                !@is_writable($entrada_storage."/framework/cache/data") ||
                !@is_writable($entrada_storage."/framework/sessions") ||
                !@is_writable($entrada_storage."/framework/views") ||
				!@is_writable($entrada_storage."/logs") ||
				!@is_writable($entrada_storage."/lor") ||
				!@is_writable($entrada_storage."/msprs") ||
				!@is_writable($entrada_storage."/resource-images") ||
				!@is_writable($entrada_storage."/secure-access") ||
				!@is_writable($entrada_storage."/syllabi") ||
				!@is_writable($entrada_storage."/user-photos")) {

				$ERROR++;
				$i = count($ERROR);

				$ERRORSTR[$i]  = "At least one of the directories in your stoage directory is not writable, please run the following commands:";
				$ERRORSTR[$i] .= "<div style=\"font-family: monospace; font-size: 9px\">\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/annualreports<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/app<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/app/public<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/cache<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/cbme-uploads<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/community-discussions<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/community-galleries<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/community-shares<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/eportfolio<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/event-files<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/exam-files<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/framework<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/framework/cache<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/framework/cache/data<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/framework/sessions<br />\n";
                $ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/framework/views<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/logs<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/lor<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/msprs<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/resource-images<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/secure-access<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/syllabi<br />\n";
				$ERRORSTR[$i] .= "chmod 777 ".$entrada_storage."/user-photos<br />\n";
				$ERRORSTR[$i] .= "</div>\n";
			}
		} elseif (!@is_dir($entrada_storage)) {
			$ERROR++;
			$ERRORSTR[] = "The absolute path you have provided for the <strong>Entrada Storage Path</strong> does not not exist. Please ensure this directory exists and that all folders within it can be written to by PHP.";
		} else {
			$ERROR++;
			$ERRORSTR[] = "The absolute directory path on the server where Entrada storage will be located must be entered before continuing.";
		}

		if ($ERROR && ($ERROR > $TOTAL_ERRORS)) {
			$TOTAL_ERRORS = $ERROR;

			$STEP = 2;
		}
	case 2 :
		/**
		 * Keys to allow Entrada to access the authentication web-service.
		 */
		if (isset($_POST["auth_username"]) && ($auth_username = clean_input($_POST["auth_username"], "alphanumeric"))) {
			$PROCESSED["auth_username"] = $auth_username;
		} else {
			$PROCESSED["auth_username"] = generate_hash();
		}

		if (isset($_POST["auth_password"]) && ($auth_password = clean_input($_POST["auth_password"], "alphanumeric"))) {
			$PROCESSED["auth_password"] = $auth_password;
		} else {
			$PROCESSED["auth_password"] = generate_hash();
		}
	case 1 :
	default :
		continue;
	break;
}

$setup = new Entrada_Setup($PROCESSED);

/**
 * Post-Error Check Data Processing
 */
switch ($STEP) {
	case 6 :
		if (@file_exists($PROCESSED["entrada_absolute"]."/.htaccess")) {
			if (@file_exists($PROCESSED["entrada_absolute"]."/core/config/config.inc.php")) {
				try {
					if (!$setup->loadDumpData()) {
						$ERROR++;
						$ERRORSTR[] = $setup->database_error;
					}
				} catch(Exception $e) {
					$ERROR++;
				}
			} else {
				$config_text = $setup->outputConfigData();

				$display_config = true;

				$ERROR++;
				$ERRORSTR[] = "Please make sure that you have saved the <strong>config.inc.php</strong> file before continuing.";
			}
		} else {
			$display_htaccess = true;

			$ERROR++;
			$ERRORSTR[] = "Please make sure that you have saved the <strong>.htaccess</strong> file before continuing.";
		}

		if ($ERROR && ($ERROR > $TOTAL_ERRORS)) {
			$TOTAL_ERRORS = $ERROR;

			$STEP = 5;
		}
	break;
	case 5 :
		if ((!isset($_POST["htaccess_text"]) || !$_POST["htaccess_text"]) && !$setup->writeHTAccess()) {
			$display_htaccess = true;
		} else {
			$display_htaccess = false;
		}

		if ((!isset($_POST["config_text"]) || !$_POST["config_text"]) && !$setup->writeConfigData()) {
			$config_text = $setup->outputConfigData();

			$display_config = true;
		} else {
			$display_config = false;
		}
	break;
	case 4 :
		/**
		 * Test the provided database connection information.
		 */
		if (!$setup->checkEntradaDBConnection()) {
			$ERROR++;
			$ERRORSTR[] = "We were unable to connect to your primary <strong>Entrada Database</strong> [".(isset($PROCESSED["entrada_database"]) ? $PROCESSED["entrada_database"] : "")."].";
		}

		if (!$setup->checkAuthDBConnection()) {
			$ERROR++;
			$ERRORSTR[] = "We were unable to connect to your <strong>Authentication Database</strong> [".(isset($PROCESSED["auth_database"]) ? $PROCESSED["auth_database"] : "")."].";
		}

		if (!$setup->checkOLab4DBConnection()) {
			$ERROR++;
			$ERRORSTR[] = "We were unable to connect to your <strong>OLab4 Database</strong> [".(isset($PROCESSED["openlabyrinth_database"]) ? $PROCESSED["openlabyrinth_database"] : "")."].";
		}

		if (!$setup->checkClerkshipDBConnection()) {
			$ERROR++;
			$ERRORSTR[] = "We were unable to connect to your <strong>Clerkship Database</strong> [".(isset($PROCESSED["clerkship_database"]) ? $PROCESSED["clerkship_database"] : "")."].";
		}
	
		if ($ERROR && ($ERROR > $TOTAL_ERRORS)) {
			$TOTAL_ERRORS = $ERROR;

			$STEP = 3;
		}
	break;
	default :
		continue;
	break;
}

$absolute_url = 'http';
if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
	$absolute_url .= "s";
}
$absolute_url .= "://";
if ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" && $_SERVER["SERVER_PORT"] != "443") || (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "on" && $_SERVER["SERVER_PORT"] != "80")) {
	$absolute_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].implode("/", array_pop(explode("/", $_SERVER["REQUEST_URI"])));
} else {
	$absolute_url .= $_SERVER["SERVER_NAME"].implode("/", array_slice(explode("/", $_SERVER["REQUEST_URI"]), 0, (count(explode("/", $_SERVER["REQUEST_URI"])) - 2)));
}

$relative_url = implode("/", array_slice(explode("/", $_SERVER["REQUEST_URI"]), 0, (count(explode("/", $_SERVER["REQUEST_URI"])) - 2)));

$absolute_path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']), 0, (count(explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME'])) - 2)) );

$storage_path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']), 0, (count(explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME'])) - 2)) )."/core/storage";
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<title>Entrada: Setup</title>
	<link href="../templates/default/css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link href="../templates/default/css/style.css" rel="stylesheet" type="text/css" media="all" />
	<script type="text/javascript" src="../javascript/scriptaculous/prototype.js"></script>
	<script type="text/javascript" src="../javascript/scriptaculous/scriptaculous.js"></script>
	<script type="text/javascript" src="../javascript/jquery/jquery.min.js"></script>
	<script type="text/javascript">jQuery.noConflict();</script>
	<script type="text/javascript" src="../templates/default/js/libs/bootstrap.min.js"></script>
    <script type="text/javascript" src="../templates/default/js/libs/modernizr-2.5.3.min.js"></script>

	<style type="text/css">
		table.setup-list {
			width: 100%;
			border-collapse: collapse;
			font-size: 12px;
		}
		table.setup-list tr.line {
			background: none repeat scroll 0 0 #FAFAFA;
		}
		table.setup-list tr {
			margin-top: 20px;
			min-height: 20px;
		}
		table.setup-list th.left {
			min-height: 20px;
			padding-left: 10px;
		}
		table.setup-list td.middle {
			min-height: 20px;
		}
		table.setup-list td.right {
			min-height: 20px;
			padding: 10px 10px 10px 10px;
		}
		table.setup-list label {
			font-weight: bold;
		}
		table.setup-list input[type=text], table.setup-list input[type=password] {
			width: 350px;
		}
		div.valign {
			display: table-cell;
			vertical-align: middle;
			position: relative;
			text-align: left;
		}
		li {
			padding-top: 3px;
		}
		textarea {
			font-size: 11px;
		}
		.content-small {
			font-family:'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif;
			font-size:11px;
			font-style:normal;
			color:#666;
		}
		#db-import-progress {
			position:fixed;
			top:0;
			left:0;
			background-color:#FFF;
			color:#000;
			opacity:.85;
			filter:alpha(opacity=85);
			-moz-opacity:0.85;
			z-index:10000;
		}
		.alert {
			margin-top: 20px;
		}
	</style>
</head>
<body>
    <div id="page" class="container">
        <div class="row-fluid">
            <div id="content" class="span9 offset1">
                <div class="clearfix inner-content">
                    <img src="../images/entrada-logo.png" width="296" height="50" alt="Entrada Logo" title="Welcome to Entrada" style="margin-top: 5px" />
                    <?php
                    if ($ERROR && count($ERRORSTR)) {
                        echo display_error();
                    }
                    if ($NOTICE) {
                        echo display_notice();
                    }
                    if ($SUCCESS) {
                        echo display_success();
                    }
                    ?>
                    <form action="index.php?<?php echo replace_query(array("step" => (!$ERROR || count($ERRORSTR) ? $STEP + 1 : $STEP))); ?>" method="post">
                        <input name="step" id="step" type="hidden" value="<?php echo $STEP; ?>" />
                        <?php
                        if (is_array($PROCESSED) && !empty($PROCESSED)) {
                            foreach ($PROCESSED as $key => $value) {
                                echo "<input name=\"".html_encode($key)."\" id=\"processed_".html_encode($key)."\" type=\"hidden\" value=\"".html_encode($value)."\" />\n";
                            }
                        }

                        /**
                         * Display Page
                         */
                        switch ($STEP) {
                            case 6 :
                                ?>
                                <div id="step_6" class="row-fluid">
                                    <p class="alert alert-success" id="success"<?php echo ($ERROR ? " style=\"display: none;\"" : "");?>>
                                        You have successfully installed Entrada. You may view the site at this url: <strong><?php echo $PROCESSED["entrada_url"]; ?></strong> or by clicking the "View Site" button below. Once on the site, you may log in using the admin username and password you entered during the setup process.
                                    </p>
                                    <p class="alert alert-error" id="error"<?php echo (!$ERROR ? " style=\"display: none;\"" : "");?>>
                                        There was an issue while attempting to load the table information into your databases. Please ensure all three databases are completely empty before clicking the 'Refresh' button.
                                    </p>
                                </div>
                                <?php
                            break;
                            case 5 :
                                ?>
                                <div id="step_5" class="row-fluid">
                                    <div class="alert alert-info">
                                        Lastly we need to <strong>save your configuration data</strong> to the <span style="font-family: monospace">core/config/config.inc.php</span> file and write a new <span style="font-family: monospace">.htaccess</span> file to your Entrada directory. We will try to do this for you, but if the setup tool does not have the proper permissions you will be asked to save this yourself before continuing.
                                    </div>
                                    <h2>Step 5: Save Config Data &amp; .htaccess File</h2>
                                    <div id="config"<?php echo (isset($display_config) && $display_config ? "" : " style=\"display: none;\"") ?>>
                                        <label for="config_text">
                                            1. <strong>Copy and paste</strong> the following text into the <span style="font-family: monospace">core/config/config.inc.php</span> file.
                                        </label>
                                        <br />
                                        <textarea id="config_text" name="config_text" class="span12" rows="15" onclick="this.select()" readonly="readonly"><?php echo (isset($display_config) && $display_config) ? $config_text : ""; ?></textarea>
                                    </div>
                                    <div id="htaccess" style="margin-top: 15px;<?php echo ((isset($display_htaccess) && $display_htaccess) ? "" : " display: none;"); ?>">
                                        <label for="htaccess_text">
                                            2. <strong>Copy and paste</strong> the following text into a new file named <span style="font-family: monospace">.htaccess</span> in your Entrada root.
                                        </label>
                                        <br />
                                        <textarea id="htaccess_text" name="htaccess_text" class="span12" rows="15" onclick="this.select()" readonly="readonly"><?php
                                        if (isset($display_htaccess) && $display_htaccess) {
                                                $htaccess_text = file_get_contents($setup->entrada_absolute.$setup->htaccess_file);
                                                $htaccess_text = str_replace("ENTRADA_RELATIVE", (($setup->entrada_relative != "") ? $setup->entrada_relative : "/"), $htaccess_text);
                                                echo $htaccess_text;
                                            }
                                        ?></textarea>
                                    </div>
                                    <?php
                                    if (!$display_htaccess && !$display_config) {
                                        ?>
                                        <div class="alert alert-success">
                                            <ul>
                                                <li>We have successfully saved your configuration information and created a new .htaccess file in your Entrada directory. We are now ready to create the database tables that Entrada needs to operate.</li>
                                            </ul>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div id="db-import-progress" style="display: none">
                                    <div style="display: table; width: 100%; height: 100%; _position: relative; overflow: hidden">
                                        <div style=" _position: absolute; _top: 50%; display: table-cell; vertical-align: middle;">
                                            <div style="_position: relative; _top: -50%; width: 100%; text-align: center">
                                                <span style="font-size: 18px; font-weight: bold">
                                                    <img src="../images/loading.gif" width="32" height="32" alt="Importing Database" title="Please wait while Entrada installs the databases." style="vertical-align: middle" /> Please wait while Entrada installs the databases.
                                                </span>
                                                <br /><br />
                                                This can take a few moments depending on the speed of your server.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script type="text/javascript">
                                    $('db-import-progress').setStyle({
                                        width: document.viewport.getWidth() + 'px',
                                        height: document.viewport.getHeight() + 'px'
                                    });

                                    Event.observe(window, 'load', function() {
                                        Event.observe('continue-button', 'click', function() {
                                            $('db-import-progress').show();
                                        });
                                    });
                                </script>
                                <?php
                            break;
                            case 4 :
                                ?>
                                <div id="step_4" class="row-fluid">
                                    <div class="alert alert-info">
                                        Please create a new <strong>system administrator account</strong> that you will use to manage your Entrada installation. Additional accounts can be created later in the <strong>Admin &gt; Manage Users</strong> section.
                                    </div>
                                    <h2>Step 4: System Administrator Account</h2>
                                    <table class="setup-list" summary="Step 4: System Administrator Account">
                                        <colgroup>
                                            <col width="25%" />
                                            <col width="75%" />
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="admin_firstname">Firstname</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="admin_firstname" name="admin_firstname" value="<?php echo (isset($PROCESSED["admin_firstname"]) && $PROCESSED["admin_firstname"] ? $PROCESSED["admin_firstname"] : "System"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The first name of the system administrator.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="admin_lastname">Lastname</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="admin_lastname" name="admin_lastname" value="<?php echo (isset($PROCESSED["admin_lastname"]) && $PROCESSED["admin_lastname"] ? $PROCESSED["admin_lastname"] : "Administrator"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The lastname name of the system administrator.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="admin_email">E-Mail Address</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="admin_email" name="admin_email" value="<?php echo (isset($PROCESSED["admin_email"]) && $PROCESSED["admin_email"] ? $PROCESSED["admin_email"] : ""); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The email address of the system administrator.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="admin_username">Username</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="admin_username" name="admin_username" value="<?php echo (isset($PROCESSED["admin_username"]) && $PROCESSED["admin_username"] ? $PROCESSED["admin_username"] : ""); ?>" />
                                                    </div>
                                                </td>
                                                <td class="right">
                                                    <span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    A username for the system administrator account.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="admin_password">Password</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="password" id="admin_password" name="admin_password" value="" />
                                                        <input type="hidden" id="admin_password_hash" name="admin_password_hash" value="<?php echo (isset($PROCESSED["admin_password_hash"]) && $PROCESSED["admin_password_hash"] ? $PROCESSED["admin_password_hash"] : ""); ?>" />
                                                        <input type="hidden" id="admin_password_salt" name="admin_password_salt" value="<?php echo (isset($PROCESSED["admin_password_salt"]) && $PROCESSED["admin_password_salt"] ? $PROCESSED["admin_password_salt"] : ""); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    A secure password for the administrator account.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="re_admin_password">Confirm Password</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="password" id="re_admin_password" name="re_admin_password" value="" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    Please re-type the new administrator password from above.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                            break;
                            case 3 :
                                ?>
                                <div id="step_3" class="row-fluid">
                                    <div class="alert alert-info">
                                        <strong>Before completing this step</strong> please log into your database server and create <strong>three</strong> new databases (i.e. entrada, entrada_auth, and entrada_clerkship) that Entrada will use to store its data. Also you will need to create a new database user account that has full privileges to each of these databases.
                                    </div>
                                    <h2>Step 3: Database Connection Information</h2>
                                    <table class="setup-list" summary="Step 3: Database Connection Information">
                                        <colgroup>
                                            <col width="25%" />
                                            <col width="75%" />
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="database_adapter">Database Adapter</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <select id="database_adapter" name="database_adapter" style="width: 205px">
                                                            <?php
                                                            foreach ($DATABASE_ADAPTERS as $type => $title) {
                                                                echo "<option value=\"".html_encode($type)."\"".((isset($PROCESSED["database_adapter"]) && ($PROCESSED["database_adapter"] == $type)) ? " selected=\"selected\"" : "").">".html_encode($title)."</option>\n";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="database_host">Database Hostname</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="database_host" name="database_host" value="<?php echo (isset($PROCESSED["database_host"]) && $PROCESSED["database_host"] ? $PROCESSED["database_host"] : "localhost"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The hostname of your database server.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="database_username">Database Username</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="database_username" name="database_username" value="<?php echo (isset($PROCESSED["database_username"]) && $PROCESSED["database_username"] ? $PROCESSED["database_username"] : "entrada"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The database user with full privileges to each of the databases below.
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="database_password">Database Password</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="password" id="database_password" name="database_password" value="<?php echo (isset($PROCESSED["database_password"]) && $PROCESSED["database_password"] ? $PROCESSED["database_password"] : ""); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The password of the database user listed above.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="entrada_database">Entrada Database</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="entrada_database" name="entrada_database" value="<?php echo (isset($PROCESSED["entrada_database"]) && $PROCESSED["entrada_database"] ? $PROCESSED["entrada_database"] : "entrada"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    1 of 4: The name of your primary Entrada database.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="auth_database">Authentication Database</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="auth_database" name="auth_database" value="<?php echo (isset($PROCESSED["auth_database"]) && $PROCESSED["auth_database"] ? $PROCESSED["auth_database"] : "entrada_auth"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    2 of 4: The name of your Entrada authentication database.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="clerkship_database">Clerkship Database</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="clerkship_database" name="clerkship_database" value="<?php echo (isset($PROCESSED["clerkship_database"]) && $PROCESSED["clerkship_database"] ? $PROCESSED["clerkship_database"] : "entrada_clerkship"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    3 of 4: The name of your Entrada Clerkship database.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="openlabyrinth_database">OLab4 Database</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="openlabyrinth_database" name="openlabyrinth_database" value="<?php echo (isset($PROCESSED["openlabyrinth_database"]) && $PROCESSED["openlabyrinth_database"] ? $PROCESSED["openlabyrinth_database"] : "openlabyrinth"); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    4 of 4: The name of your OLab4 database.
                                                </td>
                                            </tr>											
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                            break;
                            case 2 :
                                ?>
                                <div id="step_2" class="row-fluid">
                                    <div class="alert alert-info">
                                        Entrada requires a bit of information about where this installation is located on your server, and how it will be accessed via the web-browser. We have tried to pre-populate this information, but please review each field and confirm it is correct before continuing.
                                        <br /><br />
                                        This data will be written to your <span style="font-family: monospace">core/config/config.inc.php</span> file later in the setup process.
                                    </div>
                                    <h2>Step 2: URL &amp; Path Information</h2>
                                    <table class="setup-list" summary="Step 2: URL &amp; Path Information">
                                        <colgroup>
                                            <col width="25%" />
                                            <col width="75%" />
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="entrada_url">Entrada URL</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="entrada_url" name="entrada_url" value="<?php echo (isset($PROCESSED["entrada_url"]) && $PROCESSED["entrada_url"] ? $PROCESSED["entrada_url"] : $absolute_url); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    Full URL to Entrada (i.e. http://website.edu/entrada).
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="entrada_relative" style="font-weight: normal">Entrada Relative URL</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="entrada_relative" name="entrada_relative" value="<?php echo (isset($PROCESSED["entrada_relative"]) && $PROCESSED["entrada_relative"] ? $PROCESSED["entrada_relative"] : $relative_url); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    The relative URL to Entrada on your site (i.e. /entrada).
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="entrada_absolute">Entrada Absolute Path</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="entrada_absolute" name="entrada_absolute" value="<?php echo (isset($PROCESSED["entrada_absolute"]) && $PROCESSED["entrada_absolute"] ? $PROCESSED["entrada_absolute"] : $absolute_path); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    Full absolute filesystem path to Entrada (without trailing slash).
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="valign">
                                                        <label for="entrada_storage">Entrada Storage Path</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="valign">
                                                        <input type="text" id="entrada_storage" name="entrada_storage" value="<?php echo (isset($PROCESSED["entrada_storage"]) && $PROCESSED["entrada_storage"] ? $PROCESSED["entrada_storage"] : $storage_path); ?>" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td class="content-small" style="padding-bottom: 15px">
                                                    Full absolute filesystem path to Entrada storage directory.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                            break;
                            case 1 :
                            default :
                                ?>
                                <div id="step_1" class="row-fluid">
                                    <div class="alert alert-info">
                                        Welcome to the <strong>Entrada setup</strong> program. Before we begin please be aware that Entrada is open source software, and is licensed under the GNU General Public License (GPL v3). By continuing you acknowledge that you have read and agree to the terms of the license.
                                    </div>
                                    <h2>Step 1: Software License Agreement</h2>
                                    <div class="row-fluid">
                                        <textarea name="sla" class="span12" rows="15" readonly="readonly"><?php echo $GNU; ?></textarea>
                                    </div>
                                </div>
                                <?php
                            break;
                        }
                        ?>

                        <div class="row-fluid" style="margin: 15px 25px 10px 0; padding-right: 40px; text-align: right">
                            <input class="btn btn-primary pull-right" type="submit" value="Continue" id="continue-button" name="continue"<?php echo ($STEP > 5 ? " style=\"display: none;\"" : "");?> />
                            <input class="btn btn-primary pull-right" type="button" value="View Site" onclick="window.location= '<?php echo (isset($PROCESSED["entrada_url"]) && $PROCESSED["entrada_url"] ? $PROCESSED["entrada_url"] : "../.."); ?>';" name="view"<?php echo ($STEP != 6 || $ERROR ? " style=\"display: none;\"" : ""); ?> />
                            <input class="btn" type="submit" value="Refresh" name="refresh"<?php echo ($STEP != 6 || !$ERROR ? " style=\"display: none;\"" : "");?> />
                        </div>
                    </form>
                </div>
            </div>
        </div>
	</div>
</body>
</html>
