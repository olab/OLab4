<?php
class Migrate_2017_07_11_113433_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `course_settings` (
        `csetting_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `course_id` int(12) unsigned NOT NULL,
        `organisation_id` int(12) unsigned NOT NULL,
        `shortname` varchar(128) NOT NULL,
        `value` text NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(12) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`csetting_id`),
        KEY `course_id` (`course_id`),
        KEY `course_organisation` (`course_id`,`organisation_id`),
        KEY `shortname` (`shortname`),
        KEY `course_shortname` (`course_id`,`shortname`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE IF EXISTS `course_settings`;
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
        if ($migration->tableExists(DATABASE_NAME, "course_settings")) {
            return 1;
        }
        return 0;
    }
}
