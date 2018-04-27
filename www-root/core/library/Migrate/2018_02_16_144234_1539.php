<?php
class Migrate_2018_02_16_144234_1539 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_ss_existing_tasks` (
        `existing_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) DEFAULT NULL,
        `distribution_deleted_date` int(11) DEFAULT NULL,
        `distribution_title` VARCHAR(2048) DEFAULT NULL,
        `assessor_name` VARCHAR(100) DEFAULT NULL,
        `target_name` VARCHAR(100) DEFAULT NULL,
        `form_title` VARCHAR(1024) DEFAULT NULL,
        `schedule_details` VARCHAR(2048) DEFAULT NULL,
        `progress_details` VARCHAR(100) DEFAULT NULL,
        PRIMARY KEY (`existing_task_id`)
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
        DROP TABLE IF EXISTS `cbl_assessment_ss_existing_tasks`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_ss_existing_tasks")) {
            return 1;
        }
        return 0;
    }
}
