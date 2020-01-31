<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file represents Entrada_Setup class.
 *
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ilya Sorokin <isorokin@ucalgary.ca>
 * @copyright Copyright 2008 University of Calgary. All Rights Reserved.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
*/

class Entrada_Setup extends Entrada_Base {
    public $sql_dump_entrada = "install/sql/entrada.sql";
    public $sql_dump_auth = "install/sql/entrada_auth.sql";
    public $sql_dump_openlabyrinth = "install/sql/openlabyrinth.sql";
    public $sql_dump_clerkship = "install/sql/entrada_clerkship.sql";
    public $htaccess_file = "/setup/install/dist-htaccess.txt";

    public $entrada_url;
    public $entrada_relative;
    public $entrada_absolute;
    public $entrada_api_absolute;
    public $entrada_storage;

    public $database_adapter;
    public $database_host;
    public $database_username;
    public $database_password;
    public $entrada_database;

    public $service_username;
    public $service_password;

    public $auth_database;
    public $openlabyrinth_database;

    public $clerkship_database;

    public $admin_username;
    public $admin_password_hash;
    public $admin_password_salt;

    public $admin_firstname;
    public $admin_lastname;
    public $admin_email;

    public $auth_username;
    public $auth_password;

    public $config_file_path;

    public $database_error;

    public function __construct($processed_array) {
        $this->entrada_url = (isset($processed_array["entrada_url"]) ? $processed_array["entrada_url"] : "");
        $this->entrada_relative = (isset($processed_array["entrada_relative"]) ? $processed_array["entrada_relative"] : "");
        $this->entrada_absolute = (isset($processed_array["entrada_absolute"]) ? $processed_array["entrada_absolute"] : "");
        $this->entrada_api_absolute = (isset($processed_array["entrada_api_absolute"]) ? $processed_array["entrada_api_absolute"] : "");
        $this->entrada_storage = (isset($processed_array["entrada_storage"]) ? $processed_array["entrada_storage"] : "");

        $this->database_adapter = (isset($processed_array["database_adapter"]) ? $processed_array["database_adapter"] : "mysql");
        $this->database_host = (isset($processed_array["database_host"]) ? $processed_array["database_host"] : "");
        $this->database_username = (isset($processed_array["database_username"]) ? $processed_array["database_username"] : "");
        $this->database_password = (isset($processed_array["database_password"]) ? $processed_array["database_password"] : "");

        $this->service_username = (isset($processed_array["service_username"]) ? $processed_array["service_username"] : "");
        $this->service_password = (isset($processed_array["service_password"]) ? $processed_array["service_password"] : "");

        $this->entrada_database = (isset($processed_array["entrada_database"]) ? $processed_array["entrada_database"] : "");
        $this->auth_database = (isset($processed_array["auth_database"]) ? $processed_array["auth_database"] : "");
        $this->openlabyrinth_database = (isset($processed_array["openlabyrinth_database"]) ? $processed_array["openlabyrinth_database"] : "");
        $this->clerkship_database = (isset($processed_array["clerkship_database"]) ? $processed_array["clerkship_database"] : "");

        $this->admin_username = (isset($processed_array["admin_username"]) ? $processed_array["admin_username"] : "");
        $this->admin_password_hash = (isset($processed_array["admin_password_hash"]) ? $processed_array["admin_password_hash"] : "");
        $this->admin_password_salt = (isset($processed_array["admin_password_salt"]) ? $processed_array["admin_password_salt"] : "");

        $this->admin_firstname = (isset($processed_array["admin_firstname"]) ? $processed_array["admin_firstname"] : "");
        $this->admin_lastname = (isset($processed_array["admin_lastname"]) ? $processed_array["admin_lastname"] : "");
        $this->admin_email = (isset($processed_array["admin_email"]) ? $processed_array["admin_email"] : "");

        $this->auth_username = (isset($processed_array["auth_username"]) ? $processed_array["auth_username"] : generate_hash());
        $this->auth_password = (isset($processed_array["auth_password"]) ? $processed_array["auth_password"] : generate_hash());

        $this->config_file_path = $this->entrada_absolute . "/core/config/config.inc.php";
    }

