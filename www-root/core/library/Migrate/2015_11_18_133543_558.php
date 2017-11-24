<?php
class Migrate_2015_11_18_133543_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `<?php echo AUTH_DATABASE; ?>`.`user_mobile_data` (
        `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
        `hash` varchar(64) DEFAULT NULL,
        `hash_expires` bigint(64) NOT NULL DEFAULT '0',
        `push_notifications` tinyint(1) NOT NULL DEFAULT '1',
        `created_by` int(12) unsigned NOT NULL DEFAULT '1',
        `created_date` bigint(64) NOT NULL DEFAULT '0',
        `updated_by` int(12) NOT NULL DEFAULT '1',
        `updated_date` bigint(64) NOT NULL DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
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
        DROP TABLE `<?php echo AUTH_DATABASE; ?>`.`user_mobile_data`;
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
