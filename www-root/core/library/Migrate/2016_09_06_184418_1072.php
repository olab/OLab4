<?php
class Migrate_2016_09_06_184418_1072 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `<?php echo DATABASE_NAME; ?>`.`linked_tag_sets` (
            `linked_tag_set_id` INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,
            `organisation_id` INT(12) UNSIGNED NOT NULL,
            `type` ENUM('event', 'course_unit', 'course'),
            `objective_id` INT(12) NOT NULL,
            `target_objective_id` INT(12) NULL,
            `created_date` BIGINT(64) NULL,
            `created_by` INT(12) UNSIGNED NULL,
            `updated_date` BIGINT(64) NULL,
            `updated_by` INT(12) UNSIGNED NULL,
            `deleted_date` BIGINT(64) NULL,
            PRIMARY KEY (`linked_tag_set_id`),
            KEY `organisation_id` (`organisation_id`),
            KEY `type` (`type`),
            KEY `objective_id` (`objective_id`)
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
        DROP TABLE `<?php echo DATABASE_NAME; ?>`.`linked_tag_sets`;
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
        if ($migration->tableExists(DATABASE_NAME, "linked_tag_sets")) {
            return 1;
        } else {
            return 0;
        }
    }
}
