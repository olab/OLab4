<?php
class Migrate_2018_02_14_175622_1569 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        USE <?php echo DATABASE_NAME ?>;
        CREATE TABLE `duty_hours_entries` (
            `dhentry_id` INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,
            `proxy_id` INT(12) UNSIGNED NOT NULL,
            `encounter_date` INT(12) NOT NULL,
            `updated_date` BIGINT(64) UNSIGNED NOT NULL DEFAULT '0',
            `rotation_id` INT(12) UNSIGNED NOT NULL DEFAULT '0',
            `llocation_id` INT(12) UNSIGNED NOT NULL DEFAULT '0',
            `lsite_id` INT(11) NOT NULL DEFAULT '0',
            `hours` FLOAT(4,2) NOT NULL,
            `hours_type` enum('on_duty', 'off_duty', 'absence') NOT NULL DEFAULT 'on_duty',
            `comments` TEXT NULL DEFAULT NULL,
            `entry_active` INT(1) NOT NULL DEFAULT '1',
            `course_id` INT(12) UNSIGNED NOT NULL,
            `cperiod_id` INT(12) UNSIGNED NOT NULL,
            PRIMARY KEY (`dhentry_id`)
        ) DEFAULT CHARSET=utf8 ENGINE=InnoDB;
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
        DROP TABLE `duty_hours_entries`;
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
        $migration = new Models_Migration();
        if ($migration->tableExists(DATABASE_NAME, "duty_hours_entries")) {
            return 1;
        }

        return 0;
    }
}
