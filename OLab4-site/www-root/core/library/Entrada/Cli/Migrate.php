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

class Entrada_Cli_Migrate extends Entrada_Cli {
    protected $command = "";

    /**
     * @var array The valid actions that can be run with this utility.
     */
    protected $actions = array(
        "audit" => "Verifies that all present migrations have been run successfully.",
        "create" => "Create a new Entrada database migration file.",
        "down-s" => "Rollback the last successful up migration. Provide optional filename to output SQL instead of run it.",
        "pending" => "See a list of the pending database migrations in your installation.",
        "quiet" => "Perform --up or --down migrations without prompting for confirmation.",
        "up-s" => "Run all pending migrations against your database. Provide optional filename to output SQL instead of run it.",
        "help" => "The migrate help menu.",
    );

    protected $output_filename = false;

    protected $issue_number = "";

    protected $sql = array();

    protected $migrations = array();

    protected $current_migration = "";

    protected $run_migrations = array();

    private static $template = <<<TEMPLATECLASS
<?php
class Migrate_%MIGRATION_NAME% extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        \$this->record();
        ?>
        -- SQL Upgrade Queries Here;
        <?php
        \$this->stop();

        return \$this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        \$this->record();
        ?>
        -- SQL Downgrade Queries Here;
        <?php
        \$this->stop();

        return \$this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        return -1;
    }
}

TEMPLATECLASS;

    public function __construct($command = "", $output_filename = false) {
        $this->command = $command;
        $this->output_filename = $output_filename;
    }

    public function playCreate() {
        print "\n";

        $this->issue_number = $this->prompt("Provide the Entrada GitHub Issue number for this change", true, "alphanumeric");

        $this->create();
    }

    public function playPending() {
        print "\n";

        $this->setPendingMigrations();

        if ($this->migrations) {
            $this->renderPendingMigrations("up");
        } else {
            print $this->color("Nothing to do. You, my friend, are all up to date.", "green");
            print "\n";
        }

        print "\n";
    }

    public function playUp($filename = "") {
        print "\n";

        $this->setOutputFilename($filename);

        $this->setPendingMigrations();

        if ($this->migrations) {
            $this->renderPendingMigrations("up");
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

                foreach ($this->migrations as $migration) {
                    $this->current_migration = $migration;

                    require_once(ENTRADA_CORE . "/library/Migrate/" . $migration . ".php");

                    $migration = "Migrate_" . $migration;

                    $migrate = new $migration($this->command, $this->output_filename);
                    $migrate->quiet = $this->quiet;

                    print $migration . ": BEGINNING";

                    if (method_exists($migrate, "audit") && ($migrate->audit() === 1)) {
                        $successful = true;

                        $result = array("success" => 0, "fail" => 0);

                        print "\n" . $migration . ": ";
                        print $this->color("SKIPPED (Audit reports this change is already present)", "grey");
                    } else {
                        $result = $migrate->up();

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
                        $report = array(
                            "migration" => $this->current_migration,
                            "success" => $result["success"],
                            "fail" => $result["fail"]
                        );
                        $this->resetDatabaseOfRecord();
                        $record = new Models_Migration($report);
                        if (!$record->insert()) {
                            print $this->color("ERROR Unable to record that the migration was successful.", "red");
                            print "\n";
                        }
                    }
                }
            } else {
                print "\n";
                print "No problem, exiting gracefully. See you next time.";
                print "\n";
            }
        } else {
            print $this->color("Nothing to do. You, my friend, are all up to date.", "green");
            print "\n";
        }

        print "\n";
    }
    
    protected function resetDatabaseOfRecord () {
        global $db;
    
        $query = "USE `" . DATABASE_NAME . "`;";
        $returnVal = $db->Execute($query);
        return $returnVal;
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

                    /**
                     * Look for the end of the current statement - semi-colon with only whitespace after.
                     */
                    if (preg_match('/;[\s]*$/', $sql_line)) {
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
