<?php
class Migrate_2017_03_16_135210_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        CREATE TABLE IF NOT EXISTS `cbl_assessment_lu_types` (
        `assessment_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `shortname` varchar(50) NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`assessment_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_type_organisations` (
        `atype_organisation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `assessment_type_id` int(11) unsigned NOT NULL,
        `organisation_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`atype_organisation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `assessment_type_id` int(11) unsigned NOT NULL DEFAULT 1 AFTER `form_id`;

        INSERT INTO `cbl_assessment_lu_types` (`title`, `description`, `shortname`, `created_date`, `created_by`, `updated_by`, `updated_date`, `deleted_date`)
        VALUES
        ('Distribution', 'Distribution based assessment', 'distribution', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        ('CBME', 'CBME based assessment', 'cbme', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL);
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
        -- SQL Downgrade Queries Here;
        DROP TABLE IF EXISTS `cbl_assessment_lu_types`;
        DROP TABLE IF EXISTS `cbl_assessment_type_organisations`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `assessment_type_id`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_lu_types") &&
            $migration->tableExists(DATABASE_NAME, "cbl_assessment_type_organisations") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "assessment_type_id")
        ) {
            return 1;
        }
        return 0;
    }
}
