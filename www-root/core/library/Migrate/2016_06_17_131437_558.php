<?php
class Migrate_2016_06_17_131437_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_delegations` ADD `delegator_type` ENUM('proxy_id','external_assessor_id') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'proxy_id' AFTER `delegator_id`;
        ALTER TABLE `notification_users` CHANGE `content_type` `content_type` VARCHAR(48)  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT '';
        ALTER TABLE `cbl_assessment_distribution_delegation_assignments` ADD `deleted_reason_id` INT  NULL  DEFAULT NULL  AFTER `deleted_date`;
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
        ALTER TABLE `cbl_assessment_distribution_delegations` DROP `delegator_type`;
        ALTER TABLE `cbl_assessment_distribution_delegation_assignments` DROP `deleted_reason_id`;
        ALTER TABLE `notification_users` CHANGE `content_type` `content_type` VARCHAR(32)  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT '';
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_distribution_delegations", "delegator_type") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_distribution_delegation_assignments", "deleted_reason_id")) {
            // Check that the metadata matches
            $meta = $migration->fieldMetadata(DATABASE_NAME, "notification_users", "content_type");
            if (!empty($meta)) {
                if (isset($meta["Type"]) && $meta["Type"]) {
                    if ($meta["Type"] == "varchar(48)") {
                        return 1;
                    }
                }
            }
        }
        return 0;
    }
}
