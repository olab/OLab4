<?php
class Migrate_2016_07_06_172924_000 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        ALTER TABLE `event_eventtypes` MODIFY `duration` FLOAT;
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
        ALTER TABLE `event_eventtypes` MODIFY `duration` INT(12);
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
            SELECT `COLUMNS`.`COLUMN_TYPE`
            FROM `information_schema`.`COLUMNS`
            WHERE `COLUMNS`.`COLUMN_NAME` = 'duration'
            AND `COLUMNS`.`TABLE_NAME` = 'event_eventtypes'
            AND `COLUMNS`.`TABLE_SCHEMA` = ?";
        $column_type = $db->GetOne($query, DATABASE_NAME);
        if (strtoupper($column_type) == 'FLOAT') {
            return 1;
        } else if (strtoupper($column_type) == 'INT(12)') {
            return 0;
        } else {
            return -1;
        }
    }
}
