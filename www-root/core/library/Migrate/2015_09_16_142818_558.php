<?php
class Migrate_2015_09_16_142818_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `notifications` ADD COLUMN `from_email` varchar(255) DEFAULT NULL AFTER `proxy_id`;
        ALTER TABLE `notifications` ADD COLUMN `from_firstname` varchar(255) DEFAULT NULL AFTER `from_email`;
        ALTER TABLE `notifications` ADD COLUMN `from_lastname` varchar(255) DEFAULT NULL AFTER `from_firstname`;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `notifications` DROP COLUMN `from_email`;
        ALTER TABLE `notifications` DROP COLUMN `from_firstname`;
        ALTER TABLE `notifications` DROP COLUMN `from_lastname`;
        <?php
        $this->stop();

        return $this->run();
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
        global $db;
        $query = "SHOW COLUMNS FROM `notifications` LIKE 'from_email'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `notifications` LIKE 'from_firstname'";
            $second_column = $db->GetRow($query);
            if ($second_column) {
                $query = "SHOW COLUMNS FROM `notifications` LIKE 'from_lastname'";
                $third_column = $db->GetRow($query);
                if ($third_column) {
                    return 1;
                }
            }
        }
        return 0;
    }
}
