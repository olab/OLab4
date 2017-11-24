<?php
class Migrate_2016_05_26_132210_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_notifications` ADD `assessment_type` ENUM('assessment','delegation') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'assessment' AFTER `dassessment_id`;
        ALTER TABLE `cbl_assessment_notifications` CHANGE `dassessment_id` `assessment_value` INT(11) UNSIGNED NOT NULL;
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
        ALTER TABLE `cbl_assessment_notifications` CHANGE `assessment_value` `dassessment_id` INT(11)  UNSIGNED  NOT NULL;
        ALTER TABLE `cbl_assessment_notifications` DROP `assessment_type`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_notifications", "assessment_type") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessment_notifications", "assessment_value")) {
            return 1;
        }
        return 0;
    }
}
