<?php
class Migrate_2017_04_25_082919_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessment_form_type_meta` (
            `form_type_meta_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `form_type_id` int(11) unsigned NOT NULL,
            `organisation_id` int(11) NOT NULL,
            `meta_name` char(50) NOT NULL,
            `meta_value` text NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT '1',
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
          PRIMARY KEY (`form_type_meta_id`),
          KEY `form_type_id` (`form_type_id`),
          KEY `organisation_id` (`organisation_id`)
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `cbl_assessment_form_type_meta`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_form_type_meta")) {
            return 1;
        }

        return 0;
    }
}
