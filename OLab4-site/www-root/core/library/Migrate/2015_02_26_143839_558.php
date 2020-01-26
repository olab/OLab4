<?php
class Migrate_2015_02_26_143839_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_targets` ADD COLUMN `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id') NOT NULL DEFAULT 'proxy_id' AFTER `adistribution_id`;
        ALTER TABLE `cbl_assessment_distribution_targets` ADD COLUMN `target_scope` enum('self','children','faculty','internal_ learners','external_ learners','all_ learners') NOT NULL DEFAULT 'self' AFTER `target_type`;

        UPDATE `cbl_assessment_distribution_targets` SET `target_scope` = 'self';
        UPDATE `cbl_assessment_distribution_targets` SET `target_type` = 'course_id' WHERE `adtto_id` = 5;

        ALTER TABLE `cbl_assessment_distribution_targets` DROP FOREIGN KEY `cbl_assessment_distribution_targets_ibfk_2`;
        ALTER TABLE `cbl_assessment_distribution_targets` DROP COLUMN `adtto_id`;

        DROP TABLE `cbl_assessments_lu_distribution_target_types_options`;
        DROP TABLE `cbl_assessments_lu_distribution_target_types`;
        DROP TABLE `cbl_assessments_lu_distribution_targets_options`;
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
        CREATE TABLE `cbl_assessments_lu_distribution_targets_options` (
        `adtoption_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adtoption_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_targets_options` (`adtoption_id`, `name`, `description`, `deleted_date`)
        VALUES
        (1, 'group', 'Use the whole group.', NULL),
        (2, 'individuals', 'Use Individuals.', NULL),
        (3, 'schedule', 'Use schedule.', NULL);

        CREATE TABLE `cbl_assessments_lu_distribution_target_types` (
        `adttype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adttype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_target_types` (`adttype_id`, `name`, `description`, `deleted_date`)
        VALUES
        (1, 'proxy_id', NULL, NULL),
        (2, 'course_id', NULL, NULL),
        (3, 'cgroup_id', NULL, NULL),
        (4, 'group_id', NULL, NULL),
        (5, 'schedule_id', NULL, NULL),
        (6, 'organisation_id', NULL, NULL),
        (7, 'self', NULL, NULL),
        (8, 'adistribution_id', NULL, NULL),
        (9, 'form_id', NULL, NULL);

        CREATE TABLE `cbl_assessments_lu_distribution_target_types_options` (
        `adtto_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adttype_id` int(11) unsigned NOT NULL,
        `adtoption_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`adtto_id`,`adttype_id`,`adtoption_id`),
        KEY `adistribution_id` (`adttype_id`),
        KEY `adtoption_id` (`adtoption_id`),
        CONSTRAINT `cbl_assessments_lu_distribution_target_types_options_ibfk_1` FOREIGN KEY (`adttype_id`) REFERENCES `cbl_assessments_lu_distribution_target_types` (`adttype_id`),
        CONSTRAINT `cbl_assessments_lu_distribution_target_types_options_ibfk_2` FOREIGN KEY (`adtoption_id`) REFERENCES `cbl_assessments_lu_distribution_targets_options` (`adtoption_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_target_types_options` (`adtto_id`, `adttype_id`, `adtoption_id`)
        VALUES
        (1, 1, 1),
        (2, 1, 3),
        (3, 2, 1),
        (4, 2, 2),
        (5, 2, 3),
        (6, 3, 1),
        (7, 3, 2),
        (8, 3, 3),
        (9, 4, 1),
        (10, 4, 2),
        (11, 4, 3),
        (12, 5, 1),
        (13, 5, 2),
        (14, 6, 1),
        (15, 6, 2),
        (16, 7, 2),
        (17, 9, 1);

        ALTER TABLE `cbl_assessment_distribution_targets` ADD COLUMN `adtto_id` int(11) unsigned NOT NULL AFTER `adistribution_id`;
        UPDATE `cbl_assessment_distribution_targets` SET `adtto_id` = 5 WHERE `target_type` = 'course_id';
        UPDATE `cbl_assessment_distribution_targets` SET `adtto_id` = 1 WHERE `target_type` = 'proxy_id';

        ALTER TABLE DROP COLUMN `target_type`;
        ALTER TABLE DROP COLUMN `target_scope`;

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
        return -1;
    }
}
