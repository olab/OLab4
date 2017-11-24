<?php
class Migrate_2015_07_27_110335_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `proxy_id`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `assessor_type` enum('internal', 'external') NOT NULL DEFAULT 'internal' AFTER `adistribution_id`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `assessor_value` int(12) NOT NULL AFTER `assessor_type`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `external_hash` varchar(32) DEFAULT NULL AFTER `end_date`;
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
        -- SQL Downgrade Queries Here;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `proxy_id` int(12) NOT NULL AFTER `adistribution_id`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `assessor_type`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `assessor_value`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `external_hash`;
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

        $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'assessor_type'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'assessor_value'";
            $column2 = $db->GetRow($query);
            if ($column2) {
                $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'external_hash'";
                $column3 = $db->GetRow($query);
                if ($column3) {
                    $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'proxy_id'";
                    $column4 = $db->GetRow($query);
                    if (!$column4) {
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
