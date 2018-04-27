<?php
class Migrate_2017_06_13_190540_1928 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`)
        VALUES
        (NULL, 'calendar_display_start_hour', NULL, '7');
        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`)
        VALUES
        (NULL, 'calendar_display_last_hour', NULL, '19');
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
        DELETE FROM `settings` WHERE `shortname` = 'calendar_display_start_hour' AND `organisation_id` IS NULL;
        DELETE FROM `settings` WHERE `shortname` = 'calendar_display_last_hour' AND `organisation_id` IS NULL;
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
        $settings = new Entrada_Settings();
        if ($settings->read("calendar_display_start_hour") === false) {
            return 0;
        } else if ($settings->read("calendar_display_last_hour") === false) {
            return 0;
        } else {
            return 1;
        }
    }
}
