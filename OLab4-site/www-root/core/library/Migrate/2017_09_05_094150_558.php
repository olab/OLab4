<?php
class Migrate_2017_09_05_094150_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_pins` (
            `pin_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `aprogress_id` int(11) unsigned NOT NULL,
            `dassessment_id` int(11) DEFAULT NULL,
            `proxy_id` int(12) NOT NULL,
            `pin_type` varchar(128) NOT NULL,
            `pin_value` int(11) NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `updated_by` int(12) unsigned,
            `updated_date` bigint(64) unsigned,
            `deleted_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`pin_id`),
        KEY `aprogress_id` (`aprogress_id`)
        );
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
        DROP TABLE `cbl_pins`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_pins")) {
            return 1;
        }

        return 0;
    }
}
