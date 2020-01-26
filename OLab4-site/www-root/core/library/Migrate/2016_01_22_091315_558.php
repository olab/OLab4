<?php
class Migrate_2016_01_22_091315_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_additional_tasks` (
        `additional_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `target_id` int(11) NOT NULL,
        `assessor_value` int(11) unsigned NOT NULL,
        `assessor_type` enum('internal','external') DEFAULT NULL,
        `delivery_date` bigint(64) NOT NULL,
        `created_by`int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `deleted_by`int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`additional_task_id`),
        CONSTRAINT `cbl_assessment_additional_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
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
        DROP TABLE IF EXISTS `cbl_assessment_additional_tasks`;
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
        $query = "SHOW TABLES FROM " . DATABASE_NAME . " LIKE 'cbl_assessment_additional_tasks'";
        $table = $db->GetRow($query);
        if ($table) {
            return 1;
        } else {
            return 0;
        }
    }
}
