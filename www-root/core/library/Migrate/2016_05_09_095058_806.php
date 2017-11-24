<?php
class Migrate_2016_05_09_095058_806 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (
            `setting_id` ,
            `shortname` ,
            `organisation_id` ,
            `value`
        )
        VALUES
            (NULL ,  'caliper_endpoint', NULL,  ''),
            (NULL ,  'caliper_sensor_id', NULL,  ''),
            (NULL ,  'caliper_api_key', NULL,  ''),
            (NULL ,  'caliper_debug', NULL,  '0');
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
        DELETE FROM `settings`
        WHERE `shortname` = 'caliper_endpoint'
            OR `shortname` = 'caliper_sensor_id'
            OR `shortname` = 'caliper_api_key'
            OR `shortname` = 'caliper_debug';
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
        if ((Entrada_Settings::read("caliper_endpoint") !== false) && (Entrada_Settings::read("caliper_sensor_id") !== false) && (Entrada_Settings::read("caliper_api_key") !== false) && (Entrada_Settings::read("caliper_debug") !== false)) {
            return 1;
        }

        return 0;
    }
}
