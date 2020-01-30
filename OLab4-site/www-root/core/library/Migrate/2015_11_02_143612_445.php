<?php
class Migrate_2015_11_02_143612_445 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `event_audience` ADD COLUMN `custom_time` INT(1) DEFAULT '0' AFTER `audience_value`;
        ALTER TABLE `event_audience` ADD COLUMN `custom_time_start` BIGINT(64) DEFAULT '0' AFTER `custom_time`;
        ALTER TABLE `event_audience` ADD COLUMN `custom_time_end` BIGINT(64) DEFAULT '0' AFTER `custom_time_start`;
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
        ALTER TABLE `event_audience` DROP COLUMN `custom_time`;
        ALTER TABLE `event_audience` DROP COLUMN `custom_time_start`;
        ALTER TABLE `event_audience` DROP COLUMN `custom_time_end`;
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
        if ($migration->columnExists(DATABASE_NAME, "event_audience", "custom_time")) {
            if ($migration->columnExists(DATABASE_NAME, "event_audience", "custom_time_start")) {
                if ($migration->columnExists(DATABASE_NAME, "event_audience", "custom_time_end")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
