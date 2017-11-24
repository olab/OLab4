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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

use Ifsnop\Mysqldump as Mysqldump;

class Entrada_Cli_Setup extends Entrada_Cli {
    protected $command = "";

    /**
     * @var array The valid actions that can be run with this utility.
     */
    protected $actions = array(
        "create" => "Create new Entrada installer SQL files.",
        "help" => "The setup help menu.",
    );

    public function __construct($command = "") {
        $this->command = $command;
    }

    public function playCreate() {
        $this->quiet = true;

        print "\n";

        /**
         * The keys of this array are the database filenames (that exist in www-root/setup/install/sql) and the values
         * of this array are the corresponding databases.
         */
        $databases = array(
            "entrada.sql" => DATABASE_NAME,
            "entrada_auth.sql" => AUTH_DATABASE,
            "entrada_clerkship.sql" => CLERKSHIP_DATABASE
        );

        /**
         * These values must be replaced, and not included from the SQL dump. They contain placeholder variables parsed
         * by the setup utility, and lines with MySQL functions.
         */
        $replaced_values = array(
            AUTH_DATABASE => array(
                "registered_apps" => array(
                    "id:1" => array(
                        "id" => 1,
                        "script_id" => "'%AUTH_USERNAME%'",
                        "script_password" => "MD5('%AUTH_PASSWORD%')",
                        "server_ip" => "'%'",
                        "server_url" => "'%'",
                        "employee_rep" => "1",
                        "notes" => "'Entrada'"
                    )
                ),
                "user_data" => array(
                    "id:1" => array(
                        "id" => 1,
                        "number" => 0,
                        "username" => "'%ADMIN_USERNAME%'",
                        "password" => "'%ADMIN_PASSWORD_HASH%'",
                        "salt" => "NULL",
                        "organisation_id" => "1",
                        "department" => "NULL",
                        "prefix" => "''",
                        "firstname" => "'%ADMIN_FIRSTNAME%'",
                        "lastname" => "'%ADMIN_LASTNAME%'",
                        "date_of_birth" => "NULL",
                        "email" => "'%ADMIN_EMAIL%'",
                        "email_alt" => "''",
                        "email_updated" => "NULL",
                        "google_id" => "NULL",
                        "telephone" => "''",
                        "fax" => "''",
                        "address" => "''",
                        "city" => "''",
                        "province" => "''",
                        "postcode" => "''",
                        "country" => "''",
                        "country_id" => "NULL",
                        "province_id" => "NULL",
                        "notes" => "'System Administrator'",
                        "office_hours" => "NULL",
                        "privacy_level" => "0",
                        "copyright" => "0",
                        "notifications" => "1",
                        "entry_year" => "NULL",
                        "grad_year" => "NULL",
                        "gender" => "0",
                        "clinical" => "1",
                        "uuid" => "UUID()",
                        "created_date" => "UNIX_TIMESTAMP()",
                        "created_by" => "1",
                        "updated_date" => "0",
                        "updated_by" => "0"
                    )
                ),
                "system_roles" => array(
                    "id:1" => array(
                        "id" => 1,
                        "role_name" => "(YEAR(CURRENT_DATE())+4)",
                        "groups_id" => 1,
                    ),
                    "id:2" => array(
                        "id" => 2,
                        "role_name" => "(YEAR(CURRENT_DATE())+3)",
                        "groups_id" => 1,
                    ),
                    "id:3" => array(
                        "id" => 3,
                        "role_name" => "(YEAR(CURRENT_DATE())+2)",
                        "groups_id" => 1,
                    ),
                    "id:4" => array(
                        "id" => 4,
                        "role_name" => "(YEAR(CURRENT_DATE())+1)",
                        "groups_id" => 1,
                    ),
                    "id:5" => array(
                        "id" => 5,
                        "role_name" => "(YEAR(CURRENT_DATE()))",
                        "groups_id" => 1,
                    ),
                    "id:6" => array(
                        "id" => 6,
                        "role_name" => "(YEAR(CURRENT_DATE())-1)",
                        "groups_id" => 1,
                    ),
                    "id:7" => array(
                        "id" => 7,
                        "role_name" => "(YEAR(CURRENT_DATE())-2)",
                        "groups_id" => 1,
                    ),
                    "id:8" => array(
                        "id" => 8,
                        "role_name" => "(YEAR(CURRENT_DATE())-3)",
                        "groups_id" => 1,
                    ),
                    "id:9" => array(
                        "id" => 9,
                        "role_name" => "(YEAR(CURRENT_DATE())-4)",
                        "groups_id" => 1,
                    ),
                    "id:10" => array(
                        "id" => 10,
                        "role_name" => "(YEAR(CURRENT_DATE())+4)",
                        "groups_id" => 2,
                    ),
                    "id:11" => array(
                        "id" => 11,
                        "role_name" => "(YEAR(CURRENT_DATE())+3)",
                        "groups_id" => 2,
                    ),
                    "id:12" => array(
                        "id" => 12,
                        "role_name" => "(YEAR(CURRENT_DATE())+2)",
                        "groups_id" => 2,
                    ),
                    "id:13" => array(
                        "id" => 13,
                        "role_name" => "(YEAR(CURRENT_DATE())+1)",
                        "groups_id" => 2,
                    ),
                    "id:14" => array(
                        "id" => 14,
                        "role_name" => "(YEAR(CURRENT_DATE()))",
                        "groups_id" => 2,
                    ),
                    "id:15" => array(
                        "id" => 15,
                        "role_name" => "(YEAR(CURRENT_DATE())-1)",
                        "groups_id" => 2,
                    ),
                    "id:16" => array(
                        "id" => 16,
                        "role_name" => "(YEAR(CURRENT_DATE())-2)",
                        "groups_id" => 2,
                    ),
                    "id:17" => array(
                        "id" => 17,
                        "role_name" => "(YEAR(CURRENT_DATE())-3)",
                        "groups_id" => 2,
                    ),
                    "id:18" => array(
                        "id" => 18,
                        "role_name" => "(YEAR(CURRENT_DATE())-4)",
                        "groups_id" => 2,
                    )
                )
            )
        );

        print $this->color("So you would like to create new Entrada installer SQL files eh? Good for you.", "yellow");
        print "\n\n";
        print $this->color("This process will drop and recreate the " . (implode(", ", array_values($databases))) . " databases, and overwrite the existing " . (implode(", ", array_keys($databases))) . " files in www-root/setup/install/sql/.", "grey");

        print "\n\n";

        $response = $this->prompt("Would you like to proceed? [Y/n]", array("y", "yes", "n", "no"), array("alpha", "lower"));

        if (in_array($response, array("y", "yes"))) {
            print "\n";

            $username = $this->prompt("Please enter a privileged MySQL username", false, array("trim"));
            if ($username) {
                $password = $this->promptSilent("Please enter a privileged MySQL password");
                if ($password) {
                    print "\n\n";

                    $setup_config = array(
                        "database_adapter" => DATABASE_TYPE,
                        "database_host" => DATABASE_HOST,
                        "database_username" => $username,
                        "database_password" => $password,
                        "entrada_database" => DATABASE_NAME,
                        "auth_database" => AUTH_DATABASE,
                        "clerkship_database" => CLERKSHIP_DATABASE,
                    );

                    $setup = new Entrada_Setup($setup_config);

                    $setup->sql_dump_entrada = ENTRADA_ABSOLUTE . "/setup/install/sql/entrada.sql";
                    $setup->sql_dump_auth = ENTRADA_ABSOLUTE . "/setup/install/sql/entrada_auth.sql";
                    $setup->sql_dump_clerkship = ENTRADA_ABSOLUTE . "/setup/install/sql/entrada_clerkship.sql";

                    if (!$setup->checkEntradaDBConnection()) {
                        print $this->color("Unable to connect to the database server with the credentials you provided.", "red");
                        print "\n\n";

                        return false;
                    }

                    print "Recreating the " . (implode(", ", array_values($databases))) . " databases: ";

                    if ($setup->recreateDatabases()) {
                        print $this->color("COMPLETE", "green");
                    } else {
                        print $this->color("FAILED", "red");
                        print "\n\n";

                        return false;
                    }

                    print "\n";

                    print "Reloading the Entrada installer data: ";

                    if ($setup->loadDumpData(false)) {
                        print $this->color("COMPLETE", "green");
                    } else {
                        print $this->color("FAILED", "red");
                        print "\n\n";

                        print strip_tags($setup->database_error);

                        print "\n\n";

                        return false;
                    }

                    print "\n\n";

                    print "Applying all pending Migrations: ";
                    print "\n";

                    $migrate = new Entrada_Cli_Migrate();
                    $migrate->quiet = true;
                    $migrate->playUp();

                    try {
                        print "\n";

                        $dump_settings = array(
                            "include-tables" => array(),
                            "exclude-tables" => array(),
                            "compress" => "None",
                            "no-data" => false,
                            "add-drop-table" => false,
                            "single-transaction" => true,
                            "lock-tables" => true,
                            "add-locks" => false,
                            "extended-insert" => true,
                            "disable-keys" => false,
                            "where" => "",
                            "no-create-info" => false,
                            "skip-triggers" => false,
                            "add-drop-trigger" => true,
                            "routines" => false,
                            "hex-blob" => true,
                            "databases" => false,
                            "add-drop-database" => false,
                            "skip-tz-utc" => true,
                            "no-autocommit" => false,
                            "default-character-set" => "utf8",
                            "skip-comments" => true,
                            "skip-dump-date" => true,
                            "init_commands" => array(),
                            /* deprecated */
                            "disable-foreign-keys-check" => true
                        );

                        foreach ($databases as $filename => $database_name) {
                            print "Dumping " . $database_name . " as " . $filename . ": ";

                            $dump = new Mysqldump\Mysqldump("mysql:host=" . DATABASE_HOST . ";dbname=" . $database_name, $username, $password, $dump_settings);

                            /*
                             * Check to see if this database has table specific field values that need to be replaced
                             * by alternate values. If so, set using the $replacedValues array.
                             */
                            if (isset($replaced_values[$database_name])) {
                                $dump->replacedValues = $replaced_values[$database_name];
                            }

                            $dump->start(ENTRADA_ABSOLUTE . "/setup/install/sql/" . $filename);
                            print $this->color("COMPLETE", "green");
                            print "\n";
                        }

                        print "\n\n";
                        print $this->color("All finished! Your new Entrada install SQL files have been created.", "yellow");
                        print "\n";
                    } catch (\Exception $e) {
                        print "\n\n";
                        print $this->color("Mysqldump Error:", "red");
                        print "\n";
                        print $this->color($e->getMessage(), "red");
                        print "\n";
                    }
                } else {
                    print "\n\n";
                    print $this->color("Unfortunately we could not capture your password.", "red");
                    print "\n";
                }
            } else {
                print "\n\n";
                print $this->color("Unfortunately we could not capture your username.", "red");
                print "\n";
            }
        }

        print "\n";
    }

