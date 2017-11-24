<?php
class Migrate_2016_05_10_085657_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_distribution_delegation_assignments` (
        `addassignment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `addelegation_id` int(11) unsigned NOT NULL,
        `adistribution_id` int(11) unsigned NOT NULL,
        `dassessment_id` int(11) unsigned NOT NULL,
        `delegator_id` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_reason` text CHARACTER SET utf8,
        `assessor_type` enum('internal','external') CHARACTER SET utf8 DEFAULT NULL,
        `assessor_value` int(11) unsigned DEFAULT NULL,
        `target_type` enum('proxy_id','external_hash','course_id','schedule_id') CHARACTER SET utf8 DEFAULT NULL,
        `target_value` int(11) unsigned DEFAULT NULL,
        `created_date` bigint(64) unsigned DEFAULT NULL,
        `created_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`addassignment_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_delegation_assignments_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=230 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessment_distribution_delegations` (
        `addelegation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `delegator_id` int(11) unsigned NOT NULL,
        `start_date` bigint(64) unsigned DEFAULT NULL,
        `end_date` bigint(64) unsigned DEFAULT NULL,
        `completed_by` int(11) DEFAULT NULL,
        `completed_reason` text CHARACTER SET utf8,
        `completed_date` bigint(64) unsigned DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`addelegation_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_delegations_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ALTER TABLE `cbl_assessment_notifications` CHANGE `notification_type` `notification_type` ENUM('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','assessment_submitted','assessment_delegation_assignment_removed')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'assessor_start';
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
        DROP TABLE IF EXISTS `cbl_assessment_distribution_delegations`;
        DROP TABLE IF EXISTS `cbl_assessment_distribution_delegation_assignments`;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `notification_type` `notification_type` ENUM('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','assessment_submitted')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'assessor_start';
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

        // Both tables exist, return present
        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_delegations") && $migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_delegation_assignments")) {
            return 1;
        } else {
            return 0; // they don't exist
        }
    }
}
