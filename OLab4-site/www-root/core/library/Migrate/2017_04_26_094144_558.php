<?php
class Migrate_2017_04_26_094144_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `global_lu_learner_levels` (
        `level_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(128) NOT NULL DEFAULT '',
        `description` varchar(128) NOT NULL DEFAULT '',
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`level_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `global_lu_learner_levels` (`level_id`, `title`, `description`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`, `deleted_by`)
        VALUES
        (1, 'Year 1', 'Year 1', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (2, 'Year 2', 'Year 2', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (3, 'Year 3', 'Year 3', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (4, 'Year 4', 'Year 4', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (5, 'Year 5', 'Year 5', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (6, 'Year 6', 'Year 6', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (7, 'Fellow', 'Fellow', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (8, 'PGY1', 'Year 1', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (9, 'PGY2', 'Year 2', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (10, 'PGY3', 'Year 3', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (11, 'PGY4', 'Year 4', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (12, 'PGY5', 'Year 5', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (13, 'PGY6', 'Year 6', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (14, 'PGY7', 'Year 7', 1483714897, 1, 1483714897, 1, NULL, NULL),
        (15, 'PGY8', 'Year 8', 1483714897, 1, 1483714897, 1, NULL, NULL);

        CREATE TABLE IF NOT EXISTS `learner_level_organisation` (
        `level_org_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(12) unsigned NOT NULL,
        `level_id` int(11) unsigned NOT NULL,
        `order` int(2) NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned NOT NULL,
        `deleted_by` int(11) unsigned NOT NULL,
        PRIMARY KEY (`level_org_id`),
        KEY `organisation_id` (`organisation_id`,`level_id`),
        CONSTRAINT `level_id` FOREIGN KEY (`level_id`) REFERENCES `global_lu_learner_levels` (`level_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `learner_level_organisation` (`level_org_id`, `organisation_id`, `level_id`, `order`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`, `deleted_by`)
        VALUES
        (1, 1, 1, 1, 1483714897, 1, 1483714897, 1, NULL, NULL),
        (2, 1, 2, 2, 1483714897, 1, 1483714897, 1, NULL, NULL),
        (3, 1, 3, 3, 1483714897, 1, 1483714897, 1, NULL, NULL),
        (4, 1, 4, 4, 1483714897, 1, 1483714897, 1, NULL, NULL),
        (5, 1, 5, 5, 1483714897, 1, 1483714897, 1, NULL, NULL),
        (6, 1, 6, 6, 1483714897, 1, 1483714897, 1, NULL, NULL);

        CREATE TABLE IF NOT EXISTS `course_learner_levels` (
        `course_level_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `course_id` int(12) unsigned NOT NULL,
        `level_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned NOT NULL,
        `deleted_by` int(11) unsigned NOT NULL,
        PRIMARY KEY (`course_level_id`),
        KEY `organisation_id` (`course_id`,`level_id`),
        CONSTRAINT `course_learner_levels_level_id` FOREIGN KEY (`level_id`) REFERENCES `global_lu_learner_levels` (`level_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE IF NOT EXISTS `global_lu_learner_statuses` (
        `status_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(128) NOT NULL DEFAULT '',
        `description` varchar(128) NOT NULL DEFAULT '',
        `percent_active` FLOAT NOT NULL DEFAULT 100.0,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`status_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `global_lu_learner_statuses` (`status_id`, `title`, `description`, `percent_active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`, `deleted_by`)
        VALUES
        (1, 'Active', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (2, 'AVP', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (3, 'Clerkship', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (4, 'Compassionate Leave', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (5, 'Elective', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (6, 'IEP', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (7, 'Inactive', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (8, 'Leave-NoPay', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (9, 'Leave-Pay', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (10, 'Long-Term', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (11, 'Med Leave', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (12, 'Parental Leave', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (13, 'PEAP', '', 100.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (14, 'Preregister', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (15, 'PreResProg', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (16, 'Probation', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (17, 'Remediation', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (18, 'Suspension', '', 0.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (19, 'Part-time (25%)', '', 25.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (20, 'Part-time (50%)', '', 50.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (21, 'Part-time (60%)', '', 60.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (22, 'Part-time(75%)', '', 75.0, 1493231858, 1, 1493231858, 1, NULL, NULL),
        (23, 'Part-time(80%)', '', 80., 1493231858, 1, 1493231858, 1, NULL, NULL);

        CREATE TABLE IF NOT EXISTS `learner_status_organisation` (
        `status_org_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(12) unsigned NOT NULL,
        `status_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned NOT NULL,
        `deleted_by` int(11) unsigned NOT NULL,
        PRIMARY KEY (`status_org_id`),
        KEY `organisation_id` (`organisation_id`,`status_id`),
        CONSTRAINT `lso_status_id` FOREIGN KEY (`status_id`) REFERENCES `global_lu_learner_statuses` (`status_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE IF NOT EXISTS `user_learner_levels` (
        `user_learner_level_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `proxy_id` int(11) unsigned NOT NULL,
        `level_id` int(11) unsigned NOT NULL,
        `seniority` ENUM('junior','senior'),
        `course_id` int(11) unsigned DEFAULT NULL,
        `stage_objective_id` int(11) unsigned DEFAULT NULL,
        `cperiod_id` int(11) unsigned NOT NULL,
        `start_date` bigint(64) unsigned NOT NULL,
        `finish_date` bigint(64) DEFAULT NULL,
        `status_id` int(11) unsigned NOT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `notes` varchar(255) DEFAULT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by_type` varchar(50) NOT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`user_learner_level_id`),
        KEY `proxy_id` (`proxy_id`),
        CONSTRAINT `user_learner_levels_level_id` FOREIGN KEY (`level_id`) REFERENCES `global_lu_learner_levels` (`level_id`),
        CONSTRAINT `user_learner_levels_status_id` FOREIGN KEY (`status_id`) REFERENCES `global_lu_learner_statuses` (`status_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
        SET FOREIGN_KEY_CHECKS=0;

        DROP TABLE IF EXISTS `global_lu_learner_levels`, `learner_level_organisation`, `global_lu_learner_statuses`, `learner_status_organisation`, `user_learner_levels`;

        SET FOREIGN_KEY_CHECKS=1;
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
        if ($migration->tableExists(DATABASE_NAME, "global_lu_learner_levels") &&
            $migration->tableExists(DATABASE_NAME, "learner_level_organisation") &&
            $migration->tableExists(DATABASE_NAME, "global_lu_learner_statuses") &&
            $migration->tableExists(DATABASE_NAME, "learner_status_organisation") &&
            $migration->tableExists(DATABASE_NAME, "user_learner_levels")
        ) {
            return 1;
        }
        return 0;
    }
}
