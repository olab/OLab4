<?php
class Migrate_2016_07_13_162523_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `secure_access_keys`
        CHANGE `version` `version` varchar(64) DEFAULT NULL;
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
        ALTER TABLE `secure_access_keys`
        CHANGE `version` `version` varchar(8) DEFAULT NULL;
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
        global $db;
        $query1 = "
            SELECT 1
            FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = ".$db->qstr(DATABASE_NAME)."
            AND `TABLE_NAME` = 'secure_access_keys'
            AND `COLUMN_NAME` = 'version'
            AND `CHARACTER_MAXIMUM_LENGTH` = '64'";

        if ($db->GetOne($query1)) {
            return 1;
        }
    }
}
