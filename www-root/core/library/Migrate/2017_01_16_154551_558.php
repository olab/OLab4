<?php
class Migrate_2017_01_16_154551_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_type_groups` (
        `atype_group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_type_id` int(11) unsigned NOT NULL,
        `group` varchar(35) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        PRIMARY KEY (`atype_group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessment_type_organisations` (
        `atype_organisation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_type_id` int(11) unsigned NOT NULL,
        `organisation_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`atype_organisation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessment_types` (
        `assessment_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `shortname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        `description` text COLLATE utf8_unicode_ci,
        `instructions` text COLLATE utf8_unicode_ci,
        `button_text` varchar(255) NOT NULL,
        `order` int(12) NOT NULL DEFAULT '0',
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`assessment_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_form_objectives` (
        `assessment_form_objective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_id` int(11) unsigned NOT NULL,
        `objective_id` int(11) unsigned NOT NULL,
        `organisation_id` int(11) unsigned NOT NULL,
        `course_id` int(11) unsigned NOT NULL,
        PRIMARY KEY(`assessment_form_objective_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `cbl_assessment_types` (`assessment_type_id`, `shortname`, `title`, `description`, `instructions`, `button_text`, `order`, `created_date`, `created_by`, `updated_by`, `updated_date`, `deleted_date`)
        VALUES
        (1, 'default', 'Standard Assessment', 'NULL', NULL, 'Submit', 0, 1484625600, 1, NULL, NULL, NULL),
        (2, 'complete_and_confirm_by_email', 'Complete and confirm via email', 'Complete an assessment based on the selected tool. Upon completion, the attending will receive an email notification asking them to complete the assessment as well.', 'Once you have submitted this assessment, the selected attending will receive an email link to complete this assessment task.', 'Submit and notify attending by email', 2, 1484625600, 1, NULL, NULL, NULL),
        (3, 'complete_and_confirm_by_pin', 'Complete and confirm via pin', 'Complete an assessment based on the selected tool. Upon completion the assessment, the attending will confirm it on the spot and adjust your assessment as necessary.', 'Once you have submitted this assessment, the attending will be prompted to enter their PIN and complete this assessment task.', 'Submit and have attending confirm by PIN', 3, 1484625600, 1, NULL, NULL, NULL),
        (4, 'send_blank_form', 'Send blank form', 'The attending will receive an email notification to complete an assessment based on the selected tool.', 'Once you have submitted this assessment, the selected attending will receive a blank assessment task containing this form.', 'Submit and send attending a blank form', 1, 1484625600, 1, NULL, NULL, NULL);
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
        DROP TABLE `cbl_assessment_type_groups`;
        DROP TABLE `cbl_assessment_type_organisations`;
        DROP TABLE `cbl_assessment_types`;
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
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_type_groups")) {
            return 0;
        }

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_type_organisations")) {
            return 0;
        }

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_types")) {
            return 0;
        }

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_form_objectives")) {
            return 0;
        }

        return 1;
    }
}
