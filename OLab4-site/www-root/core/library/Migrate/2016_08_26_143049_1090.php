<?php
class Migrate_2016_08_26_143049_1090 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_leave_tracking`
        ADD COLUMN `weekdays_used` INT(12) DEFAULT NULL AFTER `days_used`,
        ADD COLUMN `weekend_days_used` INT(12) DEFAULT NULL AFTER `weekdays_used`;
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
        ALTER TABLE `cbl_leave_tracking`
        DROP COLUMN `weekdays_used`,
        DROP COLUMN `weekend_days_used`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_leave_tracking", "weekdays_used") && $migration->columnExists(DATABASE_NAME, "cbl_leave_tracking", "weekend_days_used")) {
            return 1;
        } else {
            return 0;
        }
    }
}
