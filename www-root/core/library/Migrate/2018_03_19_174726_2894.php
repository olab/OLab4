<?php
class Migrate_2018_03_19_174726_2894 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `settings` SET `value` = '111200' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.11.2' WHERE `shortname` = 'version_entrada';
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
        UPDATE `settings` SET `value` = '111100' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.11.1' WHERE `shortname` = 'version_entrada';
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
        return -1;
    }
}
