<?php
class Migrate_2016_03_01_093559_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `rotation_start_date` BIGINT(64) DEFAULT 0 AFTER `delivery_date`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `rotation_end_date` BIGINT(64) DEFAULT 0 AFTER `rotation_start_date`;
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
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `rotation_start_date`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `rotation_end_date`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "rotation_start_date")) {
            if ($migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "rotation_end_date")) {
                return 1;
            }
        }
        return 0;
    }
}
