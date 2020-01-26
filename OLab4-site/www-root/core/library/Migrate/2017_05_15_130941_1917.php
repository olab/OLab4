<?php
class Migrate_2017_05_15_130941_1917 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $directory_list = array(
                "app",
                "app/public",
                "framework",
                "framework/cache",
                "framework/cache/data",
                "framework/sessions",
                "framework/views");
        $config = new Zend_Config(require "config/config.inc.php");
        $storage_dir = $config->entrada_storage;
        $class_name = get_class($this);
        $has_error = false;

        print "\n";
        print $class_name . ": Task: " . $this->color("Checking Storage directory for directories and permissions (". $storage_dir .")", "green");
        if (file_exists($storage_dir)) {
            foreach ($directory_list as $directory) {
                $filename = $storage_dir . DIRECTORY_SEPARATOR . $directory;

                // make sure the directory exists. If not, attempt to create it
                if (!file_exists($filename)) {
                    // attempt to create it
                    if (!mkdir($filename, 0777)) {
                        $has_error = true;
                        print "\n";
                        print $class_name . ": Error: " . $this->color("Could not create required directory (". $filename .")", "red");
                    } else {
                        print "\n";
                        print $class_name . ": Note: " . $this->color("Created directory (". $filename .")", "green");
                    }
                }

                // get current permissions of the directory. Check again if it exists, in case the attempt to create it failed previously
                if (file_exists($filename)) {
                    $permissions = fileperms($filename);
                    if (substr(sprintf('%o', $permissions), -4) !== "0777") {
                        if (!chmod($filename, 0777)) {
                            $has_error = true;
                            print "\n";
                            print $class_name . ": Error: " . $this->color("Could not update permissions on (" . $filename . ")", "red");
                        } else {
                            print "\n";
                            print $class_name . ": Note: " . $this->color("Permissions updated on (" . $filename . ")", "green");
                        }
                    }
                }
            }
        } else {
            $has_error = true;
            print "\n";
            print $class_name . ": Error: " . $this->color("The storage directory specified in config.inc.php does not exist (". $storage_dir .")", "red");
        }

        if ($has_error) {
            print "\n\n";
            print $class_name . ": Error: " . $this->color("Storage directory is missing directories or permissions. Please manually fix the errors reported above.", "red");
        }

        return !$has_error;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        // There is no downgrade for this migration
        return true;
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
