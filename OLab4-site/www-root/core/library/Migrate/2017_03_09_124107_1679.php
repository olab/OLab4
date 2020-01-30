<?php
class Migrate_2017_03_09_124107_1679 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `<?php echo AUTH_DATABASE; ?>`.`user_relations` (
            `relation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `from` int(10) unsigned NOT NULL,
            `to` int(10) unsigned NOT NULL,
            `type` smallint(5) unsigned NOT NULL,
            PRIMARY KEY (`relation_id`),
            UNIQUE KEY `relation_unique` (`from`,`to`,`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `<?php echo AUTH_DATABASE; ?>`.`user_relations`;
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
        $migration = new Models_Migration();
        if ($migration->tableExists(AUTH_DATABASE, "user_relations")) {
            return 1;
        }

        return 0;
    }
}
