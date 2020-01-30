<?php
class Migrate_2016_08_08_140019_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `<?php echo DATABASE_NAME; ?>`.`course_unit_objectives` (
            `cuobjective_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `cunit_id` INT(11) UNSIGNED NOT NULL,
            `objective_id` INT(12) NOT NULL,
            `objective_order` INT(6) NOT NULL DEFAULT 0,
            `updated_date` BIGINT(64) NULL,
            `updated_by` INT(12) UNSIGNED NULL,
            PRIMARY KEY (`cuobjective_id`),
            KEY `cunit_id` (`cunit_id`),
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
        DROP TABLE `<?php echo DATABASE_NAME; ?>`.`course_unit_objectives`;
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
        if ($migration->tableExists(DATABASE_NAME, "course_unit_objectives")) {
            return 1;
        } else {
            return 0;
        }
    }
}
