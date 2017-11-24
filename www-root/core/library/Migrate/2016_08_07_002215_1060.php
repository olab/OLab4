<?php
class Migrate_2016_08_07_002215_1060 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `bookmarks` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `uri` varchar(255) NOT NULL DEFAULT '',
            `bookmark_title` varchar(255) DEFAULT NULL,
            `proxy_id` int(11) DEFAULT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `order` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
        DROP TABLE `bookmarks`;
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
        if ($migration->tableExists(DATABASE_NAME, "bookmarks")) {
            return 1;
        }
        return 0;
    }
}
