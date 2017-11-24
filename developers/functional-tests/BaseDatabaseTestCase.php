<?php
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

if (!class_exists('PHPUnit_Framework_TestCase')) {
    require_once ('autoload.php');
}

abstract class BaseDatabaseTestCase extends PHPUnit_Extensions_Database_TestCase {
    protected $db;

    public function setUp() {
        parent::setUp();


        if (!defined("ADODB_QUOTE_FIELDNAMES")) {
            $ADODB_QUOTE_FIELDNAMES = true;	// Whether or not you want ADOdb to backtick field names in AutoExecute, GetInsertSQL and GetUpdateSQL.
            define("ADODB_QUOTE_FIELDNAMES", $ADODB_QUOTE_FIELDNAMES);
        }
        $this->db = NewADOConnection(DATABASE_TYPE);
        $this->db->Connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
        global $db;
        $db = $this->db;
    }

    public static function setUpBeforeClass() {
        /**
         * Register the Composer autoloader.
         */
        require_once("autoload.php");

        require_once("config/settings.inc.php");
        require_once("functions.inc.php");
     }


    /**
     * Required function to return a connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection() {
        $config = new Zend_Config(require "config/config.inc.php");
        $dsn = "mysql:host={$config->database->host};dbname={$config->database->entrada_database}";
        $pdo = new PDO($dsn, $config->database->username, $config->database->password);

        return $this->createDefaultDBConnection($pdo);
    }

    /**
     * Required function to return a data set.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    final public function getDataSet() {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/fixtures/entrada.xml');
    }
}
