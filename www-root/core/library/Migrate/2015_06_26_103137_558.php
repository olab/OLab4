<?php
class Migrate_2015_06_26_103137_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_distribution_assessment_assessors` (
        `aassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `assessor_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id') NOT NULL DEFAULT 'proxy_id',
        `assessor_value` int(11) DEFAULT NULL,
        `delegation_list_id` int(11) DEFAULT NULL,
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`aassessor_id`),
        CONSTRAINT `cbl_distribution_assessment_assessors_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_distribution_assessment_targets` (
        `atarget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `target_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id') NOT NULL DEFAULT 'proxy_id',
        `target_value` int(11) DEFAULT NULL,
        `delegation_list_id` int(11) DEFAULT NULL,
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`atarget_id`),
        CONSTRAINT `cbl_distribution_assessment_targets_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
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
        DROP TABLE `cbl_distribution_assessment_assessors`;
        DROP TABLE `cbl_distribution_assessment_targets`;
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
