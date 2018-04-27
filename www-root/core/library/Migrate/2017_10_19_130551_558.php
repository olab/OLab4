<?php
class Migrate_2017_10_19_130551_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_lu_methods` ADD `phases` INT  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `order`;

        UPDATE `cbl_assessment_lu_methods` SET `phases` = 2 WHERE `shortname` = 'complete_and_confirm_by_email' OR `shortname` = 'complete_and_confirm_by_pin';

        INSERT INTO `cbl_assessment_lu_methods` (`shortname`, `order`, `created_date`, `created_by`, `updated_by`, `updated_date`, `deleted_date`)
        VALUES
        ('double_blind_assessment', 4, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL),
        ('faculty_triggered_assessment', 0, UNIX_TIMESTAMP(), 1, NULL, NULL, NULL);

        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`)
        VALUES
        ('assessment_tasks_show_all_multiphase_assessments', NULL, '0');

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
        ALTER TABLE `cbl_assessment_lu_methods` DROP `phases`;
        DELETE FROM `cbl_assessment_lu_methods` WHERE `shortname` = 'double_blind_assessment' OR `shortname` = 'faculty_triggered_assessment';
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_lu_methods", "phases")) {
            return 1;
        }
        return 0;
    }
}
