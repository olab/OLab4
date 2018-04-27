<?php
class Migrate_2017_02_23_103108_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress` ADD `target_type` ENUM('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id','event_id')  NOT NULL  DEFAULT 'proxy_id'  AFTER `target_record_id`;

        UPDATE `cbl_assessment_progress` AS ap
        JOIN `cbl_assessment_distributions` AS d ON ap.`adistribution_id` = d.`adistribution_id`
        JOIN `cbl_assessment_distribution_targets` AS adt ON adt.`adistribution_id` = d.`adistribution_id`
        SET ap.`target_type` = 'schedule_id'
        WHERE adt.`target_type` = 'schedule_id'
        AND adt.`target_scope` = 'self';

        UPDATE `cbl_assessment_progress` AS ap
        JOIN `cbl_assessment_distributions` AS d ON ap.`adistribution_id` = d.`adistribution_id`
        JOIN `cbl_assessment_distribution_targets` AS adt ON adt.`adistribution_id` = d.`adistribution_id`
        SET ap.`target_type` = 'course_id'
        WHERE adt.`target_type` = 'course_id'
        AND adt.`target_scope` = 'self';

        UPDATE `cbl_assessment_progress` AS ap
        JOIN `cbl_assessment_distributions` AS d ON ap.`adistribution_id` = d.`adistribution_id`
        JOIN `cbl_assessment_distribution_targets` AS adt ON adt.`adistribution_id` = d.`adistribution_id`
        SET ap.`target_type` = 'event_id'
        WHERE adt.`target_type` = 'eventtype_id'
        AND adt.`target_scope` = 'self'
        AND adt.`target_role` = 'any';
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
        ALTER TABLE `cbl_assessment_progress` DROP `target_type`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_progress", "target_type")) {
            return 1;
        }
        return 0;
    }
}