    public function checkEntradaDBConnection() {
        try {
            $db = NewADOConnection($this->database_adapter);
            return @$db->Connect($this->database_host, $this->database_username, $this->database_password, $this->entrada_database);
        } catch(Exception $e) {
            return false;
        }
    }

    public function checkAuthDBConnection() {
        try {
            $db = NewADOConnection($this->database_adapter);
            return @$db->Connect($this->database_host, $this->database_username, $this->database_password, $this->auth_database);
        } catch(Exception $e) {
            return false;
        }
    }

    public function checkOLab4DBConnection() {
        try {
            $db = NewADOConnection($this->database_adapter);
            return @$db->Connect($this->database_host, $this->database_username, $this->database_password, $this->openlabyrinth_database);
        } catch(Exception $e) {
            return false;
        }
    }

    public function checkClerkshipDBConnection() {
        try {
            $db = NewADOConnection($this->database_adapter);
            return @$db->Connect($this->database_host, $this->database_username, $this->database_password, $this->clerkship_database);
        } catch (Exception $e) {
            return false;
        }
    }

    public function writeHTAccess() {
        try {
            $htaccess_text = @file_get_contents($this->entrada_absolute . $this->htaccess_file);
            $htaccess_text = str_replace("ENTRADA_RELATIVE", (($this->entrada_relative != "") ? $this->entrada_relative : "/"), $htaccess_text);

            if (!@file_put_contents($this->entrada_absolute."/.htaccess", $htaccess_text)) {
                return false;
            }

            if (!@file_exists($this->entrada_absolute."/.htaccess")) {
                return false;
            }
        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }

        return true;
    }

    public function writeConfigData() {
        try {
            $configArray = array(
                "entrada_url"  => $this->entrada_url,
                "entrada_relative"  => $this->entrada_relative,
                "entrada_absolute" => $this->entrada_absolute,
                "entrada_api_absolute" => $this->entrada_api_absolute,
                "entrada_storage" => $this->entrada_storage,
                "database" => array(
                    "adapter" => $this->database_adapter,
                    "host" => $this->database_host,
                    "username" => $this->database_username,
                    "password" => $this->database_password,
                    "entrada_database" => $this->entrada_database,
                    "auth_database" => $this->auth_database,
                    "clerkship_database" => $this->clerkship_database,
                    "openlabyrinth_database" => $this->openlabyrinth_database
                ),
                "admin" => array(
                    "firstname" => $this->admin_firstname,
                    "lastname" => $this->admin_lastname,
                    "email" => $this->admin_email,
                ),
                "service_account" => array(
                    "username" => $this->service_username,
                    "password" => $this->service_password
                ),
                "auth_username" => $this->auth_username,
                "auth_password" => $this->auth_password,
            );
            $config = new Zend_Config($configArray);
            $writer = new Zend_Config_Writer_Array();
            $writer->write($this->config_file_path, $config);
        } catch(Zend_Config_Exception $e) {
            return false;
        }

        return true;
    }

    public function outputConfigData() {
$config_text = <<<CONFIGTEXT
<?php
return array (
  'entrada_url' => '{$this->entrada_url}',
  'entrada_relative' => '{$this->entrada_relative}',
  'entrada_absolute' => '{$this->entrada_absolute}',
  'entrada_api_absolute' => '{$this->entrada_api_absolute}',
  'entrada_storage' => '{$this->entrada_storage}',
  'database' => array (
    'adapter' => '{$this->database_adapter}',
    'host' => '{$this->database_host}',
    'username' => '{$this->database_username}',
    'password' => '{$this->database_password}',
    'entrada_database' => '{$this->entrada_database}',
    'auth_database' => '{$this->auth_database}',
    'clerkship_database' => '{$this->clerkship_database}',
    'openlabyrinth_database' => '{$this->openlabyrinth_database}'
  ),
  'admin' => array (
    'firstname' => '{$this->admin_firstname}',
    'lastname' => '{$this->admin_lastname}',
    'email' => '{$this->admin_email}',
  ),
  'service_account' => array(
    'username' => '{$this->service_username}',
    'password' => '{$this->service_password}'
  ),
  'auth_username' => '{$this->auth_username}',
  'auth_password' => '{$this->auth_password}'
);
CONFIGTEXT;
        return $config_text;
    }

