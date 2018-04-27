<?php
class Migrate_2017_06_02_093520_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessments_form_statistics` (
            `afstatistic_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `course_id` int(11) unsigned DEFAULT NULL,
            `form_id` int(11) unsigned NOT NULL,
            `proxy_id` int(11) unsigned NOT NULL,
            `count` int(11) unsigned NOT NULL,
            PRIMARY KEY (`afstatistic_id`),
            KEY `form_id` (`course_id`, `form_id`,`proxy_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `cbl_assessment_progress` ADD `progress_time` BIGINT(64) UNSIGNED NULL DEFAULT NULL AFTER `progress_value`;
        ALTER TABLE `cbl_assessment_lu_types` ADD INDEX (`shortname`);
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
        DROP TABLE `cbl_assessments_form_statistics`;

        ALTER TABLE `cbl_assessment_progress` DROP `progress_time`;
        ALTER TABLE `cbl_assessment_lu_types` DROP INDEX `shortname`;

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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessments_form_statistics")) {
            if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_progress", "progress_time")) {
                return 1;
            }
        }

        return 0;
    }
}
