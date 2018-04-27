<?php
class Migrate_2017_03_07_152626_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_targets` ADD `task_type` ENUM('assessment','evaluation') NULL  DEFAULT 'assessment'  AFTER `adistribution_id`;
        UPDATE `cbl_distribution_assessment_targets` AS a JOIN `cbl_assessment_distributions` AS b ON b.`adistribution_id` = a.`adistribution_id` SET a.`task_type` = b.`assessment_type`;
        ALTER TABLE `cbl_assessment_additional_tasks` ADD `target_type` ENUM('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id','event_id') NOT NULL DEFAULT 'proxy_id' AFTER `target_id`;
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
        ALTER TABLE `cbl_distribution_assessment_targets` DROP `task_type`;
        ALTER TABLE `cbl_assessment_additional_tasks` DROP `target_type`;
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
        if (!$migrate->columnExists(DATABASE_NAME, "cbl_distribution_assessment_targets", "task_type")) {
            return 0;
        }
        if (!$migrate->columnExists(DATABASE_NAME, "cbl_assessment_additional_tasks", "target_type")) {
            return 0;
        }
        return 1;
    }
}