    public function playDown($filename = "") {
        print "\n";

        $this->setOutputFilename($filename);

        $migration = $this->setRollbackMigration();
        if ($migration) {
            $this->renderPendingMigrations("down");
            print "\n";

            if ($this->output_filename) {
                print $this->color("These SQL changes will be written to:", "green");
                print "\n";
                print $this->output_filename;
            } else {
                print $this->color("These SQL changes will be automatically applied to the database.", "yellow");
            }

            print "\n\n";

            if ($this->quiet) {
                $response = "y";
            } else {
                $response = $this->prompt("Would you like to proceed? [Y/n]", array("y", "yes", "n", "no"), array("alpha", "lower"));
            }

            if (in_array($response, array("y", "yes"))) {
                print "\n";

                $this->current_migration = $migration;

                require_once(ENTRADA_CORE . "/library/Migrate/" . $migration . ".php");

                $migration = "Migrate_" . $migration;

                $migrate = new $migration($this->command, $this->output_filename);

                print $migration . ": BEGINNING";

                if (method_exists($migrate, "down")) {
                    $result = $migrate->down();

                    print "\n" . $migration . ": ";

                    /*
                     * Allows you to return a simple true / false from the migration if you'd prefer.
                     */
                    if (is_bool($result)) {
                        if ($result) {
                            $result = array("success" => 1, "fail" => 0);
                        } else {
                            $result = array("success" => 0, "fail" => 1);
                        }
                    }

                    if ($result["success"] > 0) {
                        $successful = true;

                        if ($result["fail"] > 0) {
                            print $this->color("COMPLETE (" . $result["success"] . " success / " . $result["fail"] . " failures)", "yellow");
                        } else {
                            print "COMPLETE";
                        }
                    } else {
                        $successful = false;

                        print $this->color("FAILED", "red");
                    }
                }

                print "\n";

                if ($successful) {
                    $this->resetDatabaseOfRecord();
                    $record = new Models_Migration();
                    if (!$record->delete($this->current_migration)) {
                        print $this->color("ERROR Unable to delete the migration record from migrations table.", "red");
                        print "\n";
                    }
                }
            } else {
                print "\n";
                print "No problem, exiting gracefully. See you next time.";
                print "\n";
            }
        } else {
            print $this->color("Nothing to do. Unable to find any migrations to rollback.", "green");
            print "\n";
        }

        print "\n";
    }

