<?php
class Migrate_2016_07_06_125605_994 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `events_lu_eventtypes` MODIFY `eventtype_title` VARCHAR(64);
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
        ALTER TABLE `events_lu_eventtypes` MODIFY `eventtype_title` VARCHAR(32);
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
        $query = "
            SELECT `COLUMNS`.`CHARACTER_MAXIMUM_LENGTH`
            FROM `information_schema`.`COLUMNS`
            WHERE `COLUMNS`.`COLUMN_NAME` = 'eventtype_title'
            AND `COLUMNS`.`TABLE_NAME` = 'events_lu_eventtypes'
            AND `COLUMNS`.`TABLE_SCHEMA` = ?";
        $length = $db->GetOne($query, DATABASE_NAME);
        if ($length == 64) {
            return 1;
        } else if ($length == 32) {
            return 0;
        } else {
            return -1;
        }
    }
}
