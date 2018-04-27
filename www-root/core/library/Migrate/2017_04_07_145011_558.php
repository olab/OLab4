<?php
class Migrate_2017_04_07_145011_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `forwarded_from_assessment_id` INT(11) DEFAULT NULL AFTER `updated_by`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `forwarded_date` BIGINT(64) DEFAULT NULL AFTER `forwarded_from_assessment_id`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `forwarded_by` INT(11) DEFAULT NULL AFTER `forwarded_date`;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `notification_type` `notification_type` ENUM('delegator_start','delegator_late','assessor_start','assessor_reminder','assessment_approver','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','delegation_task_deleted','assessment_submitted','assessment_delegation_assignment_removed','assessment_submitted_notify_approver','assessment_submitted_notify_learner','assessment_delegation_assignment_removed', 'assessment_task_deleted_forwarded', 'assessment_task_forwarded')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'assessor_start';
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
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `forwarded_from_assessment_id`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `forwarded_date`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `forwarded_by`;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `notification_type` `notification_type` ENUM('delegator_start','delegator_late','assessor_start','assessor_reminder','assessment_approver','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','delegation_task_deleted','assessment_submitted','assessment_delegation_assignment_removed','assessment_submitted_notify_approver','assessment_submitted_notify_learner','assessment_delegation_assignment_removed')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'assessor_start';
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "forwarded_from_assessment_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "forwarded_date") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "forwarded_by")
        ) {
            return 1;
        }
        return 0;
    }
}
