<?php
class Migrate_2017_05_16_085408_1699 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `event_medbiq_resources` (
        `em_resource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL,
        `resource_id` int(11) NOT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`em_resource_id`),
        KEY `event_id` (`event_id`),
        KEY `resource_id` (`resource_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `event_medbiq_resources`;
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
        if ($migration->tableExists(DATABASE_NAME, "event_medbiq_resources")) {
            return 1;
        }

        return 0;
    }
}