    public function configFileExists() {
        if (!@file_exists($this->config_file_path))
        {
            return false;
        }

        return true;
    }

    public function loadDumpData($replace_keywords = true) {
        $db_dump_files = array(
            $this->entrada_database => $this->sql_dump_entrada,
            $this->auth_database => $this->sql_dump_auth,
            $this->openlabyrinth_database => $this->sql_dump_openlabyrinth,
            $this->clerkship_database => $this->sql_dump_clerkship
        );

        try {
            foreach ($db_dump_files as $database_name => $dump_file) {
                $db = NewADOConnection($this->database_adapter);
                $db->Connect($this->database_host, $this->database_username, $this->database_password, $database_name);
                $queries = $this->parseDatabaseDump($dump_file, $replace_keywords);
                foreach ($queries as $key => $query) {
                    if (!$db->Execute($query)) {
                        throw new Exception("Unable to execute query #".($key + 1)." in the ".$database_name." database file. Please review the following: <div style=\"font-family: monospace; font-size: 10px\">".$db->ErrorMsg()."</div>");
                    }
                }
            }
        } catch(Exception $e) {
            $this->database_error = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Returns array of queries from dump file
     * @todo check dump file existence before parsing
     *
     * @param  $dump_file Path to dump file
     * @param bool $replace_keywords
     * @return array
     */
    public function parseDatabaseDump($dump_file, $replace_keywords = true) {
        $replace_keywords = (bool) $replace_keywords;
        $sql_dump = array();

        $query = "";
        if ($handle = @fopen($dump_file, "r")) do {
            $sql_line = fgets($handle);

            if ((trim($sql_line) != "") && (strpos($sql_line, "--") === false)) {
                $query .= $sql_line;

                /**
                 * Look for the end of the current statement - semi-colon with only whitespace after.
                 */
                if (preg_match('/;[\s]*$/', $sql_line)) {
                    if ($replace_keywords) {
                        $search = array("%ADMIN_FIRSTNAME%", "%ADMIN_LASTNAME%", "%ADMIN_EMAIL%", "%ADMIN_USERNAME%", "%ADMIN_PASSWORD_HASH%", "%ADMIN_PASSWORD_SALT%", "%AUTH_USERNAME%", "%AUTH_PASSWORD%");
                        $replace = array($this->admin_firstname, $this->admin_lastname, $this->admin_email, $this->admin_username, $this->admin_password_hash, $this->admin_password_salt, $this->auth_username, $this->auth_password);
                        $query = str_replace($search, $replace, $query);
                    }

                    $sql_dump[] = $query;
                    $query = "";
                }
            }
        } while (!@feof($handle));
        return $sql_dump;
    }

    /**
     * Drops and recreates the three databases
     * @return boolean Success of truncation
     */
    public function recreateDatabases() {
        $dbs = array($this->entrada_database, $this->auth_database, $this->clerkship_database, $this->openlabyrinth_database );
        try {
            foreach ($dbs as $database_name) {
                $db = NewADOConnection($this->database_adapter);
                $db->Connect($this->database_host, $this->database_username, $this->database_password, $database_name);
                if ($db->Execute("DROP DATABASE `" . $database_name . "`")) {
                    $db->Execute("CREATE DATABASE `" . $database_name . "`");
                }
            }
        } catch(Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes all data and tables from the three databases
     * @return boolean Success of truncation
     */
    public function truncateDatabases() {
        $dbs = array($this->entrada_database, $this->auth_database, $this->clerkship_database, $this->openlabyrinth_database);
        try {
            foreach ($dbs as $database_name) {
                $db = NewADOConnection($this->database_adapter);
                $db->Connect($this->database_host, $this->database_username, $this->database_password, $database_name);
                $rs = $db->GetAll("SHOW TABLES");
                foreach($rs as $row) {
                    $db->Execute("DROP TABLE `".$row[0]."`");
                }
            }
        } catch(Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes all data and tables from the three databases and loads the class specified dumps back in
     * @param string $method
     * @return boolean Success of reset
     */
    public function resetDatabases($method = "truncate") {
        if ($method == "truncate") {
            $this->truncateDatabases();
        } else {
            $this->recreateDatabases();
        }

        return $this->loadDumpData();
    }
}