    public function playAudit() {
        print "\n";

        $this->loadFileSystemMigrations();

        if ($this->migrations) {
            foreach ($this->migrations as $migration) {

                $this->current_migration = $migration;

                require_once(ENTRADA_CORE . "/library/Migrate/" . $migration . ".php");

                $migration = "Migrate_" . $migration;

                $migrate = new $migration($this->command);

                print $migration . ": ";

                if (!method_exists($migrate, "audit")) {
                    print $this->color("SKIPPED - No audit() method exists to test.", "grey");
                } else {
                    $audit = $migrate->audit();
                    switch ($audit) {
                        case -1 :
                            print $this->color("SKIPPED - No audit() tests present.", "grey");
                            break;
                        case 0 :
                            print $this->color("MISSING - audit() reports there are changes missing in database.", "red");
                            break;
                        case 1 :
                            print $this->color("PRESENT - audit() reports all changes are present.", "green");
                            break;
                        default :
                            print $this->color("FAILED - audit() reported an unexpected value [" . $audit . "].", "red");
                            break;
                    }
                }

                print "\n";
            }
        } else {
            print $this->color("There are no migrations in " . ENTRADA_CORE . "/library/Migrate to audit.", "yellow");
            print "\n";
        }

        print "\n";
    }

    public function playHelp() {
        $this->renderActionHelp($this->command, $this->actions);
    }

