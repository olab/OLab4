<?php
class Migrate_2017_03_17_152438_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        DROP TABLE IF EXISTS `cbl_assessment_ss_current_tasks`;

        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `form_id` int(11) unsigned DEFAULT NULL AFTER `delivery_date`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `assessment_type_id` int(11) unsigned NOT NULL DEFAULT '1' AFTER `form_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `organisation_id` int(11) unsigned DEFAULT NULL AFTER `assessment_type_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `associated_record_id` int(11) unsigned DEFAULT NULL AFTER `organisation_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `associated_record_type` enum('event_id','proxy_id','course_id','group_id','schedule_id') DEFAULT NULL AFTER `associated_record_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `min_submittable` int(11) unsigned DEFAULT '0' AFTER `associated_record_type`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `max_submittable` int(11) unsigned DEFAULT '0' AFTER `min_submittable`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `feedback_required` tinyint(1) NOT NULL DEFAULT '0' AFTER `max_submittable`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `start_date` bigint(64) NOT NULL DEFAULT '0' AFTER `feedback_required`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `end_date` bigint(64) NOT NULL DEFAULT '0' AFTER `start_date`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` ADD `additional_assessment` tinyint(1) DEFAULT '0' AFTER `end_date`;
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
        CREATE TABLE `cbl_assessment_ss_current_tasks` (
        `current_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `dassessment_id` int (11) unsigned,
        `assessor_type` enum('internal','external') DEFAULT NULL,
        `assessor_value` int(11) unsigned NOT NULL,
        `target_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id'),
        `target_value` int(11) NOT NULL,
        `title` TEXT,
        `rotation_start_date` bigint(64) DEFAULT 0,
        `rotation_end_date` bigint(64) DEFAULT 0,
        `delivery_date` bigint(64) NOT NULL,
        `schedule_details` TEXT,
        `created_by`int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `deleted_by`int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`current_task_id`),
        CONSTRAINT `cbl_assessment_ss_current_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_ss_current_tasks_ibfk_2` FOREIGN KEY (`dassessment_id`) REFERENCES `cbl_distribution_assessments` (`dassessment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `form_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `assessment_type_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `organisation_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `associated_record_id`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `associated_record_type`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `min_submittable`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `max_submittable`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `feedback_required`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `start_date`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `end_date`;
        ALTER TABLE `cbl_assessment_ss_future_tasks` DROP `additional_assessment`;
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
        if (
            !$migration->tableExists(DATABASE_NAME, "cbl_assessment_ss_current_tasks") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "form_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "assessment_type_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "organisation_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "associated_record_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "associated_record_type") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "assessment_type_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "min_submittable") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "max_submittable") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "feedback_required") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "start_date") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "end_date") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "additional_assessment")
        ) {
            return 1;
        }
        return -1;
    }
}
