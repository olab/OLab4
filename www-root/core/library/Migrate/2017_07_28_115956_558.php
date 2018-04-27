<?php
class Migrate_2017_07_28_115956_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_meeting_files` (
        `meeting_file_id` int(12) NOT NULL AUTO_INCREMENT,
        `meeting_id` int(12) NOT NULL DEFAULT '0',
        `type` varchar(255) NOT NULL DEFAULT '',
        `size` varchar(32) NOT NULL DEFAULT '',
        `name` varchar(255) NOT NULL DEFAULT '',
        `title` varchar(255) NOT NULL DEFAULT '',
        `file_order` int(12) NOT NULL DEFAULT '0',
        `created_date` bigint(64) NOT NULL DEFAULT '0',
        `created_by` int(12) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) DEFAULT '0',
        `updated_by` int(12) DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT '0',
        `deleted_by` int(12) DEFAULT '0',
        PRIMARY KEY (`meeting_file_id`),
        KEY `meeting_id` (`meeting_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE IF EXISTS `cbl_meeting_files`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_meeting_files")) {
            return 1;
        }
        return 0;
    }
}
