<?php
class Migrate_2016_08_02_150657_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` ADD COLUMN `unit_code` VARCHAR(16) NULL AFTER `cunit_id`;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` DROP COLUMN `unit_code`;
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
        $unit_code_exists = $migration->columnExists(DATABASE_NAME, "course_units", "unit_code");
        if ($unit_code_exists) {
            return 1;
        } else {
            return 0;
        }
    }
}
