<?php
class Migrate_2015_05_15_130638_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_schedule`
            MODIFY `schedule_type` enum('block','rotation','repeat','course_id','rotation_id') NOT NULL DEFAULT 'block';
        ALTER TABLE `cbl_assessment_distribution_targets`
            MODIFY `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self') NOT NULL DEFAULT 'proxy_id';
        ALTER TABLE `cbl_assessment_distribution_targets`
            MODIFY `target_id` int(11) DEFAULT NULL;
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
        ALTER TABLE `cbl_assessment_distribution_schedule`
        MODIFY `schedule_type` enum('schedule_id','schedule_parent_id','daily','weekly','monthly','yearly','course_id') NOT NULL DEFAULT 'schedule_id';
        ALTER TABLE `cbl_assessment_distribution_targets`
        MODIFY `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id') NOT NULL DEFAULT 'proxy_id';
        ALTER TABLE `cbl_assessment_distribution_targets`
        MODIFY `target_id` int(11) NOT NULL;
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
