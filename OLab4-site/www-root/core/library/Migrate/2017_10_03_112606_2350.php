<?php
class Migrate_2017_10_03_112606_2350 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        if (defined("WEEK_OBJECTIVES_SHOW_LINKS") && WEEK_OBJECTIVES_SHOW_LINKS) { ?>
            INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('curriculum_weeks_enabled', NULL, 1);
        <?php } else { ?>
            INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('curriculum_weeks_enabled', NULL, 0);
        <?php }
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DELETE FROM `settings` WHERE `shortname` = 'curriculum_weeks_enabled';
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
        if ($settings->read("curriculum_weeks_enabled") !== false) {
            return 1;
        }
        return 0;
    }
}
