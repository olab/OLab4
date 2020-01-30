<?php
class Migrate_2015_11_18_091429_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `created_date` BIGINT(64) NOT NULL AFTER `external_hash`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `created_by` INT(11) NOT NULL AFTER `created_date`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `updated_date` BIGINT(64) DEFAULT NULL AFTER `created_by`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `updated_by` INT(11) DEFAULT NULL AFTER `updated_date`;
        ALTER TABLE `cbl_assessment_progress_responses` ADD COLUMN `deleted_date` BIGINT(64) DEFAULT NULL AFTER `updated_by`;
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `uuid` varchar(36) NOT NULL AFTER `dassessment_id`;
        UPDATE `cbl_assessment_progress` SET `uuid` = UUID();
        UPDATE `cbl_distribution_assessments` SET `created_date` = 1;
        UPDATE `cbl_distribution_assessments` SET `created_by` = 1;
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
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `created_date`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `created_by`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `updated_date`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `updated_by`;
        ALTER TABLE `cbl_assessment_progress_responses` DROP COLUMN `deleted_date`;
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `uuid`;
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
        $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'created_date'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'created_by'";
            $second_column = $db->GetRow($query);
            if ($second_column) {
                $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'updated_date'";
                $third_column = $db->GetRow($query);
                if ($third_column) {
                    $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'updated_by'";
                    $fourth_column = $db->GetRow($query);
                    if ($fourth_column) {
                        $query = "SHOW COLUMNS FROM `cbl_assessment_progress_responses` LIKE 'deleted_date'";
                        $fifth_column = $db->GetRow($query);
                        if ($fifth_column) {
                            $query = "SHOW COLUMNS FROM `cbl_assessment_progress` LIKE 'uuid'";
                            $sixth_column = $db->GetRow($query);
                            if ($sixth_column) {
                                return 1;
                            }
                        }
                    }
                }
            }
        }
        return 0;
    }
}
