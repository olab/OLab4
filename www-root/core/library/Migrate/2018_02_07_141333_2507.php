<?php
class Migrate_2018_02_07_141333_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_schedule_sites` (
        `cssite_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `schedule_id` int(12) unsigned NOT NULL,
        `site_id` int(12) unsigned NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`cssite_id`),
        KEY `cbl_schedule_sites_schedule_id` (`schedule_id`),
        KEY `cbl_schedule_slots_site_id` (`site_id`),
        CONSTRAINT `cbl_schedule_sites_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`),
        CONSTRAINT `cbl_schedule_slots_site_id` FOREIGN KEY (`site_id`) REFERENCES `global_lu_sites` (`site_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        CREATE TABLE `cbl_schedule_slot_sites` (
        `csssite_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `schedule_slot_id` int(12) unsigned NOT NULL,
        `site_id` int(12) unsigned NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`csssite_id`),
        KEY `cbl_schedule_slot_sites_schedule_slot_id` (`schedule_slot_id`),
        KEY `cbl_schedule_slot_sites_site_id` (`site_id`),
        CONSTRAINT `cbl_schedule_slot_sites_schedule_slot_id` FOREIGN KEY (`schedule_slot_id`) REFERENCES `cbl_schedule_slots` (`schedule_slot_id`),
        CONSTRAINT `cbl_schedule_slot_sites_site_id` FOREIGN KEY (`site_id`) REFERENCES `global_lu_sites` (`site_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
        DROP TABLE `cbl_schedule_sites`;
        DROP TABLE `cbl_schedule_slot_sites`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_schedule_sites") &&
            $migration->tableExists(DATABASE_NAME, "cbl_schedule_slot_sites")) {
            return 1;
        }

        return 0;
    }
}
