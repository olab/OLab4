<?php
class Migrate_2016_07_19_164804_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `<?php echo DATABASE_NAME; ?>`.`weeks` (
            `week_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `curriculum_type_id` INT(12) UNSIGNED NOT NULL,
            `week_title` VARCHAR(128) NOT NULL DEFAULT '',
            `updated_date` BIGINT(64) DEFAULT NULL,
            `updated_by` INT(11) DEFAULT NULL,
            `created_date` BIGINT(64) NOT NULL,
            `created_by` INT(11) NOT NULL,
            `deleted_date` BIGINT(64) DEFAULT NULL,
            PRIMARY KEY (`week_id`),
            KEY (`curriculum_type_id`)
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
        DROP TABLE `<?php echo DATABASE_NAME; ?>`.`weeks`;
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
        if ($migration->tableExists(DATABASE_NAME, "weeks")) {
            return 1;
        } else {
            return 0;
        }
    }
}
