<?php
class Migrate_2016_11_23_121842_1342 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`draft_audience`
        ADD COLUMN `custom_time` int(1) DEFAULT '0' AFTER `audience_value`,
        ADD COLUMN `custom_time_start` bigint(64) DEFAULT '0' AFTER `custom_time`,
        ADD COLUMN `custom_time_end` bigint(64) DEFAULT '0' AFTER `custom_time_start`;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`draft_events`
        ADD COLUMN `attendance_required` tinyint(1) DEFAULT '1' AFTER `event_duration`;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`draft_audience`
        DROP COLUMN `custom_time`,
        DROP COLUMN `custom_time_start`,
        DROP COLUMN `custom_time_end`;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`draft_events` DROP COLUMN `attendance_required`;
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

        if ($migration->columnExists(DATABASE_NAME, "draft_audience", "custom_time") && $migration->columnExists(DATABASE_NAME, "draft_audience", "custom_time_start") && $migration->columnExists(DATABASE_NAME, "draft_audience", "custom_time_end") && $migration->columnExists(DATABASE_NAME, "draft_events", "attendance_required")) {
            return 1;
        } else {
            return 0;
        }
    }
}
