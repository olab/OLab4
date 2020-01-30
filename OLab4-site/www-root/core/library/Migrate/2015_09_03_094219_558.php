<?php
class Migrate_2015_09_03_094219_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_assessor_target_feedback` (
        `atfeedback_id` int(12) NOT NULL AUTO_INCREMENT,
        `dassessment_id` int(12) NOT NULL,
        `assessor_type` enum('internal','external') DEFAULT NULL,
        `assessor_value` int(11) DEFAULT NULL,
        `assessor_feedback` tinyint(1) DEFAULT NULL,
        `target_type` enum('internal','external') DEFAULT NULL,
        `target_value` int(11) DEFAULT NULL,
        `target_feedback` tinyint(1) DEFAULT NULL,
        `target_progress_value` enum('inprogress','complete') DEFAULT NULL,
        `comments` text DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`atfeedback_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
