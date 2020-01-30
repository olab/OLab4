<?php
class Migrate_2016_07_22_132727_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` (
            `cunit_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `unit_title` VARCHAR(128) NOT NULL DEFAULT '',
            `unit_description` VARCHAR(128) NOT NULL DEFAULT '',
            `course_id` INT(12) UNSIGNED NOT NULL,
            `cperiod_id` INT(11) NULL,
            `week_id` INT(11) UNSIGNED NULL,
            `updated_date` BIGINT(64) DEFAULT NULL,
            `updated_by` INT(11) DEFAULT NULL,
            `created_date` BIGINT(64) NOT NULL,
            `created_by` INT(11) NOT NULL,
            `deleted_date` BIGINT(64) DEFAULT NULL,
            PRIMARY KEY (`cunit_id`),
            KEY (`course_id`),
            KEY (`cperiod_id`),
            KEY (`week_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `<?php echo DATABASE_NAME; ?>`.`course_units`;
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
        if ($migration->tableExists(DATABASE_NAME, "course_units")) {
            return 1;
        } else {
            return 0;
        }
    }
}
