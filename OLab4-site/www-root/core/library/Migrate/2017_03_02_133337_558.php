<?php
class Migrate_2017_03_02_133337_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress_approvals` ADD `approval_status` ENUM('approved','hidden','pending')  NOT NULL  DEFAULT 'pending'  AFTER `release_status`;
        UPDATE `cbl_assessment_progress_approvals` SET `approval_status` = 'approved' WHERE `release_status` = 1;
        UPDATE `cbl_assessment_progress_approvals` SET `approval_status` = 'hidden' WHERE `release_status` = 2;
        UPDATE `cbl_assessment_progress_approvals` SET `approval_status` = 'pending' WHERE `release_status` = 0;
        ALTER TABLE `cbl_assessment_progress_approvals` DROP `release_status`;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `created_date` `created_date` BIGINT(64)  UNSIGNED  NOT NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `created_by` `created_by` INT(11)  UNSIGNED  NOT NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` ADD `updated_date` BIGINT(64)  UNSIGNED  NULL  DEFAULT NULL  AFTER `created_by`;
        ALTER TABLE `cbl_assessment_progress_approvals` ADD `updated_by` INT(11)  UNSIGNED  NULL  DEFAULT NULL  AFTER `updated_date`;
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
        ALTER TABLE `cbl_assessment_progress_approvals` ADD `release_status` INT(1)  NOT NULL  DEFAULT 0  AFTER `approver_id`;
        UPDATE `cbl_assessment_progress_approvals` SET `release_status` = 0 WHERE `approval_status` = 'pending';
        UPDATE `cbl_assessment_progress_approvals` SET `release_status` = 1 WHERE `approval_status` = 'approved';
        UPDATE `cbl_assessment_progress_approvals` SET `release_status` = 2 WHERE `approval_status` = 'hidden';
        ALTER TABLE `cbl_assessment_progress_approvals` DROP `approval_status`;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `created_date` `created_date` BIGINT(64)  NOT NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `created_by` `created_by` INT(11)  NOT NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` DROP `updated_date`;
        ALTER TABLE `cbl_assessment_progress_approvals` DROP `updated_by`;

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
        $migrate = new Models_Migration();
        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_progress_approvals", "approval_status") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_assessment_progress_approvals", "updated_by") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_assessment_progress_approvals", "updated_date")
        ) {
            return 1;
        }
        return 0;
    }
}
