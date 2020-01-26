<?php
class Migrate_2015_02_24_160446_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`cbl_assessments_lu_itemtypes` MODIFY COLUMN `shortname` varchar(128) NOT NULL DEFAULT '';
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`cbl_assessments_lu_itemtypes` MODIFY COLUMN `name` varchar(256) NOT NULL DEFAULT '';

        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'horizontal_multiple_choice_single', `name` = 'Horizontal Multiple Choice (single response)' WHERE `itemtype_id` = 1;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'vertical_multiple_choice_single', `name` = 'Vertical Multiple Choice (single response)' WHERE `itemtype_id` = 2;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'horizontal_multile_choice_multiple', `name` = 'Horizontal Multiple Choice (multiple responses)' WHERE `itemtype_id` = 4;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'vertical_multiple_choice_multiple', `name` = 'Vertical Multiple Choice (multiple responses)' WHERE `itemtype_id` = 5;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`cbl_assessments_lu_itemtypes` MODIFY COLUMN `shortname` varchar(64) NOT NULL DEFAULT '';
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`cbl_assessments_lu_itemtypes` MODIFY COLUMN `name` varchar(128) NOT NULL DEFAULT '';

        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'horizontal_matrix_single', `name` = 'Horizontal Choice Matrix (single response)' WHERE `itemtype_id` = 1;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'vertical_matrix_single', `name` = 'Vertical Choice Matrix (single response)' WHERE `itemtype_id` = 2;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'horizontal_matrix_multiple', `name` = 'Horizontal Choice Matrix (multiple responses)' WHERE `itemtype_id` = 4;
        UPDATE `cbl_assessments_lu_itemtypes` SET `shortname` = 'vertical_matrix_multiple', `name` = 'Vertical Choice Matrix (multiple responses)' WHERE `itemtype_id` = 5;
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