    protected function loadFileSystemMigrations() {
        $this->migrations = array();

        $migration_files = glob(ENTRADA_CORE . "/library/Migrate/*.php");
        if ($migration_files) {
            foreach ($migration_files as $migration_file) {
                $this->migrations[] = basename($migration_file, ".php");
            }
        }
    }

    protected function setPendingMigrations() {
        $this->loadFileSystemMigrations();

        if ($this->migrations) {
            $migration = new Models_Migration();
            $results = $migration->getDatabaseMigrations($this->migrations);
            if ($results) {
                foreach ($results as $result) {
                    $key = array_search($result["migration"], $this->migrations);
                    if ($key !== false) {
                        unset($this->migrations[$key]);
                    }
                }
            }
        }

        return $this->migrations;
    }

    protected function renderPendingMigrations($direction = "") {
        if ($this->migrations) {
            $total_migrations = count($this->migrations);

            if ($total_migrations != 1) {
                print "The following " . $this->color($total_migrations . " " . $direction . " migrations", "purple") . " need to be run:";
            } else {
                print "The following " . $this->color($total_migrations . " " . $direction . " migration", "purple") . " needs to be run:";
            }
            print "\n";
            foreach ($this->migrations as $migration) {
                print "    > " . $this->color($migration, "grey");
                print "\n";
            }
        }
    }

    protected function setOutputFilename($filename = "") {
        if ($filename) {
            if (file_exists($filename)) {
                print $this->color("The file you are trying to write these SQL changes to already exists.", "red");
                print "\n";
                print $filename;
                print "\n\n";
                print $this->color("Please remove this file before continuing.", "red");

                $this->end(true);
            } else {
                $this->output_filename = $filename;
            }
        }

        return true;
    }

