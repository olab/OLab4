<?php
class Migrate_2017_06_30_091900_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbme_course_objectives` ADD INDEX `objective_id` (`objective_id`);
        ALTER TABLE `cbl_assessment_form_objectives` ADD INDEX `objective_id_organisation_id` (`objective_id`, `organisation_id`);
        ALTER TABLE `cbl_assessment_form_objectives` ADD INDEX `objective_id` (`objective_id`);
        ALTER TABLE `cbl_distribution_assessments` ADD INDEX `form_id` (`form_id`);
        ALTER TABLE `cbl_assessment_progress` ADD INDEX `assessor_type_value` (`assessor_type`, `assessor_value`);
        ALTER TABLE `cbl_assessment_progress` ADD INDEX `dassessment_values` (`dassessment_id`, `progress_value`, `target_record_id`, `assessor_type`, `assessor_value`);
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
        ALTER TABLE `cbme_course_objectives` DROP INDEX `objective_id`;
        ALTER TABLE `cbl_assessment_form_objectives` DROP INDEX `objective_id_organisation_id`;
        ALTER TABLE `cbl_assessment_form_objectives` DROP INDEX `objective_id`;
        ALTER TABLE `cbl_distribution_assessments` DROP INDEX `form_id`;
        ALTER TABLE `cbl_assessment_progress` DROP INDEX `assessor_type_value`;
        ALTER TABLE `cbl_assessment_progress` DROP INDEX `dassessment_values`;
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
        global $db;
        $schema_query = "SELECT * FROM INFORMATION_SCHEMA.`STATISTICS` a WHERE a.`TABLE_NAME` = ? AND a.`INDEX_NAME` = ?";
        $res = $db->GetAll($schema_query, array("cbl_assessment_form_objectives", "objective_id_organisation_id"));
        if (empty($res)) {
            return 0;
        }
        $res = $db->GetAll($schema_query, array("cbl_assessment_form_objectives", "objective_id"));
        if (empty($res)) {
            return 0;
        }
        $res = $db->GetAll($schema_query, array("cbme_course_objectives", "objective_id"));
        if (empty($res)) {
            return 0;
        }
        $res = $db->GetAll($schema_query, array("cbl_assessment_progress", "assessor_type_value"));
        if (empty($res)) {
            return 0;
        }
        $res = $db->GetAll($schema_query, array("cbl_distribution_assessments", "form_id"));
        if (empty($res)) {
            return 0;
        }
        $res = $db->GetAll($schema_query, array("cbl_assessment_progress", "dassessment_values"));
        if (empty($res)) {
            return 0;
        }
        return 1;
    }
}
