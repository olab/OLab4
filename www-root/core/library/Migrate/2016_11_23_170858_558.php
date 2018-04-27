<?php
class Migrate_2016_11_23_170858_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbme_objective_trees` (
        `cbme_objective_tree_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `tree_id` int(11) NOT NULL,
        `primary` tinyint(4) NOT NULL DEFAULT '0',
        `left` int(11) NOT NULL,
        `right` int(11) NOT NULL,
        `depth` int(11) NOT NULL,
        `organisation_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `objective_id` int(11) DEFAULT NULL,
        `active_from` bigint(64) DEFAULT NULL,
        `active_until` bigint(64) DEFAULT NULL,
        `created_by` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`cbme_objective_tree_id`),
        KEY `lft` (`left`,`right`),
        KEY `objective_id` (`objective_id`),
        KEY `depth` (`depth`,`left`,`right`),
        KEY `tree_id` (`tree_id`),
        KEY `organisation_id` (`organisation_id`,`course_id`),
        KEY `tree_id_2` (`tree_id`,`organisation_id`,`course_id`),
        KEY `primary_2` (`primary`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_lu_form_types` (
        `form_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `category` enum('form','blueprint','cbme_form') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'form',
        `shortname` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
        `title` char(100) CHARACTER SET utf8 NOT NULL,
        `description` text CHARACTER SET utf8 NOT NULL,
        `course_related` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
        `cbme` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(12) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`form_type_id`),
        KEY `category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_form_type_organisation` (
        `aftype_organisation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(12) unsigned NOT NULL,
        `form_type_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`aftype_organisation_id`),
        KEY `organisation_id` (`organisation_id`,`form_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_form_blueprint_elements` (
        `afblueprint_element_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_blueprint_id` int(11) unsigned NOT NULL,
        `element_type` enum('text','blueprint_component','item','rubric') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blueprint_component',
        `element_value` int(11) unsigned DEFAULT NULL,
        `text` text COLLATE utf8_unicode_ci,
        `component_order` int(11) unsigned NOT NULL,
        `comment_type` enum('disabled','optional','mandatory','flagged') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'disabled',
        `editor_state` text COLLATE utf8_unicode_ci,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`afblueprint_element_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_lu_form_blueprint_components` (
        `blueprint_component_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `shortname` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        `description` text COLLATE utf8_unicode_ci NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`blueprint_component_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_form_blueprint_authors` (
        `afbauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_blueprint_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`afbauthor_id`),
        KEY `form_blueprint_id` (`form_blueprint_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `cbl_assessments_lu_form_blueprints` (
        `form_blueprint_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_type_id` int(11) NOT NULL,
        `course_id` int(11) unsigned DEFAULT NULL,
        `title` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
        `description` text COLLATE utf8_unicode_ci,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `published` tinyint(4) unsigned NOT NULL DEFAULT '0',
        `active` tinyint(4) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`form_blueprint_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_form_blueprint_objectives` (
        `afblueprint_objective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) unsigned NOT NULL,
        `objective_id` int(11) unsigned NOT NULL,
        `associated_objective_id` int(11) unsigned DEFAULT NULL,
        `afblueprint_element_id` int(11) unsigned NOT NULL,
        `created_date` bigint(20) unsigned NOT NULL,
        `created_by` int(10) unsigned NOT NULL,
        `updated_date` bigint(20) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (`afblueprint_objective_id`),
        KEY `objective_id` (`objective_id`,`afblueprint_objective_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_assessments_form_blueprint_rating_scales` (
        `afblueprint_rating_scale_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) unsigned NOT NULL,
        `rating_scale_id` int(11) unsigned NOT NULL,
        `afblueprint_element_id` int(11) unsigned NOT NULL,
        `created_date` bigint(20) unsigned NOT NULL,
        `created_by` int(10) unsigned NOT NULL,
        `updated_date` bigint(20) unsigned DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (`afblueprint_rating_scale_id`),
        KEY `scale_id` (`rating_scale_id`,`afblueprint_rating_scale_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `cbl_assessments_lu_form_types` (`form_type_id`, `category`, `shortname`, `title`, `description`, `course_related`, `active`, `cbme`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
        (1, 'form', 'rubric_form', 'Generic Form', 'This is the default form type.', 0, 1, 0, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (2, 'blueprint', 'cbme_supervisor', 'Supervisor Form', 'CBME Supervisor Form', 1, 1, 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (3, 'blueprint', 'cbme_fieldnote', 'Field Note Form', 'CBME Field Note Form', 1, 1, 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (4, 'blueprint', 'cbme_procedure', 'Procedure Form', 'CBME Procedure Form', 1, 1, 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (5, 'cbme_form', 'cbme_ppa_form', 'PPA Form', 'CBME PPA Form', 0, 1, 0, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        (6, 'cbme_form', 'cbme_rubric', 'Rubric Form', 'CBME Rubric Form', 0, 1, 1, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL);

        -- This query will give all organisations in entrada_auth.organisations access to using all form_types.
        INSERT INTO `cbl_assessments_form_type_organisation`
        SELECT NULL, a.`organisation_id`, b.`form_type_id`, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL
        FROM `<?php echo AUTH_DATABASE; ?>`.`organisations` AS a
        JOIN `cbl_assessments_lu_form_types` AS b
        ON b.`deleted_date` IS NULL
        WHERE NOT EXISTS (
        SELECT *
        FROM `cbl_assessments_form_type_organisation` AS c
        WHERE c.`organisation_id` = a.`organisation_id`
        AND c.`form_type_id` = b.`form_type_id`
        )
        ORDER BY a.`organisation_id`, b.`form_type_id`;

        INSERT INTO `cbl_assessments_lu_form_blueprint_components` (`blueprint_component_id`, `shortname`, `description`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
        (1, 'epa_selector', 'EPA Selector', UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        (2, 'contextual_variable_list', 'Contextual Variables', UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        (3, 'entrustment_scale', 'Entrustment Scale', UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        (4, 'ms_ec_scale', 'MS/EC Scale Selector', UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL);

        ALTER TABLE `cbl_assessments_lu_forms` ADD `form_type_id` INT(11)  UNSIGNED  NOT NULL  AFTER `form_id`;
        UPDATE `cbl_assessments_lu_forms` SET `form_type_id` = 1;
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
        DROP TABLE `cbme_objective_trees`;
        DROP TABLE `cbl_assessments_lu_form_types`;
        DROP TABLE `cbl_assessments_form_type_organisation`;
        DROP TABLE `cbl_assessments_form_blueprint_elements`;
        DROP TABLE `cbl_assessments_lu_form_blueprint_components`;
        DROP TABLE `cbl_assessments_lu_form_blueprints`;
        ALTER TABLE `cbl_assessments_lu_forms` DROP COLUMN `form_type_id`;
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
        if (!$migration->tableExists(DATABASE_NAME, "cbme_objective_trees")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_form_types")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_form_type_organisation")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_form_blueprint_elements")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprint_components")) {
            return 0;
        }
        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprints")) {
            return 0;
        }
        if (!$migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_forms", "form_type_id")) {
            return 0;
        }
        return 1;
    }
}
