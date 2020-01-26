<?php
class Migrate_2017_07_18_124834_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_target_task_releases` (
            `adt_task_release_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `adistribution_id` int(12) unsigned NOT NULL,
            `target_option` ENUM('never','always','percent') NOT NULL DEFAULT 'never',
            `unique_targets` tinyint(1) NOT NULL DEFAULT 1,
            `percent_threshold` int(12) DEFAULT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`adt_task_release_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `target_task_releases_adistribution_id` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_target_report_releases` (
            `adt_report_release_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `adistribution_id` int(12) unsigned NOT NULL,
            `target_option` ENUM('never','always','percent') NOT NULL DEFAULT 'never',
            `unique_targets` tinyint(1) NOT NULL DEFAULT 1,
            `percent_threshold` int(12) DEFAULT NULL,
            `comment_options` ENUM('identifiable','anonymous') NOT NULL DEFAULT 'anonymous',
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`adt_report_release_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `target_report_releases_adistribution_id` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_distribution_assessment_options` (
            `daoption_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `adistribution_id` int(12) unsigned DEFAULT NULL,
            `dassessment_id` int(12) unsigned NOT NULL,
            `actor_id` int(12) DEFAULT NULL,
            `option_name` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `option_value` text CHARACTER SET utf8 NOT NULL DEFAULT '',
            `assessment_siblings` varchar(256) DEFAULT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
            PRIMARY KEY (`daoption_id`),
        KEY `adistribution_id` (`adistribution_id`),
        KEY `dassessment_id` (`dassessment_id`),
        KEY `actor_id` (`actor_id`),
        KEY `option_name` (`option_name`),
        CONSTRAINT `assessment_options_dassessment_id` FOREIGN KEY (`dassessment_id`) REFERENCES `cbl_distribution_assessments` (`dassessment_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE IF EXISTS `cbl_assessment_distribution_target_task_releases`;
        DROP TABLE IF EXISTS `cbl_assessment_distribution_target_report_releases`;
        DROP TABLE IF EXISTS `cbl_distribution_assessment_options`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_target_task_releases") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_target_report_releases") &&
            $migration->tableExists(DATABASE_NAME, "cbl_distribution_assessment_options")
        ) {
            return 1;
        }
        return 0;
    }
}
