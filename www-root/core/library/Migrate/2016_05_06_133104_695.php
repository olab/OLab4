<?php
class Migrate_2016_05_06_133104_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `assessments` ADD COLUMN `form_id` int(11) UNSIGNED NULL AFTER `cperiod_id`;
        ALTER TABLE `assessments` ADD FOREIGN KEY (form_id) REFERENCES `cbl_assessments_lu_forms`(`form_id`)
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
        ALTER TABLE `assessments` DROP COLUMN `form_id`;
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
        if ($migration->columnExists(DATABASE_NAME, "assessments", "form_id")) {
            return 1;
        }

        return 0;
    }
}
