<?php
class Migrate_2016_06_06_114233_885 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE  `assessments` ADD `notify_threshold` INT( 1 ) NOT NULL DEFAULT  '0' AFTER  `grade_threshold`;

        ALTER TABLE  `assessments` ADD `published` INT( 1 ) NOT NULL DEFAULT '1';

        CREATE TABLE IF NOT EXISTS `assessment_notificatons` (
            `at_notificaton_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `assessment_id` int(12) unsigned NOT NULL,
            `proxy_id` int(12) unsigned NOT NULL,
            `updated_date` bigint(64) unsigned NOT NULL,
            `updated_by` int(12) unsigned NOT NULL,
            PRIMARY KEY (`at_notificaton_id`),
            KEY `assessment_id` (`assessment_id`,`proxy_id`,`updated_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Notification list for assessments';
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
        ALTER TABLE  `assessments` DROP `notify_threshold`;

        ALTER TABLE  `assessments` DROP `published`;

        DROP TABLE `assessment_notificatons`;
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
        if ($migration->columnExists(DATABASE_NAME, "assessments", "notify_threshold")) {
            if ($migration->columnExists(DATABASE_NAME, "assessments", "published")) {
                if ($migration->tableExists(DATABASE_NAME, "assessment_notificatons")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
