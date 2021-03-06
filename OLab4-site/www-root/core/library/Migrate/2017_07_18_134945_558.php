<?php
class Migrate_2017_07_18_134945_558 extends Entrada_Cli_Migrate {
    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_items` ADD COLUMN `attributes` TEXT NULL AFTER `default_response`;
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
        ALTER TABLE `cbl_assessments_lu_items` DROP COLUMN `attributes`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "attributes")) {
            return 1;
        }
        return 0;
    }
}