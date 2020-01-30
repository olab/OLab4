<?php
class Migrate_2016_10_05_085608_1184 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        ALTER TABLE `assessments` ADD COLUMN `collection_id` int(10) NULL DEFAULT NULL AFTER `cperiod_id`;
        CREATE TABLE `assessment_collections` (
            `collection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(128) NOT NULL, 
            `description` TEXT NOT NULL, 
            `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
             PRIMARY KEY(`collection_id`)
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
        -- SQL Downgrade Queries Here;
        DROP TABLE `assessment_collections`;
        ALTER TABLE `assessments`DROP COLUMN `collection_id`;
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

        if ($migration->columnExists(DATABASE_NAME, "assessments", "collection_id") && $migration->tableExists(DATABASE_NAME, "assessment_collections")) {
            return 1;
        } else {
            return 0;
        }
    }
}
