<?php
class Migrate_2017_01_18_131738_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `assessment_statistics` CHANGE `distribution_id` `distribution_id` VARCHAR(64)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT '';
        ALTER TABLE `cbl_assessment_additional_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_deleted_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_progress` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_progress_responses` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_ss_current_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_ss_future_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;

        ALTER TABLE `cbl_distribution_assessments` ADD `form_id` INT(11)  UNSIGNED  NULL  DEFAULT NULL  AFTER `dassessment_id`;
        ALTER TABLE `cbl_assessment_progress` CHANGE `adtarget_id` `adtarget_id` INT(11)  UNSIGNED  NULL;

        UPDATE `cbl_distribution_assessments` a
        JOIN `cbl_assessment_distributions` b ON a.`adistribution_id` = b.`adistribution_id`
        SET a.`form_id` = b.`form_id`;
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
        ALTER TABLE `assessment_statistics` CHANGE `distribution_id` `distribution_id` VARCHAR(64)  CHARACTER SET utf8  COLLATE utf8_general_ci NOT NULL DEFAULT '';
        ALTER TABLE `cbl_assessment_additional_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_deleted_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_progress` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_progress_responses` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_ss_current_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NOT NULL;
        ALTER TABLE `cbl_assessment_ss_future_tasks` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NOT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_progress` CHANGE `adtarget_id` `adtarget_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_distribution_assessments` DROP `form_id`;
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
