<?php
class Migrate_2017_01_31_154701_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessments_form_type_component_settings` (
            `aftc_setting_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `form_type_id` int(11) unsigned NOT NULL,
            `component_order` int(11) unsigned NOT NULL,
            `settings` text NOT NULL,
            `created_date` bigint(64) NOT NULL,
            `created_by` int(12) NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(12) DEFAULT NULL,
            `deleted_date` bigint(64) DEFAULT NULL,
            PRIMARY KEY (`aftc_setting_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
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
        DROP TABLE `cbl_assessments_form_type_component_settings`;
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

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_form_type_component_settings")) {
            return 0;
        }

        return 1;
    }
}
