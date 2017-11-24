<?php
class Migrate_2016_05_02_152214_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_itemtypes` ADD COLUMN `classname` varchar(128) NULL AFTER `shortname`;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'HorizontalMultipleChoiceSingleResponse' where `itemtype_id` = 1;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'VerticalMultipleChoiceSingleResponse' where `itemtype_id` = 2;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'DropdownSingleResponse' where `itemtype_id` = 3;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'HorizontalMultipleChoiceMultipleResponse' where `itemtype_id` = 4;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'VerticalMultipleChoiceMultipleResponse' where `itemtype_id` = 5;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'DropdownMultipleResponse' where `itemtype_id` = 6;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'FreeText' where `itemtype_id` = 7;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'Date' where `itemtype_id` = 8;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'User' where `itemtype_id` = 9;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'Numeric' where `itemtype_id` = 10;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'RubricLine' where `itemtype_id` = 11;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'Scale' where `itemtype_id` = 12;
        UPDATE `cbl_assessments_lu_itemtypes` SET classname = 'FieldNote' where `itemtype_id` = 13;
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
        ALTER TABLE `cbl_assessments_lu_itemtypes` DROP COLUMN `classname`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_itemtypes", "classname")) {
            return 1;
        }

        return 0;
    }
}
