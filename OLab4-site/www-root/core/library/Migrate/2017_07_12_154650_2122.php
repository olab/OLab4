<?php
class Migrate_2017_07_12_154650_2122 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`)
        VALUES (NULL, 'personnel_api_curriculum_coord_show_all_staff', NULL, "0");
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
        DELETE FROM `settings` WHERE `shortname` = 'personnel_api_curriculum_coord_show_all_staff' AND `organisation_id` IS NULL;
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
        if ($settings->read("personnel_api_curriculum_coord_show_all_staff") !== false) {
            return 1;
        }

        return 0;
    }
}