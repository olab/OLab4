<?php
class Migrate_2017_04_12_110900_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_targets` MODIFY COLUMN `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self', 'external_hash') NOT NULL DEFAULT 'proxy_id';
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
        ALTER TABLE `cbl_assessment_distribution_targets` MODIFY COLUMN `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self') NOT NULL DEFAULT 'proxy_id';
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
