<?php
class Migrate_2016_07_11_141024_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `target_type` `target_type` ENUM('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id','event_id')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'proxy_id';
        ALTER TABLE `cbl_distribution_assessments` ADD `associated_record_id` INT(11)  UNSIGNED  NULL  DEFAULT NULL  AFTER `assessor_value`;
        ALTER TABLE `cbl_distribution_assessments` ADD `associated_record_type` ENUM('event_id','proxy_id','course_id','group_id','schedule_id')  NULL  DEFAULT NULL  AFTER `associated_record_id`;
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
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `target_type` `target_type` ENUM('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'proxy_id';
        ALTER TABLE `cbl_distribution_assessments` DROP `associated_record_id`;
        ALTER TABLE `cbl_distribution_assessments` DROP `associated_record_type`;
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
        if (!$migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "associated_record_id") || !$migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "associated_record_type")) {
            return 0;
        }

        $meta = $migration->fieldMetadata(DATABASE_NAME, "cbl_distribution_assessment_targets", "target_type");
        if (empty($meta)) {
            return 0;
        } else {
            if (!strstr($meta["Type"], "'event_id'")) {
                return 0;
            }
        }

        return 1;
    }
}
