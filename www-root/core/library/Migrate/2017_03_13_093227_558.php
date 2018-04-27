<?php
class Migrate_2017_03_13_093227_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT '1' AFTER `delivery_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD COLUMN `deleted_reason_id` int(11) unsigned DEFAULT NULL AFTER `updated_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD COLUMN `deleted_reason_notes` varchar(255) DEFAULT NULL AFTER `deleted_reason_id`;
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
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP COLUMN `visible`;
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP COLUMN `deleted_reason_id`;
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP COLUMN `deleted_reason_notes`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_distribution_delegations", "deleted_reason_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_distribution_delegations", "deleted_reason_notes") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_distribution_delegations", "visible")
        ) {
            return 1;
        }
        return 0;
    }
}
