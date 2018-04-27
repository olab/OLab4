<?php
class Migrate_2016_12_06_163749_1391 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE INDEX `cperiod_id` ON `<?php echo DATABASE_NAME; ?>`.`course_objectives`(`cperiod_id`);
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
        DROP INDEX `cperiod_id` ON `<?php echo DATABASE_NAME; ?>`.`course_objectives`;
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
        if ($migration->indexExists(DATABASE_NAME, "course_objectives", "cperiod_id")) {
            return 1;
        } else {
            return 0;
        }
    }
}
