<?php
class Migrate_2018_02_20_122533_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_form_objectives` ADD INDEX `form_id` (`form_id`);
        ALTER TABLE `cbl_assessments_lu_items` ADD INDEX `item_code` (`item_code`);
        ALTER TABLE `global_lu_objective_sets` ADD INDEX `shortname` (`shortname`);
        ALTER TABLE `cbl_assessment_form_elements` ADD INDEX `form_id_2` (`form_id`, `element_type`);
        ALTER TABLE `cbl_assessment_rating_scale_responses` ADD INDEX `ardescriptor_id` (`ardescriptor_id`);
        ALTER TABLE `cbl_assessment_rating_scale_responses` ADD INDEX `weight` (`weight`);
        ALTER TABLE `cbl_assessment_type_organisations` ADD INDEX `assessment_type_id` (`assessment_type_id`);
        ALTER TABLE `cbl_assessment_type_organisations` ADD INDEX `organisation_id` (`organisation_id`);
        ALTER TABLE `cbl_assessment_type_organisations` ADD INDEX `organisation_id_2` (`organisation_id`, `assessment_type_id`);
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
        ALTER TABLE `cbl_assessment_form_objectives` DROP INDEX `form_id`;
        ALTER TABLE `cbl_assessments_lu_items` DROP INDEX `item_code`;
        ALTER TABLE `global_lu_objective_sets` DROP INDEX `shortname`;
        ALTER TABLE `cbl_assessment_form_elements` DROP INDEX `form_id_2`;
        ALTER TABLE `cbl_assessment_rating_scale_responses` DROP INDEX `ardescriptor_id`;
        ALTER TABLE `cbl_assessment_rating_scale_responses` DROP INDEX `weight`;
        ALTER TABLE `cbl_assessment_type_organisations` DROP INDEX `assessment_type_id`;
        ALTER TABLE `cbl_assessment_type_organisations` DROP INDEX `organisation_id`;
        ALTER TABLE `cbl_assessment_type_organisations` DROP INDEX `organisation_id_2`;
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
        $migrate = new Models_Migration();
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_form_objectives', 'form_id')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessments_lu_items', 'item_code')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'global_lu_objective_sets', 'shortname')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_form_elements', 'form_id_2')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_rating_scale_responses', 'ardescriptor_id')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_rating_scale_responses', 'weight')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_type_organisations', 'assessment_type_id')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_type_organisations', 'organisation_id')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_type_organisations', 'organisation_id_2')) {
            return 0;
        }
        return 1;
    }
}
