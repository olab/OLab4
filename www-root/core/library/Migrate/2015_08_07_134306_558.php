<?php
class Migrate_2015_08_07_134306_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `assessor_type` enum('internal', 'external') NOT NULL DEFAULT 'internal' AFTER `adistribution_id`;
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `assessor_value` int(12) NOT NULL AFTER `assessor_type`;
        UPDATE `cbl_assessment_progress`
        SET `assessor_value` = `proxy_id`
        WHERE `assessor_value` = 0;
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `proxy_id`;

        ALTER TABLE `cbl_assessment_progress_responses` ADD COLUMN `assessor_type` enum('internal', 'external') NOT NULL DEFAULT 'internal' AFTER `adistribution_id`;
        ALTER TABLE `cbl_assessment_progress_responses` ADD COLUMN `assessor_value` int(12) NOT NULL AFTER `assessor_type`;
        UPDATE `cbl_assessment_progress_responses`
        SET `assessor_value` = `proxy_id`
        WHERE `assessor_value` = 0;
        ALTER TABLE `cbl_assessment_progress_responses` DROP COLUMN `proxy_id`;
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
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `proxy_id` int(12) NOT NULL AFTER `adistribution_id`;
        UPDATE `cbl_assessment_progress`
        SET `proxy_id` = `assessor_value`
        WHERE `proxy_id` = 0;
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `assessor_type`;
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `assessor_value`;

        ALTER TABLE `cbl_assessment_progress_responses` ADD COLUMN `proxy_id` int(12) NOT NULL AFTER `adistribution_id`;
        UPDATE `cbl_assessment_progress_responses`
        SET `proxy_id` = `assessor_value`
        WHERE `proxy_id` = 0;
        ALTER TABLE `cbl_assessment_progress_responses` DROP COLUMN `assessor_type`;
        ALTER TABLE `cbl_assessment_progress_responses` DROP COLUMN `assessor_value`;
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
        $query = "SHOW COLUMNS FROM `cbl_assessment_progress` LIKE 'assessor_type'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_assessment_progress` LIKE 'assessor_value'";
            $column2 = $db->GetRow($query);
            if ($column2) {
                $query = "SHOW COLUMNS FROM `cbl_assessment_progress_responses` LIKE 'assessor_type'";
                $column3 = $db->GetRow($query);
                if ($column3) {
                    $query = "SHOW COLUMNS FROM `cbl_assessment_progress_responses` LIKE 'assessor_value'";
                    $column4 = $db->GetRow($query);
                    if ($column4) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
