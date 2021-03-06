<?php
class Migrate_2015_02_20_130446_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule` MODIFY COLUMN `code` varchar(128) DEFAULT NULL;
        ALTER TABLE `cbl_schedule` MODIFY COLUMN `title` varchar(256) DEFAULT NULL;
        ALTER TABLE `cbl_schedule_slots` MODIFY COLUMN `updated_date` int(11) DEFAULT NULL;
        ALTER TABLE `cbl_schedule_slots` MODIFY COLUMN `updated_by` int(11) DEFAULT NULL;
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
        ALTER TABLE `cbl_schedule` MODIFY COLUMN `code` varchar(32) DEFAULT NULL;
        ALTER TABLE `cbl_schedule` MODIFY COLUMN `title` varchar(128) DEFAULT NULL;
        ALTER TABLE `cbl_schedule_slots` MODIFY COLUMN `updated_date` int(11) NOT NULL;
        ALTER TABLE `cbl_schedule_slots` MODIFY COLUMN `updated_by` int(11) NOT NULL;
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
