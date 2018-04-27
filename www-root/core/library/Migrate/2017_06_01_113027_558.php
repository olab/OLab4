<?php
class Migrate_2017_06_01_113027_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_form_blueprints` ADD `complete` TINYINT(4)  UNSIGNED  NULL  DEFAULT '0'  AFTER `active`;
        ALTER TABLE `cbl_assessments_lu_form_blueprints` ADD `organisation_id` INT(11)  UNSIGNED  NULL  DEFAULT NULL  AFTER `course_id`;
        UPDATE `cbl_assessments_lu_form_blueprints` SET `complete` = 1 WHERE `published` = 1;
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
        ALTER TABLE `cbl_assessments_lu_form_blueprints` DROP `complete`;
        ALTER TABLE `cbl_assessments_lu_form_blueprints` DROP `organisation_id`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprints", "complete")
            && $migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprints", "organisation_id")
        ) {
            return 1;
        }
        return 0;
    }
}
