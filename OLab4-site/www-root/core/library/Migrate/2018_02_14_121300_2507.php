<?php
class Migrate_2018_02_14_121300_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        DROP TABLE `cbl_schedule_slot_sites`;

        ALTER TABLE `cbl_schedule_slots`
        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `course_id`;
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

        ALTER TABLE `cbl_schedule_slots`
        DROP COLUMN `site_id`;
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

        if (!$migration->tableExists(DATABASE_NAME, "cbl_schedule_slot_sites") &&
            $migration->columnExists(DATABASE_NAME, "cbl_schedule_slots", "site_id")) {
            return 1;
        }

        return 0;
    }
}
