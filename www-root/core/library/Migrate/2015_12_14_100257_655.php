<?php
class Migrate_2015_12_14_100257_655 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `settings` SET `value` = '16100' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.6.1' WHERE `shortname` = 'version_entrada';
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
        UPDATE `settings` SET `value` = '1633' WHERE `shortname` = 'version_db';
        UPDATE `settings` SET `value` = '1.6.0' WHERE `shortname` = 'version_entrada';
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
