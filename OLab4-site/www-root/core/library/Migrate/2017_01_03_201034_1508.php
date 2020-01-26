<?php
class Migrate_2017_01_03_201034_1508 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `settings` SET `value` = '18000' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.8.0' WHERE `shortname` = 'version_entrada';
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
        UPDATE `settings` SET `value` = '17500' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.7.5' WHERE `shortname` = 'version_entrada';
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
        $settings = new Entrada_Settings;
        if (($settings->read("version_db") >= 18000) && version_compare("1.8.0", $settings->read("version_entrada"), "<=")) {
            return 1;
        }

        return 0;
    }
}
