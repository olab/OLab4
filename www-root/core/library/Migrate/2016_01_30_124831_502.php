<?php
class Migrate_2016_01_30_124831_502 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `global_lu_buildings` (
            `building_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `organisation_id` int(11) unsigned NOT NULL,
            `building_code` varchar(16) NOT NULL DEFAULT '',
            `building_name` varchar(128) NOT NULL DEFAULT '',
            `building_address1` varchar(128) NOT NULL DEFAULT '',
            `building_address2` varchar(128) NOT NULL DEFAULT '',
            `building_city` varchar(64) NOT NULL DEFAULT '',
            `building_province` varchar(64) NOT NULL DEFAULT '',
            `building_country` varchar(64) NOT NULL DEFAULT '',
            `building_postcode` varchar(16) NOT NULL DEFAULT '',
        PRIMARY KEY (`building_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `global_lu_rooms` (
            `room_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `building_id` int(11) unsigned NOT NULL,
            `room_number` varchar(20) NOT NULL DEFAULT '',
        PRIMARY KEY (`room_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `events` ADD COLUMN `room_id` int(11) unsigned DEFAULT NULL AFTER `event_location`;
        ALTER TABLE `draft_events` ADD COLUMN `room_id` int(11) unsigned DEFAULT NULL AFTER `event_location`;

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
        DROP TABLE `global_lu_buildings`;
        DROP TABLE `global_lu_rooms`;
        ALTER TABLE `events` DROP COLUMN `room_id`;
        ALTER TABLE `draft_events` DROP COLUMN `room_id`;
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
        if ($migration->tableExists(DATABASE_NAME, "global_lu_buildings")) {
            if ($migration->tableExists(DATABASE_NAME, "global_lu_rooms")) {
                return 1;
            }
        }

        return 0;
    }
}
