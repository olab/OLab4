<?php
class Migrate_2018_02_06_094310_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule_slots`
        ADD COLUMN `slot_min_spaces` int(11) NOT NULL DEFAULT '0' AFTER `slot_type_id`,
        ADD COLUMN `strict_spaces` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `slot_spaces`;
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
        ALTER TABLE `cbl_schedule_slots`
        DROP COLUMN `slot_min_spaces`,
        DROP COLUMN `strict_spaces`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_schedule_slots", "slot_min_spaces") &&
            $migration->columnExists(DATABASE_NAME, "cbl_schedule_slots", "strict_spaces")) {
            return 1;
        }

        return 0;
    }
}
