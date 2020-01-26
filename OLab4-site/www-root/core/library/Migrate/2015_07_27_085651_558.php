<?php
class Migrate_2015_07_27_085651_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_assessors` ADD COLUMN `dassessment_id` int(12) NOT NULL AFTER `aassessor_id`;
        ALTER TABLE `cbl_distribution_assessment_targets` ADD COLUMN `dassessment_id` int(12) NOT NULL AFTER `atarget_id`;
        ALTER TABLE `cbl_assessment_notifications` ADD COLUMN `notified_value` int(11) unsigned NOT NULL AFTER `adistribution_id`;
        ALTER TABLE `cbl_assessment_notifications` ADD COLUMN `notified_type` enum('proxy_id','external_assessor_id') NOT NULL DEFAULT 'proxy_id' AFTER `notified_value`;
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
        ALTER TABLE `cbl_distribution_assessment_assessors` DROP COLUMN `dassessment_id`;
        ALTER TABLE `cbl_distribution_assessment_targets` DROP COLUMN `dassessment_id`;
        ALTER TABLE `cbl_assessment_notifications` DROP COLUMN `notified_value`;
        ALTER TABLE `cbl_assessment_notifications` DROP COLUMN `notified_type`;
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
        $query = "SHOW COLUMNS FROM `cbl_distribution_assessment_assessors` LIKE 'dassessment_id'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_distribution_assessment_targets` LIKE 'dassessment_id'";
            $column2 = $db->GetRow($query);
            if ($column2) {
                $query = "SHOW COLUMNS FROM `cbl_assessment_notifications` LIKE 'notified_value'";
                $column3 = $db->GetRow($query);
                if ($column3) {
                    return 1;
                } else {
                    $query = "SHOW COLUMNS FROM `cbl_assessment_notifications` LIKE 'notified_type'";
                    $column4 = $db->GetRow($query);
                    if ($column4) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
