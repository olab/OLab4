<?php
class Migrate_2015_04_23_213148_701 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `lrs_history` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `type` varchar(43) NOT NULL DEFAULT '',
            `run_last` bigint(64) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`) VALUES
        (NULL, 'lrs_endpoint', NULL, ''),
        (NULL, 'lrs_version', NULL, ''),
        (NULL, 'lrs_username', NULL, ''),
        (NULL, 'lrs_password', NULL, '');
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
        DROP TABLE IF EXISTS `lrs_history`;

        DELETE FROM `settings` WHERE `shortname` IN ('lrs_endpoint', 'lrs_version', 'lrs_username', 'lrs_password') AND `organisation_id` IS NULL;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or not complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        $migration = new Models_Migration();
        if ($migration->tableExists(DATABASE_NAME, "lrs_history")) {
            $settings = new Entrada_Settings;
            if (($settings->read("lrs_endpoint") !== false) && ($settings->read("lrs_version") !== false) && ($settings->read("lrs_username") !== false) && ($settings->read("lrs_password") !== false)) {
                return 1;
            }
        }

        return 0;
    }
}
