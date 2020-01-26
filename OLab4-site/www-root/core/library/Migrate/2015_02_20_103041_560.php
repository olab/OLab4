<?php
class Migrate_2015_02_20_103041_560 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;

        ALTER TABLE `cbl_schedule` ADD COLUMN `schedule_order` INT(11) DEFAULT NULL AFTER `draft_id`;
        ALTER TABLE `cbl_schedule_slots` ADD COLUMN `course_id` INT(11) DEFAULT NULL AFTER `slot_spaces`;

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
        -- SQL Downgrade Queries Here;

        ALTER TABLE `cbl_schedule` DROP COLUMN `schedule_order`;
        ALTER TABLE `cbl_schedule_slots` DROP COLUMN `course_id`;

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
        return -1;
    }
}
