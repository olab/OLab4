<?php
class Migrate_2016_10_19_100101_502 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>

        ALTER TABLE `global_lu_rooms` ADD `room_name` VARCHAR(100),ADD `room_description` VARCHAR(255), ADD `room_max_occupancy` INT(4) NOT NULL DEFAULT 0;

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

        ALTER TABLE `global_lu_rooms` DROP `room_name`,DROP `room_description`, DROP `room_max_occupancy`;
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
        if ($migration->columnExists(DATABASE_NAME, "global_lu_rooms", "room_name")) {
            if ($migration->columnExists(DATABASE_NAME, "global_lu_rooms", "room_description")) {
                if ($migration->columnExists(DATABASE_NAME, "global_lu_rooms", "room_max_occupancy")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
