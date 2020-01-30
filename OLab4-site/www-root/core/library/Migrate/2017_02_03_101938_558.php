<?php
class Migrate_2017_02_03_101938_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD `assessment_type_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `form_id`;
        ALTER TABLE `cbl_assessment_progress_responses` CHANGE `created_by` `created_by` INT(11)  NULL;
        ALTER TABLE `cbl_assessment_progress` CHANGE `created_by` `created_by` INT(11)  NULL;
        ALTER TABLE `cbl_assessment_form_elements` ADD INDEX (`element_type`, `element_id`);
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
        ALTER TABLE `cbl_distribution_assessments` DROP `assessment_type_id`;
        ALTER TABLE `cbl_assessment_progress` CHANGE `created_by` `created_by` INT(11) NOT NULL;
        ALTER TABLE `cbl_assessment_progress_responses` CHANGE `created_by` `created_by` INT(11) NOT NULL;
        ALTER TABLE `cbl_assessment_form_elements` DROP INDEX (`element_type`);
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
