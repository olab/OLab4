<?php
class Migrate_2016_08_25_093740_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `visibility_status` ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible' AFTER `notifications`;
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
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `visibility_status`;
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

        // Check for new field
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "visibility_status")) {
            return 1;
        } else {
            return 0;
        }
    }
}
