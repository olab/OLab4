<?php
class Migrate_2017_01_11_093418_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_item_groups` (
                `item_group_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
                `form_type_id` int(11) unsigned NOT NULL,
                `shortname` char(64) NOT NULL,
                `title` char(100) NOT NULL,
                `description` text NOT NULL,
                `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
                `created_date` bigint(64) unsigned NOT NULL,
                `created_by` int(12) unsigned NOT NULL,
                `updated_date` bigint(64) unsigned DEFAULT NULL,
                `updated_by` int(12) unsigned DEFAULT NULL,
                `deleted_date` bigint(64) unsigned DEFAULT NULL,
            PRIMARY KEY (`item_group_id`),
            KEY `form_type_id` (`form_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `cbl_assessments_lu_items`
            ADD `standard_item` TINYINT(1) NOT NULL DEFAULT '0' AFTER `itemtype_id`,
            ADD `item_group_id` INT(12) DEFAULT NULL AFTER `standard_item`,
            ADD INDEX (`item_group_id`);
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
        DROP TABLE `cbl_assessments_lu_item_groups`;
        ALTER TABLE `cbl_assessments_lu_items`
            DROP COLUMN `standard_item`,
            DROP COLUMN `item_group_id`;
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

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_item_groups")) {
            return 0;
        }
        if (!($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "standard_item"))) {
            return 0;
        }
        if (!($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "item_group_id"))) {
            return 0;
        }

        return 1;
    }
}
