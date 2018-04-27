<?php
class Migrate_2017_07_25_141505_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_academic_advisor_meetings` (
        `meeting_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `meeting_date` bigint(64) unsigned NOT NULL,
        `meeting_member_id` int(12) unsigned NOT NULL,
        `comment` text NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(12) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`meeting_id`)
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
        DROP TABLE IF EXISTS `cbl_academic_advisor_meetings`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_academic_advisor_meetings")) {
            return 1;
        }
        return 0;
    }
}
