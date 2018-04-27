<?php
class Migrate_2017_07_12_101353_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `expiry_offset` bigint(64) DEFAULT NULL AFTER `release_date`;
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `expiry_notification_offset` bigint(64) DEFAULT NULL AFTER `expiry_offset`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `expiry_date` bigint(64) DEFAULT NULL AFTER `rotation_end_date`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `expiry_notification_date` bigint(64) DEFAULT NULL AFTER `expiry_date`;
        ALTER TABLE `cbl_assessment_lu_task_deleted_reasons` ADD COLUMN `user_visible` tinyint(1) DEFAULT 1 AFTER `notes_required`;

        INSERT INTO `cbl_assessment_lu_task_deleted_reasons` (`order_id`, `reason_details`, `notes_required`, `user_visible`, `updated_date`, `updated_by`, `created_date`, `created_by`, `deleted_date`)
        VALUES (4, 'Expired', 0, 0, NULL, NULL, 1456515087, 1, NULL);

        ALTER TABLE `cbl_assessment_notifications` MODIFY `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessment_approver','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','delegation_task_deleted','assessment_submitted','assessment_delegation_assignment_removed','assessment_submitted_notify_approver','assessment_submitted_notify_learner','assessment_expiry_warning') NOT NULL DEFAULT 'assessor_start';
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
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `expiry_offset`;
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `expiry_notification_offset`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `expiry_date`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `expiry_notification_date`;
        ALTER TABLE `cbl_assessment_lu_task_deleted_reasons` DROP COLUMN `user_visible`;

        ALTER TABLE `cbl_assessment_notifications` MODIFY `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessment_approver','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','delegation_task_deleted','assessment_submitted','assessment_delegation_assignment_removed','assessment_submitted_notify_approver','assessment_submitted_notify_learner') NOT NULL DEFAULT 'assessor_start';
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
        if (
            $migrate->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "expiry_offset") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "expiry_notification_offset") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "expiry_date") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "expiry_notification_date") &&
            $migrate->columnExists(DATABASE_NAME, "cbl_assessment_lu_task_deleted_reasons", "user_visible")
        ) {
            return 1;
        }
        return 0;
    }
}
