<?php

class Migrate_2016_09_16_083715_1004 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule_slots` ADD INDEX `schedule_id` (`schedule_id`);
        ALTER TABLE `cbl_schedule` ADD INDEX `draft_id` (`draft_id`);
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
        DROP INDEX `schedule_id` ON `cbl_schedule_slots`;
        DROP INDEX `draft_id` ON `cbl_schedule`;
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
        if ($migration->indexExists(DATABASE_NAME, "cbl_schedule_slots", "schedule_id") && $migration->indexExists(DATABASE_NAME, "cbl_schedule", "draft_id")) {
            return 1;
        }
        return 0;
    }
}