    protected function setRollbackMigration() {
        $migrations = new Models_Migration();
        $migration = $migrations->fetchLastMigration();

        if ($migration) {
            $this->migrations[] = $migration;
        }

        return $migration;
    }

    protected function create() {
        $migration_dir = ENTRADA_CORE . "/library/Migrate/";
        $migration_name = date("Y_m_d_His") . "_" . $this->issue_number;
        $migration_file = $migration_dir . $migration_name . ".php";

        $search = array(
            "%MIGRATION_NAME%"
        );

        $replace = array(
            $migration_name,
        );

        print "\n";

        if (!file_exists($migration_file) && file_put_contents($migration_file, str_replace($search, $replace, $this::$template))) {
            print $this->color("Successfully created new migration file:", "green");
            print "\n";
            print $migration_file;
        } else {
            print $this->color("Failed to create the new migration file:", "red");
            print "\n";
            print $migration_file;
        }

        print "\n\n";
    }

    protected function run() {
        global $db;

        $success = 0;
        $fail = 0;

        $class_name = get_class($this);

        if ($this->sql) {
            if ($this->output_filename) {
                /*
                 * Add comments to the SQL;
                 */
                array_unshift($this->sql, "-- Beginning of " . $class_name);
                array_push($this->sql, "-- End of " . $class_name);

                if (file_put_contents($this->output_filename, implode("\n\n", $this->sql) . "\n", FILE_APPEND) !== false) {
                    $success++;
                } else {
                    $fail++;

                    print "\n";
                    print $class_name . ": " . $this->color("Unable to write the SQL to " . $this->output_filename, "red");

                    $this->end(true);
                }
            } else {
                foreach ($this->sql as $key => $query) {
                    print "\n";
                    print $class_name . ": Executing query #" . ($key + 1) . ": ";

                    if ($db->Execute($query)) {
                        $success++;

                        print $this->color("SUCCESS", "green");
                    } else {
                        $fail++;

                        print $this->color("FAILED", "red");
                        print "\n";
                        print "Failure while executing query #" . ($key + 1) . " in " . $class_name . ":";
                        print "\n\n";
                        print $this->color(trim($query), "grey");
                        print "\n\n";
                        print "Database Server Response:\n";
                        print $this->color($db->ErrorMsg(), "grey");
                        print "\n\n";

                        if ($this->quiet) {
                            $response = "y";
                        } else {
                            $response = $this->prompt("Would you like to try and continue? [Y/n]", array("y", "yes", "n", "no"), array("alpha", "lower"));
                        }

                        if (!in_array($response, array("y", "yes"))) {
                            $this->end(true);
                        }
                    }
                }
            }

            return array("success" => $success, "fail" => $fail);
        } else {
            print "\n";
            print $class_name . ": " . $this->color("NOTICE - There were no SQL queries found in this migration.", "yellow");
        }

        return false;
    }

    protected function record() {
        /*
         * Reset the protected sql array, so we can have multiple
         * record and runs in one migration if need be.
         */
        $this->sql = array();

        ob_start();
    }

    protected function stop() {
        $queries = ob_get_clean();

        return $this->parseSQL($queries);
    }

    protected function parseSQL($string = "") {
        $sql_query = "";

        if ($string) {
            $lines = preg_split('/$\R?^/m', $string);

            foreach ($lines as $sql_line) {
                if ((trim($sql_line) != "") && (strpos($sql_line, "--") === false)) {
                    $sql_query .= $sql_line;

                    if (preg_match("/;[\n|\r\n]*\$/", $sql_line)) {
                        $this->sql[] = $sql_query;
                        $sql_query = "";
                    }
                }
            }
        }

        return count($this->sql);
    }

    protected function end($user_quit = false) {
        print "\n\n";

        if ($user_quit) {
            print "The migration process has been ended as requested.";
        } else {
            print "Migration complete!";
        }

        print "\n\n";

        exit;
    }
}
