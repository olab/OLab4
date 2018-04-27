<?php
class Migrate_2018_02_12_093535_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule`
        DROP COLUMN `one45_rotation_id`,
        DROP COLUMN `one45_owner_group_id`,
        DROP COLUMN `one45_moment_id`;

        ALTER TABLE `cbl_schedule_audience`
        DROP COLUMN `one45_p_id`,
        DROP COLUMN `one45_rotation_id`,
        DROP KEY `schedule_od`;
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
        ALTER TABLE `cbl_schedule`
        ADD COLUMN `one45_rotation_id` int(11) DEFAULT NULL AFTER `schedule_id`,
        ADD COLUMN `one45_owner_group_id` int(11) DEFAULT NULL AFTER `one45_rotation_id`,
        ADD COLUMN `one45_moment_id` int(11) DEFAULT NULL AFTER `one45_owner_group_id`;

        ALTER TABLE `cbl_schedule_audience`
        ADD COLUMN `one45_p_id` int(11) DEFAULT NULL AFTER `saudience_id`,
        ADD COLUMN `one45_rotation_id` int(11) DEFAULT NULL AFTER `deleted_date`,
        ADD KEY `schedule_od` (`schedule_id`);
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

        if (!($migration->columnExists(DATABASE_NAME, "cbl_schedule", "one45_rotation_id") ||
              $migration->columnExists(DATABASE_NAME, "cbl_schedule", "one45_owner_group_id") ||
              $migration->columnExists(DATABASE_NAME, "cbl_schedule", "one45_moment_id") ||
              $migration->columnExists(DATABASE_NAME, "cbl_schedule_audience", "one45_p_id") ||
              $migration->columnExists(DATABASE_NAME, "cbl_schedule_audience", "one45_rotation_id") ||
              $migration->indexExists(DATABASE_NAME, "cbl_schedule_audience", "schedule_od"))) {
            return 1;
        }

        return 0;
    }
}
