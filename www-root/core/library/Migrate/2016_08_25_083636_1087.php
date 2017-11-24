<?php
class Migrate_2016_08_25_083636_1087 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD `delivery_date` BIGINT(64)  UNSIGNED  NULL  DEFAULT NULL  AFTER `end_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD `deleted_date` BIGINT(64)  UNSIGNED  NULL  DEFAULT NULL  AFTER `updated_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD `deleted_by` INT(11)  NULL  DEFAULT NULL  AFTER `deleted_date`;
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
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP `delivery_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP `deleted_date`;
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP `deleted_by`;
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
        if ($migration->columnExists(DATABASE_NAME, 'cbl_assessment_distribution_delegations', 'delivery_date') &&
            $migration->columnExists(DATABASE_NAME, 'cbl_assessment_distribution_delegations', 'deleted_date') &&
            $migration->columnExists(DATABASE_NAME, 'cbl_assessment_distribution_delegations', 'deleted_by')
        ) {
            return 1;
        }
        return 0;
    }
}
