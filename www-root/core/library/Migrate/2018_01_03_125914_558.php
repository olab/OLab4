<?php
class Migrate_2018_01_03_125914_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessment_plan_containers` (
        `assessment_plan_container_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(1024) NOT NULL,
        `description` text DEFAULT NULL,
        `course_id` int(12) NOT NULL,
        `cperiod_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_plan_container_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_plans` (
        `assessment_plan_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(1024) NOT NULL,
        `description` text DEFAULT NULL,
        `assessment_plan_container_id` int(12) NOT NULL,
        `objective_id` int(12) NOT NULL,
        `valid_from` bigint(64) NOT NULL,
        `valid_until` bigint(64) NOT NULL,
        `published` tinyint(1) NOT NULL DEFAULT 0,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_plan_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_plan_objectives` (
        `assessment_plan_objective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_plan_id` int(12) NOT NULL,
        `objective_id` int(12) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_plan_objective_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_plan_forms` (
        `assessment_plan_form_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_plan_id` int(12) NOT NULL,
        `form_id` int(12) NOT NULL,
        `minimum_assessments` int(12) NOT NULL,
        `iresponse_id` int(12) NOT NULL,
        `minimum_assessors` int(12) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_plan_form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_plan_form_objectives` (
        `assessment_plan_form_objective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_plan_id` int(12) NOT NULL,
        `assessment_plan_form_id` int(12) NOT NULL,
        `objective_id` int(12) NOT NULL,
        `objective_parent` int(12) NOT NULL DEFAULT 0,
        `objective_set_id` int(12) NOT NULL,
        `minimum` int(12) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_plan_form_objective_id`)
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
        DROP TABLE IF EXISTS `cbl_assessment_plan_containers`;
        DROP TABLE IF EXISTS `cbl_assessment_plans`;
        DROP TABLE IF EXISTS `cbl_assessment_plan_objectives`;
        DROP TABLE IF EXISTS `cbl_assessment_plan_forms`;
        DROP TABLE IF EXISTS `cbl_assessment_plan_form_objectives`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_plan_containers") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_plan_form_objectives") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_plan_forms") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_plan_objectives") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_plans")) {
            return 1;
        }

        return -1;
    }
}
