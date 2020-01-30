<?php
class Migrate_2016_11_06_103801_1082 extends Entrada_Cli_Migrate {
    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_rating_scale` (
        `rating_scale_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) unsigned NOT NULL,
        `rating_scale_type` int(12) unsigned NOT NULL,
        `rating_scale_title` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `rating_scale_description` text CHARACTER SET utf8,
        `created_date` bigint(20) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(20) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (`rating_scale_id`),
        KEY `rating_scale_type` (`rating_scale_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessment_rating_scale_responses` (
        `rating_scale_response_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `rating_scale_id` int(11) unsigned NOT NULL,
        `text` text COLLATE utf8_unicode_ci,
        `ardescriptor_id` int(11) unsigned NOT NULL,
        `order` tinyint(3) unsigned NOT NULL DEFAULT '0',
        `flag_response` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `deleted_date` bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (`rating_scale_response_id`),
        KEY `ind_rating_scale_id` (`rating_scale_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessment_rating_scale_authors` (
        `rating_scale_author_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `rating_scale_id` int(11) unsigned NOT NULL,
        `author_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'proxy_id',
        `created_date` bigint(20) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(20) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (`rating_scale_author_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_lu_rating_scale_types` (
        `rating_scale_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) unsigned NOT NULL,
        `shortname` varchar(64) NOT NULL DEFAULT '',
        `title` varchar(255) NOT NULL DEFAULT '',
        `description` text NOT NULL,
        `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
        `created_by` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`rating_scale_type_id`),
        KEY `organisation_id` (`organisation_id`),
        KEY `active` (`active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ALTER TABLE `cbl_assessments_lu_rubrics` ADD COLUMN `rating_scale_id` int(11) unsigned DEFAULT NULL AFTER `is_scale`;
        ALTER TABLE `cbl_assessments_lu_items` ADD COLUMN `rating_scale_id` int(11) unsigned DEFAULT NULL AFTER `item_code`;
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
        DROP TABLE `cbl_assessment_rating_scale`;
        DROP TABLE `cbl_assessment_rating_scale_responses`;
        DROP TABLE `cbl_assessment_rating_scale_authors`;
        DROP TABLE `cbl_assessments_lu_rating_scale_types`;
        ALTER TABLE `cbl_assessments_lu_rubrics` DROP COLUMN `rating_scale_id`;
        ALTER TABLE `cbl_assessments_lu_items` DROP COLUMN `rating_scale_id`;
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

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_rating_scale_types")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_rating_scale")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_rating_scale_responses")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessment_rating_scale_authors")) {
            return 0;
        }
        if (!$migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_rubrics", "rating_scale_id")) {
            return 0;
        }
        if (!$migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "rating_scale_id")) {
            return 0;
        }
        return 1;
    }
}
