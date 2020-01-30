<?php
class Migrate_2017_01_16_081101_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessments_form_blueprint_item_templates` (
            `afb_item_template_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `form_type_id` int(12) unsigned NOT NULL,
            `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
            `parent_item` int(12) unsigned NOT NULL DEFAULT '0',
            `ordering` int(11) NOT NULL DEFAULT '1',
            `component_order` int(11) unsigned NOT NULL,
            `item_definition` text NOT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned DEFAULT NULL,
            `updated_by` int(12) unsigned DEFAULT NULL,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`afb_item_template_id`),
        KEY `blueprint_id` (`form_type_id`),
        KEY `parent_item` (`parent_item`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `cbl_assessments_lu_item_groups`
            ADD `item_type` ENUM('item','rubric') NOT NULL DEFAULT 'item' AFTER `form_type_id`;

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
        DROP TABLE `cbl_assessments_form_blueprint_item_templates`;
        ALTER TABLE `cbl_assessments_lu_item_groups` DROP `item_type`;
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

        if (!$migration->tableExists(DATABASE_NAME, "cbl_assessments_form_blueprint_item_templates")) {
            return 0;
        }
        if (!($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_item_groups", "item_type"))) {
            return 0;
        }

        return 1;
    }
}
