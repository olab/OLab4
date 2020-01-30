<?php
class Migrate_2016_06_09_152346_901 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_targets` MODIFY COLUMN `target_type` ENUM('proxy_id', 'group_id', 'cgroup_id', 'course_id', 'schedule_id', 'organisation_id', 'self', 'eventtype_id') NOT NULL DEFAULT 'proxy_id';
        ALTER TABLE `cbl_assessment_distribution_assessors` MODIFY COLUMN `assessor_type` ENUM('proxy_id', 'cgroup_id', 'group_id', 'schedule_id', 'external_hash', 'course_id', 'organisation_id', 'eventtype_id') NOT NULL DEFAULT 'proxy_id';
        ALTER TABLE `cbl_assessment_distribution_assessors` MODIFY COLUMN `assessor_scope` ENUM('self', 'children', 'faculty', 'internal_learners', 'external_learners', 'all_learners', 'attended_learners') NOT NULL DEFAULT 'self';
        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_eventtypes` (
          `deventtype_id` int(11) NOT NULL AUTO_INCREMENT,
          `adistribution_id` int(11) NOT NULL,
          `eventtype_id` int(12) NOT NULL,
          PRIMARY KEY (`deventtype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        ALTER TABLE `cbl_assessment_distribution_targets` MODIFY COLUMN `target_type` ENUM('proxy_id', 'group_id', 'cgroup_id', 'course_id', 'schedule_id', 'organisation_id', 'self') NOT NULL DEFAULT 'proxy_id';
        ALTER TABLE `cbl_assessment_distribution_assessors` MODIFY COLUMN `assessor_type` ENUM('proxy_id', 'cgroup_id', 'group_id', 'schedule_id', 'external_hash', 'course_id', 'organisation_id') NOT NULL DEFAULT 'proxy_id',
        ALTER TABLE `cbl_assessment_distribution_assessors` MODIFY COLUMN `assessor_scope` ENUM('self', 'children', 'faculty', 'internal_learners', 'external_learners', 'all_learners') NOT NULL DEFAULT 'self';
        DROP TABLE `cbl_assessment_distribution_eventtypes`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_eventtypes")) {
            return 1;
        }
        return 0;
    }
}
