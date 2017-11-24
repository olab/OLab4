<?php
class Migrate_2016_06_13_114418_810 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `resource_images` (
        `image_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `resource_type` enum('course','track','objective') NOT NULL DEFAULT 'course',
        `resource_id` int(12) unsigned NOT NULL DEFAULT '0',
        `image_mimetype` varchar(64) DEFAULT NULL,
        `image_filesize` int(32) NOT NULL DEFAULT '0',
        `image_active` int(1) NOT NULL DEFAULT '1',
        `updated_date` bigint(64) NOT NULL DEFAULT '0',
        PRIMARY KEY (`image_id`),
        UNIQUE KEY `resource_id` (`resource_id`,`resource_type`)
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
        DROP TABLE IF EXISTS `resource_images`;
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

        if ($migration->tableExists(DATABASE_NAME, "resource_images")) {
            return 1;
        }

        return 0; // they don't exist
    }
}
