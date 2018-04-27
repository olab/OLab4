<?php
class Migrate_2018_02_12_095404_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule_audience`
        CHANGE `audience_type` `audience_type` enum('proxy_id','course_id','cperiod_id','cgroup_id') NOT NULL DEFAULT 'proxy_id',
        ADD COLUMN `custom_start_date` bigint(64) DEFAULT NULL AFTER `audience_value`,
        ADD COLUMN `custom_end_date` bigint(64) DEFAULT NULL AFTER `custom_start_date`;
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
        ALTER TABLE `cbl_schedule_audience`
        CHANGE `audience_type` `audience_type` enum('proxy_id','course_id','cperiod_id') NOT NULL DEFAULT 'proxy_id',
        DROP COLUMN `custom_start_date`,
        DROP COLUMN `custom_end_date`;
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
        global $db;

        $migration = new Models_Migration();

        $audience_type = $migration->fieldMetadata(DATABASE_NAME, "cbl_schedule_audience", 'audience_type');

        if ($audience_type && isset($audience_type["Type"]) &&
            $audience_type["Type"] == "enum('proxy_id','course_id','cperiod_id','cgroup_id')" &&
            $migration->columnExists(DATABASE_NAME, "cbl_schedule_audience", "custom_start_date") &&
            $migration->columnExists(DATABASE_NAME, "cbl_schedule_audience", "custom_end_date")) {
            return 1;
        } else {
            return 0;
        }
    }
}
