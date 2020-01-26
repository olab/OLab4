<?php
class Migrate_2017_02_23_100009_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_targets` ADD INDEX (`target_type`, `target_value`);
        ALTER TABLE `cbl_distribution_assessment_targets` ADD INDEX (`dassessment_id`);
        ALTER TABLE `cbl_assessment_deleted_tasks` ADD INDEX (`target_id`);
        ALTER TABLE `cbl_assessment_deleted_tasks` ADD INDEX (`assessor_type`, `assessor_value`);
        ALTER TABLE `cbl_assessment_additional_tasks` ADD INDEX (`target_id`);
        ALTER TABLE `cbl_assessment_additional_tasks` ADD INDEX (`assessor_type`, `assessor_value`);
        ALTER TABLE `cbl_schedule_audience` ADD INDEX (`audience_type`, `audience_value`);
        ALTER TABLE `cbl_schedule_audience` ADD INDEX (`schedule_id`);
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
        ALTER TABLE `cbl_distribution_assessment_targets` DROP INDEX `dassessment_id`;
        ALTER TABLE `cbl_distribution_assessment_targets` DROP INDEX `target_type`;
        ALTER TABLE `cbl_assessment_deleted_tasks` DROP INDEX `target_id`;
        ALTER TABLE `cbl_assessment_deleted_tasks` DROP INDEX `assessor_type`;
        ALTER TABLE `cbl_assessment_additional_tasks` DROP INDEX `target_id`;
        ALTER TABLE `cbl_assessment_additional_tasks` DROP INDEX `assessor_type`;
        ALTER TABLE `cbl_schedule_audience` DROP INDEX `audience_type`;
        ALTER TABLE `cbl_schedule_audience` DROP INDEX `schedule_id`;
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
        if ($migration->indexExists(DATABASE_NAME, "cbl_assessment_progress", "dassessment_id") && $migration->indexExists(DATABASE_NAME, "cbl_assessment_progress", "target_type") &&
            $migration->indexExists(DATABASE_NAME, "cbl_assessment_deleted_tasks", "target_id") && $migration->indexExists(DATABASE_NAME, "cbl_assessment_deleted_tasks", "assessor_type") &&
            $migration->indexExists(DATABASE_NAME, "cbl_assessment_additional_tasks", "target_id") && $migration->indexExists(DATABASE_NAME, "cbl_assessment_additional_tasks", "assessor_type") &&
            $migration->indexExists(DATABASE_NAME, "cbl_schedule_audience", "audience_type") && $migration->indexExists(DATABASE_NAME, "cbl_schedule_audience", "schedule_id")
        ) {
            return 1;
        }
        return 0;
    }
}
